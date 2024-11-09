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
// look for nested table in external relations
foreach ($generator->external_columns as $key => $ext_col) {
    if (isset($ext_col['nested']) && $ext_col['nested']) {
        $has_nested = true;
        $nested_table_class = ' table-togglable';
        $nested_table_data = ' data-toggle-column="first" data-toggle-selector=".footable-toggle"';
    }
}
?>
<div class="px-4">
    {# Partial block debug - rendered alone on the research results #}
    {% block object_debug %}
    <div id="debug-content">{{ object.debug_content|raw }}</div>
    {% endblock object_debug %}
    {# END Partial block - rendered alone on the research results #}
    <div id="toolbar" class="d-lg-flex flex-wrap justify-content-between">
        {% if object.records_count > 0 %}

        <div class="d-flex ms-auto order-lg-2">
            {{ object.select_number_per_page|raw }}
        </div>

        <hr class="w-100 d-lg-none">

        {% endif %}
        <div class="d-flex order-lg-0 mb-3">
            {% if object.can_create == true %}
            <a href="{{ constant('ADMIN_URL') }}{{ object.item }}/create" class="btn btn-sm me-1 btn-primary d-flex align-items-center"><i class="{{ constant('ICON_PLUS') }} prepend"></i>{{ constant('ADD_NEW') }}</a>
            {% endif %}
<?php
            // when building users & profiles templates, $_POST['export_btn'] is not set
            if (!isset($_POST['rp_export_btn']) || ($_POST['rp_export_btn'])) {
?>
                {% if object.records_count > 0 %}
                {{ object.export_data_button|raw }}
                {% endif %}
<?php
            }
?>
        </div>

        <div class="order-lg-1 mx-lg-auto">
            <form name="rp-search-form" id="rp-search-form" class="form-inline justify-content-center">
                <div class="input-group input-group-sm">
                    <button class="input-group-text btn {{ constant('DEFAULT_BUTTONS_CLASS') }} dropdown-toggle ps-4 pe-3" type="button" data-bs-toggle="dropdown" aria-expanded="false"></button>
                    <ul id="rp-search-field" class="dropdown-menu">
                        {% for field_name, field_display_name in object.fields %}
                        {% set active = '' %}
                        {% if field_name == attribute(session.rp_search_field, object.table) %}
                        {% set active = ' active' %}
                        {% elseif field_name == '<?php echo $generator->list_options['default_search_field'] ?>' %}
                        {% set active = ' active' %}
                        {% endif %}
                        <li><a class="dropdown-item{{ active }}" href="#" rel="noindex" data-value="{{ field_name }}">{{ field_display_name }}</a></li>
                        {% endfor %}
                    </ul>
                    {% set search_value = '' %}
                    {% if attribute(session.rp_search_string, object.table) is defined %}
                    {% set search_value = attribute(session.rp_search_string, object.table) %}
                    {% endif %}
                    <input id="rp-search" name="rp-search" type="text" value="{{ search_value }}" placeholder="{{ constant('SEARCH') }}" class="form-control form-control-sm flex-grow-1">
                    <button id="rp-search-submit" class="input-group-text btn {{ constant('DEFAULT_BUTTONS_CLASS') }}" data-ladda-button="true" data-style="zoom-in" type="submit"><span class="ladda-label"><i class="{{ constant('ICON_SEARCH') }}"></i></span></button>
                </div>
            </form>
        </div>
    </div>

    {# Partial block list - rendered alone on the research results #}
    {% block object_list %}

    {%
        set filtered_class = {
<?php
for ($i = 0; $i < $generator->columns_count; $i++) {
    if (!$generator->columns['skip'][$i]) {
        $field_name = $generator->columns['name'][$i];
        $end_line = "\n";
        if ($i + 1 < $generator->columns_count) {
            $end_line = ',' . "\n";
        }
?>
            <?php echo $field_name; ?>: '<?php echo $field_name; ?>' in object.active_filtered_fields?' class="' ~ constant('ADMIN_FILTERED_COLUMNS_CLASS') ~ '"':''<?php echo $end_line; ?>
<?php
    }
}
?>
        }
    %}

    <div id="{{ object.item }}-list">

        {% if object.records_count > 0 %}
<?php
        $table_columns_count = 1; // 1 = action buttons
?>
        <div class="table-data-wrapper">
            <table class="table table-data<?php echo $nested_table_class; ?>" <?php echo $nested_table_data ?>>
                <thead>
                    <tr class="{{ constant('DEFAULT_TABLE_HEADING_CLASS') }}">
<?php
                        if ($has_nested) {
                            $table_columns_count += 1;
?>
                        <th>&nbsp;</th>
<?php
                        } // END if
?>
                        {% if constant('ADMIN_ACTION_BUTTONS_POSITION') == 'left' %}
<?php
                        if (isset($_POST['rp_bulk_delete']) && ($_POST['rp_bulk_delete'])) {
?>
                        {% if object.can_create == true %}
                        <th>
                            <div class="form-check ps-1 d-flex mb-0" data-bs-toggle="tooltip" data-bs-title="{{ constant('TOGGLE_ALL') }}">
                                <input type="checkbox" id="bulk-check-toggle" name="bulk-check-toggle" class="form-check-input">
                                <label for="bulk-check-toggle" class="form-check-label me-0"></label>
                            </div>
                        </th>
                        {% endif %}
<?php
                            $table_columns_count += 1;
                        } // END if
?>
                        <th>{{ constant('ACTION_CONST') }}</th>
                        {% endif %}
<?php
                        for ($i = 0; $i < $generator->columns_count; $i++) {
                            if (!$generator->columns['skip'][$i]) {
                                $table_columns_count += 1;
                                $field_name = $generator->columns['name'][$i];
                                if ($generator->columns['sorting'][$i] && $generator->columns['nested'][$i] !== true) {
                                    /* get sorting field (may be relation) */

                                    // default without relation
                                    $data_field = $field_name;
                                    $relation = $generator->columns['relation'][$i];
                                    if (!empty($relation['target_table'])) {
                                        //sorting relation
                                        $data_field = array();
                                        $target_fields = explode(', ', $relation['target_fields']);
                                        foreach ($target_fields as $tf) {
                                            $data_field[] = $relation['target_table'] . '.' . $tf;
                                        }
                                        $data_field = implode(', ', $data_field);
                                    }
?>
                        <th class="sorting">{{ object.fields.<?php echo $field_name; ?> }}<a href="#" class="sorting-up py-1 d-flex align-items-start" rel="noindex" data-field="<?php echo $data_field; ?>" data-direction="ASC"><i class="{{ constant('ICON_ARROW_UP') }}"></i></a><a href="#" class="sorting-down py-1 d-flex align-items-end" rel="noindex" data-field="<?php echo $data_field; ?>" data-direction="DESC"><i class="{{ constant('ICON_ARROW_DOWN') }}"></i></a></th>
<?php
                                } else {
                                    // Footable plugin groups & hide hidden fields
                                    // Field with 'data-bs-toggle="true"' will have a '+' sign to expand hidden fields.
                                    $footable_data = '';
                                    if ($generator->columns['nested'][$i]) {
                                        $table_columns_count -= 1;
                                        $footable_data = ' data-breakpoints="all"';
                                    }
?>
                        <th<?php echo $footable_data; ?>>{{ object.fields.<?php echo $field_name; ?> }}</th>
<?php
                                } // END else
                            } // END if !skip
                        } // END for

                        // External relations
                        if (count($generator->external_columns) > 0) {
                            foreach ($generator->external_columns as $key => $ext_col) {
                                if ($ext_col['active']) {
                                    $table_columns_count += 1;
                                    $footable_data = '';
                                    if (isset($ext_col['nested']) && $ext_col['nested']) {
                                        $table_columns_count -= 1;
                                        $footable_data = ' data-breakpoints="all"';
                                    }
?>
                        <th<?php echo $footable_data; ?>><?php echo $ext_col['table_label']; ?></th>
<?php
                                } // end if
                            } // END foreach
                        } // END if
                        if (isset($_POST['rp_open_url_btn']) && ($_POST['rp_open_url_btn'])) {
                            $table_columns_count += 1;
?>
                        <th>{{ constant('DISPLAY') }}</th>
<?php
                        } // END if
?>
                            {% if constant('ADMIN_ACTION_BUTTONS_POSITION') == 'right' %}
                        <th>{{ constant('ACTION_CONST') }}</th>
<?php
                            if (isset($_POST['rp_bulk_delete']) && ($_POST['rp_bulk_delete'])) { ?>
                            {% if object.can_create == true %}
                        <th>
                            <div class="form-check ps-1 d-flex mb-0" data-bs-toggle="tooltip" data-bs-title="{{ constant('TOGGLE_ALL') }}">
                                <input type="checkbox" id="bulk-check-toggle" name="bulk-check-toggle" class="form-check-input">
                                <label for="bulk-check-toggle" class="form-check-label me-0"></label>
                            </div>
                        </th>
                            {% endif %}
<?php
                            } // END if
?>
                            {% endif %}
                    </tr>
                </thead>
                <tbody>
                    {% for i in range(0, object.records_count - 1) %}
                    <tr>
<?php
                        if ($has_nested) {
?>
                        <td></td>
<?php
                        } // END if
?>
                        {% if constant('ADMIN_ACTION_BUTTONS_POSITION') == 'left' %}
<?php
                        if (isset($_POST['rp_bulk_delete']) && ($_POST['rp_bulk_delete'])) {
?>
                        {% if object.can_create == true %}
                        <td>
                            <div class="form-check ps-0 pt-1 d-flex mb-0">
                                <input type="checkbox" id="bulk-check-{{ object.pk_url_params[loop.index0] }}" name="bulk-check-{{ object.pk_url_params[loop.index0] }}" class="form-check-input bulk-check" data-id="{{ object.pk_url_params[loop.index0] }}">
                                <label for="bulk-check-{{ object.pk_url_params[loop.index0] }}" class="form-check-label me-0"></label>
                            </div>
                        </td>
                        {% endif %}
<?php
                        } // END if
?>
                        <td class="has-btn-group no-ellipsis">
                            <div class="btn-group">
<?php
                                if (isset($_POST['rp_view_record']) && ($_POST['rp_view_record'])) {
?>
                                <a href="#" class="btn btn-sm btn-primary btn-view-record" data-target="{{ object.item }}/view/{{ object.pk_url_params[loop.index0] }}" data-bs-toggle="tooltip" data-bs-title="{{ constant('VIEW_DETAIL') }}"><span class="{{ constant('ICON_VIEW') }} icon-md"></span></a>
<?php
                                } // END if
?>
                                {% if object.update_record_authorized[object.pk_concat_values[loop.index0]] == true %}
                                <a href="{{ constant('ADMIN_URL') }}{{ object.item }}/edit/{{ object.pk_url_params[loop.index0] }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-bs-title="{{ constant('EDIT') }}"><span class="{{ constant('ICON_EDIT') }} icon-md"></span></a>
                                {% endif %}
                                {% if object.can_create == true %}
                                <a href="{{ constant('ADMIN_URL') }}{{ object.item }}/delete/{{ object.pk_url_params[loop.index0] }}" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-title="{{ constant('DELETE_CONST') }}"><span class="{{ constant('ICON_DELETE') }} icon-md"></span></a>
                                {% endif %}
                            </div>
                        </td>
                        {% endif %}
<?php
                        for ($i = 0; $i < $generator->columns_count; $i++) {
                            if (!$generator->columns['skip'][$i]) {
                                $field_name = $generator->columns['name'][$i];

                                // get display value
                                $display_value = TemplatesUtilities::getDisplayValue($generator, $field_name, $i);

                                if (!empty($generator->columns['jedit'][$i])) { // with jedit
                                    echo TemplatesUtilities::getJeditCell($generator, $field_name, $display_value, $i);
                                } else { // without jedit
?>
                        <td{{ filtered_class.<?php echo $field_name; ?>|raw }}><?php echo $display_value; ?></td>
<?php
                                } // END without jedit
                            } // END if !skip
                        } // END for
                        // External relations
                        if (count($generator->external_columns) > 0) {
                            // nested table with external data
?>
                        {% if object.external_tables_count > 0 %}
                        {% for j in range(0, object.external_tables_count - 1) %}
                        <td class="no-ellipsis">
                            {% if object.external_rows_count[i][j] > 0 %}
                            <a class="dropdown-toggle text-nowrap" data-bs-toggle="collapse" href="#{{ object.external_fields[i][j]['uniqid'] }}" rel="noindex" role="button" aria-expanded="false" aria-controls="{{ object.external_fields[i][j]['uniqid'] }}"><small class="badge rounded-pill text-bg-light prepend">{{ object.external_rows_count[i][j] }}</small><span class="show-external">{{ constant('SHOW') }}</span><span class="hide-external">{{ constant('HIDE') }}</span></a>
                            <div class="collapse mt-2" id="{{ object.external_fields[i][j]['uniqid'] }}">
                                {{ object.external_add_btn[i][j]|raw }}
                                <div class="table table-sm text-bg-light">
                                    <div class="thead {{ constant('DEFAULT_TABLE_HEADING_CLASS') }}">
                                        <div class="tr">
                                            {% for field, value in object.external_fields[i][j].fieldnames %}
                                            <div class="th">{{ value }}</div>
                                            {% endfor %}
                                        </div>
                                    </div>
                                    <div class="tbody">

                                        {# Loop records #}

                                        {% for k in range(0, object.external_rows_count[i][j] - 1) %}
                                        <div class="tr">

                                            {# Loop fields #}

                                            {% for field, value in object.external_fields[i][j].fields %}
                                            <div class="td">{{ object.external_fields[i][j].fields[field][k]|raw }}</div>
                                            {% endfor %}
                                        </div>
                                        {% endfor %}
                                    </div>
                                </div>
                            </div>
                            {% else %}
                            {{ object.external_add_btn[i][j]|raw }}
                            {% endif %}
                        </td>
                        {% endfor %}
                        {% endif %}
<?php
                        } // END if
                        if (isset($_POST['rp_open_url_btn']) && ($_POST['rp_open_url_btn'])) { ?>
                        <td><a href="{{ constant('BASE_URL') }}" rel="noindex" data-delay="500" data-bs-toggle="tooltip" data-bs-title="{{ constant('OPEN_URL') }}" target="_blank"><span class="{{ constant('ICON_NEW_TAB') }} text-center"></span></a></td>
<?php
                        } // END if
?>
                        {% if constant('ADMIN_ACTION_BUTTONS_POSITION') == 'right' %}
                        <td class="has-btn-group no-ellipsis">
                            <div class="btn-group">
<?php
                                if (isset($_POST['rp_view_record']) && ($_POST['rp_view_record'])) {
?>
                                <a href="#" class="btn btn-sm btn-primary btn-view-record" data-target="{{ object.item }}/view/{{ object.pk_url_params[loop.index0] }}" data-bs-toggle="tooltip" data-bs-title="{{ constant('VIEW_DETAIL') }}"><span class="{{ constant('ICON_VIEW') }} icon-md"></span></a>
<?php
                                } // END if
?>
                                {% if object.update_record_authorized[object.pk_concat_values[loop.index0]] == true %}
                                <a href="{{ constant('ADMIN_URL') }}{{ object.item }}/edit/{{ object.pk_url_params[loop.index0] }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-bs-title="{{ constant('EDIT') }}" data-delay="500"><span class="{{ constant('ICON_EDIT') }} icon-md"></span></a>
                                {% endif %}
                                {% if object.can_create == true %}
                                <a href="{{ constant('ADMIN_URL') }}{{ object.item }}/delete/{{ object.pk_url_params[loop.index0] }}" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-title="{{ constant('DELETE_CONST') }}" data-delay="500"><span class="{{ constant('ICON_DELETE') }} icon-md"></span></a>
                                {% endif %}
                            </div>
                        </td>
<?php
                            if (isset($_POST['rp_bulk_delete']) && ($_POST['rp_bulk_delete'])) { ?>
                        {% if object.can_create == true %}
                        <td>
                            <div class="form-check ps-0 pt-1 d-flex mb-0">
                                <input type="checkbox" id="bulk-check-{{ object.pk_url_params[loop.index0] }}" name="bulk-check-{{ object.pk_url_params[loop.index0] }}" class="form-check-input bulk-check" data-id="{{ object.pk_url_params[loop.index0] }}">
                                <label for="bulk-check-{{ object.pk_url_params[loop.index0] }}" class="form-check-label me-0"></label>
                            </div>
                        </td>
                        {% endif %}
<?php
                            } // END if
?>
                        {% endif %}
                    </tr>
                    {% endfor %}
                </tbody>
<?php
                    if (isset($_POST['rp_bulk_delete']) && ($_POST['rp_bulk_delete'])) {
?>
                {% if object.can_create == true %}
                <tfoot>
                    <tr>
                        {% set delete_btn_class = '' %}
                        {% if constant('ADMIN_ACTION_BUTTONS_POSITION') == 'right' %}
                        {% set delete_btn_class = 'text-end' %}
                        {% endif %}
                        <td colspan="<?php echo $table_columns_count; ?>" class="{{ delete_btn_class }}">
                            <button type="button" id="bulk-delete-btn" class="btn btn-danger"><span class="{{ constant('ICON_DELETE') }} icon-md prepend"></span> {{ constant('DELETE_SELECTED_RECORDS') }}</button>
                        </td>
                    </tr>
                </tfoot>
                {% endif %}
<?php
                    } // END if
?>
            </table>
        </div> <!-- END table-responsive -->

        {% else %}
        <p class="text-semibold">
            {{ alert(constant('NO_RECORD_FOUND'), 'alert-info has-icon')|raw }}
        </p>
        {% endif %}

        <div id="pagination-wrapper" class="d-flex justify-content-between p-4">
            {{ object.pagination_html|raw }}
        </div>
    </div> <!-- END {{ object.item }}-list -->

    {% endblock object_list %}
    {# END Partial block - rendered alone on the research results #}

</div> <!-- END card -->
<?php
        if (isset($_POST['rp_bulk_delete']) && ($_POST['rp_bulk_delete'])) {
?>
{% if object.records_count > 0 and object.can_create == true %}
<!-- Bulk delete modal -->
<div id="bulk-delete-modal" class="modal" tabindex="-1" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header text-bg-primary">
            <h6 class="modal-title mb-0">{{ constant('DELETE_SELECTED_RECORDS') }}</h6>
        </div>

        <div class="modal-body">
            <p>{{ constant('DELETE_SELECTED_RECORDS') }}? (<span id="records-count"></span> {{ constant('RECORDS') }})</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light modal-close"><i class="{{ constant('ICON_CANCEL') }} prepend"></i> {{ constant('CANCEL') }}</button>
            <button type="button" id="bulk-delete-confirm-btn" class="btn btn-primary">{{ constant('YES') }} <i class="{{ constant('ICON_CHECKMARK') }} append"></i></button>
        </div>
    </div>
</div>
{% endif %}
<?php
        } // END if
?>
