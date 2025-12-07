<?php
include 'config/db.php';
date_default_timezone_set('Asia/Jakarta');

// Inisialisasi variabel
$data = null;
$pesan = '';
$biaya_per_jam = 2000; // tarif dasar per jam

// Proses keluar parkir via AJAX (langsung ke DB, aman)
if (isset($_GET['action']) && $_GET['action'] === 'keluar_ajax' && isset($_GET['barcode'])) {
    $barcode = trim($_GET['barcode']);
    $barcode_safe = mysqli_real_escape_string($conn, $barcode);
    $response = ['success' => false, 'message' => ''];

    // Cari data kendaraan yang masih IN dengan barcode tersebut
    $query = "SELECT * FROM parkir WHERE barcode_id = '$barcode_safe' AND status = 'IN'";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        $response['message'] = "Query error: " . mysqli_error($conn);
    } else if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $id = intval($data['id']);
        $jam_keluar = date('Y-m-d H:i:s');
        $jam_masuk = strtotime($data['jam_masuk']);
        $durasi = ceil((strtotime($jam_keluar) - $jam_masuk) / 60); // dalam menit
        $jam = ceil($durasi / 60);
        $biaya = $jam * $biaya_per_jam;

        $update = "UPDATE parkir 
                   SET jam_keluar='$jam_keluar', durasi_menit='$durasi', biaya='$biaya', status='OUT'
                   WHERE id='$id'";
        if (mysqli_query($conn, $update)) {
            $response['success'] = true;
            $response['message'] = "Kendaraan berhasil keluar. Durasi: $durasi menit. Biaya: Rp " . number_format($biaya, 0, ',', '.');
        } else {
            $response['message'] = "Gagal memproses keluar kendaraan: " . mysqli_error($conn);
        }
    } else {
        $response['message'] = "Data tidak ditemukan atau kendaraan sudah keluar.";
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>E-PARKING | Kendaraan Keluar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
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
            max-width: 600px;
            margin: 32px auto;
            padding: 0 24px;
        }

        /* Page Title */
        .page-title {
            margin-bottom: 32px;
            text-align: center;
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

        /* Card */
        .card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .card-body {
            padding: 32px;
        }

        /* Alert */
        .alert {
            padding: 16px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: none;
            align-items: flex-start;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        .alert i {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert.show {
            display: flex;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .alert-info {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            border: 1px solid rgba(37, 99, 235, 0.3);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form */
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

        .form-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            color: var(--text-primary);
            background: var(--bg-primary);
            transition: var(--transition);
            font-family: inherit;
            text-align: center;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
            letter-spacing: normal;
            text-transform: none;
        }

        /* Button Group */
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            width: 100%;
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

        .btn i {
            font-size: 18px;
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
            background: var(--secondary);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-secondary:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary.scanning {
            background: var(--danger);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .btn-secondary.scanning:hover {
            background: #dc2626;
        }

        /* Scanner Overlay */
        .scanner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            animation: fadeIn 0.3s ease;
        }

        .scanner-overlay.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .scanner-container {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 24px;
            max-width: 500px;
            width: 100%;
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .scanner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .scanner-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .scanner-title i {
            color: var(--primary);
            font-size: 20px;
        }

        .close-btn {
            width: 36px;
            height: 36px;
            padding: 0;
            background: var(--bg-tertiary);
            border-radius: 50%;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            color: var(--text-secondary);
        }

        .close-btn:hover {
            background: var(--border);
            transform: rotate(90deg);
        }

        .close-btn i {
            font-size: 20px;
        }

        #reader {
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 3px solid var(--primary);
        }

        .scanner-instruction {
            text-align: center;
            margin-top: 16px;
            color: var(--text-secondary);
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .scanner-instruction i {
            font-size: 18px;
            color: var(--primary);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
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

            .page-title h1 {
                font-size: 24px;
            }

            .card-body {
                padding: 24px;
            }

            .scanner-container {
                padding: 20px;
            }
        }

        /* Loading Animation */
        @keyframes fadeInUp {
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
            animation: fadeInUp 0.5s ease-out;
        }
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
            <h1>Kendaraan Keluar</h1>
            <p>Scan QR code atau input kode parkir untuk checkout</p>
        </div>

        <!-- Main Card -->
        <div class="card">
            <div class="card-body">
                <!-- Alert Message -->
                <div id="alertMessage" class="alert"></div>

                <!-- Form -->
                <form method="POST" id="formCari">
                    <div class="form-group">
                        <label class="form-label" for="barcode">Kode Parkir</label>
                        <div class="input-wrapper">
                            <i class="bi bi-upc-scan input-icon"></i>
                            <input type="text" name="barcode" id="barcode" class="form-input" placeholder="PARK-20251021-ABCD" required autofocus>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" id="btnScan" class="btn btn-secondary">
                            <i class="bi bi-qr-code-scan"></i>
                            <span id="scanBtnText">Scan QR Code</span>
                        </button>
                        <button type="submit" name="cari" class="btn btn-primary">
                            <i class="bi bi-box-arrow-right"></i>
                            Proses Keluar Kendaraan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scanner Overlay -->
    <div id="scannerOverlay" class="scanner-overlay">
        <div class="scanner-container">
            <div class="scanner-header">
                <h3 class="scanner-title">
                    <i class="bi bi-qr-code-scan"></i>
                    Scan QR Code
                </h3>
                <button type="button" class="close-btn" id="closeScanner">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div id="reader"></div>
            <div class="scanner-instruction">
                <i class="bi bi-info-circle"></i>
                Arahkan kamera ke QR Code pada karcis parkir
            </div>
        </div>
    </div>

    <!-- Beep Sound -->
    <audio id="beepSound" src="https://actions.google.com/sounds/v1/cartoon/clang_and_wobble.ogg" preload="auto"></audio>

    <script>
        const btnScan = document.getElementById("btnScan");
        const scanBtnText = document.getElementById("scanBtnText");
        const scannerOverlay = document.getElementById("scannerOverlay");
        const closeScanner = document.getElementById("closeScanner");
        const reader = document.getElementById("reader");
        const barcodeInput = document.getElementById("barcode");
        const beepSound = document.getElementById("beepSound");
        const alertMessage = document.getElementById("alertMessage");
        const formCari = document.getElementById("formCari");
        
        let html5QrCode;
        let isScanning = false;

        // Open scanner
        btnScan.addEventListener("click", () => {
            scannerOverlay.classList.add("active");
            startScanner();
            btnScan.classList.add("scanning");
            scanBtnText.textContent = "Scanning...";
        });

        // Close scanner
        closeScanner.addEventListener("click", () => {
            stopScanner();
            scannerOverlay.classList.remove("active");
            btnScan.classList.remove("scanning");
            scanBtnText.textContent = "Scan QR Code";
        });

        // Close on overlay click
        scannerOverlay.addEventListener("click", (e) => {
            if (e.target === scannerOverlay) {
                stopScanner();
                scannerOverlay.classList.remove("active");
                btnScan.classList.remove("scanning");
                scanBtnText.textContent = "Scan QR Code";
            }
        });

        function startScanner() {
            if (isScanning) return;
            
            html5QrCode = new Html5Qrcode("reader");
            isScanning = true;
            
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                qrCodeMessage => {
                    beepSound.play();
                    barcodeInput.value = qrCodeMessage;
                    
                    stopScanner();
                    scannerOverlay.classList.remove("active");
                    btnScan.classList.remove("scanning");
                    scanBtnText.textContent = "Scan QR Code";

                    prosesKeluar(qrCodeMessage);
                },
                errorMessage => {}
            ).catch(err => {
                showAlert("Gagal mengakses kamera. Pastikan izin kamera diaktifkan.", "error");
                stopScanner();
                scannerOverlay.classList.remove("active");
                btnScan.classList.remove("scanning");
                scanBtnText.textContent = "Scan QR Code";
            });
        }

        function stopScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    isScanning = false;
                }).catch(err => {
                    isScanning = false;
                });
            }
        }

        function prosesKeluar(barcode) {
            hideAlert();
            showAlert("Memproses data kendaraan...", "info");

            fetch(`keluar.php?action=keluar_ajax&barcode=${encodeURIComponent(barcode)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, "success");
                        barcodeInput.value = '';
                    } else {
                        showAlert(data.message, "error");
                    }
                })
                .catch(err => {
                    showAlert("Terjadi kesalahan pada sistem. Silakan coba lagi.", "error");
                });
        }

        function showAlert(message, type) {
            const icons = {
                success: '<i class="bi bi-check-circle-fill"></i>',
                error: '<i class="bi bi-exclamation-circle-fill"></i>',
                info: '<i class="bi bi-info-circle-fill"></i>'
            };

            alertMessage.className = `alert alert-${type} show`;
            alertMessage.innerHTML = icons[type] + '<span>' + message + '</span>';
        }

        function hideAlert() {
            alertMessage.classList.remove('show');
        }

        // Form submit handler
        formCari.addEventListener('submit', (e) => {
            e.preventDefault();
            const barcode = barcodeInput.value.trim();
            if (barcode) {
                prosesKeluar(barcode);
            }
        });
    </script>
</body>
</html>