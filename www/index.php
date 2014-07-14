<!DOCTYPE html> 
<html> 
	<head> 
	<title>Homepage</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<meta charset="UTF-8">
	<link rel="stylesheet" href="js/jquery.mobile-1.4.2.min.css" />
	<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/jquery.mobile-1.4.2.min.js"></script>
	<script src="js/jqplot/jquery.jqplot.min.js"></script>
	<link rel="stylesheet" href="js/jqplot/jquery.jqplot.min.css" />
	<script src="js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
	<script src="js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
	<script src="js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
	<script src="js/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
	<link rel="stylesheet" href="css/wnw.css" />
	</head> 
<body> 
<div data-role="page"  data-theme="a">
	<div data-role="header">
		<h1>Watch 'n' Water</h1>
	</div><!-- /header -->

	<div data-role="content">
	
<?php
	//Not logged in
	session_start();
	if(!isset($_SESSION['sessionID'])):
?>
	
		<form id="check-user" class="ui-login-form ui-body ui-corner-all" data-ajax="false">
			<fieldset>
				<div data-role="fieldcontain">
					<label for="username">Username:</label>
					<input type="text" value="" name="username" id="username"/>
				</div>
				<div data-role="fieldcontain">
					<label for="password">Password:</label>
					<input type="password" value="" name="password" id="password"/>
				</div>
				<input type="button" name="submit" id="submit" value="Submit">
			</fieldset>
		</form>

	<?php
		//Logged in
		else:
	?>
    
		<div data-role="collapsibleset" data-theme="a" data-content-theme="a"  data-iconpos="right" data-collapsed-icon="arrow-r" data-expanded-icon="arrow-d">
            <div id="collapsible-watering-plan" data-role="collapsible">
                <h3>Watering plan</h3>
                <table data-role="table" id="table-column-toggle-watering-plan" data-mode="columntoggle" class="ui-responsive table-stroke">
					<thead>
    					<tr>
      						<th>Output</th>
      						<th data-priority="1">Starts at</th>
     					 	<th data-priority="2">Duration</th>
      						<th data-priority="3">Days of the week</th>
      						<th data-priority="4">Action</th>
    					</tr>
  					</thead>
					<tbody id="table-body-watering-plan">
					<tr><td>Loading...</td></tr>
					</tbody>
				</table>
            </div>
            <div id="collapsible-outputs" data-role="collapsible">
                <h3>Status of the outputs</h3>
            	<table data-role="table" id="table-column-toggle-outputs" data-mode="columntoggle" class="ui-responsive table-stroke">
					<thead>
    					<tr>
      						<th>Output</th>
      						<th data-priority="1">Status</th>
    					</tr>
  					</thead>
  	    			<tbody id="table-body-outputs">
     				<tr><td>Loading...</td></tr>
       				</tbody>
       			</table>
            </div>
            <div id="collapsible-sensors" data-role="collapsible">
                <h3>Values of the sensors</h3>
                <table data-role="table" id="table-column-toggle-sensors" data-mode="columntoggle" class="ui-responsive table-stroke">
     				<tbody id="table-body-sensors">
     				<tr><td>Loading...</td></tr>
       				</tbody>
       			</table>
       			<div id="TemperatureChart" class="sensors-chart">Loading 'Temperature' chart...</div>
       			<div id="HumidityChart" class="sensors-chart">Loading 'Humidity' chart...</div>
       			<div id="PressureChart" class="sensors-chart">Loading 'Pressure' chart...</div>
            </div>
  		</div>
  		
  	<?php
  		endif;
  	?>
  	
	</div><!-- /content -->
</div><!-- /page -->

<script type="text/javascript">
<?php
	if(!isset($_SESSION['sessionID'])):
?>

$(document).ready(function(){
	$(document).on('click','#submit',function(){
		if($('#username').val().length > 0 && $('#password').val().length > 0){
			$.ajax({
				url: 'php/check.php',
				data: { action:'login', username:$('#username').val(), password:$('#password').val() },
				type: 'post',
				async: 'true',
				dataType: 'json',
				beforeSend: function(){ $.mobile.loading('show'); },
				complete: function(){$.mobile.loading('hide'); },
				success: function(result){
					if(result.status) {
						location.reload();					
					} else {
						alert('Logon unsuccessful!');
					}
				},
				error: function(){ alert('Network error has occurred please try again.'); }
			});
		} else {
			alert('Please fill all necessary fields');
		}
		return false;
	});
});

<?php
	else:
?>


