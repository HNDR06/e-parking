<?php
session_start();

// Include database controller
require_once 'controllerInput.php';

// ==========================================
// 1. HANDLE UPLOAD IMAGE (AJAX Request)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_capture') {
    header('Content-Type: application/json');
    
    $ticketId = $_POST['ticket_id'];
    $imageData = $_POST['image_data'];
    
    // Decode Base64
    $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $imageBinary = base64_decode($imageData);
    
    // Buat folder uploads jika belum ada
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Simpan File
    $fileName = 'capture_' . $ticketId . '.jpg';
    $filePath = $uploadDir . $fileName;
    
    if (file_put_contents($filePath, $imageBinary)) {
        // Update Database (Pastikan fungsi updateTicketImage ada di controllerInput.php Anda)
        // Jika belum ada, tambahkan fungsi tersebut di controllerInput.php seperti panduan sebelumnya
        if (function_exists('updateTicketImage')) {
            $dbUpdated = updateTicketImage($ticketId, $filePath);
            $msg = $dbUpdated ? 'Database Updated' : 'File Saved, DB Update Failed';
        } else {
            $msg = 'File Saved (Function updateTicketImage not found)';
        }
        
        echo json_encode(['success' => true, 'file' => $filePath, 'message' => $msg]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menulis file']);
    }
    exit;
}

// ==========================================
// 2. LOGIKA UTAMA (Sama seperti sebelumnya)
// ==========================================

function generateTicketId() {
    return 'TKT' . time() . rand(100, 999);
}

function generatePlateNumber() {
    $regions = ['B', 'D', 'E', 'F', 'T', 'A', 'L', 'N', 'R', 'S', 'W', 'Z'];
    $region = $regions[array_rand($regions)];
    $number = rand(1000, 9999);
    $letters = '';
    for ($i = 0; $i < 3; $i++) { $letters .= chr(rand(65, 90)); }
    return $region . ' ' . $number . ' ' . $letters;
}

function generateVehicleType() {
    $types = ['Motor', 'Mobil'];
    return $types[array_rand($types)];
}

// Handle Print Popup
if (isset($_GET['print']) && isset($_GET['ticket'])) {
    $ticketId = $_GET['ticket'];
    if (isset($_SESSION['tickets'][$ticketId])) {
        $ticketData = $_SESSION['tickets'][$ticketId];
        $qrData = json_encode($ticketData);
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Ticket</title>
            <style>
                body { font-family: monospace; text-align: center; padding: 20px; }
                .qr { margin: 20px 0; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body onload="setTimeout(function(){window.print()}, 500)">
            <h2>PARKIR TIKET</h2>
            <h3><?php echo $ticketData['ticketId']; ?></h3>
            <div class="qr"><img src="<?php echo $qrCodeUrl; ?>" width="200"></div>
            <p><?php echo $ticketData['plateNumber']; ?></p>
            <p><?php echo $ticketData['entryTimeDisplay']; ?></p>
        </body>
        </html>
        <?php
        exit;
    }
}

// Handle Generate Manual
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    $ticketId = generateTicketId();
    $plateNumber = generatePlateNumber();
    $vehicleType = generateVehicleType();
    $entryTime = date('Y-m-d H:i:s');
    
    $ticketData = [
        'ticketId' => $ticketId,
        'plateNumber' => $plateNumber,
        'vehicleType' => $vehicleType,
        'entryTime' => $entryTime,
        'entryTimeDisplay' => date('d/m/Y H:i:s'),
        'status' => 'active',
        'source' => 'manual'
    ];
    
    $result = saveTicketToDatabase($ticketData);
    
    if ($result['success']) {
        if (!isset($_SESSION['tickets'])) { $_SESSION['tickets'] = []; }
        $_SESSION['tickets'][$ticketId] = $ticketData;
        $_SESSION['last_ticket'] = $ticketId;
        $_SESSION['show_gate'] = true;
    }
}

