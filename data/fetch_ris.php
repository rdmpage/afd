<?php

// Dump RIS

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');

$rows_per_page = 10;
$skip = 0;

$done = false;

while (!$done)
{
	$resp = $couch->send("GET", "/" . $config['couchdb'] 
		. "/_design/export/_view/ris" . "?skip=$skip&limit=$rows_per_page"
		);	
	
	$articles = json_decode($resp);
	
	//print_r($articles);
	
	foreach ($articles->rows as $row)
	{
		//echo $row->id . "\n";
		echo $row->value . "\n";
	}
	
	$skip += $rows_per_page;

	$done = (count($articles->rows) == 0);
}
	

?>
