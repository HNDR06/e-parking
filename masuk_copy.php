<?php
// Start output buffering FIRST
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/db.php';

// Debug: Check if session is working
if (isset($_GET['debug'])) {
    echo '<pre>';
    echo "Session ID: " . session_id() . "\n";
    echo "Show Gate: " . (isset($_SESSION['show_gate']) ? 'YES' : 'NO') . "\n";
    echo "Last Ticket: " . (isset($_SESSION['last_ticket']) ? 'YES' : 'NO') . "\n";
    if (isset($_SESSION['last_ticket'])) {
        print_r($_SESSION['last_ticket']);
    }
    echo "POST data: ";
    print_r($_POST);
    echo "GET data: ";
    print_r($_GET);
    echo '</pre>';
    exit;
}

// Function to generate unique ticket ID
function generateTicketId() {
    return 'TKT' . time() . rand(100, 999);
}

// Function to generate random plate number
function generatePlateNumber() {
    $regions = ['B', 'D', 'E', 'F', 'T', 'A', 'L', 'N', 'R', 'S', 'W', 'Z'];
    $region = $regions[array_rand($regions)];
    $number = rand(1000, 9999);
    $letters = '';
    for ($i = 0; $i < 3; $i++) {
        $letters .= chr(rand(65, 90));
    }
    return $region . ' ' . $number . ' ' . $letters;
}

// Function to generate random vehicle type
function generateVehicleType() {
    $types = ['Motor', 'Mobil'];
    return $types[array_rand($types)];
}

// Function to get available slot
function getAvailableSlot($conn, $jenis) {
    // Return null since we're not using slots
    return null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    // Debug log
    error_log("Form submitted - generating ticket");
    
    $ticketId = generateTicketId();
    $plateNumber = generatePlateNumber();
    $vehicleType = generateVehicleType();
    $entryTime = date('Y-m-d H:i:s');
    $slot = getAvailableSlot($conn, $vehicleType);
    
    error_log("Generated: Ticket=$ticketId, Plate=$plateNumber, Type=$vehicleType, Slot=$slot");
    
    // Check if slot column exists in database
    $checkSlot = $conn->query("SHOW COLUMNS FROM parkir LIKE 'slot'");
    $hasSlotColumn = ($checkSlot->num_rows > 0);
    
    // Save to database
    if ($hasSlotColumn) {
        $stmt = $conn->prepare("INSERT INTO parkir (no_plat, jenis, jam_masuk, barcode_id, slot, status, petugas) VALUES (?, ?, ?, ?, ?, 'IN', 'System')");
        $stmt->bind_param("sssss", $plateNumber, $vehicleType, $entryTime, $ticketId, $slot);
    } else {
        $stmt = $conn->prepare("INSERT INTO parkir (no_plat, jenis, jam_masuk, barcode_id, status, petugas) VALUES (?, ?, ?, ?, 'IN', 'System')");
        $stmt->bind_param("ssss", $plateNumber, $vehicleType, $entryTime, $ticketId);
    }
    
    if ($stmt->execute()) {
        $_SESSION['last_ticket'] = [
            'ticketId' => $ticketId,
            'plateNumber' => $plateNumber,
            'vehicleType' => $vehicleType,
            'slot' => $slot,
            'entryTime' => $entryTime,
            'source' => 'manual'
        ];
        $_SESSION['show_gate'] = true;
        error_log("SUCCESS: Ticket saved, session set");
    } else {
        $_SESSION['error'] = 'Gagal menyimpan data: ' . $stmt->error;
        error_log("ERROR: Database insert failed - " . $stmt->error);
    }
    
    // Redirect to avoid form resubmission
    header('Location: masuk_copy.php?v=' . time());
    exit;
}

