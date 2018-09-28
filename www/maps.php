<?php
  include "config/config.php";
  include "lib/filetools.php";
  include "lib/tifftools.php";

  function endsWith($haystack, $needle) {
    $length = strlen($needle);

    return $length === 0 || 
    (substr($haystack, -$length) === $needle);
  }

  $body  = "<h1>Maps</h1><hr class=\"alt\">";

  $maps = array_reverse(array_diff(scandir($_R["maps"]), array('..', '.')));

  $body .= '<table class="table table-striped table-sm"><tr><th>#</th><th>Name</th><th>uid</th><th>Date</th><th>Size</th><th>Info</th><th></th></tr>';
  $pos = 1;
  foreach ($maps as $map) {
    $name = file_get_contents($_R["maps"] . $map . "/name");
    $fname = $map;
    $fsize = dirsize($_R["maps"].$map);

    $tifname = "";

    $dirfiles = array_diff(scandir($_R["maps"].$map), array('..', '.'));
    foreach ($dirfiles as $f) {
      if (endsWith($f,".tif")) {
        $tifname = $f;
        break;
      }
    }

    $img64 = str_replace(".tif",".64.png",$tifname);
    $img512 = str_replace(".tif",".512.png",$tifname);

    $img64a = '<a href="' . $_R["maps"].$map."/".$img512.'"><img style="height:64px;" src="'.$_R["maps"].$map."/".$img64.'"></a>';

    $tifflnk = $_R["maps"].$map."/".$tifname;
    $tiffdl = " <a href=\"". $tifflnk . "\">Download TIFF</a>";

    $infofile = $_R["maps"] . $map . "/info";
    if (!file_exists($infofile)) {
      // generate tiff info

      // GeoTIFF info
      $tiff_info = getGdalInfo($_R["maps"] . $map . "/" . $tifname);

      $info = fopen($infofile, "w");
      fwrite($info, $tiff_info["p"] . "," . $tiff_info["s"]); // perimeter, area
      fclose($info);
    }

    $tiff_info = explode(",",file_get_contents($infofile));
    $t_p = (round($tiff_info[0])) . "m";
    $t_s = (round($tiff_info[1]));

    if ($t_s > 100000) {
      $t_s = (round($t_s/10000)/100) . "km2";
    } else {
      $t_s = $t_s . "m2";
    }

    $tiffinfo = "<small>S: ${t_s}</small><br><small>P: ${t_p}</small>";

    $body .= '<tr><td>'.$pos++.'</td><td>'.$img64a.$name.'</td><td>'.$tifname."<br><small>".$fname.$tiffdl."</small>".'</td><td>2018-07-05 09:10:12</td><td>'.human_filesize($fsize).'</td><td>'.$tiffinfo.'</td><td><a href="maps.add2layer.php?uid='.$map.'" class="btn btn-sm btn-success">Add to layer</a>';
    $body .= "\n" . '<a href="maps.rm.php?uid='.$map.'" onclick="return confirm(\'Are you sure? Tiles will be still vissible in  layers.\nMap '.$name.' will be deleted forever.\')" class="btn btn-sm btn-danger">Delete</a>'."\n";

    $body .= '</td>';    
  }
  $body .= '</table>';
  $body .= '<div class="float-right"><a href="maps.add.php" class="btn btn-info">Add map</a></div>';

  $contents["tab"] = "Maps";
  $contents["header"] = ""; 
  $contents["script"] = ""; 
  $contents["body"] = $body;
  // draw template
  include 'templates/template.main.php';

?>
