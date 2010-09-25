<?php
//	use the given XSL stylesheets to generate the smaller XML files for more efficient usage.
function remove_dir($path){
	$dir = new DirectoryIterator($path);
	foreach($dir as $f){
		if($f->isFile() || $f->isLink()){
			unlink($f->getPathName());
		}
		else if(!$f->isDot() && $f->isDir()){
			remove_dir($f->getPathName());
		}
	}
	rmdir($path);
}

function process($version, &$log){
	$s = microtime(1);
	$dataDir = dirname(__FILE__) . "/../data/";
	$xslDir = dirname(__FILE__) . "/../xsl/";

	$api_xml = $dataDir . $version . "/api.xml";
	if(file_exists($api_xml)){
		$api = new DOMDocument();
		$api->load($api_xml);
		$log[] = "API xml file load time: " . (microtime(1) - $s);
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
			$log[] = $style . " transformation time: " . (microtime(1) - $start);
		}
		$log[] = "Total time: " . (microtime(1) - $s);
		return true;
	}
	return false;
}

//	MAIN
if(isset($_POST["dir"])){
	//	generate it.
	$logger = array();
	$version = $_POST["dir"];
	$dataDir = dirname(__FILE__) . "/../data/";
	$xslDir = dirname(__FILE__) . "/../xsl/";
	$s = microtime(1);

	if(!file_exists($dataDir . $version)){
		mkdir($dataDir . $version, 0750);
		mkdir($dataDir . $version . '/cache', 0750);
	} else {
		//	clear the cache
		$f = glob($dataDir . $version . '/cache/*');
		$files = array_filter($f, 'is_file');
		array_map('unlink', $files);
		$dirs = array_filter($f, 'is_dir');
		array_map('remove_dir', $dirs);	
	}

	//	check if there was a file uploaded first, and if not default to the URL.
	if(isset($_FILES["xmlfile"]) && is_uploaded_file($_FILES["xmlfile"]["tmp_name"])){
		//	save the file.
		$type = explode("/", $_FILES["xmlfile"]["type"]);
		$type = array_pop($type);
		if($type == 'xml'){
			$tmp_name = $_FILES["xmlfile"]["tmp_name"];
			$name = $_FILES["xmlfile"]["name"];
			$test = move_uploaded_file($tmp_name, $dataDir . $version . '/api.xml');

			if($test){
				$logger[] = "Successfully saved the api.xml file.";
				$success = process($version, $logger);
			} else {
				$logger[] = "api.xml file was not saved.";
				$success = $test;
			}
		} else {
			$logger[] = "Uploaded file is not an XML file.";
			$success = false;
		}
	}

	//	go get the api.xml if we have a URL.
	else if(isset($_POST["api"]) && strlen($_POST["api"])){
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
			$api = new DOMDocument();
			$api->loadXML($response);
			$api->save($dataDir . $version . "/api.xml");
			$logger[] = "Successfully retrieved the api.xml file.";

			$success = process($version, $logger);
		}
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
<?php if(isset($success) && $success){ ?>
	<div style="font-weight: bold;">Transforms for <?php echo $version; ?> was successful.</div>
<?php } else if(isset($version) && isset($success)) { ?>
	<div style="font-weight: bold;color:#900;">The directory or original api.xml file for version <?php echo $version; ?> was not found.</div>
<?php } ?>	
<?php 
if(isset($logger)){
	echo '<div style="margin: 1em; background-color:#efefed;">';
	foreach($logger as $log){
		echo "<div>" . $log . "</div>";
	}
	echo '</div>';
}	
?>
	<br/>
	<p>Enter the DTK version of documentation you wish to process (required), and optionally upload an <strong>api.xml</strong> file.</p>
	<p>If this is a new version of API documentation, this upload utility will create the proper directories and place the XML within it.</p>
	<p>If you do not attach an api.xml file, this utility assumes it already exists and you are simply re-running the transforms.</p>
	<form action="upload.php" method="post" enctype="multipart/form-data">
		<div>
			<label for="dir">API version: </label>
			<input type="text" name="dir" id="dir" value="<?php echo (isset($_POST['dir'])?$_POST['dir']:''); ?>" />
		</div>
		<div>
			<label>URL for api.xml: </label>
			<input type="text" name="api" value="<?php echo (isset($_POST['api'])?$_POST['api']:''); ?>" />
			<div style="font-weight: bold;">-- OR --</div>
			<label>Upload api.xml: </label>
			<input type="file" name="xmlfile" />
		</div>
		<div style="margin-top: 2em;"><input type="submit" value="Run the transforms." /></div>
		<div>(Note this may take a while, so be patient.)</div>
	</form>
</body>
</html>
