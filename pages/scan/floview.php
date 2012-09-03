<?php
/**
 * pages/scan/floview.php
 * Flottenübersicht einscannen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Flooding-Schutz 10 Minuten
if($cache->get('scanfloview'.$_POST['uid']) AND !isset($_GET['force'])) {
	$tmpl->error = 'Die Flotten&uuml;bersicht wurde in den letzten 5 Minuten schon eingescannt!';
	$tmpl->output();
	die();
}
// Flooding-Schutz in den Cache laden
$cache->set('scanfloview'.$_POST['uid'], 1, 300);

// Daten sichern
$_POST['uid'] = (int)$_POST['uid'];
$_POST['schiffe'] = (int)$_POST['schiffe'];
$_POST['steuer'] = (int)$_POST['steuer'];
$_POST['kop'] = (int)$_POST['kop'];
$_POST['kopmax'] = (int)$_POST['kopmax'];
$_POST['pkop'] = (int)$_POST['pkop'];
$_POST['pkopmax'] = (int)$_POST['pkopmax'];

$bergbau = explode('-', $_POST['bb']);
foreach($bergbau as $key=>$val) {
	$bergbau[$key] = (int)$val;
}
$bergbau = array_unique($bergbau);

// Daten eintragen
query("
	UPDATE ".PREFIX."user
	SET
		userSchiffe = ".$_POST['schiffe'].",
		userFlottensteuer = ".$_POST['steuer'].",
		userKop = ".$_POST['kop'].",
		userKopMax = ".$_POST['kopmax'].",
		userPKop = ".$_POST['pkop'].",
		userPKopMax = ".$_POST['pkopmax'].",
		userFlottenUpdate = ".time()."
	WHERE
		user_playerID = ".$_POST['uid']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());


// Bergbau aktualisieren
// alle austragen
query("
	UPDATE ".PREFIX."planeten_schiffe
	SET
		schiffeBergbau = NULL,
		schiffeBergbauUpdate = 0
	WHERE
		schiffeBergbau = ".$_POST['uid']."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

if(count($bergbau)) {
	query("
		UPDATE ".PREFIX."planeten_schiffe
		SET
			schiffeBergbau = ".$_POST['uid'].",
			schiffeBergbauUpdate = ".time()."
		WHERE
			schiffe_planetenID IN(".implode(",", $bergbau).")
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$sql = "
		INSERT IGNORE INTO
			".PREFIX."planeten_schiffe
			(schiffe_planetenID, schiffeBergbau, schiffeBergbauUpdate)
		VALUES
		";
	
	foreach($bergbau as $key=>$val) {
		$bergbau[$key] = "(".$val.", ".$_POST['uid'].", ".time().")";
	}
	
	$sql .= implode(", ", $bergbau);
	
	query($sql) OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
}


// Log-Eintrag
if($config['logging'] >= 2) {
	// eigene Flottenübersicht
	if($_POST['uid'] == $user->id) {
		insertlog(4, 'scannt die eigene Flottenübersicht ein');
	}
	// Flottenübersicht von jemand anderem
	else {
		$query = query("
			SELECT
				playerName
			FROM ".GLOBPREFIX."player
			WHERE
				playerID = ".$_POST['uid']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			$data = mysql_fetch_assoc($query);
			insertlog(4, 'scannt die Flottenübersicht von '.$data['playerName'].' ('.$_POST['uid'].') ein');
		}
	}
}

// Ausgabe
$tmpl->content = 'Flotten&uuml;bersicht erfolgreich eingescannt';



?>