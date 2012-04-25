<?php
/**
 * pages/admin/register.php
 * Verwaltung -> Registrierung (Inhalt)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



$content =& $csw->data['register']['content'];

$content = '
<div class="hl2">Registrierung</div>
<div class="icontent">
	Hier kannst du einstellen, welche Allianzen und Spieler sich in der Datenbank anmelden d&uuml;rfen.
	<br />
	Au&szlig;erdem kannst du f&uuml;r jede Allianz deren Berechtigungen und die Sichtbarkeit von Galaxien und anderen Allianzen einschr&auml;nken.
	<br /><br />
	Beachte aber, dass du dich dadurch auch selbst aus dieser Sektion aussperren kannst, wenn du dir oder deiner Allianz die Registrierungserlaubnis entziehst oder die Berechtigung &quot;<i>alle Spieler sowie Registrierungserlaubnis für Spieler und Allianzen verwalten</i>&quot; f&uuml;r deine Allianz sperrst!
</div>

<br />

<div class="hl2">Allianzen mit Registrierungserlaubnis</div>
<div class="icontent">
	<div class="fcbox center" style="width:500px">
		<form action="#" name="register_allianz" onsubmit="return form_send(this, \'index.php?p=admin&amp;sp=register_addally&amp;ajax\', $(this).siblings(\'.ajax\'))">
		Die Allianz mit der ID 
		&nbsp;<input type="text" class="smalltext" name="id" />&nbsp; 
		<input type="submit" class="button" style="width:80px" value="hinzuf&uuml;gen" />
		</form>
		<div class="ajax center"></div>
	</div>
	
	<br />
	
	<table class="data registerallianzen" style="margin:auto">
	<tr>
		<th>ID</th>
		<th>Tag</th>
		<th>Name</th>
		<th>Einschr&auml;nkungen</th>
		<th>Optionen</th>
	</tr>';

// Allianzen abfragen
$query = query("
	SELECT
		allianzenID,
		allianzenTag,
		allianzenName,
		
		registerAllyRechte,
		registerProtectedAllies,
		registerProtectedGalas
	FROM
		".GLOBPREFIX."allianzen
		LEFT JOIN ".PREFIX."register
			ON register_allianzenID = allianzenID
	WHERE
		register_allianzenID IS NOT NULL
	ORDER BY
		allianzenID ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// keine Allianzen mit Registriererlaubnis
if(!mysql_num_rows($query)) {
	$content .= '
	<tr>
		<td colspan="5" style="font-style:italic" class="keine">keine</td>
	</tr>';
}

while($row = mysql_fetch_assoc($query)) {
	$content .= allianzrow($row);
}

$content .= '
	</table>
</div>

<br />

<div class="hl2">Spieler mit Registrierungserlaubnis</div>
<div class="icontent">
	<div class="fcbox center" style="width:500px">
		<form name="register_player" onsubmit="return form_send(this, \'index.php?p=admin&amp;sp=register_addplayer&amp;ajax\', $(this).siblings(\'.ajax\'))">
		Den Spieler mit der ID 
		&nbsp;<input type="text" class="smalltext" name="id" />&nbsp; 
		<input type="submit" class="button" style="width:80px" value="hinzuf&uuml;gen" />
		</form>
		<div class="ajax center"></div>
	</div>
	
	<br />
	
	<table class="data registerplayer" style="margin:auto">
	<tr>
		<th>ID</th>
		<th>Name</th>
		<th>Allianz</th>
		<th>angemeldet</th>
		<th>Optionen</th>
	</tr>';

// Spieler abfragen
$query = query("
	SELECT
		playerID,
		playerName,
		player_allianzenID,
		
		allianzenTag,
		allianzenName,
		
		user_playerID
	FROM
		".GLOBPREFIX."player
		LEFT JOIN ".GLOBPREFIX."allianzen
			ON allianzenID = player_allianzenID
		LEFT JOIN ".PREFIX."user
			ON user_playerID = playerID
		LEFT JOIN ".PREFIX."register
			ON register_playerID = playerID
	WHERE
		register_playerID IS NOT NULL
	ORDER BY
		playerName ASC
") OR die("Fehler in ".__FILE__." Zeile ".__LINE__.": ".mysql_error());

// keine Spieler mit Registriererlaubnis
if(!mysql_num_rows($query)) {
	$content .= '
	<tr>
		<td colspan="5" style="font-style:italic" class="keine">keine</td>
	</tr>';
}

while($row = mysql_fetch_assoc($query)) {
	$content .= userrow($row);
}

$content .= '
	</table>
	
</div>';



?>