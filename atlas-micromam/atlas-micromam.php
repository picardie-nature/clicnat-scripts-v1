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
require_once(OBS_DIR.'reseau.php');

get_db($db);
$annee_deb = 2008;
$annee_fin = strftime("%Y");

if (!defined('PROMONTOIRE2_ID_LISTE_CARTO_UMAM'))
	define('PROMONTOIRE2_ID_LISTE_CARTO_UMAM',366);
if (!defined('PROMONTOIRE2_ID_LISTE_ESP_UMAM'))
	define('PROMONTOIRE2_ID_LISTE_ESP_UMAM', 514);
if (!defined('PROMONTOIRE2_ID_SELECTION_CARTO_UMAM'))
	define('PROMONTOIRE2_ID_SELECTION_CARTO_UMAM', 20543);

$liste = new clicnat_listes_espaces($db, PROMONTOIRE2_ID_LISTE_CARTO_UMAM);

$attrs = [
	"occurrences" => [
		"name" => "occurrences",
		"type" => "int"
	],
	"species" => [
		"name" => "species",
		"type" => "int"
	],
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
$liste = new clicnat_listes_espaces($db, PROMONTOIRE2_ID_LISTE_CARTO_UMAM);
$carres = $liste->get_espaces();
if ($carres->count() == 0) {
	$q = bobs_qm()->query($db, "ins_atlas_55","select distinct espace_l93_5x5.id_espace from espace_l93_5x5, espace_departement where st_intersects(espace_departement.the_geom, espace_l93_5x5.the_geom) and espace_departement.nom in ('AISNE','OISE','SOMME')",array());
	while ($r = bobs_element::fetch($q)) {
		$liste->ajouter($r['id_espace']);
	}
}


$liste = new clicnat_listes_espaces($db, PROMONTOIRE2_ID_LISTE_CARTO_UMAM);
$carres = $liste->get_espaces();
$index_c = array();
foreach ($carres as $c) {
	$index_c[$c->nom] = $c->id_espace;
}

$pas = 5000;
$srid = 2154;

$extraction = new bobs_extractions($db);
$extraction->ajouter_condition(new bobs_ext_c_liste_especes(PROMONTOIRE2_ID_LISTE_ESP_UMAM));
$extraction->ajouter_condition(new bobs_ext_c_indice_qualite(array('3','4')));
$extraction->ajouter_condition(new bobs_ext_c_sans_tag_invalide());
$extraction->ajouter_condition(new bobs_ext_c_pas_prosp_neg());

$selection = new bobs_selection($db,PROMONTOIRE2_ID_SELECTION_CARTO_UMAM);
$selection->vider();
$extraction->dans_selection($selection->id_selection);

$n_carres = $selection->carres_nespeces_ncitations($pas,$srid);
foreach ($n_carres as $c) {
	$nom = sprintf("E%04dN%04d", ($c['x0']*$pas)/1000, ($c['y0']*$pas)/1000);
	echo "$nom {$c['count_citation']} {$c['count_especes']}\n";
	if (isset($index_c[$nom])) {
		$liste->espace_enregistre_attribut($index_c[$nom], "occurrences", $c['count_citation']);
		$liste->espace_enregistre_attribut($index_c[$nom], "species", $c['count_especes']);
	} else {
		bobs_log("cartes atlas-nat. : carré $nom pas dans la liste");
	}
}
?>
