<!-- jQuery -->
<script src="{{ asset('assets/admin/bower_components/jquery/dist/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('assets/admin/bower_components/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Bootstrap 3.3.7 -->
<script src="{{ asset('assets/admin/bower_components/bootstrap/dist/js/bootstrap.min.js') }}"></script>
<!-- SlimScroll -->
<script src="{{ asset('assets/admin/bower_components/jquery-slimscroll/jquery.slimscroll.min.js') }}"></script>
<!-- FastClick -->
<script src="{{ asset('assets/admin/bower_components/fastclick/lib/fastclick.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('assets/admin/dist/js/adminlte.min.js') }}"></script>

<script>
    window.initAdminLTE = function () {
        if (typeof $ !== 'undefined' && typeof $.AdminLTE !== 'undefined') {
            $.AdminLTE.layout.activate();
            $.AdminLTE.tree('.sidebar-menu');
        }
    };

    (function () {
        if (typeof window.jQuery === 'undefined') {
            return;
        }

        if (typeof window.jQuery.AdminLTE !== 'undefined' || (typeof window.$ !== 'undefined' && typeof window.$.AdminLTE !== 'undefined')) {
            return;
        }

        var s = document.createElement('script');
        s.src = "{{ asset('assets/admin/dist/js/adminlte.js') }}";
        s.onload = function () {
            try {
                window.initAdminLTE();
            } catch (e) {
                console.error('AdminLTE fallback init error:', e);
            }
        };
        document.head.appendChild(s);
    })();
</script>

<!-- DataTables -->
<script src="{{ asset('assets/admin/bower_components/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/admin/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>

<!-- AdminLTE for demo purposes -->
<script src="{{ asset('assets/admin/dist/js/demo.js') }}"></script>

<script src="{{ asset('assets/admin/jquery.validate.min.js') }}"></script>
<script src="{{ asset('assets/admin/validation.js') }}"></script>

<script>
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    Number.prototype.formatMoney = function(c, d, t) {
        let n = this,
            cc = isNaN(c = Math.abs(c)) ? 2 : c,
            de = d === undefined ? "." : d,
            th = t === undefined ? "," : t,
            s = n < 0 ? "-" : "",
            i = parseInt(n = Math.abs(+n || 0).toFixed(cc)) + "",
            j = i.length;

        j = (j > 3) ? j % 3 : 0;

        return s + (j ? i.substr(0, j) + th : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + th) + (cc ? de +
            Math.abs(n - i).toFixed(cc).slice(2) : "");
    };

    $("input").on("keydown", function(e) {
        console.log(this.value);
        if (e.which === 32 && e.target.selectionStart === 0) {
            return false;
        }
    });
</script>

