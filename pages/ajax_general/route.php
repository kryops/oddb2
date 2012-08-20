<?php

/**
 * pages/ajax_general/route.php
 * Planeten zu Route hinzufügen (Planet, Spieler, Ally, Meta)
 * markierte Planeten zu Route hinzufügen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


General::loadClass('route');


// Planeten zu Route hinzufügen (Planet, Spieler, Ally, Meta) -> Auswahl anzeigen
if($_GET['sp'] == 'add2route') {
	
	$types = array(
		'planet',
		'player',
		'ally',
		'meta',
		'search'
	);
	
	
	// keine Berechtigung
	if(!$user->rechte['routen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Vollständigkeit der Daten
	else if(!isset($_GET['typ'], $_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// Typ unbekannt
	else if(!in_array($_GET['typ'], $types)) {
		$tmpl->error = 'Daten ungültig!';
	}
	// alles ok
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		$select = route::getselect(isset($_GET['g']) ? $_GET['g'] : 0,1);
		
		if($select != '') {
			$tmpl->content .= '
hinzuf&uuml;gen zu <select name="route" size="1">'.$select.'</select> <img src="img/layout/leer.gif" class="icon arrowbutton hoverbutton" style="background-position:-1060px -91px" alt="[hinzuf&uuml;gen]" title="hinzuf&uuml;gen" onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=add2route_send&amp;typ='.$_GET['typ'].'&amp;id='.$_GET['id'].(isset($_GET['g']) ? '&g='.$_GET['g'] : '').'&amp;ajax&amp;route=\'+$(this).siblings(\'select\').val(), this.parentNode, false, true)" />';
		}
		else {
			$tmpl->content = 'Du hast keine Routen'.(isset($_GET['g']) ? ' in G'.(int)$_GET['g'] : '').' im Bearbeitungsmodus!';
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// Planeten zu Route hinzufügen (Planet, Spieler, Ally, Meta) -> speichern
else if($_GET['sp'] == 'add2route_send') {
	// keine Berechtigung
	if(!$user->rechte['routen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Vollständigkeit der Daten
	else if(!isset($_GET['typ'], $_GET['id'], $_GET['route'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	// alles ok
	else {
		// Daten sichern
		$_GET['id'] = (int)$_GET['id'];
		
		// Route laden
		$route = new route;
		if(($error = $route->load($_GET['route'])) !== true) {
			$tmpl->error = $error;
		}
		// keine Berechtigung
		else if(!$route->rechte_edit()) {
			$tmpl->error = 'Du hast keine Berechtigung, die Route zu bearbeiten!';
		}
		
		// einzelnen Planet hinzufügen
		else if($_GET['typ'] == 'planet') {
			if(($error = $route->add($_GET['id'])) !== true) {
				$tmpl->error = $error;
			}
			else {
				$route->save();
				$tmpl->content = 'Der Planet wurde hinzugef&uuml;gt. <a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=add2route&amp;typ=planet&amp;id='.$_GET['id'].'&amp;g='.$_GET['g'].'&amp;ajax\', this.parentNode, false, false)" class="hint">[zu weiterer Route / Liste hinzuf&uuml;gen]</a>';
			}
		}
		// Planeten eines Spielers hinzufügen
		else if($_GET['typ'] == 'player') {
			
			// gesperrte Allianzen
			if($user->protectedAllies) {
				$query = query("
					SELECT
						player_allianzenID
					FROM
						".GLOBPREFIX."player
					WHERE
						playerID = ".$_GET['id']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				if(!mysql_num_rows($query)) {
					$tmpl->error = 'Der Spieler existiert nicht!';
					$tmpl->output();
					die();
				}
				
				$data = mysql_fetch_assoc($query);
				if(in_array($data['player_allianzenID'], $user->protectedAllies)) {
					$tmpl->error = 'Du hast keine Berechtigung!';
					$tmpl->output();
					die();
				}
			}
			
			// Planeten abfragen
			$query = query("
				SELECT
					planetenID
				FROM
					".PREFIX."planeten
					".($route->gala ? "LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID" : "")."
				WHERE
					planeten_playerID = ".$_GET['id']."
					".($route->gala ? "AND systeme_galaxienID = ".$route->gala : "")."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				// hinzufügen
				$route->add($row['planetenID'], false);
			}
			
			// Route speichern
			$route->save();
			$tmpl->content = 'Die Planeten wurden hinzugef&uuml;gt. <a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=add2route&amp;typ=player&amp;id='.$_GET['id'].'&amp;ajax\', this.parentNode, false, false)" class="hint">[zu weiterer Route / Liste hinzuf&uuml;gen]</a>';
			
		}
		// Planeten eines Spielers hinzufügen
		else if($_GET['typ'] == 'ally') {
			
			// gesperrte Allianzen
			if($user->protectedAllies AND in_array($_GET['id'], $user->protectedAllies)) {
				$tmpl->error = 'Du hast keine Berechtigung!';
				$tmpl->output();
				die();
			}
			
			// Planeten abfragen
			$query = query("
				SELECT
					planetenID
				FROM
					".PREFIX."planeten
					".($route->gala ? "LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID" : "")."
					LEFT JOIN ".GLOBPREFIX."player
						ON playerID = planeten_playerID
				WHERE
					player_allianzenID = ".$_GET['id']."
					".($route->gala ? "AND systeme_galaxienID = ".$route->gala : "")."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				// hinzufügen
				$route->add($row['planetenID'], false);
			}
			
			// Route speichern
			$route->save();
			$tmpl->content = 'Die Planeten wurden hinzugef&uuml;gt. <a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=add2route&amp;typ=ally&amp;id='.$_GET['id'].'&amp;ajax\', this.parentNode, false, false)" class="hint">[zu weiterer Route / Liste hinzuf&uuml;gen]</a>';
			
		}
	}
	
	// Route aktualisieren
	if($tmpl->error == '') {
		$tmpl->script = 'if($(\'.route'.$_GET['route'].'\').length > 0) { ajaxcall("index.php?p=route&amp;sp=view&amp;id='.$_GET['route'].'&amp;update", false, false, false);}';
	}
	
	// Ausgabe
	$tmpl->output();
}

// markierte Planeten zu Route hinzufügen
else if($_GET['sp'] == 'route_addmarked') {
	// keine Berechtigung
	if(!$user->rechte['routen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	else {
		$select = route::getselect(isset($_GET['g']) ? $_GET['g'] : 0,1);
		
		if($select != '') {
			$tmpl->content .= '
markierte Planeten hinzuf&uuml;gen zu <select name="route">'.$select.'</select> <img src="img/layout/leer.gif" class="icon arrowbutton hoverbutton" style="background-position:-1060px -91px" title="hinzuf&uuml;gen" onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=route_addmarked_send&amp;ajax\', this.parentNode, $(this).parents(\'form\').serialize(), true)" />';
		}
		else {
			$tmpl->content = 'Du hast keine Routen'.(isset($_GET['g']) ? ' in G'.(int)$_GET['g'] : '').' im Bearbeitungsmodus!';
		}
	}
	
	// Ausgabe
	$tmpl->output();
}

// markierte Suchergebnisse zu Route hinzufügen -> abschicken
else if($_GET['sp'] == 'route_addmarked_send') {
	// keine Berechtigung
	if(!$user->rechte['routen']) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Daten unvollständig
	else if(!isset($_POST['route'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		// Daten sichern
		$_POST['route'] = (int)$_POST['route'];
		
		// Route laden
		$route = new route;
		if(($error = $route->load($_POST['route'])) !== true) {
			$tmpl->error = $error;
		}
		// keine Berechtigung
		else if(!$route->rechte_edit()) {
			$tmpl->error = 'Du hast keine Berechtigung, die Route zu bearbeiten!';
		}
		else {
			// IDs auslesen
			$ids = array();
			foreach($_POST as $key=>$val) {
				if(is_numeric($key)) {
					$ids[] = (int)$key;
				}
			}
			
			if(count($ids)) {
				// Bedingungen aufstellen
				$conds = array(
					"planetenID IN(".implode(", ", $ids).")"
				);
				
				if($route->gala) {
					$conds[] = "systeme_galaxienID = ".$route->gala;
				}
				
				$query = query("
					SELECT
						planetenID
					FROM
						".PREFIX."planeten
						LEFT JOIN ".PREFIX."systeme
							ON systemeID = planeten_systemeID
					WHERE
						".implode(" AND ", $conds)."
					ORDER BY
						planetenID
					LIMIT 100
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				while($row = mysql_fetch_assoc($query)) {
					$route->add($row['planetenID'], false);
				}
				
				// speichern
				$route->save();
			}
					
			$tmpl->content = 'Planeten hinzugef&uuml;gt. <a onclick="ajaxcall(\'index.php?p=ajax_general&amp;sp=route_addmarked&amp;ajax\', this.parentNode, false, true)" class="hint">[zu weiterer Route / Liste hinzuf&uuml;gen]</a>';
			$tmpl->script = 'if($(\'.route'.$route->id.'\').length > 0) { ajaxcall("index.php?p=route&sp=view&id='.$route->id.'&update", false, false, false);}';
		}
		
		// Ausgabe
		$tmpl->output();
		die();
	}
	
	// Ausgabe
	$tmpl->output();
}


?>