<?
session_start();
//inputs mysql results by url by day; outputs results by url or by day
function filterQuery($query_input, $param) {
	//resets mysql pointer
	mysql_data_seek($query_input, 0);

	while($query_results = mysql_fetch_array($query_input)){
		// asc array (dict)			
		$query_data[] = array(
			"date" => $query_results['date'],
			"page_views" => $query_results['page_views'],
			"url" => $query_results['url']
		);
	}

	//unzip results
	$var_input = array_column_function($query_data, $param);
	$pv_input = array_column_function($query_data, 'page_views');
	
	//empty arrays
	$var_results = array();
	$pv_results = array();

	//reset array pointers
	reset($var_input);
	reset($pv_input);

	//condense results using date or url
	foreach($var_input as $var_value) {
		if (!in_array($var_value, $var_results)) {
			array_push($var_results, $var_value);
			array_push($pv_results, current($pv_input));
			next($pv_input);
		}
		else if (in_array($var_value, $var_results)) {	
			reset($var_results);
			reset($pv_results);
			while ($var_value != current($var_results)) {
				next($var_results);
				next($pv_results);
			}
			$pv_results[key($pv_results)] += current($pv_input);
			next($pv_input);
		}
	}

	//zip up arrays
	if ((count($var_results)) == (count($pv_results))) {
		if ($param == 'date') {
			$var_output = array_map(function ($var_results, $pv_results) {$output = array('date' => $var_results, 'page_views' => $pv_results); return $output;}, $var_results, $pv_results);
		}
		else {
			$var_output = array_map(function ($var_results, $pv_results) {$output = array('url' => $var_results, 'page_views' => $pv_results); return $output;}, $var_results, $pv_results);
		}
	}
	else {
		echo "ERROR: Assymetical results!";
	}

	return $var_output;
}

function array_column_function($array, $column){
    $a2 = array();
    array_map(function ($a1) use ($column, &$a2){
        array_push($a2, $a1[$column]);
    }, $array);
    return $a2;
}

?>

<!DOCTYPE html>

<html>
<head>
	<title>:: JUN GROUP ::</title>

	<link href="css_MAIN.css" rel="stylesheet" type="text/css">


	<!-- jquery core -->
	<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>

	<!--[if lt IE 9 ]>
	<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
	<![endif]--> 

	<!-- jquery easing equations -->
	<script type="text/javascript" src="js/jquery-ui-1.10.2.custom.min.js"></script>

	<script type="text/javascript" src="js/jquery-scrolltofixed-min.js"></script>

	<script type="text/javascript" src="js/functions_MAIN.js"></script>

	<script type="text/javascript" src="//use.typekit.net/vns3hig.js"></script>
	<script type="text/javascript">try{Typekit.load();}catch(e){}</script>

	<script type='text/javascript' src='js/jquery.jqplugin.1.0.2.min.js'></script>


	<style>
		a.button {
		    -webkit-appearance: button;
		    -moz-appearance: button;
		    appearance: button;

		    text-decoration: none;
		    color: initial;
		    padding: 0 0.1% 0 0.1%;
		    font-size : 85%;
		    font-weight : 500;
		    width: 10%;
		    font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
		}
	</style>
</head>

