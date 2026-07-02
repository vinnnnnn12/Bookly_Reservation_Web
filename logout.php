<?php
require_once __DIR__ . '/session_init.php';
session_unset();
session_destroy();
header('Location: login.php');
exit();
?>