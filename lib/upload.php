<?php
//	use the given XSL stylesheets to generate the smaller XML files for more efficient usage.
if(isset($_POST["dir"])){
	//	generate it.
	$logger = array();
	$version = $_POST["dir"];
	$dataDir = dirname(__FILE__) . "/../data/";
	$xslDir = dirname(__FILE__) . "/../xsl/";
	$s = microtime(1);

	//	go get the api.xml if we have a URL.
	if(isset($_POST["api"]) && strlen($_POST["api"])){
		//	let's cURL it.
		$request = curl_init();
		if(defined("CURL_SA_BUNDLE_PATH")){
			curl_setopt($request, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
		}
		curl_setopt($request, CURLOPT_URL, $_POST["api"]);
		curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($request, CURLOPT_TIMEOUT, 300);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($request, CURLOPT_HEADER, 0);

		$response = curl_exec($request);
		$info = curl_getinfo($request);
		curl_close($request);

		if($info["http_code"] != 200){
			$logger[] = "There was an issue getting the api.xml file at '" . $_POST["api"] . "'; returned HTTP " . $info["http_code"] . ".";
		} else {
			if(!file_exists($dataDir . $version)){
				mkdir($dataDir . $version, 0700);
			}

			$api = new DOMDocument();
			$api->loadXML($response);
			$api->save($dataDir . $version . "/api.xml");
			$logger[] = "Successfully retrieved the api.xml file.";
		}
	}

	//	we didn't pass a URL, or there was an error getting it from somewhere.
	if(!isset($api)){
		$api_xml = $dataDir . $version . "/api.xml";
		if(file_exists($api_xml)){
			$api = new DOMDocument();
			$api->load($api_xml);
			$logger[] = "API xml file load time: " . (microtime(1) - $s);
		}
	}

	//	again.
	if(isset($api)){
		//	ok, we're good to go.  Let's do this.  LEERRRROOOOYYYYY a-JENKINS!
		$ss = array("details", "objects", "provides", "resources");
		foreach($ss as $style){
			$start = microtime(1);
			$xsl = new DOMDocument();
			$xsl->load($xslDir . $style . ".xsl");
			$p = new XSLTProcessor();
			$p->importStylesheet($xsl);

			$d = new DOMDocument();
			$d->loadXML($p->transformToXML($api));
			$d->save($dataDir . $version . "/" . $style . ".xml");
			$logger[] = $style . " transformation time: " . (microtime(1) - $start);
		}
		$logger[] = "Total time: " . (microtime(1) - $s);
		$is_success = true;
	} else {
		$dir_not_found = true;
	}
}
?>
<html>
<head>
	<title>Dojo Toolkit -- API doc upload utility</title>
	<link type="text/css" rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/dojo/1.3.2/dojo/resources/dojo.css" />
	<link type="text/css" rel="stylesheet" href="http://archive.dojotoolkit.org/nightly/dojotoolkit/dijit/tests/css/dijitTests.css" />
</head>
<body>
	<h1>Dojo Toolkit API Documentation Upload Utility</h1>
<?php if(isset($dir_not_found)){ ?>
	<div style="font-weight: bold;color:#900;">The directory or original api.xml file for version <?php echo $version; ?> was not found.</div>
<?php } ?>
<?php if(isset($is_success)){ ?>
	<div style="font-weight: bold;">Transforms for <?php echo $version; ?> was successful.</div>
<?php } ?>
<?php 
	if(isset($logger)){
		foreach($logger as $log){
			echo "<div>" . $log . "</div>";
		}
	}	
?>
	<br/>
	<p>Enter the DTK version of documentation you wish to process (required), and optionally upload an <strong>api.xml</strong> file.</p>
	<p>If this is a new version of API documentation, this upload utility will create the proper directories and place the XML within it.</p>
	<p>If you do not attach an api.xml file, this utility assumes it already exists and you are simply re-running the transforms.</p>
	<form action="upload.php" method="post" enctype="multipart/form-data">
		<div>
			<label for="dir">API version: </label>
			<input type="text" name="dir" id="dir" value="" />
		</div>
		<div>
			<label>URL for api.xml: </label>
			<input type="text" name="api" value="" />
		</div>
		<div><input type="submit" value="Run the transforms." /></div>
		<div>(Note this may take a while, so be patient.)</div>
	</form>
</body>
</html>
