<?php
/*	item.php
 *	TRT 2009-10-14
 *
 *	The file that generates the HTML that gets sent out
 *	in response to a tree click using the standalone API
 *	tool.
 */

include(dirname(__FILE__) . "/../config.php");
include("generate.php");

//	begin the real work.
if(!isset($version)){ $version = $defVersion; }
if(!isset($page)){ $page = "dojo"; }

//	check if there's URL variables
if(isset($_GET["p"])){ $page = $_GET["p"]; }
if(isset($_GET["v"])){
	//	we really only care about the last thing on the GET string.
	$version = $_GET["v"];
	if(strpos("/", $version) !== false){
		$tmp = explode($version, "/");
		$version = array_pop($tmp);
	}
}

if(strpos($page, "/") > 0){ $page = implode(".", explode("/", $page)); }
$data_dir = dirname(__FILE__) . "/../data/" . $version . "/";

//	test to see if this has been cached first.
$test = implode("/", explode(".", $page));
if(file_exists($data_dir . 'cache/' . $test . '.html')){
	echo file_get_contents($data_dir . 'cache/' . $test . '.html');
	exit();
}

//	if we got here, we're not cached so generate our HTML.
$html = generate_object_html($page, $version, $_base_url);

//	check to make sure all directories are made before trying to save it.
$tmp = explode(".", $page);
array_pop($tmp);	//	last member is never a directory.
$assembled = array();
foreach($tmp as $part){
	if(!file_exists($data_dir . 'cache/' . implode('/', $assembled) . '/' . $part)){
		mkdir($data_dir . 'cache/' . implode('/', $assembled) . '/' . $part, 0750);
	}
	$assembled[] = $part;
}
file_put_contents($data_dir . 'cache/' . implode('/', explode('.', $page)) . '.html', $html);
echo $html;
?>
