<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'config/db.php';

// Ambil data dari database
if (!isset($_GET['id'])) {
    die("❌ ID tidak ditemukan!");
}

$id = intval($_GET['id']);
$query = "SELECT * FROM parkir WHERE id = $id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("❌ Data tidak ditemukan di database!");
}

$data = mysqli_fetch_assoc($result);
$jamMasuk = date('d/m/Y H:i', strtotime($data['jam_masuk']));
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Karcis Parkir</title>
<style>
    @media print {
        @page {
            size: 58mm auto; /* Ukuran thermal printer */
            margin: 0;
        }
        body {
            margin: 0;
            -webkit-print-color-adjust: exact;
        }
        button { display: none; }
    }

    body {
        font-family: 'Courier New', Courier, monospace;
        background: #f0f2f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    .ticket {
        background: #f9fafc;
        border: 1px solid #cbd5e1;
        padding: 18px 20px;
        width: 280px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
        border-radius: 10px;
        color: #1e293b;
        font-family: 'Courier New', Courier, monospace;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    .header-logo {
        font-weight: 700;
        font-size: 18px;
        color: #1e40af;
        letter-spacing: 1.5px;
        margin-bottom: 6px;
    }

    .header-line {
        height: 1.5px;
        background-color: #1e40af;
        margin-bottom: 15px;
        border-radius: 1px;
    }

    .subtitle {
        font-size: 12px;
        margin-bottom: 15px;
        color: #64748b;
        font-weight: 500;
        font-family: 'Courier New', Courier, monospace;
        letter-spacing: 0.5px;
    }

    hr {
        border: none;
        border-top: 0.8px solid #cbd5e1;
        margin: 12px 0;
    }

    .info {
        text-align: left;
        font-size: 13px;
        margin: 10px 0;
        line-height: 1.5;
        color: #334155;
        font-weight: 600;
        font-family: 'Courier New', Courier, monospace;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    .info strong {
        float: right;
        text-align: right;
        font-weight: 700;
        color: #1e293b;
    }

    .qr {
        margin: 18px auto 0;
        width: 120px;
        height: 120px;
    }

    .qr img {
        display: block;
        margin: 0 auto;
        width: 120px;
        height: 120px;
    }

    .footer {
        font-size: 10px;
        margin-top: 15px;
        color: #475569;
        font-style: italic;
        letter-spacing: 0.5px;
        font-family: 'Courier New', Courier, monospace;
        font-weight: 500;
    }

    button {
        margin-top: 20px;
        background-color: #1e40af;
        color: white;
        border: none;
        padding: 10px 0;
        width: 100%;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 700;
        font-size: 14px;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #1e3a8a;
    }
</style>
</head>
<body>
    <div class="ticket">
        <div class="header-logo">E-PARKING SYSTEM</div>
        <div class="header-line"></div>
        <div class="subtitle">Sistem Parkir Otomatis</div>

        <div class="info">
            No. Plat <strong><?= htmlspecialchars($data['no_plat']) ?></strong><br>
            Jenis <strong><?= htmlspecialchars($data['jenis']) ?></strong><br>
            Jam Masuk <strong><?= $jamMasuk ?></strong><br>
            Kode Tiket <strong><?= htmlspecialchars($data['barcode_id']) ?></strong>
        </div>

        <hr>

        <div class="qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($data['barcode_id']) ?>&size=120x120" alt="QR Code" crossorigin="anonymous">
        </div>

        <div class="footer">
            Terima kasih telah menggunakan e-Parking.<br>
            Simpan tiket ini untuk keluar area parkir.
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        // Create and automatically trigger PDF download on page load
        window.addEventListener('load', () => {
            const { jsPDF } = window.jspdf;
            const ticket = document.querySelector('.ticket');

            html2canvas(ticket, { scale: 2, useCORS: true }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF({
                    unit: 'pt',
                    format: [canvas.width, canvas.height]
                });
                pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
                pdf.save('karcis_<?= htmlspecialchars($data['no_plat']) ?>.pdf');
                // Since buttons are removed, no need to hide them here.
            });
        });
    </script>
</body>
</html>