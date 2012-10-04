<?php

require_once(dirname(__FILE__) . '/utils.php');

$filename = 'notparsed.txt';
$file_handle = fopen($filename, "r");

$failed = array();

while (!feof($file_handle)) 
{
	$line = fgets($file_handle);
	
	$parts = explode("\t", $line);
	
	$parts[1] = trim($parts[1]);
	
	
	
	if (preg_match('/(?<authorstring>.*)\s*(?<year>[0-9]{4})\.\s+(?<title>.*)\s+<em>(?<journal>.*)<\/em>\s+(?<series>.*)?\s*<strong>(?<volume>\d+)<\/strong>(\((?<issue>\d+)\))?:\s+(?<spage>\d+)([–|-](?<epage>\d+))\b/Uu', $parts[1], $m))
	{
		//print_r($m);
		$matched = true;
		$reference = reference_from_matches($m);
		
		//print_r($reference);
		
		$reference->id = $parts[0];
		$reference->keywords[] = $parts[0];
	
		$openurl = reference2openurl($reference);
		
		//echo $openurl . "\n";
		
		bioguid($reference);
		
		if (isset($reference->epage))
		{
		
			$biostor_id = import_from_openurl($openurl);
			
			if ($biostor_id != 0)
			{
				$found = true;
				
				$reference->url = 'http://biostor.org/reference/' . $biostor_id;
			}	
		}		
		
		
		//print_r($reference);
		
		echo reference2ris($reference);

	}   
	else
	{
		/*
		//echo $parts[1]. "\n";
		
		// Book?
		if (preg_match('/(?<authorstring>.*)\s+(?<year>[0-9]{4})\.\s+<em>(?<title>.*)<\/em>\.\s+(?<publoc>.*)\s*:(?<publisher>.*)\s*((?<frontmatter>[i|v|x]+)\s*)?(?<pages>\d+)\s+pp\.(\s*(?<extra>.*))?$/Uu', $parts[1], $m))
		{
			print_r($m);
			$matched = true;
			//$reference = reference_from_matches($mm);
		}   
		else
		{
			$failed[] = $parts[1];
		}
		*/
	}
}

fclose($file_handle);

print_r($failed);
echo count($failed) . ' failed' . "\n";



?>
