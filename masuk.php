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

// Handle image upload via AJAX
if (isset($_POST['upload_image']) && isset($_POST['ticket_id'])) {
    $ticketId = $_POST['ticket_id'];
    $imageData = $_POST['image_data'];
    
    // Remove data:image/png;base64, prefix
    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $imageData = base64_decode($imageData);
    
    // Create uploads directory if not exists
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $filename = $ticketId . '_' . time() . '.png';
    $filepath = $uploadDir . $filename;
    
    // Save image
    if (file_put_contents($filepath, $imageData)) {
        // Update ticket data in session with image path
        if (isset($_SESSION['tickets'][$ticketId])) {
            $_SESSION['tickets'][$ticketId]['vehicleImage'] = $filepath;
            
            // Update in database
            updateTicketImage($ticketId, $filepath);
            
            echo json_encode([
                'success' => true,
                'message' => 'Image saved successfully',
                'filepath' => $filepath
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Ticket not found in session'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save image'
        ]);
    }
    exit;
}

// Check if this is a print request
if (isset($_GET['print']) && isset($_GET['ticket'])) {
    $ticketId = $_GET['ticket'];
    if (isset($_SESSION['tickets'][$ticketId])) {
        $ticketData = $_SESSION['tickets'][$ticketId];
        $qrData = json_encode($ticketData);
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData);
        
        // Display print page
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Print Karcis - <?php echo $ticketData['ticketId']; ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    padding: 20px;
                    background: white;
                }
                
                .ticket-card {
                    max-width: 400px;
                    margin: 0 auto;
                    border: 4px dashed #333;
                    border-radius: 10px;
                    padding: 30px;
                }
                
                .ticket-header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                
                .ticket-header h1 {
                    color: #667eea;
                    font-size: 36px;
                    margin-bottom: 10px;
                }
                
                .ticket-header p {
                    color: #666;
                }
                
                .qr-code {
                    text-align: center;
                    margin: 30px 0;
                }
                
                .qr-code img {
                    width: 300px;
                    height: 300px;
                    border: 2px solid #ddd;
                    border-radius: 10px;
                    padding: 10px;
                }
                
                .ticket-details {
                    background: #f8f9fa;
                    border-radius: 10px;
                    padding: 20px;
                }
                
                .detail-row {
                    padding: 10px 0;
                    border-bottom: 1px solid #dee2e6;
                }
                
                .detail-row:last-child {
                    border-bottom: none;
                }
                
                .detail-label {
                    color: #666;
                    font-size: 12px;
                    margin-bottom: 5px;
                }
                
                .detail-value {
                    color: #333;
                    font-size: 16px;
                    font-weight: bold;
                    font-family: monospace;
                }
                
                .ticket-footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 12px;
                }
                
                @media print {
                    body {
                        padding: 0;
                    }
                }
            </style>
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
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
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    $ticketId = generateTicketId();
    $plateNumber = generatePlateNumber();
    $vehicleType = generateVehicleType();
    $entryTime = date('Y-m-d H:i:s');
    $entryTimeDisplay = date('d/m/Y H:i:s');
    
    // Create ticket data array
    $ticketData = [
        'ticketId' => $ticketId,
        'plateNumber' => $plateNumber,
        'vehicleType' => $vehicleType,
        'entryTime' => $entryTime,
        'entryTimeDisplay' => $entryTimeDisplay,
        'status' => 'active',
        'source' => 'manual',
        'vehicleImage' => null // Will be updated after capture
    ];
    
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
    $_SESSION['show_camera'] = true; // Flag to show camera
}

// Handle RFID from MQTT (via GET parameter)
if (isset($_GET['rfid'])) {
    $rfidData = $_GET['rfid'];
    $ticketId = generateTicketId();
    $plateNumber = generatePlateNumber();
    $vehicleType = generateVehicleType();
    $entryTime = date('Y-m-d H:i:s');
    $entryTimeDisplay = date('d/m/Y H:i:s');
    
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
        'vehicleImage' => null
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
    $_SESSION['show_camera'] = true; // Flag to show camera
    
    // Save device status to maintain connection state
    if (isset($_GET['device_status'])) {
        $_SESSION['device_status'] = $_GET['device_status'];
    }
}

