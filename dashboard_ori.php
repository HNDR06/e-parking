<?php
include 'config/db.php';
date_default_timezone_set('Asia/Jakarta');

$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-6 days'));
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'Semua';

$start = mysqli_real_escape_string($conn, $start);
$end = mysqli_real_escape_string($conn, $end);
$jenis = mysqli_real_escape_string($conn, $jenis);

$start_dt = DateTime::createFromFormat('Y-m-d', $start);
$end_dt = DateTime::createFromFormat('Y-m-d', $end);
if (!$start_dt || !$end_dt) {
    $start = date('Y-m-d', strtotime('-6 days'));
    $end = date('Y-m-d');
    $start_dt = new DateTime($start);
    $end_dt = new DateTime($end);
}

if ($start_dt > $end_dt) {
    $tmp = $start_dt;
    $start_dt = $end_dt;
    $end_dt = $tmp;
    $start = $start_dt->format('Y-m-d');
    $end = $end_dt->format('Y-m-d');
}

// Filter jenis kendaraan
$jenis_filter = "";
if ($jenis !== 'Semua') {
    $jenis_filter = " AND jenis = '$jenis'";
}

$q_in = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM parkir
     WHERE DATE(jam_masuk) BETWEEN '$start' AND '$end' $jenis_filter"
);
$in_range = (int) mysqli_fetch_assoc($q_in)['total'];

$q_out = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM parkir
     WHERE status='OUT' AND DATE(jam_keluar) BETWEEN '$start' AND '$end' $jenis_filter"
);
$out_range = (int) mysqli_fetch_assoc($q_out)['total'];

$q_income = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(biaya),0) AS total FROM parkir
     WHERE status='OUT' AND DATE(jam_keluar) BETWEEN '$start' AND '$end' $jenis_filter"
);
$income_range = (float) mysqli_fetch_assoc($q_income)['total'];

$labels = [];
$data_masuk = [];
$data_keluar = [];

$interval = new DateInterval('P1D');
$period = new DatePeriod($start_dt, $interval, (clone $end_dt)->modify('+1 day'));

