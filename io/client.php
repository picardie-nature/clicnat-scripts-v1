<?php
switch ($_SERVER['REMOTE_ADDR']) {
	case '127.0.0.1':
	case '212.85.132.58':
		break;
	default:
		header('HTTP/1.1 403 Forbidden');
		echo "Acces interdit";
		exit();
}

if (file_exists('../fonctions/connectcn.php')) {
	require_once('../fonctions/connectcn.php');
	$mysql_host = HOST;
	$mysql_port = PORT;
	$mysql_user = USER;
	$mysql_password = PASSWORD;
	$mysql_database = DATABASE;
} else {
	$mysql_host = 'localhost';
	$mysql_port = '3306';
	$mysql_user = 'dev';
	$mysql_password = 'pwd';
	$mysql_database = 'dreal_io';
}

const sql_create_ce = 'create table if not exists clicnat_commune_espece_imp (code_insee char(5), id_espece integer, derniere_annee_obs integer, primary key(code_insee,id_espece),key(derniere_annee_obs)) character set utf8';
const sql_insert_commune = 'insert into clicnat_commune_espece_imp (code_insee, id_espece, derniere_annee_obs) values (:code_insee, :id_espece, :derniere_annee)';
const sql_commune_commit1 = 'drop table if exists clicnat_commune_espece';
const sql_commune_commit2 = 'alter table clicnat_commune_espece_imp rename to clicnat_commune_espece';

const sql_create_esp = 'create table if not exists clicnat_espece_imp  (
	id_espece integer,
	classe char(1),
	nom_f varchar(200),
	nom_s varchar(200),
	ordre varchar(100),
	famille varchar(100),
	cd_nom integer unique,
	menace text,
	habitat text,
	action_conservation text,
	commentaire_statut_menace text,
	commentaire_repartition text,
	determinant_znieff boolean,
	ref_etat_conv varchar(11),
	ref_prio_conv_cat varchar(32), 
	ref_indice_rar varchar(2),
	ref_menace varchar(2),
	ref_statut_bio varchar(21),
	ref_statut_origine varchar(34),
	ref_niveau_connaissance varchar(24),
	cd_ref integer,
	primary key (id_espece)
) character set utf8';

const sql_espece_commit1 = 'drop table if exists clicnat_espece';
const sql_espece_commit2 = 'alter table clicnat_espece_imp rename to clicnat_espece';
const sql_insert_espece = 'insert into clicnat_espece_imp (
	id_espece,
	classe, 
	nom_s,
	nom_f,
	ordre,
	famille,
	cd_nom,
	menace,
	habitat, 
	action_conservation,
	commentaire_statut_menace,
	commentaire_repartition,
	determinant_znieff, 
	ref_etat_conv,
	ref_prio_conv_cat,
	ref_indice_rar,
	ref_menace,
	ref_statut_bio,
	ref_statut_origine,
	ref_niveau_connaissance,
	cd_ref
) values (
	:id_espece,
	:classe,
	:nom_s,
	:nom_f,
	:ordre,
	:famille,
	:cd_nom,
	:menace,
	:habitat,
	:action_conservation,
	:commentaire_statut_menace,
	:commentaire_repartition,
	:determinant_znieff,
	:ref_etat_conv,
	:ref_prio_conv_cat,
	:ref_indice_rar,
	:ref_indice_menace,
	:ref_statut_bio,
	:ref_statut_origine,
	:ref_niveau_connaissance,
	:cd_ref
)';

function msg_exception_pdo($ex) {
	echo $ex->getMessage();
}

try {
	$db = new PDO("mysql:host=$mysql_host;port=$mysql_port;dbname=$mysql_database", $mysql_user, $mysql_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

	// creation des tables
	$db->exec(sql_create_ce);
	$db->exec(sql_create_esp);
	
	$f = fopen('php://input','r');
	$doc = '';
	while ($ligne = fgets($f)) {
		$doc .= $ligne;
	}

	$obj = json_decode($doc);
	switch ($obj->contenu) {
		case 'especes':
			$req_insert = $db->prepare(sql_insert_espece);
			foreach ($obj->especes as $e) {
				$req_insert->bindParam(':id_espece', $e->id_espece);
				$req_insert->bindParam(':classe', $obj->classe);
				$req_insert->bindParam(':nom_s', $e->nom_s);
				$req_insert->bindParam(':nom_f', $e->nom_f);
				$req_insert->bindParam(':cd_nom', $e->cd_nom);
				$req_insert->bindParam(':menace', $e->menace);
				$req_insert->bindParam(':habitat', $e->habitat);
				$req_insert->bindParam(':action_conservation', $e->action_conservation);
				$req_insert->bindParam(':commentaire_statut_menace', $e->commentaire_statut_menace);
				$req_insert->bindParam(':commentaire_repartition', $e->commentaire_repartition);
				$req_insert->bindParam(':determinant_znieff', $e->determinant_znieff);
				$req_insert->bindParam(':ref_etat_conv', $e->ref_etat_conv);
				$req_insert->bindParam(':ref_prio_conv_cat', $e->ref_prio_conv_cat);
				$req_insert->bindParam(':ref_indice_rar', $e->ref_rarete);
				$req_insert->bindParam(':ref_indice_menace', $e->ref_menace);
				$req_insert->bindParam(':ref_statut_bio', $e->ref_statut_bio);
				$req_insert->bindParam(':ref_statut_origine', $e->ref_statut_origine);
				$req_insert->bindParam(':ref_niveau_connaissance', $e->ref_niveau_connaissance);
				$req_insert->bindParam(':ordre', $e->ordre);
				$req_insert->bindParam(':famille', $e->famille);
				$req_insert->bindParam(':cd_ref', $e->cd_ref);

				if (!$req_insert->execute()) {
					echo "ERREUR_DB_ESP_Q2\n";
					echo var_dump($req_insert->errorInfo());
					exit(1);
				}
			}
			echo "OK\n";
			break;
		case 'especes_commit':
			$db->exec(sql_espece_commit1);
			$db->exec(sql_espece_commit2);
			echo "OK\n";
			break;
		case 'commune_commit':
			$db->exec(sql_commune_commit1);
			$db->exec(sql_commune_commit2);
			echo "OK\n";
			break;
		case 'commune':
			$req_insert = $db->prepare(sql_insert_commune);
			foreach ($obj->especes as $espece) {
				$req_insert->bindParam(':code_insee', $obj->commune);
				$req_insert->bindParam(':id_espece', $espece->id_espece);
				$req_insert->bindParam(':derniere_annee', $espece->derniere_annee);
				if (!$req_insert->execute()) {
					echo "ERREUR_DB_COM_Q2\n";
					exit(1);
				}
			}
			echo "OK\n";
			break;
		default:
			echo "NOK commande inconnue";
			break;
	}

} catch (PDOException $e) {
	echo "ERREUR_DB\n";
	//echo "<pre>{$e->getMessage()}</pre>";
	exit(1);
} catch (Exception $e) {
	echo "ERREUR\n";
	echo "<pre>";
	print_r($e);
	echo "</pre>";
	exit(1);
}
?>
