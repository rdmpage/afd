<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');

// All point localities of objects (gulp)

$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/localities/_view/geometry");
header("Content-type: text/plain; charset=utf-8\n\n");
echo $resp;

?>