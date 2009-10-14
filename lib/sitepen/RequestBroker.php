<?php
/**
 * @package SitePen_Small_Platform
 * @copyright Copyright (c) 2009 SitePen, Inc.
 */

/**
 * SitePen Platform request broker
 *
 * FIXME: add a good description here, and make sure the phpdoc tags are
 * all up to par.
 *
 * @package SitePen_Small_Platform
 */
class RequestBroker {
	/**
	 * The URI prefix for requests we broker.
	 * @var string
	 */
	private $baseUrl = "/lib";
	private $service_dir = "";

	/**
	 * The debugLevel level. RequestBroker::DEBUG, RequestBroker::INFO, RequestBroker::WARN, RequestBroker::ERROR
	 * @var integer
	 */
	private $debugLevel = self::LOG_NONE;
	const LOG_DEBUG = 3;
	const LOG_INFO = 2;
	const LOG_WARN = 1;
	const LOG_ERROR = 0;
	const LOG_NONE = -1;

	/**
	 * The debugLevel level, as applied to RPC responses.
	 *
	 * Use this to send back logging in the response. The logging returned will
	 * be at the level of max(debugLevel, rpcDebugLevel).
	 * @var integer
	 */
	private $rpcDebugLevel = self::LOG_NONE;

	/**
	 * Logging messages. Each message is an array with members "message" and "level".
	 * @var array
	 */
	private $log = array();

	/**
	 * Parsed JSON-RPC request object
	 * @var object
	 */
	private $rpcRequest = null;

	/**
	 * HTTP response code for the current request. Default 200.
	 * @var integer
	 */
	private $httpResponseCode = 200;

	/**
	 * Result object to return in the JSON response
	 * @var mixed
	 */
	private $responseResult = null;

	// JSON-RPC error codes
	const JSON_RPC_ERROR_PARSE_ERROR = -32700;
	const JSON_RPC_ERROR_INVALID_REQUEST = -32600;
	const JSON_RPC_ERROR_METHOD_NOT_FOUND = -32601;
	const JSON_RPC_ERROR_INVALID_PARAMS = -32602;
	const JSON_RPC_ERROR_INTERNAL_ERROR = -32603;

	//	additional error codes, mostly dealing with auth
	const JSON_RPC_ERROR_NOT_AUTHORIZED = -32001;
	const JSON_RPC_ERROR_FORBIDDEN = -32000;
	const JSON_RPC_ERROR_WRONG_PASSWORD = -2;
	const JSON_RPC_ERROR_PASSWORD_MISMATCH = -1;
	const JSON_RPC_ERROR_GENERAL_ERROR = 0;

	/**
	 * Error to return in the JSON response
	 * @var mixed
	 */
	private $responseError = null;

	//
	// singleton stuff
	//
	private static $instance;
	private function __construct(){ }
	public static function singleton(){
		if(!isset(self::$instance)){
			$c = __CLASS__;
			self::$instance = new $c;
			self::$instance->rpcRequest = new stdClass();
		}
		return self::$instance;
	}

	public final function __clone(){
		trigger_error("Cannot clone the request broker.", E_USER_ERROR);
	}

	/**
	 * Get or set a private property on this object.
	 *
	 * Pass a single parameter to act as a setter, no parameters to act
	 * as a getter. No matter how you call this, it always returns the
	 * current (post-modification) value.
	 *
	 * <code>
	 * $endpoint = $broker->baseUrl(); // get the current base URL
	 * $broker->baseUrl("/custom/api"); // set a new base URL
	 * </code>
	 *
	 * @return mixed
	 */
	public function __call($fn, $args){
		$whitelist = array("baseUrl", "service_dir", "debugLevel", "rpcDebugLevel");
		if(is_null($fn) || !in_array($fn, $whitelist)){ return null; }
		if(count($args)){
			// setter
			$this->$fn = $args[0];
		}
		// getter
		return $this->$fn;
	}

	/**
	 * Log something.
	 *
	 * @param string $msg Message to log
	 * @param integer $level Logging level (RequestBroker::DEBUG, RequestBroker::INFO, RequestBroker::WARN, RequestBroker::ERROR)
	 * @return void
	 */
	private function _log($msg, $level){
		$this->log[] = array("message"=>$msg, "level"=>$level);
	}

	/**
	 * Output a debug message
	 *
	 * @param string $msg Message to log
	 * @return void
	 */
	public function debug($msg){
		if($this->debugLevel >= self::LOG_DEBUG){
			$this->_log($msg, self::LOG_DEBUG);
		}
	}

	/**
	 * Output a log message
	 *
	 * @param string $msg Message to log
	 * @return void
	 */
	public function log($msg){
		if($this->debugLevel >= self::LOG_INFO){
			$this->_log($msg, self::LOG_INFO);
		}
	}

