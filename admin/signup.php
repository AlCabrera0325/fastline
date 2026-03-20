<?php
session_start();
if (isset($_SESSION['user']))  { header('Location: ../index.php');    exit; }
if (isset($_SESSION['admin'])) { header('Location: add_hotline.php'); exit; }
require '../includes/db.php';

$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $username  = trim($_POST['username']  ?? '');
    $password  = $_POST['password']         ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($full_name))                              $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                                                        $errors[] = 'A valid email address is required.';
    if (empty($username) || strlen($username) < 3)      $errors[] = 'Username must be at least 3 characters.';
    if (strlen($password) < 6)                          $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                         $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $errors[] = 'That email or username is already taken.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $pdo->prepare("INSERT INTO users (full_name, email, username, password) VALUES (?,?,?,?)");
            $stmt->execute([$full_name, $email, $username, $hashed]);
            $_SESSION['user'] = [
                'id'        => $pdo->lastInsertId(),
                'username'  => $username,
                'full_name' => $full_name,
            ];
            header('Location: ../index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastLine — Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            background: linear-gradient(135deg, #c92a2a 0%, #e03131 100%);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .top-bar i    { color: white; font-size: 1.5rem; }
        .top-bar span { color: white; font-size: 1.4rem; font-weight: 700; letter-spacing: 1px; }

        .page-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 44px 40px 40px;
            width: 100%;
            max-width: 480px;
            animation: slideUp 0.5s cubic-bezier(0.16,1,0.3,1) both;
        }

        @keyframes slideUp {
            from { opacity:0; transform:translateY(24px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #c92a2a, #e03131);
            border-radius: 18px;
            margin-bottom: 16px;
            box-shadow: 0 6px 20px rgba(201,42,42,0.35);
            animation: pop 0.5s 0.2s cubic-bezier(0.34,1.56,0.64,1) both;
        }

        @keyframes pop {
            from { opacity:0; transform:scale(0.5); }
            to   { opacity:1; transform:scale(1); }
        }

        .icon-wrap i { font-size: 1.9rem; color: white; }

        .card-header h1 { font-size: 1.8rem; font-weight: 700; color: #212529; margin-bottom: 4px; }
        .card-header p  { color: #6c757d; font-size: 0.9rem; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(201,42,42,0.08);
            border: 1px solid rgba(201,42,42,0.2);
            color: #c92a2a;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 10px;
        }

        /* ── Errors ── */
        .error-list {
            background: #fff5f5;
            border: 1px solid #ffc9c9;
            border-left: 4px solid #c92a2a;
            border-radius: 8px;
            padding: 13px 16px;
            margin-bottom: 22px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%,100%{transform:translateX(0)} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)}
        }

        .error-list p {
            color: #c92a2a;
            font-size: 0.86rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .error-list p + p { margin-top: 5px; }

        /* ── Two-column row ── */
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 520px) { .row { grid-template-columns: 1fr; } }

        /* ── Fields ── */
        .field { margin-bottom: 18px; }

        .field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 7px;
        }

        .field label .optional {
            font-weight: 400;
            color: #adb5bd;
            font-size: 0.78rem;
        }

        .input-wrap { position: relative; }

        .input-wrap .icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 0.9rem;
            pointer-events: none;
            transition: color 0.2s;
            z-index: 1;
        }

        .input-wrap input {
            width: 100%;
            border: 2px solid #dee2e6;
            border-radius: 9px;
            padding: 12px 14px 12px 40px;
            font-size: 0.93rem;
            font-family: inherit;
            color: #212529;
            background: white;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-wrap input:focus {
            border-color: #c92a2a;
            box-shadow: 0 0 0 3px rgba(201,42,42,0.12);
        }

        .input-wrap:focus-within .icon { color: #c92a2a; }

        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #adb5bd;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: #495057; }

        /* Password strength */
        .strength-bar  { height: 3px; border-radius: 2px; margin-top: 6px; background: #dee2e6; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 2px; width: 0%; transition: width 0.3s, background 0.3s; }
        .strength-label { font-size: 0.7rem; color: #adb5bd; margin-top: 3px; min-height: 14px; }

        /* ── Submit ── */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #c92a2a, #e03131);
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
        }
        .btn-submit:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(201,42,42,0.35);
        }
        .btn-submit:active { transform: translateY(0); }

        /* ── Divider ── */
        .divider { display: flex; align-items: center; gap: 12px; margin: 26px 0; }
        .divider::before, .divider::after { content:''; flex:1; height:1px; background:#dee2e6; }
        .divider span { color: #adb5bd; font-size: 0.8rem; white-space: nowrap; }

        /* ── Links ── */
        .links-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .links-row a {
            color: #6c757d;
            text-decoration: none;
            font-size: 0.87rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
        }
        .links-row a:hover  { color: #212529; }
        .links-row a.primary { color: #c92a2a; font-weight: 600; }
        .links-row a.primary:hover { color: #a61e1e; }
        .sep { color: #dee2e6; }

        @media (max-width: 480px) { .card { padding: 32px 22px 28px; } }
    </style>
</head>
<body>

<div class="top-bar">
    <i class="fas fa-phone-volume"></i>
    <span>FastLine</span>
</div>

<div class="page-body">
    <div class="card">

        <div class="card-header">
            <div class="icon-wrap"><i class="fas fa-user-plus"></i></div>
            <h1>Create Account</h1>
            <p>Emergency Hotline Directory</p>
            <div class="badge"><i class="fas fa-user-plus"></i> New Registration</div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="error-list">
            <?php foreach ($errors as $e): ?>
            <p><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="field">
                <label>Full Name</label>
                <div class="input-wrap">
                    <i class="fas fa-id-card icon"></i>
                    <input type="text" name="full_name" placeholder="e.g. Juan dela Cruz"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="field">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope icon"></i>
                    <input type="email" name="email" placeholder="you@email.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="field">
                <label>Username</label>
                <div class="input-wrap">
                    <i class="fas fa-at icon"></i>
                    <input type="text" name="username" placeholder="Choose a username (min. 3 characters)"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           minlength="3" required>
                </div>
            </div>

            <div class="row">
                <div class="field">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" name="password" id="pw"
                               placeholder="Min. 6 characters" minlength="6" required>
                        <button type="button" class="toggle-pw" onclick="toggleVis('pw','eye1')">
                            <i class="fas fa-eye" id="eye1"></i>
                        </button>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <div class="strength-label" id="strengthLabel"></div>
                </div>

                <div class="field">
                    <label>Confirm Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" name="confirm_password" id="pw2"
                               placeholder="Repeat password" minlength="6" required>
                        <button type="button" class="toggle-pw" onclick="toggleVis('pw2','eye2')">
                            <i class="fas fa-eye" id="eye2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i> Create Account
            </button>

        </form>

        <div class="divider"><span>already have an account?</span></div>

        <div class="links-row">
            <a href="user_login.php" class="primary">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </a>
            <span class="sep">|</span>
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Back to FastLine
            </a>
        </div>

    </div>
</div>

<script>
function toggleVis(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

document.getElementById('pw').addEventListener('input', function () {
    const val    = this.value;
    const fill   = document.getElementById('strengthFill');
    const label  = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 6)             score++;
    if (val.length >= 10)            score++;
    if (/[A-Z]/.test(val))           score++;
    if (/[0-9]/.test(val))           score++;
    if (/[^A-Za-z0-9]/.test(val))    score++;

    const levels = [
        { w:'0%',   bg:'transparent', text:'' },
        { w:'25%',  bg:'#e03131',     text:'Weak' },
        { w:'50%',  bg:'#fd7e14',     text:'Fair' },
        { w:'75%',  bg:'#fcc419',     text:'Good' },
        { w:'100%', bg:'#2f9e44',     text:'Strong' },
    ];
    const lvl = levels[Math.min(score, 4)];
    fill.style.width      = lvl.w;
    fill.style.background = lvl.bg;
    label.textContent     = lvl.text;
    label.style.color     = lvl.bg;
});
</script>

</body>
</html>
