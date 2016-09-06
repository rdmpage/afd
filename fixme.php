<?php

// fix biostor reference by adding thumbnail
// has to be done outside Glasgow as proxy imposes limit on size of POST 
// and thumbnail is too big for this

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');

$id = '';

if (isset($_GET['id']))
{
	$id = $_GET['id'];
}


if ($id != '')
{
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $id);
	$result = json_decode($resp);
	if (isset($result->error))
	{
	}
	else
	{
		//print_r($result);		
		
		if (isset($result->identifiers->biostor))
		{
			// OK, now we have BioStor id
			
			$url = 'http://biostor.org/reference/' . $result->identifiers->biostor . '.json';
			
			//echo $url;
			
			
			$json = get($url);
			
			if ($json != '')
			{
				
				$biostor = json_decode($json);
				
				if (isset($biostor->thumbnails))
				{
					$result->thumbnail = $biostor->thumbnails[0];
					$result->identifiers->bhl = $biostor->bhl_pages[0];
				}
				
				$result->pageIdentifiers = $biostor->bhl_pages;
				
				if (isset($biostor->geometry))
				{
					$result->geometry = $biostor->geometry;
				}
				
				//print_r($result);
				
				//echo json_encode($result);
				
				// update 
				$resp = $couch->send("PUT", "/" . $config['couchdb'] . "/" . $id, json_encode($result));
				echo $resp;
				
			}
			
		}
		
	}
}
	

?>