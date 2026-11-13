
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

// DELETE MEDICINE
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_medicines.php");
    exit;
}

checkToken();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    message("Invalid medicine ID.");
} else {
    $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount()) {
        message("Medicine deleted successfully!");
    } else {
        message("Medicine record not found.");
    }
}

header("Location: view_medicines.php");
exit;
?>
