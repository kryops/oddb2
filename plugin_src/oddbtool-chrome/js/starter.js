/*
 * Einstellungen auslesen und Seitenlade-Event aufrufen
 */
 
chrome.extension.sendRequest({type: "get"}, function(response) {
	
	oddbtool.prefs = response.prefs;
	
	oddbtool.loadPage(document);
});
