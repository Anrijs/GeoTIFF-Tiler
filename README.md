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
Build docker:
`docker build . -t geotiff-tiler`

Run docker:
`docker run -p <port>:80 -v $(pwd)/www:/var/www/html/ -it  geotiff-tiler`
<port> is port which will be used to access web interface.

## Usage
Open localhost:<port> in web browser

Add new layer under **Layers** tab
Upload new GeoTIFF or Ozi map map under **Maps** tab
Add uploaded map to any layer
Follow active jobs in **Dashboard** tab
Preview map under **Map** tab
