<?php

/**
 * common/route.php
 * Routen-Klasse
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


class route {
	// ID
	public $id = false;
	// Routeninhalt
	public $data = array();
	// berechnet
	public $finished = false;
	// ist eine Liste
	public $liste = false;
	// ist eine Toxxroute
	public $toxx = false;
	// Galaxie
	public $gala = false;
	// Anzahl Planeten
	public $count = 0;
	// DB-Datensatz
	public $info = false;
	// Antrieb
	public $antrieb = 30;
	// maximale Anzahl Planeten
	public $limit = 2000;
	
	/**
	 * Route aus der DB laden
	 * @param $id int
	 * @param $data array vorgeladener Datensatz @default false
	 * @return bool / string = Erfolg / Fehlermeldung
	 */
	public function load($id, $data=false) {
		global $user;
		
		// Daten sichern
		$id = (int)$id;
		if(!$id) {
			return false;
		}
		
		$this->id = $id;
		
		/*
		
		$data = array(
			plid => entf / true,
			plid => entf / true
		);
		
		*/
		
		// Datensatz abfragen
		if(!$data) {
			$query = query("
				SELECT
					routenDate,
					routen_playerID,
					routen_galaxienID,
					routenName,
					routenListe,
					routenTyp,
					routenEdit,
					routenFinished,
					routenData,
					routenCount,
					routenMarker,
					routenAntrieb,
					
					user_playerName,
					user_allianzenID,
					statusStatus
				FROM
					".PREFIX."routen
					LEFT JOIN ".PREFIX."user
						ON user_playerID = routen_playerID
					LEFT JOIN ".PREFIX."allianzen_status
						ON statusDBAllianz = user_allianzenID
						AND status_allianzenID = ".$user->allianz."
				WHERE
					routenID = ".$this->id."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(!mysql_num_rows($query)) {
				return 'Die Route/Liste existiert nicht!';
			}
			
			$data = mysql_fetch_assoc($query);
		}
		
		// Datensatz ins Objekt laden
		$this->finished = (bool)$data['routenFinished'];
		$this->liste = ($data['routenListe'] == 1);
		$this->toxx = ($data['routenListe'] == 2);
		$this->gala = (int)$data['routen_galaxienID'];
		$this->count = (int)$data['routenCount'];
		$this->antrieb = $data['routenAntrieb'] ? $data['routenAntrieb'] : $user->settings['antrieb'];
		$this->data = json_decode($data['routenData'], true);
		unset($data['routenFinished']);
		unset($data['routen_galaxienID']);
		unset($data['routenCount']);
		unset($data['routenData']);
		$this->info = $data;
		
		// keine Berechtigung
		if(!$this->rechte_view()) {
			return 'Du hast keine Berechtigung, die Route/Liste anzuzeigen';
		}
		
		return true;
	}
	
	
	/**
	 * Route speichern
	 * @return bool Erfolg
	 */
	public function save() {
		// keine Berechtigung
		if(!$this->rechte_edit()) {
			return false;
		}
		
		// sortieren
		if(!$this->finished) {
			ksort($this->data);
		}
		
		// speichern
		query("
			".($this->id ? "UPDATE" : "INSERT INTO")." ".PREFIX."routen
			SET
				routenDate = ".(int)$this->info['routenDate'].",
				routen_playerID = ".(int)$this->info['routen_playerID'].",
				routen_galaxienID = ".(int)$this->gala.",
				routenName = '".escape($this->info['routenName'])."',
				routenListe = ".($this->liste ? '1' : ($this->toxx ? '2' : '0')).",
				routenTyp = ".(int)$this->info['routenTyp'].",
				routenEdit = ".($this->info['routenEdit'] ? '1' : '0').",
				routenFinished = ".($this->finished ? '1' : '0').",
				routenData = '".escape(json_encode($this->data))."',
				routenCount = ".(int)$this->count.",
				routenMarker = ".(int)$this->info['routenMarker'].",
				routenAntrieb = ".(int)$this->info['routenAntrieb']."
			".($this->id ? "
			WHERE
				routenID = ".(int)$this->id : "")."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// ID ermitteln
		if(!$this->id) {
			$this->id = mysql_insert_id();
		}
		
		return true;
	}
	
	/**
	 * neue Route erstellen -> Info erzeugen
	 * @return bool Erfolg
	 */
	public function create() {
		global $user, $status_meta;
		
		// Infos schon vorhanden
		if($this->info) {
			return false;
		}
		
		$this->info = array(
			'routenDate'=>time(),
			'routen_playerID'=>$user->id,
			'routenName'=>'Unbenannt',
			'routenTyp'=>1,
			'routenEdit'=>0,
			'routenMarker'=>0,
			'routenAntrieb'=>0,
			'user_allianzenID'=>$user->allianz,
			'statusStatus'=>$status_meta
		);
		
		$this->antrieb = $user->settings['antrieb'];
		
		return true;
	}
	
	
	/**
	 * Marker setzen
	 * @param $id int Plani-ID des Markers
	 * @return bool Erfolg
	 */
	public function setmarker($id) {
		// Daten sichern
		$id = (int)$id;
		if(!$id) {
			return false;
		}
		
		// noch nicht gespeichert
		if(!$this->id) {
			return false;
		}
		
		// noch nicht berechnet
		if(!$this->finished) {
			return false;
		}
		
		// keine Berechtigung
		if(!$this->rechte_view()) {
			return false;
		}
		
		// Marker nicht in Route enthalten
		if($id != 0 AND !$this->contains($id)) {
			return false;
		}
		
		// Marker setzen
		query("
			UPDATE ".PREFIX."routen
			SET
				routenMarker = ".$id."
			WHERE
				routenID = ".(int)$this->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$this->info['routenMarker'] = $id;
		
		return true;
	}
	
	/**
	 * Route berechnen
	 * @param $start int Startpunkt (PlaniID)
	 * @return bool / string = Erfolg / Fehlermeldung
	 */
	public function compute($start) {
		// schon berechnet
		if($this->finished) {
			return 'Die Route wurde schon berechnet!';
		}
		
		// ist eine Liste
		if($this->liste) {
			return 'Listen können nicht berechnet werden!';
		}
		
		// Startplaneten evtl hinzufügen
		if(!$this->contains($start)) {
			// verhindern, dass das Berechnen am Limit scheitert
			$this->limit++;
			
			if(($error = $this->add($start)) !== true) {
				return 'Ungültigen Startpunkt eingegeben: '.$error;
			}
			
			if(!$this->contains($start)) {
				return 'Ungültigen Startpunkt eingegeben!';
			}
		}
		
		// Positionsdaten abfragen
		$query = query("
			SELECT
				planetenID,
				planetenPosition,
				planeten_systemeID,
				
				systemeX,
				systemeY,
				systemeZ
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
			WHERE
				planetenID IN(".implode(",", array_keys($this->data)).")
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$sys = array();
		$startsys = 0;
		
		while($row = mysql_fetch_assoc($query)) {
			// System-Element anlegen
			if(!isset($sys[$row['planeten_systemeID']])) {
				$sys[$row['planeten_systemeID']] = array(
					$row['systemeX'],
					$row['systemeY'],
					$row['systemeZ'],
					$row['planetenPosition'],
					$row['planetenPosition'],
					array($row['planetenID']=>$row['planetenPosition'])
				);
			}
			// System-Element erweitern
			else {
				$sys[$row['planeten_systemeID']][5][$row['planetenID']] = $row['planetenPosition'];
				// maximale und minimale Planetenposition pro System speichern
				if($row['planetenPosition'] < $sys[$row['planeten_systemeID']][3]) {
					$sys[$row['planeten_systemeID']][3] = $row['planetenPosition'];
				}
				else if($row['planetenPosition'] > $sys[$row['planeten_systemeID']][4]) {
					$sys[$row['planeten_systemeID']][4] = $row['planetenPosition'];
				}
			}
			
			// Startsystem ermitteln
			if($row['planetenID'] == $start) {
				$startsys = $row['planeten_systemeID'];
			}
		}
		
		// finales Array anlegen
		$data = array(
			$start=>0
		);
		
		$cur_sys = $startsys;
		$syspos = $sys[$startsys];
		$pos = $sys[$cur_sys][5][$start];
		unset($sys[$cur_sys][5][$start]);
		
		// Route berechnen
		while(count($sys)) {
			// Planeten innerhalb des Systems sortieren
			if(abs($pos-$sys[$cur_sys][4]) < abs($pos-$sys[$cur_sys][3])) {
				krsort($sys[$cur_sys][5]);
			}
			else {
				ksort($sys[$cur_sys][5]);
			}
			
			// Planeten zur Route hinzufügen
			foreach($sys[$cur_sys][5] as $id=>$p) {
				$data[$id] = (int)entf($syspos[0],$syspos[1],$syspos[2],$pos,$sys[$cur_sys][0],$sys[$cur_sys][1],$sys[$cur_sys][2],$p);
				$syspos = $sys[$cur_sys];
				$pos = $p;
			}
			
			// System löschen
			unset($sys[$cur_sys]);	
			
			// nächstes System ermitteln
			$next_sys = 0;
			
			$min = -1;
			
			foreach($sys as $id=>$val) {
				$entf = false;
				if($min > ($entf = entf($val[0],$val[1],$val[2],1,$syspos[0],$syspos[1],$syspos[2],1)) OR $min == -1) {
					$min = $entf;
					$next_sys = $id;
				}
			}
			
			$cur_sys = $next_sys;
			
		}
		
		// Daten übernehmen
		$this->data = $data;
		
		$this->finished = true;
		
		return true;
	}
	
	/**
	 * nächsten Planeten einer Route ausgehend von einem System ermitteln
	 * alternativ nächstgelegenen Planeten einer Liste berechnen
	 * @param $sys int System-ID
	 * @param $count int Anzahl der nächsten Planeten @default 1
	 * @return array / false = Plani-IDs / fehlgeschlagen
	 */
	public function compute_next($sys, $count=1) {
		// Daten sichern
		$sys = (int)$sys;
		if(!$sys) {
			return false;
		}
		
		$count = (int)$count;
		if($count < 1) {
			return false;
		}
		
		// noch nicht berechnet
		if(!$this->liste AND !$this->finished) {
			return false;
		}
		
		// leer
		if(!count($this->data)) {
			return false;
		}
		
		// Route -> nächstes System ermitteln
		if(!$this->liste) {
			// System-Info auslesen
			$syspl = array();
			
			$query = query("
				SELECT
					planetenID
				FROM
					".PREFIX."planeten
				WHERE
					planeten_systemeID = ".$sys."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				$syspl[$row['planetenID']] = true;
			}
			
			if(!count($syspl)) {
				return false;
			}
			
			$found = false;
			$plids = array();
			$i = 1;
			
			// Route durchgehen
			foreach($this->data as $id=>$val) {
				if(!$found) {
					// Startsystem gefunden
					if(isset($syspl[$id])) {
						$found = true;
					}
				}
				else {
					// nächster Plani außerhalb des Startsystems
					if(!isset($syspl[$id])) {
						//return array($id);
						$plids[] = $id;
						
						if($i >= $count) {
							break;
						}
						$i++;
					}
				}
			}
			
			// Planeten-IDs zurückgeben
			if(count($plids)) {
				return $plids;
			}
			// Startsystem nicht in Route gefunden
			return false;
		}
		// Liste -> nächstgelegenen Planeten berechnen
		else {
			// Systemdaten abfragen
			$query = query("
				SELECT
					systeme_galaxienID,
					systemeX,
					systemeY,
					systemeZ
				FROM
					".PREFIX."systeme
				WHERE
					systemeID = ".$sys."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(!mysql_num_rows($query)) {
				return false;
			}
			
			$data = mysql_fetch_assoc($query);
			
			
			// Positionsdaten abfragen
			$query = query("
				SELECT
					planetenID
				FROM
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID
				WHERE
					planetenID IN(".implode(",", array_keys($this->data)).")
					AND planeten_systemeID != ".$sys."
					AND systeme_galaxienID = ".$data['systeme_galaxienID']."
				ORDER BY
					".entf_mysql("systemeX", "systemeY", "systemeZ", "planetenPosition", $data['systemeX'], $data['systemeY'], $data['systemeZ'], "1")."
				LIMIT ".$count."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// kein Planet gefunden
			if(!mysql_num_rows($query)) {
				return false;
			}
			
			$plids = array();
			
			while($row = mysql_fetch_assoc($query)) {
				$plids[] = $row['planetenID'];
			}
			
			// Planeten zurückgeben
			return $plids;
		}
	}
	
	/**
	 * Route wieder in den Bearbeitungsmodus zurücksetzen
	 * @return bool Erfolg
	 */
	public function reset() {
		if(!$this->finished) {
			return false;
		}
		
		// Entfernungen löschen
		foreach($this->data as $key=>$val) {
			$this->data[$key] = true;
		}
		
		$this->info['routenMarker'] = 0;
		$this->finished = false;
		
		return true;
	}
	
	/**
	 * durchsucht eine Route nach einer Plani-ID
	 * @param $id int Plani-ID
	 * @return bool Plani-ID in Route enthalten
	 */
	public function contains($id) {
		return isset($this->data[$id]);
	}
	
	/**
	 * Fügt einen Planeten zu einer Route hinzu
	 * @param $id int PlaniID
	 * @param $check Gültigkeit überprüfen @default true
	 * @return bool / string = Erfolg / Fehlermeldung
	 */
	public function add($id, $check=true) {
		// Daten sichern
		$id = (int)$id;
		if(!$id) {
			return false;
		}
		
		// Route nicht im Bearbeitungsmodus
		if($this->finished) {
			return 'Die Route ist nicht im Bearbeitungsmodus!';
		}
		
		// schon enthalten
		if($this->contains($id)) {
			return true;
		}
		
		// schon in einer anderen Toxxroute
		if($this->toxx AND $this->in_toxxroute($id)) {
			return 'Der Planet ist schon in einer anderen Toxxroute enthalten!';
		}
		
		// Limit
		if($this->count >= $this->limit) {
			return 'Die Route kann nicht mehr als '.$this->limit.' Planeten enthalten!';
		}
		
		// Existenz und Galaxie überprüfen
		if($check) {
			$query = query("
				SELECT
					systeme_galaxienID
				FROM
					".PREFIX."planeten
					LEFT JOIN ".PREFIX."systeme
						ON systemeID = planeten_systemeID
				WHERE
					planetenID = ".$id."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(!mysql_num_rows($query)) {
				return 'Der Planet ist nicht eingetragen!';
			}
			
			if(!$this->liste AND $this->gala) {
				$data = mysql_fetch_assoc($query);
				if($data['systeme_galaxienID'] != $this->gala) {
					return 'Der Planet liegt nicht in der Galaxie der Route!';
				}
			}
		}
		
		// hinzufügen
		$this->data[$id] = true;
		$this->count++;
		
		return true;
	}
	
	/**
	 * Fügt einen Planeten zu einer Route hinzu
	 * @param $ids array(plids)
	 * @return bool / string = Erfolg / Fehlermeldung
	 */
	public function add_batch($ids) {
		// Daten sichern
		if(!is_array($ids)) {
			return 'Daten ungültig!';
		}
		
		// Route nicht im Bearbeitungsmodus
		if($this->finished) {
			return 'Die Route ist nicht im Bearbeitungsmodus!';
		}
		
		// Daten aufbereiten
		foreach($ids as $key=>$val) {
			$val = (int)$val;
			$ids[$key] = $val;
			// schon enthalten
			if($val < 1 OR $this->contains($val) OR ($this->toxx AND $this->in_toxxroute($val))) {
				unset($ids[$key]);
			}
		}
		
		if(!count($ids)) {
			return true;
		}
		
		// Existenz und Galaxie überprüfen
		$query = query("
			SELECT
				planetenID
			FROM
				".PREFIX."planeten
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = planeten_systemeID
			WHERE
				planetenID IN(".implode(', ', $ids).")
				".($this->gala ? " AND systeme_galaxienID = ".$this->gala : "")."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			// hinzufügen
			$this->data[$row['planetenID']] = true;
			$this->count++;
			
			// Limit
			if($this->count >= $this->limit) {
				break;
			}
		}
		
		return true;
	}
	
	/**
	 * Planet aus der Route löschen
	 * @param $id int PlaniID
	 * @return bool / string = Erfolg / Fehlermeldung
	 */
	public function remove($id) {
		// Daten sichern
		$id = (int)$id;
		if(!$id) {
			return false;
		}
		
		// Route nicht im Bearbeitungsmodus
		if($this->finished) {
			return 'Die Route ist nicht im Bearbeitungsmodus!';
		}
		
		// nicht enthalten
		if(!$this->contains($id)) {
			return true;
		}
		
		// entfernen
		unset($this->data[$id]);
		$this->count--;
		
		// aus Toxxroutenliste entfernen
		if($this->toxx) {
			global $toxxroute;
			
			if(is_array($toxxroute) AND isset($toxxroute[$id])) {
				unset($toxxroute[$id]);
			}
		}
		
		return true;
	}
	
	/**
	 * User hat die Berechtigung zum Anzeigen
	 * @return bool Berechtigung
	 */
	public function rechte_view() {
		global $user, $status_meta;
		
		// ohne Info immer Berechtigung
		if(!$this->info) {
			return true;
		}
		
		/*
			Routen-Typen
			1 - privat
			2 - Allianz
			3 - Meta
			4 - alle
		*/
		
		// eigene Routen kann man immer sehen
		if($this->info['routen_playerID'] == $user->id) {
			return true;
		}
		
		// Galaxie gesperrt
		if($user->protectedGalas AND $this->gala AND in_array($this->gala, $user->protectedGalas)) {
			return false;
		}
		
		// Allianz gesperrt
		if($user->protectedAllies AND $this->info['user_allianzenID'] !== NULL AND in_array($this->info['user_allianzenID'], $user->protectedAllies)) {
			return false;
		}
		
		
		// Allianz-Route
		if($this->info['routenTyp'] > 1 AND $user->rechte['routen_ally'] AND $user->allianz AND $user->allianz == $this->info['user_allianzenID']) {
			return true;
		}
		// Meta-Route
		if($this->info['routenTyp'] > 2 AND $user->rechte['routen_meta'] AND $user->allianz AND $this->info['statusStatus'] == $status_meta) {
			return true;
		}
		// andere Route
		if($this->info['routenTyp'] == 4 AND $user->rechte['routen_other'] AND $this->info['statusStatus'] != $status_meta) {
			return true;
		}
		
		// keine Berechtigung
		return false;
	}
	
	/**
	 * User hat die Berechtigung zum Editieren und Löschen
	 * @return bool Berechtigung
	 */
	public function rechte_edit() {
		global $user;
		
		// ohne Info immer Berechtigung
		if(!$this->info) {
			return true;
		}
		
		// eigene Routen kann man immer bearbeiten
		if($this->info['routen_playerID'] == $user->id) {
			return true;
		}
		
		// ohne Anzeigeberechtigung auch keine Editierberechtigung
		if(!$this->rechte_view()) {
			return false;
		}
		
		// Editier-Flag gesetzt
		if($this->info['routenEdit']) {
			return true;
		}
		
		
		// keine Berechtigung
		return false;
	}
	
	/**
	 * Anzeigeberechtigungen als MySQL-Bedingungen
	 * @return string Bedingungen
	 */
	public static function rechte_view_mysql() {
		global $user, $status_meta;
		
		$conds = array();
		
		// Rechte-Einschränkungen
		if($user->allianz AND !$user->rechte['routen_ally']) {
			$conds[] = "user_allianzenID != ".$user->allianz;
		}
		
		if($user->allianz AND !$user->rechte['routen_meta']) {
			$conds[] = "(statusStatus IS NULL OR statusStatus != ".$status_meta.")";
		}
		
		if(!$user->rechte['routen_other']) {
			$conds[] = "statusStatus = ".$status_meta;
		}
		
		// Galaxie gesperrt
		if($user->protectedGalas) {
			$conds[] = "routen_galaxienID NOT IN(".implode(", ", $user->protectedGalas).")";
		}
		
		// Allianz gesperrt
		if($user->protectedAllies) {
			$conds[] = "(user_allianzenID IS NULL OR user_allianzenID NOT IN(".implode(", ", $user->protectedAllies)."))";
		}
		
		// mit den Routentypen verbinden
		$conds = "
	(
		routen_playerID = ".$user->id."
			OR (
				(
					routenTyp = 4
					OR (routenTyp > 1 AND user_allianzenID = ".$user->allianz.")
					OR (routenTyp > 2 AND statusStatus = ".$status_meta.")
				)
				".(count($conds) ? "AND ".implode(" AND ", $conds) : "")."
			)
	)";
		
		// zurückgeben
		return $conds;
	}
	
	/**
	 * überprüfen, ob ein Planet in einer Toxxroute ist
	 * @param $id int PlaniID
	 * @return bool ist in Toxxroute enthalten
	 */
	public static function in_toxxroute($id) {
		global $toxxroute;
		
		// Planeten noch nicht ermittelt
		if($toxxroute === NULL) {
			$toxxroute = array();
			
			$query = query("
				SELECT
					routenData
				FROM
					".PREFIX."routen
				WHERE
					routenListe = 2
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				$data = json_decode($row['routenData'], true);
				foreach($data as $key=>$val) {
					$toxxroute[$key] = true;
				}
			}
		}
		
		// Existenz zurückgeben
		return isset($toxxroute[$id]);
	}
	
	/**
	 * Liste aller Routen ermitteln
	 * @param $gala int Beschränkung auf Galaxie
	 * @param $typ
	 *  0 - alle Routen
	 *  1 - editierbare Routen
	 *  2 - berechnete Routen
	 * $not int / array IDs herausfiltern
	 * @return array(id=>[name,user,gala])
	 */
	public static function getlist($gala=0, $typ=0, $not=0) {
		global $user;
		
		// keine Berechtigung
		if(!$user->rechte['routen']) {
			return false;
		}
		
		// Daten sichern
		$gala = (int)$gala;
		
		// Bedingungen aufstellen
		$conds = array(route::rechte_view_mysql());
		
		// editierbare Routen
		if($typ == 1) {
			$conds[] = "routenFinished = 0";
		
			// Berechtigung zum Editieren
			$conds[] = "(routen_playerID = ".$user->id." OR routenEdit = 1)";
		}
		// berechnete Routen
		else if($typ == 2) {
			$conds[] = "(routenFinished = 1 OR routenListe = 1)";
		}
		
		// Filter
		if($not) {
			if(is_array($not) AND count($not)) {
				// sichern
				foreach($not as $key=>$val) {
					$not[$key] = (int)$val;
				}
				
				$conds[] = "routenID NOT IN(".implode(",", $not).")";
			}
			else {
				$conds[] = "routenID != ".$not;
			}
		}
		
		// Galaxie
		$gala = (int)$gala;
		if($gala) {
			$conds[] = "(routen_galaxienID = 0 OR routen_galaxienID = ".$gala.")";
		}
		
		// Routen abfragen
		$data = array();
		
		$query = query("
			SELECT
				routenID,
				routen_playerID,
				routen_galaxienID,
				routenName
			FROM
				".PREFIX."routen
				LEFT JOIN ".PREFIX."user
					ON user_playerID = routen_playerID
				LEFT JOIN ".PREFIX."allianzen_status
					ON statusDBAllianz = user_allianzenID
					AND status_allianzenID = ".$user->allianz."
			WHERE
				".implode(" AND ", $conds)."
			ORDER BY
				(routen_playerID = ".$user->id.") DESC,
				routenName ASC
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			$data[$row['routenID']] = array(
				$row['routenName'],
				$row['routen_playerID'],
				$row['routen_galaxienID']
			);
		}
		
		if(!count($data)) {
			$data = false;
		}
		
		// zurückgeben
		return $data;
	}
	
	/**
	 * <option>-Elemente der Routenliste erzeugen
	 * @param $typ
	 *  0 - alle Routen
	 *  1 - editierbare Routen
	 *  2 - berechnete Routen
	 * @param $gala int Galaxie
	 * @return string HTML
	 */
	public static function getselect($gala=0, $typ=0, $not=0) {
		global $user;
		
		// Routen ermitteln
		$routes = route::getlist($gala, $typ, $not);
		
		if($routes === false) {
			return '';
		}
		
		$own = false;
		$other = false;
		
		$content = '';
		
		foreach($routes as $id=>$data) {
			// Gruppierung
			// eigene Route
			if($data[1] == $user->id) {
				if(!$own) {
					$content .= '
<optgroup label="Eigene Routen/Listen">';
					$own = true;
				}
			}
			// fremde Route
			else {
				if(!$other) {
					if($own) {
						$content .= '
</optgroup>';
					}
					$content .= '
<optgroup label="Routen/Listen von anderen">';
					$other = true;
				}
			}
			
			$content .= '
<option value="'.$id.'">'.htmlspecialchars($data[0], ENT_COMPAT, 'UTF-8').($data[2] ? ' (G'.$data[2].')' : '').'</option>';
			
		}
		
		$content .= '
</optgroup>';
		
		// zurückgeben
		return $content;
	}
	
}



?>