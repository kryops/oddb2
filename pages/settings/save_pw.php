<?php
/**
 * pages/settings/save_pw.php
 * Passwort ändern
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten unvollständig
if(!isset($_POST['old'], $_POST['new1'], $_POST['new2'])) {
	$tmpl->error = '<br />Daten unvollst&auml;ndig!';
	$tmpl->output();
	die();
}

// kein altes Passwort eingegeben
if(trim($_POST['old']) == '') {
	$tmpl->error = '<br />kein altes Passwort eingegeben!';
	$tmpl->output();
	die();
}

// Userdaten abfragen
$query = query("
	SELECT
		userPassword
	FROM
		".PREFIX."user
	WHERE
		user_playerID = ".$user->id."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

$data = mysql_fetch_assoc($query);

// altes Passwort verschlüsseln
$_POST['old'] = General::encryptPassword($_POST['old'], $config['instancekey']);

// altes Passwort falsch
if($_POST['old'] != $data['userPassword']) {
	$tmpl->error = '<br />Das alte Passwort ist falsch!';
	$tmpl->output();
	die();
}

// neue Passwörter unterschiedlich
if($_POST['new1'] != $_POST['new2']) {
	$tmpl->error = '<br />Die beiden neuen Passw&ouml;rter sind unterschiedlich!';
	$tmpl->output();
	die();
}

// kein neues Passwort eingegeben
if(trim($_POST['new1']) == '') {
	$tmpl->error = '<br />Kein neues Passwort eingegeben!';
	$tmpl->output();
	die();
}

// neues Passwort verschlüsseln
$newpw = General::encryptPassword($_POST['new1'], $config['instancekey']);

// speichern
query("
	UPDATE
		".PREFIX."user
	SET
		userPassword = '".$newpw."'
	WHERE
		user_playerID = ".$user->id."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Cookie ändern
if(isset($_COOKIE['oddb'])) {
	setcookie('oddb', $user->id.'+'.$newpw.'+'.INSTANCE, time()+31536000);
}

// Cache löschen
$cache->remove('user'.$user->id);

// Log-Eintrag
if($config['logging'] >= 2) {
	insertlog(19, 'ändert das Passwort');
}

$tmpl->content = '<br />Das Passwort wurde erfolgreich ge&auml;ndert';

// Felder leeren
$tmpl->script = '$("input[type=password]").val("");';

// Ausgabe
$tmpl->output();



?>