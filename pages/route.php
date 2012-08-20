<?php
/**
 * pages/route.php
 * Routen und Listen
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) $_GET['sp'] = '';

// Routen-Klasse ladem
General::loadClass('route');

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Routen';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	''=>true,
	'view'=>true,
	
	'setmarker'=>true,
	'reset'=>true,
	'compute'=>true,
	
	'create'=>true,
	'create_send'=>true,
	
	'edit'=>true,
	'edit_send'=>true,
	
	'addoptions'=>true,
	'add'=>true,
	
	'remove'=>true,
	
	'karte'=>true,
	
	'export'=>true
);


/**
 * Umgebungsvariablen
 */

// Routenbezeichnungen (nach routenListe)
$rnames = array(
	0=>'Route',
	1=>'Liste',
	2=>'Toxxroute'
);

// Routentypen
$rtypes = array(
	1=>'privat',
	2=>'Allianz',
	3=>'Meta',
	4=>'alle'
);


// Grundberechtigung
if(!$user->rechte['routen']) {
	$tmpl->error = 'Du hast keine Berechtigung!';
	$tmpl->output();
	die();
}



/**
 * Funktionen
 */

// Route löschen
if(isset($_GET['del'])) {
	$route = new route;
	if($route->load($_GET['del']) AND $route->rechte_edit()) {
		query("
			DELETE FROM
				".PREFIX."routen
			WHERE
				routenID = ".(int)$_GET['del']."
		") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
		
		// Log-Eintrag
		if($config['logging'] >= 2) {
			insertlog(26, 'löscht die Route '.$route->info['routenName'].' ('.(int)$_GET['del'].')');
		}
	}
}


// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

// Route erstellen
else if($_GET['sp'] == 'create_send') {
	include './pages/route/create.php';
}

// Route bearbeiten
else if($_GET['sp'] == 'edit_send') {
	include './pages/route/edit.php';
}

// Marker setzen
else if($_GET['sp'] == 'setmarker') {
	// Vollständigkeit der Daten
	if(isset($_GET['id'], $_GET['marker'])) {
		// Route laden
		$route = new route;
		if($route->load($_GET['id'])) {
			// Marker setzen und speichern
			if($route->setmarker($_GET['marker'])) {
				$route->save();
				
				// Log-Eintrag
				if($config['logging'] >= 2) {
					insertlog(26, 'setzt den Marker für die Route '.$route->info['routenName'].' ('.(int)$_GET['id'].') auf '.(int)$_GET['marker']);
				}
			}
		}
	}
	
	$tmpl->output();
}

// Route berechnen
else if($_GET['sp'] == 'compute') {
	// Daten unvollständig
	if(!isset($_GET['id'], $_POST['start'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		$_GET['id'] = (int)$_GET['id'];
		$_POST['start'] = (int)$_POST['start'];
		
		// Route laden
		$route = new route;
		if($route->load($_GET['id']) AND $route->rechte_edit()) {
			// enthält keine Planeten
			if(!$route->count) {
				$tmpl->error = 'Die Route enthält noch keine Planeten!';
			}
			// berechnen
			else if(($error = $route->compute($_POST['start'])) === true) {
				$route->save();
				
				// Log-Eintrag
				if($config['logging'] >= 2) {
					insertlog(26, 'berechnet die Route '.$route->info['routenName'].' ('.(int)$_GET['id'].') vom Planet '.$_POST['start'].' aus');
				}
				
				// Ansicht aktualisieren
				$tmpl->script = 'ajaxcall("index.php?p=route&sp=view&id='.$_GET['id'].'&update", false, false, false);';
			}
			// Fehler beim Berechnen
			else {
				$tmpl->error = $error;
			}
		}
		// Fehler beim Laden
		else {
			$tmpl->error = 'Laden der Route fehlgeschlagen!';
		}
	}
	
	if($tmpl->error != '') {
		$tmpl->error = '<br />'.$tmpl->error;
	}
	
	$tmpl->output();
}

// in den Bearbeitungsmodus zurücksetzen
else if($_GET['sp'] == 'reset') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		$_GET['id'] = (int)$_GET['id'];
		
		$route = new route;
		if($route->load($_GET['id']) AND $route->finished AND $route->rechte_edit()) {
			$route->reset();
			$route->save();
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(26, 'setzt die Route '.$route->info['routenName'].' ('.(int)$_GET['id'].') in den Bearbeitungsmodus zurück');
			}
		}
		
		$tmpl->script = 'ajaxcall("index.php?p=route&sp=view&id='.$_GET['id'].'&update", false, false, false);';
	}
	
	$tmpl->output();
}

