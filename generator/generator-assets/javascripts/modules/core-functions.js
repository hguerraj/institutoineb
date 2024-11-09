const enablePrettyCheckbox = function (selector) {
    phpfbPrettyCheckbox[selector] = new PrettyCheckbox(selector, {
        prettyWrapper: {
            baseClass: 'pretty',
            defaultClass: 'p-default',
            checkboxStyle: 'p-default',
            radioStyle: 'p-round',
            fill: 'p-',
            plain: 'p-plain',
            animations: 'p-smooth',
            size: 'p-bigger',
        },
        labelWrapper: {
            color: 'p-',
            icon: 'fas fa-check me-2 text-success',
        },
        toggle         : false,
        toggleOn: {
            label: '',
            color: 'p-',
            icon : ''
        },
        toggleOff: {
            label: '',
            color: 'p-',
            icon : ''
        }
    });
}

const enableSelectModal = function ($btnName) {
    // $('button[name^="rp_jedit_select_modal"], button[name^="cu_select_modal"]')
    $('button[name^="' + $btnName + '"]').on('click', function () {
        const column = $(this).attr('data-column'),
            origin = $(this).attr('data-origin'); // rp_jedit|create-edit
        $.ajax({
            url: generatorUrl + 'inc/select-values.php',
            type: 'GET',
            data: {
                column: column,
                action: 'select-table'
            }
        })
            .done(function (data) {
                $('#ajax-modal .modal-content').html(data);
                $('#ajax-modal .modal-content select').each(function (_i, field) {
                    const selectId = $(field).attr('id');
                    window.slimSelects[selectId] = new SlimSelect({
                        select: '#' + selectId
                    });
                });
                window.ajaxModal.open();
                // register values in generator on confirmation
                $('#ajax-modal button[name="select-values-cancel-btn"]').on('click', () => {
                    window.ajaxModal.close();
                });
                $('#ajax-modal button[name="select-values-submit-btn"]').on('click', () => {
                    let selectFrom = $('input[name="select-from-' + column + '"]:checked').val(),
                        selectFromTable = '',
                        selectFromValue = '',
                        selectFromField1 = '',
                        selectFromField2 = '',
                        selectCustomNames = '',
                        selectCustomValues = '',
                        selectMultiple = $('input[name="select_multiple-' + column + '"]:checked').val();
                    if (selectFrom == 'from_table') {
                        selectFromTable = $('select[name="table-' + column + '"]').val();
                        selectFromValue = $('select[name="value-' + column + '"]').val();
                        selectFromField1 = $('select[name="field-1-' + column + '"]').val();
                        selectFromField2 = $('select[name="field-2-' + column + '"]').val();
                    } else if (selectFrom == 'custom_values') {
                        selectCustomNames = $('input[name^="custom_name-"]').serializeArray();
                        selectCustomValues = $('input[name^="custom_value-"]').serializeArray();
                    }
                    $.ajax({
                        url: generatorUrl + 'inc/register-select-values.php',
                        type: 'POST',
                        data: {
                            column: column,
                            origin: origin,
                            select_from: selectFrom,
                            select_from_table: selectFromTable,
                            select_from_value: selectFromValue,
                            select_from_field_1: selectFromField1,
                            select_from_field_2: selectFromField2,
                            select_custom_names: selectCustomNames,
                            select_custom_values: selectCustomValues,
                            select_multiple: selectMultiple
                        }
                    })
                        .done(function (output) {
                            window.ajaxModal.close();
                            if (origin == 'rp_jedit') {
                                $('#rp_select-values-' + column).html(output);
                                if (selectMultiple) {
                                    // disable live edit if multiple
                                    $('*[name="rp_value_type_' + column + '"]').attr('data-multiple', true);
                                }
                                $('*[name="rp_value_type_' + column + '"]').trigger('change');
                            } else if (origin == 'create-edit') {
                                $('#cu_select-values-' + column).html(output);
                            }
                        })
                        .fail(function () {
                            console.log('error');
                        });
                });
            })
            .fail(function (_data, _statut, error) {
                console.log(error);
            });
    });
}

const enableTooltips = function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, { delay: { "show": 750, "hide": 100 } })
    });
}

const loadPillContent = function (pillsContents) {
    const $pillContent = document.querySelector(pillsContents.container);
    if (typeof($pillContent.dataset.loaded) === 'undefined') {
        fetch(pillsContents.url)
        .then((response) => {
            return response.text()
        })
            .then((data) => {
                $pillContent.innerHTML = '';
                $pillContent.dataset.loaded = true;
                loadData(data, pillsContents.container);
                if (typeof (pillsContents.formId) !== 'undefined') {
                    $pillContent.dataset.ajaxForm = pillsContents;
                    $pillContent.dataset.ajaxFormId = pillsContents.formId;
                }
                setTimeout(() => {
                    enableTooltips();
                }, 1000);
        }).catch((error) => {
            console.log(error);
        });
    }
}

export { enablePrettyCheckbox, enableSelectModal, enableTooltips, loadPillContent }
