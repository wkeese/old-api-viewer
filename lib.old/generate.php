<?php
/*	generate_html.php
 *	TRT 2010-02-03
 *
 *	Given the page and version, generate the HTML fragment
 *	and return it.
 */

include("markdown/markdown.php");
function convert_type($type){
	$base = 'object';
	switch($type){
		case 'Namespace':
		case 'namespace': $base='namespace'; break;
		case 'Constructor': $base='constructor'; break;
		case 'Node':
		case 'DOMNode':
		case 'DomNode':   $base='domnode'; break;
		case 'Array':   $base='array'; break;
		case 'Boolean':   $base='boolean'; break;
		case 'Date':    $base='date'; break; 
		case 'Error':     $base='error'; break;
		case 'Function':  $base='function'; break;
		case 'Integer':
		case 'Float':
		case 'int':
		case 'Double':
		case 'integer':
		case 'Number':    $base='number'; break;   
		case 'RegExp':    $base='regexp'; break;
		case 'String':    $base='string'; break;
		default:      $base='object'; break;
	}
	return $base;
}

function icon_url($type, $size=16){
	$img = "object";
	switch($type){
		case 'Namespace':
		case 'namespace': $img='namespace'; break;
		case 'Constructor': $img='constructor'; break;
		case 'Node':
		case 'DOMNode':
		case 'DomNode':   $img='domnode'; break;
		case 'Array':   $img='array'; break;
		case 'Boolean':   $img='boolean'; break;
		case 'Date':    $img='date'; break; 
		case 'Error':     $img='error'; break;
		case 'Function':  $img='function'; break;
		case 'Integer':
		case 'Float':
		case 'int':
		case 'Double':
		case 'integer':
		case 'Number':    $img='number'; break;   
		case 'RegExp':    $img='regexp'; break;
		case 'String':    $img='string'; break;
		default:      $img='object'; break;
	}
	return 'css/icons/' . $size . 'x' . $size . '/' . $img . '.png';
}

function do_markdown($text){
	//	prep the text and run it through the Markdown parser.
	$lines = explode("\n", $text);
	$fixed = array();
	$b = false;
	foreach($lines as $line){
		//	 pull off the preceding tab.
		$s = $line;
		if(strpos($line, "\t")===0){ $s = substr($s, 1); }

		//	deal with the munging of lists in the markdown.
		if(preg_match('/(\t)*\*/', $s)){
			if(!$b){
				$b = true;
				$s = "\n" . $s;
			}
		} else {
			$b = false;
		}

		$fixed[] = $s;
	}
	$str = Markdown(implode("\n", $fixed));
	return $str;
}

function format_example($text){
	//	do this for SyntaxHighlighter use.
	$s = ""; // */ "\n<!--\n" . $text . "\n-->\n";
	//	insert an additional tab if the first character is a tab.
	if(strpos($text, "\t")===0){
		$text = "\t" . $text;
	}
	$lines = explode("\n", "\n" . $text);
	$isCode = false;
	foreach($lines as &$line){
		if(strpos($line, "\t")===0){
			$line = htmlentities(substr($line, 1));	//	always pull off the first tab.
		}
		if(strpos($line, "\t")===0){
			if(!$isCode){
				$isCode = true;
				$line = '<pre class="brush: js;" lang="javascript">' . "\n" . $line;
			}
		} else {
			if($isCode){
				$isCode = false;
				$line .= '</pre>';
			}
		}
	}
	if($isCode){
		//	probably we never saw the last line, or the last line was code.
		$lines[] = '</pre>';
	}
	return $s . implode("\n", $lines);
}

//	BEGIN array_filter functions
function is_event($item){
	$public = strpos($item["name"], "on");
	$private = strpos($item["name"], "_on");
	return $public === 0 || $private === 0;
}
function is_method($item){
	$public = strpos($item["name"], "on");
	$private = strpos($item["name"], "_on");
	return $public !== 0 && $private !== 0;
}
function is_static($item){
	return $item["scope"] == "normal";
}
function is_not_static($item){
	return $item["scope"] != "normal";
}

function is_not_from_Object($item){
	return !(count($item["defines"]) == 1 && $item["defines"][0] == "Object");
}

function is_not_node($item){
	$obj = strtolower($item);
	return !strpos($obj, "node") && !strpos($obj, "style") && !strpos($obj, "__");
}
//	END array_filter functions

function load_docs($version){
	//	helper function to load up the XML docs we need.
	global $dataDir;
	$data_dir = $dataDir . $version . "/";

	//	load up the doc.
	$provides = "provides.xml";
	$resources = "resources.xml";
	$details = "details.xml";
	$f = $data_dir . $details;

	if(!file_exists($f)){
        header(":", true, 400);
        header("Content-type: text/plain");
		echo "API data does not exist for the version: " . $version;
		exit();
	}

	$xml = new DOMDocument();
	$xml->load($f);

	$p_xml = new DOMDocument();
	$p_xml->load($data_dir . $provides);

	$r_xml = new DOMDocument();
	$r_xml->load($data_dir . $resources);

	$xpath = new DOMXPath($xml);
	$p_xpath = new DOMXPath($p_xml);
	$r_xpath = new DOMXPath($r_xml);

	$docs = array(
		"xml"=>$xml,
		"p_xml"=>$p_xml,
		"r_xml"=>$r_xml,
		"xpath"=>$xpath,
		"p_xpath"=>$p_xpath,
		"r_xpath"=>$r_xpath
	);
	return $docs;
}