	/**
	 * Output a warning message
	 *
	 * @param string $msg Message to log
	 * @return void
	 */
	public function warn($msg){
		if($this->debugLevel >= self::LOG_WARN){
			$this->_log($msg, self::LOG_WARN);
		}
	}

	/**
	 * Output an error message
	 *
	 * @param string $msg Message to log
	 * @return void
	 */
	public function error($msg){
		if($this->debugLevel >= self::LOG_ERROR){
			$this->_log($msg, self::LOG_ERROR);
		}
	}

	/**
	 * Retrieve the parsed JSON-RPC request object, if any.
	 *
	 * This is only really valid after calling parseRequest(), of course.
	 *
	 * @return object
	 */
	public function getRequest(){
		return $this->rpcRequest;
	}

	/**
	 * Normalize the request coming in from the URI.
	 *
	 * Stores the request object as a member of $broker, retrievable by $broker->getRequest().
	 *
	 * @return boolean True if the request needs a response, else false
	 */
	private function parseRequest(){
		// FIXME: do error handling for requests using GET, etc...
		if($_SERVER['REQUEST_URI'] == "{$this->baseUrl}/SMD"){
			$this->sendSmdResponse();
			return false;
		}else{
			// read the raw POST body
			// TODO: handle invalid JSON
			$this->rpcRequest = $this->fromJson(file_get_contents("php://input"));

			// split out the object & method
			$parts = explode(".", $this->rpcRequest->method);
			$method = array_pop($parts);
			$obj = implode("_", $parts);
			$this->rpcRequest->obj = strlen($obj) ? $obj : "__DEFAULT_RPC_OBJECT";
			$this->rpcRequest->method = $method;
			return true;
		}
	}

	/**
	 * The main request dispatcher.
	 *
	 * It attempts to match the request URI with the object and method requested,
	 * then calls the method.
	 *
	 * @return void
	 */
	public function run(){
		if(!$this->parseRequest()){ return; }

		$r = $this->rpcRequest;
		$classname = $r->obj;
		$method = $r->method;
		$params = isset($r->params) ? $r->params : array();
		ob_start();

	 	// FIXME: Allow for the PHP5 magic __call() method to handle arbitrary/non-
		//        existent methods on the requested object? We'd probably need a
		//        _toSmd() method on the object, then, since there'd be no way to
		//        use reflection to figure out available methods. Either that, or
		//        set it up so you manually register methods with the API.

		// try and instantiate the requested object and call the method
		if(class_exists($classname)){
			// TODO: perhaps attempt to pass, say, an object ID into the constructor?
			$fn = array(new $classname, $method);
			if(is_callable($fn)){
				// call the requested method
				$this->responseResult = call_user_func_array($fn, $params);
			}else{
				// method doesn't exist
				$this->error("ROUTING ERROR: Couldn't find {$classname}->{$method}().");
				$this->rpcError(self::JSON_RPC_ERROR_METHOD_NOT_FOUND);
			}
		}else{
			// can't find the requested class
			$this->error("INSTANTIATION ERROR: Couldn't get the object [$classname]");
			$this->rpcError(self::JSON_RPC_ERROR_INVALID_REQUEST);
		}

		// ignore any output from print or echo, and clear the buffer
		ob_end_clean();

		$this->sendResponse();
	}

	/**
	 * Generate and return a JSON-RPC response.
	 *
	 * @link http://groups.google.com/group/json-rpc/web/json-rpc-1-2-proposal
	 * @return void
	 */
	private function sendResponse(){
		if(!isset($this->rpcRequest->id)){ return; } // never respond to Notifications

		$response = new stdClass();
		$response->jsonrpc = "2.0";
		$response->id = $this->rpcRequest->id;

		if($this->responseError){
			$response->error = $this->responseError; // error response
		}else{
			$response->result = $this->responseResult; // normal response
		}

		if($this->rpcDebugLevel != self::LOG_NONE){
			$response->log = array();
			foreach($this->log as $msg){
				if($msg["level"] <= $this->rpcDebugLevel){
					$response->log[] = $msg["message"];
				}
			}
		}

		echo $this->toJson($response);
	}

