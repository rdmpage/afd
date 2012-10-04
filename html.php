<?php

/**
 * @file html.php
 *
 * Wrap HTML output
 *
 */
require_once (dirname(__FILE__) . '/config.inc.php');

//--------------------------------------------------------------------------------------------------
function html_html_open()
{
	return '<!DOCTYPE html>' . "\n";
}

//--------------------------------------------------------------------------------------------------
function html_html_close()
{
	return '</html>';
}

//--------------------------------------------------------------------------------------------------
function html_head_open()
{
	global $config;
	
	$html = '<head>' . "\n"
		. '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\n";
				
	// Base URL for all links on page
	// This is very useful because we use Apache mod_rewrite extensively, and this ensures
	// image URLs can still be written as relative addresses
	$html .= '<base href="' . $config['web_server'] . $config['web_root'] . '" />' . "\n";
	
	return $html;
}

//--------------------------------------------------------------------------------------------------
function html_head_close()
{
	return '</head>' . "\n";
}

//--------------------------------------------------------------------------------------------------
function html_body_open($params = '')
{
	global $config;
	
	$html = '';
	if ($params != '')
	{
		$html = '<body';
		foreach ($params as $k => $v)
		{
			$html .= ' '  . $k . '=' . '"' . $v . '"';
		}
		
		$html .= '>' . "\n";
	
	}
	else
	{
		$html = '<body>' . "\n";
	}
	
	return $html;	
}

//--------------------------------------------------------------------------------------------------
function html_page_header($has_search = false, $query = '', $category = 'all')
{
	global $config;
	
	$html = '';
	$html .= '<div style="border-bottom:1px dotted rgb(128,128,128);padding-bottom:10px;">';
	$html .= '<a href="' . $config['web_root'] . '"><span style="font-size:24px;">' . $config['site_name'] . '</span></a>';
	
	if ($has_search)
	{
		echo html_search_box($query, $category);
	}

	$html .= '</div>';

	return $html;
}

//--------------------------------------------------------------------------------------------------
function html_body_close($disqus = false)
{
	global $config;
	
	$html = '';
	
	if ($disqus && $config['use_disqus'])
	{
		$html .= '
<div id="disqus_thread"></div>
<script type="text/javascript">
    /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
    var disqus_shortname = \'australianfaunaldirectoryoncouchdb\'; // required: replace example with your forum shortname

    // The following are highly recommended additional parameters. Remove the slashes in front to use.
    // var disqus_identifier = \'unique_dynamic_id_1234\';
    // var disqus_url = \'http://example.com/permalink-to-page.html\';

    /* * * DON\'T EDIT BELOW THIS LINE * * */
    (function() {
        var dsq = document.createElement(\'script\'); dsq.type = \'text/javascript\'; dsq.async = true;
        dsq.src = \'http://\' + disqus_shortname + \'.disqus.com/embed.js\';
        (document.getElementsByTagName(\'head\')[0] || document.getElementsByTagName(\'body\')[0]).appendChild(dsq);
    })();
</script>
<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>';

	}	
	
	$html .= '</body>' . "\n";
	return $html;
}


//--------------------------------------------------------------------------------------------------
function html_title($str)
{
	return '<title>' . $str . '</title>' . "\n";
}

//--------------------------------------------------------------------------------------------------
// Absolutely vital to write this in the form <script></script>, otherwise
// Firefox breaks badly
function html_include_script($script_path)
{
	global $config;
	
	if (preg_match('/^http:/', $script_path))
	{
		// Externally hosted
		return '<script type="text/javascript" src="' . $script_path . '"></script>' . "\n";
	}
	else
	{
		return '<script type="text/javascript" src="' . $config['web_root'] . $script_path . '"></script>' . "\n";
	}
}

//--------------------------------------------------------------------------------------------------
function html_include_css($css_path)
{
	global $config;
	return '<link type="text/css" href="' . $config['web_root'] . $css_path . '" rel="stylesheet" />' . "\n";
}

//--------------------------------------------------------------------------------------------------
function html_include_link($type, $title, $path, $rel)
{
	global $config;
	return '<link type="' . $type . '" title="' . $title . '" href="' . $config['web_root'] . $path . '" rel="' . $rel . '" />' . "\n";
}


//--------------------------------------------------------------------------------------------------
function html_image($image_path, $class = '')
{
	global $config;
	$html = '<img ';
	if ($class != '')
	{
		$html .= ' class="' . $class . '"';
	}
	$html .=  'src="' . $image_path . '" alt="" />';
	return $html;
}

//--------------------------------------------------------------------------------------------------
function html_search_box($query = '')
{
	global $config;
	
	echo '
		<div style="float:right">
		<form method="get" action="index.php">
			<input type="search" name="search" style="font-size:24px;" id="search" placeholder="Search" value="' . $query . '">
			<input type="submit" value="Search" style="font-size:24px;">
		</form>
		</div>';
	return $html;
}



?>