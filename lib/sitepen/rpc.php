<?php

$RPC_CONFIG = array(
	// Human-readable description of the project
	"appName" => "Dojo Toolkit API Documentation RPC services",

	// API endpoint
	"url" => "/rpc"
);

require("RequestBroker.php");

global $broker;
$broker->debugLevel(RequestBroker::LOG_DEBUG);
$broker->rpcDebugLevel(RequestBroker::LOG_DEBUG);
$broker->baseUrl("/rpc");
$broker->service_dir("/lib/services");
$broker->run();

?>
