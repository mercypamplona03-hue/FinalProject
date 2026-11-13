
<?php
session_start();

require 'db_connect.php';

// ==============================
// HELPER FUNCTIONS
// ==============================

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

function token() {
    return $_SESSION['token'];
}

function checkToken() {
    if (!hash_equals(token(), $_POST['token'] ?? '')) {
        die('Invalid request. Please refresh the page.');
    }
}

function message($text) {
    $_SESSION['message'] = $text;
}

function showMessage() {
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-success">' .
             e($_SESSION['message']) . '</div>';

        unset($_SESSION['message']);
    }
}

// ==============================
// DASHBOARD STATISTICS
// ==============================

$total = $pdo->query("
    SELECT COUNT(*) FROM medicines
")->fetchColumn();

$stock = $pdo->query("
    SELECT COALESCE(SUM(quantity), 0) FROM medicines
")->fetchColumn();

$low = $pdo->query("
    SELECT COUNT(*) FROM medicines
    WHERE quantity <= 10
")->fetchColumn();

$expired = $pdo->query("
    SELECT COUNT(*) FROM medicines
    WHERE expiry_date < CURDATE()
")->fetchColumn();

$near = $pdo->query("
    SELECT COUNT(*) FROM medicines
    WHERE expiry_date >= CURDATE()
    AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>MediStock Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="style.css">

    <style>
        .dashboard-card-link {
            display: block;
            height: 100%;
            color: inherit;
            text-decoration: none;
        }

        .dashboard-card-link .card-box {
            height: 100%;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .dashboard-card-link:hover .card-box {
            transform: translateY(-5px);
            border-color: #35f0c1;
            box-shadow: 0 5px 18px rgba(53, 240, 193, 0.15);
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="brand">
        <i class="bi bi-capsule"></i> MediStock
    </div>

    <a href="index.php" class="active">
        <i class="bi bi-grid me-2"></i> Dashboard
    </a>

    <a href="add_medicine.php">
        <i class="bi bi-plus-circle me-2"></i> Add Medicine
    </a>

</div>

<!-- MAIN CONTENT -->
<div class="main">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h3 class="fw-bold">Pharmacy Dashboard</h3>

            <div class="subtitle">
                Manage your medicines and monitor inventory.
            </div>
        </div>

        <span class="badge badge-stock p-2">
            ● System Active
        </span>

    </div>

    <?php showMessage(); ?>

    <!-- DASHBOARD CARDS -->
    <div class="row g-3">

        <!-- TOTAL MEDICINES -->
        <div class="col-6 col-lg-3">

            <a href="view_medicines.php" class="dashboard-card-link">

                <div class="card-box">

                    <div class="stat-icon">
                        <i class="bi bi-capsule"></i>
                    </div>

                    <div class="stat-number">
                        <?= $total ?>
                    </div>

                    <div class="stat-label">
                        Total Medicines
                    </div>

                </div>

            </a>

        </div>

        <!-- TOTAL STOCK -->
        <div class="col-6 col-lg-3">

            <a href="view_medicines.php" class="dashboard-card-link">

                <div class="card-box">

                    <div class="stat-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <div class="stat-number">
                        <?= $stock ?>
                    </div>

                    <div class="stat-label">
                        Total Stock
                    </div>

                </div>

            </a>

        </div>

        <!-- LOW STOCK -->
        <div class="col-6 col-lg-3">

            <a href="view_medicines.php?filter=low" class="dashboard-card-link">

                <div class="card-box">

                    <div class="stat-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div class="stat-number">
                        <?= $low ?>
                    </div>

                    <div class="stat-label">
                        Low Stock Medicines
                    </div>

                </div>

            </a>

        </div>

        <!-- EXPIRED MEDICINES -->
        <div class="col-6 col-lg-3">

            <a href="view_medicines.php?filter=expired" class="dashboard-card-link">

                <div class="card-box">

                    <div class="stat-icon">
                        <i class="bi bi-calendar-x"></i>
                    </div>

                    <div class="stat-number">
                        <?= $expired ?>
                    </div>

                    <div class="stat-label">
                        Expired Medicines
                    </div>

                </div>

            </a>

        </div>

    </div>

    <!-- EXPIRATION MONITORING -->
    <div class="card-box mt-3">

        <div class="d-flex justify-content-between align-items-center">

            <div>
                <h5 class="fw-bold">Expiration Monitoring</h5>

                <div class="subtitle">
                    Medicines expiring within 30 days
                </div>
            </div>

            <h2 style="color:#ffffff">
                <?= $near ?>
            </h2>

        </div>

    </div>

    <!-- QUICK ACTIONS -->
    <div class="row g-3 mt-2">

        <div class="col-md-6">

            <a href="add_medicine.php" class="btn btn-green w-100 p-3">

                <i class="bi bi-plus-circle me-2"></i>
                Add New Medicine

            </a>

        </div>

        <div class="col-md-6">

            <a href="view_medicines.php" class="btn btn-outline-green w-100 p-3">

                <i class="bi bi-box-seam me-2"></i>
                Manage Inventory

            </a>

        </div>

    </div>

    <!-- FOOTER -->
    <footer class="subtitle text-center mt-5">
        ---Pharmacy Inventory System---
    </footer>

</div>

</body>
</html>