// Planeten hinzufügen / entfernen
else if($_GET['sp'] == 'add') {
	include './pages/route/add.php';
}

// Planet(en) aus Route entfernen
else if($_GET['sp'] == 'remove') {
	// Daten unvollständig
	if(!isset($_GET['id'])) {
		$tmpl->error = 'Daten unvollständig!';
	}
	else {
		$_GET['id'] = (int)$_GET['id'];
		
		$route = new route;
		if($route->load($_GET['id']) AND !$route->finished AND $route->rechte_edit()) {
			// Formular -> mehrere Planeten
			if(count($_POST)) {
				foreach($_POST as $key=>$val) {
					$route->remove($key);
				}
			}
			// einzelner Planet
			if(isset($_GET['remove'])) {
				$route->remove($_GET['remove']);
			}
			
			$route->save();
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(26, 'entfernt Planeten aus der Route '.$route->info['routenName'].' ('.$_GET['id'].')');
			}
		}
		
		$tmpl->script = 'ajaxcall("index.php?p=route&sp=view&id='.$_GET['id'].'&update", false, false, false);';
	}
	
	$tmpl->output();
}

// Systeme auf der Karte hervorheben
else if($_GET['sp'] == 'karte') {
	$error = true;
	$gala = (int)$_GET['gala'];
	
	// Galaxie ungültig
	if(!$gala) {
		$error = 'Ungültige Galaxie eingegeben!';
	}
	// Galaxie gesperrt
	else if($user->protectedGalas AND in_array($gala, $user->protectedGalas)) {
		$error = 'Du hast keinen Zugriff auf diese Galaxie!';
	}
	// Vollständigkeit der Daten
	else if(isset($_GET['id'])) {
		// Route laden
		$route = new route;
		if(($error = $route->load($_GET['id'])) === true) {
			$ids = array();
			
			if($route->count) {
				$query = query("
					SELECT
						planeten_systemeID
					FROM
						".PREFIX."planeten
						LEFT JOIN ".PREFIX."systeme
							ON systemeID = planeten_systemeID
					WHERE
						planetenID IN(".implode(", ", array_keys($route->data)).")
						AND systeme_galaxienID = ".$gala."
					GROUP BY
						planeten_systemeID
				") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());
				
				while($row = mysql_fetch_assoc($query)) {
					$ids[] = $row['planeten_systemeID'];
				}
			}
			
			$t = time();
			
			$tmpl->name = ($route->liste ? 'Liste' : 'Route').' '.htmlspecialchars($route->info['routenName'], ENT_COMPAT, 'UTF-8').' - Karte';
			$tmpl->content = '
<div class="rkarte'.$t.'" style="min-width:620px;height:640px;margin:auto">
</div>';
			$tmpl->script = 'ajaxcall("index.php?p=karte&gala='.$gala.'", $(".rkarte'.$t.'"), {highlight : "'.implode('-', $ids).'"}, true);';	
			
			// Log-Eintrag
			if($config['logging'] >= 2) {
				insertlog(26, 'lässt sich die Route '.$route->info['routenName'].' ('.(int)$_GET['id'].') auf der Karte anzeigen');
			}
		}
	}
	else {
		$error = 'Daten unvollständig!';
	}
	
	// Fehler
	if($error !== true) {
		$tmpl->error = $error;
	}
	
	// Ausgabe
	$tmpl->output();
}

/**
 * Seite
 */

// Route/Liste erstellen
else if($_GET['sp'] == 'create') {
	include './pages/route/create.php';
}

// Route/Liste bearbeiten
else if($_GET['sp'] == 'edit') {
	include './pages/route/edit.php';
}

// Planeten hinzufügen / entfernen
else if($_GET['sp'] == 'addoptions') {
	include './pages/route/add.php';
}

// Planeten hinzufügen / entfernen
else if($_GET['sp'] == 'export') {
	include './pages/route/export.php';
}

// Route/Liste anzeigen
else if($_GET['sp'] == 'view') {
	include './pages/route/view.php';
}

// Übersicht
else {
	include './pages/route/oview.php';
}

?>