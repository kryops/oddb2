<?php

/*
 * Patch Version 2.2 - 2.2.1 (Patch-Nummer 2)
 */


if($config['patchversion'] == 1) {
	
	/*
	 * Spieler-Einträge mit leerem Benutzernamen löschen
	 * Gedacht als Platzhalter für nichtexistente Spieler-IDs, damit sie nicht jeden Tag neu
	 * abgefragt werden, wurden aber auch bei Fehlschlagen des odrequests gesetzt
	 * (z.B. OD zu langsam oder nicht erreichbar)
	 */
	
	query("
		DELETE FROM
			".GLOBPREFIX."player
		WHERE
			playerName = ''
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	
	$config['patchversion'] = 2;
	
}

?>