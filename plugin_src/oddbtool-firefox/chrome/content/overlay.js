/**
 * ODDB Tool by Kryops
 * http://www.kryops.de/
 */

var oddbtool = {
	/**
	 * Version
	 */
	version: '2.2a',
	
	
	jqueryload: false,
	jQuery: false,
	
	betaserver: false,
	
	/**
	 * Startfunktion
	 */
	onLoad: function() {
		// Einstellungen laden
		oddbtool.getPrefs();
		
		// Seite laden -> Autoparser
		oddbtool.tabbrowser = window.getBrowser();
		oddbtool.tabbrowser.addEventListener('DOMContentLoaded', oddbtool.loadPage, true);
	},
	
	/**
	 * Gebäude-Positionen auf dem Sprite
	 */
	geb: {
		0:0,
		'-1':16,
		'-2':32,
		'-3':48,
		'-4':64,
		1000:80,
		1001:96,
		1002:112,
		1003:128,
		1004:144,
		1005:160,
		1006:176,
		1007:192,
		1008:208,
		1009:224,
		1010:240,
		1011:256,
		1012:272,
		1013:288,
		1014:304,
		1015:320,
		1016:336,
		1017:352,
		1018:368,
		1019:384,
		1020:400,
		1021:416,
		1022:432,
		1023:448,
		1024:464,
		1025:480,
		1026:496,
		1027:512,
		1028:528,
		1029:544,
		1030:560,
		1031:576,
		1032:592,
		1033:608,
		1034:624,
		1035:640,
		1036:656,
		1037:672,
		1038:688,
		1039:704,
		1040:720,
		1041:736,
		1042:752,
		1043:992,
		1044:784,
		1045:800,
		1046:816,
		1047:832,
		1048:848,
		1049:864,
		1050:880,
		1051:944,
		1052:896,
		1053:912,
		1054:928,
		1055:976,
		1056:960
	},
	
	// Rassen-Suchmuster und Klassennamen
	rassen: {
		'Mensch':'mensch',
		'Roc':'tiroc',
		'Trado':'trado',
		'Bera':'bera',
		'Myri':'myri',
		'Lux':'lux',
		'Revisker':'revisker'
	},
	
	// Sonderzeichen im FoW ersetzen
	charmap_search: ['&amp;#92', '&amp;#47', '&amp;', '', '', '', '\\\'', '', '', '', ''],
	charmap_replace: ['\\', '/', '&', '&dagger;', '&bull;', '´', '\'', '˜', '”', '™', '€'],
	
	/**
	 * Einstellungen laden
	 */
	getPrefs: function() {
		var prefManager = Components.classes["@mozilla.org/preferences-service;1"].getService(Components.interfaces.nsIPrefBranch);
		oddbtool.prefs = {
			url: prefManager.getCharPref('extensions.oddbtool.url'),
			fow: prefManager.getBoolPref('extensions.oddbtool.fow'),
			auto_poview: prefManager.getBoolPref('extensions.oddbtool.auto_poview'),
			auto_planet: prefManager.getBoolPref('extensions.oddbtool.auto_planet'),
			auto_system: prefManager.getBoolPref('extensions.oddbtool.auto_system'),
			auto_orbit: prefManager.getBoolPref('extensions.oddbtool.auto_orbit'),
			auto_floview: prefManager.getBoolPref('extensions.oddbtool.auto_floview'),
			auto_sitter: prefManager.getBoolPref('extensions.oddbtool.auto_sitter'),
			auto_einst: prefManager.getBoolPref('extensions.oddbtool.auto_einst'),
			auto_toxx: prefManager.getBoolPref('extensions.oddbtool.auto_toxx'),
		}
	},
	
	/**
	 * Einstellungen aus dem Menü heraus öffnen
	 */
	preferences_open: function() {
		try {
			return window.openDialog(
				"chrome://oddbtool/content/options.xul", 
				"Einstellungen",
				"top=200,left=200"
			);
		}catch(e){}
	},
	
	/**
	 * = PHP-Funktion str_replace()
	 * @param search mixed suchen
	 * @param replace mixed ersetzen
	 * @param subject string, in dem ersetzt werden soll
	 * @param count int optional max. Anzahl der Ersetzungen
	 * @return string mit Ersetzungen
	 */
	str_replace: function(search, replace, subject, count) {
		// Replaces all occurrences of search in haystack with replace  
		// 
		// version: 1006.1915
		// discuss at: http://phpjs.org/functions/str_replace
		// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +   improved by: Gabriel Paderni
		// +   improved by: Philip Peterson
		// +   improved by: Simon Willison (http://simonwillison.net)
		// +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
		// +   bugfixed by: Anton Ongson
		// +      input by: Onno Marsman
		// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +    tweaked by: Onno Marsman
		// +      input by: Brett Zamir (http://brett-zamir.me)
		// +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +   input by: Oleg Eremeev
		// +   improved by: Brett Zamir (http://brett-zamir.me)
		// +   bugfixed by: Oleg Eremeev
		// %          note 1: The count parameter must be passed as a string in order    // %          note 1:  to find a global variable in which the result will be given
		// *     example 1: str_replace(' ', '.', 'Kevin van Zonneveld');
		// *     returns 1: 'Kevin.van.Zonneveld'
		// *     example 2: str_replace(['{name}', 'l'], ['hello', 'm'], '{name}, lars');
		// *     returns 2: 'hemmo, mars'
		var i = 0, j = 0, temp = '', repl = '', sl = 0, fl = 0,
		f = [].concat(search),
		r = [].concat(replace),
		s = subject,
		ra = r instanceof Array, sa = s instanceof Array;
		s = [].concat(s);
		
		if (count) {
			this.window[count] = 0;
		}
		for (i=0, sl=s.length; i < sl; i++) {
			if (s[i] === '') {
				continue;
			}
			for (j=0, fl=f.length; j < fl; j++) {
				temp = s[i]+'';
				repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
				s[i] = (temp).split(f[j]).join(repl);
				if (count && s[i] !== temp) {
					this.window[count] += (temp.length-s[i].length)/f[j].length;
				}
			}
		}
		return sa ? s : s[0];
	},
	
	in_array: function(item,arr) {
		for(p=0;p<arr.length;p++) if (item == arr[p]) return true;
		return false;
	},
	
	
	/**
	 * Zahl mit Tausender-Trennern formatieren
	 * @param number int Zahl
	 * @return string formatierte Zahl
	 */
	ressmenge: function(number) {
		number = '' + number;
		
		number = number.replace(/,/g, '');
		if (number.length > 3) {
			var mod = number.length % 3;
			var output = (mod > 0 ? (number.substring(0,mod)) : '');
			for (i=0 ; i < Math.floor(number.length / 3); i++) {
				if ((mod == 0) && (i == 0)) {
					output += number.substring(mod+ 3 * i, mod + 3 * i + 3);
				}
				else {
					output+= '.' + number.substring(mod + 3 * i, mod + 3 * i + 3);
				}
			}
			return (output);
		}
		else return number;
	},
	
	/**
	 * ist die aktuelle Seite eine OD-Seite?
	 * @param url String
	 * @return bool
	 */
	isODPage: function(url) {
		var url = url.toString();
		if(url.indexOf('omega-day.com/game/') != -1 || url.indexOf('omega-day.de/game/') != -1 || url.indexOf('omegaday.de/game/') != -1) {
			// Betaserver ausschließen
			if(!oddbtool.betaserver && (url.indexOf('beta.o') != -1 || url.indexOf('pre.o') != -1 || url.indexOf('beta2.o') != -1)) {
				return false;
			}
			// www-old ausschließen
			if(url.indexOf('www-old.o') != -1) {
				return false;
			}
			return true;
		}
		return false;
	},
	
	
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
		floview: /\?op=fleet$/,
		toxx: /\?op=orbit&index=\d+&bioatack=1$/
	},
	
	/**
	 * ist die aktuelle Seite eine Seite, die geparst werden kann?
	 * @param url String
	 * @return bool
	 */
	isParsePage: function(url) {
		
		// geeignete Seite
		for(var i in oddbtool.parserRegex) {
			if(oddbtool.parserRegex[i].exec(url) != null) {
				return true;
			}
		}
		
		// ungeeignete Seite
		return false;
	},
	
	/**
	 * ist die aktuelle Seite eine Toxx-Seite?
	 * @param url String
	 * @return bool
	 */
	isToxxPage: function(url) {
		
		// Toxx-Seite
		if(oddbtool.parserRegex.toxx.exec(url) != null) {
			return true;
		}
		
		// andere Seite
		return false;
	},
	
	/**
	 * Seite wird geladen
	 * -> FoW-Ausgleich und Autoparser starten
	 */
	loadPage: function(e) {
		var page = e.target;
		var url = page.location;
		
		// handelt es sich um eine OD-Seite?
		if(!oddbtool.isODPage(url)) {
			return false;
		}
		
		// FoW, Parser und jQuery laden
		if(!oddbtool.scriptsloaded) {
			var subscriptLoader = Components.classes["@mozilla.org/moz/jssubscript-loader;1"]
						  .getService(Components.interfaces.mozIJSSubScriptLoader);
			
			subscriptLoader.loadSubScript("chrome://oddbtool/content/fow.js");
			
			subscriptLoader.loadSubScript("chrome://oddbtool/content/parser.js");
			
			subscriptLoader.loadSubScript("chrome://oddbtool/content/jquery1.7.1.js");
			
			jQuery.noConflict();
			
			$ = function(selector,context){ return new jQuery.fn.init(selector,context||gBrowser.contentDocument); };
			$.fn = $.prototype = jQuery.fn;
			
			oddbtool.jQuery = jQuery;
			
			jQuery.ajaxSetup({
			  cache: false,
			  dataType: 'xml'
			});
			
			// Logo klickbar machen
			$(document).on('click', '#oddbtoollogo', function() {
				oddbtool.preferences_open();
			});
			
			// Parsen-Link
			$(document).on('click', '#oddbtoolparselink', function() {
				oddbtool.parser(false, false, true);
			});
			
			// Menüs
			$(document).on('mousedown', 'div.oddbtoolpfeil', function(){
				var id = $(this).attr('id');
				id = id.replace(/oddbtoolpfeil/g, '');
				$('div.oddbtoolmenu').hide();
				$('#oddbtoolmenu'+id).show();
			});
			$(document).on('mouseleave', 'div.oddbtoolmenu', function(){
				$(this).hide();
			});
			
			oddbtool.scriptsloaded = true;
		}
		// handelt es sich um eine Seite, die geparst werden kann?
		if(!oddbtool.isParsePage(url)) {
			return false;
		}
		
		
		// Einstellungen neu laden
		oddbtool.getPrefs();
		
		// CSS einbinden
		var css = ['#oddbtoolwin {position:absolute; left:240px; top:5px; width:290px; font-family:Arial,Sans; font-size:9px; color:white; z-index:2; background-color:rgba(0,0,0,0.5)}',
		'#oddbtoolwin a {font-family:Arial,Sans; font-size:9px}',
		'#oddbtoollogo {position:absolute; top:1px; left:200px; width:32px; height:32px; background-image:url('+oddbtool.prefs.url+'img/layout/fowsprite32.png); cursor:pointer; z-index:2}',
		
		'#oddbtoolheadline{position:absolute; top:85px; left:450px; width:650px; text-align:center;}',
		
		'#oddbtoolfowtbl{position:absolute; top:525px; left:300px; width:700px; background-color:rgba(255,255,255,0.15); padding:10px; -moz-border-radius:12px; border-radius:12px; font-family:Arial,Sans; font-size:12px; color:white; z-index:1;}',
		'#oddbtoolfowtbl table{width:100%}',
		'#oddbtoolfowtbl th, #oddbtoolfowtbl td, #oddbtoolfowtbl a {font-size:9pt;padding:5px}',
		'#oddbtoolfowtbl th{background-color:rgba(255,255,255,0.1); text-align:left; font-weight:bold;}',
		'#oddbtoolfowtbl td:first-child{font-weight:bold; padding-left:8px;}',
		'#oddbtooliframe {display:none}',
		
		'.oddbtoolplanet {position:absolute; width:100px; background-position:3px -3px; background-repeat:no-repeat; font-size:11px}',
		'.oddbtoolorbit {position:relative;background-color:rgba(0,0,0,0.5);top:-72px;left:0px;text-align:center;font-size:11px !important}',
		'.oddbtoolorbitoverride {position:relative}',
		'.oddbtoolorbitoverride:hover {text-decoration:none !important}',
		'.oddbtoolplanet a {font-size:11px}',
		'.oddbtoolplanet img {border:none;}',
		'.oddbtoolplanet td {width:16px; height:14px; background-image:url('+oddbtool.prefs.url+'img/gebaeude/gebaeude_small.png);}',
		'.oddbtooladd3 {float:right; margin-right:10px; margin-top:20px; font-size:11px}',
		'.oddbtoollink:hover, .oddbtoolorbit a:hover {text-decoration:none !important;}',
		
		'.oddbtoolpfeil {position:absolute; width:15px; height:14px; background-image:url('+oddbtool.prefs.url+'img/layout/fowsprite32.png); background-position:-32px 0px; z-index:3; cursor:pointer}',
		'.oddbtoolmenu {position:absolute; width:140px; background-color:rgba(30,30,30,0.9); padding:5px; -moz-border-radius:6px; font-family:Arial,Sans; z-index:2;}',
		'.oddbtoolmenu a {display:block; color:white; font-size:11px; padding:4px;}',
		'.oddbtoolmenu a:hover {background-color:#282828; text-decoration:none;}',
		'.oddbtoolrasse {margin:8px 0px 0px -30px; float:right; background-image:url('+oddbtool.prefs.url+'img/layout/fowsprite32.png); width:30px; height:27px; background-repeat:no-repeat;}',
		'.oddbtoolr {background-position:30px 30px;}',
		'.oddbtoolrbera {background-position:-47px 0px;}',
		'.oddbtoolrlux {background-position:-77px 0px;}',
		'.oddbtoolrmensch {background-position:-107px 0px;}',
		'.oddbtoolrmyri {background-position:-137px 0px;}',
		'.oddbtoolrtrado {background-position:-197px 0px;}',
		'.oddbtoolrrevisker {background-position:-227px 0px;}',
		'.oddbtoolrtiroc {background-position:-167px 0px;}',
		'#oddbtoolkommentar {position:absolute; top:350px; left:400px; background-color:rgba(30,30,30,0.9); padding:8px; font-size:11px; -moz-border-radius:8px; border-radius:8px; text-align:right; display:none; z-index:4;}',
		'#oddbtoolkommentar iframe {width:160px; height:85px; border:none;}',
		'#oddbtoolkommentar a {font-size:13px; font-weight:bold; cursor:pointer;}'
		].join('');
		
		
		$('head',page).append('<style type="text/css">'+css+'</style>');
		
		// Logo und log-div erzeugen
		$('body',page).append('<div id="oddbtoollogo" class="oddbtoolsprite" title="Einstellungen f&uuml;r das ODDB Tool &auml;ndern"></div><div id="oddbtoolwin"></div>');
		
		// Planet getoxxt
		if(oddbtool.isToxxPage(url)) {
			oddbtool.toxxen(page);
			
			// Parsen verhindern
			return false;
		}
		
		var data = false;
		
		// FoW-Ausgleich in Systemen
		var regex_sys = /\?op=system&sys=(\d+)/;
		if(oddbtool.prefs.fow && regex_sys.exec(url) != null) {
			// Seite parsen
			var data2 = oddbtool.parsePage(page);
			
			// Daten für Autoparser kopieren
			data = oddbtool.jQuery.extend(true, {}, data2);
			
			oddbtool.fow(page, data2);
		}
		
		// Autoparser
		for(var i in oddbtool.parserRegex) {
		
			if(oddbtool.parserRegex[i].exec(url) != null) {
				
				if(typeof(oddbtool.prefs["auto_"+i]) != 'undefined' && oddbtool.prefs["auto_"+i]) {
					window.setTimeout(
						function() {
							oddbtool.parser(page, data);
						},
						200
					);
				}
				else {
					$('#oddbtoolwin',page).append('<a href="javascript:void(0)" id="oddbtoolparselink">[Seite parsen]</a>');
				}
				
				
				return true;
			}
		}
	},
	
	/** 
	 * Planet getoxxt
	 * @param page Seite
	 */
	toxxen: function(page) {
		var url = page.location;
		p = /op=orbit&index=(\d+)&bioatack=1/;
		var data = p.exec(url);
		if(data != null) {
			var input = oddbtool.getQuelltext(page);
			if(input.indexOf(' Wohnungen sind unbewohnbar geworden! ') != -1) {
				var id = data[1];
				// getoxxte Summe ermitteln
				p = />([\d\.]+) Wohnungen sind unbewohnbar/;
				data = p.exec(input);
				if(data != null) {
					var getoxxt = data[1];
					getoxxt = getoxxt.replace(/\./g, '');
				}
				else {
					$('#oddbtoolwin',page).append('<span style="color:red">Konnte Getoxxt-Summe nicht ermitteln!</span><br>');
					return false;
				}
				
				// abschicken
				var addr = oddbtool.prefs.url+'index.php?p=ajax_general&sp=toxx&id='+id+'&bev='+getoxxt+'&plugin&ajax';
				jQuery.get(addr);
				
				$('#oddbtoolwin',page).append('Planet als getoxxt markiert<br>');
			}
		}
	}
};

/**
 * Event-Listener
 */
// Firefox starten

window.addEventListener("load", oddbtool.onLoad, false);