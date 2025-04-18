<?php
session_start();
require_once dirname(__FILE__) . '/data.php';
require_once dirname(__FILE__) . '/audit.php';

// JSON file paths
$credentials_file = dirname(__FILE__) . '/credentials.json';

// Initialize CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize error message
$error = '';

// Check login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "CSRF token mismatch";
        } else {
            if (!file_exists($credentials_file)) {
                $error = "Credentials file not found. Please run setup.php.";
            } else {
                $credentials_content = file_get_contents($credentials_file);
                if ($credentials_content === false) {
                    $error = "Error reading credentials file. Check permissions.";
                } else {
                    $credentials = json_decode($credentials_content, true);
                    if (json_last_error() !== JSON_ERROR_NONE || !isset($credentials['username']) || !isset($credentials['password'])) {
                        $error = "Invalid credentials file format.";
                    } else {
                        if ($_POST['username'] === $credentials['username'] && password_verify($_POST['password'], $credentials['password'])) {
                            $_SESSION['logged_in'] = true;
                            $_SESSION['username'] = $_POST['username'];
                            logAudit('login', "User {$_POST['username']} logged in");
                        } else {
                            $error = "Invalid username or password.";
                        }
                    }
                }
            }
        }
    }
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        include dirname(__FILE__) . '/templates/login_template.php';
        exit;
    }
}

// Handle export
if (isset($_GET['export'])) {
    if ($_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch');
    }
    $data = loadData();
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="osint_data.json"');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Handle import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "CSRF token mismatch";
    } else {
        $file = $_FILES['import_file'];
        if ($file['type'] !== 'application/json' || $file['error'] !== UPLOAD_ERR_OK) {
            $error = "Invalid file. Please upload a valid JSON file.";
        } else {
            $content = file_get_contents($file['tmp_name']);
            $imported_data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($imported_data['categories']) || !isset($imported_data['links'])) {
                $error = "Invalid JSON format.";
            } else {
                try {
                    saveData($imported_data);
                    logAudit('import_data', 'Imported new data file');
                    header('Location: admin.php');
                    exit;
                } catch (Exception $e) {
                    $error = "Import failed: " . $e->getMessage();
                }
            }
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login']) && !isset($_FILES['import_file'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "CSRF token mismatch";
    } else {
        $data = loadData();
        
        try {
            switch ($_POST['action']) {
                case 'add_category':
                    if (empty($_POST['category_name'])) {
                        throw new Exception("Category name is required");
                    }
                    $data['categories'][] = [
                        'id' => uniqid(),
                        'name' => trim($_POST['category_name']),
                        'icon' => $_POST['category_icon'] ?? 'bi-circle'
                    ];
                    logAudit('add_category', "Added category: {$_POST['category_name']}");
                    break;
                case 'edit_category':
                    if (empty($_POST['category_id']) || empty($_POST['category_name'])) {
                        throw new Exception("Category ID and name are required");
                    }
                    foreach ($data['categories'] as &$category) {
                        if ($category['id'] == $_POST['category_id']) {
                            $category['name'] = trim($_POST['category_name']);
                            $category['icon'] = $_POST['category_icon'] ?? 'bi-circle';
                            break;
                        }
                    }
                    logAudit('edit_category', "Edited category ID: {$_POST['category_id']}");
                    break;
                case 'delete_category':
                    if (empty($_POST['category_id'])) {
                        throw new Exception("Category ID is required");
                    }
                    $data['categories'] = array_filter($data['categories'], function($category) {
                        return $category['id'] != $_POST['category_id'];
                    });
                    $data['links'] = array_filter($data['links'], function($link) {
                        return $link['category_id'] != $_POST['category_id'];
                    });
                    logAudit('delete_category', "Deleted category ID: {$_POST['category_id']}");
                    break;
                case 'add_link':
                    if (empty($_POST['category_id']) || empty($_POST['link_name']) || empty($_POST['link_url']) || empty($_POST['link_type'])) {
                        throw new Exception("All link fields are required except description and tags");
                    }
                    if (!filter_var($_POST['link_url'], FILTER_VALIDATE_URL)) {
                        throw new Exception("Invalid URL format");
                    }
                    $data['links'][] = [
                        'id' => uniqid(),
                        'category_id' => $_POST['category_id'],
                        'name' => trim($_POST['link_name']),
                        'url' => trim($_POST['link_url']),
                        'description' => trim($_POST['link_description'] ?? ''),
                        'type' => $_POST['link_type'],
                        'tags' => trim($_POST['link_tags'] ?? ''),
                        'clicks' => 0
                    ];
                    logAudit('add_link', "Added link: {$_POST['link_name']}");
                    break;
                case 'edit_link':
                    if (empty($_POST['link_id']) || empty($_POST['link_name']) || empty($_POST['link_url']) || empty($_POST['link_type'])) {
                        throw new Exception("All link fields are required except description and tags");
                    }
                    if (!filter_var($_POST['link_url'], FILTER_VALIDATE_URL)) {
                        throw new Exception("Invalid URL format");
                    }
                    foreach ($data['links'] as &$link) {
                        if ($link['id'] == $_POST['link_id']) {
                            $link['name'] = trim($_POST['link_name']);
                            $link['url'] = trim($_POST['link_url']);
                            $link['description'] = trim($_POST['link_description'] ?? '');
                            $link['type'] = $_POST['link_type'];
                            $link['tags'] = trim($_POST['link_tags'] ?? '');
                            break;
                        }
                    }
                    logAudit('edit_link', "Edited link ID: {$_POST['link_id']}");
                    break;
                case 'delete_link':
                    if (empty($_POST['link_id'])) {
                        throw new Exception("Link ID is required");
                    }
                    $data['links'] = array_filter($data['links'], function($link) {
                        return $link['id'] != $_POST['link_id'];
                    });
                    logAudit('delete_link', "Deleted link ID: {$_POST['link_id']}");
                    break;
                case 'logout':
                    logAudit('logout', "User logged out");
                    session_destroy();
                    header('Location: admin.php');
                    exit;
                default:
                    throw new Exception("Invalid action");
            }
            
            saveData($data);
            header('Location: admin.php');
            exit;
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$data = loadData();
$categories = getCategories($data);
$predefined_categories = $GLOBALS['predefined_categories']; // For template

include dirname(__FILE__) . '/templates/admin_template.php';
?>