<?php

// router file for rewriting with the internal php server
// $php -S localhost:8080 router.php


if (file_exists($_SERVER["DOCUMENT_ROOT"] . urldecode($_SERVER["SCRIPT_NAME"]))) {
    return false;
}

$__url = parse_url($_SERVER["REQUEST_URI"]);
if ('/' !== $__url['path']) {
	$_GET['babrw'] = $__url['path'];
}


require_once dirname(__FILE__).'/index.php';

