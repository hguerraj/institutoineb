<?php
if (file_exists($_SERVER['SCRIPT_NAME'] . 'install.lock')) {
    header('Location: /');
    exit;
}

include_once 'do-install.php';
