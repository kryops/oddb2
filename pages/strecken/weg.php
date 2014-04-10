<?php
/**
 * pages/strecken/weg.php
 * schnellster Weg
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



class SchnellsterWeg {
	
	const depth = 4;
	
	private $start;
	private $ziel;
	private $sameGala;
	
	private $gates = array();
	private $myrigates = array();
	private $sprunggen = array();
	
	private $mgdata = array();
	
	private $entf_direct;
	private $entf_shortest;
	
	/**
	 * Gefundene Routen
	 */
	private $routes = array();
	private $routeQueue = array();
	
	public $error;
	public $content;
	
	
	public function compute() {
		
		// Daten validieren, konvertieren und laden
		$this->validate();
		$this->loadRouteData();
		$this->loadMyrigateData();
		
		if($this->error) {
			return;
		}
		
		/*
		 * Direkte Entfernung berechnen
		 */
		if($this->start['g'] === $this->ziel['g']) {
			$this->sameGala = true;
			
			$this->entf_direct = $this->entf($this->start, $this->ziel);
			
		}
		else {
			$this->sameGala = false;
			
			$this->entf_direct = $this->start['gate'] + $this->ziel['gate'];
		}
		
		$this->entf_shortest = $this->entf_direct;
		
		
		/*
		 * Routen-Queue durchgehen
		 */
		
		$this->routeQueue[0] = array(
			
			/**
			 * [[Typ, ID (optional; bei Typ 2 = Galaxie)], ...]
			 * Typen:
			 * 1 - zum Ziel
			 * 2 - zum Gate
			 * 3 - zum Myrigate
			 * 4 - zum Sprunggenerator
			 */
			'steps' => array(),
			'entf' => 0,
			'wasAtGate' => false,
			'currentPosition' => $this->start,
			'usedMyrigates' => array()
		);
		
		$i = 0;
		
		while(isset($this->routeQueue[$i])) {
			
			$this->iterate($this->routeQueue[$i]);
			
			$i++;
		}
		
		/*
		 * Ergebnis rendern
		 */
		
		$this->content = '<br />
					direkter Weg'.($this->sameGala ? '' : ' &uuml;ber das Gate').': <b>'.flugdauer($this->entf_direct, $_POST['antrieb']).'</b> bei A'.$_POST['antrieb'].'
					<br /><br />';
		
		if(count($this->routes)) {
			$i = 0;
			
			while(count($this->routes) AND $i < 20) {
				
				$this->content .= $this->renderRoute(array_pop($this->routes));
				
				$i++;
			}
			
		}
		else {
			$this->content .= '<b>keinen k&uuml;rzeren Weg gefunden</b>';
		}
		
	}
	
	/**
	 * Arbeitet eine Teil-Route ab
	 * Speichert fertige Routen oder hängt neue Teilrouten an die Queue an
	 * @param array $route
	 * - steps: [Typ, ID/Gala]
	 * - entf
	 * - currentPosition
	 * - wasAtGate
	 * - usedMyrigates
	 */
	private function iterate($route) {
		
		$depth = count($route['steps']);
		
		/*
		 * Direktflug zum Ziel
		 * - am Gate oder in der Ziel-Galaxie
		 */
		
		if($route['currentPosition']['atGate'] OR $route['currentPosition']['g'] == $this->ziel['g']) {
			
			// Entfernung zum Ziel berechnen
			if($route['currentPosition']['atGate']) {
				$entf = $this->ziel['gate'];
			}
			else {
				$entf = $this->entf($route['currentPosition'], $this->ziel);
			}
			
			
			// kürzerer Weg gefunden
			if($route['entf'] + $entf < $this->entf_shortest) {
				
				$newRoute = $route;
				$newRoute['entf'] += $entf;
				$newRoute['steps'][] = array(1, false);
				
				$this->routes[] = $newRoute;
				$this->entf_shortest = $newRoute['entf'];
				
			}
			
		}
		
		/*
		 * Flug zum Gate
		 * - nicht am Gate
		 * - war noch nicht am Gate
		 * - nicht letzte Iteration
		 */
		if(!$route['currentPosition']['atGate']
			AND !$route['wasAtGate']
			AND $depth < SchnellsterWeg::depth
			AND $route['entf'] + $route['currentPosition']['gate'] < $this->entf_shortest) {
			
			$newRoute = $route;
			$newRoute['entf'] += $route['currentPosition']['gate'];
			$newRoute['currentPosition']['atGate'] = true;
			$newRoute['wasAtGate'] = true;
			$newRoute['steps'][] = array(2, $route['currentPosition']['g']);
			
			$this->routeQueue[] = $newRoute;
			
		}
		
		
		/*
		 * Nächsten Sprunggenerator benutzen
		* - nicht am Gate
		* - war noch nicht am Gate
		* - nicht letzte Iteration
		*/
		
		if(!$route['currentPosition']['atGate']
			AND !$route['wasAtGate']
			AND $depth < SchnellsterWeg::depth
			AND ($sprung = $this->findNextSprunggenerator($route['currentPosition'])) !== false
			AND $route['entf'] + $sprung[1] < $this->entf_shortest) {
				
			$newRoute = $route;
			$newRoute['entf'] += $sprung[1];
			$newRoute['currentPosition']['atGate'] = true;
			$newRoute['wasAtGate'] = true;
			$newRoute['steps'][] = array(4, $sprung[0]);
			
			$this->routeQueue[] = $newRoute;
				
		}
		
		/*
		 * Myrigate benutzen
		 * - Myrigate noch nicht benutzt
		 * - nicht letzte Iteration
		 * - vorletzte Iteration: nur Myrigates mit Riss in Ziel-Galaxien
		 */
		
		if($depth < SchnellsterWeg::depth) {
			
			foreach($this->myrigates as $id=>$data) {
					
				// nicht am Gate: Auf Myrigates der Galaxie beschränken
				if(!$route['currentPosition']['atGate'] AND $data['g'] != $route['currentPosition']['g']) {
					continue;
				}
					
				// Myrigate noch nicht benutzt
				if(in_array($id, $route['usedMyrigates'])) {
					continue;
				}
				
				// vorletzte Iteration: nur Myrigates mit Riss in Ziel-Galaxie
				if($depth === SchnellsterWeg::depth-1 AND $data['riss']['g']) {
					continue;
				}
				
				// Entfernung zum Myrigate berechnen
				if($route['currentPosition']['atGate']) {
					$entf = $data['gate'];
				}
				else {
					$entf = $this->entf($route['currentPosition'], $data);
				}
				
				
				// an die Queue anhängen
				if($route['entf'] + $entf < $this->entf_shortest) {
					
					$newRoute = $route;
					$newRoute['entf'] += $entf;
					$newRoute['steps'][] = array(3, $id);
					$newRoute['currentPosition'] = $data['riss'];
					$newRoute['usedMyrigates'][] = $id;
					
					$this->routeQueue[] = $newRoute;
					
				}
					
			}
			
		}
		
	}
	
	/**
	 * Berechtigung und übermittelte Daten überprüfen
	 */
	private function validate() {
		global $user;
		
		/*
		 * Berechtigung
		 */
		
		if(!$user->rechte['strecken_weg']) {
			$this->error = 'Du hast keine Berechtigung!';
		}
		
		/*
		 * Daten validieren und konvertieren
		 */
		
		// Daten unvollständig
		else if(!isset($_POST['start'], $_POST['dest'], $_POST['antrieb'], $_POST['napmanuell'])) {
			$this->error = 'Daten unvollständig!';
		}
		
		// Antrieb ungültig
		else if((int)$_POST['antrieb'] < 1) {
			$this->error = 'Ung&uuml;ltiger Antrieb eingegeben!';
		}
		
		// allianzlos
		if(!isset($_POST['nap'])) {
			$_POST['nap'] = 0;
		}
		
		// Daten sichern
		$_POST['antrieb'] = (int)$_POST['antrieb'];
		$_POST['nap'] = (int)$_POST['nap'];
		$_POST['napmanuell'] = preg_replace('/[^\d,]/Uis', '', $_POST['napmanuell']);
		
	}
	
	/**
	 * Eingaben für Start und Ziel übernehmen und in Koordinaten umrechnen
	 * Überprüfung auf offene Galaxie
	 * @param string $start
	 * @param string $ziel
	 */
	private function loadRouteData() {
		global $user;
		
		if($this->error) {
			return;
		}
		
		// Startpunkt und Zielpunkt in Koordinaten umwandeln
		$points = array($_POST['start'], $_POST['dest']);
		
		foreach($points as $key=>$val) {
			$name = $key ? 'Zielpunkt' : 'Startpunkt';
		
			// Daten in Koordinaten umwandeln
			$val = flug_point($val);
			$points[$key] = $val;
		
			// Fehler
			if(!is_array($val) AND !$this->error) {
				if($val == 'coords') {
					$this->error = 'Ung&uuml;ltige Koordinaten beim '.$name.' eingegeben!';
				}
				else if($val == 'data') {
					$this->error = 'Ung&uuml;ltige Daten beim '.$name.' eingegeben!';
				}
				else {
					$this->error = $name.' nicht gefunden!';
				}
			}
		}
		
		
		// kein Zugriff auf die Galaxie
		if(!$this->error AND $user->protectedGalas AND (in_array($points[0][0], $user->protectedGalas) OR in_array($points[1][0], $user->protectedGalas))) {
			if($points[0][0] == $points[1][0]) {
				$this->error = 'Deine Allianz hat keinen Zugriff diese Galaxie!';
			}
			else {
				$this->error = 'Deine Allianz hat keinen Zugriff auf eine der Galaxien!';
			}
		}
		
		if($this->error) {
			return;
		}
		
		/*
		 * Daten aufbereiten
		*/
		
		$this->start = array(
				'g' => $points[0][0],
				'x' => $points[0][1],
				'y' => $points[0][2],
				'z' => $points[0][3],
				'pos' => $points[0][4],
				'atGate' => false
		);
		
		$this->ziel = array(
				'g' => $points[1][0],
				'x' => $points[1][1],
				'y' => $points[1][2],
				'z' => $points[1][3],
				'pos' => $points[1][4],
				'atGate' => false
		);
		
		/*
		 * Gate-Entfernung berechnen
		 */
		$query = query("
			SELECT
				galaxienID,
				galaxienGateX,
				galaxienGateY,
				galaxienGateZ,
				galaxienGatePos
			FROM
				".PREFIX."galaxien
			WHERE
				galaxienGate > 0
				AND galaxienID IN(".$points[0][0].", ".$points[1][0].")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			
			// Galaxie des Startpunktes
			if($row['galaxienID'] == $points[0][0]) {
				$this->start['gate'] = entf(
					$points[0][1],
					$points[0][2],
					$points[0][3],
					$points[0][4],
					$row['galaxienGateX'],
					$row['galaxienGateY'],
					$row['galaxienGateZ'],
					$row['galaxienGatePos']
				);
			}
			
			// Galaxie des Zielpunkts
			if($row['galaxienID'] == $points[1][0]) {
				$this->ziel['gate'] = entf(
						$points[1][1],
						$points[1][2],
						$points[1][3],
						$points[1][4],
						$row['galaxienGateX'],
						$row['galaxienGateY'],
						$row['galaxienGateZ'],
						$row['galaxienGatePos']
				);
			}
			
		}
		
		// nicht alle Gates eingescannt
		if(!isset($this->start['gate'], $this->ziel['gate'])) {
			$this->error = 'Alle Gates der beteilgten Galaxien m&uuml;ssen eingescannt sein, um die Suche zu starten!';
		}
		
	}
	
	/**
	 * Myrigates und Sprunggeneratoren laden
	 */
	private function loadMyrigateData() {
		global $status_freund, $user;
		
		if($this->error) {
			return;
		}
		
		/*
		 * implodierten NAP-String erzeugen
		 */
		
		$nap = array();
		
		// NAPs einer DB-Allianz
		if(trim($_POST['napmanuell']) == '') {
			$query = query("
				SELECT
					status_allianzenID
				FROM
					".PREFIX."allianzen_status
				WHERE
					statusDBAllianz = ".$_POST['nap']."
					AND statusStatus IN(".implode(', ', $status_freund).")
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				$nap[] = $row['status_allianzenID'];
			}
			
			$nap = implode(', ', $nap);
	
			// keine NAPs eingetragen
			if($nap == '') {
				$this->error = 'F&uuml;r die Allianz sind keine NAPs eingetragen!';
			}
		}
		
		// NAPs manuell eingetragen
		else {
			$_POST['napmanuell'] = explode(',', $_POST['napmanuell']);
			
			foreach($_POST['napmanuell'] as $key=>$val) {
				$val = (int)trim($val);
				
				if($val) {
					$nap[] = $val;
				}
			}
			$nap = implode(', ', $nap);
	
			// keine NAPs eingetragen
			if($nap == '') {
				$this->error = 'Eingabe der NAPs ung&uuml;ltig!';
			}
		}
		
		if($this->error) {
			return;
		}
		
		/*
		 * Gates laden
		 */
		$query = query("
			SELECT
				galaxienID,
				galaxienGate
			FROM
				".PREFIX."galaxien
			WHERE
				galaxienGate > 0
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$this->gates[$row['galaxienID']] = $row['galaxienGate'];
		}
		
		
		/*
		 * Myrigates laden
		 */
		
		$query = query("
			SELECT
				myrigates_galaxienID,
				
				p.planetenID,
				p.planetenPosition,
				p.planetenGateEntf,
		
				s.systemeX,
				s.systemeY,
				s.systemeZ,
				
				p.planeten_playerID,
				playerName,
				player_allianzenID,
		
				allianzenTag,
				
				
				p2.planetenID AS riss_planetenID,
				p2.planetenPosition AS riss_planetenPosition,
				p2.planetenGateEntf AS riss_planetenGateEntf,
				
				s2.systeme_galaxienID AS riss_systeme_galaxienID,
				s2.systemeX AS riss_systemeX,
				s2.systemeY AS riss_systemeY,
				s2.systemeZ AS riss_systemeZ
				
			FROM
				".PREFIX."myrigates
				LEFT JOIN ".PREFIX."planeten p
					ON myrigates_planetenID = p.planetenID
				LEFT JOIN ".PREFIX."systeme s
					ON p.planeten_systemeID = s.systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON planeten_playerID = playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
				LEFT JOIN ".PREFIX."planeten p2
					ON p.planetenMyrigate = p2.planetenID
				LEFT JOIN ".PREFIX."systeme s2
					ON p2.planeten_systemeID = s2.systemeID
				
			WHERE
				player_allianzenID IN (".$nap.")
				AND myrigatesSprung = 0
				AND p.planetenGateEntf IS NOT NULL
				AND p2.planetenGateEntf IS NOT NULL
				AND p.planetenID IS NOT NULL
				AND p2.planetenID IS NOT NULL
				".($user->protectedGalas ? "AND s.systeme_galaxienID NOT IN(".implode(",", $user->protectedGalas).") AND s2.systeme_galaxienID NOT IN(".implode(",", $user->protectedGalas).")" : "")."
				
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
		while($row = mysql_fetch_assoc($query)) {
			
			// Myrigate-Datensatz aufbereiten und speichern
			$this->myrigates[$row['planetenID']] = array(
				'g' => $row['myrigates_galaxienID'],
				'x' => $row['systemeX'],
				'y' => $row['systemeY'],
				'z' => $row['systemeZ'],
				'pos' => $row['planetenPosition'],
				'gate' => $row['planetenGateEntf'],
				'atGate' => false,
				
				'riss' => array(
					'g' => $row['riss_systeme_galaxienID'],
					'x' => $row['riss_systemeX'],
					'y' => $row['riss_systemeY'],
					'z' => $row['riss_systemeZ'],
					'pos' => $row['riss_planetenPosition'],
					'gate' => $row['riss_planetenGateEntf'],
					'atGate' => false
				)
			);
			
			// Inhaberdaten speichern
			$this->mgdata[$row['planetenID']] = array(
				'myrigates_galaxienID' => $row['myrigates_galaxienID'],
				'planeten_playerID' => $row['planeten_playerID'],
				'playerName' => $row['playerName'],
				'player_allianzenID' => $row['player_allianzenID'],
				'allianzenTag' => $row['allianzenTag']
			);
		}
		
		
		/*
		 * Sprunggeneratoren laden
		 */
		
		$query = query("
			SELECT
				myrigates_galaxienID,
		
				planetenID,
				planetenPosition,
				planetenGateEntf,
		
				systemeX,
				systemeY,
				systemeZ,
		
				planeten_playerID,
				playerName,
				player_allianzenID,
		
				allianzenTag
				
			FROM
				".PREFIX."myrigates
				LEFT JOIN ".PREFIX."planeten
					ON myrigates_planetenID = planetenID
				LEFT JOIN ".PREFIX."systeme
					ON planeten_systemeID = systemeID
				LEFT JOIN ".GLOBPREFIX."player
					ON planeten_playerID = playerID
				LEFT JOIN ".GLOBPREFIX."allianzen
					ON allianzenID = player_allianzenID
			
			WHERE
				myrigatesSprung > 0
				AND myrigatesSprungFeind = 0
				AND planetenGateEntf IS NOT NULL
				".($user->protectedGalas ? "AND systeme_galaxienID NOT IN(".implode(",", $user->protectedGalas).")" : "")."
		
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
		while($row = mysql_fetch_assoc($query)) {
				
			// Sprunggenerator-Datensatz aufbereiten und speichern
			$this->sprunggen[$row['planetenID']] = array(
				'g' => $row['myrigates_galaxienID'],
				'x' => $row['systemeX'],
				'y' => $row['systemeY'],
				'z' => $row['systemeZ'],
				'pos' => $row['planetenPosition'],
				'gate' => $row['planetenGateEntf'],
				'atGate' => false
			);
				
			// Inhaberdaten speichern
			$this->mgdata[$row['planetenID']] = array(
				'myrigates_galaxienID' => $row['myrigates_galaxienID'],
				'planeten_playerID' => $row['planeten_playerID'],
				'playerName' => $row['playerName'],
				'player_allianzenID' => $row['player_allianzenID'],
				'allianzenTag' => $row['allianzenTag']
			);
		}
		
	}
	
	/**
	 * Shortcut für die Entfernungsberechnung mit Arrays (Schlüssel: x, y, z, pos)
	 * @param array $source
	 * @param array $dest
	 * @return Entfernung
	 */
	private function entf($source, $dest) {
		return entf(
			$source['x'],
			$source['y'],
			$source['z'],
			$source['pos'],
			$dest['x'],
			$dest['y'],
			$dest['z'],
			$dest['pos']
		);
	}
	
	/**
	 * Nächsten Sprunggenerator ermitteln
	 * @param array $point
	 * @return array|false [ID, Entfernung]
	 */
	private function findNextSprunggenerator($point) {
		
		$nextId = false;
		$nextEntf = false;
		$entf = false;
		
		// vom Gate aus nicht nach Sprunggeneratoren suchen
		if($point['atGate']) {
			return false;
		}
		
		foreach($this->sprunggen as $id=>$data) {
			
			if($data['g'] == $point['g']) {
				
				if($nextId === false OR ($entf = $this->entf($point, $data)) < $nextEntf) {
					
					if($nextId === false) {
						$nextEntf = $this->entf($point, $data);
					}
					else {
						$nextEntf = $entf;
					}
					
					$nextId = $id;
				}
				
			}
			
		}
		
		if(!$nextId) {
			return false;
		}
		else {
			return array($nextId, $nextEntf);
		}
		
	}
	
	/**
	 * Route rendern
	 * @param array $route
	 * @return string HTML
	 */
	private function renderRoute($route) {
		
		$content = '';
		
		
		// Routenpunkte durchgehen
		
		foreach($route['steps'] as $step) {
			$type = $step[0];
			$id = $step[1];
			
			switch($type) {
				
				/*
				 * zum Ziel
				 */
				case 1:
					
					$content .= 'danach zum Ziel';
					
					break;
				
				/*
				 * zum Gate
				 */
				case 2:
					
					$content .= 'Gate &nbsp;
								<b>G'.$id.' &nbsp;
								<a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$this->gates[$id].'">'.$this->gates[$id].'</a></b>';
					
					break;
					
				/*
				 * Myrigate / Sprunggenerator
				 */
				case 3:
				case 4:
					
					if(is_numeric($id) AND isset($this->mgdata[$id])) {
					
						$data = $this->mgdata[$id];
						
						if($type == 3) {
							$content .= 'Myrigate';
						}
						else {
							$content .= 'Sprunggenerator';
						}
						
						$content .= ' &nbsp;
									<b>G'.$data['myrigates_galaxienID'].' &nbsp; 
									<a class="link winlink contextmenu" data-link="index.php?p=show_planet&amp;id='.$id.'">'.$id.'</a> &nbsp;
									(';
						
						// Inhaber
						if($data['playerName'] != NULL) {
							$content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_player&amp;id='.$data['planeten_playerID'].'&amp;ajax">'.htmlspecialchars($data['playerName'], ENT_COMPAT, 'UTF-8').'</a>';
						}
						// frei, Lux oder Altrasse
						else if($data['planeten_playerID'] == 0) {
							$content .= '<i>frei</i>';
						}
						else if($data['planeten_playerID'] == -2) {
							$content .= '<i>Lux</i>';
						}
						else if($data['planeten_playerID'] == -3) {
							$content .= '<i>Altrasse</i>';
						}
						// unbekannter Inhaber
						else {
							$content .= '<i>unbekannt</i>';
						}
						
						$content .= ' <span class="small">';
						
						// Allianz anzeigen, wenn Spieler bekannt
						if($data['playerName'] != NULL) {
							// hat Allianz
							if($data['allianzenTag'] != NULL) {
								$content .= '<a class="link winlink contextmenu" data-link="index.php?p=show_ally&amp;id='.$data['player_allianzenID'].'&amp;ajax">'.htmlspecialchars($data['allianzenTag'], ENT_COMPAT, 'UTF-8').'</a>';
							}
							// allianzlos
							else if(!$data['player_allianzenID']) {
								$content .= '<i>allianzlos</i>';
							}
							// unbekannte Allianz
							else {
								$content .= '<i>Allianz unbekannt</i>';
							}
						}
						
						$content .= '</span>)</b> ';
						
						
						if($type == 3) {
							$content .= '&rarr; Riss';
						}
						else {
							$content .= '&rarr; Gate';
						}
					
					}
					
					break;
			}
			
			$content .= '<br />';
			
		}
		
		// Entfernung
		$content .= '&rarr; <b>'.flugdauer($route['entf'], $_POST['antrieb']).'</b>
					<br /><br />';
		
		return $content;
		
	}
	
}







// Inhalt
if($_GET['sp'] == 'weg') {
	// Allianz-Tags ermitteln
		$query = query("
			SELECT
				allianzenID,
				allianzenTag
			FROM 
				".GLOBPREFIX."allianzen
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = allianzenID
			WHERE
				register_allianzenID IS NOT NULL
				OR allianzenID = ".$user->allianz."
			ORDER BY
				allianzenID ASC
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$allyopt = '';
		while($row = mysql_fetch_assoc($query)) {
			$allyopt .= '
							<option value="'.$row['allianzenID'].'"'.($row['allianzenID'] == $user->allianz ? ' selected="selected"' : '').'>'.htmlspecialchars($row['allianzenTag'], ENT_COMPAT, 'UTF-8').'</option>';
		}
		
		// Content erzeugen
		$csw->data['weg']['content'] = '
	<div class="hl2">
		schnellster Weg
	</div>
	
	<div class="icontent">
		Diese Funktion berechnet die k&uuml;rzeste Verbindung zwischen zwei Punkten im OD-Universum. Sie rechnet folgende Verbindungen mit ein:
		<ul>
			<li>Direkter Weg zum Ziel in dessen Galaxie</li>
			<li>Direkter Weg zum Gate</li>
			<li>Myrigate &rarr; Riss</li>
			<li>Sprunggenerator &rarr; Gate</li>
		</ul>
		<br />
		Man kann nur die Myrigates benutzen, mit deren Inhaber man einen NAP hat. Es werden nur als benutzbar eingetragene Sprunggeneratoren ber&uuml;cksichtigt.
		<br /><br /><br />
		
		<form action="#" name="strecken_weg" onsubmit="return form_send(this, \'index.php?p=strecken&amp;sp=weg_send&amp;ajax\', $(this).siblings(\'.ajax\'))">
		<div class="fcbox formcontent center" style="padding:10px;width:600px">
			Startpunkt: <input type="text" class="text center tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:80px" name="start" />
			 &nbsp; &nbsp; 
			Ziel: <input type="text" class="text center tooltip" data-tooltip="bei System-IDs &lt;b&gt;sys&lt;/b&gt; davorsetzen (z.B. sys1234)" style="width:80px" name="dest" />
			 &nbsp; &nbsp; 
			Antrieb: <input type="text" class="smalltext" name="antrieb" value="'.$user->settings['antrieb'].'" />
			<div class="small hint" style="margin:-8px 100px 6px 0px">
				(Planet, System oder Koordinaten) &nbsp; &nbsp;(Planet oder System)
			</div>
			<div style="text-align:left;margin:0px 0px 15px 110px">
				NAPs der Allianz 
				&nbsp;<select name="nap" size="1">
					'.$allyopt.'
				</select>&nbsp; 
				benutzen
				<br />
				oder manuell eintragen: <input type="text" class="text center" name="napmanuell" /> 
				&nbsp; <span class="small hint">(IDs mit Komma getrennt)</span>
			</div>
			<input type="submit" class="button" value="schnellsten Weg berechnen" />
		</div>
		</form>
		<div class="ajax center" style="line-height:20px"></div>
	</div>';
}


// abschicken
else if($_GET['sp'] == 'weg_send') {
	
	$weg = new SchnellsterWeg();
	$weg->compute();
	
	// Ausgabe
	if($weg->error) {
		$tmpl->error = '<br />'.$weg->error;
	}
	else {
		$tmpl->content = $weg->content;
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(7, 'berechnet den schnellsten Weg von '.$_POST['start'].' nach '.$_POST['dest']);
		}
	}
	
	$tmpl->output();
}



?>