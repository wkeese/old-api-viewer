<?php

# basePath should represent the path from the DOCUMENT_ROOT
# NOTE: to have the permalinks/REST-ful resource urls work, 
# you'll need to make similar changes to the rewrite rules
$basePath = "/api";

$_base_url = "//" . $_SERVER["HTTP_HOST"] . $basePath . "/";
//$_base_url = "./";

# Location of dojo, without trailing slash
$dojoroot = "//ajax.googleapis.com/ajax/libs/dojo/1.8.0";
# $dojoroot = "/trunk";	// local build

$_site_name = "The Dojo Toolkit";

# Initially display this version
$defVersion = "1.8";

# Location of data files for each version.   This directory should contain a subdirectory for each product version,
# and the subdirectory should contain details.xml and tree.json
$dataDir = dirname(__FILE__) . "/../api_data/";

# Bug database: if $bugdb is specified as a non-empty string, add an "error in the documentation?" link to the
# bottom of each tab that will navigate to specified link appended with the tab's URL
$bugdb = "https://docs.google.com/spreadsheet/viewform?hl=en_US&formkey=dFlDcHEyaHMwbEd4MFBObkNrX0E1MFE6MQ&entry_0=";

# Path and URL to reference doc: if set, use the specified directory to check if corresponding reference doc exists,
# and then use the url to generate a link to that reference doc.
# The reference doc directory structure needs to be similar to api URLs, with version numbers for the top level
# directories.
$refdoc = array(
	"dir" => dirname(__FILE__) . "/../reference-guide/",
	"url" => "/reference-guide/",
	"suffix" => ".html"
);

$defPage = "";
$default_theme = "dtk";
$theme = "dtk";
$filter_privates = true;

# Set to false to regenerate the page for a module every time you view it.
# Useful while working on documentation, or the doc parser and api viewers themselves.
$use_cache = false;

