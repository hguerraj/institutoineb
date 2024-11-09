<?php

use phpformbuilder\Form;
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Factory\RendererFactory;
use Jfcherng\Diff\Renderer\RendererConstant;

if (!file_exists('../../../conf/conf.php')) {
    exit('<p class="alert alert-danger has-icon mt-5">Configuration file (conf/conf.php) not found</p>');
}
include_once '../../../conf/conf.php';

session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('diff-files-form') === true && file_exists(BACKUP_DIR . $_POST['file-to-diff'])) {
    require_once ROOT . 'vendor/autoload.php';
    $file_to_diff = addslashes($_POST['file-to-diff']);
    $old_file = BACKUP_DIR . $file_to_diff;
    $new_file = ADMIN_DIR . $file_to_diff;
} elseif (isset($_POST['file-to-diff']) && !file_exists(BACKUP_DIR . $_POST['file-to-diff'])) {
    exit('<p class="alert alert-danger has-icon mt-5">Unable to locate ' . BACKUP_DIR . $_POST['file-to-diff'] . ' in the backup directory.</p>');
} else {
    exit('<p class="alert alert-danger has-icon mt-5">The security token has expired. Please reload the CRUD generator page then retry the comparison.</p>');
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mb-15">
            <div id="merge-result"></div>
            <div id="file-diff-content-wrapper">
                <h2 class="text-center"><?php echo SIDE_BY_SIDE_COMPARISON ?> - <?php echo $file_to_diff; ?></h2>
                <p class="text-center"><?php echo SIDE_BY_SIDE_COMPARISON_HELPER; ?></p>
                <?php

                // options for Diff class
                $diff_options = [
                    // show how many neighbor lines
                    // Differ::CONTEXT_ALL can be used to show the whole file
                    'context' => Differ::CONTEXT_ALL,
                    // ignore case difference
                    'ignoreCase' => false,
                    // ignore whitespace difference
                    'ignoreWhitespace' => false,
                ];

                // options for renderer class
                $renderer_options = [
                    // how detailed the rendered HTML is? (none, line, word, char)
                    'detailLevel' => 'line',
                    // renderer language: eng, cht, chs, jpn, ...
                    // or an array which has the same keys with a language file
                    'language' => 'eng',
                    // show line numbers in HTML renderers
                    'lineNumbers' => true,
                    // show a separator between different diff hunks in HTML renderers
                    'separateBlock' => true,
                    // show the (table) header
                    'showHeader' => true,
                    // the frontend HTML could use CSS "white-space: pre;" to visualize consecutive whitespaces
                    // but if you want to visualize them in the backend with "&nbsp;", you can set this to true
                    'spacesToNbsp' => false,
                    // HTML renderer tab width (negative = do not convert into spaces)
                    'tabSize' => 4,
                    // this option is currently only for the Combined renderer.
                    // it determines whether a replace-type block should be merged or not
                    // depending on the content changed ratio, which values between 0 and 1.
                    'mergeThreshold' => 0.8,
                    // change this value to a string as the returned diff if the two input strings are identical
                    'resultForIdenticals' => null,
                    // extra HTML classes added to the DOM of the diff container
                    'wrapperClasses' => ['diff-wrapper'],
                ];
                $html = DiffHelper::calculateFiles($old_file, $new_file, 'SideBySide', $diff_options, $renderer_options);
                if (!empty($html)) {
                    echo $html;
                    if (DEMO !== true) {
                        ?>
                <div class="text-center my-5">
                    <input id="file-to-diff-input" type="hidden" value="<?php echo $file_to_diff; ?>">
                    <button type="button" id="do-merge" class="btn btn-lg btn-primary"><?php echo MERGE; ?></button>
                </div>
                        <?php
                    } else {
                        ?>
                <div class="alert alert-info has-icon">
                    <h4 class="mb-0">The Side by side comparison module is disabled in this demo.</h4>
                </div>
                        <?php
                    }
                } else {
                    ?>
                <div class="alert alert-info has-icon my-5">
                    <?php echo NOTHING_TO_MERGE; ?>
                </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php
/* Pass $a and $b to javascript */
echo '<script>var left=' . json_encode(explode("\n", file_get_contents($old_file))) . ', right=' . json_encode(explode("\n", file_get_contents($new_file))) . ';</script>';
?>
<script>
    if (!$('#diff-files-container').hasClass('loaded')) {
        window.enableFileDiff = function() {
            $('.diff-wrapper').phpdiffmerge({
                failedToMergeMsg: '<?php echo UNABLE_TO_MERGE; ?>',
                left: left,
                right: right,
                merged: function(merge, left, right) {
                    const filePath = $('#file-to-diff-input').val();
                    $.post(
                        'inc/ajax-diff-merge.php', {
                            action: 'register_merged_content',
                            merge: merge,
                            filepath: filePath
                        },
                        function() {
                            $('#merge-result').html('<div class="alert alert-success has-icon alert-dismissible fade show" role="alert">' + '<?php echo addslashes(MERGE_DONE); ?>'.replace('%FILE%', filePath) + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                            $('#file-diff-content-wrapper').html('');
                            window.scrollTo(0, 0);
                        }
                    );
                },
                button: '#do-merge'
                    /* Use your own "Merge now" button */
                    // ,button: '#myButtonId'
                    // pupupResult: true
                    /* uncomment to see the complete merge in a pop-up window */
                    /* uncomment to pass additional infos to the console. */
                    // ,debug: true
            });
            $('.diff-wrapper thead th:first-child').html('<?php echo BACKUP_VERSION; ?> <span class="badge badge-light"><?php echo str_replace(ROOT, '', BACKUP_DIR) . $_POST['file-to-diff']; ?></span>');
            $('.diff-wrapper thead th:nth-child(2)').html('<?php echo NEW_VERSION; ?> <span class="badge badge-light"><?php echo str_replace(ROOT, '', ADMIN_DIR . $_POST['file-to-diff']) ?></span>');
        }

        loadjs([
                '<?php echo GENERATOR_URL; ?>generator-assets/lib/php-diff/diff-table.min.css',
                '<?php echo GENERATOR_URL; ?>generator-assets/lib/php-diff/jquery.phpdiffmerge.min.js'
            ], 'diff-files',
            {
                async: false
            }
        );

        loadjs.ready('diff-files', function() {
            $('#diff-files-container').addClass('loaded')
            enableFileDiff();
        });
    } else {
        enableFileDiff();
    }
</script>