function read_object_fields($page, $version, $docs=array()){
	//	for the given page, grab any mixins and assemble a "flat" version of it (no prototype walking).
	//	create a PHP-based associative array structure out of the page in question.
	if(!count($docs)){
		$docs = load_docs($version);
	}

	$xml = $docs["xml"];
	$p_xml = $docs["p_xml"];
	$r_xml = $docs["r_xml"];
	$xpath = $docs["xpath"];
	$p_xpath = $docs["p_xpath"];
	$r_xpath = $docs["r_xpath"];

	//	get the XML for the page.
	$context = $xpath->query('//object[@location="' . $page . '"]')->item(0);
	if(!$context){
		//	we got nothing, just return null.
		return null;
	}

	//	get any mixins, and ignore if the mixin == superclass.  Note that we're going to ignore any prototype mixins,
	//	as they are (in general) applied in the same way as instance mixins.
	$mixinNodes = $xpath->query('mixins/mixin[@scope="instance"]', $context);
	$mixins = array();
	foreach($mixinNodes as $m){
		//	test 1: make sure the mixin is not the superclass.
		if($m->getAttribute("location") == $context->getAttribute("superclass")){
			continue;
		}
		//	test 2: make sure we can actually read the mixin definition
		$m_test = $xpath->query("//object[@location='" . $m->getAttribute("location") . "']");
		if($m_test->length){
			$mixins[$m->getAttribute("location")] = $m_test->item(0);
		}
	}

	//	push in our page.
	$mixins[$page] = $context;
	$props = array();
	$methods = array();

	foreach($mixins as $location=>$node){
		//	properties
		$nl = $xpath->query("properties/property", $node);
		foreach($nl as $n){
			$nm = $n->getAttribute("name");
			$private = $n->getAttribute("private") == "true";
			if(!$private && strpos($nm, "_")===0){
				$private = true;
			}
			if(array_key_exists($nm, $props)){
				//	next one up in the chain overrides the original.
				$props[$nm]["scope"] = $n->getAttribute("scope");
				$props[$nm]["type"] = $n->getAttribute("type");
				$props[$nm]["override"] = true;
				$props[$nm]["defines"][] = $location;
			} else {
				$props[$nm] = array(
					"name"=>$nm,
					"scope"=>$n->getAttribute("scope"),
					"visibility"=>($private == true ? "private" : "public"),
					"type"=>$n->getAttribute("type"),
					"defines"=>array($location),
					"override"=>false
				);
			}

			if($n->getElementsByTagName("summary")->length){
				$desc = trim($n->getElementsByTagName("summary")->item(0)->nodeValue);
				if(strlen($desc)){
					$props[$nm]["summary"] = $desc;
				}
			}
			if($n->getElementsByTagName("description")->length){
				$desc = trim($n->getElementsByTagName("description")->item(0)->nodeValue);
				if(strlen($desc)){
					$props[$nm]["description"] = do_markdown($desc);
				}
			}
		}

		//	methods
		$nl = $xpath->query("methods/method", $node);
		foreach($nl as $n){
			$nm = $n->getAttribute("name");
			$private = $n->getAttribute("private") == "true";
			if(!$private && strpos($nm, "_")===0){
				$private = true;
			}
			if(!strlen($nm)){
				$nm = "constructor";
			}
			if(array_key_exists($nm, $methods)){
				//	next one up in the chain overrides the original.
				$methods[$nm]["scope"] = $n->getAttribute("scope");
				$methods[$nm]["override"] = true;
				$methods[$nm]["defines"][] = $location;
				if($n->getAttribute("constructor") == "constructor"){
					$methods[$nm]["constructor"] = true;
					$methods[$nm]["scope"] = "prototype";
				}
			} else {
				$methods[$nm] = array(
					"name"=>$nm,
					"scope"=>$n->getAttribute("scope"),
					"visibility"=>($private=="true"?"private":"public"),
					"parameters"=>array(),
					"return-types"=>array(),
					"defines"=>array($location),
					"override"=>false,
					"constructor"=>$n->getAttribute("constructor")=="constructor"
				);
			}

			if($n->getElementsByTagName("summary")->length){
				$desc = trim($n->getElementsByTagName("summary")->item(0)->nodeValue);
				if(strlen($desc)){
					$methods[$nm]["summary"] = $desc;
				}
			}
			if($n->getElementsByTagName("description")->length){
				$desc = trim($n->getElementsByTagName("description")->item(0)->nodeValue);
				if(strlen($desc)){
					$methods[$nm]["description"] = do_markdown($desc);
				}
			}
			$ex = $n->getElementsByTagName("example");
			if($ex->length){
				if(!array_key_exists("examples", $methods[$nm])){
					$methods[$nm]["examples"] = array();
				}
				foreach($ex as $example){
					$methods[$nm]["examples"][] = $example->nodeValue;
				}
			}
			if($n->getElementsByTagName("return-description")->length){
				$desc = trim($n->getElementsByTagName("return-description")->item(0)->nodeValue);
				if(strlen($desc)){
					$methods[$nm]["return-description"] = $desc;
				}
			}

			//	do up the parameters and the return types.
			$params = $xpath->query("parameters/parameter", $n);
			if($params->length){
				//	TODO: double-check that the XML will always have this.
				$methods[$nm]["parameters"] = array();
				foreach($params as $param){
					$item = array(
						"name"=>$param->getAttribute("name"),
						"type"=>$param->getAttribute("type"),
						"usage"=>$param->getAttribute("usage"),
						"description"=>""
					);
					if($param->getElementsByTagName("summary")->length){
						$desc = trim($param->getElementsByTagName("summary")->item(0)->nodeValue);
						if(strlen($desc)){
							$item["description"] = $desc;
						}
					}
					$methods[$nm]["parameters"][] = $item;
				}
			}

			if($nm == "constructor"){
				$methods[$nm]["return-types"] = array();
				$methods[$nm]["return-types"][] = array(
					"type"=>$location,
					"description"=>""
				);
			} else {
				$rets = $xpath->query("return-types/return-type", $n);
				if($rets->length){
					//	TODO: double-check that the XML will always have this.
					$methods[$nm]["return-types"] = array();
					foreach($rets as $ret){
						$item = array(
							"type"=>$ret->getAttribute("type"),
							"description"=>""
						);
						$methods[$nm]["return-types"][] = $item;
					}
				}
			}
		}
	}
	return array("props"=>$props, "methods"=>$methods);
}

