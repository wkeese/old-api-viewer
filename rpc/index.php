<?php
/*	*Very* basic JSON-P service to get the info for an object.
 *
 *	TRT 20100717
 *	v. 1.0
 *
 *	url should look like this:
 *
 *	rpc/[version]/[object path]?limit|exclude=foo&callback=bar
 *
 *	limit is a comma-delimited list of the properties you want.  All are returned
 *	if not passed.
 *	exclude is a comma-delimited list of the properties you don't want.
 *
 *	if callback is passed, this will be wrapped with that function name.
 */

//	header("Content-Type: application/json");
	header("Content-Type: text/plain");

include(dirname(__FILE__) . "/../config.php");
include(dirname(__FILE__) . "/../lib/generate.php");

//	URL parsing.
$d = dir($dataDir);
$versions = array();
$has_version = false;
while(($entry = $d->read()) !== false){
	if(!(strpos($entry, ".")===0) && file_exists("../data/".$entry."/api.xml")){
		$versions[] = $entry;
	}
}
$d->close();
sort($versions);

$parts = array();
$is_page = false;
if(array_key_exists("qs", $_GET) && strlen($_GET["qs"])){
	$r = $_GET["qs"];
	$r = str_replace("jsdoc/", "", $r);
	$parts = explode("/", $r);

	//	check if the version exists
	$version = $parts[0];
	if(in_array($version, $versions)){
		array_shift($parts);
	} else {
		$version = $defVersion;
	}

	if(count($parts)){
		if(count($parts)>1){
			$page = implode(".", $parts);
		} else {
			$page = str_replace("/", ".", $parts[0]);
		}
		$is_page = true;
	}
} else {
	$page = $defPage;
	$version = $defVersion;
}

//	find out if we are filtering.
$do_filter = false;
if(array_key_exists("limit", $_GET) || array_key_exists("exclude", $_GET)){
	$do_filter = true;
	if(array_key_exists("limit", $_GET)){
		$limit = $_GET["limit"];
	} else {
		$exclude = $_GET["exclude"];
	}
}

//	ok, get the object.
$obj = generate_object($page, $version);

if(!$obj){
	print "";
	exit();
}

if($do_filter){
	$tmp = array();
	if(isset($limit)){
		//	we are looking for just specific information, so let's do that.
		$filters = explode(",", $limit);
	} else {
		$filters = explode(",", $exclude);
	}

	foreach($obj as $key=>$value){
		$test = in_array($key, $filters);
		if($test && isset($limit)){
			$tmp[$key] = $value;
		}
		if(!$test && isset($exclude)){
			$tmp[$key] = $value;
		}
	}

	$obj = $tmp;
}

$json = json_encode($obj);
if(array_key_exists("callback", $_GET)){
	$json = $_GET["callback"] . "(" . $json . ");";
}

print $json;
?>
