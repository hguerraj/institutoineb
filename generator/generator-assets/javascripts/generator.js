/* jshint jquery: true, browser: true, globalstrict: true, unused:false */
/* global window, document */

/* global $, adminUrl, generatorUrl, console, loadjs */
'use strict';

import { enablePrettyCheckbox, enableSelectModal, enableTooltips, loadPillContent } from './modules/core-functions.js';

window.enablePrettyCheckbox = enablePrettyCheckbox;

const enableBsPills = function () {
    const bsPills = document.querySelectorAll('a[data-bs-toggle="tab"]');
    const ajaxForms = {
        configurationForm: {
            formId: 'configuration-form',
            container: '#ajax-configuration-form-container',
            url: generatorUrl + 'inc/tabs-contents/configuration-form.php'
        },
        diffFilesForm: {
            formId: 'diff-files-form',
            container: '#ajax-diff-files-form-container',
            url: generatorUrl + 'inc/tabs-contents/diff-files-form.php'
        }
    };
    const pillsContents = {
        organizeNavbar: {
            container: '#pills-admin-navbar',
            url: generatorUrl + 'inc/tabs-contents/organize-navbar.php'
        }
    }
    bsPills.forEach(pill => {
        pill.addEventListener('show.bs.tab', function (e) {
            $('#user-msg-container').html('');
            if ($('#pills-configuration-tab').attr('aria-selected') == 'true') {
                $('#pills-crud-nav').removeClass('d-none').addClass('d-flex');
            } else {
                $('#pills-crud-nav').removeClass('d-flex').addClass('d-none');
            }

            const bsTarget = $(e.target).attr('data-bs-target');
            if (bsTarget === '#pills-configuration') {
                // loadAjaxForm(ajaxForms['configurationForm']);
                loadPillContent(ajaxForms['configurationForm']);
            } else if (bsTarget === '#pills-admin-navbar') {
                loadPillContent(pillsContents.organizeNavbar);
            } else if (bsTarget === '#pills-compare-merge') {
                loadPillContent(ajaxForms['diffFilesForm']);
            }
        });
    });
}

/* Global variables
-------------------------------------------------- */

loadjs([
    adminUrl + 'assets/javascripts/bootstrap/dist/bootstrap-bundle.min.js'
], 'core',
{
    async: false
}
);

import { enableReadPaginated } from './modules/read-paginated.js';

