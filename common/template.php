<?php

/**
 * common/template.php
 * Template-Klasse
 * Startmenü
 * Contentswitch-Klasse
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



/**
 * Templateklasse
 * gibt ein Template entweder als HTML oder XML (AJAX) aus
 */
class template {
	// Template-Variablen, alle string
	// Icon eines Fensters - [leer], system, planet, player, ally, meta
	public $icon = '';
	// Name der Seite
	public $name = '';
	// Inhalt der Seite
	public $content = '';
	// JavaScript, das ausgeführt werden soll
	public $script = '';
	// Fehlermeldung
	public $error = '';
	// soll dem Tab eine Überschrift hinzugefügt werden?
	public $headline = true;
	
	/**
	 * Template als XML oder HTML ausgaben
	 */
	public function output() {
		global $user, $time_start, $queries, $cache, $config;
		
		/*
		 * Fehler bei seitenexternen Ausgaben abfangen
		 */
		// FoW-Abgleich
		if($_GET['p'] == 'fow') {
			diefow($this->error);
		}
		
		// API
		if($_GET['p'] == 'api') {
			header('Content-Type: application/json; charset=utf-8');
			
			$output = array(
				'error' => $this->error
			);
			
			die(json_encode($output));
		}
		
		
		
		// normale Seite
		// Ausgabe als HTML
		if(!isset($_GET['ajax'])) {
			// Content-Type-Header HTML
			header('Content-Type: text/html; charset=utf-8');
			
			
			// nicht eingeloggte User abfangen
			if(!$user->login) {
				$tmpl = new template_login;
				$tmpl->content = $this->content;
				$tmpl->error = $this->error;
				$tmpl->output();
				die();
			}
			
			// Fehler
			if($this->error) {
				// Seitentitel ändern
				$this->name = 'Fehler!';
				
				// Fehlermeldung rendern
				$this->content = '
					<div class="icontent" style="text-align:center;margin:20px;font-size:16px;font-weight:bold">
						<img src="img/layout/error.png" width="150" height="137" alt="Fehler" />
						<br /><br />
						'.$this->error.'
					</div>';
				
				// Scripts entfernen
				$this->script = '';
			}
			
			// Template ausgeben
			echo '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" type="text/css" href="css/main.css'.FILESTAMP.'" />
<link rel="shortcut icon" href="favicon.ico" />
<title>'.$this->name.' - ODDB</title>
</head>
<body>

'.(DEBUG ? '<div style="position:absolute;top:10px;left:500px;z-index:1000;font-weight:bold;color:red">Debug-Modus aktiviert!</div>' : '').'
<div id="bgpic"></div>

<div id="logo"></div>

<div id="menu">
<a data-link="index.php?p=oview"'.($_GET['p'] == 'oview' ? ' class="active"' : '').'>&Uuml;BERSICHT</a>';
			// Einscannen
			if($user->rechte['scan']) {
				echo '
<a data-link="index.php?p=scan"'.($_GET['p'] == 'scan' ? ' class="active"' : '').'>EINSCANNEN</a>';
			}
			echo '
<span></span>';
			
			$spacer = false;
			// Suchen
			if($user->rechte['search']) {
				echo '
<a data-link="index.php?p=search"'.($_GET['p'] == 'search' ? ' class="active"' : '').'>SUCHEN</a>';
				$spacer = true;
			}
			// Scouten
			if($user->rechte['scout']) {
				echo '
<a data-link="index.php?p=scout"'.($_GET['p'] == 'scout' ? ' class="active"' : '').'>SCOUTEN</a>';
				$spacer = true;
			}
			// Raiden & Toxxen
			if($user->rechte['toxxraid']) {
				echo '
<a data-link="index.php?p=toxx"'.($_GET['p'] == 'toxx' ? ' class="active"' : '').'>RAIDEN &amp; TOXXEN</a>';
				$spacer = true;
			}
			// Strecken
			if($user->rechte['strecken_flug']
				OR $user->rechte['strecken_weg']
				OR $user->rechte['strecken_saveroute']
				OR $user->rechte['strecken_ueberflug']) {
					echo '
<a data-link="index.php?p=strecken"'.($_GET['p'] == 'strecken' ? ' class="active"' : '').'>STRECKEN</a>';
					$spacer = true;
			}
			// Routen
			if($user->rechte['routen']) {
				echo '
<a data-link="index.php?p=route"'.($_GET['p'] == 'route' ? ' class="active"' : '').'>ROUTEN</a>';
				$spacer = true;
			}
			if($spacer) {
				echo '
<span></span>';
			}
			
			$spacer = false;
			// Karte
			if($user->rechte['karte']) {
				echo '
<a data-link="index.php?p=karte"'.($_GET['p'] == 'karte' ? ' class="active"' : '').'>KARTE</a>';
				$spacer = true;
			}
			// Gates und Myrigates
			if($user->rechte['gates'] OR $user->rechte['show_myrigates']) {
				echo '
<a data-link="index.php?p=gates"'.($_GET['p'] == 'gates' ? ' class="active"' : '').'>GATES &amp; MGATES</a>';
				$spacer = true;
			}
			
			if($spacer) {
				echo '
<span></span>';
			}
			
			$spacer = false;
			// Invasionen
			if($user->rechte['invasionen'] OR $user->rechte['fremdinvakolos'] OR $user->rechte['show_player_db_ally'] OR $user->rechte['show_player_db_meta'] OR $user->rechte['show_player_db_other'] OR $user->rechte['masseninva']) {
				echo '
<a data-link="index.php?p=inva"'.($_GET['p'] == 'inva' ? ' class="active"' : '').'>INVASIONEN</a>';
				$spacer = true;
			}
			
			if($spacer) {
				echo '
<span></span>';
			}
			
			// Ressplaneten und Werften
			echo '
<a data-link="index.php?p=ress"'.($_GET['p'] == 'ress' ? ' class="active"' : '').'>RESSPLANETEN</a>
<a data-link="index.php?p=werft"'.($_GET['p'] == 'werft' ? ' class="active"' : '').'>WERFTEN</a>';
			
			if($user->rechte['show_player_db_ally'] OR $user->rechte['show_player_db_meta'] OR $user->rechte['show_player_db_other']) {
				echo '
<a data-link="index.php?p=forschung"'.($_GET['p'] == 'forschung' ? ' class="active"' : '').'>FORSCHUNG</a>';
			}
			
			echo '
<span></span>';
			
			$spacer = false;
			// Spieler
			if($user->rechte['userlist'] OR $user->rechte['allywechsel'] OR $user->rechte['inaktivensuche']) {
				echo '
<a data-link="index.php?p=player"'.($_GET['p'] == 'player' ? ' class="active"' : '').'>SPIELER</a>';
				$spacer = true;
			}
			// Allianzen
			if($user->rechte['show_ally']) {
				echo '
<a data-link="index.php?p=allianzen"'.($_GET['p'] == 'allianzen' ? ' class="active"' : '').'>ALLIANZEN</a>';
				$spacer = true;
			}
			if($spacer) {
				echo '
<span></span>';
			}
			// Statistiken, Einstellungen
			echo '
<a data-link="index.php?p=stats"'.($_GET['p'] == 'stats' ? ' class="active"' : '').'>STATISTIKEN</a>
<a data-link="index.php?p=settings"'.($_GET['p'] == 'settings' ? ' class="active"' : '').'>EINSTELLUNGEN</a>';
			// Verwaltung
			if($user->rechte['verwaltung_galaxien'] OR $user->rechte['verwaltung_galaxien2'] OR $user->rechte['verwaltung_user_register'] OR $user->rechte['verwaltung_rechte'] OR $user->rechte['verwaltung_logfile'] OR $user->rechte['verwaltung_settings'] OR $user->rechte['verwaltung_backup']) {
				echo '
<a data-link="index.php?p=admin"'.($_GET['p'] == 'admin' ? ' class="active"' : '').'>VERWALTUNG</a>';
			}
			// Tools
			echo '
<span></span>
<a data-link="index.php?p=tools"'.($_GET['p'] == 'tools' ? ' class="active"' : '').'>TOOLS</a>
</div>

<div id="headerbar">';
			// offene Invasionen ermitteln
			if($user->rechte['invasionen']) {
				$invas = getopeninvas();
			
				echo '
<div id="headerinva"'.(!$invas ? ' style="display:none"' : '').'>
<div class="headerbarimg" style="background-position:-786px 0px"></div>
<div class="headerbarlabel"><a class="link contextmenu" data-link="index.php?p=inva">'.$invas.' offene Aktion'.($invas != 1 ? 'en' : '').'!</a></div>
</div>';
			}
			echo '
</div>

<div id="contentc">
<div id="contenthl"></div>

<div id="contentbody">
	<div class="content" id="content1" data-link="index.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_COMPAT, 'UTF-8').'">
		<div class="hl1">
			<div class="hl1buttons">
				<div style="background-position:-215px -44px" title="Seite zu den Favoriten hinzuf&uuml;gen" onclick="fav_add(this, 1)"></div>
				<div style="background-position:-240px -44px" class="favbutton" title="Seite in einem Fenster &ouml;ffnen" onclick="win_fromcontent2(this, \''.addslashes($this->name).'\', false)"></div>
				<div class="ctabclose" title="Tab schlie&szlig;en" onclick="tab_close2(this)"></div>
			</div>
			'.$this->name.'
		</div>
		
		'.$this->content.'
	</div>
</div>

<div id="contentbl"></div>

<div id="footerlinks">
<a href="index.php?p=impressum">Impressum</a> &nbsp;&bull;&nbsp; 
<a href="http://www.kryops.de/" target="_blank">Projekt-Homepage</a>
</div>
</div>

<div id="tabbar"></div>

<div id="szgr" class="szgr-bottom" '.(!$user->settings['szgr'] ? ' style="display:none"' : '').'>
<div id="szgrclose" title="Schnellzugriffsleiste schlie&szlig;en" onclick="szgr_toggle()"></div>

<div id="szgrc">';
			// Schnellzugriffsleiste
			$szgr = false;
			if($user->rechte['show_system']) {
				$szgr = true;
				echo '
<form action="#" onsubmit="return szgr_send(\'system\', this.i.value)">
	System <input type="text" name="i" onfocus="this.select()" />
	<input type="submit" style="display:none" />
</form>';
			}
			if($user->rechte['show_planet']) {
				$szgr = true;
				echo '
<form action="#" onsubmit="return szgr_send(\'planet\', this.i.value)">
	Planet <input type="text" name="i" onfocus="this.select()" />
	<input type="submit" style="display:none" />
</form>';
			}
			if($user->rechte['show_player']) {
				$szgr = true;
				echo '
<form action="#" onsubmit="return szgr_send(\'player\', this.i.value)">
	Spieler <input type="text" name="i" onfocus="this.select()" />
	<input type="submit" style="display:none" />
</form>';
			}
			if($user->rechte['show_ally']) {
				$szgr = true;
				echo '
<form action="#" onsubmit="return szgr_send(\'ally\', this.i.value)">
	Allianz <input type="text" name="i" onfocus="this.select()" />
	<input type="submit" style="display:none" />
</form>';
			}
			// keine Schnellzugriffsleisten-Optionen
			if(!$szgr) {
				echo '
<span class="small2" style="font-style:italic">keine Schnellzugriffs-Optionen verfügbar</span>';
			}
			echo '
</div>
</div>

<div id="windowbar"><div id="startbutton"></div></div>

<div id="startmenu">
<div class="cmborder" style="background-position:-528px -59px"></div>
<div id="startmenuc">
<div id="favoriten">'.startmenu().'
</div>
<div id="favspacer"></div>
<div id="historyc" style="display:none"></div>
<a onclick="dbhistory_open()" id="historylink">Chronik anzeigen</a>
<a href="javascript:szgr_toggle()" id="szgrlink">Schnellzugriffsleiste '.($user->settings['szgr'] ? 'schlie&szlig;en' : '&ouml;ffnen').'</a>
<a href="index.php?p=logout">Logout</a>
</div>
<div class="cmborder" style="background-position:-528px -65px"></div>
</div>

<div id="contextmenu">
<div class="cmborder" style="background-position:-528px -59px"></div>
<div id="contextmenuc"></div>
<div class="cmborder" style="background-position:-528px -65px"></div>
</div>

<div id="tooltip"></div>

<div id="ajax"><img src="img/layout/ajax.gif" width="24" height="24" alt="laden" /></div>

<noscript><div class="error noscript">Die Datenbank funktioniert nur mit aktiviertem JavaScript!</div></noscript>

<script type="text/javascript" src="js/jquery.js'.FILESTAMP.'"></script>
<script type="text/javascript" src="js/general'.(DEBUG ? '_src' : '').'.js'.FILESTAMP.'"></script>
<script type="text/javascript">
var tabs = [[1, \''.addslashes($this->name).'\']],
	szgr = '.($user->settings['szgr'] ? 'true' : 'false').',
	settings = {
		\'wminoncontent\' : '.($user->settings['wminoncontent'] ? 'true' : 'false').',
		\'newtabswitch\' : '.($user->settings['newtabswitch'] ? 'true' : 'false').',
		\'winlinknew\' : '.($user->settings['winlinknew'] ? 'true' : 'false').',
		\'winlink2tab\' : '.($user->settings['winlink2tab'] ? 'true' : 'false').',
		\'closeontransfer\' : '.($user->settings['closeontransfer'] ? 'true' : 'false').',
		\'szgrtype\' : '.$user->settings['szgrtype'].',
		\'effects\' : 200
	},
	ODServer = "'.$user->odServer.'";
';
			if($user->rechte['invasionen']) {
				echo '
$(document).ready(function(){
	window.setInterval("openinvas()", '.($config['invasionen_update']*1000).');
});';
			}
			echo '
	'.$this->script.'
</script>

'.ADCODE.'

<!-- Rendertime '.number_format(microtime(true)-$time_start, 6).'s, '.$queries.' Queries'.((DEBUG AND function_exists('memory_get_peak_usage')) ? ', '.ressmenge(@memory_get_peak_usage(true)).' Bytes RAM' : '').' -->
';
			// MySQL-Stack-Debug
			if(DEBUG) {
				global $mysql_stack;
				if($mysql_stack !== NULL) {
					echo '<!--
';
					print_r($mysql_stack);
					echo '
-->';
				}
			}
			echo '
</body>
</html>';
		}
		// AJAX-Request
		// Ausgabe als XML
		else {
			// bei Fehlern Inhalt und Scripts zurücksetzen
			if($this->error) {
				$this->content = '';
				$this->script = '';
				
				// Auch für externe abrufbar machen
				header('Access-Control-Allow-Origin: *');
			}
			
			// Content-Type-Header XML
			header('Content-Type:text/xml; charset=utf-8');
			
			// Rendertime berechnen
			$time = number_format(microtime(true)-$time_start, 6);
			
			// Template ausgeben
			echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
<page>
<error><![CDATA['.$this->error.']]></error>
<time><![CDATA['.$time.']]></time>
<queries><![CDATA['.$queries.']]></queries>';
			if(DEBUG) {
				// RAM
				if(function_exists('memory_get_peak_usage')) {
					echo '
<ram><![CDATA['.ressmenge(@memory_get_peak_usage(true)).' Bytes RAM]]></ram>';
				}
				
				// MySQL-Stack
				global $mysql_stack;
				if($mysql_stack !== NULL) {
					echo '
<!--
';
					print_r($mysql_stack);
					echo '
-->';
				}
			}
			echo '
<icon><![CDATA['.$this->icon.']]></icon>
<name><![CDATA['.$this->name.']]></name>
<content><![CDATA['.$this->content.']]></content>
<script><![CDATA['.$this->script.']]></script>
</page>';
		}
	}
	
	
	/**
	 * mit einer Fehlermeldung abbrechen
	 * @param string $message
	 */
	public function abort($message = 'Es ist ein Fehler aufgetreten!') {
		$this->error = $message;
		$this->output();
		die();
	}
}

/**
 * Favoritenliste im Startmenü generieren
 * @return string HTML -> #favoriten
 */
function startmenu() {
	global $user;
	
	$query = query("
		SELECT
			favoritenID,
			favoritenLink,
			favoritenName,
			favoritenTyp
		FROM
			".PREFIX."favoriten
		WHERE
			favoriten_playerID = ".$user->id."
		ORDER BY
			favoritenID ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// keine Favoriten angelegt
	if(!mysql_num_rows($query)) {
		return '
	<div style="font-size:10px;text-align:center;margin:10px;margin-bottom:20px">
		noch keine Favoriten angelegt
	</div>';
	}
	
	// Inhalt erzeugen
	$content = '';
	
	while($row = mysql_fetch_assoc($query)) {
		$content .= '
	<a class="fav" id="fav'.$row['favoritenID'].'" data-link="'.htmlspecialchars($row['favoritenLink']).'" data-type="'.$row['favoritenTyp'].'">'.htmlspecialchars($row['favoritenName'], ENT_COMPAT, 'UTF-8').'</a>';
	}
	
	// Inhalt zurückgeben
	return $content;
}

/**
 * contentswitch-Klasse
 */
class contentswitch {
	/* $data array [key=$_GET['sp']]
		link string Adresse der Unterseite
		bg string css des Contentswitch-Links
		content string Inhalt der Unterseite
		reload string true/false Unterseite bei neuem Klick neu laden
		width int Mindestbreite der Unterseite
	*/
	public $data = array();
	
	// $active string aktive Unterseite (entspricht key von $data)
	public $active;
	
	/**
	 * gibt den Inhalt des Contentswitchs zurück
	 * @return string
	 */
	public function output() {
		// Offset des aktiven Contents berechnen
		$actoffset = 0;
		$i = 0;
		foreach($this->data as $page=>$data) {
			if($page == $this->active) {
				$actoffset = 150*$i;
			}
			$i++;
		}
		
		// Ausgabe erzeugen
		// nur eine Seite mit Berechtigung -> Contentswitch weglassen
		if(count($this->data) == 1) {
			foreach($this->data as $page=>$data) {
				$outc = '
				<div class="icontent1 icontentact" style="min-width:'.$data['width'].'px">
					'.$data['content'].'
				</div>';
			}
		}
		// mehrere Seiten -> Contentswitch anzeigen
		else {
			$outc = '
				<div class="csw'.(count($this->data) > 5 ? ' cswbig' : '').'" style="min-width:'.(count($this->data)*150).'px">
					<div class="cswact" style="left:'.$actoffset.'px"></div>
					<div class="cswbgc">';
			// Contentswitch-Hintergrund erzeugen
			foreach($this->data as $page=>$data) {
				$outc .= '<div class="cswbg" style="'.$data['bg'].'"></div>';
			}
			$outc .= '
					</div>';
			// Contentswitch-Links erzeugen
			$i = 1;
			foreach($this->data as $page=>$data) {
				$outc .= '<div class="cswlink" data-link="'.htmlspecialchars($data['link'], ENT_COMPAT, 'UTF-8').'" data-pos="'.$i.'" data-reload="'.$data['reload'].'"></div>';
				$i++;
			}
			$outc .= '
				</div>';
			// Contentbereiche erzeugen
			$i = 1;
			foreach($this->data as $page=>$data) {
				$outc .= '
	<div class="icontent'.$i.(($page == $this->active) ? ' icontentact' : '').'" style="min-width:'.$data['width'].'px'.(($page != $this->active) ? ';display:none' : '').'">
		'.$data['content'].'
	</div>';
				$i++;
			}
		}
		// Inhalt zurückgeben
		return $outc;
	}
}

?>