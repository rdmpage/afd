<?php

$config['proxy_name'] = 'wwwcache.gla.ac.uk';
$config['proxy_port'] = 8080;

$config['proxy_name'] = '';
$config['proxy_port'] = '';


//--------------------------------------------------------------------------------------------------
/**
 * @brief Test whether HTTP code is valid
 *
 * HTTP codes 200 and 302 are OK.
 *
 * For JSTOR we also accept 403
 *
 * @param HTTP code
 *
 * @result True if HTTP code is valid
 */
function HttpCodeValid($http_code)
{
	if ( ($http_code == '200') || ($http_code == '302') || ($http_code == '403'))
	{
		return true;
	}
	else{
		return false;
	}
}


//--------------------------------------------------------------------------------------------------
/**
 * @brief GET a resource
 *
 * Make the HTTP GET call to retrieve the record pointed to by the URL. 
 *
 * @param url URL of resource
 *
 * @result Contents of resource
 */
function get($url, $userAgent = '', $timeout = 0)
{
	global $config;
	
	$data = '';
	
	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 
	//curl_setopt ($ch, CURLOPT_HEADER,		  1);  

	curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	
	if ($userAgent != '')
	{
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	}	
	
	if ($timeout != 0)
	{
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	}
	
	if ($config['proxy_name'] != '')
	{
		curl_setopt ($ch, CURLOPT_PROXY, $config['proxy_name'] . ':' . $config['proxy_port']);
	}
	
			
	$curl_result = curl_exec ($ch); 
	
	//echo $curl_result;
	
	if (curl_errno ($ch) != 0 )
	{
		echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
	}
	else
	{
		$info = curl_getinfo($ch);
		
		 //$header = substr($curl_result, 0, $info['header_size']);
		//echo $header;
		
		
		$http_code = $info['http_code'];
		
		//echo "<p><b>HTTP code=$http_code</b></p>";
		
		if (HttpCodeValid ($http_code))
		{
			$data = $curl_result;
		}
	}
	return $data;
}

//--------------------------------------------------------------------------------------------------
function import_from_openurl($openurl, $threshold = 0.5)
{
	$found = 0;
	
	// 2. Call BioStor
	$url = 'http://direct.biostor.org/openurl.php?' . $openurl . '&format=json';
	$json = get($url);
	
	//echo $url . "\n";
		
	// 3. Search result
		
	$x = json_decode($json);
	
	//print_r($x);
	//exit();
	
	if (isset($x->reference_id))
	{
		// 4. We have this already
		$found = $x->reference_id;
	}
	else
	{
		// 5. Did we get a (significant) hit? 
		// Note that we may get multiple hits, we use the best one
		$h = -1;
		$n = count($x);
		for($k=0;$k<$n;$k++)
		{
			if ($x[$k]->score > $threshold)
			{
				$h = $k;
			}
		}
		
		if ($h != -1)
		{		
			// 6. We have a hit, construct OpenURL that forces BioStor to save
			$openurl .= '&id=http://www.biodiversitylibrary.org/page/' . $x[$h]->PageID;
			$url = 'http://direct.biostor.org/openurl.php?' . $openurl . '&format=json';

			$json = get($url);
			$j = json_decode($json);
			$found = $j->reference_id;
		}
	}
	//echo "Found $found\n";
	
	return $found;
}

//--------------------------------------------------------------------------------------------------
function reference2openurl($reference)
{
	$openurl = '';
	$openurl .= 'ctx_ver=Z39.88-2004&rft_val_fmt=info:ofi/fmt:kev:mtx:journal';
	//$openurl .= '&genre=article';
	
	if (isset($reference->authors))
	{
		foreach ($reference->authors as $author)
		{
			$name_parts = array();
			
			if (isset($author->forename))
			{
				$name_parts[] = $author->forename;
			}

			if (isset($author->surname))
			{
				$name_parts[] = $author->surname;
			}
			
			if (isset($author->literal) && (count($name_parts) == 0))
			{
				$name_parts[] = $author->literal;
			}
		
			$openurl .= '&rft.au=' . urlencode(join(' ', $name_parts));
		}	
	}
	$openurl .= '&rft.atitle=' . urlencode($reference->title);
	$openurl .= '&rft.jtitle=' . urlencode($reference->secondary_title);
	if (isset($reference->issn))
	{
		$openurl .= '&rft.issn=' . $reference->issn;
	}
	if (isset($reference->series))
	{
		$openurl .= '&rft.series=' . $reference->series;
	}
	$openurl .= '&rft.volume=' . $reference->volume;
	
	if (isset($reference->spage))
	{
		$openurl .= '&rft.spage=' . $reference->spage;
	}
	if (isset($reference->epage))
	{
		$openurl .= '&rft.epage=' . $reference->epage;
	}
	if (isset($reference->pagination))
	{
		$openurl .= '&rft.pages=' . $reference->pagination;
	}
	$openurl .= '&rft.date=' . $reference->year;

	return $openurl;
}