// CORE loaded
loadjs.ready('core', () => {
    // material-pickers-base has to be always loaded for modals (bulk delete)
    loadjs([
        classUrl + 'phpformbuilder/plugins/material-pickers-base/dist/css/material-pickers-base.min.css',
        classUrl + 'phpformbuilder/plugins/material-pickers-base/dist/js/material-pickers-base.min.js'
    ], 'modal-js');

    loadjs.ready('modal-js', () => {
        $('.modal').modal().removeClass('d-none');
        window.ajaxModal = M.Modal.getInstance($('#ajax-modal'));
    });

    // pills
    enableBsPills();

    // tooltips
    enableTooltips();

    // loader
    $('*[data-toggle-loader="true"]').on('click', () => {
        $('#loader').removeClass('d-none');
        $('body').addClass('pace-running').removeClass('pace-done');
    });

    let $actionInput = $('input[name="action"]'),
        actionValue = $actionInput.val();
    if (actionValue === undefined || actionValue === '') {
        actionValue = 'build_read';
        $actionInput.val(actionValue);
    }
    $('#form-select-table select[name="table"]').on('change', function () {
        // transmit the selected table to the table-reset form
        $('input[name="table-to-reset"]').val($(this).val());
        $('button[name="btn-reset-table"]').find('em').text($(this).val());
    });

    $('input[name="list_type"]').on('click', function () {
        if ($('input[value="build_paginated_list"]').prop('checked') === true) {
            $('div[data-show-values="build_paginated_list"]')
                .removeClass('off')
                .addClass('on');
            $('div[data-show-values="build_single_element_list"]')
                .removeClass('on')
                .addClass('off');
        } else if ($('input[value="build_single_element_list"]').prop('checked') === true) {
            $('div[data-show-values="build_paginated_list"]')
                .removeClass('on')
                .addClass('off');
            $('div[data-show-values="build_single_element_list"]')
                .removeClass('off')
                .addClass('on');
            if (!$('body').hasClass('form-read-single-loaded')) {
                const target = $('.hidden-wrapper[data-show-values="build_single_element_list"]');
                $.ajax({
                    url: generatorUrl + 'inc/tabs-contents/form-read-single.php'
                })
                    .done(function (data) {
                        // data-show-values="build_single_element_list"
                        target.html(data);
                        $('body').addClass('form-read-single-loaded');
                        enableTooltips();
                        enableSelectModal('rs_jedit_select_modal');
                        const run = window.run;
                        if (typeof run != 'undefined') {
                            // the run function set the new form token value registered in session by filters-dynamic-elements.php
                            setTimeout(run, 0);
                        }
                    })
                    .fail(function (_data, _statut, error) {
                        console.log(error);
                    });
            }
        }
    });

    $('#reload-db-structure-link').on('click', function () {
        $.ajax({
            url: generatorUrl + 'inc/reload-db-structure.php',
            type: 'GET'
        })
            .done(function () {
                location.reload();
            });
    });

    // intercept form post and delete unused fields before POST to avoid PHP max_post_size issue
    $('button[name="form-select-fields-submit-btn"]').on('click', function (e) {
        e.preventDefault();
        const $form = $('#form-select-fields');
        $.when($form.find('.hidden-wrapper.off').remove()).then(function () {
            $form.submit();
        });
    });

    enableReadPaginated();

    // debug
    if ($('#debug')[0]) {
        const debug = $('#debug');
        $('#btn-debug').on('click', function () {
            debug.toggleClass('on');
        });
    }

    // lock|unlock admin
    if ($('#lock-admin-link')[0]) {
        $('#lock-admin-link').on('click', function (e) {
            e.preventDefault();
            $('form#lock-unlock-admin').submit();
            return false;
        });
    }

    $('.choose-action-radio').on('click', function () {
        actionValue = $(this).attr('id');
        $actionInput.val(actionValue);
        const element = document.querySelector('input[name="action"]');
        const event = new Event('change');
        element.dispatchEvent(event);
        $('.choose-action-radio')
            .removeClass('text-bg-primary-500 active')
            .addClass('text-bg-secondary-700');
        $('#' + actionValue)
            .removeClass('text-bg-secondary-700')
            .addClass('text-bg-primary-500 active');

        if (actionValue == 'build_create_edit' && !$('body').hasClass('form-create-edit-loaded')) {
            const target = $('.hidden-wrapper[data-show-values="build_create_edit"]');
            $.ajax({
                url: generatorUrl + 'inc/tabs-contents/form-create-edit.php'
            })
                .done(function (data) {
                    target.html(data);
                    import('./modules/create-update.js')
                        .then((module) => {
                            $('body').addClass('form-create-edit-loaded');
                            module.enableCreateUpdate();
                            module.loadValidationAuto();
                            enableTooltips();
                            const run = window.run;
                            if (typeof run != 'undefined') {
                                // the run function set the new form token value registered in session by filters-dynamic-elements.php
                                setTimeout(run, 0);
                            }
                        });
                })
                .fail(function (_data, _statut, error) {
                    console.log(error);
                });
        }

        return false;
    });

    $('#' + actionValue).trigger('click');
    if (actionValue === 'build_read') {
        $('input[name="list_type"]:checked').trigger('click');
    }

    // reset table ajax modal
    if ($('button[name="btn-reset-table"]')[0]) {
        $('button[name="btn-reset-table"]').on('click', function (e) {
            $.ajax({
                url: generatorUrl + 'inc/reset-table-form.php',
                type: 'POST',
                data: {
                    table: $('input[name="table-to-reset"]').val()
                }
            })
                .done(function (data) {
                    $('#ajax-modal .modal-content').html(data);
                    window.enablePrettyCheckbox('#ajax-modal');
                    window.ajaxModal.open();
                    $('input[name="reset-data-choices"]').on('click', function () {
                        $('input[name="reset-data"]').val($('input[name="reset-data-choices"]:checked').val());
                    });
                    $('button[name="reset-table-choices-cancel-btn"]').on('click', () => {
                        window.ajaxModal.close();
                    });
                    $('button[name="reset-table-choices-submit-btn"]').on('click', () => {
                        $('#form_reset_table').submit();
                    });
                })
                .fail(function (_data, _statut, error) {
                    console.log(error);
                });
            return false;
        });
    }

    // reinstall ajax modal
    if ($('button[name="btn-reinstall"]')[0]) {
        $('button[name="btn-reinstall"]').on('click', function (e) {
            $.ajax({
                url: generatorUrl + 'inc/reinstall-form.php'
            })
                .done(function (data) {
                    $('#ajax-modal .modal-content').html(data);
                    window.enablePrettyCheckbox('#ajax-modal');
                    window.ajaxModal.open();
                    $('button[name="reinstall-phpcg-cancel-btn"]').on('click', () => {
                        window.ajaxModal.close();
                    });
                    $('button[name="reinstall-phpcg-submit-btn"]').on('click', () => {
                        $('#form-reinstall-phpcg').submit();
                    });
                })
                .fail(function (_data, _statut, error) {
                    console.log(error);
                });
            return false;
        });
    }

    // install authentication module
    if ($('#install-authentication-module-btn')[0]) {
        const adminUrl = $('#install-authentication-module-btn').attr('data-admin-url');
        const authModInstall = {
            formId: 'form-admin-setup',
            container: '#authentication-module-installer',
            url: adminUrl + 'secure/install/index.php'
        }
        $('#install-authentication-module-btn').on('click', function (e) {
            loadPillContent(authModInstall);
        });
    }

    // remove authentication module
    if ($('#remove-authentication-module')[0]) {
        $('#remove-authentication-module').on('click', function (e) {
            e.preventDefault();
            $.ajax({
                url: generatorUrl + 'inc/remove-authentication-module.php',
                type: 'GET'
            })
                .done(function (data) {
                    $('#ajax-modal .modal-content').html(data);
                    window.enablePrettyCheckbox('#ajax-modal');
                    window.ajaxModal.open();
                    // register values in generator on confirmation
                    $('button[name="remove-authentication-module-cancel-btn"]').on('click', () => {
                        window.ajaxModal.close();
                    });
                })
                .fail(function (_data, _statut, error) {
                    console.log(error);
                });
        });
    }

    // scroll to error (invalid feedback)
    if (document.querySelector('p.invalid-feedback:not(.fv-plugins-message-container)')) {
        setTimeout(() => {
            let dims = document.querySelector('p.invalid-feedback:not(.fv-plugins-message-container)').getBoundingClientRect();
            window.scrollTo(window.scrollX, dims.top - 200 + window.scrollY);
        }, 1000);
    }

    // close buttons
    $('.card-header .btn-close').on('click', function () {
        $(this)
            .closest('.card')
            .remove();
    });
});
