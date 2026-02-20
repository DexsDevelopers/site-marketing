<?php
require 'includes/db_connect.php';

$res = file_get_contents('http://localhost/site-marketing/api_marketing.php?action=cron_process');
print_r($res);
