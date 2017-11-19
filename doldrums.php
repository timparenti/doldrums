<?php
function computeFacts($lat, $lon, $tz) {
  global $print_year, $bounds, $facts;
  require_once('season.inc.php');

  date_default_timezone_set($tz);
  $now = time();


  # Determine the year in question, centered around the winter solstice.
  if ($lat < 0) {
    $year = date("Y", $now);
    $bounds = array( strtotime($year."-01-01 12:00"), strtotime($year."-12-31 12:00") );
    $print_year = $year;
    $solstice = date_equisol($year,1);
    $equinox = date_equisol($year,2);
  }
  else {
    $year = ( date("n", $now) >= 7 ? date("Y", $now) : date("Y", $now) - 1 );
    $bounds = array( strtotime($year."-07-01 12:00"), strtotime(($year+1)."-06-30 12:00") );
    $print_year = $year."-".($year+1);
    $solstice = date_equisol($year,3);
    $equinox = date_equisol($year+1,0);
  }
  addFact("Winter solstice at ".t($solstice), $solstice);
  addFact("Spring equinox at ".t($equinox), $equinox);


  # Detect DST discontinuities.
  # These are arrays, as there may technically be more than one in each direction.
  $d = $bounds[0];
  $existing_zone = date("Z", $d);
  while ($d <= $bounds[1]) {
    $new_zone = date("Z", $d);
    if ($new_zone < $existing_zone) {
      $discon['neg'][] = $d;
      $existing_zone = $new_zone;
    }
    elseif ($new_zone > $existing_zone) {
      $discon['pos'][] = $d;
      $existing_zone = $new_zone;
    }
    $d = strtotime("+1 day", $d);
  }
  foreach ($discon['neg'] as $dis) {
    addFact("Daylight Saving Time ends (fall back)", $dis);
  }
  foreach ($discon['pos'] as $dis) {
    addFact("Daylight Saving Time begins (spring forward)", $dis);
  }

  # Bound the doldrums.
    # Doldrums start at the earlier of:
    #   - The day after the latest negative (fall back) DST discontinuity, if it exists.
    #   - 30 days before the winter solstice.
    if (count($discon['neg']) > 0) {
      $doldrums[0] = min( strtotime("+1 day 12:00", max($discon['neg'])), strtotime("-30 days 12:00", $solstice) );
    }
    else {
      $doldrums[0] = strtotime("-30 days 12:00", $solstice);
    }

    # Doldrums end at the later of:
    #   - The day before the earliest positive (spring forward) DST discontinuity, if it exists.
    #   - The day after the spring equinox.
    if (count($discon['pos']) > 0) {
      $doldrums[1] = max( strtotime("-1 day 12:00", min($discon['pos'])), strtotime("+1 day 12:00", $equinox) );
    }
    else {
      $doldrums[1] = strtotime("+1 day 12:00", $equinox);
    }


  # Get stats on each day in the doldrums.
  # But start with the day before for comparison purposes.
  $d = strtotime("-1 day", $doldrums[0]);
  while ($d <= $doldrums[1]) {
    $sun_info = date_sun_info($d, $lat, $lon);
    $i = d($d);

    # Use "seconds since local noon" as a consistent "diff" metric,
    # while still storing the timestamps themselves.
    $dawns['time'][$i] = $sun_info['civil_twilight_begin'];
    $dawns['diff'][$i] = $dawns['time'][$i] - $d;
    $rises['time'][$i] = $sun_info['sunrise'];
    $rises['diff'][$i] = $rises['time'][$i] - $d;
    $sets['time'][$i] = $sun_info['sunset'];
    $sets['diff'][$i] = $sets['time'][$i] - $d;
    $dusks['time'][$i] = $sun_info['civil_twilight_end'];
    $dusks['diff'][$i] = $dusks['time'][$i] - $d;

    $day_lengths[$i] = $sets['time'][$i] - $rises['time'][$i];
    $twi_lengths[$i] = $dusks['time'][$i] - $dawns['time'][$i];

    # Only make comparisons to yesterday when in the doldrums.  (Avoids nil references, really.)
    if ($d >= $doldrums[0]) {
      $y = prevDay($d);

      $rise_deltas[$i] = $rises['diff'][$i] - $rises['diff'][$y];
      $set_deltas[$i] = $sets['diff'][$i] - $sets['diff'][$y];
      $day_deltas[$i] = $day_lengths[$i] - $day_lengths[$y];
      $twi_deltas[$i] = $twi_lengths[$i] - $twi_lengths[$y];

      # And calculate the length of the previous night while we're here.
      $night_lengths[$i] = $rises['time'][$i] - $sets['time'][$y];
    }

    $d = strtotime("+1 day", $d);
  }


  # Find the latest dawns/rises and the earliest sets/dusks.
  # These are arrays because there may be ties.
  $latest_dawn = array_keys($dawns['diff'], max($dawns['diff']));
  $latest_rise = array_keys($rises['diff'], max($rises['diff']));
  $earliest_set = array_keys($sets['diff'], min($sets['diff']));
  $earliest_dusk = array_keys($dusks['diff'], min($dusks['diff']));
  #
  # Break ties by delaying the fact to the latest date, so you KNOW you can be hopeful after!
  $latest_dawn_time = $dawns['time'][end($latest_dawn)];
  $latest_rise_time = $rises['time'][end($latest_rise)];
  $earliest_set_time = $sets['time'][end($earliest_set)];
  $earliest_dusk_time = $dusks['time'][end($earliest_dusk)];

  # Find the shortest days and twilights.
  $short_day_length = min($day_lengths);
  $short_twi_length = min($twi_lengths);
  $long_night_length = max($night_lengths);
  $shortest_day = array_keys($day_lengths, $short_day_length);
  $shortest_twi = array_keys($twi_lengths, $short_twi_length);
  $longest_night = array_keys($night_lengths, $long_night_length);

  # Mention extremes based on whether they go to zero.
  if ($short_twi_length != 0) {
    addFact("Shortest twilight (".dur($short_twi_length).")", strtotime(end($shortest_twi)));
    addFact("Latest dawn at ".t($latest_dawn_time), $latest_dawn_time);
    addFact("Earliest dusk at ".t($earliest_dusk_time), $earliest_dusk_time);
  }
  else {
    addFact("Last day of total darkness", strtotime(end($shortest_twi)));
    addFact("First twilight begins at ".t($dawns['time'][nextDay(end($shortest_twi))]), strtotime(nextDay(end($shortest_twi))));
  }
  if ($short_day_length != 0) {
    addFact("Shortest day (".dur($short_day_length).")", strtotime(end($shortest_day)));
    addFact("Latest sunrise at ".t($latest_rise_time), $latest_rise_time);
    addFact("Earliest sunset at ".t($earliest_set_time), $earliest_set_time);
  }
  else {
    addFact("Last day with no direct sun", strtotime(end($shortest_day)));
    addFact("First sunrise at ".t($rises['time'][nextDay(end($shortest_day))]), strtotime(nextDay(end($shortest_day))));
  }
  if ($long_night_length < 86400) {
    addFact("Longest night (".dur($long_night_length).") ends at sunrise", strtotime(end($longest_night)));
  }

  # Find various milestones.
  addMilestones("Day length is now up to %dur%", $day_lengths, $day_lengths, 1800, $solstice, $doldrums[1]);
  addMilestones("Twilight length is now up to %dur%", $twi_lengths, $twi_lengths, 1800, $solstice, $doldrums[1]);
  addMilestones("Dawn is now as early as %t%", $dawns['time'], $dawns['diff'], 1800, $solstice, $doldrums[1], -1);
  addMilestones("Sunrise is now as early as %t%", $rises['time'], $rises['diff'], 1800, $solstice, $doldrums[1], -1);
  addMilestones("Sunset is now as late as %t%", $sets['time'], $sets['diff'], 1800, $solstice, $doldrums[1]);
  addMilestones("Dusk is now as late as %t%", $dusk['time'], $dusk['diff'], 1800, $solstice, $doldrums[1]);

  addMilestones("Day length is increasing by %dur% per day", $day_deltas, $day_deltas, 30, $solstice, $doldrums[1]);
  addMilestones("Sunrise is getting %dur% earlier each day", $rise_deltas, $rise_deltas, 30, $solstice, $doldrums[1], -1);
  addMilestones("Sunset is getting %dur% later each day", $set_deltas, $set_deltas, 30, $solstice, $doldrums[1]);
}


  
  function addMilestones($text, $repl_array, $search_array, $factor, $start, $end, $direction=1) {
    $d = $start;
    $i = d($d);
    $existing_quotient = intval($search_array[$i] / $factor);

    while ($d <= $end) {
      $i = d($d);
      $new_quotient = intval($search_array[$i] / $factor);
      # Only add a fact when the movement in the specified direction is EXACTLY 1 band of width $factor;
      # this greatly reduces chance of an obvious DST discontinuity triggering the fact.
      # It also happens to reduce the spamminess of these facts in near-polar areas
      # by skipping over things that change by more than $factor nearly every day.
      if (($new_quotient - $existing_quotient) / $direction == 1) {
        addFact( str_replace(
                     array("%dur%", "%t%"),
                     array(dur($repl_array[$i]), t($repl_array[$i])),
                     $text),
                 $d );
        $existing_quotient = $new_quotient;
      }
      $d = strtotime("+1 day", $d);
    }
  }
  function addFact($text, $d=false, $timepoint=false) {
    global $facts;

    # Don't add the fact if this is clearly a nil reference, or close to it.
    # Otherwise, you'll get a calendar going back to 1970.
    if ($d <= 1) { return; }

    if ($timepoint == true) {
      $facts[d($d)][] = $text." at ".date("H:i:s T", $d);
    }
    else {
      $facts[d($d)][] = $text;
    }
  }

  function dur($dur) {
    $r = "";
    $dur = abs($dur);
    if ($dur >= 3600) {
      $h = intval($dur/3600);
      $r .= $h."h";
      $dur %= 3600;
      $m = intval($dur/60);
      $r .= zPad($m,2)."&#x2032;";
      $dur %= 60;
    }
    else {
      $m = intval($dur/60);
      $r .= $m."&#x2032;";
      $dur %= 60;
    }
    $s = $dur;
    $r .= zPad($s,2)."&#x2033;";

    return $r;
  }
  function t($t) {
    return date("H:i:s T", $t);
  }
  function d($d) {
    return date("Y-m-d", $d);
  }
  function zPad($n, $dig) {
    $r = "";
    for ($i = strlen($n); $i < $dig; $i++) {
      $r .= "0";
    }
    $r .= $n;
    return $r;
  }
  function nextDay($d) {
    return d(strtotime("+1 day", $d));
  }
  function prevDay($d) {
    return d(strtotime("-1 day", $d));
  }

?>
