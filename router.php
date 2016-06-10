<?php

if (file_exists($_SERVER["DOCUMENT_ROOT"] . $_SERVER["REQUEST_URI"])) {
    return false;
}

$__url = parse_url($_SERVER["REQUEST_URI"]);
if ('/' !== $__url['path']) {
	$_GET['babrw'] = $__url['path'];
}


require_once dirname(__FILE__).'/index.php';

