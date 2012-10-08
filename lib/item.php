<?php
/*	item.php
 *	TRT 2009-10-14
 *
 *	The file that generates the HTML that gets sent out
 *	in response to a tree click using the standalone API
 *	tool.
 *
 *  In other words, returns the content for a tab describing a specified module, like dijit/Dialog.
 *
 *  Usage: http://.../api/lib/item.php?p=dijit/Dialog&v=1.7
 */

include(dirname(__FILE__) . "/../config.php");
include("cache.php");
include("generate.php");

function get_page($version, $page, $use_cache=true, $_base_url = "", $refdoc=""){
	//	test to see if this has been cached first.
	if($use_cache){
		$html = cache_get($version, $page);
		if($html){
			return $html;
		}
	}

/*******
Code for handling when people try to go directly to a field, or give an invalid page.
Commented out for now since generate_object() is likely broken.

	//	if we got here, it's not in the cache so we need to pull some hackery (in case someone is
	//	trying to hit a field directly).
	$obj = generate_object($page, $version);
	if(!$obj){
		$tmp = explode(".", $page);
		$find = array_pop($tmp);
		$tmp = implode(".", $tmp);
		$obj = generate_object($tmp, $version);
		if(!$obj){
			return generate_object_html($page, $version, $_base_url);
		}

		$field = null;
		foreach($obj["properties"] as $key=>$value){
			$test = array_pop(explode(".", $key));
			if($test == $find){
				$field = $value;
				break;
			}
		}
		if(!$field){
			foreach($obj["methods"] as $key=>$value){
				$test = array_pop(explode(".", $key));
				if($test == $find){
					$field = $value;
					break;
				}
			}
		}
		if(!$field){
			foreach($obj["events"] as $key=>$value){
				$test = array_pop(explode(".", $key));
				if($test == $find){
					$field = $value;
					break;
				}
			}
		}
		if($field){
			if($use_cache){
				$html = cache_get($version, $tmp);
				if($html){
					$html = '<div style="display:none;" class="jsdoc-hash-reference">'
						. $field["name"]
						. '</div>'
						. $html;
					return $html;
				}
			}
			$html = generate_object_html($tmp, $version, $_base_url);
			if($use_cache){
				$success = cache_set($version, $tmp, $html);
			}
			return $html;
		}
	}
******/

	//	if we got here, we're not cached so generate our HTML.
	$html = generate_object_html($page, $version, $_base_url, "", true, array(), $refdoc);

	if($use_cache){
		$success = cache_set($version, $page, $html);
	}
	return $html;
}

//	begin the real work.
if(!isset($version)){ $version = $defVersion; }
if(!isset($page)){ $page = "dojo"; }

//	check if there's URL variables
if(isset($_GET["p"])){ $page = $_GET["p"]; }
if(isset($_GET["v"])){ $version = $_GET["v"]; }

//  sanitize $version and $page so user can't specify a string like ../../...
$version = preg_replace("/\\.\\.+/", "", $version);
$page = preg_replace("/\\.\\.+/", "", $page);

echo get_page($version, $page, $use_cache, $_base_url, $refdoc);
?>
