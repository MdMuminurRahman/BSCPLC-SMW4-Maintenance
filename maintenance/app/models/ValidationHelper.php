<?php
class ValidationHelper {
    /**
     * Validate maintenance schedule data
     */
    public static function validateMaintenanceData($data) {
        $errors = [];

        // Title validation
        if (empty($data['title'])) {
            $errors[] = 'Maintenance title is required';
        } elseif (strlen($data['title']) > 255) {
            $errors[] = 'Title cannot exceed 255 characters';
        }

        // Date validation
        if (empty($data['start_time'])) {
            $errors[] = 'Start time is required';
        }
        if (empty($data['end_time'])) {
            $errors[] = 'End time is required';
        }

        // Compare dates if both exist
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $start = new DateTime($data['start_time']);
            $end = new DateTime($data['end_time']);
            
            if ($start >= $end) {
                $errors[] = 'End time must be after start time';
            }
        }

        return $errors;
    }

    /**
     * Validate circuit data
     */
    public static function validateCircuitData($circuit) {
        $errors = [];

        // Circuit ID validation
        if (empty($circuit['circuit_id'])) {
            $errors[] = 'Circuit ID is required';
        } elseif (strlen($circuit['circuit_id']) > 50) {
            $errors[] = 'Circuit ID cannot exceed 50 characters';
        }

        // Admin validation
        if (empty($circuit['admin_a']) && empty($circuit['admin_b'])) {
            $errors[] = 'At least one admin must be specified';
        }

        // Bandwidth validation
        if (!empty($circuit['bandwidth'])) {
            $validBandwidths = ['10G', '100G', 'STM1', 'STM4', 'STM16', 'STM64'];
            $normalizedBandwidth = strtoupper(trim($circuit['bandwidth']));
            if (!in_array($normalizedBandwidth, $validBandwidths)) {
                $errors[] = 'Invalid bandwidth value';
            }
        }

        // Status validation
        if (!empty($circuit['status'])) {
            $validStatuses = ['Active', 'Inactive', 'Maintenance'];
            if (!in_array(ucfirst(strtolower($circuit['status'])), $validStatuses)) {
                $errors[] = 'Invalid status value';
            }
        }

        return $errors;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $type = 'excel') {
        $errors = [];
        $maxSize = 10 * 1024 * 1024; // 10MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'File size exceeds limit';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'File was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'No file was uploaded';
                    break;
                default:
                    $errors[] = 'File upload error occurred';
            }
            return $errors;
        }

        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds 10MB limit';
        }

        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($type === 'excel' && !in_array($fileType, ['xlsx', 'xls'])) {
            $errors[] = 'Only Excel files (.xlsx, .xls) are allowed';
        }

        return $errors;
    }

    /**
     * Sanitize and validate email
     */
    public static function validateEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }

    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        return $errors;
    }

    /**
     * Validate date format and range
     */
    public static function validateDate($date, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Clean and validate input string
     */
    public static function sanitizeString($string, $maxLength = null) {
        $string = trim(strip_tags($string));
        if ($maxLength !== null && strlen($string) > $maxLength) {
            $string = substr($string, 0, $maxLength);
        }
        return $string;
    }
}