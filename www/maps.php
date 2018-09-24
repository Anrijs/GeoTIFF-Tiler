<?php
  include "config/config.php";
  include "lib/filetools.php";

  function endsWith($haystack, $needle) {
    $length = strlen($needle);

    return $length === 0 || 
    (substr($haystack, -$length) === $needle);
  }

  $body  = "<h1>Maps</h1><hr class=\"alt\">";

  $maps = array_diff(scandir($_R["maps"]), array('..', '.'));

  $body .= '<table class="table table-striped table-sm"><tr><th>#</th><th>Name</th><th>uid</th><th>Date</th><th>Size</th><th></th></tr>';
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

    $body .= '<tr><td>1</td><td>'.$img64a.$name.'</td><td>'.$tifname."<br><small>".$fname."</small>".'</td><td>2018-07-05 09:10:12</td><td>'.human_filesize($fsize).'</td><td><a href="maps.add2layer.php?uid='.$map.'" class="btn btn-sm btn-success">Add to layer</a>';
    $body .= "\n" . '<a href="maps.rm.php?uid='.$map.'" onclick="return confirm(\'Are you sure? Tiles will be still vissible in  layers.\nMap '.$name.' will be deleted forever.\')" class="btn btn-sm btn-danger">Delete</a>'."\n".'</td>';    
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
