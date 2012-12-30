<?php
/**
 * pages/admin/settings.php
 * Verwaltung -> Einstellungen (Inhalt)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


$content =& $csw->data['settings']['content'];
			
// Konfigurations-Klasse laden
if(!class_exists('config')) {
	include './common/config.php';
}

// Konfiguration laden, nicht besetze Werte mit leeren Strings füllen
$c = $gconfig;
foreach($c as $key=>$val) {
	$c[$key] = '';
}
$c = array_merge($c, config::getcustom(INSTANCE));

$content = '
<div class="hl2">Einstellungen</div>
<div class="icontent">
	Hier kannst du einen Teil der Grundeinstellungen deiner DB-Instanz verändern. Die &uuml;brigen Einstellungen k&ouml;nnen nur &uuml;ber die Administrationsoberfl&auml;che oder die Konfigurationsdateien ge&auml;ndert werden.
	<br /><br />
	Die eingeklammerten Werte hinter den Eingabefeldern sind die Standardwerte.
	<br />
	L&auml;sst du diese Felder leer, nimmt die Konfiguration die eingeklammerten Werte an (k&ouml;nnen im Administrationsbereich ge&auml;ndert werden).
	<br /><br />
	<form onsubmit="form_send(this, \'index.php?p=admin&amp;sp=settings_send&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
	<div style="width:80%;margin:auto">
		Nachricht, die f&uuml;r alle auf der &Uuml;bersichtsseite angezeigt werden soll:
		<textarea name="oviewmsg" style="width:100%;margin:auto;margin-top:10px;height:50px">'.(isset($config['oviewmsg']) ? htmlspecialchars($config['oviewmsg']) : '').'</textarea>
	</div>
	<br />
	<table class="leftright" style="width:100%">
	<tr>
		<th colspan="2">Scan-Einstellungen</th>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td style="width:55%">normale Scans veraltet nach (Tage)</td>
		<td><input type="text" class="smalltext tooltip" name="scan_veraltet" value="'.htmlspecialchars($c['scan_veraltet'], ENT_COMPAT, 'UTF-8').'" /> <span class="small hint">('.htmlspecialchars($gconfig['scan_veraltet'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td>Ally-Scans veraltet nach (Tage)</td>
		<td><input type="text" class="smalltext tooltip" name="scan_veraltet_ally" value="'.htmlspecialchars($c['scan_veraltet_ally'], ENT_COMPAT, 'UTF-8').'" /> <span class="small hint">('.htmlspecialchars($gconfig['scan_veraltet_ally'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td>Planeten&uuml;bersicht veraltet nach (Stunden)</td>
		<td><input type="text" class="smalltext tooltip" name="scan_veraltet_oview" value="'.htmlspecialchars($c['scan_veraltet_oview'], ENT_COMPAT, 'UTF-8').'" /> <span class="small hint">('.htmlspecialchars($gconfig['scan_veraltet_oview'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td>Einstellungen und Sitter veraltet nach (Tage)</td>
		<td><input type="text" class="smalltext tooltip" name="scan_veraltet_einst" value="'.htmlspecialchars($c['scan_veraltet_einst'], ENT_COMPAT, 'UTF-8').'" /> <span class="small hint">('.htmlspecialchars($gconfig['scan_veraltet_einst'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td>Flotten&uuml;bersicht veraltet nach (Tage)</td>
		<td><input type="text" class="smalltext tooltip" name="scan_veraltet_flotten" value="'.htmlspecialchars($c['scan_veraltet_flotten'], ENT_COMPAT, 'UTF-8').'" /> <span class="small hint">('.htmlspecialchars($gconfig['scan_veraltet_flotten'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td>Scan der Einnahmen veraltet nach (Tage)</td>
		<td><input type="text" class="smalltext tooltip" name="scan_veraltet_geld" value="'.htmlspecialchars($c['scan_veraltet_geld'], ENT_COMPAT, 'UTF-8').'" /> <span class="small hint">('.htmlspecialchars($gconfig['scan_veraltet_geld'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td>Forschung veraltet nach (Tage)</td>
		<td><input type="text" class="smalltext tooltip" name="scan_veraltet_forschung" value="'.htmlspecialchars($c['scan_veraltet_forschung'], ENT_COMPAT, 'UTF-8').'" /> <span class="small hint">('.htmlspecialchars($gconfig['scan_veraltet_forschung'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<th colspan="2">Sicherheit</th>
	</tr>
	<tr>
		<td>Spieler automatisch freischalten</td>
		<td><select name="disable_freischaltung" size="1">
		<option value=""></option>
		<option value="0"'.($c['disable_freischaltung'] === false ? ' selected="selected"' : '').'>nein</option>
		<option value="1"'.($c['disable_freischaltung'] ? ' selected="selected"' : '').'>ja</option>
		</select> <span class="small hint">('.($gconfig['disable_freischaltung'] ? 'ja' : 'nein').')</span></td>
	</tr>
	<tr>
		<td>Autofreischaltung Rechtelevel</td>
		<td><select name="disable_freischaltung_level" size="1">
		<option value=""></option>';
	foreach($rechte as $key=>$data) {
		$content .= '
		<option value="'.$key.'"'.($c['disable_freischaltung_level'] === $key ? ' selected="selected"' : '').'>'.htmlspecialchars($data['name'], ENT_COMPAT, 'UTF-8').'</option>';
	}
	$content .= '
		</select> <span class="small hint">('.htmlspecialchars($rechte[$gconfig['disable_freischaltung_level']]['name'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	<tr>
		<td>Logging-Stufe</td>
		<td><select name="logging" size="1" class="tooltip" tooltip="[vorsichtig] loggt alle Aktionen eines Spielers, [paranoid] loggt zus&auml;tzlich alle Seiten, die ein Spieler ansieht (z.B. Invasionen oder Spielerlisten)">
		<option value=""></option>
		<option value="0"'.($c['logging'] === 0 ? ' selected="selected"' : '').'>deaktiviert</option>
		<option value="1"'.($c['logging'] == 1 ? ' selected="selected"' : '').'>nur Verwaltung</option>
		<option value="2"'.($c['logging'] == 2 ? ' selected="selected"' : '').'>vorsichtig</option>
		<option value="3"'.($c['logging'] == 3 ? ' selected="selected"' : '').'>paranoid</option>
		</select> <span class="small hint">(';
	$data = array(
		0=>'deaktiviert',
		1=>'nur Verwaltung',
		2=>'vorsichtig',
		3=>'paranoid'
	);
	$content .= $data[$gconfig['logging']].')</span></td>
	</tr>
	<tr>
		<td>Speicherdauer des Logs (Tage)</td>
		<td><input type="text" class="smalltext tooltip" name="logging_time" value="'.htmlspecialchars($c['logging_time'], ENT_COMPAT, 'UTF-8').'" tooltip="Zeit in Tagen, wie lange Log-Eintr&auml;ge gespeichert bleiben sollen" /> <span class="small hint">('.htmlspecialchars($gconfig['logging_time'], ENT_COMPAT, 'UTF-8').')</span></td>
	</tr>
	</table>
	
	<br /><br />
	<div class="center">
		<input type="submit" class="button" style="width:150px" value="Konfiguration speichern" />
	</div>
	</form>
	<br />
	<div class="ajax center"></div>
	
</div>';

?>