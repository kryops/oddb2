/*
 * Request-Listener
 */
 
chrome.extension.onRequest.addListener(function(request, sender, sendResponse) {
	
	// Default-Einstellungen
	var prefs = {
		"url": "http://oddb.kryops.de/",
		"fow": true,
		"auto_poview": true,
		"auto_planet": true,
		"auto_system": true,
		"auto_orbit": true,
		"auto_floview": true,
		"auto_sitter": false,
		"auto_einst": false,
		"auto_toxx": true
	};
	
	// schon gespeichert
	if(localStorage['oddbtool']) {
		prefs = JSON.parse(localStorage['oddbtool']);
	}
	
	sendResponse({"prefs": prefs});
});
