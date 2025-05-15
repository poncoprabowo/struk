<?php
require __DIR__ . '/vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

// Terima data dari POST
$toko = $_POST['toko'] ?? 'Toko Default';
$alamat = $_POST['alamat'] ?? '';
$kasir = $_POST['kasir'] ?? 'Kasir Tidak Diketahui';

$barangs = $_POST['barang'];
$jumlahs = $_POST['jumlah'];
$hargas = $_POST['harga'];

date_default_timezone_set('Asia/Jakarta');
$tanggal = date("d/m/Y H:i");

$total = 0;
$items = [];

for ($i = 0; $i < count($barangs); $i++) {
    $barang = $barangs[$i];
    $jumlah = (int)$jumlahs[$i];
    $harga = (int)$hargas[$i];
    if (!empty($barang) && $jumlah > 0 && $harga >= 0) {
        $subtotal = $jumlah * $harga;
        $total += $subtotal;
        $items[] = [
            "nama" => $barang,
            "jumlah" => $jumlah,
            "harga" => $harga,
            "subtotal" => $subtotal
        ];
    }
}

// Format struk dalam teks mentah
function formatRupiah($angka) {
    return number_format($angka, 0, ',', '.');
}

$struk = "-------------------------\n";
$struk .= "     $toko\n";
$struk .= "$alamat\n";
$struk .= "Kasir: $kasir\n";
$struk .= "Tanggal: $tanggal\n";
$struk .= "-------------------------\n";

foreach ($items as $item) {
    $struk .= "{$item['nama']}\n";
    $struk .= "{$item['jumlah']} x " . formatRupiah($item['harga']) . "   " . formatRupiah($item['subtotal']) . "\n";
}
$struk .= "=========================\n";
$struk .= "Total         " . formatRupiah($total) . "\n";
$struk .= "=========================\n";
$struk .= "Terima kasih atas kunjungan!\n\n\n"; // tambah line kosong agar terpotong

// Simpan ke file log (opsional)
file_put_contents("struk_log.txt", $struk . PHP_EOL, FILE_APPEND);

// Kirim ke printer thermal
try {
    // Sesuaikan dengan nama printer Anda (lihat di Windows atau gunakan Bluetooth connector)
    $connector = new WindowsPrintConnector("RPPOS-80"); // Nama printer sesuaikan!
    $printer = new Printer($connector);
    
    // Cetak struk
    $printer -> text($struk);
    $printer -> cut();
    $printer -> close();

    echo "<h3>Struk berhasil dikirim ke printer!</h3>";
} catch (Exception $e) {
    echo "<h3>Gagal mencetak: " . $e->getMessage() . "</h3>";
}

echo "<pre>$struk</pre>";
?>