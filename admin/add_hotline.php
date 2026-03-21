<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
require '../includes/db.php';

$message  = '';
$error    = '';
$editData = null;

// ── ADD ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name        = trim($_POST['name']        ?? '');
    $category    = $_POST['category']          ?? '';
    $phone       = trim($_POST['phone']        ?? '');
    $description = trim($_POST['description']  ?? '');
    $city        = $_POST['city']              ?? 'national';
    $barangay    = $_POST['barangay']          ?? 'all';

    if (empty($name) || empty($category) || empty($phone)) {
        $error = 'Name, category, and phone are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO hotlines (name, category, phone, description, city, barangay, is_active) VALUES (?,?,?,?,?,?,1)");
        $stmt->execute([$name, $category, $phone, $description, $city, $barangay ?: 'all']);
        $admin = $_SESSION['admin'] ?? 'unknown';
        $pdo->prepare("INSERT INTO activity_logs (admin_username, action, details) VALUES (?,?,?)")
            ->execute([$admin, 'Added Hotline', "\"$name\" ($category)"]);
        $message = "Hotline \"" . htmlspecialchars($name) . "\" added successfully!";
    }
}

// ── EDIT ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id          = (int)($_POST['id']          ?? 0);
    $name        = trim($_POST['name']         ?? '');
    $category    = $_POST['category']           ?? '';
    $phone       = trim($_POST['phone']         ?? '');
    $description = trim($_POST['description']   ?? '');
    $city        = $_POST['city']               ?? 'national';
    $barangay    = $_POST['barangay']           ?? 'all';

    if (empty($name) || empty($category) || empty($phone)) {
        $error    = 'Name, category, and phone are required.';
        $stmt     = $pdo->prepare("SELECT * FROM hotlines WHERE id = ?");
        $stmt->execute([$id]);
        $editData = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $pdo->prepare("UPDATE hotlines SET name=?, category=?, phone=?, description=?, city=?, barangay=? WHERE id=?")
            ->execute([$name, $category, $phone, $description, $city, $barangay ?: 'all', $id]);
        $admin = $_SESSION['admin'] ?? 'unknown';
        $pdo->prepare("INSERT INTO activity_logs (admin_username, action, details) VALUES (?,?,?)")
            ->execute([$admin, 'Updated Hotline', "\"$name\" ($category) — ID #$id"]);
        $message = "Hotline \"" . htmlspecialchars($name) . "\" updated successfully!";
    }
}

// ── TOGGLE ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE hotlines SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    $admin = $_SESSION['admin'] ?? 'unknown';
    $pdo->prepare("INSERT INTO activity_logs (admin_username, action, details) VALUES (?,?,?)")
        ->execute([$admin, 'Toggled Hotline', "ID #$id"]);
    header('Location: add_hotline.php');
    exit;
}

