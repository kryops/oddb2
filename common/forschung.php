<?php
error_reporting(E_ALL);

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



class Forschung {
	
	/**
	 * Grafikpfad-Wurzel
	 */
	public static $baseUrl = 'http://omega-day.com/static/img/';
	
	public static $kategorien = array(
		1 => 'Gebäude',
		2 => 'Schiffe',
		3 => 'Systeme'
	);
	
	
	/**
	 * Template für das Forschungs-Array eines Users, wenn die DB-Spalte leer ist
	 * @var unknown
	 */
	public static $userForschungTemplate = array(
		'update' => array(
			1=>0,
			2=>0,
			3=>0
		),
		
		'current'=>0,
		'current_end'=>0,
		
		1=>array(),
		2=>array(),
		3=>array()
	);
	
	
	/**
	 * welche Forschungen (Pfad oder Name [in beiden Sprachen]) sollen ignoriert werden?
	 * @var unknown
	 */
	private static $ignore = array(
		'ships/steel_s.gif',
		'ships/titanid_s.gif',
		'ships/hellfire_s.gif',
		'ships/collidingfield_s.gif',
		'ships/hyperium_s.gif',
		'ships/freedom_s.gif',
		'ships/shelloptress_s.gif',
		'ships/core_s.gif',
		'ships/steel01_s.gif',
			
		'buildings/basiscamp.gif',
		'buildings/forschungscamp.gif',
		
		'ship-components/generatorV1_01.gif',
		'ship-components/ion1.gif',
		'ship-components/transportV101.gif'
	);
	
	
	private static $forschungen = false;
	private static $forschungen_path = false;
	
	
	/**
	 * Alle Forschungen auslesen und zwischenspeichern
	 * @return array
	 */
	public static function getAll() {
		
		if(self::$forschungen === false) {
			
			self::$forschungen = array();
			self::$forschungen_path = array();
			
			$query = query("
				SELECT
					forschungID,
					forschungKategorie,
					forschungName,
					forschungPfad
				FROM
					".GLOBPREFIX."forschung
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			while($row = mysql_fetch_assoc($query)) {
				self::$forschungen[$row['forschungID']] = $row;
				self::$forschungen_path[$row['forschungPfad']] = $row['forschungID'];
			}
			
		}
		
		return self::$forschungen;
	}
	
	
	/**
	 * Datensatz einer Forschung zurückgeben
	 * @param int $id
	 * @return boolean
	 */
	public static function get($id) {
		
		if(self::$forschungen === false) {
			self::getAll();
		}
		
		if(!isset(self::$forschungen[$id])) {
			return false;
		}
		
		return self::$forschungen[$id];
	}
	
	
	/**
	 * Alle Forschungen einer Kategorie ermitteln
	 * @param integer $kat
	 */
	public static function getKategorie($kat) {
		
		if(self::$forschungen === false) {
			self::getAll();
		}
		
		
		$array = array();
		
		foreach(self::$forschungen as $fid=>$f) {
			if($f['forschungKategorie'] == $kat) {
				$array[$fid] = $f;
			}
		}
		
		return $array;
	}
	
	
	/**
	 * ID einer Forschung aus dem Pfad ermitteln
	 * @param string $path
	 * @return boolean
	 */
	public static function getId($path) {
		
		if(self::$forschungen_path === false) {
			self::getAll();
		}
		
		if(!isset(self::$forschungen_path[$path])) {
			return false;
		}
		
		return self::$forschungen_path[$path];
	}
	
	/**
	 * Forschung eintragen
	 * @param string $path
	 * @param string $name
	 * @param int $kategorie
	 * @return ID|false
	 */
	public static function add($path, $name, $kategorie) {
		
		// Kategorie validieren
		if(!isset(self::$kategorien[$kategorie])) {
			return false;
		}
		
		// Technologie ignorieren
		if(in_array($name, self::$ignore) OR in_array($path, self::$ignore)) {
			return false;
		}
		
		
		if(($id = self::getId($path)) === false) {
			
			// in DB eintragen
			query("
				INSERT INTO
					".GLOBPREFIX."forschung
				SET
					forschungKategorie = ".(int)$kategorie.",
					forschungName = '".escape($name)."',
					forschungPfad = '".escape($path)."'
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$id = mysql_insert_id();
			
			// Zwischenspeicher aktualisieren
			self::$forschungen[$id] = array(
				'forschungKategorie' => (int)$kategorie,
				'forschungName' => $name,
				'forschungPfad' => $path
			);
			
			self::$forschungen_path[$path] = $id;
			
		}
		
		return $id;
	}
	
	
	/**
	 * Forschung-Spalte eines Benutzers in Array umwandeln oder neu anlegen
	 * @param string $f MySQL-Spalte userForschung
	 * @return array
	 */
	public static function getUserArray($f) {
		
		if($f == '') {
			return self::$userForschungTemplate;
		}
		
		return json_decode($f, true);
	}
	
}

?>