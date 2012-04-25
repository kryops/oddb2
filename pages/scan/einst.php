<?php
/**
 * pages/scan/einst.php
 * Einstellungen einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Flooding-Schutz 10 Minuten
if($cache->get('scaneinst'.$_POST['uid'])) {
	$tmpl->error = 'Die Einstellungen wurden in den letzten 10 Minuten schon eingescannt!';
	$tmpl->output();
	die();
}
// Flooding-Schutz in den Cache laden
$cache->set('scaneinst'.$_POST['uid'], 1, 600);

// Daten sichern
$_POST['uid'] = (int)$_POST['uid'];

$_POST['einst'] = preg_replace('#[^\d]#Uis', '', $_POST['einst']);
if(strlen($_POST['einst']) != 6) $_POST['einst'] = '';
// Meta-Handel ausblenden
//$_POST['einst'][3] = '0';

// eintragen
query("
	UPDATE ".PREFIX."user
	SET
		userODSettings = '".$_POST['einst']."',
		userODSettingsUpdate = ".time()."
	WHERE
		user_playerID = ".$_POST['uid']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Log-Eintrag
if($config['logging'] >= 2) {
	// eigene Einstellungen
	if($_POST['uid'] == $user->id) {
		insertlog(4, 'scannt die eigenen Einstellungen ein');
	}
	// Einstellungen von jemand anderem
	else {
		// Name fetchen
		$query = query("
			SELECT
				playerName
			FROM ".GLOBPREFIX."player
			WHERE
				playerID = ".$_POST['uid']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			$data = mysql_fetch_assoc($query);
			$uname = $data['playerName'];
			
			// eintragen
			insertlog(4, 'scannt die Einstellungen von '.$uname.' ('.$_POST['uid'].') ein');
		}
	}
}

// Ausgabe
$tmpl->content = 'Einstellungen erfolgreich eingescannt';



?>