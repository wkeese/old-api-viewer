<?php
	include(dirname(__FILE__) . "/../config.php");
	include("markdown/markdown.php");
	include("geshi/geshi.php");
	function icon_url($type, $size=24){
		$img = "object";
		switch($type){
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
		return '/images/icons/' . $size . 'x' . $size . '/' . $img . '.png';
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
		return Markdown(implode("\n", $fixed));
	}

	//	begin the real work.
	if(!isset($version)){ $version = $defVersion; }
	if(!isset($page)){ $page = "dojo"; }

	//	check if there's URL variables
	if(isset($_GET["p"])){
		$page = $_GET["p"];
	}
	if(isset($_GET["v"])){
		$version = $_GET["v"];
	}

	if(strpos($page, "/") > 0){
		$page = implode(".", explode("/", $page));
	}

	//	load up the doc.
	$f = dirname(__FILE__) . "/../data/" . $version . "/details.xml";
	if(!file_exists($f)){
		$f = dirname(__FILE__) . "/../data/" . $defVersion . "/details.xml";
	}
	if(!file_exists($f)){
		echo "API data does not exist for the default version: " . $defVersion . "<br/>";
		exit();
	}
	$xml = new DOMDocument();
	$xml->load($f);

	$xpath = new DOMXPath($xml);

	//	get our context.
	$context = $xpath->query('//object[@location="' . $page . '"]')->item(0);

	if(!$context){
		$s = '<div style="font-weight: bold;color: #900;">The requested object was not found.</div>';
		echo $s;
		exit();
	}

	//	figure out a few things first.
	$is_constructor = ($context->getAttribute("type")=="Function" && $context->getAttribute("classlike")=="true");
	$nl = $xpath->query('//object[starts-with(@location, "' . $page . '.") and not(starts-with(substring-after(@location, "' . $page . '."), "_"))]');
	$is_namespace = ($nl->length > 0);

	//	start up the output process.
	$s = '<div class="jsdoc-toolbar">'
		. '<span class="jsdoc-permalink"><a class="jsdoc-link" href="/'
	    . $version . '/' . implode("/", explode(".", $page))
		. '">Permalink</a></span>'	
		. '<label>View options: </label>'
		. '<span class="trans-icon jsdoc-private"><img src="/images/icons/24x24/private.png" align="middle" border="0" alt="Toggle private members" title="Toggle private members" /></span>'
		. '<span class="trans-icon jsdoc-inherited"><img src="/images/icons/24x24/inherited.png" align="middle" border="0" alt="Toggle inherited members" title="Toggle inherited members" /></span>'
		. '</div>';

	//	page heading.
	$s .= '<h1 class="jsdoc-title">'
		.'<img class="trans-icon" border="0" width="36" height="36" src="';
	if($is_namespace){
		$s .= '/images/icons/36x36/namespace.png';
	} else if ($is_constructor){
		$s .= '/images/icons/36x36/constructor.png';
	} else {
		$s .= '/images/icons/36x36/object.png';
	}
	$s .= '" />' . $context->getAttribute("location") . '</h1>';

	//	breadcrumbs and prototype chain
	$protos = array();
	$bc = array($context->getAttribute("location"));
	$node = $context;
	while($node && $node->getAttribute("superclass")){
		$sc = $node->getAttribute("superclass");
		$bc[] = $sc;
		$protos[$sc] = $node;
		$node = $xpath->query('//object[@location="' . $sc . '"]')->item(0);
	}
	$bc = array_reverse($bc);

	$s .= '<div class="jsdoc-prototype">Object';
	foreach($bc as $p){
		$s .= ' &raquo; ';
		if($p != $page){
			$name = implode("/", explode(".", $p));
			$s .= '<a class="jsdoc-link" href="/' . $version . '/' . $name . '">' . $p . '</a>';
		} else {
			$s .= $p;
		}
	}
	$s .= '</div>';

	//	require
	if($page == "dojo"){
		$s .= '<div class="jsdoc-require">&lt;script src="path/to/dojo.js"&gt;&lt;/script&gt;</div>';
	} else {
		//	TODO: we don't really know this from the XML, we're just kind of assuming.
		$s .= '<div class="jsdoc-require">dojo.require("' . $page . '");</div>';
	}

	//	description.  XML doesn't have summary for some reason.
	$desc = $xpath->query("description/text()", $context)->item(0);
	if($desc){
		$s .= '<div class="jsdoc-full-summary">'
			. do_markdown($desc->nodeValue)
			. "</div>";
	}

	//	examples.
	$examples = $xpath->query("examples/example", $context);
	if($examples->length > 0){
		$s .= '<div class="jsdoc-examples">'
			. '<h2>Examples:</h2>';
		$counter = 1;
		foreach($examples as $example){
			$g = new GeSHi($example->nodeValue, 'javascript');
			$g->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
			$g->enable_classes();
			$s .= '<div class="jsdoc-example">'
				. '<h3>Example ' . $counter++ . '</h3>'
				. $g->parse_code()
				. '</div>';
		}
		$s .= '</div>';
	}

	//	now it gets ugly.  We need to go get all the properties and methods of ourselves,
	//	plus anything in the prototype chain (i.e. superclass), PLUS anything in the mixins list,
	//	and merge them all together, AND make sure they are unique.  On top of that, we need
	//	to make sure we're getting that info from the top to the bottom.
	$mixins = array();
	$props = array();
	$methods = array();

	//	start with getting the mixins.
	$nl = $xpath->query("mixins[not(@scope)]/mixin[@scope='prototype']|mixins[@scope='prototype']/mixin[@scope='instance']", $context);
	foreach($nl as $m){
		//	again, this is ugly.
		$mixins[$m->getAttribute("location")] = $m;
	}

	//	output the mixin list
	$tmp = array();
	foreach($mixins as $key=>$node){
		//	TODO: don't build the link if it's not part of the fearsome threesome.
		$name = implode("/", explode('.', $key));
		$tmp[] = '<a class="jsdoc-link" href="/' . $version . '/' . $name . '">' . $key . '</a>';
	}
	if(count($tmp)){
		$s .= '<div class="jsdoc-mixins"><label>mixins: </label>'
			. implode(", ", $tmp)
			. '</div>';
	}


	//	and now, this is how we shall do the inheritance!
	$protos = array_reverse($protos);
	$chains = array_merge($protos, $mixins);

	//	now let's get our props and methods.
	foreach($chains as $location=>$node){
		//	properties
		$nl = $xpath->query("properties/property", $node);
		foreach($nl as $n){
			$nm = $n->getAttribute("name");
			if(array_key_exists($nm, $props)){
				//	next one up in the chain overrides the original.
				$props[$nm]["scope"] = $n->getAttribute("scope");
				$props[$nm]["type"] = $n->getAttribute("type");
				$props[$nm]["defines"][] = $location;
			} else {
				$props[$nm] = array(
					"name"=>$nm,
					"scope"=>$n->getAttribute("scope"),
					"visibility"=>($n->getAttribute("private")=="true"?"private":"public"),
					"type"=>$n->getAttribute("type"),
					"defines"=>array($location),
					"override"=>false
				);
			}

			if($n->getElementsByTagName("summary")->length){
				$desc = trim($n->getElementsByTagName("summary")->item(0)->nodeValue);
				if(strlen($desc)){
					$props[$nm]["description"] = $desc;
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
		$nl = $xpath->query("methods/method[not(@constructor)]", $node);
		foreach($nl as $n){
			$nm = $n->getAttribute("name");
			if(array_key_exists($nm, $methods)){
				//	next one up in the chain overrides the original.
				$methods[$nm]["scope"] = $n->getAttribute("scope");
				$methods[$nm]["defines"][] = $location;
			} else {
				$methods[$nm] = array(
					"name"=>$nm,
					"scope"=>$n->getAttribute("scope"),
					"visibility"=>($n->getAttribute("private")=="true"?"private":"public"),
					"parameters"=>array(),
					"return-types"=>array(),
					"defines"=>array($location),
					"override"=>false
				);
			}

			if($n->getElementsByTagName("summary")->length){
				$desc = trim($n->getElementsByTagName("summary")->item(0)->nodeValue);
				if(strlen($desc)){
					$props[$nm]["description"] = $desc;
				}
			}
			if($n->getElementsByTagName("description")->length){
				$desc = trim($n->getElementsByTagName("description")->item(0)->nodeValue);
				if(strlen($desc)){
					$methods[$nm]["description"] = do_markdown($desc);
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
					if($param->getElementsByTagName("description")->length){
						$desc = trim($param->getElementsByTagName("description")->item(0)->nodeValue);
						if(strlen($desc)){
							$item["description"] = do_markdown($desc);
						}
					}
					$methods[$nm]["parameters"][] = $item;
				}
			}

			$rets = $xpath->query("return-types/return-type", $n);
			if($rets->length){
				//	TODO: double-check that the XML will always have this.
				$methods[$nm]["return-types"] = array();
				foreach($rets as $ret){
					$item = array(
						"type"=>$ret->getAttribute("type"),
						"description"=>""
					);
					if($ret->getElementsByTagName("description")->length){
						$desc = trim($ret->getElementsByTagName("description")->item(0)->nodeValue);
						if(strlen($desc)){
							$item["description"] = do_markdown($desc);
						}
					}
					$methods[$nm]["return-types"][] = $item;
				}
			}
		}
	}

	//	now...our properties
	$nl = $xpath->query("properties/property", $context);
	foreach($nl as $n){
		$nm = $n->getAttribute("name");
		if(array_key_exists($nm, $props)){
			//	next one up in the chain overrides the original.
			$props[$nm]["scope"] = $n->getAttribute("scope");
			$props[$nm]["type"] = $n->getAttribute("type");
			$props[$nm]["override"] = true;
		} else {
			$props[$nm] = array(
				"name"=>$nm,
				"scope"=>$n->getAttribute("scope"),
				"visibility"=>($n->getAttribute("private")=="true"?"private":"public"),
				"type"=>$n->getAttribute("type"),
				"defines"=>array(),
				"override"=>false
			);
		}

		if($n->getElementsByTagName("summary")->length){
			$desc = trim($n->getElementsByTagName("summary")->item(0)->nodeValue);
			if(strlen($desc)){
				$props[$nm]["description"] = $desc;
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
	$nl = $xpath->query("methods/method[not(@constructor)]", $context);
	foreach($nl as $n){
		$nm = $n->getAttribute("name");
		if(array_key_exists($nm, $methods)){
			//	next one up in the chain overrides the original.
			$methods[$nm]["scope"] = $n->getAttribute("scope");
			$methods[$nm]["override"] = true;
		} else {
			$methods[$nm] = array(
				"name"=>$nm,
				"scope"=>$n->getAttribute("scope"),
				"visibility"=>($n->getAttribute("private")=="true"?"private":"public"),
				"parameters"=>array(),
				"return-types"=>array(),
				"defines"=>array(),
				"override"=>false
			);
		}

		if($n->getElementsByTagName("summary")->length){
			$desc = trim($n->getElementsByTagName("summary")->item(0)->nodeValue);
			if(strlen($desc)){
				$props[$nm]["description"] = $desc;
			}
		}
		if($n->getElementsByTagName("description")->length){
			$desc = trim($n->getElementsByTagName("description")->item(0)->nodeValue);
			if(strlen($desc)){
				$methods[$nm]["description"] = do_markdown($desc);
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
				if($param->getElementsByTagName("description")->length){
					$desc = trim($param->getElementsByTagName("description")->item(0)->nodeValue);
					if(strlen($desc)){
						$item["description"] = do_markdown($desc);
					}
				}
				$methods[$nm]["parameters"][] = $item;
			}
		}

		$rets = $xpath->query("return-types/return-type", $n);
		if($rets->length){
			//	TODO: double-check that the XML will always have this.
			$methods[$nm]["return-types"] = array();
			foreach($rets as $ret){
				$item = array(
					"type"=>$ret->getAttribute("type"),
					"description"=>""
				);
				if($ret->getElementsByTagName("description")->length){
					$desc = trim($ret->getElementsByTagName("description")->item(0)->nodeValue);
					if(strlen($desc)){
						$item["description"] = do_markdown($desc);
					}
				}
				$methods[$nm]["return-types"][] = $item;
			}
		}
	}

	//	ok, now that (in theory) we have all the properties and methods, let's output them.
	//	properties first; the way we'd done the old API tool is semantically correct but unfortunately
	//	the XML documentation doesn't really give us much of the namespace/constructor determinations,
	//	mostly because of the flat structure and the full locations.
	$s .= '<div class="jsdoc-children">';
	$s .= '<div class="jsdoc-field-list">';
	if(count($props) || count($methods)){
		if(count($props)){
			$s .= '<h2>Properties</h2>';
			ksort($props);
			foreach($props as $name=>$prop){
				$s .= '<div class="jsdoc-field '
					. (isset($prop["visibility"]) ? $prop["visibility"] : 'public') . ' '
					. (isset($prop["defines"]) && count($prop["defines"]) && !$prop["override"] ? 'inherited':'')
					. '">'
					. '<div class="jsdoc-title">'
					. '<span>'
					. '<img class="trans-icon" src="' . icon_url($prop) . '" width="24" height="24" border="0" />'
					. '</span>'
					. $name
					. '</div>';	//	jsdoc-title
				//	inheritance list.
				if(isset($prop["defines"]) && count($prop["defines"])){
					$s .= '<div class="jsdoc-inheritance">'
						. ($prop["override"] ? "Overrides ":"Defined by ");
					$tmp = array();
					foreach($prop["defines"] as $def){
						$tmp[] = '<a class="jsdoc-link" href="/' . $version . '/' . implode("/", explode(".", $def)) . '">'
							. $def
							. '</a>';
					}

					$s .= implode(",", $tmp) . '</div>';	//	jsdoc-inheritance
				}
				if(array_key_exists("description", $prop)){
					$s .= '<div class="jsdoc-summary">' . $prop["description"] . '</div>';
				}
				$s .= '</div>';	//	jsdoc-field
			}
		}

		if(count($methods)){
			$s .= '<h2>Methods</h2>';
			ksort($methods);
			foreach($methods as $name=>$method){
				$s .= '<div class="jsdoc-field '
					. (isset($method["visibility"]) ? $method["visibility"] : 'public') . ' '
					. (isset($method["defines"]) && count($method["defines"]) && !$method["override"] ? 'inherited':'')
					. '">'
					. '<div class="jsdoc-title">'
					. '<span>'
					. '<img class="trans-icon" src="' . icon_url('Function') . '" width="24" height="24" border="0" />'
					. '</span>'
					. $name;
				if(count($method["parameters"])){
					$tmp = array();
					$s .= '<span class="parameters">(';
					foreach($method["parameters"] as $p){
						$tmp[] = $p["name"] 
							. '<span class="jsdoc-comment-type">:'
							. (strlen($p["type"]) ? $p["type"] : 'Object')
							. (strlen($p["usage"]) ? (($p["usage"] == "optional") ? '?' : (($p["usage"] == "one-or-more") ? '...' : '')) : '')
							. '</span>';
					}
					$s .= implode(', ', $tmp)
						. ')</span>';
				} else {
					$s .= '<span class="parameters">()</span>';
				}

				if(count($method["return-types"])){
					$s .= '<span class="jsdoc-return-type">:';
					$tmp = array();
					foreach($method["return-types"] as $rt){
						$tmp[] = $rt["type"];
					}
					$s .= implode("|", $tmp) . '</span>';
				} else {
					$s .= '<span class="jsdoc-return-type">:void</span>';
				}

				$s .= '</div>';	//	jsdoc-title
				//	inheritance list.
				if(isset($prop["defines"]) && count($method["defines"])){
					$s .= '<div class="jsdoc-inheritance">'
						. ($method["override"] ? "Overrides ":"Defined by ");
					$tmp = array();
					foreach($method["defines"] as $def){
						$tmp[] = '<a class="jsdoc-link" href="/' . $version . '/' . implode("/", explode(".", $def)) . '">'
							. $def
							. '</a>';
					}

					$s .= implode(",", $tmp) . '</div>';	//	jsdoc-inheritance
				}
				if(array_key_exists("description", $method)){
					$s .= '<div class="jsdoc-summary">' . $method["description"] . '</div>';
				}
				$s .= '</div>';	//	jsdoc-field
			}
		}
	}

	//	child objects: put up a list of any child objects that are attached to this particular one.
	$children = $xpath->query('//object[starts-with(@location, "' . $page . '.")]');
	if($children->length){
		$s .= '<h2>Attached Objects</h2>';
		foreach($children as $child){
			$s .= '<div class="jsdoc-field">'
				. '<div class="jsdoc-title">'
				. '<span>'
				. '<img class="trans-icon" src="'; 

			if($child->getAttribute("type") == "Function" && $child->getAttribute("classlike") == "true"){
				$s .= icon_url("Constructor");
			} else {
				$s .= icon_url($child->getAttribute("type"));
			}
			$s .= '" width="24" height="24" border="0" />'
				. '</span>'
				. '<a class="jsdoc-link" href="/' . $version . '/' . implode("/", explode(".", $child->getAttribute("location"))) . '">'
				. $child->getAttribute("location")
				. '</a>'
				. '</div>'	//	jsdoc-title
				. '</div>';	//	jsdoc-field
		}
	}

	$s .= '</div>';	// jsdoc-field-list.
	$s .= '</div>';	// jsdoc-children.

	echo $s;
?>
