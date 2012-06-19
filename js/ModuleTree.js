define(["dojo/_base/declare", "dijit/Tree"], function(declare, Tree){

	return declare("ModuleTree", Tree, {
		// summary:
		//		Variation on Tree to have icons and correct click behavior

		getIconClass: function(item){

			var type = item.__type.toLowerCase();

			// Lots of modules are marked as type undefined, for which we have no icon, so use object instead.
			// TODO: we also have no icon for instance, so use object icon.
			if(/undefined|instance/.test(type)){
				type = "object";
			}

			return "icon16 " + type + "Icon16";
		},

		onClick: function(item){
			var type = item.__type;
			if(type != "namespace"){
				addTabPane(item.__id, currentVersion);
			}
		}
	});
});
