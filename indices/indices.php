<?php
if (file_exists('config.php'))
	require_once('config.php');
else
	require_once('/etc/baseobs/config.php');
echo "init\n";
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');
require_once(OBS_DIR.'selection.php');
require_once(OBS_DIR.'utilisateur.php');

$seuils_orig = [
	'TC' => [0,36.5,'TC'],
	'C'  => [36.5,68.5,'C'],
	'AC' => [68.5,84.5,'AC'],
	'PC' => [84.5,92.5,'PC'],
	'AR' => [92.5,96.5,'AR'],
	'R'  => [96.5,98.5,'R'],
	'TR' => [98.5,99.5,'TR'],
	'EX' => [99.5,100,'EX']
];

if (count($argv) != 4) {
	echo "Usage: php indice.php id_selection dim_maille fichier_csv";
	exit(1);
}
$id_selection = $argv[1]; // numéro de la sélection qui contient les données
$nb_citations_mins = [ // nombre de citations requis pour compter une maille prospectée
	"Indice1c" => ['n_min' => 1, 'n_prosp' => 0, 'seuils' => null],
	"Indice3c" => ['n_min' => 3, 'n_prosp' => 0, 'seuils' => null],
	"Indice5c" => ['n_min' => 5, 'n_prosp' => 0, 'seuils' => null],
	"Indice7c" => ['n_min' => 7, 'n_prosp' => 0, 'seuils' => null],
	"Indice10c" => ['n_min' => 10, 'n_prosp' => 0, 'seuils' => null]
];
$dim_maille = $argv[2];
$proj_maille = 2154;
get_db($db);

$selection = new bobs_selection($db, $id_selection);

echo "Sélection : {$selection} #{$selection->id_selection}\n";
// Compter le nombre de mailles avec nb_citations_min
$occupation_des_mailles = [];
echo "Compte le nombre de mailles\n";

$n = 0;
$ntotal = $selection->n();
foreach ($selection->get_citations() as $citation) {
	$n++;
	echo "\r$n/$ntotal citations";
	flush();
	$observation = $citation->get_observation();
	$mailles = $observation->get_espace()->get_index_atlas_repartition($proj_maille, $dim_maille);
	foreach ($mailles as $m) {
		$k = "{$m['x0']}.{$m['y0']}";
		if (isset($occupation_des_mailles[$k]))
			$occupation_des_mailles[$k]++;
		else
			$occupation_des_mailles[$k] = 1;
	}
}
$csv = fopen($argv[3],"w");
$nombre_carres_prosp = 0;
foreach ($nb_citations_mins as $k => $t) {
	foreach ($occupation_des_mailles as $m => $n) {
		if ($n >= $nb_citations_mins[$k]['n_min']) {
			$nb_citations_mins[$k]['n_prosp']++;
		}
	}
	fwrite($csv, "Nombre de carrés prospectés : $nombre_carres_prosp avec seuil = {$nb_citations_mins[$k]['n_min']}\n");
}

// Déterminer l'emprise de l'extraction (C)
$depts = [];
$extraction = bobs_extractions::charge_xml($db, $selection->extraction_xml);
foreach ($extraction->conditions as $condition) {
	fwrite($csv, $condition."\n");
	if (get_class($condition) == 'bobs_ext_c_departement') {
		$depts[] = $condition->id_espace;
	}
}
$in = '';
foreach ($depts as $dept) {
	$in .= $dept.',';
}
$in = trim($in, ',');
$sql_carres_couverture = "select count(*) from clicnat_carre_atlas($proj_maille,$dim_maille,(select st_union(the_geom) from espace_departement where id_espace in ($in))) as t";

$q = bobs_qm()->query($db,'carres', $sql_carres_couverture, array());
$r = bobs_element::fetch($q);
$C = $r['count'];
fwrite($csv, "$C nombre de carrés total de l'extraction. ");
$cols_indice_csv = "";
foreach ($nb_citations_mins as $k => $t) {
	$nb_citations_mins[$k]['taux'] = $nb_citations_mins[$k]['n_prosp']/$C;
	fwrite($csv, "Taux de prospection (n_cit_min) = {$nb_citations_mins[$k]['n_min']} : {$nb_citations_mins[$k]['taux']}\n");

	// calculs des nouveaux seuils
	$seuils_ponder = [];
	foreach ($seuils_orig as $indice => $seuil) {
		$Rr = $seuil[0];
		$P = 100*($C-$nb_citations_mins[$k]['n_prosp'])/$C;
		$seuils_ponder[$indice] = [];
		$seuils_ponder[$indice][0] = $indice=='TC'?0:$Rr+$P-($Rr*$P/100);
		$Rr = $seuil[1];
		$seuils_ponder[$indice][1] = $Rr+$P-($Rr*$P/100);

		fwrite($csv, "$k,{$seuils_ponder[$indice][0]},$indice,{$seuils_ponder[$indice][1]}\n");

	}
	$nb_citations_mins[$k]['seuils'] = $seuils_ponder;
	$cols_indice_csv .= "$k,";
}

fwrite($csv,"ID,Nom_f,Nom_s,Mailles_OQP,Rr,RRpond,$cols_indice_csv\n");  //entête CSV
// Evaluer chaque espèce
foreach ($selection->especes() as $espece) {
	echo "traitement de $espece\n";
	fwrite($csv, "$espece->id_espece,");
	fwrite($csv, str_replace(",","",$espece->nom_f).","); //pour virer les , dans les noms d'especes
	fwrite($csv, str_replace(",","",$espece->nom_s).",");
	$mailles = [];
	foreach ($selection->get_citations() as $citation) {
		if ($citation->id_espece != $espece->id_espece) 
			continue;
		foreach ($citation->get_observation()->get_espace()->get_index_atlas_repartition($proj_maille, $dim_maille) as $m) {
			$k = "{$m['x0']}.{$m['y0']}";
			if (isset($mailles[$k])) 
				continue;
			$mailles[$k] = 1;
		}
	}
	$n_mailles = count($mailles);
	fwrite($csv, "$n_mailles,");
	$Rr_esp = 100 - 100 * ($n_mailles/$C);
	$Rr_espPond = $Rr_esp-($P-($Rr_esp*$P/100)); //Pour comparaison entre différent lots de données
	fwrite($csv, "$Rr_esp,");
	fwrite($csv, "$Rr_espPond,");

	foreach ($nb_citations_mins as $k => $t) {
		foreach ($nb_citations_mins[$k]['seuils'] as $seuil => $vals) {
			if ($Rr_esp >= $vals[0] && $Rr_esp < $vals[1]) {
				fwrite($csv,"$seuil,");
				break;
			}
		}
	}

	fwrite($csv, "\n");
}
?>
