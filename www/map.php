<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GPS Test</title>
    <link rel="stylesheet" href="css/leaflet.css" />
    <link rel="stylesheet" href="css/leaflet.draw.css" />
    <link rel="stylesheet" href="css/leaflet.measure.css" />
    <link rel="stylesheet" href="css/leaflet.sidebar.css" />
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

    <script src="js/leaflet.js"></script>
    <script src="js/leaflet.draw.js"></script>
    <script src="js/leaflet.measure.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.4.0/gpx.min.js"></script>

    <style>
        body {
            padding: 0;
            margin: 0;
        }
        html, body, #map {
            height: 100%;
            width: 100%;
            background-color: #000;
            font-family: helvetica;
        }
        .rgl {
            position: absolute;
            bottom: 0;
            left: 0;
            z-index: 999;
            background-color: #fefefe;
            padding: 8px;
        }
        .actn {
            border-bottom: solid 1px #999;
            margin-bottom: 4px;
        }
        
        .tracksq {
            height:8px;
            width:8px;
            margin-right: 16px;
            display: inline-block;
        }
        
        .leaflet-retina .leaflet-control-layers-toggle,
        .leaflet-control-layers-toggle {
            background-image: url('../img/layers.png');
        }
        
        #dyn {
          margin-right: 60px;
          min-height: 60px;
        }

        #sidetracks {
            padding: 12px;
        }
        
        .clickable {
            cursor: pointer;
            text-decoration: underline;
            color: #2196F3;
        }
    </style>
</head>
<body>
    <div id="map" class="sidebar-map"></div>
    <div class="rgl">
        <div id="stat"></div>
        <div id="dyn"></div>
    </div>
</body>
<script type="text/javascript">



var map_osm = L.tileLayer('//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
  maxZoom: 22,
  maxNativeZoom: 18,
});

<?php
    include "config/config.php";
    include "lib/tifftools.php";


    $moverlays = array();
    
    if (isset($_GET["map"])) {
      // find map and enable overlay
      $mapid = $_GET["map"];
      $maps = array_diff(scandir($_R["maps"]), array('..', '.'));
      foreach ($maps as $l) {
        if(!is_dir($_R["maps"] . $l)) {
          continue;
        }
        if ($l == $mapid) {
          $map = getMapInfo($_R["maps"] . $mapid);

          $tiledir = $map["tiledir"];
          if (strlen($tiledir) < 1) {
            $tiledir = "original/" . $map["original"]["tiledir"];
          }
          echo "var map_" . $l . " =  L.tileLayer('/maps/" . $l . "/" . $tiledir . "/{z}/{x}/{y}.png', {\n";
          echo "attribution: '<a href=\"https://github.com/Anrijs/GeoTIFF-Tiler\">GeoTIFF Tiler</a>',\n";
          echo "  maxZoom: 22,\n";
          echo "  maxNativeZoom: 22,\n";
          echo "  detectRetina: false,\n";
          echo "});\n";
          $moverlays[] = array("id" => $l, "name" => $map["name"]);
          break;
        }
      }
  }

  $lyrid = isset($_GET["layer"]) ? $_GET["layer"] : FALSE;
  $overlays = array();
  $layers = array_diff(scandir($_R["layers"]), array('..', '.'));
  foreach ($layers as $l) {
    if(!is_dir($_R["layers"] . $l)) {
    	continue;
    }
    $name = file_get_contents($_R["layers"] . $l . "/name");
    $ol = array();
    $ol["name"] = trim(preg_replace('/\s\s+/', ' ', $name));
    $ol["id"] = $l;
    $overlays[] = $ol;

    if ($lyrid == $l) {
      $moverlays[] = $ol;
    }

    echo "var map_" . $l . " =  L.tileLayer('/" . $_R["layers"] . $l . "/{z}/{x}/{y}.png', {\n";
    echo "attribution: '<a href=\"https://github.com/Anrijs/GeoTIFF-Tiler\">GeoTIFF Tiler</a>',\n";
    echo "  maxZoom: 22,\n";
    echo "  maxNativeZoom: 22,\n";
    echo "  detectRetina: false,\n";
    echo "});\n";
  }
