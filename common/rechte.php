<?php
error_reporting(E_ALL);

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



class Rechte {
	
	/**
	 * Berechtigung ermitteln, den Scan eines Planeten zu sehen
	 * @param array $pl kompletter Planeten-Datensatz
	 * @return bool Berechtigung
	 */
	public static function getRechteShowPlanet($pl) {
		
		global $user, $status_meta;
		
		// Generelle Berechtigung
		$r = $user->rechte['show_planet'];
		
		// bei eigenen Planeten immer Berechtigung, falls globale Berechtigung
		if($r AND $pl['planeten_playerID'] != $user->id) {
			// keine Berechtigung (Ally)
			if(!$user->rechte['show_planet_ally'] AND $user->allianz AND $pl['player_allianzenID'] == $user->allianz) {
				$r = false;
			}
			// keine Berechtigung (Meta)
			else if($user->allianz AND !$user->rechte['show_planet_meta'] AND $pl['statusStatus'] == $status_meta) {
				$r = false;
			}
			// keine Berechtigung (Allianz gesperrt)
			else if($user->protectedAllies AND in_array($pl['player_allianzenID'], $user->protectedAllies)) {
				$r = false;
			}
			// keine Berechtigung (registrierte Allianzen)
			else if(!$user->rechte['show_planet_register'] AND $pl['register_allianzenID']) {
				$r = false;
			}
		}
		
		return $r;
	}
	
	
	/**
	 * Berechtigung ermitteln, ein Myrigate an einem Planeten zu sehen
	 * @param array $pl kompletter Planeten-Datensatz
	 * @return bool Berechtigung
	 */
	public static function getRechteShowMyrigate($pl) {
		
		global $user, $status_meta;
		
		$r = true;
		
		// keine Berechtigung (global)
		if(!$user->rechte['show_myrigates']) {
			$r = false;
		}
		// Myrigates eigener Planeten ansonsten immer sichtbar
		else if($user->id == $pl['planeten_playerID']) {}
		// keine Berechtigung (Allianz)
		else if($user->allianz AND !$user->rechte['show_myrigates_ally'] AND $user->allianz == $pl['player_allianzenID']) {
			$r = false;
		}
		// keine Berechtigung (Meta)
		else if($user->allianz AND !$user->rechte['show_myrigates_meta'] AND $pl['statusStatus'] == $status_meta AND $user->allianz != $pl['player_allianzenID']) {
			$r = false;
		}
		// keine Berechtigung (Allianz gesperrt)
		else if($user->protectedAllies AND in_array($pl['player_allianzenID'], $user->protectedAllies)) {
			$r = false;
		}
		// keine Berechtigung (registrierte Allianzen)
		else if(!$user->rechte['show_myrigates_register'] AND $pl['register_allianzenID'] !== NULL AND $pl['statusStatus'] != $status_meta) {
			$r = false;
		}
		
		return $r;
	}
	
	
	/**
	 * Berechtigung ermitteln, eine Ressplanet-Markierung zu sehen
	 * @param array $pl kompletter Planeten-Datensatz
	 * @return bool Berechtigung
	 */
	public static function getRechteRessplanet($pl) {
		
		global $user, $status_meta;
		
		$r = true;
		
		// eigener Planeten ansonsten immer sichtbar
		if($user->id == $pl['planeten_playerID']) {}
		// keine Berechtigung (Allianz)
		else if($user->allianz AND !$user->rechte['ressplani_ally'] AND $user->allianz == $pl['player_allianzenID']) {
			$r = false;
		}
		// keine Berechtigung (Meta)
		else if($user->allianz AND !$user->rechte['ressplani_meta'] AND $pl['statusStatus'] == $status_meta) {
			$r = false;
		}
		// keine Berechtigung (registrierte Allianzen)
		else if(!$user->rechte['ressplani_register'] AND $pl['register_allianzenID'] !== NULL) {
			$r = false;
		}
		
		return $r;
	}
	
	
	/**
	 * Berechtigung ermitteln, eine Werft-Markierung zu sehen
	 * @param array $pl kompletter Planeten-Datensatz
	 * @return bool Berechtigung
	 */
	public static function getRechteWerft($pl) {
		
		global $user, $status_meta;
		
		$r = true;
		
		// eigener Planeten ansonsten immer sichtbar
		if($user->id == $pl['planeten_playerID']) {}
		// keine Berechtigung (Allianz)
		else if($user->allianz AND !$user->rechte['werft_ally'] AND $user->allianz == $pl['player_allianzenID']) {
			$r = false;
		}
		// keine Berechtigung (Meta)
		else if($user->allianz AND !$user->rechte['werft_meta'] AND $pl['statusStatus'] == $status_meta) {
			$r = false;
		}
		// keine Berechtigung (registrierte Allianzen)
		else if(!$user->rechte['werft_register'] AND $pl['register_allianzenID'] !== NULL) {
			$r = false;
		}
		
		return $r;
	}
	
	/**
	 * Berechtigung ermitteln, eine Bunker-Markierung zu sehen
	 * @param array $pl kompletter Planeten-Datensatz
	 * @return bool Berechtigung
	 */
	public static function getRechteBunker($pl) {
		
		global $user, $status_meta;
		
		$r = true;
		
		// eigener Planeten ansonsten immer sichtbar
		if($user->id == $pl['planeten_playerID']) {}
		// keine Berechtigung (Allianz)
		else if($user->allianz AND !$user->rechte['bunker_ally'] AND $user->allianz == $pl['player_allianzenID']) {
			$r = false;
		}
		// keine Berechtigung (Meta)
		else if($user->allianz AND !$user->rechte['bunker_meta'] AND $pl['statusStatus'] == $status_meta) {
			$r = false;
		}
		// keine Berechtigung (registrierte Allianzen)
		else if(!$user->rechte['bunker_register'] AND $pl['register_allianzenID'] !== NULL) {
			$r = false;
		}
		
		return $r;
	}
	
	
}



?>