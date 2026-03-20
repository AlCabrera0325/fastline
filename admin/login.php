<?php
session_start();
if (isset($_SESSION['admin'])) { header('Location: add_hotline.php'); exit; }
require '../includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin'] = $user['username'];
            header('Location: add_hotline.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastLine Admin — Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap');
        :root { --red:#c92a2a; --red-dark:#a61e1e; --red-glow:rgba(201,42,42,0.35); --bg:#0d0d0d; --surface:#161616; --border:rgba(255,255,255,0.07); --text:#f0f0f0; --muted:#888; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .bg-grid { position:fixed; inset:0; background-image:linear-gradient(rgba(201,42,42,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,42,42,0.04) 1px,transparent 1px); background-size:40px 40px; z-index:0; }
        .bg-glow { position:fixed; width:600px; height:600px; border-radius:50%; background:radial-gradient(circle,rgba(201,42,42,0.12) 0%,transparent 70%); top:50%; left:50%; transform:translate(-50%,-50%); z-index:0; animation:pulse 4s ease-in-out infinite; }
        @keyframes pulse { 0%,100%{opacity:0.6;transform:translate(-50%,-50%) scale(1)} 50%{opacity:1;transform:translate(-50%,-50%) scale(1.1)} }
        .login-wrapper { position:relative; z-index:1; width:100%; max-width:420px; padding:20px; animation:slideUp 0.6s cubic-bezier(0.16,1,0.3,1) both; }
        @keyframes slideUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        .login-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:44px 40px 40px; box-shadow:0 40px 80px rgba(0,0,0,0.6); }
        .logo-area { text-align:center; margin-bottom:36px; }
        .logo-icon { display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; background:var(--red); border-radius:16px; margin-bottom:16px; box-shadow:0 0 30px var(--red-glow); animation:iconPop 0.5s 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
        @keyframes iconPop { from{opacity:0;transform:scale(0.5)} to{opacity:1;transform:scale(1)} }
        .logo-icon i { font-size:1.8rem; color:white; }
        .logo-area h1 { font-family:'Bebas Neue',sans-serif; font-size:2rem; letter-spacing:3px; color:var(--text); line-height:1; }
        .logo-area p { font-size:0.82rem; color:var(--muted); margin-top:6px; letter-spacing:1.5px; text-transform:uppercase; }
        .admin-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(201,42,42,0.12); border:1px solid rgba(201,42,42,0.25); color:#ff8080; font-size:0.72rem; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; padding:4px 10px; border-radius:20px; margin-top:10px; }
        .error-box { background:rgba(201,42,42,0.1); border:1px solid rgba(201,42,42,0.35); border-radius:10px; padding:12px 16px; margin-bottom:24px; display:flex; align-items:center; gap:10px; font-size:0.88rem; color:#ff8080; animation:shake 0.4s ease; }
        @keyframes shake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-6px)} 75%{transform:translateX(6px)} }
        .field { margin-bottom:20px; }
        .field label { display:block; font-size:0.78rem; font-weight:600; letter-spacing:1.2px; text-transform:uppercase; color:var(--muted); margin-bottom:8px; }
        .input-wrap { position:relative; }
        .input-wrap i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:0.9rem; pointer-events:none; transition:color 0.2s; z-index:1; }
        .input-wrap input { width:100%; background:rgba(255,255,255,0.04); border:1px solid var(--border); border-radius:10px; padding:13px 14px 13px 40px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.95rem; outline:none; transition:border-color 0.2s,box-shadow 0.2s; }
        .input-wrap input:focus { border-color:var(--red); background:rgba(201,42,42,0.06); box-shadow:0 0 0 3px var(--red-glow); }
        .input-wrap:focus-within i { color:var(--red); }
        .toggle-pw { position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; font-size:0.9rem; padding:0; transition:color 0.2s; }
        .toggle-pw:hover { color:var(--text); }
        .btn-login { width:100%; padding:14px; background:var(--red); color:white; border:none; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:1rem; font-weight:600; cursor:pointer; margin-top:8px; transition:background 0.2s,transform 0.15s,box-shadow 0.2s; display:flex; align-items:center; justify-content:center; gap:8px; }
        .btn-login:hover { background:var(--red-dark); transform:translateY(-1px); box-shadow:0 8px 24px var(--red-glow); }
        .divider { height:1px; background:var(--border); margin:28px 0; }
        .back-link { text-align:center; }
        .back-link a { color:var(--muted); text-decoration:none; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px; transition:color 0.2s; }
        .back-link a:hover { color:var(--text); }
        @media(max-width:480px){ .login-card{padding:32px 24px 28px;} }
    </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-glow"></div>
<div class="login-wrapper">
    <div class="login-card">
        <div class="logo-area">
            <div class="logo-icon"><i class="fas fa-phone-volume"></i></div>
            <h1>FastLine</h1>
            <p>Emergency Hotline Directory</p>
            <div class="admin-badge"><i class="fas fa-shield-alt"></i> Admin Portal</div>
        </div>
        <?php if (!empty($error)): ?>
        <div class="error-box"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="field">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" autocomplete="username" required>
                </div>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                    <button type="button" class="toggle-pw" id="togglePw"><i class="fas fa-eye" id="eyeIcon"></i></button>
                </div>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Sign In</button>
        </form>
        <div class="divider"></div>
        <div class="back-link">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to FastLine</a>
        </div>
    </div>
</div>
<script>
    document.getElementById('togglePw').addEventListener('click', function() {
        const pw = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        const hidden = pw.type === 'password';
        pw.type = hidden ? 'text' : 'password';
        icon.className = hidden ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
</script>
</body>
</html>
