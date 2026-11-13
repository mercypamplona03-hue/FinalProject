
<?php
session_start();
require 'db_connect.php';

// FUNCTIONS
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

// SEARCH AND FILTER
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? '';

$sql = "SELECT * FROM medicines WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (medicine_name LIKE ? OR generic_name LIKE ? OR category LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term];
}

if ($filter === 'low') {
    $sql .= " AND quantity <= 10";
} elseif ($filter === 'expired') {
    $sql .= " AND expiry_date < CURDATE()";
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Medicine Inventory - MediStock</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="sidebar">

    <div class="brand">
        <i class="bi bi-capsule"></i> MediStock
    </div>

    <a href="index.php">
        <i class="bi bi-grid me-2"></i> Dashboard
    </a>

    <a href="add_medicine.php">
        <i class="bi bi-plus-circle me-2"></i> Add Medicine
    </a>

    <a href="view_medicines.php" class="active">
        <i class="bi bi-box-seam me-2"></i> View Inventory
    </a>

    <a href="view_medicines.php?filter=low">
        <i class="bi bi-exclamation-triangle me-2"></i> Low Stock
    </a>

    <a href="view_medicines.php?filter=expired">
        <i class="bi bi-calendar-x me-2"></i> Expired Medicines
    </a>

</div>

<div class="main">

    <h3 class="fw-bold">Medicine Inventory</h3>
    <p class="subtitle mb-4">View, search, edit, and delete medicine records.</p>

    <?php showMessage(); ?>

    <div class="card-box">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h5 class="fw-bold mb-0">Medicine Records</h5>

            <a href="add_medicine.php" class="btn btn-green">
                <i class="bi bi-plus-circle me-2"></i> Add Medicine
            </a>

        </div>

        <!-- SEARCH -->
        <form method="GET" class="d-flex gap-2 mb-4" style="max-width:450px">

            <input type="hidden" name="filter" value="<?= e($filter) ?>">

            <input type="search" name="search" class="form-control"
                placeholder="Search medicine name..."
                value="<?= e($search) ?>">

            <button type="submit" class="btn btn-green">
                <i class="bi bi-search"></i>
            </button>

            <a href="view_medicines.php" class="btn btn-outline-green">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>

        </form>

        <!-- FILTER MESSAGE -->
        <?php if ($filter === 'low'): ?>

            <p class="subtitle">Showing low stock medicines (10 or fewer).</p>

        <?php elseif ($filter === 'expired'): ?>

            <p class="subtitle">Showing expired medicines.</p>

        <?php endif; ?>

        <!-- MEDICINE TABLE -->
        <div class="table-responsive">

            <table class="table table-hover">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Medicine</th>
                        <th>Category</th>
                        <th>Batch No.</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Expiry Date</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($medicines): ?>

                    <?php foreach ($medicines as $m): ?>

                        <?php
                        $today = date('Y-m-d');

                        $isExpired = $m['expiry_date'] < $today;

                        $isNear = !$isExpired &&
                            $m['expiry_date'] <= date('Y-m-d', strtotime('+30 days'));

                        if ($isExpired) {
                            $status = 'Expired';
                            $badge = 'badge-expired';
                        } elseif ($isNear) {
                            $status = 'Near Expiry';
                            $badge = 'badge-near';
                        } elseif ($m['quantity'] <= 10) {
                            $status = 'Low Stock';
                            $badge = 'badge-low';
                        } else {
                            $status = 'In Stock';
                            $badge = 'badge-stock';
                        }
                        ?>

                        <tr>

                            <td><?= (int)$m['id'] ?></td>

                            <td>
                                <strong><?= e($m['medicine_name']) ?></strong>
                                <div class="subtitle">
                                    <?= e($m['generic_name']) ?>
                                </div>
                            </td>

                            <td><?= e($m['category']) ?></td>

                            <td><?= e($m['batch_no']) ?></td>

                            <td><?= (int)$m['quantity'] ?></td>

                            <td>
                                ₱<?= number_format((float)$m['unit_price'], 2) ?>
                            </td>

                            <td><?= e($m['expiry_date']) ?></td>

                            <td><?= e($m['supplier']) ?></td>

                            <td>
                                <span class="badge <?= e($badge) ?>">
                                    <?= e($status) ?>
                                </span>
                            </td>

                            <td>

                                <div class="d-flex gap-2">

                                    <!-- EDIT BUTTON -->
                                    <a href="edit_medicine.php?id=<?= (int)$m['id'] ?>"
                                        class="btn btn-sm btn-outline-green">

                                        <i class="bi bi-pencil"></i>

                                    </a>

                                    <!-- DELETE BUTTON -->
                                    <form action="delete_medicine.php" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this medicine?')">

                                        <input type="hidden" name="token"
                                            value="<?= e(token()) ?>">

                                        <input type="hidden" name="id"
                                            value="<?= (int)$m['id'] ?>">

                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="10" class="text-center py-5">

                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>

                            No medicines found.

                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="subtitle">
            Showing <?= count($medicines) ?> medicine record(s).
        </div>

    </div>
</div>

</body>
</html>
