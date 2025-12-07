<?php
include './config/db.php';

// Fungsi untuk generate barcode unik
function generateBarcodeID() {
    // Contoh format: PARK-20251021-XXXX
    $prefix = "PARK-" . date("Ymd") . "-";
    $random = strtoupper(substr(uniqid(), -4)); // ambil 4 karakter unik terakhir
    return $prefix . $random;
}

// Statistik parkir real-time
$total_capacity = 100;
$count_in = 0;
$slots_available = $total_capacity;
$data_in = [];

// Query jumlah kendaraan status IN dan ambil data kendaraan IN
$status_in = mysqli_real_escape_string($conn, 'IN');
$sql_count_in = "SELECT COUNT(*) AS jumlah FROM parkir WHERE `status`='$status_in'";
$result_count_in = mysqli_query($conn, $sql_count_in);
if ($result_count_in) {
    $row_count_in = mysqli_fetch_assoc($result_count_in);
    $count_in = intval($row_count_in['jumlah']);
    $slots_available = $total_capacity - $count_in;
} else {
    echo "<div style='color: red; font-weight: bold;'>❌ Query count error: " . mysqli_error($conn) . "</div>";
}

$sql_in = "SELECT id, barcode_id, no_plat, jenis, jam_masuk FROM parkir WHERE `status`='$status_in' ORDER BY jam_masuk DESC";
$result_in = mysqli_query($conn, $sql_in);
if ($result_in) {
    while ($row = mysqli_fetch_assoc($result_in)) {
        $data_in[] = $row;
    }
} else {
    echo "<div style='color: red; font-weight: bold;'>❌ Query data error: " . mysqli_error($conn) . "</div>";
}

