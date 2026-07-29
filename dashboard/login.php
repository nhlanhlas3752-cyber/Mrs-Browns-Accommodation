<?php
require_once dirname(__DIR__) . '/backend/auth.php';

if (is_logged_in()) {
    header('Location: /dashboard/overview.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (rate_limit_exceeded('login:' . $ip, 5, 900)) {
        $error = 'Too many login attempts. Please wait 15 minutes before trying again.';
    } elseif (attempt_login($email, $password)) {
        header('Location: /dashboard/overview.php');
        exit;
    } else {
        $error = 'Invalid email address or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Hilret Trust</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600&family=Dancing+Script:wght@600;700;800&display=swap" rel="stylesheet">
<style>
/* ================================================================
   HILRET TRUST — Login · Full-screen property hero + centred card
   ================================================================ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  height: 100%;
  font-family: 'Inter', sans-serif;
  overflow: hidden;
}

/* ── Full-screen hero background ────────────────────────────── */
.hero-bg {
  position: fixed;
  inset: 0;
  background:
    url('/IMG-20251001-WA0060.jpg')
    center center / cover no-repeat;
  animation: bgZoom 18s ease-in-out infinite alternate;
  z-index: 0;
}
@keyframes bgZoom {
  from { transform: scale(1);    filter: brightness(.85); }
  to   { transform: scale(1.06); filter: brightness(.75); }
}

/* ── Layered overlay ─────────────────────────────────────────── */
.hero-overlay {
  position: fixed;
  inset: 0;
  background:
    linear-gradient(
      160deg,
      rgba(13,22,41,.82)  0%,
      rgba(13,22,41,.65) 40%,
      rgba(13,22,41,.78) 100%
    );
  z-index: 1;
}
/* Warm orange glow at bottom — echoes the property's warmth */
.hero-overlay::after {
  content: '';
  position: absolute;
  bottom: -60px; left: 50%;
  transform: translateX(-50%);
  width: 700px; height: 300px;
  border-radius: 50%;
  background: radial-gradient(ellipse, rgba(254,102,0,.18) 0%, transparent 70%);
  animation: glowBreathe 5s ease-in-out infinite;
}
@keyframes glowBreathe {
  0%,100% { opacity: .7; transform: translateX(-50%) scale(1); }
  50%      { opacity: 1;  transform: translateX(-50%) scale(1.12); }
}

/* ── Floating particles ──────────────────────────────────────── */
.particles { position: fixed; inset: 0; z-index: 2; pointer-events: none; }
.pt {
  position: absolute;
  border-radius: 50%;
  animation: ptDrift linear infinite;
}
@keyframes ptDrift {
  0%   { transform: translateY(100vh); opacity: 0; }
  6%   { opacity: 1; }
  94%  { opacity: 1; }
  100% { transform: translateY(-40px); opacity: 0; }
}

/* ── Page wrapper — centres the card ─────────────────────────── */
.page {
  position: relative;
  z-index: 10;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  animation: pageFadeIn .6s ease both;
}
@keyframes pageFadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* ── Glassmorphism card ──────────────────────────────────────── */
.card {
  width: 100%;
  max-width: 440px;
  background: rgba(13, 22, 41, 0.82);
  backdrop-filter: blur(20px) saturate(1.4);
  -webkit-backdrop-filter: blur(20px) saturate(1.4);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 20px;
  padding: 44px 44px 36px;
  box-shadow:
    0 32px 80px rgba(0,0,0,.55),
    0  2px  0 rgba(255,255,255,.06) inset,
    0 -1px  0 rgba(0,0,0,.4) inset;
  animation: cardRise .7s cubic-bezier(.22,1,.36,1) .1s both;
}
@keyframes cardRise {
  from { transform: translateY(40px) scale(.97); opacity: 0; }
  to   { transform: translateY(0)    scale(1);   opacity: 1; }
}
/* Top orange line */
.card::before {
  content: '';
  position: absolute;
  top: 0; left: 44px; right: 44px;
  height: 2px;
  background: linear-gradient(90deg, transparent, #FE6600 30%, #E8A020 70%, transparent);
  border-radius: 99px;
  animation: lineReveal .8s cubic-bezier(.22,1,.36,1) .6s both;
}
@keyframes lineReveal {
  from { transform: scaleX(0); opacity: 0; }
  to   { transform: scaleX(1); opacity: 1; }
}

/* ── Logo ─────────────────────────────────────────────────────── */
.logo-wrap {
  text-align: center;
  margin-bottom: 28px;
  animation: fadeSlideDown .5s ease .3s both;
}
@keyframes fadeSlideDown {
  from { transform: translateY(-16px); opacity: 0; }
  to   { transform: translateY(0);     opacity: 1; }
}
.logo-hilret {
  display: block;
  font-family: 'Poppins', sans-serif;
  font-size: 2rem;
  font-weight: 900;
  color: #FE6600;
  text-transform: uppercase;
  letter-spacing: 3px;
  line-height: 1;
}
.logo-trust {
  display: block;
  font-family: 'Dancing Script', cursive;
  font-size: 1.6rem;
  font-weight: 700;
  color: rgba(255,255,255,.7);
  line-height: 1.2;
}
.logo-swoosh {
  display: block;
  width: 110px;
  height: 10px;
  margin: 4px auto 0;
  overflow: visible;
}
.logo-swoosh path {
  stroke-dasharray: 200;
  stroke-dashoffset: 200;
  animation: drawSwoosh 1s cubic-bezier(.22,1,.36,1) .8s forwards;
}
@keyframes drawSwoosh {
  from { stroke-dashoffset: 200; }
  to   { stroke-dashoffset: 0; }
}

/* ── Heading ──────────────────────────────────────────────────── */
.sign-in-head {
  text-align: center;
  margin-bottom: 6px;
  animation: fadeSlideUp .5s ease .4s both;
}
.sign-in-head h1 {
  font-family: 'Poppins', sans-serif;
  font-size: 1.7rem;
  font-weight: 900;
  color: #fff;
  letter-spacing: -.4px;
  line-height: 1;
}
.sign-in-head h1 em {
  font-style: normal;
  color: #FE6600;
}
.sign-in-sub {
  text-align: center;
  color: rgba(255,255,255,.45);
  font-size: .82rem;
  margin-bottom: 28px;
  line-height: 1.5;
  animation: fadeSlideUp .5s ease .45s both;
}
@keyframes fadeSlideUp {
  from { transform: translateY(14px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* ── Divider ─────────────────────────────────────────────────── */
.divider {
  height: 1px;
  background: rgba(255,255,255,.08);
  margin: 0 0 24px;
  animation: fadeSlideUp .5s ease .48s both;
}

/* ── Error box ───────────────────────────────────────────────── */
.lp-error {
  display: flex;
  align-items: center;
  gap: 9px;
  background: rgba(239,68,68,.15);
  border: 1px solid rgba(239,68,68,.4);
  border-left: 3px solid #EF4444;
  color: #FCA5A5;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: .79rem;
  font-weight: 500;
  margin-bottom: 18px;
  animation: shake .45s cubic-bezier(.36,.07,.19,.97) both;
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

/* ── Floating label fields ───────────────────────────────────── */
.fields { animation: fadeSlideUp .5s ease .5s both; }
.fl-field {
  position: relative;
  margin-bottom: 16px;
}
.fl-field input {
  width: 100%;
  padding: 20px 46px 8px 16px;
  background: rgba(255,255,255,.07);
  border: 1.5px solid rgba(255,255,255,.12);
  border-radius: 10px;
  font-family: 'Inter', sans-serif;
  font-size: .9rem;
  color: #fff;
  outline: none;
  caret-color: #FE6600;
  transition: border-color .2s, background .2s, box-shadow .2s;
}
.fl-field input::placeholder { color: transparent; }
.fl-field input:focus {
  border-color: #FE6600;
  background: rgba(254,102,0,.07);
  box-shadow: 0 0 0 3px rgba(254,102,0,.15);
}
.fl-field label {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  font-size: .88rem;
  color: rgba(255,255,255,.38);
  pointer-events: none;
  transition: top .18s ease, transform .18s ease, font-size .18s ease, color .18s ease;
  transform-origin: left center;
}
.fl-field input:focus ~ label,
.fl-field input:not(:placeholder-shown) ~ label {
  top: 9px;
  transform: translateY(0) scale(.74);
  color: #FE6600;
  font-weight: 700;
}
/* Field icon */
.fl-icon {
  position: absolute;
  right: 14px; top: 50%;
  transform: translateY(-50%);
  color: rgba(255,255,255,.25);
  pointer-events: none;
}
.fl-field input:focus ~ .fl-icon { color: #FE6600; }
/* Password toggle */
.fl-toggle {
  position: absolute;
  right: 13px; top: 50%;
  transform: translateY(-50%);
  background: none; border: none;
  color: rgba(255,255,255,.28);
  cursor: pointer;
  padding: 4px;
  transition: color .15s;
  display: flex; align-items: center;
}
.fl-toggle:hover { color: #FE6600; }

/* ── Submit button ───────────────────────────────────────────── */
.submit-wrap { animation: fadeSlideUp .5s ease .58s both; }
.lp-submit {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 10px;
  font-family: 'Poppins', sans-serif;
  font-size: .92rem;
  font-weight: 800;
  letter-spacing: .4px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  color: #fff;
  background: linear-gradient(110deg, #FE6600 0%, #E8A020 50%, #FE6600 100%);
  background-size: 200% auto;
  transition: background-position .45s ease, transform .1s, box-shadow .2s;
  box-shadow: 0 4px 20px rgba(254,102,0,.4);
  margin-top: 4px;
}
.lp-submit:hover {
  background-position: right center;
  box-shadow: 0 6px 28px rgba(254,102,0,.6);
  transform: translateY(-1px);
}
.lp-submit:active { transform: translateY(1px); box-shadow: 0 2px 10px rgba(254,102,0,.3); }
/* Shimmer sweep */
.lp-submit::after {
  content: '';
  position: absolute;
  top: 0; left: -150%;
  width: 80%; height: 100%;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.3) 50%, transparent 100%);
  transform: skewX(-20deg);
  transition: left .55s ease;
}
.lp-submit:hover::after { left: 180%; }
/* Loading */
.lp-submit.loading { pointer-events: none; }
.lp-submit.loading .btn-text { opacity: 0; }
.lp-submit .btn-spinner {
  display: none;
  position: absolute; inset: 0;
  align-items: center; justify-content: center;
}
.lp-submit.loading .btn-spinner { display: flex; }
.spinner-ring {
  width: 20px; height: 20px;
  border: 2.5px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Auth footer links ───────────────────────────────────────── */
.auth-links {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 22px;
  padding-top: 18px;
  border-top: 1px solid rgba(255,255,255,.08);
  animation: fadeSlideUp .5s ease .65s both;
}
.auth-link {
  font-size: .78rem;
  color: rgba(255,255,255,.45);
  text-decoration: none;
  transition: color .2s;
}
.auth-link:hover { color: #FE6600; }
.auth-sep { color: rgba(255,255,255,.18); font-size: .7rem; }

/* ── Property label (bottom of screen) ──────────────────────── */
.property-label {
  position: fixed;
  bottom: 20px; left: 0; right: 0;
  text-align: center;
  color: rgba(255,255,255,.28);
  font-size: .7rem;
  font-weight: 500;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  z-index: 10;
  animation: pageFadeIn .8s ease .9s both;
}
.property-label span { color: rgba(254,102,0,.6); }

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 520px) {
  .card { padding: 32px 24px 28px; max-width: 100%; border-radius: 16px; }
  html, body { overflow: auto; }
  .hero-bg { position: fixed; }
}
</style>
</head>
<body>

<!-- Full-screen property photo background -->
<div class="hero-bg"></div>
<div class="hero-overlay"></div>

<!-- Floating particles -->
<div class="particles">
  <div class="pt" style="width:3px;height:3px;left:8%;background:rgba(254,102,0,.5);animation-duration:16s;animation-delay:0s"></div>
  <div class="pt" style="width:2px;height:2px;left:22%;background:rgba(232,160,32,.4);animation-duration:21s;animation-delay:3s"></div>
  <div class="pt" style="width:3px;height:3px;left:38%;background:rgba(254,102,0,.4);animation-duration:18s;animation-delay:7s"></div>
  <div class="pt" style="width:2px;height:2px;left:55%;background:rgba(255,255,255,.25);animation-duration:23s;animation-delay:1s"></div>
  <div class="pt" style="width:4px;height:4px;left:67%;background:rgba(254,102,0,.35);animation-duration:14s;animation-delay:10s"></div>
  <div class="pt" style="width:2px;height:2px;left:80%;background:rgba(232,160,32,.45);animation-duration:19s;animation-delay:5s"></div>
  <div class="pt" style="width:3px;height:3px;left:92%;background:rgba(254,102,0,.3);animation-duration:25s;animation-delay:8s"></div>
  <div class="pt" style="width:2px;height:2px;left:47%;background:rgba(255,255,255,.2);animation-duration:20s;animation-delay:13s"></div>
</div>

<!-- Centred login card -->
<div class="page">
  <div class="card">

    <!-- Logo -->
    <div class="logo-wrap">
      <span class="logo-hilret">Hilret</span>
      <span class="logo-trust">Trust</span>
      <svg class="logo-swoosh" viewBox="0 0 110 10" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M3 7.5 Q27.5 2 55 5.5 Q82.5 9 107 4"
              stroke="#FE6600" stroke-width="2.5" fill="none" stroke-linecap="round"/>
      </svg>
    </div>

    <!-- Heading -->
    <div class="sign-in-head">
      <h1>Sign <em>in</em></h1>
    </div>
    <p class="sign-in-sub">Access the Hilret Trust property management portal.</p>

    <div class="divider"></div>

    <?php if ($error): ?>
      <div class="lp-error">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="" id="login-form" novalidate>
      <div class="fields">

        <!-- Email -->
        <div class="fl-field">
          <input
            type="email"
            id="email"
            name="email"
            placeholder=" "
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required autofocus autocomplete="email"
          >
          <label for="email">Email Address</label>
          <svg class="fl-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>

        <!-- Password -->
        <div class="fl-field">
          <input
            type="password"
            id="password"
            name="password"
            placeholder=" "
            required
            autocomplete="current-password"
          >
          <label for="password">Password</label>
          <button type="button" class="fl-toggle" id="pwd-toggle" aria-label="Show/hide password">
            <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>

      </div>

      <!-- Submit -->
      <div class="submit-wrap">
        <button type="submit" class="lp-submit" id="submit-btn">
          <span class="btn-text">Sign In to Dashboard</span>
          <span class="btn-spinner">
            <span class="spinner-ring"></span>
          </span>
        </button>
      </div>

    </form>

    <!-- Auth footer links -->
    <div class="auth-links">
      <a href="forgot-password.php" class="auth-link">Forgot password?</a>
    </div>

  </div>
</div>

<!-- Property credit at bottom -->
<div class="property-label">
  Hilret Trust &nbsp;·&nbsp; <span>Botanic Grove, Durban</span> &nbsp;·&nbsp; KwaZulu-Natal
</div>

<script>
/* ── Show / hide password ───────────────────────────────────── */
var pwdInput  = document.getElementById('password');
var pwdToggle = document.getElementById('pwd-toggle');
var eyeIcon   = document.getElementById('eye-icon');

var eyeOpen   = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
var eyeClosed = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';

pwdToggle.addEventListener('click', function () {
  var isPass = pwdInput.type === 'password';
  pwdInput.type = isPass ? 'text' : 'password';
  eyeIcon.innerHTML = isPass ? eyeClosed : eyeOpen;
});

/* ── Loading spinner on submit ──────────────────────────────── */
document.getElementById('login-form').addEventListener('submit', function () {
  var btn = document.getElementById('submit-btn');
  btn.classList.add('loading');
  setTimeout(function () { btn.classList.remove('loading'); }, 6000);
});
</script>

</body>
</html>
