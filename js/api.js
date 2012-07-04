require([
	"dojo",
	"dojo/fx/easing",
	"dojo/on",
	"dojo/parser",
	"dojo/query",
	"dijit/registry",
	"dojox/fx/_core",
	"api/ModuleTreeModel",
	"api/ModuleTree",

	// Modules used by the parser
	"dijit/layout/BorderContainer",
	"dijit/layout/TabContainer",
	"dijit/layout/ContentPane",
	"dijit/layout/AccordionContainer"
], function(dojo, easing, on, parser, query, registry, xfx, ModuleTreeModel, ModuleTree){

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
	//	NB: this is basically dojox.fx.smoothScroll, but for some reason smoothScroll uses target.x/y instead
	//	of left/top.  dojo.coords is returning a different y than the top for some reason.  Maybe position will
	//	be better post 1.3.
	if(!args.target){ 
		args.target = dojo.coords(args.node, true);
	}
	var _anim = function(val){
		args.win.scrollLeft = val[0];
		args.win.scrollTop = val[1];
	};

	var anim = new dojo._Animation(dojo.mixin({
		beforeBegin: function(){
			if(this.curve){ delete this.curve; }
			var current = { x: args.win.scrollLeft, y: args.win.scrollTop };
			anim.curve = new dojox.fx._Line([ current.x, current.y ], [ args.target.l, args.target.t - 12 ]);
			console.log(anim.curve);
		},
		onAnimate: _anim
	}, args));
	return anim;
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

	//	build the toolbar.
	var link = null, perm = query("div.jsdoc-permalink", context), l = window.location;
	if(perm.length){
		link = (page.length ? baseUrl : "") + perm[0].innerHTML;
	}
	var tbc = (link ? '<span class="jsdoc-permalink"><a class="jsdoc-link" href="' + link + '">Permalink</a></span>' : '')
		+ '<label>View options: </label>'
		+ '<span class="trans-icon jsdoc-private"><img src="' + baseUrl + 'css/icons/24x24/private.png" align="middle" border="0" alt="Toggle private members" title="Toggle private members" /></span>'
		+ '<span class="trans-icon jsdoc-inherited"><img src="' + baseUrl + 'css/icons/24x24/inherited.png" align="middle" border="0" alt="Toggle inherited members" title="Toggle inherited members" /></span>';
	var toolbar = dojo.create("div", {
		className: "jsdoc-toolbar",
		innerHTML: tbc		
	}, this.domNode, "first");

	//	if SyntaxHighlighter is present, run it in the content
	if(SyntaxHighlighter){
		SyntaxHighlighter.highlight();
	}

	var privateOn = false, inheritedOn = true;
	//	hide the private members.
	query("div.private, li.private", this.domNode).style("display", "none");

	//	make the summary sections collapsible.
	query("h2.jsdoc-summary-heading", this.domNode).forEach(function(item){
		dojo.connect(item, "onclick", function(e){
			var d = e.target.nextSibling;
			while(d.nodeType != 1 && d.nextSibling){ d = d.nextSibling; }
			if(d){
				var dsp = dojo.style(d, "display");
				dojo.style(d, "display", (dsp=="none"?"":"none"));
				query("span.jsdoc-summary-toggle", e.target).forEach(function(item){
					dojo[(dsp=="none"?"removeClass":"addClass")](item, "closed");
				});
			}
		});

		query("span.jsdoc-summary-toggle", item).addClass("closed");

		//	probably should replace this with next or something.
		var d = item.nextSibling;
		while(d.nodeType != 1 && d.nextSibling){ d = d.nextSibling; }
		if(d){
			dojo.style(d, "display", "none");
		}
	});

	//	set up the buttons in the toolbar.
	query("div.jsdoc-toolbar span.trans-icon", this.domNode).forEach(function(node){
		if(dojo.hasClass(node, "jsdoc-private")){
			dojo.addClass(node, "off");
			dojo.connect(node, "onclick", dojo.hitch(this, function(e){
				privateOn = !privateOn;
				dojo[(privateOn ? "removeClass" : "addClass")](node, "off");
				query("div.private, li.private", this.domNode).forEach(function(n){
					var state = (privateOn ? "" : "none");
					dojo.style(n, "display", state);
				});
			}));
		} else {
			dojo.connect(node, "onclick", dojo.hitch(this, function(e){
				inheritedOn = !inheritedOn;
				dojo[(inheritedOn ? "removeClass" : "addClass")](node, "off");
				query("div.inherited, li.inherited", this.domNode).forEach(function(n){
					var state = (inheritedOn ? "" : "none");
					if(!(!privateOn && dojo.hasClass(n, "private"))){
						dojo.style(n, "display", state);
					}
				});
			}));
		}
	});

	//	set the title
	var w = registry.byId("content").selectedChildWidget;
	document.title = w.title + " - " + (siteName || "The Dojo Toolkit");
	
	//	set the content of the printBlock.
	dojo.byId("printBlock").innerHTML = w.domNode.innerHTML;
};

addTabPane = function(page, version){
	var p = registry.byId("content");
	var url = baseUrl + "lib/item.php?p=" + page.split(".").join("/") + "&v=" + (version || currentVersion);
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
		onLoad: dojo.hitch(pane, paneOnLoad)
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

dojo.addOnLoad(function(){
	parser.parse(document.body);
	var w = registry.byId("content");
	if(w){
		dojo.subscribe(w.id + "-selectChild", w, function(arr){
			document.title = this.selectedChildWidget.title + " - " + (siteName || "The Dojo Toolkit");
			dojo.byId("printBlock").innerHTML = this.selectedChildWidget.domNode.innerHTML;
		});
	}

	var s = dojo.byId("versionSelector");
	s.onchange = dojo.hitch(s, versionChange);

	buildTree();

	if(page && currentVersion) {
		var p = addTabPane(page, currentVersion);

		//	handle any URL hash marks.
		if(p && window.location.hash.length){
			var h = dojo.connect(p, "onLoad", function(){
				dojo.disconnect(h);
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
