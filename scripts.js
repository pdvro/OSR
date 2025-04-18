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
        setTimeout(() => msnry.layout(), 300);
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