function get_Object_fields(){
	//	simple helper function to return a fake generated prop/method set for Object.
	$props = array();
	$methods = array();

	//	no properties other than the constructor on an object, so we'll just return that as an empty array.
	$native = array("constructor", "hasOwnProperty", "isPrototypeOf", "propertyIsEnumerable", "toLocaleString", "toString", "valueOf");
	$returns = array("Object", "Boolean", "Boolean", "Boolean", "String", "String", "Object");
	$summaries = array(
		"A reference to the constructor function for this object.",
		"Checks whether an object has a locally defined (noninherited) property with a specified name.",
		"Check whether this object is the prototype object of a specified object.",
		"Checks whether a named property exists and would be enumerated by a for/in loop.",
		"Returns a localized string representation of the object.",
		"Returns a string representation of the object.",
		"Returns the primitve value of the object, if any."
	);

	foreach($native as $i=>$nm){
		$methods[$nm] = array(
			"name"=>$nm,
			"scope"=>"prototype",
			"visibility"=>"public",
			"parameters"=>array(),
			"return-types"=>array(),
			"defines"=>array("Object"),
			"override"=>false,
			"summary"=>$summaries[$i]
		);
		$methods[$nm]["return-types"][] = array(
			"type"=>$returns[$i],
			"description"=>""
		);
		if($nm == "hasOwnProperty" || $nm == "propertyIsEnumerable"){
			$methods[$nm]["parameters"][] = array(
				"name"=>"propname",
				"type"=>"String",
				"usage"=>"",
				"description"=>"The property to check."
			);
		}
		else if($nm == "isPrototypeOf"){
			$methods[$nm]["parameters"][] = array(
				"name"=>"o",
				"type"=>"Object",
				"usage"=>"",
				"description"=>"The object to check against."
			);
		}
	}

	return array("props"=>$props, "methods"=>$methods);
}

