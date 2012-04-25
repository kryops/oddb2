<?php
/**
 * pages/strecken.php
 * Strecken- und Flugberechnung
 * - Entfernung berechnen
 * - die nächsten Systeme anzeigen
 * - aktuelle Flugposition berechnen
 * - nächster / entferntester Planet von Spieler / Allianz / Meta
 * schnellster Weg
 * Saveroutengenerator
 * System-Überflug
 * Flugzeiten-Matrix
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	if($user->rechte['strecken_flug']) $_GET['sp'] = 'flug';
	else if($user->rechte['strecken_weg']) $_GET['sp'] = 'weg';
	else if($user->rechte['strecken_saveroute']) $_GET['sp'] = 'saveroute';
	else if($user->rechte['strecken_ueberflug']) $_GET['sp'] = 'ueberflug';
	else $_GET['sp'] = 'flug';
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Strecken';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'flug'=>true,
	'flug_entf'=>true,
	'flug_next'=>true,
	'flug_pos'=>true,
	'flug_search'=>true,
	
	'weg'=>true,
	'weg_send'=>true,
	
	'saveroute'=>true,
	'saveroute_send'=>true,
	
	'ueberflug'=>true,
	'ueberflug_send'=>true,
	
	'matrix'=>true,
	'matrix_send'=>true
);



// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX-Funktionen
 */

// Entfernung berechnen (AJAX)
else if($_GET['sp'] == 'flug_entf') {
	include './pages/strecken/flug.php';
}

// die nächsten Systeme anzeigen (AJAX)
else if($_GET['sp'] == 'flug_next') {
	include './pages/strecken/flug.php';
}

// Flugposition berechnen (AJAX)
else if($_GET['sp'] == 'flug_pos') {
	include './pages/strecken/flug.php';
}

// nächsten / entferntesten Planet finden (AJAX)
else if($_GET['sp'] == 'flug_search') {
	include './pages/strecken/flug.php';
}

// schnellsten Weg berechnen (AJAX)
else if($_GET['sp'] == 'weg_send') {
	include './pages/strecken/weg.php';
}

// Saveroute berechnen (AJAX)
else if($_GET['sp'] == 'saveroute_send') {
	include './pages/strecken/saveroute.php';
}

// System-Überflug (AJAX)
else if($_GET['sp'] == 'ueberflug_send') {
	include './pages/strecken/ueberflug.php';
}

// Entfernung berechnen (AJAX)
else if($_GET['sp'] == 'matrix_send') {
	include './pages/strecken/matrix.php';
}

