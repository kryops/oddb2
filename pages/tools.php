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
	'api'=>true,
	'apikey'=>true
);

// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * Funktionen
 */

// API-Key ändern
else if($_GET['sp'] == 'apikey') {
	
	$apikey = General::generateApiKey();
	
	query("
		UPDATE
			".PREFIX."user
		SET
			userApiKey = '".$apikey."'
		WHERE
			user_playerID = ".$user->id."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	$tmpl->script = '$(".apikey").val("'.INSTANCE.'-'.$user->id.'-'.$apikey.'");';
	
	$tmpl->output();
	
}

/**
 * Seite
 */

// API-Dokumentation
else if($_GET['sp'] == 'api') {
	
	$col_multi = 40;
	
	$apikey = $user->getApiKey();
	
	$tmpl->name = 'API-Dokumentation';
	
	$tmpl->content = '
	<div class="icontent">
		<br />
		Die ODDB stellt eine JSON-API f&uuml;r Erweiterungen zur Verf&uuml;gung.
		<br />
		Der Zugriff wird &uuml;ber einen benutzerspezifischen API-Key erm&ouml;glicht, der es erlaubt, ohne Session und Cookies auf die Daten der ODDB zuzugreifen.
		<br /><br />
		JSON kann von JavaScript &uuml;ber AJAX sowie von PHP und vielen anderen Programmiersprachen verarbeitet werden.
		<br /><br />
	</div>
	<div class="hl2">Abfrage</div>
	<div class="icontent">
		Grund-Adresse f&uuml;r den API-Zugriff mit deinem pers&ouml;nlichen API-Key:
		<br />
		<input type="text" class="text" style="width:600px" value="'.(ADDR.'index.php?p=api&amp;key='.$apikey).'" readonly="readonly" onmouseover="$(this).select()" />
		<br /><br />
		Daran k&ouml;nnen beliebige Filter geh&auml;ngt werden, die in der Adresse dieselbe Form haben wie die der Suchfunktion. 
		<br />Daf&uuml;r muss beim Ausf&uuml;hren einer Suche lediglich der Adress-Teil nach <i>p=search&amp;s=1</i> kopiert und an die Adresse geh&auml;ngt werden.
		<br /><br />
		Beispiele verschiedener Filter:
		<ul>
			<li>Bestimmter Planet: <b>...&amp;pid=1234</b></li>
			<li>Planeten eines Systems: <b>...&amp;sid=1234</b></li>
			<li>Planeten eines Spielers: <b>...&amp;uid=1234</b></li>
			<li>Planeten einer Allianz: <b>...&amp;aid=1234</b></li>
		</ul>
		<br />
		Weitere Einstellungen:
		<ul>
			<li><b>limit</b> - maximale Anzahl der Ergebnisse begrenzen; standardm&auml;&szlig;ig 100, aus Performancegr&uuml;nden auf maximal 1000 begrenzt</li>
			<li><b>offset</b> - Anzahl der Ergebnisse, die zu Beginn &uuml;bersprungen werden sollen; standardm&auml;&szlig;ig 0</li>
		</ul>
		<br /><br />
	</div>
	
	<div class="hl2">Antwort</div>
	<div class="icontent">
		<b>Fehlerfall</b>
		<p>Tritt ein Fehler auf, wird ein Objekt ausgegeben, das folgende Eigenschaften besitzt:</p>
		
		<table class="apidoc">
		<tr>
			<td>error</td>
			<td>Fehlermeldung</td>
		</tr>
		</table>
		
		<br /><br />
		<b>Normale Antwort</b>
		
		<p>Die Ausgabe der API h&auml;ngt von den Berechtigungen des Benutzers ab. Planeten, f&uuml;r die der Benutzer keine Such-Berechtigungen hat oder die durch Allianz- und Galaxie-Einschr&auml;nkungen gesperrt sind, werden nicht ausgegeben.
		<p>Es wird ein Array ausgegeben, das die gefundenen Planeten enth&auml;lt (Arrays, die Objekte enthalten, werden hier durch [] dargestellt).</p>
		<p>Die API besitzt folgende Eigenschaften:</p>
		
		<br />
		
		<b>[</b>
		<table class="apidoc">
		<tr>
			<td>planet</td>
			<td>Objekt, das Daten &uuml;ber den Planeten enth&auml;lt</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi).'px">
		<tr>
			<td>id</td>
			<td>ID des Planeten</td>
		</tr>
		<tr>
			<td>name</td>
			<td>Name des Planeten</td>
		</tr>
		<tr>
			<td>gescannt</td>
			<td>Unix-Timestamp, bei welchem die Oberfl&auml;che des Planeten zuletzt gescannt wurde</td>
		</tr>
		<tr>
			<td>gescanntVoll</td>
			<td>Unix-Timestamp, bei welchem der Planet zuletzt vollst&auml;ndig gescannt wurde</td>
		</tr>
		<tr>
			<td>unscannbar</td>
			<td>Unix-Timestamp, bei welchem der Planet zuletzt als unscannbar markiert wurde</td>
		</tr>
		<tr>
			<td>typ</td>
			<td>Nummer des Planetentyps</td>
		</tr>
		<tr>
			<td>groesse</td>
			<td>Gr&ouml;&szlig;e des Planeten</td>
		</tr>
		<tr>
			<td>bevoelkerung</td>
			<td>Bev&ouml;lkerung des Planeten als ganze Zahl</td>
		</tr>
		<tr>
			<td>forschung</td>
			<td>Forschungspunkte des Planeten
			<br /><b>false</b>, wenn der Planet noch nicht vollst&auml;ndig gescannt wurde</td>
		</tr>
		<tr>
			<td>industrie</td>
			<td>Industriepunkte des Planeten
			<br /><b>false</b>, wenn der Planet noch nicht vollst&auml;ndig gescannt wurde</td>
		</tr>
		<tr>
			<td>werte</td>
			<td>Objekt, das die Rohstoffwerte des Planeten enth&auml;lt</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi*2).'px">
		<tr>
			<td>erz</td>
			<td>Erz-Wert des Planeten</td>
		</tr>
		<tr>
			<td>metall</td>
			<td>Metall-Wert des Planeten (identisch mit Erz-Wert)</td>
		</tr>
		<tr>
			<td>wolfram</td>
			<td>Wolfram-Wert des Planeten</td>
		</tr>
		<tr>
			<td>kristall</td>
			<td>Kristall-Wert des Planeten</td>
		</tr>
		<tr>
			<td>fluor</td>
			<td>Fluor-Wert des Planeten</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi).'px">
		<tr>
			<td>produktion</td>
			<td>Objekt, das die Rohstoffproduktion des Planeten enth&auml;lt
			<br /><b>false</b>, wenn der Planet noch nicht vollst&auml;ndig gescannt wurde</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi*2).'px">
		<tr>
			<td>erz</td>
			<td>Erz-Produktion des Planeten</td>
		</tr>
		<tr>
			<td>metall</td>
			<td>Metall-Produktion des Planeten</td>
		</tr>
		<tr>
			<td>wolfram</td>
			<td>Wolfram-Produktion des Planeten</td>
		</tr>
		<tr>
			<td>kristall</td>
			<td>Kristall-Produktion des Planeten</td>
		</tr>
		<tr>
			<td>fluor</td>
			<td>Fluor-Produktion des Planeten</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi).'px">
		<tr>
			<td>vorrat</td>
			<td>Objekt, das den Rostoffvorrat des Planeten enth&auml;lt
			<br /><b>false</b>, wenn die Oberfl&auml;che des Planeten noch nicht gescannt wurde</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi*2).'px">
		<tr>
			<td>erz</td>
			<td>Erz-Vorrat des Planeten</td>
		</tr>
		<tr>
			<td>metall</td>
			<td>Metall-Vorrat des Planeten</td>
		</tr>
		<tr>
			<td>wolfram</td>
			<td>Wolfram-Vorrat des Planeten</td>
		</tr>
		<tr>
			<td>kristall</td>
			<td>Kristall-Vorrat des Planeten</td>
		</tr>
		<tr>
			<td>fluor</td>
			<td>Fluor-Vorrat des Planeten</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi).'px">
		<tr>
			<td>natives</td>
			<td>Anzahl der Natives vor dem Planeten</td>
		</tr>
		<tr>
			<td>kommentar</td>
			<td>Planeten-Kommentar</td>
		</tr>
		<tr>
			<td>scan</td>
			<td>Objekt, das die Bebauung des Planeten enth&auml;lt
			<br /><b>false</b>, wenn der Planet noch nicht gescannt wurde oder der Benutzer keine Berechtigung hat</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi*2).'px">
		<tr>
			<td>url</td>
			<td>Adresse zur Miniatur-Grafik der Bebauung in OD</td>
		</tr>
		<tr>
			<td>planet</td>
			<td>String, der die Geb&auml;ude-IDs auf dem Planeten enth&auml;lt; durch + getrennt</td>
		</tr>
		<tr>
			<td>orbit</td>
			<td>String, der die Geb&auml;ude-IDs des Orbits enth&auml;lt; durch + getrennt</td>
		</tr>
		<tr>
			<td>orbiter</td>
			<td>Summierter Orbiter-Angriff</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi).'px">
		<tr>
			<td>myrigate</td>
			<td>Ziel-ID des Myrigates
			<br /><b>true</b>, wenn ein Sprunggenerator am Planeten steht</b>
			<br /><b>false</b>, wenn kein Myrigate vorhanden ist oder der Benutzer keine Berechtigung hat</td>
		</tr>
		<tr>
			<td>gateEntfernung</td>
			<td>Entfernung des Planeten zum Gate (Stunden:Minuten)
			<br /><b>false</b>, wenn kein Gate eingetragen ist</td>
		</tr>
		<tr>
			<td>getoxxt</td>
			<td>Unix-Timestamp, bis zu welchem der Planet als getoxxt eingetragen ist
			<br /><b>false</b>, wenn der Benutzer keine Berechtigung hat</td>
		</tr>
		<tr>
			<td>geraidet</td>
			<td>Unix-Timestamp, zu welchem der Planet als geraidet markiert wurde
			<br /><b>false</b>, wenn der Benutzer keine Berechtigung hat</td>
		</tr>
		<tr>
			<td>bergbau</td>
			<td><b>true</b>, wenn ein Bergbau am Planeten l&auml;uft
			<br /><b>false</b>, wenn kein Bergbau l&auml;uft oder der Benutzer keine Berechtigung hat</td>
		</tr>
		<tr>
			<td>terraformer</td>
			<td><b>true</b>, wenn ein Terraformer am Planeten l&auml;uft
			<br /><b>false</b>, wenn kein Terraformer l&auml;uft oder der Benutzer keine Berechtigung hat</td>
		</tr>
		<tr>
			<td>ressplanet</td>
			<td><b>true</b>, wenn der Planet als Ressplanet markiert ist
			<br /><b>false</b>, wenn der Planet nicht als Ressplanet markiert ist oder der Benutzer keine Berechtigung hat</td>
		</tr>
		<tr>
			<td>werft</td>
			<td><b>true</b>, wenn der Planet als Werft markiert ist
			<br /><b>false</b>, wenn der Planet nicht als Werft markiert ist oder der Benutzer keine Berechtigung hat</td>
		</tr>
		<tr>
			<td>bunker</td>
			<td><b>true</b>, wenn der Planet als Bunker markiert ist
			<br /><b>false</b>, wenn der Planet nicht als Bunker markiert ist oder der Benutzer keine Berechtigung hat</td>
		</tr>
		</table>
		
		<table class="apidoc">
		<tr>
			<td>system</td>
			<td>Objekt, das Daten &uuml;ber das System enth&auml;lt</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi).'px">
		<tr>
			<td>id</td>
			<td>ID des Systems</td>
		</tr>
		<tr>
			<td>galaxie</td>
			<td>Galaxie des Systems</td>
		</tr>
		<tr>
			<td>x</td>
			<td>X-Koordinate des Systems</td>
		</tr>
		<tr>
			<td>y</td>
			<td>Y-Koordinate des Systems</td>
		</tr>
		<tr>
			<td>z</td>
			<td>Z-Koordinate des Systems</td>
		</tr>
		<tr>
			<td>gescannt</td>
			<td>Unix-Timestamp, zu dem das System zuletzt gescannt wurde</td>
		</tr>
		</table>
		
		<table class="apidoc">
		<tr>
			<td>inhaber</td>
			<td>Objekt, das Daten &uuml;ber den Inhaber des Planeten enth&auml;lt</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi).'px">
		<tr>
			<td>id</td>
			<td>ID des Inhabers
			<br /><b>0</b> - frei
			<br /><b>-1</b> - unbekannt
			<br /><b>-2</b> - unbekannter Lux
			<br /><b>-3</b> - unbekannte Altrasse</td>
		</tr>
		<tr>
			<td>name</td>
			<td>Name des Inhabers
			<br />zus&auml;tzliche Werte: <b>frei, unbekannt, Seze Lux, Altrasse</b></td>
		</tr>
		<tr>
			<td>rasse</td>
			<td>Rasse des Inhabers
			<br /><b>false</b> bei freien oder unbekannten Planeten</td>
		</tr>
		<tr>
			<td>punkte</td>
			<td>Imperiumspunkte des Inhabers
			<br /><b>false</b> bei freien oder unbekannten Planeten</td>
		</tr>
		<tr>
			<td>umodus</td>
			<td>(boolean) ist der Inhaber im Urlaubsmodus?</td>
		</tr>
		<tr>
			<td>geloescht</td>
			<td>(boolean) hat der Inhaber seinen Account gel&ouml;scht?</td>
		</tr>
		<tr>
			<td>allianz</td>
			<td>Objekt, das Daten &uuml;ber die Allianz des Inhabers enth&auml;lt
			<br /><b>false</b>, wenn der Inhaber allianzlos ist oder der Planet frei oder unbekannt ist</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi*2).'px">
		<tr>
			<td>id</td>
			<td>ID der Allianz</td>
		</tr>
		<tr>
			<td>tag</td>
			<td>Tag der Allianz</td>
		</tr>
		<tr>
			<td>name</td>
			<td>Name der Allianz</td>
		</tr>
		<tr>
			<td>status</td>
			<td>Status-Bezeichnung, die die Allianz in der Datenbank hat</td>
		</tr>
		</table>
		
		<table class="apidoc">
		<tr>
			<td>entfernung</td>
			<td>Entfernung des Planeten zum angegebenen Ursprungspunkt (Stunden:Minuten)
			<br /><b>false</b>, wenn kein Ursprungspunkt angegeben wurde</td>
		</tr>
		<tr>
			<td>antrieb</td>
			<td>Antrieb, mit welchem die Entfernungen ausgerechnet wurden
			<br />wurde kein Antrieb angegeben (<i>antr</i>), wird der Standardantrieb des Benutzers genommen</td>
		</tr>
		<tr>
			<td>invasionen</td>
			<td>Array, das Objekte f&uuml;r alle Aktionen enth&auml;lt, die aktuell am Planeten laufen</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi).'px">
		<tr>
			<td>[</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>typ</td>
			<td>Bezeichnung des Aktions-Typs</td>
		</tr>
		<tr>
			<td>ende</td>
			<td>Unix-Timestamp, zu welchem die Aktion endet
			<br /><b>false</b>, wenn das Ende unbekannt ist</td>
		</tr>
		<tr>
			<td>kommentar</td>
			<td>Kommentar der Aktion</td>
		</tr>
		<tr>
			<td>aggressor</td>
			<td>Objekt, das Daten zu dem Spieler enth&auml;lt, der die Aktion durchf&uuml;hrt
			<br /><b>false</b>, wenn der Aggressor unbekannt ist</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi*2).'px">
		<tr>
			<td>id</td>
			<td>ID des Aggressors</td>
		</tr>
		<tr>
			<td>name</td>
			<td>Name des Aggressors</td>
		</tr>
		<tr>
			<td>allianz</td>
			<td>Objekt, das Daten &uuml;ber die Allianz des Aggressors enth&auml;lt
			<br /><b>false</b>, wenn der Aggressor allianzlos ist</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi*3).'px">
		<tr>
			<td>id</td>
			<td>ID der Allianz des Aggressors</td>
		</tr>
		<tr>
			<td>tag</td>
			<td>Tag der Allianz des Aggressors</td>
		</tr>
		</table>
		
		<table class="apidoc" style="margin-left:'.($col_multi).'px">
		<tr>
			<td>]</td>
			<td>&nbsp;</td>
		</tr>
		</table>
		
		<b>]</b>
		
	</div>
	';
	
	
	$tmpl->output();
	
}

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
	// keine Berechtigung für FoW-Ausgleich
	if(!$user->rechte['fow']) {
		$tmpl->content .= '
		<br />
		<div class="center" style="font-weight:bold">Du hast keine Berechtigung, den FoW-Ausgleich zu benutzen!</div>
		<br /><br />';
	}
	$tmpl->content .= '
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
			<li>die .crx-Datei herunterladen (Rechtsklick &rarr; Link speichern unter)</li>
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
			
			<br /><br />
			<p>Solltest du einen Loginfehler angezeigt bekommen, obwohl du in der Datenbank eingeloggt bist, musst du unter
			<br />
			<span style="font-style:italic">Einstellungen &rarr; Datenschutz</span> &nbsp;Cookies von <span style="font-style:italic">'.SERVER.'</span> &nbsp;explizit erlauben oder einen Haken bei&nbsp; <span style="font-style:italic">Cookies von Drittanbietern akzeptieren</span>&nbsp; setzen.</p>
			
		</div>
		<br /><br />
	
		<div class="hl2">
			ODDB Bookmarklet
		</div>
		
		<div class="icontent">
			
			<p>Ein Bookmarklet ist ein Link, der als Favorit gespeichert wird. Wird er aufgerufen, &ouml;ffnet er keine neue Seite, sondern bindet Code in die aktuelle Seite ein.</p>
			<p>Das ODDB Tool wurde als Bookmarklet portiert. Wird es auf einer geeigneten OD-Seite aufgerufen, bietet es die Haupt-Funktionen des ODDB Tools: den Parser und den FoW-Ausgleich. 
				Dadurch k&ouml;nnen die Funktionen des ODDB Tools auch in anderen Browsern oder auf dem iPad genutzt werden.</p>
			<p>Das Bookmarklet ist auf die Adresse dieser Installation und deine Benutzerdaten angepasst. &Auml;nderst du dein Passwort, musst du das Bookmarklet neu einrichten.</p>';
	
	$authToken = str_replace("'", "\\'", h($user->getAuthToken()));
	$authTokenVar = "ODDBAuth".substr(md5(microtime(true)), 0, 8);
	
	$bookmarklet = "javascript:(function(){".$authTokenVar."='".$authToken."';var s=document.createElement('script');s.type='text/javascript';s.src='".ADDR."plugin/bookmarklet.php?authTokenVar=".$authTokenVar."';document.body.appendChild(s);})();";
	
	$tmpl->content .= '
			
			<table style="width:100%">
			<tr>
				<td style="width:50%">
					<p>Adresse des Bookmarklets:</p>
					<textarea class="text tools-bookmarklet-field">'.$bookmarklet.'</textarea>
					<p class="small hint">[kopieren und als Favorit speichern]</p>
				</td>
				<td>	
					Link: <a href="'.$bookmarklet.'" class="bold" title="ODDB Bookmarklet">ODDB Bookmarklet</a> &nbsp; <span class="small hint">[in die Favoritenleiste ziehen]</span>
				</td>
			</tr>
			</table>
			
		</div>';
	
	// ODDB-API
	if($user->rechte['api']) {
		
		// Key auslesen
		$apikey = $user->getApiKey();
		
		
		$tmpl->content .= '
		<div class="hl2">
			ODDB API
		</div>
		<div class="icontent">
			Die ODDB stellt eine eigene API f&uuml;r Erweiterungen und Scripts zur Verf&uuml;gung.
			<br /><br />
			Dein API-Key lautet: 
			<input type="text" class="text apikey" style="width:290px" value="'.$apikey.'" onmouseover="$(this).select()" /> 
			&nbsp; <a class="italic" onclick="toolsPage.changeApiKey()">(Key &auml;ndern)</a>
			<br /><br />
			<a class="link contextmenu bold" data-link="index.php?p=tools&amp;sp=api">&raquo; Entwickler-Dokumentation</a>
		</div>
		';
	}
	
	
	$tmpl->content .= '
		
	</div>
	';
	
	// Ausgabe
	$tmpl->output();
}

?>