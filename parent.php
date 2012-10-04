<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');

// get nameString of a taxon

$id = $_GET['id'];

$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $id );
header("Content-type: text/plain; charset=utf-8\n\n");
echo $resp;


?>