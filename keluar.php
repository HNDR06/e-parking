<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Parking - Kendaraan Keluar</title>
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

        /* Form Input Manual */
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
            max-width: 500px;
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

        /* Layout Data (Hidden by default) */
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
    <div class="header">
        <div style="width: 40px; height: 40px; background-color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4285f4; font-weight: bold; font-size: 20px;">P</div>
        <h1>E-PARKING - Kendaraan Keluar</h1>
    </div>

    <!-- FORM INPUT MANUAL -->
    <div class="form-container" id="formContainer">
        <div class="form-box">
            <h2>Kendaraan Keluar</h2>
            <p>Masukkan nomor kendaraan untuk checkout</p>
            
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
                    Input Manual
                </button>
            </form>
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
                
                <!-- Button Buka Gerbang (hanya untuk input manual) -->
                <button id="btnBukaGerbang" class="btn-buka-gerbang" style="display: none;">
                    🚪 Buka Gerbang
                </button>
            </div>
        </div>
    </div>

    <!-- JsBarcode Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>
    
    <script>
        // Track if data is from manual input
        let isManualInput = false;
        
        // Check URL parameters on page load
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Check if data exists in URL (from RFID)
            if (urlParams.has('id_struk')) {
                isManualInput = false;
                loadDataFromURL(urlParams);
            }
        });
        
        // Handle button Buka Gerbang
        document.getElementById('btnBukaGerbang').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.textContent;
            
            // Disable button and show loading
            btn.disabled = true;
            btn.textContent = '⏳ Membuka Gerbang...';
            
            // Get ID struk for reference
            const idStruk = document.getElementById('strukId').textContent.replace('ID: ', '');
            
            // Send request to backend
            bukaGerbang(idStruk)
                .then(response => {
                    if (response.success) {
                        btn.textContent = '✅ Gerbang Terbuka';
                        btn.style.backgroundColor = '#34a853';
                        
                        // Wait 2 seconds then reset to form
                        setTimeout(() => {
                            resetToForm();
                        }, 2000);
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

        // Handle manual form submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            isManualInput = true; // Mark as manual input
            
            const nomorKendaraan = document.getElementById('nomorKendaraan').value;
            
            // Show loading state
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
                    alert('Terjadi kesalahan saat mengambil data. Menggunakan data dummy...');
                    // Fallback to dummy data
                    loadDummyData(nomorKendaraan);
                });
        });
        
        function bukaGerbang(idStruk) {
            // TODO: Ganti dengan endpoint backend kamu untuk buka gerbang
            const apiEndpoint = 'api_buka_gerbang.php'; // Sesuaikan dengan nama file PHP kamu
            
            return fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_struk=' + encodeURIComponent(idStruk)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .catch(error => {
                console.error('Fetch error:', error);
                // Temporary: return success for testing if backend not ready
                console.log('Using dummy response for testing');
                return {
                    success: true,
                    message: 'Gerbang berhasil dibuka (dummy mode - backend belum siap)'
                };
            });
        }
        
        function resetToForm() {
            // Hide data container, show form
            document.getElementById('dataContainer').classList.remove('show');
            document.getElementById('formContainer').style.display = 'flex';
            
            // Reset form
            document.getElementById('checkoutForm').reset();
            document.getElementById('nomorKendaraan').value = '';
            
            // Reset manual input flag
            isManualInput = false;
            
            // Hide button buka gerbang
            document.getElementById('btnBukaGerbang').style.display = 'none';
            
            // Reset button state
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.textContent = 'Input Manual';
            submitBtn.disabled = false;
        }
        
        function fetchParkingData(nomorKendaraan) {
            // TODO: Ganti dengan endpoint backend kamu
            const apiEndpoint = 'api_keluar.php'; // Sesuaikan dengan nama file PHP kamu
            
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

        function loadDataFromURL(urlParams) {
            const waktuMasuk = urlParams.get('waktu_masuk');
            const waktuKeluar = urlParams.get('waktu_keluar');
            
            // Calculate duration and tariff if datetime provided
            let calculation;
            if (waktuMasuk && waktuKeluar) {
                calculation = calculateParkingFee(waktuMasuk, waktuKeluar);
            }
            
            const data = {
                id_struk: urlParams.get('id_struk'),
                nomor_kendaraan: urlParams.get('nomor_kendaraan'),
                waktu_masuk: waktuMasuk ? formatDateTime(waktuMasuk) : urlParams.get('waktu_masuk'),
                waktu_keluar: waktuKeluar ? formatDateTime(waktuKeluar) : urlParams.get('waktu_keluar'),
                durasi: calculation ? calculation.durasiText : urlParams.get('durasi'),
                total_bayar: calculation ? calculation.totalBayar.toString() : urlParams.get('total_bayar')
            };
            
            displayData(data);
        }

        function loadDummyData(nomorKendaraan) {
            // Dummy data untuk testing
            const waktuMasuk = '2024-12-07 10:00:00';
            const waktuKeluar = '2024-12-07 14:30:00';
            
            // Calculate duration and tariff
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
        
        function calculateParkingFee(waktuMasuk, waktuKeluar) {
            // Parse datetime strings
            const masuk = new Date(waktuMasuk);
            const keluar = new Date(waktuKeluar);
            
            // Calculate difference in milliseconds
            const diffMs = keluar - masuk;
            
            // Convert to hours and minutes
            const diffMins = Math.floor(diffMs / 60000);
            const hours = Math.floor(diffMins / 60);
            const minutes = diffMins % 60;
            
            // Calculate total hours (round up if there are remaining minutes)
            const totalHours = minutes > 0 ? hours + 1 : hours;
            
            // Calculate tariff: Rp 1.000 first hour, Rp 2.000 next hours
            let totalBayar = 0;
            if (totalHours === 0) {
                totalBayar = 1000; // Minimum charge for less than 1 hour
            } else if (totalHours === 1) {
                totalBayar = 1000;
            } else {
                totalBayar = 1000 + ((totalHours - 1) * 2000);
            }
            
            // Format duration text
            let durasiText = '';
            if (hours > 0) {
                durasiText += hours + ' jam';
            }
            if (minutes > 0) {
                durasiText += (hours > 0 ? ' ' : '') + minutes + ' menit';
            }
            if (hours === 0 && minutes === 0) {
                durasiText = 'Kurang dari 1 menit';
            }
            
            return {
                durasiText: durasiText,
                totalBayar: totalBayar,
                hours: hours,
                minutes: minutes
            };
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

        function displayData(data) {
            // Hide form, show data
            document.getElementById('formContainer').style.display = 'none';
            document.getElementById('dataContainer').classList.add('show');
            
            // Display data
            document.getElementById('displayNomor').textContent = data.nomor_kendaraan;
            document.getElementById('displayWaktuMasuk').textContent = data.waktu_masuk;
            document.getElementById('displayWaktuKeluar').textContent = data.waktu_keluar;
            document.getElementById('displayDurasi').textContent = data.durasi;
            document.getElementById('displayTotal').textContent = 'Rp ' + parseInt(data.total_bayar).toLocaleString('id-ID');
            document.getElementById('strukId').textContent = 'ID: ' + data.id_struk;
            
            // Generate barcode from ID Struk
            generateBarcode(data.id_struk);
            
            // Show button "Buka Gerbang" only for manual input
            if (isManualInput) {
                document.getElementById('btnBukaGerbang').style.display = 'block';
            }
        }
        
        function generateBarcode(idStruk) {
            try {
                JsBarcode("#barcode", idStruk, {
                    format: "CODE128",
                    width: 1.5,
                    height: 60,
                    displayValue: true,
                    fontSize: 12,
                    margin: 5,
                    marginTop: 5,
                    marginBottom: 5
                });
            } catch(e) {
                console.error('Error generating barcode:', e);
                document.getElementById('barcode').innerHTML = '<text>Error generating barcode</text>';
            }
        }
    </script>
</body>
</html>