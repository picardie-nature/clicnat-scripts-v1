#!/bin/bash

for sel in $(cat selections.csv); do
	id=$(echo $sel|cut -d . -f 1)
	y=$(echo $sel|cut -d . -f 2)
	echo "id=$id annee=$y"
	php filtre_structures.php $id
	php dump.php $id $y noupdate
done