?>

var baseMaps = {
  "OSM": map_osm,
};

var overlayMaps = {
        <?php
        foreach (array_reverse($overlays) as $ol) {
                echo '"'.$ol["name"].'": ' . "map_" . $ol['id'] . ",\n";
        }

        foreach (array_reverse($moverlays) as $ol) {
          echo '"'.$ol["name"].'": ' . "map_" . $ol['id'] . ",\n";
        }
        ?>
};


    var map = L.map('map', {maxZoom:22}).setView([getUrlLatitude(), getUrlLongitude()], getUrlZoom());

    baseMaps["OSM"].addTo(map);
    
    L.control.layers(baseMaps, overlayMaps).addTo(map);

    <?php 
    foreach ($moverlays as $ol) {
      echo "map_" . $ol['id'] . ".addTo(map);\n";
    }
    ?>
    
    var measureControl = L.control.measure(
      {
        position: 'topleft',
        primaryLengthUnit: 'meters',
        secondaryLengthUnit: 'kilometers',
        primaryAreaUnit: 'sqmeters', 
        secondaryAreaUnit: 'hectares',
        decPoint: '.', 
        thousandsSep: ' ',
        activeColor: '#F44336',
        localization: 'lv'
      });
    measureControl.addTo(map);

    var cc = "?";
    
    var colors = [
      "#F44336",
      "#3F51B5",
      "#9C27B0",
      "#E91E63",
      "#009688",
      "#00BCD4",
      "#03A9F4",
      "#2196F3",
      "#FFEB3B",
      "#CDDC39",
      "#8BC34A",
      "#4CAF50",
      "#795548",
      "#FF5722",
      "#FF9800",
      "#FFC107",
    ];

