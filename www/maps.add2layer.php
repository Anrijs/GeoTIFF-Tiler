<?php
  include "config/config.php";

  $uid = "";
  if(isset($_GET['uid'])) {
    $uid = $_GET['uid'];
  } else {
    die("Missing map id");
  }

  $mapdir = $_R["maps"] . $uid;

  $body  = "<h1>Add Map to layer</h1><hr class=\"alt\">";

  $mname = file_get_contents($_R["maps"] . $uid . "/name");

  // get layers
  $layers = array();
  $layersf = array_diff(scandir($_R["layers"]), array('..', '.'));
  foreach ($layersf as $layer) {
    $name = file_get_contents($_R["layers"] . $layer . "/name");
    $zoom = file_get_contents($_R["layers"] . $layer . "/zoom");
    $maps = explode(";",file_get_contents($_R["layers"] . $layer . "/maps"));

    $body .= '<form action="maps.layer.process.php" method="post">';
    $body .= '<input type="hidden" name="luid" id="luid" value="'.$layer.'">';
    $body .= '<input type="hidden" name="muid" id="muid" value="'.$uid.'">';

    $body .= '<input type="hidden" name="lname" id="lname" value="'.$name.'">';
    $body .= '<input type="hidden" name="mname" id="mname" value="'.$mname.'">';

    $body .= '<input class="btn btn-md" type="submit" value="Add to '.$name.'" name="submit">';
    $body .= '</form><br>';
  }

  $contents["tab"] = "Maps";
  $contents["header"] = "";
  $contents["script"] = "";
  $contents["body"] = $body;
  // draw template
  include 'templates/template.main.php';

?>
