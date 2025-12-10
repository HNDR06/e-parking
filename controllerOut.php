<?php
// Include database connection
require_once __DIR__ . '/config/db.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $response = ['success' => false, 'message' => '', 'data' => null];
        
        if (isset($_POST['nomor_kendaraan'])) {
            // Manual input by plate number
            $plateNo = $conn->real_escape_string($_POST['nomor_kendaraan']);

            $query = "SELECT barcode_id, no_plat, jenis, jam_masuk, 
                            jam_keluar, durasi_menit, biaya, image_path 
                     FROM parkir 
                     WHERE no_plat = '$plateNo' 
                     AND jam_keluar IS NULL 
                     ORDER BY jam_masuk DESC 
                     LIMIT 1";
            
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                
                // Hitung durasi dan biaya
                $waktuMasuk = new DateTime($data['jam_masuk']);
                $waktuKeluar = new DateTime();
                $durasi = $waktuMasuk->diff($waktuKeluar);
                
                $jam = $durasi->h + ($durasi->days * 24);
                $menit = $durasi->i;
                $durasiText = $jam . ' jam ' . $menit . ' menit';
                
                // Hitung biaya (contoh: Rp 2000/jam untuk motor, Rp 3000/jam untuk mobil)
                $tarifPerJam = ($data['jenis'] == 'Motor') ? 2000 : 3000;
                $totalJam = $jam + ($menit > 0 ? 1 : 0); // Round up
                $totalBayar = $totalJam * $tarifPerJam;
                
                // Update database dengan waktu keluar
                $updateQuery = "UPDATE parkir 
                               SET jam_keluar = NOW(), 
                                   durasi_menit = ($jam * 60 + $menit),
                                   biaya = $totalBayar 
                               WHERE barcode_id = '{$data['barcode_id']}'";
                
                if ($conn->query($updateQuery)) {
                    $response['success'] = true;
                    $response['message'] = 'Data berhasil ditemukan';
                    $response['data'] = [
                        'id_struk' => $data['barcode_id'],
                        'nomor_kendaraan' => $data['no_plat'],
                        'tipe_kendaraan' => $data['jenis'],
                        'waktu_masuk' => date('d/m/Y H:i', strtotime($data['jam_masuk'])),
                        'waktu_keluar' => date('d/m/Y H:i'),
                        'durasi' => $durasiText,
                        'total_bayar' => number_format($totalBayar, 0, ',', '.'),
                        'foto_kendaraan' => $data['image_path'] ?? ''
                    ];
                } else {
                    $response['message'] = 'Gagal update data: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Kendaraan tidak ditemukan atau sudah keluar';
            }
        } 
        elseif (isset($_POST['ticket_id'])) {
            // QR Code scan
            $ticketId = $conn->real_escape_string($_POST['ticket_id']);
            
            $query = "SELECT barcode_id, no_plat, jenis, jam_masuk, 
                            jam_keluar, durasi_menit, biaya, image_path 
                     FROM parkir 
                     WHERE barcode_id = '$ticketId' 
                     AND jam_keluar IS NULL
                     order BY jam_masuk DESC
                     LIMIT 1";
            
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                
                // Hitung durasi dan biaya (sama seperti di atas)
                $waktuMasuk = new DateTime($data['jam_masuk']);
                $waktuKeluar = new DateTime();
                $durasi = $waktuMasuk->diff($waktuKeluar);
                
                $jam = $durasi->h + ($durasi->days * 24);
                $menit = $durasi->i;
                $durasiText = $jam . ' jam ' . $menit . ' menit';
                
                $tarifPerJam = ($data['jenis'] == 'Motor') ? 2000 : 3000;
                $totalJam = $jam + ($menit > 0 ? 1 : 0);
                $totalBayar = $totalJam * $tarifPerJam;
                
                // Update database
                $updateQuery = "UPDATE parkir 
                               SET jam_keluar = NOW(), 
                                   durasi_menit = ($jam * 60 + $menit),
                                   biaya = $totalBayar 
                               WHERE barcode_id = '$ticketId'";
                
                if ($conn->query($updateQuery)) {
                    $response['success'] = true;
                    $response['message'] = 'Data berhasil ditemukan';
                    $response['data'] = [
                        'id_struk' => $data['barcode_id'],
                        'nomor_kendaraan' => $data['no_plat'],
                        'tipe_kendaraan' => $data['jenis'],
                        'waktu_masuk' => date('d/m/Y H:i', strtotime($data['jam_masuk'])),
                        'waktu_keluar' => date('d/m/Y H:i'),
                        'durasi' => $durasiText,
                        'total_bayar' => number_format($totalBayar, 0, ',', '.'),
                        'foto_kendaraan' => $data['image_path'] ?? ''
                    ];
                } else {
                    $response['message'] = 'Gagal update data: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Tiket tidak ditemukan atau sudah digunakan';
            }
        }
        elseif (isset($_POST['rfid_data'])) {
            // RFID tap
            $rfidData = $conn->real_escape_string($_POST['rfid_data']);
            
            $query = "SELECT barcode_id, no_plat, jenis, jam_masuk, 
                            jam_keluar, durasi_menit, biaya, image_path 
                     FROM parkir 
                     WHERE referee_no = '$rfidData' 
                     AND jam_keluar IS NULL
                     order BY jam_masuk DESC
                     LIMIT 1";
            
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                
                // Hitung durasi dan biaya
                $waktuMasuk = new DateTime($data['jam_masuk']);
                $waktuKeluar = new DateTime();
                $durasi = $waktuMasuk->diff($waktuKeluar);
                
                $jam = $durasi->h + ($durasi->days * 24);
                $menit = $durasi->i;
                $durasiText = $jam . ' jam ' . $menit . ' menit';
                
                $tarifPerJam = ($data['jenis'] == 'Motor') ? 2000 : 3000;
                $totalJam = $jam + ($menit > 0 ? 1 : 0);
                $totalBayar = $totalJam * $tarifPerJam;
                
                // Update database
                $updateQuery = "UPDATE parkir 
                               SET jam_keluar = NOW(), 
                                   durasi_menit = ($jam * 60 + $menit),
                                   biaya = $totalBayar 
                               WHERE barcode_id = '{$data['barcode_id']}'";
                
                if ($conn->query($updateQuery)) {
                    $response['success'] = true;
                    $response['message'] = 'Data berhasil ditemukan';
                    $response['data'] = [
                        'id_struk' => $data['barcode_id'],
                        'nomor_kendaraan' => $data['no_plat'],
                        'tipe_kendaraan' => $data['jenis'],
                        'waktu_masuk' => date('d/m/Y H:i', strtotime($data['jam_masuk'])),
                        'waktu_keluar' => date('d/m/Y H:i'),
                        'durasi' => $durasiText,
                        'total_bayar' => number_format($totalBayar, 0, ',', '.'),
                        'foto_kendaraan' => $data['image_path'] ?? ''
                    ];
                } else {
                    $response['message'] = 'Gagal update data: ' . $conn->error;
                }
            } else {
                $response['message'] = 'RFID tidak terdaftar atau sudah keluar';
            }
        } else {
            $response['success'] = true;
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'data' => null
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan',
        'data' => null
    ]);
}

// Tutup koneksi
$conn->close();
?>