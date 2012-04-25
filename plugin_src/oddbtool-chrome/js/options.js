/*
 * Gespeicherte Einstellungen ins Formular übernehmen
 */
$(document).ready(function() {
	
	var prefs = {
		"url": "http://oddb.kryops.de/",
		"fow": true,
		"auto": true,
		"settings": false
	};
	
	if(localStorage['oddbtool']) {
		prefs = JSON.parse(localStorage['oddbtool']);
	}
	
	$('#url').val(prefs.url);
	
	if(prefs.fow) {
		$('#fow').prop('checked', true);
	}
	if(prefs.auto) {
		$('#auto').prop('checked', true);
	}
	if(prefs.settings) {
		$('#settings').prop('checked', true);
	}
	
	/*
	 * Einstellungen speichern
	 */
	$('form').submit(function(e) {
		
		var newprefs = {};
		
		newprefs['url'] = $('#url').val();
		newprefs['fow'] = Boolean($('#fow:checked').length);
		newprefs['auto'] = Boolean($('#auto:checked').length);
		newprefs['settings'] = Boolean($('#settings:checked').length);
		
		localStorage['oddbtool'] = JSON.stringify(newprefs);
		
		
		e.preventDefault();
		
		window.close();
		
		return false;
	})
});