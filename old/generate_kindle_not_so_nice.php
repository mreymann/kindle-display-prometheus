#!/usr/bin/php
<?php
$log = true;

# SENSOR: MIJIA
$hum_rrds = [	'/var/lib/munin/shuttle/shuttle-mijia-y1-g.rrd',	# living room
		'/var/lib/munin/shuttle/shuttle-mijia2-y1-g.rrd',	# bedroom
		'/var/lib/munin/zero/zero-mijia4-y1-g.rrd',		# guest room
		'/var/lib/munin/shuttle/shuttle-mijia3-y1-g.rrd'	# balcony
];

$tmp_rrds = [	'/var/lib/munin/shuttle/shuttle-mijia-y2-g.rrd',	# living room
		'/var/lib/munin/shuttle/shuttle-mijia2-y2-g.rrd',	# bedroom
		'/var/lib/munin/zero/zero-mijia4-y2-g.rrd',		# guest room
		'/var/lib/munin/shuttle/shuttle-mijia3-y2-g.rrd'	# balcony
];

$h = get_last( $hum_rrds );
$t = get_last( $tmp_rrds );

# SENSOR: KINDLE BATTERY
$batt = shell_exec("ssh -q root@192.168.178.25 'cat /sys/devices/system/yoshi_battery/yoshi_battery0/battery_capacity' | tr -d -c 0-9") . '%';

# OTHER VALUES
$tmpX = "n/a";
$date = date( "r" );

$svg = file_get_contents( "kindle_template.svg" );

# replace placeholders
$find = [ '|%A|', '|%B|', '|%C|', '|%D|', '|%E|', '|%F|', '|%G|', '|%H|', '|%Q|', '|%R|', '|%S|' ];
$repl = [ $t[0],  $h[0],  $t[1],  $h[1],  $t[2],  $h[2],  $t[3],  $h[3],  $batt,  $tmpX,  $date  ]; 

# replace links to rrdgraphs
$find[] = '|xlink:href="h(\d)\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/h$1.svg"';
$find[] = '|xlink:href="t(\d)\.png"|';
$repl[] = 'xlink:href="file:///home/pigpen/kindle/t$1.svg"';

$out = preg_replace( $find, $repl, $svg );
file_put_contents("kindle.svg", $out);

# ---------- only functions past this point ----------

function get_last( $rrds ) {
	$last = [];
	foreach ( $rrds as $rrd ) {
		$tries = 10;
		$cmd = "rrdtool lastupdate $rrd";
		do {
			$out = shell_exec( $cmd );
			if ( $out === NULL ) mylog( "NULL returned by command '$cmd'" );
			preg_match_all( '|(\d+): (.*)|', $out, $matches );
			$value = $matches[2][0];
			$tries--;
		} while ( ! is_numeric( $value ) && $tries >= 0 && sleep( 5 ) );
		if ( is_numeric( $value ) ) {
			$last[] = str_pad( round( $value ), 2, " ", STR_PAD_LEFT );
		}
		else {
			mylog( "Command: $cmd" );
			mylog( 'Value not numeric! Raw output:' );
			mylog( "'$out'" );
			$last[] = ' -';
		}
	}
	return $last;
}

function mylog( $msg ) {
	global $log;
	if ( ! $log ) return;
	$prefix = date( "r" ) . ": ";
	error_log( $prefix . $msg  . "\n" , 3, '/home/pigpen/kindle/generate_kindle.log' );
	return;
}
