<?php
require_once 'templates/header.php';

$db = Database::getInstance();

// Şirket silme işlemi
if (isset($_POST['sil']) && isset($_POST['id'])) {
    try {
        // Şirketin faturalarını kontrol et
        $fatura_sayisi = $db->query(
            "SELECT COUNT(*) FROM invoices WHERE company_id = :id",
            [':id' => $_POST['id']]
        )->fetchColumn();

        if ($fatura_sayisi > 0) {
            hata("Bu şirkete ait " . $fatura_sayisi . " adet fatura bulunduğu için silinemez!");
        } else {
            $db->query("DELETE FROM companies WHERE id = :id", [':id' => $_POST['id']]);
            basari("Şirket başarıyla silindi!");
            header('Location: sirketler.php');
            exit;
        }
    } catch (Exception $e) {
        hata($e->getMessage());
    }
}

// Şirketleri al
$sql = "SELECT 
    c.id,
    c.unvan,
    c.vergi_no,
    c.aktif,
    c.telefon,
    c.email,
    COUNT(DISTINCT i.id) as fatura_sayisi,
    COUNT(DISTINCT m.id) as musteri_sayisi,
    GROUP_CONCAT(
        DISTINCT CONCAT(u.ad_soyad, ' (', u.email, ')') 
        ORDER BY u.ad_soyad 
        SEPARATOR ', '
    ) as kullanicilar
    FROM companies c 
    LEFT JOIN invoices i ON i.company_id = c.id 
    LEFT JOIN customers m ON m.company_id = c.id
    LEFT JOIN user_companies uc ON uc.company_id = c.id
    LEFT JOIN users u ON u.id = uc.user_id
    GROUP BY c.id, c.unvan, c.vergi_no, c.aktif
    ORDER BY c.unvan";

$sirketler = $db->query($sql)->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Şirketler</h1>
        </div>
        <div class="col text-end">
            <?php if ($_SESSION['user']['rol'] == 'admin'): ?>
                <a href="sirket_ekle.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Şirket Ekle
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-responsive">
                    <thead>
                        <tr>
                            <th>Ünvan</th>
                            <th>Vergi No</th>
                            <th>Telefon</th>
                            <th>E-posta</th>
                            <th>Fatura Sayısı</th>
                            <th>Müşteri Sayısı</th>
                            <th>Kullanıcılar</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sirketler as $sirket): ?>
                            <tr>
                                <td><?php echo $sirket['unvan']; ?></td>
                                <td><?php echo $sirket['vergi_no']; ?></td>
                                <td><?php echo $sirket['telefon']; ?></td>
                                <td><?php echo $sirket['email']; ?></td>
                                <td><?php echo $sirket['fatura_sayisi']; ?></td>
                                <td><?php echo $sirket['musteri_sayisi']; ?></td>
                                <td><?php echo $sirket['kullanicilar']; ?></td>
                                <td>
                                    <?php if ($sirket['aktif']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="sirket_duzenle.php?id=<?php echo $sirket['id']; ?>"
                                            class="btn btn-sm btn-primary" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($_SESSION['user']['rol'] == 'admin'): ?>
                                            <?php if ($sirket['fatura_sayisi'] == 0): ?>
                                                <form method="post" class="d-inline"
                                                    onsubmit="return confirm('Bu şirketi silmek istediğinize emin misiniz?');">
                                                    <?php echo csrf_token_field(); ?>
                                                    <input type="hidden" name="id" value="<?php echo $sirket['id']; ?>">
                                                    <button type="submit" name="sil" class="btn btn-sm btn-danger" title="Sil">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>