<body bgcolor="#ffffff" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
	<div id="overlay"></div>
	<div id="topLogoExtendedBG"></div>	
	<div id="wrapper">
		<div id="topLogoBar">
			<IMG SRC="images/logo2.png" WIDTH="702" HEIGHT="222" BORDER="0" />
		</div>
		<div id="mainContentContainer">	
			<div id="contentPage1">
			<?php
			require 'config.php';
			function getbgc($trcount){
				$blue="\"background-color: #fbfbfb;\"";
				$green="\"background-color: #eff0ef;\"";
				$odd=$trcount%2;
			    if($odd==1){return $blue;}
				else{return $green;}    
			}
			if (!isset($_SERVER['PHP_AUTH_USER'])){
				$_SERVER['PHP_AUTH_USER'] = 0;
			}
			$partner_name = $_SERVER['PHP_AUTH_USER'];

			if (!isset($_GET['campaign'])){
				$_GET['campaign'] = 0;
			}
			$campaign_name = $_GET['campaign'];

			##################### COLLECTING THE DATA WITH MYSQL ###############################
			# But, for the current month, we're really concerned with showing the payouts by day.

			if($partner_name == "jungroup") {
				$partner_name = $campaign_name;
			}

			$_SESSION["campaign"] = $partner_name;

			$offer_id_query = mysql_query("select offers.id as id from offers 
											join campaigns on campaigns.id = offers.campaign_id
											where campaigns.name = '$partner_name'" );	
			$offer_id_array = array();

			$last_updated_query = mysql_query ('select convert_tz(max(wtps.updated_at),"UTC","US/Eastern") from web_traffic_performance_summary wtps ');
			$last_updated_time = mysql_fetch_array($last_updated_query);
			                      
			// echo "<br><b>Data last updated at ". $last_updated_time[0] . " Eastern Time </b><br>";

			echo "<br><i style='color:#9E1108;'>Our system is currently under maintenance and you may observe a lag in the numbers below.</i></br>";



			############# export to csv #############
			echo '<a href="csv.php?num=1" class="button" style="float:right;"> Export to CSV </a>';
			############# export to csv #############
										
			while ($row = mysql_fetch_array($offer_id_query)) {		// asc arr (dict)
			    $offer_id_array[] = $row["id"];			// num/str array
			}
			$comma_separated_offers = implode(",", $offer_id_array);	// string



			#### START OUTPUTTING OF DATA AROUND TRAFFIC DELIVERY BY DAY BY URL

			$traffic_by_day_query = mysql_query( "select date, url, sum(page_views) as page_views from web_traffic_performance_summary wtps
			                                      join web_traffic_urls on web_traffic_urls.id = wtps.web_traffic_url_id
			                                      where wtps.offer_id in ($comma_separated_offers)
			                                      group by date, url
			                                      order by date, url");



			# Go through our payouts by day for the current month, output the data so the user can see it.
			echo "<BR>";
			#echo "<h1>" . strtoupper($offer_id_query[1]) . "<BR><BR></h1>";
			echo "<h2>TOTAL TRAFFIC:</h2>";
			echo "<table class=\"gridtable\">";
			echo "<tr>";
			echo "<th>DATE</th><th class='th_url'>URL</th><th>PAGE VIEWS</th>";
			echo "</tr>";

			$i = 0;    
			$pages_overall = 0;    
			$tr_day[] = array();                                        
			while ($row = mysql_fetch_array($traffic_by_day_query)) {  
			    echo "<tr style=".getbgc($i).">";
			    echo "<td>" . date("F j, Y", strtotime($row['date'])). " </td><td class='td_url'>" . $row['url'] . " </td><td> " . number_format($row['page_views']) . "</td>";
			    echo "</tr>";
			    
			    $pages_overall += $row['page_views'];		    

			    $i++;

			    $tr_day[] = array($row['date'],$row['url'],$row['page_views']);

			}

			$_SESSION["tr_day"] = $tr_day;
			                                        
			echo "</table>";                                                
			echo "<BR/>";

			#var_dump($traffic_by_day_query);			// type(val) 
			$results_by_url = filterQuery($traffic_by_day_query, 'url');    // instead use this: 
																			// $results_by_url = mysql_query( "SELECT url, SUM(page_views_y) AS tot_page_views
										    								//	  						FROM (SELECT date, url, sum(page_views) AS page_views_y 
										    								//	  								FROM web_traffic_performance_summary wtps
												                            //            						JOIN web_traffic_urls ON web_traffic_urls.id = wtps.web_traffic_url_id
												                            //           					 	WHERE wtps.offer_id IN ($comma_separated_offers)
												                            //            						GROUP BY date, url
												                            //            						ORDER BY date, url) AS T
												                            //       					GROUP BY url");
			#var_dump($results_by_url);

			############# export to csv #############
			// echo '<a href="csv.php?num=2" class="button" style="float:right;"> Export to CSV </a>';
			############# export to csv #############
			echo "<h2>TRAFFIC BY URL:</h2>";
			echo "<table class=\"gridtable\">";
			echo "<tr>";
			echo "<th class='th_url'>URL</th><th class='th_url'>PAGE VIEWS</th>";
			echo "</tr>";


			$i = 0;
			                                    
			foreach ($results_by_url as $result_row) {  
			    echo "<tr style=".getbgc($i).">";
			    echo "<td class='td_url'>" . $result_row['url'] . " </td><td> " . number_format($result_row['page_views']) . "</td>";
			    echo "</tr>";
			    $i++;
			}
			                                        
			echo "</table>";                                                

			echo "<BR/>";

			echo "<h2>TOTAL PAGE VIEWS: <br><br><FONT COLOR=\"#cc0000\">". number_format($pages_overall) . " total page views <BR></h2><BR></FONT>";

			?>

			<div id="output">	</div>

			<script type="text/javascript">
				var $output = $('#output');

				var elementOffset = $('#output').offset().top

				    $(document).ready(function(){
				  $("#wrapper")	.css("height",elementOffset + "px");
				});

				$(function(){
				  $("table.alternate_color tr:even").addClass("d0");
				   $("table.alternate_color tr:odd").addClass("d1");
				});
			</script>                                            

		    </div>
		</div>
	</div><!-- end main content -->	
	
    <div id="footer">
		<h1>	
		<font color="#ffffff">CONTACT US<br></font>
		</h1>
		<br>
		650 5th Avenue<br>
		27th Floor<br>
		New York, NY 10019<br>
		212.692.9500<br>
		<br>
		<a href="mailto:sales@jungroup.com">SALES@JUNGROUP.COM</a>
	</div>
</body>
</html>
