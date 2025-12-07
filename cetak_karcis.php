<?php
require_once 'config/db.php';

$ticketId = isset($_GET['ticket']) ? $_GET['ticket'] : null;

if (!$ticketId) {
    die('Tiket tidak ditemukan');
}

// Get ticket data from database
$stmt = $conn->prepare("SELECT * FROM parkir WHERE barcode_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("s", $ticketId);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();

if (!$ticket) {
    die('Data tiket tidak ditemukan');
}

$plateNumber = $ticket['no_plat'];
$vehicleType = $ticket['jenis'];
$entryTime = date('d/m/Y H:i:s', strtotime($ticket['jam_masuk']));
$entryDate = date('d/m/Y', strtotime($ticket['jam_masuk']));
$entryTimeOnly = date('H:i:s', strtotime($ticket['jam_masuk']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karcis Parkir - <?php echo htmlspecialchars($ticketId); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .ticket-wrapper {
            background: white;
            width: 58mm;
            padding: 20px 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        /* Perforated edges */
        .ticket-wrapper::before,
        .ticket-wrapper::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            height: 8px;
            background-image: radial-gradient(circle, #f5f5f5 30%, transparent 30%);
            background-size: 8px 8px;
            background-position: 4px 0;
        }

        .ticket-wrapper::before {
            top: -4px;
        }

        .ticket-wrapper::after {
            bottom: -4px;
        }

        .ticket-header {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 2px dashed #ddd;
            margin-bottom: 12px;
        }

        .ticket-logo {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }

        .ticket-title {
            font-size: 10px;
            color: #666;
            letter-spacing: 1px;
        }

        .ticket-body {
            padding: 8px 0;
        }

        .ticket-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 9px;
            line-height: 1.5;
        }

        .ticket-label {
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
        }

        .ticket-value {
            color: #000;
            font-weight: 700;
            text-align: right;
        }

        .ticket-id {
            text-align: center;
            padding: 12px 0;
            margin: 12px 0;
            border-top: 2px dashed #ddd;
            border-bottom: 2px dashed #ddd;
        }

        .ticket-id-label {
            font-size: 8px;
            color: #666;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }

        .ticket-id-value {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #000;
        }

        .ticket-qr {
            text-align: center;
            padding: 12px 0;
            margin: 12px 0;
            border-top: 2px dashed #ddd;
        }

        .qr-code-image {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            display: block;
            border: 2px solid #ddd;
            padding: 4px;
            background: white;
        }

        .ticket-footer {
            text-align: center;
            padding-top: 12px;
            border-top: 2px dashed #ddd;
        }

        .ticket-notes {
            font-size: 7px;
            color: #999;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .ticket-website {
            font-size: 8px;
            color: #666;
            font-weight: 600;
        }

        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'JetBrains Mono', monospace;
        }

        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(37, 99, 235, 0.4);
        }

        .print-button:active {
            transform: translateY(0);
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .ticket-wrapper {
                box-shadow: none;
                margin: 0;
                padding: 10mm 6mm;
            }

            .ticket-wrapper::before,
            .ticket-wrapper::after {
                display: none;
            }

            .print-button {
                display: none;
            }
        }

        @page {
            size: 58mm auto;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="ticket-wrapper">
        <!-- Header -->
        <div class="ticket-header">
            <div class="ticket-logo">E-PARKING</div>
            <div class="ticket-title">TIKET PARKIR</div>
        </div>

        <!-- Body -->
        <div class="ticket-body">
            <div class="ticket-row">
                <span class="ticket-label">Tanggal:</span>
                <span class="ticket-value"><?php echo $entryDate; ?></span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Waktu:</span>
                <span class="ticket-value"><?php echo $entryTimeOnly; ?></span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Plat No:</span>
                <span class="ticket-value"><?php echo htmlspecialchars($plateNumber); ?></span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Jenis:</span>
                <span class="ticket-value"><?php echo strtoupper($vehicleType); ?></span>
            </div>
        </div>

        <!-- Ticket ID -->
        <div class="ticket-id">
            <div class="ticket-id-label">NOMOR TIKET</div>
            <div class="ticket-id-value"><?php echo htmlspecialchars($ticketId); ?></div>
        </div>

        <!-- QR Code -->
        <div class="ticket-qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo urlencode($ticketId); ?>" 
                 alt="QR Code" 
                 class="qr-code-image">
        </div>

        <!-- Footer -->
        <div class="ticket-footer">
            <div class="ticket-notes">
                Simpan tiket ini dengan baik.<br>
                Tunjukkan saat keluar parkir.<br>
                Kehilangan tiket dikenakan denda.
            </div>
            <div class="ticket-website">www.e-parking.com</div>
        </div>
    </div>

    <button class="print-button" onclick="window.print()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
            <path d="M6 14h12v8H6z"/>
        </svg>
        Cetak Tiket
    </button>

    <script>
        // Auto print on load (optional, bisa diaktifkan jika ingin auto print)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // };

        // Close window after print
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>
</html>