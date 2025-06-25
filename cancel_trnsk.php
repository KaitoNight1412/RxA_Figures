<?php
session_start();
require "koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_ids'])) {
    $transaction_ids = $_POST['transaction_ids'];
    $placeholders = implode(',', array_fill(0, count($transaction_ids), '?'));

    // Gunakan prepared statement
    $stmt_pembayaran = mysqli_prepare($koneksi, "DELETE FROM pembayaran WHERE id_transaksi IN ($placeholders)");
    mysqli_stmt_bind_param($stmt_pembayaran, str_repeat('i', count($transaction_ids)), ...$transaction_ids);
    mysqli_stmt_execute($stmt_pembayaran);
    mysqli_stmt_close($stmt_pembayaran);

    $stmt_transaksi = mysqli_prepare($koneksi, "DELETE FROM transaksi WHERE id_transaksi IN ($placeholders)");
    mysqli_stmt_bind_param($stmt_transaksi, str_repeat('i', count($transaction_ids)), ...$transaction_ids);
    mysqli_stmt_execute($stmt_transaksi);
    mysqli_stmt_close($stmt_transaksi);

    header("Location: daftar_transaksi.php?pesan=cancel_sukses");
    exit;
} else {
    header("Location: daftar_transaksi.php?pesan=cancel_error");
    exit;
}
