#!/bin/bash

if [[ ! -d heatmap ]]; then
        git clone https://github.com/sethoscope/heatmap
fi

if [[ ! -d osmviz ]]; then
        git clone https://github.com/cbick/osmviz
fi

export PYTHONPATH=$PWD/osmviz/src/

if [[ ! -f upoints ]]; then
        echo "copy (select st_y(utilisateur.the_geom) as y, st_x(utilisateur.the_geom) as x from utilisateur,espace_departement where st_intersects(utilisateur.the_geom,espace_departement.the_geom) and utilisateur.the_geom is not null and espace_departement.reference in ('02','60','80')) to stdout;"|psql -h sgc clicnat pn > upoints
fi

fsortie=$(date +"observateurs_%Y%m%d.png")

if [[ -f $fsortie ]]; then
	rm $fsortie
fi

python heatmap/heatmap.py \
        --verbose \
        -p upoints \
         --osm \
        --zoom 9 \
        -o $fsortie\
        -d 0.6 \
        -m "10088ff10" \
        -M "000ffffff" \
        --osm_base "http://gpic.web-fr.org/mapproxy/wmts/basemaps_google/GLOBAL_MERCATOR/"

