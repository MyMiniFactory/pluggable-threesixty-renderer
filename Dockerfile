FROM php:7.3.9-cli

ARG UNAME=worker
ARG UID=1000
ARG GID=1000

# For classic parent_image
RUN groupadd --gid $GID $UNAME && useradd --gid $GID --uid $UID $UNAME

WORKDIR /app

# Installing the diferents librairy, Povray and meshlab
RUN apt-get update && \
    apt-get -y upgrade && \
    apt-get -y autoclean && \
    apt-get -y autoremove && \
    apt-get -y install povray libpng-dev build-essential git assimp-utils && \
    docker-php-ext-install gd && \
    apt-get -y purge

RUN git clone https://github.com/MyMiniFactory/Fast-Quadric-Mesh-Simplification

WORKDIR Fast-Quadric-Mesh-Simplification
RUN make 

WORKDIR /app

# Add stl2pov folder to image
Add stl2pov-2.5.0 stl2pov-2.5.0

# Adding executable permissions to stl2pov
RUN chmod +x stl2pov-2.5.0/stl2pov

# Copy the script and the template
Copy generateTheeSixty.php generateTheeSixty.php
Copy template-h.pov template-h.pov
Copy template-v.pov template-v.pov

# Creates the tmp folder and 360 folder
RUN mkdir tmp

RUN chown -R $UNAME:$UNAME /app

USER $UNAME

ENTRYPOINT ["php", "generateTheeSixty.php"]