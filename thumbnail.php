<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');

// get object 

$image = '';

$id = $_GET['id'];

$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $id);

$obj = json_decode($resp);

if (isset($obj->thumbnail))
{
	$image = $obj->thumbnail;
	if (preg_match('/^data:(?<mime>image\/.*);base64/', $image, $m))
	{
		//print_r($m);
		header("Content-type: " . $m['mime']);
		$image = preg_replace('/^data:(?<mime>image\/.*);base64/', '', $image);
		echo base64_decode($image);
	}
}
else
{
	$filename = '';

	if (isset($obj->identifiers))
	{
		if (isset($obj->identifiers->doi))
		{
			$filename = dirname(__FILE__) . '/images/doi.png';
		}
		else
		{
			if (isset($obj->identifiers->hdl))
			{
				$filename = dirname(__FILE__) . '/images/hdl.png';
			}
		}
		
	}
	
	if ($filename == '')
	{
		if (isset($obj->pdf))
		{
			$filename = dirname(__FILE__) . '/images/pdf.png';
		}
	}	
	
	if ($filename == '')
	{
		if (isset($obj->urls))
		{
			if (count($obj->urls) > 0)
			{
				$filename = dirname(__FILE__) . '/images/web.png';	
				foreach ($obj->urls as $url)
				{								
					if (preg_match('/http:\/\/www.jstor.org\//', $url))
					{
						$filename = dirname(__FILE__) . '/images/jstor.png';	
						break;
					}
				}
			}
		}
	}	
	
	
			
	if ($filename == '')
	{
		$filename = dirname(__FILE__) . '/images/blank100x100.png';
	}		
	
	
	
	$file = @fopen($filename, "r");
	$png = fread($file, filesize($filename));
	fclose($file);
	
	header('Content-type: image/png');
	echo $png;		
	
}



?>