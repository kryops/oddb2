/**
 * filtert die verschiedenen DB-Instanzen nach einem Suchfeld
 * @param val string Such-String
 */
function instfilter(val) {
	val = val.replace(/\\/g, '\\\\');
	val = val.replace(/\(/g, '\\(');
	val = val.replace(/\)/g, '\\)');
	val = val.replace(/\[/g, '\\[');
	val = val.replace(/\]/g, '\\]');
	var p = new RegExp(val, 'i');
	
	$('#instances a').each(function() {
		if($(this).html().search(p) != -1) {
			$(this).css('display', 'block');
		}
		else {
			$(this).css('display', 'none');
		}
	});
}


/**
 * einloggen
 * @return false normalen Submit verhindern
 */
function login() {
	// Validierung
	var f = document.loginform;
	if(f.username.value == '') {
		$('#loginerror').html('Kein Username eingegeben!<br /><br />');
		f.username.focus();
	}
	else if(f.pw.value == '') {
		$('#loginerror').html('Kein Passwort eingegeben!<br /><br />');
		f.pw.focus();
	}
	// AJAX-Request
	else {
		// Loginerror ausblenden
		$('#loginerror').html('');
		
		// Autologin 0 oder 1
		var alogin = 0;
		if(f.autologin.checked) alogin = 1;
		
		// Formular-Buttons deaktivieren
		$('input[type=button], input[type=submit]', f).attr('disabled', 'disabled');
		
		$.ajax({
			type: 'post',
			url: 'index.php?p=login&sp=login&inst='+instance+'&ajax',
			data:  {
				'username' : f.username.value,
				'pw' : f.pw.value,
				'autologin' : alogin
			},
			success: function(data){
				// Fehlermeldung ausgeben
				var error = $(data).find('error').text();
				if(error != '') {
					$('#loginerror').html(error+'<br /><br />');
					
					// Formular-Buttons reaktivieren
					$('input[type=button], input[type=submit]', f).removeAttr('disabled');
				}
				// alles ok -> weiterleiten
				else {
					url('index.php');
				}
			},
			error: function(e, msg) {
				// Fehlermeldung ausgeben
				$('#loginerror').html('Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'<br /><br />');
				
				// Formular-Buttons reaktivieren
				$('input[type=button], input[type=submit]', f).removeAttr('disabled');
			}
		});
	}
	
	// normalen Submit verhindern
	return false;
}

/**
 * registrieren
 * @return false normalen Submit verhindern
 */
function register() {
	// Validierung
	var f = document.registerform;
	if(f.id.value == '') {
		$('#registererror').html('Keine OD-UserID eingegeben!<br /><br />');
		f.id.focus();
	}
	else if(f.id.value.replace(/\d/g, '') != '') {
		$('#registererror').html('Die OD-UserID darf nur aus Zahlen bestehen!<br /><br />');
		f.id.focus();
	}
	else if(f.email.value == '' || f.email.value.indexOf('@') == -1 || f.email.value.indexOf('.') == -1) {
		$('#registererror').html('Keine oder ungültige E-Mail-Adresse eingegeben!<br /><br />');
		f.email.focus();
	}
	else if(f.pw.value == '') {
		$('#registererror').html('Kein Passwort eingegeben!<br /><br />');
		f.pw.focus();
	}
	else if(f.pw.value != f.pw2.value) {
		$('#registererror').html('Die beiden Passwörter sind unterschiedlich!<br /><br />');
		f.pw2.focus();
	}
	// AJAX-Request
	else {
		// Formular-Buttons deaktivieren
		$('input[type=button], input[type=submit]', f).attr('disabled', 'disabled');
		
		$.ajax({
			type: 'post',
			url: 'index.php?p=login&sp=register&inst='+instance+'&ajax',
			data:  {
				'id' : f.id.value,
				'email' : f.email.value,
				'pw' : f.pw.value,
				'pw2' : f.pw2.value
			},
			success: function(data){
				// Fehlermeldung ausgeben
				var error = $(data).find('error').text();
				if(error != '') {
					$('#registererror').html(error+'<br /><br />');
					
					// Formular-Buttons reaktivieren
					$('input[type=button], input[type=submit]', f).removeAttr('disabled');
				}
				// alles ok -> weiterleiten
				else {
					url('index.php');
				}
			},
			error: function(e, msg) {
				// Fehlermeldung ausgeben
				$('#loginerror').html('Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'<br /><br />');
				
				// Formular-Buttons reaktivieren
				$('input[type=button], input[type=submit]', f).removeAttr('disabled');
			}
		});
	}
	
	// normalen Submit verhindern
	return false;
}

/**
 * neues Passwort anfordern
 * @return false normalen Submit verhindern
 */
function sendpw() {
	// Validierung
	var f = document.pwform;
	if(f.username.value == '') {
		$('#pwajax').html('<span class="error">Kein Username eingegeben!</span>');
		f.username.focus();
	}
	else if(f.email.value == '' || f.email.value.indexOf('@') == -1 || f.email.value.indexOf('.') == -1) {
		$('#pwajax').html('<span class="error">Keine oder ungültige E-Mail-Adresse eingegeben!</span>');
		f.email.focus();
	}
	// AJAX-Request
	else {
		form_send(f, 'index.php?p=login&sp=sendpw&inst='+instance+'&ajax', $('#pwajax'));
	}
	
	// normalen Submit verhindern
	return false;
}