	/**
	 * Generate and send the SMD for this API.
	 *
	 * @link http://groups.google.com/group/json-schema/web/service-mapping-description-proposal
	 * @return void
	 */
	private function sendSmdResponse(){
		global $RPC_CONFIG;
		$url = "";
		$app = "";
		if(isset($RPC_CONFIG)){
			if(array_key_exists("url", $RPC_CONFIG)){
				$url = $RPC_CONFIG["url"];
			}else{
				$s = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "s" : "";
				$server = trim($_SERVER["SERVER_NAME"]);
				$base = trim($this->baseUrl);
				$url = "http{$s}://{$server}{$base}";
			}
			$app = array_key_exists("appName", $RPC_CONFIG) ? $RPC_CONFIG["appName"] . " " : "";
		}

		$smd = new stdClass();
		$smd->SMDVersion = "2.0";
		$smd->envelope = "JSON-RPC-2.0";
		$smd->transport = "POST";
		$smd->target = $this->baseUrl;
		$smd->id = $url;
		$smd->description = "{$app}JSON-RPC 2.0 API";
		$smd->services = $this->buildSmdServices();

		echo $this->toJson($smd);
	}

	/**
	 * Create an array of available services for use in putting together
	 * this API's SMD.
	 *
	 * This works by reading every file in the lib directory and
	 * attempting to autoload the class associated with it (which it
	 * arrives at by simply chopping ".php" off of the end of the filename).
	 * If the class is successfully loaded, each public method on the class
	 * gets added as an API call.
	 *
	 * @return array
	 */
	private function buildSmdServices($dir=null){
		// FIXME: at some point we'll probably want to let each class
		//        specify what methods are available at what security
		//        level (perhaps even to the extent of having some
		//        methods completely unreachable via the API).
		$services = array();
		if(is_null($dir)){
			$dir = dirname(__FILE__)."/".$this->service_dir;
		}
		foreach(scandir($dir) as $base){
			$fpath = str_replace(dirname(__FILE__)."/" . $this->service_dir . "/", "", "$dir/$base");
			if($base == "." || $base == ".."){ continue; }
			$fname = str_replace("/", "_", $fpath);
			$classname = basename($fname, ".php");
			if(is_dir("$dir/$base")){
				// recurse into directories
				$services = array_merge($services, $this->buildSmdServices("$dir/$base"));
			}elseif(class_exists($classname)){
				// pick method names out of classes
				$cls = new ReflectionClass($classname);
				foreach($cls->getMethods() as $method){
					if($method->isPublic()){
						$smdName = str_replace("_", ".", $classname);
						$params = array();
						foreach($method->getParameters() as $param){
							$params[] = array(
								"name" => $param->getName(),
								"optional" => $param->isOptional(),
							);
						}
						$s = new stdClass();
						if(count($params)){ $s->parameters = $params; }
						$services["$smdName.{$method->getName()}"] = $s;
					}
				}
			}
		}
		return $services;
	}

	/**
	 * Get or set the response's HTTP status code.
	 *
	 * Pass the $code parameter to set a new code.
	 *
	 * <code>
	 * $code = $broker->httpCode(); // get the current status
	 * $broker->httpCode(404); // Not Found
	 * </code>
	 *
	 * @param integer $code (optional) HTTP status code to set
	 * @return string Full HTTP response status code string
	 */
	public function httpCode($code=null){
		// FIXME: this doesn't take care to differentiate which codes are
		//        in HTTP/1.0 vs HTTP/1.1; should it?
		$phrases = array(
			100 => "Continue",
			101 => "Switching Protocols",

			200 => "OK",
			201 => "Created",
			202 => "Accepted",
			203 => "Non-Authoritative Information",
			204 => "No Content",
			205 => "Reset Content",
			206 => "Partial Content",

			300 => "Multiple Choices", // useful for disambiguation?
			301 => "Moved Permanently",
			302 => "Found",
			303 => "See Other",
			304 => "Not Modified",
			305 => "Use Proxy",
			307 => "Temporary Redirect",

			400 => "Bad Request",
			401 => "Unauthorized", // use when auth'ing will make a request valid
			403 => "Forbidden", // use when auth'ing won't make a difference
			404 => "Not Found",
			405 => "Method Not Allowed", // HTTP method, e.g. using GET when only POST is valid
			406 => "Not Acceptable",
			407 => "Proxy Authentication Required",
			408 => "Request Timeout",
			409 => "Conflict",
			410 => "Gone", // something existed but was intentionally removed
			411 => "Length Required",
			412 => "Precondition Failed",
			413 => "Request Entity Too Large",
			414 => "Request-URI Too Long",
			415 => "Unsupported Media Type",
			416 => "Requested Range Not Satisfiable",
			417 => "Expectation Failed",

			500 => "Internal Server Error",
			501 => "Not Implemented",
			502 => "Bad Gateway",
			503 => "Service Unavailable",
			504 => "Gateway Timeout",
		);

		if(!is_null($code)){
			if(!array_key_exists($code, $phrases)){
				$code = 404;
			}
			header("HTTP/1.1 $code {$phrases[$code]}");
			$this->httpResponseCode = $code;
		}

		return "HTTP/1.1 $this->httpResponseCode {$phrases[$this->httpResponseCode]}";
	}

