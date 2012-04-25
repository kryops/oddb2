/*
 * Request-Listener
 */
 
chrome.extension.onRequest.addListener(function(request, sender, sendResponse) {
	
	// Default-Einstellungen
	var prefs = {
		"url": "http://oddb.kryops.de/",
		"fow": true,
		"auto": true,
		"settings": false
	};
	
	// schon gespeichert
	if(localStorage['oddbtool']) {
		prefs = JSON.parse(localStorage['oddbtool']);
	}
	
	sendResponse({"prefs": prefs});
});
