<?php
// includes/ocr_api.php

/**
 * Sends an image/PDF to OCR.space API and returns the extracted text.
 * Get your free API key at: https://ocr.space/OCRAPI
 */
function scanDocumentWithOCR($file_path) {
    // IMPORTANT: Replace with your actual free OCR.space API Key
    $api_key = 'K89546350488957'; 
    $api_url = 'https://api.ocr.space/parse/image';

    // Verify file exists
    if (!file_exists($file_path)) {
        return ['success' => false, 'error' => 'File not found on server.'];
    }

    // Prepare the file for cURL transmission
    $cfile = new CURLFile($file_path);
    
    // Set up the POST data
    $post_data = [
        'apikey' => $api_key,
        'file' => $cfile,
        'language' => 'eng',
        'isOverlayRequired' => 'false',
        'scale' => 'true',
        'isTable' => 'true'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    
    // Execute and close
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['success' => false, 'error' => 'cURL Error: ' . $err];
    }

    $result = json_decode($response, true);

    if (isset($result['IsErroredOnProcessing']) && $result['IsErroredOnProcessing'] == false) {
        // Successfully extracted text
        $extracted_text = '';
        foreach ($result['ParsedResults'] as $page) {
            $extracted_text .= $page['ParsedText'] . " \n";
        }
        return ['success' => true, 'text' => trim($extracted_text)];
    } else {
        // API returned an error (e.g., file too large, invalid format)
        $error_msg = isset($result['ErrorMessage'][0]) ? $result['ErrorMessage'][0] : 'Unknown API Error';
        return ['success' => false, 'error' => $error_msg];
    }
}
?>