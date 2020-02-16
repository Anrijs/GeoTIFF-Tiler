<?php
    function haversineGreatCircleDistance(
      $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
      // convert from degrees to radians
      $latFrom = deg2rad($latitudeFrom);
      $lonFrom = deg2rad($longitudeFrom);
      $latTo = deg2rad($latitudeTo);
      $lonTo = deg2rad($longitudeTo);
    
      $latDelta = $latTo - $latFrom;
      $lonDelta = $lonTo - $lonFrom;
    
      $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
      return $angle * $earthRadius;
    }

    function has_prefix_t($string, $prefix) {
        return ((substr($string, 0, strlen($prefix)) == $prefix) ? true : false);
    }

    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        return $length === 0 || (substr($haystack, -$length) === $needle);
    }

    function getLatLon($info) {
        $pt = explode("(", $info)[2];
        $pt = explode(")", $pt)[0];
        $pt = explode(",", $pt);

        $lon =  trim($pt[0]);
        $lat =  trim($pt[1]);

        $lat_pts = explode("\"", $lat);
        $lon_pts = explode("\"", $lon);

	$hemi = $lon_pts[1];
	$pole = $lat_pts[1];

	$lat_pts = explode("d", $lat_pts[0]);
	$lon_pts = explode("d", $lon_pts[0]);

	$lat_dd = intval($lat_pts[0]);
        $lon_dd = intval($lon_pts[0]);

        $lat_pts = explode("'", $lat_pts[1]);
        $lon_pts = explode("'", $lon_pts[1]);

        $lat_mm = intval($lat_pts[0]);
        $lon_mm = intval($lon_pts[0]);

        $lat_ss = floatval($lat_pts[1]);
        $lon_ss = floatval($lon_pts[1]);

	$lat_mm += ($lat_ss/60);
        $lon_mm += ($lon_ss/60);

	$outlat = $lat + ($lat_mm/60);
        $outlon = $lon + ($lon_mm/60);

	if (pole == "S") {
		$outlat = -$outlat;
	}
	if ($hemi == "W") {
		$outlon = $outlon;
	}

        return array($outlat, $outlon);
    }

    function getPixelSize($info) {
        $pt = explode("(", $info)[1];
        $pt = explode(")", $pt)[0];
        $pt = explode(",", $pt);

        $px1 =  $pt[0] * 100000;
        $px2 =  $pt[1] * 100000;

        return array($px1, $px2);
    }

    function getTiffSize($info) {
        $pt = trim(str_replace("Size is", "", $info));
        $pt = explode(",", $pt);

        $x =  trim($pt[0]);
        $y =  trim($pt[1]);

        return array($x, $y);
    }

    function getMapInfo($mapdir) {
        $tiledir = "";

        $hastif = FALSE;
        $tifname = "";
    
        $hasoriginals = FALSE;
        $originalmap = "";
        $originalimg = "";
        
        $alttiledir = "";
    
        $dirfiles = array_diff(scandir($mapdir), array('..', '.'));
        foreach ($dirfiles as $f) {
            if (endsWith($f,".tif")) {
                $tifname = $f;
                $hastif = TRUE;
            }
            if (endsWith($f,".xyz")) {
                $tiledir = $f;
            }
            if($f == "original") {
                $hasoriginals = TRUE;
                $originalfiles = array_diff(scandir($mapdir."/original"), array('..', '.'));
                foreach ($originalfiles as $o) {
                    if (endsWith($o,".xyz")) {
                        $alttiledir = $o;
                    }

                    $ext = end(explode(".",$o));
                        if ($ext == "map") {
                        $originalmap = $o;
                    }
                    if (in_array($ext, array("png","bmp","jpg","jpeg","tif","tiff"))) {
                        $originalimg = $o;
                    }
                }
            }
        }

        if (strlen($tifname)) {
            $name = $tifname;
        } else {
            $name = $originalmap;
        }

        $name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $name);
        
        return array(
            "name" => $name,
            "tiledir" => $tiledir,
            "tif" => array(
                "available" => $hastif,
                "image" => $tifname
            ),
            "original" => array(
                "available" => $hasoriginals,
                "map" => $originalmap,
                "image" => $originalimg,
                "tiledir" => $alttiledir
            )
        );
    }

    function getGdalInfo($filepath) {
        $cmd = "gdalinfo " . $filepath;
        $tiff_info = shell_exec($cmd . " 2>&1; echo $?");

        $tiff_info = explode("\n", $tiff_info);

        $ul = array();
        $ur = array();
        $ll = array();
        $lr = array();

        foreach ($tiff_info as $info) {
            if (has_prefix_t($info, "Center")) {
                $center = getLatLon($info);
            }
            if (has_prefix_t($info, "Upper Left")) {
                $ul = getLatLon($info);
            }
            if (has_prefix_t($info, "Upper Right")) {
                $ur = getLatLon($info);
            }
            if (has_prefix_t($info, "Lower Left")) {
                $ll = getLatLon($info);
            }
            if (has_prefix_t($info, "Lower Right")) {
                $lr = getLatLon($info);
            }
            if (has_prefix_t($info, "Pixel Size")) {
                $tiff_info["px"] = getPixelSize($info);
            }
	    if (has_prefix_t($info, "Size is")) {
                $tiff_info["size"] = getTiffSize($info);
            }


        }

        $lxtop = haversineGreatCircleDistance($ul[0], $ul[1], $ur[0], $ur[1]);
        $lxbottom = haversineGreatCircleDistance($ll[0], $ll[1], $lr[0], $lr[1]);

        $lyleft = haversineGreatCircleDistance($ul[0], $ul[1], $ll[0], $ll[1]);
        $lyright = haversineGreatCircleDistance($ur[0], $ur[1], $lr[0], $lr[1]);


        $a = $lxtop;
        $b = $lyright;
        $c = $lxbottom;
        $d = $lyleft;

        $s = ($a+$b+$c)/2;

        $area1 = sqrt(abs($s*($s-$a)*($s-$b)*($s-$c)));

        $s = ($a+$d+$c)/2;

        $area2 = sqrt(abs($s*($s-$a)*($s-$d)*($s-$c)));

	    $temp = $s*($s-$a)*($s-$b)*($s-$c);

        $area = $area1 + $area2;
        $p = $a + $b + $c + $d;

        $tiff_info["top"] = $lxtop;
        $tiff_info["bottpm"] = $lxbottom;
        $tiff_info["left"] = $lyleft;
        $tiff_info["right"] = $lyright;

        $tiff_info["center"] = $center;

        $tiff_info["s"] = round($area); //sqm
        $tiff_info["p"] = round($p); //m

        if (!array_key_exists("px",$tiff_info)) {
            $x = 0;
            $y = 0;

	    if (array_key_exists("size", $tiff_info)) {
                $x = $lxtop / $tiff_info["size"][0];
                $y = $lyright / $tiff_info["size"][1];
            }

            $tiff_info["px"] = array($x,$y);
	}

        return $tiff_info;
    }

    function genLocationFile($mapuid, $tiff_info) {

    }

    if (isset($_GET["test"])) {
        print_r(getGdalInfo("/var/www/html/maps/" . $_GET["test"]));
    }
?>
