<?php
require_once __DIR__ . '/bootstrap.php';
admin_logout();
header('Location: index.php');
exit;
