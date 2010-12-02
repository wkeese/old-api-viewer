<?php
/*	cache.php
 *	v.1.0
 *	2010-12-02
 *	TRT
 *
 *	A small refactor of the caching system so that it can be enabled/disabled
 *	via a config setting.
 */

function cache_clear($version){
	$files = glob("data/" . $version . '/cache/*');
	$files = array_filter($files, 'is_file');
	array_map('unlink', $files);
}

function cache_get($version, $page, $ext = 'html'){
	$data_dir = dirname(__FILE__) . "/../data/" . $version . "/";
	$test = implode("/", explode(".", $page));
	if(file_exists($data_dir . 'cache/' . $test . '.' . $ext)){
		$out = file_get_contents($data_dir . 'cache/' . $test . '.' . $ext);
		if($ext == 'json'){
			$out = json_decode($out, true);
		}
		return $out;
	}
	return null;
}

function cache_set($version, $page, $content, $ext = 'html'){
	$data_dir = dirname(__FILE__) . "/../data/" . $version . "/";
	$tmp = explode(".", $page);
	array_pop($tmp);	//	last member is never a directory
	$assembled = array();

	//	make sure the cache directory exists first
	_cache_ensure($data_dir);
	foreach($tmp as $part){
		if(!file_exists($data_dir . 'cache/' . implode('/', $assembled) . '/' . $part)){
			mkdir($data_dir . 'cache/' . implode('/', $assembled) . '/' . $part, 0750);
		}
		$assembled[] = $part;
	}
	$save = $content;
	if($ext == 'json'){
		//	we expect our content to be an object, so JSON encode it to a string.
		$save = json_encode($content);
	}
	return file_put_contents($data_dir . 'cache/' . implode('/', explode('.', $page)) . '.' . $ext, $save);
}

//	more direct methods, used for global things like the class-tree.
function _cache_ensure($dir){
	if(!file_exists($dir . 'cache')){
		mkdir($dir . 'cache', 0750);
	}
}
function _cache_file_get($dir, $filename){
	if(file_exists($dir . 'cache/' . $filename)){
		return file_get_contents($dir . 'cache/' . $filename);
	}
	return null;
}
function _cache_file_set($dir, $filename, $content){
	_cache_ensure($dir);
	return file_put_contents($dir . 'cache/' . $filename, $content);
}
?>
