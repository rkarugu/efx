$(document).ready(function() {
    // Handle header menu clicks for dynamic menus
    $('.header-main-menu a.menu-trigger').on('click', function(e) {
        e.preventDefault();
        
        var menuType = $(this).data('menu');
        var $clickedItem = $(this).parent();
        
        // Update active state in header
        $('.header-main-menu li').removeClass('active');
        $clickedItem.addClass('active');
        
        // Show loading state
        $('#sidebar-submenu-container').addClass('loading');
        
        // Load corresponding sidebar menu via AJAX
        loadSidebarMenu(menuType);
        
        // Store in session storage
        sessionStorage.setItem('activeMenu', menuType);
    });
    
    // Mobile menu toggle
    $('#mobile-menu-toggle').on('click', function(e) {
        e.preventDefault();
        $('#mobile-menu-dropdown').toggleClass('active');
        $(this).toggleClass('active');
    });
    
    // Close mobile menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.header-main-menu').length) {
            $('#mobile-menu-dropdown').removeClass('active');
            $('#mobile-menu-toggle').removeClass('active');
        }
    });
    
    // Populate mobile menu with same items as desktop
    function populateMobileMenu() {
        var desktopItems = $('.header-menu-desktop > li').clone();
        desktopItems.find('a.menu-trigger').on('click', function(e) {
            e.preventDefault();
            var menuType = $(this).data('menu');
            
            // Close mobile menu
            $('#mobile-menu-dropdown').removeClass('active');
            $('#mobile-menu-toggle').removeClass('active');
            
            // Update active state
            $('.header-main-menu li').removeClass('active');
            $('.header-menu-desktop > li').each(function() {
                if ($(this).find('a[data-menu="' + menuType + '"]').length) {
                    $(this).addClass('active');
                }
            });
            
            // Load menu
            $('#sidebar-submenu-container').addClass('loading');
            loadSidebarMenu(menuType);
            sessionStorage.setItem('activeMenu', menuType);
        });
        
        $('.mobile-menu-list').html(desktopItems);
    }
    
    // Load sidebar menu via AJAX
    function loadSidebarMenu(menuType) {
        $.ajax({
            url: '/admin/load-sidebar-menu',
            method: 'GET',
            data: { menu: menuType },
            dataType: 'json',
            success: function(response) {
                if (response.html) {
                    // Update sidebar content
                    $('#sidebar-submenu-container').html(response.html);
                    $('#active-menu-title').text(response.title);
                    
                    // Remove loading state
                    $('#sidebar-submenu-container').removeClass('loading');
                    
                    // Reinitialize treeview for new menu items
                    setTimeout(function() {
                        if (typeof $.fn.tree !== 'undefined') {
                            $('[data-widget="tree"]').tree();
                        }
                        
                        // Reinitialize AdminLTE components
                        if (typeof window.initAdminLTE === 'function') {
                            window.initAdminLTE();
                        }
                    }, 100);
                    
                    // Reinitialize any tooltips or other plugins
                    if (typeof $('[data-toggle="tooltip"]').tooltip === 'function') {
                        $('[data-toggle="tooltip"]').tooltip();
                    }
                } else {
                    console.error('Invalid response format:', response);
                    $('#sidebar-submenu-container').removeClass('loading');
                    $('#sidebar-submenu-container').html(
                        '<li><a href="#"><i class="fa fa-exclamation-triangle"></i> Invalid response</a></li>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading sidebar menu:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    error: error,
                    response: xhr.responseText
                });
                
                $('#sidebar-submenu-container').removeClass('loading');
                
                var errorMsg = 'Error loading menu';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                
                // Show error message
                $('#sidebar-submenu-container').html(
                    '<li><a href="#"><i class="fa fa-exclamation-triangle"></i> ' + errorMsg + '</a></li>'
                );
            }
        });
    }
    
    // Initialize mobile menu on page load
    populateMobileMenu();
    
    // Restore active menu from session storage on page load
    var storedMenu = sessionStorage.getItem('activeMenu');
    if (storedMenu) {
        $('.header-main-menu li').removeClass('active');
        $('.header-main-menu a[data-menu="' + storedMenu + '"]').parent().addClass('active');
    }
});
