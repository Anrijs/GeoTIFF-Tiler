<?php
  function getactive($tab,$check) {
    return $tab == $check ? "active" : "";
  }
?>
<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
  <a class="navbar-brand" href="#">GeoTIFF Tiler</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarsExampleDefault">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item <?php echo getactive($contents["tab"], "Dashboard"); ?>">
        <a class="nav-link" href="index.php">Dashboard <span class="sr-only">(current)</span></a>
      </li>
      <li class="nav-item <?php echo getactive($contents["tab"], "Maps");?>">
        <a class="nav-link" href="maps.php">Maps</a>
      </li>
      <li class="nav-item <?php echo getactive($contents["tab"], "Layers");?>">
        <a class="nav-link" href="layers.php">Layers</a>
      </li>
      <li class="nav-item <?php echo getactive($contents["tab"], "About");?>">
        <a class="nav-link" href="about.php">About</a>
      </li>
      <li style="width:1px; background: #777; margin: 4px;;" class="nav-item"></li>
      <li class="nav-item <?php echo getactive($contents["tab"], "Map");?>">
        <a class="nav-link" href="map.php">Map</a>
      </li>

    </ul>
  </div>
</nav>
