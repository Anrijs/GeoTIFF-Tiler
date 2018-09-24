<?php
include "config/config.php";

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

$tmp_dir = uniqid();
while(file_exists($_R["temp"] . $tmp_dir)) {
  $tmp_dir = uniqid();
}

$tmp_dir = $_R["temp"] . $tmp_dir . "/";

if(mkdir($tmp_dir) == 0) {
    die("Failed to upload map. Temp directory (".$_R["temp"].") not writeable.");
}


$mapPath = $tmp_dir . $mapFname;
$imgPath = $tmp_dir . $imgFname;

move_uploaded_file($_FILES["mapFileToUpload"]["tmp_name"], $mapPath);
move_uploaded_file($_FILES["imgFileToUpload"]["tmp_name"], $imgPath);

// TODO:
// check if image is image
// check if map is map


$dir = uniqid();
while(file_exists($_R["maps"] . $dir)) {
  $dir = uniqid();
}

$target_dir = $_R["maps"] . $dir . "/";

$mapname = $_POST['mapName'];

// make map directory
if(mkdir($target_dir) == 0) {
    echo "Failed to upload map. Maps directory (".$_R["maps"].") not writeable.";
}

$target_file = $target_dir . $tifFname;
$cmd = "gdal_translate -of GTiff " . $mapPath . " " . $target_file;
shell_exec($cmd . ' > /dev/null 2>/dev/null &');

// TODO:
// run as subprocess
sleep(2);

deleteDir($tmp_dir);

$namefile = fopen($target_dir . "name", "w");
fwrite($namefile, $mapname);
fclose($namefile);

# make png preview
$target_file_512_png = str_replace(".tif", ".512.png", $target_file);
$target_file_64_png = str_replace(".tif", ".64.png", $target_file);
$cmd = "convert " . $target_file . " -resize \"64^>\" " . $target_file_64_png;
shell_exec($cmd);
$cmd = "convert " . $target_file . " -resize \"512^>\" " . $target_file_512_png;
shell_exec($cmd . ' > /dev/null 2>/dev/null &');

// GeoTIFF center
$cmd = "gdalinfo " . $target_file . " ";
$tiff_info = shell_exec($cmd . " 2>&1; echo $?");

$tiff_info = explode("\n", $tiff_info);
foreach ($tiff_info as $info) {
    if (has_prefix($info, "Center")) {
        $pt = explode("(", $info)[1];
        $pt = explode(")", $pt)[0];
        $pt = explode(",", $pt);

        $lon =  trim($pt[0]);
        $lat =  trim($pt[1]);

        $coordfile = fopen($target_dir . "coord", "w");
        fwrite($coordfile, $lat . "," . $lon);
        fclose($coordfile);
    }
}

header("Location: maps.php");



?>
