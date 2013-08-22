#!/bin/bash
cd $1
resolution=$2
for f in $(find . -type f -name "*.shp"); do
	tif=$(echo $f|tr -s '/' '_'|sed "s/.shp/.tif/"|sed "s/^._//")
	layer=$(basename $f|sed "s/.shp//")
	echo "$f ($layer) => $tif";
	cmd="gdal_rasterize -burn 1 -l $layer -te 570000 6855000 798000 7038000 -tr $resolution $resolution $f $tif"
	$cmd
done;

