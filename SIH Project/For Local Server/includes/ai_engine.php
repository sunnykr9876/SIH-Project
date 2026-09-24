<?php
// includes/ai_engine.php

function getOCRText($file_path) {
    // Free OCR API Key. If you hit the rate limit during the hackathon, 
    // get a free key instantly at https://ocr.space/OCRAPI and replace 'helloworld'
    $api_key = 'K89546350488957'; 
    $api_url = 'https://api.ocr.space/parse/image';

    if (!file_exists($file_path)) return ['success' => false, 'error' => 'File missing.'];

    $cfile = new CURLFile(realpath($file_path));
    
    $post_data = [
        'apikey' => $api_key,
        'file' => $cfile,
        'language' => 'eng',
        'isTable' => 'true',
        'scale' => 'true'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (isset($result['ParsedResults'][0]['ParsedText'])) {
        return ['success' => true, 'text' => $result['ParsedResults'][0]['ParsedText']];
    }
    return ['success' => false, 'error' => 'OCR Failed or file too large.'];
}

function runAIVerification($pdo, $doc_id, $doc_type, $absolute_file_path, $student_name, $expected_no, $expected_date) {
    
    // Skip Bank Passbook for now as it lacks standard formatting
    if ($doc_type == 'BANK_PASSBOOK') {
        $stmt = $pdo->prepare("UPDATE application_documents SET ai_status='PASSED', ai_remarks='Manual Verification Recommended' WHERE id=?");
        $stmt->execute([$doc_id]);
        return;
    }

    $ocr = getOCRText($absolute_file_path);
    
    if (!$ocr['success']) {
        $stmt = $pdo->prepare("UPDATE application_documents SET ai_status='ERROR', ai_remarks=? WHERE id=?");
        $stmt->execute(['OCR Engine Error: ' . $ocr['error'], $doc_id]);
        return;
    }

    $text = strtoupper($ocr['text']);
    // Strip everything except letters and numbers for flawless matching even if OCR makes spacing errors
    $text_clean = preg_replace('/[^A-Z0-9]/', '', $text); 

    $status = 'PASSED';
    $remarks = [];

    if ($doc_type == 'AADHAAR') {
         // 1. Name Verification
         $name_clean = preg_replace('/[^A-Z]/', '', strtoupper($student_name));
         if (!empty($name_clean) && strpos($text_clean, $name_clean) !== false) {
             $remarks[] = "Name: Verified";
         } else {
             // Fallback: check if at least parts of the name match (OCR sometimes misreads one letter)
             $parts = explode(' ', strtoupper($student_name));
             $found_part = false;
             foreach($parts as $p) {
                 if (strlen($p) > 2 && strpos($text, $p) !== false) { $found_part = true; break; }
             }
             if ($found_part) {
                 $remarks[] = "Name (Partial): Verified";
             } else {
                 $remarks[] = "Name: Mismatched";
                 $status = 'FAILED';
             }
         }

         // 2. Identity Number Verification
         $expected_clean = preg_replace('/[^0-9]/', '', $expected_no);
         if (!empty($expected_clean) && strpos($text_clean, $expected_clean) !== false) {
             $remarks[] = "Aadhaar No: Verified";
         } else {
             $remarks[] = "Aadhaar No: Mismatched";
             $status = 'FAILED';
         }

    } else {
         // CASTE & INCOME Certificate Number Verification
         $expected_clean = preg_replace('/[^A-Z0-9]/', '', strtoupper($expected_no));
         if (!empty($expected_clean) && strpos($text_clean, $expected_clean) !== false) {
             $remarks[] = "Cert No: Verified";
         } else {
             $remarks[] = "Cert No: Mismatched";
             $status = 'FAILED';
         }

         // Date Verification
         if (!empty($expected_date) && $expected_date != 'N/A') {
             $d1 = date('d/m/Y', strtotime($expected_date));
             $d2 = date('d-m-Y', strtotime($expected_date));
             $d1_clean = preg_replace('/[^0-9]/', '', $d1); // DDMMYYYY format
             
             if (strpos($text_clean, $d1_clean) !== false || strpos($text, $d1) !== false || strpos($text, $d2) !== false) {
                 $remarks[] = "Issue Date: Verified";
             } else {
                 $remarks[] = "Issue Date: Mismatched";
                 $status = 'FAILED';
             }
         }
    }

    $remark_str = implode(" | ", $remarks);

    $stmt = $pdo->prepare("UPDATE application_documents SET ai_status=?, ai_remarks=?, ocr_raw_text=? WHERE id=?");
    $stmt->execute([$status, $remark_str, $text, $doc_id]);
}
?>