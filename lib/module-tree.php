<?php
//	header("Content-Type: application/json");
	header("Content-Type: text/plain");
include("../config.php");

function sorter($a, $b){
	$a_name = strtolower($a["_reference"]);
	$b_name = strtolower($b["_reference"]);

	if($a_name == $b_name){
		$r = 0;
	}

	if(strlen(strstr($a_name, 'folder'))){
		if(strlen(strstr($b_name, 'folder'))){
			$r = ($a_name > $b_name) ? 1 : -1;
		} else {
			$r = -1;
		}
	}
	else if(strlen(strstr($a_name, 'file'))){
		if(strlen(strstr($b_name, 'folder'))){
			$r = -1;
		}
		else if(strlen(strstr($b_name, 'file'))){
			$r = ($a_name > $b_name) ? 1 : -1;
		} 
		else {
			$r = 1;
		}
	}
	else {
		$r = ($a_name > $b_name) ? 1 : -1;
	}
	return $r;
}

	$defVersion = "1.3";
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

	$f = dirname(__FILE__) . "/../data/" . $version . "/resources.xml";
	if(!file_exists($f)){
		$f = dirname(__FILE__) . "/../data/" . $defVersion . "/resources.xml";
	}
	if(!file_exists($f)){
		echo "API data does not exist for the default version: " . $defVersion . "<br/>";
		exit();
	}
	$xml = new DOMDocument();
	$xml->load($f);

	//	gotta do this manually, ugh.  Dunno who thought a flat object structure was a good idea.
	$xpath = new DOMXPath($xml);
	$resources = $xpath->query('//object/resources/resource');

	$ret = array();
	$filetree = array();
	$objects = array();
	$counter = 0;

	//	reverse lookup; we want a unique list of resources with all objects that
	//	are modified within them.
	foreach($resources as $node){
		$path = $node->nodeValue;
		$parts = explode('/', $path);
		$assembled = "";
		$parent = "";
		for($i=0; $i<count($parts); $i++){
			$part = $parts[$i];
			$assembled .= $part;
			$type = "folder";

			if($i == count($parts)-1){
				$type = "file";
			} 
			else {
				$assembled .= "/";
			}

			if(!array_key_exists($part, $filetree)){
				$filetree[$part] = array(
					"id" => $type . '-' . $counter++,
					"name" => $part,
					"path" => $assembled,
					"type" => $type
				);
			}

			if($type == "file"){
				//	go find the objects for this resource.
				$obj_nodes = $xpath->query('//object[resources/resource="' . $path . '"]');
				if($obj_nodes->length){
					if(!array_key_exists("children", $filetree[$part])){
						$filetree[$part]["children"] = array();
					}
					foreach($obj_nodes as $obj_node){
						$name = $obj_node->getAttribute("location");
						if(!array_key_exists($name, $objects)){
							$obj_type = $obj_node->getAttribute("type");
							$classlike = $obj_node->getAttribute("classlike");
							if($obj_type == "Function" && $classlike=="true"){
								$obj_type == "constructor";
							}
							if(!strlen($obj_type)){
								$obj_type = "object";
							}
							$objects[$name] = array(
								"id" => $name,
								"name" => $name,
								"type" => ($obj_type == "Function" && $classlike=="true") ? "Constructor" : $obj_type
							);
						}

						$found = false;
						foreach($filetree[$part]["children"] as $child){
							if($child["_reference"] == $name){
								$found = true;
								break;
							}
						}

						//	second check: make sure said object isn't created simply via path/provide.
						$part_assembly = array();
						foreach($parts as $a_part){
							$part_assembly[] = $a_part;
							$test = implode(".", $part_assembly);
							if($name == $test){
								$found = true;
								break;
							}
						}

						if(!$found){
							$filetree[$part]["children"][] = array(
								"_reference" => $name
							);
						}
					}
				}
			}

			//	finally, we need to see if we have a parent and add ourselves to them.
			foreach($filetree as &$item){
				if($item["path"] == $parent){
					if(!array_key_exists("children", $item)){
						$item["children"] = array();
					}
					$found = false;
					foreach($item["children"] as $child){
						if($child["_reference"] == $filetree[$part]["id"]){
							$found = true;
							break;
						}
					}
					if(!$found){
						$item["children"][] = array(
							"_reference" => $filetree[$part]["id"]
						);
					}
					break;
				}
			}

			$parent .= $part;
			if($type != "file"){
				$parent .= '/';
			}
		}
	}

	//	whew!  That's done.  Now we need to flatten and sort.
	foreach($filetree as &$item){
		if(array_key_exists("children", $item)){
			usort($item["children"], "sorter");
		}
		if($item["id"] == "folder-0"){
			$item["type"] = "root";
		}
		$ret[] = $item;
	}
	foreach($objects as &$item){
		$ret[] = $item;
	}

	$str = str_replace('{{{items}}}', json_encode($ret), $str);
	echo $str;
?>
