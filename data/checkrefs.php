<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');


$filename = 'refs.json';
$file_handle = fopen($filename, "r");

$k = array();
		
while (!feof($file_handle)) 
{
	$line = fgets($file_handle);
	
	if (preg_match('/"id":"(?<id>.*)","key":"(?<key>.*)"/Uu', $line, $m))
	{
		//echo $m['key'] . "\n";
		
		$key = $m['key'];
		$k[$key] = $m['id'];
		
		/*
		
		$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $id);
		$r = json_decode($resp);

		if (isset($r->error))
		{
			echo $id . "\n";
			
			$k[$id] = 1;
		}
		
		*/
		
	}
}

fclose($file_handle);

//print_r($k);

//echo count($k) . "\n";

foreach ($k as $key => $value)
{
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $key);
	$r = json_decode($resp);

	if (isset($r->error))
	{
		//echo $key . "\n";
		
		// Get reference...
		$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $value);
		$r = json_decode($resp);
		
		//print_r($r);
		
		if (isset($r->publishedIn))
		{
			echo $key . "\t" . $r->publishedIn . "\n";
		}
		
	}
	
}




?>
