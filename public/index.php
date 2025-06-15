<?php
session_start();

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$kayit_hata = '';
$kayit_basarili = '';
$giris_hata = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['register_submit'])) {
        $kullanici_adi = trim($_POST["kullanici_adi_kayit"]);
        $eposta = trim($_POST["eposta_kayit"]);
        $sifre = trim($_POST["sifre_kayit"]);
        $sifre_tekrar = trim($_POST["sifre_tekrar_kayit"]);

        if (empty($kullanici_adi) || empty($eposta) || empty($sifre) || empty($sifre_tekrar)) {
            $kayit_hata = "Lütfen tüm alanları doldurun.";
        } elseif ($sifre !== $sifre_tekrar) {
            $kayit_hata = "Şifreler uyuşmuyor.";
        } elseif (strlen($sifre) < 6) {
            $kayit_hata = "Şifreniz en az 6 karakter olmalıdır.";
        } else {
            $sql = "SELECT id FROM USER WHERE kullanici_adi = ? OR eposta = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ss", $param_kullanici_adi, $param_eposta);
                $param_kullanici_adi = $kullanici_adi;
                $param_eposta = $eposta;
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $kayit_hata = "Bu kullanıcı adı veya e-posta zaten kullanımda.";
                } else {
                    $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);

                    $sql_insert = "INSERT INTO USER (kullanici_adi, sifre, eposta) VALUES (?, ?, ?)";
                    if ($stmt_insert = $conn->prepare($sql_insert)) {
                        $stmt_insert->bind_param("sss", $param_kullanici_adi_ins, $param_sifre_ins, $param_eposta_ins);
                        $param_kullanici_adi_ins = $kullanici_adi;
                        $param_sifre_ins = $hashed_sifre;
                        $param_eposta_ins = $eposta;

                        if ($stmt_insert->execute()) {
                            $kayit_basarili = "Kaydınız başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.";
                        } else {
                            $kayit_hata = "Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin. Hata: " . $stmt_insert->error;
                        }
                        $stmt_insert->close();
                    } else {
                        $kayit_hata = "Veritabanı sorgusu hazırlanamadı: " . $conn->error;
                    }
                }
                $stmt->close();
            } else {
                $kayit_hata = "Veritabanı sorgusu hazırlanamadı: " . $conn->error;
            }
        }
    } elseif (isset($_POST['login_submit'])) {
        $kullanici_adi_giris = trim($_POST["kullanici_adi_giris"]);
        $sifre_giris = trim($_POST["sifre_giris"]);

        if (empty($kullanici_adi_giris) || empty($sifre_giris)) {
            $giris_hata = "Lütfen kullanıcı adı ve şifreyi girin.";
        } else {
            $sql = "SELECT id, kullanici_adi, sifre FROM USER WHERE kullanici_adi = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $param_kullanici_adi_giris);
                $param_kullanici_adi_giris = $kullanici_adi_giris;
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $kullanici_adi, $hashed_sifre_db);
                    $stmt->fetch();

                    if (password_verify($sifre_giris, $hashed_sifre_db)) {
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["kullanici_adi"] = $kullanici_adi;

                        header("location: dashboard.php");
                        exit;
                    } else {
                        $giris_hata = "Hatalı şifre.";
                    }
                } else {
                    $giris_hata = "Bu kullanıcı adına sahip bir hesap bulunamadı.";
                }
                $stmt->close();
            } else {
                $giris_hata = "Veritabanı sorgusu hazırlanamadı: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orman Takip Sistemi - Giriş/Kayıt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Orman Takip Sistemi</h2>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="true">Giriş Yap</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" aria-controls="register" aria-selected="false">Kayıt Ol</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="login" role="tabpanel" aria-labelledby="login-tab">
                                <?php
                                if (!empty($giris_hata)) {
                                    echo '<div class="alert alert-danger">' . $giris_hata . '</div>';
                                }
                                ?>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="mb-3">
                                        <label for="kullanici_adi_giris" class="form-label">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" id="kullanici_adi_giris" name="kullanici_adi_giris" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="sifre_giris" class="form-label">Şifre</label>
                                        <input type="password" class="form-control" id="sifre_giris" name="sifre_giris" required>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="login_submit" class="btn btn-success">Giriş Yap</button>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
                                <?php
                                if (!empty($kayit_basarili)) {
                                    echo '<div class="alert alert-success">' . $kayit_basarili . '</div>';
                                }
                                if (!empty($kayit_hata)) {
                                    echo '<div class="alert alert-danger">' . $kayit_hata . '</div>';
                                }
                                ?>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="mb-3">
                                        <label for="kullanici_adi_kayit" class="form-label">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" id="kullanici_adi_kayit" name="kullanici_adi_kayit" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="eposta_kayit" class="form-label">E-posta</label>
                                        <input type="email" class="form-control" id="eposta_kayit" name="eposta_kayit" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="sifre_kayit" class="form-label">Şifre</label>
                                        <input type="password" class="form-control" id="sifre_kayit" name="sifre_kayit" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="sifre_tekrar_kayit" class="form-label">Şifre Tekrar</label>
                                        <input type="password" class="form-control" id="sifre_tekrar_kayit" name="sifre_tekrar_kayit" required>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="register_submit" class="btn btn-primary">Kayıt Ol</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>