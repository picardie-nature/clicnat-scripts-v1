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

if (!$selection)
	throw new Exception("pas de sélection à cet id {$argv[1]}");

$outdir = "selection{$selection->id_selection}";

if (!file_exists($outdir))
	mkdir($outdir);

$citations = $selection->citations();
$n = 0;
$t = $citations->n();
$pprev = null;
foreach ($citations->ids() as $id_citation) {
	$n++;
	$p = round($n/$t*1000)/10;
	if ($pprev != $p) {
		echo " $n/$t progression=$p\r";
		flush();
		$pprev = $p;
	}
	$citation = new clicnat_citation_export_sinp($db, $id_citation);
	$citation->sauve();

	$dee = $citation->current();

	$doc = new DOMDocument("1.0", "UTF-8");
	$doc->loadXML($dee['document']);
	$doc->formatOutput = true;
	file_put_contents("$outdir/{$citation->id_citation}.xml", $doc->saveXML());
}
?>
