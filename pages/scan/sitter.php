<?php
/**
 * pages/scan/sitter.php
 * Sitterliste einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Flooding-Schutz 10 Minuten
if($cache->get('scansitter'.$_POST['uid']) AND !isset($_GET['force'])) {
	$tmpl->error = 'Die Sitter wurden in den letzten 10 Minuten schon eingescannt!';
	$tmpl->output();
	die();
}
// Flooding-Schutz in den Cache laden
$cache->set('scansitter'.$_POST['uid'], 1, 600);

// Daten sichern
$_POST['uid'] = (int)$_POST['uid'];


// IDs ermitteln
if(isset($_POST['sitterfrom']) AND is_array($_POST['sitterfrom']) AND count($_POST['sitterfrom'])) {
	foreach($_POST['sitterfrom'] as $key=>$val) {
		$_POST['sitterfrom'][$key] = "'".escape(html_entity_decode($val, ENT_QUOTES, 'utf-8'))."'";
	}
	
	$sfrom = array();
	
	$query = query("
		SELECT
			playerID
		FROM
			".GLOBPREFIX."player
		WHERE
			playerName IN (".implode(', ', $_POST['sitterfrom']).")
			AND playerDeleted = 0
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$sfrom[] = $row['playerID'];
	}
	
	$sfrom = implode('+', $sfrom);
}
else $sfrom = '';


if(isset($_POST['sitterto']) AND is_array($_POST['sitterto']) AND count($_POST['sitterto'])) {
	foreach($_POST['sitterto'] as $key=>$val) {
		$_POST['sitterto'][$key] = "'".escape(html_entity_decode($val, ENT_QUOTES, 'utf-8'))."'";
	}
	
	$sto = array();
	
	$query = query("
		SELECT
			playerID
		FROM
			".GLOBPREFIX."player
		WHERE
			playerName IN (".implode(', ', $_POST['sitterto']).")
			AND playerDeleted = 0
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	while($row = mysql_fetch_assoc($query)) {
		$sto[] = $row['playerID'];
	}
	
	$sto = implode('+', $sto);
}
else $sto = '';

//	eintragen
query("
	UPDATE ".PREFIX."user
	SET
		userSitterTo = '".$sto."',
		userSitterFrom = '".$sfrom."',
		userSitterUpdate = ".time()."
	WHERE
		user_playerID = ".$_POST['uid']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Log-Eintrag
if($config['logging'] >= 2) {
	// eigene Sitter
	if($_POST['uid'] == $user->id) {
		insertlog(4, 'scannt die eigenen Sitter ein');
	}
	// Sitter von jemand anderem
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
			insertlog(4, 'scannt die Sitter von '.$uname.' ('.$_POST['uid'].') ein');
		}
	}
}

// Ausgabe
$tmpl->content = 'Sitter erfolgreich eingescannt';



?>