<?php

/**
 * common/cache.php
 * Cache-Klasse
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


/**
 * Cache-Abstraktions-Klasse
 * APC / memcached
 */
class cache {
	
	private $prefix;
	
	/**
	 * Konstruktor
	 * evtl memcached-Verbindung aufbauen
	 * CACHING-Konstante definieren
	 */
	function __construct() {
		global $config;
		
		// APC vorhanden
		if($config['caching'] == 1) {
			if(!function_exists('apc_fetch')) {
				$config['caching'] = 0;
			}
		}
		// memcached-Verbindung
		else if($config['caching'] == 2) {
			$memcache = new Memcache;
			if(!@$memcache->connect($config['memcached_host'], $config['memcached_port'])) {
				$config['caching'] = 0;
			}
		}
		
		// Präfix übernehmen
		$this->prefix = $config['caching_prefix'];
		
		// Konstante setzen
		define('CACHING', $config['caching']);
	}
	
	/**
	 * Eintrag aus dem Cache lesen (instanzbasiert)
	 * @param $key string Schlüssel
	 *
	 * @return mixed Inhalt / false
	 */
	public function get($key) {
		// APC
		if(CACHING == 1) {
			return apc_fetch($this->prefix.INSTANCE.$key);
		}
		// memcached
		else if(CACHING == 2) {
			global $memcache;
			return $memcache->get($this->prefix.INSTANCE.$key);
		}
		// Caching deaktiviert
		else return false;
	}
	/**
	 * Eintrag in den Cache schreiben (instanzbasiert)
	 * @param $key string Schlüssel
	 * @param $content string Inhalt (muss vorher serialisiert werden)
	 * @param $ttl int Zeit in s, wie lange der Eintrag gespeichert bleiben soll (default 0 = unendlich)
	 *
	 * @return bool Erfolg
	 */
	public function set($key, $content, $ttl=0) {
		// APC
		if(CACHING == 1) {
			return apc_store($this->prefix.INSTANCE.$key, $content, $ttl);
		}
		// memcached
		else if(CACHING == 2) {
			global $memcache;
			return $memcache->set($this->prefix.INSTANCE.$key, $content, false, $ttl);
		}
		// Caching deaktiviert
		else return false;
	}
	/**
	 * Eintrag aus dem Cache löschen (instanzbasiert)
	 * @param $key string Schlüssel
	 *
	 * @return bool Erfolg
	 */
	public function remove($key) {
		// APC
		if(CACHING == 1) {
			return apc_delete($this->prefix.INSTANCE.$key);
		}
		// memcached
		else if(CACHING == 2) {
			global $memcache;
			return $memcache->delete($this->prefix.INSTANCE.$key);
		}
		// Caching deaktiviert
		else return false;
	}
	
	/**
	 * löscht alle Einträge vom Cache-Server
	 */
	public function clear() {
		// APC
		if(CACHING == 1) {
			return apc_clear_cache('user');
		}
		// memcached
		else if(CACHING == 2) {
			global $memcache;
			return $memcache->flush();
		}
		// Caching deaktiviert
		else return false;
	}
	
	/**
	 * Eintrag aus dem Cache lesen (global)
	 * @param $key string Schlüssel
	 *
	 * @return mixed Inhalt / false
	 */
	public function getglobal($key) {
		// APC
		if(CACHING == 1) {
			return apc_fetch($this->prefix.$key);
		}
		// memcached
		else if(CACHING == 2) {
			global $memcache;
			return $memcache->get($this->prefix.$key);
		}
		// Caching deaktiviert
		else return false;
	}
	/**
	 * Eintrag in den Cache schreiben (global)
	 * @param $key string Schlüssel
	 * @param $content string Inhalt (muss vorher serialisiert werden)
	 * @param $ttl int Zeit in s, wie lange der Eintrag gespeichert bleiben soll (default 0 = unendlich)
	 *
	 * @return bool Erfolg
	 */
	public function setglobal($key, $content, $ttl=0) {
		// APC
		if(CACHING == 1) {
			return apc_store($this->prefix.$key, $content, $ttl);
		}
		// memcached
		else if(CACHING == 2) {
			global $memcache;
			return $memcache->set($this->prefix.$key, $content, false, $ttl);
		}
		// Caching deaktiviert
		else return false;
	}
	/**
	 * Eintrag aus dem Cache löschen (global)
	 * @param $key string Schlüssel
	 *
	 * @return bool Erfolg
	 */
	public function removeglobal($key) {
		// APC
		if(CACHING == 1) {
			return apc_delete($this->prefix.$key);
		}
		// memcached
		else if(CACHING == 2) {
			global $memcache;
			return $memcache->delete($this->prefix.$key);
		}
		// Caching deaktiviert
		else return false;
	}
	
	/**
	 * den Cache für alle angemeldeten Spieler einer Allianz löschen
	 * @param $id int Ally-ID
	 */
	function delally($id) {
		// Daten sichern
		$id = (int)$id;
		
		// angemeldete Spieler der Allianz ermitteln
		$query = query("
			SELECT
				user_playerID
			FROM
				".PREFIX."user
			WHERE
				user_allianzenID = ".$id."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			// Cache löschen
			$this->remove('user'.$row['user_playerID']);
		}
	}
}


?>