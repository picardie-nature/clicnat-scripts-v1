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
$lib = $argv[2];

if (!$selection)
	throw new Exception("pas de sélection à cet id {$argv[1]}");

$outdir = "{$lib}_{$selection->id_selection}";
$oldoutdir = "{$selection->id_selection}_{$lib}";
$no_update = isset($argv[3]);

$citations = $selection->citations();
$n = 0;
$t = $citations->n();
$pprev = null;
$ajout = 0;
$vus = 0;
$mv = 0;
$zip = new ZipArchive();
$zip->open("{$selection->id_selection}_$lib.zip",ZipArchive::CREATE);
foreach ($citations->ids() as $id_citation) {
	$n++;
	$p = round($n/$t*1000)/10;
	if ($pprev != $p) {
		echo " $n/$t progression=$p ajout=$ajout vus=$vus mvs=$mv       \r";
		flush();
		$pprev = $p;
	}
	$citation = new clicnat_citation_export_sinp($db, $id_citation);
	$oldIndex = $zip->locateName("$oldoutdir/{$citation->id_citation}.xml");
	if ($no_update) {
		if ($zip->locateName("$outdir/{$citation->id_citation}.xml") !== false) {
			$vus++;
			// supprime ancien nom s'il existe
			if ($oldIndex !== false) {
				$zip->deleteIndex($oldIndex);
			}
			continue;
		}
	}

	// renomme si existe déjà
	if ($oldIndex !== false) {
		$zip->renameIndex($oldIndex, "$outdir/{$citation->id_citation}.xml");
		$mv++;
		continue;
	}
	
	// création enregistrement
	$citation->sauve();

	$dee = $citation->current();

	$doc = new DOMDocument("1.0", "UTF-8");
	$doc->loadXML($dee['document']);
	$doc->formatOutput = true;
	$zip->addFromString("$outdir/{$citation->id_citation}.xml", $doc->saveXML());
	$ajout++;
	
}
$zip->close();
file_put_contents("{$selection->id_selection}_$lib.txt", "selection={$selection->id_selection}\nn_citations_selection=$t\najout=$ajout\nvus=$vus\nmvs=$mv\n");
?>