function getWateringPlan(){
	/**
	 * Retrieve watering plan
	 */
 	$.ajax({                                      
 		url: 'php/getWateringPlan.php',                     
        data: "",                       
   	  	dataType: 'json',  
   	  	async: 'true',         
    	success: function(data) {
   	   		if (data.success == true){
   	   			var _html = '';
   	   			var wateringPlan = data.wateringPlan;
   				for(i=0; i < data.itemsNumber; i++){
   	  				_html += "<tr>";
   	   				_html += "<td>" + wateringPlan[i]['description'] + "</td>";
   	   				_html += "<td>" + wateringPlan[i]['startTime'] + "</td>";
   	   				_html += "<td>" + wateringPlan[i]['duration'] + "</td>";
   	   				var _weekdays = "";
   	   				if(wateringPlan[i]['weekdays'].length >= 7){
   	   					var classActive = "ui-weekday-active";
   	   					var classInactive = "ui-weekday-inactive";
	   	   				for(j=0;j<7;j++){
   	   						switch(j){
   	   							case 0:
   	   								if(wateringPlan[i]['weekdays'][j]=='0') _weekdays += "<span class=\""+classInactive+"\">";
   	   								else _weekdays += "<span class=\""+classActive+"\">";
   	   								_weekdays += "Mo</span>";
   	   								break;
   	   							case 1:
   	   								if(wateringPlan[i]['weekdays'][j]=='0') _weekdays += "<span class=\""+classInactive+"\">";
   	   								else _weekdays += "<span class=\""+classActive+"\">";
   	   								_weekdays += "Tu</span>";
   	   								break;
   	   							case 2:
   	   								if(wateringPlan[i]['weekdays'][j]=='0') _weekdays += "<span class=\""+classInactive+"\">";
   	   								else _weekdays += "<span class=\""+classActive+"\">";
   	   								_weekdays += "We</span>";
   	   								break;
   	   							case 3:
   	   								if(wateringPlan[i]['weekdays'][j]=='0') _weekdays += "<span class=\""+classInactive+"\">";
   	   								else _weekdays += "<span class=\""+classActive+"\">";
   	   								_weekdays += "Th</span>";
   	   								break;
   	   							case 4:
   	   								if(wateringPlan[i]['weekdays'][j]=='0') _weekdays += "<span class=\""+classInactive+"\">";
   	   								else _weekdays += "<span class=\""+classActive+"\">";
   	   								_weekdays += "Fr</span>";
   	   								break;
   	   							case 5:
   	   								if(wateringPlan[i]['weekdays'][j]=='0') _weekdays += "<span class=\""+classInactive+"\">";
   	   								else _weekdays += "<span class=\""+classActive+"\">";
   	   								_weekdays += "Sa</span>";
   	   								break;
   	   							case 6:
   	   								if(wateringPlan[i]['weekdays'][j]=='0') _weekdays += "<span class=\""+classInactive+"\">";
   	   								else _weekdays += "<span class=\""+classActive+"\">";
   	   								_weekdays += "Su</span>";
   	   								break;
   	   							default:
   	   								_weekdays += "<span class=\""+classInactive+"\">-</span>";
   	   								break;
   	   						}
   	   					}	
   	   				}
   	   				_html += "<td>" + _weekdays + "</td>";
   	   				_html += "<td>";
   	   				_html +="<a href=\"#\" class=\"ui-icon-as-link\"><img src=\"js/images/icons-png/edit-black.png\" /></a>";
   	   				_html +="<a href=\"#\" class=\"ui-icon-as-link\"><img src=\"js/images/icons-png/delete-black.png\" /></a>";
   	   				_html += "</td>";
   	   				//_html += "<td>" + wateringPlan[i]['isOneShot'] + "</td>";
   	   				//_html += "<td>" + wateringPlan[i]['isForced'] + "</td>";
   	   				_html += "</tr>";
   	   			}
        		$('#table-body-watering-plan').html(_html); 
		    } else {
        		$('#table-column-toggle-watering-plan').html(data.message);
		    }
      	} 
    });
}

function getOutputsStatus(){
	/**
	 * Retrieve outputs' status
	 */
    $.ajax({                                      
		url: 'php/getOutputsStatus.php',                     
   		data: "",                       
   	  	dataType: 'json',
   	  	async: 'true',           
   	   	success: function(data) {
   	   		if (data.success == true){
   	   			var _html = '';
   	   			for(i=0; i < data.outputsNumber; i++){
   	   				_html += "<tr><td>" + i + "</td><td>";
   	   				if (data.output[i] == '0' || data.output[i] == 0)
   	   					_html += "Inactive";
   	   				else
	   					_html += "Active";
   	   				_html += "</td></tr>";
   	   			}
        		$('#table-body-outputs').html(_html); 
		    } else {
        	    $('#table-column-toggle-outputs').html(data.message);
		    }
      	} 
    });
}    	

