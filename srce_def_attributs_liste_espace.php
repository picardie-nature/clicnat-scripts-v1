<?php
require_once('/etc/baseobs/config.php');
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');
require_once(OBS_DIR.'espace.php');
require_once(OBS_DIR.'selection.php');
require_once(OBS_DIR.'liste_espace.php');
require_once(OBS_DIR.'extractions.php');
require_once(OBS_DIR.'extractions-conditions.php');

get_db($db);

define("SELECTION", 11875);
define("LISTE_ESPACE", 115);

$tables_recherche = array('espace_point', 'espace_chiro', 'espace_line', 'espace_polygon');

$liste = new clicnat_listes_espaces($db, LISTE_ESPACE);
$selection = new bobs_selection($db, SELECTION);

foreach ($liste->get_espaces() as $espace) {
	echo "$espace {$espace->id_espace}\n";


	// Communes et départements
	$txt = "";
	$depts = array();
	foreach ($espace->get_communes() as $commune) {
		$txt .= "{$commune->nom2}, ";
		if (!array_key_exists($commune->dept, $depts)) {
			$depts[$dept] = sprintf("%02d",$commune->dept);
		}
	}
	$txt_dept = "";
	foreach ($depts as $dept) {
		$txt_dept .= "$dept, ";
	}
	$liste->espace_enregistre_attribut($espace->id_espace,"Communes",trim($txt,', '));
	$liste->espace_enregistre_attribut($espace->id_espace,"Dpt",trim($txt_dept,', '));

	// Compte nb citations znieff
	$selection->vider();
	foreach ($tables_recherche as $table_recherche) {
		$extraction = new bobs_extractions($db);
		$extraction->ajouter_condition(new bobs_ext_c_espece_det_znieff());
		$extraction->ajouter_condition(new bobs_ext_c_poly($espace->get_table(), $table_recherche, $espace->id_espace));

		$citations = $extraction->dans_un_tableau();
		$ids = array_column($citations, "id_citation");
		if (count($ids) >0) {
			echo "Extraction donne ".count($ids)." citations\n";
			$selection_ids = $selection->get_citations()->ids();
			$nouveau_ids = array_diff($ids, $selection_ids);
			if (count($nouveau_ids) > 0) {
				echo "Ajoute ".count($nouveau_ids)." citations à la sélection\n";
				$selection->ajouter_ids($nouveau_ids);
			}
		}
	}
	$n = $selection->n();
	echo "\tenregistre $n\n";
	$liste->espace_enregistre_attribut($espace->id_espace,"ZNIEFF",$n);
}
