<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
require '../includes/db.php';

// ── Filters ───────────────────────────────────────────────
$filterAdmin  = $_GET['admin']  ?? '';
$filterAction = $_GET['action'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$sql    = "SELECT * FROM activity_logs WHERE 1=1";
$params = [];

if (!empty($filterAdmin)) {
    $sql .= " AND admin_username = ?";
    $params[] = $filterAdmin;
}
if (!empty($filterAction)) {
    $sql .= " AND action LIKE ?";
    $params[] = "%$filterAction%";
}

// Count total for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE 1=1" .
    (!empty($filterAdmin)  ? " AND admin_username = ?"  : "") .
    (!empty($filterAction) ? " AND action LIKE ?"       : ""));
$countParams = [];
if (!empty($filterAdmin))  $countParams[] = $filterAdmin;
if (!empty($filterAction)) $countParams[] = "%$filterAction%";
$countStmt->execute($countParams);
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

$sql .= " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of admins for filter
$admins = $pdo->query("SELECT DISTINCT admin_username FROM activity_logs ORDER BY admin_username")->fetchAll(PDO::FETCH_COLUMN);

// Action color map
function actionColor($action) {
    if (str_contains($action, 'Added'))    return ['bg'=>'rgba(40,167,69,0.15)',  'color'=>'#6fcf97', 'icon'=>'fa-plus-circle'];
    if (str_contains($action, 'Updated'))  return ['bg'=>'rgba(255,193,7,0.15)',  'color'=>'#ffd43b', 'icon'=>'fa-edit'];
    if (str_contains($action, 'Deleted'))  return ['bg'=>'rgba(201,42,42,0.15)',  'color'=>'#ff8080', 'icon'=>'fa-trash'];
    if (str_contains($action, 'Toggled'))  return ['bg'=>'rgba(52,144,220,0.15)', 'color'=>'#74b9ff', 'icon'=>'fa-toggle-on'];
    if (str_contains($action, 'Posted'))   return ['bg'=>'rgba(155,89,182,0.15)', 'color'=>'#c39ef7', 'icon'=>'fa-bell'];
    if (str_contains($action, 'Login'))    return ['bg'=>'rgba(52,144,220,0.15)', 'color'=>'#74b9ff', 'icon'=>'fa-sign-in-alt'];
    return ['bg'=>'rgba(255,255,255,0.08)', 'color'=>'#888', 'icon'=>'fa-circle'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastLine — Activity Logs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap');
        :root { --red:#c92a2a; --bg:#0d0d0d; --surface:#161616; --border:rgba(255,255,255,0.08); --text:#f0f0f0; --muted:#888; }
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
        .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        .page-title { font-family:'Bebas Neue',sans-serif; font-size:1.8rem; letter-spacing:2px; display:flex; align-items:center; gap:10px; }
        .page-title i { color:var(--red); }
        .total-badge { background:rgba(201,42,42,0.12); border:1px solid rgba(201,42,42,0.25); color:#ff8080; font-size:0.75rem; font-weight:600; padding:5px 12px; border-radius:20px; }

        /* Filters */
        .filters { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
        .filters select, .filters input { background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:9px 13px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.88rem; outline:none; }
        .filters select:focus, .filters input:focus { border-color:var(--red); }
        .filters select option { background:#1e1e1e; }
        .btn-filter { background:var(--red); color:white; border:none; border-radius:8px; padding:9px 16px; font-family:'DM Sans',sans-serif; font-size:0.88rem; cursor:pointer; display:flex; align-items:center; gap:6px; }
        .btn-clear { background:transparent; border:1px solid var(--border); color:var(--muted); border-radius:8px; padding:9px 14px; font-family:'DM Sans',sans-serif; font-size:0.88rem; cursor:pointer; text-decoration:none; display:flex; align-items:center; gap:6px; }

        /* Log timeline */
        .log-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; overflow:hidden; }
        .log-item { display:flex; align-items:flex-start; gap:14px; padding:14px 18px; border-bottom:1px solid rgba(255,255,255,0.04); transition:background 0.15s; }
        .log-item:last-child { border-bottom:none; }
        .log-item:hover { background:rgba(255,255,255,0.02); }
        .log-dot { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:0.88rem; flex-shrink:0; }
        .log-body { flex:1; }
        .log-action { font-size:0.9rem; font-weight:600; }
        .log-details { font-size:0.82rem; color:var(--muted); margin-top:2px; }
        .log-meta { font-size:0.75rem; color:var(--muted); margin-top:6px; display:flex; gap:14px; flex-wrap:wrap; }
        .admin-chip { background:rgba(201,42,42,0.1); color:#ff8080; padding:2px 8px; border-radius:10px; font-size:0.72rem; font-weight:600; }
        .time-chip { display:flex; align-items:center; gap:4px; }

        /* Pagination */
        .pagination { display:flex; gap:6px; justify-content:center; margin-top:24px; flex-wrap:wrap; }
        .page-btn { background:var(--surface); border:1px solid var(--border); color:var(--muted); padding:7px 13px; border-radius:7px; text-decoration:none; font-size:0.85rem; transition:all 0.2s; }
        .page-btn:hover { border-color:var(--red); color:var(--text); }
        .page-btn.active { background:var(--red); border-color:var(--red); color:white; }

        .empty-state { text-align:center; padding:60px; color:var(--muted); }
        .empty-state i { font-size:3rem; opacity:0.3; margin-bottom:16px; display:block; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="brand"><i class="fas fa-phone-volume"></i> FastLine</div>
    <div class="nav-links">
        <a href="add_hotline.php" class="nav-link"><i class="fas fa-phone-alt"></i> Hotlines</a>
        <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i> Notifications</a>
        <a href="logs.php" class="nav-link active"><i class="fas fa-history"></i> Activity Logs</a>
        <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> View Site</a>
        <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="page">
    <div class="page-header">
        <div class="page-title"><i class="fas fa-history"></i> Activity Logs</div>
        <span class="total-badge"><?php echo $totalLogs; ?> total entries</span>
    </div>

    <!-- Filters -->
    <form method="GET" action="">
        <div class="filters">
            <select name="admin">
                <option value="">All Admins</option>
                <?php foreach ($admins as $a): ?>
                <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $filterAdmin === $a ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($a); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="action" placeholder="Filter by action..." value="<?php echo htmlspecialchars($filterAction); ?>">
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <a href="logs.php" class="btn-clear"><i class="fas fa-times"></i> Clear</a>
        </div>
    </form>

    <!-- Logs -->
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <i class="fas fa-history"></i>
            <p>No activity logs yet.<br>Actions will be recorded here automatically.</p>
        </div>
    <?php else: ?>
    <div class="log-card">
        <?php foreach ($logs as $log):
            $c = actionColor($log['action']);
        ?>
        <div class="log-item">
            <div class="log-dot" style="background:<?php echo $c['bg']; ?>; color:<?php echo $c['color']; ?>">
                <i class="fas <?php echo $c['icon']; ?>"></i>
            </div>
            <div class="log-body">
                <div class="log-action"><?php echo htmlspecialchars($log['action']); ?></div>
                <?php if ($log['details']): ?>
                <div class="log-details"><?php echo htmlspecialchars($log['details']); ?></div>
                <?php endif; ?>
                <div class="log-meta">
                    <span class="admin-chip"><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($log['admin_username']); ?></span>
                    <span class="time-chip"><i class="fas fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&admin=<?php echo urlencode($filterAdmin); ?>&action=<?php echo urlencode($filterAction); ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
            <a href="?page=<?php echo $i; ?>&admin=<?php echo urlencode($filterAdmin); ?>&action=<?php echo urlencode($filterAction); ?>" class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>&admin=<?php echo urlencode($filterAdmin); ?>&action=<?php echo urlencode($filterAction); ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