/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	// Strecken- und Flugberechnung
	if($user->rechte['strecken_flug']) $csw->data['flug'] = array(
		'link'=>'index.php?p=strecken&sp=flug',
		'bg'=>'background-image:url(img/layout/csw_strecken.png)',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				Strecken- und Flugberechnung
			</div>
			
			<div class="icontent">
				<form action="#" name="strecken_flug" onsubmit="form_send(this, this.action, $(this).siblings(\'.ajax\'));return false">
				<div class="formcontent">
					Startpunkt: <input type="text" class="text center tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:90px" name="start" /> &nbsp;
					<span class="small hint">(Planet, System oder Koordinaten)</span>
					<br />
					Antrieb: <input type="text" class="smalltext" name="antrieb" value="'.$user->settings['antrieb'].'" />
					<hr />
					Entfernung nach &nbsp;<input type="text" class="text center enter tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:90px" name="dest_entf" data-action="index.php?p=strecken&amp;sp=flug_entf" /> &nbsp;
					<input type="button" class="button" value="berechnen" onclick="form_submit(this, \'index.php?p=strecken&amp;sp=flug_entf\')" /> &nbsp;
					<span class="small hint">(Planet, System oder Koordinaten)</span>
					<hr />
					Die n&auml;chsten &nbsp;<input type="text" class="smalltext enter" name="syscount" value="15" data-action="index.php?p=strecken&amp;sp=flug_next" /> &nbsp;Systeme 
					&nbsp;<input type="button" class="button" value="anzeigen" onclick="form_submit(this, \'index.php?p=strecken&amp;sp=flug_next\')" />
					<hr />
					Aktuelle Flugposition nach &nbsp;<input type="text" class="text center enter tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:90px" name="dest_flug" data-action="index.php?p=strecken&amp;sp=flug_pos" /> &nbsp; 
					Ankunft &nbsp; 
					<select name="postag" size="1">
						<option value="0">heute</option>
						<option value="1">morgen</option>
					</select> &nbsp; 
					<input type="text" class="smalltext enter" style="width:40px" name="posstunde" data-action="index.php?p=strecken&amp;sp=flug_pos" />:<input type="text" class="smalltext enter" style="width:40px" name="posminute" data-action="index.php?p=strecken&amp;sp=flug_pos" />:<input type="text" class="smalltext enter" style="width:40px" name="possekunde" data-action="index.php?p=strecken&amp;sp=flug_pos" />
					
					&nbsp;<input type="button" class="button" value="berechnen" onclick="form_submit(this, \'index.php?p=strecken&amp;sp=flug_pos\')" />
					<hr />
					Die&nbsp;
					<select name="search" size="1">
						<option value="0">n&auml;chsten</option>
						<option value="1">entferntesten</option>
					</select>&nbsp;
					<input type="text" class="smalltext enter" name="searchcount" data-action="index.php?p=strecken&amp;sp=flug_search" value="5" />&nbsp;
					Planeten von&nbsp;
					<select name="searchtyp" size="1" onchange="if(this.value > 4){$(this).siblings(\'.searchid\').css(\'display\', \'inline\').focus()}else{$(this).siblings(\'.searchid\').css(\'display\', \'none\')}">
						<option value="1">dir selbst</option>
						<option value="2">deiner Allianz</option>
						<option value="3">deiner Meta</option>
						<option value="4">feindlichen Allianzen</option>
						<option value="5">dem Spieler</option>
						<option value="6">der Allianz</option>
					</select>&nbsp;
					<input type="text" class="smalltext enter searchid" name="searchid" style="width:90px;display:none" data-action="index.php?p=strecken&amp;sp=flug_search" />&nbsp;
					<input type="button" class="button" value="anzeigen" onclick="form_submit(this, \'index.php?p=strecken&amp;sp=flug_search\')" />
					<br />
					<span class="small hint">(bei Spieler und Allianz auch mehrere IDs mit Komma getrennt m&ouml;glich)</span>
				</div>
				</form>
				<br />
				<div class="ajax"></div>
			</div>
		'
	);
	
	// schnellster Weg (Content wird nachgeladen)
	if($user->rechte['strecken_weg']) {
		$csw->data['weg'] = array(
			'link'=>'index.php?p=strecken&sp=weg',
			'bg'=>'background-image:url(img/layout/csw_strecken.png);background-position:-150px 0px',
			'reload'=>'false',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt
		if($_GET['sp'] == 'weg') {
			include './pages/strecken/weg.php';
		}
	}
	
	// Saveroutengenerator
	if($user->rechte['strecken_saveroute']) $csw->data['saveroute'] = array(
		'link'=>'index.php?p=strecken&sp=saveroute',
		'bg'=>'background-image:url(img/layout/csw_strecken.png);background-position:-300px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				Saveroutengenerator
			</div>
			
			<div class="icontent">
				<b>Hinweis</b>: Die Option &quot;Planeten deiner Meta&quot; bezieht sich auf Planeten von Allianzen, die den Status &quot;Meta&quot; haben.
				<br /><br /><br />
				<form action="#" name="strecken_saveroute" onsubmit="return form_send(this, \'index.php?p=strecken&amp;sp=saveroute_send&amp;ajax\', $(this).siblings(\'.ajax\'))">
				<div class="fcbox formcontent center" style="padding:10px;width:600px">
					Galaxie: <input type="text" class="smalltext" name="gala" /> &nbsp; 
					Antrieb: <input type="text" class="smalltext" name="antrieb" value="'.$user->settings['antrieb'].'" /> &nbsp;
					max. Ergebnisse: <input type="text" class="smalltext" name="count" value="10" />
					<div style="text-align:left;margin:12px 0px 15px 90px">
						Saveroute berechnen f&uuml;r: 
						&nbsp;<select name="data" size="1">
							<option value="meta">Planeten deiner Meta</option>
							<option value="ally">Planeten deiner Allianz</option>
							<option value="user">eigene Planeten</option>
							<option value="free">freie Planeten</option>
							<option value="all">alle Planeten</option>
						</select>
						<br />
						oder Allianzen manuell eintragen: <input type="text" class="text center" name="datamanuell" /> 
						<br />
						<div class="small hint" style="margin:-8px 0px 0px 140px">(IDs mit Komma getrennt, 0 f&uuml;r freie und allianzlose Planeten)</div>
						<input type="checkbox" name="allysonly" /> <span class="togglecheckbox" data-name="allysonly">in den Systemen d&uuml;rfen nur die ausgewählten Allianzen vertreten sein</span>
						<br />
						<input type="checkbox" name="mgates" /> <span class="togglecheckbox" data-name="mgates">in den Systemen m&uuml;ssen sich Myrigates befinden</span>
					</div>
					
					<input type="submit" class="button" value="Saverouten berechnen" />
				</div>
				</form>
				
				<div class="ajax" style="line-height:20px"></div>
			</div>
		'
	);
	
	// System-Überflug
	if($user->rechte['strecken_ueberflug']) $csw->data['ueberflug'] = array(
		'link'=>'index.php?p=strecken&sp=ueberflug',
		'bg'=>'background-image:url(img/layout/csw_strecken.png);background-position:-450px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				System-&Uuml;berflug
			</div>
			
			<div class="icontent">
				Mit dieser Funktion kann man berechnen, wie man ein System mit m&ouml;glichst geringer Anfluzgszeit erreicht, indem man seine Schiffe zuerst zu einem anderen Planeten schickt und dann im Flug umlenkt.
				<br /><br />
				Die Berechnungen gelten f&uuml;r dieselben Planetenpositionen innerhalb der Systeme. Will man einen Planeten links im System anfliegen, sollte man von einem Planeten links losfliegen und vor dem Umlenken ebenfalls einen Planeten links im System anfliegen, um die Anflugszeit nicht zu verl&auml;ngern.
				<br /><br /><br />
				
				<form action="#" name="strecken_ueberflug" onsubmit="return form_send(this, \'index.php?p=strecken&amp;sp=ueberflug_send&amp;ajax\', $(this).siblings(\'.ajax\'))">
				<div class="fcbox formcontent center" style="padding:10px;width:600px">
					Startpunkt (optional): <input type="text" class="text center tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:80px" name="start" />
					 &nbsp; &nbsp; 
					Zielsystem: <input type="text" class="text center" style="width:80px" name="dest" />
					 &nbsp; &nbsp; 
					Antrieb: <input type="text" class="smalltext" name="antrieb" value="'.$user->settings['antrieb'].'" />
					<div class="small hint" style="margin:-8px 190px 6px 0px">
						(System oder Planet)
					</div>
					max. Ergebnisse: <input type="text" class="smalltext" name="count" value="15" /> &nbsp; &nbsp;
					Berechnungen beschr&auml;nken auf die 
					<select name="range" size="1">
						<option>10</option>
						<option>20</option>
						<option selected="selected">50</option>
						<option>100</option>
						<option>200</option>
					</select>
					n&auml;chsten Systeme
					<div class="small hint" style="margin:-8px 80px 12px 0px;text-align:right">
						(falls kein Startpunkt angegeben)
					</div>
					<input type="submit" class="button" value="&Uuml;berflug berechnen" />
				</div>
				</form>
				<div class="ajax" style="line-height:20px"></div>
			</div>
		'
	);
	
	// Flugzeiten-Matrix
	if($user->rechte['strecken_flug']) $csw->data['matrix'] = array(
		'link'=>'index.php?p=strecken&sp=matrix',
		'bg'=>'background-image:url(img/layout/csw_strecken.png);background-position:-600px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				Flugzeiten-Matrix
			</div>
			
			<div class="icontent">
				Die Flugzeiten-Matrix zeigt f&uuml;r zwei Listen von Planeten oder Systemen die jeweilgen Flugzeiten an.
				<br />
				F&uuml;r jeden Eintrag in Liste 1 wird die Entfernung zu jedem Eintrag in Liste 2 berechnet.
				<br />
				Die schnellste Verbindung wird gr&uuml;n hinterlegt.
				<br /><br /><br />
				
				<form action="#" name="matrix" onsubmit="form_send(this, \'index.php?p=strecken&amp;sp=matrix_send&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
				<div class="formcontent">
					Liste 1 (senkrecht): <input type="text" class="text center tooltip" data-tooltip="mit Kommas getrennt; bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:600px" name="list1" /> &nbsp;
					<br />
					Liste 2 (waagrecht): <input type="text" class="text center tooltip" data-tooltip="mit Kommas getrennt; bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:600px" name="list2" /> &nbsp;
					<br /><br />
					Antrieb: <input type="text" class="smalltext" name="antrieb" value="'.$user->settings['antrieb'].'" />
					<br /><br />
					<div class="center">
						<input type="submit" class="button" value="Entfernungen berechnen" />
					</div>
				</div>
				</form>
				<br />
				<div class="ajax"></div>
			</div>
		'
	);
	
	
	// nur Unterseite ausgeben
	if(isset($_GET['switch'])) {
		if(isset($csw->data[$_GET['sp']])) {
			$tmpl->content = $csw->data[$_GET['sp']]['content'];
		}
		else {
			$tmpl->error = 'Du hast keine Berechtigung!';
		}
	}
	// keine Berechtigung
	else if(!isset($csw->data[$_GET['sp']])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Contentswitch ausgeben
	else {
		$tmpl->content = $csw->output();
	}
	$tmpl->output();
}
?>