//--------------------------------------------------------------------------------------------------
function bioguid($reference)
{
	$found = false;
	
	//echo reference2openurl($reference) . "\n";
	
	$url = 'http://bioguid.info/openurl.php?' . reference2openurl($reference) . '&display=json';
	$json = get($url);
	
	//echo $url . "\n";
	
	$obj = json_decode($json);
	
	//print_r($obj);
	
	if ($obj->status == 'ok')
	{
		$found = true;
		
		if (isset($obj->issn))
		{
			$reference->issn = $obj->issn;
		}
		if (isset($obj->doi))
		{
			$reference->doi = $obj->doi;
		}
		if (isset($obj->pmid))
		{
			$reference->pmid = $obj->pmid;
		}
		if (isset($obj->hdl))
		{
			$reference->hdl = $obj->hdl;
		}
		if (isset($obj->url))
		{
			$reference->url = $obj->url;
		}
		if (isset($obj->pdf))
		{
			$reference->pdf = $obj->pdf;
		}
		
		if (isset($obj->abstract))
		{
			$reference->abstract = $obj->abstract;
		}

		// Flesh out
		if (isset($obj->atitle) && !isset($reference->title))
		{
			$reference->title = $obj->atitle;
		}
		if (isset($obj->issue) && !isset($reference->issue))
		{
			$reference->issue = $obj->issue;
		}
		if (isset($obj->spage) && !isset($reference->spage))
		{
			$reference->spage = $obj->spage;
		}
		if (isset($obj->epage) && !isset($reference->epage))
		{
			$reference->epage = $obj->epage;
		}
		if (isset($obj->url) && !isset($reference->url))
		{
			$reference->url = $obj->url;
		}
		if (isset($obj->pdf) && !isset($reference->pdf))
		{
			$reference->pdf = $obj->pdf;
		}
		
		
		
		
	}
	
	return $found;
}

//--------------------------------------------------------------------------------------------------
function reference_from_matches($matches)
{
	$reference = new stdclass;

	// title
	$title = $matches['title'];
	$title = html_entity_decode($title, ENT_NOQUOTES, 'UTF-8');
	$title = trim(strip_tags($title));
	$title = preg_replace('/\.$/', '', $title);
	
	// authors
	$authorstring = $matches['authorstring'];
	$authorstring = preg_replace("/,$/u", "", trim($authorstring));
	$authorstring = preg_replace("/&/u", "|", $authorstring);
	$authorstring = preg_replace("/\.,/u", "|", $authorstring);				
	$reference->authors = explode("|", $authorstring);
	
	
	for ($i = 0; $i < count($reference->authors); $i++)
	{
		$author_parts = explode(",", $reference->authors[$i]);
		$forename = $author_parts[1];
		$forename = preg_replace('/([A-Z])\.([A-Z])/', '$1. $2', trim($forename));
		$reference->authors[$i] = $forename . ' ' . trim($author_parts[0]);
	}
						
	$reference->genre = 'article';
	$reference->title = $title;
	$reference->secondary_title = trim(strip_tags($matches['journal']));
	
	if ($matches['series'] != '')
	{
		$reference->series = $matches['series'];
	}
	
	$reference->volume = $matches['volume'];
	
	if ($matches['issue'] != '')
	{
		$reference->issue = $matches['issue'];
	}

	$reference->spage = $matches['spage'];
	$reference->epage = $matches['epage'];
	
	$reference->year = $matches['year'];
	
	//print_r($reference);
	
	return $reference;
}

//--------------------------------------------------------------------------------------------------
function reference2ris($reference)
{
	$ris = '';
	
	$ris .= "TY  - JOUR\n";
	
	if (isset($reference->id))
	{
		$ris .=  "ID  - " . $reference->id . "\n";
	}
	
	
	foreach ($reference->authors as $a)
	{
		$ris .= "AU  - " . $a . "\n";	
	}
	
	$ris .=  "TI  - " . $reference->title . "\n";
	$ris .=  "JF  - " . $reference->secondary_title . "\n";
	$ris .=  "VL  - " . $reference->volume . "\n";
	if (isset($reference->issn))
	{
		$ris .=  "SN  - " . $reference->issn . "\n";
	}
	if (isset($reference->issue))
	{
		$ris .=  "IS  - " . $reference->issue . "\n";
	}
	$ris .=  "SP  - " . $reference->spage . "\n";
	$ris .=  "EP  - " . $reference->epage . "\n";
	$ris .=  "Y1  - " . $reference->year . "///\n";
	if (isset($reference->url))
	{
		$ris .=  "UR  - " . $reference->url . "\n";
	}
	if (isset($reference->doi))
	{
		$ris .= 'M3  - ' . $reference->doi . "\n"; 
	}
	
	if (isset( $reference->pdf))
	{
		$ris .=  "L1  - " . $reference->pdf . "\n";
	}
	if (isset( $reference->doi))
	{
		$ris .=  "UR  - http://dx.doi.org/" . $reference->doi . "\n";
	}
	if (isset( $reference->hdl))
	{
		$ris .=  "UR  - http://hdl.handle.net/" . $reference->hdl . "\n";
	}

	if (isset($reference->abstract))
	{
		$ris .=  "N2  - " . $reference->abstract . "\n";
	}
	
	
	if (isset($reference->keywords))
	{
		foreach ($reference->keywords as $keyword)
		{
			$ris .=  "KW  - " . $keyword . "\n";
		}
	}
	
	
	$ris .=  "ER  - \n";
	$ris .=  "\n";
	
	return $ris;
}				


?>