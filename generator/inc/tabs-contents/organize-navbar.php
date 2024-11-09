<?php
include_once '../../../conf/conf.php';

session_start();

$nav_data = array();
$db_data  = array();
if (file_exists(ADMIN_DIR . 'crud-data/nav-data.json') && file_exists(ADMIN_DIR . 'crud-data/db-data.json')) {
    $json      = file_get_contents(ADMIN_DIR . 'crud-data/nav-data.json');
    $nav_data = json_decode($json, true);

    $json      = file_get_contents(ADMIN_DIR . 'crud-data/db-data.json');
    $db_data = json_decode($json, true);

    if (!is_null($nav_data)) {
        $has_wrong_table_data = false;
        $list = '<ol id="sortable-nav">';
        foreach ($nav_data as $root => $navcat) {
            $tables_count = count($navcat['tables']);

            // Sidebar categories items
            if ($tables_count > 0) {
                $list .= '<li class="parent border border-light border-4 d-flex bg-white px-3 py-2 m-3">';
                $list .= '<div class="d-flex flex-column align-content-stretch">';
                $list .= '  <div class="editable">';
                $list .= '      <p class="small mb-1">' . CLICK_TO_EDIT . '</p>';
                $list .= '      <p class="cat-name text-semibold px-3 py-2">' . $navcat['name'] . '</p>';
                $list .= '  </div>';
                $list .= '  <div class="mt-auto mb-1">';
                $list .= '      <button type="btn" class="btn btn-primary btn-sm btn-block drag-me"><i class="fas fa-arrows-alt me-3"></i>' . DRAG_ME . '</button>';
                $list .= '  </div>';
                $list .= '</div>';
                $list .= '<ol>';
                for ($i = 0; $i < $tables_count; $i++) {
                    $table       = $navcat['tables'][$i];
                    // if the table structure is not registered in db_data
                    // (it may have been reset or the table may have been removed, or anything)
                    if (!isset($db_data[$table]) || !isset($db_data[$table]['table_label'])) {
                        $list .= '<li class="child border-0 text-bg-light d-flex align-items-center justify-content-between m-1 disabled" id="' . $table . '"><span class="text-danger">' . $table . ':' . WRONG_TABLE_DATA . '<span class="font-weight-bold">*</strong></span></li>';
                        $has_wrong_table_data = true;
                    } else {
                        $is_disabled = $navcat['is_disabled'][$i];
                        $title = $db_data[$table]['table_label'];
                        $icon  = $db_data[$table]['icon'];
                        $disabled_class = '';
                        if ($is_disabled == 'true') {
                            $disabled_class = ' disabled';
                        }
                        $list .= '<li class="child border-0 text-bg-light d-flex align-items-center justify-content-between m-1' . $disabled_class . '" id="' . $table . '"><div class="drag-me d-flex w-100"><div class="text-bg-danger p-3 lh-1 disable-icon" data-bs-toggle="tooltip" data-bs-title="' . REMOVE_FROM_NAVBAR . '"><i class="fas fa-x text-white px-2"></i></div><div class="text-bg-primary p-3 lh-1 enable-icon" data-bs-toggle="tooltip" data-bs-title="' . ADD_TO_NAVBAR . '"><i class="fas fa-plus text-white px-2"></i></div><p class="text-start px-5 py-3 mb-0 lh-1 flex-grow-1" data-bs-toggle="tooltip" data-bs-title="' . DRAG_ME . '"><i class="fas fa-arrows-alt me-4 lh-1"></i>' . $title . '</p></div><i data-iconpicker="true" id="' . $table . '-icon" class="' . $icon . '" data-bs-toggle="tooltip" data-bs-title="' . CLICK_TO_OPEN_THE_ICONPICKER . '"></i></li>';
                    }
                }
                $list .= '</ol>';
            }
            $list .= '</li>';
        }
        $list .= '</ol>';
        if ($has_wrong_table_data) {
            $list .= WRONG_TABLE_DATA_MESSAGE;
        }
    } else {
        // if empty
        $list = '<p class="alert alert-warning text-center my-5 has-icon">Your navbar is empty</p>';
    }
    $new_list_html = '<li class="parent border border-light border-4 d-flex bg-white px-3 py-2 m-3">';
    $new_list_html .= '<div class="d-flex flex-column align-content-stretch">';
    $new_list_html .= '  <div class="editable">';
    $new_list_html .= '      <p class="small mb-1">' . CLICK_TO_EDIT . '</p>';
    $new_list_html .= '      <p class="cat-name text-semibold px-3 py-2">' . CLICK_TO_EDIT . '</p>';
    $new_list_html .= '  </div>';
    $new_list_html .= '  <div class="mt-auto mb-1">';
    $new_list_html .= '      <button type="btn" class="btn btn-primary btn-sm btn-block drag-me"><i class="fas fa-arrows-alt me-3"></i>' . DRAG_ME . '</button>';
    $new_list_html .= '  </div>';
    $new_list_html .= '</div>';
    $new_list_html .= '<ol></ol>';
}
?>
<div class="container">
    <h2 class="h1 text-center my-4">PHP CRUD Generator - <?php echo ORGANIZE_ADMIN_NAVBAR; ?></h2>
    <div id="result"></div>
    <p class="lead text-center text-semibold mb-5"><?php echo DRAG_AND_DROP_HELP; ?></p>
    <?php if (DEMO === true) { ?>
    <div class="alert alert-info has-icon">
        <p class="h4 mb-0">The Navbar module is disabled in this demo.</p>
    </div>
    <?php } ?>
    <button type="button" id="add-category-btn" class="btn btn-sm btn-primary ms-5"><?php echo ADD_CATEGORY; ?><i class="<?php echo ICON_PLUS; ?> append"></i></button>
    <?php
    echo $list;
    ?>
    <div class="text-center mt-4">
        <button type="button" id="save-changes-btn" class="btn btn-lg btn-primary"><?php echo SAVE_CHANGES; ?><i class="<?php echo ICON_CHECKMARK; ?> append"></i></button>
    </div>
