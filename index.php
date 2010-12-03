<?php
//	set up all our variables.
include("config.php");
include("lib/cache.php");

//	find out what versions of the docs we have; if the given version isn't available, switch to the most recent.
$d = dir($dataDir);
$versions = array();
$has_version = false;
while(($entry = $d->read()) !== false){
	if(!(strpos($entry, ".")===0) && file_exists("data/".$entry."/api.xml")){
		$versions[] = $entry;
	}
}
$d->close();
sort($versions);

$parts = array();
$is_page = false;
if(array_key_exists("qs", $_GET) && strlen($_GET["qs"])){
	$r = $_GET["qs"];
	$r = str_replace("jsdoc/", "", $r);
	$parts = explode("/", $r);

	//	check if the version exists
	$version = $parts[0];
	if(in_array($version, $versions)){
		array_shift($parts);
	} else {
		$version = $defVersion;
	}

	if(count($parts)){
		if(count($parts)>1){
			$page = implode("/", $parts);
		} else {
			$page = str_replace(".", "/", $parts[0]);
		}
		$is_page = true;
	}
} else {
	$page = $defPage;
	$version = $defVersion;
}

//	check if the version passed is available.
foreach($versions as $entry){
	if($entry == $version){
		$has_version = true;
		break;
	}
}
if(!$has_version){
	$version = $versions[count($versions)-1];
}

//	get the theme from the config file.
if(!isset($default_theme)){
	$default_theme = "dtk";
}
$th = isset($theme) ? $theme : $default_theme;

