<?php
/**
 * pages/route/export.php
 * Route als CVS exportieren
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


function cvsquotes($str) {
	return str_replace(
		array('"', "\r\n", "\n"),
		array('""', "", ""),
		$str
	);
}



$route = new route;

if(!isset($_GET['id'])) {
	$tmpl->error = 'Keine ID übergeben';
}
// Laden der Route fehlgeschlagen
else if(($return = $route->load($_GET['id'])) !== true) {
	$tmpl->error = $return;
}
// keine Berechtigung
else if(!$route->rechte_view()) {
	$tmpl->error = 'Du hast keine Berechtigung, die Route/Liste anzuzeigen!';
}
// Route anzeigen
else {
	$tmpl->name = $rnames[$route->info['routenListe']].' '.htmlspecialchars($route->info['routenName'], ENT_COMPAT, 'UTF-8').' - Export';
	
	$_GET['id'] = (int)$_GET['id'];
	$heute = strtotime('today');
	
	// CSV automatisch markieren
	$t = time();
	$tmpl->script = '$("#route_export'.$t.'").select();';
	
	
	// Sortierung nach Entfernung
	$sort = false;
	if(isset($_GET['sort']) AND $_GET['sort'] != '' AND $route->liste) {
		$sort = flug_point($_GET['sort']);
		
		// Fehler
		if(!is_array($sort)) {
			$sort = false;
		}
	}
	
	// Export anzeigen
	$tmpl->content .= '
	<div class="center" style="padding:10px 0px">
	<textarea style="width:700px;height:400px" id="route_export'.$t.'">';
		$tmpl->content .= ($route->liste ? 'Gala,' : '').'System,ID,Name,Inhaber,Allianz,Status,Gr&ouml;&szlig;e,';
		$tmpl->content .= (($route->finished OR $sort) ? '&quot;Entfernung A'.$route->antrieb.'&quot;,' : '').'Scan,Kommentar'.($user->rechte['toxxraid'] ? ',geraidet,&quot;Toxx bis&quot;' : '').'
';
	
	if($route->count) {
		// Bedingungen aufstellen
		$conds = array(
			"planetenID IN(".implode(",",array_keys($route->data)).")"
		);
		
		// Sortierung -> Gala
		if($sort) {
			$conds[] = 'systeme_galaxienID = '.$sort[0];
		}
		
		// eingeschränkte Berechtigungen
		if($user->protectedGalas) {
			$conds[] = "systeme_galaxienID NOT IN(".implode(",", $user->protectedGalas).")";
		}
		if($user->protectedAllies) {
			$conds[] = "(player_allianzenID IS NULL OR player_allianzenID NOT IN(".implode(",", $user->protectedAllies)."))";
		}
		
		$data = array();
		
		// Planeten abfragen
		$query = query("
			SELECT
				planetenID,
				planetenName,
				planeten_playerID,
				planeten_systemeID,
				planetenGroesse,
				planetenTyp,
				planetenRMErz,
				planetenRMMetall,
				planetenRMWolfram,
				planetenRMKristall,
				planetenRMFluor,
				planetenUpdateOverview,
				planetenUnscannbar,
				planetenGebPlanet,
				planetenGebOrbit,
				planetenKategorie,
				planetenGeraidet,
				planetenGetoxxt,
				planetenKommentar,
				
				systeme_galaxienID,
				systemeX,
				systemeZ,
				".($sort ? entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $sort[1], $sort[2], $sort[3], $sort[4])." AS planetenEntfernung," : "")."
				
				playerName,
				player_allianzenID,
				playerUmod,
				playerRasse,
				
				allianzenTag,
				
				register_allianzenID,
				
				statusStatus
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON planeten_playerID = playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."register
						ON register_allianzenID = allianzenID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = ".$user->allianz."
					AND status_allianzenID = allianzenID
			WHERE
				".implode(' AND ', $conds)."
			ORDER BY
				NULL
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$data[$row['planetenID']] = $row;
			// Sortierung
			if($sort) {
				$route->data[$row['planetenID']] = $row['planetenEntfernung'];
			}
		}
		
		// sortieren
		if($sort) {
			asort($route->data);
		}
		
		foreach($route->data as $id=>$entf) {
			if(isset($data[$id])) {
				$row =& $data[$id];
				
				$tmpl->content .=  ($route->liste ? $row['systeme_galaxienID'].',' : '').$row['planeten_systemeID'].','.$row['planetenID'].',';
				$tmpl->content .= '&quot'.h(cvsquotes($row['planetenName'])).'&quot;,&quot;'.h(cvsquotes($row['playerName'])).'&quot;,&quot;'.h(cvsquotes($row['allianzenTag'])).'&quot;,';
				
				// Status
				$tmpl->content .= '&quot;';
				if($row['statusStatus'] === NULL) {
					$row['statusStatus'] = 0;
				}
				if(!isset($status[$row['statusStatus']])) {
					$tmpl->content .= '-';
				}
				else {
					$tmpl->content .= $status[$row['statusStatus']];
				}
				$tmpl->content .= '&quot;,';
				
				$tmpl->content .= $row['planetenGroesse'].',';
				
				// Entfernung
				if($route->finished) {
					$tmpl->content .= flugdauer($entf, $route->antrieb).',';
				}
				else if($sort) {
					$tmpl->content .= flugdauer($row['planetenEntfernung'], $route->antrieb).',';
				}
				
				// Scan
				if(!$row['planetenUpdateOverview']) {
					$tmpl->content .= 'nie';
				}
				else {
					$tmpl->content .= strftime('%d.%m.%y', $row['planetenUpdateOverview']);
				}
				
				$tmpl->content .= ',&quot;'.h(cvsquotes($row['planetenKommentar'])).'&quot;,';
				
				// geraidet und getoxxt
				if($user->rechte['toxxraid']) {
					$tmpl->content .= (time()-$row['planetenGeraidet'] < 604800 ? strftime('%d.%m.%y', $row['planetenGeraidet']) : '-').',';
					$tmpl->content .= ($row['planetenGetoxxt'] > time() ? strftime('%d.%m.%y', $row['planetenGetoxxt']) : '-');
				}
				
				$tmpl->content .= '
';
			}
		}
	}
	
	$tmpl->content .= '</textarea>
</div>';
}

// Ausgabe
$tmpl->output();



?>