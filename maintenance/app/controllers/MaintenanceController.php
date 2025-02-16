<?php
class MaintenanceController extends Controller {
    private $maintenanceModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
        $this->maintenanceModel = $this->model('Maintenance');
    }

    public function index() {
        $maintenanceRecords = $this->maintenanceModel->getAllMaintenanceRecords();
        $this->view('maintenance/index', ['records' => $maintenanceRecords]);
    }

    public function view($id = null) {
        if ($id === null) {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        }
        
        if (!$id) {
            $this->setFlash('error', 'Invalid maintenance ID');
            $this->redirect('maintenance');
        }

        $maintenance = $this->maintenanceModel->getMaintenanceById($id);
        $affectedCircuits = $this->maintenanceModel->getAffectedCircuits($id);
        
        $this->view('maintenance/view', [
            'maintenance' => $maintenance,
            'circuits' => $affectedCircuits
        ]);
    }

    public function edit() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $data = [
                'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time']
            ];

            if ($this->maintenanceModel->updateMaintenance($id, $data)) {
                $this->setFlash('success', 'Maintenance record updated successfully');
            } else {
                $this->setFlash('error', 'Error updating maintenance record');
            }
            $this->redirect('maintenance');
        } else {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $maintenance = $this->maintenanceModel->getMaintenanceById($id);
            $this->view('maintenance/edit', ['maintenance' => $maintenance]);
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $ids = isset($_POST['maintenance_ids']) ? $_POST['maintenance_ids'] : [];
            
            if (empty($ids)) {
                $this->setFlash('error', 'No maintenance records selected');
                $this->redirect('maintenance');
            }

            $success = true;
            foreach ($ids as $id) {
                if (!$this->maintenanceModel->deleteMaintenance($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $this->setFlash('success', 'Selected maintenance records deleted successfully');
            } else {
                $this->setFlash('error', 'Error deleting maintenance records');
            }
        }
        $this->redirect('maintenance');
    }
}