<?php
// htdocs/includes/mock_ocr.php

/**
 * TRUE TESSERACT OCR INTEGRATION
 * Checks server OS for Tesseract binary. If missing, throws graceful fallback.
 * If present, extracts raw text and runs strict multi-point string matching.
 */

function process_document_ai($document_type, $file_path, $student_profile, $form_data) {
    
    // ---------------------------------------------------------
    // 1. TESSERACT INSTALLATION CHECK
    // ---------------------------------------------------------
    // Suppress errors with @ and redirect stderr to stdout to catch "command not found"
    $tesseract_check = @shell_exec('tesseract -v 2>&1');
    
    // If shell_exec is disabled by cPanel or Tesseract is missing
    if (empty($tesseract_check) || stripos($tesseract_check, 'tesseract') === false) {
        $payload = [
            'extracted_text' => ['Error' => 'System dependency missing.'],
            'verification_breakdown' => [
                'Name' => 'NOT SCANNED',
                'Certificate_Number' => 'NOT SCANNED',
                'Date' => 'NOT SCANNED'
            ]
        ];
        
        return [
            'status' => 'MANUAL_REVIEW',
            'extracted_json' => json_encode($payload, JSON_PRETTY_PRINT),
            'remarks' => 'Failed to verify because Tesseract Open Source OCR Engine not installed.'
        ];
    }

    // ---------------------------------------------------------
    // 2. EXECUTE REAL TESSERACT OCR
    // ---------------------------------------------------------
    // escapeshellarg() is crucial for security to prevent command injection from file names
    $safe_file_path = escapeshellarg($file_path);
    
    // Run Tesseract on the image and output text to stdout
    $ocr_raw_output = @shell_exec("tesseract $safe_file_path stdout 2>&1");
    
    if (empty($ocr_raw_output)) {
        $payload = ['Error' => 'Tesseract executed but could not read any text (Image may be blurry, blank, or a random photo).'];
        return [
            'status' => 'MISMATCH',
            'extracted_json' => json_encode($payload, JSON_PRETTY_PRINT),
            'remarks' => 'AI Scrutiny Failed: Tesseract could not detect readable text on this document.'
        ];
    }

    // Normalize text for comparison (lowercase, remove weird formatting)
    $ocr_text_lower = strtolower(preg_replace('/\s+/', ' ', $ocr_raw_output));
    $ocr_text_no_spaces = preg_replace('/[^a-z0-9]/', '', $ocr_text_lower);

    // ---------------------------------------------------------
    // 3. STRICT MULTI-POINT MATCHING LOGIC
    // ---------------------------------------------------------
    $extracted_data = [];
    $field_results = [];
    $overall_status = 'MATCH';
    $remarks_array = [];

    // --- COMPULSORY CHECK 1: NAME ---
    $profile_name_lower = strtolower(trim($student_profile['name']));
    // Check if the user's name exists ANYWHERE in the extracted OCR text
    if (strpos($ocr_text_lower, $profile_name_lower) !== false) {
        $extracted_data['Name'] = $student_profile['name'];
        $field_results['Name'] = 'MATCH';
    } else {
        $extracted_data['Name'] = 'NOT FOUND / MISMATCH';
        $field_results['Name'] = 'MISMATCH';
        $overall_status = 'MISMATCH';
        $remarks_array[] = "Name not found in document text.";
    }

    // --- COMPULSORY CHECK 2: CERTIFICATE NUMBER / ID NUMBER ---
    $id_field_key = ($document_type == 'AADHAAR') ? 'Aadhaar_Number' : 'Certificate_No';
    $expected_id_value = ($document_type == 'AADHAAR') ? ($student_profile['aadhaar_raw'] ?? '') : ($form_data[strtolower($document_type) == 'caste_certificate' ? 'caste_cert_no' : 'income_cert_no'] ?? '');
    
    // Strip all dashes, slashes, and spaces from both the expected ID and OCR text for a bulletproof check
    $clean_expected_id = preg_replace('/[^a-z0-9]/', '', strtolower($expected_id_value));

    if (!empty($clean_expected_id) && strpos($ocr_text_no_spaces, $clean_expected_id) !== false) {
         $extracted_data[$id_field_key] = $expected_id_value;
         $field_results['Certificate_Number'] = 'MATCH';
    } else {
         $extracted_data[$id_field_key] = 'NOT FOUND / MISMATCH';
         $field_results['Certificate_Number'] = 'MISMATCH';
         $overall_status = 'MISMATCH';
         $remarks_array[] = "Certificate/ID Number not found in document text.";
    }

    // --- SECONDARY CHECK: DATE (DOB or ISSUE DATE) ---
    $date_field_key = ($document_type == 'AADHAAR') ? 'DOB' : 'Issue_Date';
    $expected_date_value = ($document_type == 'AADHAAR') ? $student_profile['dob'] : ($form_data[strtolower($document_type) == 'caste_certificate' ? 'caste_issue_date' : 'income_issue_date'] ?? '');
    
    // Documents print dates differently. We generate the 3 most common formats to search for.
    // E.g., 1999-12-31 becomes "31/12/1999", "31-12-1999", and "1999-12-31"
    $d1 = date('d/m/Y', strtotime($expected_date_value));
    $d2 = date('d-m-Y', strtotime($expected_date_value));
    $d3 = date('Y-m-d', strtotime($expected_date_value));

    if (strpos($ocr_text_lower, $d1) !== false || strpos($ocr_text_lower, $d2) !== false || strpos($ocr_text_lower, $d3) !== false) {
        $extracted_data[$date_field_key] = $expected_date_value;
        $field_results['Date'] = 'MATCH';
    } else {
        $extracted_data[$date_field_key] = 'NOT FOUND / MISMATCH';
        $field_results['Date'] = 'MISMATCH';
        $overall_status = 'MISMATCH';
        $remarks_array[] = "Date mismatch or not found on document.";
    }

    // Generate Final Payload
    $payload = [
        'tesseract_raw_preview' => substr(trim($ocr_raw_output), 0, 150) . '...', // Show admin a snippet of what Tesseract actually "saw"
        'extracted_text' => $extracted_data,
        'verification_breakdown' => $field_results
    ];

    $final_remarks = empty($remarks_array) ? 'AI Verification Passed: Tesseract OCR successfully validated all fields.' : implode(' ', $remarks_array);

    return [
        'status' => $overall_status,
        'extracted_json' => json_encode($payload, JSON_PRETTY_PRINT),
        'remarks' => $final_remarks
    ];
}
?>