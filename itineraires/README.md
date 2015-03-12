# Itinéraires de randonnées

Objectif : compléter avec de nouveaux attributs la carto des chemins de randonnées pour indiquer ce qui est observable

Chargement de la couche dans clicnat :

```bash
$ shp2pgsql -s 2154 -W latin1 Itineraires_Picardie.shp > itineraires.sql
```


```sql
\i itineraires.sql
insert into espace_line (nom,reference,the_geom) select nom_itiner as nom,'iti_picardie_2015:'||id_itiner as reference,st_linemerge(transform(geom,4326)) from itineraires_picardie where st_geometrytype(st_linemerge(transform(geom,4326)))='ST_LineString';
drop table itineraires_picardie;
insert into listes_espaces_data (id_liste_espace,espace_table,id_espace) select 219,'espace_line',id_espace from espace_line where reference like 'iti_picardie_2015:%';
```
