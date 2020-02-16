<?php
    include "config/config.php";
    include "lib/tifftools.php";

    $mapname = $_POST['mname'];
    $layername = $_POST['lname'];

    $mapuid = $_POST['muid'];
    $mapext = isset($_POST['mapext']);

    $layeruid = $_POST['luid'];

    if ($layeruid != "-1") {
        $layerzooms = file_get_contents($_R['layers'] . $layeruid . "/zoom");
    } else {
        $layerzooms = $_POST['zmin'] . "-" . $_POST['zmax'];
    }
    $zooms = explode("-", $layerzooms);
    if (sizeof($zooms) != 2) {
        die("Error: invalid layer zoom file.");
    }
    $zmin = intval($zooms[0]);
    $zmax = intval($zooms[1]);

    if($zmin > $zmax) {
        $tempmin = $zmin;
        $zmin = $zmax;
        $zmax = $tempmin;
    }

    $layerzooms = $zmin;
    while ($zmin < $zmax) {
        $zmin++;
        $layerzooms .= "," . $zmin;
    }

    $params = " -keep -z " . $layerzooms;
    if($layeruid == "-1") {
        $params .= " -noimport";
    }

    if($mapext) {
        $params .= " -mapext";
    }

    $mapdir = $_R['maps'] . $mapuid;
    $mapInfo = getMapInfo($mapdir);

    $hastif = $mapInfo["tif"]["available"];
    $hasmap = $mapInfo["original"]["available"];

    if (!$hastif && !$hasmap) {
        die("Unable to slice. No valid images found.");
        return;
    }

    if (!$hastif) {
        $mapdir .= "/original";
    }

    $cmd = "python import.py " . $mapdir . " " . $_R['layers'] . $layeruid . " " . $params . " redis " . $mapuid . ' "' . $mapuid . " -> " . $layeruid . '"';
    
    shell_exec($cmd . ' > /dev/null 2>/dev/null &');

    if ($layeruid != "-1") {
        file_put_contents($_R["layers"] . $layeruid . "/maps", $mapuid . ";" . PHP_EOL , FILE_APPEND | LOCK_EX);
    }
    header("Location: index.php");

    //echo $cmd;
    // os run "python import.py maps/{uid} layers/{uid} redis {mapid} {mapname}->{layername}"

?>
