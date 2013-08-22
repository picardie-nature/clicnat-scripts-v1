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

define("SELECTION", 11875);
define("LISTE_ESPACE", 115);

$tables_recherche = array('espace_point', 'espace_chiro', 'espace_line', 'espace_polygon');

$liste = new clicnat_listes_espaces($db, LISTE_ESPACE);
$selection = new bobs_selection($db, SELECTION);

$conditions_de_base = array(
	new bobs_ext_c_indice_qualite(array(3,4)),
	new bobs_ext_c_pas_prosp_neg(),
	new bobs_ext_c_interval_date('30/06/2003','30/06/2013')
);

function enlever_invalides($selection) {
	$id_tag_invalide = get_config()->query_nv('/clicnat/validation/id_tag_invalide');
	$ids = $selection->id_citations_avec_tag($id_tag_invalide);
	if (count($ids) >0)
	return $selection->enlever_ids($ids);
}

$reseau_colonnes = array(
	"cs" => "Chiros",
	"ar" => "Amphrept",
	"sc" => "Orthopteres",
	"li" => "Odonates",
	"mm" => "Mammmarin",
	"mt" => "Mammterres",
	"ml" => "Mollusques",
	"av" => "Avifaune",
	"pa" => "Papillons",
	"ae" => "Araignees",
	"co" => "Coccinelle"
);

foreach ($liste->get_espaces() as $espace) {
	echo "$espace {$espace->id_espace}\n";
	// ID & Code_site
	$liste->espace_enregistre_attribut($espace->id_espace, "ID_site",$espace->nom);
	$liste->espace_enregistre_attribut($espace->id_espace, "Nom_site",$espace->reference);
	$liste->espace_enregistre_attribut($espace->id_espace, "Code_site",$espace->nom);
	
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
		foreach ($conditions_de_base as $condition) {
			$extraction->ajouter_condition($condition);
		}
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
	enlever_invalides($selection);
	$n = $selection->n();
	echo "\tenregistre $n\n";
	$liste->espace_enregistre_attribut($espace->id_espace,"ZNIEFF",$n);

	// Par réseau & ZNIEFF | AR->E | VU->CR
	$conds = array(
		new bobs_ext_c_ref_rarete("'AR','R','E'"),
		new bobs_ext_c_ref_menace("'VU','EN','CR'"),
		new bobs_ext_c_espece_det_znieff()
	);
	foreach (array_keys($reseau_colonnes) as $id_reseau) {
		$selection->vider();
		foreach ($tables_recherche as $table_recherche) {
			foreach ($conds as $condition) {
				$extraction = new bobs_extractions($db);
				$extraction->ajouter_condition($condition);
				$extraction->ajouter_condition(new bobs_ext_c_poly($espace->get_table(), $table_recherche, $espace->id_espace));
				$extraction->ajouter_condition(new bobs_ext_c_reseau(new bobs_reseau($db, $id_reseau)));
				foreach ($conditions_de_base as $condition) {
					$extraction->ajouter_condition($condition);
				}
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
		}
		enlever_invalides($selection);
		$liste->espace_enregistre_attribut($espace->id_espace, $reseau_colonnes[$id_reseau], $selection->n());

		// enregistre la liste d'especes dans un csv
		if ($selection->n() > 0) {
			$f = fopen("srce/{$espace->nom}_{$id_reseau}.csv",'w');
			$selection->liste_especes_csv($f);
			fclose($f);
		}
	}
}