function generate_object($page, $version, $docs=array()){
	//	create a PHP-based associative array structure out of the page in question.
	if(!count($docs)){
		$docs = load_docs($version);
	}

	$xml = $docs["xml"];
	$p_xml = $docs["p_xml"];
	$r_xml = $docs["r_xml"];
	$xpath = $docs["xpath"];
	$p_xpath = $docs["p_xpath"];
	$r_xpath = $docs["r_xpath"];

	//	get the XML for the page.
	$context = $xpath->query('//object[@location="' . $page . '"]')->item(0);
	if(!$context){
		//	we got nothing, just return null.
		return null;
	}

	//	ok, we have a context, let's build up our object.
	$obj = array();

	//	BEGIN OBJECT GRAPH ASSEMBLY.
	//	provides
	$test = $p_xpath->query('//object[@location="' . $page . '"]/provides/provide');
	if($test && $test->length == 1){
		$obj["require"] = $test->item(0)->nodeValue;
	}

	//	resources
	$test = $r_xpath->query('//object[@location="' . $page . '"]/resources/resource');
	if($test && $test->length == 1){
		$obj["resource"] = $test->item(0)->nodeValue;
	}

	//	basic information.
	$is_constructor = ($context->getAttribute("type")=="Function" && $context->getAttribute("classlike")=="true");
	$nl = $xpath->query('//object[starts-with(@location, "' . $page . '.") and not(starts-with(substring-after(@location, "' . $page . '."), "_"))]');
	$is_namespace = ($nl->length > 0);
	$type = $context->getAttribute("type");
	if(!strlen($type)){ $type = 'Object'; }
	if($is_constructor){ $type = 'Constructor'; }
//	if($is_namespace){ $type = 'Namespace'; }

	$obj["type"] = $type;
	$obj["title"] = $context->getAttribute("location");
	$obj["version"] = $version;

	//	the prototype chain
	$bc = array($context->getAttribute("location"));
	$node = $context;
	while($node && $node->getAttribute("superclass")){
		$sc = $node->getAttribute("superclass");
		$bc[] = $sc;
		$node = $xpath->query('//object[@location="' . $sc . '"]')->item(0);
	}
	$bc[] = "Object";
	$bc = array_reverse($bc);

	//	note that this is "in order"; used to either fetch other objects or for something like breadcrumbs.
	$obj["prototypes"] = $bc;

	//	description.  Actual description node first, fall back to summary if needed.
	$desc = $xpath->query("description/text()", $context)->item(0);
	if(!$desc){ $desc = $xpath->query("summary/text()", $context)->item(0); }
	if($desc){ $obj["description"] = $desc->nodeValue; }

	//	mixins
	//	examples.
	$examples = $xpath->query("examples/example", $context);
	if($examples->length > 0){
		$obj["examples"] = array();
		foreach($examples as $example){
			$obj["examples"][] = $example->nodeValue;
		}
	}

	//	now it gets ugly.  We need to go get all the properties and methods of ourselves,
	//	plus anything in the prototype chain (i.e. superclass), PLUS anything in the mixins list,
	//	and merge them all together, AND make sure they are unique.  On top of that, we need
	//	to make sure we're getting that info from the top to the bottom.
	$obj["mixins"] = array();
	$obj["properties"] = array();
	$obj["methods"] = array();

	//	start with getting the mixins.
	$nl = $xpath->query("mixins/mixin[@scope='instance']", $context);
	foreach($nl as $m){
		//	again, this is ugly.
		$m_test = $xpath->query("//object[@location='" . $m->getAttribute("location") . "']");
		if($m_test->length){
			$obj["mixins"][] = $m->getAttribute("location");
		}
	}

	//	ok.  Walk the prototype chain from one to another, and get the list of props and methods for all.
	$protos = array();
	foreach($bc as $ancestor){
		if($ancestor == "Object"){
			$protos[$ancestor] = get_Object_fields();
		} else {
			$protos[$ancestor] = read_object_fields($ancestor, $version, $docs);
		}
	}

	//	Now that we have the complete prototype chain, merge everything and include override info if needed.
	$props = array();
	$methods = array();
	foreach($protos as $_object=>$proto){
		if($proto && array_key_exists("props", $proto)){
			foreach($proto["props"] as $nm=>$prop){
				if(array_key_exists($nm, $props)){
					//	next one up in the chain overrides the original.
					$props[$nm]["override"] = true;
					$props[$nm]["defines"][] = $_object;
					if(isset($prop["summary"])){
						$prop[$nm]["summary"] = $prop["summary"];
					}
					if(isset($prop["description"])){
						$prop[$nm]["description"] = $prop["description"];
					}
				} else {
					$props[$nm] = $prop;
				}
			}
		}
		if($proto && array_key_exists("methods", $proto)){
			foreach($proto["methods"] as $nm=>$method){
				if(array_key_exists($nm, $methods)){
					//	next one up in the chain overrides the original.
					$methods[$nm]["override"] = true;
					$methods[$nm]["defines"][] = $_object;
					$methods[$nm]["scope"] = $method["scope"];
					if($nm == "constructor"){
						$methods[$nm]["return-types"][0]["type"] = $_object;
					}
					if(count($method["parameters"])){
						$methods[$nm]["parameters"] = $method["parameters"];
					}
					if(isset($method["summary"])){
						$methods[$nm]["summary"] = $method["summary"];
					}
					if(isset($method["description"])){
						$methods[$nm]["description"] = $method["description"];
					}
				} else {
					$methods[$nm] = $method;
				}
			}
		}
	}

	//	sort the props and methods correctly (alphabetical order).
	$methods = array_filter($methods, "is_not_from_Object");
	$events = array_filter($methods, "is_event");
	$methods = array_filter($methods, "is_method");

	//	put any normal scope (i.e. attached directly) first.  Note that we only want
	//	the ones attached directly to our page, and nothing from the inheritance chain.
	$static = array_filter($props, "is_static");
	$not_static = array_filter($props, "is_not_static");
	$tmp = array();
	foreach($static as $nm=>$field){
		if($field["defines"][0] == $page){
			$tmp[$page . "." . $nm] = $field;
		}
	}
	ksort($tmp);
	ksort($not_static);
	$props = array_merge($tmp, $not_static);

	$static = array_filter($methods, "is_static");
	$not_static = array_filter($methods, "is_not_static");
	$tmp = array();
	foreach($static as $nm=>$field){
		if($field["defines"][0] == $page){
			$tmp[$page . "." . $nm] = $field;
		}
	}
	ksort($tmp);
	ksort($not_static);
	$methods = array_merge($tmp, $not_static);

	$static = array_filter($events, "is_static");
	$not_static = array_filter($events, "is_not_static");
	$tmp = array();
	foreach($static as $nm=>$field){
		if($field["defines"][0] == $page){
			$tmp[$page . "." . $nm] = $field;
		}
	}
	ksort($tmp);
	ksort($not_static);
	$events = array_merge($tmp, $not_static);

	$obj["properties"] = $props;
	$obj["methods"] = $methods;
	$obj["events"] = $events;

	//	attached objects.  Try to filter out the craptacular ones.
	$children = $xpath->query('//object[starts-with(@location, "' . $page . '.")]');
	$attached = array();
	if($children->length){
		foreach($children as $node){
			$attached[] = $node->getAttribute("location");
		}
		$attached = array_filter($attached, "is_not_node");
		$obj["attached"] = $attached;
	}

	return $obj;	
}

///////////////////////////////////////////////////////////////////////////////////////////////
//
//	BEGIN HTML OUTPUT GENERATION
//
///////////////////////////////////////////////////////////////////////////////////////////////

//	private functions for pieces
function _generate_property_output($prop, $name, $docs = array(), $counter = 0, $base_url = "", $suffix = ""){
	//	create the HTML strings for a single property
	$s = '<li class="' . convert_type($prop["type"]) . 'Icon '
		. (isset($prop["visibility"]) ? $prop["visibility"] : 'public') . ' '
		. (isset($prop["defines"]) && count($prop["defines"]) && !$prop["override"] ? 'inherited':'')
		. ($counter % 2 == 0 ? ' even':' odd')
		. '">'
		. '<a class="inline-link" href="#' . $name . '">'
		. $name
		. '</a>';
	$details = '<div class="jsdoc-field '
		. (isset($prop["visibility"]) ? $prop["visibility"] : 'public') . ' '
		. (isset($prop["defines"]) && count($prop["defines"]) && !$prop["override"] ? 'inherited':'')
		. ($counter % 2 == 0 ? ' even':' odd')
		. '">'
		. '<div class="jsdoc-title">'
		. '<a name="' . $name . '"></a>'
		. '<span class="' . convert_type($prop["type"]) . 'Icon">'
		. $name
		. '</span>'
		. '</div>';

	//	inheritance list.
	if(isset($prop["defines"]) && count($prop["defines"])){
		$tmp = array();
		foreach($prop["defines"] as $def){
			$tmp[] = '<a class="jsdoc-link" href="' . $base_url . implode("/", explode(".", $def)) . $suffix . '">'
				. $def
				. '</a>';
		}
		if($prop["override"]){
			array_pop($tmp);
		}
		$details .= '<div class="jsdoc-inheritance">'
			. ($prop["override"] ? "Overrides ":"Defined by ")
			. implode(", ", $tmp)
			. '</div>';
	}
	if(array_key_exists("description", $prop)){
		$details .= '<div class="jsdoc-summary">' . $prop["description"] . '</div>';
	} else if(array_key_exists("summary", $prop)){
		$details .= '<div class="jsdoc-summary">' . $prop["summary"] . '</div>';
	}
	if(array_key_exists("summary", $prop)){
		$s .= ' <span>' . $prop["summary"] . '</span>';
	}
	$s .= '</li>';	//	jsdoc-title
	$details .= '</div>';	//	jsdoc-field
	return array("s"=>$s, "details"=>$details);
}