// Handle RFID from MQTT
if (isset($_GET['rfid'])) {
    $rfidData = $_GET['rfid'];
    $ticketId = generateTicketId();
    $plateNumber = generatePlateNumber();
    $vehicleType = generateVehicleType();
    $entryTime = date('Y-m-d H:i:s');
    $slot = getAvailableSlot($conn, $vehicleType);
    
    // Check if slot column exists in database
    $checkSlot = $conn->query("SHOW COLUMNS FROM parkir LIKE 'slot'");
    $hasSlotColumn = ($checkSlot->num_rows > 0);
    
    // Save to database
    if ($hasSlotColumn) {
        $stmt = $conn->prepare("INSERT INTO parkir (no_plat, jenis, jam_masuk, barcode_id, slot, status, petugas, referee_no) VALUES (?, ?, ?, ?, ?, 'IN', 'RFID', ?)");
        $stmt->bind_param("ssssss", $plateNumber, $vehicleType, $entryTime, $ticketId, $slot, $rfidData);
    } else {
        $stmt = $conn->prepare("INSERT INTO parkir (no_plat, jenis, jam_masuk, barcode_id, status, petugas, referee_no) VALUES (?, ?, ?, ?, 'IN', 'RFID', ?)");
        $stmt->bind_param("sssss", $plateNumber, $vehicleType, $entryTime, $ticketId, $rfidData);
    }
    
    if ($stmt->execute()) {
        $_SESSION['last_ticket'] = [
            'ticketId' => $ticketId,
            'plateNumber' => $plateNumber,
            'vehicleType' => $vehicleType,
            'slot' => $slot,
            'entryTime' => $entryTime,
            'source' => 'rfid',
            'rfidData' => $rfidData
        ];
        $_SESSION['show_gate'] = true;
        
        if (isset($_GET['device_status'])) {
            $_SESSION['device_status'] = $_GET['device_status'];
        }
    } else {
        $_SESSION['error'] = 'Gagal menyimpan data: ' . $stmt->error;
    }
    
    header('Location: masuk_copy.php?v=' . time());
    exit;
}

// Handle reset
if (isset($_GET['reset'])) {
    unset($_SESSION['show_gate']);
    unset($_SESSION['last_ticket']);
    header('Location: masuk_copy.php?v=' . time());
    exit;
}

