<?php
  include "config/config.php";

  $body  = "<h1>Upload Geo TIFF Map</h1><hr class=\"alt\">";

  $body .= '<form action="maps.upload.php" method="post" enctype="multipart/form-data">';
  $body .= 'Map name: <input type="text" name="mapName" id="mapName"><br>';
  $body .= 'Select tiff image to upload:<br>';
  $body .= '<input type="file" name="fileToUpload" id="fileToUpload"><br>';
  $body .= '<input class="btn btn-info" type="submit" value="Upload" name="submit">';
  $body .= '</form>';
  
  $body .= '<br><h1> or Ozi calibrated map + image</h2>';
  $body .= '<form action="maps.ozi.upload.php" method="post" enctype="multipart/form-data">';
  $body .= 'Map name: <input type="text" name="mapName" id="mapName"><br>';
  $body .= 'Select map calibration (.map) to upload:<br>';
  $body .= '<input type="file" name="mapFileToUpload" id="mapFileToUpload"><br>';
  $body .= 'Select map image to upload:<br>';
  $body .= '<input type="file" name="imgFileToUpload" id="imgFileToUpload"><br>';

  $body .= '<br><div class="form-check">';
  $body .= '<input type="checkbox" class="form-check-input" name="makeTiff" id="makeTiff" checked>';
  $body .= '<label class="form-check-label" for="makeTiff">Convert to GeoTIFF</label>';
  $body .= '</div><br>';

  $body .= '<input class="btn btn-info" type="submit" value="Upload" name="submit">';
  $body .= '</form>';
  
  $contents["tab"] = "Maps";
  $contents["header"] = "";
  $contents["script"] = "";
  $contents["body"] = $body;
  // draw template
  include 'templates/template.main.php';

?>
