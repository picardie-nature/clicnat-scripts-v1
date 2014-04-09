<?php
if (file_exists('config.php'))
	require_once('config.php');
else
	require_once('/etc/baseobs/config.php');

require_once(DB_INC_PHP);

require_once(OBS_DIR.'espece.php');
require_once(OBS_DIR.'selection.php');
require_once(OBS_DIR.'utilisateur.php');

$seuils_orig = array(
	'TC' => array(0,36.5,'TC'),
	'C'  => array(36.5,68.5,'C'),
	'AC' => array(68.5,84.5,'AC'),
	'PC' => array(84.5,92.5,'PC'),
	'AR' => array(92.5,96.5,'AR'),
	'R'  => array(96.5,98.5,'R'),
	'TR' => array(98.5,99.5,'TR'),
	'EX' => array(99.5,100,'EX')
);

if (count($argv) != 3) {
	echo "Usage: php indice.php id_selection nb_citations_min";
	exit(1);
}

$id_selection = $argv[1]; // numéro de la sélection qui contient les données
$nb_citations_min = $argv[2]; // nombre de citations requis pour compter une maille prospectée
$dim_maille = 5000;
$proj_maille = 2154;

get_db($db);

$selection = new bobs_selection($db, $id_selection);
echo "Sélection : {$selection} #{$selection->id_selection}\n";
// Compter le nombre de mailles avec nb_citations_min
$occupation_des_mailles = array();
foreach ($selection->get_citations() as $citation) {
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

$nombre_carres_prosp = 0;
foreach ($occupation_des_mailles as $m => $n) {
	if ($n >= $nb_citations_min) {
		$nombre_carres_prosp++;
	}
}
$total = count($occupation_des_mailles);
echo "Nombre de carrés prospectés : $nombre_carres_prosp avec seuil = $nb_citations_min ($total avec seuil = 1)\n";

// calculs des nouveaux seuils
$seuils_ponder = array();
foreach ($seuils_orig as $indice => $seuil) {
	$Rr = $seuil[0];
	$P = $nombre_carres_prosp;
	$seuils_ponder[$indice] = array();
	$seuils_ponder[$indice][0] = $Rr+$P-($Rr*$P/100);
	$Rr = $seuil[1];
	$seuils_ponder[$indice][1] = $Rr+$P-($Rr*$P/100);
}

// Evaluer chaque espèce
foreach ($selection->especes()  as $espece) {
	echo "$espece";
	flush();
	$mailles = array();
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
	echo " occupe $n_mailles\n";
}


?>
