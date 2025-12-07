<?php
require 'config/db.php';
date_default_timezone_set('Asia/Jakarta');

// Get parameters
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-6 days'));
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'Semua';

$start = mysqli_real_escape_string($conn, $start);
$end = mysqli_real_escape_string($conn, $end);
$jenis = mysqli_real_escape_string($conn, $jenis);

// Filter jenis kendaraan
$jenis_filter = "";
if ($jenis !== 'Semua') {
    $jenis_filter = " AND jenis = '$jenis'";
}

// Get summary data
$q_in = mysqli_query($conn, "SELECT COUNT(*) AS total FROM parkir WHERE DATE(jam_masuk) BETWEEN '$start' AND '$end' $jenis_filter");
$in_range = (int) mysqli_fetch_assoc($q_in)['total'];

$q_out = mysqli_query($conn, "SELECT COUNT(*) AS total FROM parkir WHERE status='OUT' AND DATE(jam_keluar) BETWEEN '$start' AND '$end' $jenis_filter");
$out_range = (int) mysqli_fetch_assoc($q_out)['total'];

$q_income = mysqli_query($conn, "SELECT COALESCE(SUM(biaya),0) AS total FROM parkir WHERE status='OUT' AND DATE(jam_keluar) BETWEEN '$start' AND '$end' $jenis_filter");
$income_range = (float) mysqli_fetch_assoc($q_income)['total'];

// Get detail data per jenis
$q_motor_in = mysqli_query($conn, "SELECT COUNT(*) AS total FROM parkir WHERE jenis='Motor' AND DATE(jam_masuk) BETWEEN '$start' AND '$end'");
$motor_in = (int) mysqli_fetch_assoc($q_motor_in)['total'];

$q_motor_out = mysqli_query($conn, "SELECT COUNT(*) AS total FROM parkir WHERE jenis='Motor' AND status='OUT' AND DATE(jam_keluar) BETWEEN '$start' AND '$end'");
$motor_out = (int) mysqli_fetch_assoc($q_motor_out)['total'];

$q_motor_income = mysqli_query($conn, "SELECT COALESCE(SUM(biaya),0) AS total FROM parkir WHERE jenis='Motor' AND status='OUT' AND DATE(jam_keluar) BETWEEN '$start' AND '$end'");
$motor_income = (float) mysqli_fetch_assoc($q_motor_income)['total'];

$q_mobil_in = mysqli_query($conn, "SELECT COUNT(*) AS total FROM parkir WHERE jenis='Mobil' AND DATE(jam_masuk) BETWEEN '$start' AND '$end'");
$mobil_in = (int) mysqli_fetch_assoc($q_mobil_in)['total'];

$q_mobil_out = mysqli_query($conn, "SELECT COUNT(*) AS total FROM parkir WHERE jenis='Mobil' AND status='OUT' AND DATE(jam_keluar) BETWEEN '$start' AND '$end'");
$mobil_out = (int) mysqli_fetch_assoc($q_mobil_out)['total'];

$q_mobil_income = mysqli_query($conn, "SELECT COALESCE(SUM(biaya),0) AS total FROM parkir WHERE jenis='Mobil' AND status='OUT' AND DATE(jam_keluar) BETWEEN '$start' AND '$end'");
$mobil_income = (float) mysqli_fetch_assoc($q_mobil_income)['total'];

// Get detail transactions
$query_detail = "SELECT id, no_plat, jenis, jam_masuk, jam_keluar, durasi_menit, biaya, status, petugas 
                 FROM parkir 
                 WHERE DATE(jam_masuk) BETWEEN '$start' AND '$end' $jenis_filter
                 ORDER BY jam_masuk DESC";
$result_detail = mysqli_query($conn, $query_detail);

