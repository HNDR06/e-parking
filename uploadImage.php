<?php
// updatePhoto.php
// Update foto kendaraan di database berdasarkan ticket_id

header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';

// Check if required parameters are present
if (!isset($_POST['ticket_id']) || !isset($_POST['photo'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ticket ID and photo path required'
    ]);
    exit;
}

$ticketId = $_POST['ticket_id'];
$photoPath = $_POST['photo'];

try {
    // Update database with photo path
    $sql = "UPDATE parkir SET foto_kendaraan = ? WHERE barcode_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $photoPath, $ticketId);
    $result = $stmt->execute();
    
    if ($result) {
        error_log("✅ Photo path updated in database for ticket: " . $ticketId);
        echo json_encode([
            'success' => true,
            'message' => 'Photo path updated successfully',
            'ticket_id' => $ticketId,
            'photo_path' => $photoPath
        ]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("❌ Update photo error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>