function getSensorsValues(){
	/**
	 * Retrieve sensors' values
	 */
    $.ajax({                                      
	 	url: 'php/getSensorsValues.php',                     
   		data: "",                       
   	  	dataType: 'json', 
   	  	async: 'true',          
   	   	success: function(data) {
   	   		if (data.success == true){
	        	var timestamp = data.date;              
			    var temperature = data.temperature;              
  			    var humidity = data.humidity;  
        		var pressure = data.pressure;           
        		var _html = "<tr><th>Date and time</th><td>"+timestamp+"</td></tr>";
	        	_html += "<tr><th>Temperature</th><td>"+temperature+" &deg;C</td></tr>";
    	    	_html += "<tr><th>Humidity</th><td>"+humidity+" %</td></tr>";
        		_html += "<tr><th>Pressure</th><td>"+pressure+" Pa</td></tr>";
        		$('#table-body-sensors').html(_html); 
		    } else {
        	    $('#table-column-toggle-sensors').html(data.message);
		    }
      	} 
    });
}

function graphSensorsHistory(){
	/**
	 * Retrieve sensors' history
	 */
    $.ajax({                                      
	 	url: 'php/getSensorsHistory.php',                     
   		data: "",                       
   	  	dataType: 'json', 
   	  	async: 'true',      
   	   	success: function(data) {
   	   		if (data.success == true){
   	   			var plot = $.jqplot('TemperatureChart',[data.temperature], {
   	   				seriesDefaults: {
   	   					showMarker: false, 
   	   					rendererOptions:{smooth: true}
   	   				},
   	   				axesDefaults: {
   	   					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
   	   					labelOptions: {
          					fontSize: '0.75em'
          				}
   	   				},
   	   				axes: { 
   	   					xaxis:{
   	   						renderer: $.jqplot.DateAxisRenderer,
   	   						tickOptions: { formatString: '%H:%M' }
   	   					},
   	   					yaxis: {
   	   						label: 'Temperature (°C)',
   	   						tickOptions: { formatString: '%.2f'}
   	   					},
   	   				}
   	   			}).replot();
   	   			var plot1 = $.jqplot('HumidityChart',[data.humidity], {
   	   				seriesDefaults: {
   	   					showMarker: false, 
   	   					rendererOptions:{smooth: true}
   	   				},
   	   				axesDefaults: {
   	   					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
   	   					labelOptions: {
          					fontSize: '0.75em'
          				}
   	   				},
   	   				axes: { 
   	   					xaxis:{
   	   						renderer: $.jqplot.DateAxisRenderer,
   	   						tickOptions: { formatString: '%H:%M'}
   	   					},
   	   					yaxis: {
   	   						label: 'Humidity (%)',
   	   						tickOptions: { formatString: '%.2f'}
   	   					}
   	   				}
   	   			}).replot();   	   			
   	   			var plot2 = $.jqplot('PressureChart',[data.pressure], {
   	   				seriesDefaults: {
   	   					showMarker: false, 
   	   					rendererOptions:{smooth: true}
   	   				},
   	   				axesDefaults: {
   	   					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
   	   					labelOptions: {
          					fontSize: '0.75em'
          				}
   	   				},
   	   				axes: { 
   	   					xaxis:{
   	   						renderer: $.jqplot.DateAxisRenderer,
   	   						tickOptions: { formatString: '%H:%M'}
   	   					},
   	   					yaxis: {
   	   						label: 'Pressure (Pa)',
   	   						tickOptions: { formatString: '%.2f'}
   	   					}
   	   				}
   	   			}).replot();
		    } else {
        	    $('sensorsChart').html(data.message);
        	    console.log("Error retrieving sensors history");
		    }
      	} 
    });
}

function refreshAll(){	
	getWateringPlan();
	getOutputsStatus();
	getSensorsValues();
	graphSensorsHistory();
}

$(document).ready(function(){	
		refreshAll();
});


window.setInterval(function(){ getWateringPlan(); }, 60000); //every minute
window.setInterval(function(){ getOutputsStatus(); }, 10000); //every 10 seconds
window.setInterval(function(){ getSensorsValues(); }, 10000); //every 10 seconds
window.setInterval(function(){ graphSensorsHistory(); }, 300000); //every 5 minutes


<?php
	endif;
?>

</script>


</body>
</html>