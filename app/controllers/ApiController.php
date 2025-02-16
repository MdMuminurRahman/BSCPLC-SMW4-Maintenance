<?php
class ApiController {
    private $db;
    private $maintenanceModel;

    public function __construct() {
        $this->db = new Database();
        $this->maintenanceModel = new Maintenance();
    }

    public function handleRequest() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(401, 'Unauthorized');
            return;
        }

        $action = $_GET['action'] ?? '';
        
        try {
            switch ($action) {
                case 'get_affected_circuits':
                    $this->getAffectedCircuits();
                    break;
                    
                case 'delete_maintenance':
                    $this->deleteMaintenance();
                    break;
                    
                case 'get_maintenance_details':
                    $this->getMaintenanceDetails();
                    break;
                    
                default:
                    $this->sendResponse(400, 'Invalid action');
            }
        } catch (Exception $e) {
            Logger::error('API Error: ' . $e->getMessage());
            $this->sendResponse(500, 'Internal server error');
        }
    }

    private function getAffectedCircuits() {
        $maintenanceId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$maintenanceId) {
            $this->sendResponse(400, 'Invalid maintenance ID');
            return;
        }

        $circuits = $this->maintenanceModel->getAffectedCircuits($maintenanceId);
        $this->sendResponse(200, 'Success', $circuits);
    }

    private function deleteMaintenance() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(405, 'Method not allowed');
            return;
        }

        $ids = isset($_POST['ids']) ? json_decode($_POST['ids']) : [];
        
        if (empty($ids)) {
            $this->sendResponse(400, 'No maintenance IDs provided');
            return;
        }

        $success = true;
        foreach ($ids as $id) {
            if (!$this->maintenanceModel->deleteMaintenance($id)) {
                $success = false;
                break;
            }
        }

        if ($success) {
            $this->sendResponse(200, 'Maintenance records deleted successfully');
        } else {
            $this->sendResponse(500, 'Error deleting maintenance records');
        }
    }

    private function getMaintenanceDetails() {
        $maintenanceId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$maintenanceId) {
            $this->sendResponse(400, 'Invalid maintenance ID');
            return;
        }

        $maintenance = $this->maintenanceModel->getMaintenanceById($maintenanceId);
        if (!$maintenance) {
            $this->sendResponse(404, 'Maintenance record not found');
            return;
        }

        $circuits = $this->maintenanceModel->getAffectedCircuits($maintenanceId);
        $data = [
            'maintenance' => $maintenance,
            'circuits' => $circuits
        ];

        $this->sendResponse(200, 'Success', $data);
    }

    private function sendResponse($status, $message, $data = null) {
        http_response_code($status);
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}