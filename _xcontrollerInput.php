<?php
// controllerInput.php
// Controller untuk handle database operations

// Include database configuration
require_once __DIR__ . '/config/db.php';

// Function to save ticket to database
function saveTicketToDatabase($ticketData) {
    global $conn;
    
    if (!$conn || $conn->connect_error) {
        return [
            'success' => false,
            'message' => 'Database connection failed'
        ];
    }
    
    try {
        // Cek apakah ada data RFID dan foto
        $rfidData = isset($ticketData['rfidData']) ? $ticketData['rfidData'] : null;
        $fotoKendaraan = isset($ticketData['photo']) ? $ticketData['photo'] : null;
        
        $sql = "INSERT INTO parkir (no_plat, jenis, jam_masuk, barcode_id, status, petugas, rfid_data, foto_kendaraan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("❌ PREPARE FAILED: " . $conn->error);
            return [
                'success' => false,
                'message' => 'Prepare failed: ' . $conn->error
            ];
        }
        
        $status = 'IN';
        $petugas = 'Hendrawan';
        
        $stmt->bind_param(
            "ssssssss",
            $ticketData['plateNumber'],
            $ticketData['vehicleType'],
            $ticketData['entryTime'],
            $ticketData['ticketId'],
            $status,
            $petugas,
            $rfidData,
            $fotoKendaraan
        );
        
        $result = $stmt->execute();
        
        if ($result) {
            $logMessage = "✅ Ticket saved to database: " . $ticketData['ticketId'];
            if ($rfidData) {
                $logMessage .= " (RFID: " . $rfidData . ")";
            }
            if ($fotoKendaraan) {
                $logMessage .= " (Photo: " . $fotoKendaraan . ")";
            }
            error_log($logMessage);
            
            return [
                'success' => true,
                'message' => 'Ticket saved successfully',
                'ticket_id' => $ticketData['ticketId'],
                'insert_id' => $stmt->insert_id
            ];
        } else {
            error_log("❌ EXECUTE FAILED: " . $stmt->error);
            return [
                'success' => false,
                'message' => 'Failed to save ticket: ' . $stmt->error
            ];
        }
        
    } catch(Exception $e) {
        error_log("❌ Database Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Function to get ticket by ID
function getTicketById($ticketId) {
    global $conn;
    
    if (!$conn || $conn->connect_error) {
        return null;
    }
    
    try {
        $sql = "SELECT * FROM parkir WHERE barcode_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ticketId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch(Exception $e) {
        error_log("Database Error: " . $e->getMessage());
        return null;
    }
}

// Function to get ticket by RFID
function getTicketByRFID($rfidData) {
    global $conn;
    
    if (!$conn || $conn->connect_error) {
        return null;
    }
    
    try {
        $sql = "SELECT * FROM parkir WHERE rfid_data = ? AND status = 'IN' ORDER BY jam_masuk DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $rfidData);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch(Exception $e) {
        error_log("Database Error: " . $e->getMessage());
        return null;
    }
}

// Function to get all active tickets
function getActiveTickets() {
    global $conn;
    
    if (!$conn || $conn->connect_error) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM parkir WHERE status = 'IN' ORDER BY jam_masuk DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch(Exception $e) {
        error_log("Database Error: " . $e->getMessage());
        return [];
    }
}

// Function to update ticket status (untuk checkout nanti)
function updateTicketStatus($ticketId, $status, $exitTime = null) {
    global $conn;
    
    if (!$conn || $conn->connect_error) {
        return false;
    }
    
    try {
        if ($exitTime) {
            $sql = "UPDATE parkir SET status = ?, jam_keluar = ? WHERE barcode_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $status, $exitTime, $ticketId);
        } else {
            $sql = "UPDATE parkir SET status = ? WHERE barcode_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $status, $ticketId);
        }
        
        return $stmt->execute();
    } catch(Exception $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

// Function to get statistics (optional - untuk dashboard)
function getParkingStatistics() {
    global $conn;
    
    if (!$conn || $conn->connect_error) {
        return null;
    }
    
    try {
        $stats = [];
        
        // Total active tickets
        $sql = "SELECT COUNT(*) as total FROM parkir WHERE status = 'IN'";
        $result = $conn->query($sql);
        $stats['active_tickets'] = $result->fetch_assoc()['total'];
        
        // Total tickets today
        $sql = "SELECT COUNT(*) as total FROM parkir WHERE DATE(jam_masuk) = CURDATE()";
        $result = $conn->query($sql);
        $stats['today_tickets'] = $result->fetch_assoc()['total'];
        
        // Total motor vs mobil today
        $sql = "SELECT jenis, COUNT(*) as total FROM parkir 
                WHERE DATE(jam_masuk) = CURDATE() 
                GROUP BY jenis";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $stats['vehicle_' . strtolower($row['jenis'])] = $row['total'];
        }
        
        // Total RFID entries today
        $sql = "SELECT COUNT(*) as total FROM parkir 
                WHERE DATE(jam_masuk) = CURDATE() AND rfid_data IS NOT NULL";
        $result = $conn->query($sql);
        $stats['rfid_entries_today'] = $result->fetch_assoc()['total'];
        
        // Total manual entries today
        $sql = "SELECT COUNT(*) as total FROM parkir 
                WHERE DATE(jam_masuk) = CURDATE() AND rfid_data IS NULL";
        $result = $conn->query($sql);
        $stats['manual_entries_today'] = $result->fetch_assoc()['total'];
        
        return $stats;
    } catch(Exception $e) {
        error_log("Database Error: " . $e->getMessage());
        return null;
    }
}

// If accessed directly, return JSON response
if (basename($_SERVER['PHP_SELF']) == 'controllerInput.php') {
    header('Content-Type: application/json');
    
    // Handle API requests
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'stats':
                echo json_encode(getParkingStatistics());
                break;
                
            case 'active':
                echo json_encode(getActiveTickets());
                break;
                
            case 'ticket':
                if (isset($_GET['id'])) {
                    echo json_encode(getTicketById($_GET['id']));
                } else {
                    echo json_encode(['error' => 'Ticket ID required']);
                }
                break;
                
            case 'rfid':
                if (isset($_GET['rfid'])) {
                    echo json_encode(getTicketByRFID($_GET['rfid']));
                } else {
                    echo json_encode(['error' => 'RFID data required']);
                }
                break;
                
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['error' => 'No action specified']);
    }
    exit;
}
?>