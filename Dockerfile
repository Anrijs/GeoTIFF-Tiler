#
# GeoTIFF Tiler Dockerfile
# 
# https://github.com/anrijs/geotiff-tiler
#
#

# Pull base image
FROM php:7.0-apache

COPY php.user.ini /usr/local/etc/php/conf.d/php.user.ini
COPY www/ /var/www/html

RUN a2enmod rewrite

RUN apt-get update && apt-get -y install \
  gdal-bin \
  pngnq \
  libgdal-dev \
  git \
  redis-server \
  python \
  python-dev \
  python-redis \
  python-pip \
  imagemagick

WORKDIR /var/www/html/

WORKDIR /app
RUN git clone https://github.com/vss-devel/tilers-tools.git

ADD /requirements.txt /app/requirements.txt

RUN export PATH=$PATH:/app/tilers-tools

ENV CPLUS_INCLUDE_PATH=$CPLUS_INCLUDE_PATH:/usr/include/gdal
ENV C_INCLUDE_PATH=/usr/include/gdal

RUN pip install -r requirements.txt

# ImageMagick policy
COPY imagemagick.policy.xml /etc/ImageMagick-6/policy.xml

#ports and volumes
EXPOSE 80
VOLUME /var/www/html/maps /var/www/html/layers /var/www/html/tmp

ENTRYPOINT  service apache2 start && nohup redis-server