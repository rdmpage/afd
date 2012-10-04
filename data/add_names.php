<?php

// Import names and taxa from CSV file and add to CouchDB
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');


$count = 0;

$header = array();

$filename = 'Chordata.csv'; // 32.1 Mb
//$filename = 'MYOBATRACHIDAE.csv';
//$filename = 'test.csv';

$filename = 'afd/HigherTaxa.csv'; // 34.8 Mb
//$filename = 'LowerTaxa.csv'; // 5.5 Mb
//$filename = 'Mollusca.csv'; // 6 Mb
//$filename = 'Insecta.csv'; // 93.7 Mb

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
   $line = fgets($file_handle);
   $line = trim($line);
   $line = preg_replace('/^"/', '', $line);
   $line = preg_replace('/"$/', '', $line);
   $parts = explode('","', $line);
   
   //print_r($parts);
   
   if ($count == 0)
   {
   		for ($i = 0; $i < count($parts); $i++)
   		{
   			$header[$parts[$i]] = $i;
   		}
   }
   else
   {
   
   		$obj = new stdclass;
   		
   		$obj->docType = 'taxonName';
		$obj->guid = $parts[$header['NAME_GUID']];   		
   		
   		// Make the name (for fuck's sake!)
		$obj->rankString = $parts[$header['RANK']];
		
		// Name type
		if ($parts[$header['NAME_TYPE']]!= '')
		{
			$obj->nameType = $parts[$header['NAME_TYPE']];
		}
		
		if ($parts[$header['FAMILY']] != '')
		{
			$obj->family = $header['FAMILY'];
		}
		
		if ($obj->nameType == 'Common')
		{
			// Common name
			$obj->nameComplete = $parts[$header['NAMES_VARIOUS']];
			$obj->nameCompleteHtml = $obj->nameComplete;
		}
		else
		{	
			// Scientific name
			if ($parts[$header['GENUS']] != '')
			{
				$obj->nameComplete = $parts[$header['GENUS']];
				
				if ($parts[$header['SUBGENUS']] != '')
				{
					$obj->nameComplete .= ' (' . $parts[$header['SUBGENUS']] . ')';
				}
				
				if ($parts[$header['SPECIES']] != '')
				{
					$obj->nameComplete .= ' ' . $parts[$header['SPECIES']];
				}
				if ($parts[$header['SUBSPECIES']] != '')
				{
					$obj->nameComplete .= ' ' . $parts[$header['SUBSPECIES']];
				}
				
				$obj->nameCompleteHtml = '<i>' . $obj->nameComplete . '</i>';
			}
			else
			{
				if ($obj->rankString == 'Genus')
				{
					$obj->nameComplete = $parts[$header['NAMES_VARIOUS']];
					$obj->nameCompleteHtml = '<i>' . $obj->nameComplete . '</i>';
				}
				else
				{
					$obj->nameComplete = $parts[$header['SCIENTIFIC_NAME']];
					$obj->nameCompleteHtml = $obj->nameComplete;
				}
			}
		}
				
		// Authorship
		if ($parts[$header['AUTHOR']] != '')
		{
			$obj->authorship = $parts[$header['AUTHOR']];
			$obj->nameWithAuthorship = $obj->nameComplete . ' ' . $obj->authorship;
			$obj->nameWithAuthorshipHtml = $obj->nameCompleteHtml . ' ' . $obj->authorship;
		}

		// Year
		if ($parts[$header['YEAR']] != '')
		{
			$obj->year = $parts[$header['YEAR']];
			
			$obj->nameWithAuthorship .= ', ' . $obj->year;
			$obj->nameWithAuthorshipHtml .= ', ' . $obj->year;			
		}

		// Original
		if ($parts[$header['ORIG_COMBINATION']] != '')
		{
			$obj->originalCombination = ($parts[$header['ORIG_COMBINATION']] == 'Y' ? true : false );
		}

		// Publication
		if ($parts[$header['PUBLICATION_GUID']] != '')
		{
			$obj->publishedInCitation = $parts[$header['PUBLICATION_GUID']];
		}
		if ($parts[$header['PUB_PUB_FORMATTED']] != '')
		{
			$obj->publishedIn = mb_convert_encoding($parts[$header['PUB_PUB_FORMATTED']], 'UTF-8');
		}
		
   		$obj->guid = $parts[$header['NAME_GUID']];
   		$obj->concept = $parts[$header['CONCEPT_GUID']];
   		$obj->taxon = $parts[$header['TAXON_GUID']];
		
		print_r($obj);
		
		$operation = 'add';
		
		if ($obj->guid != '')
		{
			
			// Taxon name
			aud_document($obj, $obj->guid, $operation);
			
			// Taxon concept
			if ($parts[$header['CONCEPT_GUID']] == $parts[$header['TAXON_GUID']])
			{
				$taxon = new stdclass;
				$taxon->docType = 'taxonConcept';
				$taxon->guid = $parts[$header['TAXON_GUID']];   
				$taxon->parent = $parts[$header['PARENT_TAXON_GUID']];
				$taxon->nameString = $obj->nameComplete;
				$taxon->nameStringHtml = $obj->nameCompleteHtml;
				$taxon->rankString = $obj->rankString;
				
				print_r($taxon);
				
				aud_document($taxon, $taxon->guid, $operation);
			}
		}
   }
    
   $count++;
   
  // if ($count > 1000) exit();
}



?>