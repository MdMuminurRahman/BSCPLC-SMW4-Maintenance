<?php
namespace App\Controllers;

use App\Models\Maintenance;
use App\Models\Performance;
use App\Models\Security;
use App\Models\Logger;
use App\Models\Utility;
use App\Models\ValidationHelper;
use Exception;

class MaintenanceController extends Controller {
    private $maintenanceModel;

    public function __construct() {
        parent::__construct();
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
        $this->maintenanceModel = $this->model('Maintenance');
    }

    public function index() {
        $maintenanceRecords = $this->maintenanceModel->getAllMaintenanceRecords();
        $this->view('maintenance/index', ['records' => $maintenanceRecords]);
    }

    public function viewMaintenance($id = null) {
        $id = $id ?? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $this->setFlash('error', 'Invalid maintenance ID');
            $this->redirect('maintenance');
            return;
        }

        $maintenance = $this->maintenanceModel->getMaintenanceById($id);
        if (!$maintenance) {
            $this->setFlash('error', 'Maintenance record not found');
            $this->redirect('maintenance');
            return;
        }

        $affectedCircuits = $this->maintenanceModel->getAffectedCircuits($id);
        
        $this->view('maintenance/view', [
            'maintenance' => $maintenance,
            'circuits' => $affectedCircuits
        ]);
    }

    public function edit() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->validateCsrf();

            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                $this->setFlash('error', 'Invalid maintenance ID');
                $this->redirect('maintenance');
                return;
            }

            $data = [
                'title' => htmlspecialchars(
                    filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW),
                    ENT_QUOTES,
                    'UTF-8'
                ),
                'start_time' => filter_input(INPUT_POST, 'start_time', FILTER_UNSAFE_RAW),
                'end_time' => filter_input(INPUT_POST, 'end_time', FILTER_UNSAFE_RAW)
            ];

            // Validate dates
            try {
                Utility::validateDateRange($data['start_time'], $data['end_time']);
            } catch (\Exception $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect("maintenance/edit?id=$id");
                return;
            }

            if ($this->maintenanceModel->updateMaintenance($id, $data)) {
                Logger::info('Maintenance record updated', ['id' => $id]);
                $this->setFlash('success', 'Maintenance record updated successfully');
            } else {
                Logger::error('Failed to update maintenance record', ['id' => $id]);
                $this->setFlash('error', 'Error updating maintenance record');
            }
            $this->redirect('maintenance');
        } else {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                $this->setFlash('error', 'Invalid maintenance ID');
                $this->redirect('maintenance');
                return;
            }

            $maintenance = $this->maintenanceModel->getMaintenanceById($id);
            if (!$maintenance) {
                $this->setFlash('error', 'Maintenance record not found');
                $this->redirect('maintenance');
                return;
            }

            $this->view('maintenance/edit', ['maintenance' => $maintenance]);
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->redirect('maintenance');
            return;
        }

        $this->validateCsrf();

        $ids = filter_input(INPUT_POST, 'maintenance_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [];
        $ids = array_filter($ids, 'is_numeric');
        
        if (empty($ids)) {
            $this->setFlash('error', 'No maintenance records selected');
            $this->redirect('maintenance');
            return;
        }

        try {
            $success = true;
            foreach ($ids as $id) {
                if (!$this->maintenanceModel->deleteMaintenance($id)) {
                    $success = false;
                    Logger::error('Failed to delete maintenance record', ['id' => $id]);
                    break;
                }
                Logger::info('Maintenance record deleted', ['id' => $id]);
            }

            if ($success) {
                $this->setFlash('success', 'Selected maintenance records deleted successfully');
            } else {
                $this->setFlash('error', 'Error deleting maintenance records');
            }
        } catch (\Exception $e) {
            Logger::error('Exception while deleting maintenance records', [
                'error' => $e->getMessage(),
                'ids' => $ids
            ]);
            $this->setFlash('error', 'An error occurred while deleting records');
        }

        $this->redirect('maintenance');
    }

    protected function handleFileUpload($file, $allowedTypes = ['xlsx'], $maxSize = null) {
        try {
            Performance::start('file_upload');
            
            $validation = ValidationHelper::validateFileUpload($file, 'excel');
            if (!empty($validation)) {
                throw new \Exception(implode(', ', $validation));
            }

            $fileName = time() . '_' . Security::sanitizeFilePath($file['name']);
            $uploadPath = UPLOAD_DIR . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                Performance::monitorFileUpload($file['size']);
                Logger::info('File uploaded successfully', [
                    'name' => $fileName,
                    'size' => Utility::formatFileSize($file['size'])
                ]);
                return $uploadPath;
            }

            throw new \Exception('Failed to move uploaded file');
        } catch (\Exception $e) {
            Logger::error('File upload failed', [
                'error' => $e->getMessage(),
                'file' => $file['name']
            ]);
            throw $e;
        } finally {
            Performance::end('file_upload');
        }
    }
}