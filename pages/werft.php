<?php
/**
 * pages/werft.php
 * eigene Werften
 * Verbündete Werften
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');

// default-Unterseite definieren
if(!isset($_GET['sp'])) {
	if(($user->rechte['werft_ally'] OR $user->rechte['werft_meta']) AND $user->allianz) {
		$_GET['sp'] = 'ally';
	}
	else {
		$_GET['sp'] = 'own';
	}
}

// Template initialisieren
$tmpl = new template;
$tmpl->name = 'Werften';

// vorhandene Seiten (key = $_GET['sp'])
$pages = array(
	'own'=>true,
	'add'=>true,
	'edit'=>true,
	'edit_send'=>true,
	'edit_all'=>true,
	'edit_all_ally'=>true,
	'del'=>true,
	
	'ally'=>true
);


// Ress-Labels
$ress = array(
	'<img src="img/layout/leer.gif" class="ress ress_tooltip erz" />',
	'<img src="img/layout/leer.gif" class="ress ress_tooltip metall" />',
	'<img src="img/layout/leer.gif" class="ress ress_tooltip wolfram" />',
	'<img src="img/layout/leer.gif" class="ress ress_tooltip kristall" />',
	'<img src="img/layout/leer.gif" class="ress ress_tooltip fluor" />'
);






// 404-Error
if(!isset($pages[$_GET['sp']])) {
	$tmpl->error = 'Die Seite existiert nicht!';
	$tmpl->output();
}

/**
 * AJAX-Funktionen
 */

// Werft hinzufügen
else if($_GET['sp'] == 'add') {
	include './pages/werft/actions.php';
}

// Bedarf ändern
else if($_GET['sp'] == 'edit') {
	include './pages/werft/actions.php';
}

// Bedarf ändern -> absenden
else if($_GET['sp'] == 'edit_send') {
	include './pages/werft/actions.php';
}

// Bedarf aller eigener Werften ändern
else if($_GET['sp'] == 'edit_all') {
	include './pages/werft/actions.php';
}

// Bedarf markierter verbündeter Werften ändern
else if($_GET['sp'] == 'edit_all_ally') {
	include './pages/werft/actions.php';
}

// Werft entfernen
else if($_GET['sp'] == 'del') {
	include './pages/werft/actions.php';
}

/**
 * normale Seiten
 */
else {
	// Contentswitch erzeugen
	$csw = new contentswitch;
	// aktive Seite definieren
	$csw->active = $_GET['sp'];
	
	
	
	// Verbündete Werften
	if(($user->rechte['werft_ally'] OR $user->rechte['werft_meta']) AND $user->allianz) {
		$csw->data['ally'] = array(
			'link'=>'index.php?p=werft&sp=ally',
			'bg'=>'background-image:url(img/layout/csw_werft.png);background-position:-150px 0px',
			'reload'=>'true',
			'width'=>650,
			'content'=>''
		);
		
		// Inhalt für verbündete Werften
		if($_GET['sp'] == 'ally') {
			include './pages/werft/ally.php';
		}
	}
	
	// eigene Werften
	$csw->data['own'] = array(
		'link'=>'index.php?p=werft&sp=own',
		'bg'=>'background-image:url(img/layout/csw_werft.png)',
		'reload'=>'false',
		'width'=>650,
		'content'=>''
	);
	
	// Inhalt für eigene Werften
	if($_GET['sp'] == 'own') {
		include './pages/werft/own.php';
	}
	
	
	
	
	
	
	// nur Unterseite ausgeben
	if(isset($_GET['switch'])) {
		if(isset($csw->data[$_GET['sp']])) {
			$tmpl->content = $csw->data[$_GET['sp']]['content'];
		}
		else {
			$tmpl->error = 'Du hast keine Berechtigung!';
		}
	}
	// keine Berechtigung
	else if(!isset($csw->data[$_GET['sp']])) {
		$tmpl->error = 'Du hast keine Berechtigung!';
	}
	// Contentswitch ausgeben
	else {
		$tmpl->content = $csw->output();
	}
	$tmpl->output();
	
}
?>