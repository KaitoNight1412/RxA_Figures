<?php
session_start();
require "koneksi.php";

if (!isset($_SESSION['checkout'])) {
    header("Location: keranjang.php");
    exit;
}

$data = $_SESSION['checkout'];
$id_user = $_SESSION['id_user'];
$alamat = $data['alamat_id'];
$total_harga = $data['total_akhir'];
$ongkir = $data['ongkir'];
$total_barang = $data['total_barang'];

$provider = $_POST['provider'] ?? '';
$status = 'Belum Dibayar';
$tanggal_pemesanan = date('Y-m-d H:i:s');

$gambar_name = $_FILES['bukti_pembayaran']['name'] ?? '';
$gambar_tmp = $_FILES['bukti_pembayaran']['tmp_name'] ?? '';
$folder_tujuan = "bukti_bayar/";

$file_extension = pathinfo($gambar_name, PATHINFO_EXTENSION);
$new_filename = 'bukti_' . time() . '_' . $id_user . '.' . $file_extension;

if (!empty($gambar_tmp)) {
    move_uploaded_file($gambar_tmp, $folder_tujuan . $new_filename);
} else {
    $new_filename = '';
}

$checkout_items = $data['checkout_items'];
$ids_str = implode(',', $checkout_items);
$query_produk = "SELECT k.id_produk, k.id_keranjang, k.jumlah_item, p.harga, k.subtotal
                 FROM keranjang k 
                 JOIN produk p ON k.id_produk = p.id_produk 
                 WHERE k.id_user = '$id_user' 
                 AND k.id_produk IN ($ids_str)";
$result_produk = mysqli_query($koneksi, $query_produk);

$success = true;

if ($result_produk && mysqli_num_rows($result_produk) > 0) {
    while ($produk = mysqli_fetch_assoc($result_produk)) {
        $id_produk = $produk['id_produk'];
        $id_keranjang = $produk['id_keranjang'];
        $jumlah = $produk['jumlah_item'];
        $harga_satuan = $produk['harga'];
        $subtotal = $produk['subtotal'];
        
        $query_transaksi = "INSERT INTO transaksi (
            id_produk, 
            id_user, 
            id_alamat, 
            id_keranjang, 
            tanggal_pemesanan, 
            jumlah_produk,
            total_harga, 
            ongkir, 
            status
        ) VALUES (
            '$id_produk',
            '$id_user',
            '$alamat',
            '$id_keranjang',
            '$tanggal_pemesanan',
            '$jumlah',
            '$subtotal',
            '$ongkir',
            '$status'
        )";
        
        $insert_transaksi = mysqli_query($koneksi, $query_transaksi);
        
        if ($insert_transaksi) {
            $id_transaksi = mysqli_insert_id($koneksi);
            $query_pembayaran = "INSERT INTO pembayaran (
                id_transaksi, 
                provider, 
                bukti_pembayaran
            ) VALUES (
                '$id_transaksi', 
                '$provider', 
                '$new_filename'
            )";
            mysqli_query($koneksi, $query_pembayaran);
            mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang'");
        } else {
            $success = false;
            break;
        }
    }
    unset($_SESSION['checkout']);
} else {
    $success = false;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi Pembayaran</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
        }
    </style>
</head>
<body>
<script>
    Swal.fire({
        title: 'Berhasil!',
        text: 'Pembelian produk berhasil!',
        icon: 'success',
        timer: 1500,
        timerProgressBar: false,
        showConfirmButton: false
    });
    setTimeout(function() {
        window.location.href = 'log_transaksi.php';
    }, 2600);

</script>
</body>
</html>
