<?php
error_reporting(E_ALL);

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');


class Invasionen {
	
	public static $invanamen = array(
		1=>'Invasion',
		2=>'Resonation',
		3=>'Genesis',
		4=>'Besatzung',
		5=>'Kolonisation'
	);
	
	public static $invanamen_short = array(
		1=>'Inva',
		2=>'Reso',
		3=>'Genesis',
		4=>'Besatzung',
		5=>'Kolo'
	);
	
	/**
	 * Invasionen abfragen
	 * @param array $conds
	 * @param bool $filter_rechte Filter durch Berechtigungs-Einschränkungen ergänzen
	 * @return array
	 * 		Invasionen-Datensätze
	 * 		Schlüssel = Planeten-ID
	 */
	public static function get($conds=array(), $filter_rechte=true) {
		
		// Berechtigungen filtern
		if($filter_rechte) {
			
			global $user;
			
			// keine Berechtigungen
			if(!$user->rechte['invasionen'] AND !$user->rechte['fremdinvakolos']) {
				return array();
			}
			
			// Berechtigungen eingeschränkt
			if(!$user->rechte['invasionen']) {
				$conds[] = "(invasionenFremd = 1 OR invasionenTyp = 5)";
			}
			if(!$user->rechte['fremdinvakolos']) {
				$conds[] = "(invasionenFremd = 0 OR invasionenTyp != 5)";
			}
			if($user->protectedAllies) {
				$conds[] = "(player_allianzenID IS NULL OR player_allianzenID NOT IN(".implode(", ", $user->protectedAllies)."))";
			}
			
		}
		
		// beendete Invasionen herausfiltern
		$conds[] = "(invasionenEnde > ".time()." OR invasionenEnde = 0)";
		
		
		$invasionen = array();
		
		// Invasionen abfragen
		$query = query("
			SELECT
				invasionenID,
				invasionen_planetenID,
				invasionen_systemeID,
				invasionenTyp,
				invasionen_playerID,
				invasionenAggressor,
				invasionenEnde,
				invasionenOpen,
				invasionenAbbrecher,
				invasionenFreundlich,
				invasionenKommentar,
				
				systeme_galaxienID,
				systemeX,
				systemeZ,
				systemeGateEntf,
				
				p1.playerName,
				p1.player_allianzenID,
				p2.playerName AS a_playerName,
				p2.player_allianzenID AS a_player_allianzenID,
				
				a1.allianzenTag,
				a2.allianzenTag AS a_allianzenTag
			FROM
				".PREFIX."invasionen
				LEFT JOIN ".PREFIX."systeme
					ON systemeID = invasionen_systemeID
				LEFT JOIN ".GLOBPREFIX."player p1
					ON p1.playerID = invasionen_playerID
				LEFT JOIN ".GLOBPREFIX."player p2
					ON p2.playerID = invasionenAggressor
				LEFT JOIN ".GLOBPREFIX."allianzen a1
					ON a1.allianzenID = p1.player_allianzenID
				LEFT JOIN ".GLOBPREFIX."allianzen a2
					ON a2.allianzenID = p2.player_allianzenID
			WHERE
				".implode(' AND ', $conds)."
			ORDER BY
				NULL
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			
			// Datensatz ergänzen
			$row['invasionenTypName'] = isset(self::$invanamen[$row['invasionenTyp']]) ? self::$invanamen[$row['invasionenTyp']] : '';
			
			// ans Array hängen
			if(!isset($invasionen[$row['invasionen_planetenID']])) {
				$invasionen[$row['invasionen_planetenID']] = array();
			}
			
			$invasionen[$row['invasionen_planetenID']][] = $row;
		}
		
		
		return $invasionen;
	}
	
	
	/**
	 * Invasionen für die Suchfunktion abfragen (nur ID und Typ)
	 * @param array $conds
	 * @param bool $filter_rechte Filter durch Berechtigungs-Einschränkungen ergänzen
	 * @return array
	 * 		Invasionen-Datensätze
	 * 		Schlüssel = Planeten-ID
	 */
	public static function getForSearch() {
		
		global $user;
		
		// keine Berechtigungen
		if(!$user->rechte['invasionen'] AND !$user->rechte['fremdinvakolos']) {
			return array();
		}
		
		$conds = array();
		
		// Berechtigungen eingeschränkt
		if(!$user->rechte['invasionen']) {
			$conds[] = "(invasionenFremd = 1 OR invasionenTyp = 5)";
		}
		if(!$user->rechte['fremdinvakolos']) {
			$conds[] = "(invasionenFremd = 0 OR invasionenTyp != 5)";
		}
		if($user->protectedAllies) {
			$conds[] = "(player_allianzenID IS NULL OR player_allianzenID NOT IN(".implode(", ", $user->protectedAllies)."))";
		}
		
		// beendete Invasionen herausfiltern
		$conds[] = "(invasionenEnde > ".time()." OR invasionenEnde = 0)";
		
		
		$invasionen = array();
		
		// Invasionen abfragen
		$query = query("
			SELECT
				invasionen_planetenID,
				invasionenTyp
			FROM
				".PREFIX."invasionen
			WHERE
				".implode(' AND ', $conds)."
			ORDER BY
				NULL
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		while($row = mysql_fetch_assoc($query)) {
			
			// ans Array hängen
			if(!isset($invasionen[$row['invasionen_planetenID']])) {
				$invasionen[$row['invasionen_planetenID']] = array();
			}
			
			$invasionen[$row['invasionen_planetenID']][] = array(
				'invasionenTyp'=>$row['invasionenTyp'],
				'invasionenTypName' => (isset(self::$invanamen_short[$row['invasionenTyp']]) ? self::$invanamen_short[$row['invasionenTyp']] : '')
			);
		}
		
		return $invasionen;
	}
	
}

?>