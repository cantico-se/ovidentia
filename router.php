<?php

// router file for rewriting with the internal php server
// $php -S localhost:8080 router.php


$__url = parse_url($_SERVER["REQUEST_URI"]);
$__path = urldecode($__url['path']);

if (file_exists($_SERVER["DOCUMENT_ROOT"] . $__path)) {
    return false;
}


if ('/' !== substr($__path, -1) && substr_count($__path, '/') > 1) {
    header('location:'.$_SERVER["REQUEST_URI"].'/');
    exit;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';


function __processRule($pattern, $target)
{
    global $__path;
    
    $pattern = str_replace('/', '\/', $pattern);
    
    if (preg_match('/'.$pattern.'/', substr($__path, 1), $match)) {
        
        $query = preg_replace_callback('/\$(\d+)/', function($m) use ($match) {
            return $match[$m[1]];
        }, parse_url($target, PHP_URL_QUERY));

	

        parse_str($query, $arr);
        
        $_GET = array_merge($_GET, $arr);
        return true;
    }
    
    return false;
}



function __processHtaccess()
{
    $htaccess = file_get_contents('.htaccess');
    if ($htaccess) {
        if (preg_match_all('/^\s*RewriteRule\s+([^\s]+)\s+([^\s]+)(?:\s+\[([^\s]+)\]$|$)/m', $htaccess, $match, PREG_SET_ORDER)) {
            foreach ($match as $rule) {
                $flags = array();
                if (isset($rule[3])) {
                    $flags = explode(',', $rule[3]);
                }
                if (__processRule($rule[1], $rule[2]) && in_array('L', $flags)) {
                    break;
                }
            }
        }
    }
}



__processHtaccess();



require_once dirname(__FILE__).'/index.php';

