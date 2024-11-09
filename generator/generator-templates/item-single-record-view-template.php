<?php
// phpcs:disable Generic.WhiteSpace.ScopeIndent
use generator\TemplatesUtilities;

include_once GENERATOR_DIR . 'class/generator/TemplatesUtilities.php';

$generator = $_SESSION['generator'];

// look for nested table
$has_nested         = false;
$nested_elements    = '';
$nested_table_class = '';
$nested_table_data = '';
for ($i = 0; $i < $generator->columns_count; $i++) {
    if ($generator->columns['nested'][$i]) {
        $has_nested = true;
        $nested_table_class = ' table-togglable';
        $nested_table_data = ' data-toggle-column="first" data-toggle-selector=".footable-toggle"';
    }
}
?>
<div class="modal-header text-bg-info">
    <p class="modal-title h1 mb-0 fs-5">{{ object.item_label }}</p>
    <button type="button" class="btn-close modal-close" aria-label="{{ constant('CLOSE') }}"></button>
</div>
<div class="modal-body">
    <div id="single-record-debug-content">{{ object.debug_content|raw }}</div>
    {# Partial block print_view - rendered alone on the print view #}
    {% block print_view %}
    {% if object.records_count > 0 %}
    <table id="single-record-export-table" class="table table-sm table-borderless">
        <thead>
            <tr>
                <th></th>
                <td></td>
            </tr>
        </thead>
        <tbody>
            {% for i in range(0, object.records_count - 1) %}
            <?php
            for ($i = 0; $i < $generator->columns_count; $i++) {
                if (!$generator->columns['skip'][$i]) {
                    $field_name = $generator->columns['name'][$i];

                    // get display value
                    $display_value = TemplatesUtilities::getDisplayValue($generator, $field_name, $i);
            ?>
            <tr>
                <th><?php echo $field_name; ?></th>
                <td><?php echo $display_value; ?></td>
            </tr>
            <?php
                } // END if !skip
            } // END for
            // External relations
            if (count($generator->external_columns) > 0) {
                // nested table with external data
                ?>
            {% if object.external_tables_count > 0 %}
            {% for j in range(0, object.external_tables_count - 1) %}
            <tr>
                <th style="vertical-align:text-top !important;padding-top:.5rem;">{{ object.external_tables_labels[j] }}</th>
                <td class="no-ellipsis">
                    {% if object.external_rows_count[i][j] > 0 %}
                    <table class="table table-sm">
                        <thead class="text-bg-light">
                            <tr>
                                {% for field, value in object.external_fields[i][j].fieldnames %}
                                <th>{{ value }}</th>
                                {% endfor %}
                            </tr>
                        </thead>
                        <tbody>

                            {# Loop records #}

                            {% for k in range(0, object.external_rows_count[i][j] - 1) %}
                            <tr>

                                {# Loop fields #}

                                {% for field, value in object.external_fields[i][j].fields %}
                                <td>{{ object.external_fields[i][j].fields[field][k]|raw }}</td>
                                {% endfor %}
                            </tr>
                            {% endfor %}
                        </tbody>
                    </table>
                    {% else %}
                    {{ constant('NO_RECORD_FOUND') }}
                    {% endif %}
                </td>
            </tr>
            {% endfor %}
            {% endif %}
            <?php
            } // END if
            ?>
            {% endfor %}
        </tbody>
    </table>
    {% endif %}

    {% endblock print_view %}
    {# END Partial block print_view - rendered alone on the print view #}
</div>
<div class="modal-footer">
    <a href="{{ constant('ADMIN_URL') }}{{ object.item }}/print-view/{{ object.pk_url_params[0] }}" class="btn btn-primary" target="_blank" rel="nofollow">{{ constant('EXPORT') }} / {{ constant('PRINT') }}</a>
    <button type="button" class="btn btn-light modal-close">{{ constant('CLOSE') }} <i class="{{ constant('ICON_CANCEL') }} append"></i></button>
</div>
