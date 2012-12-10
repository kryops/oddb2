/*
 * Container für Hintergrund-Funktionen
 */
var oddbtoolbackground = {
	/**
	 * Adress-Muster, deren Seiten geparst werden können
	 */
	parserRegex: {
		system: /\?op=system&sys=\d+(|&galx=\d+)$/,
		poview: /\?op=planlist$/,
		planet: /\?op=planet&index=\d+/,
		orbit: /\?op=orbit&index=\d+$/,
		einst: /\?op=settings$/,
		sitter: /\?op=sitter$/,
		floview: /\?op=fleet&tab=5$/,
		floviewbbs: /\?op=fleet&tab=2$/,
		toxx: /\?op=orbit&index=\d+&bioatack=1$/
	},
	
	/**
	 * ist die aktuelle Seite eine Seite, die geparst werden kann?
	 * @param url String
	 * @return bool
	 */
	isParsePage: function(url) {
		
		// geeignete Seite
		for(var i in oddbtoolbackground.parserRegex) {
			if(oddbtoolbackground.parserRegex[i].exec(url) != null) {
				return true;
			}
		}
		
		// ungeeignete Seite
		return false;
	}
}


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


/*
 * UMTS-Header modifizieren
 */

chrome.webRequest.onBeforeSendHeaders.addListener(
	function(details) {
		
		if(!oddbtoolbackground.isParsePage(details.url)) {
			return;
		}
		
		details.requestHeaders.push({name: 'Cache-Control', value: 'deny-all, no-cache'});
		details.requestHeaders.push({name: 'Pragma', value: 'deny-all, no-cache'});
		
		return {
			requestHeaders : details.requestHeaders
		};
	},
	{
		urls : [
				"*://www.omega-day.com/game/*",
				"*://omega-day.com/game/*",
				"*://www.omega-day.de/game/*",
				"*://www3.omega-day.de/game/*",
				"*://omega-day.de/game/*",
				"*://www.omegaday.de/game/*",
				"*://omegaday.de/game/*"
			]
	},
	["blocking", "requestHeaders"]
);