// Proses simpan data
if (isset($_POST['submit'])) {
    $no_plat = strtoupper(trim($_POST['no_plat']));
    $jenis   = $_POST['jenis'];
    $jam_masuk = date('Y-m-d H:i:s');
    $barcode_id = generateBarcodeID();
    $status = 'IN';

    // Amankan input
    $no_plat_safe = mysqli_real_escape_string($conn, $no_plat);
    $jenis_safe = mysqli_real_escape_string($conn, $jenis);
    $jam_masuk_safe = mysqli_real_escape_string($conn, $jam_masuk);
    $barcode_id_safe = mysqli_real_escape_string($conn, $barcode_id);
    $status_safe = mysqli_real_escape_string($conn, $status);

    // Query simpan
    $query = "INSERT INTO parkir (no_plat, jenis, jam_masuk, barcode_id, status)
              VALUES ('$no_plat_safe', '$jenis_safe', '$jam_masuk_safe', '$barcode_id_safe', '$status_safe')";

    if (mysqli_query($conn, $query)) {
        // Tidak langsung cetak karcis, hanya simpan data dan reload halaman
        header("Location: index.php");
        exit;
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ Gagal menyimpan data: " . mysqli_error($conn) . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>E-PARKING | Kendaraan Masuk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --primary-light: #3b82f6;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Header */
        .header {
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border);
            padding: 24px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .header-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        /* Navigation */
        .navbar {
            display: flex;
            gap: 8px;
        }

        .navbar a {
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .navbar a i {
            font-size: 16px;
            transition: var(--transition);
        }

        .navbar a:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            transform: translateY(-2px);
        }

        .navbar a:hover i {
            transform: scale(1.1);
        }

        .navbar a.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .navbar a.active i {
            animation: iconBounce 0.6s ease-in-out;
        }

        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* Page Title */
        .page-title {
            margin-bottom: 32px;
        }

        .page-title h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .page-title p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Grid Layout */
        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        /* Card */
        .card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .card-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-light);
            background: var(--bg-tertiary);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            font-size: 20px;
            color: var(--primary);
        }

        .card-body {
            padding: 24px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            letter-spacing: 0.01em;
            margin-bottom: 8px;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 18px;
            pointer-events: none;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            color: var(--text-primary);
            background: var(--bg-primary);
            transition: var(--transition);
            font-family: inherit;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .form-select {
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="%2394a3b8" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 20px;
            cursor: pointer;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05), rgba(59, 130, 246, 0.05));
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: var(--radius-lg);
            padding: 20px;
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            background: var(--primary);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-subtitle {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        }

        thead th {
            padding: 16px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tbody tr {
            border-bottom: 1px solid var(--border-light);
            transition: var(--transition);
        }

        tbody tr:hover {
            background: var(--bg-tertiary);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody td {
            padding: 16px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        tbody td:first-child {
            font-weight: 600;
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        /* Action Button */
        .btn-cetak {
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-cetak:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .navbar {
                width: 100%;
                flex-wrap: wrap;
            }

            .navbar a {
                flex: 1;
                text-align: center;
                padding: 12px 16px;
            }

            .container {
                padding: 24px 16px;
            }

            .grid-2col {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .page-title h1 {
                font-size: 24px;
            }

            .stat-value {
                font-size: 28px;
            }

            .button-group {
                flex-direction: column;
            }

            thead th,
            tbody td {
                padding: 12px;
                font-size: 13px;
            }
        }

        /* Loading Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeIn 0.5s ease-out backwards;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">P</div>
                <span class="logo-text">E-PARKING</span>
            </div>
            <nav class="navbar">
                <a href="index.php" <?php if(basename($_SERVER['PHP_SELF']) === 'index.php'): ?>class="active"<?php endif; ?>>
                    <i class="bi bi-car-front-fill"></i>
                    Kendaraan Masuk
                </a>
                <a href="dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) === 'dashboard.php'): ?>class="active"<?php endif; ?>>
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
                <a href="keluar.php" <?php if(basename($_SERVER['PHP_SELF']) === 'keluar.php'): ?>class="active"<?php endif; ?>>
                    <i class="bi bi-box-arrow-right"></i>
                    Kendaraan Keluar
                </a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Title -->
        <div class="page-title">
            <h1>Input Kendaraan Masuk</h1>
            <p>Kelola kendaraan yang masuk ke area parkir</p>
        </div>

        <!-- Form & Stats Grid -->
        <div class="grid-2col">
            <!-- Input Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="bi bi-clipboard-plus"></i>
                        Form Input Kendaraan
                    </h2>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="no_plat">Nomor Plat Kendaraan</label>
                            <div class="input-wrapper">
                                <i class="bi bi-123 input-icon"></i>
                                <input type="text" name="no_plat" id="no_plat" class="form-input" placeholder="Contoh: B 1234 XYZ" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="jenis">Jenis Kendaraan</label>
                            <div class="input-wrapper">
                                <i class="bi bi-car-front input-icon"></i>
                                <select name="jenis" id="jenis" class="form-select" required>
                                    <option value="" disabled selected>Pilih Jenis Kendaraan</option>
                                    <option value="Motor">Motor</option>
                                    <option value="Mobil">Mobil</option>
                                </select>
                            </div>
                        </div>

                        <div class="button-group">
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle-fill"></i>
                                Simpan Data
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                                Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="bi bi-bar-chart-fill"></i>
                        Status Parkir Real-time
                    </h2>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon-wrapper">
                                <i class="bi bi-p-square-fill"></i>
                            </div>
                            <div class="stat-label">Slot Tersedia</div>
                            <div class="stat-value"><?php echo $slots_available; ?></div>
                            <div class="stat-subtitle">dari <?php echo $total_capacity; ?> slot</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon-wrapper">
                                <i class="bi bi-car-front-fill"></i>
                            </div>
                            <div class="stat-label">Terisi</div>
                            <div class="stat-value"><?php echo $count_in; ?></div>
                            <div class="stat-subtitle">kendaraan aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parking List -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="bi bi-list-ul"></i>
                    Daftar Kendaraan Terparkir
                </h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="bi bi-ticket-perforated"></i> No. Tiket</th>
                                <th><i class="bi bi-123"></i> Plat Nomor</th>
                                <th><i class="bi bi-car-front"></i> Jenis</th>
                                <th><i class="bi bi-clock"></i> Waktu Masuk</th>
                                <th><i class="bi bi-printer"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($data_in) > 0): ?>
                            <?php foreach ($data_in as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['barcode_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_plat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['jenis']); ?></td>
                                    <td><?php echo date('d-m-Y H:i', strtotime($row['jam_masuk'])); ?></td>
                                    <td>
                                        <a href="cetak.php?id=<?php echo urlencode($row['id']); ?>" class="btn-cetak" target="_blank">
                                            <i class="bi bi-printer-fill"></i>
                                            Cetak
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                    <div>Belum ada kendaraan terparkir</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>