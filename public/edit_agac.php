<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;
$agac = null;
$hata = '';
$basarili = '';

if ($id === null || !is_numeric($id)) {
    $hata = "Geçersiz ağaç ID'si.";
} else {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $tur = trim($_POST['tur']);
        $bolge = trim($_POST['bolge']);
        $yas = trim($_POST['yas']);
        $boy = trim($_POST['boy']);
        $cap = trim($_POST['cap']);
        $durum = trim($_POST['durum']);

        if (empty($tur) || empty($bolge) || empty($yas) || empty($boy) || empty($cap) || empty($durum)) {
            $hata = "Lütfen tüm alanları doldurun.";
        } elseif (!is_numeric($yas) || $yas <= 0) {
            $hata = "Yaş geçerli bir sayı olmalıdır.";
        } elseif (!is_numeric($boy) || $boy <= 0) {
            $hata = "Boy geçerli bir sayı olmalıdır.";
        } elseif (!is_numeric($cap) || $cap <= 0) {
            $hata = "Çap geçerli bir sayı olmalıdır.";
        } else {
            $sql_update = "UPDATE AGACLAR SET tur = ?, bolge = ?, yas = ?, boy = ?, cap = ?, durum = ? WHERE id = ?";
            if ($stmt_update = $conn->prepare($sql_update)) {
                $stmt_update->bind_param("ssiddsi", $tur, $bolge, $yas, $boy, $cap, $durum, $id);
                if ($stmt_update->execute()) {
                    $basarili = "Ağaç bilgisi başarıyla güncellendi.";
                } else {
                    $hata = "Güncelleme sırasında bir hata oluştu: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $hata = "Güncelleme sorgusu hazırlanamadı: " . $conn->error;
            }
        }
    }

    $sql_select = "SELECT id, tur, bolge, yas, boy, cap, durum FROM AGACLAR WHERE id = ?";
    if ($stmt_select = $conn->prepare($sql_select)) {
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        if ($result->num_rows == 1) {
            $agac = $result->fetch_assoc();
        } else {
            $hata = "Ağaç bilgisi bulunamadı.";
        }
        $stmt_select->close();
    } else {
        $hata = "Sorgu hazırlanamadı: " . $conn->error;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ağaç Bilgisini Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Ağaç Bilgisini Düzenle</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($hata)): ?>
                            <div class="alert alert-danger"><?php echo $hata; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($basarili)): ?>
                            <div class="alert alert-success"><?php echo $basarili; ?></div>
                        <?php endif; ?>

                        <?php if ($agac): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . htmlspecialchars($agac['id']); ?>" method="post">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tur" class="form-label">Ağaç Türü</label>
                                        <input type="text" class="form-control" id="tur" name="tur" value="<?php echo htmlspecialchars($agac['tur']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="bolge" class="form-label">Bölge/Parsel</label>
                                        <input type="text" class="form-control" id="bolge" name="bolge" value="<?php echo htmlspecialchars($agac['bolge']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="yas" class="form-label">Yaş (Yıl)</label>
                                        <input type="number" class="form-control" id="yas" name="yas" value="<?php echo htmlspecialchars($agac['yas']); ?>" required min="1">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="boy" class="form-label">Boy (Metre)</label>
                                        <input type="number" step="0.01" class="form-control" id="boy" name="boy" value="<?php echo htmlspecialchars($agac['boy']); ?>" required min="0.01">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="cap" class="form-label">Çap (CM)</label>
                                        <input type="number" step="0.01" class="form-control" id="cap" name="cap" value="<?php echo htmlspecialchars($agac['cap']); ?>" required min="0.01">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="durum" class="form-label">Durum</label>
                                    <select class="form-select" id="durum" name="durum" required>
                                        <option value="Sağlıklı" <?php echo ($agac['durum'] == 'Sağlıklı') ? 'selected' : ''; ?>>Sağlıklı</option>
                                        <option value="Kesildi" <?php echo ($agac['durum'] == 'Kesildi') ? 'selected' : ''; ?>>Kesildi</option>
                                        <option value="Hastalıklı" <?php echo ($agac['durum'] == 'Hastalıklı') ? 'selected' : ''; ?>>Hastalıklı</option>
                                        <option value="Dikildi" <?php echo ($agac['durum'] == 'Dikildi') ? 'selected' : ''; ?>>Dikildi</option>
                                    </select>
                                </div>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-success me-md-2">Güncelle</button>
                                    <a href="dashboard.php" class="btn btn-secondary">İptal</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>