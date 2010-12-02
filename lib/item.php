<?php
/*	item.php
 *	TRT 2009-10-14
 *
 *	The file that generates the HTML that gets sent out
 *	in response to a tree click using the standalone API
 *	tool.
 */

include(dirname(__FILE__) . "/../config.php");
include("cache.php");
include("generate.php");

//	begin the real work.
if(!isset($version)){ $version = $defVersion; }
if(!isset($page)){ $page = "dojo"; }

//	check if there's URL variables
if(isset($_GET["p"])){ $page = $_GET["p"]; }
if(isset($_GET["v"])){ $version = $_GET["v"]; }
if(strpos($page, "/") > 0){ $page = implode(".", explode("/", $page)); }

//	test to see if this has been cached first.
if($use_cache){
	$html = cache_get($version, $page);
	if($html){
		echo $html;
		exit();
	}
}

//	if we got here, we're not cached so generate our HTML.
$html = generate_object_html($page, $version, $_base_url);

if($use_cache){
	$success = cache_set($version, $page, $html);
}

echo $html;
?>
