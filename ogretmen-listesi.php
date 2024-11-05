<?php
session_start();
require_once 'baglan.php';

// Oturum kontrolü 
if (!isset($_SESSION['kullanici_adi'])) {
    header("Location: giris.php");
    exit();
}


// Öğretmenleri ve kulüplerini çek
$sql = "SELECT o.*, 
        GROUP_CONCAT(DISTINCT COALESCE(k.kulup_adi, 'Kulüp Yok') SEPARATOR ', ') as kulupler,
        GROUP_CONCAT(DISTINCT COALESCE(ok.haftalik_ders_saati, '-') SEPARATOR ', ') as ders_saatleri
        FROM Ogretmenler o 
        LEFT JOIN OgretmenKulupleri ok ON o.ogretmen_id = ok.ogretmen_id
        LEFT JOIN Kulupler k ON ok.kulup_id = k.kulup_id
        GROUP BY o.ogretmen_id, o.ad, o.soyad, o.aktif
        ORDER BY o.ad, o.soyad";

$ogretmenler = $conn->query($sql);



// Tablonun ilgili kısmını düzenleyelim
?>
<td>
    <?php 
    if ($ogretmen['kulupler'] && $ogretmen['kulupler'] != 'Kulüp Yok') {
        $kulupler = explode(',', $ogretmen['kulupler']);
        foreach ($kulupler as $kulup) {
            echo '<span class="badge badge-info badge-kulup">' . 
                 htmlspecialchars(trim($kulup)) . '</span> ';
        }
    } else {
        echo '<span class="badge badge-secondary">Kulüp Yok</span>';
    }
    ?>
</td>
<td>
    <?php 
    if ($ogretmen['ders_saatleri'] && $ogretmen['ders_saatleri'] != '-') {
        $saatler = explode(',', $ogretmen['ders_saatleri']);
        foreach ($saatler as $saat) {
            if ($saat != '-') {
                echo '<span class="badge badge-primary">' . trim($saat) . ' saat</span> ';
            }
        }
    } else {
        echo '-';
    }
    ?>
</td>
// Silme işlemi
if (isset($_POST['sil']) && isset($_POST['ogretmen_id'])) {
    $silinecek_id = intval($_POST['ogretmen_id']);
    
    // Önce öğretmenin kulüp ilişkilerini sil
    $conn->query("DELETE FROM OgretmenKulupleri WHERE ogretmen_id = $silinecek_id");
    
    // Sonra öğretmeni sil
    if ($conn->query("DELETE FROM Ogretmenler WHERE ogretmen_id = $silinecek_id")) {
        $mesaj = ["tip" => "success", "metin" => "Öğretmen başarıyla silindi"];
    } else {
        $mesaj = ["tip" => "danger", "metin" => "Öğretmen silinirken hata oluştu"];
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğretmen Listesi | Kulüp Yönetim Sistemi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            padding: 20px 0;
        }
        .sidebar-link {
            color: rgba(255,255,255,.8);
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
            text-decoration: none;
        }
        .sidebar-link:hover, .sidebar-link.active {
            color: #fff;
            background: rgba(255,255,255,.1);
            text-decoration: none;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,.1);
        }
        .badge-kulup {
            font-size: 0.85em;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .table th {
            border-top: none;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sol Menü -->
            <div class="col-md-2 sidebar">
                <div class="text-center mb-4">
                    <h4 class="text-white">Kulüp Yönetimi</h4>
                </div>
                <a href="panel.php" class="sidebar-link">
                    <i class="fas fa-home mr-2"></i> Ana Sayfa
                </a>
                <a href="kulup-listesi.php" class="sidebar-link">
                    <i class="fas fa-users mr-2"></i> Kulüpler
                </a>
                <a href="ogrenci-listesi.php" class="sidebar-link">
                    <i class="fas fa-user-graduate mr-2"></i> Öğrenciler
                </a>
                <a href="ogretmen-listesi.php" class="sidebar-link active">
                    <i class="fas fa-chalkboard-teacher mr-2"></i> Öğretmenler
                </a>
                <a href="odeme-listesi.php" class="sidebar-link">
                    <i class="fas fa-money-bill-wave mr-2"></i> Ödemeler
                </a>
                <hr class="bg-secondary">
                <a href="cikis.php" class="sidebar-link">
                    <i class="fas fa-sign-out-alt mr-2"></i> Çıkış Yap
                </a>
            </div>

            <!-- Ana İçerik -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">
                        <i class="fas fa-chalkboard-teacher mr-2"></i>
                        Öğretmen Listesi
                    </h1>
                    <a href="ogretmen-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>Yeni Öğretmen Ekle
                    </a>
                </div>

                <?php if (isset($mesaj)): ?>
                    <div class="alert alert-<?php echo $mesaj['tip']; ?> alert-dismissible fade show">
                        <?php echo $mesaj['metin']; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="ogretmenTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Ad Soyad</th>
                                        <th>Kulüpler</th>
                                        <th>Ders Saatleri</th>
                                        <th>Durum</th>
                                        <th style="width: 150px;">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($ogretmen = $ogretmenler->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ogretmen['ad'] . ' ' . $ogretmen['soyad']); ?></td>
                                            <td>
                                                <?php 
                                                if ($ogretmen['kulupler']) {
                                                    $kulupler = explode(',', $ogretmen['kulupler']);
                                                    foreach ($kulupler as $kulup) {
                                                        echo '<span class="badge badge-info badge-kulup">' . 
                                                             htmlspecialchars($kulup) . '</span>';
                                                    }
                                                } else {
                                                    echo '<span class="badge badge-secondary">Kulüp Yok</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($ogretmen['ders_saatleri']) {
                                                    $saatler = explode(',', $ogretmen['ders_saatleri']);
                                                    echo implode(' saat, ', $saatler) . ' saat';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($ogretmen['aktif']): ?>
                                                    <span class="badge badge-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Pasif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="ogretmen-duzenle.php?id=<?php echo $ogretmen['ogretmen_id']; ?>" 
                                                       class="btn btn-primary" title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger" 
                                                            onclick="ogretmenSil(<?php echo $ogretmen['ogretmen_id']; ?>)"
                                                            title="Sil">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Silme Onay Modalı -->
    <div class="modal fade" id="silModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Öğretmen Sil</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Öğretmeni silmek istediğinize emin misiniz?
                </div>
                <div class="modal-footer">
                    <form method="post">
                        <input type="hidden" name="ogretmen_id" id="silinecek_id">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" name="sil" class="btn btn-danger">Sil</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#ogretmenTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
            }
        });
    });

    function ogretmenSil(id) {
        $('#silinecek_id').val(id);
        $('#silModal').modal('show');
    }
    </script>
</body>
</html>
