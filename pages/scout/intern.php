<?php
/**
 * pages/scout/intern.php
 * veraltete Allianzsysteme anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


// Tabellenklasse laden
if(!class_exists('datatable')) {
	include './common/datatable.php';
}


// Content erzeugen
$content =& $csw->data['intern']['content'];

$content = '
	<table style="width:100%">
	<tr>
	<td style="width:50%;vertical-align:top">
	<div class="hl2">
		Allyintern scouten
	</div>
	
	<div class="icontent">
		';

// keine Allianz
if(!$user->allianz) {
	$content .= '
		<br />
		<div class="center" style="font-weight:bold">Du geh&ouml;rst keiner Allianz an!</div>
		<br />';
}
// keine veralteten Systeme
if($user->allianz AND $cache->get('fow_ally'.$user->allianz)) {
	$content .= '
		<br />
		<div class="center" style="font-weight:bold">Alle Systeme deiner Allianz sind aktuell.</div>
		<br />';
}
else if($user->allianz) {
	// Systeme abfragen
	$query = query("
		SELECT
			systemeID,
			systemeX,
			systemeZ,
			systeme_galaxienID,
			systemeUpdate
		FROM
			".PREFIX."systeme
		WHERE
			systemeUpdate < ".(time()-$config['scan_veraltet_ally']*86400)."
			AND systemeAllianzen LIKE '%+".$user->allianz."+%'
		ORDER BY
			systemeUpdate ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// veraltete Allysysteme vorhanden
	if(mysql_num_rows($query)) {
		// Tabellen-Header
		$content .= '
		Hier werden dir alle Systeme deiner Allianz angezeigt, die &auml;lter als '.$config['scan_veraltet_ally'].' Tage sind.
		<br /><br /><br />
		<table class="data" style="margin:auto">
			<tr>
				<th>Gala</th>
				<th>System</th>
				<th>Scan</th>
				<th>&nbsp;</th>
			</tr>';
		while($row = mysql_fetch_assoc($query)) {
			$content .= '
			<tr>
				<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
				<td>'.datatable::system($row['systemeID']).'</td>
					<td>'.datatable::scan($row['systemeUpdate'], $config['scan_veraltet_ally']).'</td>
				<td><a href="'.($user->odServer != '' ? $user->odServer : 'http://www.omega-day.com').'/game/index.php?op=system&amp;sys='.$row['systemeID'].'" target="_blank">[in OD &ouml;ffnen]</a></td>
			</tr>';
		}
		$content .= '
		</table>';
	}
	// alle Allysysteme aktuell
	else {
		$cache->set('fow_ally'.$user->allianz, 1, 3600);
		
		$content .= '
		<br />
		<div class="center" style="font-weight:bold">Alle Systeme deiner Allianz sind aktuell.</div>
		<br />';
	}
	
	//
	// Bergbau-Systeme
	//
	$content .= '
		</div>
		
		<br />
		
		<div class="hl2">
			Veraltete Bergbau-Systeme
		</div>
		
		<div class="icontent">';
	
	$query = query("
		SELECT
			systemeID,
			systemeX,
			systemeZ,
			systeme_galaxienID,
			systemeUpdate
		FROM
			".PREFIX."planeten_schiffe
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = schiffeBergbau
			LEFT JOIN ".PREFIX."planeten
				ON planetenID = schiffe_planetenID
			LEFT JOIN ".PREFIX."systeme
				ON systemeID = planeten_systemeID
		WHERE
			player_allianzenID = ".$user->allianz."
			AND systemeUpdate < ".(time()-$config['scan_veraltet_ally']*86400)."
			AND systemeAllianzen NOT LIKE '%+".$user->allianz."+%'
		GROUP BY
			systemeID
		ORDER BY
			systemeUpdate ASC
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	if(mysql_num_rows($query)) {
		// Tabellen-Header
		$content .= '
		In diesen veralteten Systemen ist ein Bergbauschiff deiner Allianz eingetragen:
		<br /><br /><br />
		<table class="data" style="margin:auto">
			<tr>
				<th>Gala</th>
				<th>System</th>
				<th>Scan</th>
				<th>&nbsp;</th>
			</tr>';
		while($row = mysql_fetch_assoc($query)) {
			$content .= '
			<tr>
				<td>'.datatable::galaxie($row['systeme_galaxienID'], $row['systemeX'], $row['systemeZ']).'</td>
				<td>'.datatable::system($row['systemeID']).'</td>
					<td>'.datatable::scan($row['systemeUpdate'], $config['scan_veraltet_ally']).'</td>
				<td><a href="'.($user->odServer != '' ? $user->odServer : 'http://www.omega-day.com').'/game/index.php?op=system&amp;sys='.$row['systemeID'].'" target="_blank">[in OD &ouml;ffnen]</a></td>
			</tr>';
		}
		$content .= '
		</table>';
	}
	else {
		$content .= '
		<br />
		<div class="center" style="font-weight:bold">Alle Bergbau-Systeme deiner Allianz sind aktuell.</div>
		<br />';
	}
	
}

$content .= '
	</div>
	</td>
	<td style="vertical-align:top">
	<div class="hl2">Inaktive User scouten</div>
	<div class="icontent">
	Mit dieser Funktion kannst du dir alle Systeme eines Spielers anzeigen lassen, der im 8-Tage-Modus ist oder zu dem du keinen Sitter hast, um seine Planeten auf Invasionen zu &uuml;berpr&uuml;fen.
	<br /><br />
	<form action="#" name="scout_inaktiv" onsubmit="form_send(this, \'index.php?p=scout&amp;sp=inaktiv_send&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
	<div class="formcontent center">
		Spieler: <input type="text" class="text center" style="width:90px" name="player" /> &nbsp;
		<span class="small hint">(ID oder Name)</span>
		<br /><br />
		<input type="submit" class="button" value="Systeme anzeigen" />
	</div>
	</form>
	<div class="ajax center"></div>
	</div>
	
	</td>
	</tr>
	</table>';

// Log-Eintrag
if($config['logging'] == 3) {
	insertlog(5, 'lässt sich die veralteten Allianz-Systeme anzeigen');
}


?>