<?php

// Lookup in biostor
require_once(dirname(__FILE__) . '/ris.php');
require_once(dirname(__FILE__) . '/utils.php');


//----------------------------------------------------------------------------------------

$list_of_matches = array();

function biostor_import($reference)
{
	global $list_of_matches;
	
	$reference->genre = 'article';
	
	// Ignore things we don't have
	//if ($reference->year > 1922) return;
	//if (!in_array($reference->volume, array(38,39,40,41))) return;
	
	// Ignore BioStor stuff
	$ignore = false;
	if (isset($reference->urls))
	{
		foreach ($reference->urls as $url)
		{
			if (preg_match('/biostor/', $url))
			{
				$ignore = true;
			}
		}
	}
	if ($ignore) 
	{ 
		//echo "!Already have\n";
		return; 
	}
	
	// Tropicos
	$reference->title =  preg_replace('/~/Uu', '', $reference->title);	
	$reference->title =  preg_replace('/---/Uu', ' ', $reference->title);	
	
	// clean
	if (isset($reference->issn))
	{
		if ($reference->issn == '0193-4406')
		{
			if (isset($reference->issue))
			{
				if (isset($reference->volume))
				{
					if ($reference->volume == 0)
					{
						$reference->volume = $reference->issue;
						unset($reference->issue);
					}
				}
				else
				{
					$reference->volume = $reference->issue;
					unset($reference->issue);
				}
			}
		}
	}
	
	$go = true;
	
	// print_r($reference);
	
	$openurl = reference2openurl($reference);
	
	// echo $openurl . "\n";
	
	$biostor_id = import_from_openurl($openurl, 0.5, true);
				
	if ($biostor_id == 0)
	{
		//echo "!Not found\n";
	}
	else
	{
		// echo "* " . $reference->publisher_id . " $biostor_id\n";
		
		echo $reference->publisher_id . "\t$biostor_id\n";
		
		$list_of_matches[$reference->publisher_id] = $biostor_id;	
	}
	

}


$filename = '';
if ($argc < 2)
{
	echo "Usage: import.php <RIS file> <mode>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}


$file = @fopen($filename, "r") or die("couldn't open $filename");
fclose($file);

import_ris_file($filename, 'biostor_import');

/*
echo "Matched\n";
foreach ($list_of_matches as $k => $v)
{
	echo "$k\t$v\n";
}
*/

?>