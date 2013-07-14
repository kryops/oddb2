<?php
error_reporting(E_ALL);

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


class ODDBApi {
	
	public static $instance;
	
	public static $uid;
	
	public static $apikey;
	
	
	/**
	 * API-Content ausgeben
	 * @param array $out Ausgabe-Array
	 * @param boolean $die nach der Ausgabe abbrechen
	 */
	public static function output($out, $die=false) {
		
		header('Content-Type: application/json; charset=utf-8');
		header('Access-Control-Allow-Origin: *');
		
		echo json_encode($out);
		
		if($die) {
			die();
		}
	}
	
	
	/**
	 * Fehler ausgeben und abbrechen
	 * @param string $error
	 */
	public static function outputError($error) {
		
		self::output(
			array('error'=>$error),
			true
		);
		
	}
	
	
	/**
	 * API-Key verifizieren und User-Objekt generieren
	 * @param string $key
	 */
	public static function verifyKey($key) {
		
		global $config, $cache;
		
		$key = explode('-', $key);
		
		// ungültig
		if(count($key) < 3) {
			self::outputError('API-Key ungültig!');
		}
		
		// auswerten
		// Instanz schon in der index.php gesetzt
		self::$instance = (int)array_shift($key);
		
		self::$uid = (int)array_shift($key);
		
		self::$apikey = implode('-', $key);
		
		
		
		global $user;
		
		$user = new user;
		
		
		// IP-Ban überprüfen
		$ipban = ban_get();
		
		if($config['ipban'] AND $ipban > $config['ipban']) {
			self::outputError('Deine IP ist aufgrund vieler Fehlversuche gesperrt. Bitte versuche es später wieder!');
		}
		
		// Benutzerdaten abfragen
		$query = query("
			SELECT
				user_playerName,
				user_allianzenID,
				userRechtelevel,
				userRechte,
				userBanned,
				userSettings,
				userOnlineDB,
				userOnlinePlugin,
				userODServer,
				registerProtectedAllies,
				registerProtectedGalas,
				registerAllyRechte
			FROM
				".PREFIX."user
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = user_allianzenID
			WHERE
				user_playerID = ".self::$uid."
				AND userApiKey = '".escape(self::$apikey)."'
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// User existiert nicht oder falscher Key
		if(!mysql_num_rows($query)) {
			
			ban_add($ipban);
			
			self::outputError('API-Key ungültig!');
			
		}
		
		
		$data = mysql_fetch_assoc($query);
		
		$user->login = true;
		$user->id = self::$uid;
		
		// Objekt mit Daten füllen
		$user->populateData($data);
		
		// User gesperrt
		if($user->banned) {
			self::outputError('Dein Account wurde gesperrt!');
		}
		
		// Flooding-Schutz
		$flooding = $user->flooding();
		
		if($flooding) {
			self::outputError('Flooding-Schutz! Du kannst innerhalb von '.$config['flooding_time'].' Sekunden maximal '.$config['flooding_pages'].' Seiten aufrufen!');
		}
		
		// keine Berechtigung
		if(!$user->rechte['api']) {
			self::outputError('Du hast keine Berechtigung, die ODDB-API zu benutzen!');
		}
		
	}
	
	
	/**
	 * Planeten suchen und als JSON ausgeben
	 * @param array $filter Suchfilter
	 */
	public static function searchPlanets($filter) {
		
		global $status, $rassen, $user;
		
		General::loadClass('Rechte');
		General::loadClass('Invasionen');
		General::loadClass('Search');
		
		// Antrieb
		$antr = isset($filter['antr']) ? $filter['antr'] : $user->settings['antrieb'];
		
		// Entfernung und Sortierung
		$entf = Search::getEntf($filter);
		$sort = Search::getSort($filter, $entf);
		
		// Invasionen abfragen
		$invasionen = Invasionen::get();
		
		// Planeten suchen
		$conds = Search::buildConditions($filter);
		
		// Limit und Offset
		$limit = isset($filter['limit']) ? (int)$filter['limit'] : 100;
		if($limit < 1) {
			$limit = 1;
		}
		if($limit > 1000) {
			$limit = 1000;
		}
		
		$offset = isset($filter['offset']) ? (int)$filter['offset'] : 0;
		
		
		$search = Search::getSearchAsArray($conds, $entf, $sort, $offset, $limit);
		
		$inhaber_map = array(
			-3 => 'Altrasse',
			-2 => 'Seze Lux',
			-1 => 'unbekannt',
			0 => 'frei',
			1 => 'X',
			2 => 'Rebellion'
		);
		
		$out = array();
		
		
		// Suchergebinsse umformen
		foreach($search as $row) {
			
			// Inhaber und Rasse ermitteln
			$inhaber = ($row['planeten_playerID'] > 2);
			$inhabername = $row['playerName'];
			
			if(isset($inhaber_map[$row['planeten_playerID']])) {
				$inhabername = $inhaber_map[$row['planeten_playerID']];
			}
			
			$rasse = false;
			
			if($row['playerRasse'] AND isset($rassen[$row['playerRasse']])) {
				$rasse = $rassen[$row['playerRasse']];
			}
			else if(in_array($row['planeten_playerID'], array(1,2,-2))) {
				$rasse = $rassen[10];
			}
			
			// Sprunggenerator
			if($row['planetenMyrigate'] == 2) {
				$row['planetenMyrigate'] = true;
			}
			
			
			$outrow = array(
				
				'planet' => array(
					'id' => $row['planetenID'],
					'name' => $row['planetenName'],
					
					'gescannt' => $row['planetenUpdateOverview'],
					'gescanntVoll' => $row['planetenUpdate'],
					'unscannbar' => $row['planetenUnscannbar'],
					
					'typ' => $row['planetenTyp'],
					'groesse' => $row['planetenGroesse'],
					'bevoelkerung' => $row['planetenBevoelkerung'],
					'forschung' => ($row['planetenUpdate'] ? $row['planetenForschung'] : false),
					'industrie' => ($row['planetenUpdate'] ? $row['planetenIndustrie'] : false),
					
					'werte' => array(
						'erz' => $row['planetenRWErz'],
						'metall' => $row['planetenRWErz'],
						'wolfram' => $row['planetenRWWolfram'],
						'kristall' => $row['planetenRWKristall'],
						'fluor' => $row['planetenRWFluor']
					),
					
					'produktion' => ($row['planetenUpdate'] ? array(
						'erz' => $row['planetenRPErz'],
						'metall' => $row['planetenRPMetall'],
						'wolfram' => $row['planetenRPWolfram'],
						'kristall' => $row['planetenRPKristall'],
						'fluor' => $row['planetenRPFluor']
					) : false),
					
					'vorrat' => ($row['planetenUpdateOverview'] ? array(
						'erz' => $row['planetenRMErz'],
						'metall' => $row['planetenRMMetall'],
						'wolfram' => $row['planetenRMWolfram'],
						'kristall' => $row['planetenRMKristall'],
						'fluor' => $row['planetenRMFluor']
					) : false),
					
					'natives' => $row['planetenNatives'],
					'kommentar' => $row['planetenKommentar'],
					
					'scan' => (($row['planetenUpdateOverview'] AND Rechte::getRechteShowPlanet($row)) ? array(
						'url' => odscreen($row['planetenTyp'], $row['planetenGebPlanet'], $row['planetenGebOrbit']),
						'planet' => $row['planetenGebPlanet'],
						'orbit' => $row['planetenGebOrbit'],
						'orbiter' => $row['planetenOrbiter']
					) : false),
					
					'myrigate' => (($row['planetenMyrigate'] AND Rechte::getRechteShowMyrigate($row)) ? $row['planetenMyrigate'] : false),
					
					'gateEntfernung' => ($row['planetenGateEntf'] !== NULL ? flugdauer($row['planetenGateEntf'], $antr) : false),
					
					'getoxxt' => ($user->rechte['toxxraid'] ? $row['planetenGetoxxt'] : false),
					'geraidet' => ($user->rechte['toxxraid'] ? $row['planetenGeraidet'] : false),
					
					'bergbau' => ($user->rechte['fremdinvakolos'] AND $row['schiffeBergbau'] > 0),
					'terraformer' => ($user->rechte['fremdinvakolos'] AND $row['schiffeTerraformer'] > 0),
					
					'ressplanet' => (($row['planetenRessplani'] AND Rechte::getRechteRessplanet($row)) ? true : false),
					'werft' => (($row['planetenWerft'] AND Rechte::getRechteWerft($row)) ? true : false),
					'bunker' => (($row['planetenBunker'] AND Rechte::getRechteBunker($row)) ? true : false)
				),
				
				'system' => array(
					'id' => $row['systemeID'],
					'galaxie' => $row['systeme_galaxienID'],
					'x' => $row['systemeX'],
					'y' => $row['systemeY'],
					'z' => $row['systemeZ'],
					
					'gescannt' => $row['systemeUpdate']
				),
				
				'inhaber' => array(
					'id' => $row['planeten_playerID'],
					'name' => $inhabername,
					'rasse' => $rasse,
					'punkte' => ($row['playerImppunkte'] ? $row['playerImppunkte'] : false),
					'umodus' => ($row['playerUmod'] ? true : false),
					'geloescht' => ($row['playerDeleted'] ? true : false),
					
					'allianz' => ($row['player_allianzenID'] ? array(
						'id' => $row['player_allianzenID'],
						'tag' => $row['allianzenTag'],
						'name' => $row['allianzenName'],
						'status' => (($row['statusStatus'] == NULL OR !isset($status[$row['statusStatus']])) ? $status[0] : $status[$row['statusStatus']]),
					) : false)
				),
				
				'entfernung' => (isset($row['planetenEntfernung']) ? flugdauer($row['planetenEntfernung'], $antr) : false),
				
				'antrieb' => $antr,
				
				'invasionen' => array()
				
			);
			
			// Invasionen anhängen
			if(isset($invasionen[$row['planetenID']])) {
				
				foreach($invasionen[$row['planetenID']] as $inva) {
					
					$invarow = array(
						'typ' => $inva['invasionenTypName'],
						'ende' => ($inva['invasionenEnde'] ? $inva['invasionenEnde'] : false),	
						'kommentar' => $inva['invasionenKommentar'],
						
						'aggressor' => ($inva['invasionenAggressor'] ? array(
							'id' => $inva['invasionenAggressor'],
							'name' => $inva['a_playerName'],
							'allianz' => ($inva['a_player_allianzenID'] ? array(
								'id' => $inva['a_player_allianzenID'],
								'tag' => $inva['a_allianzenTag']
							) : false)
						) : false)
					);
					
					$outrow['invasionen'][] = $invarow;
				}
				
			}
			
			$out[] = $outrow;
		}
		
		
		// ausgeben
		self::output($out, true);
		
	}
	
	
	/**
	 * API-Anfrage bearbeiten
	 */
	public static function runApi() {
		
		global $config;
		
		if(!isset($_GET['key'])) {
			self::outputError('Kein API-Key angegeben!');
		}
		
		self::verifyKey($_GET['key']);
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			General::loadClass('Search');
			insertlog(28, 'benutzt die API ('.Search::getSearchDescription($_GET).')');
		}
		
		self::searchPlanets($_GET);
		
	}
	
}


?>