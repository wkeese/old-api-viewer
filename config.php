<?php

# basePath should represent the path from the DOCUMENT_ROOT
# NOTE: to have the permalinks/REST-ful resource urls work, 
# you'll need to make similar changes to the rewrite rules
$basePath = "";

$_base_url = "http://" . $_SERVER["HTTP_HOST"] . $basePath . "/";
//$_base_url = "./";
$dojoroot = "http://ajax.googleapis.com/ajax/libs/dojo/1.7";
// $dojoroot = "/trunk";	// local build

$_site_name = "The Dojo Toolkit";
$defVersion = "1.8";
$dataDir = dirname(__FILE__) . "/data/";
$defPage = "";
$default_theme = "dtk";
$theme = "dtk";
$filter_privates = true;
$use_cache = true;