function _generate_method_output($method, $name, $docs = array(), $counter = 0, $base_url = "", $suffix = ""){
	//	create the HTML strings for a single method.
	$s = '<li class="functionIcon '
		. (isset($method["visibility"]) ? $method["visibility"] : 'public') . ' '
		. (isset($method["defines"]) && count($method["defines"]) && !$method["override"] ? 'inherited':'')
		. ($counter % 2 == 0 ? ' even':' odd')
		. '">'
		. '<a class="inline-link" href="#' . $name . '">'
		. $name
		. '</a>';
	$details = '<div class="jsdoc-field '
		. (isset($method["visibility"]) ? $method["visibility"] : 'public') . ' '
		. (isset($method["defines"]) && count($method["defines"]) && !$method["override"] ? 'inherited':'')
		. ($counter % 2 == 0 ? ' even':' odd')
		. '">'
		. '<div class="jsdoc-title">'
		. '<a name="' . $name . '"></a>'
		. '<span class="functionIcon">'
		. $name
		. '</span>'
		. '</div>';
	if(count($method["parameters"])){
		$tmp = array();
		foreach($method["parameters"] as $p){
			$tmp[] = $p["name"];
		}
		$s .= '<span class="parameters">('
			. implode(', ', $tmp)
			. ')</span>';
	} else {
		$s .= '<span class="parameters">()</span>';
	}

	if(count($method["return-types"])){
		$tmp = array();
		foreach($method["return-types"] as $rt){
			$tmp[] = $rt["type"];
		}
		$s .= '<span class="jsdoc-returns"> returns ' . implode("|", $tmp) . '</span>';
	}

	//	inheritance list.
	if(isset($method["defines"]) && count($method["defines"])){
		$tmp = array();
		foreach($method["defines"] as $def){
			$tmp[] = '<a class="jsdoc-link" href="' . $base_url . implode("/", explode(".", $def)) . $suffix . '">'
				. $def
				. '</a>';
		}
		if($method["override"]){
			array_pop($tmp);
		}
		$details .= '<div class="jsdoc-inheritance">'
			. ($method["override"] ? "Overrides ":"Defined by ")
			. implode(", ", $tmp) 
			. '</div>';	//	jsdoc-inheritance
	}

	if(count($method["return-types"])){
		$tmp = array();
		foreach($method["return-types"] as $rt){
			$tmp[] = $rt["type"];
		}
		$details .= '<div class="jsdoc-return-type">Returns '
			. '<strong>'
			. implode("|", $tmp)
			. '</strong>';
		if(array_key_exists("return-description", $method)){
			$details .= ': <span class="jsdoc-return-description">'
				. $method["return-description"]
				. '</span>';
		}
		$details .= '</div>';
	} 
	else if(array_key_exists("return-description", $method)){
		$details .= '<div class="jsdoc-return-type"><div class="jsdoc-return-description">'
			. $method["return-description"]
			. '</div></div>';
	}

	if(array_key_exists("description", $method)){
		$details .= '<div class="jsdoc-summary">' . do_markdown($method["description"]) . '</div>';
	} else if(array_key_exists("summary", $method)){
		$details .= '<div class="jsdoc-summary">' . $method["summary"] . '</div>';
	}
	if(array_key_exists("summary", $method)){
		$s .= ' <span>' . $method["summary"] . '</span>';
	}
	$s .= '</li>';	//	jsdoc-title

	if(count($method["parameters"])){
		$details .= _generate_param_table($method["parameters"], $docs, $base_url, $suffix);
	}

	if(array_key_exists("examples", $method)){
		$details .= '<div class="jsdoc-examples">';
		$counter = 1;
		foreach($method["examples"] as $example){
			$details .= '<div class="jsdoc-example">'
				. '<div><strong>Example ' . $counter++ . '</strong></div>'
				. format_example($example)
				. '</div>';
		}
		$details .= '</div>';
	}

	$details .= '</div>';	//	jsdoc-field
	return array("s"=>$s, "details"=>$details);
}

