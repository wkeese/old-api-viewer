<?php
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

if(isset($_POST["dir"]) && isset($_POST["cli"])){
	if($_POST["cli"] != "6a033ee0-c8ce-11df-bd3b-0800200c9a66"){
		print "Not authorized";
		exit();
	}

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
		//	check the type.
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

	print_r($logger);
	print 1;
	exit;
}

print 0;
?>