//	check to clear the cache or not
if(isset($_GET["clearcache"]) && $use_cache){
	cache_clear($version);
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<title><?php echo ($is_page ? implode(".", explode("/", $page)) : "API Documentation") ?> - The Dojo Toolkit</title>
		<meta http-equiv="X-UA-Compatible" content="chrome=1"/> 
		<link rel="stylesheet" href="<?php echo $basePath; ?>css/jsdoc.css" type="text/css" media="all" />
		<link rel="stylesheet" href="<?php echo $basePath; ?>css/jsdoc-print.css" type="text/css" media="print" />
<?php if(file_exists("themes/" . $th . "/" . $th . ".css")){ ?>
<link rel="stylesheet" href="<?php echo $basePath; ?>themes/<?php echo $th; ?>/<?php echo $th; ?>.css" type="text/css" media="all" />
<?php } ?>
		<script type="text/javascript">djConfig={isDebug:false};</script>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/dojo/1.5/dojo/dojo.xd.js"></script>
		<!-- SyntaxHighlighter -->
		<script type="text/javascript" src="<?php echo $basePath; ?>js/syntaxhighlighter/scripts/shCore.js"><</script>
		<script type="text/javascript" src="<?php echo $basePath; ?>js/syntaxhighlighter/scripts/shBrushJScript.js"><</script>
		<script type="text/javascript" src="<?php echo $basePath; ?>js/syntaxhighlighter/scripts/shBrushXml.js"><</script>
		<link rel="stylesheet" href="<?php echo $basePath; ?>js/syntaxhighlighter/styles/shCore.css" type="text/css" />
		<link rel="stylesheet" href="<?php echo $basePath; ?>js/syntaxhighlighter/styles/shThemeDefault.css" type="text/css" />

		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="keywords" content="The Dojo Toolkit, dojo, JavaScript Framework" />
		<meta name="description" content="The Dojo Toolkit" />
		<meta name="author" content="Dojo Foundation" />
		<meta name="copyright" content="Copyright 2006-2009 by the Dojo Foundation" />
		<meta name="company" content="Dojo Foundation" />

		<script type="text/javascript">
			var baseUrl = "<?php echo $_base_url; ?>";
			var siteName = 'The Dojo Toolkit';
			dojo.addOnLoad(function(){
				dojo.parser.parse(document.body);
<?php if($is_page){ ?>
				(dojo.hitch(dijit.byId("initialPagePane"), paneOnLoad))();
<?php } ?>
				setTimeout(function(){
					var loader = dojo.byId("loader");
					dojo.fadeOut({ node: loader, duration: 500, onEnd: function(){ loader.style.display = "none"; }}).play();
				}, 500);
			});

			var page = '<?php echo ($is_page?$page:"") ?>';
			var currentVersion = '<?php echo $version; ?>';
		</script>
		<script type="text/javascript" src="<?php echo $basePath; ?>js/api.js"></script>
	</head>
	<body class="claro">
		<div id="loader"><div id="loaderInner"></div></div>
		<div id="printBlock"></div>
<!--
		<div dojoType="dojo.data.ItemFileReadStore" jsId="classStore" url="<?php echo $basePath; ?>lib/class-tree.php?v=<?php echo $version; ?>"></div>
		<div dojoType="dojo.data.ItemFileReadStore" jsId="moduleStore" url="<?php echo $basePath; ?>lib/module-tree.php?v=<?php echo $version; ?>"></div>
-->
		<div id="main" dojoType="dijit.layout.BorderContainer" liveSplitters="false">
			<div id="head" dojoType="dijit.layout.ContentPane" region="top">
<?php include("themes/" . $th . "/header.php"); ?>
			</div>
			<div dojoType="dijit.layout.BorderContainer" minSize="20" style="width:300px;" id="navigation" region="leading" splitter="true" gutters="false">
				<div dojoType="dijit.layout.ContentPane" title="Search" region="top">
					<div style="padding: 4px;">
						<label for="versionSelector">Version: </label>
						<select id="versionSelector" style="width:auto;"><?php
foreach($versions as $v){
	echo '<option value="' . $v . '"' . ($version==$v?' selected="true"':'') . '>' . $v . '</option>' . "\n";
}
						?></select>
					</div>
				</div>
				<div dojoType="dijit.layout.AccordionContainer" region="center">
					<div id="classTreePane" dojoType="dijit.layout.ContentPane" title="By Object" selected="true"></div>
<!--
					<div dojoType="dijit.layout.ContentPane" title="By Resource">
						<div id="moduleTree" dojoType="dijit.Tree" store="moduleStore" query="{type:'root'}">
							<script type="dojo/method" event="getIconClass" args="item, opened">
								if(!item || (moduleStore.getValue(item, "type") == "folder" || moduleStore.getValue(item, "type") == "root")){
									return opened ? "dijitFolderOpened":"dijitFolderClosed"; 
								}
								else if(moduleStore.getValue(item, "type") == "file"){
									return "dijitLeaf";
								} 
								else if (!moduleStore.getValue(item, "type").length){
									return "objectIcon16";
								}
								else {
									return moduleStore.getValue(item, "type").toLowerCase() + "Icon16";
								}
							</script>
								<script type="dojo/method" event="onClick" args="item">
								var type = moduleStore.getValue(item, "type");
								if(type != "folder" && type != "root" && type != "file"){
									addTabPane(moduleStore.getValue(item, "name"), currentVersion);
								}
							</script>
						</div>
					</div>
-->
				</div>
				<div dojoType="dijit.layout.ContentPane" region="bottom" style="height:18px;background-color:#f2f2f2;border-top:1px solid #dedede;padding:0 2px 4px 48px;position:relative;overflow:hidden;">
					<span style="position:absolute;top:5px;left:3px;font-size:11px;">Legend: </span>
					<img src="<?php echo $basePath; ?>css/icons/16x16/array.png" align="middle" title="Array" alt="Array" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/boolean.png" align="middle" title="Boolean" alt="Boolean" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/constructor.png" align="middle" title="Constructor" alt="Constructor" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/date.png" align="middle" title="Date" alt="Date" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/domnode.png" align="middle" title="DomNode" alt="DomNode" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/error.png" align="middle" title="Error" alt="Error" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/function.png" align="middle" title="Function" alt="Function" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/namespace.png" align="middle" title="Namespace" alt="Namespace" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/number.png" align="middle" title="Number" alt="Number" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/object.png" align="middle" title="Object" alt="Object" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/regexp.png" align="middle" title="RegExp" alt="RegExp" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/singleton.png" align="middle" title="Singleton" alt="Singleton" border="0" />
					<img src="<?php echo $basePath; ?>css/icons/16x16/string.png" align="middle" title="String" alt="String" border="0" />
				</div>
			</div>
			<div id="content" dojoType="dijit.layout.TabContainer" region="center" tabStrip="true">
				<div id="baseTab" dojoType="dijit.layout.ContentPane" title="Welcome">
<?php include("themes/" . $th . "/index.php"); ?>
				</div>
<?php if($is_page && strlen($page)){
	/*
	echo '<div id="initialPagePane" dojoType="dijit.layout.ContentPane" title="'
		. implode(".", explode("/", $page))
		. '" closable="true" selected="true" onLoad="paneOnLoad" href="'.$basePath.'lib/item.php?v='
		. $version
		. '&p='
		. $page
		. '">'
		. '</div>';
	 // */
} ?>
			</div>
			<div id="foot" dojoType="dijit.layout.ContentPane" region="bottom">
<?php include("themes/" . $th . "/footer.php"); ?>
			</div>
		</div>
	</body>
</html>