// ── LOAD EDIT FORM ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM hotlines WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ── FETCH DATA ────────────────────────────────────────────
$hotlines = $pdo->query("SELECT * FROM hotlines ORDER BY category, city")->fetchAll(PDO::FETCH_ASSOC);
$users    = $pdo->query("SELECT id, full_name, username, email, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastLine — Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap');
        :root {
            --red:#c92a2a; --red-dark:#a61e1e; --red-glow:rgba(201,42,42,0.25);
            --bg:#0d0d0d; --surface:#161616; --surface2:#1e1e1e;
            --border:rgba(255,255,255,0.08); --text:#f0f0f0; --muted:#888;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }

        /* ── Top Navbar ── */
        .navbar { background:var(--surface); border-bottom:1px solid var(--border); padding:14px 32px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
        .brand { font-family:'Bebas Neue',sans-serif; font-size:1.6rem; letter-spacing:3px; display:flex; align-items:center; gap:10px; }
        .brand i { color:var(--red); }
        .nav-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .admin-chip { background:rgba(201,42,42,0.12); border:1px solid rgba(201,42,42,0.25); color:#ff8080; font-size:0.75rem; font-weight:600; letter-spacing:1px; text-transform:uppercase; padding:5px 12px; border-radius:20px; }
        .nav-link { background:transparent; border:1px solid var(--border); color:var(--muted); padding:7px 14px; border-radius:8px; cursor:pointer; font-size:0.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s; font-family:'DM Sans',sans-serif; }
        .nav-link:hover { border-color:var(--red); color:#ff8080; }

        /* ── Secondary Nav ── */
        .subnav { background:var(--surface); border-bottom:1px solid var(--border); padding:0 32px; display:flex; align-items:center; gap:0; overflow-x:auto; }
        .subnav-link { padding:13px 20px; cursor:pointer; font-size:0.85rem; font-weight:600; color:var(--muted); text-decoration:none; border-bottom:2px solid transparent; transition:all 0.2s; display:flex; align-items:center; gap:7px; white-space:nowrap; }
        .subnav-link:hover { color:var(--text); }
        .subnav-link.active { color:#ff8080; border-bottom-color:var(--red); }
        .subnav-link .count { background:rgba(201,42,42,0.15); color:#ff8080; font-size:0.7rem; padding:2px 7px; border-radius:10px; }
        .subnav-btn { padding:13px 20px; cursor:pointer; font-size:0.85rem; font-weight:600; color:var(--muted); border:none; background:none; border-bottom:2px solid transparent; transition:all 0.2s; display:flex; align-items:center; gap:7px; white-space:nowrap; font-family:'DM Sans',sans-serif; }
        .subnav-btn:hover { color:var(--text); }
        .subnav-btn.active { color:#ff8080; border-bottom-color:var(--red); }

        /* ── Pages ── */
        .page { max-width:1200px; margin:0 auto; padding:32px 24px; display:none; }
        .page.active { display:block; }
        .page-title { font-family:'Bebas Neue',sans-serif; font-size:1.8rem; letter-spacing:2px; margin-bottom:28px; display:flex; align-items:center; gap:10px; }
        .page-title i { color:var(--red); }

        /* ── Grid ── */
        .grid { display:grid; grid-template-columns:360px 1fr; gap:24px; align-items:start; }
        @media(max-width:900px){ .grid { grid-template-columns:1fr; } }

        /* ── Card ── */
        .card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:26px; }
        .card h2 { font-size:1rem; font-weight:600; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
        .card h2 i { color:var(--red); }

        /* ── Form ── */
        .field { margin-bottom:16px; }
        .field label { display:block; font-size:0.74rem; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
        .field input, .field select, .field textarea { width:100%; background:rgba(255,255,255,0.04); border:1px solid var(--border); border-radius:8px; padding:10px 13px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.92rem; outline:none; transition:border-color 0.2s,box-shadow 0.2s; }
        .field input:focus, .field select:focus, .field textarea:focus { border-color:var(--red); box-shadow:0 0 0 3px var(--red-glow); background:rgba(201,42,42,0.04); }
        .field select option { background:#1e1e1e; }
        .field textarea { resize:vertical; min-height:72px; }
        .btn-submit { width:100%; padding:12px; background:var(--red); color:white; border:none; border-radius:8px; font-family:'DM Sans',sans-serif; font-size:0.95rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:background 0.2s,transform 0.15s; margin-top:4px; }
        .btn-submit:hover { background:var(--red-dark); transform:translateY(-1px); }
        .btn-cancel { width:100%; padding:12px; background:transparent; color:var(--muted); border:1px solid var(--border); border-radius:8px; font-family:'DM Sans',sans-serif; font-size:0.9rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; margin-top:8px; text-decoration:none; }
        .btn-cancel:hover { border-color:var(--muted); color:var(--text); }

        /* ── Alerts ── */
        .alert { padding:12px 16px; border-radius:8px; margin-bottom:18px; display:flex; align-items:center; gap:10px; font-size:0.87rem; }
        .alert-success { background:rgba(40,167,69,0.12); border:1px solid rgba(40,167,69,0.3); color:#6fcf97; }
        .alert-error   { background:rgba(201,42,42,0.1);  border:1px solid rgba(201,42,42,0.3);  color:#ff8080; }

        /* ── Table ── */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:0.86rem; }
        th { padding:10px 14px; text-align:left; font-size:0.7rem; letter-spacing:1px; text-transform:uppercase; color:var(--muted); border-bottom:1px solid var(--border); white-space:nowrap; }
        td { padding:11px 14px; border-bottom:1px solid rgba(255,255,255,0.04); vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:rgba(255,255,255,0.02); }
        tr.inactive td { opacity:0.45; }

        /* ── Badges ── */
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:0.7rem; font-weight:600; text-transform:capitalize; }
        .badge-police   { background:rgba(52,144,220,0.15);  color:#74b9ff; }
        .badge-medical  { background:rgba(40,167,69,0.15);   color:#6fcf97; }
        .badge-fire     { background:rgba(253,126,20,0.15);  color:#fda94f; }
        .badge-disaster { background:rgba(255,193,7,0.15);   color:#ffd43b; }
        .badge-active   { background:rgba(40,167,69,0.15);   color:#6fcf97; }
        .badge-inactive { background:rgba(150,150,150,0.12); color:#888; }

        /* ── Action Buttons ── */
        .actions { display:flex; gap:6px; }
        .btn-edit { background:transparent; border:1px solid rgba(255,193,7,0.35); color:#ffd43b; padding:5px 10px; border-radius:6px; cursor:pointer; font-size:0.78rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
        .btn-edit:hover { background:rgba(255,193,7,0.12); border-color:#ffd43b; }
        .btn-toggle { background:transparent; border:1px solid rgba(255,255,255,0.12); color:var(--muted); padding:5px 10px; border-radius:6px; cursor:pointer; font-size:0.78rem; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
        .btn-toggle:hover { border-color:var(--muted); color:var(--text); }
        .btn-delete { background:transparent; border:1px solid rgba(201,42,42,0.3); color:#ff8080; padding:5px 10px; border-radius:6px; cursor:pointer; font-size:0.78rem; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
        .btn-delete:hover { background:rgba(201,42,42,0.15); border-color:var(--red); }

        /* ── Stats ── */
        .stats { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
        .stat-card { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:16px 20px; flex:1; min-width:100px; }
        .stat-card .num { font-family:'Bebas Neue',sans-serif; font-size:2rem; color:var(--red); line-height:1; }
        .stat-card .lbl { font-size:0.75rem; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:1px; }

        /* ── Users ── */
        .user-avatar { width:30px; height:30px; border-radius:50%; background:rgba(201,42,42,0.2); display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:600; color:#ff8080; margin-right:8px; }

        .empty-state { text-align:center; padding:40px; color:var(--muted); }
        .empty-state i { font-size:2.5rem; opacity:0.3; margin-bottom:12px; display:block; }

        .editing-banner { background:rgba(255,193,7,0.08); border:1px solid rgba(255,193,7,0.25); border-radius:10px; padding:10px 14px; margin-bottom:16px; font-size:0.85rem; color:#ffd43b; display:flex; align-items:center; gap:8px; }
    </style>
</head>
<body>

<!-- ── Top Navbar ── -->
<nav class="navbar">
    <div class="brand"><i class="fas fa-phone-volume"></i> FastLine</div>
    <div class="nav-right">
        <span class="admin-chip"><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($_SESSION['admin']); ?></span>
        <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> View Site</a>
        <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<!-- ── Secondary Nav ── -->
<div class="subnav">
    <button class="subnav-btn active" onclick="showPage('hotlines', this)">
        <i class="fas fa-phone-alt"></i> Hotlines
        <span class="count"><?php echo count($hotlines); ?></span>
    </button>
    <button class="subnav-btn" onclick="showPage('users', this)">
        <i class="fas fa-users"></i> Registered Users
        <span class="count"><?php echo count($users); ?></span>
    </button>
    <a href="reports.php" class="subnav-link"><i class="fas fa-chart-bar"></i> Reports</a>
    <a href="notifications.php" class="subnav-link"><i class="fas fa-bell"></i> Notifications</a>
    <a href="logs.php" class="subnav-link"><i class="fas fa-history"></i> Activity Logs</a>
</div>

<!-- ── PAGE 1: HOTLINES ── -->
<div id="page-hotlines" class="page active">
    <div style="max-width:1200px; margin:0 auto; padding:32px 24px;">

        <!-- Stats -->
        <div class="stats">
            <?php
            $activeCount   = count(array_filter($hotlines, fn($h) => $h['is_active'] ?? 1));
            $inactiveCount = count($hotlines) - $activeCount;
            $cats = ['police'=>0,'medical'=>0,'fire'=>0,'disaster'=>0];
            foreach ($hotlines as $h) if (isset($cats[$h['category']])) $cats[$h['category']]++;
            ?>
            <div class="stat-card"><div class="num"><?php echo count($hotlines); ?></div><div class="lbl">Total</div></div>
            <div class="stat-card"><div class="num"><?php echo $activeCount; ?></div><div class="lbl">Active</div></div>
            <div class="stat-card"><div class="num"><?php echo $inactiveCount; ?></div><div class="lbl">Inactive</div></div>
            <div class="stat-card"><div class="num"><?php echo $cats['police']; ?></div><div class="lbl">Police</div></div>
            <div class="stat-card"><div class="num"><?php echo $cats['medical']; ?></div><div class="lbl">Medical</div></div>
            <div class="stat-card"><div class="num"><?php echo $cats['fire']; ?></div><div class="lbl">Fire</div></div>
            <div class="stat-card"><div class="num"><?php echo $cats['disaster']; ?></div><div class="lbl">Disaster</div></div>
        </div>

        <div class="grid">
            <!-- ADD / EDIT FORM -->
            <div class="card">
                <?php if ($editData): ?>
                    <div class="editing-banner"><i class="fas fa-pencil-alt"></i> Editing hotline #<?php echo $editData['id']; ?></div>
                    <h2><i class="fas fa-pencil-alt"></i> Edit Hotline</h2>
                <?php else: ?>
                    <h2><i class="fas fa-plus"></i> Add New Hotline</h2>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="<?php echo $editData ? 'edit' : 'add'; ?>">
                    <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <?php endif; ?>

                    <div class="field">
                        <label>Hotline Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Angeles City Police"
                               value="<?php echo htmlspecialchars($editData['name'] ?? $_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select category</option>
                            <?php foreach(['police','medical','fire','disaster'] as $cat):
                                $sel = (($editData['category'] ?? $_POST['category'] ?? '') === $cat) ? 'selected' : ''; ?>
                            <option value="<?php echo $cat; ?>" <?php echo $sel; ?>><?php echo ucfirst($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Phone Number *</label>
                        <input type="text" name="phone" required placeholder="e.g. (045) 322-2870"
                               value="<?php echo htmlspecialchars($editData['phone'] ?? $_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label>Description</label>
                        <textarea name="description" placeholder="Short description"><?php echo htmlspecialchars($editData['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="field">
                        <label>City</label>
                        <select name="city">
                            <?php
                            $cities = ['national'=>'National','san_fernando'=>'City of San Fernando','angeles'=>'Angeles City','mabalacat'=>'Mabalacat City','mexico'=>'Mexico','lubao'=>'Lubao','apalit'=>'Apalit','guagua'=>'Guagua','porac'=>'Porac','bacolor'=>'Bacolor','candaba'=>'Candaba','floridablanca'=>'Floridablanca','arayat'=>'Arayat','san_simon'=>'San Simon','sto_tomas'=>'Sto. Tomas','san_luis'=>'San Luis'];
                            $curCity = $editData['city'] ?? $_POST['city'] ?? 'national';
                            foreach ($cities as $val => $label):
                                $sel = $curCity === $val ? 'selected' : '';
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $sel; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Barangay <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                        <input type="text" name="barangay" placeholder="Leave blank for city-wide"
                               value="<?php $bVal = $editData ? ($editData['barangay'] === 'all' ? '' : $editData['barangay']) : ($_POST['barangay'] ?? ''); echo htmlspecialchars($bVal); ?>">
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-<?php echo $editData ? 'save' : 'plus-circle'; ?>"></i>
                        <?php echo $editData ? 'Save Changes' : 'Add Hotline'; ?>
                    </button>
                    <?php if ($editData): ?>
                        <a href="add_hotline.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- HOTLINES TABLE -->
            <div class="card">
                <h2><i class="fas fa-list"></i> All Hotlines (<?php echo count($hotlines); ?>)</h2>
                <?php if (empty($hotlines)): ?>
                    <div class="empty-state"><i class="fas fa-phone-slash"></i><p>No hotlines yet.</p></div>
                <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th><th>Category</th><th>Phone</th><th>City</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hotlines as $h): ?>
                            <tr class="<?php echo ($h['is_active'] ?? 1) ? '' : 'inactive'; ?>">
                                <td><?php echo htmlspecialchars($h['name']); ?></td>
                                <td><span class="badge badge-<?php echo $h['category']; ?>"><?php echo ucfirst($h['category']); ?></span></td>
                                <td><?php echo htmlspecialchars($h['phone']); ?></td>
                                <td><?php echo htmlspecialchars($h['city']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($h['is_active'] ?? 1) ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo ($h['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="add_hotline.php?edit=<?php echo $h['id']; ?>" class="btn-edit">
                                            <i class="fas fa-pencil-alt"></i> Edit
                                        </a>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                                            <button type="submit" class="btn-toggle">
                                                <i class="fas fa-<?php echo ($h['is_active'] ?? 1) ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="delete_hotline.php" style="display:inline"
                                              onsubmit="return confirm('Delete this hotline permanently?')">
                                            <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                                            <button type="submit" class="btn-delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── PAGE 2: USERS ── -->
<div id="page-users" class="page">
    <div style="max-width:1200px; margin:0 auto; padding:32px 24px;">
        <div class="page-title"><i class="fas fa-users"></i> Registered Users</div>
        <div class="card">
            <h2><i class="fas fa-user-circle"></i> All Users (<?php echo count($users); ?>)</h2>
            <?php if (empty($users)): ?>
                <div class="empty-state"><i class="fas fa-user-slash"></i><p>No users have signed up yet.</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>#</th><th>Full Name</th><th>Username</th><th>Email</th><th>Registered</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td style="color:var(--muted)"><?php echo $i + 1; ?></td>
                            <td>
                                <span class="user-avatar"><?php echo strtoupper(substr($u['full_name'], 0, 1)); ?></span>
                                <?php echo htmlspecialchars($u['full_name']); ?>
                            </td>
                            <td style="color:var(--muted)">@<?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td style="color:var(--muted); font-size:0.82rem;"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showPage(name, tab) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.subnav-btn').forEach(t => t.classList.remove('active'));
    document.getElementById('page-' + name).classList.add('active');
    tab.classList.add('active');
}
if (new URLSearchParams(window.location.search).get('tab') === 'users') {
    showPage('users', document.querySelectorAll('.subnav-btn')[1]);
}
</script>

</body>
</html>