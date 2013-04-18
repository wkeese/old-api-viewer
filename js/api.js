require([
	"dojo/_base/array",
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
	"dojo/topic",
	"dijit/registry",
	"dijit/Dialog",
	"dojox/fx/_core",
	"api/ModuleTreeModel",
	"api/ModuleTree",

	// Modules used by the parser
	"dijit/layout/BorderContainer",
	"dijit/layout/TabContainer",
	"dijit/layout/ContentPane",
	"dijit/layout/AccordionContainer"
], function(array, dom, domClass, domConstruct, domStyle, fx, lang, on, parser, query, ready, topic,
			registry, Dialog, Line, ModuleTreeModel, ModuleTree, BorderContainer, TabContainer){

// This file contains the top level javascript code to setup the tree, etc.

var helpDialog;

page = page || "";

function smoothScroll(args){
	//	NB: this is basically dojox.fx.smoothScroll

	var node = args.node,
		win = args.win;

	// Run animation to bring the node to the top of the pane (if possible).   Is that what we want?
	// Or should it move to the center?   Or scroll the minimal amount possible to bring the
	// node into view?
	return new fx.Animation(lang.mixin({
		beforeBegin: function(){
			if(this.curve){ delete this.curve; }
			var current = { x: win.scrollLeft, y: win.scrollTop };

			var target;
			if(node.offsetTop >= win.scrollTop && node.offsetTop + node.clientHeight <= win.scrollTop + win.clientHeight){
				// If node is already in view, don't do any scrolling.   Particularly important when clicking a
				// TreeNode selects (or opens) a tab, which then triggers code for the TreeNode to scroll into view.
				target = [current.x, current.y];
			}else{
				// Otherwise, scroll to near top of containing div
				target = [node.offsetLeft, Math.max(node.offsetTop - 30, 0)];
			}

			this.curve = new Line([ current.x, current.y ], target);
		},
		onAnimate: function(val){
			win.scrollLeft = val[0];
			win.scrollTop = val[1];
		}
	}, args));
}

paneOnLoad = function(data){
	var context = this.domNode;

	// Setup listener so when you click on links to other modules, it opens a new tab rather than refreshing the
	// whole page
	on(context, on.selector("a.jsdoc-link", "click"), function(evt){
		// Don't do this code for the permalink button, that's handled in a different place
		if(domClass.contains(this.parentNode, "jsdoc-permalink")){
			return;
		}

		// Stop the browser from navigating to a new page
		evt.preventDefault();

		// Open tab for specified module
		var tmp = this.href.replace(/^[a-z]*:/, "").replace(baseUrl, "").replace(/#.*/, "").split("/");
		var version = tmp[0];
		var page = tmp.slice(1).join("/");
		var pane = addTabPane(page, version);

		// After the page has loaded, scroll to specified anchor in the page
		var anchor = this.href.replace(/.*#/, "");
		if(anchor){
			pane.onLoadDeferred.then(function(){
				var target = query('a[name="' + anchor + '"]', context);
				if(target[0]){
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
		if(target[0]){
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

		query(".jsdoc-property-list > *", context).forEach(function(li){
			var hide =
				(!extensionOn && domClass.contains(li, "extension-module")) ||
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
	var link = query("div.jsdoc-permalink", context)[0].innerHTML;

	var tbc = (link ? '<span class="jsdoc-permalink"><a class="jsdoc-link" href="' + link + '">Permalink</a></span>' : '')
		+ '<label>View options: </label>'
		+ '<span class="trans-icon jsdoc-extension"><img src="' + baseUrl + 'css/icons/24x24/extension.png" align="middle" border="0" alt="Toggle extension module members" title="Toggle extension module members" /></span>'
		+ '<span class="trans-icon jsdoc-private"><img src="' + baseUrl + 'css/icons/24x24/private.png" align="middle" border="0" alt="Toggle private members" title="Toggle private members" /></span>'
		+ '<span class="trans-icon jsdoc-inherited"><img src="' + baseUrl + 'css/icons/24x24/inherited.png" align="middle" border="0" alt="Toggle inherited members" title="Toggle inherited members" /></span>';
	var toolbar = domConstruct.create("div", {
		className: "jsdoc-toolbar",
		innerHTML: tbc
	}, this.domNode, "first");

	var extensionBtn = query(".jsdoc-extension", toolbar)[0];
	on(extensionBtn, "click", function(e){
		extensionOn = !extensionOn;
		domClass.toggle(extensionBtn, "off", !extensionOn);
		adjustLists();
	});

	var privateBtn = query(".jsdoc-private", toolbar)[0];
	domClass.add(privateBtn, "off");	// initially off
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
		// quick hack to convert <pre><code> --> <pre class="brush: js;" lang="javascript">,
		// as expected by the SyntaxHighlighter
		var children = query("pre code", context);
		children.forEach(function(child){
			var parent = child.parentNode,
				isXML = lang.trim(child.innerText || child.textContent).charAt(0) == "<";
			domConstruct.place("<pre class='brush: " + (isXML ? "xml" : "js") + ";'>" + child.innerHTML + "</pre>",
				parent, "after");
			domConstruct.destroy(parent);
		});

		// run highlighter
		SyntaxHighlighter.highlight();
	}

	// Setup feedback link and dialog
	if(bugdb){
		var helpLink = domConstruct.create("a", {
			"class": "feedback",
			href: bugdb + encodeURIComponent(link),
			target: "_blank",
			innerHTML: "Error in the documentation? Can’t find what you are looking for? Let us know!"
		}, context);

		on(helpLink, "click", function(event){
			if(!event.button && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey){
				event.preventDefault();
				helpDialog.set("content", domConstruct.create("iframe", {
					src: this.href,
					frameborder: "0",
					style: "width: 47em; height: 500px; border: 0 none"
				}));
				helpDialog.show();
			}
		});
	}

	var privateOn = false, inheritedOn = true, extensionOn = true;

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

	// URL to get the tab content.
	var url = baseUrl + "apidata/" + (version || currentVersion) + "/" + page;

	var title = page + " (" + version + ")";

	//	get the children and make sure we haven't opened this yet.
	var c = p.getChildren();
	for(var i=0; i<c.length; i++){
		if(c[i].title == title){
			p.selectChild(c[i]);
			return c[i];
		}
	}
	var pane = new dijit.layout.ContentPane({
		id: page.replace(/[\/.]/g, "_") + "_" + version,
		page: page,		// save page because when we select a tab we locate the corresponding TreeNode
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

// Intentional globals (accessed from welcome tab)
moduleModel = null;
moduleTree = null;

buildTree = function(){
	//	handle changing the tree versions.
	if(moduleTree){
		moduleTree.destroyRecursive();
	}

	// load welcome tab for this version
	registry.byId("baseTab").set("href", baseUrl + "themes/" + theme + "/index.php?v=" + currentVersion);

	//	load the module tree data.
	moduleModel = new ModuleTreeModel(baseUrl + 'apidata/' + currentVersion + '/tree.json');

	moduleTree = new ModuleTree({
		id: "moduleTree",
		model: moduleModel,
		showRoot: false,
		persist: false,		// tree item ids have slashes, which confuses the persist code
		version: currentVersion
	});
	registry.byId("moduleTreePane").set("content", moduleTree);

	var w = registry.byId("content");
	if(w){
		// Code to run when a pane is selected by clicking a tab label (although it also unwantedly runs when a pane is
		// selected by clicking a node in the tree)
		w.watch("selectedChildWidget", function(attr, oldVal, selectedChildWidget){
			// If we are still scrolling the Tree from a previous run, cancel that animation
			if(moduleTree.scrollAnim){
				moduleTree.scrollAnim.stop();
			}

			if(!selectedChildWidget.page){
				// This tab doesn't have a corresponding entry in the tree.   It must be the welcome tab.
				return;
			}

			// Select the TreeNode corresponding to this tab's object.   For dijit/form/Button the path must be
			// ["root", "dijit/", "dijit/form/", "dijit/form/Button"]
			var parts = selectedChildWidget.page.match(/[^/\.]+[/\.]?/g),
				path = ["root"].concat(array.map(parts, function(part, idx){
				return parts.slice(0, idx+1).join("").replace(/\.$/, "");
			}));
			moduleTree.set("path", path).then(function(){
				// And then scroll it into view.
				moduleTree.scrollAnim = smoothScroll({
					node: moduleTree.selectedNodes[0].domNode,
					win: dom.byId("moduleTreePane"),
					duration: 300
				}).play();
			},
			function(err){
				console.log("tree: error setting path to " + path);
			});
		}, true);
	}
};

versionChange = function(e){
	// summary:
	//		Change the version displayed.

	var v = this.options[this.selectedIndex].value;

	//	if we reverted, bug out.
	if(currentVersion == v){ return; }

	currentVersion = v;

	buildTree();
};

ready(function(){
	parser.parse(document.body);
	var w = registry.byId("content");
	if(w){
		// Code to run when a pane is selected
		w.watch("selectedChildWidget", function(attr, oldVal, selectedChildWidget){
			document.title = selectedChildWidget.title + " - " + (siteName || "The Dojo Toolkit");
		});
	}

	// global:
	helpDialog = new dijit.Dialog({ title: "Feedback" }).placeAt(document.body);
	helpDialog.startup();

	// When user selects a choice in the version <select>, switch to that version
	var s = dom.byId("versionSelector");
	s.onchange = lang.hitch(s, versionChange);

	buildTree();

	// If URL pointed to a specific page, load tab for that page.
	// The data is actually already included on the page (for google's benefit), so we could optimize this by using
	// that data instead of loading over XHR again.
	if(page && currentVersion) {
		var p = addTabPane(page, currentVersion);

		//	handle any URL hash marks.
		if(p && window.location.hash.length){
			var h = p.onLoadDeferred.then(function(){
				var target = query('a[name$="' + window.location.hash.substr(window.location.hash.indexOf('#')+1) + '"]', p.domNode);
				if(target[0]){
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
