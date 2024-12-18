<html>
<?php

# Set location and timezone.
#$lat = -33.87; $lon = 151.21; $tz = "Australia/Sydney";
#$lat = 51.51; $lon = -0.13; $tz = "Europe/London";
#$lat = -22.91; $lon = -43.17; $tz = "America/Sao_Paulo";
#$lat = 69.65; $lon = 18.96; $tz = "Europe/Oslo";
#$lat = 55.76; $lon = 37.62; $tz = "Europe/Moscow";
#$lat = 42.00; $lon = -80.18; $tz = "America/New_York";
$lat = 40.44; $lon = -79.95; $tz = "America/New_York";


# Compute the facts.
require_once('doldrums.php');
computeFacts($lat, $lon, $tz);


$start_date = strtotime(min(array_keys($facts)));
$end_date = strtotime(max(array_keys($facts)));

echo "\n<head>";
  echo "\n<title>Positive thoughts for the Doldrums of ".htmlentities($print_year)."</title>";
?>
<style>
  #calendar .month-odd { background: #fff; }
  #calendar .month-even { background: #eee; }
  #calendar tr { break-inside: avoid; }
</style>
<?php
echo "\n</head>";
echo "\n<body>";
echo "\n";

# Print the facts.
echo "<h2>POSITIVE THOUGHTS FOR THE DOLDRUMS OF ".htmlentities($print_year)."</h2>";
echo "<h3>customized to (lat ".$lat.", lon ".$lon.")</h3>";

echo "\n<table border=1 id=\"calendar\">";
echo "\n<tr>";
echo "\n<th>Monday</th>";
echo "\n<th>Tuesday</th>";
echo "\n<th>Wednesday</th>";
echo "\n<th>Thursday</th>";
echo "\n<th>Friday</th>";
echo "\n<th>Saturday</th>";
echo "\n<th>Sunday</th>";
echo "\n</tr>";
$d = strtotime("last Monday", $start_date);
while ($d <= strtotime("next Sunday", $end_date)) {
  $i = date("Y-m-d", $d);
  
  # Start the week row.
  if (date("N", $d) == 1) {
    echo "\n<tr valign=top>";
  }

  # Print the day.
  echo "\n<td class=\"month-".( date("n", $d) % 2 == 0 ? "even" : "odd" )."\">";
  echo date("\nd F Y", $d);
  if (array_key_exists($i, $facts)) {
    echo "\n<ul>";
    foreach ($facts[$i] as $fact) {
      echo "\n<li>".htmlentities($fact)."</li>";
    }
    echo "\n</ul>";
  }
  echo "\n</td>";

  # End the week row.
  if (date("N", $d) == 7) {
    echo "\n</tr>";
  }

  $d = strtotime("+1 day", $d);
}
echo "</table>";

echo "</body>";
?>
</html>
