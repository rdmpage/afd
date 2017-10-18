<?php

// Lookup in biostor
require_once(dirname(__FILE__) . '/ris.php');
require_once(dirname(__FILE__) . '/utils.php');
require_once(dirname(__FILE__) . '/crossref.php');


//----------------------------------------------------------------------------------------

$list_of_matches = array();

function crossref_import($reference)
{
	global $list_of_matches;
	
	$reference->genre = 'article';
	
	// Ignore things we don't have
	//if ($reference->year > 1922) return;
	//if (!in_array($reference->volume, array(38,39,40,41))) return;
	
	// Ignore if we have DOI
	$ignore = false;
	if (isset($reference->doi))
	{
		$ignore = true;
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
	
	//print_r($reference);
	
	$parts = array();
	
	foreach ($reference->authors as $author)
	{
		$a = $author->forename . ' ' . $author->surname;
		$parts[] = $a;
	}
	
	$keys = array('year', 'title', 'secondary_title', 'volume', 'issie', 'spage', 'epage');
	
	foreach ($keys as $k)
	{
		if (isset($reference->{$k}))
		{
			$parts[] = $reference->{$k};
		}
	}
	$query = join(' ', $parts);
	
	// echo "$query\n";
	
	$result = crossref_search($query, true, 0.75);
	
	//print_r($result);
	
	if (isset($result->doi))
	{
		echo $reference->publisher_id . "\t" . $result->doi . "\n";
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

import_ris_file($filename, 'crossref_import');

/*
echo "Matched\n";
foreach ($list_of_matches as $k => $v)
{
	echo "$k\t$v\n";
}
*/

?>