/****  URL  STUFF  FROM /kartes ****/
    var overlays = [];

    function getUrlMapParam(pos) {
      var map = getParam("map");
      if (map !== undefined) {
        var pts = map.split('/');
        if(pts.length > 2) {
          if (pts[pos] !== undefined) {
            return pts[pos];
          }
        }
      }
    }

    function getUrlLatitude() {
      var p = getUrlMapParam(1);
      if(p !== undefined) {
        return p;
      }
      return 56.94666;
    }

    function getUrlLongitude() {
      var p = getUrlMapParam(2);
      if(p !== undefined) {
        return p;
      }
      return 24.12786;
    }

    function getUrlZoom() {
      var p = getUrlMapParam(0);
      if(p !== undefined) {
        return p;
      }
      return 12;
    }

    function getUrlMarkerLatitude() {
      var p = getParam("marker");
      if (p !== undefined) {
        var pts = p.split('/');
        if (pts !== undefined && pts.length > 1) {
          return pts[0];
        }
      }
      return getUrlLatitude();
    }

    function getUrlMarkerLongitude() {
      var p = getParam("marker");
      if (p !== undefined) {
        var pts = p.split('/');
        if (pts !== undefined && pts.length > 1) {
          return pts[1];
        }
      }
      return getUrlLongitude();
    }

    function getParam(param) {
      // #map=18/57.42883/27.05595
      var hash = window.location.hash.substring(1);
      var params = hash.split('&');
      var i = 0;
      var pname = "";

      for (i = 0; i< params.length; i++) {
        pname = params[i].split('=');
        if (param === pname[0]) {
          return pname[1] === undefined ? true : pname[1];
        }
      }
    }

    function updateUrlParams(lat, lon, zoom) {
      lat = Math.round(lat*100000)/100000;
      lon = Math.round(lon*100000)/100000;

      markstr = "";

      if(marker !== undefined) {
        markstr = "&marker=" + marker.getLatLng().lat + "/" + marker.getLatLng().lng;
      }

      if(overlays.length > 0) {
        markstr += "&layers=" + overlays.join(";");
      }

      window.location.hash = "#map="+zoom+"/"+lat+"/"+lon + markstr;
    }

    var marker;
    if(getParam("marker")) {
      marker = L.marker([getUrlMarkerLatitude(), getUrlMarkerLongitude()]).addTo(map);
    }

    Array.prototype.remove = function() {
    var what, a = arguments, L = a.length, ax;
    while (L && this.length) {
        what = a[--L];
        while ((ax = this.indexOf(what)) !== -1) {
            this.splice(ax, 1);
        }
    }
    return this;
    };

    map.on('overlayadd', function(e) {
      var lname = e.name;
      if(!(lname in overlays)) {
        overlays.push(lname);
      }
      updateUrlParams(e.target.getCenter().lat, e.target.getCenter().lng, e.target.getZoom());
    });

    map.on('overlayremove', function(e) {
      test = e;
      var lname = e.name;
      overlays.remove(lname);
      updateUrlParams(e.target.getCenter().lat, e.target.getCenter().lng, e.target.getZoom());
    });
    
    /**** END  OF  URL  STUFF  FROM /kartes ****/

   map.on('mousemove', function(e) {   
        var latd = Math.floor(e.latlng.lat);
        var latm = Math.floor((e.latlng.lat-latd)*60);
        var latmd = Math.round((e.latlng.lat-latd)*60*100000)/100000;
        var lats = Math.floor((e.latlng.lat-latd-latm/60)*3600);

        var lond = Math.floor(e.latlng.lng);
        var lonm = Math.floor((e.latlng.lng-lond)*60);
        var lonmd = Math.round((e.latlng.lng-lond)*60*100000)/100000
        var lons = Math.floor((e.latlng.lng-lond-lonm/60)*3600);

        var text = '<small>DD.DDD: ' + (Math.round(e.latlng.lat*100000)/100000)+', '+(Math.round(e.latlng.lng*100000)/100000)+'</small><br>';
            text += '<small>DM.MMM :</b> ' + latd + '&deg; ' + latmd + '\', ' + lond + '&deg; ' + lonmd + '\'</small>';
            //text += '<b style="font-size:1.2em;">DMS    :</b> ' + latd + '&deg; ' + latm + '\' ' + lats + '", ' + lond + '&deg; ' + lonm + '\' ' + lons + '"<br>';

        $('#dyn').html(text);
        var popLocation= e.latlng;

         map.on('dragend', function(e) {
            updateUrlParams(e.target.getCenter().lat, e.target.getCenter().lng, e.target.getZoom());
         });

        map.on('zoomend', function(e) {
            updateUrlParams(e.target.getCenter().lat, e.target.getCenter().lng, e.target.getZoom());
        });
    });
    
    function placemarker(lat, lon) {
      marker = L.marker([lat, lon]).addTo(map);
      updateUrlParams(map.getCenter().lat, map.getCenter().lng, map.getZoom());
    }
    
    map.on('click', function(e) {   
      var latd = Math.round(e.latlng.lat*100000)/100000;
      var lond = Math.round(e.latlng.lng*100000)/100000;

      var text = '<small>' + latd +', '+lond+' <button onclick="placemarker('+latd+','+lond+')">Add marker</button></small>';
      $('#stat').html(text);
    });

    
    $('.leaflet-container').css('cursor','crosshair');

    <?php 
      echo "var gpxs = [";
      if (isset($_GET["gpx"])) {
        $gpxs = $_GET["gpx"];
        $gpxs = explode(";", $gpxs);

        foreach ($gpxs as $gpx) {
          echo '"'.$gpx.'",';
        }
      }
      echo "];\n";
    ?>

    for (var i = 0; i < gpxs.length; i++) {
      var gpx = "gpx/" + gpxs[i]; // URL to your GPX file or the GPX itself
      new L.GPX(gpx, {async: true}).on('loaded', function(e) {
      map.fitBounds(e.target.getBounds());
      }).addTo(map);
    }
</script>

</html>

 
