<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

class UploadController extends Controller {
    private $circuitModel;
    private $maintenanceModel;

    public function __construct() {
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

    public function uploadCircuit() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['circuit_file'])) {
            try {
                $file = $_FILES['circuit_file'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                
                if ($ext !== 'xlsx') {
                    throw new Exception('Only XLSX files are allowed');
                }

                $uploadPath = CIRCUIT_LIST_DIR . '/' . time() . '_' . $file['name'];
                move_uploaded_file($file['tmp_name'], $uploadPath);

                // Read Excel file
                $spreadsheet = IOFactory::load($uploadPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $data = [];

                foreach ($worksheet->getRowIterator(2) as $row) { // Skip header row
                    $rowData = [];
                    foreach ($row->getCellIterator() as $cell) {
                        $rowData[] = $cell->getValue();
                    }
                    
                    // Process only if either Admin A or Admin B is BSCCL
                    if (stripos($rowData[1], 'BSCCL') !== false || stripos($rowData[2], 'BSCCL') !== false) {
                        $bandwidth = $this->processBandwidth($rowData[3]);
                        $data[] = [
                            'circuit_id' => $rowData[0],
                            'admin_a' => $rowData[1],
                            'admin_b' => $rowData[2],
                            'bandwidth' => $bandwidth,
                            'status' => $rowData[4]
                        ];
                    }
                }

                // Begin transaction
                $db = new Database();
                $db->beginTransaction();

                // Clear existing data
                $this->circuitModel->truncateTable();

                // Insert new data
                foreach ($data as $row) {
                    $this->circuitModel->insert($row);
                }

                $db->endTransaction();
                $this->setFlash('success', 'Circuit list uploaded successfully');
                $this->redirect('upload');

            } catch (Exception $e) {
                if (isset($db)) {
                    $db->cancelTransaction();
                }
                $this->setFlash('error', 'Error: ' . $e->getMessage());
                $this->redirect('upload');
            }
        }
    }

    public function uploadMaintenance() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['maintenance_file'])) {
            try {
                $maintenanceTitle = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
                $startTime = $_POST['start_time'];
                $endTime = $_POST['end_time'];

                $file = $_FILES['maintenance_file'];
                $uploadPath = MAINTENANCE_LIST_DIR . '/' . time() . '_' . $file['name'];
                move_uploaded_file($file['tmp_name'], $uploadPath);

                // Read Excel file
                $spreadsheet = IOFactory::load($uploadPath);
                $worksheet = $spreadsheet->getActiveSheet();
                
                // Create maintenance record
                $maintenanceId = $this->maintenanceModel->createMaintenance([
                    'title' => $maintenanceTitle,
                    'start_time' => $startTime,
                    'end_time' => $endTime
                ]);

                // Process circuits
                foreach ($worksheet->getRowIterator(2) as $row) {
                    $circuitId = $worksheet->getCellByColumnAndRow(1, $row->getRowIndex())->getValue();
                    
                    // Check if circuit exists and is active
                    $circuit = $this->circuitModel->findByCircuitId($circuitId);
                    if ($circuit && strtolower($circuit->status) === 'active') {
                        $this->maintenanceModel->addCircuit($maintenanceId, $circuitId);
                    }
                }

                $this->setFlash('success', 'Maintenance schedule uploaded successfully');
                $this->redirect('maintenance');

            } catch (Exception $e) {
                $this->setFlash('error', 'Error: ' . $e->getMessage());
                $this->redirect('upload');
            }
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