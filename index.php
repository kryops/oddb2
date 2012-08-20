<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
*                                                                               *
*                               ODDB V2 by Kryops                               *
*                     Copyright (c) 2011-2012 Michael Strobel                   *
*                                                                               *
* Permission is hereby granted, free of charge, to any person obtaining a copy  *
* of this software and associated documentation files (the "Software"), to deal *
* in the Software without restriction, including without limitation the rights  *
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell     *
* copies of the Software, and to permit persons to whom the Software is         *
* furnished to do so, subject to the following conditions:                      *
*                                                                               *
* The above copyright notice and this permission notice shall be included in    *
* all copies or substantial portions of the Software.                           *
*                                                                               *
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR    *
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,      *
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE   *
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER        *
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, *
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE *
* SOFTWARE.                                                                     *
*                                                                               *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * index.php - Hauptdatei
 * bindet alle nötigen Dateien ein
 * Session starten
 * MySQL- und evtl memcached-Verbindung
 * Instanzierung
 * Logout
 * Login überprüfen
 * Userdaten, Einstellungen und Rechte laden und ggf cachen
 * Online-Zeitpunkt updaten
 * Flooding-Schutz
 * gewünschte Seite einbinden
 */

// alle Fehlermeldungen aktivieren
error_reporting(E_ALL);

// Rendertime-Messung starten
$time_start = microtime(true);

// User-Abort deaktivieren, um eventuelle Fehler zu verhinden
ignore_user_abort(true);

// Query-Zähler initialisieren
$queries = 0;

// Sicherheitskonstante
define('ODDB', true);
define('ODDBADMIN', false);

// Session starten
@session_start();

// Zeitzone setzen -> Performance
date_default_timezone_set('Europe/Berlin');

// HTTP-Cache-Header ausgeben (Caching der Seite verhindern)
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);

// magic quotes abfangen
if(get_magic_quotes_gpc()) {
	function strsl(&$item, $key) {
		$item = stripslashes($item);
	}
	array_walk_recursive($_GET, 'strsl');
	array_walk_recursive($_POST, 'strsl');
}


// Übersichtsseite als Standard definieren
if(!isset($_GET['p'])) $_GET['p'] = 'oview';

// Logout
if($_GET['p'] == 'logout') {
	setcookie('oddb', '', time()-3600);
	@session_destroy();
	$_COOKIE = array();
	$_SESSION = array();
}


// Umgebungsdaten und globale Funktionen einbinden
include './common.php';


// globale Einstellungen einbinden
if(@include('./config/global.php')) {
	
	// Konstanten anlegen
	define('ADDR', $config['addr']);			// Adresse der DB (mit abschließendem /)
	define('SERVER', $config['server']);		// Server der DB (ohne http://, ohne abschließendes /)
	define('KEY', $config['key']);				// globaler Sicherheitsschlüssel (Cronjobs)
	define('IMPRESSUM', $config['impressum']);
	define('ADCODE', $config['adcode']); 		// optionaler Werbecode
	define('GLOBPREFIX', $config['mysql_globprefix']);
	
	// Datenbanken einbinden
	include './config/dbs.php';
	
}
// nicht installiert
else {
	define('INSTALLED', false);
	header('Location: install/');
	die();
}

$gconfig = $config;


// Instanz-Konstante definieren
// es gibt nur eine DB
if(!$dbs) {
	define('INSTANCE', 1);
}
else if(count($dbs == 1)) {
	define('INSTANCE', array_shift(array_keys($dbs)));
}
// Instanz in der Session gespeichert
else if(isset($_SESSION['inst']) AND isset($dbs[$_SESSION['inst']])) {
	define('INSTANCE', $_SESSION['inst']);
}
// Instanz in der Adresse übergeben
else if(isset($_GET['inst']) AND isset($dbs[$_GET['inst']])) {
	define('INSTANCE', $_GET['inst']);
}
// Instanz im Cookie gespeichert
else if(isset($_COOKIE['oddb'])) {
	$cdata = explode('+', $_COOKIE['oddb']);
	if(isset($cdata[2]) AND isset($dbs[$cdata[2]])) {
		define('INSTANCE', $cdata[2]);
	}
	// invalides Cookie -> keine Instanz
	else define('INSTANCE', 0);
}
// keine Instanz ausgewählt
else define('INSTANCE', 0);

// keine Instanz ausgewählt -> Login-Seite einbinden
if(!INSTANCE) {
	include './pages/login.php';
}

// Einstellungen der Instanz einbinden
(@include('./config/config'.INSTANCE.'.php')) OR die('Konfigurationsdatei nicht gefunden!');

