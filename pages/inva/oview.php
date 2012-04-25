<?php
/**
 * pages/inva/oview.php
 * veraltete Planetenübersichten
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


$content =& $csw->data['oview']['content'];

$content = '
	<div class="hl2">Veraltete Planeten&uuml;bersichten</div>';

// Bedingungen aufstellen
$conds = array();

if($user->protectedAllies) {
	$conds[] = "user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
	$conds[] = "user_allianzenID NOT IN(".implode(', ', $user->protectedAllies).")";
}

if(!$user->rechte['show_player_db_ally'] AND $user->allianz) {
	$conds[] = "user_allianzenID != ".$user->allianz;
}
if(!$user->rechte['show_player_db_meta'] AND $user->allianz) {
	$conds[] = "(user_allianzenID = ".$user->allianz." OR statusStatus = 0 OR statusStatus != ".$status_meta.")";
}
if(!$user->rechte['show_player_db_other'] AND $user->allianz) {
	$conds[] = "statusStatus = ".$status_meta;
}


// Daten abfragen
$query = query("
	SELECT
		user_playerID,
		user_playerName,
		user_allianzenID,
		userOverviewUpdate,
		
		allianzenTag
	FROM
		".PREFIX."user
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = user_allianzenID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = allianzenID
	".(count($conds) ? "WHERE
		".implode(' AND ', $conds) : "")."
	ORDER BY
		userOverviewUpdate ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// keine Einträge
if(!mysql_num_rows($query)) {
	$content .= '
	<br />
	<div class="center">
		Du hast keine Berechtigung!
	</div>
	<br /><br /><br />
	';
}
// Einträge anzeigen
else {
	// eigene Sitter abfragen
	$query2 = query("
		SELECT
			userSitterFrom
		FROM
			".PREFIX."user
		WHERE
			user_playerID = ".$user->id."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	$sitter = mysql_fetch_assoc($query2);
	$sitter = explode('+', $sitter['userSitterFrom']);
	
	$content .= '
	<div class="icontent">
		Planeten&uuml;bersichten gelten als veraltet, wenn sie vor mehr als '.$config['scan_veraltet_oview'].' Stunden eingescannt wurden.
	</div>
	<br /><br />
	<table class="data center" style="margin:auto">
	<tr>
		<th>Spieler</th>
		<th>Allianz</th>
		<th>aktualisiert</th>
	</tr>';
	
	$oldtime = time()-3600*$config['scan_veraltet_oview'];
	
	// Tabellen-Klasse einbinden
	if(!class_exists('datatable')) {
		include './common/datatable.php';
	}
	
	while($row = mysql_fetch_assoc($query)) {
		$content .= '
	<tr'.(!in_array($row['user_playerID'], $sitter) ? ' style="opacity:0.4"' : '').'>
		<td>'.datatable::player($row['user_playerID'], $row['user_playerName']).'</a></td>
		<td>'.datatable::allianz($row['user_allianzenID'], $row['allianzenTag']).'</td>
		<td '.(($row['userOverviewUpdate'] > $oldtime) ? 'class="green"' : 'class="red" style="font-weight:bold"').'>';
		if($row['userOverviewUpdate']) {
			$content .= datum($row['userOverviewUpdate']);
		}
		else {
			$content .= 'nie';
		}
		$content .= '</td>
	</tr>';
	}
	
	$content .= '
	</table>';
	
}

// Log-Eintrag
if($config['logging'] == 3 AND !isset($_GET['s'], $_GET['page'])) {
	insertlog(5, 'lässt sich die Liste der veralteten Planetenübersichten anzeigen');
}



?>