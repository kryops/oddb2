<?php
/**
 * pages/show_planet/show.php
 * Planet anzeigen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// Daten sichern
$_GET['id'] = escape($_GET['id']);

// Query generieren
$sql = "
	SELECT
		planetenID,
		planetenName,
		planeten_playerID,
		planetenUpdateOverview,
		planetenUpdate,
		planetenTyp,
		planetenGroesse,
		planetenGebPlanet,
		planetenGebOrbit,
		planetenGateEntf,
		planetenRWErz,
		planetenRWWolfram,
		planetenRWKristall,
		planetenRWFluor,
		planetenRPErz,
		planetenRPMetall,
		planetenRPWolfram,
		planetenRPKristall,
		planetenRPFluor,
		planetenRMErz,
		planetenRMMetall,
		planetenRMWolfram,
		planetenRMKristall,
		planetenRMFluor,
		planetenForschung,
		planetenIndustrie,
		planetenBevoelkerung,
		planetenRessplani,
		planetenWerft,
		planetenWerftBedarf,
		planetenWerftFinish,
		planetenBunker,
		planetenGeraidet,
		planetenGetoxxt,
		planetenKommentar,
		planetenKommentarUser,
		planetenKommentarUpdate,
		planetenHistory,
		planetenNatives,
		
		systemeID,
		systemeX,
		systemeZ,
		systeme_galaxienID,
		
		playerName,
		playerRasse,
		playerUmod,
		playerDeleted,
		player_allianzenID,
		
		allianzenTag,
		
		register_allianzenID,
		
		schiffeTerraformer,
		schiffeBergbau,
		
		statusStatus
	FROM
		".PREFIX."planeten
		LEFT JOIN ".PREFIX."systeme
			ON systemeID = planeten_systemeID
		LEFT JOIN ".GLOBPREFIX."player
			ON playerID = planeten_playerID
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = player_allianzenID
		LEFT JOIN ".PREFIX."register
			ON register_allianzenID = allianzenID
		LEFT JOIN ".PREFIX."planeten_schiffe
			ON schiffe_planetenID = planetenID
		LEFT JOIN ".PREFIX."allianzen_status
			ON statusDBAllianz = ".$user->allianz."
			AND status_allianzenID = allianzenID";

$data = false;

// Planeten-ID
if(is_numeric(trim($_GET['id']))) {
	// Daten abfragen
	$query = query("
		".$sql."
		WHERE
			planetenID = ".$_GET['id']."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Planet mit dieser ID existiert
	if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);
}

// Name eingegeben oder ID nicht gefunden
if(!$data) {
	// * als Wildcard benutzen
	$_GET['id'] = str_replace('*', '%', $_GET['id']);
	
	// AutoWildcard-Einstellung
	if($user->settings['szgrwildcard']) {
		$_GET['id'] = '%'.$_GET['id'].'%';
	}
	
	// Daten abfragen (doppelt escapen wegen LIKE-Bug)
	$query = query("
		".$sql."
		WHERE
			planetenName LIKE '".escape($_GET['id'])."'
		ORDER BY
			(planetenName = '".str_replace('%', '', $_GET['id'])."') DESC,
			planetenID ASC
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Planet mit diesem Namen
	if(mysql_num_rows($query)) $data = mysql_fetch_assoc($query);
}

show_planet_rechte($data);

// Log-Eintrag
if($config['logging'] >= 3) {
	insertlog(5, 'lässt den Planet '.$data['planetenID'].' anzeigen');
}

// Gebäude-Arrays erzeugen
$gpl = gebaeude($data['planetenGebPlanet'], $data['planetenGroesse'], false);
$gor = gebaeude($data['planetenGebOrbit'], false, false);

// anzeigen
$tmpl->name = 'G'.$data['systeme_galaxienID'].' '.$data['planetenID'].' ('.htmlspecialchars($data['planetenName'], ENT_COMPAT, 'UTF-8').')';
$tmpl->icon = 'planet';
$tmpl->content = '
<div class="icontent" style="min-width:560px">
	<div class="fcbox center small2">
		<span class="'.scan_color($data['planetenUpdate'], $config['scan_veraltet']).'">';
// Scan-Datum
if(!$data['planetenUpdateOverview']) $tmpl->content .= 'noch kein Scan vorhanden';
else if(!$data['planetenUpdate']) $tmpl->content .= 'noch kein voller Scan vorhanden';
else $tmpl->content .= 'voller Scan '.datum($data['planetenUpdate'], true);

$tmpl->content .= '</span>'.($data['planetenUpdateOverview'] > $data['planetenUpdate'] ? ' <span class="'.scan_color($data['planetenUpdateOverview'], $config['scan_veraltet']).'"> &nbsp; (Oberfl&auml;che '.datum($data['planetenUpdateOverview'], true).')</span>' : '').'
	</div>';

// Spieler gelöscht
if($data['playerDeleted']) {
	$tmpl->content .= '
	<div class="fcbox center small2 red">
		Der Spieler hat sich wahrscheinlich gel&ouml;scht und der Planet existiert m&ouml;glicherweise nicht mehr!
	</div>';
}

// Natives
if($data['planetenNatives']) {
	$tmpl->content .= '
	<div class="fcbox center small2 red">
	Beim letzten Scan befanden sich <b>'.$data['planetenNatives'].'</b> Natives im Orbit!
	</div>';
}

// Invasion etc
if($user->rechte['invasionen'] OR $user->rechte['fremdinvakolos']) {
	// laufende Invasionen etc ermitteln
	$ilabels = array(
		1=>'laufende Invasion',
		2=>'laufende Resonation',
		3=>'laufendes Genesis',
		4=>'laufende Besatzung',
		5=>'laufende Kolonisation'
	);

	$invasionen = array();
	
	$conds = array(
		"invasionen_planetenID = ".$data['planetenID'],
		"(invasionenEnde > ".time()." OR invasionenEnde = 0)"
	);
	
	// Berechtigungen
	if(!$user->rechte['invasionen']) {
		$conds[] = "(invasionenFremd = 1 OR invasionenTyp = 5)";
	}
	if(!$user->rechte['fremdinvakolos']) {
		$conds[] = "(invasionenFremd = 0 OR invasionenTyp != 5)";
	}
	if($user->protectedAllies) {
		$conds[] = "(player_allianzenID IS NULL OR player_allianzenID NOT IN(".implode(", ", $user->protectedAllies)."))";
	}
	
	
	$query = query("
		SELECT
			invasionenID,
			invasionenTyp,
			invasionenEnde,
			invasionenAggressor,
			
			playerName,
			player_allianzenID,
			allianzenTag
		FROM
			".PREFIX."invasionen
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = invasionenAggressor
			LEFT JOIN ".GLOBPREFIX."allianzen
				ON allianzenID = player_allianzenID
		WHERE
			".implode(' AND ', $conds)."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	if(mysql_num_rows($query)) {
		$inva = mysql_fetch_assoc($query);
		
		if(isset($ilabels[$inva['invasionenTyp']])) {
			$tmpl->content .= '<div class="fcbox center small2 red userlistaction">'.$ilabels[$inva['invasionenTyp']];
			
			if($inva['playerName'] != NULL) {
				$tmpl->content .= ' von <a class="link winlink contextmenu red" data-link="index.php?p=show_player&amp;id='.$inva['invasionenAggressor'].'">'.htmlspecialchars($inva['playerName'], ENT_COMPAT, 'UTF-8').'</a>';
				if($inva['allianzenTag'] != NULL) {
					$tmpl->content .= ' <a class="link winlink contextmenu red" data-link="index.php?p=show_ally&amp;id='.$inva['player_allianzenID'].'">'.htmlspecialchars($inva['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
				}
			}
			// Ende bei Besatzungen nicht anzeigen
			if($inva['invasionenTyp'] != 4) {
				$tmpl->content .= ' &nbsp;-&nbsp; Ende: '.($inva['invasionenEnde'] ? datum($inva['invasionenEnde']) : '<i>unbekannt</i>');
			}
			$tmpl->content .= ' &nbsp; <img src="img/layout/leer.gif" style="background-position:-1000px -91px" class="link winlink contextmenu hoverbutton" data-link="index.php?p=inva&amp;sp=inva_details&amp;id='.$inva['invasionenID'].'" alt="[Details]" title="Details" /></div>';
		}
		
	}
}

// Bergbau
if($data['schiffeBergbau'] !== NULL AND $user->rechte['fremdinvakolos']) {
	$tmpl->content .= '
	<div class="fcbox center small2">
		An diesem Planeten l&auml;uft ein Bergbauvorgang
		&nbsp;
		<span><a class="hint" onclick="ajaxcall(\'index.php?p=show_planet&sp=removebbstf&id='.$data['planetenID'].'&ajax\', this.parentNode, false, false)">[entfernen]</a></span>
	</div>';
}

// Terraformer
if($data['schiffeTerraformer']  AND $user->rechte['fremdinvakolos']) {
	$tmpl->content .= '
	<div class="fcbox center small2">
		An diesem Planeten l&auml;uft ein Terraformer
		&nbsp;
		<span><a class="hint" onclick="ajaxcall(\'index.php?p=show_planet&sp=removebbstf&id='.$data['planetenID'].'&ajax\', this.parentNode, false, false)">[entfernen]</a></span>
	</div>';
}

$tmpl->content .= '
	<div class="fcbox">
		<div class="fhl2" style="padding:2px">
			<table class="tneutral tbold" style="width:100%">
			<tr>
				<td style="width:40%">Daten</td>
				<td style="width:20%">Werte</td>
				<td style="width:20%">Produktion</td>
				<td style="width:20%">Vorrat</td>
			</tr>
			</table>
		</div>
		
		<table class="tneutral tdata tsmall" style="width:100%">
		<tr>
				<td style="width:39%" rowspan="2">
					<b>Galaxie</b>: <span style="color:#'.sektor_coord($data['systemeX'], $data['systemeZ']).'">'.$data['systeme_galaxienID'].'</span>
					<br />
					<b>System</b>: <a class="link winlink contextmenu" data-link="index.php?p=show_system&amp;id='.$data['systemeID'].'&amp;ajax">'.$data['systemeID'].'</a>
					<br />
					'.($data['planetenGateEntf'] != NULL ? '
					<b>Gate</b>: '.flugdauer($data['planetenGateEntf'], $user->settings['antrieb']).' bei A'.$user->settings['antrieb'].'
					<br />' : '').'
					<b>Inhaber</b>: ';
// Inhaber
if($data['playerName'] != NULL) {
	$tmpl->content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['planeten_playerID'].'&amp;ajax">'.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').'</a>';
	if($data['playerUmod']) {
		$tmpl->content .= '<sup class="small red">zzZ</sup>';
	}
	$tmpl->content .= '&nbsp;';
	if(isset($rassen2[$data['playerRasse']])) {
		$tmpl->content .= '<img src="img/layout/leer.gif" alt="" class="rasse '.$rassen2[$data['playerRasse']].'" />';
	}
}
// frei
else if($data['planeten_playerID'] == 0) {
	$tmpl->content .= '<i>keiner</i>';
}
// Lux
else if($data['planeten_playerID'] == -2) {
	$tmpl->content .= '<span style="color:#ffff88;font-weight:bold;font-style:italic">Seze Lux</span>';
}
// Altrasse
else if($data['planeten_playerID'] == -3) {
	$tmpl->content .= '<span style="color:#ffff88;font-weight:bold;font-style:italic">Altrasse</span>';
}
// unbekannter Inhaber
else {
	$tmpl->content .= '<i>unbekannt</i>';
}
// Allianz anzeigen, wenn Spieler bekannt
if($data['playerName'] != NULL) {
	$tmpl->content .= ' &nbsp; ';
	// hat Allianz
	if($data['allianzenTag'] != NULL) {
		if($data['statusStatus'] == NULL) $data['statusStatus'] = 0;
		$tmpl->content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$data['player_allianzenID'].'&amp;ajax">'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
		// Status, wenn nicht eigene Allianz
		if($data['player_allianzenID'] != $user->allianz) {
			$tmpl->content .= '&nbsp;<span class="small hint" '.$status_color[$data['statusStatus']].'>('.$status[$data['statusStatus']].')</span>';
		}
	}
	// allianzlos
	else if(!$data['player_allianzenID']) {
		$tmpl->content .= '<i>allianzlos</i>';
	}
	// unbekannte Allianz
	else {
		$tmpl->content .= '<i>???</i>';
	}
}
// Größe und Bevölkerung
$tmpl->content .= ' <br />
					<b>Gr&ouml;&szlig;e</b>: '.$data['planetenGroesse'].'
					<br />
					<b>Bev&ouml;lkerung</b>: '.ressmenge($data['planetenBevoelkerung']);
if($data['planetenUpdate']) {
	$tmpl->content .= '
					<br />
					<b>Forschung</b>: '.ressmenge($data['planetenForschung']).'
					<br />
					<b>Industrie</b>: '.ressmenge($data['planetenIndustrie']);
}
$tmpl->content .= '
					<br />
					<b>Planetenpunkte</b>: '.ressmenge(imppunkte($data)).'
				</td>
				<td style="width:20%;height:112px">
					<img src="img/layout/leer.gif" alt="Erz" class="ress erz" /> &nbsp; '.$data['planetenRWErz'].'%
					<br />
					<img src="img/layout/leer.gif" alt="Metall" class="ress metall" /> &nbsp; '.$data['planetenRWErz'].'%
					<br />
					<img src="img/layout/leer.gif" alt="Wolfram" class="ress wolfram" /> &nbsp; '.$data['planetenRWWolfram'].'%
					<br />
					<img src="img/layout/leer.gif" alt="Kristall" class="ress kristall" /> &nbsp; '.$data['planetenRWKristall'].'%
					<br />
					<img src="img/layout/leer.gif" alt="Fluor" class="ress fluor" /> &nbsp; '.$data['planetenRWFluor'].'%
				</td>';
// bei vollem Scan Ressproduktion und Ressmengen anzeigen
if($data['planetenUpdate']) {
	$tmpl->content .= '
				<td style="width:20%">
					<img src="img/layout/leer.gif" alt="Erz" class="ress erzprod" /> &nbsp; '.ressmenge($data['planetenRPErz']).'
					<br />
					<img src="img/layout/leer.gif" alt="Metall" class="ress metallprod" /> &nbsp; '.ressmenge($data['planetenRPMetall']).'
					<br />
					<img src="img/layout/leer.gif" alt="Wolfram" class="ress wolframprod" /> &nbsp; '.ressmenge($data['planetenRPWolfram']).'
					<br />
					<img src="img/layout/leer.gif" alt="Kristall" class="ress kristallprod" /> &nbsp; '.ressmenge($data['planetenRPKristall']).'
					<br />
					<img src="img/layout/leer.gif" alt="Fluor" class="ress fluorprod" /> &nbsp; '.ressmenge($data['planetenRPFluor']).'
				</td>
				<td style="width:21%">
					<img src="img/layout/leer.gif" alt="Erz" class="ress erz" /> &nbsp; '.ressmenge($data['planetenRMErz']).'
					<br />
					<img src="img/layout/leer.gif" alt="Metall" class="ress metall" /> &nbsp; '.ressmenge($data['planetenRMMetall']).'
					<br />
					<img src="img/layout/leer.gif" alt="Wolfram" class="ress wolfram" /> &nbsp; '.ressmenge($data['planetenRMWolfram']).'
					<br />
					<img src="img/layout/leer.gif" alt="Kristall" class="ress kristall" /> &nbsp; '.ressmenge($data['planetenRMKristall']).'
					<br />
					<img src="img/layout/leer.gif" alt="Fluor" class="ress fluor" /> &nbsp; '.ressmenge($data['planetenRMFluor']).'
				</td>';
}
// nur Oberfläche
else if($data['planetenUpdateOverview']) {
	$tmpl->content .= '
				<td style="width:20%;font-style:italic">
					<div style="width:70%;text-align:center">
						<br />noch kein
						<br />voller Scan
						<br />vorhanden
					</div>
				</td>
				<td style="width:21%">
					<img src="img/layout/leer.gif" alt="Erz" class="ress erz" /> &nbsp; '.ressmenge($data['planetenRMErz']).'
					<br />
					<img src="img/layout/leer.gif" alt="Metall" class="ress metall" /> &nbsp; '.ressmenge($data['planetenRMMetall']).'
					<br />
					<img src="img/layout/leer.gif" alt="Wolfram" class="ress wolfram" /> &nbsp; '.ressmenge($data['planetenRMWolfram']).'
					<br />
					<img src="img/layout/leer.gif" alt="Kristall" class="ress kristall" /> &nbsp; '.ressmenge($data['planetenRMKristall']).'
					<br />
					<img src="img/layout/leer.gif" alt="Fluor" class="ress fluor" /> &nbsp; '.ressmenge($data['planetenRMFluor']).'
				</td>';
}
// noch kein Scan vorhanden
else {
	$tmpl->content .= '
				<td style="width:41%;height:80px;text-align:center;font-style:italic" colspan="2">
					<div style="height:30px"></div>
					noch kein Scan vorhanden &nbsp; &nbsp; &nbsp; &nbsp;
				</td>';
}

// Ressplanet, Werft und Bunker

// Berechtigungen ermitteln
$tr = show_planet_typrechte($data);
$r = $tr;

$flags = array();
if($r['ressplani'] AND $data['planetenRessplani']) $flags[] = 'Ressplanet';
if($r['bunker'] AND $data['planetenBunker']) $flags[] = 'Bunker';
if($r['werft'] AND $data['planetenWerft']) $flags[] = 'Werft';
if(count($flags) == 3) $flagtext = 'Ressplanet, Bunker und Werft';
else $flagtext = implode(' und ', $flags);

$tmpl->content .= '
			</tr>
			<tr>
				<td colspan="3">
					<div>
						';
if(count($flags)) {
	$tmpl->content .= 'eingetragen als '.$flagtext.' &nbsp;';
}
else if($r['flags']) {
	$tmpl->content .= '<i>nicht eingeteilt</i> &nbsp; ';
}
if($r['flags']) {
	$tmpl->content .= '
						<a onclick="ajaxcall(\'index.php?p=show_planet&amp;id='.$data['planetenID'].'&amp;sp=typ&amp;ajax\', this.parentNode, false, false)" class="hint">[&auml;ndern]</a>';
}
$tmpl->content .= '
					</div>';
// raiden und toxxen
$r = false;
// Links nur anzeigen, wenn besetzter fremder oder Feind-Planet
if($user->rechte['toxxraid']) {
	$r = true;
	if($data['planeten_playerID'] == $user->id) $r = false;
	else if($data['planeten_playerID'] == 0 OR $data['planeten_playerID'] == 1) $r = false;
	else if($user->allianz AND $data['player_allianzenID'] == $user->allianz) $r = false;
	else if($user->allianz AND in_array($data['statusStatus'], $status_freund)) $r = false;
}
if($r) {
	$tmpl->content .= '
					<div>
						zuletzt geraidet: '.($data['planetenGeraidet'] ? datum($data['planetenGeraidet']) : '<i>nie</i>').' &nbsp;
						<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=raid&amp;id='.$data['planetenID'].'&amp;typ=show&amp;ajax\', this.parentNode, false, false)" class="hint">[jetzt geraidet]</a>
					</div>
					<div>
						getoxxt bis: '.($data['planetenGetoxxt'] > time() ? datum($data['planetenGetoxxt']) : '<i>nicht getoxxt</i>').' &nbsp;
						<a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=toxx&amp;id='.$data['planetenID'].'&amp;typ=show&amp;ajax\', this.parentNode, false, false)" class="hint">[jetzt getoxxt]</a>
					</div>
					<span>
						<a onclick="ajaxcall(\'index.php?p=show_planet&amp;sp=orbiter_del&amp;id='.$data['planetenID'].'&amp;ajax\', this.parentNode, false, false)" class="hint">[Orbiter l&ouml;schen]</a>
					</span>
					&nbsp;
					<span>
						<a onclick="ajaxcall(\'index.php?p=show_planet&amp;sp=ress_del&amp;id='.$data['planetenID'].'&amp;ajax\', this.parentNode, false, false)" class="hint">[Ress auf 0 setzen]</a>
					</span>';
}
$tmpl->content .= '
					<div style="height:8px"></div>
					
					<div>
						<div class="kommentar" style="float:left"></div> &nbsp; ';
// Kommentar
if(trim($data['planetenKommentar']) != '') {
	if($data['planetenKommentarUser'] == $user->id) $uname = htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8');
	else {
		// Autor ermitteln
		$query = query("
			SELECT
				playerName
			FROM
				".GLOBPREFIX."player
			WHERE
				playerID = ".$data['planetenKommentarUser']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// User mit dieser ID existiert
		if(mysql_num_rows($query)) {
			$uname = mysql_fetch_array($query);
			$uname = htmlspecialchars($uname[0], ENT_COMPAT, 'UTF-8');
		}
		else $uname = '<i>unbekannt</i>';
	}
	// ausgeben
	$tmpl->content .= 'von <a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['planetenKommentarUser'].'&amp;ajax">'.$uname.'</a> 
						<span class="small">('.datum($data['planetenKommentarUpdate']).')</span>
						&nbsp; <a onclick="kommentar_edit('.$data['planetenID'].', this.parentNode, true)" class="hint">[&auml;ndern]</a>
						
						<div class="kommentarc">'.nl2br(htmlspecialchars($data['planetenKommentar'], ENT_COMPAT, 'UTF-8')).'</div>';
}
// kein Kommentar
else {
	$tmpl->content .= '&nbsp; <a onclick="kommentar_edit('.$data['planetenID'].', this.parentNode, false)">Kommentar hinzuf&uuml;gen</a>';
}
// Bebauung anzeigen
$tmpl->content .= '</div>
				</td>
			</tr>
		</table>
	</div>';

// Werft
if($data['planetenWerft'] AND $data['planetenUpdateOverview'] AND $tr['werft']) {
	$tmpl->content .= '
	<div class="fcbox icontent center small2" style="line-height:2em">
	Schiff fertig: ';
	
	$t = strtotime('today')-172800;
	
	// fertig
	// unbekannt
	if($data['planetenUpdateOverview'] < $t) {
		$tmpl->content .= '<span class="yellow" style="font-style:italic">unbekannt</span>';
	}
	// leerstehend
	else if($data['planetenWerftFinish'] < time()) {
		$tmpl->content .= '<span class="red" style="font-weight:bold">leerstehend</span>';
	}
	// Schiff im Bau
	else if($data['planetenWerftFinish'] < time()+7200) {
		$tmpl->content .= '<b>'.datum($data['planetenWerftFinish']).'</b>';
	}
	else {
		$tmpl->content .= datum($data['planetenWerftFinish']);
	}
	
	// Bedarf
	$tmpl->content .= '
	<br />
	<span class="plwerftbedarfu'.$data['planeten_playerID'].' plwerftbedarf'.$data['planetenID'].'">Werft-Bedarf: ';
	
	// unbekannt
	if($data['planetenWerftBedarf'] == '') {
		$tmpl->content .= '<span style="font-style:italic">nicht eingetragen</span> &nbsp; ';
	}
	else {
		// Bedarf ausrechnen
		$b = unserialize($data['planetenWerftBedarf']);
		
		// Ress-Labels
		$ress = array(
			'<img src="img/layout/leer.gif" class="ress ress_tooltip erz" />',
			'<img src="img/layout/leer.gif" class="ress ress_tooltip metall" />',
			'<img src="img/layout/leer.gif" class="ress ress_tooltip wolfram" />',
			'<img src="img/layout/leer.gif" class="ress ress_tooltip kristall" />',
			'<img src="img/layout/leer.gif" class="ress ress_tooltip fluor" />'
		);
		
		foreach($b as $key=>$val) {
			$tmpl->content .= $ress[$key].' '.ressmenge2($val, true).' &nbsp; ';
		}
	}
	
	
	$tmpl->content .= '
	</span>';
	// Werft-Bedarf bearbeiten
	$r = show_planet_typrechte($data);
	if($r['flags']) {
		$tmpl->content .= '<a class="link winlink contextmenu hint" data-link="index.php?p=werft&amp;sp=edit&amp;id='.$data['planetenID'].'">[&auml;ndern]</a>';
	}
	$tmpl->content .= '
	</div>';
}

$tmpl->content .= '
	
	<div class="plshowbg">
		<div class="plshowc">
			<div class="plshowp">
				<img src="img/planeten/'.$data['planetenTyp'].'_big.jpg" alt="" />
			</div>
			<div style="position:absolute">
				<table class="plshowt">
					<tr>
						<td>'.$gor[7].'</td>
						<td>'.$gor[1].'</td>
						<td>'.$gpl[36].'</td>
						<td>'.$gpl[35].'</td>
						<td>'.$gpl[29].'</td>
						<td>'.$gpl[23].'</td>
						<td>'.$gpl[30].'</td>
						<td>'.$gpl[34].'</td>
					</tr>
					<tr>
						<td>'.$gor[8].'</td>
						<td>'.$gor[2].'</td>
						<td>'.$gpl[32].'</td>
						<td>'.$gpl[24].'</td>
						<td>'.$gpl[18].'</td>
						<td>'.$gpl[10].'</td>
						<td>'.$gpl[19].'</td>
						<td>'.$gpl[25].'</td>
					</tr>
					<tr>
						<td>'.$gor[9].'</td>
						<td>'.$gor[3].'</td>
						<td>'.$gpl[28].'</td>
						<td>'.$gpl[14].'</td>
						<td>'.$gpl[6].'</td>
						<td>'.$gpl[2].'</td>
						<td>'.$gpl[7].'</td>
						<td>'.$gpl[15].'</td>
					</tr>
					<tr>
						<td>'.$gor[10].'</td>
						<td>'.$gor[4].'</td>
						<td>'.$gpl[22].'</td>
						<td>'.$gpl[13].'</td>
						<td>'.$gpl[5].'</td>
						<td>'.$gpl[1].'</td>
						<td>'.$gpl[3].'</td>
						<td>'.$gpl[11].'</td>
					</tr>
					<tr>
						<td>'.$gor[11].'</td>
						<td>'.$gor[5].'</td>
						<td>'.$gpl[31].'</td>
						<td>'.$gpl[17].'</td>
						<td>'.$gpl[9].'</td>
						<td>'.$gpl[4].'</td>
						<td>'.$gpl[8].'</td>
						<td>'.$gpl[16].'</td>
					</tr>
					<tr>
						<td>'.$gor[12].'</td>
						<td>'.$gor[6].'</td>
						<td>'.$gpl[33].'</td>
						<td>'.$gpl[27].'</td>
						<td>'.$gpl[21].'</td>
						<td>'.$gpl[12].'</td>
						<td>'.$gpl[20].'</td>
						<td>'.$gpl[26].'</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
	';
// Inhaber-History ab 2 Inhabern anzeigen
if($data['planetenHistory'] >= 2) {
	$tmpl->content .= '
	<div class="fcbox small2">
		<div class="center">
			<a onclick="ajaxcall(\'index.php?p=show_planet&amp;id='.$data['planetenID'].'&amp;sp=history&amp;ajax\', this.parentNode.parentNode, false, false)">Eigent&uuml;mer-History anzeigen ('.$data['planetenHistory'].' Eintr&auml;ge)</a>
		</div>
	</div>';
}
// Zusatzoptionen (Route, Entfernung)
if($user->rechte['routen'] OR $user->rechte['strecken_flug'] OR ($user->rechte['search'] AND $data['playerName'] != NULL)) {
	$tmpl->content .= '
	<div class="fcbox center small2" style="line-height:25px">';
	// Planet zu einer Route oder Liste hinzufügen
	if($user->rechte['routen']) {
		$tmpl->content .= '
		<div><a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=add2route&amp;typ=planet&amp;id='.$data['planetenID'].'&amp;g='.$data['systeme_galaxienID'].'&amp;ajax\', this.parentNode, false, false)">den Planeten zu einer Route oder Liste hinzuf&uuml;gen</a></div>';
	}
	// die nächsten Planeten von 
	if($user->rechte['search'] AND $data['playerName'] != NULL) {
		$tmpl->content .= '
		<div><a class="link winlink contextmenu" data-link="index.php?p=search&amp;s=1&amp;g='.$data['systeme_galaxienID'].'&amp;uid='.$data['planeten_playerID'].'&amp;sortt=1&amp;entf='.$data['planetenID'].'&amp;hide&amp;title='.urlencode('Planeten von '.$data['playerName'].' von '.$data['planetenID'].' aus').'&amp;ajax">die n&auml;chstgelegenen Planeten von '.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').' anzeigen</a></div>';
	}
	
	// Entfernung berechnen
	if($user->rechte['strecken_flug']) {
		$tmpl->content .= '
		<form action="#" name="strecken_flug" onsubmit="form_send(this, \'index.php?p=strecken&amp;sp=flug_entf&amp;simple&amp;ajax\', $(this).siblings(\'.ajax\'));return false">
			<input type="hidden" name="start" value="'.$data['planetenID'].'" />
			<input type="hidden" name="antrieb" value="'.$user->settings['antrieb'].'" />
			Entfernung nach <input type="text" class="text center small2" style="width:70px" name="dest_entf" /> <a onclick="$(this.parentNode).trigger(\'onsubmit\')">berechnen</a>
		</form>
		<div class="ajax"></div>';
	}
	$tmpl->content .= '
	</div>';
}
$tmpl->content .= '
</div>
';



?>