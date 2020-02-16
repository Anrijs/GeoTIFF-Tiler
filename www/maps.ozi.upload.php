<?php
include "config/config.php";
include "lib/tifftools.php";

function has_prefix($string, $prefix) {
    return ((substr($string, 0, strlen($prefix)) == $prefix) ? true : false);
}

function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

// get name
// get .map path
// get .png path

$mapFname = basename($_FILES["mapFileToUpload"]["name"]);
$imgFname = basename($_FILES["imgFileToUpload"]["name"]);
$tifFname = str_replace(".map", ".tif", $mapFname);

// TODO:
// check if image is image
// check if map is map


$dir = uniqid();
while(file_exists($_R["maps"] . $dir)) {
  $dir = uniqid();
}

// make map directory
$target_dir = $_R["maps"] . $dir . "/";
if(mkdir($target_dir) == 0) {
    die("Failed to upload map. Maps directory (".$_R["maps"].") not writeable.");
    return;
}

// make originals dir
$original_dir = $target_dir . "original/";
if(mkdir($original_dir) == 0) {
    die("Failed to upload map. Map directory (".$target_dir.") not writeable.");
    return;
}

$mapPath = $original_dir . $mapFname;
$imgPath = $original_dir . $imgFname;

move_uploaded_file($_FILES["mapFileToUpload"]["tmp_name"], $mapPath);
move_uploaded_file($_FILES["imgFileToUpload"]["tmp_name"], $imgPath);

$mapname = $_POST['mapName'];
$mktif = isset($_POST['makeTiff']);

if (strlen(trim($mapname)) == 0) {
    $mapname = $tifFname;
}

$target_file = $target_dir . $tifFname;

$namefile = fopen($target_dir . "name", "w");
fwrite($namefile, $mapname);
fclose($namefile);

# make png preview
$target_file_512_png = str_replace(".map", ".512.png", $mapFname);
$target_file_64_png = str_replace(".map", ".64.png", $mapFname);
$cmd = "convert \"" . $imgPath . "\" -resize \"64^>\" \"" . $target_dir . $target_file_64_png . "\"";
$cmd .= " && convert \"" . $imgPath . "\" -resize \"512^>\" \"" . $target_dir . $target_file_512_png . "\"";
shell_exec($cmd . ' > /dev/null 2>/dev/null &');

$coordfile = fopen($target_dir . "coord", "w");
$infofile = fopen($target_dir . "info", "w");


if ($mktif) {
    $cmd = "gdal_translate -of GTiff " . $mapPath . " " . $target_file;
    shell_exec($cmd . ' > /dev/null 2>/dev/null &');

    // TODO:
    // run as subprocess
    sleep(2);

    // GeoTIFF info
    $tiff_info = getGdalInfo($target_file);
    fwrite($coordfile, $tiff_info["center"][0] . "," . $tiff_info["center"][1]);
    fwrite($infofile, $tiff_info["p"] . "," . $tiff_info["s"]); // preimeter, area
}

fclose($coordfile);
fclose($infofile);

header("Location: maps.php");



?>
