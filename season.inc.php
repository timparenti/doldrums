<?php

function date_season($t=-1) {
  if ($t == -1) { $t = time(); }
  
  $year = gmdate("Y",$t);
  $season = 3;   # start with previous year's December solstice
  for ($i = 0; $i <= 3; $i++) {
    $equiSols[$i] = TDTtoUTC(JDtoGregTime(calcEquiSolTDT($year,$i)));
    if ($t >= $equiSols[$i]) { $season = $i; }  # if season has begun, set value accordingly
  }
  
  return $season;
}

function date_equisol($year,$i) {
  return intval(TDTtoUTC(JDtoGregTime(calcEquiSolTDT($year,$i))));
}

//-----Calculate and Display a single event for a single year (Either a Equiniox or Solstice)
// Meeus Astronmical Algorithms Chapter 27
function calcEquiSolTDT($year,$i) {
	// Initial estimate of date of event
	$Y = ($year-2000)/1000;
	switch($i) {
	  case 0: $JDE0 = 2451623.80984 + 365242.37404*$Y + 0.05169*pow($Y,2) - 0.00411*pow($Y,3) - 0.00057*pow($Y,4); break;
	  case 1: $JDE0 = 2451716.56767 + 365241.62603*$Y + 0.00325*pow($Y,2) + 0.00888*pow($Y,3) - 0.00030*pow($Y,4); break;
	  case 2: $JDE0 = 2451810.21715 + 365242.01767*$Y - 0.11575*pow($Y,2) + 0.00337*pow($Y,3) + 0.00078*pow($Y,4); break;
	  case 3: $JDE0 = 2451900.05952 + 365242.74049*$Y - 0.06223*pow($Y,2) - 0.00823*pow($Y,3) + 0.00032*pow($Y,4); break;
	}
	// Refine the estimate
	$T = ($JDE0 - 2451545.0) / 36525;
	$W = 35999.373*$T - 2.47;
	$dL = 1 + 0.0334*cos(deg2rad($W)) + 0.0007*cos(deg2rad(2*$W));
	$S = periodic24( $T );
	$JDEtdt = $JDE0 + ( (0.00001*$S) / $dL ); 	// This is the answer in Julian Emphemeris Days
	return $JDEtdt;
} // End calcEquiSolJD

//-----Calculate 24 Periodic Terms----------------------------------------------------
// Meeus Astronmical Algorithms Chapter 27
function periodic24($T) {
	$A = array(485,203,199,182,156,136,77,74,70,58,52,50,45,44,29,18,17,16,14,12,12,12,9,8);
	$B = array(324.96,337.23,342.08,27.85,73.14,171.52,222.54,296.72,243.58,119.81,297.17,21.02,
			247.54,325.15,60.93,155.12,288.79,198.04,199.76,95.39,287.11,320.81,227.73,15.45);
	$C = array(1934.136,32964.467,20.186,445267.112,45036.886,22518.443,
			65928.934,3034.906,9037.513,33718.147,150.678,2281.226,
			29929.562,31555.956,4443.417,67555.328,4562.452,62894.029,
			31436.921,14577.848,31931.756,34777.259,1222.114,16859.074);
	$S = 0;
	for( $i=0; $i<24; $i++ ) { $S += $A[$i]*cos(deg2rad( $B[$i] + ($C[$i]*$T) )); }
	return $S;
}

//-----Correct TDT to UTC----------------------------------------------------------------
function TDTtoUTC($tdt) {
// from Meeus Astronmical Algroithms Chapter 10
	// Correction lookup table has entry for every even year between TBLfirst and TBLlast
	$TBLfirst = 1620; $TBLlast = 2002;	// Range of years in lookup table
	$TBL = array(					// Corrections in Seconds
		/*1620*/ 121,112,103, 95, 88,   82, 77, 72, 68, 63,   60, 56, 53, 51, 48,   46, 44, 42, 40, 38,
		/*1660*/  35, 33, 31, 29, 26,   24, 22, 20, 18, 16,   14, 12, 11, 10,  9,    8,  7,  7,  7,  7,
		/*1700*/   7,  7,  8,  8,  9,    9,  9,  9,  9, 10,   10, 10, 10, 10, 10,   10, 10, 11, 11, 11,
		/*1740*/  11, 11, 12, 12, 12,   12, 13, 13, 13, 14,   14, 14, 14, 15, 15,   15, 15, 15, 16, 16,
		/*1780*/  16, 16, 16, 16, 16,   16, 15, 15, 14, 13,  
		/*1800*/ 13.1, 12.5, 12.2, 12.0, 12.0,   12.0, 12.0, 12.0, 12.0, 11.9,   11.6, 11.0, 10.2,  9.2,  8.2,
		/*1830*/  7.1,  6.2,  5.6,  5.4,  5.3,    5.4,  5.6,  5.9,  6.2,  6.5,    6.8,  7.1,  7.3,  7.5,  7.6,
		/*1860*/  7.7,  7.3,  6.2,  5.2,  2.7,    1.4, -1.2, -2.8, -3.8, -4.8,   -5.5, -5.3, -5.6, -5.7, -5.9,
		/*1890*/ -6.0, -6.3, -6.5, -6.2, -4.7,   -2.8, -0.1,  2.6,  5.3,  7.7,   10.4, 13.3, 16.0, 18.2, 20.2,
		/*1920*/ 21.1, 22.4, 23.5, 23.8, 24.3,   24.0, 23.9, 23.9, 23.7, 24.0,   24.3, 25.3, 26.2, 27.3, 28.2,
		/*1950*/ 29.1, 30.0, 30.7, 31.4, 32.2,   33.1, 34.0, 35.0, 36.5, 38.3,   40.2, 42.2, 44.5, 46.5, 48.5,
		/*1980*/ 50.5, 52.5, 53.8, 54.9, 55.8,   56.9, 58.3, 60.0, 61.6, 63.0,   63.8, 64.3); /*2002 last entry*/
		// Values for Delta T for 2000 thru 2002 from NASA
	$deltaT = 0; // deltaT = TDT - UTC (in Seconds)
	$Year = gmdate("Y",$tdt);
	$t = ($Year - 2000) / 100;	// Centuries from the epoch 2000.0
	
	if ( $Year >= $TBLfirst && $Year <= $TBLlast ) { // Find correction in table
		if ($Year%2 != 0) { // Odd year - interpolate
			$deltaT = ( $TBL[($Year-$TBLfirst-1)/2] + $TBL[($Year-$TBLfirst+1)/2] ) / 2;
		} else { // Even year - direct table lookup
			$deltaT = $TBL[($Year-$TBLfirst)/2];
		}
	} else if( $Year < 948) { 
		$deltaT = 2177 + 497*$t + 44.1*pow($t,2);
	} else if( $Year >=948) {
		$deltaT =  102 + 102*$t + 25.3*pow($t,2);
		if ($Year>=2000 && $Year <=2100) { // Special correction to avoid discontinurity in 2000
			$deltaT += 0.37 * ( $Year - 2100 );
		}
	} else { return("Error: TDT to UTC correction not computed"); }
	return ( $tdt - $deltaT ); // JavaScript native time is in milliseonds
} // End fromTDTtoUTC

function JDtoGregTime($JD) {
  $wholeUTCDays = floor($JD+0.5);
  $fracUTCDays = ($JD+0.5) - $wholeUTCDays;
  
  $dateStr = JDtoGregorian($wholeUTCDays);
  $timeStr = gmdate("H:i:s",intval($fracUTCDays * 86400));
  
  $date = strtotime($dateStr." ".$timeStr." UTC");
  
  return $date;
}

?>
