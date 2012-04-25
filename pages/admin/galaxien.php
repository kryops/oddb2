<?php
/**
 * pages/admin/galaxien.php
 * Verwaltung -> Galaxieverwaltung (Inhalt)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// Galaxien einparsen
if($user->rechte['verwaltung_galaxien']) {
	$csw->data['galaxien']['content'] .= '
<div class="hl2">neue Galaxie eintragen</div>
<div class="icontent">
	Hier kannst du neue Galaxien eintragen, indem du ihren HTML-Mainscreen einscannst. Du kannst auch den Quelltext von verschmolzenen Galaxien einscannen, wenn noch nicht alle Systeme der vorherigen Galaxien in der Datenbank eingetragen waren.
	<br />
	Galaxien werden nicht automatisch verschmolzen. Wenn du den Mainscreen einer verschmolzenen Galaxie einscannst, werden dir aber die Galaxien angezeigt, die wahrscheinlich verschmolzen wurden.
</div>
<br />
<div class="fcbox" style="width:92%;line-height:28px;padding:10px">
	<form action="#" onsubmit="if(this.gala.value){window.open(\'http://www.omega-day.com/game/?op=main&amp;order=id&amp;first=0&amp;last=100000&amp;gtyp=2&amp;viewgalaxy=\'+this.gala.value)};return false">
		Mainscreen f&uuml;r Galaxie <input type="text" class="smalltext" name="gala" style="width:40px" /> <input type="submit" class="button" value="&ouml;ffnen">
	</form>
</div>

<br />
<div class="icontent" style="text-align:center;min-width:500px">
	<form action="#">
		<textarea name="input" style="width:95%;height:200px;margin:auto"></textarea>
		<br /><br />';
	// Verschmelzung reparieren
	if($user->rechte['verwaltung_galaxien2']) {
		$csw->data['galaxien']['content'] .= '
		<input type="checkbox" name="repair" /> 
		versuchen, fehlgeschlagene Verschmelzungen zu reparieren (Gates werden gel&ouml;scht)
		<br /><br />
	';
	}
	$csw->data['galaxien']['content'] .= '
		<input type="button" class="button" value="Mainscreen einscannen" style="width:140px" onclick="quelltext_mainscreen(this.parentNode, $(this.parentNode).siblings(\'.ajax\'));this.disabled=true;window.setTimeout(\'$(\\\'.button\\\').removeAttr(\\\'disabled\\\')\', 3000)" />
	</form>
	<br />
	<div class="ajax"></div>
</div>';
}
// Galaxien verschmelzen oder löschen
if($user->rechte['verwaltung_galaxien2']) {
	$csw->data['galaxien']['content'] .= '
<br />

<div class="hl2">Galaxien verschmelzen</div>
<div class="icontent">
	Bei der Verschmelzung werden alle eingetragenen Gates der beteiligten Galaxien gel&ouml;scht.
	<br /><br />
	<form action="#" name="test" onsubmit="if(window.confirm(\'Sollen die Galaxien wirklich verschmolzen werden?\')){return form_send(this, \'index.php?p=admin&amp;sp=galaxien_merge&amp;ajax\', $(this).siblings(\'.ajax\'));}else{return false;}">
	Die Galaxien &nbsp;<input type="text" class="text center" name="source" /> 
	&nbsp;in Galaxie&nbsp;
	<input type="text" class="smalltext" name="dest" />
	&nbsp; 
	<input type="submit" class="button" value="verschmelzen" />
	<div class="small hint" style="margin-left:110px">(mit Komma getrennt)</div>
	</form>
	'.($user->rechte['verwaltung_galaxien'] ? '<br />Um eine Verschmelzung r&uuml;ckg&auml;ngig zu machen, musst du die Mainscreens der einzelnen Galaxien neu einscannen, w&auml;hrend du die Funktion zum Reparieren fehlgeschlagener Verschmelzungen aktiviert hast.' : '').'
	<div class="ajax center"></div>
</div>

<br /><br />

<div class="hl2">Galaxie l&ouml;schen</div>
<div class="icontent">
	Wurde eine Galaxie fehlerhaft eingescannt oder aus OD entfernt, kannst du sie hier l&ouml;schen.
	<br />
	<b>Die L&ouml;schung kann nicht r&uuml;ckg&auml;ngig gemacht werden!</b>
	<br /><br />
	<form action="#" name="test" onsubmit="if(window.confirm(\'Soll die Galaxie wirklich gelöscht werden?\\nDie Aktion kann nicht rückgängig gemacht werden!\')){return form_send(this, \'index.php?p=admin&amp;sp=galaxien_del&amp;ajax\', $(this).siblings(\'.ajax\'));}else{return false;}">
	Die Galaxie &nbsp;<input type="text" class="smalltext" name="gala" /> 
	&nbsp; 
	<input type="submit" class="button" value="l&ouml;schen" />
	</form>
	<div class="ajax center"></div>
</div>';
}

?>