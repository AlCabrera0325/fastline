<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
require __DIR__ . '/../includes/db.php';

try {
    $total    = $pdo->query("SELECT COUNT(*) FROM hotlines")->fetchColumn();
    $active   = $pdo->query("SELECT COUNT(*) FROM hotlines WHERE is_active = 1")->fetchColumn();
    $inactive = $pdo->query("SELECT COUNT(*) FROM hotlines WHERE is_active = 0")->fetchColumn();

    $byCategory = $pdo->query("
        SELECT category, COUNT(*) as total, SUM(is_active) as active, SUM(is_active = 0) as inactive
        FROM hotlines GROUP BY category ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $byCity = $pdo->query("
        SELECT city, COUNT(*) as total, SUM(is_active) as active
        FROM hotlines GROUP BY city ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Fixed query — uses MIN(created_at) to satisfy strict GROUP BY mode
    $usersByMonth = $pdo->query("
        SELECT DATE_FORMAT(MIN(created_at), '%M %Y') as month,
               DATE_FORMAT(MIN(created_at), '%Y-%m') as month_key,
               COUNT(*) as total
        FROM users
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month_key DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    $topFavorites = $pdo->query("
        SELECT h.name, h.category, h.city, h.phone, COUNT(f.id) as favorite_count
        FROM hotlines h LEFT JOIN favorites f ON h.id = f.hotline_id
        GROUP BY h.id, h.name, h.category, h.city, h.phone
        ORDER BY favorite_count DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<p style='color:red;font-family:sans-serif;padding:20px'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>");
}

$catColors = [
    'police'   => ['bg' => 'rgba(52,144,220,0.15)',  'color' => '#74b9ff', 'icon' => 'fa-shield-alt'],
    'medical'  => ['bg' => 'rgba(40,167,69,0.15)',   'color' => '#6fcf97', 'icon' => 'fa-ambulance'],
    'fire'     => ['bg' => 'rgba(253,126,20,0.15)',  'color' => '#fda94f', 'icon' => 'fa-fire-extinguisher'],
    'disaster' => ['bg' => 'rgba(255,193,7,0.15)',   'color' => '#ffd43b', 'icon' => 'fa-exclamation-triangle'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastLine — Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap');
        :root { --red:#c92a2a; --bg:#0d0d0d; --surface:#161616; --border:rgba(255,255,255,0.08); --text:#f0f0f0; --muted:#888; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .navbar { background:var(--surface); border-bottom:1px solid var(--border); padding:14px 32px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
        .brand { font-family:'Bebas Neue',sans-serif; font-size:1.6rem; letter-spacing:3px; display:flex; align-items:center; gap:10px; }
        .brand i { color:var(--red); }
        .nav-links { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .nav-link { color:var(--muted); text-decoration:none; font-size:0.83rem; display:flex; align-items:center; gap:6px; padding:7px 13px; border-radius:8px; transition:all 0.2s; border:1px solid transparent; white-space:nowrap; }
        .nav-link:hover { color:var(--text); border-color:var(--border); }
        .nav-link.active { color:#ff8080; border-color:rgba(201,42,42,0.3); background:rgba(201,42,42,0.08); }
        .page { max-width:1200px; margin:0 auto; padding:32px 24px; }
        .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
        .page-title { font-family:'Bebas Neue',sans-serif; font-size:1.8rem; letter-spacing:2px; display:flex; align-items:center; gap:10px; }
        .page-title i { color:var(--red); }
        .summary-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:16px; margin-bottom:32px; }
        .summary-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:20px; }
        .summary-card .num { font-family:'Bebas Neue',sans-serif; font-size:2.4rem; color:var(--red); line-height:1; }
        .summary-card .lbl { font-size:0.75rem; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:1px; }
        .summary-card .sub { font-size:0.8rem; color:var(--muted); margin-top:8px; }
        .summary-card .sub span { color:var(--text); font-weight:600; }
        .section { margin-bottom:32px; }
        .section-title { font-size:0.85rem; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:var(--muted); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .section-title i { color:var(--red); }
        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        @media(max-width:800px){ .two-col { grid-template-columns:1fr; } }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:22px; }
        .cat-row { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
        .cat-row:last-child { margin-bottom:0; }
        .cat-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; flex-shrink:0; }
        .cat-info { flex:1; }
        .cat-name { font-size:0.88rem; font-weight:600; text-transform:capitalize; margin-bottom:4px; }
        .cat-bar-wrap { height:6px; background:rgba(255,255,255,0.06); border-radius:3px; overflow:hidden; }
        .cat-bar { height:100%; border-radius:3px; }
        .cat-nums { font-size:0.8rem; color:var(--muted); margin-top:3px; }
        .cat-nums span { color:var(--text); font-weight:600; }
        table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        th { padding:9px 12px; text-align:left; font-size:0.7rem; letter-spacing:1px; text-transform:uppercase; color:var(--muted); border-bottom:1px solid var(--border); }
        td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,0.04); }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:rgba(255,255,255,0.02); }
        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:0.7rem; font-weight:600; }
        .badge-police   { background:rgba(52,144,220,0.15); color:#74b9ff; }
        .badge-medical  { background:rgba(40,167,69,0.15);  color:#6fcf97; }
        .badge-fire     { background:rgba(253,126,20,0.15); color:#fda94f; }
        .badge-disaster { background:rgba(255,193,7,0.15);  color:#ffd43b; }
        .mini-bar { height:4px; background:rgba(255,255,255,0.06); border-radius:2px; margin-top:4px; }
        .mini-fill { height:100%; border-radius:2px; background:var(--red); }
        .month-row { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.04); }
        .month-row:last-child { border-bottom:none; }
        .month-name { font-size:0.88rem; }
        .month-count { font-family:'Bebas Neue',sans-serif; font-size:1.2rem; color:var(--red); }
        .btn-print { background:rgba(255,255,255,0.06); border:1px solid var(--border); color:var(--text); padding:10px 20px; border-radius:8px; cursor:pointer; font-family:'DM Sans',sans-serif; font-size:0.88rem; display:flex; align-items:center; gap:8px; transition:all 0.2s; }
        .btn-print:hover { background:rgba(255,255,255,0.1); }
        .empty { color:var(--muted); font-size:0.85rem; text-align:center; padding:20px; }
        @media print {
            .navbar, .btn-print { display:none; }
            body { background:white; color:black; }
            .card, .summary-card { border:1px solid #ddd; background:white; }
            .num, .month-count { color:#c92a2a; }
            .cat-bar, .mini-fill { print-color-adjust:exact; -webkit-print-color-adjust:exact; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="brand"><i class="fas fa-phone-volume"></i> FastLine</div>
    <div class="nav-links">
        <a href="add_hotline.php" class="nav-link"><i class="fas fa-phone-alt"></i> Hotlines</a>
        <a href="reports.php" class="nav-link active"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i> Notifications</a>
        <a href="logs.php" class="nav-link"><i class="fas fa-history"></i> Activity Logs</a>
        <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> View Site</a>
        <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="page">
    <div class="page-header">
        <div class="page-title"><i class="fas fa-chart-bar"></i> System Reports</div>
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="num"><?php echo $total; ?></div>
            <div class="lbl">Total Hotlines</div>
            <div class="sub"><span><?php echo $active; ?></span> active · <span><?php echo $inactive; ?></span> inactive</div>
        </div>
        <?php foreach ($byCategory as $cat):
            $c = $catColors[$cat['category']] ?? ['bg'=>'rgba(255,255,255,0.1)','color'=>'#fff','icon'=>'fa-phone'];
        ?>
        <div class="summary-card">
            <div class="num" style="color:<?php echo $c['color']; ?>"><?php echo $cat['total']; ?></div>
            <div class="lbl"><?php echo ucfirst($cat['category']); ?></div>
            <div class="sub"><span><?php echo $cat['active']; ?></span> active</div>
        </div>
        <?php endforeach; ?>
        <div class="summary-card">
            <div class="num"><?php echo $totalUsers; ?></div>
            <div class="lbl">Registered Users</div>
        </div>
    </div>

    <!-- Category + City -->
    <div class="two-col section">
        <div class="card">
            <div class="section-title"><i class="fas fa-tags"></i> Hotlines by Category</div>
            <?php foreach ($byCategory as $cat):
                $c   = $catColors[$cat['category']] ?? ['bg'=>'rgba(255,255,255,0.1)','color'=>'#fff','icon'=>'fa-phone'];
                $pct = $total > 0 ? round(($cat['total'] / $total) * 100) : 0;
            ?>
            <div class="cat-row">
                <div class="cat-icon" style="background:<?php echo $c['bg']; ?>; color:<?php echo $c['color']; ?>">
                    <i class="fas <?php echo $c['icon']; ?>"></i>
                </div>
                <div class="cat-info">
                    <div class="cat-name"><?php echo ucfirst($cat['category']); ?></div>
                    <div class="cat-bar-wrap">
                        <div class="cat-bar" style="width:<?php echo $pct; ?>%; background:<?php echo $c['color']; ?>"></div>
                    </div>
                    <div class="cat-nums"><span><?php echo $cat['total']; ?></span> total · <span><?php echo $cat['active']; ?></span> active · <?php echo $pct; ?>%</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="section-title"><i class="fas fa-map-marker-alt"></i> Hotlines by City</div>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>City</th><th>Total</th><th>Active</th><th>Coverage</th></tr></thead>
                    <tbody>
                        <?php
                        $maxCity = !empty($byCity) ? ($byCity[0]['total'] ?? 1) : 1;
                        foreach ($byCity as $row):
                            $pct = round(($row['total'] / $maxCity) * 100);
                        ?>
                        <tr>
                            <td><?php echo ucfirst(str_replace('_', ' ', $row['city'])); ?></td>
                            <td><strong><?php echo $row['total']; ?></strong></td>
                            <td><?php echo $row['active']; ?></td>
                            <td style="width:80px"><div class="mini-bar"><div class="mini-fill" style="width:<?php echo $pct; ?>%"></div></div></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Favorites + Users by Month -->
    <div class="two-col section">
        <div class="card">
            <div class="section-title"><i class="fas fa-star"></i> Most Favorited Hotlines</div>
            <?php if (empty($topFavorites) || $topFavorites[0]['favorite_count'] == 0): ?>
                <div class="empty">No favorites recorded yet.</div>
            <?php else: ?>
            <table>
                <thead><tr><th>Hotline</th><th>Category</th><th>Favorites</th></tr></thead>
                <tbody>
                    <?php foreach ($topFavorites as $fav): if ($fav['favorite_count'] == 0) continue; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fav['name']); ?></td>
                        <td><span class="badge badge-<?php echo $fav['category']; ?>"><?php echo ucfirst($fav['category']); ?></span></td>
                        <td><strong><?php echo $fav['favorite_count']; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="section-title"><i class="fas fa-users"></i> User Registrations by Month</div>
            <?php if (empty($usersByMonth)): ?>
                <div class="empty">No users registered yet.</div>
            <?php else: ?>
                <?php foreach ($usersByMonth as $row): ?>
                <div class="month-row">
                    <div class="month-name"><?php echo $row['month']; ?></div>
                    <div class="month-count"><?php echo $row['total']; ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div style="text-align:right; color:var(--muted); font-size:0.78rem; margin-top:8px;">
        Report generated: <?php echo date('F d, Y h:i A'); ?>
    </div>
</div>

</body>
</html>