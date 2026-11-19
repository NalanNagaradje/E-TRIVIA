<?php
date_default_timezone_set('Europe/Paris');
$api_url = getenv('API_URL') ?: 'http://api:5000';
define('API_BASE_URL', $api_url);
?>
