<!DOCTYPE html>
<html>

  <!-- heavily based on MarkerClusterer from http://code.google.com/p/google-maps-utility-library-v3/wiki/Libraries -->

  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>Map browser</title>

    <style type="text/css">
      body {
        margin: 0;
        padding: 0;
        font-family: sans-serif;
      }

      #map {
       /* width: 600px;*/
       width:100%;
        height: 400px;
      }

    </style>
    
    <script src="http://www.google.com/jsapi"></script>    

	<script type="text/javascript" src="js/jquery-1.4.4.min.js"></script> 
	<script type="text/javascript" src="js/markerclusterer.js"></script>   
    
    <script type="text/javascript">
    
	//----------------------------------------------------------------------------------------------
	function objects_within_bounds(bounds)
	{
		// need to qrap http://localhost:5984/afd/_design/geojson/_spatial/points?bbox=-180,0,180,-90
	}
	

	//----------------------------------------------------------------------------------------------	
	function markerClickFn(pic, latlng) 
	{
		return function() 
		{     
     		//alert(pic.id);
     		
			$.getJSON("get.php?id=" + pic.id,
						function(data){
							var html = '';
							
							html += '<div style="position:relative;padding:4px;min-height:100px;">';
							if (data.thumbnail)
							{
								html += '<div style="position:absolute;left:0px;top:0px;width:70px;height:100px;text-align:center;">';
								html += '<img style="border:1px solid #ddd;" src="' + data.thumbnail + '" height="100">';
								html += '</div>';
							}
							else
							{
								html += '<div style="position:absolute;left:0px;top:0px;width:70px;height:100px;border:1px solid #ddd;text-align:center;font-size:72px;color:rgb(192,192,192);background: -webkit-gradient(linear, left top, left bottom, from(#fff), to(#eee));"></div>';
							}		
	
							html += '<div style="padding-left:80px;padding-top:10px;padding-right:10px;padding-bottom:10px;top:0px;position:absolute;">';
							html += '<div>' + '<a href="id/' + pic.id + '">' + data.title + '</a>' + '</div>';
							html += '</div>';
							html += '</div>';
							
							/*
							if (data.thumbnail)
							{
								html += '<img src="' + data.thumbnail + '">';
							}
							html += data.title;
							*/
							
							$("#items").html(html);
							
							
							
							}
					);     		
     		
     		
     	}
   }	

    </script>
    
    <script type="text/javascript">
      google.load('maps', '3', {
        other_params: 'sensor=false'
      });
      google.setOnLoadCallback(initialize);

      function initialize() {
        var center = new google.maps.LatLng(-25, 135);

        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: 4,
          center: center,
          mapTypeId: google.maps.MapTypeId.TERRAIN
        });
        
		// Load all markers into map (gulp)
		$.getJSON("localities.php",
			function(data){
				var markers = [];
				var n = data.rows.length;
				for(var i =0;i<n;i++)
				{
					var latLng = new google.maps.LatLng(data.rows[i].value[1], data.rows[i].value[0]);
					var marker = new google.maps.Marker({ position: latLng });
					
					var fn = markerClickFn(data.rows[i], latLng);
      				google.maps.event.addListener(marker, "click", fn);					
					
					markers.push(marker);
				}
				var markerCluster = new MarkerClusterer(map, markers);	
			}
		);
		
		
		// handle user moving map...
        // rdmp
        // See http://code.google.com/p/gmaps-api-issues/issues/detail?id=1371
        // We only want to do anything if the user has finished moving the map around. If we listen 
        // for the 'bounds_changed' event we will be constantly making Ajax calls. However, by
        // listening for 'idle' we only call our Ajax method when the map has stopped moving.
		google.maps.event.addListener(map, 'idle', 
			function() 
			{
				var bounds = map.getBounds();
				var html = '[' + bounds.getSouthWest().lat() + ',' + bounds.getSouthWest().lng() + '][' + bounds.getNorthEast().lat() + ',' + bounds.getNorthEast().lng() + ']';
				
				$("#bounds").html(html);
	 
	 			// Ajax method to return references with current polygon bounds
				objects_within_bounds(bounds);
	
			}
  		);   
  		
        
      }
    </script>
  </head>
  <body>
    <div id="map"></div>
    
    <div style="padding:10px;">
    	<p id="bounds" style="font-size:10px"></p>
    	<p>Click on marker to zoom in, or to view publication.</p>
    	<h3>Publication</h3>
    	<div id="items">
    		<p/>
    	</div>
    </div>
    
  </body>
</html>