</div>

<script>
    loadjs([
        '<?php echo GENERATOR_URL; ?>generator-assets/lib/jquery-sortable/jquery-sortable.css',
        '<?php echo GENERATOR_URL; ?>generator-assets/lib/jquery-sortable/jquery-sortable-min.js',
        '<?php echo GENERATOR_URL; ?>generator-assets/lib/universal-icon-picker/assets/js/universal-icon-picker.min.js'
    ], 'organize-navbar');


    loadjs.ready('organize-navbar', () => {
        const uips = [];
        $('i[data-iconpicker="true"]').each(function(index, iconpicker) {
            uips.push(
                new UniversalIconPicker('#' + $(iconpicker).attr('id'), {
                    onSelect: function(jsonIconData) {
                        $(iconpicker).attr('class', jsonIconData.iconClass);
                        setTimeout(function() {
                            if ($(iconpicker).siblings('.icons-selector').find('.selector-popup').css('display') != 'block') {
                                $("#sortable-nav").sortable('enable');
                                $("#sortable-nav ol").sortable('enable');
                            }
                        }, 500);
                    },
                    iconLibraries: [
                        'font-awesome-solid.min.json'
                    ],
                    iconLibrariesCss: [
                        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
                    ]
                })
            );
        });

        $('.selector-button').on('click', function(e) {
            $("#sortable-nav").sortable('disable');
            $("#sortable-nav ol").sortable('disable');
            $("#sortable-nav").on('click', function() {
                $("#sortable-nav").sortable('enable');
                $("#sortable-nav ol").sortable('enable');
                $("#sortable-nav").off('click');
            });
        });;

        /* Disable items */

        $('.disable-icon, .enable-icon').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $parentLi = $(this).closest('li');
            $("#sortable-nav ol").sortable('disable');
            $parentLi.toggleClass('disabled');
            if ($parentLi.hasClass('disabled')) {
                $parentLi.addClass('opacity-50');
            } else {
                $parentLi.removeClass('opacity-50');
            }
            setTimeout(function() {
                $("#sortable-nav ol").sortable('enable');
            }, 200);

            return;
        });

        /* Sortable */

        var makeSortable = function() {
            $("#sortable-nav").sortable({
                nested: false,
                distance: 20,
                delay: 200,
                containerSelector: "ol",
                handle: ".drag-me",
                onDragStart: function($item, container, _super) {
                    if ($item.hasClass('parent')) {
                        $item.find('ol').sortable('disable');
                    } else {
                        $('.parent').closest('ol').sortable('disable');
                    }
                    _super($item, container);
                },
                onDrop: function($item, container, _super) {
                    if ($item.hasClass('parent')) {
                        $item.find('ol').sortable('enable');
                    } else {
                        $('.parent').closest('ol').sortable('enable');
                    }
                    _super($item, container);
                }
            });

            $("#sortable-nav ol").sortable({
                group: 'nested'
            });
        };
        makeSortable();


        /* Content Editable */

        $('.cat-name').attr('contentEditable', true);

        /* Add new category */

        $('#add-category-btn').on('click', function() {
            $('#sortable-nav').append('<?php echo $new_list_html; ?>');
            $('.cat-name').attr('contentEditable', true);
            makeSortable();
        });

        /* Ajax POST */

        $('#save-changes-btn').on('click', function() {
            var navCats = new Object(),
                tablesIcons = new Object(),
                i = 0;
            $('#sortable-nav > li').each(function() {
                var catIndex = "navcat-" + i.toString();
                navCats[catIndex] = new Object();
                navCats[catIndex]["name"] = $(this).find('.cat-name').text();

                navCats[catIndex]["tables"] = new Array();
                navCats[catIndex]["is_disabled"] = new Array();

                $(this).find('ol > li').each(function() {
                    // register table icon
                    var table = $(this).attr('id'),
                        iconValue = $(this).find('i[data-iconpicker="true"]').attr('class');
                    tablesIcons[table] = iconValue;

                    // register table in nav category
                    navCats[catIndex]["tables"].push(table);
                    navCats[catIndex]["is_disabled"].push($(this).hasClass('disabled'));
                });

                i++;
            });
            var target = $('#result');
            $.ajax({
                url: '<?php echo GENERATOR_URL; ?>inc/ajax-organize-navbar.php',
                type: 'POST',
                data: {
                    navCats: navCats,
                    tablesIcons: tablesIcons
                },
            }).done(function(data) {
                target.html(data);
                $('html,body').animate({
                    scrollTop: 0
                }, 'slow');
            }).fail(function(data, statut, error) {
                console.log(error);
            });
        });
    });
</script>
