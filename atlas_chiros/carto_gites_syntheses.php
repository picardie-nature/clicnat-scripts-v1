<?php
if (!file_exists('config.php'))
	require_once('/etc/baseobs/config.php');
else
	require_once('config.php');
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');
bobs_qm($db);

echo "nom;Type;Ref;EffMini;EffMaxi;PremiereObs;DerniereObs;Geom\n";

$sql = "SELECT 
	nom, type, reference, min(Effectif_Total) AS mini,
 	max(Effectif_Total) AS maxi,
	min(date_part('year', date_observation)) AS premiere, 
	max(date_part('year', date_observation)) AS derniere,
	count(*) AS nb_obs,
	geom 
FROM (SELECT ecom.nom, ec.reference, sum(c.nb) AS Effectif_Total,etag.v_text AS \"type\", o.date_observation, astext(ec.the_geom) AS Geom FROM observations o, citations c, espace_chiro ec, espace_commune ecom, espace_tags etag WHERE o.espace_table='espace_chiro' AND c.nb != '-1' AND ec.id_espace=o.id_espace AND c.id_observation=o.id_observation AND ec.commune_id_espace=ecom.id_espace AND etag.id_tag=413 AND etag.id_espace= ec.id_espace AND etag.espace_table='espace_chiro' GROUP BY ecom.nom, etag.v_text, ec.reference,o.date_observation, ec.the_geom ORDER BY ecom.nom, ec.reference) AS SR1 GROUP BY nom, type, reference, Geom";

$q = bobs_qm()->query($db, 'gites_chiro', $sql, []);

foreach(bobs_element::fetch_all($q) as $ligne) {
	echo $ligne['nom'].";".$ligne['type'].";".$ligne['reference'].";".$ligne['mini'].";".$ligne['maxi'].";".$ligne['premiere'].";".
		$ligne['derniere'].";".$ligne['nb_obs'].";".$ligne['geom'].
		"\n";
}
?>
