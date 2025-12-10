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
        $rfidData = isset($ticketData['rfidData']) ? $ticketData['rfidData'] : null;

        $sql = "INSERT INTO parkir (no_plat, jenis, jam_masuk, barcode_id, status, petugas, referee_no) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $status = 'IN';
        $petugas = 'Hendrawan';
        $refereeNo = $rfidData;
        $stmt->bind_param(
            "sssssss",
            $ticketData['plateNumber'],
            $ticketData['vehicleType'],
            $ticketData['entryTime'],
            $ticketData['ticketId'],
            $status,
            $petugas,
            $refereeNo
        );
        
        $result = $stmt->execute();
        
        if ($result) {
            error_log("✅ Ticket saved to database: " . $ticketData['ticketId']);
            return [
                'success' => true,
                'message' => 'Ticket saved successfully',
                'ticket_id' => $ticketData['ticketId']
            ];
        } else {
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
                
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['error' => 'No action specified']);
    }
    exit;
}

// Update bagian API handling di paling bawah
if (basename($_SERVER['PHP_SELF']) == 'controllerInput.php') {
    header('Content-Type: application/json');
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            // ... case stats, active, ticket tetap sama ...
            case 'stats':
                echo json_encode(getParkingStatistics());
                break;
            case 'active':
                echo json_encode(getActiveTickets());
                break;
            case 'ticket':
                if (isset($_GET['id'])) echo json_encode(getTicketById($_GET['id']));
                else echo json_encode(['error' => 'Ticket ID required']);
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['error' => 'No action specified']);
    }
    exit;
}

// Function to update ticket image
function updateTicketImage($ticketId, $imagePath) {
    global $conn; // atau sesuaikan dengan DB connection Anda
    
    try {
        $stmt = $conn->prepare("UPDATE parkir SET image_path = ? WHERE barcode_id = ?");
        $stmt->bind_param("ss", $imagePath, $ticketId);
        $result = $stmt->execute();
        $stmt->close();
        
        return [
            'success' => $result,
            'message' => $result ? 'Image updated' : 'Failed to update'
        ];
    } catch (Exception $e) {
        error_log("Update image error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>