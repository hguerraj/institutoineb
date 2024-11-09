<?php

use phpformbuilder\Form;
use common\Utils;

if (!file_exists('../../../conf/conf.php')) {
    exit('<p class="alert alert-danger has-icon mt-5">Configuration file (conf/conf.php) not found</p>');
}
include_once '../../../conf/conf.php';
include_once GENERATOR_DIR . 'class/generator/Generator.php';

session_start();

// lock access on production server
if (ENVIRONMENT !== 'localhost' && GENERATOR_LOCKED === true) {
    include_once 'inc/protect.php';
}

include_once CLASS_DIR . 'phpformbuilder/Form.php';

if (isset($_SESSION['generator'])) {
    $generator   = $_SESSION['generator'];

    $generator->createDiffFileList();
    ?>

<p class="text-center"><button class="btn btn-info dropdown-toggle w-25 mb-5 collapsed" data-bs-toggle="collapse" data-bs-target="#comparison-helper" role="button" aria-expanded="false" aria-controls="relations"><?php echo NEED_HELP; ?>?</button></p>

<div class="collapse" id="comparison-helper">
    <div class="row justify-content-md-center mb-4">
        <div class="col-lg-8 mb-4">
            <?php echo SIDE_BY_SIDE_COMPARISON_NEED_HELP; ?>
        </div>
    </div>
</div>

    <?php if (!DEMO) {
        ?>
<div class="d-flex flex-column align-items-center text-center">
        <?php $generator->diff_files_form->render(); ?>
</div>

<div id="diff-files-container"></div>

<script>
        const $form = document.getElementById('diff-files-form');
        $form.addEventListener('submit', function(e) {
            e.preventDefault();
            let data = new FormData($form);
            fetch($form.getAttribute('action'), {
                method: 'post',
                body: new URLSearchParams(data).toString(),
                headers: {
                    'Content-type': 'application/x-www-form-urlencoded'
                },
                cache: 'no-store',
                credentials: 'include'
            }).then(function(response) {
                return response.text()
            }).then(function(data) {
                let $diffFilesContainer = document.getElementById('diff-files-container');
                $diffFilesContainer.innerHTML = '';
                loadData(data, '#diff-files-container');
            }).catch(function(error) {
                console.log(error);
            });
        });
        <?php
        // required for the dependent fields
        $script = $generator->diff_files_form->printJsCode(false, false);
        echo str_replace(['<script>', '</script>'], '', $script);
        ?>
</script>
        <?php
    }
}
