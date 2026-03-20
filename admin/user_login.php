<?php
session_start();
if (isset($_SESSION['user']))  { header('Location: ../index.php');    exit; }
if (isset($_SESSION['admin'])) { header('Location: add_hotline.php'); exit; }
require '../includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
            ];
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastLine — Sign In</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;600;700&display=swap');

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
        .top-bar i { color: white; font-size: 1.5rem; }
        .top-bar span {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

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
            max-width: 420px;
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

        .card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 4px;
        }

        .card-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }

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

        .error-box {
            background: #fff5f5;
            border: 1px solid #ffc9c9;
            border-left: 4px solid #c92a2a;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            color: #c92a2a;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%,100%{transform:translateX(0)} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)}
        }

        /* ── Fields ── */
        .field { margin-bottom: 20px; }

        .field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 7px;
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
            font-size: 0.95rem;
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
            margin-top: 6px;
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
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 26px 0;
        }
        .divider::before, .divider::after { content:''; flex:1; height:1px; background:#dee2e6; }
        .divider span { color: #adb5bd; font-size: 0.8rem; white-space: nowrap; }

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
        .links-row a:hover { color: #212529; }

        .links-row a.primary {
            color: #c92a2a;
            font-weight: 600;
        }
        .links-row a.primary:hover { color: #a61e1e; }

        .sep { color: #dee2e6; }

        .admin-link {
            text-align: center;
            margin-top: 18px;
        }
        .admin-link a {
            color: #adb5bd;
            font-size: 0.78rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .admin-link a:hover { color: #6c757d; }

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
            <div class="icon-wrap"><i class="fas fa-sign-in-alt"></i></div>
            <h1>Welcome Back</h1>
            <p>Emergency Hotline Directory</p>
            <div class="badge"><i class="fas fa-user"></i> User Sign In</div>
        </div>

        <?php if (!empty($error)): ?>
        <div class="error-box">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field">
                <label>Username or Email</label>
                <div class="input-wrap">
                    <i class="fas fa-user icon"></i>
                    <input type="text" name="login"
                           placeholder="Enter username or email"
                           value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>"
                           autocomplete="username" required>
                </div>
            </div>

            <div class="field">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock icon"></i>
                    <input type="password" name="password" id="pw"
                           placeholder="Enter your password"
                           autocomplete="current-password" required>
                    <button type="button" class="toggle-pw" id="togglePw">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="divider"><span>don't have an account?</span></div>

        <div class="links-row">
            <a href="signup.php" class="primary">
                <i class="fas fa-user-plus"></i> Create Account
            </a>
            <span class="sep">|</span>
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Back to FastLine
            </a>
        </div>

        <div class="admin-link">
            <a href="login.php"><i class="fas fa-shield-alt"></i> Admin Login</a>
        </div>

    </div>
</div>

<script>
document.getElementById('togglePw').addEventListener('click', function () {
    const pw   = document.getElementById('pw');
    const icon = document.getElementById('eyeIcon');
    const hide = pw.type === 'password';
    pw.type = hide ? 'text' : 'password';
    icon.className = hide ? 'fas fa-eye-slash' : 'fas fa-eye';
});
</script>

</body>
</html>
