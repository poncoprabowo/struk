<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit;
}

$toko = htmlspecialchars($data['toko'] ?? 'Toko Serba Ada');
$alamat = htmlspecialchars($data['alamat'] ?? '');
$kasir = htmlspecialchars($data['kasir'] ?? 'Kasir');
$items = $data['items'] ?? [];

require __DIR__ . '/vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

// Pastikan printer terhubung via USB atau Samba share
$connector = new FilePrintConnector("/dev/usb/lp0"); // Sesuaikan dengan path printer Anda
$printer = new Printer($connector);

date_default_timezone_set('Asia/Jakarta');
$tanggal = date("d/m/Y H:i");

try {
    // Header Toko
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("     {$toko}\n");
    if ($alamat) {
        $printer->text("{$alamat}\n");
    }
    $printer->text("Kasir: {$kasir}\n");
    $printer->text("Tanggal: {$tanggal}\n");
    $printer->text("-------------------------\n");

    $total = 0;
    foreach ($items as $item) {
        $barang = substr($item['barang'], 0, 25);
        $jumlah = (int)$item['jumlah'];
        $harga = (int)$item['harga'];
        $subtotal = $jumlah * $harga;
        $total += $subtotal;

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("{$barang}\n");
        $printer->text("{$jumlah} x " . number_format($harga, 0, ',', '.') . "   ");
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text(number_format($subtotal, 0, ',', '.') . "\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("----------------------------\n");
    }

    // Total
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("=========================\n");
    $printer->setEmphasis(true);
    $printer->text("Total: " . number_format($total, 0, ',', '.') . "\n");
    $printer->setEmphasis(false);
    $printer->text("=========================\n\n");

    // Footer
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("Terima Kasih atas kunjungan!\n");
    $printer->feed(2);
    $printer->cut();

    $printer->close();

    echo json_encode(['status' => 'success', 'message' => 'Struk berhasil dikirim ke printer']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mencetak: ' . $e->getMessage()]);
}
?>