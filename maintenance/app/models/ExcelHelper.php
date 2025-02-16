<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelHelper {
    /**
     * Process Circuit List Excel file
     * @param string $filePath Path to the uploaded Excel file
     * @return array Array of circuit data
     */
    public static function processCircuitList($filePath) {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];

        // Skip header row and process data rows
        foreach ($worksheet->getRowIterator(2) as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            // Check if either Admin A or Admin B is BSCCL
            if (stripos($rowData[1], 'BSCCL') !== false || stripos($rowData[2], 'BSCCL') !== false) {
                $data[] = [
                    'circuit_id' => trim($rowData[0]),
                    'admin_a' => trim($rowData[1]),
                    'admin_b' => trim($rowData[2]),
                    'bandwidth' => self::normalizeBandwidth($rowData[3]),
                    'status' => trim($rowData[4])
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
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $circuitIds = [];

        foreach ($worksheet->getRowIterator(2) as $row) {
            $circuitId = $worksheet->getCellByColumnAndRow(1, $row->getRowIndex())->getValue();
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
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Check if file is empty
            if ($worksheet->getHighestRow() < 2) {
                return [false, 'The Excel file is empty'];
            }

            // Validate based on file type
            if ($type === 'circuit') {
                $requiredColumns = ['Circuit ID', 'Admin A', 'Admin B', 'Bandwidth', 'Status'];
                $headerRow = [];
                foreach ($worksheet->getRowIterator(1, 1) as $row) {
                    $cellIterator = $row->getCellIterator();
                    foreach ($cellIterator as $cell) {
                        $headerRow[] = $cell->getValue();
                    }
                }

                foreach ($requiredColumns as $index => $column) {
                    if (!isset($headerRow[$index]) || stripos($headerRow[$index], $column) === false) {
                        return [false, "Missing required column: $column"];
                    }
                }
            } elseif ($type === 'maintenance') {
                // For maintenance list, just verify it has at least a Circuit ID column
                $firstCell = $worksheet->getCellByColumnAndRow(1, 1)->getValue();
                if (stripos($firstCell, 'Circuit') === false) {
                    return [false, 'First column must be Circuit ID'];
                }
            }

            return [true, ''];
        } catch (Exception $e) {
            return [false, 'Invalid Excel file format: ' . $e->getMessage()];
        }
    }
}