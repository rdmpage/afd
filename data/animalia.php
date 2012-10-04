<?php

// Add ANIMALIA (root of animal classification)

// Import names and taxa from CSV file and add to CouchDB
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
			
$operation = 'add';

$taxon = new stdclass;
$taxon->docType = 'taxonConcept';
$taxon->guid = '4647863b-760d-4b59-aaa1-502c8cdf8d3c';   
$taxon->nameString = 'ANIMALIA';
$taxon->nameStringHtml = 'ANIMALIA';
$taxon->rankString = 'Kingdom';

print_r($taxon);

aud_document($taxon, $taxon->guid, $operation);


$taxon = new stdclass;
$taxon->docType = 'taxonConcept';
$taxon->guid = '8edaf6f6-d5f7-45b0-ac82-ef7de21b47d9';   
$taxon->parent = 'ec21b060-78e8-4a37-9e62-b8bef532b001';   
$taxon->nameString = 'UNIRAMIA';
$taxon->nameStringHtml = 'UNIRAMIA';
$taxon->rankString = 'Subphylum';

print_r($taxon);

aud_document($taxon, $taxon->guid, $operation);


$taxon = new stdclass;
$taxon->docType = 'taxonConcept';
$taxon->guid = 'ec21b060-78e8-4a37-9e62-b8bef532b001';   
$taxon->parent = '4647863b-760d-4b59-aaa1-502c8cdf8d3c';   
$taxon->nameString = 'ARTHROPODA';
$taxon->nameStringHtml = 'ARTHROPODA';
$taxon->rankString = 'Phylum';

print_r($taxon);

aud_document($taxon, $taxon->guid, $operation);




$taxon = new stdclass;
$taxon->docType = 'taxonConcept';
$taxon->guid = '9482d422-aed5-4059-ba23-7cd8dfca6fbf';   
$taxon->parent = 'ec21b060-78e8-4a37-9e62-b8bef532b001';   
$taxon->nameString = 'CHELICERATA';
$taxon->nameStringHtml = 'CHELICERATA';
$taxon->rankString = 'Subphylum';

print_r($taxon);

aud_document($taxon, $taxon->guid, $operation);



?>