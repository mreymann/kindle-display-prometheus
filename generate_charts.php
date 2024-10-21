#!/usr/bin/php
<?php
error_reporting( -1 );
date_default_timezone_set( 'Europe/Berlin' );
require_once 'SVGGraph/autoloader.php'; # draw SVG charts
require_once __DIR__ . '/php-svg/autoloader.php'; # merge SVG files
use SVG\SVG;
use SVG\Nodes\Structures\SVGGroup;
$sensors = [ 'bedroom', 'guestroom', 'livingroom', 'balcony', 'bathroom' ];
$metrics = [ 'temperature', 'humidity' ];
#$sensors = [ 'balcony' ];
#$metrics = [ 'temperature' ];
$hours = 3;
$step = 300; # resolution in seconds
$trend_latest_count = 6;  # number of latest values to calculate trend from
$trend_threshold = 0.00035 ; # if slope is greater than this, draw an arrow
$base_url = 'http://grafana:9090/api/v1';
$end = time();
$start = $end - ( 3600 * $hours );
foreach ( $sensors as $sensor ) {
	echo "\n=== $sensor ===\n";
	foreach ( $metrics as $metric ) {
		echo "--- $metric ---\n";
		$url = "$base_url/query_range?query=sensor_$metric{name=\"$sensor\"}&start=$start&end=$end&step=$step";
		$json = getJSONfromURL( $url );
		$aRawValues = $json["data"]["result"][0]["values"];
		$values = [];
		foreach ( $aRawValues as $aRawValue ) {
			$ts = $aRawValue[0];
			$value = $aRawValue[1];
			#echo "\$ts: $ts, \$value: $value\n";
			$values[$ts] = $value;
		}

		$trend_values = array_slice( $values, -$trend_latest_count, $trend_latest_count, true );
		$x_trend_values = array_keys( $trend_values );
		$y_trend_values = array_values( $trend_values );

		# Ersten Timestamp als Basis verwenden und Differenz in Sekunden berechnen
		$start_time = $x_trend_values[0];
		$time_in_seconds = array_map( function( $ts ) use ( $start_time ) {
			return ( $ts - $start_time ); }, $x_trend_values );
		var_dump( $time_in_seconds );
		var_dump( $y_trend_values );

		list( $m, $b ) = linearRegression( $time_in_seconds, $y_trend_values );
		$m = round( $m, 4 );

		echo "slope (m): " . $m . "\n";
		#echo "Achsenabschnitt (b): " . $b . "\n";

		# generate SVG files
		$y_values_min = min( array_values( $values ) ); # get min value
		$values = array_map( function( $y_val ) use ( $y_values_min ) {
			return ( $y_val - $y_values_min ); }, $values ); # rebase to min
		$svg = renderSVG( $values );
		if ( abs( $m ) > $trend_threshold ) {
			echo "steep slope detected! drawing an arrow!\n";
			$arrow_file = ( $m < 0 ) ? "arrow-down.svg" : "arrow-up.svg"; 
			$svg = SVG::fromString($svg);
			$doc = $svg->getDocument();
			$arrow = SVG::fromFile($arrow_file);
			$doc2 = $arrow->getDocument();
			$group = new SVGGroup();
			$group->setAttribute('transform', 'translate(84, 8)');
			$group->addChild($doc2);
			$doc->addChild($group);
		}
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
	  'grid_division_v' => 1,
	  'label_colour' => '#000',
	  'axis_font' => 'Arial',
	  'axis_font_size' => 10,
	  'fill_under' => true,
	  'pad_right' => 0,
	  'pad_left' => 0,
	  'marker_type' => 'circle',
	  # marker_size 0 disables them
	  'marker_size' => 0,
	  'marker_colour' => 'blue',
	  'link_base' => '/',
	  'link_target' => '_top',
	  'minimum_grid_spacing' => 1,
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
	  'line_curve' => 0.75,
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

function linearRegression($x, $y) {
    $n = count($x);

    if ($n != count($y)) {
        throw new Exception("Die Arrays müssen gleich lang sein.");
    }

    // Mittelwerte der Arrays berechnen
    $mean_x = array_sum($x) / $n;
    $mean_y = array_sum($y) / $n;

    // Variablen zur Berechnung der Summe der Abweichungen
    $numerator = 0;  // Zähler für die Steigung (m)
    $denominator = 0; // Nenner für die Steigung (m)

    for ($i = 0; $i < $n; $i++) {
        $numerator += ($x[$i] - $mean_x) * ($y[$i] - $mean_y);
        $denominator += ($x[$i] - $mean_x) ** 2;
    }

    // Berechnung der Steigung (m) und des Achsenabschnitts (b)
    $m = $numerator / $denominator;
    $b = $mean_y - ($m * $mean_x);

    return [$m, $b];
}
