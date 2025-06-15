<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if ($id === null || !is_numeric($id)) {
    $_SESSION['hata_mesaji'] = "Geçersiz ağaç ID'si.";
} else {
    $sql_delete = "DELETE FROM AGACLAR WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $id);
        if ($stmt_delete->execute()) {
            $_SESSION['basari_mesaji'] = "Ağaç bilgisi başarıyla silindi.";
        } else {
            $_SESSION['hata_mesaji'] = "Silme sırasında bir hata oluştu: " . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $_SESSION['hata_mesaji'] = "Sorgu hazırlanamadı: " . $conn->error;
    }
}

$conn->close();
header("location: dashboard.php");
exit;
?>