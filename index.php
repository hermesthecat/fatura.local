<?php require_once 'templates/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title">Fatura Yönetim Sistemi</h1>
                <p class="card-text">Hoş geldiniz! Bu sistem ile:</p>
                <ul>
                    <li>Yeni fatura oluşturabilir,</li>
                    <li>Mevcut faturaları görüntüleyebilir,</li>
                    <li>Faturaları düzenleyebilir,</li>
                    <li>ve PDF olarak çıktı alabilirsiniz.</li>
                </ul>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Yeni Fatura</h5>
                                <p class="card-text">Yeni bir fatura oluşturmak için tıklayın.</p>
                                <a href="fatura_olustur.php" class="btn btn-light">Fatura Oluştur</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Fatura Listesi</h5>
                                <p class="card-text">Mevcut faturaları görüntülemek için tıklayın.</p>
                                <a href="fatura_listele.php" class="btn btn-light">Faturaları Listele</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Müşteri İşlemleri</h5>
                                <p class="card-text">Müşteri bilgilerini yönetin.</p>
                                <a href="musteri_listele.php" class="btn btn-light">Müşterileri Listele</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 