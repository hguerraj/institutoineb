import { enableSelectModal } from './core-functions.js';

const enableCreateUpdate = function () {
    // form create/edit
    $('select[name^="cu_field_type_"]').on('change', function () {
        const value = $(this).val(),
            $col = $(this)
                .closest('.form-group')
                .parent('div[class^="col-"]'),
            $widthSelect = $col.find('select[name^="cu_field_width_"]');
        let $date_placeholder;
        if (value != 'textarea') {
            $col
                .find('.tinymce')
                .prop('checked', false)
                .attr('disabled', true)
                .closest('label')
                .addClass('disabled');
        } else {
            $col
                .find('.tinymce')
                .removeAttr('disabled')
                .closest('label')
                .removeClass('disabled');
        }
        if (value == 'image') {
            $(this)
                .closest('.form-group')
                .find('.cu_special_image_wrapper')
                .show();
        } else {
            $(this)
                .closest('.form-group')
                .find('.cu_special_image_wrapper')
                .hide();
        }
        if (value == 'date' || value == 'datetime' || value == 'time' || value == 'month') {
            if (value == 'date' || value == 'datetime' || value == 'month') {
                if (value == 'date' || value == 'datetime') {
                    $date_placeholder = 'dddd mmmm yyyy';
                } else if (value == 'month') {
                    $date_placeholder = 'mmmm';
                }
                $col
                    .find('.cu_special_date_wrapper')
                    .find('label[for^="cu_special_date_"]')
                    .show();
                $col
                    .find('.cu_special_date_wrapper')
                    .find('input[name^="cu_special_date_"]')
                    .attr('placeholder', $date_placeholder)
                    .parent('div[class^="col-"]')
                    .show();
            } else {
                $col
                    .find('.cu_special_date_wrapper')
                    .find('label[for^="cu_special_date_"]')
                    .hide();
                $col
                    .find('.cu_special_date_wrapper')
                    .find('input[name^="cu_special_date_"]')
                    .parent('div[class^="col-"]')
                    .hide();
            }
            if (value == 'datetime' || value == 'time') {
                $col
                    .find('.cu_special_date_wrapper')
                    .find('label[for^="cu_special_time_"]')
                    .show();
                $col
                    .find('.cu_special_date_wrapper')
                    .find('input[name^="cu_special_time_"]')
                    .attr('placeholder', 'H:i a')
                    .parent('div[class^="col-"]')
                    .show();
            } else {
                $col
                    .find('.cu_special_date_wrapper')
                    .find('label[for^="cu_special_time_"]')
                    .hide();
                $col
                    .find('.cu_special_date_wrapper')
                    .find('input[name^="cu_special_time_"]')
                    .parent('div[class^="col-"]')
                    .hide();
            }
            $col.find('.cu_special_date_wrapper').show();
        } else {
            $col.find('.cu_special_date_wrapper').hide();
        }
        if (value == 'password') {
            $col.find('.cu_special_password_wrapper').show();
        } else {
            $col.find('.cu_special_password_wrapper').hide();
        }
        if (value == 'input' || value == 'textarea') {
            $col
                .find('.char-count')
                .removeAttr('disabled')
                .closest('label')
                .removeClass('disabled');
        } else {
            $col
                .find('.char-count')
                .prop('checked', false)
                .attr('disabled', true)
                .closest('label')
                .addClass('disabled');
        }
        // disable 33% width if datetime
        if (value == 'datetime') {
            $widthSelect.find('option[value^="33%"]').attr('disabled', true);
        } else {
            $widthSelect.find('option[value^="33%"]').removeAttr('disabled');
        }
    });

    // custom validation dynamic elements
    const validationContainers = $('[id^="validation-custom-ajax-elements-container-"]');

    const updateArgumentsHelper = function () {
        $('select[name^="cu_validation_function_"]').on('change', function () {
            const index = $(this).attr('data-index'),
                columnName = $(this).attr('data-column-name'),
                target = $('#ajax-update-validation-helper');
            $.ajax({
                url: generatorUrl + 'inc/update-validation-helper.php',
                data: {
                    columnName: columnName,
                    index: index,
                    value: $(this).val()
                }
            })
                .done(function (data) {
                    target.html(data);
                    const run = window['go'];
                    setTimeout(run, 0);
                })
                .fail(function (_data, _statut, error) {
                    console.log(error);
                });
        });
    };
    // trigger on load
    updateArgumentsHelper();

    // select values (modal)
    enableSelectModal('cu_select_modal');

    // Remove dynamic validation action
    const removeDynamicValidation = function (target, btnRemove, countInput) {
        let currentIndex = parseInt($(btnRemove).attr('data-index')); // index of removed dynamic
        let dfIndex = parseInt(countInput.val());
        // Transfer upper dynamics values to each previous
        const transferUpperValues = function () {
            const previousDynamic = target.find('.validation-dynamic[data-index="' + currentIndex + '"]'),
                previousFields = $(previousDynamic).find('input:not([type="search"]), textarea, select, radio, checkbox');
            $(previousFields).each(function (_i, field) {
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
            });
        };
        // if upper dynamic sections
        if (target.find('.validation-dynamic')[currentIndex]) {
            while (target.children('.validation-dynamic').eq(currentIndex).length > 0) {
                transferUpperValues();
                currentIndex++;
            }
        }
        // decrement dynamic-fields-index
        dfIndex -= 1;
        countInput.val(dfIndex);
        // remove last dynamic container
        target
            .find('.validation-remove-element-button:last')
            .closest('.validation-dynamic')
            .remove();
    };

    $(validationContainers).each(function () {
        // target to receive dynamic fields
        const target = $(this),
            columnName = $(this)
                .attr('id')
                .replace('validation-custom-ajax-elements-container-', '');
        // hidden field to store dynamic fields index
        let countInput = $('input[name="validation-dynamic-fields-index-' + columnName + '"]'),
            dfIndex;
        $(this)
            .siblings()
            .find('.validation-add-element-button')
            .on('click', function () {
                // increment index & dynamic-fields-index
                dfIndex = parseInt(countInput.val());
                dfIndex++;
                countInput.val(dfIndex);
                // ajax call
                $.ajax({
                    url: generatorUrl + 'inc/validation-custom-dynamic-elements.php',
                    data: {
                        columnName: columnName,
                        index: dfIndex
                    }
                })
                    .done(function (data) {
                        target.append(data);
                        const run = window.run;
                        if (typeof run != 'undefined') {
                            // the run function set the new form token value registered in session by filters-dynamic-elements.php
                            setTimeout(run, 0);
                        }
                        const selectId = target.find('div[data-index="' + dfIndex + '"] select').attr('id');
                        window.slimSelects[selectId] = new SlimSelect({
                            select: '#' + selectId
                        });
                        updateArgumentsHelper();
                        target
                            .find('.validation-remove-element-button')
                            .removeClass('hidden')
                            .off('click')
                            .on('click', function () {
                                removeDynamicValidation(target, this, countInput);
                            });
                    })
                    .fail(function (_data, _statut, error) {
                        console.log(error);
                    });
            });
        // activate remove buttons on load
        $(this)
            .find('.validation-remove-element-button')
            .on('click', function () {
                removeDynamicValidation(target, this, countInput);
            });
    });
}

