<h1>The Dojo Toolkit API</h1>
<p class="dtk-intro">
	Welcome to the Dojo Toolkit API documentation.  You are browsing version <strong><?php echo $version; ?></strong> of the Dojo Toolkit. 
	Use the selector to the left to change versions.
</p>
<p class="dtk-intro">
	To find the object you&#039;re interested in, use the tree to the left...or use the quick links below.
	To print the documentation, simply navigate to the object of your choice and hit Print.
</p>
<div class="dtk-objects">
	<?php 
	if($version < 1.8){
	 ?>
	<h2>The main objects of the Dojo Toolkit</h2>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojo"></div>
		<div class="dtk-object-title">
			<a href="/api/<?php echo $version; ?>/dojo">dojo</a>
		</div>
		<div class="dtk-object-description">
			The granddaddy of the Dojo Toolkit.  Look here for common methods such as dojo.byId or dojo.style.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dijit"></div>
		<div class="dtk-object-title">
			<a href="/api/<?php echo $version; ?>/dijit">dijit</a>
		</div>
		<div class="dtk-object-description">
			The user interface framework built on top of Dojo.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojox"></div>
		<div class="dtk-object-title">
			<a href="/api/<?php echo $version; ?>/dojox">dojox</a>
		</div>
		<div class="dtk-object-description">
			The namespace of additional Dojo Toolkit projects, including things like Charting, the Grid and DTL.
		</div>
	</div>
	<div class="clear"></div>
	<?php
	}
	?>
	<h2>Common objects of the Dojo Toolkit</h2>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojo-query"></div>
		<div class="dtk-object-title">
			<a href="/api/<?php echo $version; ?>/dojo/query">dojo/query</a>
		</div>
		<div class="dtk-object-description">
			The CSS3 query selector engine of the Dojo Toolkit.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojo-NodeList"></div>
		<div class="dtk-object-title">
			<a href="/api/<?php echo $version; ?>/dojo/NodeList">dojo/NodeList</a>
		</div>
		<div class="dtk-object-description">
			The return from any dojo.query call, with lots of goodies.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dijit-form"></div>
		<div class="dtk-object-title">
			<a href="/api/<?php echo $version; ?>/dijit/form">dijit/form</a>
		</div>
		<div class="dtk-object-description">
			The form elements of Dijit, including TextBox, Button, FilteringSelect and a lot more.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dijit-layout"></div>
		<div class="dtk-object-title">
			<a href="/api/<?php echo $version; ?>/dijit/layout">dijit/layout</a>
		</div>
		<div class="dtk-object-description">
			Layout widgets to help you design your Dijit-based interface, including BorderContainer and ContentPane.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojox-chart2d"></div>
		<div class="dtk-object-title">
			<a href="/api/<?php echo $version; ?>/dojox/charting/Chart">dojox/charting/Chart</a>
		</div>
		<div class="dtk-object-description">
			The main object of the Dojo Toolkit&#039;s amazing Charting library.
		</div>
	</div>
	<div class="dtk-object">
		<div class="dtk-object-image dtk-dojox-grid"></div>
		<div class="dtk-object-title">
			<a href="/api/<?php echo $version; ?>/dojox/grid/DataGrid">dojox/grid</a>
		</div>
		<div class="dtk-object-description">
			 The Grid classes in the Dojo Toolkit, including the DataGrid, EnhancedGrid and TreeGrid.
		</div>
	</div>
	<div class="clear"></div>
</div>
<div class="dtk-doc-tools">
	Want to use these documentation tools for your own project?  <a href="<?php if($version <1.8) { echo '/reference-guide/1.7/util/doctools.html' } else {echo 'https://github.com/wkeese/api-viewer/blob/master/README.rst'}; ?> " target="_blank">Find out how!</a>
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
.dtk-dojo { background-image: url(/images/api/dojo.png); }
.dtk-dijit { background-image: url(/images/api/dijit.png); }
.dtk-dojox { background-image: url(/images/api/dojox.png); }
.dtk-dojo-query { background-image: url(/images/api/query.png); }
.dtk-dojo-NodeList { background-image: url(/images/api/nodelist.png); }
.dtk-dijit-form { background-image: url(/images/api/form.png); }
.dtk-dijit-layout { background-image: url(/images/api/layout.png); }
.dtk-dojox-chart2d { background-image: url(/images/api/charts.png); }
.dtk-dojox-grid { background-image: url(/images/api/grid.png); }
.dtk-doc-tools {
	font-size: 0.85em;
	margin: 0.5em;
	margin-top: 2em;
	text-align: right;
}
</style>
<script type="text/javascript">
dojo.ready(function(){
	dojo.query(".dtk-objects a").forEach(function(link){
		link.onclick = function(e){
			dojo.stopEvent(e);
			var tmp = this.href.replace(window.location.protocol + '//' + window.location.hostname + '/','').replace('api/','').split('/');
			var version = tmp[0];
			var page = tmp.slice(1).join(".");
			addTabPane(page, version);
			return false;
		};
	});
});
</script>
