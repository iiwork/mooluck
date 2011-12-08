<?php
/**
 * Front Controller 역할을 수행
 * param seperator "p" 
 */
include "../class/ii.php";
$ii->import("iiController");

if (!empty($_SERVER['QUERY_STRING'])) {
	$dir_module = "";
	$params = null;	
	
	// dir 조합
	foreach (explode("/", $_SERVER['QUERY_STRING']) as $val) {
		if ($val == "p") {
			$params = explode("/", str_replace("{$dir_module}/p/", "", "/{$_SERVER['QUERY_STRING']}"));
			break;
		}
		
		$dir_module .= "/{$val}";
	}
	
	$ii->import(substr($dir_module, 1));
	$filename = basename($dir_module);
	
	$controller = new $filename($ii);
	$controller->run($params);
}
?>