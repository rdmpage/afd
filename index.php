<?php

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/html.php');
require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/qt.php');


//--------------------------------------------------------------------------------------------------
/**
 * @brief Create a COinS (ContextObjects in Spans) for a reference
 *
 * COinS encodes an OpenURL in a <span> tag. See http://ocoins.info/.
 *
 * @param reference Reference object to be encoded
 *
 * @return HTML <span> tag containing a COinS
 */
function reference_to_coins($reference)
{
	global $config;
	
	$coins = '<span class="Z3988" title="' 
		. reference_to_openurl($reference) 
		. '&amp;webhook=' . urlencode($config['web_server'] . $config['web_root'] . 'webhook.php')
		. '"></span>';
	return $coins;
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Create an OpenURL for a reference
 * *
 * @param reference Reference object to be encoded
 *
 * @return OpenURL
 */
function reference_to_openurl($reference)
{
	$openurl = '';
	$openurl .= 'ctx_ver=Z39.88-2004';

	// Local publication identifier
	$openurl .= '&amp;rfe_id=' . urlencode($reference->_id);

	switch ($reference->type)	
	{

		case 'Journal Article':
			$openurl .= '&amp;rft_val_fmt=info:ofi/fmt:kev:mtx:journal';
			$openurl .= '&amp;genre=article';
			if (count($reference->authors) > 0)
			{
				$openurl .= '&amp;rft.aulast=' . urlencode($reference->authors[0]->surname);
				$openurl .= '&amp;rft.aufirst=' . urlencode($reference->authors[0]->forename);
			}
			foreach ($reference->authors as $author)
			{
				$openurl .= '&amp;rft.au=' . urlencode($author->forename . ' ' . $author->surname);
			}
			$openurl .= '&amp;rft.atitle=' . urlencode($reference->title);
			$openurl .= '&amp;rft.jtitle=' . urlencode($reference->publication_outlet);
			if (isset($reference->series))
			{
				$openurl .= '&amp;rft.series/' . urlencode($reference->series);
			}
			if (isset($reference->identifiers->issn))
			{
				$openurl .= '&amp;rft.issn=' . $reference->identifiers->issn;
			}
			$openurl .= '&amp;rft.volume=' . $reference->volume;
			
			if (preg_match('/\-/', $reference->pages))
			{
				$pages = explode("-", $reference->pages);
				$openurl .= '&amp;rft.spage=' . $pages[0];
				$openurl .= '&amp;rft.epage=' . $pages[1];
			}
			else
			{
				$openurl .= '&amp;rft.spage=' . $reference->pages;
			}
			$openurl .= '&amp;rft.date=' . $reference->year;
									
			if (isset($reference->identifiers->doi))
			{
				$openurl .= '&amp;rft_id=info:doi/' . urlencode($reference->identifiers->doi);
			}
			else if (isset($reference->identifiers->hdl))
			{
				$openurl .= '&amp;rft_id=info:hdl/' . urlencode($reference->identifiers->hdl);
			}			
			
			if (isset($reference->urls))
			{
				foreach ($reference->urls as $url)
				{
					$openurl .= '&amp;rft_id='. urlencode($url);
				}
			}
			break;
			
		default:
			break;
	}
	
	return $openurl;
}
//--------------------------------------------------------------------------------------------------
/**
 * @brief Create a simple text string citation for a authors of a reference
 *
 * @param reference Reference object
 *
 * @return Author string 
 */
function reference_authors_to_text_string($authors, $truncate = false, $link = false)
{
	global $config;
	
	$text = '';
	
	$count = 0;
	$num_authors = count($authors);
	if ($num_authors > 0)
	{
		foreach ($authors as $author)
		{
			if ($link)
			{
				$text .= '<a href="' .  $config['web_root'] . 'author/' . urlencode($author->surname . ',' . str_replace('.', '', $author->forename)) . '">' .  $author->forename . ' ' . $author->surname . '</a>';
			}
			else
			{
				$text .= $author->forename . ' ' . $author->surname;
			}
			$count++;
			if ($count == 2 && $num_authors > 3 && $truncate)
			{
				$text .= 'et al.';
				break;
			}
			if ($count < $num_authors -1)
			{
				$text .= ', ';
			}
			else if ($count < $num_authors)
			{
				$text .= ' and ';
			}	
			
		}
	}
	
	return $text;
}


//--------------------------------------------------------------------------------------------------
function default_display()
{
	global $config;
	global $couch;
	
	header("Content-type: text/html; charset=utf-8\n\n");
	echo html_html_open();
	echo html_head_open();
	echo html_title($config['site_name']);	
	echo html_include_css('css/main.css');
	echo html_head_close();
	echo html_body_open();	
	echo html_search_box();
	
	echo '<div style="padding:10px;">';

	
	// Stuff to go here...
	echo '<h1>' . $config['site_name'] . '</h1>';
	
	echo '<p>Linking taxonomic names and literature to the Biodiversity Heritage Library.</p>';
	
	echo '<h3>Statistics</h3>';
	echo '<p>Summary of how many reference in the database have been parsed inton their component parts (title, journal, volume, pages), and what number of those have been linked to a digital identifier and/or full text.</p>';
	echo '<table>';
	
	$total = 0;
	
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/count/_view/publications");
	$result = json_decode($resp);
	if (isset($result->error))
	{
	}
	else
	{
		echo '<tr><td>Total number of publications parsed</td><td align="right">' . $result->rows[0]->value . '</td</tr>';
		
		$total = $result->rows[0]->value;
	}
	
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/count/_view/publicationsWithIdentifiers");
	$result = json_decode($resp);
	if (isset($result->error))
	{
	}
	else
	{
		echo '<tr><td>with identifiers</td><td align="right">' . $result->rows[0]->value . '</td>'
		. '<td>(' . floor(100 * $result->rows[0]->value/$total) . '%' . ')</td>'
		. '</tr>';
	}
	
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/count/_view/biostor");
	$result = json_decode($resp);
	if (isset($result->error))
	{
	}
	else
	{
		echo '<tr><td>found in BioStor (BHL)</td><td align="right">' . $result->rows[0]->value . '</td>'
		. '<td>(' . floor(100 * $result->rows[0]->value/$total) . '%' . ')</td>'
		. '</tr>';
	}
	
	
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/count/_view/doi");
	$result = json_decode($resp);
	if (isset($result->error))
	{
	}
	else
	{
		echo '<tr><td>with DOIs</td><td align="right">' . $result->rows[0]->value . '</td>'
		. '<td>(' . floor(100 * $result->rows[0]->value/$total) . '%' . ')</td>'
		. '</tr>';
	}

	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/count/_view/hdl");
	$result = json_decode($resp);
	if (isset($result->error))
	{
	}
	else
	{
		echo '<tr><td>with Handles</td><td align="right">' . $result->rows[0]->value . '</td>'
		. '<td>(' . floor(100 * $result->rows[0]->value/$total) . '%' . ')</td>'
		. '</tr>';
	}

	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/count/_view/url");
	$result = json_decode($resp);
	if (isset($result->error))
	{
	}
	else
	{
		echo '<tr><td>with one or more URLs</td><td align="right">' . $result->rows[0]->value . '</td>'
		. '<td>(' . floor(100 * $result->rows[0]->value/$total) . '%' . ')</td>'
		. '</tr>';
	}

	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/count/_view/pdf");
	$result = json_decode($resp);
	if (isset($result->error))
	{
	}
	else
	{
		echo '<tr><td>with PDFs</td><td align="right">' . $result->rows[0]->value . '</td>'
		. '<td>(' . floor(100 * $result->rows[0]->value/$total) . '%' . ')</td>'
		. '</tr>';
	}


	echo '</table>';
	
	
	echo '<h3>Getting started</h3>';
	
	// 	<td width="128" align="center"><img src="images/homepage/frog.gif"></img></td><td valign="top">View the article <a href="id/2895bab7-1771-402a-ba2a-f5ff575cddf9">Description of <i>Austrochaperina</i>, a new genus of Engystomatidae from north Australia</a> by D. B. Fry, <i>Records of The Australian Museum</i> 9:87-106 (1912)</td>

	
	echo '<table width="70%">
	
	<tr>
	
	<td width="128" align="center"><a href="map.php"><img src="images/homepage/map.png" border="0"></img></a></td><td valign="top">View articles on an <a href="map.php">interactive map</a>.</td>
	
	<td width="128" align="center" valign="top"><img src="images/homepage/220px-Rain_Frog_-_Austrochaperina_pluvialis.jpg" width="128"></img></td><td valign="top"><i>Austrochaperina</i> is a genus of microhylid frogs found on New Guinea, New Britain and Australia (from <a href="http://en.wikipedia.org/wiki/Austrochaperina">Wikipedia</a>). Browse the taxonomy of <a href="id/256c2521-2bdc-4fc3-9f97-5369f702f574">Australian members of this genus Austrochaperina</a>.</td>
	
	
	</tr>
	<tr>

	<td width="128" align="center"><img src="images/homepage/macleaya.gif"></img></td><td valign="top">View the article <a href="id/8a2d0a85-841d-4b2c-96d2-a93a733bb208"><i>Aedes (Macleaya) stoneorum</i>, a new species from Queensland (Diptera: Culicidae)</a> by E. N. Marks <i>Proceedings of The Entomological Society of Washington</i> 79:33-37 (1977)</td>

	<td width="128" align="center"><!--<div style="height:128px;width:128px;border:1px solid #eee;">--> <img src="images/homepage/150px-WLDistant" height="128"></img></div><td valign="top">View articles authored by <a href="author/Distant%2CW+L">William L. Distant</a> (image from <a href="http://en.wikipedia.org/wiki/William_Lucas_Distant">Wikipedia</a>).</td>
	</tr>
	
	<tr>
		<td width="128" align="center"></td><td valign="top"><a href="id/e93a1194-b774-4b53-b39d-9586fc46bd72">A new genus of Chrysomelinae from Australia (Coleoptera: Chrysomelidae)</a> from <a href="publication_outlet/Zootaxa"><i>Zootaxa</i></a> with Open Access PDF in Google Docs viewer</td>
		<td width="128" align="center"></td><td valign="top"></td>
	</tr>
	</table>';	
	
	echo '<h3>Downloads</h3>';
	echo '<p>You can get a tab-delimited download of the mapping between publications and identifiers. The list comprises the UUID for the publication and the corresponding identifier:</p>';
	echo '<pre style="border:1px solid rgb(228,228,228);padding:4px;">
d4d66f5e-5328-4dc8-8a44-f55bbf0c3f86	10.1002/iroh.19250140102
bf7562b7-695e-4168-b3f1-9c80d2315832	10.1002/iroh.19650500109
               .                                  .
               .                                  .
               .                                  .
</pre>';
	echo '<ul>';
	echo '<li><a href="downloads/doi.txt">Publications to DOI</a></li>';
	echo '<li><a href="downloads/biostor.txt">Publications to Biostor</a></li>';
	echo '</ul>';

	
	
	echo '<h3>About</h3>';
	echo '<p>This is a project by <a href="http://iphylo.blogspot.com/">Rod Page</a>.</p>';
	
	echo '<table>
	
	<tr><td><div style="height:48px;width:48px;border:1px solid #eee;"></div></td><td>Taxonomic names and literature citations obtained from <a href="http://www.environment.gov.au/biodiversity/abrs/online-resources/fauna/index.html">Australian Faunal Directory (AFD)</a></td></tr>
	
	<tr><td><img src="images/logos/logo.png" width="48"></td><td>Page images from the <a href="http://www.biodiversitylibrary.org">Biodiversity Heritage Library (BHL)</a></td></tr>

	<tr><td><img src="images/logos/biostor-shadow.png" width="48"></td><td>Articles located in BHL using <a href="http://biostor.org">BioStor</a></td></tr>

	<tr><td><div style="height:48px;width:48px;border:1px solid #eee;"></div></td><td>Additional articles located using <a href="http://bioguid.info">bioGUID</a></td></tr>

	<tr><td><img src="images/logos/mendeley.png" width="48"></td><td>Article details editing using Mendeley (see <a href="http://www.mendeley.com/groups/679491/australian-faunal-directory/">Australian Faunal Directory group</a>)</td></tr>
	
	<tr><td><img src="images/logos/couchdb.png" width="48"></td><td>Documents stored using <a href="http://couchdb.apache.org/">CouchDB</a></td></tr>
	
	
	</table>';
	
	echo '<div id="recentcomments" class="dsq-widget">
	<h2 class="dsq-widget-title">Recent Comments</h2>
	<script type="text/javascript" src="http://disqus.com/forums/australianfaunaldirectoryoncouchdb/recent_comments_widget.js?num_items=5&hide_avatars=0&avatar_size=32&excerpt_length=200"></script>
	</div>
	<a href="http://disqus.com/">Powered by Disqus</a>';
	
	echo '</div>';
	
	echo html_body_close(true);
	echo html_html_close();	
}

//--------------------------------------------------------------------------------------------------
function display_publication_identifiers ($publication)
{
	$html = '';
	// External links
	if (isset($publication->identifiers))
	{
		if (isset($publication->identifiers->doi))
		{
			$html .=  '<div>' . '<a href="http://dx.doi.org/' . $publication->identifiers->doi . '" target="_new">doi:' . $publication->identifiers->doi . '</a></div>';				
		}
		if (isset($publication->identifiers->hdl))
		{
			$html .=  '<div>' . '<a href="http://hdl.handle.net/' . $publication->identifiers->hdl . '" target="_new">hdl:' . $publication->identifiers->hdl . '</a></div>';				
		}
		if (isset($publication->identifiers->biostor))
		{
			$html .=  '<div>' . '<a href="http://biostor.org/reference/' . $publication->identifiers->biostor . '" target="_new">BioStor</a></div>';				
		}
		if (isset($publication->pageIdentifiers))
		{
			$html .=  '<div>' . '<a href="http://biodiversitylibrary.org/page/' . $publication->pageIdentifiers[0] . '" target="_new">BHL</a></div>';				
		}
	}
	if (isset($publication->urls))
	{
		foreach ($publication->urls as $url)
		{
			if (preg_match('/http:\/\/ci.nii.ac.jp\/naid\//', $url))
			{
				$html .=  '<div>' . '<a href="' . $url .'" target="_new">' . $url . '</a></div>';				
			}
		}
	}
		
	return $html;
}

//--------------------------------------------------------------------------------------------------
// Display one publication (may be part of a larger list)
function display_one_publication ($publication)
{
	$html = '';
	$html .= '<div style="position:relative;padding:4px;min-height:100px;">';
	
	if (isset($publication->thumbnail))
	{
		$html .= '<div style="position:absolute;left:0px;top:0px;width:70px;height:100px;text-align:center;">';				
		$html .= '<img style="border:1px solid #ddd;" src="' . $publication->thumbnail . '" height="100">';
		$html .= '</div>';
	}
	else
	{
		$html .= '<div style="position:absolute;left:0px;top:0px;width:70px;height:100px;border:1px solid #ddd;text-align:center;font-size:72px;color:rgb(192,192,192);background: -webkit-gradient(linear, left top, left bottom, from(#fff), to(#eee));background: -moz-linear-gradient(-45deg, #fff, #eee);filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr=\'#ffffff\', EndColorStr=\'#eeeeee\');"></div>';
	}		
	
	$html .= '<div style="padding-left:80px;padding-top:10px;padding-right:10px;padding-bottom:10px;top:0px;position:absolute;">';
	$html .= '<div>' . reference_authors_to_text_string($publication->authors) . '</div>';
	$html .= '<div>' . '<a href="id/' . $publication->_id . '">' . $publication->title . '</a>' . '</div>';
	$html .= '<div>' . '<i>' . $publication->publication_outlet . '</i>' . ' ' . $publication->volume . ':' . $publication->pages . ' (' . $publication->year . ')' . '</div>';

	$html .= display_publication_identifiers($publication);
	
	$html .= reference_to_coins($publication);

	$html .= '</div>';
	$html .= '</div>';
	
	return $html;
}


//--------------------------------------------------------------------------------------------------
// Display details for a publication object
function display_publication($publication)
{
	global $couch;
	global $config;
	
	echo html_html_open();
	echo html_head_open();
	echo html_title($publication->title . ' - ' . $config['site_name']);
	echo html_include_css('css/main.css');
	echo html_include_css('css/viewer.css');

	echo html_include_script('js/jquery-1.4.4.min.js');
	echo html_include_script('js/viewer.js');

	
	echo html_head_close();
	echo html_body_open();
	
	
	if (1)
	{

		echo '<div style="position:relative;">';

		
		// Metadata
		echo '<div style="position:absolute;top:0px;left:620px;padding:10px;">';
		
		echo '<div><span><a href="' . $config['web_root']	 . '">' . $config['site_name'] . '</a></span></div>';	
		echo '<p></p>';

		echo '<span style="font-weight:bold;font-size:18px;">' . $publication->title . '</span>';
		echo '<div>' . 'by ' . reference_authors_to_text_string($publication->authors, false, true) . '</div>';
		
		$outlet = $publication->publication_outlet;
		// This ensures we double urlencode "&"
		$outlet = str_replace("&", urlencode("&"), $outlet);
		
		echo '<div>' . '<a href="' .  $config['web_root'] . 'publication_outlet/' . urlencode($outlet) . '">' . '<i>' .  $publication->publication_outlet . '</i>' . '</a>' . ' ' . $publication->volume . ':' . $publication->pages . ' (' . $publication->year . ')' . '</div>';
	
		// External links ---------------------------------------------------------------------------------------
		echo '<div>';
		echo '<ul>';
		
		if ($publication->_id)
		{
			echo '<li><a href="http://lsid.tdwg.org/summary/urn:lsid:biodiversity.org.au:afd.publication:' . $publication->_id . '" target="_new">urn:lsid:biodiversity.org.au:afd.publication:' . $publication->_id . '</li>';
		}
		
		if (isset($publication->identifiers))
		{
			
			if (isset($publication->identifiers->doi))
			{
				echo '<li>' . '<a href="http://dx.doi.org/' . $publication->identifiers->doi . '" target="_new">doi:' . $publication->identifiers->doi . '</a></li>';				
			}
			if (isset($publication->identifiers->hdl))
			{
				echo '<li>' . '<a href="http://hdl.handle.net/' . $publication->identifiers->hdl . '" target="_new">hdl:' . $publication->identifiers->hdl . '</a></li>';				
			}
			if (isset($publication->identifiers->biostor))
			{
				echo '<li>' . '<a href="http://biostor.org/reference/' . $publication->identifiers->biostor . '" target="_new">BioStor</a></li>';				
			}
			if (isset($publication->pageIdentifiers))
			{
				echo '<li>' . '<a href="http://biodiversitylibrary.org/page/' . $publication->pageIdentifiers[0] . '" target="_new">BHL</a></li>';				
			}
		}		
		if (isset($publication->urls))
		{
			foreach ($publication->urls as $url)
			{
				if (preg_match('/http:\/\/ci.nii.ac.jp\/naid\//', $url))
				{
					echo '<li>' . '<a href="' . $url .'" target="_new">' . $url . '</a></li>';				
				}
				if (preg_match('/http:\/\/gallica.bnf.fr\//', $url))
				{
					echo '<li>' . '<a href="' . $url .'" target="_new">' . $url . '</a></li>';				
				}
				if (preg_match('/http:\/\/www.jstor.org\//', $url))
				{
					echo '<li>' . '<a href="' . $url .'" target="_new">' . $url . '</a></li>';				
				}
				
			}
		}
		echo '</div>';
		
		echo reference_to_coins($publication);
		
		echo '<div><a href="http://biostor.org/openurlhook.php?' 
			. reference_to_openurl($publication) 
			. '&amp;webhook=' . urlencode($config['web_server'] . $config['web_root'] . 'webhook.php')
			. '" target="_new">OpenURL Hook</a></div>';

		
		//------------------------------------------------------------------------------------------
		// Names
		$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/publication/_view/taxonNames?key=" . urlencode('"' . $publication->_id . '"') );
		$names = json_decode($resp);
		if (count($names->rows) > 0)
		{
			echo '<h3>Names published</h3>';
			echo '<ul>';
			foreach ($names->rows as $row)
			{
				echo '<li>' . '<a href="id/' . $row->value->_id . '">' . $row->value->taxonName . '</a></li>';
			}
			echo '</ul>';
		}
			
		
		echo '</div>'; // --- end metadata 
		
		// Viewer
		echo 
		'<div style="position:absolute;top:0px;left:0px;width:600px;">';
		
		$has_fulltext = isset($publication->identifiers->biostor) || isset($publication->pdf);
		
		if (!$has_fulltext)
		{
			if (isset($publication->identifiers->doi))
			{
				$url = 'http://dx.doi.org/' . $publication->identifiers->doi;
			
				echo '<div style="width:600px;height:800px;border:1px solid rgb(192,192,192);">';
				echo '<iframe src="' . $url . '" width="600" height="800" style="border: none;">';
				echo '</iframe>';
				echo '</div>';		
			}
			else
			{
				if (count($publication->urls) > 0)
				{
					$display_url = '';
					foreach ($publication->urls as $url)
					{
						if (preg_match('/http:\/\/ci.nii.ac.jp\/naid\//', $url))
						{
							$display_url = 	$url;
							break;
						}
						if (preg_match('/http:\/\/gallica.bnf.fr\//', $url))
						{
							$display_url = 	$url;
							break;
						}
						if (preg_match('/http:\/\/www.jstor.org\//', $url))
						{
							$display_url = 	$url;
							break;
						}
					}
					if ($display_url != '')
					{
						echo '<div style="width:600px;height:800px;border:1px solid rgb(192,192,192);">';
						echo '<iframe src="' . $display_url . '" width="600" height="800" style="border: none;">';
						echo '</iframe>';
						echo '</div>';		
					
					}				
				}
				else
				{
					echo '<div style="width:600px;height:800px;border:1px solid rgb(192,192,192);">
					<div style="position:absolute;top:300px;left:0px;width:600px;color:rgb(192,192,192);text-align:center;font-size:72px;">No content</div>
					</div>';
				}
			
			}
		}
		else
		{
			// PDF
			if (isset($publication->pdf))
			{
				echo '<div>';
				echo '<iframe src="http://docs.google.com/viewer?url=';
				echo str_replace(' ', '%20', $publication->pdf) . '&embedded=true" width="600" height="800" style="border: none;">';
				echo '</iframe>';
				echo '</div>';
			}
			else
			{
				// BioStor
				if (isset($publication->identifiers->biostor))
				{
					echo '<script> pages=[' . join(",", $publication->pageIdentifiers) . ']; </script>';
				
					echo '				
	<div style="position:relative;width:600px;height:800px;">
		<div id="header" >
			<div style="float:right;padding:8px;margin-right:10px;">
				<span id="page_counter">1/?</span>
				<span>&nbsp;</span>
				<span>&nbsp;</span>
				<img id="previous_page" src="images/previous.png" border="0" onclick="previous();" title="Show previous page">
				<span>&nbsp;</span>
				<img id="next_page" src="images/next.png" border="0" onclick="next();" title="Show next page">
				<span>&nbsp;</span>
				<span>&nbsp;</span>
				<span>&nbsp;</span>
				<img src="images/fourpages.png" border="0" onclick="show_all_pages();" title="Show all pages">
				<span>&nbsp;</span>
				<img src="images/onepage.png" border="0" onclick="show_page(-1);"  title="Fit page to viewer">
			</div>
		</div>
	
		<div id="page_container">
			<img id="page_image" />
			<div id="all_pages" ></div>
		</div>
	</div>
	
	<script>
	show_page(0);
	</script>';				
				}
			}
		}		
		echo '</div>
		</div>'
		
		;
	
	}
	else
	{
	
	//----------------------------------------------------------------------------------------------
	// Home
	echo '<span><a href="' . $config['web_root']	 . '">' . $config['site_name'] . '</a></span>';	
	echo html_search_box();
	
	//----------------------------------------------------------------------------------------------
	// Object	
	echo '<div>';
	echo '<h1>' . $publication->title . '</h1>';
	echo '<div>' . 'by ' . reference_authors_to_text_string($publication->authors, false, true) . '</div>';
	echo '<div>' . '<i>' . $publication->publication_outlet . '</i>' . ' ' . $publication->volume . ':' . $publication->pages . ' (' . $publication->year . ')' . '</div>';
	//echo '<div>' . $publication->_id . '</div>';
	
	/*
	if (isset($publication->abstract))
	{
		echo '<div>' . $publication->abstract . '</div>';				
	}
	*/

	// External links
	echo '<ul>';
	if (isset($publication->identifiers))
	{
		
		if (isset($publication->identifiers->doi))
		{
			echo '<li>' . '<a href="http://dx.doi.org/' . $publication->identifiers->doi . '" target="_new">doi:' . $publication->identifiers->doi . '</a></li>';				
		}
		if (isset($publication->identifiers->hdl))
		{
			echo '<li>' . '<a href="http://hdl.handle.net/' . $publication->identifiers->hdl . '" target="_new">hdl:' . $publication->identifiers->hdl . '</a></li>';				
		}
		if (isset($publication->identifiers->biostor))
		{
			echo '<li>' . '<a href="http://biostor.org/reference/' . $publication->identifiers->biostor . '" target="_new">BioStor</a></li>';				
		}
		if (isset($publication->pageIdentifiers))
		{
			echo '<li>' . '<a href="http://biodiversitylibrary.org/page/' . $publication->pageIdentifiers[0] . '" target="_new">BHL</a></li>';				
		}
		

	}
	
	if (isset($publication->urls))
	{
		foreach ($publication->urls as $url)
		{
			if (preg_match('/http:\/\/ci.nii.ac.jp\/naid\//', $url))
			{
				echo '<li>' . '<a href="' . $url .'" target="_new">' . $url . '</a></li>';				
			}
		}
	}
	
	echo '</ul>';	
	
	echo '</div>';
	
	//----------------------------------------------------------------------------------------------
	// Names
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/publication/_view/taxonNames?key=" . urlencode('"' . $publication->_id . '"') );
	$names = json_decode($resp);
	if (count($names->rows) > 0)
	{
		echo '<h2>Names published</h2>';
		echo '<ul>';
		foreach ($names->rows as $row)
		{
			echo '<li>' . '<a href="id/' . $row->value->_id . '">' . $row->value->taxonName . '</a></li>';
		}
		echo '</ul>';
	}
	

	//----------------------------------------------------------------------------------------------
	// Content	
	echo '<h2>Content</h2>';
	
	$has_fulltext = isset($publication->identifiers->biostor) || isset($publication->pdf);
	
	if (!$has_fulltext)
	{
		if (isset($publication->identifiers->doi))
		{
			$url = 'http://dx.doi.org/' . $publication->identifiers->doi;
		
			echo '<div>';
			echo '<iframe src="' . $url . '" width="100%" height="700" style="border: none;">';
			echo '</iframe>';
			echo '</div>';		
		}
	}
	else
	{
		// PDF
		if (isset($publication->pdf))
		{
			echo '<div>';
			echo '<iframe src="http://docs.google.com/viewer?url=';
			echo urlencode($publication->pdf) . '&embedded=true" width="700" height="700" style="border: none;">';
			echo '</iframe>';
			echo '</div>';
		}
		else
		{
			// BioStor
			if (isset($publication->identifiers->biostor))
			{
				echo '<script> pages=[' . join(",", $publication->pageIdentifiers) . ']; </script>';
			
				echo '				
<div style="position:relative;width:700px;height:700px;">
	<div id="header" >
		<div style="float:right;padding:8px;margin-right:10px;">
			<span id="page_counter">1/?</span>
			<span>&nbsp;</span>
			<span>&nbsp;</span>
			<img id="previous_page" src="images/previous.png" border="0" onclick="previous();" title="Show previous page">
			<span>&nbsp;</span>
			<img id="next_page" src="images/next.png" border="0" onclick="next();" title="Show next page">
			<span>&nbsp;</span>
			<span>&nbsp;</span>
			<span>&nbsp;</span>
			<img src="images/fourpages.png" border="0" onclick="show_all_pages();" title="Show all pages">
			<span>&nbsp;</span>
			<img src="images/onepage.png" border="0" onclick="show_page(-1);"  title="Fit page to viewer">
		</div>
	</div>

	<div id="page_container">
		<img id="page_image" />
		<div id="all_pages" ></div>
	</div>
</div>

<script>
show_page(0);
</script>';				
			}	
		}
	}
	
	}
	
	echo html_body_close();
	echo html_html_close();	

}

//--------------------------------------------------------------------------------------------------
// Display a taxon name object
function display_taxon_name($r)
{
	global $couch;
	global $config;
	
	echo html_html_open();
	echo html_head_open();
	echo html_title($r->nameComplete . ' - ' . $config['site_name']);
	echo html_include_css('css/main.css');
	echo html_head_close();
	echo html_body_open();
	
	echo '<div style="padding:10px;">';
		
	//----------------------------------------------------------------------------------------------
	// Home
	echo '<span><a href="' . $config['web_root']	 . '">' . $config['site_name'] . '</a></span>';	
	echo html_search_box();
	
	//----------------------------------------------------------------------------------------------
	// Object	
	echo '<div>';
	echo '<h1>Name:' . $r->nameCompleteHtml . '</h1>';
	echo '</div>';
	
	
	//----------------------------------------------------------------------------------------------
	// Concept(s)
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/taxon/_view/taxaWithName?key=" . urlencode('"' . $r->_id . '"') . '&include_docs=true' );
	$taxa = json_decode($resp);
	if (count($taxa->rows) > 0)
	{
		echo '<h2>Taxa with this name</h2>';
		echo '<ul>';
		foreach ($taxa->rows as $row)
		{
			// Get name of taxon concept
			$resp2 = $couch->send("GET", "/" . $config['couchdb'] . "/" . $row->value);
			$concept = json_decode($resp2);
			
			echo '<li>' . '<a href="id/' . $row->value . '">' . $concept->nameStringHtml . '</a></li>';
		}
		echo '</ul>';
	}
	
	//----------------------------------------------------------------------------------------------
	// Publication

	if (isset($r->publishedInCitation))
	{
		echo '<h2>Publication</h2>';
		
		$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $r->publishedInCitation );
		$publication = json_decode($resp);
		if (isset($publication->error))
		{
			// We don't have this reference
			if (isset($row->value->publishedIn))
			{
				echo '<div style="position:relative;padding:4px;min-height:100px;">';				
				echo '<div style="position:absolute;left:0px;top:0px;width:70px;height:100px;border:1px solid #ddd;text-align:center;font-size:72px;color:rgb(192,192,192);background: -webkit-gradient(linear, left top, left bottom, from(#fff), to(#eee));background: -moz-linear-gradient(-45deg, #fff, #eee);filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr=\'#ffffff\', EndColorStr=\'#eeeeee\');">?</div>';				
				echo '<div style="padding-left:80px;padding-top:10px;padding-right:10px;padding-bottom:10px;">';
				echo '<div>' . $r->publishedIn . '</div>';				
				echo '<div>' . $r->publishedInCitation . '</div>';				
				echo '</div>';
				echo '</div>';				
			}
		}
		else
		{
			echo display_one_publication($publication);
		}
	}
	
	echo '</div>';
	
	echo html_body_close();
	echo html_html_close();	
}




