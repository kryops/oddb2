<?php

/**
 * common/user.php
 * User-Klasse
 * OD-Request
 * IP-Ban-Funktionen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


/**
 * Userklasse
 * transportiert die Daten eines Accounts
 */
class user {
	// bool eingeloggt?
	public $login = false;
	// string Login-Fehlermeldung
	public $loginerror;
	// int User gesperrt (0 nicht, 1 manuell, 2 Ally gewechselt, 3 noch nicht freigeschaltet)
	public $banned = 0;
	// int User-ID
	public $id = 0;
	// string Username
	public $name;
	// int Allianz des Users
	public $allianz;
	// array Allianzen, auf die der User keinen Zugriff hat
	public $protectedAllies;
	// array Galaxien, auf die der User keinen Zugriff hat
	public $protectedGalas;
	// array User-Einstellungen
	public $settings;
	// array Rechte des Users
	public $rechte;
	
	
	/**
	 * Benutzer-Objekt mit Daten füllen
	 * @param array $data MySQL-Datensatz
	 */
	public function populateData($data) {
		
		// Userdaten verarbeiten
		$this->name = $data['user_playerName'];
		$this->allianz = $data['user_allianzenID'];
		
		/* userBanned
		   1 - manuell gebannt
		   2 - automatisch gebannt (Allywechsel, keine Registriererlaubnis mehr)
		   3 - noch nicht freigeschaltet
		*/
		$this->banned = $data['userBanned'];
		$this->settings = json_decode($data['userSettings'], true);
		
		// Berechtigungen
		$r = getrechte(
			$data['userRechtelevel'],
			$data['registerProtectedAllies'],
			$data['registerProtectedGalas'],
			$data['registerAllyRechte'],
			$data['userRechte']
		);
		
		$this->rechte = $r[1];
		$this->protectedAllies = $r[2];
		$this->protectedGalas = $r[3];
		
	}
	
