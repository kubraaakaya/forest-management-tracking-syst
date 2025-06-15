<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$agac_ekle_hata = '';
$agac_ekle_basarili = '';
$agaclar = [];

if (isset($_SESSION['basari_mesaji'])) {
    $agac_ekle_basarili = $_SESSION['basari_mesaji'];
    unset($_SESSION['basari_mesaji']);
}
if (isset($_SESSION['hata_mesaji'])) {
    $agac_ekle_hata = $_SESSION['hata_mesaji'];
    unset($_SESSION['hata_mesaji']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agac_ekle_submit'])) {
    $tur = trim($_POST['tur']);
    $bolge = trim($_POST['bolge']);
    $yas = trim($_POST['yas']);
    $boy = trim($_POST['boy']);
    $cap = trim($_POST['cap']);
    $durum = trim($_POST['durum']);
    $ekleyen_kullanici_id = $_SESSION["id"];

    if (empty($tur) || empty($bolge) || empty($yas) || empty($boy) || empty($cap) || empty($durum)) {
        $agac_ekle_hata = "Lütfen tüm alanları doldurun.";
    } elseif (!is_numeric($yas) || $yas <= 0) {
        $agac_ekle_hata = "Yaş geçerli bir sayı olmalıdır.";
    } elseif (!is_numeric($boy) || $boy <= 0) {
        $agac_ekle_hata = "Boy geçerli bir sayı olmalıdır.";
    } elseif (!is_numeric($cap) || $cap <= 0) {
        $agac_ekle_hata = "Çap geçerli bir sayı olmalıdır.";
    } else {
        $sql_insert_agac = "INSERT INTO AGACLAR (tur, bolge, yas, boy, cap, durum, ekleyen_kullanici_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt_agac = $conn->prepare($sql_insert_agac)) {
            $stmt_agac->bind_param("ssiddsi", $tur, $bolge, $yas, $boy, $cap, $durum, $ekleyen_kullanici_id);
            if ($stmt_agac->execute()) {
                $_SESSION['basari_mesaji'] = "Ağaç bilgisi başarıyla eklendi.";
                header("Location: dashboard.php");
                exit;
            } else {
                $agac_ekle_hata = "Ağaç bilgisi eklenirken bir hata oluştu: " . $stmt_agac->error;
            }
            $stmt_agac->close();
        } else {
            $agac_ekle_hata = "Sorgu hazırlanamadı: " . $conn->error;
        }
    }
}

$sql_select_agaclar = "SELECT A.id, A.tur, A.bolge, A.yas, A.boy, A.cap, A.durum, A.kayit_tarihi, U.kullanici_adi 
                       FROM AGACLAR A LEFT JOIN USER U ON A.ekleyen_kullanici_id = U.id 
                       ORDER BY A.kayit_tarihi DESC";
if ($result = $conn->query($sql_select_agaclar)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $agaclar[] = $row;
        }
    }
} else {
    $agac_ekle_hata = "Ağaç bilgileri çekilirken bir hata oluştu: " . $conn->error;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Orman Takip Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header text-center d-flex justify-content-between align-items-center">
                        <h2>Hoş Geldin, <?php echo htmlspecialchars($_SESSION["kullanici_adi"]); ?>!</h2>
                        <a href="logout.php" class="btn btn-danger btn-sm">Çıkış Yap</a>
                    </div>
                    <div class="card-body">
                        <h3>Yeni Ağaç Bilgisi Ekle</h3>
                        <?php if (!empty($agac_ekle_basarili)): ?>
                            <div class="alert alert-success"><?php echo $agac_ekle_basarili; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($agac_ekle_hata)): ?>
                            <div class="alert alert-danger"><?php echo $agac_ekle_hata; ?></div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mb-5">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tur" class="form-label">Ağaç Türü</label>
                                    <input type="text" class="form-control" id="tur" name="tur" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="bolge" class="form-label">Bölge/Parsel</label>
                                    <input type="text" class="form-control" id="bolge" name="bolge" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="yas" class="form-label">Yaş (Yıl)</label>
                                    <input type="number" class="form-control" id="yas" name="yas" required min="1">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="boy" class="form-label">Boy (Metre)</label>
                                    <input type="number" step="0.01" class="form-control" id="boy" name="boy" required min="0.01">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="cap" class="form-label">Çap (CM)</label>
                                    <input type="number" step="0.01" class="form-control" id="cap" name="cap" required min="0.01">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="durum" class="form-label">Durum</label>
                                <select class="form-select" id="durum" name="durum" required>
                                    <option value="">Seçiniz...</option>
                                    <option value="Sağlıklı">Sağlıklı</option>
                                    <option value="Kesildi">Kesildi</option>
                                    <option value="Hastalıklı">Hastalıklı</option>
                                    <option value="Dikildi">Dikildi</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="agac_ekle_submit" class="btn btn-primary">Ağaç Bilgisi Ekle</button>
                            </div>
                        </form>

                        <h3 class="mt-5">Mevcut Ağaç Bilgileri</h3>
                        <?php if (empty($agaclar)): ?>
                            <div class="alert alert-info">Henüz hiç ağaç bilgisi eklenmedi.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tür</th>
                                            <th>Bölge</th>
                                            <th>Yaş</th>
                                            <th>Boy (m)</th>
                                            <th>Çap (cm)</th>
                                            <th>Durum</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>Ekleyen</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($agaclar as $agac): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($agac['id']); ?></td>
                                            <td><?php echo htmlspecialchars($agac['tur']); ?></td>
                                            <td><?php echo htmlspecialchars($agac['bolge']); ?></td>
                                            <td><?php echo htmlspecialchars($agac['yas']); ?></td>
                                            <td><?php echo htmlspecialchars($agac['boy']); ?></td>
                                            <td><?php echo htmlspecialchars($agac['cap']); ?></td>
                                            <td><?php echo htmlspecialchars($agac['durum']); ?></td>
                                            <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($agac['kayit_tarihi']))); ?></td>
                                            <td><?php echo htmlspecialchars($agac['kullanici_adi'] ?? 'Bilinmiyor'); ?></td>
                                            <td>
                                                <a href="edit_agac.php?id=<?php echo $agac['id']; ?>" class="btn btn-warning btn-sm me-1">Düzenle</a>
                                                <a href="delete_agac.php?id=<?php echo $agac['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">Sil</a>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>