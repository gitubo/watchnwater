<!DOCTYPE html> 
<html> 
	<head> 
	<title>Homepage</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<meta charset="UTF-8">
	<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/jquery.mobile-1.4.2.min.js"></script>
	<script src="js/jqplot/jquery.jqplot.min.js"></script>
	<script src="js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
	<script src="js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
	<script src="js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
	<script src="js/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
	<link rel="stylesheet" href="js/jquery.mobile-1.4.2.min.css" />
	<link rel="stylesheet" href="js/jqplot/jquery.jqplot.min.css" />
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
    	<div data-role="popup" id="editWateringPlanItem" class="ui-corner-all">
    		<form>
    			<div style="padding:10px 20px;">
    				<h3 class="my-h3" id="editWateringPlanItemOutput">Output</h3>
    				<table style="width: 180px; margin-bottom: 5px">
    					<tr><td colspan="2" align="center" class="my-label">Starts at</td><td style="width:100%"></td><td align="center" class="my-label">Min.</td></tr>
    					<tr><td align="center">
    						<a class="my-centered-anchor" id="editWateringPlanItemAddHour" href="" data-role="button" data-iconpos="notext" data-icon="plus" data-mini="true" data-inline="true" data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span" data-theme="c">+</a>
    					</td><td align="center">
    						<a class="my-centered-anchor" id="editWateringPlanItemAddMinute" href="" data-role="button" data-iconpos="notext" data-icon="plus" data-mini="true" data-inline="true" data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span" data-theme="c">+</a>
    					</td><td align="center"></td><td align="center">
    						<a class="my-centered-anchor" id="editWateringPlanItemAddDuration" href="" data-role="button" data-iconpos="notext" data-icon="plus" data-mini="true" data-inline="true" data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span" data-theme="c">+</a>    					
    					</td></tr>
    					<tr><td id="editWateringPlanItemHour" data-wpi-value="" align="center">--</td><td id="editWateringPlanItemMinute" align="center">--</td><td align="center" class="my-label">for</td><td id="editWateringPlanItemDuration" align="center">--</td></tr>
    					<tr><td align="center">
    						<a class="my-centered-anchor" id="editWateringPlanItemRemoveHour" href="" data-role="button" data-iconpos="notext" data-icon="minus" data-mini="true" data-inline="true" data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span" data-theme="c">-</a>
    					</td><td align="center">
    						<a class="my-centered-anchor" id="editWateringPlanItemRemoveMinute" href="" data-role="button" data-iconpos="notext" data-icon="minus" data-mini="true" data-inline="true" data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span" data-theme="c">-</a>
    					</td><td align="center"></td><td align="center">
    						<a class="my-centered-anchor" id="editWateringPlanItemRemoveDuration" href="" data-role="button" data-iconpos="notext" data-icon="minus" data-mini="true" data-inline="true" data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span" data-theme="c">-</a>    					
    					</td></tr>
    				</table>
   					<label for="days" class="my-label">On</label>
   					<div data-role="fieldcontain"><fieldset data-role="controlgroup" data-type="horizontal" data-mini="true">
   						<input type="checkbox" name="editWateringPlanItemDay" id="editWateringPlanItemDayMo" />
   						<label for="editWateringPlanItemDayMo">M</label>
   						<input type="checkbox" name="editWateringPlanItemDay" id="editWateringPlanItemDayTu" />
   						<label for="editWateringPlanItemDayTu">T</label>
   						<input type="checkbox" name="editWateringPlanItemDay" id="editWateringPlanItemDayWe" />
   						<label for="editWateringPlanItemDayWe">W</label>
   						<input type="checkbox" name="editWateringPlanItemDay" id="editWateringPlanItemDayTh" />
   						<label for="editWateringPlanItemDayTh">T</label>
   						<input type="checkbox" name="editWateringPlanItemDay" id="editWateringPlanItemDayFr" />
   						<label for="editWateringPlanItemDayFr">F</label>
   						<input type="checkbox" name="editWateringPlanItemDay" id="editWateringPlanItemDaySa" />
   						<label for="editWateringPlanItemDaySa">S</label>
   						<input type="checkbox" name="editWateringPlanItemDay" id="editWateringPlanItemDaySu" />
   						<label for="editWateringPlanItemDaySu">S</label>
   					</fieldset></div>
   					<button type="submit" class="ui-btn ui-mini ui-icon-check ui-corner-all">Save</button>
   					<button type="submit" class="ui-btn ui-mini ui-icon-check ui-corner-all">Delete</button>
    			</div>
    		</form>
    	</div>
    	<div data-role="popup" id="ChartPopupContent" class="ui-content">
       		<a href="#" data-rel="back" data-role="button" data-theme="a" data-icon="delete" data-iconpos="notext" class="ui-btn-right">Close</a>
       		<div id="ChartPopup" class="my-chart"></div>
       	</div>
    	<div>
    		<a id="refreshGraphs" href="" data-role="button" data-icon="edit" data-iconpos="left" data-mini="true" data-inline="true" 
                   data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span" data-theme="c">Refresh</a>
		</div>
		<div data-role="collapsibleset" data-theme="a" data-content-theme="a"  data-iconpos="right" data-collapsed-icon="arrow-r" data-expanded-icon="arrow-d">
            <div id="collapsible-watering-plan" data-role="collapsible">
                <h3>Watering plan</h3>
                <table data-role="table" id="table-column-toggle-watering-plan" data-mode="columntoggle" class="ui-responsive table-stroke">
					<tbody id="table-body-watering-plan">
					<tr><td>Loading...</td></tr>
					</tbody>
				</table>
            </div>
            <div id="collapsible-outputs" data-role="collapsible">
                <h3>Status of the outputs</h3>
            	<table data-role="table" id="table-column-toggle-outputs" data-mode="columntoggle" class="ui-responsive table-stroke">
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