//--------------------------------------------------------------------------------------------------
function display_taxon($r)
{
	global $couch;
	global $config;
	
	echo html_html_open();
	echo html_head_open();
	echo html_title($r->nameString . ' - ' . $config['site_name']);
	echo html_include_css('css/main.css');
	echo html_include_script('js/jquery-1.4.4.min.js');
	
	// Show classification
	echo '<script type="text/javascript">
function children(id)
{
	$.getJSON("children.php?id=" + encodeURIComponent(id),
  		function(data){
   			var n = data.rows.length;
			var html = "";
			for(var i =0;i<n;i++)
			{
				html += \'<li style="list-style: square inside;"><a href="id\/\' + data.rows[i].value[1] + \'">\' + data.rows[i].value[0] + "</a></li>";
			}
			$(\'#children\').html(html);
        }
    );
}

</script>
';

	// Display parent taxon 
	echo '<script type="text/javascript">
function parent(id)
{
	$.getJSON("parent.php?id=" + encodeURIComponent(id),
  		function(data){
   			$(\'#parent\').html(data.nameStringHtml);
			
        }
        
    );
}


</script>
';

	echo html_head_close();
	echo html_body_open();
	
	echo '<div style="padding:10px;">';
	
		
	//----------------------------------------------------------------------------------------------
	// Home
	echo '<span><a href="' . $config['web_root']	 . '">' . $config['site_name'] . '</a></span>';	
	echo html_search_box();
	
	//----------------------------------------------------------------------------------------------
	// Object	
	echo '<div>';
	echo '<h1>Taxon: ' . $r->nameStringHtml . '</h1>';
	
	// Links
	echo '<h2>Links</h2>';
	echo '<ul>';
	echo '<li><a href="http://www.environment.gov.au/biodiversity/abrs/online-resources/fauna/afd/taxa/' . $r->_id . '" target="_new">Australian Faunal Directory</a></li>';
	echo '<li><a href="http://bie.ala.org.au/species/urn:lsid:biodiversity.org.au:afd.taxon:' . $r->_id . '" target="_new">urn:lsid:biodiversity.org.au:afd.taxon:' . $r->_id . '</a></li>';
	echo '</ul>';

	echo '</div>';
	
	
	//----------------------------------------------------------------------------------------------
	// Classification
	echo '<div>';
	echo '<h2>Classification</h2>';
	echo '<div>';
	
	if (isset($r->parent))
	{
		echo '<ul>';
		echo '   <li style="list-style: square inside;"><a href="id/' . $r->parent . '"><span id="parent"></span>' . '</a>';
	}
	echo '     <ul><li style="list-style: square inside;">' . $r->nameStringHtml;
	echo '      <ul id="children">';
	echo '      <img src="images/loading.gif" />';
	
	// do some ajax to build child list...
	
	echo '      </ul>';
	echo '      </li></ul>';
	if (isset($r->parent))
	{	
		echo '   </li>';
		echo '</ul>';
	}
		
	echo '</div>';
	echo '</div>';
	
	//----------------------------------------------------------------------------------------------
	// Name(s) for this taxon...
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/taxon/_view/names?key=" . urlencode('"' . $r->_id . '"') );
	$names = json_decode($resp);
		
	if (0)
	{
		echo '<pre>';
		print_r($names);
		echo '</pre>';
	}
	
	echo '<div>';
	echo '<h2>Names for this taxon</h2>';
	echo '<ul>';
	foreach ($names->rows as $row)
	{
		if (isset($row->value->originalCombination))
		{
			if ($row->value->originalCombination)
			{
				echo '<li>'; // style="background-color:yellow;">';
			}
			else
			{
				echo '<li>';
			}
		}
		else
		{
			echo '<li>';
		}
		echo $row->value->nameCompleteHtml;
		
		if (isset($row->value->authorship))
		{
			echo ' ' . $row->value->authorship;
			if (isset($row->value->year))
			{
				echo ' ' . $row->value->year;
			}
		}
		
		if (isset($row->value->publishedInCitation))
		{
			$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $row->value->publishedInCitation );
			$publication = json_decode($resp);
			if (isset($publication->error))
			{
				// We don't have this reference
				if (isset($row->value->publishedIn))
				{
					
					echo '<div style="position:relative;padding:4px;min-height:100px;">';
					
					echo '<div style="position:absolute;left:0px;top:0px;width:70px;height:100px;border:1px solid #ddd;text-align:center;font-size:72px;color:rgb(192,192,192);background: -webkit-gradient(linear, left top, left bottom, from(#fff), to(#eee));background: -moz-linear-gradient(-45deg, #fff, #eee);filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr=\'#ffffff\', EndColorStr=\'#eeeeee\');">?</div>';
					
					echo '<div style="padding-left:80px;padding-top:10px;padding-right:10px;padding-bottom:10px;">';
					echo '<div>' . $row->value->publishedIn . '</div>';				
					echo '<div>' . $row->value->publishedInCitation . '</div>';				
					echo '</div>';
					echo '</div>';
					
				}
			}
			else
			{
				echo display_one_publication($publication);
			}
		}
		
		echo '</li>';
	}
	echo '</ul>';
	echo '</div>';
	
	//----------------------------------------------------------------------------------------------
	// Treemap for child publications (to do)	
	echo '<div>';
	echo '</div>';
	
	//----------------------------------------------------------------------------------------------
	// Scripts
	echo '<script type="text/javascript">children(\'' . $r->_id . '\');</script>';

	if (isset($r->parent))
	{	
		echo '<script type="text/javascript">parent(\'' . $r->parent . '\');</script>';
	}
	
	echo '</div>';
		
	echo html_body_close();
	echo html_html_close();	
}

//--------------------------------------------------------------------------------------------------
function display_record($id)
{
	global $config;
	global $couch;
	
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $id);
	$r = json_decode($resp);
	
	if (0)
	{
		echo $resp;
	}
	
	if (isset($r->error))
	{
		// bounce
		header('Location: ' . $config['web_root'] . "\n\n");
		exit(0);
	}
	header("Content-type: text/html; charset=utf-8\n\n");
	
	// What kind of object is it?
	
	switch ($r->docType)
	{
		case 'taxonConcept':
			display_taxon($r);
			break;

		case 'taxonName':
			display_taxon_name($r);
			break;
			
		case 'publication':
			display_publication($r);
			break;
			
		default:
			break;
	}


}