function _generate_param_table($params, $docs = array(), $base_url = "", $suffix = ""){
	//	create the inline table for parameters; isolated so that nesting may occur on more than one level.
	$tmp_details = array();
	foreach($params as $p){
		$tester = explode(".", $p["type"], 2);
		$tester = $tester[0];
		$pstr = '<tr>'
			. '<td class="jsdoc-param-name">'
			. $p["name"]
			. '</td>'
			. '<td class="jsdoc-param-type">'
			. (strpos($tester, "__") === 0 ? "Object" : $p["type"])
			. '</td>'
			. '<td class="jsdoc-param-description">'
			. (strlen($p["usage"]) ? (($p["usage"] == "optional") ? '<div><em>Optional.</em></div>' : (($p["usage"] == "one-or-more") ? '<div><em>One or more can be passed.</em></div>' : '')) : '')
			. $p["description"];

		if(strpos($tester, "__")===0){
			//	try to find the object in question, and if found list out the props.
			$pconfig = generate_object($p["type"], null, $docs);
			if($pconfig && array_key_exists("properties", $pconfig)){
				$p_param = array();
				foreach($pconfig["properties"] as $name=>$value){
					$tmp_str = '<tr>'
						. '<td class="jsdoc-param-name">'
						. $name
						. '</td>'
						. '<td class="jsdoc-param-type">'
						. $value["type"]
						. '</td>'
						. '<td class="jsdoc-param-description">';
					if(array_key_exists("description", $value)){
						$tmp_str .= do_markdown($value["description"]);
					} else if (array_key_exists("summary", $value)){
						$tmp_str .= do_markdown($value["summary"]);
					} else {
						$tmp_str .= '&nbsp;';
					}
					$p_param[] = $tmp_str . '</td></tr>';
				}
				$pstr .= '<table class="jsdoc-parameters" style="margin-left:0;margin-right:0;margin-bottom:0;">'
					. '<tr>'
					. '<th>Parameter</th>'
					. '<th>Type</th>'
					. '<th>Description</th>'
					. '</tr>'
					. implode('', $p_param)
					. '</table>';
			}
		}
		$pstr .= '</td>'
			. '</tr>';
		$tmp_details[] = $pstr;
	}
	return '<table class="jsdoc-parameters">'
		. '<tr>'
		. '<th>Parameter</th>'
		. '<th>Type</th>'
		. '<th>Description</th>'
		. '</tr>'
		. implode('', $tmp_details)
		. '</table>';
}

function _generate_properties_output($properties, $docs = array(), $field_counter = 0, $base_url = "", $suffix = "", $title="Property"){
	//	generate all of the properties output
	$s = '<h2 class="jsdoc-summary-heading">Property Summary <span class="jsdoc-summary-toggle"></span></h2>'
		. '<div class="jsdoc-summary-list">'
		. '<ul>';
	$details = '<h2>Properties</h2>';
	foreach($properties as $name=>$prop){
		$tmp = _generate_property_output($prop, $name, $docs, $field_counter, $base_url, $suffix);
		$s .= $tmp["s"];
		$details .= $tmp["details"];
		$field_counter++;
	}

	$s .= '</ul></div>';	//	property-summary
	return array("s"=>$s, "details"=>$details, "counter"=>$field_counter);
}

function _generate_methods_output($methods, $docs = array(), $field_counter = 0, $base_url = "", $suffix = "", $title="Method"){
	//	generate all of the methods output
	$s = "";
	$details = "";
	if(count($methods)){
		$s .= '<h2 class="jsdoc-summary-heading">' . $title . ' Summary <span class="jsdoc-summary-toggle"></span></h2>'
			. '<div class="jsdoc-summary-list">'
			. '<ul>';
		$details .= '<h2>' . $title . 's</h2>';
		foreach($methods as $name=>$method){
			$html = _generate_method_output($method, $name, $docs, $field_counter, $base_url, $suffix);
			$s .= $html["s"];
			$details .= $html["details"];
			$field_counter++;
		}
		$s .= '</ul></div>';	//	method-summary
	}
	return array("s"=>$s, "details"=>$details, "counter"=>$field_counter);
}

