<?php
	header("Content-Type: application/json");
//	header("Content-Type: text/plain");
include("../config.php");
include("cache.php");

function nodeSorter($a, $b){
	if($a->getAttribute("location") == $b->getAttribute("location")){ return 0; }
	return ($a->getAttribute("location") > $b->getAttribute("location")) ? 1 : -1;
}

function sorter($a, $b){
	if(strtolower($a["_reference"]) == strtolower($b["_reference"])) return 0;
	return (strtolower($a["_reference"]) > strtolower($b["_reference"])) ? 1 : -1;
}

	$defVersion = "1.4";
	$version = $defVersion;
	if(array_key_exists("v", $_GET)){
		$version = $_GET["v"];
	}

	$str =<<<EOM
{
	"identifier": "id",
	"label": "name",
	"items": {{{items}}}
}
EOM;
	//	go grab the right XML file.  Note that we can load this directly, since the stylesheet
	//	shouldn't contain any variables.
	$data_dir = dirname(__FILE__) . '/../data/' . $version . '/';
	$f = $data_dir . "objects.xml";
	if(!file_exists($f)){
		$data_dir = dirname(__FILE__) . '/../data/' . $defVersion . '/';
		$f = $data_dir . "objects.xml";
	}
	if(!file_exists($f)){
		echo "API data does not exist for the default version: " . $defVersion . "<br/>";
		exit();
	}

	//	check the cache first
	if($use_cache){
		$out = _cache_file_get($data_dir, 'class-tree.json');
		if($out){
			echo $out;
			exit();
		}
	}

	$xml = new DOMDocument();
	$xml->load($f);

	//	gotta do this manually, ugh.  Dunno who thought a flat object structure was a good idea.
	$xpath = new DOMXPath($xml);
	$objects = $xpath->query('//object');

	//	great.  We have to pop the entire node list into an array and then sort it, because it looks
	//	like some objects might show up in the XML before the parent does.  Yay, @location!
	/*
	$objArray = array();
	foreach($objects as $node){ $objArray[] = $node; }
	usort($objArray, "nodeSorter");
	*/

	$ret = array();
	$counter = 0;

	//	this is the top level modules that we'll use as the root of the tree. Set this in config.php.
	$show = array();
	$keys = array();
	foreach($modules as $key=>$value){ 
		$show[$key] = $value;
		$keys[] = $key;
	}

	foreach($objects as $node){
		$name = $node->getAttribute("location");
		$type = $node->getAttribute("type");
		$classlike = $node->getAttribute("classlike");

		$name_parts = explode(".", $name);
		$short_name = array_pop($name_parts);

		if ($type=="Function" && $classlike=="true") {
			$val = array(
				"id"=>$name,  /* "object-" . $counter++, */
				"name"=>$short_name,
				"fullname"=>$name,
				"type"=>"constructor"
			);
		} else {
			$val = array(
				"id"=>$name,  /* "object-" . $counter++, */
				"name"=>$short_name,
				"fullname"=>$name,
				"type"=>(strlen($type) ? strtolower($type): "object")
			);
		} 

		if(isset($val)){
			if(isset($filter_privates) && $filter_privates && strpos($short_name, "_") === 0){
				unset($val);
				continue; 
			}
			if(count($name_parts)){
				$finder = implode(".", $name_parts);
				foreach($ret as &$obj){
					if($obj["fullname"] == $finder){
						if(!array_key_exists("children", $obj)){
							$obj["children"] = array();
						}
						$obj["children"][] = array(
							"_reference"=>$val["id"]
						);
					//	$obj["type"] = "namespace";
						break;
					}
				}
			}
			$ret[] = $val;
			unset($val);
		}
	}

	//	go through and find the top level objects, and reset the type on it.
	//	if you're using this to serve up your own docs, you'll need to add your top-level
	//	namespace here.
	$counter = 0;	//	reset the counter for reuse.
	foreach($ret as &$obj){
		$name = $obj["fullname"];
		if(array_key_exists($name, $show)){
			$obj["type"] = "root";
			$show[$name] = $counter;
		}
		$counter++;
	}

	//	finally, move the given namespaces to the top of the array.  Looks like we have to build another one, sigh.
	$fin = array();
	foreach($show as $item){
		if(array_key_exists("children", $ret[$item])){
			usort($ret[$item]["children"], "sorter");
		}
		$fin[] = &$ret[$item];
	}
	foreach($ret as &$obj){
		if(!array_key_exists($obj["fullname"], $show)){
			if(array_key_exists("children", $obj)){
				usort($obj["children"], "sorter");
			}
			$fin[] = $obj;
		}
	}

	$str = str_replace('{{{items}}}', json_encode($fin), $str);
	if($use_cache){
		$success = _cache_file_set($data_dir, "class-tree.json", $str);
	}
	echo $str;
?>
