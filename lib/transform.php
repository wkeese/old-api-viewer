<?php
/*	Usage:
 *
 *	php transform.php --version=SomeVersion /relative/path/to/dojoAPI.xml
 *
 *
 */
if (isset($_SERVER['HTTP_HOST'])) {
  die('Run from command line');
}

ini_set('memory_limit', '128M');
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);
$debug = true;

$args = array();
$kwargs = array();
$version = "0.0";
$api = '';
foreach (array_slice($argv, 1) as $arg) {
  if ($arg{0} == '-') {
    if (preg_match('%^--(version|api)=([^ ]+)$%', $arg, $match)) {
      if ($match[1] == 'version') {
        $version = $match[2];
      }
      else {
        $kwargs[$match[1]] = $match[2];
      }
    }
    else {
      die("ERROR: Unrecognized argument: $arg\n");
    }
  }
  else {
    $args[] = $arg;
  }
}

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

if (!count($args)) {
	die("You must pass an API file to generate.php");
}

if (!file_exists($args[0])) {
	die("The file {$args[0]} doesn't exist");
}

$api_contents = file_get_contents($args[0]);

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

$api = new DOMDocument();
$api->loadXML($api_contents);
$api->save($dataDir . $version . "/api.xml");

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
	echo $style . " transformation time: " . (microtime(1) - $start) . "\n";
}
echo "Total time: " . (microtime(1) - $s) . "\n";
?>
