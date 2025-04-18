<?php
// Assume $data and $categories are passed from index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSINT Link Manager</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Masonry CSS -->
    <style>
        .masonry-grid {
            
        }
        .masonry-item {
            margin-bottom: 1.5em;
        }
        .category-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1em;
            background-color: #fff;
        }
        .accordion-button {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .accordion-body {
            padding: 1rem;
        }
        .link-btn {
            margin-left: 10px;
        }
        .dark-mode {
            background-color: #222;
            color: #fff;
        }
        .dark-mode .category-card {
            background-color: #333;
            border-color: #555;
        }
        .dark-mode .accordion {
            background-color: #333;
        }
        .dark-mode .accordion-button {
            background-color: #444;
            color: #fff;
        }
        .dark-mode .accordion-button:not(.collapsed) {
            background-color: #555;
            color: #fff;
        }
        .dark-mode .navbar {
            background-color: #333 !important;
        }
        .dark-mode .link-btn {
            background-color: #555;
            border-color: #666;
        }
        .search-bar {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">OSINT Link Manager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav" aria-controls="navbarNav" 
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#manual">Manual</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="searchInput" class="form-control" placeholder="Search links by name, description, or tags...">
        </div>
        
        
        
        <!-- Masonry Grid for Categories -->
        <div class="row masonry-grid" data-masonry='{"percentPosition": true}'>
            <?php foreach ($categories as $category): ?>
                <?php
                // Only render categories with links
                $links = getLinks($data, $category['id']);
                if (empty($links)) {
                    continue;
                }
                ?>
                <div class="col-sm-6 col-lg-4 mb-4 masonry-item">
                    <div class="category-card">
                        <h3>
                            <i class="<?php echo htmlspecialchars($category['icon']); ?> me-2"></i>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </h3>
                        <div class="accordion" id="linksAccordion<?php echo htmlspecialchars($category['id']); ?>">
                            <?php foreach ($links as $index => $link): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="linkHeading<?php echo htmlspecialchars($link['id']); ?>">
                                        <button class="accordion-button collapsed" 
                                                type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#linkCollapse<?php echo htmlspecialchars($link['id']); ?>" 
                                                aria-expanded="false" 
                                                aria-controls="linkCollapse<?php echo htmlspecialchars($link['id']); ?>">
                                            <?php echo htmlspecialchars($link['name']); ?>
                                        </button>
                                        
                                    </h2>
                                    <div id="linkCollapse<?php echo htmlspecialchars($link['id']); ?>" 
                                         class="accordion-collapse collapse" 
                                         aria-labelledby="linkHeading<?php echo htmlspecialchars($link['id']); ?>" 
                                         data-bs-parent="#linksAccordion<?php echo htmlspecialchars($category['id']); ?>">
                                        <div class="accordion-body">
                                            <p><?php echo htmlspecialchars($link['description']); ?></p>
                                            <p><small>Type: <?php echo htmlspecialchars($link['type']); ?></small></p>
                                            <p><small>Tags: <?php echo htmlspecialchars($link['tags']); ?></small></p>
                                            <p><small>Clicks: <?php echo $link['clicks'] ?? 0; ?></small></p>
                                            <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                                           class="btn btn-sm btn-primary link-btn link-click" 
                                           data-link-id="<?php echo htmlspecialchars($link['id']); ?>" 
                                           target="_blank">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Manual Section -->
        <div id="manual" class="manual-section mt-4">
            <h2>Manual</h2>
            <p>Placeholder for manual content. Please provide the manual HTML or content.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Masonry JS -->
    <script src="https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Masonry
        var elem = document.querySelector('.row');
        var msnry = new Masonry(elem, {
            itemSelector: '.masonry-item',
            percentPosition: true
        });

        // Re-layout Masonry after each accordion change
        document.querySelectorAll('.accordion-button').forEach(button => {
            button.addEventListener('click', () => {
                setTimeout(() => msnry.layout(), 250);
            });
        });

            // Search functionality
            $('#searchInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                var anyVisible = false;
                $('.masonry-item').each(function() {
                    var $category = $(this);
                    var categoryVisible = false;
                    $category.find('.accordion-item').each(function() {
                        var $link = $(this);
                        var linkText = $link.text().toLowerCase();
                        var isVisible = linkText.indexOf(value) > -1;
                        $link.toggle(isVisible);
                        if (isVisible) {
                            categoryVisible = true;
                        }
                    });
                    $category.toggle(categoryVisible);
                    if (categoryVisible) {
                        anyVisible = true;
                    }
                });
                // Re-layout Masonry
                $masonryGrid.masonry('layout');
                // Show message if no results
                if (!anyVisible && value) {
                    if (!$('#noResults').length) {
                        $('.masonry-grid').after('<div id="noResults" class="text-center mt-3">No results found</div>');
                    }
                } else {
                    $('#noResults').remove();
                }
            });

            // Dark mode toggle
            $('#darkModeToggle').on('click', function() {
                $('body').toggleClass('dark-mode');
                localStorage.setItem('darkMode', $('body').hasClass('dark-mode') ? 'enabled' : 'disabled');
            });

            // Load dark mode preference
            if (localStorage.getItem('darkMode') === 'enabled') {
                $('body').addClass('dark-mode');
            }

            // Track link clicks
            $('.link-click').on('click', function(e) {
                var linkId = $(this).data('link-id');
                $.post('index.php', { track_link: true, link_id: linkId }, function(response) {
                    console.log('Click tracked:', response);
                });
            });
        });
    </script>
</body>
</html>
