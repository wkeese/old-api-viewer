Manual install for the DTK API documentation tool
--------------------------------------------------

Requirements:
PHP 5.2 or higher
Apache with mod_rewrite enabled

For uploading and processing documentation XML files:
PHP XSL module enabled
PHP cURL module enabled

Instructions to run the site:
Create a virtual host in Apache, and allow Overrides in the definition.

Place the entire API site in the directory where you are pointing the vhost.

Set the permissions on the /data directory to be writable; it should have the
ability to not only write directly to that directory, but to also create sub-
directories and write to them as well.

Open the config.php file, and edit with your specific information (including the
_base_url variable; leave this to be "/" if you are running in the root of a
vhost).  Note that modules to be displayed should all have a value of "-1" (this
is set by the class tree generator), and should be in the order in which you want
the modules to appear within the class tree.

If you are just running the site with the included XML files, that should be 
all there is to running the site; just hit your vhost and go.


Generating the documentation files from your source
---------------------------------------------------

1. get latest Node.js from http://nodejs.org/#download

2. check out js-doc-parse from bill (it has tree.json generation code)

	$ git clone --recursive https://github.com/wkeese/js-doc-parse.git
	$ cd js-doc-parse
	$ git checkout tree

3. edit config.js to give path to dojo (your path may vary from example below):

MacOS:
	environmentConfig: {
		basePath: '../trunk/',
		packages: {
			dojo: 'dojo',
			dijit: 'dijit',
			dojox: 'dojox',
			doh: 'util/doh'
		},
		...
	}

Windows:

	environmentConfig: {
		basePath: 'c:\\users\\me\\trunk\\',
		packages: {
			dojo: 'dojo',
			dijit: 'dijit',
			dojox: 'dojox',
			doh: 'util/doh'
		},
		...
		excludePaths: {
		  ...
      		    /\\(?:tests|nls|demos)\\/,
            ...
		}
	}


4. run parser on dojo source

MacOS:

    $ ./parse.sh ../trunk/dojo ../trunk/dijit ../trunk/dojox

Windows:

  C:\> parse.bat c:\\users\\me\\trunk


This will generate details.xml and tree.json.


5. move files here

Create data/1.8 directory (or whatever the current version is), and move the details.xml and tree.json there.


Theming your API documentation tool
-----------------------------------

TODO

Implementation Notes
--------------------
PHP files:
	- generate.php - utility methods
	- spider.php - used to pre-cache web static HTML versions of pages

The data files are:
	- details.xml - main information about modules
	- tree.json - just the metadata needed to display the tree of modules