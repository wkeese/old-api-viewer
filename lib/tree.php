<?php

// Since data directory may be outside of the api-viewer directory, use this php script to get the tree data

include(dirname(__FILE__) . "/../config.php");

$version = isset($_GET["v"]) ? $_GET["v"] : $defVersion;

// Get path to tree file, protecting against ".." inserted into $version string by a malicious user
$path = $dataDir .  preg_replace("/\\.\\.+/", "", $version) . "/tree.json";

if(file_exists($path)){
    header("Content-Type: application/json");
    echo file_get_contents($path);
}else{
    header(":", true, 400);
    header("Content-type: text/plain");
    echo "Cannot open file $path";
}

?>