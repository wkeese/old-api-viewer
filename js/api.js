require([
	"dojo/dom",
	"dojo/dom-class",
	"dojo/dom-construct",
	"dojo/dom-style",
	"dojo/_base/fx",
	"dojo/_base/lang",
	"dojo/on",
	"dojo/parser",
	"dojo/query",
	"dojo/ready",
	"dijit/registry",
	"dojox/fx/_core",
	"api/ModuleTreeModel",
	"api/ModuleTree",

	// Modules used by the parser
	"dijit/layout/BorderContainer",
	"dijit/layout/TabContainer",
	"dijit/layout/ContentPane",
	"dijit/layout/AccordionContainer"
], function(dom, domClass, domConstruct, domStyle, fx, lang, on, parser, query, ready,
			registry, Line, ModuleTreeModel, ModuleTree){

// This file contains the top level javascript code to setup the tree, etc.

if(currentVersion === undefined){
	//	fallback.
	var currentVersion = "1.8";
}

//	redefine the base URL.
if(page.length){
	var _href = window.location.href.replace(window.location.protocol + '//' + window.location.hostname + '/','')
		.replace('jsdoc/', '')	//	to handle legacy api.dojotoolkit.org URL formation
		.replace(window.location.hash, '');
	baseUrl = window.location.protocol + "//" + window.location.hostname + "/" 
		+ _href.replace(currentVersion + "/", "").replace(page, "").replace(page.split("/").join("."), "").replace(".html","");
	//console.log("The new base URL is ", baseUrl);
	delete _href;
}

function smoothScroll(args){
	//	NB: this is basically dojox.fx.smoothScroll

	return new fx.Animation(lang.mixin({
		beforeBegin: function(){
			if(this.curve){ delete this.curve; }
			var current = { x: args.win.scrollLeft, y: args.win.scrollTop };
			this.curve = new Line([ current.x, current.y ], [ args.node.offsetLeft, args.node.offsetTop ]);
		},
		onAnimate: function(val){
			args.win.scrollLeft = val[0];
			args.win.scrollTop = val[1];
		}
	}, args));
}

paneOnLoad = function(data){
	var context = this.domNode;

	on(context, on.selector("a.jsdoc-link", "click"), function(evt){
		evt.preventDefault();

		// Open tab for specified module
		var tmp = this.href.replace(window.location.href, "").replace(/#.*/, "").split("/");
		var version = tmp[0];
		var page = tmp.slice(1).join("/");
		var pane = addTabPane(page, version);

		// After the page has loaded, scroll to specified anchor in the page
		var anchor = this.href.replace(/.*#/, "");
		if(anchor){
			pane.onLoadDeferred.then(function(){
				var target = query('a[name="' + anchor + '"]', context);
				if(target){
					var anim = smoothScroll({
						node: target[0],
						win: context,
						duration: 600
					}).play();
				}
			});
		}
	});

	// This is for navigating from "method summary" area and scrolling down to the method details.
	on(context, on.selector("a.inline-link", "click"), function(evt){
		evt.preventDefault();
		var target = query('a[name="' + this.href.substr(this.href.indexOf('#')+1) + '"]', context);
		if(target){
			var anim = smoothScroll({
				node: target[0],
				win: context,
				duration: 600
			}).play();
		}
	});


	function adjustLists(){
		// summary:
		//		Hide/show privates and inherited methods according to setting of private and inherited toggle buttons.
		//		Set/remove "odd" class on alternating rows.

		// The alternate approach is to do this through CSS: Toggle a jsdoc-hide-privates and jsdoc-hide-inherited
		// class on the pane's DOMNode, and use :nth-child(odd) to get the gray/white shading of table rows.   The
		// only problem (besides not working on IE6-8) is that the row shading won't account for hidden rows, so you
		// might get contiguous white rows or contiguous gray rows.

		// number of visible rows so far
		var cnt = 1;

		query(".jsdoc-summary-list > ul > li", context).forEach(function(li){
			var hide =
				(!privateOn && domClass.contains(li, "private")) ||
					(!inheritedOn && domClass.contains(li, "inherited"));
			domStyle.set(li, "display", hide ? "none" : "");
			domClass.toggle(li, "odd", cnt%2);
			if(!hide){
				cnt++;
			}
		});
	}

	//	build the toolbar.
	var link = null, perm = query("div.jsdoc-permalink", context), l = window.location;
	if(perm.length){
		link = (page.length ? baseUrl : "") + perm[0].innerHTML;
	}
	var tbc = (link ? '<span class="jsdoc-permalink"><a class="jsdoc-link" href="' + link + '">Permalink</a></span>' : '')
		+ '<label>View options: </label>'
		+ '<span class="trans-icon jsdoc-private"><img src="' + baseUrl + 'css/icons/24x24/private.png" align="middle" border="0" alt="Toggle private members" title="Toggle private members" /></span>'
		+ '<span class="trans-icon jsdoc-inherited"><img src="' + baseUrl + 'css/icons/24x24/inherited.png" align="middle" border="0" alt="Toggle inherited members" title="Toggle inherited members" /></span>';
	var toolbar = domConstruct.create("div", {
		className: "jsdoc-toolbar",
		innerHTML: tbc		
	}, this.domNode, "first");

	var privateBtn = query(".jsdoc-private", toolbar)[0];
	domClass.add(privateBtn, "off");
	on(privateBtn, "click", function(e){
		privateOn = !privateOn;
		domClass.toggle(privateBtn, "off", !privateOn);
		adjustLists();
	});

	var inheritedBtn =  query(".jsdoc-inherited", toolbar)[0];
	on(inheritedBtn, "click", function(e){
		inheritedOn = !inheritedOn;
		domClass.toggle(inheritedBtn, "off", !inheritedOn);
		adjustLists();
	});


	//	if SyntaxHighlighter is present, run it in the content
	if(SyntaxHighlighter){
		SyntaxHighlighter.highlight();
	}

	var privateOn = false, inheritedOn = true;

	//	hide the private members.
	adjustLists();

	//	make the summary sections collapsible.
	query("h2.jsdoc-summary-heading", this.domNode).forEach(function(item){
		on(item, "click", function(e){
			var d = e.target.nextSibling;
			while(d.nodeType != 1 && d.nextSibling){ d = d.nextSibling; }
			if(d){
				var dsp = domStyle.get(d, "display");
				domStyle.set(d, "display", (dsp=="none"?"":"none"));
				query("span.jsdoc-summary-toggle", e.target).forEach(function(item){
					domClass.toggle(item, "closed", dsp=="none");
				});
			}
		});

		query("span.jsdoc-summary-toggle", item).addClass("closed");

		//	probably should replace this with next or something.
		var d = item.nextSibling;
		while(d.nodeType != 1 && d.nextSibling){ d = d.nextSibling; }
		if(d){
			domStyle.set(d, "display", "none");
		}
	});

	//	set the title
	var w = registry.byId("content").selectedChildWidget;
	document.title = w.title + " - " + (siteName || "The Dojo Toolkit");
	
	//	set the content of the printBlock.
	dom.byId("printBlock").innerHTML = w.domNode.innerHTML;
};

addTabPane = function(page, version){
	var p = registry.byId("content");
	var url = baseUrl + "lib/item.php?p=" + page + "&v=" + (version || currentVersion);
	var title = page;

	//	get the children and make sure we haven't opened this yet.
	var c = p.getChildren();
	for(var i=0; i<c.length; i++){
		if(c[i].title == title){
			p.selectChild(c[i]);
			return c[i];
		}
	}
	var pane = new dijit.layout.ContentPane({ 
		href: url, 
		title: title, 
		closable: true,
		parseOnLoad: false,
		onLoad: lang.hitch(pane, paneOnLoad)
	});
	p.addChild(pane);
	p.selectChild(pane);
	return pane;
};

var moduleTree, moduleModel;

buildTree = function(){
	//	handle changing the tree versions.
	if(moduleTree){
		moduleTree.destroyRecursive();
	}

	//	load the module tree data.
	moduleModel = new ModuleTreeModel(baseUrl + 'data/' + currentVersion + '/tree.json');

	moduleTree = new ModuleTree({
		model: moduleModel,
		showRoot: false
	});
	moduleTree.placeAt("moduleTreePane");
};

versionChange = function(e){
	// summary:
	//		Change the version displayed.
	//		TODO.   This currently doesn't work because we need to switch to the old API doc viewer
	//		to see old versions of the API.

	var cv = currentVersion, v = this.options[this.selectedIndex].value;
	if(v.length){
		// switch to the current version and reload the tree.
		currentVersion = v;
		//	TODO: reload the trees.
	} else {
		//	revert the selection.
		for(var i=0, l=this.options.length; i<l; i++){
			if(this.options[i].value == currentVersion){
				this.selectedIndex = i;
				v = this.options[this.selectedIndex].value;
				break;
			}
		}
	}

	//	if we reverted, bug out.
	if(cv == v){ return; }
	currentVersion = v;
	buildTree();
};

ready(function(){
	parser.parse(document.body);
	var w = registry.byId("content");
	if(w){
		w.watch("selectedChildWidget", function(attr, oldVal, selectedChildWidget){
			document.title = selectedChildWidget.title + " - " + (siteName || "The Dojo Toolkit");
		});
	}

	var s = dom.byId("versionSelector");
	s.onchange = lang.hitch(s, versionChange);

	buildTree();

	if(page && currentVersion) {
		var p = addTabPane(page, currentVersion);

		//	handle any URL hash marks.
		if(p && window.location.hash.length){
			var h = p.onLoadDeferred.then(function(){
				var target = query('a[name$="' + window.location.hash.substr(window.location.hash.indexOf('#')+1) + '"]', p.domNode);
				if(target.length){
					var anim = smoothScroll({
						node: target[0],
						win: p.domNode,
						duration: 600
					}).play();
				}
			});
		}
	}
});

});
