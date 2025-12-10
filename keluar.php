<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Parking - Kendaraan Keluar</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        .header {
            background-color: #4285f4;
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 {
            font-size: 24px;
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

        /* Form Input Selection */
        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 80px);
            padding: 40px;
        }

        .form-box {
            background-color: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }

        .form-box h2 {
            text-align: center;
            margin-bottom: 15px;
            color: #333;
            font-size: 28px;
        }

        .form-box p {
            text-align: center;
            color: #666;
            margin-bottom: 35px;
            font-size: 15px;
        }

        .input-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .method-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            border-radius: 15px;
            cursor: pointer;
            text-align: center;
            color: white;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 3px solid transparent;
        }

        .method-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .method-card.active {
            border-color: #ffc107;
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.3);
        }

        .method-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .method-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .method-desc {
            font-size: 12px;
            opacity: 0.9;
        }

        /* Manual Input Form */
        .manual-form {
            display: none;
        }

        .manual-form.show {
            display: block;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #555;
            font-weight: 500;
            font-size: 16px;
        }

        .form-group input {
            width: 100%;
            padding: 18px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4285f4;
        }

        .btn-submit {
            width: 100%;
            padding: 18px;
            background-color: #4285f4;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #3367d6;
        }

        .btn-back {
            width: 100%;
            padding: 12px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
        }

        /* QR Scanner */
        .qr-scanner {
            display: none;
        }

        .qr-scanner.show {
            display: block;
        }

        #qr-reader {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
        }

        .qr-status {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            text-align: center;
            color: #856404;
            font-weight: 600;
        }

        .qr-status.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .qr-status.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        /* RFID Waiting */
        .rfid-waiting {
            display: none;
            text-align: center;
        }

        .rfid-waiting.show {
            display: block;
        }

        .rfid-animation {
            width: 200px;
            height: 200px;
            margin: 30px auto;
            position: relative;
        }

        .rfid-wave {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 3px solid #4285f4;
            border-radius: 50%;
            animation: rfid-pulse 2s infinite;
        }

        .rfid-wave:nth-child(2) {
            animation-delay: 0.5s;
        }

        .rfid-wave:nth-child(3) {
            animation-delay: 1s;
        }

        @keyframes rfid-pulse {
            0% {
                transform: scale(0.5);
                opacity: 1;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        .rfid-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 64px;
        }

        .rfid-message {
            font-size: 20px;
            color: #333;
            margin-top: 20px;
            font-weight: 600;
        }

        .rfid-submessage {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }

        /* Data Display */
        .data-container {
            display: none;
            height: calc(100vh - 80px);
            gap: 0;
        }

        .data-container.show {
            display: flex;
        }

        .left-section {
            width: 50%;
            background-color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .right-section {
            width: 50%;
            background-color: #f9f9f9;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .photo-container {
            width: 100%;
            max-width: 500px;
            background-color: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .photo-container img {
            width: 100%;
            height: auto;
            display: block;
        }

        .info-box {
            width: 100%;
            max-width: 500px;
            margin-top: 25px;
            padding: 25px;
            background-color: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #4285f4;
        }

        .info-box h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 20px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        .total-bayar {
            margin-top: 15px;
            padding: 15px;
            background-color: #4285f4;
            color: white;
            border-radius: 8px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }

        .struk-container {
            width: 100%;
            max-width: 400px;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .struk-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #ddd;
        }

        .struk-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }

        .struk-id {
            color: #666;
            font-size: 14px;
        }

        .barcode-container {
            margin: 30px 0;
            padding: 15px;
            background-color: white;
            text-align: center;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .barcode-container svg {
            max-width: 100%;
            height: auto;
        }
        
        .btn-buka-gerbang {
            width: 100%;
            padding: 18px;
            background-color: #34a853;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        
        .btn-buka-gerbang:hover {
            background-color: #2d8e47;
        }
        
        .btn-buka-gerbang:disabled {
            background-color: #ccc;
            cursor: not-allowed;
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
        <span style="font-size: 18px;">📟</span>
        <div class="device-indicator" id="deviceIndicator"></div>
        <span class="mqtt-text" id="deviceText">Device Not Connected</span>
    </div>

    <div class="header">
        <div style="width: 40px; height: 40px; background-color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4285f4; font-weight: bold; font-size: 20px;">P</div>
        <h1>E-PARKING - Kendaraan Keluar</h1>
    </div>

    <!-- FORM SELECTION -->
    <div class="form-container" id="formContainer">
        <div class="form-box">
            <h2>Kendaraan Keluar</h2>
            <p>Pilih metode checkout kendaraan</p>
            
            <!-- Method Selection -->
            <div class="input-methods" id="methodSelection">
                <div class="method-card" data-method="manual">
                    <div class="method-icon">⌨️</div>
                    <div class="method-title">Manual Input</div>
                    <div class="method-desc">Masukkan plat nomor</div>
                </div>
                
                <div class="method-card" data-method="qr">
                    <div class="method-icon">📷</div>
                    <div class="method-title">Scan QR Code</div>
                    <div class="method-desc">Scan karcis parkir</div>
                </div>
                
                <div class="method-card" data-method="rfid">
                    <div class="method-icon">📡</div>
                    <div class="method-title">Tap RFID</div>
                    <div class="method-desc">Tempelkan kartu</div>
                </div>
            </div>

            <!-- Manual Input Form -->
            <div class="manual-form" id="manualForm">
                <form id="checkoutForm">
                    <div class="form-group">
                        <label for="nomorKendaraan">Nomor Kendaraan</label>
                        <input 
                            type="text" 
                            id="nomorKendaraan" 
                            name="nomorKendaraan" 
                            placeholder="Contoh: B 1234 ABC"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        Proses Checkout
                    </button>
                    <button type="button" class="btn-back" onclick="backToSelection()">
                        ← Kembali
                    </button>
                </form>
            </div>

            <!-- QR Scanner -->
            <div class="qr-scanner" id="qrScanner">
                <div id="qr-reader"></div>
                <div class="qr-status" id="qrStatus">
                    Arahkan kamera ke QR Code pada karcis parkir
                </div>
                <button type="button" class="btn-back" onclick="stopQRScanner()">
                    ← Kembali
                </button>
            </div>

            <!-- RFID Waiting -->
            <div class="rfid-waiting" id="rfidWaiting">
                <div class="rfid-animation">
                    <div class="rfid-wave"></div>
                    <div class="rfid-wave"></div>
                    <div class="rfid-wave"></div>
                    <div class="rfid-icon">📡</div>
                </div>
                <div class="rfid-message">Menunggu RFID...</div>
                <div class="rfid-submessage">Tempelkan kartu RFID pada reader</div>
                <button type="button" class="btn-back" onclick="backToSelection()">
                    ← Kembali
                </button>
            </div>
        </div>
    </div>

    <!-- DATA CONTAINER (Hidden by default) -->
    <div class="data-container" id="dataContainer">
        <!-- BAGIAN KIRI: Foto + Info -->
        <div class="left-section">
            <div class="photo-container">
                <img id="fotoKendaraan" src="https://cdn.idntimes.com/content-images/post/20220207/part-aksesoris-1643798279-369f2987f7332c7fb12da0544e5b9b76-9b72ce3f7bb52187727dd141299ee91f.jpg" alt="Foto Kendaraan">
            </div>
            
            <div class="info-box">
                <h3>Informasi Parkir</h3>
                <div class="info-item">
                    <span class="info-label">Nomor Kendaraan:</span>
                    <span class="info-value" id="displayNomor">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Waktu Masuk:</span>
                    <span class="info-value" id="displayWaktuMasuk">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Waktu Keluar:</span>
                    <span class="info-value" id="displayWaktuKeluar">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Durasi Parkir:</span>
                    <span class="info-value" id="displayDurasi">-</span>
                </div>
                <div class="total-bayar" id="displayTotal">
                    Rp 0
                </div>
            </div>
        </div>

        <!-- BAGIAN KANAN: Struk -->
        <div class="right-section">
            <div class="struk-container">
                <div class="struk-header">
                    <h2>STRUK PARKIR</h2>
                    <p class="struk-id" id="strukId">ID: -</p>
                </div>
                
                <div class="barcode-container">
                    <svg id="barcode"></svg>
                </div>
                
                <!-- Button Buka Gerbang -->
                <button id="btnBukaGerbang" class="btn-buka-gerbang">
                    🚪 Buka Gerbang
                </button>
            </div>
        </div>
    </div>

    <script>
        // ==================== MQTT CONFIGURATION ====================
        const MQTT_BROKER = "868dc6a1dc894a84a5793a95746a9881.s1.eu.hivemq.cloud";
        const MQTT_PORT = 8884;
        const MQTT_USERNAME = "hivemq.webclient.1751385942895";
        const MQTT_PASSWORD = "R07c6iA#dG>W3lNj?sQ:";
        const MQTT_TOPIC_MODE = "KyoumaProject/mode";
        const MQTT_TOPIC_RFID = "KyoumaProject/rfid";
        
        // ==================== GLOBAL VARIABLES ====================
        let currentMethod = null;
        let isManualInput = false;
        let html5QrCode = null;
        let mqttClient = null;
        let rfidListenerActive = false;
        
        // ==================== MQTT SETUP ====================
        const clientId = "parking_keluar_" + Math.random().toString(16).substr(2, 8);
        mqttClient = new Paho.MQTT.Client(MQTT_BROKER, MQTT_PORT, "/mqtt", clientId);
        
        const connectOptions = {
            onSuccess: onMQTTConnect,
            onFailure: onMQTTFailure,
            userName: MQTT_USERNAME,
            password: MQTT_PASSWORD,
            useSSL: true,
            timeout: 10,
            keepAliveInterval: 30,
            cleanSession: true
        };
        
        mqttClient.onConnectionLost = onMQTTConnectionLost;
        mqttClient.onMessageArrived = onMQTTMessageArrived;
        
        // ==================== MQTT FUNCTIONS ====================
        function connectMQTT() {
            updateMQTTStatus('Connecting to MQTT...', false, true);
            try {
                mqttClient.connect(connectOptions);
            } catch (error) {
                console.error("MQTT Connection Error:", error);
                updateMQTTStatus('Connection Failed', false, false);
            }
        }
        
        function onMQTTConnect() {
            console.log("✅ Connected to MQTT Broker");
            updateMQTTStatus('MQTT Connected', true, false);
            
            mqttClient.subscribe(MQTT_TOPIC_MODE, {qos: 1});
            console.log("Subscribed to: " + MQTT_TOPIC_MODE);
            
            mqttClient.subscribe(MQTT_TOPIC_RFID, {qos: 1});
            console.log("Subscribed to: " + MQTT_TOPIC_RFID);
        }
        
        function onMQTTFailure(message) {
            console.error("❌ MQTT Connection Failed:", message.errorMessage);
            updateMQTTStatus('Connection Failed', false, false);
            setTimeout(connectMQTT, 5000);
        }
        
        function onMQTTConnectionLost(responseObject) {
            if (responseObject.errorCode !== 0) {
                console.error("❌ MQTT Connection Lost:", responseObject.errorMessage);
                updateMQTTStatus('Connection Lost', false, false);
                setTimeout(connectMQTT, 5000);
            }
        }
        
        function onMQTTMessageArrived(message) {
            console.log("📨 MQTT Message:");
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
        
        function handleModeMessage(payload) {
            console.log("🔄 Mode message:", payload);
            updateDeviceStatus(payload);
            
            const modeLower = payload.toLowerCase().trim();
            if (modeLower === "out") {
                console.log("✅ Mode: EXIT - Device ready for checkout");
            } else {
                console.log("⚠️ Mode: " + payload + " - Device not in exit mode");
            }
        }
        
        function handleRFIDMessage(payload) {
            console.log("📡 RFID message:", payload);
            
            // Only process RFID if we're in RFID waiting mode and device is in "out" mode
            if (!rfidListenerActive) {
                console.log("⚠️ RFID listener not active, ignoring...");
                return;
            }
            
            const deviceText = document.getElementById('deviceText').textContent;
            if (deviceText !== "Device Connected") {
                console.warn("⚠️ RFID detected but device not in OUT mode. Ignoring...");
                return;
            }
            
            console.log("✅ RFID Detected: " + payload + " - Processing checkout...");
            processRFIDCheckout(payload);
        }
        
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
        
        function updateDeviceStatus(mode) {
            const deviceStatus = document.getElementById('deviceStatus');
            const deviceIndicator = document.getElementById('deviceIndicator');
            const deviceText = document.getElementById('deviceText');
            
            const modeLower = mode.toLowerCase().trim();
            
            if (modeLower === "out") {
                deviceStatus.classList.add('device-connected');
                deviceIndicator.classList.add('connected');
                deviceText.textContent = "Device Connected";
                console.log("✅ Device Status: CONNECTED - OUT MODE");
            } else {
                deviceStatus.classList.remove('device-connected');
                deviceIndicator.classList.remove('connected');
                deviceText.textContent = "Device Not Connected";
                console.log("⚠️ Device Status: " + mode);
            }
        }
        
        // ==================== METHOD SELECTION ====================
        document.querySelectorAll('.method-card').forEach(card => {
            card.addEventListener('click', function() {
                const method = this.dataset.method;
                selectMethod(method);
            });
        });
        
        function selectMethod(method) {
            currentMethod = method;
            
            // Hide method selection
            document.getElementById('methodSelection').style.display = 'none';
            
            // Show appropriate input method
            if (method === 'manual') {
                showManualInput();
            } else if (method === 'qr') {
                showQRScanner();
            } else if (method === 'rfid') {
                showRFIDWaiting();
            }
        }
        
        function backToSelection() {
            // Stop any active scanners
            if (html5QrCode) {
                // Cek apakah scanner sedang berjalan atau dipause
                if (html5QrCode.getState() === Html5QrcodeScannerState.SCANNING || 
                    html5QrCode.getState() === Html5QrcodeScannerState.PAUSED) {
                    
                    html5QrCode.stop().then((ignore) => {
                        console.log("Scanner berhasil dihentikan.");
                    }).catch((err) => {
                        console.error("Gagal menghentikan scanner.", err);
                    });

                } else {
                    console.log("Scanner tidak sedang berjalan, tidak perlu di-stop.");
                }
            }
            
            // Deactivate RFID listener
            rfidListenerActive = false;
            
            // Hide all input methods
            document.getElementById('manualForm').classList.remove('show');
            document.getElementById('qrScanner').classList.remove('show');
            document.getElementById('rfidWaiting').classList.remove('show');
            
            // Show method selection
            document.getElementById('methodSelection').style.display = 'grid';
            
            currentMethod = null;
        }
        
        // ==================== MANUAL INPUT ====================
        function showManualInput() {
            isManualInput = true;
            document.getElementById('manualForm').classList.add('show');
        }
        
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const nomorKendaraan = document.getElementById('nomorKendaraan').value;
            
            const submitBtn = document.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Memproses...';
            submitBtn.disabled = true;
            
            // Fetch data from backend
            fetchParkingData(nomorKendaraan)
                .then(data => {
                    if (data.success) {
                        displayData(data.data);
                    } else {
                        alert(data.message || 'Data parkir tidak ditemukan!');
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan. Menggunakan data dummy...');
                    loadDummyData(nomorKendaraan);
                });
        });
        
        function fetchParkingData(nomorKendaraan) {
            const apiEndpoint = 'controllerOut.php';
            
            return fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'nomor_kendaraan=' + encodeURIComponent(nomorKendaraan)
            })
            .then(response => response.json())
            .catch(error => {
                console.error('Fetch error:', error);
                throw error;
            });
        }
        
        // ==================== QR SCANNER ====================
        function showQRScanner() {
            document.getElementById('qrScanner').classList.add('show');
            
            html5QrCode = new Html5Qrcode("qr-reader");
            
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                onQRScanSuccess,
                onQRScanError
            ).catch(err => {
                console.error("QR Scanner Error:", err);
                document.getElementById('qrStatus').textContent = "Gagal mengakses kamera!";
                document.getElementById('qrStatus').classList.add('error');
            });
        }
        
        function onQRScanSuccess(decodedText, decodedResult) {
            console.log("✅ QR Code detected:", decodedText);
            
            // Stop scanner
            html5QrCode.stop();
            
            // Update status
            document.getElementById('qrStatus').textContent = "QR Code terdeteksi! Memproses...";
            document.getElementById('qrStatus').classList.add('success');
            
            // Process QR data
            processQRData(decodedText);
        }
        
        function onQRScanError(error) {
            // Silent - normal scanning behavior
        }
        
        function stopQRScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    console.log("QR Scanner stopped");
                    html5QrCode = null;
                    backToSelection();
                }).catch(err => {
                    console.error("Error stopping QR scanner:", err);
                    backToSelection();
                });
            } else {
                backToSelection();
            }
        }
        
        function processQRData(qrText) {
            try {
                // Parse QR code data (assuming it's JSON from ticket)
                const ticketData = JSON.parse(qrText);
                
                // Fetch complete data from backend using ticket ID
                fetchParkingDataByTicket(ticketData.ticketId)
                    .then(data => {
                        if (data.success) {
                            displayData(data.data);
                        } else {
                            alert('Data parkir tidak ditemukan!');
                            backToSelection();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Use data from QR as fallback
                        displayDataFromQR(ticketData);
                    });
            } catch (error) {
                console.error("Error parsing QR data:", error);
                alert("QR Code tidak valid!");
                backToSelection();
            }
        }
        
        function fetchParkingDataByTicket(ticketId) {
            const apiEndpoint = 'controllerOut.php';
            
            return fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ticket_id=' + encodeURIComponent(ticketId)
            })
            .then(response => response.json());
        }
        
        function displayDataFromQR(ticketData) {
            const waktuKeluar = new Date();
            const waktuMasuk = new Date(ticketData.entryTime);
            
            const calculation = calculateParkingFee(
                ticketData.entryTime,
                waktuKeluar.toISOString()
            );
            
            const data = {
                id_struk: ticketData.ticketId,
                nomor_kendaraan: ticketData.plateNumber,
                waktu_masuk: formatDateTime(ticketData.entryTime),
                waktu_keluar: formatDateTime(waktuKeluar.toISOString()),
                durasi: calculation.durasiText,
                total_bayar: calculation.totalBayar.toString()
            };
            
            displayData(data);
        }
        
        // ==================== RFID WAITING ====================
        function showRFIDWaiting() {
            rfidListenerActive = true;
            document.getElementById('rfidWaiting').classList.add('show');
            console.log("📡 RFID listener activated");
        }
        
        function processRFIDCheckout(rfidData) {
            console.log("Processing RFID checkout:", rfidData);
            
            // Deactivate listener to prevent multiple triggers
            rfidListenerActive = false;
            
            // Fetch data using RFID
            fetchParkingDataByRFID(rfidData)
                .then(data => {
                    if (data.success) {
                        displayData(data.data);
                    } else {
                        alert('Data parkir dengan RFID ini tidak ditemukan!');
                        backToSelection();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengambil data!');
                    backToSelection();
                });
        }
        
        function fetchParkingDataByRFID(rfidData) {
            const apiEndpoint = 'controllerOut.php';
            
            return fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'rfid_data=' + encodeURIComponent(rfidData)
            })
            .then(response => response.json());
        }
        
        // ==================== DATA DISPLAY ====================
        function displayData(data) {
            // Hide form, show data
            document.getElementById('formContainer').style.display = 'none';
            document.getElementById('dataContainer').classList.add('show');
            
            // Display data
            document.getElementById('displayNomor').textContent = data.nomor_kendaraan;
            document.getElementById('displayWaktuMasuk').textContent = data.waktu_masuk;
            document.getElementById('displayWaktuKeluar').textContent = data.waktu_keluar;
            document.getElementById('displayDurasi').textContent = data.durasi;
            console.log('Total Bayar:', data.total_bayar);
            document.getElementById('displayTotal').textContent = 'Rp ' + data.total_bayar;
            document.getElementById('strukId').textContent = 'ID: ' + data.id_struk;
            
            // Load vehicle image if available
            if (data.foto_kendaraan) {
                document.getElementById('fotoKendaraan').src = data.foto_kendaraan;
            }
            
            // Generate barcode
            generateBarcode(data.id_struk);
        }
        
        function generateBarcode(idStruk) {
            try {
                JsBarcode("#barcode", idStruk, {
                    format: "CODE128",
                    width: 1.5,
                    height: 60,
                    displayValue: true,
                    fontSize: 12,
                    margin: 5
                });
            } catch(e) {
                console.error('Error generating barcode:', e);
            }
        }
        
        // ==================== BUKA GERBANG ====================
        document.getElementById('btnBukaGerbang').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.textContent;
            
            btn.disabled = true;
            btn.textContent = '⏳ Membuka Gerbang...';
            
            const idStruk = document.getElementById('strukId').textContent.replace('ID: ', '');
            
            bukaGerbang(idStruk)
                .then(response => {
                    // console.log('Buka Gerbang Response:', response);
                    if (response.success) {
                        btn.textContent = '✅ Gerbang Terbuka';
                        btn.style.backgroundColor = '#34a853';
                        
                        setTimeout(() => {
                            resetToForm();
                        }, 3000);
                    } else {
                        alert(response.message || 'Gagal membuka gerbang!');
                        btn.textContent = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat membuka gerbang!');
                    btn.textContent = originalText;
                    btn.disabled = false;
                });
        });
        
        function bukaGerbang(idStruk) {
            const apiEndpoint = 'controllerOut.php';
            
            return fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_struk=' + encodeURIComponent(idStruk)
            })
            .then(response => response.json())
            .catch(error => {
                console.log('Using dummy response for testing');
                return {
                    success: true,
                    message: 'Gerbang dibuka (dummy mode)'
                };
            });
        }
        
        function resetToForm() {
            document.getElementById('dataContainer').classList.remove('show');
            document.getElementById('formContainer').style.display = 'flex';
            
            document.getElementById('checkoutForm').reset();
            isManualInput = false;
            
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.textContent = 'Proses Checkout';
            submitBtn.disabled = false;
            
            backToSelection();
        }
        
        // ==================== UTILITY FUNCTIONS ====================
        function calculateParkingFee(waktuMasuk, waktuKeluar) {
            const masuk = new Date(waktuMasuk);
            const keluar = new Date(waktuKeluar);
            
            const diffMs = keluar - masuk;
            const diffMins = Math.floor(diffMs / 60000);
            const hours = Math.floor(diffMins / 60);
            const minutes = diffMins % 60;
            
            const totalHours = minutes > 0 ? hours + 1 : hours;
            
            let totalBayar = 0;
            if (totalHours === 0) {
                totalBayar = 1000;
            } else if (totalHours === 1) {
                totalBayar = 1000;
            } else {
                totalBayar = 1000 + ((totalHours - 1) * 2000);
            }
            
            let durasiText = '';
            if (hours > 0) durasiText += hours + ' jam';
            if (minutes > 0) durasiText += (hours > 0 ? ' ' : '') + minutes + ' menit';
            if (hours === 0 && minutes === 0) durasiText = 'Kurang dari 1 menit';
            
            return { durasiText, totalBayar, hours, minutes };
        }
        
        function formatDateTime(datetime) {
            const date = new Date(datetime);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        }
        
        function loadDummyData(nomorKendaraan) {
            const waktuMasuk = '2024-12-07 10:00:00';
            const waktuKeluar = '2024-12-07 14:30:00';
            
            const calculation = calculateParkingFee(waktuMasuk, waktuKeluar);
            
            const data = {
                id_struk: 'STR-20251207-001',
                nomor_kendaraan: nomorKendaraan,
                waktu_masuk: formatDateTime(waktuMasuk),
                waktu_keluar: formatDateTime(waktuKeluar),
                durasi: calculation.durasiText,
                total_bayar: calculation.totalBayar.toString()
            };
            
            displayData(data);
        }
        
        // ==================== INITIALIZATION ====================
        window.addEventListener('DOMContentLoaded', function() {
            console.log("🚀 Initializing Exit System...");
            connectMQTT();
        });
    </script>
</body>
</html>