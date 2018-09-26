# GeoTIFF Tiler
Upload GeoTIFF images or Ozi calibrated maps and slice them to tiles.

## About
This tool tool allows to easily create TMS tiles from calibrated map images and allows to merge multiple maps in one map (TMS tileset).

## Installation
Make sure directories in `www` directory `maps`, `layers`, `logs` and `tmp` are writeable:
``` bash
chmod 777 www/maps
chmod 777 www/layers
chmod 777 www/logs
chmod 777 www/tmp
```

## Run in Docker
Mount theese:  
Uploaded maps dir: `/var/www/html/maps`  
Map layer tiles dir: `/var/www/html/layers`  
Temporary files dir: `/var/www/html/tmp`  

### Download from Docker hub
``` bash
docker run anrijs/geotiff-tiler \
    --name=geotiff-tiler \
    -v </path/to/maps>/www:/var/www/html/maps \
    -v </path/to/layers>/www:/var/www/html/layers \
    -v </path/to/temp>/www:/var/www/html/tmp \
    -p <port>:80
```
### or build Docker
`docker build . -t geotiff-tiler`

``` bash
docker run geotiff-tiler \
    --name=geotiff-tiler \
    -v </path/to/maps>/www:/var/www/html/maps \
    -v </path/to/layers>/www:/var/www/html/layers \
    -v </path/to/temp>/www:/var/www/html/tmp \
    -p <port>:80
```

## Usage
Open localhost:\<port\> in web browser  
Add new layer under **Layers** tab  
Upload new GeoTIFF or Ozi map map under **Maps** tab  
Add uploaded map to any layer  
Follow active jobs in **Dashboard** tab  
Preview map under **Map** tab

## Screenshots
### Map list
![Map list](https://raw.githubusercontent.com/Anrijs/GeoTIFF-Tiler/master/docs/1.png)

### Dashboard
![Dashboard](https://raw.githubusercontent.com/Anrijs/GeoTIFF-Tiler/master/docs/2.png)

### Map view with merged maps
![Map view](https://raw.githubusercontent.com/Anrijs/GeoTIFF-Tiler/master/docs/3.gif)
