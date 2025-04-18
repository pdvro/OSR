<?php
// JSON file path
$json_file = dirname(__FILE__) . '/osint_data.json';

// Load configuration
$config = require dirname(__FILE__) . '/config.php';
$admin_username = $config['admin_username'];
$admin_password = $config['admin_password'];

// Predefined categories with icons
$predefined_categories = [
    ['name' => 'Social Media', 'icon' => 'bi-person-lines-fill'],
    ['name' => 'Web Analysis', 'icon' => 'bi-globe'],
    ['name' => 'Maps', 'icon' => 'bi-geo-alt'],
    ['name' => 'People', 'icon' => 'bi-person'],
    ['name' => 'Satellite Imagery', 'icon' => 'bi-image'],
    ['name' => 'News', 'icon' => 'bi-newspaper'],
    ['name' => 'Transport', 'icon' => 'bi-truck'],
    ['name' => 'Finance', 'icon' => 'bi-currency-dollar'],
    ['name' => 'Tools', 'icon' => 'bi-tools'],
    ['name' => 'Image Analysis', 'icon' => 'bi-camera'],
    ['name' => 'Terrorism', 'icon' => 'bi-exclamation-triangle'],
    ['name' => 'Leaks', 'icon' => 'bi-file-earmark-break'],
    ['name' => 'Metadata', 'icon' => 'bi-file-code'],
    ['name' => 'Dark Web', 'icon' => 'bi-incognito'],
    ['name' => 'Search Engines', 'icon' => 'bi-search'],
    ['name' => 'Training', 'icon' => 'bi-book']
];

// Load data
function loadData($force_reset = false) {
    global $json_file, $predefined_categories;
    $default_data = [
        'categories' => [],
        'links' => []
    ];
    
    // Initialize default categories
    foreach ($predefined_categories as $category) {
        $default_data['categories'][] = [
            'id' => uniqid(),
            'name' => $category['name'],
            'icon' => $category['icon']
        ];
    }
    
    // Check if file needs reset
    if ($force_reset || !file_exists($json_file) || !is_writable($json_file)) {
        saveData($default_data);
        if (file_exists(dirname(__FILE__) . '/audit.php')) {
            require_once dirname(__FILE__) . '/audit.php';
            logAudit('init_data', 'Initialized osint_data.json with default categories');
        }
        return $default_data;
    }

    $content = file_get_contents($json_file);
    if ($content === false || json_decode($content, true) === null) {
        saveData($default_data);
        if (file_exists(dirname(__FILE__) . '/audit.php')) {
            require_once dirname(__FILE__) . '/audit.php';
            logAudit('reset_data', 'Reset osint_data.json due to read or parse error');
        }
        return $default_data;
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON: " . json_last_error_msg());
    }
    if (!is_array($data) || !isset($data['categories']) || !isset($data['links'])) {
        saveData($default_data);
        if (file_exists(dirname(__FILE__) . '/audit.php')) {
            require_once dirname(__FILE__) . '/audit.php';
            logAudit('reset_data', 'Reset osint_data.json due to invalid structure');
        }
        return $default_data;
    }

    return $data;
}

// Sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Save data
function saveData($data) {
    global $json_file;

    // Sanitize data before saving
    $data = sanitizeInput($data);

    if (empty($json_file) || !is_string($json_file)) {
        throw new Exception("osint_data.json path is empty or invalid");
    }
    
    if (file_exists($json_file) && !is_writable($json_file)) {
        throw new Exception("osint_data.json at $json_file is not writable: Check permissions");
    }
    
    if (!file_exists(dirname($json_file))) {
        throw new Exception("osint_data.json directory does not exist: " . dirname($json_file));
    }
    
    if (file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT)) === false) {
        throw new Exception("Failed to write to osint_data.json at $json_file: Permission denied or path issue");
    }
    
    if (!chmod($json_file, 0640)) {
        throw new Exception("Failed to set permissions on osint_data.json");
    }
}

// Get categories
function getCategories($data) {
    $categories = $data['categories'] ?? [];
    if (!is_array($categories)) {
        $categories = [];
    }
    usort($categories, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    return $categories;
}

// Get links
function getLinks($data, $category_id = null) {
    $links = $data['links'] ?? [];
    if (!is_array($links)) {
        $links = [];
    }
    if ($category_id !== null) {
        $links = array_filter($links, function($link) use ($category_id) {
            return $link['category_id'] == $category_id;
        });
    }
    usort($links, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    return array_values($links);
}

// Handle link click tracking
function trackLinkClick($link_id) {
    $data = loadData();
    foreach ($data['links'] as &$link) {
        if ($link['id'] === $link_id) {
            $link['clicks'] = isset($link['clicks']) ? $link['clicks'] + 1 : 1;
            break;
        }
    }
    saveData($data);
}
?>