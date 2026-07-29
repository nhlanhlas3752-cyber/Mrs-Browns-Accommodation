<?php
require_once dirname(__DIR__) . '/backend/auth.php';

if (is_logged_in()) {
    header('Location: /dashboard/overview.php');
    exit;
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (rate_limit_exceeded('reset:' . $ip, 3, 900)) {
        $error = 'Too many requests. Please wait 15 minutes before trying again.';
    } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        rate_limit_record('reset:' . $ip);
        $token = create_reset_token($email);
        if ($token) {
            $base_url  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $reset_url = $base_url . '/dashboard/reset-password.php?token=' . urlencode($token);

            $subject = 'Reset your Hilret Trust password';
            $body    = "Hi,\n\nYou requested a password reset for your Hilret Trust account.\n\n"
                     . "Click the link below to set a new password (expires in 1 hour):\n\n"
                     . $reset_url . "\n\n"
                     . "If you did not request this, you can ignore this email.\n\n"
                     . "— Hilret Trust";

            $headers = 'From: noreply@hilrettrust.co.za' . "\r\n"
                     . 'Reply-To: noreply@hilrettrust.co.za' . "\r\n"
                     . 'X-Mailer: PHP/' . phpversion();

            mail($email, $subject, $body, $headers);
        }
        // Always show success — never reveal if email is registered
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — Hilret Trust</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600&family=Dancing+Script:wght@600;700;800&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; font-family: 'Inter', sans-serif; overflow: hidden; }

.hero-bg {
  position: fixed; inset: 0;
  background: url('/IMG-20251001-WA0060.jpg') center center / cover no-repeat;
  animation: bgZoom 18s ease-in-out infinite alternate;
  z-index: 0;
}
@keyframes bgZoom {
  from { transform: scale(1);    filter: brightness(.85); }
  to   { transform: scale(1.06); filter: brightness(.75); }
}
.hero-overlay {
  position: fixed; inset: 0;
  background: linear-gradient(160deg, rgba(13,22,41,.82) 0%, rgba(13,22,41,.65) 40%, rgba(13,22,41,.78) 100%);
  z-index: 1;
}
.hero-overlay::after {
  content: ''; position: absolute; bottom: -60px; left: 50%; transform: translateX(-50%);
  width: 700px; height: 300px; border-radius: 50%;
  background: radial-gradient(ellipse, rgba(254,102,0,.18) 0%, transparent 70%);
  animation: glowBreathe 5s ease-in-out infinite;
}
@keyframes glowBreathe {
  0%,100% { opacity: .7; transform: translateX(-50%) scale(1); }
  50%      { opacity: 1;  transform: translateX(-50%) scale(1.12); }
}
.particles { position: fixed; inset: 0; z-index: 2; pointer-events: none; }
.pt { position: absolute; border-radius: 50%; animation: ptDrift linear infinite; }
@keyframes ptDrift {
  0%   { transform: translateY(100vh); opacity: 0; }
  6%   { opacity: 1; }
  94%  { opacity: 1; }
  100% { transform: translateY(-40px); opacity: 0; }
}
.page {
  position: relative; z-index: 10; min-height: 100vh;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 24px 16px; animation: pageFadeIn .6s ease both;
}
@keyframes pageFadeIn { from { opacity: 0; } to { opacity: 1; } }

