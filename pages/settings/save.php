<?php
/**
 * pages/settings/save.php
 * Einstellungen speichern
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten unvollständig
if(!isset($_POST['antrieb'], $_POST['szgrtype'], $_POST['email'])) {
	$tmpl->error = '<br />Daten unvollst&auml;ndig!';
	$tmpl->output();
	die();
}

// Daten sichern
$_POST['antrieb'] = (int)$_POST['antrieb'];
$_POST['szgrtype'] = (int)$_POST['szgrtype'];
$_POST['email'] = escape($_POST['email']);

$_POST['icq'] = preg_replace('/[^\d]/', '', $_POST['icq']);
$_POST['icq'] = (int)$_POST['icq'];

// Daten ungültig
if($_POST['szgrtype'] < 1 OR $_POST['szgrtype'] > 5) {
	$tmpl->error = '<br />Daten ung&uuml;ltig!';
	$tmpl->output();
	die();
}

// E-Mail ungültig
if(strpos($_POST['email'], '@') === false OR strpos($_POST['email'], '.') === false) {
	$tmpl->error = '<br />Ung&uuml;ltige E-Mail-Adresse eingegeben!';
	$tmpl->output();
	die();
}

// Antrieb ungültig
if($_POST['antrieb'] < 1) {
	$tmpl->error = '<br />Ung&uuml;ltiger Antrieb eingegeben!';
	$tmpl->output();
	die();
}


// Einstellungen speichern
/*
$fow = array(
	'gate'=>true,
	'mgate'=>true,
	'scan'=>true,
	'scout'=>$config['scan_veraltet'],
	'next'=>1
);

$settings = array(
	'antrieb'=>32,
	'szgr'=>true,
	'szgrtype'=>5,
	'szgrwildcard'=>true,
	'wminoncontent'=>true,
	'newtabswitch'=>false,
	'winlinknew'=>true,
	'winlink2tab'=>false,
	'closeontransfer'=>true,
	'suchspalten'=>'1-2-3-4-5-6-8-9-10-11-12-13-14-15',
	'fow'=>json_encode($fow)
);
*/
$user->settings['antrieb'] = $_POST['antrieb'];
$user->settings['szgrtype'] = $_POST['szgrtype'];
$user->settings['szgr'] = isset($_POST['szgr']) ? true : false;
$user->settings['szgrwildcard'] = isset($_POST['szgrwildcard']) ? true : false;
$user->settings['wminoncontent'] = isset($_POST['wminoncontent']) ? true : false;
$user->settings['newtabswitch'] = isset($_POST['newtabswitch']) ? true : false;
$user->settings['winlinknew'] = isset($_POST['winlinknew']) ? true : false;
$user->settings['winlink2tab'] = isset($_POST['winlink2tab']) ? true : false;
$user->settings['closeontransfer'] = isset($_POST['closeontransfer']) ? true : false;

$settings = escape(json_encode($user->settings));

query("
	UPDATE
		".PREFIX."user
	SET
		userSettings = '".$settings."',
		userEmail = '".$_POST['email']."',
		userICQ = ".$_POST['icq']."
	WHERE
		user_playerID = ".$user->id."
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// Cache löschen
$cache->remove('user'.$user->id);

// Log-Eintrag
if($config['logging'] >= 2) {
	insertlog(18, 'ändert die Einstellungen');
}

$tmpl->content = '<br />Die Einstellungen wurden erfolgreich gespeichert';

$tmpl->script = 'settings = {
\'wminoncontent\' : '.($user->settings['wminoncontent'] ? 'true' : 'false').',
\'newtabswitch\' : '.($user->settings['newtabswitch'] ? 'true' : 'false').',
\'winlinknew\' : '.($user->settings['winlinknew'] ? 'true' : 'false').',
\'winlink2tab\' : '.($user->settings['winlink2tab'] ? 'true' : 'false').',
\'closeontransfer\' : '.($user->settings['closeontransfer'] ? 'true' : 'false').',
\'szgrtype\' : '.$user->settings['szgrtype'].',
\'effects\' : 200
};';

// Ausgabe
$tmpl->output();



?>