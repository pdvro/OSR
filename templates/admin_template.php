<?php
// Assume $data, $categories, $predefined_categories, $error, and $_SESSION['csrf_token'] are passed from admin.php
// Load audit log
$audit_log_file = dirname(__FILE__) . '/../audit_log.json';
$audit_logs = [];
if (file_exists($audit_log_file)) {
    $audit_content = file_get_contents($audit_log_file);
    if ($audit_content !== false) {
        $audit_logs = json_decode($audit_content, true);
        if (!is_array($audit_logs)) {
            $audit_logs = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OSINT Link Manager</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .dark-mode {
            background-color: #222;
            color: #fff;
        }
        .dark-mode .card, .dark-mode .nav-tabs, .dark-mode .tab-content {
            background-color: #333;
            border-color: #555;
        }
        .dark-mode .nav-link {
            color: #ccc;
        }
        .dark-mode .nav-link.active {
            background-color: #444;
            color: #fff;
        }
        .form-section {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">Admin Dashboard</h1>
        
        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Dark Mode Toggle -->
        <button id="darkModeToggle" class="btn btn-secondary mb-3">
            <i class="bi bi-moon"></i> Toggle Dark Mode
        </button>
        
        <!-- Logout Button -->
        <form method="POST" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn btn-danger mb-3">Logout</button>
        </form>
        
        <!-- Export Button -->
        <a href="admin.php?export=true&csrf_token=<?php echo urlencode($_SESSION['csrf_token']); ?>" 
           class="btn btn-success mb-3">Export Data</a>
        
        <!-- Import Form -->
        <div class="form-section">
            <h3>Import Data</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="mb-3">
                    <label for="import_file" class="form-label">Upload JSON File</label>
                    <input type="file" name="import_file" id="import_file" class="form-control" accept=".json">
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
        </div>
        
        <!-- Add Category/Link Buttons -->
        <div class="form-section">
            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                Add Category
            </button>
            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addLinkModal">
                Add Link
            </button>
        </div>
        
        <!-- Category and Audit Tabs -->
        <h3>Manage Categories, Links, and Audit Log</h3>
        <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
            <?php foreach ($categories as $index => $category): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                            id="tab-<?php echo htmlspecialchars($category['id']); ?>" 
                            data-bs-toggle="tab" 
                            data-bs-target="#pane-<?php echo htmlspecialchars($category['id']); ?>" 
                            type="button" role="tab" 
                            aria-controls="pane-<?php echo htmlspecialchars($category['id']); ?>" 
                            aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                        <i class="<?php echo htmlspecialchars($category['icon']); ?> me-2"></i>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </button>
                </li>
            <?php endforeach; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-audit" data-bs-toggle="tab" data-bs-target="#pane-audit" 
                        type="button" role="tab" aria-controls="pane-audit" aria-selected="false">
                    <i class="bi bi-journal-text me-2"></i>Audit Log
                </button>
            </li>
        </ul>
        <div class="tab-content" id="categoryTabContent">
            <?php foreach ($categories as $index => $category): ?>
                <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                     id="pane-<?php echo htmlspecialchars($category['id']); ?>" 
                     role="tabpanel" 
                     aria-labelledby="tab-<?php echo htmlspecialchars($category['id']); ?>">
                    <div class="mt-3">
                        <!-- Edit Category Form -->
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category['id']); ?>">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" name="category_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="category_icon" class="form-control" 
                                           value="<?php echo htmlspecialchars($category['icon']); ?>" 
                                           placeholder="e.g., bi-circle">
                                </div>
                                <div class="col-md-5">
                                    <button type="submit" name="action" value="edit_category" class="btn btn-warning">Edit Category</button>
                                    <button type="button" class="btn btn-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteCategoryModal<?php echo htmlspecialchars($category['id']); ?>">
                                        Delete Category
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Links Table -->
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>URL</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Tags</th>
                                    <th>Clicks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $links = getLinks($data, $category['id']);
                                foreach ($links as $link):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($link['name']); ?></td>
                                        <td><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['url']); ?></a></td>
                                        <td><?php echo htmlspecialchars($link['description']); ?></td>
                                        <td><?php echo htmlspecialchars($link['type']); ?></td>
                                        <td><?php echo htmlspecialchars($link['tags']); ?></td>
                                        <td><?php echo $link['clicks'] ?? 0; ?></td>
                                        <td>
                                            <!-- Edit Link Button -->
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editLinkModal<?php echo htmlspecialchars($link['id']); ?>">
                                                Edit
                                            </button>
                                            <!-- Delete Link Button -->
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteLinkModal<?php echo htmlspecialchars($link['id']); ?>">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Delete Category Modal -->
                <div class="modal fade" id="deleteCategoryModal<?php echo htmlspecialchars($category['id']); ?>" 
                     tabindex="-1" aria-labelledby="deleteCategoryModalLabel<?php echo htmlspecialchars($category['id']); ?>" 
                     aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteCategoryModalLabel<?php echo htmlspecialchars($category['id']); ?>">
                                        Confirm Delete Category
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to delete the category "<?php echo htmlspecialchars($category['name']); ?>"? 
                                    This will also delete all associated links.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Edit/Delete Link Modals -->
                <?php foreach ($links as $link): ?>
                    <!-- Edit Link Modal -->
                    <div class="modal fade" id="editLinkModal<?php echo htmlspecialchars($link['id']); ?>" 
                         tabindex="-1" aria-labelledby="editLinkModalLabel<?php echo htmlspecialchars($link['id']); ?>" 
                         aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editLinkModalLabel<?php echo htmlspecialchars($link['id']); ?>">Edit Link</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="edit_link">
                                        <input type="hidden" name="link_id" value="<?php echo htmlspecialchars($link['id']); ?>">
                                        <div class="mb-3">
                                            <label for="link_name_<?php echo htmlspecialchars($link['id']); ?>" class="form-label">Link Name</label>
                                            <input type="text" name="link_name" id="link_name_<?php echo htmlspecialchars($link['id']); ?>" 
                                                   class="form-control" value="<?php echo htmlspecialchars($link['name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="link_url_<?php echo htmlspecialchars($link['id']); ?>" class="form-label">URL</label>
                                            <input type="url" name="link_url" id="link_url_<?php echo htmlspecialchars($link['id']); ?>" 
                                                   class="form-control" value="<?php echo htmlspecialchars($link['url']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="link_description_<?php echo htmlspecialchars($link['id']); ?>" class="form-label">Description</label>
                                            <textarea name="link_description" id="link_description_<?php echo htmlspecialchars($link['id']); ?>" 
                                                      class="form-control"><?php echo htmlspecialchars($link['description']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="link_type_<?php echo htmlspecialchars($link['id']); ?>" class="form-label">Type</label>
                                            <input type="text" name="link_type" id="link_type_<?php echo htmlspecialchars($link['id']); ?>" 
                                                   class="form-control" value="<?php echo htmlspecialchars($link['type']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="link_tags_<?php echo htmlspecialchars($link['id']); ?>" class="form-label">Tags</label>
                                            <input type="text" name="link_tags" id="link_tags_<?php echo htmlspecialchars($link['id']); ?>" 
                                                   class="form-control" value="<?php echo htmlspecialchars($link['tags']); ?>">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delete Link Modal -->
                    <div class="modal fade" id="deleteLinkModal<?php echo htmlspecialchars($link['id']); ?>" 
                         tabindex="-1" aria-labelledby="deleteLinkModalLabel<?php echo htmlspecialchars($link['id']); ?>" 
                         aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteLinkModalLabel<?php echo htmlspecialchars($link['id']); ?>">
                                            Confirm Delete Link
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to delete the link "<?php echo htmlspecialchars($link['name']); ?>"?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="delete_link">
                                        <input type="hidden" name="link_id" value="<?php echo htmlspecialchars($link['id']); ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            
            <!-- Audit Log Tab -->
            <div class="tab-pane fade" id="pane-audit" role="tabpanel" aria-labelledby="tab-audit">
                <div class="mt-3">
                    <h4>Audit Log</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Username</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($audit_logs) as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Add Category Modal -->
        <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="add_category">
                            <div class="mb-3">
                                <label for="category_name" class="form-label">Category Name</label>
                                <input type="text" name="category_name" id="category_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="category_icon" class="form-label">Icon (Bootstrap Icon Class)</label>
                                <input type="text" name="category_icon" id="category_icon" class="form-control" 
                                       placeholder="e.g., bi-circle" value="bi-circle">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Add Link Modal -->
        <div class="modal fade" id="addLinkModal" tabindex="-1" aria-labelledby="addLinkModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addLinkModalLabel">Add Link</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="add_link">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="link_name" class="form-label">Link Name</label>
                                <input type="text" name="link_name" id="link_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="link_url" class="form-label">URL</label>
                                <input type="url" name="link_url" id="link_url" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="link_description" class="form-label">Description</label>
                                <textarea name="link_description" id="link_description" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="link_type" class="form-label">Type</label>
                                <input type="text" name="link_type" id="link_type" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="link_tags" class="form-label">Tags (comma-separated)</label>
                                <input type="text" name="link_tags" id="link_tags" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Link</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Dark mode toggle
            $('#darkModeToggle').on('click', function() {
                $('body').toggleClass('dark-mode');
                localStorage.setItem('darkMode', $('body').hasClass('dark-mode') ? 'enabled' : 'disabled');
            });

            // Load dark mode preference
            if (localStorage.getItem('darkMode') === 'enabled') {
                $('body').addClass('dark-mode');
            }
        });
    </script>
</body>
</html>
?>