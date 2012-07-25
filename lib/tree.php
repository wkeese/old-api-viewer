<?php

// Since data directory may be outside of the api-viewer directory, use this php script to get the tree data

include(dirname(__FILE__) . "/../config.php");

$version = isset($_GET["v"]) ? $_GET["v"] : $defVersion;

echo file_get_contents($dataDir . $version . "/tree.json");

?>