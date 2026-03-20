<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
require '../includes/db.php';

$message = '';
$error   = '';

// ── Add Notification ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $title   = trim($_POST['title']   ?? '');
    $msg     = trim($_POST['message'] ?? '');
    $type    = $_POST['type'] ?? 'info';

    if (empty($title) || empty($msg)) {
        $error = 'Title and message are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO notifications (title, message, type, is_active, created_by) VALUES (?,?,?,1,?)");
        $stmt->execute([$title, $msg, $type, $_SESSION['admin']]);
        $message = "Notification \"" . htmlspecialchars($title) . "\" posted successfully!";
    }
}

// ── Toggle Notification ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE notifications SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    header('Location: notifications.php');
    exit;
}

// ── Delete Notification ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$id]);
    header('Location: notifications.php');
    exit;
}

// ── Fetch All ─────────────────────────────────────────────
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastLine — Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap');
        :root { --red:#c92a2a; --red-dark:#a61e1e; --red-glow:rgba(201,42,42,0.25); --bg:#0d0d0d; --surface:#161616; --border:rgba(255,255,255,0.08); --text:#f0f0f0; --muted:#888; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .navbar { background:var(--surface); border-bottom:1px solid var(--border); padding:16px 32px; display:flex; align-items:center; justify-content:space-between; }
        .brand { font-family:'Bebas Neue',sans-serif; font-size:1.6rem; letter-spacing:3px; display:flex; align-items:center; gap:10px; }
        .brand i { color:var(--red); }
        .nav-links { display:flex; gap:12px; align-items:center; }
        .nav-link { color:var(--muted); text-decoration:none; font-size:0.85rem; display:flex; align-items:center; gap:6px; padding:8px 14px; border-radius:8px; transition:all 0.2s; border:1px solid transparent; }
        .nav-link:hover { color:var(--text); border-color:var(--border); }
        .nav-link.active { color:#ff8080; border-color:rgba(201,42,42,0.3); background:rgba(201,42,42,0.08); }
        .page { max-width:1100px; margin:0 auto; padding:32px 24px; }
        .page-title { font-family:'Bebas Neue',sans-serif; font-size:1.8rem; letter-spacing:2px; margin-bottom:28px; display:flex; align-items:center; gap:10px; }
        .page-title i { color:var(--red); }
        .grid { display:grid; grid-template-columns:360px 1fr; gap:24px; align-items:start; }
        @media(max-width:900px){ .grid { grid-template-columns:1fr; } }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:26px; }
        .card h2 { font-size:1rem; font-weight:600; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
        .card h2 i { color:var(--red); }
        .field { margin-bottom:16px; }
        .field label { display:block; font-size:0.74rem; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
        .field input, .field select, .field textarea { width:100%; background:rgba(255,255,255,0.04); border:1px solid var(--border); border-radius:8px; padding:10px 13px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.92rem; outline:none; transition:border-color 0.2s; }
        .field input:focus, .field select:focus, .field textarea:focus { border-color:var(--red); box-shadow:0 0 0 3px var(--red-glow); }
        .field select option { background:#1e1e1e; }
        .field textarea { resize:vertical; min-height:100px; }
        .btn-submit { width:100%; padding:12px; background:var(--red); color:white; border:none; border-radius:8px; font-family:'DM Sans',sans-serif; font-size:0.95rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:background 0.2s, transform 0.15s; }
        .btn-submit:hover { background:var(--red-dark); transform:translateY(-1px); }
        .alert { padding:12px 16px; border-radius:8px; margin-bottom:18px; display:flex; align-items:center; gap:10px; font-size:0.87rem; }
        .alert-success { background:rgba(40,167,69,0.12); border:1px solid rgba(40,167,69,0.3); color:#6fcf97; }
        .alert-error   { background:rgba(201,42,42,0.1);  border:1px solid rgba(201,42,42,0.3);  color:#ff8080; }

        /* Notification cards */
        .notif-list { display:flex; flex-direction:column; gap:12px; }
        .notif-card { background:var(--surface2,#1e1e1e); border:1px solid var(--border); border-radius:10px; padding:18px; display:flex; align-items:flex-start; gap:14px; }
        .notif-card.inactive { opacity:0.45; }
        .notif-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
        .notif-icon.info      { background:rgba(52,144,220,0.15);  color:#74b9ff; }
        .notif-icon.warning   { background:rgba(255,193,7,0.15);   color:#ffd43b; }
        .notif-icon.emergency { background:rgba(201,42,42,0.15);   color:#ff8080; }
        .notif-body { flex:1; }
        .notif-title { font-weight:600; font-size:0.95rem; margin-bottom:4px; }
        .notif-msg { font-size:0.85rem; color:var(--muted); line-height:1.5; }
        .notif-meta { font-size:0.75rem; color:var(--muted); margin-top:8px; display:flex; gap:12px; flex-wrap:wrap; }
        .notif-actions { display:flex; gap:6px; flex-shrink:0; }
        .type-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:0.7rem; font-weight:600; }
        .type-info      { background:rgba(52,144,220,0.15);  color:#74b9ff; }
        .type-warning   { background:rgba(255,193,7,0.15);   color:#ffd43b; }
        .type-emergency { background:rgba(201,42,42,0.15);   color:#ff8080; }
        .status-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:0.7rem; font-weight:600; }
        .status-active   { background:rgba(40,167,69,0.15); color:#6fcf97; }
        .status-inactive { background:rgba(150,150,150,0.12); color:#888; }
        .btn-toggle { background:transparent; border:1px solid rgba(255,255,255,0.12); color:var(--muted); padding:5px 10px; border-radius:6px; cursor:pointer; font-size:0.78rem; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
        .btn-toggle:hover { border-color:var(--muted); color:var(--text); }
        .btn-delete { background:transparent; border:1px solid rgba(201,42,42,0.3); color:#ff8080; padding:5px 10px; border-radius:6px; cursor:pointer; font-size:0.78rem; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
        .btn-delete:hover { background:rgba(201,42,42,0.15); border-color:var(--red); }
        .empty-state { text-align:center; padding:40px; color:var(--muted); }
        .empty-state i { font-size:2.5rem; opacity:0.3; margin-bottom:12px; display:block; }

        /* Type icons */
        .info-icon      { content: '\f05a'; }
        .warning-icon   { content: '\f071'; }
        .emergency-icon { content: '\f0f3'; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="brand"><i class="fas fa-phone-volume"></i> FastLine</div>
    <div class="nav-links">
        <a href="add_hotline.php" class="nav-link"><i class="fas fa-phone-alt"></i> Hotlines</a>
        <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="notifications.php" class="nav-link active"><i class="fas fa-bell"></i> Notifications</a>
        <a href="logs.php" class="nav-link"><i class="fas fa-history"></i> Activity Logs</a>
        <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> View Site</a>
        <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="page">
    <div class="page-title"><i class="fas fa-bell"></i> Notifications Management</div>

    <div class="grid">
        <!-- Add Form -->
        <div class="card">
            <h2><i class="fas fa-plus"></i> Post New Notification</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="field">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="e.g. Road Closure Alert" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>
                <div class="field">
                    <label>Type *</label>
                    <select name="type">
                        <option value="info">ℹ️ Info — General announcement</option>
                        <option value="warning">⚠️ Warning — Safety advisory</option>
                        <option value="emergency">🚨 Emergency — Critical alert</option>
                    </select>
                </div>
                <div class="field">
                    <label>Message *</label>
                    <textarea name="message" placeholder="Write the notification message here..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Post Notification
                </button>
            </form>
        </div>

        <!-- Notifications List -->
        <div class="card">
            <h2><i class="fas fa-list"></i> All Notifications (<?php echo count($notifications); ?>)</h2>
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications posted yet.</p>
                </div>
            <?php else: ?>
            <div class="notif-list">
                <?php foreach ($notifications as $n):
                    $icons = ['info'=>'fa-info-circle','warning'=>'fa-exclamation-triangle','emergency'=>'fa-bell'];
                    $icon = $icons[$n['type']] ?? 'fa-bell';
                ?>
                <div class="notif-card <?php echo $n['is_active'] ? '' : 'inactive'; ?>">
                    <div class="notif-icon <?php echo $n['type']; ?>">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="notif-body">
                        <div class="notif-title"><?php echo htmlspecialchars($n['title']); ?></div>
                        <div class="notif-msg"><?php echo htmlspecialchars($n['message']); ?></div>
                        <div class="notif-meta">
                            <span class="type-badge type-<?php echo $n['type']; ?>"><?php echo ucfirst($n['type']); ?></span>
                            <span class="status-badge <?php echo $n['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $n['is_active'] ? 'Active' : 'Hidden'; ?>
                            </span>
                            <span>By <?php echo htmlspecialchars($n['created_by']); ?></span>
                            <span><?php echo date('M d, Y h:i A', strtotime($n['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="notif-actions">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                            <button type="submit" class="btn-toggle" title="<?php echo $n['is_active'] ? 'Hide' : 'Show'; ?>">
                                <i class="fas fa-<?php echo $n['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                            </button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this notification?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                            <button type="submit" class="btn-delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
