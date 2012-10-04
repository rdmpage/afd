<?php

// Webhook

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');

// Webhook requires POST
if (count($_POST) == 0)
{
	echo '<html><h1>This page is a Webhook</h1></html>';
	exit();
}

// Do we have data?
$id = NULL;
$data = NULL;

if (isset($_POST['id']))
{
	$id = $_POST['id'];
}
if (isset($_POST['data']))
{
	$data = $_POST['data'];
}

// If no data bail then with 400
if (($id == NULL) || ($data == NULL))
{
	header('HTTP/1.1 400 Bad Request');
	header('Status: 400 Bad Request');
	$_SERVER['REDIRECT_STATUS'] = 400;
	echo 'Bad Request';	
}

// Are we going to do any authentication checks...?
// Could do things like HMAC hash, see 
// http://code.google.com/p/bioguid/source/browse/trunk/www/google/index.php 

// OK, we have data

// Do we have this object?
$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $id);	
$r = json_decode($resp);

// If not then bail with 404 
if (isset($r->error))
{
	header('HTTP/1.1 404 Not Found');
	header('Status: 404 Not Found');
	$_SERVER['REDIRECT_STATUS'] = 404;
	echo 'Object with id="' . $id . '" not found';		
}

// OK, update object with new data

// Decode data (note the vital step of stripping slashes)
$json = stripcslashes($data);
$obj = json_decode($json);

//echo $json;

// Update values
foreach ($r as $key => $value)
{
	switch ($key)
	{
		// Things we are happy to replace
		case 'title':
		case 'publication_outlet':
		case 'volume':
		case 'series':
		case 'issue':
		case 'pages':
		case 'year':
		case 'pdf':
			$r->$key = $obj->$key;
			break;
			
		default:
			break;
	}
}

// Update identifiers
foreach ($obj->identifiers as $key => $value)
{
	if (!isset($r->identifiers->$key))
	{
		$r->identifiers->$key = $value;
	}
}

// If we have BioStor identifier then load more details
if (isset($obj->identifiers->biostor))
{
	// OK, now we have BioStor id
	
	$url = 'http://biostor.org/reference/' . $r->identifiers->biostor . '.json';
	
	//echo $url;
	
	$json = get($url);
	
	$biostor = json_decode($json);
	
	if (isset($biostor->thumbnails))
	{
		$r->thumbnail = $biostor->thumbnails[0];
		$r->identifiers->bhl = $biostor->bhl_pages[0];
	}
	
	$r->pageIdentifiers = $biostor->bhl_pages;
	
	if (isset($biostor->geometry))
	{
		$r->geometry = $biostor->geometry;
	}
}


// Update URLs
if (isset($obj->urls))
{
	if (count($obj->urls) > 0)
	{
		$r->urls = array();
		foreach ($obj->urls as $url)
		{
			if (!in_array($url, $r->urls))
			{
				$r->urls[] = $url;
			}
		}
	}
}

// Update PDF
if (isset($obj->pdf))
{
	$r->pdf = $obj->pdf;
}

// Update BioStor




// Update authors
if (isset($obj->authors))
{
	if (count($obj->authors) > 0)
	{
		$r->authors = array();
		foreach ($obj->authors as $author)
		{
			$r->authors[] = $author;
		}
	}
}

//echo json_encode($r);

$resp = $couch->send("PUT", "/" . $config['couchdb'] . "/" . $id, json_encode($r));

echo $resp;

?>