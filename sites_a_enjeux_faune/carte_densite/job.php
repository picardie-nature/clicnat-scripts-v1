<?php
require_once('/etc/baseobs/config.php');
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');
require_once(OBS_DIR.'espace.php');
require_once(OBS_DIR.'extractions.php');
require_once(OBS_DIR.'extractions-conditions.php');
require_once(OBS_DIR.'selection.php');
require_once(OBS_DIR.'utilisateur.php');

define('LAMBERT93', 2154);
define('ID_TAG_INVALIDE', 791);

$w_dir = 'resultats';

if (!file_exists($argv[1])) 
	throw new Exception('ne peut ouvrir le fichier contenant la liste des espèces');

if (!file_exists($w_dir))
	mkdir($w_dir);

$u = get_utilisateur($db, 2033);
$id_selection = $u->selection_cree_ou_vide('IO_SITE_ENJEUX_FAUNE');
$selection = new bobs_selection($db, $id_selection);

$act_retrait_invalide = new bobs_selection_enlever_avec_tag($db);
$act_retrait_invalide->set('id_selection', $selection->id_selection);
$act_retrait_invalide->set('id_tag', ID_TAG_INVALIDE);


// extraction des données
$f = fopen($argv[1], 'r');
$especes = array();
$indices = array();
while ($l = fgetcsv($f)) {
	list($id_espece, $indice) = $l;
	$especes[] = get_espece($db, $id_espece);
	$indices[$id_espece] = $indice;
}

$total = count($especes);
$n=0;
print_r($indices);
foreach ($especes as $espece) {
	$n++;
	echo "Extraction de $espece  ($n/$total)\n";
	if ($indices[$espece->id_espece] <= 0) {
		echo "\t passe indice = 0\n";
		continue;
	}
	$selection->vider();
	$extraction = new bobs_extractions($db);
	$extraction->ajouter_condition(new bobs_ext_c_espece($espece->id_espece));
	$extraction->ajouter_condition(new bobs_ext_c_interval_date('01/01/2002','31/12/2013'));
	$extraction->dans_selection($selection->id_selection);
	$esp_dir = "$w_dir/{$espece->id_espece}_{$indices[$espece->id_espece]}";
	mkdir($esp_dir);

	$act_retrait_invalide->prepare();
	try {
		$act_retrait_invalide->execute();
	} catch (LengthException $e) {
		echo "\t aucune donnée invalide retirée du traitement\n";
	}

	$selection->extract_shp($esp_dir, LAMBERT93);
}
?>
