<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');

// get children 

$id = $_GET['id'];

$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/tree/_view/children?key=" . urlencode('"' . $id . '"'));
header("Content-type: text/plain; charset=utf-8\n\n");
echo $resp;


?>
