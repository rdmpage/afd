<?php

$options = array();

// local
if (1)
{
	$options['host'] = "localhost";
	$options['port'] = 5984;
}


if (0)
{
	// cloudant
	$options['host'] = "rdmpage:peacrab280398@rdmpage.cloudant.com";
	$options['proxy'] = 'wwwcache.gla.ac.uk:8080'; // externally hosted
	$options['port'] = 5984;
}

if (0)
{
	// bionames
	$options['host'] = "rdmpage:peacrab@bionames.org";
	$options['port'] = 5984;
	//$options['proxy'] = 'wwwcache.gla.ac.uk:8080'; 
}


require_once (dirname(__FILE__) . '/config.inc.php');


$couch = new CouchSimple($options);

//--------------------------------------------------------------------------------------------------
class CouchSimple
{
	//----------------------------------------------------------------------------------------------
     function CouchSimple($options)
     {
         foreach($options AS $key => $value) {
             $this->$key = $value;
         }
     }

	//----------------------------------------------------------------------------------------------
     function send($method, $url, $post_data = NULL)
     {
     
		$ch = curl_init(); 
		
		$prefix = 'http://';
		
		if ($this->host != 'localhost')
		{
			$url = $prefix . $this->host . ':' . $this->port . $url;
		}
		else
		{
			$url = $prefix . $this->host . ':' . $this->port . $url;
		}
		
		//echo $url;
		
		curl_setopt ($ch, CURLOPT_URL, $url); 
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
		
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		// Set HTTP headers
		$headers = array();
		$headers[] = 'Content-type: application/json'; // we are sending JSON
		
		// Override Expect: 100-continue header (may cause problems with HTTP proxies
		// http://the-stickman.com/web-development/php-and-curl-disabling-100-continue-header/
		$headers[] = 'Expect:'; 
    	curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
				
		if (isset($this->proxy))
		{
			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
		}
		switch ($method) {
		  case 'POST':
			curl_setopt($ch, CURLOPT_POST, TRUE);
			if (!empty($post_data)) {
			  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			}
			break;
		  case 'PUT':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			if (!empty($post_data)) {
			  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			}
			break;
		  case 'DELETE':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			break;
		}
   		$response = curl_exec($ch);
    	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	
    	//echo $response;
    	
		if (curl_errno ($ch) != 0 )
		{
			echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
		}
    	
    	//echo $http_code . "\n";
   		
   		return $response;
     }
 }

//--------------------------------------------------------------------------------------------------
// Add, update, or delete object 
function aud_document($obj, $id, $operation = 'add')
{
	global $couch;
	global $config;
	
 	if ($operation == 'add')
	{
		// add (PUT as we have identifier)
		$resp = $couch->send("PUT", "/" . $config['couchdb'] . "/" . $id, json_encode($obj));
		$r = json_decode($resp);
		
		if (isset($r->error))
		{
			if ($r->error == 'conflict')
			{
				// Document exists, try update instead
				$operation = 'update';
			}
		}
	}
	
	switch ($operation)
	{			
		case 'delete':
		case 'update':
			$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $id);	
			$r = json_decode($resp);
			$rev = $r->_rev;
			
			if ($operation == 'delete')
			{
				$resp = $couch->send("DELETE", "/" . $config['couchdb'] . "/" . $id . '?rev=' . $rev);
			}
			else
			{
				$obj->_rev = $rev;
				$resp = $couch->send("PUT", "/" . $config['couchdb'] . "/" . $id, json_encode($obj));
			}
			var_dump($resp);

		default:
			break;			
	}
	var_dump($resp);
}
 
?>