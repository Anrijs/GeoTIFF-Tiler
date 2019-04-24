<?php
    include "config/config.php";

    $mapname = $_POST['mname'];
    $layername = $_POST['lname'];

    $mapuid = $_POST['muid'];
    $layeruid = $_POST['luid'];

    $layerzooms = file_get_contents($_R['layers'] . $layeruid . "/zoom");
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


    $cmd = "python import.py " . $_R['maps'] . $mapuid . " " . $_R['layers'] . $layeruid . " redis " . $mapuid . ' "' . $mapuid . " -> " . $layeruid . '"' . " -keep -z " . $layerzooms; 
    shell_exec($cmd . ' > /dev/null 2>/dev/null &');
    file_put_contents($_R["layers"] . $layeruid . "/maps", $mapuid . ";" . PHP_EOL , FILE_APPEND | LOCK_EX);
    header("Location: index.php");
    
    // echo $cmd;
    // os run "python import.py maps/{uid} layers/{uid} redis {mapid} {mapname}->{layername}"

?>
