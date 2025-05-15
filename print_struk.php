<?php
header("Content-Type: application/json");

// Baca JSON dari POST
$data = json_decode(file_get_contents('php://input'), true);

$toko = $data['toko'] ?? 'Toko Default';
$alamat = $data['alamat'] ?? '';
$kasir = $data['kasir'] ?? 'Kasir';
$items = $data['items'] ?? [];

// Konfigurasi printer
$printerPort = "COM3"; // Linux/Windows: "COM3"

if (!file_exists($printerPort)) {
    echo json_encode(['status' => 'error', 'message' => 'Printer tidak ditemukan']);
    exit;
}

// Inisialisasi konten struk
$output = "";

// ESC/POS Commands
$esc = chr(27);
$init_printer = $esc . "@";
$center_on = $esc . "\x61\x01";
$center_off = $esc . "\x61\x00";
$bold_on = $esc . "E\x01";
$bold_off = $esc . "E\x00";
$line = "---------------------------\n";
$feed_lines = $esc . "d\x04";

$output .= $init_printer;

// Nama toko (rata tengah, bold)
$output .= $center_on . $bold_on . "$toko\n" . $bold_off;

// Alamat toko
$output .= "$alamat\n";

// Kasir & Tanggal
$date = date("d/m/Y H:i");
$output .= "Kasir: $kasir\n";
$output .= "Tanggal: $date\n";
$output .= $line;

// Item belanjaan
foreach ($items as $item) {
    $nama = substr($item['nama'], 0, 18);
    $jumlah = $item['jumlah'];
    $harga = $item['harga'];
    $total = $jumlah * $harga;

    $output .= "$nama\n";
    $output .= "$jumlah x " . number_format($harga, 0, ',', '.') . "  ";
    $output .= number_format($total, 0, ',', '.') . "\n";
}

$output .= $line;

// Total keseluruhan
$totalHarga = array_sum(array_map(fn($i) => $i['jumlah'] * $i['harga'], $items));
$output .= $center_off . "Total:   " . number_format($totalHarga, 0, ',', '.') . "\n";
$output .= $line;

// Terima kasih
$output .= $center_on . "Terima Kasih\n";
$output .= "Atas Kunjungan Anda!\n";

// Feed paper
$output .= $feed_lines;

// Kirim ke printer
try {
    $handle = fopen($printerPort, "wb");
    fwrite($handle, $output);
    fclose($handle);
    echo json_encode(['status' => 'success', 'message' => 'Struk berhasil dikirim ke printer']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim ke printer: ' . $e->getMessage()]);
}
?>