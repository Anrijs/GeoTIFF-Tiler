<?php
  include "config/config.php";

  $body  = "<h1> Layers</h1><hr class=\"alt\">";


  $layers = array_diff(scandir($_R["layers"]), array('..', '.'));

  $no = 1;
  $body .= '<table class="table table-striped table-sm"><tr><th>#</th><th>Name</th><th>Zoom</th><td>Maps</td><th></th></tr>';
  foreach ($layers as $layer) {
    $name = file_get_contents($_R["layers"] . $layer . "/name");
    $zoom = file_get_contents($_R["layers"] . $layer . "/zoom");
    $maps = explode(";",file_get_contents($_R["layers"] . $layer . "/maps"));

    $body .= '<tr><td>'.$no++.'</td><td>'.$name.'</td><td>'.$zoom.'</td><td>'.(sizeof($maps)-1).'</td><td><a href="map.php#layers='.$name.'" class="btn btn-sm btn-info" style="margin-right:4px;">View layer</a><a href="#" class="btn btn-sm btn-danger">Delete</a></td>';
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
