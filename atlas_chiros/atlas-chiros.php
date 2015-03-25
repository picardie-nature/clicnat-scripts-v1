<?php
/*
 * Mise à jour de la carto des oiseaux hivernant
 *
 */
if (!file_exists('config.php'))
	require_once('/etc/baseobs/config.php');
else
	require_once('config.php');
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');
require_once(OBS_DIR.'utilisateur.php');
require_once(OBS_DIR.'espace.php');
require_once(OBS_DIR.'liste_espace.php');

get_db($db);
$annee_deb = 2009;
$annee_fin = strftime("%Y");

if (!defined('PROMONTOIRE2_ID_LISTE_CARTO_CHIROS'))
	define('PROMONTOIRE2_ID_LISTE_CARTO_CHIROS',228);
if (!defined('PROMONTOIRE2_ID_SELECTION_CARTO_CHIROS'))
	define('PROMONTOIRE2_ID_SELECTION_CARTO_CHIROS', 15580);

$liste = new clicnat_listes_espaces($db, PROMONTOIRE2_ID_LISTE_CARTO_CHIROS);

$attrs = array();
$attrs = [
	"occurrences_wintering" => [
		"name" => "occurrences_wintering",
		"type" => "int"
	],
	"occurrences_summering" => [
		"name" => "occurrences_summering",
		"type" => "int"
	],
	"species_wintering" => [
		"name" => "species_wintering",
		"type" => "int"
	],
	"species_summering" => [
		"name" => "species_summering",
		"type" => "int"
	]
];

/* vérification et création de champs */
$l_attrs = $liste->attributs();

// retir ceux qui existe déjà
foreach ($l_attrs as $l_attr) {
	if (isset($attrs[$l_attr['name']]))
		unset($attrs[$l_attr['name']]);
}


// insert le reste
foreach ($attrs as $attr) {
	echo "Création champ {$attr['name']}\n";
	$liste->attributs_def_ajout_champ($attr['name'], $attr['type'], null);
}

unset($liste);
$liste = new clicnat_listes_espaces($db, PROMONTOIRE2_ID_LISTE_CARTO_CHIROS);
$carres = $liste->get_espaces();
if ($carres->count() == 0) {
	$q = bobs_qm()->query($db, "ins_atlas_55","select distinct espace_l93_5x5.id_espace from espace_l93_5x5, espace_departement where st_intersects(espace_departement.the_geom, espace_l93_5x5.the_geom) and espace_departement.nom in ('AISNE','OISE','SOMME')",array());
	while ($r = bobs_element::fetch($q)) {
		$liste->ajouter($r['id_espace']);
	}
}


$liste = new clicnat_listes_espaces($db, PROMONTOIRE2_ID_LISTE_CARTO_CHIROS);
$carres = $liste->get_espaces();
$index_c = array();
foreach ($carres as $c) {
	$index_c[$c->nom] = $c->id_espace;
}

$pas = 5000;
$srid = 2154;
$reseau = 'cs';
$extraction = new bobs_extractions($db);
$extraction->ajouter_condition(new bobs_ext_c_reseau(get_bobs_reseau($db, $reseau)));
$extraction->ajouter_condition(new bobs_ext_c_indice_qualite(array('3','4')));
$extraction->ajouter_condition(new bobs_ext_c_sans_tag_invalide());
$extraction->ajouter_condition(new bobs_ext_c_pas_prosp_neg());

$selection = new bobs_selection($db, PROMONTOIRE2_ID_SELECTION_CARTO_CHIROS);
$selection->vider();

// estivage
$extraction->dans_selection(PROMONTOIRE2_ID_SELECTION_CARTO_CHIROS);
$selection = new bobs_selection($db, PROMONTOIRE2_ID_SELECTION_CARTO_CHIROS);
$as = new bobs_selection_enlever_ou_conserver_que_hivernage($db);
$as->set('id_selection', $selection->id_selection);
$as->set('enlever', true);
$as->prepare();
$as->execute();

$n_carres = $selection->carres_nespeces_ncitations($pas,$srid);
foreach ($n_carres as $c) {
	$nom = sprintf("E%04dN%04d", ($c['x0']*$pas)/1000, ($c['y0']*$pas)/1000);
	echo "$nom {$c['count_citation']} {$c['count_especes']}\n";
	if (isset($index_c[$nom])) {
		$liste->espace_enregistre_attribut($index_c[$nom], "occurrences_summering", $c['count_citation']);
		$liste->espace_enregistre_attribut($index_c[$nom], "species_summering", $c['count_especes']);
	} else {
		bobs_log("cartes atlas-nat. : carré $nom pas dans la liste");
	}
}


// hivernage
$selection->vider();
$extraction->dans_selection(PROMONTOIRE2_ID_SELECTION_CARTO_CHIROS);
$selection = new bobs_selection($db, PROMONTOIRE2_ID_SELECTION_CARTO_CHIROS);
$as = new bobs_selection_enlever_ou_conserver_que_hivernage($db);
$as->set('id_selection', $selection->id_selection);
$as->set('enlever', false);
$as->prepare();
$as->execute();

$n_carres = $selection->carres_nespeces_ncitations($pas,$srid);
foreach ($n_carres as $c) {
	$nom = sprintf("E%04dN%04d", ($c['x0']*$pas)/1000, ($c['y0']*$pas)/1000);
	echo "$nom {$c['count_citation']} {$c['count_especes']}\n";
	if (isset($index_c[$nom])) {
		$liste->espace_enregistre_attribut($index_c[$nom], "occurrences_wintering", $c['count_citation']);
		$liste->espace_enregistre_attribut($index_c[$nom], "species_wintering", $c['count_especes']);
	} else {
		bobs_log("cartes atlas-nat. : carré $nom pas dans la liste");
	}
}

?>
