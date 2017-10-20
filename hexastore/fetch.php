<?php

require_once (dirname(dirname(__FILE__)) . '/lib.php');


$root = 'ecd1ecdb-9c63-4b18-a7ee-d54118255cb2'; // taxon Temnoplectron
$root = '0e20015e-237f-492b-943f-102caa8acc5b'; // Scarabaeini 

$stack = array();
$stack[] = $root;

$triples = array();

$done =  array();

while (count($stack) > 0)
{
	echo "---------\n";
	echo "Stack\n";
	print_r($stack);
	echo "Done\n";
	print_r($done);
	$id = array_pop($stack);
	$done[] = $id;

	$url = 'http://127.0.0.1:5984/afd/_design/nt/_view/triples?key=' . urlencode('"' . $id . '"');
	
	$json = get($url);
	
	if ($json != '')
	{	
		$obj = json_decode($json);
		
		//print_r($obj);
		
		// process
		
		foreach ($obj->rows as $row)
		{
			$triples[] = $row->value;
			
			// name
			if ($row->value[1] == "http://rs.tdwg.org/ontology/voc/TaxonConcept#hasName")
			{
				$to_visit = $row->value[2];
				$to_visit = preg_replace('/urn:lsid:biodiversity.org.au:(afd.name|afd.taxon|afd.publication):/', '', $to_visit);
				
				if (!in_array($to_visit, $done))
				{
					$stack[] = $to_visit;
				}
			}
			
			// publication
			if ($row->value[1] == "http://rs.tdwg.org/ontology/voc/Common#publishedInCitation")
			{
				$to_visit = $row->value[2];
				$to_visit = preg_replace('/urn:lsid:biodiversity.org.au:(afd.name|afd.taxon|afd.publication):/', '', $to_visit);
				
				if (!in_array($to_visit, $done))
				{
					$stack[] = $to_visit;
				}
			}
			
			// descendant taxa
			if ($row->value[1] == "http://www.w3.org/2000/01/rdf-schema#subClassOf")
			{
				$to_visit = $row->value[0];
				$to_visit = preg_replace('/urn:lsid:biodiversity.org.au:(afd.name|afd.taxon|afd.publication):/', '', $to_visit);
				
				if (!in_array($to_visit, $done))
				{
					$stack[] = $to_visit;
				}
			}
			
		}
		
		
/*		$obj->_id = $obj->id;
		
		$couch->add_update_or_delete_document($obj->results[0],  $obj->_id);
	
		echo $obj->results[0]->name . "\n";
	
	
		// children (next part of the tree to parse)
		if (isset($obj->results[0]->child_taxa))
		{
			foreach ($obj->results[0]->child_taxa as $child)
			{
				$stack[] = $child->id;
			}
		}
		
		// synonyms
		if (isset($obj->results[0]->synonyms))
		{
			foreach ($obj->results[0]->synonyms as $synonym)
			{
				$stack[] = $synonym->id;
			}
		}
		
*/		
	}
	
}
print_r($triples);

echo json_encode($triples);


?>