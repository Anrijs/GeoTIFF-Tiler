<?php
  include "config/config.php";

  if(isset($_POST['layerName'])) {
    $name = $_POST['layerName'];
    $min = 10;
    $max = 21;
    $dir = uniqid();

    if(isset($_POST['zoomMin'])) {
      $min = $_POST['zoomMin'];
    }
    if(isset($_POST['zoomMax'])) {
      $max = $_POST['zoomMax'];
    }

    #if dir notexists: create
    while(file_exists($_R["layers"] . $dir)) {
      $dir = uniqid();
    }

    $target_dir = $_R["layers"] . $dir;
    mkdir($target_dir);
    $namefile = fopen($target_dir . "/name", "w");
    fwrite($namefile, $name);
    fclose($namefile);

    $zoomfile = fopen($target_dir . "/zoom", "w");
    fwrite($zoomfile, $min."-".$max);
    fclose($zoomfile);

    $mapsfile = fopen($target_dir . "/maps", "w");
    fwrite($mapsfile, "");
    fclose($mapsfile);

    header("Location: layers.php");
  }

  $body  = "<h1>Add layer</h1><hr class=\"alt\">";

  $body .= '<form action="layers.add.php" method="post" enctype="multipart/form-data">';
  $body .= '<label for="layerName"><b>Layer name</b></label><br><input type="text" name="layerName" id="layerName"><br>';
  $body .= '<label min="0" max="24" for="zoomMin"><b>Zoom</b></label><br><input style="width: 60px;" min="0" max="24" type="number" name="zoomMin" id="zoomMin"> - <input style="width: 60px;" type="number" name="zoomMax" id="zoomMax"><br>';
  $body .= '<input style="margin-top: 8px;" class="btn btn-info" type="submit" value="Add layer" name="submit">';
  $body .= '</form>';

  $contents["tab"] = "Maps";
  $contents["header"] = "";
  $contents["script"] = "";
  $contents["body"] = $body;
  // draw template
  include 'templates/template.main.php';

?>
