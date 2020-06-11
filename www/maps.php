<?php
  include "config/config.php";
  include "lib/filetools.php";
  include "lib/tifftools.php";

  function regenerateInfo($target_dir, $target_file) {
    // GeoTIFF info
    $tiff_info = getGdalInfo($target_file);

    $infofile = fopen($target_dir . "info", "w");
    fwrite($infofile, $tiff_info["p"] . "," . $tiff_info["s"] . "," . $tiff_info["px"]); // preimeter, area
    fclose($infofile);
  }

  $page = 1;
  if (isset($_GET["page"])) {
    $page = $_GET["page"];
    if ($page == "all") {
      $page = -1;
    } else if (is_int($page)) {
      $page = intval($page);
    }
  }

  $itemStart = 0;
  $itemEnd = -1;

  if ($page > 0) {
    $itemStart = 15 * ($page - 1);
    $itemEnd = 15 * $page;
  }

  $body  = "<h1>Maps</h1><hr class=\"alt\">";

  $maps = array_reverse(array_diff(scandir($_R["maps"]), array('..', '.')));

  $tableBody .= '<table class="table table-striped table-sm"><tr><th>#</th><th>Name</th><th>uid</th><th>Date</th><th>Size</th><th>Info</th><th></th></tr>';
  $pos = 1;
  
  foreach ($maps as $map) {
    if ($itemEnd > 0 && $pos < $itemStart) {
      $pos++;
      continue;
    }

    if ($itemEnd > 0 && $pos > $itemEnd) {
      $pos++;
      continue;
    }

    $name = $map;
    $fsize = 0;

    if (!file_exists($_R["maps"] . $map . "/name")) continue;

    $name = file_get_contents($_R["maps"] . $map . "/name");
    $fname = $map;
    $fsize = dirsize($_R["maps"].$map);

    $mapInfo = getMapInfo($_R["maps"].$map);

    $fname = $mapInfo["name"];
    $tiledir = $mapInfo["tiledir"];

    $hastif = $mapInfo["tif"]["available"];
    $tifname = $mapInfo["tif"]["image"];

    $hasoriginal = $mapInfo["original"]["available"];
    $originalimg = $mapInfo["original"]["image"];
    $originalmap = $mapInfo["original"]["map"];
    $originaltiledir = $mapInfo["original"]["tiledir"];

    $img64 = $fname . ".64.png";
    $img512 = $fname . ".512.png";

    $img64a = '<a href="' . $_R["maps"].$map."/".$img512.'"><img style="height:64px;" src="'.$_R["maps"].$map."/".$img64.'"></a>';

    $lnks = array();

    $imgname = $originalimg;
    
    if ($hastif) {
        $tifflnk = $_R["maps"].$map."/".$tifname;
        $lnks[] = " <a href=\"". $tifflnk . "\">Download TIFF</a>";
        $imgname = $tifname;
    }

    if (strlen($originalmap) > 0) {
        $omaplink = $_R["maps"].$map."/original/".$originalmap;
        $lnks[] = "<a href=\"". $omaplink . "\">OZI map calibration</a>";
    }
    if (strlen($originalimg) > 0) {
	      $oimglink = $_R["maps"].$map."/original/".$originalimg;
        $lnks[] = "<a href=\"". $oimglink . "\">Original image</a>";
    }

    $tiffdl = implode(" | ", $lnks);

    $infogen = 0;

    $infofile = $_R["maps"] . $map . "/info";
    $infook = file_exists($infofile);
    if ($infook) {
        $tiff_info = explode(",",file_get_contents($infofile));
        if (sizeof($tiff_info) < 4) {
          $infook = 0;
          $infogen++;
        }
    }

    if ($hastif) {
      if (!$infook) {
        $infogen++;
        // generate tiff info

        // GeoTIFF info
        $tiff_info = getGdalInfo($_R["maps"] . $map . "/" . $tifname);

        $tiff_info = array($tiff_info["p"], $tiff_info["s"], $tiff_info["px"][0], $tiff_info["px"][1]);

        $info = fopen($infofile, "w");
        fwrite($info, implode(",", $tiff_info)); // perimeter, area, px size
        fclose($info);
      }

      if (sizeof($tiff_info) < 3) {
        // regenerate
      }

      $t_p = (round($tiff_info[0])) . "m";
      $t_s = (round($tiff_info[1]));
      $t_px = (round(($tiff_info[2]+$tiff_info[3])*50)/100);

      if ($t_s > 1000000) {
        $t_s = (round($t_s/10000)/100) . "km2";
      } else if ($t_s > 10000) {
        $t_s = (round($t_s/100)/100) . "ha";
      } else {
        $t_s = $t_s . "m2";
      }
    }

    if (strlen($tiledir) < 1) {
      if (strlen($originaltiledir) < 1) {
        $tiledir = $map . "/" . $fname . " (not sliced)";
      } else {
        $tiledir = $map . "/original/" . $originaltiledir . "/{z}/{x}/{y}.png";
      }
    } else {
      $tiledir = $map . "/" . $tiledir . "/{z}/{x}/{y}.png";
    }

    $tiffinfo = "<small>S: ${t_s}</small><br><small>P: ${t_p}</small><br><small>{$t_px} px/cm</small>";

    $tableBody .= '<tr><td>'.$pos++.'</td><td>'.$img64a.$name.'</td><td>'.$imgname."<br><small>maps/".$tiledir.'<br>'.$tiffdl.'</small></td><td>2018-07-05 09:10:12</td><td>'.human_filesize($fsize).'</td><td>'.$tiffinfo.'</td><td><a href="maps.add2layer.php?uid='.$map.'" class="btn btn-sm btn-success">Add to layer</a>';
    $tableBody .= "\n" . '<a href="map.php?map='.$map.'" class="btn btn-sm btn-info" style="margin-right:4px;">View layer</a>';
    $tableBody .= "\n" . '<a href="maps.rm.php?uid='.$map.'" onclick="return confirm(\'Are you sure? Tiles will be still vissible in  layers.\nMap '.$name.' will be deleted forever.\')" class="btn btn-sm btn-danger">Delete</a>'."\n";

    $tableBody .= '</td>';
  }
  $tableBody .= '</table>';

  // generate pagination
  $paginationBody = "<nav aria-label=\"Map pages\"><ul class=\"pagination\">";
  $totalPages = ceil($pos / 15);
  for ($i = 1 ; $i <= $totalPages; $i++) {
    $activecl = "";
    if ($i == $page) {
      $activecl = "active";
    }
    $paginationBody .= "<li class=\"page-item $activecl\"><a class=\"page-link\" href=\"?page=$i\">$i</a></li>";
  }
  $activecl = "";
  if ($page == -1) {
    $activecl = "active";
  }
  $paginationBody .= "<li class=\"page-item $activecl\"><a class=\"page-link\" href=\"?page=all\">All</a></li>";
  $paginationBody .= "</ul></nav>";

  $pbar = "<div class=\"row\">";
  
  $pbar .= "<div class=\"col-lg-10 float-left\">" . $paginationBody . "</div>";
  $pbar .= '<div class="col-lg-2 float-right"><a href="maps.add.php" class="btn btn-info">Add map</a></div>';
  $pbar .= "</div>";

  $body .= $pbar;
  $body .= $tableBody;
  $body .= $pbar;

  $contents["tab"] = "Maps";
  $contents["header"] = "";
  $contents["script"] = "";
  $contents["body"] = $body;
  // draw template
  include 'templates/template.main.php';

?>