function generate_object_html($page, $version, $base_url = "", $suffix = "", $versioned = true, $docs = array()){
	//	$page:
	//		The object to render, i.e. "dojox.charting.Chart2D"
	//	$version:
	//		The version against which to generate the page.
	//	$base_url:
	//		A URL fragment that will be prepended to any link generated.
	//	$suffx:
	//		A string that will be appended to any link generated, i.e. ".html"
	//	$docs:
	//		An optional array of XML documents to run the function against.  See spider.php
	//		for example usage.
	if(!isset($page)){
		throw new Exception("generate_html: you must pass an object name!");
	}
	if(!isset($version)){
		throw new Exception("generate_html: you must pass a version!");
	}

	if(strpos($page, "/") > 0){
		$page = implode(".", explode("/", $page));
	}
	$data_dir = dirname(__FILE__) . "/../data/" . $version . "/";

	//	get the docs to run against.  this can be optionally provided;
	//	if they are they ALL need to be there.
	if(!count($docs)){
		$docs = load_docs($version);
	}

	$xml = $docs["xml"];
	$p_xml = $docs["p_xml"];
	$r_xml = $docs["r_xml"];
	$xpath = $docs["xpath"];
	$p_xpath = $docs["p_xpath"];
	$r_xpath = $docs["r_xpath"];

	//	check if we're to build links versioned and if so, add that to the base url.
	if($versioned){
		$base_url .= $version . '/';
	}

	//	get our object
	$obj = generate_object($page, $version, $docs);
	if(!$obj){
        header(":", true, 400);
        header("Content-type: text/plain");
		echo "The requested object was not found.";
		exit();
	}

	//	process it and output us some HTML.
	$s = '<div class="jsdoc-permalink" style="display:none;">' . $base_url . implode('/', explode(".", $page)) . $suffix . '</div>';

	//	page heading.
	$s .= '<h1 class="jsdoc-title ' . convert_type($obj["type"]) . 'Icon36">'
		. $obj["title"]
		. ' <span style="font-size:11px;color:#999;">(version ' . $version . ')</span>'
		. '</h1>';

	//	prototype chain
	$s .= '<div class="jsdoc-prototype">';
	foreach($obj["prototypes"] as $i=>$p){
		if($i){ $s .= ' &raquo; '; }
		if($p != $page && $p != "Object"){
			$name = implode("/", explode(".", $p));
			$s .= '<a class="jsdoc-link" href="' . $base_url . $name . $suffix . '">' . $p . '</a>';
		} else {
			$s .= $p;
		}
	}
	$s .= '</div>';

	if($page == "dojo"){
		$s .= '<div class="jsdoc-require">&lt;script src="path/to/dojo.js"&gt;&lt;/script&gt;</div>';
	} else if(array_key_exists("require", $obj)) {
		$s .= '<div class="jsdoc-require">dojo.require("' . $obj["require"] . '");</div>';
	}

	if(array_key_exists("resource", $obj)){
		$s .= '<div class="jsdoc-prototype">Defined in ' . $obj["resource"] . '</div>';
	}

	//	usage.  Go look for a constructor.
	if(array_key_exists("methods", $obj) && array_key_exists("constructor", $obj["methods"])){
		$fn = $obj["methods"]["constructor"];
		$s .= '<div class="jsdoc-function-information"><h3>Usage:</h3>'
			. '<div class="function-signature">'
			. '<span class="keyword">var</span> foo = new '
			. $page
			. '(';
		if(count($fn["parameters"])){
			$tmp = array();
			foreach($fn["parameters"] as $param){
				$tmp[] = '<span class="jsdoc-comment-type">/* '
					. $param["type"]
					. ($param["usage"] == "optional" ? "?":"")
					. ' */</span> '
					. $param["name"];
			}
			$s .= implode(", ", $tmp);
		}
		$s .= ');</div></div>';
	}

	if(array_key_exists("description", $obj)){
		$s .= '<div class="jsdoc-full-summary">'
			. do_markdown($obj["description"])
			. "</div>";
	}

	//	examples.
	if(array_key_exists("examples", $obj)){
		$examples = $obj["examples"];
		if(count($examples)){
			$s .= '<div class="jsdoc-examples">'
				. '<h2>Examples:</h2>';
			$counter = 1;
			foreach($examples as $example){
				$s .= '<div class="jsdoc-example">'
					. '<h3>Example ' . $counter++ . '</h3>'
					. format_example($example)
					. '</div>';
			}
			$s .= '</div>';
		}
	}

	//	mixins
	if(array_key_exists("mixins", $obj)){
		$tmp = array();
		$super = $obj["prototypes"][count($obj["prototypes"])-2];
		foreach($obj["mixins"] as $mixin){
			if($mixin != $super){
				$name = implode("/", explode('.', $mixin));
				$tmp[] = '<a class="jsdoc-link" href="' . $base_url . $name . $suffix . '">' . $mixin . '</a>';
			}
		}
		if(count($tmp)){
			$s .= '<div class="jsdoc-mixins"><label>mixins: </label>'
				. implode(", ", $tmp)
				. '</div>';
		}
		
	}

	//	Properties, methods, events, and attached objects.
	$s .= '<div class="jsdoc-children">';
	$s .= '<div class="jsdoc-field-list">';
	$details = '<div class="jsdoc-children">'
		. '<div class="jsdoc-fields">';
	$field_counter = 0;

	$props = $obj["properties"];
	$methods = $obj["methods"];
	$events = $obj["events"];
	if(count($props) || count($methods) || count($events)){
		if(count($props)){
			$tmp = _generate_properties_output($props, $docs, $field_counter, $base_url, $suffix, "Properties");
			$s .= $tmp["s"];
			$details .= $tmp["details"];
			$field_counter = $tmp["counter"];
		}
		if(count($methods)){
			$tmp = _generate_methods_output($methods, $docs, $field_counter, $base_url, $suffix, "Method");
			$s .= $tmp["s"];
			$details .= $tmp["details"];
			$field_counter = $tmp["counter"];
		}
		if(count($events)){
			$tmp = _generate_methods_output($events, $docs, $field_counter, $base_url, $suffix, "Event");
			$s .= $tmp["s"];
			$details .= $tmp["details"];
		}
	}

	//	child objects: put up a list of any child objects that are attached to this particular one.
	if(array_key_exists("attached", $obj) && count($obj["attached"])){
		$children = $obj["attached"];
		$s .= '<h2 class="jsdoc-summary-heading">Attached Objects <span class="jsdoc-summary-toggle"></span></h2>'
			. '<div class="jsdoc-summary-list">';
		foreach($children as $child){
			$s .= '<div class="jsdoc-field">'
				. '<div class="jsdoc-title">'
				. '<span class="';

			$tmp = $xpath->query('//object[@location="' . $child . '"]')->item(0);
			if($tmp){
				if($tmp->getAttribute("type") == "Function" && $tmp->getAttribute("classlike") == "true"){
					$s .= "constructor";
				} else {
					$s .= convert_type($tmp->getAttribute("type"));
				}
			} else {
				$s .= convert_type("Object");
			}
			$s .= '">'
				. '<a class="jsdoc-link" href="' . $base_url . implode("/", explode(".", $child)) . $suffix . '">'
				. $child
				. '</a>'
				. '</span>'
				. '</div>'	//	jsdoc-title
				. '</div>';	//	jsdoc-field
		}
		$s .= '</div>';
	}

	$s .= '</div>';	// jsdoc-field-list.
	$s .= '</div>';	// jsdoc-children.
	$details .= '</div></div>';

	return $s . $details;
}


