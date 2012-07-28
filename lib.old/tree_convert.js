// One off code to convert old format of tree to new format.
// Usage: node tree_convert.sh 1.7

var version = process.argv[0],
	http = require("http");

var options = {
	host: 'localhost',
	port: 8080,
	path: '/api1.6/lib/class-tree.php?v=' + version,
	method: 'GET'
};

var req = http.get(options, function(res){
	var data = "";
	res.on('data', function (chunk){
		//console.warn("got chunk len " + chunk.length);
		data += chunk;
	});
	res.on('end', function(){
		// items[] is the data in the old format
		var items = JSON.parse(data).items;

		// function to convert to new format
		function parse(id){
			// Get the item specified by id
			var item = items.filter(function(item){ return item.id == id; })[0];

			if(!item) debugger;

			// recurse on children
			if(item.children){
				item.children = item.children.map(function(ref){
					return parse(ref._reference);
				});
			}

			return item;
		}

		var out = {
			"id": "root",
			"type": "folder",
			"children": [
				parse("dojo"),
				parse("dijit"),
				parse("dojox"),
				parse("djConfig")
			]
		};
		out.children.forEach(function(item){ item.type = "namespace"; });	// "root" --> "namespace" for dojo, dijit, etc.
		out.children[3].type = "object";	// djConfig.type

		console.log(JSON.stringify(out, null, "\t"));
	});
});

req.on('error', function(e){
	console.error('problem with request: ' + e.message);
});
