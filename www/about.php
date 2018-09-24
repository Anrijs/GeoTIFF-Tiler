<?php
  include "config/config.php";


  $body  = "<h1> About</h1><hr class=\"alt\">";

  $body .= "<b>GeoTIFF Tiler</b> by <b>Anrijs</b> | <a href=\"https://spaaace.lv\">spaaace.lv</a><br>";
  $body .= "Git: <a href=\"https://github.com/Anrijs/GeoTIFF-Tiler\">GeoTIFF Tiler</a>";

  $contents["tab"] = "About";
  $contents["header"] = ""; 
  $contents["script"] = ""; 
  $contents["body"] = $body;
  // draw template
  include 'templates/template.main.php';

?>
