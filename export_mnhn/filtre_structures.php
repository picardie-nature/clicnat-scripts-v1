<?php
if (!file_exists('config.php'))
	require_once('/etc/baseobs/config.php');
else
	require_once('config.php');
require_once(DB_INC_PHP);
require_once(OBS_DIR.'espece.php');
require_once(OBS_DIR.'selection.php');
require_once(OBS_DIR.'sinp.php');

$selection = new bobs_selection($db, $argv[1]);
$a_retirer = [];
$n = 0;
$stats = [];
foreach ($selection->citations_avec_tag(610) as $citation) {
	$n++;
	echo "$n ".count($a_retirer)."\r";
	flush();	
	$tag = $citation->get_tag(610);
	switch ($tag['v_text']) {
		case 'onema':
		case 'onf':
			$a_retirer[] = $citation->id_citation;
			if (isset($stats[$tag['v_text']]))
				$stats[$tag['v_text']]++;
			else
				$stats[$tag['v_text']] = 1;
			break;
		default:
			break;
	}
}
print_r($a_retirer);
print_r($stats);
?>
