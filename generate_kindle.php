#!/usr/bin/php
<?php
$log = false;

date_default_timezone_set( 'Europe/Berlin' );

# prometheus
$sensors = [ 'bedroom', 'guestroom', 'livingroom', 'balcony', 'bathroom' ];
$metrics = [ 'temperature', 'humidity' ];
$base_url = 'http://grafana:9090/api/v1';

mylog( 'STARTING ...' );

$v = get_last();

# SENSOR: KINDLE BATTERY
$batt = shell_exec("ssh -q kindle 'cat /sys/devices/system/yoshi_battery/yoshi_battery0/battery_capacity' | tr -d -c 0-9") . '%';

# OTHER VALUES
#$tmpX = $t[4] . " C"; # bathroom
$date = date( "r" );

$svg = file_get_contents( "kindle_template.svg" );

# A = livingroom tmp
# B = livingroom hum
# C = bedroom tmp
# D = bedroom hum
# E = guestroom tmp
# F = guestroom hum
# G = balcony tmp
# H = balcony hum

# replace placeholders
$find = [ '|%A|', '|%B|', '|%C|', '|%D|', '|%E|', '|%F|', '|%G|', '|%H|', '|%Q|', '|%R|', '|%S|' ];
#$repl = [ $t[0],  $h[0],  $t[1],  $h[1],  $t[2],  $h[2],  $t[3],  $h[3],  $batt,  $tmpX,  $date  ]; 
$repl = [ $v["livingroom_temperature"], $v["livingroom_humidity"],  $v["bedroom_temperature"],  $v["bedroom_humidity"],  $v["guestroom_temperature"],  $v["guestroom_humidity"],  $v["balcony_temperature"],  $v["balcony_humidity"],  $batt,  $v["bathroom_temperature"] . " °C",  $date  ]; 

/*
chart-balcony-humidity.svg
chart-balcony-temperature.svg
chart-bathroom-humidity.svg
chart-bathroom-temperature.svg
chart-bedroom-humidity.svg
chart-bedroom-temperature.svg
chart-guestroom-humidity.svg
chart-guestroom-temperature.svg
chart-livingroom-humidity.svg
chart-livingroom-temperature.svg
 */

# replace links to SVG charts
$find[] = '|xlink:href="t1\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/chart-livingroom-temperature.svg"';
$find[] = '|xlink:href="h1\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/chart-livingroom-humidity.svg"';

$find[] = '|xlink:href="t2\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/chart-bedroom-temperature.svg"';
$find[] = '|xlink:href="h2\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/chart-bedroom-humidity.svg"';

$find[] = '|xlink:href="t3\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/chart-guestroom-temperature.svg"';
$find[] = '|xlink:href="h3\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/chart-guestroom-humidity.svg"';

$find[] = '|xlink:href="t4\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/chart-balcony-temperature.svg"';
$find[] = '|xlink:href="h4\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/chart-balcony-humidity.svg"';

$out = preg_replace( $find, $repl, $svg );
file_put_contents("kindle.svg", $out);
mylog( 'DONE ...' );
mylog( '--------' );

# ---------- only functions past this point ----------

function get_last() {
	global $sensors, $metrics, $base_url;
	$vals = [];
	foreach ( $sensors as $sensor ) {
		foreach ( $metrics as $metric ) {
			#$url_latest = "$base_url/query?query=sensor_$metric{name=\"$sensor\"}";
			# try not to return null using last_over_time()
			$url_latest = "$base_url/query?query=last_over_time(sensor_$metric{name=\"$sensor\"}[10m])";
			$json = getJSONfromURL( $url_latest );
			$aValues = $json["data"]["result"][0]["value"];
			$ts = $aValues[0];
			$date = gmdate( "Y-m-d\TH:i:s\Z", $ts );
			$value = $aValues[1];
			#echo "$sensor ($metric): date (UTC): $date, value: $value\n";
			$vals["${sensor}_${metric}"] = round( $value );
		}
	}
	return $vals;
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

function mylog( $msg ) {
	global $log;
	if ( ! $log ) return;
	$prefix = date( "r" ) . ": ";
	error_log( $prefix . $msg  . "\n" , 3, '/home/pigpen/kindle/generate_kindle.log' );
	return;
}
