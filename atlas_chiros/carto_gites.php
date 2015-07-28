<?php
if (!file_exists('config.php'))
	require_once('/etc/baseobs/config.php');
else
	require_once('config.php');
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');

echo "nom;Type;Ref;Espece;EffMini;EffMaxi;PremiereObs;DerniereObs;Geom\n";
$sql = "SELECT ecom.nom, etag.v_text AS \"Type\", ec.reference, e.nom_f, min(c.nb) AS \"EffectifMini\", max(c.nb) AS \"EffectifMaxi\", min(date_part('year', o.date_observation)) AS \"PremiereObs\", max(date_part('year', o.date_observation)) AS \"DerniereObs\", astext(ec.the_geom) AS geom FROM observations o, citations c, espace_chiro ec, especes e, espace_commune ecom, espace_tags etag WHERE o.espace_table='espace_chiro' AND c.nb != '-1' AND ec.id_espace=o.id_espace AND c.id_observation=o.id_observation AND c.id_espece=e.id_espece AND ec.commune_id_espace=ecom.id_espace AND etag.id_tag=413 AND etag.id_espace= ec.id_espace AND etag.espace_table='espace_chiro' GROUP BY ecom.nom, etag.v_text, ec.reference, e.nom_f, ec.the_geom ORDER BY ecom.nom, ec.reference, e.nom_f";

$q = bobs_qm()->query($db, 'gites_chiro', $sql, []);
foreach(bobs_element::fetch_all($q) as $ligne) {
	echo $ligne['nom'].";".$ligne['Type'].";".$ligne['reference'].";".$ligne['nom_f'].";".$ligne['EffectifMini'].";".$ligne['EffectifMaxi'].";".
		$ligne['PremiereObs'].";".$ligne['DerniereObs'].";".$ligne['geom'].
		"\n";
}
?>
