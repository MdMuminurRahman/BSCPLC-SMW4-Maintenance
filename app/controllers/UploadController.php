<?php
namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Database;
use App\Models\Circuit;
use App\Models\Maintenance;
use App\Models\ExcelHelper;
use App\Models\Logger;
use App\Models\Config;
use Exception;

require 'vendor/autoload.php';

class UploadController extends Controller {
    private $circuitModel;
    private $maintenanceModel;

    public function __construct() {
        parent::__construct();
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
        $this->circuitModel = $this->model('Circuit');
        $this->maintenanceModel = $this->model('Maintenance');
    }

    public function index() {
        $lastUploads = [
            'circuit' => $this->circuitModel->getLastUploadTime(),
            'maintenance' => $this->maintenanceModel->getLastUploadTime()
        ];
        $this->view('upload/index', $lastUploads);
    }

    public function uploadCircuits() {
        try {
            $uploadPath = $this->handleFileUpload($_FILES['circuit_list']);
            if (!$uploadPath) {
                throw new \Exception('File upload failed');
            }

            $excel = ExcelHelper::getInstance();
            $data = $excel->readFile($uploadPath)->getActiveSheet();
            
            if (empty($data)) {
                throw new \Exception('No data found in uploaded file');
            }

            // Process the Excel data
            $db = new Database();
            foreach ($data as $row) {
                // Process each row
                // ...existing code...
            }

            $this->setFlash('success', 'Circuit list uploaded successfully');
            $this->redirect('upload');

        } catch (\Exception $e) {
            Logger::error('Circuit upload failed', ['error' => $e->getMessage()]);
            $this->setFlash('error', 'Error: ' . $e->getMessage());
            $this->redirect('upload');
        }
    }

    public function uploadMaintenance() {
        try {
            $uploadPath = $this->handleFileUpload($_FILES['maintenance_schedule']);
            if (!$uploadPath) {
                throw new \Exception('File upload failed');
            }

            $excel = ExcelHelper::getInstance();
            $data = $excel->readFile($uploadPath)->getActiveSheet();

            if (empty($data)) {
                throw new \Exception('No data found in uploaded file');
            }

            // Process maintenance data
            // ...existing code...

            $this->setFlash('success', 'Maintenance schedule uploaded successfully');
            $this->redirect('maintenance');

        } catch (\Exception $e) {
            Logger::error('Maintenance upload failed', ['error' => $e->getMessage()]);
            $this->setFlash('error', 'Error: ' . $e->getMessage());
            $this->redirect('upload');
        }
    }

    private function processBandwidth($value) {
        $value = strtoupper($value);
        if (strpos($value, 'VC4-64C') !== false) return 'STM64';
        if (strpos($value, 'VC4-16C') !== false) return 'STM16';
        if (strpos($value, '10G') !== false) return '10G';
        if (strpos($value, '100G') !== false) return '100G';
        if (strpos($value, 'STM1') !== false) return 'STM1';
        if (strpos($value, 'STM4') !== false) return 'STM4';
        return $value;
    }
}