// Handle RFID
if (isset($_GET['rfid'])) {
    $rfidData = $_GET['rfid'];
    $ticketId = generateTicketId();
    $plateNumber = generatePlateNumber();
    $vehicleType = generateVehicleType();
    $entryTime = date('Y-m-d H:i:s');
    
    $ticketData = [
        'ticketId' => $ticketId,
        'plateNumber' => $plateNumber,
        'vehicleType' => $vehicleType,
        'entryTime' => $entryTime,
        'entryTimeDisplay' => date('d/m/Y H:i:s'),
        'status' => 'active',
        'source' => 'rfid',
        'rfidData' => $rfidData
    ];
    
    $result = saveTicketToDatabase($ticketData);
    
    if ($result['success']) {
        if (!isset($_SESSION['tickets'])) { $_SESSION['tickets'] = []; }
        $_SESSION['tickets'][$ticketId] = $ticketData;
        $_SESSION['last_ticket'] = $ticketId;
        $_SESSION['show_gate'] = true;
        
        if (isset($_GET['device_status'])) {
            $_SESSION['device_status'] = $_GET['device_status'];
        }
    }
}

// Handle Reset
if (isset($_GET['reset'])) {
    unset($_SESSION['show_gate']);
    unset($_SESSION['last_ticket']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$showGate = $_SESSION['show_gate'] ?? false;
$lastTicketId = $_SESSION['last_ticket'] ?? null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Parking System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; text-align: center; }
        .btn { padding: 15px 30px; border: none; border-radius: 5px; font-size: 18px; cursor: pointer; color: white; background: #667eea; width: 100%; transition: 0.3s; }
        .btn:hover { background: #5a6268; }
        
        /* CSS MONITOR KAMERA (DITAMPILKAN AGAR TIDAK BLACK SCREEN) */
        .camera-monitor {
            background: #222;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .video-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .cam-box {
            width: 45%;
            min-width: 200px;
        }
        .cam-box h4 { margin: 0 0 5px 0; color: #aaa; font-size: 12px; }
        
        /* Video dan Canvas dibuat visible */
        video, canvas {
            width: 100%;
            height: auto;
            border: 2px solid #555;
            border-radius: 5px;
            background: #000;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="card">
            <h1>🅿️ E-Parking System</h1>
            <p>Pelita Bangsa University</p>
        </div>

        <?php if (!$showGate): ?>
            <div class="card">
                <h2>Siap Mencetak Tiket</h2>
                <p>Silakan klik tombol di bawah atau tempelkan kartu RFID.</p>
                <form method="POST">
                    <button type="submit" name="generate" class="btn">
                        🎫 Cetak Tiket Manual
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="card">
                <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h1 style="margin:0;">🚪 GERBANG TERBUKA</h1>
                    <p style="margin:5px 0 0 0;">Capture Foto Sedang Diproses...</p>
                </div>

                <?php $currentTicket = $_SESSION['tickets'][$lastTicketId]; ?>
                
                <div style="text-align: left; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                    <p><strong>Tiket:</strong> <?php echo $currentTicket['ticketId']; ?></p>
                    <p><strong>Plat:</strong> <?php echo $currentTicket['plateNumber']; ?></p>
                    <p><strong>Status Upload:</strong> <span id="uploadStatusBadge" class="status-badge" style="background:#ffc107; color:black;">Menunggu...</span></p>
                </div>

                <p style="font-size: 12px; color: #888; margin-top: 20px;">Halaman akan refresh dalam 5 detik...</p>
                
                <a href="?reset=1" class="btn" style="background: #6c757d; margin-top: 10px; text-decoration: none; display: block;">
                    🔄 Reset Manual
                </a>
            </div>

            <script>
                // Auto Print Manual
                <?php if ($currentTicket['source'] !== 'rfid'): ?>
                setTimeout(() => { window.open('?print=1&ticket=<?php echo $lastTicketId; ?>', '_blank'); }, 500);
                <?php endif; ?>
                
                // Auto Reset
                setTimeout(() => { window.location.href = '?reset=1'; }, 5000);
            </script>
        <?php endif; ?>

        <div class="camera-monitor">
            <h3>📷 Camera Monitor</h3>
            <p style="font-size: 11px; color: #ccc;">Pastikan video di kiri muncul agar capture di kanan tidak hitam.</p>
            
            <div class="video-container">
                <div class="cam-box">
                    <h4>🔴 LIVE FEED</h4>
                    <video id="webcam" autoplay playsinline muted></video>
                </div>
                <div class="cam-box">
                    <h4>📸 CAPTURE RESULT</h4>
                    <canvas id="canvas"></canvas>
                </div>
            </div>
            <div id="debugLog" style="font-family: monospace; font-size: 10px; margin-top: 10px; color: #0f0;"></div>
        </div>
    </div>

    <script>
        const video = document.getElementById('webcam');
        const canvas = document.getElementById('canvas');
        const debugLog = document.getElementById('debugLog');
        const uploadBadge = document.getElementById('uploadStatusBadge');

        function log(msg) {
            console.log(msg);
            debugLog.innerHTML = msg;
        }

        // 1. Inisialisasi Kamera
        async function startCamera() {
            try {
                log("Meminta akses kamera...");
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: "environment"
                    } 
                });
                video.srcObject = stream;
                await video.play();
                log("✅ Kamera Aktif (Live)");
            } catch (err) {
                log("❌ Gagal Akses Kamera: " + err.message);
                alert("Kamera Error: Pastikan akses diizinkan dan menggunakan HTTPS/Localhost.");
            }
        }

        // 2. Fungsi Capture & Upload
        async function processCapture(ticketId) {
            log("⏳ Menunggu exposure kamera (1.5 detik)...");
            
            if (uploadBadge) uploadBadge.textContent = "Menyiapkan Kamera...";
            
            // Delay 1.5 detik agar gambar tidak gelap
            setTimeout(async () => {
                if (video.readyState === 4) { // Ready
                    // Set ukuran canvas
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    
                    const ctx = canvas.getContext('2d');
                    // Draw video ke canvas
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    
                    log("📸 Gambar diambil, mengupload...");
                    if (uploadBadge) {
                        uploadBadge.textContent = "Mengupload...";
                        uploadBadge.style.background = "#17a2b8";
                        uploadBadge.style.color = "white";
                    }

                    // Convert & Upload
                    const imageData = canvas.toDataURL('image/jpeg', 0.8);
                    
                    const formData = new FormData();
                    formData.append('action', 'save_capture');
                    formData.append('ticket_id', ticketId);
                    formData.append('image_data', imageData);
                    
                    try {
                        const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        
                        if (result.success) {
                            log("✅ SUKSES: " + result.file);
                            if (uploadBadge) {
                                uploadBadge.textContent = "✅ Tersimpan";
                                uploadBadge.style.background = "#28a745";
                            }
                        } else {
                            log("❌ GAGAL: " + result.message);
                            if (uploadBadge) {
                                uploadBadge.textContent = "❌ Gagal";
                                uploadBadge.style.background = "#dc3545";
                            }
                        }
                    } catch (e) {
                        log("❌ Error Network: " + e.message);
                    }
                } else {
                    log("⚠️ Kamera belum siap data stream.");
                    // Retry
                    setTimeout(() => processCapture(ticketId), 500);
                }
            }, 1500); // Waktu tunggu exposure
        }

        // Jalankan Kamera saat load
        startCamera();

        // Jika Gate Terbuka, jalankan proses capture
        <?php if ($showGate): ?>
        document.addEventListener('DOMContentLoaded', () => {
            // Tunggu video play event
            video.addEventListener('playing', () => {
                processCapture('<?php echo $lastTicketId; ?>');
            });
            
            // Fallback jika event playing terlewat
            setTimeout(() => {
                if(video.currentTime > 0) processCapture('<?php echo $lastTicketId; ?>');
            }, 2000);
        });
        <?php endif; ?>

        // === MQTT LOGIC (Simplified) ===
        // ... (Tambahkan kembali logika MQTT Anda di sini jika diperlukan) ...
        // ... MQTT Code untuk redirect ke ?rfid=... ...
    </script>
</body>
</html>