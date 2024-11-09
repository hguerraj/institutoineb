<script src="<?php echo ADMIN_URL; ?>assets/javascripts/loadjs.min.js"></script>
<script>
    const adminUrl = '<?php echo ADMIN_URL; ?>';
    const classUrl = '<?php echo CLASS_URL; ?>';
    const datetimepickersStyle = '<?php echo DATETIMEPICKERS_STYLE; ?>';
</script>
<?php if (ENVIRONMENT === 'development') { ?>
<script src="<?php echo ADMIN_URL; ?>assets/javascripts/project.js"></script>
<script async defer src="<?php echo CLASS_URL; ?>phpformbuilder/plugins/ajax-data-loader/ajax-data-loader.js"></script>
<?php } else { ?>
<script src="<?php echo ADMIN_URL; ?>assets/javascripts/project.min.js"></script>
<script async defer src="<?php echo CLASS_URL; ?>phpformbuilder/plugins/ajax-data-loader/ajax-data-loader.min.js"></script>
<?php } ?>
