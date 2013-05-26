<?php
/**
 * pages/scout.php
 * extern scouten
 * Planeten scouten
 * allyintern scouten, inaktive User scouten
 * über Sitter scouten
 * Kolos finden
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	$_GET['sp'] = 'extern';
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Scouten';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'extern'=>true,
	'planet'=>true,
	'intern'=>true,
	'sitter'=>true,
	'kolo'=>true,
	
	'extern_send'=>true,
	'planet_send'=>true,
	'sitter_send'=>true,
	'kolo_send'=>true,
	'inaktiv_send'=>true
);

 

 
// keine Berechtigung
if(!$user->rechte['scout']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
	$tmpl->output();
}
// 404-Error
else if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX-Funktionen
 */

// extern scouten (AJAX)
else if($_GET['sp'] == 'extern_send') {
	include './pages/scout/extern.php';
}

// Planeten scouten (AJAX)
else if($_GET['sp'] == 'planet_send') {
	include './pages/scout/planet.php';
}

// über Sitter scouten (AJAX)
else if($_GET['sp'] == 'sitter_send') {
	include './pages/scout/sitter.php';
}

// Kolos finden (AJAX)
else if($_GET['sp'] == 'kolo_send') {
	include './pages/scout/kolo.php';
}

// inaktive User scouten (AJAX)
else if($_GET['sp'] == 'inaktiv_send') {
	include './pages/scout/inaktiv.php';
}

