document.querySelectorAll('.btn-minus').forEach((btn, index) => {
    btn.addEventListener('click', () => {
        const input = document.querySelectorAll('.qty')[index];
        let qty = parseInt(input.value);
        if (qty > 1) {
            qty--;
            input.value = qty;
            updateSubtotal(index, qty);
        }
    });
});

document.querySelectorAll('.btn-plus').forEach((btn, index) => {
    btn.addEventListener('click', () => {
        const input = document.querySelectorAll('.qty')[index];
        let qty = parseInt(input.value);
        let max = parseInt(input.max);
        if (qty < max) {
            qty++;
            input.value = qty;
            updateSubtotal(index, qty);
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Stok Terbatas!',
                text: 'Jumlah melebihi stok yang tersedia.',
                confirmButtonText: 'OK'
            });
        }
    });
});

function updateSubtotal(index, qty) {
    const input = document.querySelectorAll('.qty')[index];
    const harga = parseInt(input.dataset.harga);
    const id = input.dataset.id;
    const subtotal = harga * qty;
    document.querySelectorAll('.subtotal')[index].innerText = 'Rp' + subtotal.toLocaleString('id-ID');

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "UpdateKeranjang.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        if (xhr.status === 200) {
            const res = JSON.parse(xhr.responseText);
            if (res.status !== 'success') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Update',
                    text: res.message || 'Terjadi kesalahan saat update.'
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Koneksi Gagal',
                text: 'Tidak dapat menghubungi server.'
            });
        }
    };
    xhr.send(`id_keranjang=${id}&jumlah_item=${qty}&subtotal=${subtotal}`);
}

document.querySelectorAll('.btn-delete').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const cartItem = btn.closest('.cart-item');

        Swal.fire({
            title: 'Hapus Produk?',
            text: 'Apakah Anda yakin ingin menghapus item ini dari keranjang?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "hapus_keranjang.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const res = JSON.parse(xhr.responseText);
                        if (res.status === 'success') {
                            cartItem.remove();
                            updateTotalBayar();

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'Item berhasil dihapus.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: res.message || 'Terjadi kesalahan saat menghapus.'
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Koneksi Gagal',
                            text: 'Tidak dapat menghubungi server.'
                        });
                    }
                };
                xhr.send(`id_keranjang=${id}`);
            }
        });
    });
});

function updateTotalBayar() {
    let total = 0;
    document.querySelectorAll('.select-item').forEach((checkbox, index) => {
        if (checkbox.checked) {
            const input = document.querySelectorAll('.qty')[index];
            const qty = parseInt(input.value);
            const harga = parseInt(input.dataset.harga);
            total += qty * harga;
        }
    });

    document.getElementById('total-text').innerText = 'Rp' + total.toLocaleString('id-ID');
}

// Event listener
document.querySelectorAll('.select-item').forEach((cb) => {
    cb.addEventListener('change', updateTotalBayar);
});

document.querySelectorAll('.qty').forEach((input) => {
    input.addEventListener('input', updateTotalBayar);
});

document.querySelectorAll('.btn-minus, .btn-plus').forEach((btn) => {
    btn.addEventListener('click', () => {
        setTimeout(updateTotalBayar, 100);
    });
});
