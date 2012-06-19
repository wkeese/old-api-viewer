<?php
	$start = microtime(true);
	include("../lib/generate.php");

/*
	$page = "dijit.layout.ContentPane";
	$version = "1.8";
	echo generate_object_html($page, $version, "../");
 */

/*
	$tree = generate_object_tree("1.8", array(
		"dojo"=>-1,
		"dijit"=>-1,
		"dojox"=>-1,
		"djConfig"=>-1
	));
	echo generate_object_tree_html($tree, "dojox", "http://dojo-api.local/html/1.8/", ".html");
*/
	$page = "dijit.form.CurrencyTextBox";
	$version = "1.8";

	if(count($_GET)){
		if(array_key_exists("p", $_GET)) $page = $_GET["p"];
		if(array_key_exists("v", $_GET)) $version = $_GET["v"];
	}

	print '<link rel="stylesheet" href="../css/jsdoc.css" type="text/css" media="all" />';


	print generate_object_html($page, $version);
	print "<div>Completed in " . round(microtime(true) - $start, 4) . "s</div>";
	//print generate_object_html($page, $version);

	/*
	print "<pre>";
//	print json_encode(generate_object($page, $version)) . "\n\n";
	print_r(generate_object($page, $version));
	print "</pre>";
	//	*/
?>