//--------------------------------------------------------------------------------------------------
function display_search($query)
{
	global $config;
	global $couch;
	
	
	$query = stripcslashes(trim($query));
	
	if (1)
	{
		// taxon name search
		$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/taxon/_view/nameComplete?key=" . urlencode('"' . $query . '"'));
		$r = json_decode($resp);
		
		//echo $query;
		//echo $resp;
		
		// If one hit bounces us straight there
		if (count($r->rows) == 1)
		{
			header('Location: ' . $config['web_root'] . 'id/' . $r->rows[0]->value . "\n\n");
			exit(0);
		}
		
		// Either nothing or > 1 hit
		header("Content-type: text/html; charset=utf-8\n\n");
		echo html_html_open();
		echo html_head_open();
		echo html_title('Search - ' . $config['site_name']);
		echo html_include_css('css/main.css');
		echo html_head_close();
		echo html_body_open();	
	
		//----------------------------------------------------------------------------------------------
		// Home
		echo '<span><a href="' . $config['web_root']	 . '">' . $config['site_name'] . '</a></span>';	
		echo html_search_box();
			
		//----------------------------------------------------------------------------------------------
		if (count($r->rows) == 0)
		{
			echo '<p>Nothing found for <b>' . $query . '</b></p>';
		}
		else
		{
			echo '<p>' . count($r->rows) . ' records match "' . $query . '":</p>';
			echo '<ol>';
			
			foreach ($r->rows as $row)
			{
				echo '<li><a href="id/' . $row->id . '">' . $row->key . '</a></li>';
			}
			echo '</ol>';
		}
	
		echo html_body_close(false);
		echo html_html_close();	
	}
	else
	{
		// Cloudant full text search
		$q = 'title:' . $query;
		$q = str_replace(' ', ' title:', $q);
		$q = explode(' ', $q);
		$q = join(" AND ", $q);
		
		$url = '/_design/lookup/_search/all?q=' . urlencode($q) . '&include_docs=true';
		
		//echo $url;
		
		$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $url);
		
		//echo $resp;
		$obj = json_decode($resp);
		
		
		//print_r($obj);
		
		//echo '<h1>' . $query . '</h1>';
		
		
		header("Content-type: text/html; charset=utf-8\n\n");
		echo html_html_open();
		echo html_head_open();
		echo html_title('Search - ' . $config['site_name']);
		echo html_include_css('css/main.css');
		echo html_head_close();
		echo html_body_open();	
	
		//----------------------------------------------------------------------------------------------
		// Home
		echo '<span><a href="' . $config['web_root']	 . '">' . $config['site_name'] . '</a></span>';	
		echo html_search_box($query);
		
		
		echo '<ul>';
		foreach ($obj->rows as $row)
		{
			/*
			echo '<div>';
			echo $row->score . ' ';
			echo '<b>' . $row->doc->title . '</b>';
			
			if (isset($row->doc->thumbnail))
			{
				echo '<br/><img src="' . $row->doc->thumbnail . '" height="100"/>';
			}
			echo '</div>';
			*/
			
			// list view
			$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $row->id);
			$publication = json_decode($resp);
			if (isset($publication->error))
			{
				// We don't have this reference
			}
			else
			{
				echo '<li style="list-style-type:none;">' . display_one_publication($publication) . '</li>';
			}
			
		}	
	
		echo '</ul>';
		
		echo html_body_close(false);
		echo html_html_close();	
		
	}
}

