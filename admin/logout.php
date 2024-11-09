<?php
use secure\Secure;

header("X-Robots-Tag: noindex", true);

session_start();
include_once '../conf/conf.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';
Secure::logout();
