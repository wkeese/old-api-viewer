<?php
	include("../lib/generate.php");

/*
	$page = "dijit.layout.ContentPane";
	$version = "1.4";
	echo generate_object_html($page, $version, "../");
 */

	$tree = generate_object_tree("1.4", array(
		"dojo"=>-1,
		"dijit"=>-1,
		"dojox"=>-1,
		"djConfig"=>-1
	));
	echo generate_object_tree_html($tree, "dojox", "http://dojo-api.local/html/1.4/", ".html");
?>
