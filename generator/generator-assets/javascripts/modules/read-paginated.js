import { enableSelectModal } from './core-functions.js';

const enableReadPaginated = function () {
    // skip fields
    $('input[name^="rp_others_"]').on('change', function () {
        const skippable = $(this)
            .closest('div.form-group')
            .siblings('.skippable');
        if ($(this).val() === 'skip') {
            skippable.slideUp();
        } else {
            skippable.slideDown();
        }
    });

    $('input[name^="rp_others_"]:checked').trigger('change');

    // read paginated list
    $('select[name^="rp_value_type_"]').add('input[name^="rp_value_type_"]').on('change', function () {
        const value = $(this).val(),
            $parent = $(this).closest('div.row'),
            jeditFieldName = $parent.find('select[name^="rp_jedit_"]').attr('name');
        if (value == 'array' || value == 'image' || value == 'file' || value == 'password' || $(this).attr('data-multiple')) {
            window.slimSelects[jeditFieldName].set('');
            window.slimSelects[jeditFieldName].disable();
            $parent.find('label').addClass('disabled');
        } else {
            window.slimSelects[jeditFieldName].enable();
            $parent.find('label').removeClass('disabled');
        }
    });

    // change on load
    setTimeout(() => {
        $('select[name^="rp_value_type_"]').trigger('change');
    }, 3000);

    // link bulk cascade delete to cascade delete
    if ($('input[name^="bulk_constrained_tables_"]')[0]) {
        $('input[name^="bulk_constrained_tables_"]').on('change', function () {
            const cascade_delete_input_id = $(this).attr('id').replace('bulk_', '');
            $('#' + cascade_delete_input_id).prop("checked", true);
        });
        $('input[name^="constrained_tables_"]').on('change', function () {
            const bulk_cascade_delete_input_id = 'bulk_' + $(this).attr('id');
            $('#' + bulk_cascade_delete_input_id).prop("checked", true);
        });
    }

    // select values (modal)
    enableSelectModal('rp_jedit_select_modal');

    /* =============================================
        filters dynamic elements
    ============================================= */

    // target to receive dynamic fields
    const target = $('#filters-ajax-elements-container');

    // hidden field to store dynamic fields index
    let countInput = $('input[name="filters-dynamic-fields-index"]'),
        dfIndex = parseInt(countInput.val());

    const enableRemoveDynamic = function () {
        // Remove action
        $('.filters-remove-element-button')
            .removeClass('hidden')
            .off('click')
            .on('click', function () {
                let currentIndex = parseInt($(this).attr('data-index')); // index of removed dynamic
                // Transfer upper dynamics values to each previous
                const transferUpperValues = function () {
                    const previousDynamic = $('.filters-dynamic[data-index="' + currentIndex + '"]'),
                        previousFields = $(previousDynamic).find('input:not([type="search"]), textarea, select, radio, checkbox');
                    $(previousFields).each(function (_i, field) {
                        if ($(field).attr('data-activates') === undefined) {
                            // specific condition for material select
                            let fieldId = $(field).attr('id'),
                                followingField = '',
                                newValue = '';
                            if ($(field).is('input[type="radio"]')) {
                                let followingFieldName = $(field)
                                    .attr('name')
                                    .replace('-' + parseInt(currentIndex), '-' + parseInt(currentIndex + 1));
                                followingField = $('input[name="' + followingFieldName + '"]:checked');
                                newValue = followingField.val();
                                if ($(field).val() == newValue) {
                                    $(field).prop('checked', true);
                                } else {
                                    $(field).prop('checked', false);
                                }

                                // reinitialize the dependent fields
                                const selector = '#filters-ajax-elements-' + currentIndex + ' .hidden-wrapper';
                                phpfbDependentFields[selector] = new DependentFields(selector);
                                if (window.CustomEvent && typeof window.CustomEvent === 'function') {
                                    document.querySelector(selector).dispatchEvent(new CustomEvent('change'));
                                } else {
                                    document.querySelector(selector).dispatchEvent(document.createEvent('CustomEvent').initCustomEvent('change', true, true));
                                }
                                // console.log('currentIndex : ' + currentIndex);
                                // console.log('currentId : ' + fieldId);
                                // console.log('followingFieldName : ' + followingFieldName);
                                // console.log('newValue : ' + newValue);
                            } else {
                                const followingFieldId = fieldId.replace('-' + parseInt(currentIndex), '-' + parseInt(currentIndex + 1));
                                followingField = $('#' + followingFieldId);
                                if ($(field).is('select')) {
                                    newValue = followingField.find('option:selected').val();
                                    window.slimSelects[fieldId].set(newValue);
                                } else {
                                    newValue = followingField.val();
                                    $(field).val(newValue);
                                }
                            }
                        }
                    });
                };
                // if upper dynamic sections
                if ($('.filters-dynamic')[currentIndex]) {
                    while ($($('.filters-dynamic')[currentIndex + 1]).length > 0) {
                        transferUpperValues();
                        currentIndex++;
                    }
                }
                // decrement dynamic-fields-index
                dfIndex -= 1;
                countInput.val(dfIndex);
                enableDaterangeAjaxToggle();
                // remove last dynamic container
                $('.filters-remove-element-button:last')
                    .closest('.filters-dynamic')
                    .remove();
            });
    };

    const enableDaterangeAjaxToggle = function () {
        // disable ajax on daterange filters
        $('input[name^="filter-daterange-"]').off('change').on('change', function (e) {
            const index = $(e.target).attr('name').replace('filter-daterange-', ''),
                value = $(e.target).val();
            if (value > 0) {
                $('input[name="filter-ajax-' + index + '"][value=""]').prop('checked', true);
                $('input[name="filter-ajax-' + index + '"]').prop('disabled', true);
            } else {
                $('input[name="filter-ajax-' + index + '"]').prop('disabled', false);
            }
        });
        $('input[name^="filter-mode-"]').on('change', function (e) {
            const index = $(e.target).attr('name').replace('filter-mode-', ''),
                value = $(e.target).val();
            if (value === 'advanced') {
                $('input[name="filter-ajax-' + index + '"]').prop('disabled', false);
            } else {
                $('input[name="filter-daterange-' + index + '"]:checked').trigger('change');
            }
        });
    }

    $('.filters-add-element-button').on('click', function () {
        // increment index & dynamic-fields-index
        dfIndex++;
        countInput.val(dfIndex);
        // ajax call
        $.ajax({
            url: generatorUrl + 'inc/filters-dynamic-elements.php',
            data: {
                index: dfIndex
            }
        })
            .done(function (data) {
                target.append(data);
                $('#filters-ajax-elements-container').find('div[data-index="' + dfIndex + '"]').attr('id', 'filters-ajax-elements-' + dfIndex);
                const run = window.run;
                if (typeof run != 'undefined') {
                    // the run function set the new form token value registered in session by filters-dynamic-elements.php
                    setTimeout(run, 0);
                }
                phpfbDependentFields['#filters-ajax-elements-container'] = new DependentFields('#filters-ajax-elements-container .hidden-wrapper');
                window.enablePrettyCheckbox('#filters-ajax-elements-' + dfIndex);
                const selectId = $('#filters-ajax-elements-' + dfIndex).find('select').attr('id');
                window.slimSelects[selectId] = new SlimSelect({
                    select: '#' + selectId
                });
                enableRemoveDynamic();
                enableFilterTest();

                enableDaterangeAjaxToggle();
            })
            .fail(function (_data, _statut, error) {
                console.log(error);
            });
    });

    // enable remove filter buttons
    enableRemoveDynamic();

    // enable date range ajax toggle
    enableDaterangeAjaxToggle();

    $('input[name^="filter-daterange-"]').each(function () {
        if ($(this).is(':checked')) {
            $(this).trigger('change');
        }
    });

    // filters test
    // 'test_filter_' are generated by class/phpformbuilder/FormExtended.php
    const enableFilterTest = function () {
        if ($('button[name^="filter_test-"]')[0]) {
            $('button[name^="filter_test-"]')
                .off('click')
                .on('click', function () {
                    const i = $(this).val(),
                        jsonKey = 'test_filter_' + i;
                    let data = {};
                    data[jsonKey] = 'all';
                    if ($('select[name="test_filter_' + i + '"]')[0]) {
                        data[jsonKey] = $('select[name="test_filter_' + i + '"]').val();
                    }
                    data.index = i;
                    data.filter_mode = $('input[name="filter-mode-' + i + '"]:checked').val();
                    data.filter_A = $('select[name="filter_field_A-' + i + '"]').val();
                    data.select_label = $('input[name="filter_select_label-' + i + '"]').val();
                    data.option_text = $('input[name="filter_option_text-' + i + '"]').val();
                    data.fields = $('input[name="filter_fields-' + i + '"]').val();
                    data.field_to_filter = $('input[name="filter_field_to_filter-' + i + '"]').val();
                    data.from = $('input[name="filter_from-' + i + '"]').val();
                    data.type = $('input[name="filter_type-' + i + '"]').val();
                    $.ajax({
                        url: generatorUrl + 'inc/filter-test.php',
                        type: 'POST',
                        data: data
                    })
                        .done(function (output) {
                            $('#ajax-modal .modal-content').html(output);
                            setTimeout(() => {
                                if ($('#ajax-modal .modal-content .db-error')[0]) {
                                    $('#ajax-modal .modal-body').prepend($('#ajax-modal .modal-content .db-error'));
                                }
                            }, 0);
                            window.enablePrettyCheckbox('#ajax-modal');
                            window.ajaxModal.open();
                            $('#ajax-modal button[name="cancel_filters"]').attr('disabled', true);
                            $('#ajax-modal button[name="submit_filters"]').attr('disabled', true);
                        })
                        .fail(function (_data, _statut, error) {
                            console.log(error);
                        });
                });
        }
    };

    enableFilterTest();

    // disable jedit for secondary target fields
    $('select[name^="rp_target_column_"]').on('change', function () {
        let secondSelectName = $(this).attr('name').replace('rp_target_column_0', 'rp_target_column_1');
        if (new RegExp(/rp_target_column_1/).test($(this).attr('name'))) {
            secondSelectName = $(this).attr('name').replace('rp_target_column_1', 'rp_target_column_0');
        }
        const value = $(this).val(),
            value2 = $($('#' + secondSelectName)).val(),
            jeditFieldName = $(this).attr('name').replace(/target_column_[0-9]+/, 'jedit'),
            $jeditParent = $('select[name="' + jeditFieldName + '"]').closest('div.row'),
            dotRegex = new RegExp(/\./);
        if (dotRegex.test(value) || dotRegex.test(value2)) {
            window.slimSelects[jeditFieldName].set('');
            window.slimSelects[jeditFieldName].disable();
            $jeditParent.find('label').addClass('disabled');
        } else {
            window.slimSelects[jeditFieldName].enable();
            $jeditParent.find('label').removeClass('disabled');
        }
    });

    // change on load
    // add a timeout, else the slimselect disable() doesn't work (Slimselect bug?)
    setTimeout(() => {
        $('select[name^="rp_target_column_0"]').trigger('change');
    }, 3000);
}

export { enableReadPaginated };
