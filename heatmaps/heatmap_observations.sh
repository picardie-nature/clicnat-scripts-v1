#!/bin/bash

if [[ ! -d heatmap ]]; then
        git clone https://github.com/sethoscope/heatmap
fi

if [[ ! -d osmviz ]]; then
        git clone https://github.com/cbick/osmviz
fi

export PYTHONPATH=$PWD/osmviz/src/

if [[ ! -f points ]]; then
	where="observations.id_observation between 404973 and 473898"
        echo "copy (select st_y(espace_point.the_geom) as y, st_x(espace_point.the_geom) as x from observations,espace_point,espace_departement where st_intersects(espace_point.the_geom,espace_departement.the_geom) and espace_departement.reference in ('02','60','80') and $where and observations.id_espace=espace_point.id_espace) to stdout;"|psql -h sgc clicnat pn > points
        echo "copy (select st_y(espace_chiro.the_geom) as y, st_x(espace_chiro.the_geom) as x from observations,espace_chiro,espace_departement where st_intersects(espace_chiro.the_geom,espace_departement.the_geom) and espace_departement.reference in ('02','60','80') and $where and observations.id_espace=espace_chiro.id_espace) to stdout;"|psql -h sgc clicnat pn >> points
	cat points|sort -u > points_tmp
	rm points; mv points_tmp points
else
	echo "Conserve le fichier de points déjà existant"
fi


fsortie=$(date +"observations_%Y%m%d.png")

if [[ -f $fsortie ]]; then
	rm $fsortie
fi

python heatmap/heatmap.py \
        --verbose \
        -p points \
         --osm \
        --zoom 9 \
        -o $fsortie\
        -d 0.6 \
        -m "10088ff10" \
        -M "000ffffff" \
        --osm_base "http://gpic.web-fr.org/mapproxy/wmts/basemaps_google/GLOBAL_MERCATOR/"

