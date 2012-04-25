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
	Dieser Bereich dient dazu, Datenbank-Instanzen abzugleichen oder umzuziehen.
	<br /><br />
	<b>Die hier erzeugten Exporte beinhalten lediglich die Daten aus System- und Planetenscans, ein komplettes Backup der Instanz ist nur &uuml;ber die MySQL-Datenbank m&ouml;glich!</b>
	<br /><br />
	Importierte System- und Planetenscans werden nur &uuml;bernommen, wenn sie aktueller sind als die Daten dieser Instanz. Damit der Import vollst&auml;ndig funktioniert, sollten alle Galaxien eingetragen und alle Systeme mindestens einmal verdeckt gescannt worden sein. Ebenso empfiehlt es sich, vor dem Import die Spielerprofile aller angemeldeten Spieler einzulesen.
	<br /><br />
	Der Import kann mehrere Minuten dauern.
	
	<br /><br /><br /><br />
	
	<table style="width:100%">
	<tr>
	<td style="width:50%;font-weight:bold" class="center">
		<a href="index.php?p=admin&amp;sp=backup_export" onclick="$(this.parentNode).html(\'<img src=&quot;img/layout/ajax.gif&quot; style=&quot;width:24px;height:24px&quot; class=&quot;backupload&quot; />\');window.setTimeout(function(){$(\'.backupload\').fadeOut(500);},2000)">Daten dieser Instanz exportieren und herunterladen</a>
	</td>
	<td style="width:50%;font-weight:bold" class="center">
		<a href="index.php?p=admin&amp;sp=backup_import" onclick="window.open(this.href, \'oddbimport\', \'width=500,height=300\'); return false">Daten in diese Instanz importieren</a>
	</td>
	</tr>
	</table>
	<br /><br />
</div>

<br /><br />
<div class="hl2">Spielerprofile einlesen</div>
<div class="icontent">
	Diese Funktion liest alle Spielerprofile in einem bestimmten ID-Bereich von Omega-Day in die Datenbank ein, die mindestens 2000 Imperiumspunkte haben.
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