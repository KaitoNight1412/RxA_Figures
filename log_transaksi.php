<?php
include "koneksi.php";
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login1.php?AndaBelumLogin");
    exit;
}

$id_user = $_SESSION['id_user'];

// Query untuk mengambil transaksi user dengan detail produk dan bukti pembayaran
$sql = "SELECT 
    t.id_transaksi,
    t.tanggal_pemesanan,
    t.total_harga,
    COALESCE(t.status, 'Belum Dibayar') as status,
    t.jumlah_produk,
    t.ongkir,
    pb.bukti_pembayaran,
    p.nama_produk,
    p.harga,
    p.gambar
FROM transaksi t
LEFT JOIN produk p ON t.id_produk = p.id_produk
LEFT JOIN pembayaran pb ON t.id_transaksi = pb.id_transaksi
WHERE t.id_user = ?
ORDER BY t.tanggal_pemesanan DESC, t.id_transaksi DESC";

$sql = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($sql, "i", $id_user);
mysqli_stmt_execute($sql);
$result = mysqli_stmt_get_result($sql);

// Kelompokkan transaksi berdasarkan waktu pemesanan yang sama (dalam menit yang sama)
$transactions = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Group by exact timestamp (rounded to nearest minute for same-checkout transactions)
    $transaction_time = date('Y-m-d H:i', strtotime($row['tanggal_pemesanan']));
    $group_key = $transaction_time; // Just use the time, not user_id
    
    if (!isset($transactions[$group_key])) {
        $transactions[$group_key] = [
            'tanggal' => $row['tanggal_pemesanan'],
            'status' => $row['status'],
            'ongkir' => $row['ongkir'],
            'bukti_pembayaran' => $row['bukti_pembayaran'],
            'produk' => [],
            'total_keseluruhan' => 0,
            'transaction_ids' => []
        ];
    }
    
    $transactions[$group_key]['produk'][] = $row;
    $transactions[$group_key]['total_keseluruhan'] += $row['total_harga'];
    $transactions[$group_key]['transaction_ids'][] = $row['id_transaksi'];
    
    // Use the latest status if there are multiple transactions
    if ($row['status'] !== 'Belum Dibayar') {
        $transactions[$group_key]['status'] = $row['status'];
    }
}

