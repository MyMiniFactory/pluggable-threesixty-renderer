FROM php:7.3.9-cli

WORKDIR /app

# Installing the diferents librairy, Povray and meshlab
RUN apt-get update && \
    apt-get -y upgrade && \
    apt-get -y autoclean && \
    apt-get -y autoremove && \
    apt-get -y install povray libpng-dev build-essential git assimp-utils optipng && \
    docker-php-ext-install gd && \
    apt-get -y purge && \
    git clone https://github.com/MyMiniFactory/Fast-Quadric-Mesh-Simplification && \
    make -C Fast-Quadric-Mesh-Simplification/ && \
    cp Fast-Quadric-Mesh-Simplification/a.out a.out && \
    rm -r Fast-Quadric-Mesh-Simplification && \
    git clone https://github.com/timschmidt/stl2pov && \
    make -C stl2pov/ && \
    cp stl2pov/stl2pov stl2povcompiled && \
    rm -r stl2pov && \
    mv stl2povcompiled stl2pov

# Copy the script and the template
Copy generateTheeSixty.php generateTheeSixty.php 
Copy template-h.pov template-h.pov 
Copy template-v.pov template-v.pov

# Creates the tmp folder and 360 folder
RUN mkdir tmp && \
    chmod +x stl2pov

ENTRYPOINT ["php", "generateTheeSixty.php"]