<?php
if (!file_exists('config.php'))
	require_once('/etc/baseobs/config.php');
else
	require_once('config.php');

require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');

get_db($db);

$q = bobs_qm()->query(get_db(), '1', 'select * from especes where borne_a > 0 order by borne_a', []);
$pile = [];

echo ".\n";

while ($r = bobs_element::fetch($q)) {
	if (count($pile) > 0) 
		while ($pile[count($pile)-1] < $r['borne_b'])
			array_pop($pile);
	$pile[] = $r['borne_b'];
	$_m = $pile[count($pile)-1];
	$q2 = bobs_qm()->query(get_db(), '2', 
		'select count(*) from citations c,especes e, observations o 
		where o.id_observation=c.id_observation and c.id_espece=e.id_espece and e.borne_a>=$1 and e.borne_b<=$2
		and o.brouillard = false and c.nb != -1',
		[$r['borne_a'],$r['borne_b']]
	);
	$n = bobs_element::fetch($q2);
	echo "{$r['id_espece']};{$n['count']};";
	echo count($pile).";";
	echo (($r['borne_b']-$r['borne_a']-1)/2).";";
	echo str_pad("", (count($pile)-1)*2, " ");
	echo sprintf("%s;%s;",str_replace(";"," ",trim($r['nom_s'])), str_replace(";"," ",trim($r['nom_f']))); 
	echo "\n";
}