// Handle skip camera
if (isset($_GET['skip_camera'])) {
    unset($_SESSION['show_camera']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle reset
if (isset($_GET['reset'])) {
    unset($_SESSION['show_gate']);
    unset($_SESSION['last_ticket']);
    unset($_SESSION['show_camera']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$showGate = $_SESSION['show_gate'] ?? false;
$showCamera = $_SESSION['show_camera'] ?? false;
$lastTicketId = $_SESSION['last_ticket'] ?? null;
$savedDeviceStatus = $_SESSION['device_status'] ?? null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Parking System - QR Code Generator</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icon {
            background: #667eea;
            padding: 15px;
            border-radius: 10px;
            color: white;
            font-size: 32px;
        }

        .header-text h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 5px;
        }

        .header-text p {
            color: #666;
            font-size: 16px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 30px;
        }

        .info-box {
            background: #f0f4ff;
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-box p {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        /* Camera Capture Styles */
        .camera-container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .camera-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .camera-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .camera-header p {
            color: #666;
        }

        #camera-preview {
            width: 100%;
            max-width: 640px;
            height: auto;
            border-radius: 10px;
            background: #000;
            margin: 0 auto;
            display: block;
        }

        #captured-image {
            width: 100%;
            max-width: 640px;
            height: auto;
            border-radius: 10px;
            margin: 0 auto;
            display: none;
        }

        .camera-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .camera-status {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            color: #856404;
            font-weight: 600;
        }

        .camera-status.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        /* Gate Message Styles */
        .gate-container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .ticket-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .ticket-info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .ticket-info-row:last-child {
            border-bottom: none;
        }

        .ticket-info-label {
            color: #666;
            font-weight: 600;
        }

        .ticket-info-value {
            color: #333;
            font-family: monospace;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        /* Status Indicators */
        .mqtt-status {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
        }

        .mqtt-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #dc3545;
            animation: pulse 2s infinite;
        }

        .mqtt-indicator.connected {
            background: #28a745;
        }

        .mqtt-indicator.connecting {
            background: #ffc107;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .mqtt-text {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .device-status {
            position: fixed;
            top: 80px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            border: 2px solid #dc3545;
        }

        .device-status.device-connected {
            border-color: #28a745;
        }

        .device-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #dc3545;
            animation: pulse 2s infinite;
        }

        .device-indicator.connected {
            background: #28a745;
            animation: none;
        }

        .device-text {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .device-icon {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <!-- MQTT Status Indicator -->
    <div class="mqtt-status">
        <div class="mqtt-indicator" id="mqttIndicator"></div>
        <span class="mqtt-text" id="mqttStatus">Connecting to MQTT...</span>
    </div>

    <!-- Device Status Indicator -->
    <div class="device-status" id="deviceStatus">
        <span class="device-icon">📟</span>
        <div class="device-indicator" id="deviceIndicator"></div>
        <span class="device-text" id="deviceText">Device Not Connected</span>
    </div>

    <div class="container">
        <!-- Header -->
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
            <!-- Form Input -->
            <div class="card">
                <h2>Welcome to Pelita Bangsa University</h2>
                
                <form method="POST">
                    <div class="info-box">
                        <p>ℹ️ Information</p>
                        <p style="color: #333; margin: 0;">Click Button Below to Print Ticket</p>
                    </div>

                    <button type="submit" name="generate" class="btn btn-primary">
                        🎫 Print Ticket
                    </button>
                </form>
            </div>
        <?php elseif ($showCamera && $lastTicketId): ?>
            <!-- Camera Capture -->
            <div class="camera-container">
                <div class="camera-header">
                    <h2>📸 Capture Vehicle Photo</h2>
                    <p>Take a photo of the vehicle for record</p>
                </div>

                <div class="camera-status" id="cameraStatus">
                    Initializing camera...
                </div>

                <video id="camera-preview" autoplay playsinline></video>
                <canvas id="canvas" style="display: none;"></canvas>
                <img id="captured-image" alt="Captured Vehicle">
            </div>

            <script>
                            // Auto Capture Configuration
                const AUTO_CAPTURE_DELAY = 1000; // 1 detik setelah kamera ready
                let stream = null;
                let capturedImageData = null;
                let autoCaptureTimer = null;
                const ticketId = '<?php echo $lastTicketId; ?>';

                // Initialize camera dengan auto-capture
                async function initCamera() {
                    const statusEl = document.getElementById('cameraStatus');
                    const video = document.getElementById('camera-preview');
                    
                    try {
                        statusEl.textContent = 'Requesting camera access...';
                        
                        stream = await navigator.mediaDevices.getUserMedia({ 
                            video: { 
                                facingMode: 'environment',
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            } 
                        });
                        
                        console.log('✅ Camera stream obtained');
                        video.srcObject = stream;
                        
                        // Hide manual controls
                        const manualControls = document.querySelector('.camera-controls');
                        if (manualControls) {
                            manualControls.style.display = 'none';
                        }
                        
                        // Wait for video to actually be ready and playing
                        await waitForVideoReady(video);
                        
                        // Update status
                        statusEl.textContent = 'Camera ready! Auto-capturing Wait...';
                        statusEl.style.background = '#d4edda';
                        statusEl.style.borderColor = '#28a745';
                        statusEl.style.color = '#155724';
                        
                        // Start countdown
                        startAutoCaptureCountdown();
                        
                    } catch (error) {
                        console.error('❌ Camera error:', error);
                        statusEl.textContent = 'Camera access denied: ' + error.message;
                        statusEl.style.background = '#f8d7da';
                        statusEl.style.borderColor = '#dc3545';
                        statusEl.style.color = '#721c24';
                        
                        // Auto skip if camera fails
                        setTimeout(() => {
                            window.location.href = '?skip_camera=1';
                        }, 3000);
                    }
                }

                // Wait for video to be fully ready
                function waitForVideoReady(video) {
                    return new Promise((resolve, reject) => {
                        // Set timeout as fallback
                        const timeout = setTimeout(() => {
                            if (video.readyState >= 2) {
                                console.log('✅ Video ready (timeout fallback)');
                                resolve();
                            } else {
                                console.error('❌ Video failed to load');
                                reject(new Error('Video load timeout'));
                            }
                        }, 5000);
                        
                        // Check if already ready
                        if (video.readyState >= 2) {
                            clearTimeout(timeout);
                            console.log('✅ Video already ready');
                            resolve();
                            return;
                        }
                        
                        // Wait for loadeddata event
                        video.addEventListener('loadeddata', () => {
                            clearTimeout(timeout);
                            console.log('✅ Video loadeddata event fired');
                            resolve();
                        }, { once: true });
                        
                        // Also listen to canplay as backup
                        video.addEventListener('canplay', () => {
                            clearTimeout(timeout);
                            console.log('✅ Video canplay event fired');
                            resolve();
                        }, { once: true });
                    });
                }

                // Countdown before auto-capture
                function startAutoCaptureCountdown() {
                    let countdown = 1;
                    const statusEl = document.getElementById('cameraStatus');
                    
                    const countdownInterval = setInterval(() => {
                        if (countdown > 0) {
                            statusEl.textContent = `📸 Auto-capturing`;
                            countdown--;
                        } else {
                            clearInterval(countdownInterval);
                            statusEl.textContent = '📷 Capturing photo...';
                            
                            // Small delay to ensure status is visible
                            setTimeout(() => {
                                autoCapturePhoto();
                            }, 200);
                        }
                    }, 1000);
                }

                // Auto capture photo
                function autoCapturePhoto() {
                    const video = document.getElementById('camera-preview');
                    const canvas = document.getElementById('canvas');
                    const capturedImage = document.getElementById('captured-image');
                    const statusEl = document.getElementById('cameraStatus');
                    
                    try {
                        // Verify video is actually playing
                        if (video.readyState < 2) {
                            console.error('❌ Video not ready for capture');
                            statusEl.textContent = '❌ Video not ready. Retrying...';
                            
                            // Retry after 1 second
                            setTimeout(autoCapturePhoto, 1000);
                            return;
                        }
                        
                        // Set canvas size to match video
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        
                        console.log(`📐 Canvas size: ${canvas.width}x${canvas.height}`);
                        
                        if (canvas.width === 0 || canvas.height === 0) {
                            console.error('❌ Invalid canvas dimensions');
                            statusEl.textContent = '❌ Invalid video dimensions. Retrying...';
                            
                            // Retry after 1 second
                            setTimeout(autoCapturePhoto, 1000);
                            return;
                        }
                        
                        // Draw video frame to canvas
                        const context = canvas.getContext('2d');
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        
                        // Convert to base64
                        capturedImageData = canvas.toDataURL('image/png');
                        
                        console.log('✅ Photo captured, data length:', capturedImageData.length);
                        
                        // Show captured image
                        capturedImage.src = capturedImageData;
                        video.style.display = 'none';
                        capturedImage.style.display = 'block';
                        
                        // Update status
                        statusEl.textContent = '✅ Photo captured! Uploading...';
                        statusEl.style.background = '#fff3cd';
                        statusEl.style.borderColor = '#ffc107';
                        statusEl.style.color = '#856404';
                        
                        // Auto upload immediately
                        setTimeout(() => {
                            autoUploadPhoto();
                        }, 500);
                        
                    } catch (error) {
                        console.error('❌ Capture error:', error);
                        statusEl.textContent = '❌ Capture failed: ' + error.message;
                        statusEl.style.background = '#f8d7da';
                        statusEl.style.borderColor = '#dc3545';
                        
                        skipToNextStep();
                    }
                }

                // Auto upload photo
                async function autoUploadPhoto() {
                    const statusEl = document.getElementById('cameraStatus');
                    
                    if (!capturedImageData) {
                        console.error('❌ No image data to upload');
                        statusEl.textContent = '❌ No image data. Redirecting...';
                        skipToNextStep();
                        return;
                    }

                    try {
                        console.log('📤 Uploading image...');
                        
                        const formData = new FormData();
                        formData.append('upload_image', '1');
                        formData.append('ticket_id', ticketId);
                        formData.append('image_data', capturedImageData);
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        console.log('📥 Upload response:', result);
                        
                        if (result.success) {
                            statusEl.textContent = '✅ Photo saved successfully! Redirecting...';
                            statusEl.style.background = '#d4edda';
                            statusEl.style.borderColor = '#28a745';
                            statusEl.style.color = '#155724';
                            
                            stopCamera();
                            
                            // Redirect after short delay
                            setTimeout(() => {
                                window.location.href = '?skip_camera=1';
                            }, 1500);
                        } else {
                            console.error('❌ Upload failed:', result.message);
                            statusEl.textContent = '❌ Upload failed: ' + result.message;
                            statusEl.style.background = '#f8d7da';
                            statusEl.style.borderColor = '#dc3545';
                            
                            skipToNextStep();
                        }
                    } catch (error) {
                        console.error('❌ Upload error:', error);
                        statusEl.textContent = '❌ Upload error: ' + error.message;
                        statusEl.style.background = '#f8d7da';
                        statusEl.style.borderColor = '#dc3545';
                        
                        skipToNextStep();
                    }
                }

                // Skip to next step if upload fails
                function skipToNextStep() {
                    stopCamera();
                    setTimeout(() => {
                        window.location.href = '?skip_camera=1';
                    }, 2000);
                }

                // Stop camera
                function stopCamera() {
                    if (stream) {
                        stream.getTracks().forEach(track => {
                            track.stop();
                            console.log('🛑 Camera track stopped');
                        });
                    }
                    if (autoCaptureTimer) {
                        clearTimeout(autoCaptureTimer);
                    }
                }

                // Initialize on load
                window.addEventListener('DOMContentLoaded', function() {
                    console.log('🚀 Initializing auto-capture camera...');
                    initCamera();
                });

                // Cleanup on page unload
                window.addEventListener('beforeunload', function() {
                    stopCamera();
                });
            </script>
        <?php else: ?>
            <!-- Gate Open Message -->
            <div class="gate-container">
                <div style="background: #28a745; color: white; padding: 60px 40px; border-radius: 10px; margin-bottom: 30px;">
                    <h1 style="font-size: 48px; margin-bottom: 20px; text-align: center;">
                        🚪 GERBANG TERBUKA
                    </h1>
                    <p style="font-size: 24px; text-align: center; margin: 0;">
                        SILAKAN MASUK
                    </p>
                </div>
                
                <?php 
                $currentTicket = $_SESSION['tickets'][$lastTicketId];
                $isRFID = isset($currentTicket['source']) && $currentTicket['source'] === 'rfid';
                ?>
                
                <div class="ticket-info">
                    <?php if ($isRFID): ?>
                        <div class="ticket-info-row">
                            <span class="ticket-info-label">RFID:</span>
                            <span class="ticket-info-value"><?php echo $currentTicket['rfidData']; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="ticket-info-row">
                        <span class="ticket-info-label">Nomor Tiket:</span>
                        <span class="ticket-info-value"><?php echo $currentTicket['ticketId']; ?></span>
                    </div>
                    <div class="ticket-info-row">
                        <span class="ticket-info-label">Kendaraan:</span>
                        <span class="ticket-info-value"><?php echo strtoupper($currentTicket['vehicleType']); ?> - <?php echo $currentTicket['plateNumber']; ?></span>
                    </div>
                    <div class="ticket-info-row">
                        <span class="ticket-info-label">Waktu Masuk:</span>
                        <span class="ticket-info-value"><?php echo $currentTicket['entryTimeDisplay']; ?></span>
                    </div>
                    <?php if (isset($currentTicket['vehicleImage']) && $currentTicket['vehicleImage']): ?>
                    <div class="ticket-info-row">
                        <span class="ticket-info-label">Foto Kendaraan:</span>
                        <span class="ticket-info-value">✅ Tersimpan</span>
                    </div>
                    <?php endif; ?>
                </div>

                <p style="color: #666; margin-top: 20px; font-size: 14px; text-align: center;">
                    <?php if ($isRFID): ?>
                        RFID terdeteksi.<br>
                    <?php else: ?>
                        Karcis dibuka di tab baru untuk dicetak.<br>
                    <?php endif; ?>
                    Halaman ini akan otomatis reset dalam 3 detik...
                </p>

                <div class="action-buttons">
                    <a href="?reset=1" class="btn btn-secondary" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        🔄 Kendaraan Baru
                    </a>
                </div>
            </div>

            <script>
                <?php 
                $isRFID = isset($currentTicket['source']) && $currentTicket['source'] === 'rfid';
                if (!$isRFID): // Only open print for manual generation
                ?>
                // Open print page in new tab WITHOUT switching focus
                setTimeout(function() {
                    var printWindow = window.open('?print=1&ticket=<?php echo $lastTicketId; ?>', '_blank', 'noopener,noreferrer');
                    
                    // Multiple attempts to keep focus on current window
                    setTimeout(function() {
                        window.focus();
                    }, 10);
                    
                    setTimeout(function() {
                        window.focus();
                    }, 100);
                    
                    setTimeout(function() {
                        window.focus();
                    }, 300);
                }, 100);
                <?php endif; ?>
                
                // Auto redirect after 5 seconds
                setTimeout(function() {
                    window.location.href = '?reset=1';
                }, 3000);
            </script>
        <?php endif; ?>
    </div>

    <script>
        // MQTT Configuration - HiveMQ Cloud
        const MQTT_BROKER = "868dc6a1dc894a84a5793a95746a9881.s1.eu.hivemq.cloud";
        const MQTT_PORT = 8884;
        const MQTT_USERNAME = "hivemq.webclient.1751385942895";
        const MQTT_PASSWORD = "R07c6iA#dG>W3lNj?sQ:";
        const MQTT_TOPIC_MODE = "KyoumaProject/mode";
        const MQTT_TOPIC_RFID = "KyoumaProject/rfid";
        
        // Create MQTT Client
        const clientId = "parking_web_" + Math.random().toString(16).substr(2, 8);
        const client = new Paho.MQTT.Client(MQTT_BROKER, MQTT_PORT, "/mqtt", clientId);
        
        // MQTT Connection Options
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
        
        // Set callback handlers
        client.onConnectionLost = onConnectionLost;
        client.onMessageArrived = onMessageArrived;
        
        // Update MQTT status UI
        function updateMQTTStatus(status, connected = false, connecting = false) {
            const indicator = document.getElementById('mqttIndicator');
            const statusText = document.getElementById('mqttStatus');
            
            indicator.className = 'mqtt-indicator';
            if (connected) {
                indicator.classList.add('connected');
            } else if (connecting) {
                indicator.classList.add('connecting');
            }
            
            statusText.textContent = status;
        }

        // Update Device status UI
        function updateDeviceStatus(mode) {
            const deviceStatus = document.getElementById('deviceStatus');
            const deviceIndicator = document.getElementById('deviceIndicator');
            const deviceText = document.getElementById('deviceText');
            
            const modeLower = mode.toLowerCase().trim();
            
            if (modeLower === "in") {
                deviceStatus.classList.add('device-connected');
                deviceIndicator.classList.add('connected');
                deviceText.textContent = "Device Connected";
                console.log("✅ Device Status: CONNECTED (mode: " + mode + ")");
                
                localStorage.setItem('device_mode', 'in');
                localStorage.setItem('device_connected', 'true');
            } else {
                deviceStatus.classList.remove('device-connected');
                deviceIndicator.classList.remove('connected');
                deviceText.textContent = "Device Not Connected";
                console.log("❌ Device Status: NOT CONNECTED (mode: " + mode + ")");
                
                localStorage.setItem('device_mode', mode);
                localStorage.setItem('device_connected', 'false');
            }
        }
        
        // Restore device status from localStorage on page load
        function restoreDeviceStatus() {
            const savedConnected = localStorage.getItem('device_connected');
            const savedMode = localStorage.getItem('device_mode');
            
            <?php if ($savedDeviceStatus): ?>
                console.log("Restoring device status from session: <?php echo $savedDeviceStatus; ?>");
                updateDeviceStatus("<?php echo $savedDeviceStatus; ?>");
            <?php else: ?>
                if (savedConnected === 'true' && savedMode === 'in') {
                    console.log("Restoring device status from localStorage: CONNECTED");
                    updateDeviceStatus('in');
                } else if (savedMode) {
                    console.log("Restoring device status from localStorage: " + savedMode);
                    updateDeviceStatus(savedMode);
                }
            <?php endif; ?>
        }
        
        // Connect to MQTT Broker
        function connectMQTT() {
            updateMQTTStatus('Connecting to MQTT...', false, true);
            try {
                client.connect(connectOptions);
            } catch (error) {
                console.error("MQTT Connection Error:", error);
                updateMQTTStatus('Connection Failed', false, false);
            }
        }
        
        // Called when connection is established
        function onConnect() {
            console.log("Connected to MQTT Broker");
            updateMQTTStatus('MQTT Connected', true, false);
            
            client.subscribe(MQTT_TOPIC_MODE, {qos: 1});
            console.log("Subscribed to topic: " + MQTT_TOPIC_MODE);
            
            client.subscribe(MQTT_TOPIC_RFID, {qos: 1});
            console.log("Subscribed to topic: " + MQTT_TOPIC_RFID);
        }
        
        // Called when connection fails
        function onFailure(message) {
            console.error("Connection Failed:", message.errorMessage);
            updateMQTTStatus('Connection Failed', false, false);
            
            setTimeout(connectMQTT, 5000);
        }
        
        // Called when connection is lost
        function onConnectionLost(responseObject) {
            if (responseObject.errorCode !== 0) {
                console.error("Connection Lost:", responseObject.errorMessage);
                updateMQTTStatus('Connection Lost', false, false);
                
                setTimeout(connectMQTT, 5000);
            }
        }
        
        // Called when message arrives
        function onMessageArrived(message) {
            console.log("Message arrived:");
            console.log("Topic: " + message.destinationName);
            console.log("Payload: " + message.payloadString);
            
            const topic = message.destinationName;
            const payload = message.payloadString;
            
            if (topic === MQTT_TOPIC_MODE) {
                handleModeMessage(payload);
            } else if (topic === MQTT_TOPIC_RFID) {
                handleRFIDMessage(payload);
            }
        }
        
        // Handle message from KyoumaProject/mode
        function handleModeMessage(payload) {
            console.log("Mode message:", payload);
            
            updateDeviceStatus(payload);
            showNotification("Mode Update", payload);
            
            const modeLower = payload.toLowerCase().trim();
            if (modeLower === "in") {
                console.log("🚗 Mode: ENTRY - Device ready to generate ticket");
            } else {
                console.log("🚫 Mode: " + payload + " - Device not in entry mode");
            }
        }
        
        // Handle message from KyoumaProject/rfid
        function handleRFIDMessage(payload) {
            console.log("RFID message:", payload);
            
            showNotification("RFID Detected", payload);
            
            const deviceText = document.getElementById('deviceText').textContent;
            
            if (deviceText === "Device Connected") {
                console.log("✅ RFID Detected: " + payload + " - Opening gate...");
                
                const currentMode = localStorage.getItem('device_mode') || 'in';
                
                window.location.href = '?rfid=' + encodeURIComponent(payload) + '&device_status=' + encodeURIComponent(currentMode);
            } else {
                console.warn("⚠️ RFID detected but device not connected. Ignoring...");
            }
        }
        
        // Show notification
        function showNotification(title, message) {
            console.log(`[${title}] ${message}`);
        }
        
        // Initialize MQTT connection on page load
        window.addEventListener('DOMContentLoaded', function() {
            restoreDeviceStatus();
            connectMQTT();
        });
    </script>
</body>
</html>