/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	// Systeme scouten
	$csw->data['extern'] = array(
		'link'=>'index.php?p=scout&sp=extern',
		'bg'=>'background-image:url(img/layout/csw_scout.png)',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				Systeme scouten
			</div>
			
			<div class="icontent">
				<form action="#" name="scout_extern" onsubmit="form_send(this, \'index.php?p=scout&amp;sp=extern_send&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
				<div class="formcontent">
					Startpunkt: <input type="text" class="text center tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:90px" name="start" /> &nbsp;
					<span class="small hint">(Planet, System oder Koordinaten)</span>
					<br />
					Antrieb: <input type="text" class="smalltext" name="antrieb" value="'.$user->settings['antrieb'].'" />
					<br /><br />
					die n&auml;chsten 
					&nbsp;<input type="text" class="smalltext" name="syscount" value="15" />&nbsp; 
					Systeme anzeigen &auml;lter als 
					&nbsp;<input type="text" class="smalltext" name="days" value="'.$config['scan_veraltet'].'" />&nbsp; 
					Tage
					&nbsp; &nbsp;
					(<input type="checkbox" name="hidereserv" checked="checked" /> 
					<span class="togglecheckbox" data-name="hidereserv">reservierte Systeme ausblenden</span>)
					<br /><br />
					<div class="center">
						<input type="submit" class="button" value="Scoutziele anzeigen" />
					</div>
				</div>
				</form>
				<div class="ajax center"></div>
			</div>
		'
	);
	
	// Planeten scouten
	$csw->data['planet'] = array(
		'link'=>'index.php?p=scout&sp=planet',
		'bg'=>'background-image:url(img/layout/csw_scout.png);background-position:-150px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>''
	);
	
	$csw->data['planet']['content'] = '<div class="hl2">
				Planeten scouten
			</div>
			
			<div class="icontent" style="padding:3px">
				<form action="#" name="scout_extern" onsubmit="form_send(this, \'index.php?p=scout&amp;sp=planet_send&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
				<div class="formcontent">
					<table style="width:100%">
					<tr>
					<td style="width:50%;vertical-align:top">
					Startpunkt: <input type="text" class="text center tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:90px" name="start" /> &nbsp;
					<span class="small hint">(Planet, System oder Koordinaten)</span>
					<br />
					Antrieb: <input type="text" class="smalltext" name="antrieb" value="'.$user->settings['antrieb'].'" />
					<br /><br />
					die n&auml;chsten 
					&nbsp;<input type="text" class="smalltext" name="count" value="25" />&nbsp; 
					Planeten anzeigen &auml;lter als 
					&nbsp;<input type="text" class="smalltext" name="days" value="'.$config['scan_veraltet'].'" />&nbsp; 
					Tage
					<br />
					<input type="checkbox" name="hidereserv" checked="checked" /> 
					<span class="togglecheckbox" data-name="hidereserv">Planeten in reservierten Systemen ausblenden</span>
					</td>
					<td style="vertical-align:top">
					nur Planeten anzeigen
					<br />
					des Spielers <input type="text" class="text" style="width:90px" name="player" /> <span class="small hint">(ID oder Name)</span>
					<br />
					der Allianz <input type="text" class="text" style="width:90px" name="ally" /> <span class="small hint">(ID, Tag oder Name)</span>
					<br />
					<br />
					mit Status <select name="status" size="1">
					<option value="-1">alle</option>';
	foreach($status as $key=>$name) {
		$csw->data['planet']['content'] .= '<option value="'.$key.'">'.$name.'</option>';
	}
	$csw->data['planet']['content'] .= '
					</select>
					<br />
					der Rasse <select name="rasse" size="1">
					<option value="-1">alle</option>
					<option value="0">alle Altrassen</option>';
	foreach($rassen as $key=>$name) {
		$csw->data['planet']['content'] .= '<option value="'.$key.'">'.$name.'</option>';
	}
	$csw->data['planet']['content'] .= '
					</select>
					</td>
					</tr>
					</table>
					<br />
					<div class="center">
						<input type="submit" class="button" value="Scoutziele anzeigen" />
					</div>
				</div>
				</form>
				<div class="ajax center"></div>
			</div>';
	
	// allyintern scouten (Content wird nachgeladen)
	$csw->data['intern'] = array(
		'link'=>'index.php?p=scout&sp=intern',
		'bg'=>'background-image:url(img/layout/csw_scout.png);background-position:-300px 0px',
		'reload'=>'true',
		'width'=>650,
		'content'=>''
	);
	
	$timestamp = time();
	// über Sitter scouten
	$csw->data['sitter'] = array(
		'link'=>'index.php?p=scout&sp=sitter',
		'bg'=>'background-image:url(img/layout/csw_scout.png);background-position:-450px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				&Uuml;ber Sitter scouten
			</div>
			
			<div class="icontent">
				Hier kannst du alle Systeme einer Allianz anzeigen lassen, die &auml;lter als '.$config['scan_veraltet_ally'].' Tage sind.
				<br /><br />
				<form action="#" name="scout_sitter" id="scout_sitter'.$timestamp.'" onsubmit="form_send(this, \'index.php?p=scout&amp;sp=sitter_send&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
				<div class="formcontent center">
					Allianz: <input type="text" class="text center" style="width:90px" name="allianz" /> &nbsp;
					<span class="small hint">(ID, Tag oder Name)</span>
					<br /><br />
					<input type="submit" class="button" value="Systeme anzeigen" />
				</div>
				</form>
				<div class="ajax center"></div>
			</div>
		'
	);
	
	// Kolos finden
	$csw->data['kolo'] = array(
		'link'=>'index.php?p=scout&sp=kolo',
		'bg'=>'background-image:url(img/layout/csw_scout.png);background-position:-600px 0px',
		'reload'=>'false',
		'width'=>650,
		'content'=>'
			<div class="hl2">
				Kolos / BBS / Terraformer in eigenen Systemen finden
			</div>
			
			<div class="icontent">
				<form action="#" name="scout_kolo" onsubmit="form_send(this, \'index.php?p=scout&amp;sp=kolo_send&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
				<div class="fcbox" style="width:520px;padding:10px">
					<div class="formcontent" style="margin-left:60px">
						Zeige alle Systeme
						<br />
						<input type="radio" name="typ" value="0" checked="checked" /> 
						der eigenen Allianz
						<br />
						<input type="radio" name="typ" value="1" /> 
						der Allianz 
						<input type="text" class="text center" style="width:90px" name="allianz" /> &nbsp;
						<span class="small hint">(ID, Tag oder Name)</span>
						<br />
						<input type="checkbox" name="free" checked="checked" /> <span class="togglecheckbox" data-name="free">die freie Planeten enthalten</span> &nbsp;
						(optionale Mindestgröße <input type="text" class="smalltext" name="gr" /> )
						<br />
						<input type="checkbox" name="findsysally" /> <span class="togglecheckbox" data-name="findsysally">die Planeten der Allianz</span> &nbsp;
						<input type="text" class="smalltext" name="sysally" /> &nbsp;
						<span class="togglecheckbox" data-name="findsysally">enthalten</span> &nbsp;
						<span class="small hint">(ID, Tag oder Name)</span>
						<br />
						deren Scan &auml;lter als 
						&nbsp;<input type="text" class="smalltext" name="stunden" value="24" />&nbsp;
						Stunden ist
					</div>
					<div class="center" style="margin-top:15px">
						<input type="submit" class="button" value="Systeme anzeigen" />
					</div>
				</div>
				</form>
				<div class="ajax center"></div>
			</div>
		'
	);
	
	
	// Content für allyintern scouten erzeugen
	if($_GET['sp'] == 'intern' AND isset($csw->data['intern'])) {
		include './pages/scout/intern.php';
	}
	
	// Script für Sitter scouten
	if($_GET['sp'] == 'sitter' AND isset($csw->data['sitter']) AND isset($_GET['ally'])) {
		$tmpl->script = "
			$('#scout_sitter".$timestamp." .text').val('".$_GET['ally']."');
			$('#scout_sitter".$timestamp."').submit();";
	}
	
	
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