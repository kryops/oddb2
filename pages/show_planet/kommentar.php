<?php
/**
 * pages/show_planet/kommentar.php
 * Kommentar ändern (DB und ingame)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



/**
 * gibt das HTML-Layout für das ingame-Kommentar-Formular aus
 */
function kommentar_game() {
	echo '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<title>Kommentar bearbeiten</title>
<style type="text/css">
body {
	background-color:rgb(30,30,30);
	margin:0;
	padding:0px;
	text-align:right;
	font-family:Arial,Sans;
	font-size:11px;
	color:#fff;
	overflow:hidden;
}

textarea {
	width:150px;
	height:50px;
	background-color:rgba(255,255,255,0.1);
	border:1px solid rgba(255,255,255,0.3);
	font-family:Arial,Sans;
	font-size:11px;
	color:#fff;
	padding:2px;
}

input {
	background-color:rgba(255,255,255,0.1);
	color:#fff;
	border:1px solid rgba(255,255,255,0.25);
	cursor:pointer;
	margin-top:5px;
}

.error  {
	text-align:center;
	font-weight:bold;
	color:red;
}
</style>
</head>
<body>

';
}



// Kommentar ändern
if($_GET['sp'] == 'kommentar') {
	// Existenz der Daten
	if(!isset($_POST['kommentar'])) {
		$tmpl->error = 'Kein Kommentar übergeben!';
		$tmpl->output();
		die();
	}
	
	// Daten sichern
	$_GET['id'] = (int)$_GET['id'];
	
	$data = false;
	
	// Existenz und Berechtigung ermitteln
	$query = query("
		SELECT
			planetenID,
			planeten_playerID,
			
			systeme_galaxienID,
			
			player_allianzenID,
			
			register_allianzenID,
			
			statusStatus
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = player_allianzenID
		WHERE
			planetenID = ".$_GET['id']."
		ORDER BY planetenID ASC
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Planet mit dieser ID existiert
	if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);
	
	// Rechte ermitteln, den Planeten anzuzeigen
	show_planet_rechte($data);
	
	// Kommentar ändern
	query("
		UPDATE ".PREFIX."planeten
		SET
			planetenKommentar = '".escape($_POST['kommentar'])."',
			planetenKommentarUser = ".$user->id.",
			planetenKommentarUpdate = ".time()."
		WHERE
			planetenID = ".$_GET['id']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Log-Eintrag
	if($config['logging'] >= 2) {
		insertlog(13, 'ändert den Kommentar des Planeten '.$_GET['id']);
	}
	
	// Ausgabe
	$tmpl->content = '<div class="kommentar" style="float:left"></div> &nbsp; ';
	// Kommentar eingegeben
	if(trim($_POST['kommentar']) != '') {
		$tmpl->content .= 'von <a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$user->id.'&amp;ajax">'.htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8').'</a> 
			<span class="small">('.datum(time()).')</span>
			&nbsp; <a onclick="kommentar_edit('.$_GET['id'].', this.parentNode, true)" class="hint">[&auml;ndern]</a>
			
			<div class="kommentarc">'.nl2br(htmlspecialchars($_POST['kommentar'], ENT_COMPAT, 'UTF-8')).'</div>';
		
		$tmpl->script = "$('.plkommentar".$_GET['id']."').data('tooltip', '".addslashes(str_replace(array("\r\n", "\n"), '', nl2br(htmlspecialchars($_POST['kommentar'], ENT_COMPAT, 'UTF-8'))))."').show();";
	}
	// kein Kommentar
	else {
		$tmpl->content .= '&nbsp; <a onclick="kommentar_edit('.$_GET['id'].', this.parentNode, false)">Kommentar hinzuf&uuml;gen</a>';
		
		$tmpl->script = "$('.plkommentar".$_GET['id']."').data('tooltip', '').hide();";
	}
}

// Kommentar aus OD heraus ändern (Formular anzeigen)
else if($_GET['sp'] == 'kommentar_editgame') {
	// Daten sichern
	$_GET['id'] = (int)$_GET['id'];
	
	$data = false;
	
	// Existenz und Berechtigung ermitteln
	$query = query("
		SELECT
			planetenID,
			planeten_playerID,
			planetenKommentar,
			planeten_systemeID,
			
			systeme_galaxienID,
			
			player_allianzenID,
			
			register_allianzenID,
			
			statusStatus
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = player_allianzenID
		WHERE
			planetenID = ".$_GET['id']."
		ORDER BY planetenID ASC
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Planet mit dieser ID existiert
	if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);
	
	// Rechte ermitteln, den Planeten anzuzeigen
	show_planet_rechte($data);
	
	kommentar_game();

	// Fehler
	if($tmpl->error) {
		echo '<br /><div class="error">'.$tmpl->error.'</div>';
	}
	else {
		echo '
<form name="formular" action="index.php?p=show_planet&amp;sp=kommentar_editgame2&amp;id='.$_GET['id'].'" method="post">
<textarea name="kommentar">'.htmlspecialchars($data['planetenKommentar'], ENT_COMPAT, 'UTF-8').'</textarea>
<br />
<input type="submit" value="speichern" />
</form>';
	}

	echo '
</body>
</html>';
	
	die();
}

// Kommentar aus OD heraus ändern (abschicken)
else if($_GET['sp'] == 'kommentar_editgame2') {
	// Existenz der Daten
	if(!isset($_POST['kommentar'])) {
		$tmpl->error = 'Kein Kommentar übergeben!';
	}
	
	// Daten sichern
	$_GET['id'] = (int)$_GET['id'];
	
	$data = false;
	
	// Existenz und Berechtigung ermitteln
	$query = query("
		SELECT
			planetenID,
			planeten_playerID,
			
			systeme_galaxienID,
			
			player_allianzenID,
			
			register_allianzenID,
			
			statusStatus
		FROM
			".PREFIX."planeten
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = planeten_playerID
			LEFT JOIN ".PREFIX."register
				ON register_allianzenID = player_allianzenID
			LEFT JOIN ".PREFIX."allianzen_status
				ON statusDBAllianz = ".$user->allianz."
				AND status_allianzenID = player_allianzenID
		WHERE
			planetenID = ".$_GET['id']."
		ORDER BY planetenID ASC
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Planet mit dieser ID existiert
	if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);
	
	// Rechte ermitteln, den Planeten anzuzeigen
	show_planet_rechte($data);
	
	// Layout ausgeben
	kommentar_game();
	
	if(!$tmpl->error) {
		// Kommentar ändern
		query("
			UPDATE ".PREFIX."planeten
			SET
				planetenKommentar = '".escape($_POST['kommentar'])."',
				planetenKommentarUser = ".$user->id.",
				planetenKommentarUpdate = ".time()."
			WHERE
				planetenID = ".$_GET['id']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(13, 'ändert den Kommentar des Planeten '.$_GET['id']);
		}
		
		// Ausgabe
		echo '<div style="text-align:center">Der Kommentar wurde gespeichert.<br /><br />Die &Auml;nderung ist erst nach erneutem Aufrufen der Systemansicht sichtbar.</div>';
	}
	// Fehler
	else {
		echo '<br /><div class="error">'.$tmpl->error.'</div>';
	}
	
	// restliche Ausgabe
	echo '
</body>
</html>';
	
	die();
}



?>