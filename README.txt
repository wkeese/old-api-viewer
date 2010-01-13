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


If you are looking to generate and process your own documentation
-----------------------------------------------------------------

Requirements:
PHP 5.2 or higher
A checkout of the Dojo Toolkit util "project" (if you are using the DTK build
tools, you should already have this.)

Configuration:
in util/docscripts/modules, place a file with the name of your module (for example,
"myModule") called [name].module.properties.  This is a text file, and should contain
one line--a variable called "location", and the *relative* path to the top-most directory
of your module.  For example:

location = ../../../myProject/myModule/

Save and close the file.

Generating the documentation XML file:
Kick open a terminal or command prompt (Windows), navigate to the util/docscripts directory,
and type in the following command:

php generate.php myModule --serialize=xml --outfile=../../myModule --clean

This will start the documentation parser and create an XML file in the root of your dojotoolkit
checkout called "myModule.xml".  Note that the outfile path is a *relative* path; it is 
relative to the /util/docscripts/cache directory.

Be warned: if your module is very large/extensive, the doc parser will take a long time to
run.  Also, you cannot run this script from a browser--it MUST be run from the command line.


Uploading your generated XML file
---------------------------------
To upload and process your XML file, navigate (in a browser) to:

[myVhost]/lib/upload.php

The barebones upload page asks for two variables: a version number, and a URL to your XML file.
Note that it must be a URL and NOT a straight-up file.  The URL can be anything (cURL is used
to fetch it), and the version can also be any string.

Once you have the two fields filled in, hit the process button.

The site will grab your XML file, run it through a number of XSL transforms, and create a directory
in /data with the version number you entered.  Note that if the version number already exists, it
will reuse that directory and replace any existing XML files within it.

The site will also (again, if it doesn't exist) create a /cache directory within the /data/[version]
directory; if the cache directory already exists, it will delete everything inside of it.  Again,
note that this directory MUST be writable (this is the caching mechanism for the site).

Once the processing is finished, you can then hit your API root again and it will use the new information.


Theming your API documentation tool
-----------------------------------

TODO
