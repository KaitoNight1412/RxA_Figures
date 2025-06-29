<?php
session_start();
include "koneksi.php";



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About</title>
    <link rel="stylesheet" href="css/about.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <header>
        <a href="homepage.php"><img src="img/logo/logo.png" alt="R&A Logo" srcset="" class="logo" ></a>
        <nav>
            <div class="profile-icon">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="dashboard.php">Add Product</a>
                    <a href="daftar_transaksi.php">Orders</a>
                    <a href="DaftarProduk.php">Products</a>
                    <a href="about.php">About</a>
                    <a href="admin.php"><img src="img/user/user.png" alt="Admin Icon" class="profile"></a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
                    <a href="keranjang.php">Cart</a>
                    <a href="log_transaksi.php">History</a>
                    <a href="DaftarProduk.php">Products</a>
                    <a href="about.php">About</a>
                    <a href="user.php"><img src="img/user/user.png" alt="User Icon" class="profile"></a>
                <?php else: ?>
                    <a href="DaftarProduk.php">Products</a>
                    <a href="login1.php">Login</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <h1>Haloo....</h1>
        <div class="deskripsi">
            <p>Selamat datang di R&A Figure Store.</p>
            <p>Kami harap anda menemukan apa yang anda cari.</p>
            <br>
            <p>R&A Figure Store adalah sebuah toko merchandise</p>
            <p>yang memfokuskan penjualan figure, seperti nendoroid.</p>
            <br>
            <p>Dah itu aja.... Selamat berbelanja kawan...</p>
        </div>
        <h3>Awas khilaf !!</h3>
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
</body>
</html>