<?php

// Update references

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/lib.php');

$count = 1;

$filename = 'matched.txt';
$filename = 'm.txt';
$filename = 'fix-biostor.txt';


$file_handle = fopen($filename, "r");

while (!feof($file_handle)) 
{
	$row = fgets($file_handle);
	$parts = explode ("\t", $row);
	
	$id = $parts[0];
	$biostor_id = trim($parts[1]);
	
	// fetch
	
	$url = 'http://127.0.0.1:5984/afd/' . $id;
	
	$json = get($url);
	
	echo $url . "\n";
	
	if ($json != '')
	{
		$obj = json_decode($json);
		
		if (!isset($obj->identifiers))
		{
			$obj->identifiers = new stdclass;
		}
		
		if (!isset($obj->identifiers->biostor))
		{		
			$obj->identifiers->biostor = $biostor_id;
		}
				
		
		// Get details from BioStor
		// Get thumbnail & geo from BioStor
		if (isset($obj->identifiers->biostor))
		{
			$url = 'http://direct.biostor.org/reference/' . $obj->identifiers->biostor . '.json';
			$json = get($url);
			
	
			$biostor = json_decode($json);
			
			if (isset($biostor->identifiers->doi))
			{
				if (!isset($obj->identifiers->doi))
				{		
					$obj->identifiers->doi = $biostor->identifiers->doi;
				}			
			}
				
			if (isset($biostor->thumbnails))
			{
				$obj->thumbnail = $biostor->thumbnails[0];
				$obj->identifiers->bhl = $biostor->bhl_pages[0];
			}
	
			$obj->pageIdentifiers = $biostor->bhl_pages;
	
			if (isset($biostor->geometry))
			{
				$obj->geometry = $biostor->geometry;
			}
		
			print_r($obj);
		
			aud_document($obj, $obj->id, 'update');
		}
		
	}
	
	
		// Give server a break every 10 items
		if (($count++ % 10) == 0)
		{
			$rand = rand(1000000, 3000000);
			echo "\n...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
			usleep($rand);
		}	
	
}


?>