<?php
session_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/sales_auth.php';

    if (!isset($_SESSION['username'])) {
        throw new Exception('Authentication required');
    }

    $sales = new SalesReport();

    // API request handling
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['action'])) {
            throw new Exception('Missing action parameter');
        }

        switch ($_GET['action']) {
            case 'getSales':
                if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
                    throw new Exception('Missing date parameters');
                }
                $result = $sales->getSales($_GET['start_date'], $_GET['end_date']);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'generatePDF':
                if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
                    throw new Exception('Missing date parameters');
                }
                $sales->generatePDF($_GET['start_date'], $_GET['end_date']);
                // PDF generation handles its own output
                break;
            
            default:
                throw new Exception('Invalid action');
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($data)) {
            throw new Exception('No data received');
        }

        if (!isset($data['action'])) {
            throw new Exception('Missing action parameter');
        }

        switch ($data['action']) {
            case 'deleteSale':
                if (!isset($data['id'])) {
                    throw new Exception('Missing sale ID');
                }
                $result = $sales->deleteSale($data['id']);
                echo json_encode(['success' => true, 'deleted' => $result]);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>