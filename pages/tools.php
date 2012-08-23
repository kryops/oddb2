<?php
/**
 * pages/tools.php
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Tools';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * Seite
 */

// Übersichtsseite anzeigen
else {
	$firefox = strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox');
	if($firefox !== false) {
		$firefox = true;
	}
	
	$chrome = strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome');
	if($chrome !== false) {
		$chrome = true;
	}
	
	
	$tmpl->content = '
	<div class="icontent" style="line-height:1.5em">';
	// kein Firefox
	if(!$firefox AND !$chrome) {
		$tmpl->content .= '
		<br />
		<div class="center" style="font-weight:bold">Die Tools k&ouml;nnen nur mit Firefox oder Chrome benutzt werden!</div>
		<br />';
	}
	// keine Berechtigung für FoW-Ausgleich
	if(!$user->rechte['fow']) {
		$tmpl->content .= '
		<br />
		<div class="center" style="font-weight:bold">Du hast keine Berechtigung, den FoW-Ausgleich zu benutzen!</div>
		<br />';
	}
	$tmpl->content .= '
		<br />
		<div style="width:90%;margin:auto;margin-bottom:8px">
			<span style="font-weight:bold">F&uuml;r alle Tools gelten die Hinweise:</span>
			<br />
			Solltest du einen Loginfehler angezeigt bekommen, obwohl du in der Datenbank eingeloggt bist, musst du unter
			<br />
			<span style="font-style:italic">Einstellungen &rarr; Datenschutz</span> &nbsp;Cookies von <span style="font-style:italic">'.SERVER.'</span> &nbsp;explizit erlauben oder einen Haken bei&nbsp; <span style="font-style:italic">Cookies von Drittanbietern akzeptieren</span>&nbsp; setzen.
			<br /><br />
			Beim Surfen &uuml;ber UMTS-Anbindungen gibt es h&auml;ufig Probleme beim Parsen, da die Anbieter den Quelltext komprimieren, um Traffic zu sparen.
			<br />
			Einen Workaround zumindest f&uuml;r Vodafone bietet z.B. der <a href="http://www.vodafone.de/business/hilfe-support/high-performance-client.html" target="_blank" style="font-weight:bold">HighPerformance Client</a>. Dort muss die Komprimierung ausgeschaltet werden, damit das Parsen korrekt funktioniert.
		</div>
		<br />
		<div class="hl2">
			<img src="img/tools/oddbtool.png" alt="" style="width:32px;height:32px;margin:-8px;margin-left:-4px;margin-right:5px" /> 
			ODDB Tool
		</div>
		
		<div class="icontent">
			Das ODDB Tool ist ein Addon f&uuml;r Firefox und Google Chrome, das speziell f&uuml;r die ODDB programmiert wurde. Es beinhaltet einen FoW-Ausgleich mit direkter Anzeige der Bebauung in der Systemansicht und einen automatischen Parser.
			<br /><br />';
	
	if($chrome) {
		$tmpl->content .= '
			<a href="plugin/'.ODDBTOOLPATH_CHROME.'" style="font-weight:bold;color:#03d2ff">
				<img src="img/tools/chrome.png" alt="" style="width:48px;height:48px;vertical-align:middle;margin-right:5px" />
				Installation ODDB Tool V'.ODDBTOOL.' f&uuml;r Google Chrome
			</a>
			
			<br /><br />
			
			<b>Bitte beachte: Chrome blockiert die Installation von Erweiterungen, die nicht aus dem Google Chrome Webstore stammen.<br />
			Um das ODDB Tool zu installieren, musst du
		
			<ul>
			<li>die .crx-Datei herunterladen</li>
			<li>in Chrome die Erweiterungsseite &ouml;ffnen <i>(Einstellungen &rarr; Tools &rarr; Erweiterungen)</i></li>
			<li>die heruntergeladene Datei in Chrome hineinziehen</li>
			</ul>
			</b>';
	}
	else {
		$tmpl->content .= '
			<a href="plugin/'.ODDBTOOLPATH.'" style="font-weight:bold;color:#03d2ff">
				<img src="img/tools/firefox.png" alt="" style="width:48px;height:48px;vertical-align:middle;margin-right:5px" />
				Installation ODDB Tool V'.ODDBTOOL.' f&uuml;r Firefox
			</a>';
	}
	
	$tmpl->content .= '
			<br /><br />
			Adresse der ODDB, die eingestellt werden muss: <input type="text" class="text" value="'.ADDR.'" style="width:400px" onmouseover="this.select()" />
			
		</div>
		<br /><br />';
	

	
	
	$tmpl->content .= '
		
	</div>
	';
	
	// Ausgabe
	$tmpl->output();
}

?>