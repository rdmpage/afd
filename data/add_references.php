<?php

// Import references from RIS and add to CouchDB
// If reference is in BioStor we get PageIDs and thumbnail
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/lib.php');

require_once (dirname(__FILE__) . '/ris.php');

function couchdb_import($reference)
{
	global $couch;
	global $config;
	
	// Make object Mendeley-like (?)
	
	//print_r($reference);
	
	$obj = new stdclass;
	
	foreach ($reference as $k => $v)
	{
		//echo $k;
		switch ($k)
		{
			case 'abstract':
			case 'authors':
			case 'issue':
			case 'pdf':
			case 'title':
			case 'urls':
			case 'volume':
			case 'year':				
				$obj->${k} = $v;
				break;
				
			case 'secondary_title':
				$obj->publication_outlet = $v;
				break;
				
			case 'biostor':
			case 'doi':
			case 'hdl':
			case 'isbn':
			case 'issn':
			case 'pmid':
				$obj->identifiers->${k} = $v;
				break;
				
			case 'hdl':
				$obj->identifiers->hdl = $v;
				break;
				
			case 'spage':
				$obj->pages = $v;
				if (isset($reference->epage))
				{
					$obj->pages .= '-' . $reference->epage;
				}
				break;
				
			case 'genre':
				switch ($v)
				{
					case 'article':
						$obj->type = "Journal Article";
						break;
						
					case 'book':
						$obj->type = "Book";
						break;
				}
				break;
				
			case 'keywords':
				foreach ($v as $kw)
				{
					//cd6d423e-011b-4a46-b370-ceb5c535772
					if (preg_match('/[a-z0-9]{8}\-[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{11}/', $kw))
					{
						$obj->id = $kw;
					}
				}
				break;
						
				
			default:
				break;
		}
	}
	
	$obj->docType = 'publication';
	
	// Get thumbnail & geo from BioStor
	if (isset($obj->identifiers->biostor))
	{
		$url = 'http://biostor.org/reference/' . $obj->identifiers->biostor . '.json';
		$json = get($url);
		
		$biostor = json_decode($json);
		
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
	}
	
	print_r($obj);
	
	//$operation = 'update';
	$operation = 'add';
	//$operation = 'delete';
	
	// To do: Handle case where missing keyword means we don't have a AFD identifier
	if (isset($obj->id))
	{	
		aud_document($obj, $obj->id, $operation);
	}

}


	
$filename = '1.ris';
$filename = '2.ris';
$filename = '3.ris';
$filename = '4.ris';
$filename = '5.ris';
//$filename = '6.ris';
//$filename = '7.ris';
$filename = '8.ris';
$filename = 'test.ris';
$filename = 'afd.ris';

$filename = '296e26ee-ce62-4efa-8f80-a7a037570a45.ris';

//$filename = '757e3d6a-bbcc-4573-8de3-210c611dc93d.ris';

$filename = '8f5f6ff0-6dcc-49f6-bb7c-60a8325ee210.ris';
$filename = '03811041-933f-4bd1-9b52-44a09d5fcf2e.ris';
$filename = 'c202c04e-3c81-4a8e-a648-1ea83db1cd21.ris';
$filename = 'b72ae515-9c9e-49df-b3f1-936c7c2e3833.ris';

$filename = '21bc7535-4317-4ce0-a287-e13e47efabbe.ris';
$filename = 'b8d89012-9059-4af0-a0a1-093cb69103e8.ris';
$filename = 'c7ae0257-fb30-4e4d-adfd-e68724df0b69.ris';
$filename = 'bd6ed9f5-0edd-4be8-8e39-a8754d8a1985.ris';
$filename = 'db86c064-6846-42c9-81a9-e7d9a1d3fbd0.ris';
$filename = '8da39a69-b913-40ce-a32b-c0bda72cabfc.ris';

// Geo
$filename = '7eb47f5d-f491-45b8-84ef-00a1f7cbf3c8.ris';
$filename = 'e08b6613-7add-4f2e-b934-9869b1d68884.ris';
$filename = 'bc2438b9-9840-45fb-a549-27a934cf1462.ris';
$filename = 'd47ad5f4-08d8-4893-96b0-a4c492077dbe.ris';
$filename = 'c696c01d-88d3-449c-b392-ea592474bec7.ris';
$filename = 'b23c9277-e4d9-48ef-84d1-a124812d7dcb.ris';
$filename = '2fb8db9d-e944-4a5d-8938-3c8e98008d20.ris';

// addme-2010-12-06
$filename = 'addme-2010-12-06.ris';

// 2011-01-10 (first result from checkparse)
$filename = '2011-01-10.ris';

// Import a RIS file and add to CouchDB database
$file = @fopen($filename, "r") or die("couldn't open $filename");
$ris = @fread($file, filesize($filename));
fclose($file);

import_ris($ris, 'couchdb_import');


?>