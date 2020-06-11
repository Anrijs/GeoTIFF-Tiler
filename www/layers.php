<?php
  include "config/config.php";

  $body  = "<h1> Layers</h1><hr class=\"alt\">";


  $layers = array_diff(scandir($_R["layers"]), array('..', '.'));

  $no = 1;
  $body .= '<table class="table table-striped table-sm"><tr><th>#</th><th>Name</th><th>Zoom</th><td>Maps</td><th></th></tr>';
  foreach ($layers as $layer) {
    if (!is_dir($_R["layers"] . $layer)) continue;

    $name = $layer;
    $zoom = "";
    $maps = array();

    if (file_exists($_R["layers"] . $layer . "/name")) $name = file_get_contents($_R["layers"] . $layer . "/name");
    if (file_exists($_R["layers"] . $layer . "/zoom")) $zoom = file_get_contents($_R["layers"] . $layer . "/zoom");
    if (file_exists($_R["layers"] . $layer . "/maps")) $maps = explode(";",file_get_contents($_R["layers"] . $layer . "/maps"));

    $namehtml = $name . '<br><small class="light-text">'.$layer.'/{z}/{x}/{y}.png</small>';

    $body .= '<tr><td>'.$no++.'</td>';
    $body .= '<td>'.$namehtml.'</td>';
    $body .= '<td>'.$zoom.'</td>';
    $body .= '<td>'.(sizeof($maps)-1).'</td>';
    $body .= '<td>';
    $body .= '<a href="map.php?layer='.$layer.'" class="btn btn-sm btn-info" style="margin-right:4px;">View layer</a>';
    $body .= '<a href="layers.rm.php?uid='.$layer.'" onclick="return confirm(\'Are you sure? Used maps will still vissible in maps page.\nMap '.$name.' will be deleted forever.\')" class="btn btn-sm btn-danger">Delete</a>'."\n";
  }
  $body .= '</table>';
  $body .= '<div class="float-right"><a href="layers.add.php" class="btn btn-info">Add layer</a></div>';

  $contents["tab"] = "Layers";
  $contents["header"] = "";
  $contents["script"] = "";
  $contents["body"] = $body;
  // draw template
  include 'templates/template.main.php';

?>