//--------------------------------------------------------------------------------------------------
function display_author($author)
{
	global $couch;
	global $config;
	
	echo html_html_open();
	echo html_head_open();
	echo html_title($author . ' - ' . $config['site_name']);
	echo html_include_css('css/main.css');
	echo html_include_script('js/jquery-1.4.4.min.js');
	
	echo html_head_close();
	echo html_body_open();
	
	echo '<div style="padding:10px;">';
	
	
	//----------------------------------------------------------------------------------------------
	// Home
	echo '<span><a href="' . $config['web_root']	 . '">' . $config['site_name'] . '</a></span>';	
	echo html_search_box();
	
	//----------------------------------------------------------------------------------------------
	// Author
	echo '<h1>Author: ' . $author . '</h1>';
	
	$author = preg_replace('/,\s+/', ',', $author);
	
	$key = explode(",", $author);
	
	$resp = $couch->send("GET", "/" . $config['couchdb'] . "/_design/author/_view/by?key=" . urlencode(json_encode($key)) );	
	$authored_by = json_decode($resp);
	
	//print_r($authored_by);
	
	$num_authored = count($authored_by->rows);
	
	if (count($num_authored) == 0)
	{
	}
	else
	{
		echo '<h2>Publications by this author (' . $num_authored . ')</h2>';
		if ($num_authored < 10)
		{
			// list view
			echo '<ul>';
			foreach ($authored_by->rows as $row)
			{
				$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $row->value);
				$publication = json_decode($resp);
				if (isset($publication->error))
				{
					// We don't have this reference
				}
				else
				{
					echo '<li style="list-style-type:none;">' . display_one_publication($publication) . '</li>';
				}
			}
			echo '</ul>';
		}
		else
		{
			// treemap view (should do this with a CouchDB index...
			$pubs = array();
			foreach ($authored_by->rows as $row)
			{
				$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $row->value);
				$publication = json_decode($resp);
				if (isset($publication->error))
				{
					// We don't have this reference
				}
				else
				{
					if (!isset($pubs[$publication->publication_outlet]))
					{
						$pubs[$publication->publication_outlet] = array();
					}
					$pubs[$publication->publication_outlet][]=$row->value;
				}
			}
			
			// Get sizes of categories
			$size = array();
			foreach ($pubs as $p)
			{
				$sizes[] = count($p);
			}
			
			// Get size of rectangle we want to draw this in
			$r = new Rectangle(0,0,400,500);
				
			// Construct quantum treemap
			$qt = new QuantumTreemap($sizes, 1.0, $r);
			$qt->quantumLayout();
			$json =  $qt->export2json();
			$obj = json_decode($json);
			
			// Add category labels and list of object ids to each cell in treemap
			$i = 0;
			foreach ($pubs as $k => $v)
			{
				$obj->rects[$i]->label = $k;
				$obj->rects[$i]->ids = array();
				foreach ($v as $id)
				{
					$obj->rects[$i]->ids[] = $id;
				}				
				$i++;
			}
			
			// Treemap
			echo  "\n";
			echo '<div style="position:relative">';
			draw($obj);
			echo '</div>' . "\n";
			
			
			
			
		}

	}
	
	echo '<div>';
	
	/*
	echo '<script>
	$(".Z3988").each(function() {alert("hi"); });
	
	</script>';
	*/
	
	echo html_body_close(false);
	echo html_html_close();	
	

}

