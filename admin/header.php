<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastLine</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header-->
    <header class="header">
        <div class="container header-inner">
            <div class="logo">
                <i class="fas fa-phone-volume"></i>
                <h1>Fastline</h1>
            </div>
            <div class="header-nav">
                <?php if (isset($_SESSION['user'])): ?>
                    <span class="user-greeting">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>
                    </span>
                    <a href="admin/user_logout.php" class="nav-btn nav-btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php elseif (isset($_SESSION['admin'])): ?>
                    <span class="user-greeting">
                        <i class="fas fa-shield-alt"></i>
                        <?php echo htmlspecialchars($_SESSION['admin']); ?>
                    </span>
                    <a href="admin/add_hotline.php" class="nav-btn nav-btn-outline">
                        <i class="fas fa-cog"></i> Admin Panel
                    </a>
                <?php else: ?>
                    <a href="admin/user_login.php" class="nav-btn nav-btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </a>
                    <a href="admin/signup.php" class="nav-btn nav-btn-solid">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!--Hero Section-->
    <section class="hero">
        <div class="container">
            <h2>Emergency Numbers at Your Fingertips</h2>
            <p>Quick access to local emergency services</p>
        </div>
    </section>

    <!--Location Selection-->
    <section class="location-section">
        <div class="container">
            <div class="location-card">
                <h3><i class="fas fa-map-marker-alt"></i> Select Your Location</h3>
                <div class="location-buttons">
                    <button id="useCurrentLocation" class="btn btn-primary">
                        <i class="fas fa-crosshairs"></i> Use Current Location
                    </button>
                    <button id="useManualLocation" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Select Manually
                    </button>
                </div>

                <div id="manualLocationForm" class="manual-location hidden">
                    <select id="citySelect" class="form-control">
                        <option value="">Select City</option>
                    </select>
                    <select id="barangaySelect" class="form-control">
                        <option value="">Select Barangay</option>
                    </select>
                    <button id="confirmLocation" class="btn btn-primary">Confirm Location</button>
                </div>

                <div id="currentLocationDisplay" class="current-location hidden">
                    <p><strong>Current Location:</strong> <span id="locationText">Not set</span></p>
                </div>
            </div>
        </div>
    </section>

    <!--Search Feature-->
    <section class="search-section">
        <div class="container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search for emergency services (e.g., police, ambulance, fire)">
            </div>
        </div>
    </section>

    <!--Emergency Categories Tabs-->
    <section class="categories-section">
        <div class="container">
            <div class="tabs">
                <button class="tab-button active" data-tab="police">
                    <i class="fas fa-shield-alt"></i>
                    <span>Police & Security</span>
                </button>
                <button class="tab-button" data-tab="medical">
                    <i class="fas fa-ambulance"></i>
                    <span>Medical & Health</span>
                </button>
                <button class="tab-button" data-tab="fire">
                    <i class="fas fa-fire-extinguisher"></i>
                    <span>Fire & Rescue</span>
                </button>
                <button class="tab-button" data-tab="disaster">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Disaster & Safety</span>
                </button>
                <button class="tab-button" data-tab="favorites">
                    <i class="fas fa-star"></i>
                    <span>Favorites</span>
                </button>
            </div>
