<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ── Rate limit public form submissions (10 per IP per hour) ──
$_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (rate_limit_exceeded('apply:' . $_ip, 10, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many submissions. Please try again later.']);
    exit;
}
rate_limit_record('apply:' . $_ip);

// ── Image re-processing helper ────────────────────────────────
function _reprocess_image(string $src, string $dest, string $ext): bool {
    if (!function_exists('imagecreatefromjpeg')) return false;
    try {
        $img = match($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($src),
            'png'         => @imagecreatefrompng($src),
            default       => false,
        };
        if (!$img) return false;
        $result = match($ext) {
            'jpg', 'jpeg' => imagejpeg($img, $dest, 90),
            'png'         => imagepng($img, $dest, 6),
            default       => false,
        };
        imagedestroy($img);
        return (bool)$result;
    } catch (Throwable $e) {
        return false;
    }
}

// ── Sanitise helpers ──────────────────────────────────────────
function str_clean(?string $v): string {
    return trim(strip_tags((string)$v));
}
function date_clean(?string $v): ?string {
    if (!$v) return null;
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return $d ? $d->format('Y-m-d') : null;
}

// ── Collect & validate text fields ───────────────────────────
$errors = [];

$first_name = str_clean($_POST['first_name'] ?? '');
$last_name  = str_clean($_POST['last_name']  ?? '');
$email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone      = str_clean($_POST['phone']      ?? '');
$dob        = date_clean($_POST['dob']       ?? '');
$gender     = in_array($_POST['gender'] ?? '', ['male','female','other']) ? $_POST['gender'] : 'male';

$occupation     = str_clean($_POST['occupation']     ?? '');
$employer       = str_clean($_POST['employer']       ?? '');
$funding_source = str_clean($_POST['funding']        ?? '');

$preferred_residence = str_clean($_POST['building']       ?? '');
$room_type           = str_clean($_POST['room_type']      ?? 'single');
$move_in_date        = date_clean($_POST['move_in_date']  ?? '');
$notes               = str_clean($_POST['notes']          ?? '');
$terms_accepted      = !empty($_POST['terms']) ? 1 : 0;

if (!$first_name)    $errors[] = 'First name is required.';
if (!$last_name)     $errors[] = 'Last name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if (!$phone)         $errors[] = 'Phone number is required.';
if (!$occupation)    $errors[] = 'Occupation is required.';
if (!$funding_source) $errors[] = 'Funding source is required.';
if (!$preferred_residence) $errors[] = 'Preferred residence is required.';
if (!$terms_accepted) $errors[] = 'You must accept the terms and conditions.';

// ── Validate & save uploaded documents ───────────────────────
$upload_dir    = dirname(__DIR__) . '/uploads/applications/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
$allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
$allowed_ext   = ['pdf', 'jpg', 'jpeg', 'png'];
$max_size      = 5 * 1024 * 1024; // 5 MB

// Define which documents are required based on occupation & funding
$is_student  = $occupation === 'student';
$is_bursary  = $funding_source === 'bursary';
$is_employer = $funding_source === 'employer';
$needs_income = !$is_bursary && !$is_employer;

$doc_fields = [
    'doc_id'          => ['label' => 'ID / Passport',         'required' => true],
    'doc_enrollment'  => ['label' => 'Proof of Enrollment',   'required' => $is_student],
    'doc_bursary'     => ['label' => 'Bursary Award Letter',  'required' => $is_bursary],
    'doc_sponsorship' => ['label' => 'Sponsorship Letter',    'required' => $is_employer],
    'doc_income'      => ['label' => 'Bank Statement/Payslip','required' => $needs_income],
];

$doc_paths = [];

foreach ($doc_fields as $field => $meta) {
    $file = $_FILES[$field] ?? null;

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
        if ($meta['required']) {
            $errors[] = $meta['label'] . ' is required.';
        }
        $doc_paths[$field] = null;
        continue;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = $meta['label'] . ': upload error, please try again.';
        $doc_paths[$field] = null;
        continue;
    }

    if ($file['size'] > $max_size) {
        $errors[] = $meta['label'] . ' exceeds the 5 MB file size limit.';
        $doc_paths[$field] = null;
        continue;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        $errors[] = $meta['label'] . ': only PDF, JPG or PNG files are accepted.';
        $doc_paths[$field] = null;
        continue;
    }

    // Verify MIME from file content (not trusting browser-supplied type)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime     = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_types)) {
        $errors[] = $meta['label'] . ': file type not permitted.';
        $doc_paths[$field] = null;
        continue;
    }

    // Store path for later (will move after DB insert gives us the application ID)
    $doc_paths[$field] = ['tmp' => $file['tmp_name'], 'ext' => $ext, 'original' => $file['name']];
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── Insert application ────────────────────────────────────────
try {
    $sql = 'INSERT INTO applications
        (first_name, last_name, email, phone, dob, gender,
         occupation, employer, funding_source,
         preferred_residence, room_type, move_in_date, notes, terms_accepted)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

    $stmt = db()->prepare($sql);
    $stmt->execute([
        $first_name, $last_name, $email, $phone, $dob, $gender,
        $occupation, $employer ?: null, $funding_source,
        $preferred_residence, $room_type, $move_in_date, $notes ?: null,
        $terms_accepted
    ]);

    $application_id = db()->lastInsertId();

    // ── Move files & record paths ─────────────────────────────
    $saved_paths = [];
    foreach ($doc_paths as $field => $info) {
        if (!$info) { $saved_paths[$field] = null; continue; }

        $filename    = bin2hex(random_bytes(16)) . '.' . $info['ext'];
        $destination = $upload_dir . $filename;

        $stored = false;
        if (in_array($info['ext'], ['jpg', 'jpeg', 'png'])) {
            // Re-encode images via GD to strip embedded payloads / metadata
            $stored = _reprocess_image($info['tmp'], $destination, $info['ext']);
        }
        if (!$stored) {
            $stored = move_uploaded_file($info['tmp'], $destination);
        }

        if ($stored) {
            $saved_paths[$field] = 'uploads/applications/' . $filename;
        } else {
            $saved_paths[$field] = null;
        }
    }

    // ── Update application with document paths ────────────────
    db()->prepare(
        'UPDATE applications SET
            doc_id_path          = ?,
            doc_enrollment_path  = ?,
            doc_bursary_path     = ?,
            doc_sponsorship_path = ?,
            doc_income_path      = ?
         WHERE id = ?'
    )->execute([
        $saved_paths['doc_id']          ?? null,
        $saved_paths['doc_enrollment']  ?? null,
        $saved_paths['doc_bursary']     ?? null,
        $saved_paths['doc_sponsorship'] ?? null,
        $saved_paths['doc_income']      ?? null,
        $application_id
    ]);

    // ── Send confirmation email to applicant ─────────────────────
    send_application_confirmation($email, $first_name, $last_name, $application_id, $preferred_residence, $move_in_date);

    // ── Notify admin of new application ──────────────────────────
    send_admin_new_application(
        $application_id, $first_name, $last_name,
        $email, $phone, $preferred_residence,
        $funding_source, $move_in_date
    );

    echo json_encode(['success' => true, 'message' => 'Application received successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again.']);
}