var theChart = null;
var chartTitle = [];
var chartData = [];

function getWateringPlan(){
	/**
	 * Retrieve watering plan
	 */
 	$.ajax({                                      
 		url: 'php/getWateringPlan.php',                     
        data: "",                       
   	  	dataType: 'json',  
   	  	async: 'true', 
		beforeSend: function(){ $.mobile.loading('show'); },
		complete: function(){$.mobile.loading('hide'); },        
    	success: function(data) {
   	   		if (data.success == true){
   	   			var _html = '';
   	   			var wateringPlan = data.wateringPlan;
   	   			var _currentOutput = -1;
   				for(i=0; i < data.itemsNumber; i++){
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
   	   				if (_currentOutput != wateringPlan[i]['output']) {
   	   					_html += "<tr>";
   	   					_html += "<td class=\"watering-plan-output-name\">" + wateringPlan[i]['description'] + "</td>";
//   	   				_html += "<td rowspan=\"2\"><a href=\"#\" class=\"ui-icon-as-link\"><img src=\"js/images/icons-png/edit-black.png\" /></a></td>";
   	   					_html += "</tr>";
   	   					_currentOutput = wateringPlan[i]['output'];
   	   				}
   	   				_html += "<tr class=\"watering-plan-item\" ";
   	   				_html += "data-wpi-id=\"" + wateringPlan[i]['id'] + "\" ";
   	   				_html += "data-wpi-description=\"" + wateringPlan[i]['description'] + "\" ";
    	   			_html += "data-wpi-start-time=\"" + wateringPlan[i]['startTime'] + "\" ";
   	   				_html += "data-wpi-duration=\"" + wateringPlan[i]['duration'] + "\" ";
   	   				_html += "data-wpi-weekdays=\"" + wateringPlan[i]['weekdays'] + "\" ";
  	   				_html += ">";
   	   				_html += "<td>" + wateringPlan[i]['startTime'] + " for " + wateringPlan[i]['duration'] + " min. on " + _weekdays + "</td>";
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
    $.ajax({                                      
		url: 'php/getOutputsStatus.php',                     
   		data: "",                       
   	  	dataType: 'json',
   	  	async: 'true',    
		beforeSend: function(){ },
		complete: function(){ 
			$(".open-output-popup").click(function(){
				var index = $(this).attr('data-output')
					if(chartData != null && chartData[index] != null) {
						if(theChart) theChart.destroy();
						$('ChartPopup').empty();
						theChart = $.jqplot('ChartPopup',[chartData[index]], {
	   	   					title: chartTitle[index],
   		   					seriesDefaults: { showMarker: false, rendererOptions:{smooth: true}, breakOnNull: true },
   	   						axesDefaults: {
   	   							labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	   	   						labelOptions: { fontSize: '0.75em' }
   		   					},
   	   						axes: { 
   	   							xaxis:{
   	   								renderer: $.jqplot.DateAxisRenderer,
   	   								tickOptions: { formatString: '%H:%M' },
   	   								max: (chartData[index])[(chartData[index]).length-1][0],
	   	   							min: (chartData[index])[0][0]
		   	   					},	
   			   					yaxis: { tickOptions: { formatString: '%.2f'} },
   	   						}
   	   					});
        				$( "#ChartPopupContent" ).popup();
						$( "#ChartPopupContent" ).popup( "open" );
					} else console.log("chartData obj is null (index = " + index + ")");
				return false;
			});
		},       
   	   	success: function(data) {
   	   		if (data.success == true){
   	   			var _html = '';
   	   			for(i=0; i < data.outputsNumber; i++){
   	   				_html += "<tr><td>" + i + "</td><td>";
   	   				if (data.output[i] == '0' || data.output[i] == 0)
   	   					_html += "OFF";
   	   				else
	   					_html += "ON";
   	   				_html += "</td><td><a href=\"#\" data-output=\"output" + i + "\" class=\"open-output-popup\">24h chart</a></td></tr>";
   	   			}
        		$('#table-body-outputs').html(_html); 
 		    } else {
        	    $('#table-column-toggle-outputs').html(data.message);
		    }
      	} 
    });
}    	

function getOutputsHistory(){
	$.ajax({                                      
		url: 'php/getOutputsHistory.php',                     
   		data: "",                       
   	  	dataType: 'json',
   	  	async: 'true',    
		beforeSend: function(){ 
			chartData['output0'] = null;
   	   		chartData['output1'] = null;
   	   		chartData['output2'] = null;
   	   		chartData['output3'] = null; 
   	   	},
		complete: function(){},       
   	   	success: function(data) {
   	   		if (data.success == true){
   	   			chartData['output0'] = data.output0;
   	   			chartData['output1'] = data.output1;
   	   			chartData['output2'] = data.output2;
   	   			chartData['output3'] = data.output3;
 		    } else {
        	    console.log("No data retrieved from getOutputHistory");
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
		beforeSend: function(){ },
		complete: function(){ 
			$(".open-output-popup").click(function(){
				var index = $(this).attr('data-output')
					if(chartData != null && chartData[index] != null) {
						if(theChart) theChart.destroy();
						$('ChartPopup').empty();
						theChart = $.jqplot('ChartPopup',[chartData[index]], {
	   	   					title: chartTitle[index],
   		   					seriesDefaults: { showMarker: false, rendererOptions:{smooth: true}, breakOnNull: true },
   	   						axesDefaults: {
   	   							labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	   	   						labelOptions: { fontSize: '0.75em' }
   		   					},
   	   						axes: { 
   	   							xaxis:{
   	   								renderer: $.jqplot.DateAxisRenderer,
   	   								tickOptions: { formatString: '%H:%M' },
   	   								max: (chartData[index])[(chartData[index]).length-1][0],
	   	   							min: (chartData[index])[0][0]
		   	   					},	
   			   					yaxis: { tickOptions: { formatString: '%.2f'} },
   	   						}
   	   					});
        				$( "#ChartPopupContent" ).popup();
						$( "#ChartPopupContent" ).popup( "open" );
					} else console.log("chartData obj is null (index = " + index + ")");
				return false;
			});		
		},    
   	   	success: function(data) {
   	   		if (data.success == true){
   	   			var _html = "";
   	   			_html += "<tr><th>Date and time</th><td>"+data.date+"</td><td>&nbsp;</td></tr>";
	  	    	_html += "<tr><th>Temperature</th><td>"+data.temperature+" &deg;C</td><td><a href=\"#\" data-output=\"temperature\" class=\"open-output-popup\">24h chart</a></td></tr>";
	  	    	_html += "<tr><th>Humidity</th><td>"+data.humidity+" %</td><td><a href=\"#\" data-output=\"humidity\" class=\"open-output-popup\">24h chart</a></td></tr>";
        		_html += "<tr><th>Pressure</th><td>"+data.pressure+" Pa</td><td><a href=\"#\" data-output=\"pressure\" class=\"open-output-popup\">24h chart</a></td></tr>";
        		_html += "<tr><th>Soil moisture</th><td>"+data.soilMoisture+"</td><td><a href=\"#\" data-output=\"soilMoisture\" class=\"open-output-popup\">24h chart</a></td></tr>";
        		_html += "<tr><th>Luminosity</th><td>"+data.luminosity+" lux</td><td><a href=\"#\" data-output=\"luminosity\" class=\"open-output-popup\">24h chart</a></td></tr>";
        		$('#table-body-sensors').html(_html); 
		    } else {
        	    $('#table-column-toggle-sensors').html(data.message);
		    }
      	} 
    });
}

function getSensorsHistory(){
    $.ajax({                                      
	 	url: 'php/getSensorsHistoryMinutes.php',                
   		data: "",                       
   	  	dataType: 'json', 
   	  	async: 'true',  
   	   	success: function(data) {
	   	   	if (data.success == true){
   				chartData['temperature'] = data.temperature;
   				chartData['humidity'] = data.humidity;
   				chartData['pressure'] = data.pressure;
   				chartData['soilMoisture'] = data.soilMoisture;
   				chartData['luminosity'] = data.luminosity;
   		   	} else {
        	    console.log("Error retrieving sensors history: " + data.message);
		    }
      	} 
    });

}

function prepareChartTitles(){
	chartTitle['temperature'] = 'Temperature (C)';
	chartTitle['humidity'] = 'Humidity (%)';
	chartTitle['pressure'] = 'Pressure (Pa)';
	chartTitle['soilMoisture'] = 'Soil Moisture';
	chartTitle['luminosity'] = 'Luminosity (Lux)';
	chartTitle['output0'] = 'Output 0';
	chartTitle['output1'] = 'Output 1';
	chartTitle['output2'] = 'Output 2';
	chartTitle['output3'] = 'Output 3';
}

function refreshAll(){
	$.mobile.loading('show', {
		text: 'loading',
		textVisible: true,
		theme: 'c',
		html: ""
	});
	getWateringPlan();
	getOutputsStatus();
	getOutputsHistory();
	getSensorsValues();
	getSensorsHistory();
	$.mobile.loading('hide');
}

$(document).ready(function(){	
	prepareChartTitles();
	refreshAll();

	$('#refreshGraphs').click(function(){	
		refreshAll();
		return false; 
	});
	
	$('#table-column-toggle-watering-plan').on('click', 'tr.watering-plan-item', function(e){
		e.preventDefault();
		var id = $(this).attr('data-wpi-id');
		$('#editWateringPlanItemOutput').html($(this).attr('data-wpi-description'));
		var item = parseInt(($(this).attr('data-wpi-start-time')).substr(0,2));
		if(item<9) item = "0" + item.toString();
		$('#editWateringPlanItemHour').attr('data-wpi-value', item);
		$('#editWateringPlanItemHour').html(item);
		item = parseInt(($(this).attr('data-wpi-start-time')).substr(3,2));
		if(item<9) item = "0" + item.toString();
		$('#editWateringPlanItemMinute').attr('data-wpi-value', item);
		$('#editWateringPlanItemMinute').html(item);
		item = parseInt($(this).attr('data-wpi-duration'));
		if(item<9) item = "0" + item.toString();
		$('#editWateringPlanItemDuration').attr('data-wpi-value', item);
		$('#editWateringPlanItemDuration').html(item);
		$('#editWateringPlanItemDayMo').prop('checked',false);
		$('#editWateringPlanItemDayTu').prop('checked',false);
		$('#editWateringPlanItemDayWe').prop('checked',false);
		$('#editWateringPlanItemDayTh').prop('checked',false);
		$('#editWateringPlanItemDayFr').prop('checked',false);
		$('#editWateringPlanItemDaySa').prop('checked',false);
		$('#editWateringPlanItemDaySu').prop('checked',false);
		if ($(this).attr('data-wpi-weekdays').charAt(0) != '0')
			$('#editWateringPlanItemDayMo').prop('checked',true);
		if ($(this).attr('data-wpi-weekdays').charAt(1) != '0')
			$('#editWateringPlanItemDayTu').prop('checked',true);
		if ($(this).attr('data-wpi-weekdays').charAt(2) != '0')
			$('#editWateringPlanItemDayWe').prop('checked',true);
		if ($(this).attr('data-wpi-weekdays').charAt(3) != '0')
			$('#editWateringPlanItemDayTh').prop('checked',true);
		if ($(this).attr('data-wpi-weekdays').charAt(4) != '0')
			$('#editWateringPlanItemDayFr').prop('checked',true);
		if ($(this).attr('data-wpi-weekdays').charAt(5) != '0')
			$('#editWateringPlanItemDaySa').prop('checked',true);
		if ($(this).attr('data-wpi-weekdays').charAt(6) != '0')
			$('#editWateringPlanItemDaySu').prop('checked',true);
		$('#editWateringPlanItemDayMo').checkboxradio('refresh');
		$('#editWateringPlanItemDayTu').checkboxradio('refresh');
		$('#editWateringPlanItemDayWe').checkboxradio('refresh');
		$('#editWateringPlanItemDayTh').checkboxradio('refresh');
		$('#editWateringPlanItemDayFr').checkboxradio('refresh');
		$('#editWateringPlanItemDaySa').checkboxradio('refresh');
		$('#editWateringPlanItemDaySu').checkboxradio('refresh');
		$('#editWateringPlanItem').popup('open');
	});
	$('#editWateringPlanItemAddHour').click(function(){
		var value = parseInt($('#editWateringPlanItemHour').attr('data-wpi-value'));
		if(!value) value=0;
		value = value + 1;
		if(value>23) value = 0;
		$('#editWateringPlanItemHour').attr('data-wpi-value', value);
		var strValue = "";
		if(value<10) strValue = "0" + value.toString()
		else strValue = value.toString();	
		$('#editWateringPlanItemHour').html(strValue);
	});
	$('#editWateringPlanItemAddMinute').click(function(){
		var value = parseInt($('#editWateringPlanItemMinute').attr('data-wpi-value'));
		if(!value) value=0;
		value = value + 1;
		if(value>59) value = 0;
		$('#editWateringPlanItemMinute').attr('data-wpi-value', value);
		var strValue = "";
		if(value<10) strValue = "0" + value.toString()
		else strValue = value.toString();	
		$('#editWateringPlanItemMinute').html(strValue);
	});
	$('#editWateringPlanItemAddDuration').click(function(){
		var value = parseInt($('#editWateringPlanItemDuration').attr('data-wpi-value'));
		if(!value) value=0;
		value = value + 1;
		if(value>99) value = 0;
		$('#editWateringPlanItemDuration').attr('data-wpi-value', value);
		var strValue = "";
		if(value<10) strValue = "0" + value.toString()
		else strValue = value.toString();	
		$('#editWateringPlanItemDuration').html(strValue);
	});
	$('#editWateringPlanItemRemoveHour').click(function(){
		var value = parseInt($('#editWateringPlanItemHour').attr('data-wpi-value'));
		if(!value) value=0;
		value = value - 1;
		if(value<0) value = 23;
		$('#editWateringPlanItemHour').attr('data-wpi-value', value);
		var strValue = "";
		if(value<10) strValue = "0" + value.toString()
		else strValue = value.toString();	
		$('#editWateringPlanItemHour').html(strValue);
	});
	$('#editWateringPlanItemRemoveMinute').click(function(){
		var value = parseInt($('#editWateringPlanItemMinute').attr('data-wpi-value'));
		if(!value) value=0;
		value = value - 1;
		if(value<0) value = 59;
		$('#editWateringPlanItemMinute').attr('data-wpi-value', value);
		var strValue = "";
		if(value<10) strValue = "0" + value.toString()
		else strValue = value.toString();	
		$('#editWateringPlanItemMinute').html(strValue);
	});
	$('#editWateringPlanItemRemoveDuration').click(function(){
		var value = parseInt($('#editWateringPlanItemDuration').attr('data-wpi-value'));
		if(!value) value=0;
		value = value - 1;
		if(value<0) value = 99;
		$('#editWateringPlanItemDuration').attr('data-wpi-value', value);
		var strValue = "";
		if(value<10) strValue = "0" + value.toString()
		else strValue = value.toString();	
		$('#editWateringPlanItemDuration').html(strValue);
	});
});

<?php
	endif;
?>

</script>


</body>
</html>