//--------------------------------------------------------------------------------------------------
// All records for a publication outlet (e.g., a journal)
function display_outlet($outlet)
{
	global $couch;
	global $config;
	
	// clean
	$outlet = stripcslashes($outlet);
	
	echo html_html_open();
	echo html_head_open();
	echo html_title($outlet . ' - ' . $config['site_name']);
	echo html_include_css('css/main.css');
	echo html_include_script('js/jquery-1.4.4.min.js');
	
	echo html_head_close();
	echo html_body_open();
	
	echo '<div style="padding:10px;">';
	
	
	//----------------------------------------------------------------------------------------------
	// Home
	echo '<span><a href="' . $config['web_root']	 . '">' . $config['site_name'] . '</a></span>';	
	echo html_search_box();
	
	//----------------------------------------------------------------------------------------------
	// Outlet
	echo '<h1>' . $outlet . '</h1>';
	
	
//http://localhost:5984/afd/_design/publication/_view/outlet_year_volume?startkey=[%22Annals%20And%20Magazine%20of%20Natural%20History%22]&endkey=[%22Annals%20And%20Magazine%20of%20Natural%20History\ufff0%22]	
	
	$startkey = array($outlet);
	$endkey = array($outlet . '\ufff0');
	
	$resp = $couch->send("GET", "/" . $config['couchdb'] 
		. "/_design/publication/_view/outlet_year_volume?startkey=" . urlencode(json_encode($startkey))
		. "&endkey=" . urlencode(json_encode($endkey))
		
		);	
	
	$articles = json_decode($resp);
	
	//print_r($articles);
	
	$num_articles = count($articles->rows);
	
	if (count($articles) == 0)
	{
	}
	else
	{
		echo '<h2>Articles in this publication (' . $num_articles . ')</h2>';
		if ($num_articles < 20)
		{
			// list view
			echo '<ul>';
			foreach ($articles->rows as $row)
			{
				$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $row->value);
				$publication = json_decode($resp);
				if (isset($publication->error))
				{
					// We don't have this reference
				}
				else
				{
					echo '<li style="list-style-type:none;">' . display_one_publication($publication) . '</li>';
				}
			}
			echo '</ul>';
		}
		else
		{
			// treemap view (should do this with a CouchDB index...)
			$years = array();
			foreach ($articles->rows as $row)
			{
				$resp = $couch->send("GET", "/" . $config['couchdb'] . "/" . $row->value);
				$publication = json_decode($resp);
				if (isset($publication->error))
				{
					// We don't have this reference
				}
				else
				{
					$year = 'YYYY';
					if (isset($publication->year))
					{
						$year = $publication->year;
					}
					if (!isset($years[$publication->year]))
					{
						$years[$publication->year] = array();
					}
					$years[$publication->year][]=$row->value;
				}
			}
			
			// Get sizes of categories
			$size = array();
			foreach ($years as $p)
			{
				$sizes[] = count($p);
			}
			
			// Get size of rectangle we want to draw this in
			$r = new Rectangle(0,0,400,500);
				
			// Construct quantum treemap
			$qt = new QuantumTreemap($sizes, 1.0, $r);
			$qt->quantumLayout();
			$json =  $qt->export2json();
			$obj = json_decode($json);
			
			// Add category labels and list of object ids to each cell in treemap
			$i = 0;
			foreach ($years as $k => $v)
			{
				$obj->rects[$i]->label = $k;
				$obj->rects[$i]->ids = array();
				foreach ($v as $id)
				{
					$obj->rects[$i]->ids[] = $id;
				}				
				$i++;
			}
			
			// Treemap
			echo  "\n";
			echo '<div style="position:relative">';
			draw($obj);
			echo '</div>' . "\n";
			
			
			
			
		}

	}
	
	echo '<div>';
	
	echo html_body_close(false);
	echo html_html_close();	
	

}


//--------------------------------------------------------------------------------------------------
function main()
{	
	$query = '';
		
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	// If show a single record
	if (isset($_GET['id']))
	{	
		$id = $_GET['id'];
		display_record($id);
	}

	if (isset($_GET['search']))
	{	
		$query = $_GET['search'];
		display_search($query);
		exit(0);
	}	
	
	if (isset($_GET['author']))
	{	
		$query = $_GET['author'];
		display_author($query);
		exit(0);
	}	
	
	if (isset($_GET['publication_outlet']))
	{	
		$query = $_GET['publication_outlet'];
		display_outlet($query);
		exit(0);
	}		
}


main();
		
?>