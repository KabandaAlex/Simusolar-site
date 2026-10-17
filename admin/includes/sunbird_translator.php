<?php
// includes/sunbird_translator.php

function translate_text($text, $target_language = 'eng') {
    if ($target_language === 'eng' || empty(trim($text))) {
        return $text;
    }

    $cacheFile = __DIR__ . '/translation_cache.json';
    
    if (!file_exists($cacheFile)) {
        file_put_contents($cacheFile, json_encode([]));
    }
    
    $cache = json_decode(file_get_contents($cacheFile), true) ?: [];
    $cacheKey = md5(trim($text) . '_' . $target_language);

    // 1. Check local cache first
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    // Your Sunbird API key
    $apiKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJhbGV4a2FiYW5kYTQ0IiwiYWNjb3VudF90eXBlIjoiRnJlZSIsImV4cCI6NDkzODIxNTA2NX0.zq2pviS-WRBNwKnl02Cfn7n16Ie8V5ngfr6QTq15Wgk'; 
    $apiUrl = 'https://api.sunbird.ai/tasks/translate'; 

    $data = [
        'source_language' => 'eng',
        'target_language' => $target_language,
        'text' => trim($text)
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    
    // ==========================================
    // THE FAILSAFE: Hide errors and return English
    // ==========================================
    if ($error || !isset($result['output']['translated_text'])) {
        return $text; 
    }
    
    if (isset($result['output']['translated_text'])) {
        $translated = $result['output']['translated_text'];
        $cache[$cacheKey] = $translated;
        file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
        return $translated;
    }

    return $text; 
}

/**
 * Automatically parses HTML output and translates text nodes without breaking HTML tags.
 */
function auto_translate_html($html, $targetLanguage) {
    if ($targetLanguage === 'eng' || empty(trim($html))) {
        return $html;
    }

    // Tell the server NOT to timeout.
    set_time_limit(0); 

    libxml_use_internal_errors(true);

    $doc = new DOMDocument();
    
    // Modern way to encode HTML entities
    $encodedHtml = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8');
    
    @$doc->loadHTML($encodedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($doc);

    // Find all text nodes, excluding scripts, styles, textareas, and code blocks
    $textNodes = $xpath->query('//text()[not(ancestor::script)][not(ancestor::style)][not(ancestor::code)][not(ancestor::textarea)][not(ancestor::pre)]');

    foreach ($textNodes as $node) {
        $originalText = $node->nodeValue;
        $trimmed = trim($originalText);

        // Only translate nodes containing words
        if (!empty($trimmed) && preg_match('/[a-zA-Z]/', $trimmed)) {
            $translatedText = translate_text($trimmed, $targetLanguage);
            
            // Preserve leading and trailing spacing
            $leadingSpace = preg_match('/^\s+/', $originalText, $m1) ? $m1[0] : '';
            $trailingSpace = preg_match('/\s+$/', $originalText, $m2) ? $m2[0] : '';
            
            $node->nodeValue = htmlspecialchars_decode($leadingSpace . $translatedText . $trailingSpace, ENT_QUOTES);
        }
    }

    // ==========================================
    // NEW: Translate Form Placeholders
    // ==========================================
    $placeholderNodes = $xpath->query('//*[@placeholder]');
    foreach ($placeholderNodes as $node) {
        $originalText = $node->getAttribute('placeholder');
        $trimmed = trim($originalText);
        
        if (!empty($trimmed) && preg_match('/[a-zA-Z]/', $trimmed)) {
            $translatedText = translate_text($trimmed, $targetLanguage);
            $node->setAttribute('placeholder', $translatedText);
        }
    }

    // ==========================================
    // NEW: Translate Submit Buttons (Value Attributes)
    // ==========================================
    $buttonNodes = $xpath->query('//input[@type="submit" or @type="button"][@value]');
    foreach ($buttonNodes as $node) {
        $originalText = $node->getAttribute('value');
        $trimmed = trim($originalText);
        
        if (!empty($trimmed) && preg_match('/[a-zA-Z]/', $trimmed)) {
            $translatedText = translate_text($trimmed, $targetLanguage);
            $node->setAttribute('value', $translatedText);
        }
    }

    $translatedHtml = $doc->saveHTML();
    libxml_clear_errors();

    return $translatedHtml;
}
?>