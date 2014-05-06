<?php
require_once('/etc/baseobs/config.php');
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espace.php');
require_once(OBS_DIR.'espece.php');
get_db($db);

require_once("config_client.php");

if (!defined("URL_CLIENT")) {
	echo "URL_CLIENT doit être définit dans le fichier config_client.php";
}


$departements = array(80,60,2);

function envoi($url, $data) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	return curl_exec($ch);
}

function msg($m) {
	echo "$m ";
	flush();
}

function test_retour($data) {
	$r = trim($data, "\n");
	if ( $r == "OK") 
		return true;
	echo $r."\n";
	return false;
}

echo "URL : ".URL_CLIENT."\n";

foreach (bobs_espece::get_classes() as $classe) {
	msg("Classe $classe");
	$data = array(
		'contenu' => 'especes',
		'especes' => array(),
		'classe' => $classe
	);
	foreach (bobs_espece::get_liste_par_classe($db, $classe) as $espece) {
		$rr = $espece->get_referentiel_regional();
		$cd_ref = null;
		if ($espece->taxref_inpn_especes) {
			$inpn = new bobs_espece_inpn($db, $espece->taxref_inpn_especes);
			$cd_ref = $inpn->cd_ref;
		}
		$data_esp = array(
			"id_espece" => $espece->id_espece,
			"nom_f" => $espece->nom_f,
			"nom_s" => $espece->nom_s,
			"ordre" => $espece->ordre,
			"famille" => $espece->famille,
			"cd_nom" => $espece->taxref_inpn_especes,
			"cd_ref" => $cd_ref,
			"determinant_znieff" => $espece->determinant_znieff,
			"commentaire_statut_menace" => $espece->commentaire_statut_menace,
			"commentaire_repartition" => $espece->commentaire,
			"action_conservation" => $espece->action_conservation,
			"menace" => $espece->menace,
			"habitat" => $espece->habitat,
			"ref_menace" => $rr['categorie'],
			"ref_rarete" => $rr['indice_rar'],
			"ref_etat_conv" => $rr['etat_conv'],
			"ref_prio_conv_cat" => $rr['prio_conv_cat'],
			"ref_statut_bio" => $rr['statut_bio'],
			"ref_statut_origine" => $rr['statut_origine'],
			"ref_niveau_connaissance" => $rr['niveau_con']
		);
		$data['especes'][] = $data_esp;
	}
	msg(count($data['especes']));
	msg("a envoyer");
	$result = envoi(URL_CLIENT, $data);
	if (test_retour($result)) echo "ok\n";
	else echo "ERREUR\n";
}
echo "commit especes";
flush();
$data = array('contenu' => 'especes_commit');
echo envoi(URL_CLIENT, $data);

//todo terminer la partie liste espèces/commune qui utilise pas les commits...
exit();

foreach ($departements as $dept) {
	$communes = bobs_espace_commune::liste_pour_departement($db, $dept);
	foreach ($communes as $commune) {
		msg($commune);
		flush();
		$especes = $commune->get_liste_especes(true);
		msg($especes->count());
		msg("espèces");
		$data = array(
			'contenu' => 'commune',
			'commune' => sprintf("%02d%03d", $commune->dept, $commune->code_insee),
			'especes' => array()
		);
		foreach ($especes as $e) {
			if ($e->get_restitution_ok(bobs_espece::restitution_public))
				$data['especes'][] = $e->id_espece;
		}
		$result = envoi($url_client, $data);

		if (test_retour($result)) echo "ok\n";
		else echo "ERREUR\n";
	}
}
?>
