#!/usr/bin/php
<?php
date_default_timezone_set( 'Europe/Berlin' );
require_once 'SVGGraph/autoloader.php';
$sensors = [ 'bedroom', 'guestroom', 'livingroom', 'balcony', 'bathroom' ];
$metrics = [ 'temperature', 'humidity' ];
$hours = 3;
$step = 600; # resolution in seconds
$base_url = 'http://grafana:9090/api/v1';
$end = time();
$start = $end - ( 3600 * $hours );
foreach ( $sensors as $sensor ) {
	#echo "=== $sensor ===\n";
	foreach ( $metrics as $metric ) {
		#echo "--- $metric ---\n";
		$url = "$base_url/query_range?query=sensor_$metric{name=\"$sensor\"}&start=$start&end=$end&step=$step";
		$value_range = getJSONfromURL( $url );
		#var_dump( $value_range ); exit;
		$aValues = $value_range["data"]["result"][0]["values"];
		#var_dump( $aValues ); exit;
		$svgarray = [];
		foreach ( $aValues as $aValue ) {
			#var_dump( $aValue );
			$ts = $aValue[0];
			$value = $aValue[1];
			$date = date("Y-m-d\T H:i:s\Z", $ts);
			#echo "\$ts = $ts, \$date = $date, \$value = $value\n";
			#echo "'$ts' => '$value', ";
			$svgarray[$ts] = $value;
		}
		#var_dump( $svgarray );
		$svg = renderSVG( $svgarray );
		$svgfile = "chart-$sensor-$metric.svg";
		file_put_contents( $svgfile, $svg );
	}
}


function renderSVG( $values ) {
	$settings = [
	  'auto_fit' => false,    # auto fit on page
	  'back_colour' => '#eee',
	  'back_stroke_width' => 0,
	  'back_stroke_colour' => '#eee',
	  'stroke_colour' => '#000',
	  'axis_colour' => '#333',
	  #'axis_overlap' => 2,
	  'grid_colour' => '#666',
	  'label_colour' => '#000',
	  'axis_font' => 'Arial',
	  'axis_font_size' => 10,
	  'fill_under' => true,
	  'pad_right' => 0,
	  'pad_left' => 0,
	  'marker_type' => 'circle',
	  'marker_size' => 3,
	  'marker_colour' => 'blue',
	  'link_base' => '/',
	  'link_target' => '_top',
	  #'minimum_grid_spacing' => 20,
	  #'show_subdivisions' => true,
	  #'show_grid_subdivisions' => true,
	  #'grid_subdivision_colour' => '#ccc',
	  #'best_fit' => 'straight',
	  #'best_fit_colour' => 'red',
	  #'best_fit_dash' => '2,2',
	  'show_axes' => false,
	  'show_axis_text_h' => false,
	  'show_axis_text_v' => false,
	  'show_grid' => false,
	  'show_divisions' => false,
	  #'line_curve' => true,
	  'line_breaks' => true,
	  'datetime_keys' => true,
	  'datetime_key_format' => 'U',
	];

	#$width = 400;
	#$height = 200;
	$width = 218;
	$height = 66;
	$type = 'LineGraph';
	$colours = [ [ 'red', 'yellow' ] ];

	$graph = new Goat1000\SVGGraph\SVGGraph($width, $height, $settings);
	$graph->colours($colours);
	$graph->values($values);
	#$graph->render($type);
	return $graph->fetch($type); # don't output to browser
}

function getJSONfromURL( $url ) {
	#echo "URL: $url\n";
	$result = file_get_contents( $url );
	#echo "$result\n";
	$json = (json_decode($result, true));
	#var_dump( $json ); return;
	if ( $json["status"] == "success") {
		return $json;
	}
	else {
		return false;
	}
}
