<?php
use phpformbuilder\Form;
use phpformbuilder\FormExtended;

include_once '../../conf/conf.php';

session_start();

if (isset($_COOKIE['bootstrap_theme']) && ctype_lower($_COOKIE['bootstrap_theme'])) {
    $_SESSION['style-switcher-form']['bootstrap_theme'] = $_COOKIE['bootstrap_theme'];
} else {
    $_SESSION['style-switcher-form']['bootstrap_theme'] = BOOTSTRAP_THEME;
}

$available_styles = array('primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark');

if (isset($_COOKIE['navbar_style']) && in_array($_COOKIE['navbar_style'], $available_styles)) {
    $_SESSION['style-switcher-form']['navbar_style'] = $_COOKIE['navbar_style'];
} else {
    $_SESSION['style-switcher-form']['navbar_style'] = NAVBAR_STYLE;
}

if (isset($_COOKIE['sidebar_style']) && in_array($_COOKIE['sidebar_style'], $available_styles)) {
    $_SESSION['style-switcher-form']['sidebar_style'] = $_COOKIE['sidebar_style'];
} else {
    $_SESSION['style-switcher-form']['sidebar_style'] = SIDEBAR_STYLE;
}

$form = new FormExtended('style-switcher-form', 'vertical');
$form->setMode('development');
$form->useLoadJs('core');

$form->setOptions(array('elementsWrapper' => '<div class="bs5-form-stacked-element mb-5"></div>'));

// Bootstrap theme
$form->addBootstrapThemeSelect();

// navbar_style
$form->addNavStyleSelect('navbar_style', NAVBAR_STYLE_TXT);

// sidebar_style
$form->addNavStyleSelect('sidebar_style', SIDEBAR_STYLE_TXT);

$form->centerContent();
$form->addBtn('button', 'revert-styles', 1, STYLES_REVERT, 'class=btn btn-warning');

$form->render();
$form->printJsCode();
?>
<script>
    let bootstrapThemeClassName = 'palette-' + $('select[name="bootstrap_theme"]').val(),
        initialBootstrapThemeClassName = 'palette-' + $('select[name="bootstrap_theme"]').val(),
        navbarStyle = $('select[name="navbar_style"]').val(),
        sidebarStyle = $('select[name="sidebar_style"]').val();

    const loadNewBootstrapThemeCss = function(oldBootstrapTheme, newBootstrapTheme) {
        oldBootstrapTheme = oldBootstrapTheme.replace('palette-', '');
        newBootstrapTheme = newBootstrapTheme.replace('palette-', '');
        loadjs([
            'assets/stylesheets/themes/' + newBootstrapTheme + '/bootstrap.min.css',
            'assets/stylesheets/themes/' + newBootstrapTheme + '/admin.min.css'
        ]
        );
        $('link[rel=stylesheet][href*="admin/assets/stylesheets/themes/' + oldBootstrapTheme + '/bootstrap.min.css"]').remove();
        $('link[rel=stylesheet][href*="admin/assets/stylesheets/themes/' + oldBootstrapTheme + '/admin.min.css"]').remove();
    }

    const updateStyleSelect = function(selectId, bootstrapThemeClassName, newClassName) {
        $('#' + selectId + ' option').each(function(_index, el) {
            $(el).attr('data-html', $(el).attr('data-html').replace(bootstrapThemeClassName, newClassName));
        });
        window.slimSelects[selectId].data.data.forEach(d => {
            d.innerHTML = d.data.html;
        });
        window.slimSelects[selectId].setData(window.slimSelects[selectId].data.data);
    }

    $('select[name="bootstrap_theme"]').on('change', function() {
        const newClassName = 'palette-' + $(this).val();
        loadNewBootstrapThemeCss(bootstrapThemeClassName, newClassName);
        updateStyleSelect('navbar_style', bootstrapThemeClassName, newClassName);
        updateStyleSelect('sidebar_style', bootstrapThemeClassName, newClassName);
        // console.log('replace ' + bootstrapThemeClassName + ' with ' + newClassName);

        bootstrapThemeClassName = newClassName;
    });

    $('select[name="navbar_style"]').on('change', function() {
        const newNavbarStyle = $(this).val();
        $('#navbar-main').removeClass('bg-' + navbarStyle).addClass('bg-' + newNavbarStyle);
        navbarStyle = newNavbarStyle;
    });

    $('select[name="sidebar_style"]').on('change', function() {
        const newSidebarStyle = $(this).val();
        $('#sidebar-main').removeClass('bg-' + sidebarStyle).addClass('bg-' + newSidebarStyle);
        sidebarStyle = newSidebarStyle;
    });

    $('button[name="revert-styles"').on('click', function() {
        window.slimSelects['navbar_style'].set('<?php echo ORIGINAL_NAVBAR_STYLE; ?>');
        window.slimSelects['sidebar_style'].set('<?php echo ORIGINAL_SIDEBAR_STYLE; ?>');
        window.slimSelects['bootstrap_theme'].set('<?php echo ORIGINAL_BOOTSTRAP_THEME; ?>');
        // when slimselect changes the value it reverts the html to the initial template,
        // so we have to replace the initial Bootstrap classname (from the php userConf or cookie) with the original (defined in the php userConf).
        $('.' + initialBootstrapThemeClassName).removeClass(initialBootstrapThemeClassName).addClass('palette-<?php echo ORIGINAL_BOOTSTRAP_THEME; ?>');
    });

    document.getElementById('style-switcher-wrapper').addEventListener('hide.bs.offcanvas', () => {
        const $form = document.getElementById('style-switcher-form');
        let data = new FormData($form);
        fetch('<?php echo ADMIN_URL; ?>/inc/style-register.php', {
            method: 'post',
            body: new URLSearchParams(data).toString(),
            headers: {
                'Content-type': 'application/x-www-form-urlencoded; charset=utf-8'
            },
            cache: 'no-store',
            credentials: 'include'
        }).then(function (response) {
            return response.text()
        }).then(function (data) {
            $('#msg').html(data);
        }).catch(function (error) {
            console.log(error);
        });
    });
</script>
