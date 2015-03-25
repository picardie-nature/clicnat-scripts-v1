<?php
/**
 * Évalue le temps passé par les observateurs sur le site à partir du fichier de log
 */

$annee = 2014;
$dir_analyse = '/tmp/analyse';

class bobs_evenement {
	public $timestamp; 
	public $session;
	public $application;
	public $data;
	public $annee;

	function __construct($ligne)
	{
		$m = array();
		if (preg_match('/^(?P<annee>\d+)-(?P<mois>\d+)-(?P<jour>\d+) (?P<heure>\d+):(?P<minute>\d+):(?P<seconde>\d+) bobs-(?P<app>\w+) \(sid=(?P<session>.*)\) (?P<data>.*)/', $ligne, $m)) {
		} else {
			throw new Exception('pas marche '.$ligne);
		}
		$this->annee = $m['annee'];
		$this->timestamp = strtotime("{$m['annee']}-{$m['mois']}-{$m['jour']} {$m['heure']}:{$m['minute']}:{$m['seconde']}");
		$this->session = $m['session'];
		$this->application = $m['app'];
		$this->data = $m['data'];
	}
}

class bobs_historique {
	protected $f;

	function __construct($filename)
	{
		$this->f = fopen($filename, 'r');
	}

	public function suivant()
	{
		$ligne = fgets($this->f);
		if (empty($ligne))
			return false;
		try {
			return new bobs_evenement($ligne);
		} catch (Exception $e) {
			echo $e->getMessage();
			echo "\n";
			// tenter la suivante
			$ligne = fgets($this->f);
			return new bobs_evenement($ligne);
		}
	}
}


$h = new bobs_historique('bobs.log');
while (($e = $h->suivant())) {
	if ($e->annee != $annee) {
		echo "année {$e->annee} != $annee\n";
		break;
	}

	if ($e->application != 'poste')
		continue;
	
	$f_start = $dir_analyse.'/'.$e->session.'.first';
	$f_stop = $dir_analyse.'/'.$e->session.'.last';
	
	if (!file_exists($f_start)) {
		file_put_contents($f_start, $e->timestamp);
	} else {
		file_put_contents($f_stop, $e->timestamp);
	}
}

unset($e);

$firsts = glob($dir_analyse.'/*.first');

$temps = 0;

foreach ($firsts as $first) {
	$last = str_replace('.first','.last', $first);
	if (file_exists($last)) {
		$tdeb = file_get_contents($first);
		$tfin = file_get_contents($last);
		if ($tfin - $tdeb > 300)
			$temps += $tfin - $tdeb;
		else
			$temps += 300;
	} else {
		$temps += 300;
	}
	echo "$temps\r";
	flush();
}
echo "\n";
?>
