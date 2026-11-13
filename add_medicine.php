
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
// ADD MEDICINE
// ==============================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    checkToken();

    $name = trim($_POST['medicine_name'] ?? '');
    $generic = trim($_POST['generic_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $batch = trim($_POST['batch_no'] ?? '');
    $quantity = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT);
    $price = $_POST['unit_price'] ?? '';
    $expiry = $_POST['expiry_date'] ?? '';
    $supplier = trim($_POST['supplier'] ?? '');

    if (
        !$name || !$generic || !$category || !$batch ||
        $quantity === false || $quantity < 0 ||
        !is_numeric($price) || $price < 0 ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry) ||
        !checkdate(
            (int)substr($expiry, 5, 2),
            (int)substr($expiry, 8, 2),
            (int)substr($expiry, 0, 4)
        ) ||
        !$supplier
    ) {
        $error = "Please complete all fields correctly.";

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO medicines
            (medicine_name, generic_name, category, batch_no,
             quantity, unit_price, expiry_date, supplier)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $generic,
            $category,
            $batch,
            $quantity,
            $price,
            $expiry,
            $supplier
        ]);

        message("Medicine added successfully!");

        header("Location: view_medicines.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Add Medicine - MediStock</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="brand">
        <i class="bi bi-capsule"></i> MediStock
    </div>

    <a href="index.php">
        <i class="bi bi-grid me-2"></i> Dashboard
    </a>

    <a href="add_medicine.php" class="active">
        <i class="bi bi-plus-circle me-2"></i> Add Medicine
    </a>

    <a href="view_medicines.php">
        <i class="bi bi-box-seam me-2"></i> View Inventory
    </a>

</div>

<!-- MAIN CONTENT -->
<div class="main">

    <h3 class="fw-bold">Add New Medicine</h3>

    <p class="subtitle mb-4">
        Register a new medicine in your pharmacy inventory.
    </p>

    <!-- ERROR MESSAGE -->
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- ADD MEDICINE FORM -->
    <div class="card-box">

        <h5 class="fw-bold mb-4">
            <i class="bi bi-capsule me-2"></i>
            Medicine Information
        </h5>

        <form method="POST">

            <input type="hidden" name="token" value="<?= e(token()) ?>">

            <div class="row g-3">

                <!-- MEDICINE NAME -->
                <div class="col-md-6">

                    <label class="form-label">Medicine Name</label>

                    <input
                        type="text"
                        name="medicine_name"
                        class="form-control"
                        required
                        maxlength="150"
                        value="<?= e($_POST['medicine_name'] ?? '') ?>"
                        placeholder="Enter medicine name"
                    >

                </div>

                <!-- GENERIC NAME -->
                <div class="col-md-6">

                    <label class="form-label">Generic Name</label>

                    <input
                        type="text"
                        name="generic_name"
                        class="form-control"
                        required
                        maxlength="150"
                        value="<?= e($_POST['generic_name'] ?? '') ?>"
                        placeholder="Enter generic name"
                    >

                </div>

                <!-- CATEGORY -->
                <div class="col-md-6">

                    <label class="form-label">Category</label>

                    <select name="category" class="form-select" required>

                        <option value="">Select Category</option>

                        <?php foreach ([
                            'Pain Reliever',
                            'Antibiotic',
                            'Cold Medicine',
                            'Antihistamine',
                            'Vitamins',
                            'First Aid',
                            'Other'
                        ] as $cat): ?>

                            <option
                                value="<?= e($cat) ?>"
                                <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>
                            >
                                <?= e($cat) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- BATCH NUMBER -->
                <div class="col-md-6">

                    <label class="form-label">Batch Number</label>

                    <input
                        type="text"
                        name="batch_no"
                        class="form-control"
                        required
                        maxlength="100"
                        value="<?= e($_POST['batch_no'] ?? '') ?>"
                        placeholder="Enter batch number"
                    >

                </div>

                <!-- QUANTITY -->
                <div class="col-md-6">

                    <label class="form-label">Quantity</label>

                    <input
                        type="number"
                        name="quantity"
                        class="form-control"
                        min="0"
                        required
                        value="<?= e($_POST['quantity'] ?? '') ?>"
                        placeholder="Enter quantity"
                    >

                </div>

                <!-- UNIT PRICE -->
                <div class="col-md-6">

                    <label class="form-label">Unit Price (₱)</label>

                    <input
                        type="number"
                        name="unit_price"
                        class="form-control"
                        min="0"
                        step="0.01"
                        required
                        value="<?= e($_POST['unit_price'] ?? '') ?>"
                        placeholder="0.00"
                    >

                </div>

                <!-- EXPIRY DATE -->
                <div class="col-md-6">

                    <label class="form-label">Expiry Date</label>

                    <input
                        type="date"
                        name="expiry_date"
                        class="form-control"
                        required
                        value="<?= e($_POST['expiry_date'] ?? '') ?>"
                    >

                </div>

                <!-- SUPPLIER -->
                <div class="col-md-6">

                    <label class="form-label">Supplier</label>

                    <input
                        type="text"
                        name="supplier"
                        class="form-control"
                        required
                        maxlength="150"
                        value="<?= e($_POST['supplier'] ?? '') ?>"
                        placeholder="Enter supplier"
                    >

                </div>

                <!-- BUTTONS -->
                <div class="col-12 mt-4">

                    <button type="submit" class="btn btn-green px-4">

                        <i class="bi bi-save me-2"></i>
                        Save Medicine

                    </button>

                    <a href="index.php" class="btn btn-outline-green ms-2">
                        Cancel
                    </a>

                </div>

            </div>

        </form>

    </div>

</div>

</body>
</html>