foreach ($period as $dt) {
    $d = $dt->format('Y-m-d');
    $labels[] = $dt->format('d M');

    $qmi = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM parkir WHERE DATE(jam_masuk) = '$d' $jenis_filter"
    );
    $mi = (int) mysqli_fetch_assoc($qmi)['total'];
    $data_masuk[] = $mi;

    $qmo = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM parkir WHERE status='OUT' AND DATE(jam_keluar) = '$d' $jenis_filter"
    );
    $mo = (int) mysqli_fetch_assoc($qmo)['total'];
    $data_keluar[] = $mo;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>E-PARKING | Dashboard Monitoring</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-title-text h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .page-title-text p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Filter Section */
        .filter-section {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            letter-spacing: 0.01em;
        }

        .form-input, .form-select {
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            color: var(--text-primary);
            background: var(--bg-primary);
            transition: var(--transition);
            font-family: inherit;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
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
            text-decoration: none;
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

        .btn-success {
            background: var(--secondary);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }

        .date-range-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            font-size: 13px;
            color: var(--text-secondary);
            grid-column: 1 / -1;
        }

        .date-range-info strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            opacity: 0;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-title {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: var(--transition);
        }

        .stat-card:hover .stat-icon {
            transform: rotate(10deg) scale(1.1);
        }

        .stat-card.in .stat-icon {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
        }

        .stat-card.out .stat-icon {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .stat-card.income .stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 4px;
            letter-spacing: -0.02em;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Chart Card */
        .chart-card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }

        .chart-header {
            margin-bottom: 24px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .chart-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .chart-container {
            position: relative;
            height: 400px;
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

            .filter-form {
                grid-template-columns: 1fr;
            }

            .page-title {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-title-text h1 {
                font-size: 24px;
            }

            .stat-value {
                font-size: 28px;
            }

            .chart-card {
                padding: 20px;
            }

            .chart-container {
                height: 320px;
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

        .stat-card,
        .chart-card,
        .filter-section {
            animation: fadeIn 0.5s ease-out backwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .chart-card { animation-delay: 0.4s; }
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
            <div class="page-title-text">
                <h1>Dashboard Monitoring</h1>
                <p>Pantau aktivitas parkir dan pendapatan secara real-time</p>
            </div>
            <a href="export_excel.php?start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&jenis=<?= urlencode($jenis) ?>" class="btn btn-success">
                <i class="bi bi-file-earmark-excel-fill"></i>
                Export Excel
            </a>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label" for="start">Tanggal Mulai</label>
                    <input type="date" id="start" name="start" class="form-input" value="<?= htmlspecialchars($start) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="end">Tanggal Akhir</label>
                    <input type="date" id="end" name="end" class="form-input" value="<?= htmlspecialchars($end) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="jenis">Jenis Kendaraan</label>
                    <select id="jenis" name="jenis" class="form-select">
                        <option value="Semua" <?= $jenis === 'Semua' ? 'selected' : '' ?>>Semua</option>
                        <option value="Motor" <?= $jenis === 'Motor' ? 'selected' : '' ?>>Motor</option>
                        <option value="Mobil" <?= $jenis === 'Mobil' ? 'selected' : '' ?>>Mobil</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill"></i>
                        Terapkan Filter
                    </button>
                </div>
                <div class="date-range-info">
                    <i class="bi bi-calendar-range"></i>
                    <span><strong><?= htmlspecialchars($start) ?></strong> hingga <strong><?= htmlspecialchars($end) ?></strong></span>
                    <span style="margin-left: 16px;">•</span>
                    <i class="bi bi-<?= $jenis === 'Motor' ? 'bicycle' : ($jenis === 'Mobil' ? 'car-front' : 'grid-3x3-gap') ?>"></i>
                    <span><strong><?= htmlspecialchars($jenis) ?></strong></span>
                </div>
            </form>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card in">
                <div class="stat-header">
                    <span class="stat-title">Kendaraan Masuk</span>
                    <div class="stat-icon"><i class="bi bi-arrow-down-circle-fill"></i></div>
                </div>
                <div class="stat-value"><?= number_format($in_range) ?></div>
                <div class="stat-label">Total kendaraan masuk dalam periode</div>
            </div>

            <div class="stat-card out">
                <div class="stat-header">
                    <span class="stat-title">Kendaraan Keluar</span>
                    <div class="stat-icon"><i class="bi bi-arrow-up-circle-fill"></i></div>
                </div>
                <div class="stat-value"><?= number_format($out_range) ?></div>
                <div class="stat-label">Total kendaraan keluar dalam periode</div>
            </div>

            <div class="stat-card income">
                <div class="stat-header">
                    <span class="stat-title">Total Pendapatan</span>
                    <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
                </div>
                <div class="stat-value">Rp <?= number_format($income_range, 0, ',', '.') ?></div>
                <div class="stat-label">Pendapatan dalam periode</div>
            </div>
        </div>

        <!-- Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h2 class="chart-title">Grafik Aktivitas Parkir</h2>
                <p class="chart-subtitle">Perbandingan kendaraan masuk dan keluar per hari</p>
            </div>
            <div class="chart-container">
                <canvas id="chartAktivitas"></canvas>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('chartAktivitas').getContext('2d');
        const labels = <?= json_encode($labels) ?>;
        const masukData = <?= json_encode($data_masuk) ?>;
        const keluarData = <?= json_encode($data_keluar) ?>;

        const gradientMasuk = ctx.createLinearGradient(0, 0, 0, 400);
        gradientMasuk.addColorStop(0, 'rgba(37, 99, 235, 0.2)');
        gradientMasuk.addColorStop(1, 'rgba(37, 99, 235, 0)');

        const gradientKeluar = ctx.createLinearGradient(0, 0, 0, 400);
        gradientKeluar.addColorStop(0, 'rgba(239, 68, 68, 0.2)');
        gradientKeluar.addColorStop(1, 'rgba(239, 68, 68, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Kendaraan Masuk',
                        data: masukData,
                        borderColor: '#2563eb',
                        backgroundColor: gradientMasuk,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderWidth: 3,
                        fill: true
                    },
                    {
                        label: 'Kendaraan Keluar',
                        data: keluarData,
                        borderColor: '#ef4444',
                        backgroundColor: gradientKeluar,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderWidth: 3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeInOutQuart'
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 16,
                            font: {
                                family: 'Inter',
                                size: 13,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 13
                        },
                        displayColors: true,
                        boxPadding: 6
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 12
                            },
                            color: '#64748b'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: Math.max(...masukData.concat(keluarData)) + 5,
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                family: 'Inter',
                                size: 12
                            },
                            color: '#64748b',
                            padding: 8
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>