var pages = new Array();

var pageNumber = 0;
var mode = 0;

// http://stackoverflow.com/questions/1564298/how-to-show-loading-image-when-a-big-image-is-being-loaded
function load_page()
{	
	var onLoad = function()
    {
        document.getElementById('page_image').src = newImg.src; 
    };
      
    var newImg = new Image();

    newImg.onload = onLoad;

	// Set the source to a really big image
	newImg.src = 'http://biostor.org/bhl_image.php?PageID=' + pages[pageNumber];
}

function previous()
{	
	if (pageNumber > 0)
	{
		pageNumber--;
		$('#page_counter').html(pageNumber+1+'/'+ pages.length);
		if (mode == 0)
		{
			$('#page_image').attr("src", 'http://biostor.org/bhl_image.php?PageID=' + pages[pageNumber] + '&thumbnail');
			
			load_page();
		}
		else
		{
		}
	}
}

function next()
{	
	if (pageNumber < pages.length - 1)
	{
		pageNumber++;
		$('#page_counter').html(pageNumber+1+'/'+ pages.length);
		
		if (mode == 0)
		{
			$('#page_image').attr("src", 'http://biostor.org/bhl_image.php?PageID=' + pages[pageNumber] + '&thumbnail');
			
			load_page();
		}
		else
		{		
		}
	}
}

function show_page(page)
{
	mode = 0;
	$('#all_pages').hide();	
	$('#page_image').show();
	
	// Controls
	$('#page_counter').show();	
	$('#previous_page').show();	
	$('#next_page').show();		
		
	if (page > -1)
	{
		pageNumber = page;
	}
	$('#page_counter').html(pageNumber+1+'/'+ pages.length);
	$('#page_image').attr("src", 'http://biostor.org/bhl_image.php?PageID=' + pages[pageNumber] + '&thumbnail');
	
	load_page();
}

function show_all_pages() 
{
	mode = 1;
	
	$('#page_image').hide();
	
	// Controls
	$('#page_counter').hide();	
	$('#previous_page').hide();	
	$('#next_page').hide();		
	
	var n = pages.length;
	var html = '';
	for (var i=0;i<n;i++)
	{
		 html += '<img style="margin:10px;border:1px solid rgb(192,192,192);" onclick="show_page(' + i + ');" src="http://biostor.org/bhl_image.php?PageID=' + pages[i] + '&thumbnail"/>';		 
	}
	$('#all_pages').html(html);
	$('#all_pages').show();
	
}