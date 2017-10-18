<?php


//--------------------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}

$keys = array();

$filename = 'afd/Mollusca.csv';
//$filename = 'afd/Insecta.csv';

$count = 0;

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	
	$row = fgetcsv(
		$file_handle, 
		0, 
		translate_quoted(','),
		translate_quoted('"')
		);

	if (count($keys) == 0)
	{
		$keys = $row;
		
		print_r($row);
	}
	else
	{
		//print_r($row);
		
		$obj = new stdclass;
		
		foreach ($keys as $k => $v)
		{
			
			if ($row[$k] != '')
			{
				if (preg_match('/^(PARENT_)?PUB/', $v))
				{
					$obj->{$v} = utf8_encode($row[$k]);
				}

				if ($v == 'TAXON_GUID')
				{
					$obj->{$v} = utf8_encode($row[$k]);
				}
								
			}
		}
		
		if (isset($obj->PUBLICATION_GUID))
		{		
			// print_r($obj);
			
			if (preg_match('/(?<authorstring>.*)\s*(?<year>[0-9]{4})\.\s+(?<title>.*)\s+<em>(?<journal>.*)<\/em>\s+(?<series>.*)?\s*<strong>(?<volume>\d+)<\/strong>(\((?<issue>\d+)\))?:\s+(?<spage>\d+)([–|-](?<epage>\d+))\b/Uu', $obj->PUB_PUB_FORMATTED, $m))
			{
				//print_r($m);
			}

			if (!isset($obj->PUB_PUB_TITLE) || ($obj->PUB_PUB_TITLE == ''))
			{
				print_r($obj);
				//exit();
			}
			else
			{
				//echo $obj->PUB_PUB_TITLE . "\n";
			}
			
			$count++;
			//if ($count == 10) { exit(); }
		}
		
		
	}
	

	
}



?>

