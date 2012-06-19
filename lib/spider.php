<?php
/*	spider.php
 *	TRT 2010-02-03
 *
 *	This file should only be run from the command line.
 *
 *	Run this to generate a static HTML version of a particular version
 *	of the API documentation.
 *
 *	Main arguments (space-delimited) will become,
 *	in order, the roots of any navigation tree generated.  If not specified
 *	the default is "dojo dijit dojox".
 *
 * 	Run the spider for version 1.8
 *	php spider.php --dir=../data/1.8/ --baseurl=http://api.dojotoolkit.org/1.8
 *
 *	Run it for 1.8 and append ".html" to the end of each link in files
 *	php spider.php --dir=../data/1.8/ --baseurl=http://api.dojotoolkit.org/1.8 --output=html
 *
 * 	Run the spider and output the result to ../static
 *	php spider.php --dir=../data/1.8/ --baseurl=http://api.dojotoolkit.org/1.8 --outdir=../static --output=html
 *
 * 	Run the spider, output the result to ../static, and use a template:
 *	php spider.php --dir=../data/1.8/ --baseurl=http://api.dojotoolkit.org/1.8 --template=../../myTemplate.html --outdir=../static --output=html
 *
 *	A NOTE ABOUT USING A TEMPLATE:
 *	The template should have one variable in it, defined like so:
 *	
 *	<!-- CONTENT BLOCK -->
 *
 *	This generator will look for that in template and swap out the contents of the generated file into it.  For adding
 *	info to the title, insert this:
 *
 *	<!-- TITLE -->
 *
 *	where you want the object's title to appear in the HTML title tag.
 *
 *	Finally, for adding in a UL-based navigation list, add this somewhere:
 *
 *	<!-- NAVIGATION -->
 */

if(isset($_SERVER['HTTP_HOST'])) {
  die('Run from command line');
}

ini_set('memory_limit', '128M');
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);
$debug = true;

include("generate.php");

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

$outdir = "";
$output = "";
$baseurl = "";
$templatePath = "";
$templateBlock = '<!-- CONTENT BLOCK -->';
$templateTitleBlock = '<!-- TITLE -->';
$templateNavBlock = '<!-- NAVIGATION -->';
$def_args = array("dojo", "dijit", "dojox");
$kwargs = array();
foreach(array_slice($argv, 1) as $arg){
	if($arg{0} == '-'){
		if (preg_match('%^--(outdir|dir|output|baseurl|template)=([^ ]+)$%', $arg, $match)){
			if($match[1] == "dir"){
				$dir = $match[2];
				if(strrpos($dir, "/") != strlen($dir)-1){
					$dir .= "/";
				}
			}
			else if($match[1] == "outdir"){
				$outdir = $match[2];
				if(strrpos($outdir, "/") != strlen($outdir)-1){
					$outdir .= "/";
				}
			}
			else if($match[1] == "output"){
				$output = "." . $match[2];
			}
			else if($match[1] == "baseurl"){
				$baseurl = $match[2];
			}
			else if($match[1] == "template"){
				$templatePath = $match[2];
			}
			else {
				$kwargs[$match[1]] = $match[2];
			}
		}
	} else {
		//	for now we will ignore anything without a flag.
		$args[] = $arg;
	}
}

if(!count($args)){
	$args = $def_args;
}

//	ok, check to see if we have a directory where details.xml is...
if(!isset($dir)){
	die("ERROR: a directory where the source XML files are was not specified.");
}

$start = microtime(true);
$tmp = explode("/", $dir);
$version = array_pop($tmp);
if(!strlen($version)){
	//	try it again
	$version = array_pop($tmp);
}

if(!strlen($outdir)){
	$outdir = $dir . 'cache/';
}

