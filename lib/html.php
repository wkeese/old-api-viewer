<?php
//	Fetch an HTML snippet based on the passed variables.

include(dirname(__FILE__) . "/../config.php");
include(dirname(__FILE__) . "/generate.php");

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

if(!isset($version)){ $version = $defVersion; }
if(!isset($page)){ $page = "dojo"; }

//	check if there's URL variables
if(isset($_GET["p"])){ $page = $_GET["p"]; }
if(isset($_GET["v"])){ $version = $_GET["v"]; }
if(strpos($page, "/") > 0){ $page = implode(".", explode("/", $page)); }

$docs = load_docs($version);

//	go find the object
$obj = generate_object($page, $version, $docs);
if($obj){
	print generate_object_html($page, $version, "", "", true, $docs);
	exit();
}
if(!$obj){
	$tmp = explode(".", $page);
	$find = array_pop($tmp);
	$tmp = implode(".", $tmp);
	$obj = generate_object($tmp, $version, $docs);
	if(!$obj){
		print "Object not found.";
		exit();
	}
	$field = null;
	foreach($obj["properties"] as $key=>$value){
		$test = array_pop(explode(".", $key));
		if($test == $find){
			$tmp = _generate_property_output($value, $key, $docs);
			print $tmp["details"];
			exit();
		}
	}
	if(!$field){
		foreach($obj["methods"] as $key=>$value){
			$test = array_pop(explode(".", $key));
			if($test == $find){
				$tmp = _generate_method_output($value, $key, $docs);
				print $tmp["details"];
				exit();
			}
		}
	}
	if(!$field){
		foreach($obj["events"] as $key=>$value){
			$test = array_pop(explode(".", $key));
			if($test == $find){
				$tmp = _generate_method_output($value, $key, $docs);
				print $tmp["details"];
				exit();
			}
		}
	}
	print "Object not found.";
	exit();
}

print "Object not found.";
exit();
?>
