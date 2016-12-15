<?php
/*
 * Liste des espèces pour Clicnat Elec 
 *
 */
if (!file_exists('config.php'))
	require_once('/etc/baseobs/config.php');
else
	require_once('config.php');
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');
get_db($db);

$cats = [
	"Oiseaux" => get_espece($db, 577),
	"Mammifères" => get_espece($db, 4643),
	"Coccinelles" => get_espece($db, 6707)
];
$especes = [];
foreach ($cats as $k_cat => $cat) {
	$t = [];
	foreach ($cat->taxons_descendants() as $tax) {
		if ($tax->n_citations > 20 && $tax->expert == false && $tax->exclure_restitution == false)
			$t[] = ["lib" => $tax->__toString(), "id" => $tax->id_espece];
	}
	$especes[] = ["lib" => $k_cat, "sub" => $t, "id" => $cat->id_espece];
}
echo json_encode($especes, JSON_PRETTY_PRINT);
?>
