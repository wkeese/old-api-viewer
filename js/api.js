dojo.require("dojo.parser");
dojo.require("dojo.data.ItemFileReadStore");
dojo.require("dijit.layout.BorderContainer");
dojo.require("dijit.layout.AccordionContainer");
dojo.require("dijit.layout.TabContainer");
dojo.require("dijit.layout.ContentPane");
dojo.require("dijit.Tree");
dojo.require("dojo.fx.easing");
dojo.require("dojox.fx._core");

if(currentVersion === undefined){
	//	fallback.
	var currentVersion = "1.3";
}

var classTree, classStore;
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
	dojo.query("a.jsdoc-link", this.domNode).forEach(function(link){
		link.onclick = function(e){
			dojo.stopEvent(e);
			var tmp = this.href.replace(window.location.href, "").split("/");
			var version = tmp[0];
			var page = tmp.slice(1).join(".");
			addTabPane(page, version);
			return false;
		};
	});

	dojo.query("a.inline-link", this.domNode).forEach(function(link){
		link.onclick = function(e){
			dojo.stopEvent(e);
			var target = dojo.query('a[name="' + this.href.substr(this.href.indexOf('#')+1) + '"]', context);
			if(target.length){
				var anim = smoothScroll({
					node: target[0],
					win: context,
					duration: 600
				}).play();
			}
			return false;
		};
	});

	//	build the toolbar.
	var link = null, perm = dojo.query("div.jsdoc-permalink", context), l = window.location;
	if(perm.length){
		link = l.protocol + "//" + l.host + (l.port.length?":"+l.port:"") + perm[0].innerHTML;
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
	dojo.query("div.private, li.private", this.domNode).style("display", "none");

	//	make the summary sections collapsable.
	dojo.query("h2.jsdoc-summary-heading", this.domNode).forEach(function(item){
		dojo.connect(item, "onclick", function(e){
			var d = e.target.nextSibling;
			while(d.nodeType != 1 && d.nextSibling){ d = d.nextSibling; }
			if(d){
				var dsp = dojo.style(d, "display");
				dojo.style(d, "display", (dsp=="none"?"":"none"));
				dojo.query("span.jsdoc-summary-toggle", e.target).forEach(function(item){
					dojo[(dsp=="none"?"removeClass":"addClass")](item, "closed");
				});
			}
		});

		dojo.query("span.jsdoc-summary-toggle", item).addClass("closed");

		//	probably should replace this with next or something.
		var d = item.nextSibling;
		while(d.nodeType != 1 && d.nextSibling){ d = d.nextSibling; }
		if(d){
			dojo.style(d, "display", "none");
		}
	});

	//	set up the buttons in the toolbar.
	dojo.query("div.jsdoc-toolbar span.trans-icon", this.domNode).forEach(function(node){
		if(dojo.hasClass(node, "jsdoc-private")){
			dojo.addClass(node, "off");
			dojo.connect(node, "onclick", dojo.hitch(this, function(e){
				privateOn = !privateOn;
				dojo[(privateOn ? "removeClass" : "addClass")](node, "off");
				dojo.query("div.private, li.private", this.domNode).forEach(function(n){
					var state = (privateOn ? "" : "none");
					dojo.style(n, "display", state);
				});
			}));
		} else {
			dojo.connect(node, "onclick", dojo.hitch(this, function(e){
				inheritedOn = !inheritedOn;
				dojo[(inheritedOn ? "removeClass" : "addClass")](node, "off");
				dojo.query("div.inherited, li.inherited", this.domNode).forEach(function(n){
					var state = (inheritedOn ? "" : "none");
					if(!(!privateOn && dojo.hasClass(n, "private"))){
						dojo.style(n, "display", state);
					}
				});
			}));
		}
	});

	//	set the title
	var w = dijit.byId("content").selectedChildWidget;
	document.title = w.title + " - " + (siteName || "The Dojo Toolkit");
	
	//	finally set the content of the printBlock.
	dojo.byId("printBlock").innerHTML = w.domNode.innerHTML;
};

addTabPane = function(page, version){
	var p = dijit.byId("content");
	var url = baseUrl + "lib/item.php?p=" + page.split(".").join("/") + "&v=" + (version || currentVersion);
	var title = page.split("/").join(".");

	//	get the children and make sure we haven't opened this yet.
	var c = p.getChildren();
	for(var i=0; i<c.length; i++){
		if(c[i].title == title){
			p.selectChild(c[i]);
			return;
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
};

buildTree = function(){
	//	handle changing the tree versions.
	if(classTree){
		classTree.destroyRecursive();
	}

	//	load the class tree data.
	classStore = new dojo.data.ItemFileReadStore({
		url: baseUrl + 'lib/class-tree.php?v=' + currentVersion
	});

	classTree = new dijit.Tree({
		store: classStore,
		query: { type: 'root' },
		getIconClass: function(item, opened){
			if(!item){ return "objectIcon16"; }
			if(item == this.model.root) {
				return "namespaceIcon16";
			} else {
				if(classStore.getValue(item, "type") == "root"){
					if(classStore.getValue(item, "name") == "djConfig"){
						return "objectIcon16";
					}
					return "namespaceIcon16";
				} else {
					return classStore.getValue(item, "type") + "Icon16";
				}
			}
		},
		onClick: function(item){
			addTabPane(classStore.getValue(item, 'fullname'), currentVersion);
		}
	});
	dijit.byId("classTreePane").domNode.appendChild(classTree.domNode);
};

versionChange = function(e){
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
	var w = dijit.byId("content");
	if(w){
		dojo.subscribe(w.id + "-selectChild", w, function(arr){
			document.title = this.selectedChildWidget.title + " - " + (siteName || "The Dojo Toolkit");
			dojo.byId("printBlock").innerHTML = this.selectedChildWidget.domNode.innerHTML;
		});
	}

	var s = dojo.byId("versionSelector");
	s.onchange = dojo.hitch(s, versionChange);

	buildTree();
});
