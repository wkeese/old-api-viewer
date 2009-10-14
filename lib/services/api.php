<?php
class api {
	private function loadXsl($path, $args=array()){
		//	load the given XSL, and swap out anything in it
		//	for what we need it for.  Useful for things like choosing a specific
		//	object that we're looking up, etc.  Vars in the stylesheet are designated
		//	using {{$foo}}.
		$xsl = file_get_contents($path);
		foreach($args as $key=>$value){
			$xsl = str_replace('{{$' . $key . '}}', $value, $xsl);
		}
		return $xsl;
	}

	private function applyXsl($xml, $xsl=""){
		//	$xml: DomDocument
		//	$xsl: String
		$ss = new DOMDocument();
		$ss->loadXML($xsl);

		$proc = new XSLTProcessor();
		$proc->importStylesheet($xsl);
		$str = $proc->transformToXML($xml);
		return $str;
	}

	//	start public functions.
	
}
?>
