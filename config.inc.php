<?php

// $Id: //

/**
 * @file config.php
 *
 * Global configuration variables (may be added to by other modules).
 *
 */

global $config;

// Date timezone
date_default_timezone_set('UTC');

// Server-------------------------------------------------------------------------------------------
$config['web_server']	= 'http://localhost'; 
//$config['web_server']	= 'http://iphylo.org'; 
$config['site_name']	= 'Australian Faunal Directory on CouchDB';

// Files--------------------------------------------------------------------------------------------
$config['web_dir']		= dirname(__FILE__);
$config['web_root']		= '/~rpage/afd/';

// CouchDB------------------------------------------------------------------------------------------
$config['couchdb']		= 'afd';

// Proxy settings for connecting to the web--------------------------------------------------------- 
// Set these if you access the web through a proxy server. 
$config['proxy_name'] 	= '';
$config['proxy_port'] 	= '';

//$config['proxy_name'] 	= 'wwwcache.gla.ac.uk';
//$config['proxy_port'] 	= '8080';


$config['use_disqus'] 	= true;


?>