//	clear out the cache.
if(file_exists($outdir)){
	print "===== Clearing the output directory: " . $outdir . " =====\n";
	flush();
	$f = glob($outdir . '*');
	$files = array_filter($f, 'is_file');
	array_map('unlink', $files);
	$dirs = array_filter($f, 'is_dir');
	array_map('remove_dir', $dirs);	
} else {
	//	create the directory
	print "===== CREATING the output directory: " . $outdir . " =====\n";
	flush();
	mkdir($outdir, 0750);
}

//	check to see if we have a template
if(strlen($templatePath)){
	print "==== Loading template ====\n";
	flush();
	if(file_exists($templatePath)){
		$template = file_get_contents($templatePath);
		if($template === false){
			die("==== Template failed to load. ====");
		}

		if(isset($template) && strpos($template, $templateBlock) === false){
			die("==== Template does not have a content block like so: " . $templateBlock . " ====");
		}

		//	if we got here, we have a template string.  pull any fricking \r's from it.
		$template = str_replace("\r", "", $template);
	}
}

//	lets get our XML docs and start spidering.
print "==== Loading XML files ====\n";
flush();
$docs = array();
$docs["xml"] = new DOMDocument();
$docs["xml"]->load($dir . "details.xml");
$docs["xpath"] = new DOMXPath($docs["xml"]);
$docs["p_xml"] = new DOMDocument();
$docs["p_xml"]->load($dir . "provides.xml");
$docs["p_xpath"] = new DOMXPath($docs["p_xml"]);
$docs["r_xml"] = new DOMDocument();
$docs["r_xml"]->load($dir . "resources.xml");
$docs["r_xpath"] = new DOMXPath($docs["r_xml"]);
$docs["o_xml"] = new DOMDocument();
$docs["o_xml"]->load($dir . "objects.xml");
$docs["o_xpath"] = new DOMXPath($docs["o_xml"]);

//	prepare our trees.
$roots = array();
foreach($args as $arg){
	$roots[$arg] = -1;
}

print "==== GENERATING OBJECT TREE ====\n";
flush();
$tree = generate_object_tree($version, $roots, true, array(
	"xml"=>$docs["o_xml"],
	"xpath"=>$docs["o_xpath"]
));

print "==== GENERATING NAVIGATION TREES ====\n";
flush();
$html_trees = array();
foreach($args as $arg){
	$html_trees[$arg] = generate_object_tree_html($tree, $arg, $baseurl, $output);
}

//	our objects.
print "==== FETCHING OBJECTS ====\n";
flush();
//	build the xpath statement -- ignore any objects that don't begin with our root objects.
$query = array();
foreach($args as $arg){
	$query[] = '//object[starts-with(@location, "' . $arg . '")]';
}
$q = implode(" | ", $query);
$objects = $docs["xpath"]->query($q);
foreach($objects as $object){
	$page = $object->getAttribute("location");
	print "Generating " . implode("/", explode(".", $page)) . $output . "\n";
	flush();
	$html = generate_object_html($page, $version, $baseurl, $output, false, $docs);
	if(isset($template)){
		$html = str_replace($templateBlock, $html, $template);
		$html = str_replace($templateTitleBlock, $page, $html);

		$page_parts = explode(".", $page);
		if(count($page_parts) && array_key_exists($page_parts[0], $html_trees)){
			$html = str_replace($templateNavBlock, $html_trees[$page_parts[0]], $html);
		}
	}

	$tmp = explode(".", $page);
	array_pop($tmp);	//	last member is never a directory.
	$assembled = array();
	foreach($tmp as $part){
		if(!file_exists($outdir . implode('/', $assembled) . '/' . $part)){
			mkdir($outdir . implode('/', $assembled) . '/' . $part, 0750);
		}
		$assembled[] = $part;
	}
	file_put_contents($outdir . implode('/', explode('.', $page)) . '.html', $html);
}

print "==== SPIDERING COMPLETE. ====\n";
print "Processing time: " . round((microtime(true) - $start)/60, 3) . " minutes.\n";
flush();
?>