//	sorting functions used for the tree
function object_node_sorter($a, $b){
	if($a->getAttribute("location") == $b->getAttribute("location")){ return 0; }
	return ($a->getAttribute("location") > $b->getAttribute("location")) ? 1 : -1;
}

function node_reference_sorter($a, $b){
	if(strtolower($a["_reference"]) == strtolower($b["_reference"])) return 0;
	return (strtolower($a["_reference"]) > strtolower($b["_reference"])) ? 1 : -1;
}

//	generate a hierarchical representation of the object tree; based on the class-tree.
//	Note that this structure is generated based on the structure of dojo.data.
function generate_object_tree($version, $roots=array(), $filter=true, $docs=array()){
	//	$version:
	//		The version of the object tree to generate.
	//	$roots:
	//		The objects to be considered the root nodes of the list generated.  If empty,
	//		this will simply look for any objects that do not have a period in the name.
	//	$filter:
	//		A boolean that filters out anything that is considered "private" (i.e. beginning with
	//		an underscore "_")
	//	$docs:
	//		An optional array of XML document objects that will be used as the sources for the tree.

	//	get our source.
	if(!count($docs)){
		$data_dir = dirname(__FILE__) . "/../data/" . $version . "/";
		$f = $data_dir . "objects.xml";
		if(!file_exists($f)){
			throw new Exception("generate_object_tree_html: the required directory/file was not found.");
		}

		$xml = new DOMDocument();
		$xml->load($f);
		$xpath = new DOMXPath($xml);
	} else {
		$xml = $docs["xml"];
		$xpath = $docs["xpath"];
	}

	$objects = $xpath->query("//object");
	$ret = array();
	$counter = 0;

	//	set our top-level objects
	$show = array();
	$keys = array();
	if(count($roots)){
		//	we were given a specific set of root locations.
		foreach($roots as $key=>$value){
			$show[$key] = $value;
			$keys[] = $key;
		}
	} else {
		$r = $xpath->query("//object[not(contains(@location, '.'))]");
		foreach($r as $node){
			if($node->getAttribute("type") == "Function" && $node->getAttribute("classlike") == "true"){
				$show[$node->getAttribute("location")] = -1;
				$keys[] = $node->getAttribute("location");
			}
		}
	}

	//	ok, let's create our internal structure.
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
			if($filter && strpos($short_name, "_") === 0){
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
	
	//	go through the top-level objects and reset the type on it.
	$counter = 0;
	foreach($ret as &$obj){
		$name = $obj["fullname"];
		if(array_key_exists($name, $show)){
			$obj["type"] = "root";
			$show[$name] = $counter;
		}
		$counter++;
	}

	//	finally, move the given namespaces to the top of the array.
	$fin = array();
	foreach($show as $item){
		if(array_key_exists("children", $ret[$item])){
			usort($ret[$item]["children"], "node_reference_sorter");
		}
		$fin[] = &$ret[$item];
	}
	foreach($ret as &$obj){
		if(!array_key_exists($obj["fullname"], $show)){
			if(array_key_exists("children", $obj)){
				usort($obj["children"], "node_reference_sorter");
			}
			$fin[] = $obj;
		}
	}

	return $fin;
}

function _get_branch($obj, $root){
	//	given the object generated by the tree, find all objects that are referenced as children
	//	and return an array.  Note that you should pass both params by reference (i.e. &$myTree)
	//
	//	$obj
	//		The actual tree object to be used for lookup.
	//	$root
	//		The parent object to use for getting children.

	$ret = array();
	foreach($root["children"] as $child){
		foreach($obj as $object){
			if($object["id"] == $child["_reference"]){
				$ret[] = $object;
				break;
			}
		}
	}
	return $ret;
}

function _generate_branch_html($tree, $obj, $base_url = "", $suffix = ""){
	//	recursive private function to "listify" the given branch.
	$s = '<li class="' . ($obj["type"]=="root"?"namespace":$obj["type"]) . 'Icon">'
		. '<a class="jsdoc-link" href="' . $base_url . implode("/", explode(".", $obj["fullname"])) . $suffix . '">'
		. $obj["name"]
		. '</a>';
	if(array_key_exists("children", $obj)){
		$s .= "\n". '<ul class="jsdoc-children">';
		$branch = _get_branch($tree, $obj);
		foreach($branch as $child){
			$s .= _generate_branch_html($tree, $child, $base_url, $suffix);
		}
		$s .= '</ul>' . "\n";
	}
	return $s . '</li>' . "\n";
}

function generate_object_tree_html($tree, $root, $base_url = "", $suffix = ""){
	//	summary:
	//		Given an object tree (such as generated above), create an HTML
	//		version, complete with links.
	//	$tree:
	//		The array structure as given from above.
	//	$root:
	//		The string indicating what root object to use for branching.
	//	$base_url:
	//		A string prepended to any links generated.
	//	$suffix:
	//		A string appended to any links generated.
	if(!isset($tree)){
		throw new Exception("generate_object_tree_html: you must pass in an object tree.");
	}

	//	find the root object in the tree.
	$roots = array();
	foreach($tree as $object){
		if($object["type"] == "root"){
			$roots[] = $object;
		}
	}

	//	let's give it a start.
	$s = '<ul class="jsdoc-navigation">' . "\n";
	foreach($roots as $r){
		if($r["id"] == $root){
			$s .= _generate_branch_html($tree, $r, $base_url, $suffix);
		} else {
			$s .= '<li class="namespaceIcon">'
				. '<a class="jsdoc-link" href="' . $base_url . implode("/", explode(".", $r["fullname"])) . $suffix . '">'
				. $r["name"]
				. '</a>'
				. '</li>' . "\n";
		}
	}

	$s .= '</ul>' . "\n";
	return $s;
}

?>
