<?php
session_start();

// Include database controller
require_once 'controllerInput.php';

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
        $letters .= chr(rand(65, 90)); // A-Z
    }
    return $region . ' ' . $number . ' ' . $letters;
}

// Function to generate random vehicle type
function generateVehicleType() {
    $types = ['Motor', 'Mobil'];
    return $types[array_rand($types)];
}

// Check if this is a print request
if (isset($_GET['print']) && isset($_GET['ticket'])) {
    error_log("=== PRINT REQUEST ===");
    error_log("Ticket ID requested: " . $_GET['ticket']);
    
    $ticketId = $_GET['ticket'];
    
    if (isset($_SESSION['tickets'][$ticketId])) {
        error_log("Ticket found in session, generating print page");
        $ticketData = $_SESSION['tickets'][$ticketId];
        $qrData = json_encode($ticketData);
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData);
        
        error_log("QR Code URL: " . $qrCodeUrl);
        
        // Display print page
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Print Karcis - <?php echo $ticketData['ticketId']; ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: white; }
                .ticket-card { max-width: 400px; margin: 0 auto; border: 4px dashed #333; border-radius: 10px; padding: 30px; }
                .ticket-header { text-align: center; margin-bottom: 30px; }
                .ticket-header h1 { color: #667eea; font-size: 36px; margin-bottom: 10px; }
                .ticket-header p { color: #666; }
                .qr-code { text-align: center; margin: 30px 0; }
                .qr-code img { width: 300px; height: 300px; border: 2px solid #ddd; border-radius: 10px; padding: 10px; }
                .ticket-details { background: #f8f9fa; border-radius: 10px; padding: 20px; }
                .detail-row { padding: 10px 0; border-bottom: 1px solid #dee2e6; }
                .detail-row:last-child { border-bottom: none; }
                .detail-label { color: #666; font-size: 12px; margin-bottom: 5px; }
                .detail-value { color: #333; font-size: 16px; font-weight: bold; font-family: monospace; }
                .ticket-footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
                @media print { body { padding: 0; } }
            </style>
            <script>
                window.onload = function() {
                    setTimeout(function() { window.print(); }, 500);
                }
            </script>
        </head>
        <body>
            <div class="ticket-card">
                <div class="ticket-header">
                    <h1>KARCIS PARKIR</h1>
                    <p>Simpan karcis ini sampai keluar</p>
                </div>
                <div class="qr-code">
                    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">
                </div>
                <div class="ticket-details">
                    <div class="detail-row">
                        <div class="detail-label">Nomor Tiket</div>
                        <div class="detail-value"><?php echo $ticketData['ticketId']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Kendaraan</div>
                        <div class="detail-value"><?php echo strtoupper($ticketData['vehicleType']); ?> - <?php echo $ticketData['plateNumber']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Waktu Masuk</div>
                        <div class="detail-value"><?php echo $ticketData['entryTimeDisplay']; ?></div>
                    </div>
                </div>
                <div class="ticket-footer">
                    <p>Scan QR code ini saat keluar parkir</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        error_log("Print page rendered successfully");
        exit;
    } else {
        error_log("❌ ERROR: Ticket not found in session");
        error_log("Available tickets: " . json_encode(array_keys($_SESSION['tickets'] ?? [])));
        echo "Error: Ticket not found";
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    error_log("=== FORM SUBMITTED ===");
    
    $ticketId = generateTicketId();
    $plateNumber = generatePlateNumber();
    $vehicleType = generateVehicleType();
    $entryTime = date('Y-m-d H:i:s');
    $entryTimeDisplay = date('d/m/Y H:i:s');
    
    // Get photo from POST if available
    $photoFilename = null;
    if (isset($_POST['photo']) && !empty($_POST['photo'])) {
        error_log("Photo data received, processing...");
        
        // Save photo
        $uploadDir = __DIR__ . '/uploads/vehicles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $imageData = $_POST['photo'];
        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageDecoded = base64_decode($imageData);
        
        if ($imageDecoded !== false) {
            $photoFilename = $ticketId . '_' . time() . '.jpg';
            $filepath = $uploadDir . $photoFilename;
            $saved = file_put_contents($filepath, $imageDecoded);
            
            if ($saved) {
                $photoFilename = 'uploads/vehicles/' . $photoFilename;
                error_log("Photo saved: " . $photoFilename);
            } else {
                error_log("Failed to save photo file");
                $photoFilename = null;
            }
        }
    } else {
        error_log("No photo data in POST");
    }
    
    // Create ticket data array
    $ticketData = [
        'ticketId' => $ticketId,
        'plateNumber' => $plateNumber,
        'vehicleType' => $vehicleType,
        'entryTime' => $entryTime,
        'entryTimeDisplay' => $entryTimeDisplay,
        'status' => 'active',
        'source' => 'manual',
        'photo' => $photoFilename
    ];
    
    error_log("Ticket data prepared: " . json_encode($ticketData));
    
    // Save to database using controller
    $result = saveTicketToDatabase($ticketData);
    if ($result['success']) {
        error_log("✅ Manual ticket saved: " . $ticketId);
    } else {
        error_log("❌ Failed to save manual ticket: " . $result['message']);
    }
    
    // Store in session
    if (!isset($_SESSION['tickets'])) {
        $_SESSION['tickets'] = [];
    }
    $_SESSION['tickets'][$ticketId] = $ticketData;
    $_SESSION['last_ticket'] = $ticketId;
    $_SESSION['show_gate'] = true;
    
    error_log("Session set - redirecting to gate page");
    
    // Redirect to avoid form resubmission
    header('Location: ' . $_SERVER['PHP_SELF'] . '?submitted=1');
    exit;
}

// Handle RFID from MQTT (via GET parameter)
if (isset($_GET['rfid'])) {
    $rfidData = $_GET['rfid'];
    $ticketId = generateTicketId();
    $plateNumber = generatePlateNumber();
    $vehicleType = generateVehicleType();
    $entryTime = date('Y-m-d H:i:s');
    $entryTimeDisplay = date('d/m/Y H:i:s');
    
    // Get photo from GET if available
    $photoFilename = null;
    if (isset($_GET['photo']) && !empty($_GET['photo'])) {
        $photoFilename = $_GET['photo'];
    }
    
    // Create ticket data array
    $ticketData = [
        'ticketId' => $ticketId,
        'plateNumber' => $plateNumber,
        'vehicleType' => $vehicleType,
        'entryTime' => $entryTime,
        'entryTimeDisplay' => $entryTimeDisplay,
        'status' => 'active',
        'source' => 'rfid',
        'rfidData' => $rfidData,
        'photo' => $photoFilename
    ];
    
    // Save to database using controller
    $result = saveTicketToDatabase($ticketData);
    if ($result['success']) {
        error_log("✅ RFID ticket saved: " . $ticketId . " (RFID: " . $rfidData . ")");
    } else {
        error_log("❌ Failed to save RFID ticket: " . $result['message']);
    }
    
    // Store in session
    if (!isset($_SESSION['tickets'])) {
        $_SESSION['tickets'] = [];
    }
    $_SESSION['tickets'][$ticketId] = $ticketData;
    $_SESSION['last_ticket'] = $ticketId;
    $_SESSION['show_gate'] = true;
    
    // Save device status to maintain connection state
    if (isset($_GET['device_status'])) {
        $_SESSION['device_status'] = $_GET['device_status'];
    }
}

// Handle reset
if (isset($_GET['reset'])) {
    unset($_SESSION['show_gate']);
    unset($_SESSION['last_ticket']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check if just submitted
if (isset($_GET['submitted'])) {
    error_log("Checking submitted flag - show_gate: " . ($_SESSION['show_gate'] ?? 'not set'));
}

$showGate = $_SESSION['show_gate'] ?? false;
$lastTicketId = $_SESSION['last_ticket'] ?? null;
$savedDeviceStatus = $_SESSION['device_status'] ?? null;

error_log("showGate: " . ($showGate ? 'true' : 'false'));
error_log("lastTicketId: " . ($lastTicketId ?? 'null'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Parking System - QR Code Generator</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: white; border-radius: 10px; padding: 30px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .header-content { display: flex; align-items: center; gap: 20px; }
        .header-icon { background: #667eea; padding: 15px; border-radius: 10px; color: white; font-size: 32px; }
        .header-text h1 { color: #333; font-size: 32px; margin-bottom: 5px; }
        .header-text p { color: #666; font-size: 16px; }
        .card { background: white; border-radius: 10px; padding: 40px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .card h2 { color: #333; font-size: 24px; margin-bottom: 30px; }
        
        /* Camera Section */
        /* Camera hidden */
        #video, #canvas { display: none !important; }
        
        .info-box { background: #f0f4ff; border: 2px solid #667eea; border-radius: 10px; padding: 20px; margin-bottom: 25px; }
        .info-box p { color: #667eea; font-weight: 600; margin-bottom: 10px; }
        .info-box ul { color: #666; margin: 10px 0 0 20px; }
        .btn { width: 100%; padding: 15px; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-2px); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        
        /* Gate styles */
        .gate-container { background: white; border-radius: 10px; padding: 40px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .ticket-info { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; text-align: left; }
        .ticket-info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
        .ticket-info-row:last-child { border-bottom: none; }
        .ticket-info-label { color: #666; font-weight: 600; }
        .ticket-info-value { color: #333; font-family: monospace; }
        .action-buttons { display: grid; grid-template-columns: 1fr; gap: 15px; margin-top: 20px; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        /* Status indicators */
        .mqtt-status, .device-status { position: fixed; right: 20px; background: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: flex; align-items: center; gap: 10px; z-index: 1000; }
        .mqtt-status { top: 20px; }
        .device-status { top: 80px; border: 2px solid #dc3545; }
        .device-status.device-connected { border-color: #28a745; }
        .mqtt-indicator, .device-indicator { width: 12px; height: 12px; border-radius: 50%; background: #dc3545; animation: pulse 2s infinite; }
        .mqtt-indicator.connected, .device-indicator.connected { background: #28a745; animation: none; }
        .mqtt-indicator.connecting { background: #ffc107; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .mqtt-text, .device-text { font-size: 14px; color: #333; font-weight: 600; }
        .device-icon { font-size: 18px; }
        
        .photo-preview-container { margin: 20px 0; text-align: center; }
        .photo-preview-container img { max-width: 200px; border-radius: 10px; border: 2px solid #667eea; }
    </style>
</head>
<body>
    <!-- Status Indicators -->
    <div class="mqtt-status">
        <div class="mqtt-indicator" id="mqttIndicator"></div>
        <span class="mqtt-text" id="mqttStatus">Connecting to MQTT...</span>
    </div>
    <div class="device-status" id="deviceStatus">
        <span class="device-icon">📟</span>
        <div class="device-indicator" id="deviceIndicator"></div>
        <span class="device-text" id="deviceText">Device Not Connected</span>
    </div>

    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="header-icon">🅿️</div>
                <div class="header-text">
                    <h1>E-Parking System</h1>
                    <p>Pelita Bangsa University</p>
                </div>
            </div>
        </div>

        <?php if (!$showGate): ?>
            <div class="card">
                <h2>Welcome to Pelita Bangsa University</h2>
                
                <form method="POST" id="ticketForm">
                    <!-- Hidden Camera Section -->
                    <input type="hidden" name="photo" id="photoData">
                    <video id="video" autoplay playsinline style="display: none;"></video>
                    <canvas id="canvas" style="display: none;"></canvas>

                    <div class="info-box">
                        <p>ℹ️ Information</p>
                        <p style="color: #333; margin: 0;">Click button below to print ticket</p>
                        <p style="color: #666; font-size: 14px; margin-top: 10px;" id="cameraStatus">📷 Camera initializing...</p>
                    </div>

                    <button type="submit" name="generate" id="submitBtn" class="btn btn-primary">
                        🎫 Print Ticket
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Gate Open Message -->
            <div class="gate-container">
                <div style="background: #28a745; color: white; padding: 60px 40px; border-radius: 10px; margin-bottom: 30px;">
                    <h1 style="font-size: 48px; margin-bottom: 20px; text-align: center;">🚪 GERBANG TERBUKA</h1>
                    <p style="font-size: 24px; text-align: center; margin: 0;">SILAKAN MASUK</p>
                </div>
                
                <?php 
                if ($lastTicketId && isset($_SESSION['tickets'][$lastTicketId])) {
                    $currentTicket = $_SESSION['tickets'][$lastTicketId];
                    $isRFID = isset($currentTicket['source']) && $currentTicket['source'] === 'rfid';
                    
                    error_log("Displaying gate for ticket: " . $lastTicketId);
                ?>
                
                <div class="ticket-info">
                    <?php if ($isRFID): ?>
                        <div class="ticket-info-row">
                            <span class="ticket-info-label">RFID:</span>
                            <span class="ticket-info-value"><?php echo htmlspecialchars($currentTicket['rfidData']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="ticket-info-row">
                        <span class="ticket-info-label">Nomor Tiket:</span>
                        <span class="ticket-info-value"><?php echo htmlspecialchars($currentTicket['ticketId']); ?></span>
                    </div>
                    <div class="ticket-info-row">
                        <span class="ticket-info-label">Kendaraan:</span>
                        <span class="ticket-info-value"><?php echo strtoupper(htmlspecialchars($currentTicket['vehicleType'])); ?> - <?php echo htmlspecialchars($currentTicket['plateNumber']); ?></span>
                    </div>
                    <div class="ticket-info-row">
                        <span class="ticket-info-label">Waktu Masuk:</span>
                        <span class="ticket-info-value"><?php echo htmlspecialchars($currentTicket['entryTimeDisplay']); ?></span>
                    </div>
                    <?php if (isset($currentTicket['photo']) && $currentTicket['photo']): ?>
                        <div class="photo-preview-container">
                            <p style="color: #666; margin-bottom: 10px;">Foto Kendaraan:</p>
                            <img src="<?php echo htmlspecialchars($currentTicket['photo']); ?>" alt="Vehicle Photo">
                        </div>
                    <?php endif; ?>
                </div>

                <p style="color: #666; margin-top: 20px; font-size: 14px; text-align: center;">
                    <?php if ($isRFID): ?>
                        RFID terdeteksi.<br>
                    <?php else: ?>
                        Karcis dibuka di tab baru untuk dicetak.<br>
                    <?php endif; ?>
                    Halaman ini akan otomatis reset dalam 5 detik...
                </p>

                <div class="action-buttons">
                    <a href="?reset=1" class="btn btn-secondary" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        🔄 Kendaraan Baru
                    </a>
                </div>
                
                <?php } else { ?>
                    <p style="color: #dc3545; text-align: center;">Error: Ticket data not found</p>
                    <div class="action-buttons">
                        <a href="?reset=1" class="btn btn-secondary" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                            🔄 Back to Home
                        </a>
                    </div>
                <?php } ?>
            </div>

            <script>
                <?php 
                if ($lastTicketId && isset($_SESSION['tickets'][$lastTicketId])) {
                    $currentTicket = $_SESSION['tickets'][$lastTicketId];
                    $isRFID = isset($currentTicket['source']) && $currentTicket['source'] === 'rfid';
                    
                    if (!$isRFID): 
                ?>
                // Open print window
                console.log("=== OPENING PRINT WINDOW ===");
                console.log("Ticket ID: <?php echo $lastTicketId; ?>");
                console.log("URL: " + window.location.origin + window.location.pathname + "?print=1&ticket=<?php echo $lastTicketId; ?>");
                
                setTimeout(function() {
                    var printUrl = '?print=1&ticket=<?php echo $lastTicketId; ?>';
                    console.log("Opening: " + printUrl);
                    
                    var printWindow = window.open(printUrl, '_blank', 'width=800,height=600');
                    
                    if (printWindow) {
                        console.log("✅ Print window opened successfully");
                    } else {
                        console.error("❌ Failed to open print window - popup blocked?");
                        alert("Pop-up blocked! Please allow pop-ups for this site to print tickets.");
                    }
                    
                    // Keep focus on main window
                    setTimeout(function() { window.focus(); }, 10);
                    setTimeout(function() { window.focus(); }, 100);
                    setTimeout(function() { window.focus(); }, 300);
                }, 100);
                <?php 
                    else:
                        error_log("RFID entry - skipping print window");
                    endif;
                } else {
                    error_log("❌ Cannot open print window - no ticket data");
                }
                ?>
                
                // Auto redirect after 5 seconds
                setTimeout(function() {
                    console.log("Auto-redirecting to reset...");
                    window.location.href = '?reset=1';
                }, 5000);
            </script>
        <?php endif; ?>
    </div>

    <script>
        // Hidden background camera functionality
        let stream = null;
        let cameraReady = false;
        
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const submitBtn = document.getElementById('submitBtn');
        const photoData = document.getElementById('photoData');
        const cameraStatus = document.getElementById('cameraStatus');
        const ticketForm = document.getElementById('ticketForm');
        
        // Initialize hidden camera in background
        async function initCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        facingMode: 'environment'
                    } 
                });
                video.srcObject = stream;
                
                // Wait for video to be ready
                video.onloadedmetadata = () => {
                    cameraReady = true;
                    cameraStatus.textContent = '✅ Camera ready (background)';
                    cameraStatus.style.color = '#28a745';
                    console.log('Camera initialized in background');
                };
            } catch (err) {
                console.error('Camera error:', err);
                cameraStatus.textContent = '⚠️ Camera not available (will continue without photo)';
                cameraStatus.style.color = '#ffc107';
                cameraReady = false;
            }
        }
        
        // Capture photo on form submit (background)
        ticketForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default submit
            
            if (cameraReady && stream && video.videoWidth > 0) {
                // Capture photo in background
                console.log('Capturing photo in background...');
                submitBtn.disabled = true;
                submitBtn.textContent = '📸 Capturing...';
                
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0);
                
                const imageData = canvas.toDataURL('image/jpeg', 0.8);
                photoData.value = imageData;
                
                console.log('Photo captured! Size:', imageData.length, 'bytes');
                
                // Stop camera stream
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                
                submitBtn.textContent = '📤 Submitting...';
                
                // Submit form after short delay
                setTimeout(() => {
                    console.log('Submitting form...');
                    ticketForm.submit();
                }, 300);
            } else {
                // No camera, submit without photo
                console.log('No camera available, submitting without photo...');
                submitBtn.disabled = true;
                submitBtn.textContent = '📤 Submitting...';
                setTimeout(() => {
                    ticketForm.submit();
                }, 300);
            }
        });
        
        // Initialize camera in background on load
        window.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing background camera...');
            initCamera();
        });

        // MQTT Configuration
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
            indicator.className = 'mqtt-indicator';
            if (connected) indicator.classList.add('connected');
            else if (connecting) indicator.classList.add('connecting');
            statusText.textContent = status;
        }

        function updateDeviceStatus(mode) {
            const deviceStatus = document.getElementById('deviceStatus');
            const deviceIndicator = document.getElementById('deviceIndicator');
            const deviceText = document.getElementById('deviceText');
            const modeLower = mode.toLowerCase().trim();
            
            if (modeLower === "in") {
                deviceStatus.classList.add('device-connected');
                deviceIndicator.classList.add('connected');
                deviceText.textContent = "Device Connected";
                localStorage.setItem('device_mode', 'in');
                localStorage.setItem('device_connected', 'true');
            } else {
                deviceStatus.classList.remove('device-connected');
                deviceIndicator.classList.remove('connected');
                deviceText.textContent = "Device Not Connected";
                localStorage.setItem('device_mode', mode);
                localStorage.setItem('device_connected', 'false');
            }
        }
        
        function restoreDeviceStatus() {
            <?php if ($savedDeviceStatus): ?>
                updateDeviceStatus("<?php echo $savedDeviceStatus; ?>");
            <?php else: ?>
                const savedConnected = localStorage.getItem('device_connected');
                const savedMode = localStorage.getItem('device_mode');
                if (savedConnected === 'true' && savedMode === 'in') {
                    updateDeviceStatus('in');
                } else if (savedMode) {
                    updateDeviceStatus(savedMode);
                }
            <?php endif; ?>
        }
        
        function connectMQTT() {
            updateMQTTStatus('Connecting to MQTT...', false, true);
            try {
                client.connect(connectOptions);
            } catch (error) {
                console.error("MQTT Connection Error:", error);
                updateMQTTStatus('Connection Failed', false, false);
            }
        }
        
        function onConnect() {
            console.log("Connected to MQTT Broker");
            updateMQTTStatus('MQTT Connected', true, false);
            client.subscribe(MQTT_TOPIC_MODE, {qos: 1});
            client.subscribe(MQTT_TOPIC_RFID, {qos: 1});
        }
        
        function onFailure(message) {
            console.error("Connection Failed:", message.errorMessage);
            updateMQTTStatus('Connection Failed', false, false);
            setTimeout(connectMQTT, 5000);
        }
        
        function onConnectionLost(responseObject) {
            if (responseObject.errorCode !== 0) {
                console.error("Connection Lost:", responseObject.errorMessage);
                updateMQTTStatus('Connection Lost', false, false);
                setTimeout(connectMQTT, 5000);
            }
        }
        
        function onMessageArrived(message) {
            const topic = message.destinationName;
            const payload = message.payloadString;
            
            if (topic === MQTT_TOPIC_MODE) {
                handleModeMessage(payload);
            } else if (topic === MQTT_TOPIC_RFID) {
                handleRFIDMessage(payload);
            }
        }
        
        function handleModeMessage(payload) {
            console.log("Mode message:", payload);
            updateDeviceStatus(payload);
        }
        
        async function handleRFIDMessage(payload) {
            console.log("RFID message:", payload);
            const deviceText = document.getElementById('deviceText').textContent;
            
            if (deviceText === "Device Connected") {
                console.log("✅ RFID Detected: " + payload + " - Capturing photo...");
                
                // Capture photo automatically for RFID
                if (!photoTaken && stream) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0);
                    const imageData = canvas.toDataURL('image/jpeg', 0.8);
                    
                    // Upload photo first
                    try {
                        const formData = new FormData();
                        formData.append('image', imageData);
                        formData.append('ticket_id', 'TKT' + Date.now());
                        
                        const response = await fetch('uploadImage.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        let photoPath = '';
                        if (result.success) {
                            photoPath = result.filepath;
                            console.log("✅ Photo uploaded:", photoPath);
                        }
                        
                        // Get current device mode
                        const currentMode = localStorage.getItem('device_mode') || 'in';
                        
                        // Redirect with RFID and photo
                        window.location.href = '?rfid=' + encodeURIComponent(payload) + 
                                             '&device_status=' + encodeURIComponent(currentMode) +
                                             '&photo=' + encodeURIComponent(photoPath);
                    } catch (error) {
                        console.error("Photo upload error:", error);
                        // Continue without photo
                        const currentMode = localStorage.getItem('device_mode') || 'in';
                        window.location.href = '?rfid=' + encodeURIComponent(payload) + 
                                             '&device_status=' + encodeURIComponent(currentMode);
                    }
                }
            }
        }
        
        window.addEventListener('DOMContentLoaded', function() {
            restoreDeviceStatus();
            connectMQTT();
        });
    </script>
</body>
</html>