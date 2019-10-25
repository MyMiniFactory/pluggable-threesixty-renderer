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
    apt-get -y purge && \
    git clone https://github.com/MyMiniFactory/Fast-Quadric-Mesh-Simplification && \
    make -C Fast-Quadric-Mesh-Simplification/ && \
    cp Fast-Quadric-Mesh-Simplification/a.out a.out && \
    rm -r Fast-Quadric-Mesh-Simplification

# Copy the script and the template
Copy generateTheeSixty.php generateTheeSixty.php 
Copy template-h.pov template-h.pov 
Copy template-v.pov template-v.pov 
Copy stl2pov-2.5.0/stl2pov stl2pov

# Creates the tmp folder and 360 folder
RUN mkdir tmp && \
    chown -R $UNAME:$UNAME /app && \
    chmod +x stl2pov

USER $UNAME

ENTRYPOINT ["php", "generateTheeSixty.php"]