$showGate = $_SESSION['show_gate'] ?? false;
$lastTicket = $_SESSION['last_ticket'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
$savedDeviceStatus = $_SESSION['device_status'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-PARKING | Kendaraan Masuk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style/style_masuk.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js"></script>
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
                <a href="masuk_copy.php" class="active">
                    <i class="bi bi-car-front-fill"></i>
                    Kendaraan Masuk
                </a>
                <a href="dashboard.php">
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

    <!-- Status Bar -->
    <div class="status-bar">
        <div class="status-item mqtt-status">
            <div class="status-indicator" id="mqttIndicator"></div>
            <span class="status-label">MQTT:</span>
            <span class="status-text" id="mqttStatus">Connecting...</span>
        </div>
        
        <div class="status-item device-status" id="deviceStatusContainer">
            <div class="status-indicator" id="deviceIndicator"></div>
            <span class="status-label">Device:</span>
            <span class="status-text" id="deviceText">Not Connected</span>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <?php if (!$showGate): ?>
        <!-- Page Title -->
        <div class="page-title">
            <div class="page-title-text">
                <h1>Kendaraan Masuk</h1>
                <p>Sistem otomatis untuk pendaftaran kendaraan masuk area parkir</p>
            </div>
        </div>

        <!-- Entry Card -->
        <div class="entry-section">
            <div class="entry-card">
                <div class="entry-icon-wrapper">
                    <div class="entry-icon">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                    <div class="icon-pulse"></div>
                </div>
                
                <h2 class="entry-title">Selamat Datang</h2>
                <p class="entry-subtitle">Tekan tombol untuk mencetak tiket parkir Anda</p>
                
                <form method="POST" class="entry-form">
                    <button type="submit" name="generate" class="btn-entry">
                        <span class="btn-icon">
                            <i class="bi bi-printer-fill"></i>
                        </span>
                        <span class="btn-text">Cetak Tiket Parkir</span>
                        <span class="btn-shine"></span>
                    </button>
                </form>

                <div class="info-box">
                    <i class="bi bi-info-circle-fill"></i>
                    <div class="info-content">
                        <strong>Informasi:</strong>
                        <span>Tiket akan dicetak otomatis dan gerbang akan terbuka</span>
                    </div>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <h3>QR Code</h3>
                    <p>Setiap tiket dilengkapi QR code unik</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <h3>Proses Cepat</h3>
                    <p>Pencetakan tiket hanya dalam hitungan detik</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3>Aman Terjamin</h3>
                    <p>Data kendaraan tersimpan dengan aman</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($showGate && $lastTicket): ?>
    <!-- Gate Open Overlay -->
    <div class="gate-overlay" id="gateOverlay">
        <!-- Animated Background -->
        <div class="gate-bg">
            <div class="gate-bg-pattern"></div>
        </div>

        <!-- Gate Doors -->
        <div class="gate-door gate-left">
            <div class="door-panel"></div>
            <div class="door-stripe"></div>
        </div>
        
        <div class="gate-door gate-right">
            <div class="door-panel"></div>
            <div class="door-stripe"></div>
        </div>
        
        <!-- Particles Effect -->
        <div class="particles" id="particles"></div>
        
        <!-- Main Content -->
        <div class="gate-content">
            <!-- Success Icon -->
            <div class="success-badge">
                <div class="success-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="success-ring"></div>
                <div class="success-ring-2"></div>
            </div>
            
            <!-- Title -->
            <h1 class="gate-title">GERBANG TERBUKA</h1>
            <p class="gate-subtitle">Silakan Masuk ke Area Parkir</p>
            
            <!-- Welcome Message -->
            <div class="gate-welcome-message">
                <i class="bi bi-check-circle"></i>
                <p>Tiket Anda telah dicetak.<br>Simpan dengan baik untuk keluar parkir.</p>
            </div>
            
            <!-- Timer -->
            <div class="timer-box">
                <i class="bi bi-hourglass-split"></i>
                <span>Halaman akan direset dalam <strong id="countdown">8</strong> detik</span>
            </div>
            
            <!-- Action Button -->
            <button onclick="closeGate()" class="btn-close-gate">
                <i class="bi bi-arrow-clockwise"></i>
                <span>Kendaraan Berikutnya</span>
            </button>
        </div>
    </div>

    <script>
        // Countdown & Auto Reset
        let countdown = 8;
        const countdownEl = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            if (countdownEl) countdownEl.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '?reset=1';
            }
        }, 1000);
        
        // Create particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 3 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 2) + 's';
                particlesContainer.appendChild(particle);
            }
        }
        createParticles();
        
        function closeGate() {
            clearInterval(timer);
            window.location.href = '?reset=1';
        }
        
        <?php if ($lastTicket['source'] === 'manual'): ?>
        // Open print page for manual generation
        setTimeout(() => {
            const printWindow = window.open('cetak_karcis.php?ticket=<?php echo $lastTicket['ticketId']; ?>', '_blank', 'width=400,height=600');
        }, 500);
        <?php endif; ?>
    </script>
    <?php endif; ?>

    <!-- MQTT Script -->
    <script>
        const MQTT_BROKER = "868dc6a1dc894a84a5793a95746a9881.s1.eu.hivemq.cloud";
        const MQTT_PORT = 8884;
        const MQTT_USERNAME = "hivemq.webclient.1751385942895";
        const MQTT_PASSWORD = "R07c6iA#dG>W3lNj?sQ:";
        const MQTT_TOPIC_MODE = "KyoumaProject/mode";
        const MQTT_TOPIC_RFID = "KyoumaProject/rfid";
        
        const clientId = "parking_web_" + Math.random().toString(16).substr(2, 8);
        const client = new Paho.MQTT.Client(MQTT_BROKER, MQTT_PORT, "/mqtt", clientId);
        
        const connectOptions = {
            onSuccess: onConnect,
            onFailure: onFailure,
            userName: MQTT_USERNAME,
            password: MQTT_PASSWORD,
            useSSL: true,
            timeout: 10,
            keepAliveInterval: 30,
            cleanSession: true
        };
        
        client.onConnectionLost = onConnectionLost;
        client.onMessageArrived = onMessageArrived;
        
        function updateMQTTStatus(status, connected = false, connecting = false) {
            const indicator = document.getElementById('mqttIndicator');
            const statusText = document.getElementById('mqttStatus');
            
            indicator.className = 'status-indicator';
            if (connected) indicator.classList.add('connected');
            else if (connecting) indicator.classList.add('connecting');
            
            statusText.textContent = status;
        }

        function updateDeviceStatus(mode) {
            const deviceContainer = document.getElementById('deviceStatusContainer');
            const deviceIndicator = document.getElementById('deviceIndicator');
            const deviceText = document.getElementById('deviceText');
            
            const modeLower = mode.toLowerCase().trim();
            
            if (modeLower === "in") {
                deviceContainer.classList.add('device-connected');
                deviceIndicator.classList.add('connected');
                deviceText.textContent = "Connected (IN Mode)";
                localStorage.setItem('device_mode', 'in');
                localStorage.setItem('device_connected', 'true');
            } else {
                deviceContainer.classList.remove('device-connected');
                deviceIndicator.classList.remove('connected');
                deviceText.textContent = "Not Connected";
                localStorage.setItem('device_mode', mode);
                localStorage.setItem('device_connected', 'false');
            }
        }
        
        function connectMQTT() {
            updateMQTTStatus('Connecting...', false, true);
            try {
                client.connect(connectOptions);
            } catch (error) {
                updateMQTTStatus('Failed', false, false);
            }
        }
        
        function onConnect() {
            updateMQTTStatus('Connected', true, false);
            client.subscribe(MQTT_TOPIC_MODE, {qos: 1});
            client.subscribe(MQTT_TOPIC_RFID, {qos: 1});
        }
        
        function onFailure(message) {
            updateMQTTStatus('Failed', false, false);
            setTimeout(connectMQTT, 5000);
        }
        
        function onConnectionLost(responseObject) {
            if (responseObject.errorCode !== 0) {
                updateMQTTStatus('Disconnected', false, false);
                setTimeout(connectMQTT, 5000);
            }
        }
        
        function onMessageArrived(message) {
            const topic = message.destinationName;
            const payload = message.payloadString;
            
            if (topic === MQTT_TOPIC_MODE) {
                updateDeviceStatus(payload);
            } else if (topic === MQTT_TOPIC_RFID) {
                const deviceText = document.getElementById('deviceText').textContent;
                if (deviceText.includes("Connected")) {
                    const currentMode = localStorage.getItem('device_mode') || 'in';
                    window.location.href = '?rfid=' + encodeURIComponent(payload) + '&device_status=' + encodeURIComponent(currentMode);
                }
            }
        }
        
        function restoreDeviceStatus() {
            const savedConnected = localStorage.getItem('device_connected');
            const savedMode = localStorage.getItem('device_mode');
            
            <?php if ($savedDeviceStatus): ?>
            updateDeviceStatus("<?php echo $savedDeviceStatus; ?>");
            <?php else: ?>
            if (savedConnected === 'true' && savedMode === 'in') {
                updateDeviceStatus('in');
            } else if (savedMode) {
                updateDeviceStatus(savedMode);
            }
            <?php endif; ?>
        }
        
        window.addEventListener('DOMContentLoaded', function() {
            restoreDeviceStatus();
            connectMQTT();
        });
    </script>
</body>
</html>