<script>
    $(document).ready(function() {
        // Debug script loading
        console.log('jQuery version:', jQuery.fn.jquery);
        console.log('Bootstrap modal available:', typeof $.fn.modal !== 'undefined');
        console.log('AdminLTE available:', typeof $.AdminLTE !== 'undefined');
        console.log('Tree plugin available:', typeof $.fn.tree !== 'undefined');

        var initOrWaitForAdminLTE = function () {
            if (typeof $.AdminLTE !== 'undefined') {
                window.initAdminLTE();
                return;
            }

            var attempts = 0;
            var timer = setInterval(function () {
                if (typeof $.AdminLTE !== 'undefined') {
                    clearInterval(timer);
                    window.initAdminLTE();
                    return;
                }
                attempts++;
                if (attempts >= 20) {
                    clearInterval(timer);
                    console.error('AdminLTE not available');
                }
            }, 250);
        };

        initOrWaitForAdminLTE();
        
        // ── Sidebar scroll fix ─────────────────────────────────────────
        // AdminLTE's slimScroll injects inline height on .sidebar which
        // breaks wheel-scroll. We override it after init and on resize.
        function fixSidebarScroll() {
            try {
                var headerH  = $('.main-header').outerHeight() || 50;
                var winH     = $(window).height();
                var scrollH  = winH - headerH;
                var $sidebar = $('.main-sidebar .sidebar');
                // Kill slimScroll bar element (visual only, not functional)
                $sidebar.siblings('.slimScrollBar, .slimScrollRail').hide();
                // Override inline height set by slimScroll
                $sidebar.css({
                    'overflow-y' : 'auto',
                    'overflow-x' : 'hidden',
                    'height'     : scrollH + 'px',
                    'max-height' : 'none'
                });
                // Wrapper must also be correct height
                var $wrap = $sidebar.parent('.slimScrollDiv');
                if ($wrap.length) {
                    $wrap.css({ 'height': scrollH + 'px', 'overflow': 'hidden' });
                }
            } catch (e) {}
        }
        // Run after AdminLTE finishes its init (slimScroll runs inside initAdminLTE)
        setTimeout(fixSidebarScroll, 400);
        $(window).on('resize', fixSidebarScroll);
        
        // Initialize Push Menu
        if (typeof $.fn.pushMenu !== 'undefined') {
            $('[data-toggle="push-menu"]').pushMenu();
        }

        $(document).off('click.chapchap.sidebarToggle').on('click.chapchap.sidebarToggle', 'a.sidebar-toggle, [data-toggle="push-menu"]', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (typeof $.fn.pushMenu !== 'undefined') {
                try {
                    $(this).pushMenu('toggle');
                    return;
                } catch (err) {
                }
            }

            var $body = $('body');
            var collapseScreenSize = 767;
            var windowWidth = $(window).width();

            if (windowWidth > collapseScreenSize) {
                $body.toggleClass('sidebar-collapse');
            } else {
                $body.toggleClass('sidebar-open');
            }
        });
        
        // Initialize Bootstrap components
        if (typeof $.fn.dropdown !== 'undefined') {
            $('.dropdown-toggle').dropdown();
        }
        
        // Initialize modals - exclude vehicle assignment modals to prevent conflicts with Vue.js
        $('.modal').not('#driver-assignment-modal, #turnboy-assignment-modal').on('show.bs.modal', function () {
            $(this).find('select').each(function() {
                // Only initialize Select2 if the element doesn't have Vue.js v-model
                if (typeof $(this).select2 !== 'undefined' && !$(this).attr('v-model')) {
                    // Safely check if Select2 is already initialized
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                    $(this).select2();
                }
            });
        });

        if ($(".account_receivables").find('.active').length > 0) {
            $(".account_receivables").addClass('active');
        }

        if ($(".point_of_sale").find('.active').length > 0) {
            $(".point_of_sale").addClass('active');
        }

        // Validate form on select
        $('form').on('select2:select', 'select.form-control', function(e) {
            $(this).valid();
        });

        // Validate form
        $("form.validate-form").each(function(index, form) {
            $(form).validate();
        });

        // Initialize DataTables
        $('#sticky_header').DataTable({
            'fixedHeader': true,
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'pageLength': 100,
            'initComplete': function (settings, json) {
                var info = this.api().page.info();
                var total_record = info.recordsTotal;
                if (total_record < 101) {
                    $('.dataTables_paginate').hide();
                }
            },
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': 'noneedtoshort'
            }]
        });

        $('#create_datatable').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'pageLength': 100,
            'initComplete': function (settings, json) {
                var info = this.api().page.info();
                var total_record = info.recordsTotal;
                if (total_record < 101) {
                    $('.dataTables_paginate').hide();
                }
            },
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': 'noneedtoshort'
            }]
        });

        $('#create_datatable_10').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'pageLength': 10,
            'initComplete': function (settings, json) {
                let info = this.api().page.info();
                let total_record = info.recordsTotal;
                if (total_record < 11) {
                    $('.dataTables_paginate').hide();
                }
            },
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': 'noneedtoshort'
            }]
        });

        $('#create_datatable_25').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'pageLength': 25,
            'initComplete': function (settings, json) {
                let info = this.api().page.info();
                let total_record = info.recordsTotal;
                if (total_record < 26) {
                    $('.dataTables_paginate').hide();
                }
            },
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': 'noneedtoshort'
            }]
        });

        $('#create_datatable_50').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'pageLength': 50,
            'initComplete': function (settings, json) {
                var info = this.api().page.info();
                var total_record = info.recordsTotal;
                if (total_record < 51) {
                    $('.dataTables_paginate').hide();
                }
            },
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': 'noneedtoshort'
            }]
        });

        $('#create_datatable_desc').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'pageLength': 100,
            'initComplete': function (settings, json) {
                var info = this.api().page.info();
                var total_record = info.recordsTotal;
                if (total_record < 101) {
                    $('.dataTables_paginate').hide();
                }
            },
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': 'noneedtoshort'
            }],
            "aaSorting": [[0, 'desc']]
        });

        $('.create_multiple_datatable_10').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': true,
            'info': true,
            'autoWidth': false,
            'pageLength': 10,
            'initComplete': function (settings, json) {
                let info = this.api().page.info();
                let total_record = info.recordsTotal;
                if (total_record < 11) {
                    $('.dataTables_paginate').hide();
                }
            },
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': 'noneedtoshort'
            }]
        });

        $('#create_datatable_no_ordering').DataTable({
            'paging': true,
            'lengthChange': true,
            'searching': true,
            'ordering': false,
            'info': true,
            'autoWidth': false,
            'pageLength': 100,
            'initComplete': function (settings, json) {
                var info = this.api().page.info();
                var total_record = info.recordsTotal;
                if (total_record < 101) {
                    $('.dataTables_paginate').hide();
                }
            },
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': 'noneedtoshort'
            }]
        });

        $(".validate").validate();

        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    });
</script>

@yield('uniquepagescriptforchart')
@stack('scripts')

<!-- Signature validation override script -->
<script src="{{ asset('js/signature-override.js') }}"></script>

<!-- Header Menu Script -->
<script src="{{ asset('assets/admin/js/header-menu.js') }}"></script>

<!-- Theme Toggle Script -->
<script>
(function () {
    var STORAGE_KEY = 'altrom_theme';
    var body = document.body;
    var btn  = document.getElementById('theme-toggle-btn');
    var icon = document.getElementById('theme-toggle-icon');

    function applyTheme(theme) {
        if (theme === 'light') {
            body.classList.add('light-mode');
            if (icon) {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
            if (btn) btn.setAttribute('title', 'Switch to Dark Mode');
        } else {
            body.classList.remove('light-mode');
            if (icon) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
            if (btn) btn.setAttribute('title', 'Switch to Light Mode');
        }
    }

    // Apply saved preference immediately (no flash)
    var saved = localStorage.getItem(STORAGE_KEY) || 'dark';
    applyTheme(saved);

    // Wire up button click
    if (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var current = body.classList.contains('light-mode') ? 'light' : 'dark';
            var next = current === 'light' ? 'dark' : 'light';
            localStorage.setItem(STORAGE_KEY, next);
            applyTheme(next);
        });
    }
})();
</script>
