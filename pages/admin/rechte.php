<?php
/**
 * pages/admin/rechte.php
 * Verwaltung -> Berechtigungen (Inhalt)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


$content =& $csw->data['rechte']['content'];
			
// Konfigurations-Klasse laden
if(!class_exists('config')) {
	include './common/config.php';
}


$content = '
<div class="hl2">Berechtigungen</div>
<div class="icontent">
	Hier kannst du die Rechtelevel dieser Instanz bearbeiten. Die eingestellten Berechtigungen k&ouml;nnen von den allianzspezifischen Berechtigungen eingeschr&auml;nkt sowie von den userspezifischen Berechtigungen &uuml;berschrieben werden.
	<br /><br /><br />
	
	<table class="data small2 thighlight" style="margin:auto">
	<tr>
	<th>Rechtelevel</th>
	<th>bearbeiten</th>
	</tr>';

// Rechtelevel durchgehen
foreach($rechte as $key=>$data) {
	$content .= '
	<tr>
	<td class="rechtelevel'.$key.'" style="text-align:left">
		<b>'.htmlspecialchars($data['name'], ENT_COMPAT, 'UTF-8').'</b>
		<br />
		'.htmlspecialchars($data['desc'], ENT_COMPAT, 'UTF-8').'
	</td>
	<td>
		'.($key != 4 ? '<img src="img/layout/leer.gif" class="link winlink contextmenu icon hoverbutton configbutton" data-link="index.php?p=admin&amp;sp=rechte_edit&amp;id='.$key.'" title="Rechtelevel bearbeiten" />' : 'nicht m&ouml;glich').'
	</td>
	</tr>';
}

$content .= '
	</table>
</div>
';

?>