<?php
namespace App\Models;

class ExcelHelper {
    private static $instance = null;
    private $data = [];

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function readFile($filePath) {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }

        $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($fileType, ['xlsx', 'xls', 'csv'])) {
            throw new \Exception('Invalid file type. Only Excel files (xlsx, xls) and CSV files are allowed.');
        }

        if ($fileType === 'csv') {
            return $this->readCSV($filePath);
        }

        if ($fileType === 'xlsx') {
            return $this->readXLSX($filePath);
        }

        throw new \Exception('File format not supported. Please convert to XLSX or CSV.');
    }

    private function readCSV($filePath) {
        $data = [];
        if (($handle = fopen($filePath, "r")) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }
        $this->data = $data;
        return $this;
    }

    private function readXLSX($filePath) {
        $data = [];
        
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $xmlString = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($xmlString !== false) {
                $xml = simplexml_load_string($xmlString);
                if ($xml) {
                    foreach ($xml->sheetData->row as $row) {
                        $rowData = [];
                        foreach ($row->c as $cell) {
                            $value = (string)$cell->v;
                            $rowData[] = $value;
                        }
                        if (!empty($rowData)) {
                            $data[] = $rowData;
                        }
                    }
                }
            }
            $zip->close();
        }
        
        $this->data = $data;
        return $this;
    }

    public function getSheet($index = 0) {
        return $this->data;
    }

    public function getActiveSheet() {
        return $this->getSheet(0);
    }

    public static function writeFile($data, $filePath) {
        $handle = fopen($filePath, 'w');
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    /**
     * Process Circuit List Excel file
     * @param string $filePath Path to the uploaded Excel file
     * @return array Array of circuit data
     */
    public static function processCircuitList($filePath) {
        $spreadsheet = self::getInstance()->readFile($filePath)->getActiveSheet();
        $data = [];

        // Skip header row and process data rows
        foreach (array_slice($spreadsheet, 1) as $row) {
            // Check if either Admin A or Admin B is BSCCL
            if (stripos($row[1], 'BSCCL') !== false || stripos($row[2], 'BSCCL') !== false) {
                $data[] = [
                    'circuit_id' => trim($row[0]),
                    'admin_a' => trim($row[1]),
                    'admin_b' => trim($row[2]),
                    'bandwidth' => self::normalizeBandwidth($row[3]),
                    'status' => trim($row[4])
                ];
            }
        }

        return $data;
    }

    /**
     * Process Maintenance List Excel file
     * @param string $filePath Path to the uploaded Excel file
     * @return array Array of circuit IDs
     */
    public static function processMaintenanceList($filePath) {
        $spreadsheet = self::getInstance()->readFile($filePath)->getActiveSheet();
        $circuitIds = [];

        foreach (array_slice($spreadsheet, 1) as $row) {
            $circuitId = $row[0];
            if ($circuitId) {
                $circuitIds[] = trim($circuitId);
            }
        }

        return $circuitIds;
    }

    /**
     * Normalize bandwidth values to standard format
     * @param string $bandwidth Original bandwidth value
     * @return string Normalized bandwidth value
     */
    private static function normalizeBandwidth($bandwidth) {
        $bandwidth = strtoupper(trim($bandwidth));
        
        $mappings = [
            'VC4-64C' => 'STM64',
            'VC4-16C' => 'STM16',
            '10G' => '10G',
            '100G' => '100G',
            'STM1' => 'STM1',
            'STM4' => 'STM4'
        ];

        foreach ($mappings as $pattern => $standardized) {
            if (strpos($bandwidth, $pattern) !== false) {
                return $standardized;
            }
        }

        return $bandwidth;
    }

    /**
     * Validate Excel file format and structure
     * @param string $filePath Path to the Excel file
     * @param string $type Type of file ('circuit' or 'maintenance')
     * @return array [bool success, string message]
     */
    public static function validateExcelFile($filePath, $type) {
        try {
            $spreadsheet = self::getInstance()->readFile($filePath)->getActiveSheet();
            
            // Check if file is empty
            if (count($spreadsheet) < 2) {
                return [false, 'The Excel file is empty'];
            }

            // Validate based on file type
            if ($type === 'circuit') {
                $requiredColumns = ['Circuit ID', 'Admin A', 'Admin B', 'Bandwidth', 'Status'];
                $headerRow = $spreadsheet[0];

                foreach ($requiredColumns as $index => $column) {
                    if (!isset($headerRow[$index]) || stripos($headerRow[$index], $column) === false) {
                        return [false, "Missing required column: $column"];
                    }
                }
            } elseif ($type === 'maintenance') {
                // For maintenance list, just verify it has at least a Circuit ID column
                $firstCell = $spreadsheet[0][0];
                if (stripos($firstCell, 'Circuit') === false) {
                    return [false, 'First column must be Circuit ID'];
                }
            }

            return [true, ''];
        } catch (\Exception $e) {
            return [false, 'Invalid Excel file format: ' . $e->getMessage()];
        }
    }

    public static function readMaintenanceSchedule($filePath) {
        try {
            $spreadsheet = self::getInstance()->readFile($filePath)->getActiveSheet();
            // ...existing code...
        } catch (\Exception $e) {
            Logger::error('Failed to read maintenance schedule', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}