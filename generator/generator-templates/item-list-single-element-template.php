<?php
// phpcs:disable Generic.WhiteSpace.ScopeIndent
use generator\TemplatesUtilities;

include_once GENERATOR_DIR . 'class/generator/TemplatesUtilities.php';

$generator = $_SESSION['generator'];
?>
<div class="px-4">
    <div id="debug-content">{{ object.debug_content|raw }}</div>
    <div id="toolbar" class="d-lg-flex flex-wrap justify-content-between mb-3">
<?php
    // when building users & profiles templates, $_POST['export_btn'] is not set
    if (!isset($_POST['rs_export_btn']) || ($_POST['rs_export_btn'])) {
?>
        <div class="d-flex order-lg-0">
            {{ object.export_data_button|raw }}
        </div>
<?php
    } // END if
    if (isset($_POST['rs_open_url_btn']) && ($_POST['rs_open_url_btn'])) {
?>
        <a href="{{ constant('BASE_URL') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="{{ constant('OPEN_URL') }}" target="_blank"><span class="{{ constant('ICON_NEW_TAB') }} text-center"></span></a>
<?php
    } // END if
?>
    {% if object.update_record_authorized[object.pk_concat_values[0]] == true %}
        <a href="{{ constant('ADMIN_URL') }}{{ object.item }}/edit/{{ object.pk[0] }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-bs-title="{{ constant('EDIT') }}"><span class="{{ constant('ICON_EDIT') }} icon-md"></span></a>
    {% endif %}
    </div>

    {% if object.records_count > 0 %}
    {% for i in range(0, object.records_count - 1) %}
    <div class="table-data-wrapper">
        <table id="{{ object.item }}-list" class="table table-data table-borderless">
            <tbody>
<?php
    for ($i = 0; $i < $generator->columns_count; $i++) {
        if (!$generator->columns['skip'][$i]) {
            $field_name = $generator->columns['name'][$i];
            // get display value
            $display_value = TemplatesUtilities::getDisplayValue($generator, $field_name, $i);
?>
                <tr>
                    <th>{{ object.fields.<?php echo $field_name; ?> }}</th>
<?php
            if (!empty($generator->columns['jedit'][$i])) { // with jedit
                echo TemplatesUtilities::getJeditCell($generator, $field_name, $display_value, $i);
            } else { // without jedit
?>
                    <td><?php echo $display_value; ?></td>
<?php
            } // END without jedit
?>
                </tr>
<?php
        } // END if !skip
    } // END for

    // External relations
    if (count($generator->external_columns) > 0) {
        foreach ($generator->external_columns as $key => $ext_col) {
            if ($ext_col['active'] === true) { ?>
                <tr>
                    <th><?php echo $ext_col['table_label']; ?></th>
                    {% if object.external_tables_count > 0 %}
                    {% for j in range(0, object.external_tables_count - 1) %}
                    <td class="no-ellipsis">
                        {% if object.external_rows_count[i][j] > 0 %}
                        <table class="table table-sm">
                            <thead class="text-bg-light">
                                <tr>
                                    {% for field, value in object.external_fields[i][j].fields %}
                                    <th>{{ field }}</th>
                                    {% endfor %}
                                </tr>
                            </thead>
                            <tbody>

                                {# Loop records #}

                                {% for k in range(0, object.external_rows_count[i][j] - 1) %}
                                <tr>

                                    {# Loop fields #}

                                    {% for field, value in object.external_fields[i][j].fields %}
                                    <td>{{ object.external_fields[i][j].fields[field][k] }}</td>
                                    {% endfor %}
                                </tr>
                                {% endfor %}
                            </tbody>
                        </table>
                        {% endif %}
                    </td>
                    {% endfor %}
                    {% endif %}
<?php
            } // end if
        } // END foreach
    } // END if
?>
                </tr>
            </tbody>
        </table>
    </div> <!-- END table-responsive -->
    {% endfor %}
    {% else %}
    <p class="text-semibold">
        {{ alert(constant('NO_RECORD_FOUND'), 'alert-info has-icon')|raw }}
    </p>
    {% endif %}
</div>
