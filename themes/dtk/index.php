<?php
//	set up $basePath
include("../../config.php");

// and get the version number from URL
$version = $_GET["v"];
?>
<h1>The Dojo Toolkit API</h1>
<p class="dtk-intro">
	Welcome to the Dojo Toolkit API documentation.  You are browsing version <strong><?php echo $version; ?></strong> of the Dojo Toolkit. 
	Use the selector to the left to change versions.
</p>
<p class="dtk-intro">
	To find the object you're interested in, use the tree to the left...or use the quick links below.
	To print the documentation, simply navigate to the object of your choice and hit Print.
</p>
<div class="dtk-objects">
	<h2>The main packages of the Dojo Toolkit</h2>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojo"></div>
		<div class="dtk-object-title">
			<a href='javascript:moduleTree.selectAndClick(["<?php echo ($version < 1.8) ? "dojo" : "dojo/"; ?>"]);'>dojo</a>
		</div>
		<div class="dtk-object-description">
			The granddaddy of the Dojo Toolkit.  Look here for common methods such as dojo.byId or dojo.style.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dijit"></div>
		<div class="dtk-object-title">
			<a href='javascript:moduleTree.selectAndClick(["<?php echo $version < 1.8 ? "dijit" : "dijit/"; ?>"]);'>dijit</a>
		</div>
		<div class="dtk-object-description">
			The user interface framework built on top of Dojo.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojox"></div>
		<div class="dtk-object-title">
			<a href='javascript:moduleTree.selectAndClick(["<?php echo $version < 1.8 ? "dojox" : "dojox/"; ?>"]);'>dojox</a>
		</div>
		<div class="dtk-object-description">
			The namespace of additional Dojo Toolkit projects, including things like Charting, the Grid and DTL.
		</div>
	</div>
	<div class="clear"></div>
	<h2>Common modules and packages of the Dojo Toolkit</h2>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojo-query"></div>
		<div class="dtk-object-title">
			<a href='javascript:moduleTree.selectAndClick([<?php echo $version < 1.8 ? '"dojo", "dojo.query"' : '"dojo/", "dojo/query"'; ?>]);'>dojo/query</a>
		</div>
		<div class="dtk-object-description">
			The CSS3 query selector engine of the Dojo Toolkit.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojo-NodeList"></div>
		<div class="dtk-object-title">
			<a href='javascript:moduleTree.selectAndClick([<?php echo $version < 1.8 ? '"dojo", "dojo.NodeList"' : '"dojo/", "dojo/NodeList"'; ?>]);'>dojo/NodeList</a>
		</div>
		<div class="dtk-object-description">
			The return from any dojo/query call, with lots of goodies.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dijit-form"></div>
		<div class="dtk-object-title">
			<a href='javascript:moduleTree.selectAndClick([<?php echo $version < 1.8 ? '"dijit", "dijit.form"' : '"dijit/", "dijit/form/"'; ?>]);'>dijit/form</a>
		</div>
		<div class="dtk-object-description">
			The form elements of Dijit, including TextBox, Button, FilteringSelect and a lot more.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dijit-layout"></div>
		<div class="dtk-object-title">
			<a href='javascript:moduleTree.selectAndClick([<?php echo $version < 1.8 ? '"dijit", "dijit.layout"' : '"dijit/", "dijit/layout/"'; ?>]);'>dijit/layout</a>
		</div>
		<div class="dtk-object-description">
			Layout widgets to help you design your Dijit-based interface, including BorderContainer and ContentPane.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojox-chart2d"></div>
		<div class="dtk-object-title">
			<a href='javascript:moduleTree.selectAndClick([<?php echo $version < 1.8 ? '"dojox", "dojox.charting"' : '"dojox/", "dojox/charting/", "dojox/charting/Chart"'; ?>]);'>dojox/charting</a>
		</div>
		<div class="dtk-object-description">
			The main object of the Dojo Toolkit&#039;s amazing Charting library.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojox-grid"></div>
		<div class="dtk-object-title">
			<a href='javascript:moduleTree.selectAndClick([<?php echo $version < 1.8 ? '"dojox", "dojox.grid", "dojox.grid.DataGrid"' : '"dojox/", "dojox/grid/", "dojox/grid/DataGrid"'; ?>]);'>dojox/grid</a>
		</div>
		<div class="dtk-object-description">
			 The Grid classes in the Dojo Toolkit, including the DataGrid, EnhancedGrid and TreeGrid.
		</div>
	</div>
	<div class="clear"></div>
</div>

<div class="dtk-doc-tools">
Want to use these documentation tools for your own project?
<a href="<?php
if($version < 1.8){
	echo '/reference-guide/1.7/util/doctools.html';
}else{
	echo 'https://github.com/wkeese/api-viewer/blob/master/README.rst';
} ?>" target="_blank">Find out how!</a>
</div>

<style type="text/css">
.dtk-intro { 
	margin-top: 1.25em;
}
.dtk-intro strong {
	font-size: 1.5em;
}
.dtk-objects { }
.dtk-objects h2 {
	margin-top: 1.5em;
}
.clear { clear: both }
.dtk-object {
	float: left;
	width: 160px;
	min-height: 76px;
	position: relative;
	margin: 6px;
	padding-left: 76px;
}
.dtk-object-image {
	position: absolute;
	top: 0;
	left: 0;
	width: 72px;
	height: 58px;
	background-repeat: no-repeat;
	background-position: center center;
}
.dtk-object-title { font-size: 1.25em; }
.dtk-object-description { font-size: 0.85em; }
.dtk-dojo { background-image: url(<?php echo $basePath ?>/themes/dtk/images/api/dojo.png); }
.dtk-dijit { background-image: url(<?php echo $basePath ?>/themes/dtk/images/api/dijit.png); }
.dtk-dojox { background-image: url(<?php echo $basePath ?>/themes/dtk/images/api/dojox.png); }
.dtk-dojo-query { background-image: url(<?php echo $basePath ?>/themes/dtk/images/api/query.png); }
.dtk-dojo-NodeList { background-image: url(<?php echo $basePath ?>/themes/dtk/images/api/nodelist.png); }
.dtk-dijit-form { background-image: url(<?php echo $basePath ?>/themes/dtk/images/api/form.png); }
.dtk-dijit-layout { background-image: url(<?php echo $basePath ?>/themes/dtk/images/api/layout.png); }
.dtk-dojox-chart2d { background-image: url(<?php echo $basePath ?>/themes/dtk/images/api/charts.png); }
.dtk-dojox-grid { background-image: url(<?php echo $basePath ?>/themes/dtk/images/api/grid.png); }
.dtk-doc-tools {
	font-size: 0.85em;
	margin: 0.5em;
	margin-top: 2em;
	text-align: right;
}
</style>