const loadValidationAuto = function () {
    // auto validation ajax
    const $columnFields = $('select[name^="cu_field_type_"], select[name^="cu_special_password_"]');

    $columnFields.on('change', function (e) {
        // target to receive ajax
        const columnName = $(e.target)
            .attr('id')
            .replace('cu_field_type_', '')
            .replace('cu_special_password_', ''),
            target = $('div[id="validation-auto-ajax-elements-container-' + columnName + '"]'),
            fieldType = $('select[name="cu_field_type_' + columnName + '"]').val(),
            passwordValue = $('select[name^="cu_special_password_' + columnName + '"]').val();
        $.ajax({
            url: generatorUrl + 'inc/validation-auto-ajax-elements.php',
            async: true,
            data: {
                columnName: columnName,
                fieldType: fieldType,
                passwordValue: passwordValue
            }
        })
            .done(function (data) {
                // add auto validation fields (disabled)
                target.html(data);
            })
            .fail(function () {
                console.log('error');
            });
    });
    $columnFields.each(function () {
        const validation_radio = $(this)
            .attr('name')
            .replace('cu_field_type_', 'cu_validation_type_');
        // trigger validation auto ajax if 'auto' is checked
        if ($('input[name="' + validation_radio + '"]')[0]) {
            if ($('input[name="' + validation_radio + '"]:checked').val() == 'auto') {
                $(this).trigger('change');
            }
        }
    });
};

export { enableCreateUpdate, loadValidationAuto };