.card {
  width: 100%; max-width: 440px;
  background: rgba(13,22,41,.82);
  backdrop-filter: blur(20px) saturate(1.4);
  -webkit-backdrop-filter: blur(20px) saturate(1.4);
  border: 1px solid rgba(255,255,255,.1); border-radius: 20px;
  padding: 44px 44px 36px;
  box-shadow: 0 32px 80px rgba(0,0,0,.55), 0 2px 0 rgba(255,255,255,.06) inset, 0 -1px 0 rgba(0,0,0,.4) inset;
  animation: cardRise .7s cubic-bezier(.22,1,.36,1) .1s both;
  position: relative;
}
@keyframes cardRise {
  from { transform: translateY(40px) scale(.97); opacity: 0; }
  to   { transform: translateY(0)    scale(1);   opacity: 1; }
}
.card::before {
  content: ''; position: absolute; top: 0; left: 44px; right: 44px; height: 2px;
  background: linear-gradient(90deg, transparent, #FE6600 30%, #E8A020 70%, transparent);
  border-radius: 99px;
  animation: lineReveal .8s cubic-bezier(.22,1,.36,1) .6s both;
}
@keyframes lineReveal {
  from { transform: scaleX(0); opacity: 0; }
  to   { transform: scaleX(1); opacity: 1; }
}

.logo-wrap { text-align: center; margin-bottom: 24px; animation: fadeSlideDown .5s ease .3s both; }
@keyframes fadeSlideDown {
  from { transform: translateY(-16px); opacity: 0; }
  to   { transform: translateY(0);     opacity: 1; }
}
.logo-hilret { display: block; font-family: 'Poppins', sans-serif; font-size: 2rem; font-weight: 900; color: #FE6600; text-transform: uppercase; letter-spacing: 3px; line-height: 1; }
.logo-trust  { display: block; font-family: 'Dancing Script', cursive; font-size: 1.6rem; font-weight: 700; color: rgba(255,255,255,.7); line-height: 1.2; }
.logo-swoosh { display: block; width: 110px; height: 10px; margin: 4px auto 0; overflow: visible; }
.logo-swoosh path { stroke-dasharray: 200; stroke-dashoffset: 200; animation: drawSwoosh 1s cubic-bezier(.22,1,.36,1) .8s forwards; }
@keyframes drawSwoosh { from { stroke-dashoffset: 200; } to { stroke-dashoffset: 0; } }

.icon-ring {
  width: 56px; height: 56px; border-radius: 50%;
  background: rgba(254,102,0,.12); border: 1.5px solid rgba(254,102,0,.3);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px;
  animation: fadeSlideDown .5s ease .35s both;
}

.sign-in-head { text-align: center; margin-bottom: 6px; animation: fadeSlideUp .5s ease .4s both; }
.sign-in-head h1 { font-family: 'Poppins', sans-serif; font-size: 1.6rem; font-weight: 900; color: #fff; letter-spacing: -.4px; line-height: 1; }
.sign-in-head h1 em { font-style: normal; color: #FE6600; }
.sign-in-sub { text-align: center; color: rgba(255,255,255,.45); font-size: .82rem; margin-bottom: 24px; line-height: 1.6; animation: fadeSlideUp .5s ease .45s both; }
@keyframes fadeSlideUp {
  from { transform: translateY(14px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}
.divider { height: 1px; background: rgba(255,255,255,.08); margin: 0 0 20px; animation: fadeSlideUp .5s ease .48s both; }

.lp-error {
  display: flex; align-items: center; gap: 9px;
  background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.4); border-left: 3px solid #EF4444;
  color: #FCA5A5; padding: 10px 14px; border-radius: 8px; font-size: .79rem; font-weight: 500;
  margin-bottom: 16px; animation: shake .45s cubic-bezier(.36,.07,.19,.97) both;
}
@keyframes shake {
  0%,100% { transform: translateX(0); }
  15%  { transform: translateX(-7px); }
  30%  { transform: translateX(7px); }
  45%  { transform: translateX(-5px); }
  60%  { transform: translateX(5px); }
  75%  { transform: translateX(-3px); }
  90%  { transform: translateX(3px); }
}

.lp-success {
  display: flex; flex-direction: column; align-items: center; gap: 12px;
  background: rgba(39,174,96,.12); border: 1px solid rgba(39,174,96,.35); border-left: 3px solid #27AE60;
  color: #6ee7a4; padding: 18px 14px; border-radius: 8px; font-size: .83rem; text-align: center;
  margin-bottom: 16px;
}
.lp-success strong { font-size: .9rem; font-weight: 700; color: #86efac; }


.fields { animation: fadeSlideUp .5s ease .5s both; }
.fl-field { position: relative; margin-bottom: 16px; }
.fl-field input {
  width: 100%; padding: 20px 46px 8px 16px;
  background: rgba(255,255,255,.07); border: 1.5px solid rgba(255,255,255,.12);
  border-radius: 10px; font-family: 'Inter', sans-serif; font-size: .9rem; color: #fff;
  outline: none; caret-color: #FE6600;
  transition: border-color .2s, background .2s, box-shadow .2s;
}
.fl-field input::placeholder { color: transparent; }
.fl-field input:focus { border-color: #FE6600; background: rgba(254,102,0,.07); box-shadow: 0 0 0 3px rgba(254,102,0,.15); }
.fl-field label {
  position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
  font-size: .88rem; color: rgba(255,255,255,.38); pointer-events: none;
  transition: top .18s ease, transform .18s ease, font-size .18s ease, color .18s ease;
  transform-origin: left center;
}
.fl-field input:focus ~ label,
.fl-field input:not(:placeholder-shown) ~ label {
  top: 9px; transform: translateY(0) scale(.74); color: #FE6600; font-weight: 700;
}
.fl-icon { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,.25); pointer-events: none; }
.fl-field input:focus ~ .fl-icon { color: #FE6600; }

.submit-wrap { animation: fadeSlideUp .5s ease .58s both; }
.lp-submit {
  width: 100%; padding: 14px; border: none; border-radius: 10px;
  font-family: 'Poppins', sans-serif; font-size: .92rem; font-weight: 800; letter-spacing: .4px;
  cursor: pointer; position: relative; overflow: hidden; color: #fff;
  background: linear-gradient(110deg, #FE6600 0%, #E8A020 50%, #FE6600 100%);
  background-size: 200% auto;
  transition: background-position .45s ease, transform .1s, box-shadow .2s;
  box-shadow: 0 4px 20px rgba(254,102,0,.4); margin-top: 4px;
}
.lp-submit:hover { background-position: right center; box-shadow: 0 6px 28px rgba(254,102,0,.6); transform: translateY(-1px); }
.lp-submit:active { transform: translateY(1px); box-shadow: 0 2px 10px rgba(254,102,0,.3); }
.lp-submit::after {
  content: ''; position: absolute; top: 0; left: -150%; width: 80%; height: 100%;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.3) 50%, transparent 100%);
  transform: skewX(-20deg); transition: left .55s ease;
}
.lp-submit:hover::after { left: 180%; }
.lp-submit.loading { pointer-events: none; }
.lp-submit.loading .btn-text { opacity: 0; }
.lp-submit .btn-spinner { display: none; position: absolute; inset: 0; align-items: center; justify-content: center; }
.lp-submit.loading .btn-spinner { display: flex; }
.spinner-ring { width: 20px; height: 20px; border: 2.5px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.auth-links { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.08); animation: fadeSlideUp .5s ease .65s both; }
.auth-link { font-size: .78rem; color: rgba(255,255,255,.45); text-decoration: none; transition: color .2s; }
.auth-link:hover { color: #FE6600; }
.auth-sep { color: rgba(255,255,255,.18); font-size: .7rem; }

.property-label { position: fixed; bottom: 20px; left: 0; right: 0; text-align: center; color: rgba(255,255,255,.28); font-size: .7rem; font-weight: 500; letter-spacing: 1.5px; text-transform: uppercase; z-index: 10; animation: pageFadeIn .8s ease .9s both; }
.property-label span { color: rgba(254,102,0,.6); }

@media (max-width: 520px) {
  .card { padding: 32px 24px 28px; max-width: 100%; border-radius: 16px; }
  html, body { overflow: auto; }
}
</style>
</head>
<body>

<div class="hero-bg"></div>
<div class="hero-overlay"></div>

<div class="particles">
  <div class="pt" style="width:3px;height:3px;left:8%;background:rgba(254,102,0,.5);animation-duration:16s;animation-delay:0s"></div>
  <div class="pt" style="width:2px;height:2px;left:22%;background:rgba(232,160,32,.4);animation-duration:21s;animation-delay:3s"></div>
  <div class="pt" style="width:3px;height:3px;left:38%;background:rgba(254,102,0,.4);animation-duration:18s;animation-delay:7s"></div>
  <div class="pt" style="width:2px;height:2px;left:55%;background:rgba(255,255,255,.25);animation-duration:23s;animation-delay:1s"></div>
  <div class="pt" style="width:4px;height:4px;left:67%;background:rgba(254,102,0,.35);animation-duration:14s;animation-delay:10s"></div>
  <div class="pt" style="width:2px;height:2px;left:80%;background:rgba(232,160,32,.45);animation-duration:19s;animation-delay:5s"></div>
</div>

<div class="page">
  <div class="card">

    <div class="logo-wrap">
      <span class="logo-hilret">Hilret</span>
      <span class="logo-trust">Trust</span>
      <svg class="logo-swoosh" viewBox="0 0 110 10" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M3 7.5 Q27.5 2 55 5.5 Q82.5 9 107 4" stroke="#FE6600" stroke-width="2.5" fill="none" stroke-linecap="round"/>
      </svg>
    </div>

    <div class="icon-ring">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FE6600" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    </div>

    <div class="sign-in-head">
      <h1>Forgot <em>Password</em></h1>
    </div>
    <p class="sign-in-sub">Enter your email and we'll send you a link to reset your password.</p>
    <div class="divider"></div>

    <?php if ($error): ?>
      <div class="lp-error">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="lp-success">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <strong>Check your email</strong>
        If that email is registered, a reset link has been sent. Please check your inbox (and spam folder).
      </div>
      <div class="auth-links" style="border-top:none; padding-top:0; margin-top:4px;">
        <a href="login.php" class="auth-link">← Back to Sign In</a>
      </div>
    <?php else: ?>

    <form method="POST" action="" id="fp-form" novalidate>
      <div class="fields">
        <div class="fl-field">
          <input type="email" id="email" name="email" placeholder=" "
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus autocomplete="email">
          <label for="email">Email Address</label>
          <svg class="fl-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
      </div>

      <div class="submit-wrap">
        <button type="submit" class="lp-submit" id="submit-btn">
          <span class="btn-text">Send Reset Link</span>
          <span class="btn-spinner"><span class="spinner-ring"></span></span>
        </button>
      </div>
    </form>

    <div class="auth-links">
      <a href="login.php" class="auth-link">← Back to Sign In</a>
      <span class="auth-sep">·</span>
      <a href="signup.php" class="auth-link">Create account</a>
    </div>

    <?php endif; ?>

  </div>
</div>

<div class="property-label">
  Hilret Trust &nbsp;·&nbsp; <span>Botanic Grove, Durban</span> &nbsp;·&nbsp; KwaZulu-Natal
</div>

<script>
var form = document.getElementById('fp-form');
if (form) {
  form.addEventListener('submit', function () {
    var btn = document.getElementById('submit-btn');
    btn.classList.add('loading');
    setTimeout(function () { btn.classList.remove('loading'); }, 6000);
  });
}
</script>

</body>
</html>