// Ist die DB deaktiviert?
if(!$config['active']) {
	include './pages/login.php';
}

// bei öffentlich zugänglichen Seiten auch die login.php einbinden
if($_GET['p'] == 'impressum') {
	include './pages/login.php';
}

// MySQL und Caching initialisieren
$mysql_conn = new mysql;

// MySQL-Präfix als Konstante definieren
$config['mysql_prefix'] = $config['mysql_globprefix'].INSTANCE.'_';
define('PREFIX', $config['mysql_prefix']);

// Cache-Klasse initialisieren
$cache = new cache();
$ucache = true;


// Patches installieren
General::patchApplication();


// Login überprüfen
$user = new user;

// aktive Session vorhanden?
if(isset($_SESSION['oddbuid'], $_SESSION['ip'])) {
	// Daten sichern
	$_SESSION['oddbuid'] = (int)$_SESSION['oddbuid'];
	
	// Änderung der IP überprüfen
	if($_SERVER['REMOTE_ADDR'] != $_SESSION['ip']) {
		$user->loginerror = 'Deine IP hat sich ge&auml;ndert!<br />Tritt das &ouml;fter auf, benutze die Autologin-Funktion.';
	}
	// Existenz des Users überprüfen und Daten, Einstellungen und Rechte abfragen
	else {
		// Userdaten aus dem Cache laden
		if($data = $cache->get('user'.$_SESSION['oddbuid'])) {
			$user->login = true;
			$user->id = $_SESSION['oddbuid'];
			$ucache = false;
		}
		// MySQL benutzen
		else {
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
					registerProtectedAllies,
					registerProtectedGalas,
					registerAllyRechte
				FROM
					".PREFIX."user
					LEFT JOIN ".PREFIX."register
						ON register_allianzenID = user_allianzenID
				WHERE
					user_playerID = ".$_SESSION['oddbuid']."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			// User existiert nicht
			if(!mysql_num_rows($query)) {
				$user->loginerror = 'Dein Account wurde gel&ouml;scht!';
			}
			// User existiert
			else {
				$data = mysql_fetch_assoc($query);
				
				$user->login = true;
				$user->id = $_SESSION['oddbuid'];
			}
		}
	}
}
// Cookie vorhanden -> Session erzeugen
if(!$user->login AND isset($_COOKIE['oddb'])) {
	// Cookie parsen und validieren
	$cdata = explode('+', $_COOKIE['oddb']);
	$cdata[0] = (int)$cdata[0];
	$cdata[1] = escape($cdata[1]);
	
	// veraltete IP-Bans löschen, wenn kein Cache benutzt wird
	if(!CACHING) {
		query("
			DELETE FROM ".GLOBPREFIX."ban
			WHERE
				banTime < ".(time()-$config['ipban_time']*60)."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	}
	
	// IP-Ban überprüfen
	$ipban = ban_get();
	
	// Letzte Aktualisierung überprüfen
	$query = query("
		SELECT
			playerUpdate
		FROM
			".PREFIX."user
			LEFT JOIN ".GLOBPREFIX."player
				ON playerID = user_playerID
		WHERE
			user_playerID = ".$cdata[0]."
	") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
	
	// User existiert nicht
	if(!mysql_num_rows($query)) {
		$user->loginerror = 'Dein Account wurde gel&ouml;scht!';
	}
	// IP gebannt
	else if($config['ipban'] AND $ipban > $config['ipban']) {
		$user->loginerror = 'Deine IP ist aufgrund vieler Fehlversuche gesperrt. Bitte versuche es sp&auml;ter wieder!';
	}
	// User existiert
	else {
		$data = mysql_fetch_assoc($query);
		
		// OD-Request aufrufen
		if($data['playerUpdate'] < time()-1800) {
			odrequest($cdata[0], true);
			user_checkban($cdata[0]);
		}
		
		// User-Daten abfragen
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
				registerProtectedAllies,
				registerProtectedGalas,
				registerAllyRechte
			FROM
				".PREFIX."user
				LEFT JOIN ".PREFIX."register
					ON register_allianzenID = user_allianzenID
			WHERE
				user_playerID = ".$cdata[0]."
				AND userPassword = '".$cdata[1]."'
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// falsches Passwort
		if(!mysql_num_rows($query)) {
			$user->loginerror = 'Deine Userdaten haben sich ge&auml;ndert!';
			
			// Ban eintragen / erhöhen
			ban_add($ipban);
			
			// Cookie löschen
			setcookie('oddb', '', time()-3600);
		}
		// alles OK
		else {
			$data = mysql_fetch_assoc($query);
			
			// Bans der IP löschen
			ban_del();
			
			// ID in die Userklasse laden
			$user->login = true;
			$user->id = $cdata[0];
			
			// UID in die Session laden
			$_SESSION['oddbuid'] = $cdata[0];
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			
			// Logeintrag
			if($config['logging'] >= 2 AND time()-$data['userOnlineDB'] > 60) {
				insertlog(2, 'loggt sich ein (Autologin)');
			}
		}
	}
}

// Flooding default auf false setzen
$flooding = false;

// eingeloggt
// - Userdaten der Klasse zuweisen und ggf cachen
// - Online-Zeit updaten
// - Flooding-Schutz
if($user->login) {
	// Userdaten verarbeiten
	$user->name = $data['user_playerName'];
	$user->allianz = $data['user_allianzenID'];
	
	/* userBanned
	   1 - manuell gebannt
	   2 - automatisch gebannt (Allywechsel, keine Registriererlaubnis mehr)
	   3 - noch nicht freigeschaltet
	*/
	$user->banned = $data['userBanned'];
	$user->settings = unserialize($data['userSettings']);
	
	// Berechtigungen
	$r = getrechte(
		$data['userRechtelevel'],
		$data['registerProtectedAllies'],
		$data['registerProtectedGalas'],
		$data['registerAllyRechte'],
		$data['userRechte']
	);
	
	$user->rechte = $r[1];
	$user->protectedAllies = $r[2];
	$user->protectedGalas = $r[3];
	
	// Online-Zeit updaten, wenn nicht gebannt
	if(!$user->banned) {
		$uonline = false;
		// Zugriff über das Plugin, letzter vor über 2min
		if($_GET['p'] == 'fow' OR isset($_GET['plugin'])) {
			if(time()-120 > $data['userOnlinePlugin']) {
				$col = 'userOnlinePlugin';
				$uonline = true;
			}
		}
		// Zugriff über die DB, letzter vor über 2min
		else if(time()-120 > $data['userOnlineDB']) {
			$col = 'userOnlineDB';
			$uonline = true;
		}
		if($uonline) {
			query("
				UPDATE ".PREFIX."user
				SET
					".$col." = ".time()."
				WHERE
					user_playerID = ".$user->id."
			") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
			
			$data[$col] = time();
		}
		
		// Userdaten cachen (1min)
		if($ucache OR $uonline) {
			$cache->set('user'.$user->id, $data, 60);
		}
		
		// Flooding-Schutz
		if($config['flooding']) {
			// Cache benutzen
			if(CACHING) {
				// bisherige Aufrufe fetchen
				if($data = $cache->get('flooding'.$user->id)) {
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
					$cache->set('flooding'.$user->id, $data, $config['flooding_time']);
					
					// wenn es zu viele sind, sperren
					if($p > $config['flooding_pages']) {
						$flooding = true;
					}
				}
				// neuen Eintrag
				else {
					$data = array(time());
					$cache->set('flooding'.$user->id, $data, $config['flooding_time']);
				}
			}
			// MySQL benutzen
			else {
				// Seitenaufrufe auslesen
				$query = query("
					SELECT COUNT(*) FROM ".GLOBPREFIX."flooding
					WHERE
						flooding_playerID = ".$user->id."
						AND floodingTime > ".(time()-$config['flooding_time'])."
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				$queries++;
				
				$data = mysql_fetch_array($query);
				
				// wenn es zu viele sind, sperren
				if($data[0] > $config['flooding_pages']) {
					$flooding = true;
				}
				
				// nur neuen Seitenaufruf hinzufügen, wenn kein starkes Flooding
				if($data[0] < 2*$config['flooding_pages']) {
					query("
						INSERT INTO ".GLOBPREFIX."flooding
						SET
							flooding_playerID = ".$user->id.",
							floodingTime = ".time()."
					") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				}
			}
		}
	}
	// Userdaten auch cachen wenn gebannt (1min)
	else if($ucache) {
		$cache->set('user'.$user->id, $data, 60);
	}
}


//
// welche Seite will der User aufrufen?
//

// nicht eingeloggt, gebannt, noch nicht freigeschaltet, Flooding-Schutz
if(!$user->login OR $user->banned OR $flooding) {
	include './pages/login.php';
}
// eingeloggt
else {
	// Seite vorhanden
	if(isset($pages[$_GET['p']])) {
		include './pages/'.$_GET['p'].'.php';
	}
	// Seite nicht vorhanden -> 404
	else {
		$tmpl = new template;
		$tmpl->error = 'Die Seite existiert nicht!';
		$tmpl->output();
	}
}

?>