// Sort transactions by date (newest first)
uasort($transactions, function($a, $b) {
    return strtotime($b['tanggal']) - strtotime($a['tanggal']);
});
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembelian</title>
    <link rel="stylesheet" href="css/log_transaksi.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <header>
        <a href="homepage.php"><img src="img/logo/logo.png" alt="R&A Logo" srcset="" class="logo" ></a>
        <nav>
            <div class="profile-icon">    
                <a href="DaftarProduk.php">Products</a>
                <a href="keranjang.php">Cart</a>
                <a href="about.php">About</a>
                <a href="cek_profil.php"><img src="img/user/user.png" alt="Profile Icon" class="profile"></a>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="header">
                <h1>Riwayat Pembelian</h1>
                <p>Lihat semua produk yang pernah Anda beli</p>
            </div>

            <div class="transaction-list">
                <?php if (empty($transactions)): ?>
                    <div class="no-transactions">
                        <div style="font-size: 3em; margin-bottom: 20px;">🛍️</div>
                        <h3>Belum Ada Transaksi</h3>
                        <p>Anda belum melakukan pembelian apapun</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($transactions as $key => $transaction): ?>
                        <div class="transaction-item" onclick="toggleDetails('<?php echo $key; ?>')">
                            <div class="transaction-summary">
                                <div class="transaction-info">
                                    <div class="transaction-date">
                                        <?php echo date('d F Y', strtotime($transaction['tanggal'])); ?>
                                    </div>
                                    <div class="product-preview">
                                        <?php 
                                        $firstProduct = $transaction['produk'][0]['nama_produk'];
                                        echo $firstProduct;
                                        ?>
                                    </div>
                                    <?php if (count($transaction['produk']) > 1): ?>
                                        <div class="more-products">
                                            +<?php echo count($transaction['produk']) - 1; ?> produk lainnya
                                        </div>
                                    <?php endif; ?>

                                    <?php
                                        // Mapping status ke kelas warna (untuk badge style)
                                        $status_class = [
                                            'Belum Dibayar' => 'belum-dibayar',
                                            'Dibayar' => 'dibayar',
                                            'Dikirim' => 'dikirim',
                                            'Selesai' => 'selesai',
                                            'Dibatalkan' => 'dibatalkan'
                                        ];

                                        $current_class = isset($status_class[$transaction['status']]) ? $status_class[$transaction['status']] : 'belum-dibayar';
                                    ?>

                                    <span class="status <?= $current_class ?>">
                                        <?= $transaction['status']; ?>
                                    </span>
                                </div>
                                <div class="transaction-total">
                                    <div class="total-price">
                                        Rp <?php echo number_format($transaction['total_keseluruhan'] + $transaction['ongkir'], 0, ',', '.'); ?>
                                    </div>
                                    <div class="arrow-icon">▼</div>
                                </div>
                            </div>
                            
                            <div class="transaction-details" id="details-<?php echo $key; ?>">
                                <div class="details-content">
                                    <!-- <div class="transaction-meta">
                                        <p><strong>ID Transaksi:</strong> <?php echo implode(', ', $transaction['transaction_ids']); ?></p>
                                        <p><strong>Tanggal & Waktu:</strong> <?php echo date('d F Y, H:i:s', strtotime($transaction['tanggal'])); ?></p>
                                    </div> -->
                                    
                                    <h4 style="margin: 20px 0 15px 0; color: #333;">Detail Produk:</h4>
                                    <?php foreach ($transaction['produk'] as $produk): ?>
                                        <div class="product-item">
                                            <div class="product-image">
                                                <?php if (!empty($produk['gambar'])): ?>
                                                    <img src="gambar_produk/<?php echo $produk['gambar']; ?>" alt="<?php echo $produk['nama_produk']; ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                                <?php else: ?>
                                                    📦
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-details">
                                                <div class="product-name"><?php echo $produk['nama_produk']; ?></div>
                                                <div class="product-price">
                                                    Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?>
                                                </div>
                                            </div>
                                            <div class="product-quantity">
                                                Qty: <?php echo $produk['jumlah_produk']; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Ringkasan Total -->
                                    <div style="border-top: 2px solid #e9ecef; margin-top: 15px; padding-top: 15px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                            <span>Subtotal Produk:</span>
                                            <span>Rp <?php echo number_format($transaction['total_keseluruhan'], 0, ',', '.'); ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                            <span>Ongkos Kirim:</span>
                                            <span>Rp <?php echo number_format($transaction['ongkir'], 0, ',', '.'); ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1em; color: #28a745;">
                                            <span>Total Keseluruhan:</span>
                                            <span>Rp <?php echo number_format($transaction['total_keseluruhan'] + $transaction['ongkir'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>

                                    <!-- Bukti Pembayaran Section -->
                                    <?php if (!empty($transaction['bukti_pembayaran'])): ?>
                                        <div class="payment-proof-section" style="border-top: 2px solid #e9ecef; margin-top: 20px; padding-top: 20px;">
                                            <h4 style="margin-bottom: 15px; color: #333;">
                                                <i class="fas fa-receipt" style="margin-right: 8px; color: #28a745;"></i>
                                                Bukti Pembayaran:
                                            </h4>
                                            <div class="payment-proof-container" style="text-align: center;">
                                                <img src="bukti_bayar/<?php echo $transaction['bukti_pembayaran']; ?>" 
                                                     alt="Bukti Pembayaran" 
                                                     class="payment-proof-image"
                                                     onclick="openPaymentProof('bukti_bayar/<?php echo $transaction['bukti_pembayaran']; ?>')"
                                                     style="max-width: 300px; max-height: 400px; width: auto; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.3s ease;">
                                                <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                                                    <i class="fas fa-search-plus" style="margin-right: 5px;"></i>
                                                    Klik untuk memperbesar
                                                </p>
                                            </div>
                                        </div>
                                    <?php elseif ($transaction['status'] == 'Belum Dibayar'): ?>
                                        <div class="no-payment-proof" style="border-top: 2px solid #e9ecef; margin-top: 20px; padding-top: 20px; text-align: center;">
                                            <div style="color: #ffc107; font-size: 2em; margin-bottom: 10px;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <p style="color: #856404; font-weight: bold;">Belum ada bukti pembayaran</p>
                                            <p style="color: #666; font-size: 0.9em;">Silakan upload bukti pembayaran Anda</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal for Payment Proof -->
    <div id="paymentProofModal" class="modal" onclick="closePaymentProof()">
        <div class="modal-content">
            <span class="close" onclick="closePaymentProof()">&times;</span>
            <img id="modalPaymentProof" src="" alt="Bukti Pembayaran" style="width: 100%; height: auto;">
        </div>
    </div>

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

    <script>
        function toggleDetails(transactionId) {
            const details = document.getElementById('details-' + transactionId);
            const item = details.closest('.transaction-item');
            
            // Close all other details
            document.querySelectorAll('.transaction-details').forEach(detail => {
                if (detail !== details) {
                    detail.classList.remove('active');
                    detail.closest('.transaction-item').classList.remove('active');
                }
            });
            
            // Toggle current details
            details.classList.toggle('active');
            item.classList.toggle('active');
        }

        function confirmReceived(transactionIds) {
            if (confirm('Apakah Anda yakin telah menerima pesanan ini?')) {
                window.location.href = 'confirm_received.php?ids=' + transactionIds;
            }
        }

        function openPaymentProof(imageSrc) {
            document.getElementById('paymentProofModal').style.display = 'block';
            document.getElementById('modalPaymentProof').src = imageSrc;
        }

        function closePaymentProof() {
            document.getElementById('paymentProofModal').style.display = 'none';
        }

        // Close details when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.transaction-item')) {
                document.querySelectorAll('.transaction-details').forEach(detail => {
                    detail.classList.remove('active');
                    detail.closest('.transaction-item').classList.remove('active');
                });
            }
        });
    </script>

    <style>
        .transaction-meta {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }

        .transaction-meta p {
            margin: 5px 0;
            font-size: 0.9em;
        }

        .transaction-actions {
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
        }

        .btn-pay, .btn-confirm {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-pay:hover, .btn-confirm:hover {
            background: #218838;
        }

        .btn-pay {
            background: #007bff;
        }

        .btn-pay:hover {
            background: #0056b3;
        }

        /* Status badge colors */
        .status.menunggu-konfirmasi {
            background: #ffc107;
            color: #212529;
        }

        .status.diproses {
            background: #17a2b8;
            color: white;
        }

        .status.dibatalkan {
            background: #dc3545;
            color: white;
        }

        /* Modal styles for payment proof */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            position: relative;
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 800px;
            background-color: white;
            border-radius: 10px;
            text-align: center;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
            z-index: 10000;
        }

        .close:hover {
            color: #000;
        }

        .payment-proof-image:hover {
            transform: scale(1.05);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .no-payment-proof {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
        }
    </style>
</body>
</html>