	/**
	 * Get or generate a JSON-RPC error object to use in the response.
	 *
	 * This internally sets the HTTP response code appropriately (the codes
	 * are JSON-RPC spec), so you don't have to worry about doing it yourself.
	 *
	 * <code>
	 * // throw an application error with a custom error string; returns HTTP status 200
	 * $broker->rpcError("I'm sorry Dave, I can't do that.");
	 *
	 * // create a JSON-RPC error; returns HTTP status 500
	 * $broker->rpcError(null, null, RequestBroker::JSON_RPC_ERROR_INVALID_PARAMS);
	 * </code>
	 *
	 * @link http://groups.google.com/group/json-rpc/web/json-rpc-1-2-proposal
	 * @param string $message Error message to return
	 * @param object $data Error data to return
	 * @param integer $error JSON-RPC error code
	 * @return array Dictionary containing "code" and "message"
	 */
	public function rpcError($message=null, $data=null, $error=null){
		$phrases = array(
			self::JSON_RPC_ERROR_PARSE_ERROR => array("s"=>"Parse Error", "h"=>500),
			self::JSON_RPC_ERROR_INVALID_REQUEST => array("s"=>"Invalid Request", "h"=>400),
			self::JSON_RPC_ERROR_METHOD_NOT_FOUND => array("s"=>"Method not found", "h"=>404),
			self::JSON_RPC_ERROR_INVALID_PARAMS => array("s"=>"Invalid params", "h"=>500),
			self::JSON_RPC_ERROR_INTERNAL_ERROR => array("s"=>"Internal error", "h"=>500),
			self::JSON_RPC_ERROR_NOT_AUTHORIZED => array("s"=>"Unauthorized", "h"=>401),
			self::JSON_RPC_ERROR_FORBIDDEN => array("s"=>"Forbidden", "h"=>403),
			self::JSON_RPC_ERROR_GENERAL_ERROR => array("s"=>"Application error", "h"=>200)
		);

		$rpcError = (!is_null($error) && array_key_exists($error, $phrases)) ? $error : self::JSON_RPC_ERROR_GENERAL_ERROR;
		$e = $phrases[$rpcError];
		$this->httpCode($e["h"]);
		$this->responseError = new stdClass();
		$this->responseError->code = !is_null($error) ? $error : $rpcError;
		$this->responseError->message = is_null($message) ? $e["s"] : $message;
		if(!is_null($data)){
			$this->responseError->data = $data;
		}

		/*
		if(class_exists("FB")){
			FB::error($this->responseError->message);
		}
		 */
		return $this->responseError;
	}

	/**
	 * Encode an object or array as a JSON string.
	 *
	 * @param mixed $object Object or array to encode
	 * @return string JSON-encoded string
	 */
	public function toJson($object){
		// FIXME: right now this only supports the official PHP JSON extension,
		//        which is officially part of the language as of 5.2, and exists
		//        as a PECL extension prior to that. It might be good to update
		//        this to support PEAR JSON, Zend_Json, etc...
		return json_encode($object);
	}

	/**
	 * Decode a JSON string as an object or array.
	 *
	 * @param string $json JSON-encoded string
	 * @return mixed Decoded PHP object
	 */
	public function fromJson($json){
		// FIXME: right now this only supports the official PHP JSON extension,
		//        which is officially part of the language as of 5.2, and exists
		//        as a PECL extension prior to that. It might be good to update
		//        this to support PEAR JSON, Zend_Json, etc...
		return json_decode($json);
	}
}


/**
 * Attempt to load objects by class name from the ./lib directory.
 *
 * Underscores in class names are turned into slashes; this allows for nested
 * folder organization similar to PEAR, Zend Framework, etc. As a consequence,
 * it's better to use CamelCasedNames than underscore_separated_ones for the
 * "real" class names.
 *
 * <code>
 * // load up the file in ./lib/services/data/archiver.php
 * $obj = new services_data_archiver();
 * </code>
 *
 * @return void
 */
function __autoload($cls){
	// automatically folderize underscore-separated words
	$fname = str_replace("_", "/", $cls);
	$f = dirname(__FILE__)."/services/{$fname}.php";
	if(file_exists($f)){
		include_once($f);
	}
}


/**
 * Stub class for calling the root of the RPC endpoint, handling errors, etc.
 *
 * @package SitePen_Small_Platform
 */
class __DEFAULT_RPC_OBJECT{}


/**
 * @global RequestBroker $broker
 */
$broker = RequestBroker::singleton();
// provide the global var $broker as "the framework"
