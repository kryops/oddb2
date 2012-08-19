<?php

/**
 * pages/login/loginscreen.php
 * Loginbereich (Inhalt)
 */

// Sicherheitsabfrage
if(!defined('ODDB')) die('unerlaubter Zugriff!');



// FoW abfangen
if(isset($_GET['p']) AND $_GET['p'] == 'fow') {
	diefow();
}

// Loginerror formatieren und Logindaten löschen
if($user->loginerror) {
	$user->loginerror .= '<br /><br />';
	
	setcookie('oddb', '', time()-3600);
	@session_destroy();
	$_COOKIE = array();
	$_SESSION = array();
}

// Login-Template
$tmpl = new template_login;
$tmpl->content = '
<div class="hl1">Login</div>
	
	<div class="icontent" id="contentlogin">';
if($dbs AND count($dbs) > 1) {
	$tmpl->content .= '
		<br />
		';
	$tmpl->content .= htmlspecialchars($dbs[INSTANCE], ENT_COMPAT, 'UTF-8');
	$tmpl->content .= '
		<br />
		<a href="index.php" class="small"><br />(andere Datenbank ausw&auml;hlen)</a>
		<br />';
}
$tmpl->content .= '
		<noscript>
			<br />
			<span class="error">Die Datenbank funktioniert nur mit aktiviertem JavaScript!</span>
			<br />
		</noscript>
		<br />
		<span class="error" id="loginerror">'.$user->loginerror.'</span>
		<form action="#" name="loginform" onsubmit="return login()">
		<table class="leftright" style="margin:auto;margin-bottom:5px">
		<tr>
			<td>Username</td>
			<td><input type="text" class="text" name="username" autofocus /></td>
		</tr>
		<tr>
			<td>Passwort</td>
			<td><input type="password" class="text" name="pw" /></td>
		</tr>
		</table>
		<input type="checkbox" name="autologin" /> <span class="togglecheckbox" data-name="autologin">eingeloggt bleiben</span>
		<br /><br />
		<input type="submit" class="button" style="width:100px" value="Login" />
		</form>
		<br /><br />
		<a href="javascript:void(0)" onclick="$(\'#contentlogin\').slideUp(400);$(\'#contentregister\').slideDown(400);$(\'.hl1\').html(\'Registrieren\')">registrieren</a> 
		&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
		<a href="javascript:void(0)" onclick="$(\'#contentlogin\').slideUp(400);$(\'#contentpw\').slideDown(400);$(\'.hl1\').html(\'Passwort vergessen\')">Passwort vergessen</a>
		<div style="height:5px"></div>
	</div>
	
	<div class="icontent" id="contentregister" style="display:none">
		<br />
		<span class="error" id="registererror"></span>
		<form action="#" name="registerform" onsubmit="return register()">
		<table class="leftright" style="margin:auto;margin-bottom:5px">
		<tr>
			<td>OD-UserID</td>
			<td><input type="text" class="text tooltip" name="id" data-tooltip="Klicke in OD oben rechts auf deinen Namen. Die Zahl, die dann in der Adressleiste ganz rechts steht, ist deine OD-UserID" /></td>
		</tr>
		<tr>
			<td>E-Mail-Adresse</td>
			<td><input type="text" class="text" name="email" /></td>
		</tr>
		<tr>
			<td>gew&uuml;nschtes Passwort</td>
			<td><input type="password" class="text" name="pw" /></td>
		</tr>
		<tr>
			<td><i>wiederholen</i></td>
			<td><input type="password" class="text" name="pw2" /></td>
		</tr>
		</table>
		<br />
		<input type="submit" class="button" style="width:100px" value="registrieren" />
		</form>
		<br /><br />
		<a href="javascript:void(0)" onclick="$(\'#contentlogin\').slideDown(400);$(\'#contentregister\').slideUp(400);$(\'.hl1\').html(\'Login\')">zur&uuml;ck</a>
		<div style="height:5px"></div>
	</div>
	
	<div class="icontent" id="contentpw" style="display:none">
		<br />
		<form action="#" name="pwform" onsubmit="return sendpw()">
		<div style="line-height:16pt;margin-bottom:15px">Mit dieser Funktion kannst du dir ein neues Passwort<br />generieren lassen, wenn du dein altes vergessen hast.</div>
		<table class="leftright" style="margin:auto;margin-bottom:5px">
		<tr>
			<td>Username</td>
			<td><input type="text" class="text" name="username" /></td>
		</tr>
		<tr>
			<td>E-Mail-Adresse</td>
			<td><input type="text" class="text" name="email" /></td>
		</tr>
		</table>
		<br />
		<input type="submit" class="button" value="neues Passwort anfordern" />
		</form>
		<br />
		<div id="pwajax" class="center"></div>
		<br />
		<a href="javascript:void(0)" onclick="$(\'#contentlogin\').slideDown(400);$(\'#contentpw\').slideUp(400);$(\'.hl1\').html(\'Login\')">zur&uuml;ck</a>
		<div style="height:5px"></div>
	</div>';
$tmpl->script = '
if(!("autofocus" in document.createElement("input"))) {
document.loginform.username.focus();
}';
$tmpl->output();



?>