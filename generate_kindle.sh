#!/bin/sh
#set -x
cd "$(dirname "$0")"

# generate SVG charts from Prometheus data
./generate_charts.php

# replace markers with current values
./generate_kindle.php

# convert the SVG to PNG
rsvg-convert --background-color=white -o kindle.png kindle.svg

# crush & grayscale it
pngcrush -q -c 0 -ow kindle.png

# push it to web server's docroot (mv is atomic)
mv -f kindle.png /var/www/kindle/
