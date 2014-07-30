<?php
require_once('/etc/baseobs/config.php');
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');
require_once(OBS_DIR.'espace.php');
require_once(OBS_DIR.'selection.php');
require_once(OBS_DIR.'liste_espace.php');
require_once(OBS_DIR.'extractions.php');
require_once(OBS_DIR.'extractions-conditions.php');
require_once(OBS_DIR.'utilisateur.php');

get_db($db);

define("LISTE_ESPACE", 219);
define("TAMPON", 200);

$tables_recherche = array('espace_point', 'espace_chiro', 'espace_line', 'espace_polygon');
$id_tag_invalide = get_config()->query_nv('/clicnat/validation/id_tag_invalide');

$conditions_de_base = array(
	new bobs_ext_c_indice_qualite(array(3,4)),
	new bobs_ext_c_pas_prosp_neg(),
	new bobs_ext_c_interval_date('01/01/2004','31/12/2014'),
	new bobs_ext_c_sans_tag($id_tag_invalide)
);

$liste = new clicnat_listes_espaces($db, LISTE_ESPACE);

function classement_liste_espece($db, $liste) {
	$l_categories = array(
		// oiseaux
		'Guêpiers, rolliers',
		'Coucous, pigeons, tourterelles',
		'Rapaces diurnes',
		'Faisans, perdrix, râles',
		"Oiseaux d'eau",
		'Passereaux',
		'Grands échassiers',
		'Pics',
		'Martinets',
		'Engoulevents',
		'Petits échassiers (limicoles), mouettes, goélands...',
		'Perroquets',
		'Rapaces nocturnes',
		// mammifères
		'Carnivores',
		'Cétacés',
		'Chiroptères',
		'Insectivores',
		'Lagomorphes',
		'Ongulés',
		'Pinnipèdes',
		'Rongeurs'
	);
	$categories = array();
	foreach ($l_categories as $c) {
		$categories[$c] = 0;
	}
	
	$classe_o = new bobs_classe($db, 'O');
	$oiseaux = array();
	foreach ($classe_o->liste_especes_nom_simple() as $o) {
		$oiseaux[$o['id_espece']] = $o;
	}
	foreach ($liste as $id_espece) {
		$espece = get_espece($db, $id_espece);
		$k = false;
		switch ($espece->classe) {
			case 'O': // les oiseaux
				$k = $oiseaux[$espece->id_espece]['nom_simple'];
				break;
			case 'M': // les mammifères
				$k = $espece->ordre;
				break;
		}
		if ($k && isset($categories[$k]))
			$categories[$k]++;

	}
	return $categories;
}

foreach ($liste->get_espaces() as $espace) {
	echo $espace."\n";
	$n_citations = 0;
	$especes = array();
	foreach ($tables_recherche as $table_recherche) {
		$extraction = new bobs_extractions($db);
		foreach ($conditions_de_base as $condition) {
			$extraction->ajouter_condition($condition);
		}
		$extraction->ajouter_condition(new bobs_ext_c_poly_tampon($espace->get_table(), $table_recherche, $espace->id_espace, TAMPON));
		echo "\t$table_recherche ".TAMPON."m ";
		flush();
		$citations = $extraction->dans_un_tableau();
		$n_citations += count($citations);
		foreach ($citations as $citation) {
			$c = get_citation($db, $citation['id_citation']);
			if (isset($especes[$c->id_espece])) 
				$especes[$c->id_espece]['total']++;
			else
				$especes[$c->id_espece]['total'] = 1;
			$annee = strftime("%Y", strtotime($citation['date_observation']));
			if (isset($especes[$c->id_espece]['annees'][$annee]))
				$especes[$c->id_espece]['annees'][$annee]++;
			else
				$especes[$c->id_espece]['annees'][$annee] = 1;
		}
		echo count($citations)." citations\n";
	}

	// vues sur 3 années
	$especes_3y = array();
	foreach ($especes as $id_espece=>$espece) {
		if (count($espece['annees']) >= 3)
			$especes_3y[] = $id_espece;
	}

	$liste->espace_enregistre_attribut($espace->id_espace, "n_citations", $n_citations);
	$liste->espace_enregistre_attribut($espace->id_espace, "n_especes", count($especes));
	$liste->espace_enregistre_attribut($espace->id_espace, "n_especes_3y", count($especes_3y));

	$cl = classement_liste_espece($db, $especes_3y);
	foreach ($cl as $k => $n) {
		$liste->espace_enregistre_attribut($espace->id_espace, $k, $n);
	}

}
?>
