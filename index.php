<?php
require_once dirname(__FILE__) . '/data.php';

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Handle link click tracking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_link'])) {
    trackLinkClick($_POST['link_id']);
    echo json_encode(['status' => 'success']);
    exit;
}

$data = loadData(); // Force reset to ensure default categories
$categories = getCategories($data);

include dirname(__FILE__) . '/templates/index_template.php';
?>