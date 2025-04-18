<?php
// Setup script to initialize files
$errors = [];
$base_dir = dirname(__FILE__);

// Create credentials.json
$credentials_file = $base_dir . '/credentials.json';
$password = 'password123'; // Replace with user-provided password
if (strlen($password) < 8) {
    throw new Exception("Password must be at least 8 characters long.");
}
$username = 'admin';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$credentials = ['username' => $username, 'password' => $hashed_password];
if (file_put_contents($credentials_file, json_encode($credentials, JSON_PRETTY_PRINT)) === false) {
    $errors[] = "Failed to create credentials.json at $credentials_file: Check permissions.";
} else {
    @chmod($credentials_file, 0644);
    if (!is_writable($credentials_file)) {
        $errors[] = "credentials.json at $credentials_file is not writable after creation.";
    }
}

// Reset osint_data.json
$osint_file = $base_dir . '/osint_data.json';
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
$default_data = ['categories' => [], 'links' => []];
foreach ($predefined_categories as $category) {
    $default_data['categories'][] = [
        'id' => uniqid(),
        'name' => $category['name'],
        'icon' => $category['icon']
    ];
}
$default_data['links'] = [
    [
        'id' => 'link1',
        'category_id' => $default_data['categories'][14]['id'], // Search Engines
        'name' => 'Google',
        'url' => 'https://www.google.com',
        'description' => 'Popular search engine',
        'type' => 'Tool',
        'tags' => 'search, web',
        'clicks' => 0
    ],
    [
        'id' => 'link2',
        'category_id' => $default_data['categories'][0]['id'], // Social Media
        'name' => 'Twitter',
        'url' => 'https://www.twitter.com',
        'description' => 'Social media platform for real-time updates',
        'type' => 'Platform',
        'tags' => 'social, microblogging',
        'clicks' => 0
    ],
    [
        'id' => 'link3',
        'category_id' => $default_data['categories'][2]['id'], // Maps
        'name' => 'Google Maps',
        'url' => 'https://maps.google.com',
        'description' => 'Mapping and navigation service',
        'type' => 'Tool',
        'tags' => 'maps, navigation',
        'clicks' => 0
    ],
    [
        'id' => 'link4',
        'category_id' => $default_data['categories'][5]['id'], // News
        'name' => 'BBC News',
        'url' => 'https://www.bbc.com/news',
        'description' => 'International news outlet',
        'type' => 'Source',
        'tags' => 'news, international',
        'clicks' => 0
    ],
    [
        'id' => 'link5',
        'category_id' => $default_data['categories'][8]['id'], // Tools
        'name' => 'LexisNexis',
        'url' => 'https://www.lexisnexis.com',
        'description' => 'Public records and legal research',
        'type' => 'Database',
        'tags' => 'records, legal',
        'clicks' => 0
    ]
];

if (file_put_contents($osint_file, json_encode($default_data, JSON_PRETTY_PRINT)) === false) {
    $errors[] = "Failed to create osint_data.json at $osint_file: Check permissions.";
} else {
    @chmod($osint_file, 0644);
    if (!is_writable($osint_file)) {
        $errors[] = "osint_data.json at $osint_file is not writable after creation.";
    }
}

// Create audit_log.json
$audit_file = $base_dir . '/audit_log.json';
if (file_put_contents($audit_file, json_encode([], JSON_PRETTY_PRINT)) === false) {
    $errors[] = "Failed to create audit_log.json at $audit_file: Check permissions.";
} else {
    @chmod($audit_file, 0644);
    if (!is_writable($audit_file)) {
        $errors[] = "audit_log.json at $audit_file is not writable after creation.";
    }
}

echo "<h1>Setup Results</h1>";
if (empty($errors)) {
    echo "<p>Setup completed successfully!</p>";
    echo "<p>Login credentials: Username: admin, Password: password123</p>";
    echo "<p><a href='index.php'>Go to OSINT Link Manager</a></p>";
    echo "<p><strong>Next Steps:</strong> Delete setup.php for security.</p>";
} else {
    echo "<p>Errors occurred:</p><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "<p>Check AMPPS error log at C:\\Program Files\\Ampps\\apache\\logs\\error.log for details.</p>";
}
?>