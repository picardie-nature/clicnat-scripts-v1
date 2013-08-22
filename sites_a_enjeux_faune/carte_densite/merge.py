#!/usr/bin/python
# -*- coding: utf8 -*-

from osgeo import gdal,ogr
from osgeo.gdalconst import *

import sys
import os
from glob import glob
import re
import numpy 

def debug(m):
	sys.stderr.write("info: "+m+"\n")


os.chdir(sys.argv[1])


if os.path.exists("merge.tif"):
	debug("a supprimer fichier précédent");
	os.unlink("merge.tif")

fichiers = glob("*.tif");
fichiers.sort()

driver = gdal.GetDriverByName("GTiff")
src = gdal.Open(fichiers.pop())

raster = driver.CreateCopy("merge.tif", src, 0)
tmp = driver.CreateCopy("tmp.tif", src, 0)

buff_out = raster.GetRasterBand(1).ReadAsArray(0,0,src.RasterXSize,src.RasterYSize)
buff_tmp = raster.GetRasterBand(1).ReadAsArray(0,0,src.RasterXSize,src.RasterYSize)

buff_out[numpy.where(buff_out != 0)] = 0
buff_tmp[numpy.where(buff_tmp != 0)] = 0

src = None
prev_id_espece = 0
indice = 0

def ajoute(buff_tmp, buff_out, indice):
	buff_tmp[numpy.where(buff_tmp > 0)] = 1
	buff_tmp *= indice
	buff_out += buff_tmp

for fichier in fichiers:
	print fichier
	r = re.search('(\d+)_(\d+)_espace_(\w+)\.tif', fichier)
	id_espece=r.group(1)
	indice=int(r.group(2))

	if prev_id_espece != id_espece:
		if prev_id_espece != 0:
			ajoute(buff_tmp, buff_out, indice)

		buff_tmp[numpy.where(buff_tmp != 0)] = 0
		prev_id_espece = id_espece
	

	src = gdal.Open(fichier)
	buff_in = src.GetRasterBand(1).ReadAsArray(0,0,src.RasterXSize,src.RasterYSize)

	buff_tmp += buff_in	

# derniere iteration
ajoute(buff_tmp, buff_out, indice)

# resultat
raster.GetRasterBand(1).WriteArray(buff_out)
raster.GetRasterBand(1).ComputeStatistics(False)

reaster = None

sys.exit(0)
