/* global $, adminUrl, console, loadjs */
'use strict';

const files = [
    adminUrl + 'assets/javascripts/jquery-3.5.1.min.js',
    adminUrl + 'assets/javascripts/bootstrap/dist/bootstrap-bundle.min.js',
    adminUrl + 'assets/javascripts/plugins/pace.min.js',
    adminUrl + 'assets/javascripts/plugins/OverlayScrollbars.min.js'
];

function toggleSidebar () {
    if ($('body').hasClass('sidebar-collapsed')) {
        $('body').addClass('sidebar-open').removeClass('sidebar-collapsed');
        const $sidebarBackdrop = $('<div class="sidebar-backdrop"></div>');
        $('body').append($sidebarBackdrop);
        $sidebarBackdrop.on('click', function () {
            toggleSidebar();
        })
    } else {
        $('body').removeClass('sidebar-open').addClass('sidebar-collapsed');
        $('.sidebar-backdrop').remove();
    }
}

loadjs(files, 'core', {
    async: false
});

loadjs.ready('core', function () {
    // material-pickers-base has to be always loaded for modals (bulk delete)
    loadjs([
        classUrl + 'phpformbuilder/plugins/material-pickers-base/dist/css/material-pickers-base.min.css',
        classUrl + 'phpformbuilder/plugins/material-pickers-base/dist/js/material-pickers-base.min.js'
    ]);

    /*===============================
    =            Sidebar            =
    ===============================*/

    if ($('.sidebar')[0]) {
        OverlayScrollbars(document.querySelector('#sidebar-main'), {
            overflowBehavior: {
                x: "scroll",
                y: "scroll"
            }
        });
        // Accordion behaviour if COLLAPSE_INACTIVE_SIDEBAR_CATEGORIES
        if ($('#sidebar-main.collapse-inactive-categories')[0] && $('.category-content.collapse')[0]) {
            $('#sidebar-main .category-title a.dropdown-toggle').not('[href="#sidebarFiltersNav"]').on('click', function (e) {
                const hrefId = $(e.target).attr('aria-controls');
                $('#sidebar-main .category-content').not('[id="sidebarFiltersNav"]').not('[id="' + hrefId + '"]').collapse('hide');
            });
        }
        if ($('body').width() >= 768) {
            $('body').addClass('sidebar-open');
        } else {
            $('body').addClass('sidebar-collapsed');
        }
        $('.sidebar-toggler').on('click', toggleSidebar);
    }

    /*=============================================
    =                   Navbar                   =
    =============================================*/

    if (('#fullscreen-btn')[0]) {
        $('#fullscreen-btn').on('click', function () {
            if ($('body').hasClass('full-screen')) {
                document.exitFullscreen();
                $('body').removeClass('full-screen')
            } else {
                document.documentElement.requestFullscreen();
                $('body').addClass('full-screen')
            }
        });
    }

    if ($('#style-switcher-btn')[0]) {
        const styleSwitcherWrapper = document.getElementById('style-switcher-wrapper')
        styleSwitcherWrapper.addEventListener('show.bs.offcanvas', () => {
            if (!$('#style-switcher-wrapper').attr('data-loaded')) {
                const currentForm = {
                    formId: 'style-switcher-form',
                    container: '#style-switcher-wrapper .offcanvas-body',
                    url: adminUrl + 'inc/style-switcher-form.php'
                };
                const $formContainer = document.querySelector(currentForm.container);
                if (typeof ($formContainer.dataset.ajaxForm) === 'undefined') {
                    fetch(currentForm.url)
                        .then((response) => {
                            return response.text()
                        })
                        .then((data) => {
                            $formContainer.innerHTML = '';
                            $formContainer.dataset.ajaxForm = currentForm;
                            $formContainer.dataset.ajaxFormId = currentForm.formId;
                            loadData(data, currentForm.container);
                        }).catch((error) => {
                            console.log(error);
                        });
                }
            }
        });
    }
});
