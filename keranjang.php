<?php 
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login1.php?Logindulu");
    exit;
}

$_SESSION['from_page'] = 'keranjang.php';
$id_user = $_SESSION['id_user'];    

$sql = "SELECT  keranjang.id_keranjang,
                keranjang.subtotal,
                keranjang.jumlah_item,
                produk.id_produk as id_produk,
                produk.nama_produk as nama_produk,
                produk.harga as harga,
                produk.gambar as gambar,
                produk.stok as stok
                from keranjang
                join produk on keranjang.id_produk=produk.id_produk
                WHERE keranjang.id_user = '$id_user' ";

$query = mysqli_query($koneksi,$sql);
$jumlah_keranjang = mysqli_num_rows($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link rel="stylesheet" href="css/keranjang.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <header>
        <img src="img/logo/logo.png" alt="R&A Logo" srcset="" class="logo" >
        <nav>
            <div class="profile-icon">    
                <a href="DaftarProduk.php">Products</a>
                <a href="log_transaksi.php">History</a>
                <a href="about.php">About</a>
                <a href="cek_profil.php"><img src="img/user/user.png" alt="Profile Icon" class="profile"></a>
            </div>
        </nav>
    </header>

    <main>
        <form action="transaksi.php" method="POST">
        <?php if ($jumlah_keranjang > 0 ) { ?>
            <?php while ($keranjang = mysqli_fetch_assoc($query)) { ?>
                <div class="cart-item">
                    <!-- ✅ Checkbox untuk pilih item -->
                    <input type="checkbox" class="select-item" name="checkout_items[]" value="<?= $keranjang['id_produk'] ?>">

                    <div class="product-image">
                        <img src="gambar_produk/<?= $keranjang['gambar'] ?>" alt="<?= $keranjang['nama_produk'] ?>" width="100">
                    </div>
                    <div class="product-details">
                        <a href="produk.php?id_produk=<?= $keranjang['id_produk'] ?>">
                            <h4><?= $keranjang['nama_produk'] ?></h4>
                        </a>
                        <p>Rp<?= number_format($keranjang['harga'], 0, ',', '.') ?></p>
                    </div>
                    <div class="product-actions">
                        <div class="quantity">
                            <button class="btn-minus" type="button">-</button>
                            <input type="number"
                                class="qty"
                                min="0"
                                max="<?=$keranjang['stok']?>"
                                value="<?= $keranjang['jumlah_item'] ?>"
                                data-harga="<?= $keranjang['harga'] ?>"
                                data-id="<?= $keranjang['id_keranjang'] ?>">
                            <button class="btn-plus" type="button">+</button>
                        </div>
                        <div class="subtotal">
                            <strong>Rp<?= number_format($keranjang['subtotal'], 0, ',', '.') ?></strong>
                        </div>
                        <button class="btn-delete" type="button" data-id="<?= $keranjang['id_keranjang'] ?>">Hapus</button>
                    </div>
                </div>
            <?php } ?>
            
        <div id="total-bayar" style="margin-top: 20px;">
            Total Bayar: <strong id="total-text">Rp0</strong>
        </div>

            <button type="submit" style="margin-top: 20px;">Checkout Produk Terpilih</button>
        <?php } else { ?>
            <p>Kamu belum belanja apapun</p>
        <?php } ?>
        </form>
            <!-- <a href="DaftarProduk.php">nambah yuk</a> -->
    </main>

    <footer>
        <div class="footer-center">
            <p>Official Social Media Account</p>
            <div class="social-icons">
                <a href="https://x.com/" class="x-icon"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="https://www.youtube.com/" class="yt-icon"><i class="fa-brands fa-youtube"></i></a>
                <a href="https://www.instagram.com/" class="ig-icon"><i class="fa-brands fa-instagram"></i></a>
            </div>
            <div class="copyright">
                &copy; <?= date('Y') ?> R&A Figure Store. All right reserved.
            </div>
        </div>
    </footer>

    <script src="script/keranjang.js?<?=time() ?>"></script>
    <script>
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault(); // cegah aksi default tombol
                const idKeranjang = this.dataset.id;
                const cartItem = this.closest('.cart-item');

                Swal.fire({
                    title: 'Yakin mau hapus?',
                    text: 'Produk akan dihapus dari keranjang kamu.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Kirim AJAX ke hapus_keranjang.php
                        fetch('hapus_keranjang.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `id_keranjang=${encodeURIComponent(idKeranjang)}`
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // Hapus elemen dari DOM
                                cartItem.remove();

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Dihapus!',
                                    text: 'Produk berhasil dihapus dari keranjang.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                // Optional: refresh total bayar
                                updateTotal();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal!',
                                    text: 'Terjadi kesalahan saat menghapus item.'
                                });
                            }
                        });
                    }
                });
            });
        });

        // Fungsi untuk update total (opsional)
        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.cart-item').forEach(item => {
                const checkbox = item.querySelector('.select-item');
                const subtotalText = item.querySelector('.subtotal strong').textContent.replace(/[^\d]/g, '');
                const subtotal = parseInt(subtotalText) || 0;
                if (checkbox && checkbox.checked) {
                    total += subtotal;
                }
            });
            document.getElementById('total-text').textContent = 'Rp' + total.toLocaleString('id-ID');
        }
    </script>

</body>
</html>