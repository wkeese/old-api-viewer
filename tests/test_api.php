<?php
	header("Content-Type: text/xml");
//	header("Content-Type: text/plain");

	//	go grab the right XML file.  Note that we can load this directly, since the stylesheet
	//	shouldn't contain any variables.
	$xml = new DOMDocument();
	$xsl = new DOMDocument();

	$xml->load(dirname(__FILE__) . "/../data/HEAD/api.xml");
	$xsl->load(dirname(__FILE__) . "/../xsl/objects.xsl");

	$p = new XSLTProcessor();
	$p->importStylesheet($xsl);

	echo $p->transformToXML($xml);
?>
