<?php
header('Content-Type: application/json');
include 'config/db.php';

// CRITICAL: Set timezone untuk PHP dan MySQL
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

// Kapasitas parkir
$motor_capacity = 50;
$mobil_capacity = 50;

// Tarif parkir (sesuaikan dengan sistem Anda)
$tarif_motor = 2000; // per jam
$tarif_mobil = 5000; // per jam

// Get active vehicles
$q_motor = mysqli_query($conn, "SELECT * FROM parkir WHERE status='IN' AND jenis='Motor' ORDER BY jam_masuk ASC");
$q_mobil = mysqli_query($conn, "SELECT * FROM parkir WHERE status='IN' AND jenis='Mobil' ORDER BY jam_masuk ASC");

$motor_vehicles = [];
$mobil_vehicles = [];
$all_active_vehicles = [];

// Get current time in Asia/Jakarta timezone
$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));

// Collect motor data
$motor_slot = 1;
while ($row = mysqli_fetch_assoc($q_motor)) {
    // Create DateTime object dengan timezone
    $masuk = new DateTime($row['jam_masuk'], new DateTimeZone('Asia/Jakarta'));
    
    // Calculate difference
    $diff = $masuk->diff($now);
    
    $total_minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    $jam = $diff->h + ($diff->days * 24);
    $menit = $diff->i;
    
    if ($jam > 0) {
        $durasi = $jam . ' jam ' . $menit . ' menit';
    } else {
        $durasi = $menit . ' menit';
    }
    
    // Calculate estimated cost
    $hours = ceil($total_minutes / 60);
    if ($hours < 1) $hours = 1; // Minimum 1 jam
    $est_biaya = $hours * $tarif_motor;
    
    $slot_number = 'M' . str_pad($motor_slot, 2, '0', STR_PAD_LEFT);
    
    $vehicle_data = [
        'no_plat' => $row['no_plat'],
        'jam_masuk' => $masuk->format('d/m/Y H:i'),
        'durasi' => $durasi,
        'duration_hours' => $jam,
        'duration_minutes' => $total_minutes,
        'slot' => $slot_number,
        'jenis' => 'Motor',
        'est_biaya' => number_format($est_biaya, 0, ',', '.'),
        'barcode_id' => $row['barcode_id']
    ];
    
    $motor_vehicles[] = $vehicle_data;
    $all_active_vehicles[] = $vehicle_data;
    $motor_slot++;
}

// Collect mobil data
$mobil_slot = 1;
while ($row = mysqli_fetch_assoc($q_mobil)) {
    // Create DateTime object dengan timezone
    $masuk = new DateTime($row['jam_masuk'], new DateTimeZone('Asia/Jakarta'));
    
    // Calculate difference
    $diff = $masuk->diff($now);
    
    $total_minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    $jam = $diff->h + ($diff->days * 24);
    $menit = $diff->i;
    
    if ($jam > 0) {
        $durasi = $jam . ' jam ' . $menit . ' menit';
    } else {
        $durasi = $menit . ' menit';
    }
    
    // Calculate estimated cost
    $hours = ceil($total_minutes / 60);
    if ($hours < 1) $hours = 1; // Minimum 1 jam
    $est_biaya = $hours * $tarif_mobil;
    
    $slot_number = 'C' . str_pad($mobil_slot, 2, '0', STR_PAD_LEFT);
    
    $vehicle_data = [
        'no_plat' => $row['no_plat'],
        'jam_masuk' => $masuk->format('d/m/Y H:i'),
        'durasi' => $durasi,
        'duration_hours' => $jam,
        'duration_minutes' => $total_minutes,
        'slot' => $slot_number,
        'jenis' => 'Mobil',
        'est_biaya' => number_format($est_biaya, 0, ',', '.'),
        'barcode_id' => $row['barcode_id']
    ];
    
    $mobil_vehicles[] = $vehicle_data;
    $all_active_vehicles[] = $vehicle_data;
    $mobil_slot++;
}

// Generate slots for Motor
$motor_slots = [];
for ($i = 1; $i <= $motor_capacity; $i++) {
    $slot_number = 'M' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $index = $i - 1;
    
    if (isset($motor_vehicles[$index])) {
        $motor_slots[] = [
            'number' => $slot_number,
            'occupied' => true,
            'data' => $motor_vehicles[$index]
        ];
    } else {
        $motor_slots[] = [
            'number' => $slot_number,
            'occupied' => false,
            'data' => null
        ];
    }
}

// Generate slots for Mobil
$mobil_slots = [];
for ($i = 1; $i <= $mobil_capacity; $i++) {
    $slot_number = 'C' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $index = $i - 1;
    
    if (isset($mobil_vehicles[$index])) {
        $mobil_slots[] = [
            'number' => $slot_number,
            'occupied' => true,
            'data' => $mobil_vehicles[$index]
        ];
    } else {
        $mobil_slots[] = [
            'number' => $slot_number,
            'occupied' => false,
            'data' => null
        ];
    }
}

// Calculate statistics
$motor_occupied = count($motor_vehicles);
$motor_available = $motor_capacity - $motor_occupied;
$motor_percentage = round(($motor_occupied / $motor_capacity) * 100, 1);

$mobil_occupied = count($mobil_vehicles);
$mobil_available = $mobil_capacity - $mobil_occupied;
$mobil_percentage = round(($mobil_occupied / $mobil_capacity) * 100, 1);

// Prepare response
$response = [
    'success' => true,
    'timestamp' => $now->format('Y-m-d H:i:s'),
    'server_time' => $now->format('d/m/Y H:i:s'),
    'motor' => [
        'capacity' => $motor_capacity,
        'occupied' => $motor_occupied,
        'available' => $motor_available,
        'percentage' => $motor_percentage,
        'slots' => $motor_slots
    ],
    'mobil' => [
        'capacity' => $mobil_capacity,
        'occupied' => $mobil_occupied,
        'available' => $mobil_available,
        'percentage' => $mobil_percentage,
        'slots' => $mobil_slots
    ],
    'activeVehicles' => $all_active_vehicles
];

echo json_encode($response);
?>