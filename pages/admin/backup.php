<?php
/**
 * pages/admin/backup.php
 * Verwaltung -> Backups Import/Export (Inhalt)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


$content =& $csw->data['backup']['content'];
			

$content = '
<div class="hl2">Import / Export</div>
<div class="icontent">
	Dieser Bereich dient dazu, Datenbank-Instanzen erstmals zu bef&uuml;llen, abzugleichen oder umzuziehen.
	<br /><br />
	<b>Die hier erzeugten Exporte beinhalten lediglich die Daten aus System- und Planetenscans, ein komplettes Backup der Instanz ist nur &uuml;ber die MySQL-Datenbank m&ouml;glich!</b>
	<br />
	<p>Folgende Dateien k&ouml;nnen importiert werden:</p>
	<ul>
		<li><b>Hier erzeugte Exporte</b> (auch von anderen ODDB-Installationen)</li>
	</ul>
	<p style="margin-left:2em">
		Importierte System- und Planetenscans werden nur &uuml;bernommen, wenn sie aktueller sind als die Daten dieser Instanz. Damit der Import vollst&auml;ndig funktioniert, sollten alle Galaxien eingetragen sein. Ebenso empfiehlt es sich, vor dem Import die Spielerprofile aller angemeldeten Spieler einzulesen.
	</p>
	
	<ul>
		<li><b>Grunddaten-Exporte von Omega-Day</b> (alle verdeckten Systemscans)</li>
	</ul>
	<p style="margin-left:2em">
		Die Systeme und Planeten werden nur f&uuml;r Galaxien eingetragen, deren Mainscreen noch nicht eingescannt wurde!
	</p>
		
	<br />
	Der Import kann mehrere Minuten dauern.
	
	<br /><br /><br /><br />
	
	<table style="width:100%">
	<tr>
	<td style="width:50%" class="center hl2">
		Export
	</td>
	<td style="width:50%" class="center hl2">
		Import
	</td>
	<tr>
	<td class="center">
		<form onsubmit="return adminPage.backupExport(this)">
			
			<p>Der Export enth&auml;lt:</p>
			<p>
				<select name="mode_gala" size="1" onchange="formHelpers.toggleElement($(this).next(), ($(this).val() > 0))">
					<option value="0">alle Galaxien</option>
					<option value="1">nur bestimmte Galaxien</option>
					<option value="2">alle Galaxien au&szlig;er</option>
				</select>
				
				<input type="text" class="text" name="gala" style="display:none" />
			</p>
			<p>
				<select name="mode_sys" size="1" onchange="formHelpers.toggleElement($(this).next(), ($(this).val() > 0))">
					<option value="0">alle System-Scans</option>
					<option value="1">nur System-Scans der Allianzen</option>
					<option value="2">alle System-Scans au&szlig;er den Allianzen</option>
				</select>
				
				<input type="text" class="text" name="sys" style="display:none" />
			</p>
			<p>
				<select name="mode_plani" size="1" onchange="formHelpers.toggleElement($(this).next(), ($(this).val() > 1))">
					<option value="0">alle Planeten-Scans</option>
					<option value="1">keine Planeten-Scans</option>
					<option value="2">nur Planeten-Scans der Allianzen</option>
					<option value="3">alle Planeten-Scans au&szlig;er den Allianzen</option>
				</select>
				
				<input type="text" class="text" name="plani" style="display:none" />
			</p>
		
			<p class="small hint">
				Jeweils IDs mit Komma getrennt
				<br />
				Bei Galaxien sind auch Bereichsangaben m&ouml;glich, z.B. <i>2-5</i>
				<br />
				f&uuml;r nicht enthaltene Systeme werden auch keine Planeten-Scans exportiert
			</p>
			
			<p>
				<input type="submit" class="button" value="Export erzeugen" />
			</p>
			
		</form>
	</td>
	<td style="font-weight:bold" class="center">
		<a href="index.php?p=admin&amp;sp=backup_import" onclick="window.open(this.href, \'oddbimport\', \'width=500,height=300\'); return false">Import-Fenster &ouml;ffnen</a>
	</td>
	</tr>
	</table>
</div>

<br /><br />
<div class="hl2">Spielerprofile einlesen</div>
<div class="icontent">
	Diese Funktion liest alle Spielerprofile in einem bestimmten ID-Bereich von Omega-Day in die Datenbank ein.
	<br />
	Ein Spielerprofil wird automatisch eingelesen, wenn ein System des Spielers eingescannt wird.
	<br /><br />
	<div class="fcbox center" style="width:500px;padding:12px">
		<form name="settings_insertplayer" onsubmit="return form_send(this, \'index.php?p=admin&amp;sp=settings_insertplayer&amp;ajax\', $(this).siblings(\'.ajax\'))">
		Spieler von ID
		&nbsp;<input type="text" class="smalltext" name="start" />&nbsp; 
		bis
		&nbsp;<input type="text" class="smalltext" name="end" />&nbsp; 
		<input type="submit" class="button" style="width:80px" value="einlesen" />
		</form>
		<div class="ajax center"></div>
	</div>
</div>
';

?>