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

    function getLatLon($info) {
        $pt = explode("(", $info)[1];
        $pt = explode(")", $pt)[0];
        $pt = explode(",", $pt);

        $lon =  $pt[0];
        $lat =  $pt[1];

        return array($lat, $lon);
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

        $area1 = sqrt($s*($s-$a)*($s-$b)*($s-$c));

        $s = ($a+$d+$c)/2;
        $area2 = sqrt($s*($s-$a)*($s-$d)*($s-$c));

        $area = $area1 + $area2;
        $p = $a + $b + $c + $d;

        $tiff_info["top"] = $lxtop;
        $tiff_info["bottpm"] = $lxbottom;
        $tiff_info["left"] = $lyleft;
        $tiff_info["right"] = $lyright;

        $tiff_info["center"] = $center;

        $tiff_info["s"] = round($area); //sqm
        $tiff_info["p"] = round($p); //m


        return $tiff_info;
    }

    function genLocationFile($mapuid, $tiff_info) {

    }
?>