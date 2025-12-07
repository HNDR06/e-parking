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

// Real-time parking stats
$q_motor_active = mysqli_query($conn, "SELECT COUNT(*) AS total FROM parkir WHERE status='IN' AND jenis='Motor'");
$motor_active = (int) mysqli_fetch_assoc($q_motor_active)['total'];

$q_mobil_active = mysqli_query($conn, "SELECT COUNT(*) AS total FROM parkir WHERE status='IN' AND jenis='Mobil'");
$mobil_active = (int) mysqli_fetch_assoc($q_mobil_active)['total'];

$motor_capacity = 50;
$mobil_capacity = 50;
$motor_available = $motor_capacity - $motor_active;
$mobil_available = $mobil_capacity - $mobil_active;

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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style/style_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="masuk.php">
                    <i class="bi bi-car-front-fill"></i>
                    Kendaraan Masuk
                </a>
                <a href="dashboard.php" class="active">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
                <a href="keluar.php">
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
                <p>Pantau aktivitas parkir dan slot parkir secara real-time</p>
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
                <div class="stat-label">Total dalam periode</div>
            </div>

            <div class="stat-card out">
                <div class="stat-header">
                    <span class="stat-title">Kendaraan Keluar</span>
                    <div class="stat-icon"><i class="bi bi-arrow-up-circle-fill"></i></div>
                </div>
                <div class="stat-value"><?= number_format($out_range) ?></div>
                <div class="stat-label">Total dalam periode</div>
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

        <!-- Real-Time Parking Status Section -->
        <div class="parking-status-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-broadcast"></i>
                    Status Parkir Real-Time
                </h2>
                <div class="last-update">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span id="lastUpdate">Memperbarui...</span>
                </div>
            </div>

            <!-- Parking Overview Cards -->
            <div class="parking-overview">
                <!-- Motor Section -->
                <div class="parking-area-card">
                    <div class="area-header">
                        <div class="area-icon motor">
                            <i class="bi bi-bicycle"></i>
                        </div>
                        <div class="area-info">
                            <h3 class="area-title">Area Motor</h3>
                            <p class="area-capacity">Kapasitas: <?= $motor_capacity ?> Slot</p>
                        </div>
                    </div>
                    <div class="area-stats">
                        <div class="stat-item occupied">
                            <div class="stat-number" id="motorOccupied"><?= $motor_active ?></div>
                            <div class="stat-label">Terisi</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item available">
                            <div class="stat-number" id="motorAvailable"><?= $motor_available ?></div>
                            <div class="stat-label">Tersedia</div>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill motor" id="motorProgress" style="width: <?= ($motor_active/$motor_capacity)*100 ?>%"></div>
                    </div>
                    <div class="occupancy-text">
                        <span id="motorPercentage"><?= round(($motor_active/$motor_capacity)*100, 1) ?>%</span> Terpakai
                    </div>
                </div>

                <!-- Mobil Section -->
                <div class="parking-area-card">
                    <div class="area-header">
                        <div class="area-icon mobil">
                            <i class="bi bi-car-front-fill"></i>
                        </div>
                        <div class="area-info">
                            <h3 class="area-title">Area Mobil</h3>
                            <p class="area-capacity">Kapasitas: <?= $mobil_capacity ?> Slot</p>
                        </div>
                    </div>
                    <div class="area-stats">
                        <div class="stat-item occupied">
                            <div class="stat-number" id="mobilOccupied"><?= $mobil_active ?></div>
                            <div class="stat-label">Terisi</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item available">
                            <div class="stat-number" id="mobilAvailable"><?= $mobil_available ?></div>
                            <div class="stat-label">Tersedia</div>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill mobil" id="mobilProgress" style="width: <?= ($mobil_active/$mobil_capacity)*100 ?>%"></div>
                    </div>
                    <div class="occupancy-text">
                        <span id="mobilPercentage"><?= round(($mobil_active/$mobil_capacity)*100, 1) ?>%</span> Terpakai
                    </div>
                </div>
            </div>

            <!-- Parking Slots Grid -->
            <div class="parking-slots-container">
                <!-- Motor Slots -->
                <div class="slots-section">
                    <div class="slots-header">
                        <h3><i class="bi bi-bicycle"></i> Slot Motor</h3>
                        <span class="slots-count"><span id="motorFilledCount"><?= $motor_active ?></span>/<?= $motor_capacity ?></span>
                    </div>
                    <div class="slots-grid" id="motorSlots">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Mobil Slots -->
                <div class="slots-section">
                    <div class="slots-header">
                        <h3><i class="bi bi-car-front-fill"></i> Slot Mobil</h3>
                        <span class="slots-count"><span id="mobilFilledCount"><?= $mobil_active ?></span>/<?= $mobil_capacity ?></span>
                    </div>
                    <div class="slots-grid mobil" id="mobilSlots">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="legend">
                <div class="legend-item">
                    <span class="legend-color available"></span>
                    <span>Tersedia</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color occupied"></span>
                    <span>Terisi</span>
                </div>
            </div>
        </div>

        <!-- Active Vehicles Table Section - NEW -->
        <div class="active-vehicles-section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">
                        <i class="bi bi-car-front"></i>
                        Kendaraan yang Masih Terparkir
                    </h2>
                    <p class="section-subtitle">Daftar lengkap kendaraan dengan status IN</p>
                </div>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="activeSearchInput" placeholder="Cari plat nomor..." class="search-input">
                    </div>
                    <select id="activeFilterJenis" class="form-select">
                        <option value="all">Semua Jenis</option>
                        <option value="Motor">Motor</option>
                        <option value="Mobil">Mobil</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-hash"></i> No</th>
                            <th><i class="bi bi-tag"></i> Slot</th>
                            <th><i class="bi bi-credit-card-2-front"></i> Plat Nomor</th>
                            <th><i class="bi bi-list-ul"></i> Jenis</th>
                            <th><i class="bi bi-clock"></i> Jam Masuk</th>
                            <th><i class="bi bi-hourglass-split"></i> Durasi</th>
                            <th><i class="bi bi-cash"></i> Est. Biaya</th>
                            <th><i class="bi bi-gear"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="activeVehiclesTableBody">
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="bi bi-hourglass-split"></i>
                                <br>Memuat data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="table-info">
                    <i class="bi bi-info-circle"></i>
                    Total: <span id="activeVehiclesCount">0</span> kendaraan masih terparkir
                </div>
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
        // Chart Configuration
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
                                family: 'Plus Jakarta Sans',
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
                                family: 'Plus Jakarta Sans',
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
                                family: 'Plus Jakarta Sans',
                                size: 12
                            },
                            color: '#64748b',
                            padding: 8
                        }
                    }
                }
            }
        });

        // Global variable to store active vehicles data
        let activeVehiclesData = [];

        // Real-time parking slots update
        function updateParkingSlots() {
            fetch('get_parking_status.php')
                .then(response => response.json())
                .then(data => {
                    // Update statistics
                    document.getElementById('motorOccupied').textContent = data.motor.occupied;
                    document.getElementById('motorAvailable').textContent = data.motor.available;
                    document.getElementById('motorPercentage').textContent = data.motor.percentage + '%';
                    document.getElementById('motorProgress').style.width = data.motor.percentage + '%';
                    document.getElementById('motorFilledCount').textContent = data.motor.occupied;
                    
                    document.getElementById('mobilOccupied').textContent = data.mobil.occupied;
                    document.getElementById('mobilAvailable').textContent = data.mobil.available;
                    document.getElementById('mobilPercentage').textContent = data.mobil.percentage + '%';
                    document.getElementById('mobilProgress').style.width = data.mobil.percentage + '%';
                    document.getElementById('mobilFilledCount').textContent = data.mobil.occupied;
                    
                    // Update slots
                    renderSlots('motorSlots', data.motor.slots, 'Motor');
                    renderSlots('mobilSlots', data.mobil.slots, 'Mobil');
                    
                    // Update active vehicles table
                    activeVehiclesData = data.activeVehicles || [];
                    renderActiveVehiclesTable();
                    
                    // Update last update time
                    const now = new Date();
                    const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    document.getElementById('lastUpdate').textContent = 'Diperbarui: ' + timeStr;
                })
                .catch(error => {
                    console.error('Error updating parking status:', error);
                });
        }

        function renderSlots(containerId, slots, type) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            
            slots.forEach(slot => {
                const slotDiv = document.createElement('div');
                slotDiv.className = 'slot ' + (slot.occupied ? 'occupied' : 'available');
                
                const slotNumber = document.createElement('div');
                slotNumber.className = 'slot-number';
                slotNumber.textContent = slot.number;
                
                const slotIcon = document.createElement('div');
                slotIcon.className = 'slot-icon';
                slotIcon.innerHTML = type === 'Motor' 
                    ? '<i class="bi bi-bicycle"></i>' 
                    : '<i class="bi bi-car-front-fill"></i>';
                
                slotDiv.appendChild(slotNumber);
                slotDiv.appendChild(slotIcon);
                
                if (slot.occupied && slot.data) {
                    slotDiv.title = `Plat: ${slot.data.no_plat}\nMasuk: ${slot.data.jam_masuk}\nDurasi: ${slot.data.durasi}`;
                    slotDiv.style.cursor = 'pointer';
                }
                
                container.appendChild(slotDiv);
            });
        }

        function renderActiveVehiclesTable() {
            const tbody = document.getElementById('activeVehiclesTableBody');
            const countSpan = document.getElementById('activeVehiclesCount');
            
            // Apply filters
            const searchValue = document.getElementById('activeSearchInput').value.toLowerCase();
            const filterJenis = document.getElementById('activeFilterJenis').value;
            
            let filteredData = activeVehiclesData.filter(vehicle => {
                const matchSearch = vehicle.no_plat.toLowerCase().includes(searchValue);
                const matchJenis = filterJenis === 'all' || vehicle.jenis === filterJenis;
                return matchSearch && matchJenis;
            });
            
            countSpan.textContent = filteredData.length;
            
            if (filteredData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <br>Tidak ada kendaraan yang masih terparkir
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = '';
            filteredData.forEach((vehicle, index) => {
                const row = document.createElement('tr');
                
                // Determine duration class
                let durationClass = '';
                if (vehicle.duration_hours >= 12) {
                    durationClass = 'duration-critical';
                } else if (vehicle.duration_hours >= 6) {
                    durationClass = 'duration-warning';
                } else if (vehicle.duration_hours >= 3) {
                    durationClass = 'duration-caution';
                }
                
                row.className = durationClass;
                
                const jenisIcon = vehicle.jenis === 'Motor' ? 'bi-bicycle' : 'bi-car-front-fill';
                
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td><span class="slot-badge ${vehicle.jenis.toLowerCase()}">${vehicle.slot}</span></td>
                    <td><span class="plat-number">${vehicle.no_plat}</span></td>
                    <td><span class="vehicle-type"><i class="bi ${jenisIcon}"></i> ${vehicle.jenis}</span></td>
                    <td><span class="datetime">${vehicle.jam_masuk}</span></td>
                    <td><span class="duration ${durationClass}">${vehicle.durasi}</span></td>
                    <td><span class="price">Rp ${vehicle.est_biaya}</span></td>
                    <td>
                        <a href="keluar.php?search=${vehicle.barcode_id}" class="btn-action btn-exit" title="Proses Keluar">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Search functionality
        document.getElementById('activeSearchInput').addEventListener('keyup', function() {
            renderActiveVehiclesTable();
        });

        // Filter functionality
        document.getElementById('activeFilterJenis').addEventListener('change', function() {
            renderActiveVehiclesTable();
        });

        // Initial load
        updateParkingSlots();
        
        // Update every 5 seconds
        setInterval(updateParkingSlots, 5000);
    </script>
</body>
</html>