	/**
	 * Ermittelt, ob der Flooding-Schutz greift
	 * und zählt einen Seitenaufruf dazu
	 */
	public function flooding() {
		
		global $config, $cache;
		
		// Flooding-Schutz deaktiviert
		if(!$config['flooding']) {
			return false;	
		}
		
		// Cache benutzen
		if(CACHING) {
			// bisherige Aufrufe fetchen
			if($data = $cache->get('flooding'.$this->id)) {
				$p = 0;
				$max = time()-$config['flooding_time'];
				foreach($data as $key=>$val) {
					if($val > $max) $p++;
					else unset($data[$key]);
				}
				$data[] = time();
				// bei starkem Flooding Array kürzen
				if($p > 2*$config['flooding_pages']) {
					$data = array_slice($data, -1.5*$config['flooding_pages']);
				}
				// wieder in den Cache laden
				$cache->set('flooding'.$this->id, $data, $config['flooding_time']);
				
				// wenn es zu viele sind, sperren
				if($p > $config['flooding_pages']) {
					return true;
				}
			}
			// neuen Eintrag
			else {
				$data = array(time());
				$cache->set('flooding'.$this->id, $data, $config['flooding_time']);
			}
		}
		// MySQL benutzen
		else {
			// Seitenaufrufe auslesen
			$query = query("
				SELECT COUNT(*) FROM ".GLOBPREFIX."flooding
				WHERE
					flooding_playerID = ".$this->id."
					AND floodingTime > ".(time()-$config['flooding_time'])."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$data = mysql_fetch_array($query);
			
			// wenn es zu viele sind, sperren
			if($data[0] > $config['flooding_pages']) {
				return true;
			}
			
			// nur neuen Seitenaufruf hinzufügen, wenn kein starkes Flooding
			if($data[0] < 2*$config['flooding_pages']) {
				query("
					INSERT INTO ".GLOBPREFIX."flooding
					SET
						flooding_playerID = ".$this->id.",
						floodingTime = ".time()."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
		}
		
		// Flooding-Schutz greift nicht
		return false;
	}
	
	/**
	 * API-Key abfragen
	 * @return string API-Key
	 */
	public function getApiKey() {
		$query = query("
			SELECT
				userApiKey
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$this->id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		$data = mysql_fetch_assoc($query);
		
		$apikey = INSTANCE.'-'.$this->id.'-'.$data['userApiKey'];
		
		return $apikey;
	}
	
}


/**
 * löscht einen Spieler und dessen Anhängsel (Favoriten, Routen)
 * @param $id array/int User-ID(s)
 */
function user_del($id) {
	// mehrere Spieler
	if(is_array($id)) {
		foreach($id as $key=>$val) {
			user_del($id);
		}
	}
	// einzelner Spieler
	else {
		// ID sichern
		$id = (int)$id;
		
		// Spieler löschen
		$query = query("
			DELETE FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Favoriten löschen
		query("
			DELETE FROM
				".PREFIX."favoriten
			WHERE
				favoriten_playerID = ".$id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Routen löschen
		query("
			DELETE FROM
				".PREFIX."routen
			WHERE
				routen_playerID = ".$id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
}

/**
 * überprüft, ob ein Spieler die Allianz gewechselt hat und gesperrt werden muss
 * (beim Login und beim Cookie-Autologin)
 * @param $id int User-ID
 */
function user_checkban($id) {
	// Daten sichern
	$id = (int)$id;
	if($id <= 2) {
		return false;
	}
	
	// Allianzwechsel ermitteln
	$query = query("
		SELECT
			allyhistory_allianzenID
		FROM
			".GLOBPREFIX."player_allyhistory
		WHERE
			allyhistory_playerID = ".$id."
			AND allyhistoryFinal = 0
		ORDER BY
			allyhistoryTime DESC
		LIMIT 1
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Spieler hat die Allianz gewechselt und wurde noch nicht aktualisiert
	if(mysql_num_rows($query)) {
		$data = mysql_fetch_assoc($query);
		
		// Ban-Status auslesen
		$banend = 0;
		
		$query = query("
			SELECT
				userBanned
			FROM
				".PREFIX."user
			WHERE
				user_playerID = ".$id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			$banned = mysql_fetch_assoc($query);
			$banned = $banned['userBanned'];
		}
		
		// darf der Spieler die Instanz benutzen?
		$register = 0;
		
		// hat seine Allianz eine Registrierungserlaubnis?
		$query = query("
			SELECT
				register_playerID
			FROM
				".PREFIX."register
			WHERE
				register_playerID = ".$id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		if(mysql_num_rows($query)) {
			$register = 1;
		}
		else if($data['allyhistory_allianzenID']) {
			// hat seine neue Allianz eine Registrierungserlaubnis
			$query = query("
				SELECT
					register_allianzenID
				FROM
					".PREFIX."register
				WHERE
					register_allianzenID = ".$data['allyhistory_allianzenID']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(mysql_num_rows($query)) {
				$register = 1;
			}
		}
		
		// sperren
		if(!$register AND !$banned) {
			$banned = 2;
		}
		// entsperren
		else if($register AND $banned == 2) {
			$banned = 0;
		}
		
		// in DB übernehmen
		mysql_query("
			UPDATE
				".PREFIX."user
			SET
				user_allianzenID = ".$data['allyhistory_allianzenID'].",
				userBanned = ".$banned."
			WHERE
				user_playerID = ".$id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
}



/**
 * IP-Ban - Anzahl der Fehlversuche ermitteln
 *
 * @return int Anzahl der Fehlversuche
 */
function ban_get() {
	global $config, $cache;
	
	$ipban = 0;
	if($config['ipban']) {
		// den Cache benutzen
		if(CACHING) {
			$ipban = $cache->getglobal('ban'.$_SERVER['REMOTE_ADDR']);
			if(!$ipban) $ipban = 0;
		}
		// MySQL benutzen
		else {
			// mit IPv6 nicht möglich
			if(strpos($_SERVER['REMOTE_ADDR'], ':') !== false) {
				return 0;
			}
			
			// veraltete Bans löschen
			query("
				DELETE FROM ".GLOBPREFIX."ban
				WHERE
					banTime < ".(time()-$config['ipban_time']*60)."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Anzahl der Versuche ermitteln
			$query = query("
				SELECT
					banTries
				FROM
					".GLOBPREFIX."ban
				WHERE
					banIP = INET_ATON('".$_SERVER['REMOTE_ADDR']."')
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			if(mysql_num_rows($query)) {
				$data = mysql_fetch_assoc($query);
				$ipban = $data['banTries'];
			}
		}
	}
	
	// Anzahl der Fehlversuche zurückgeben
	return $ipban;
}

/**
 * Ban-Eintrag hinzufügen oder um 1 erhöhen
 * @param $ipban int Anzahl der bisherigen Fehlversuche
 */
function ban_add($ipban) {
	global $config, $cache;
	
	if($config['ipban']) {
		// Cache benutzen
		if(CACHING) {
			$cache->setglobal('ban'.$_SERVER['REMOTE_ADDR'], $ipban+1, $config['ipban_time']*60);
		}
		// MySQL benutzen
		else {
			// mit IPv6 nicht möglich
			if(strpos($_SERVER['REMOTE_ADDR'], ':') !== false) {
				return false;
			}
			
			// IP-Ban erhöhen
			if($ipban) {
				query("
					UPDATE ".GLOBPREFIX."ban
					SET
						banTries = banTries+1
					WHERE
						banIP = INET_ATON('".$_SERVER['REMOTE_ADDR']."')
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
			// IP-Ban neu eintragen
			else {
				query("
					INSERT INTO ".GLOBPREFIX."ban
					SET
						banIP = INET_ATON('".$_SERVER['REMOTE_ADDR']."'),
						banTries = 1,
						banTime = ".time()."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
		}
	}
}

/**
 * IP-Ban der IP löschen
 */
function ban_del() {
	global $config, $cache;
	
	if($config['ipban']) {
		// Cache benutzen
		if(CACHING) {
			$cache->removeglobal('ban'.$_SERVER['REMOTE_ADDR']);
		}
		// MySQL benutzen
		else {
			// mit IPv6 nicht möglich
			if(strpos($_SERVER['REMOTE_ADDR'], ':') !== false) {
				return false;
			}
			
			query("
				DELETE FROM ".GLOBPREFIX."ban
				WHERE
					banIP = INET_ATON('".$_SERVER['REMOTE_ADDR']."')
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
	}
}

/**
 * effektive Berechtigungen ermitteln
 * @param $level int Rechtelevel
 * @param $pallies string gesperrte Allianzen
 * @param $allyrechte string Ally-Einschränkungen
 * @param $userrechte array User-Berechtigungen
 *
 * @return array(
 *		$typ int,
 *		$rechte array,
 *		$pallies array/false,
 *		$pgalas array/false,
 *		$level int Rechtelevel (falls das übergebene nicht existiert)
 * )
 */
function getrechte($level, $pallies, $pgalas, $allyrechte, $userrechte) {
	global $rechte;
	// Berechtigungen
	/* User > Allianz > Rechtelevel
	   - Rechte generell festgelegt durch Rechtelevel
	   - Allianz-Sperren können Rechtelevel-Berechtigungen aufheben
	   - User-Berechtigungen überschreiben alles andere
	*/
	
	// Rechtelevel nicht mehr vorhanden -> erstes nehmen
	if(!isset($rechte[$level])) {
		$rrechte = $rechte[0];
		$level = 0;
	}
	else $rrechte = $rechte[$level];
	
	// Allianz-Sperren
	// Allianz-Sichtbarkeit
	if($pallies != '' AND $pallies != NULL) $pallies = explode('+', $pallies);
	else $pallies = false;
	// Galaxie-Sichtbarkeit
	if($pgalas != '' AND $pgalas != NULL) $pgalas = explode('+', $pgalas);
	else $pgalas = false;
	// Rechte-Sperren
	if($allyrechte != '' AND $allyrechte != NULL) {
		$r = explode('+', $allyrechte);
		foreach($r as $key) {
			$rrechte[$key] = false;
		}
	}
	
	// spezielle Userberechtigungen
	if($userrechte != '')  {
		$r = json_decode($userrechte, true);
		// Sperren für Allianz- und Galaxie-Sichtbarkeit aufheben
		if(isset($r['override_allies'])) {
			$pallies = false;
			unset($r['override_allies']);
		}
		if(isset($r['override_galas'])) {
			$pgalas = false;
			unset($r['override_galas']);
		}
		// Userberechtigungen anwenden
		foreach($r as $key=>$val) {
			$rrechte[$key] = $val;
		}
	}
	
	// Typ ermitteln
	// 0 - normal, 1 - eingeschränkt, 2 - modifiziert
	$typ = 0;
	
	if($userrechte != '') {
		$typ = 2;
	}
	else if($pgalas OR $pallies OR $allyrechte != '') {
		$typ = 1;
	}
	
	// zurückgeben
	return array(
		$typ,
		$rrechte,
		$pallies,
		$pgalas,
		$level
	);
}


/**
 * OD-API abfragen
 * @param $uid int ID des Spielers (playerID)
 * @param $always bool immer abfragen
 *
 * @return bool Erfolg
 */
function odrequest($uid, $always = false, $auto = false) {
	global $odrallies, $dbs, $cache, $gconfig, $config, $odip;
	
	/*
	Beispiel-Output
	
playeratm=4173&userid=602511&name=Kryops&points=34202&titel=&warpoints=8608&gesinnung=2138&allianz_tag=%3D+%5C+S+%2F+%3D&allianz_name=S%86arflee%86&allianz_punkte=1334696&grafik=&erstellzeit=0.00368309020996&serverzeit=1266585144&plcount=30&kriegspunkte=8608&questpunkte=809&handelspunkte=8814&rasse=3&allianzmembers=41&allianzkriegspunkte=0&gpunkte=26848&allianz_id=3795&meta_id=409&metaname=Dark+Federation&metatag=_-%3DD-F%3D-_&flottenadmin=1&startzeit=1244350748&version=1.6

	*/
	
	// nur numerische IDs zulassen
	if(!is_numeric($uid)) return false;
	$uid = (int)$uid;
	
	// X und Rebellion abfangen
	if(!$uid) return false;
	if($uid <= 3 OR $uid > 2000000) return false;
	
	// Downtime
	if(DOWNTIME AND date("G") == 4) return false;
	
	// OD Lag-Schutz
	if(CACHING AND !$always AND $c = $cache->getglobal('odrequest_lag')) {
		return false;
	}
	
	// Cache abfragen und eintragen
	if(CACHING AND !$always AND INSTANCE AND $c = $cache->getglobal('odrequest'.$uid)) {
		return false;
	}
	
	// Player-Daten auslesen
	$query = query("
		SELECT
			player_allianzenID,
			playerPlaneten,
			playerImppunkte,
			playerGesinnung,
			playerActivity,
			playerUpdate
		FROM
			".$config['mysql_globprefix']."player
		WHERE
			playerID = ".$uid."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// Player-Daten fetchen, falls vorhanden
	if(mysql_num_rows($query)) {
		$data = mysql_fetch_assoc($query);
		// darf das Request schon wieder abgesetzt werden?
		if(time()-$gconfig['odrequest_mintime']*60 < $data['playerUpdate']) return false;
		if(time()-$gconfig['odrequest_mintime2']*60 < $data['playerUpdate'] AND !$always) return false;
	}
	// nicht vorhanden
	else {
		$data = false;
		
		// Cache-Eintrag setzen
		if(CACHING) {
			$cache->setglobal('odrequest'.$uid, time(), $gconfig['odrequest_mintime2']*60);
		}
	}
	
	
	// OD-Interface abfragen
	if($odip == NULL) {
		$odip = gethostbyname('www.omega-day.com');
	}
	
	$file = 'http://'.$odip.'/game/states/live_state.php?userid='.$uid.'&world='.ODWORLD;
	$connection = @fopen($file,'r');
	
	// Keine Verbindung
	if(!$connection) {
		
		// Cache-Lag-Flag setzen
		if(CACHING) {
			$cache->setglobal('odrequest_lag', 1, 180);
		}
		
		return false;
	}
	
	// Antwort auslesen
	$buffer = fread($connection, 4096);
	fclose($connection);
	
	// Internal Server Error
	if(stripos($buffer, 'Internal Server Error') !== false) {
		return false;
	}
	
	//
	// odrequest-Log
	//
	/*
	
	$f = fopen("./odrlog.txt", "a");
	if($f) {
		fwrite($f, '['.date('Y-m-d H:i:s').'] odrequest '.$uid."\n");
		fclose($f);
	}
	*/
	
	//
	//
	//
	
	
	// odrequest-Zähler erhöhen
	if(CACHING) {
		$count = $cache->getglobal('odrequest');
		if($count === false) {
			$count = 1;
		}
		else $count++;
		
		$cache->setglobal('odrequest', $count, 86400);
	}
	
	// OD-MySQL-Fehler umgehen
	if(strpos($buffer, '<b>68</b><br />') !== false) {
		$buffer = preg_replace('#^(.*)<b>68</b><br />#Uis', '', $buffer);
	}
	
	// als Array parsen
	parse_str($buffer, $oddata);
	
	// bei ungewöhnlicher Rückgabe abbrechen
	if(!isset($oddata['name'], $oddata['version'])) {
		return false;
	}
	
	// OD-Lag-Schutz
	// bei über 1 Sekunde Erstellzeit Cache-Flag setzen
	if($oddata['erstellzeit'] > 1) {
		$cache->setglobal('odrequest_lag', 1, 60);
	}
	
	// HTML und CP1252 dekodieren und alles in UTF-8 enkodieren
	foreach($oddata as $key=>$val) {
		if(!is_numeric($val) AND $val != '') {
			$oddata[$key] = html_entity_decode(mb_convert_encoding($val, 'UTF-8', 'cp1252'), ENT_QUOTES, 'UTF-8');
		}
	}
	
	// User in OD vorhanden
	if(isset($oddata['name']) AND count($oddata) > 1 AND $oddata['name'] != '') {
		// Daten sichern
		$oddata['points'] = (int)$oddata['points'];
		$oddata['allianzmembers'] = (int)$oddata['allianzmembers'];
		$oddata['allianz_id'] = (int)$oddata['allianz_id'];
		$oddata['gesinnung'] = (int)$oddata['gesinnung'];
		$oddata['plcount'] = (int)$oddata['plcount'];
		$oddata['rasse'] = (int)$oddata['rasse'];
		$oddata['flottenadmin'] = (int)$oddata['flottenadmin'];
		
		// User in der DB vorhanden -> aktualisieren
		if($data) {
			// letzte Aktivität aus der Änderung der Imperiumspunkte oder Gesinnnug berechnen
			if($data['playerImppunkte'] != $oddata['points'] OR $data['playerGesinnung'] != $oddata['gesinnung']) $activity = time();
			else $activity = $data['playerActivity'];
			
			// player aktualisieren
			query("
				UPDATE ".$config['mysql_globprefix']."player
				SET
					player_allianzenID = ".$oddata['allianz_id'].",
					playerPlaneten = ".$oddata['plcount'].",
					playerImppunkte = ".$oddata['points'].",
					playerGesinnung = ".$oddata['gesinnung'].",
					playerRasse = ".$oddata['rasse'].",
					playerFA = ".$oddata['flottenadmin'].",
					playerActivity = ".$activity.",
					playerUpdate = ".time()."
				WHERE
					playerID = ".$uid."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianzwechsel eintragen
			if($data['player_allianzenID'] != $oddata['allianz_id']) {
				query("
					INSERT INTO ".$config['mysql_globprefix']."player_allyhistory
					SET
						allyhistory_playerID = ".$uid.",
						allyhistory_allianzenID = ".$oddata['allianz_id'].",
						allyhistoryTime = ".time().",
						allyHistoryLastAlly = ".$data['player_allianzenID']."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
		}
		// User neu eintragen
		else {
			
			query("
				INSERT INTO ".$config['mysql_globprefix']."player
				SET
					playerID = ".$uid.",
					playerName = '".escape($oddata['name'])."',
					player_allianzenID = ".$oddata['allianz_id'].",
					playerPlaneten = ".$oddata['plcount'].",
					playerImppunkte = ".$oddata['points'].",
					playerGesinnung = ".$oddata['gesinnung'].",
					playerRasse = ".$oddata['rasse'].",
					playerFA = ".$oddata['flottenadmin'].",
					playerActivity = 0,
					playerUpdate = ".time()."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz-History eintragen
			query("
				INSERT INTO ".$config['mysql_globprefix']."player_allyhistory
				SET
					allyhistory_playerID = ".$uid.",
					allyhistory_allianzenID = ".$oddata['allianz_id'].",
					allyhistoryTime = ".time().",
					allyHistoryLastAlly = NULL
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		
		// wurde die Allianz schon aktualisiert?
		if(!isset($odrallies)) $odrallies = array();
		
		// Allianz aktualisieren
		if($oddata['allianz_id'] AND !isset($odrallies[$oddata['allianz_id']])) {
			query("
				UPDATE ".$config['mysql_globprefix']."allianzen
				SET
					allianzenTag = '".escape($oddata['allianz_tag'])."',
					allianzenName = '".escape($oddata['allianz_name'])."',
					allianzenMember = ".$oddata['allianzmembers'].",
					allianzenUpdate = ".time()."
				WHERE
					allianzenID = ".$oddata['allianz_id']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// Allianz noch nicht eingetragen
			if(!mysql_affected_rows()) {
				query("
					INSERT IGNORE INTO ".$config['mysql_globprefix']."allianzen
					SET
						allianzenID = ".$oddata['allianz_id'].",
						allianzenTag = '".escape($oddata['allianz_tag'])."',
						allianzenName = '".escape($oddata['allianz_name'])."',
						allianzenMember = ".$oddata['allianzmembers'].",
						allianzenUpdate = ".time()."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			}
			
			// Allianz an Array anhängen
			$odrallies[$oddata['allianz_id']] = true;
		}
	}
	// User hat sich gelöscht
	else if(isset($oddata['name'], $oddata['playeratm']) AND $oddata['playeratm']) {
		if(!$auto) {
			// Deleted auf 1, Ally auf 0 setzen
			query("
				UPDATE ".$config['mysql_globprefix']."player
				SET
					playerDeleted = 1,
					player_allianzenID = 0,
					playerUpdate = ".time()."
				WHERE
					playerID = ".$uid."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		}
		else {
			return false;
		}
		
		///////////////////////////////////////

		//
		// odrequest-Log
		//
		
		/*
		$f = @fopen("/var/www/oddb/odrdelete.txt", "a");
		if($f) {
			fwrite($f, "\n".'['.date('Y-m-d H:i:s').'] delete '.$uid.': '.$buffer."\n");
			fclose($f);
		}
		*/
		
		//
		//
		//
		
		
	}
	
	return true;
}



?>