// Set headers for Excel download
$filename = "Laporan_Parkir_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output Excel content
echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #2563eb; color: white; font-weight: bold; }
        .header { background-color: #2563eb; color: white; padding: 15px; margin-bottom: 20px; }
        .summary-section { margin-bottom: 30px; }
        .summary-table th { background-color: #10b981; }
        .detail-table th { background-color: #2563eb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .subtitle { font-size: 12px; margin-bottom: 5px; }
        .total-row { background-color: #f1f5f9; font-weight: bold; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN PARKIR E-PARKING</h1>
        <p>Periode: <?= htmlspecialchars($start) ?> s/d <?= htmlspecialchars($end) ?></p>
        <p>Jenis Kendaraan: <?= htmlspecialchars($jenis) ?></p>
        <p>Dicetak pada: <?= date('d-m-Y H:i:s') ?></p>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="title">RINGKASAN DATA</div>
        <table class="summary-table">
            <tr>
                <th>Keterangan</th>
                <th class="text-center">Jumlah</th>
            </tr>
            <tr>
                <td>Total Kendaraan Masuk</td>
                <td class="text-center"><?= number_format($in_range) ?></td>
            </tr>
            <tr>
                <td>Total Kendaraan Keluar</td>
                <td class="text-center"><?= number_format($out_range) ?></td>
            </tr>
            <tr>
                <td>Kendaraan Masih Parkir</td>
                <td class="text-center"><?= number_format($in_range - $out_range) ?></td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Pendapatan</strong></td>
                <td class="text-right"><strong>Rp <?= number_format($income_range, 0, ',', '.') ?></strong></td>
            </tr>
        </table>
    </div>

    <!-- Detail per Jenis Kendaraan -->
    <div class="summary-section">
        <div class="title">DETAIL PER JENIS KENDARAAN</div>
        <table class="summary-table">
            <tr>
                <th>Jenis Kendaraan</th>
                <th class="text-center">Masuk</th>
                <th class="text-center">Keluar</th>
                <th class="text-right">Pendapatan (Rp)</th>
            </tr>
            <tr>
                <td>Motor</td>
                <td class="text-center"><?= number_format($motor_in) ?></td>
                <td class="text-center"><?= number_format($motor_out) ?></td>
                <td class="text-right">Rp <?= number_format($motor_income, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Mobil</td>
                <td class="text-center"><?= number_format($mobil_in) ?></td>
                <td class="text-center"><?= number_format($mobil_out) ?></td>
                <td class="text-right">Rp <?= number_format($mobil_income, 0, ',', '.') ?></td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td class="text-center"><strong><?= number_format($in_range) ?></strong></td>
                <td class="text-center"><strong><?= number_format($out_range) ?></strong></td>
                <td class="text-right"><strong>Rp <?= number_format($income_range, 0, ',', '.') ?></strong></td>
            </tr>
        </table>
    </div>

    <!-- Detail Transactions -->
    <div class="summary-section">
        <div class="title">DETAIL TRANSAKSI</div>
        <table class="detail-table">
            <thead>
                <tr>
                    <th class="text-center">No</th>
                    <th>No. Plat</th>
                    <th class="text-center">Jenis</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th class="text-center">Durasi (menit)</th>
                    <th class="text-right">Biaya (Rp)</th>
                    <th class="text-center">Status</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_biaya = 0;
                while ($row = mysqli_fetch_assoc($result_detail)): 
                    $total_biaya += $row['biaya'] ?? 0;
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['no_plat']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['jenis']) ?></td>
                    <td><?= $row['jam_masuk'] ? date('d-m-Y H:i', strtotime($row['jam_masuk'])) : '-' ?></td>
                    <td><?= $row['jam_keluar'] ? date('d-m-Y H:i', strtotime($row['jam_keluar'])) : '-' ?></td>
                    <td class="text-center"><?= $row['durasi_menit'] ?? '-' ?></td>
                    <td class="text-right"><?= $row['biaya'] ? 'Rp ' . number_format($row['biaya'], 0, ',', '.') : '-' ?></td>
                    <td class="text-center">
                        <?php if ($row['status'] === 'OUT'): ?>
                            <span style="color: green; font-weight: bold;">KELUAR</span>
                        <?php else: ?>
                            <span style="color: orange; font-weight: bold;">PARKIR</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['petugas'] ?? '-') ?></td>
                </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="6" class="text-right"><strong>TOTAL PENDAPATAN</strong></td>
                    <td class="text-right"><strong>Rp <?= number_format($total_biaya, 0, ',', '.') ?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div style="margin-top: 30px; font-size: 11px; color: #666;">
        <p>Dokumen ini dibuat secara otomatis oleh Sistem E-PARKING</p>
        <p>© <?= date('Y') ?> E-PARKING. All rights reserved.</p>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>