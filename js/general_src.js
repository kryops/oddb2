/**
 * default-Settings
 */
var settings = {
	// szgrtype int was soll die Schnellzugriffsleiste machen?
	// 1 - Content-Tab
	// 2 - neuer DB-Tab
	// 3 - neuer Browser-Tab
	// 5 - Fenster
	'szgrtype' : 5,
	// wminoncontent bool alle Fenster sollen minimiert werden, wenn Content geladen/umgeschaltet wird
	'wminoncontent': false,
	// newtabswitch bool bei neuem Tab auf diesen umschalten
	'newtabswitch' : false,
	// winlinknew bool Fensterlinks in Fenstern in einem neuen Fenster öffnen (alternativ neuer Tab)
	'winlinknew' : false,
	// winlink2tab bool sollen Fenster-Links wie Tab-Links behandeln (bis auf die winlinknew-Einstellung)
	'winlink2tab' : false,
	// closeontransfer bool Fenster oder Tab schließen, wenn Inhalt in jeweils anderes Medium transferiert wird
	'closeontransfer' : true,
	// effects int Dauer der Animation beim Contentswitch in ms (0 für aus)
	'effects' : 200
};

// Gebäude-Positionen
var geb = {
	0:0,
	'-1':16,
	'-2':32,
	'-3':48,
	'-5':64,
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
	1056:960,
	1057:768,
	
	1200:992,
	1201:1008,
	1202:1024,
	1203:1040,
	1205:1056,
	1206:1072,
	1207:1088,
	1208:1104,
	1210:1120,
	1211:1136,
	1216:1152,
	1217:1168,
	1218:1184,
	1220:1200,
	1221:1216,
	1223:1232,
	1224:1248,
	1225:1264,
	1230:1280,
	1234:1296,
	
	1500:1312,
	1501:1328,
	1502:1344,
	
	'-4':1360
};

/**
 * Touch-Support
 */
var touchSupport = ("ontouchstart" in window);

/**
 * Kurzform der Weiterleitung
 */
function url(a) {
	document.location.href = a;
}

/**
 * HTML5 History-API
 */
var historyapi = !!(window.history && window.history.pushState);

/**
 * Startfunktionen / Event-Handler
 *
 * Kontextmenüs (Logo, Menü, Links, Tabs, Fenster)
 * Fenster bewegen
 * Tooltip bewegen
 * Logo, Menü klickbar machen
 * bei Größenänderung des Browserfensters tabbar und windowbar aktualisieren
 * Links, Tabs und Fenster klickbar machen
 */
$(document).ready(function(){
	// Größe des Startmenüs aktualisieren
	sm_updatesize();
	
	var $document = $(document);
	
	$document.bind('click', function(e){
		// Dokument-Klick
		// Kontextmenü schließen
		cm_close();
		// Startmenü schließen, wenn kein Rechtsklick auf Starmenü selbst
		if(startmenu && (e.button != 2 || $(e.target).parents('#startmenuc').attr('id') == null)) {
			sm_hide();
		}
	}).bind('mousemove touchmove', function(e){
		// Mausbewegung
		// Fenster bewegen?
		if(fmove) {
			if(e.type == 'touchmove' && e.originalEvent.targetTouches) {
				var x = fmoveobj[0]+e.originalEvent.targetTouches[0].pageX-fmovepos[0];
				var y = fmoveobj[1]+e.originalEvent.targetTouches[0].pageY-fmovepos[1];
			}
			else {
				var x = fmoveobj[0]+e.pageX-document.documentElement.scrollLeft-fmovepos[0];
				var y = fmoveobj[1]+e.pageY-document.documentElement.scrollTop-fmovepos[1];
			}
			
			// nach unten
			if(y > $(window).height()-90) {
				y = $(window).height()-90;
			}
			// nicht zu weit schieben
			// nach oben
			else if(y < 0) {
				y = 0;
			}
			
			// nach rechts
			if(x > $(window).width()-40) {
				x = $(window).width()-40;
			}
			// nach links
			else if(x < -1*$('#fenster'+fmove).width()+40) {
				x = -1*$('#fenster'+fmove).width()+40;
			}
			
			
			$('#fenster'+fmove).css({'left' : x+'px', 'top' : y+'px'});
		}
		// Tooltip bewegen?
		if(tooltip) {
			tt_pos(e);
		}
	}).bind('mouseup touchend', function(e){
		// Fensterbewegung stoppen
		fmove = 0;
		
		if(e.type == 'touchend' && !contextmenuTriggered && !$(e.target).parents('#contextmenu').length) {
			cm_close();
		}
	});
	
	// Browserfenstergröße ändern
	$(window).resize(function(){
		// windowbar aktualisieren
		wbar_update();
		// Größe des Startmenüs aktualisieren
		sm_updatesize();
		// Fenster scrollen
		$('.fenster').css({'maxHeight' : ($(window).height()-95)+'px'});
		$('.fenster .fcc').css({'maxHeight' : ($(window).height()-145)+'px'});
		// Fenster in den sichtbaren Bereich ziehen falls sie außerhalb sind
		jQuery.each(fenster, function() {
			var pos = $('#fenster'+this[0]).offset();
			pos.top -= $(window).scrollTop();
			pos.left -= $(window).scrollLeft();
			if(pos.top > $(window).height()-115) {
				$('#fenster'+this[0]).css('top', ($(window).height()-115)+'px');
			}
			if(pos.left > $(window).width()-50) {
				$('#fenster'+this[0]).css('left', ($(window).width()-50)+'px');
			}
			if(pos.top < 0) {
				$('#fenster'+this[0]).css('top', '0px');
			}
		});
		// Breite der Inputs in der Schnellzugriffsleiste anpassen
		//szgr_updatewidth();
	});
	
	// Logo
	$('#logo').click(function(e){
		// bei Klick Übersichtsseite in content-Tab laden
		// Rechtsklick weg
		if(e.which == 3) return true;
		// Strg und mittlere Maustaste abfangen -> neuer Tab
		var type = 1;
		if(e.which == 2 || e.ctrlKey) type = 2;
		page_load(type, 'Übersicht', 'index.php?p=oview', false, false);
	}).mouseup(function(e){
		// mittlere Maustaste abfangen
		if(e.which == 2) page_load(2, 'Übersicht', 'index.php?p=oview', false, false);
	}).bind('contextmenu touchcontextmenu',function(e){
		// Kontext-Menü
		cm_open(e, 1, 'index.php?p=oview');
		
		return false;
	});
	
	// Menü
	$('#menu > a').click(function(e){
		// Seite bei Klick in content-Tab laden
		// Rechtsklick und mittlere Maustaste weg
		if(e.which == 2 || e.which == 3) return true;
		// Strg und mittlere Maustaste abfangen -> neuer Tab
		var type = 1;
		if(e.ctrlKey) type = 2;
		
		// abbrechen, wenn das Kontextmenü aufgerufen wurde
		if(contextmenuTriggered) {
			e.preventDefault();
			return false;
		}
		
		page_load(type, false, $(this).data('link'), false, false);
	}).mousedown(function(e){
		// mittlere Maustaste abfangen
		if(e.which == 2) {
			page_load(2, false, $(this).data('link'), false, false);
			return false;
		}
	}).bind('contextmenu touchcontextmenu',function(e){
		// Kontext-Menü
		// ohne passendes Link-Attribut kein Kontextmenü
		if(typeof($(this).data('link')) == 'undefined') return false;
		
		// Kontextmenü öffnen
		cm_open(e, 1, $(this).data('link'));
		
		return false;
	});
	
	// Links klickbar machen
	$document.on('click', '.link', function(e) {
		// Rechtsklick abfangen
		if(e.which == 3) return true;
		
		// abbrechen, wenn das Kontextmenü aufgerufen wurde
		if(contextmenuTriggered) {
			e.preventDefault();
			return false;
		}
		
		// Validation, bei falschem Link abbrechen
		if(typeof($(this).data('link')) == 'undefined' || $(this).data('link') == '') {
			// alte Syntax
			if($(this).attr('link') != null && $(this).attr('link') != '') {
				$(this).data('link', $(this).attr('link'));
			}
			else {
				return false;
			}
		}
		
		// Link ist in Fenster
		if($(this).parents('.fenster').attr('id') != null) {
			// Fenster-ID ermitteln
			var id = $(this).parents('.fenster').attr('id');
			// Strg und mittlere Maustaste abfangen -> neues Fenster
			var type = 4;
			if(e.which == 2 || e.ctrlKey || (settings['winlinknew'] && $(this).hasClass('winlink'))) type = 5;
			id = id.replace(/[^\d]/g, '');
			page_load(type, false, $(this).data('link'), id, false);
		}
		// Link ist in Tab
		else {
			// Fenster-Link -> neues Fenster öffnen
			if($(this).hasClass('winlink')) {
				var type = 5;
				// in Tab-Link umwandeln
				if(settings['winlink2tab']) {
					var type = 1;
					if(e.which == 2 || e.ctrlKey || settings['winlinknew']) type = 2;
				}
				page_load(type, false, $(this).data('link'), false, false);
			}
			else {
				// Strg und mittlere Maustaste abfangen -> neuer Tab
				var type = 1;
				if(e.which == 2 || e.ctrlKey) type = 2;
				page_load(type, false, $(this).data('link'), acttab, false);
			}
		}
	}).on('mouseup', '.link', function(e){
		// mittlere Maustaste abfangen
		if(e.which == 2) {
			var id = $(this).parents('.fenster').attr('id');
			if(id != null) page_load(5, false, $(this).data('link'), false, false);
			else page_load(2, false, $(this).data('link'), false, false);
		}
	});
	
	// Contentswitch klickbar machen
	$document.on('click', '.cswlink', function(e) {
		// Rechtsklick abfangen
		if(e.which == 3) return true;
		
		// abbrechen, wenn das Kontextmenü aufgerufen wurde
		if(contextmenuTriggered) {
			e.preventDefault();
			return false;
		}
		
		// Strg abfangen -> neuer Tab
		if(e.which == 2 || e.ctrlKey) page_load(2, false, $(this).data('link'), false, false);
		// normal switchen
		else {
			var reload = false;
			if($(this).data('reload')) {
				reload = true;
			}
			contentswitch(this, $(this).data('pos'), reload);
		}
	}).on('mouseup', '.cswlink', function(e){
		// mittlere Maustaste abfangen
		if(e.which == 2) page_load(2, false, $(this).data('link'), false, false);
	});
	
	// Kontextmenü initialisieren
	$document.on('contextmenu touchcontextmenu', '.contextmenu, .cswlink', function(e){
		// ohne passendes Link-Attribut kein Kontextmenü
		if(typeof($(this).data('link')) == 'undefined') {
			if($(this).attr('link') != null) {
				$(this).data('link', $(this).attr('link'));
			}
			else return false;
		}
		
		var addr = $(this).data('link');
		// Kontextmenü öffnen
		cm_open(e, 1, addr);
		
		return false;
	});
	
	// Tooltips initialisieren
	$document.on('mouseover touchmousedown', '.tooltip', function(e) {
		// Planeten-Screen erzeugen
		if($(this).hasClass('plscreen') && typeof($(this).data('tooltip')) == 'undefined') {
			var data = $(this).data('plscreen').split('_');
			var gpl = data[1].split('+');
			var gor = data[2].split('+');
			var gspec = data[3].split('+');
			
			// mit leeren Werten füllen
			for(var i=1;i<=36;i++) {
				if(typeof(gpl[i]) == 'undefined') {
					gpl[i] = 0;
				}
			}
			for(var i=1;i<=12;i++) {
				if(typeof(gor[i]) == 'undefined') {
					gor[i] = 0;
				}
			}
			for(var i=1;i<=10;i++) {
				if(typeof(gspec[i]) == 'undefined') {
					gspec[i] = 0;
				}
			}
			
			// Tooltip erzeugen
			var tt = '<div class="plscreentt"><table cellpadding="0" cellspacing="0" class="sysshoworgebt special"><tr><td style="background-position:-'+geb[gspec[1]]+'px 0px"></td><td style="background-position:-'+geb[gspec[2]]+'px 0px"></td><td style="background-position:-'+geb[gspec[3]]+'px 0px"></td><td style="background-position:-'+geb[gspec[4]]+'px 0px"></td><td style="background-position:-'+geb[gspec[5]]+'px 0px"></td><td style="background-position:-'+geb[gspec[6]]+'px 0px"></td><td style="background-position:-'+geb[gspec[7]]+'px 0px"></td><td style="background-position:-'+geb[gspec[8]]+'px 0px"></td><td style="background-position:-'+geb[gspec[9]]+'px 0px"></td><td style="background-position:-'+geb[gspec[10]]+'px 0px"></td></tr></table><table cellpadding="0" cellspacing="0" class="sysshoworgebt2"><tr><td style="background-position:-'+geb[gor[7]]+'px 0px"></td><td style="background-position:-'+geb[gor[1]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gor[8]]+'px 0px"></td><td style="background-position:-'+geb[gor[2]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gor[9]]+'px 0px"></td><td style="background-position:-'+geb[gor[3]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gor[10]]+'px 0px"></td><td style="background-position:-'+geb[gor[4]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gor[11]]+'px 0px"></td><td style="background-position:-'+geb[gor[5]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gor[12]]+'px 0px"></td><td style="background-position:-'+geb[gor[6]]+'px 0px"></td></tr></table><table cellpadding="0" cellspacing="0" class="sysshowplgebt" style="background-image:url(img/planeten/'+data[0]+'.jpg)"><tr><td style="background-position:-'+geb[gpl[36]]+'px 0px"></td><td style="background-position:-'+geb[gpl[35]]+'px 0px"></td><td style="background-position:-'+geb[gpl[29]]+'px 0px"></td><td style="background-position:-'+geb[gpl[23]]+'px 0px"></td><td style="background-position:-'+geb[gpl[30]]+'px 0px"></td><td style="background-position:-'+geb[gpl[34]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gpl[32]]+'px 0px"></td><td style="background-position:-'+geb[gpl[24]]+'px 0px"></td><td style="background-position:-'+geb[gpl[18]]+'px 0px"></td><td style="background-position:-'+geb[gpl[10]]+'px 0px"></td><td style="background-position:-'+geb[gpl[19]]+'px 0px"></td><td style="background-position:-'+geb[gpl[25]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gpl[28]]+'px 0px"></td><td style="background-position:-'+geb[gpl[14]]+'px 0px"></td><td style="background-position:-'+geb[gpl[6]]+'px 0px"></td><td style="background-position:-'+geb[gpl[2]]+'px 0px"></td><td style="background-position:-'+geb[gpl[7]]+'px 0px"></td><td style="background-position:-'+geb[gpl[15]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gpl[22]]+'px 0px"></td><td style="background-position:-'+geb[gpl[13]]+'px 0px"></td><td style="background-position:-'+geb[gpl[5]]+'px 0px"></td><td style="background-position:-'+geb[gpl[1]]+'px 0px"></td><td style="background-position:-'+geb[gpl[3]]+'px 0px"></td><td style="background-position:-'+geb[gpl[11]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gpl[31]]+'px 0px"></td><td style="background-position:-'+geb[gpl[17]]+'px 0px"></td><td style="background-position:-'+geb[gpl[9]]+'px 0px"></td><td style="background-position:-'+geb[gpl[4]]+'px 0px"></td><td style="background-position:-'+geb[gpl[8]]+'px 0px"></td><td style="background-position:-'+geb[gpl[16]]+'px 0px"></td></tr><tr><td style="background-position:-'+geb[gpl[33]]+'px 0px"></td><td style="background-position:-'+geb[gpl[27]]+'px 0px"></td><td style="background-position:-'+geb[gpl[21]]+'px 0px"></td><td style="background-position:-'+geb[gpl[12]]+'px 0px"></td><td style="background-position:-'+geb[gpl[20]]+'px 0px"></td><td style="background-position:-'+geb[gpl[26]]+'px 0px"></td></tr></table><br />'+data[4]+'</div>';
			$(this).data('tooltip', tt);
		}
		
		// ohne Tooltip-Attribut abbrechen
		if(typeof($(this).data('tooltip')) == 'undefined') return false;
		// Content ermitteln und zuweisen
		$('#tooltip').html($(this).data('tooltip'));
		
		// Position zuweisen
		tt_pos(e);
		
		// Tooltip einblenden
		$('#tooltip').stop(true, true).fadeIn(250);
		
		// tooltip auf true setzen
		tooltip = true;
	}).on('mouseout', '.tooltip', function(){
		// bei Mouseout Tooltip ausblenden
		$('#tooltip').stop(true, true).fadeOut(250);
		// tooltip auf false setzen
		tooltip = false;
	});
	
	// Touch-Tooltips wieder verschwinden lassen
	$document.on('touchstart', function(e) {
		if(!$(e.target).hasClass('tooltip')) {
			$('#tooltip').stop(true, true).hide();
			tooltip = false;
		}
	});
	
	// Tabs klickbar machen
	$('#tabbar').on('click', '.tab', function(e){
		// Klick auf einen Tab
		// Rechtsklick abfangen
		if (e.button != 0) return true;
		tab_click($(this).attr('id'));
	}).on('contextmenu touchcontextmenu', '.tab, .tabactive', function(e){
		// Kontextmenü öffnen
		cm_open(e, 2, $(this).attr('id'));
		return false;
	}).on('click', '.tabclose', function(e){
		// Klick auf den schließen-Button
		
		// Rechtsklick abfangen
		if (e.button != 0) return true;
		tab_close($(this).attr('id'));
		return false;
	});
	
	// Fensterbar klickbar machen
	$('#windowbar').on('click', '.barwindow, .barwindowact', function(e){
		// Klick auf ein Element der windowbar
		// Rechtsklick abfangen
		if (e.button != 0) return true;
		wbar_click($(this), 0);
	}).on('contextmenu touchcontextmenu', '.barwindow, .barwindowact', function(e){
		// Kontext-Menü
		var id = $(this).attr('id');
		id = id.replace(/[^\d]/g, '');
		var addr = $('#fenster'+id).data('link');
		// Kontextmenü öffnen
		cm_open(e, 3, [addr, id]);
		
		return false;
	}).on('click', '#startbutton', function(){
		// Startmenü
		sm_show();
	}).on('contextmenu', '#startbutton', function(){
		sm_show();
		return false;
	});
	
	// Fenster klickbar machen
	$document.on('mousedown touchstart', '.fenster', function(){
		wbar_click($(this), 1);
	}).on('mousedown touchstart', '.fhl1', function(e) {
		
		if(e.type == 'touchstart') {
			
			// Buttons auf dem Tablet wieder anklickbar machen
			if($(e.target).parents('.hl1buttons').length) {
				return;
			}
			
			e.preventDefault();
		}
		
		var id = $(this).parent().attr('id');
		id = id.replace(/fenster/g, '');
		fmove = id;
		
		if(e.type == 'touchstart' && e.originalEvent.targetTouches) {
			fmovepos = [e.originalEvent.targetTouches[0].pageX, e.originalEvent.targetTouches[0].pageY];
		}
		else {
			fmovepos = [e.pageX-document.documentElement.scrollLeft, e.pageY-document.documentElement.scrollTop];
		}
		
		fmoveobj = [parseInt($(this).parent().css('left').replace(/px/g, '')),
					parseInt($(this).parent().css('top').replace(/px/g, ''))];
	});
	
	// Startmenülinks klickbar machen
	$('#startmenuc').on('click', '.fav', function(e) {
		// Rechtsklick abfangen
		if(e.which == 3) return true;
		
		var addr = $(this).data('link');
		// Validation, bei falschem Link abbrechen
		if(addr == null || addr == '') return false;
		
		// Strg abfangen -> neuer Tab
		if(typeof($(this).data('type')) == 'undefined') type = 1;
		else type = $(this).data('type');
		// Strg und mittlere Maustaste abfangen -> neuer Tab
		if(type == 1) {
			if(e.which == 2 || e.ctrlKey) type = 2;
		}
		
		// Seite laden
		page_load(type, false, addr, acttab, false);
	}).on('mouseup', '.fav', function(e){
		// mittlere Maustaste abfangen
		if(e.which == 2) {
			var addr = $(this).data('link');
			// Validation, bei falschem Link abbrechen
			if(addr == null || addr == '') return false;
			
			var type = $(this).data('type');
			if(type == null) type = 1;
			// Strg und mittlere Maustaste abfangen -> neuer Tab
			if(type == 1) {
				type = 2;
			}
			
			page_load(type, false, addr, acttab, false);
		}
	}).on('contextmenu touchcontextmenu', '#favoriten a, #historyc a', function(e){
		// Startmenü Links Kontextmenü
		// Kontextmenü anzeigen, wenn Link vorhanden
		if(typeof($(this).data('link')) == 'undefined') return false;
		cm_open(e, 1, $(this).data('link'));
		return false;
	});
	
	// GUI tabben
	$document.on('keypress', function(e) {
		if(e.altKey && e.ctrlKey && e.which == 116) {
			tab_next();
		}
	}).on('keypress', '.enter', function(e) {
		// Enter-Taste bei Inputs aktivieren
		if(e.keyCode == 13) {
			var r = (typeof($(e.target).data('action')) == 'undefined') ? $(e.target).attr('rel') : $(e.target).data('action');
			
			var f = $(e.target).parents('form');
			// Action-Attribut ändern
			if(r) {
				// AJAX: &ajax an die addr hängen
				r = addajax(r);
				f.attr('action', r);
			}
			f.trigger('onsubmit');
		}
	});
	
	// AJAX konfigurieren
	$.ajaxSetup({
	  dataType: 'xml'
	});
	// AJAX-Handler initialisieren
	$document.ajaxStart(function(){
	   $('#ajax').show();
	}).ajaxComplete(function() {
		$('#ajax').hide();
	});
	
	// Checkboxen beim Klick auf das Label togglen
	$document.on('click', '.togglecheckbox', function() {
		var name = $(this).data('name');
		if(name == null) return false;
		var cb = $(this).parents('form').find('input[name="'+name+'"], .'+name);
		if(cb == null) return false;
		if(cb.attr('disabled')) return false;
		cb.prop('checked', !cb.prop('checked'));
	});
	
	// Allianzfilter
	$document.on('click', '.allyfilter input, .allyfilter a', function() {
		var p = this.parentNode.parentNode;
		var t = $(p).siblings('.allyfiltert');
		var ally = this.name;
		// auf einen Link geklickt
		if(this.tagName == 'A') {
			// nur diese Checkbox checken, wenn nicht gecheckt
			if($(this).css('opacity') < 1 || $(p).find('input[name!='+ally+']:checked').length > 0) {
				$(p).find('input').prop('checked', false);
				$(p).find('input[name='+ally+']').prop('checked', true);
				
				// Link-Transparenz
				$(p).find('a[name!='+ally+']').css('opacity', 0.5);
				$(p).find('a[name='+ally+']').css('opacity', 1);
				
				// Zeilen ein- und ausblenden
				$(t).find('tr').not(':first-child').each(function() {
					if($(this).data('ally') == ally) $(this).removeClass('hidden');
					else $(this).addClass('hidden');
				});
			}
			// alle checken
			else {
				$(p).find('input').prop('checked', true);
				// Link-Transparenz
				$(p).find('a').css('opacity', 1);
				// Zeilen einblenden
				$(t).find('tr').removeClass('hidden');
			}
		}
		// auf eine Checkbox geklickt
		else {
			var show = this.checked;
			// Zeilen ein- und ausblenden
			$(t).find('tr[data-ally='+ally+']').each(function() {
				if(show) $(this).removeClass('hidden');
				else $(this).addClass('hidden');
			});
			
			// Link-Transparenz
			if(show) $(p).find('a[name='+ally+']').css('opacity', 1);
			else $(p).find('a[name='+ally+']').css('opacity', 0.5);
		}
	});
	
	// Routenmarker
	$document.on('mouseenter', '.routemarker', function() {
		$('.routemarker').html('');
		$(this).html('<img src="img/layout/leer.gif" class="hoverbutton" style="background-position:-1060px -91px" title="Marker setzen" onclick="route_marker(this)" />');
	}).on('mouseleave', '.routemarker', function() {
		$(this).html('');
	}).on('touchmousedown', '.routemarker', function() {
		// iPad-Workaround
		$(this).html('<span />');
		route_marker($(this).children()[0]);
	});
	
	// Suche
	// Planetentypfilter
	$document.on('click', '.searchpltyplist > img', function() {
		$(this).toggleClass('active');
		
		// Feld aktualisieren
		var f = $(this).parents('form'),
			o = f.find('.searchpltyp');
		o.html('');
		
		var filter = [];
		$(this.parentNode).children('.active').each(function() {
			var src = $(this).attr('src');
			
			filter.push(src.replace(/[^\d]/g, ''));
			o.append('<img src="'+src+'" alt="" />');
		});
		
		// zusammenfügen und ins Formular
		filter = filter.join("-");
		f.find('input[name=t]').val(filter);
		
		// Filter deaktiviert
		if(filter == "") {
			o.html('<i>alle</i>');
		}
	});
	
	// Gebäudefilter
	$document.on('click', '.searchgeblist > img', function() {
		$(this).toggleClass('active');
		
		// Feld aktualisieren
		var f = $(this).parents('form');
		var o = f.find('.searchgeb');
		o.html('');
		
		var filter = [];
		$(this.parentNode).children('.active').each(function() {
			filter.push($(this).data('id'));
			o.append('<img src="'+$(this).attr('src')+'" alt="" />');
		});
		
		// zusammenfügen und ins Formular
		filter = filter.join("-");
		f.find('input[name=geb]').val(filter);
		
		// Filter deaktiviert
		if(filter == "") {
			o.html('<i>alle</i>');
		}
	});
	
	// Planetenkommentare durch Doppelklick löschen
	$document.on('dblclick', '.kommentar.searchicon', function() {
		if(window.confirm('Soll der Kommentar wirklich gelöscht werden?')) {
			var idClass = this.className.match(/plkommentar(\d+) /);
			
			if(idClass) {
				ajaxcall('index.php?p=show_planet&sp=kommentar&ajax&id='+idClass[1], false, {kommentar: ''}, false);
			}
		}
	});
	
	// Forschungsübersicht filtern
	$document.on('click', '.icon_forschung_filter', function() {
		$(this).toggleClass('icon_forschung_filter_active');
		forschungPage.filter(this);
	});
	
	// in OD Öffnen-Sammellinks
	$document.on('click', '.openinod-link', function(e) {
		var $links = $(this.parentNode).siblings('table').find('a[data-sys]');
				
		if($links.length && window.confirm('Diese Operation öffnet '+$links.length+' neue Tabs/Fenster auf einmal. Fortfahren?')) {
			$links.each(function() {
				window.open(ODServer + '/game/?op=system&sys='+$(this).data('sys'));
			});
		}
		
		e.preventDefault();
		return false;
	});
	
	// evtl gleich andere Seite laden (#-Anker)
	if($('#content1').attr('id') != null && document.location.href.indexOf('#p=') != -1) {
		var addr = 'index.php?'+document.location.href.replace(/^(.*)\#(.*)$/, '$2');
		// nur laden, falls Seite nicht schon im aktiven Tab
		if($('#content1').data('link') != addr) {
			// ist die Seite im Contentswitch?
			var exist = false;
			$('#content1 .cswlink').each(function() {
				if($(this).data('link') == addr) {
					contentswitch(this, $(this).data('pos'), false);
					exist = true;
					// Favicon neu laden
					$('head').append('<link rel="shortcut icon" href="favicon.ico" />');
				}
			});
			// nicht im Contentswitch -> neu laden
			if(!exist) {
				page_load(1, false, addr, acttab, false);
				// Favicon neu laden
				$('head').append('<link rel="shortcut icon" href="favicon.ico" />');
			}
		}
	}
	else {
		// geladene Seite zur DB-History hinzufügen
		dbhistory_add($('#content1').data('link'), tabs[0][1], 1);
	}
	
	// Browser-History funktionsfähig machen
	// HTML5 History-API
	if(historyapi) {
		$(window).bind('popstate', function() {
			history_checkapi(location.href);
		});
	}
	// alte Methode mit Ankern
	else {
		window.setInterval(history_check, 2000);
	}
	
});

/**
 * ?ajax oder &ajax an eine Adresse anhängen
 * @param addr string Adresse
 *
 * @return string Adresse mit &ajax
 */
function addajax(addr) {
	if(addr.indexOf('&ajax') == -1 && addr.indexOf('?ajax') == -1) {
		if(addr.indexOf('?') != -1) addr += '&ajax';
		else addr += '?ajax';
	}
	return addr;
}

/**
 * einfaches AJAX-Request
 * @param addr string Ziel-Adresse
 * @param r DOM Ziel-Element
 * @param post obj POST-Daten
 * @param errorout Fehler im Ziel-Element ausgeben
 */
function ajaxcall(addr, r, post, errorout) {
	// get oder post
	if(post == false) {
		var ajaxtype = 'get';
		post = '';
	}
	else var ajaxtype = 'post';
	
	// &ajax an die Adresse anhängen
	addr = addajax(addr);
	
	// Request absetzen
	$.ajax({
		type: ajaxtype,
		url: addr,
		data: post,
		success: function(data, status, xhr){
			// Fehler ausgeben
			if(xhr.responseText.substr(0,5) != '<?xml') {
				var error = xhr.responseText;
				error = error.replace(/\r\n/g, '');
				error = error.replace(/\n/g, '');
				error = error.replace(/<\?xml(.*)$/, '');
				error = 'Es ist ein Fehler aufgetreten:<br />Adresse: '+addr+'<br />'+error;
			}
			else {
				var error = $(data).find('error').text();
			}
			
			if(error) {
				console.log(error);
				
				if(errorout && r) {
					$(r).html('<div class="error">'+error+'</div>');
				}
				else {
					if(error.indexOf('mehr eingeloggt') != -1) alert('Du bist nicht mehr eingeloggt!');
					else alert(error);
				}
			}
			else {
				// Content ermitteln und in Ziel-Element einsetzen
				var content = $(data).find('content').text();
				if(r) {
					$(r).html(content);
				}
				
				// JavaScript ausführen
				var script = $(data).find('script').text();
				if(script != '') eval(script);
			}
		},
		error: function(e, msg) {
			// Fehlermeldung
			if(e.status != 0) {
				var content = e.responseText.replace(/<\?xml([.\s]*)$/, '');
				
				if(errorout && r) {
					var error = 'Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'<br /><br />Adresse: '+addr+'<br /><br />'+content;
					console.log(error);
					$(r).html(error);
				}
				else {
					content = content.replace(/<br \/>/g, "\n");
					content = content.replace(/<(|\/)b>/g, '');
					var error = "Es ist ein Fehler aufgetreten!\n\nFehlermeldung: "+msg+' '+e.status+"\n\nAdresse: "+addr+"\n\n"+content;
					console.log(error);
					alert(error);
				}
			}
		}
	});
}


// Seiten
/**
 * Seite per AJAX laden
 * @param type int 
 * 		1 - bestehender inline-Tab (el = id)
 *		2 - neuer inline-Tab
 *		3 - neuer Browser-Tab
 *		4 - Fenster (el = id)
 *		5 - neues Fenster
 * @param name string Name des Tabs oder Fensters
 *		false - Name wird später bestimmt
 * @param addr string Adresse, die geladen werden soll
 * @param el int Tab-ID/ Fenster-ID
 *		false - wird nicht gebraucht
 * @param post JSON POST-Daten, die evtl geschickt werden sollen
 */
function page_load(type, name, addr, el, post) {
	// GET oder POST?
	var ajaxtype = 'GET';
	if(post) ajaxtype = 'POST';
	else post = '';
	
	// type 3 - neuer Browser-Tab
	if(type == 3) {
		if(addr.indexOf('&ajax') != -1) addr = addr.replace(/&ajax/g, '');
		if(addr.indexOf('?ajax') != -1) addr = addr.replace(/\?ajax/g, '');
		window.open(addr);
	}
	else {
		// AJAX: &ajax an die addr hängen
		addr = addajax(addr);
		
		// type 1 oder 2 - Tab
		if(type == 1 || type == 2) {
			// type 1 - content-Tab
			if(type == 1) {
				var tab = acttab;
				if(el) tab = el;
				
				// eingebetteten Link ändern
				$('#content'+tab).data('link', addr.replace(/(&ajax)|(&switch)/g, ''));

				// tabbar aktualisieren
				tab_update();
			}
			// type 2 - neuer inline-Tab - Tab erzeugen
			else if(type == 2) {
				var tab = tab_create(name, false, addr);
			}
			// evtl Fenster minimieren
			if(settings['wminoncontent']) win_minall();
			
			$.ajax({
				type: ajaxtype,
				url: addr,
				data: post,
				success: function(data, status, xhr){
					// Fehler ausgeben
					if(xhr.responseText.substr(0,5) != '<?xml') {
						var error = xhr.responseText;
						error = error.replace(/\r\n/g, '');
						error = error.replace(/\n/g, '');
						error = error.replace(/<\?xml(.*)$/, '');
						error = 'Es ist ein Fehler aufgetreten:<br />Adresse: '+addr+'<br />'+error;
					}
					else {
						var error = $(data).find('error').text();
					}
					
					if(error) {
						// Fehlermeldung ausgeben
						$('#content'+tab).html(tab_headline('Fehler!', false)+page_error(error)).data('link', '');
						
						// Tab umbenennen
						tab_rename(tab, 'Fehler!');
						
						// tabbar aktualisieren
						tab_update();
					}
					else {
						// Name in den Tab laden
						var name = $(data).find('name').text();
						tab_rename(tab, name);
						
						// Content laden
						$('#content'+tab).html(tab_headline(name, addr)+$(data).find('content').text());
						
						// nach oben scrollen
						if(type == 1 || settings['newtabswitch']) {
							$('html, body').scrollTop(0);
						}
						
						// tabbar aktualisieren
						tab_update();
						
						// DB-History-Eintrag hinzufügen
						dbhistory_add(addr, name, type);
						
						// JavaScript ausführen
						var script = $(data).find('script').text();
						if(script) eval(script);
					}
				},
				error: function(e, msg) {
					// Fehlermeldung ausgeben
					if(e.status != 0) {
						var content = e.responseText.replace(/<\?xml([.\s]*)$/, '');
						
						$('#content'+tab).html(tab_headline('Fehler!', false)+page_error('Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'<br /><br />Adresse: '+addr+'<br /><br />'+content)).data('link', '');
						
						// Tab umbenennen
						tab_rename(tab, 'Fehler!');
						
						// tabbar aktualisieren
						tab_update();
					}
				}
			});
		}
		// type 4 oder 5 - Fenster
		else {
			$.ajax({
				type: ajaxtype,
				url: addr,
				data: post,
				success: function(data, status, xhr){
					// Fehler ausgeben
					if(xhr.responseText.substr(0,5) != '<?xml') {
						var error = xhr.responseText;
						error = error.replace(/\r\n/g, '');
						error = error.replace(/\n/g, '');
						error = error.replace(/<\?xml(.*)$/, '');
						error = 'Es ist ein Fehler aufgetreten:<br />Adresse: '+addr+'<br />'+error;
					}
					else {
						var error = $(data).find('error').text();
					}
					
					if(error) win_error(type, el, error);
					else {
						// Name, Icon und Content ermitteln
						var name = $(data).find('name').text();
						var icon = $(data).find('icon').text();
						var content = $(data).find('content').text();
						
						// bestehendes Fenster
						if(type == 4) {
							// Content laden und im Fenster eingebetteten Link aktualisieren
							$('#fenster'+el+' .fhllabel').html(name);
							$('#fenster'+el+' .fhlfavs').css('display','block');
							$('#fenster'+el+' .fcc').html(content);
							$('#fenster'+el).data('link', addr);
							
							// nach oben scrollen
							$('#fenster'+el+' .fcc').scrollTop(0);
							
							// Name und Icon in der windowbar aktualisieren
							jQuery.each(fenster, function(){
								if(this[0] == el) {
									this[1] = icon;
									this[2] = name;
								}
							});
						}
						// neues Fenster öffnen
						else {
							win_open(icon, name, content, false, addr);
						}
						// windowbar aktualisieren
						wbar_update();
						
						// DB-History-Eintrag hinzufügen
						dbhistory_add(addr, name, type);
						
						// JavaScript ausführen
						var script = $(data).find('script').text();
						if(script) eval(script);
					}
				},
				error: function(e, msg) {
					// Fehlermeldung
					if(e.status != 0) {
						var content = e.responseText.replace(/<\?xml([.\s]*)$/, '');
						
						win_error(type, el, 'Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'<br /><br />Adresse: '+addr+'<br /><br />'+content);
					}
				}
			});
		}
	}
	
	// Tooltip deaktivieren
	if(tooltip) {
		$('#tooltip').hide();
		tooltip = false;
	}
}

/**
 * Seite aus erzeugtem Content laden
 * @param type int
 *		1 - content-Tab (el = id)
 *		2 - neuer inline-Tab
 * @param name string Name des Tabs
 * @param content string Inhalt des Tabs
 * @param el int ID des Tabs
 *
 * @return int ID des Tabs
 */
function page_create(type, name, content, el) {
	// type 1 - content-Tab
	if(type == 1) {
		var id = el;
		if(name == false) $('content'+el).html(tab_headline(name, false)+content);
	}
	// type 2 - neuer Tab
	else {
		var id = tab_create(name, content, false);
	}
	
	return id;
}

/** Fehlermeldung erzeugen
 * @param content string Text der Fehlermeldung
 * @return string HTML der Fehlermeldung
 */
function page_error(content) {
	return '<div class="icontent" style="text-align:center;margin:20px;font-size:16px;font-weight:bold"><img src="img/layout/error.png" width="150" height="137" alt="Fehler" /><br /><br />'+content+'</div>';
}

/**
 * Contentswitch - Content innerhalb eines Tabs/Fensters umschalten
 * @param el DOM Element, auf das geklickt wurde
 * @param i int Position des Buttons
 * @param loadnew bool soll der Content auf jeden Fall neu geladen werden?
 *
 * @return false vorzeitiger Abbruch
 */
function contentswitch(el, i, loadnew) {
	// Adresse ermitteln
	var addr = $(el).data('link');
	if(addr == null || addr == '') return false;
	// &ajax an die Adresse hängen
	addr = addajax(addr);
	// &switch an die Adresse hängen
	if(addr.indexOf('&switch') == -1) {
		addr += '&switch';
	}
	
	// parent-ID ermitteln
	//var pid = el.parentNode.parentNode.id;
	var pid = $(el).parents('.content, .fenster').attr('id');
	
	// auf aktiven Content geklickt?
	var actclick = $('#'+pid+' .icontent'+i).hasClass('icontentact');
	// abbrechen, wenn aktiver Content nicht neu geladen werden soll
	if(!loadnew && actclick) return false;
	
	// Activeswitch umschalten
	$('#'+pid+' .cswact').stop().animate({left:((i-1)*150)+'px'}, Math.round(settings['effects']*1.75));
	
	// evtl Content laden und Ladebalken anzeigen
	if($('#'+pid+' .icontent'+i).html().replace(/\s/g, '') == '' || loadnew) {
		$('#'+pid+' .icontent'+i).html('<br /><div align="center"><img src="img/layout/ajax2.gif" width="66" height="66" /><br /><br /></div>');
		// Content laden
		$.ajax({
			type: 'get',
			url: addr,
			success: function(data, status, xhr){
				// Fehler ausgeben
				if(xhr.responseText.substr(0,5) != '<?xml') {
					var error = xhr.responseText;
					error = error.replace(/\r\n/g, '');
					error = error.replace(/\n/g, '');
					error = error.replace(/<\?xml(.*)$/, '');
					error = 'Es ist ein Fehler aufgetreten:<br />Adresse: '+addr+'<br />'+error;
				}
				else {
					var error = $(data).find('error').text();
				}
				
				if(error) {
					// Fehlermeldung ausgeben
					$('#'+pid+' .icontent'+i).html(page_error(error));
				}
				else {
					// Content anzeigen
					$('#'+pid+' .icontent'+i).html($(data).find('content').text());
					
					// JavaScript ausführen
					var script = $(data).find('script').text();
					if(script) eval(script);
				}
			},
			error: function(e, msg) {
				// Fehlermeldung ausgeben
				if(e.status != 0) {
					var content = e.responseText.replace(/<\?xml([.\s]*)$/, '');
					
					$('#'+pid+' .icontent'+i).html(page_error('Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'<br /><br />Adresse: '+addr+'<br /><br />'+content));
				}
			}
		});
		
	}
	
	// Adress-Zusätze wieder entfernen
	addr = addr.replace(/(&ajax)|(&switch)/g, '');
	// content-Link ändern -> Fav-Button in der headline
	$('#'+pid).data('link', addr);
	// Content umschalten
	if(!actclick) {
		$('#'+pid+' .icontentact').hide().removeClass('icontentact');
		$('#'+pid+' .icontent'+i).show().addClass('icontentact');
	}
	// evtl Fenster minimieren, wenn Contentswitch nicht im Fenster
	if(settings['wminoncontent'] && pid.search(/fenster/) == -1) win_minall();
	
	// Anker in Adressleiste ändern, falls Contentswitch im Tab
	if($(el).parents('.content').attr('id') != null && addr.indexOf('index.php?') != -1) {
		if(historyapi) {
			if(document.location.href.replace(/^(.*)\?(.*)(?:|\#(.*))$/, '$2') != addr.replace(/^index.php\?(.*)$/, '$1')) {
				history.pushState(null, null, addr);
			}
		}
		else {
			document.location.href = document.location.href.replace(/^(.*)\#(.*)$/, '$1')+'#'+addr.replace(/^index.php\?(.*)$/, '$1');
		}
		href = document.location.href;
	}
}

// Tabs
/**
 * Array der offenen Tabs (wird im HTML überschrieben)
 * [id, name]
 */
tabs = [[1, '']];
// wie viele Tabs sind offen?
tabcount = 1;
// welche ID bekommt der nächste Tab?
tabnr = 2;
// welcher Tab ist aktiv?
acttab = 1;

/**
 * Tabbar neu laden
 */
function tab_update() {
	
	if(!$('#contentbody').length) {
		return false;
	}
	
	var outc = '';
	// Bar nur anzeigen, wenn es mehr als einen Tab gibt
	if(tabcount > 1) {
		jQuery.each(tabs, function(){
			outc += '<div class="tab';
			if(this[0] == acttab) outc += 'active';
			outc += '" id="tab'+this[0]+'"><div class="tableft"></div><div class="tabmiddle"><div class="tabclose" id="tabclose'+this[0]+'" title="Tab schlie&szlig;en"></div>'+this[1]+'</div><div class="tabright"></div></div>';
		});
	}
	$('#tabbar').html(outc);
	
	// document-title ändern
	jQuery.each(tabs, function(){
		if(this[0] == acttab) {
			var title = this[1];
			window.setTimeout(function() {
				document.title = title+' - ODDB ';
			}, 20);
		}
	});
	// bei zu vielen Tabs Content-Bereich nach unten verschieben
	var hmov = $('#tabbar').height();
	if(hmov < 30) hmov = 30;
	$('#contentc').css('top', (hmov+75)+'px');
	// Content-Close-Buttons ein- bzw ausblenden bei einem bzw mehreren Tabs
	if(tabcount > 1) $('.ctabclose').show();
	else $('.ctabclose').hide();
	
	// Anker in Adressleiste ändern
	var addr = $('#content'+acttab).data('link');
	if(addr != null && addr.indexOf('index.php?') != -1) {
		// HTML5 History-API
		if(historyapi) {
			if(document.location.href.replace(/^(.*)\?(.*)(?:|\#(.*))$/, '$2') != addr.replace(/^index.php\?(.*)$/, '$1')) {
				history.pushState(null, null, addr);
			}
		}
		// altes System mit #-Anker
		else {
			addr = addr.replace(/(&ajax)|(&switch)/g, '');
			document.location.href = document.location.href.replace(/^(.*)\#(.*)$/, '$1')+'#'+addr.replace(/^index.php\?(.*)$/, '$1');
			href = document.location.href;
		}
		
		// Link im Menü hervorheben
		var p = addr.match(/p=([^&]+)/);
		if(p != null) {
			$('#menu > a').removeClass('active');
			$('#menu > a[data-link="index.php?p='+p[1]+'"]').addClass('active');
		}
	}
	
	// Tabbar sortable machen
	$('#tabbar').sortable({
		items : '.tab, .tabactive',
		forcePlaceholderSize : true,
		revert : 150,
		tolerance : 'pointer',
		scroll : false,
		distance : 10,
		stop : function() {
			var tabs2 = tabs;
			tabs = [];
			$('#tabbar').find('.tab, .tabactive').each(function() {
				var id = $(this).attr('id').replace(/tab/g, '');
				for(var i=0;i<tabs2.length;i++) {
					if(tabs2[i][0] == id) {
						tabs.push(tabs2[i]);
					}
				}
			});
		}
	});
}

/**
 * Tab wechseln
 * @param id string ID des Tabs, auf den geklickt wurde
 */
function tab_click(id) {
	var id = id.replace(/[^\d]/g, '');
	// Klick auf aktiven Tab verhindern
	if(id == acttab) return false;
	// Content umblenden
	$('#content'+acttab).hide();
	$('#content'+id).show();
	// evtl Fenster minimieren
	if(settings['wminoncontent']) win_minall();
	// Tabs aktualisieren
	acttab = id;
	tab_update();
}

/**
 * Tab über die Tableiste schließen
 * @param id string ID des Tabs, auf den geklickt wurde
 */
function tab_close(id) {
	if(typeof(id) != 'number') {
		id = id.replace(/[^\d]/g, '');
	}
	
	// aus DOM entfernen
	var cdiv = document.getElementById('content'+id);
	document.getElementById('contentbody').removeChild(cdiv);
	
	// aus Array entfernen
	var acttab2 = 0;
	var gogo = false;
	var tabs2 = [];
	jQuery.each(tabs, function(){
		if(this[0] != id) {
			tabs2.push([this[0], this[1]]);
			if(this[0] < id || (this[0]>id && gogo)) {
				acttab2 = this[0];
				gogo = false;
			}
		}
		else gogo = true;
	});
	
	tabs = tabs2;
	tabcount--;
	if(acttab == id) {
		acttab = acttab2;
		$('#content'+acttab).show();
	}
	
	// evtl Fenster minimieren
	if(settings['wminoncontent']) win_minall();
	
	// Tabs aktualisieren
	tab_update();
}

/**
 * Tab von innen schließen
 * @param el DOM Element des schließen-Buttons
 */
function tab_close2(el) {
	var id = el.parentNode.parentNode.parentNode.id.replace(/content/g, '');
	tab_close(id);
}

/**
 * neuen Tab erstellen
 * @param name string Name des Tabs
 * @param content string Inhalt des Tabs
 * @param link string Link des Tabs
 *
 * @return int ID des erstellten Tabs
 */
function tab_create(name, content, link) {
	var id = tabnr;
	tabnr++;
	tabcount++;
	
	// evtl Label setzen
	if(name == false) name = 'wird geladen...';
	// evtl Ladebalken anzeigen
	if(content == false) content = '<br /><div align="center"><img src="img/layout/ajax2.gif" width="66" height="66" /><br /><br /></div>';
	else content = tab_headline(name, link)+content;
	
	// aktiven Content ausblenden, wenn newtabswitch
	if(settings['newtabswitch']) {
		$('#content'+acttab).hide();
	}
	
	// neuen Content erstellen
	var newc = document.createElement("div");
	newc.id = 'content'+id;
	newc.className = 'content';
	if(link == false || link == null) link = '';
	link = link.replace(/(&ajax)|(&switch)/g, '');
	newc.setAttribute('data-link', link);
	newc.innerHTML = content;
	// verstecken, wenn kein newtabswitch
	if(!settings['newtabswitch']) {
		newc.style.display = 'none';
	}
	document.getElementById('contentbody').appendChild(newc);
	
	// Tabs aktualisieren
	tabs.push([id, name]);
	if(settings['newtabswitch']) {
		acttab = id;
	}
	tab_update();
	
	// evtl Fenster minimieren
	if(settings['wminoncontent'] && settings['newtabswitch']) win_minall();
	
	// ID des neuen Tabs zurückgeben
	return id;
}

/**
 * erzeugt eine Headline für einen inline-Tab
 * @param name string Name des Tabs
 * @param link string/false Link des Tabs
 *
 * @return string HTML der Headline
 */
function tab_headline(name, link) {
	var outc = '<div class="hl1"><div class="hl1buttons"><div style="background-position:-215px -44px';
	// ohne Link kein Favoriten-Button
	if(link == false) outc += ';display:none';
	outc += '" title="Seite zu den Favoriten hinzuf&uuml;gen" onclick="fav_add(this, 1)"></div><div  class="favbutton" style="background-position:-240px -44px" title="Seite in einem Fenster &ouml;ffnen" onclick="win_fromcontent2(this, \''+(name.replace(/'/g, "\\'"))+'\', false)"></div><div class="ctabclose" title="Tab schlie&szlig;en" onclick="tab_close2(this)"></div></div>'+name+'</div>';
	return outc;
}

/**
 * Tab umbenennen
 * @param id int ID des Tabs
 * @param name string neuer Name des Tabs
 */
function tab_rename(id, name) {
	jQuery.each(tabs, function(){
		if(this[0] == id) this[1] = name;
	});
}

/**
 * Dialog zum Umbenennen eines Tabs öffnen
 * @param id int ID des Tabs
 */
 /*
  * DEPRECATED
  *
function tab_rename1(id) {
	// Name des Tabs ermitteln
	var name = '';
	jQuery.each(tabs, function(){
		if(this[0] == id) name = this[1].replace(/"/g, '&quot');
	});
	// Content erzeugen
	var content = '<br /><br /><div align="center"><form action="#" onsubmit="return tab_rename2('+id+', this.name.value, '+fensternr+')">Name: <input type="text" class="text" name="name" value="'+name+'" /><br /><br /><input type="submit" class="button" value="umbenennen" /> <input type="button" class="button" value="abbrechen" onclick="win_close('+fensternr+')" /></form></div><br /><br />';
	// Dialog als Fenster öffnen
	var fneu = win_open('', 'Tab umbenennen', content, 400, '');
	$('#fenster'+fneu+' input:text').select();
}
*/

/**
 * Tab aus Dialog heraus umbenennen
 * @param id int ID des Tabs
 * @param name string Name des Tabs
 * @param win int ID des Dialogfensters
 *
 * @return false Absenden verhindern
 */
 /*
  * DEPRECATED
  *
function tab_rename2(id, name, win) {
	// Name HTML-fähig machen
	name = name.replace(/</g, '&lt;');
	name = name.replace(/>/g, '&gt;');
	name = name.replace(/"/g, '&quot;');
	name = name.replace(/&/g, '&amp;');
	
	// Tab umbenennen
	jQuery.each(tabs, function(){
		if(this[0] == id) {
			$(tab_headline(name, $('#content'+id).data('link'))).replaceAll('#content'+id+' .hl1');
			this[1] = name;
		}
	});
	// Dialog schließen
	win_close(win);
	// tabbar aktualisieren
	tab_update();
	
	return false;
}
*/

/**
 * Inhalt eines Fensters in neuen Tab laden
 * @param id int ID des Fensters
 */
function tab_fromwin(id) {
	// Name ermitteln
	var name = false;
	jQuery.each(fenster, function(){
		if(this[0] == id) name = this[2];
	});
	
	// Tab öffnen
	var tab = tab_create(name, $('#fenster'+id+' .fcc').html(), $('#fenster'+id).data('link'));
	
	// Formwerte übertragen (dirty)
	$('#content'+tab).html('<div class="hl1">'+$('#content'+tab+' .hl1').html()+'</div>');
	$('#fenster'+id+' .fcc > *').clone(true).appendTo('#content'+tab);
	
	// Selects übertragen
	transferform('fenster', id, 'content', tab);
	
	// Fenster evtl schließen
	if(settings['closeontransfer']) {
		win_close(id);
	}
}

/**
 * Inhalt eines Fensters von innen heraus in neuen Tab laden
 * @param el DOM Element des Buttons
 */
function tab_fromwin2(el) {
	// ID des Fensters ermitteln
	var id = el.parentNode.parentNode.parentNode.id.replace(/fenster/g, '');
	tab_fromwin(id);
}

/**
 * AJAX-Request zum Laden von Content in einen Tab
 * @param tab int ID des Tabs
 * @param ajaxtype GET/POST
 * @param addr string Adresse des Requests
 * @param post JSON POST-Daten
 */
 /*
 *
 * DEPRECATED
 *
 
function tab_ajax(tab, ajaxtype, addr, post) {
	$.ajax({
		type: ajaxtype,
		url: addr,
		data: post,
		success: function(data, status, xhr){
			// Fehler ausgeben
			if(xhr.responseText.substr(0,5) != '<?xml') {
				var error = xhr.responseText;
				error = error.replace(/\r\n/g, '');
				error = error.replace(/\n/g, '');
				error = error.replace(/<\?xml(.*)$/, '');
				error = 'Es ist ein Fehler aufgetreten:<br />Adresse: '+addr+'<br />'+error;
			}
			else {
				var error = $(data).find('error').text();
			}
			
			if(error) alert(error);
			else {
				// Name in den Tab laden
				var name = $(data).find('name').text();
				tab_rename(tab, name);
				
				// Content laden und im Tab eingebetteten Link aktualisieren
				$('#content'+tab).html(tab_headline(name, addr)+$(data).find('content').text()).data('link', addr);
				
				// tabbar aktualisieren
				tab_update();
				
				// JavaScript ausführen
				var script = $(data).find('script').text();
				if(script) eval(script);
			}
		}
	});
}
*/

/**
 * Tab neu laden
 * @param id int ID des Tabs
 *
 * @return false Abbruch bei Fehler
 */
function tab_reload(id) {
	// Adresse ermitteln
	var link = $('#content'+id).data('link');
	if(link == null || link == '') return false;
	
	// Seite laden
	page_load(1, false, link, id, false);
}


/** 
 * zum nächsten Tab wechseln
 */
function tab_next() {
	// nur 1 Tab
	if(tabs.length < 2) {
		return false;
	}
	
	var firsttab = false;
	var next = false;
	var stop = false;
	
	jQuery.each(tabs, function(){
		
		if(!stop) {
			// ersten Tab speichern
			if(firsttab === false) {
				firsttab = this[0];
			}
			
			// beim nächsten Tab angekommen
			if(next) {
				tab_click(this[0]+'');
				stop = true;
			}
			
			// beim aktiven Tab angekommen: markieren
			if(this[0] == acttab) {
				next = true;
			}
		}
		
	});
	
	// aktiver Tab am Ende: ersten Tab auswählen
	if(!stop) {
		tab_click(firsttab+'');
	}
}


// Fenster
/**
 * Objekt mit sprite-Positionen der verschiedenen Fenster-Icons
 */
ficon = {
	'' : '-300px -54px',
	'system' : '-318px -54px',
	'planet' : '-336px -54px',
	'player' : '-354px -54px',
	'ally' : '-372px -54px',
	'meta' : '-390px -54px'
};

/**
 * Array, das die Fenster beinhaltet
 * [id, icon, name, open, zindex]
 */
fenster = [];
// wie viele Fenster sind offen?
fenstercount = 0;
// welche ID bekommt das nächste Fenster?
fensternr = 1;
// welches Fenster ist aktiv?
actfenster = 0;
// welchen Z-Index bekommt das nächste Fenster?
zindex = 10;
// welches Fenster wird gerade bewegt?
fmove = 0;
// welche Position hatte die Maus am Anfang der Bewegung?
fmovepos = [0,0];
// welche Position hatte das Fenster am Anfang der Bewegung?
fmoveobj = [0,0];

/**
 * windowbar aktualisieren
 */
function wbar_update() {
	// je nach Zahl der Fenster und Browserfenster-Breite Breite anpassen
	// Firefox, Opera, Safari und Chrome
	if(fenster.length) {
		var width = ($(window).width()-(fenster.length*3)-160)/fenster.length;
		if(width > 180) width = 180;
	}
	else width = 180;
	var mlen = Math.round((width-35)/7);
	if(mlen < 3) mlen = 3;
	
	// Content erzeugen
	var outc = '<div id="startbutton"></div>';
	if(fenster.length) outc += '<div id="wbarcloseall" title="alle Fenster schlie&szlig;en" onclick="win_closeall()"></div><div id="wbarminall" title="alle Fenster minimieren" onclick="win_minall()"></div>';
	jQuery.each(fenster, function(){
		var titel = this[2];
		if(titel.length > mlen) titel = titel.substring(0,mlen-2)+'...';
		
		outc += '<div class="barwindow';
		if(this[0] == actfenster) outc += 'act';
		outc += '" style="width:'+width+'px" id="bfenster'+this[0]+'" title="'+this[2]+'"><div style="background-position:'+ficon[this[1]]+'"></div>'+titel+'</div>';
	});
	$('#windowbar').html(outc);
	
	// Windowbar sortable machen
	$('#windowbar').sortable({
		items : '.barwindow, .barwindowact',
		forcePlaceholderSize : true,
		revert : 150,
		tolerance : 'pointer',
		scroll : false,
		distance : 10,
		stop : function() {
			var fenster2 = fenster;
			fenster = [];
			$('#windowbar').find('.barwindow, .barwindowact').each(function() {
				var id = $(this).attr('id').replace(/bfenster/g, '');
				for(var i=0;i<fenster2.length;i++) {
					if(fenster2[i][0] == id) {
						fenster.push(fenster2[i]);
					}
				}
			});
		}
	});
	
	// Schnellzugriffsleiste nach oben schieben
	if(fenster.length > 2) {
		$('#szgr').removeClass('szgr-bottom');
	}
	else {
		$('#szgr').addClass('szgr-bottom');
	}
}

/**
 * Fenster auf der windowbar oder Fensterheadline anklicken
 * @param el DOM Element, auf das geklickt wurde
 * @param type int
 *		0 - Klick auf die windowbar
 *		1 - Klick auf die Fensterheadline
 *
 * @return false vorzeitig abbrechen
 */
function wbar_click(el, type) {
	var id = el.attr('id');
	var id = id.replace(/[^\d]/g, '');
	// aktives Fenster in der windowbar angeklickt
	if(actfenster == id && !type) {
		// Fenster ausblenden und auf close setzen
		jQuery.each(fenster, function(){
			if(this[0] == id) this[3] = 0;
		});
		
		win_blendout(id);
		
		//$('#fenster'+id).fadeOut(250);
		
		// neues aktives Fenster bestimmen
		var maxz = 0;
		var maxid = 0;
		
		jQuery.each(fenster, function(){
			if(this[4] > maxz && this[3] && this[0] != id) {
				maxz = this[4];
				maxid = this[0];
			}
		});
		
		actfenster = maxid;
	}
	// aktives Fenster auf headline geklickt -> nichts passiert
	else if(actfenster == id && type) return false;
	// doppeltes Event beim Minimieren abfangen
	else if(actfenster == 0 && type) return false;
	// nicht aktives Fenster angeklickt -> einblenden / in den Vordergrund schieben
	else {
		win_reopen(id, false);
	}
	// windowbar aktualisieren
	wbar_update();
}


/**
 * Fenster minimieren
 * @param id int ID des Fensters
 */
function win_min(id) {
	// aktives Fenster -> gleiche Funktion wie Klick auf windowbar
	if(actfenster == id) {
		wbar_click($('#bfenster'+id), 0);
	}
	// über Kontextmenü
	else {
		// ausblenden
		//$('#fenster'+id).fadeOut(250);
		win_blendout(id);
		
		// Array ändern
		jQuery.each(fenster, function(){
			if(this[0] == id) this[3] = 0;
		});
		// windowbar aktualisieren 
		wbar_update();
	}
}

/**
 * Fenster von sich aus minimieren
 * @param el DOM Element des minimieren-Buttons
 */
function win_min2(el) {
	var id = el.parentNode.parentNode.parentNode.id.replace(/fenster/g, '');
	win_min(id);
}

/**
 * alle Fenster minimieren
 */
function win_minall() {
	jQuery.each(fenster, function(){
		if(this[3]) {
			//$('#fenster'+this[0]).fadeOut(250);
			win_blendout(this[0]);
			this[3] = 0;
		}
	});
	actfenster = 0;
	// windowbar aktualisieren
	wbar_update();
}

/**
 * Fenster zum Minimieren ausblenden
 * @param id int ID des Fensters
 */
function win_blendout(id) {
	// ursprüngliche Position und Dimensionen speichern
	var pos = $('#fenster'+id).offset();
	pos.top -= $(window).scrollTop();
	pos.left -= $(window).scrollLeft();
	var w = $('#fenster'+id).width();
	var h = $('#fenster'+id).height();
	var zi = $('#fenster'+id).css('zIndex');
	var npos = $('#bfenster'+id).offset();
	npos.top -= $(window).scrollTop();
	
	$('#fenster'+id).css({'zIndex':1000001,'minWidth':'0px'})
	.animate(
		{top:npos.top-5,left:npos.left,height:0,width:120,opacity:0.2},
		300, 
		function() {
			// auf unsichtbar setzen, restliche Werte wiederherstellen
			$('#fenster'+id).css({'zIndex':zi,'display':'none','minWidth':'450px','top':pos.top,'left':pos.left,'opacity':1,'width':w,'height':''})
		}
	);
}

/**
 * Fenster zum Wiederherstellen einblenden
 * @param id int ID des Fensters
 */
function win_blendin(id) {
	// jQuery-Bug verhindern
	$('#fenster'+id).css({'display':'block','opacity':0});
	
	// ursprüngliche Position und Dimensionen speichern
	var pos = $('#fenster'+id).offset();
	pos.top -= $(window).scrollTop();
	pos.left -= $(window).scrollLeft();
	var w = $('#fenster'+id).width();
	var h = $('#fenster'+id).height();
	var zi = $('#fenster'+id).css('zIndex');
	var npos = $('#bfenster'+id).offset();
	npos.top -= $(window).scrollTop();
	
	$('#fenster'+id).css({'zIndex':1000001,'opacity':0.2,'minWidth':'0px','top':npos.top-5,'left':npos.left,'height':0,'width':120})
	.animate(
		{top:pos.top,left:pos.left,opacity:1,width:w,height:h},
		300, 
		function() {
			// restliche Werte wiederherstellen
			$('#fenster'+id).css({'zIndex':zi,'minWidth':'450px','width':w,'height':''})
		}
	);
}

/**
 * Fenster schließen
 * @param id int ID des Fensters
 */
function win_close(id) {
	// ausblenden
	$('#fenster'+id).fadeOut(250);
	// aus DOM entfernen
	window.setTimeout("cdiv2 = document.getElementById('fenster"+id+"');document.body.removeChild(cdiv2);", 1000);
	// aus Array entfernen
	var actf2 = 0;
	var highz = 0;
	var fenster2 = [];
	jQuery.each(fenster, function(){
		if(this[0] != id) {
			fenster2.push([this[0], this[1], this[2], this[3], this[4]]);
			if(this[3] && this[4] > highz) {
				actf2 = this[0];
				highz = this[4];
			}
		}
	});
	fenster = fenster2;
	fenstercount--;
	if(actfenster == id) {
		actfenster = actf2;
	}
	// windowbar aktualisieren
	wbar_update();
}

/**
 * Fenster von sich aus schließen
 * @param el DOM Element des schließen-Buttons
 */
function win_close2(el) {
	var id = el.parentNode.parentNode.parentNode.id.replace(/fenster/g, '');
	win_close(id);
}

/**
 * alle Fenster schließen
 */
function win_closeall() {
	jQuery.each(fenster, function(){
		win_close(this[0]);
	});
}

/**
 * neues Fenster
 * @param icon string Icons des Fensters @see ficons
 * @param name string Name des Fensters
 * @param content string Content des Fensters
 * @param width int/false Breite des Fensters; 0/false = variable Breite
 * @param link string Link auf die Seite, die geöffnet wird
 *
 * @return int ID des erzeugten Fensters
 */
function win_open(icon, name, content, width, link) {
	var fid = fensternr;
	
	// Array erweitern
	fenster.push([fensternr, icon, name, 1, zindex]);
	// headline einfügen
	var outc = '<div class="fhl1" id="fhl'+fid+'"><div class="hl1buttons"><div style="background-position:-215px -44px';
	if(link == false || link == '') outc += ';display:none';
	outc += '" title="Seite zu den Favoriten hinzuf&uuml;gen" onclick="fav_add(this, 2)" class="fhlfavs"></div><div style="background-position:-434px -54px" title="Inhalt in einen neuen DB-Tab laden" onclick="tab_fromwin2(this)"></div><div style="background-position:-408px -54px" title="Fenster minimieren" onclick="win_min2(this)"></div><div style="background-position:-265px -44px" title="Fenster schlie&szlig;en" onclick="win_close2(this)"></div></div><span class="fhllabel">'+name+'</span></div><div class="fcc">'+content+'</div>';
	
	// DOM erzeugen
	var newc = document.createElement("div");
	newc.id = 'fenster'+fensternr;
	newc.className = 'fenster';
	newc.style.display = 'none';
	if(width) newc.style.width = width+'px';
	newc.setAttribute('data-link', link, 0);
	newc.style.zIndex = zindex;
	newc.innerHTML = outc;
	document.body.appendChild(newc);
	
	// ein bisschen breiter machen, um horizontale Scrollbalken zu verstecken
	if(!width) {
		$('#fenster'+fensternr).css({'width' : ($('#fenster'+fensternr).width()+25)+'px'});
	}
	
	// bei Übergröße scrollbar machen
	$('#fenster'+fensternr).css({'maxHeight' : ($(window).height()-95)+'px'});
	$('#fenster'+fensternr+' .fcc').css({'maxHeight' : ($(window).height()-145)+'px'});
	
	// Fenster zentrieren und nach oben verschieben
	if(!width) var width = $('#fenster'+fensternr).width();
	var height = $('#fenster'+fensternr).height();
	
	var x = ($(window).width()-width)/2;
	var y = ($(window).height()-height-75)/8;
	// innerhalb des Browserfensters halten
	if(x < 5) x = 5;
	if(y < 5) y = 5;
	$('#fenster'+fensternr).css({'left' : x+'px', 'top' : y+'px'});
	
	// Größe konstant halten
	
	// einblenden
	$('#fenster'+fensternr).fadeIn(250);
	
	// resizable machen und Content-Scrollhöhe je nach Höhe anpassen
	$('#fenster'+fensternr).resizable({
		minHeight : 150,
		resize: function(event, ui) {
			$('#'+$(event.target).attr('id')+' .fcc').css({'height' : (ui.size['height']-50)+'px'});
		}
	});
	
	// Variablen erhöhen
	actfenster = fensternr;
	fensternr++;
	zindex++;
	
	// windowbar aktualisieren
	wbar_update();
	
	// ID des Fensters zurückgeben
	return fid;
}

/**
 * Fenster aktiv machen
 * -> ggf wieder einblenden und in den Vordergrund schieben
 * @param id int ID des Fensters
 * @param update bool windowbar nach Öffnen updaten
 */
function win_reopen(id, update) {
	actfenster = id;
	var wopen = false;
	
	jQuery.each(fenster, function(){
		if(this[0] == id) {
			if(this[3]) wopen = true;
			this[3] = 1;
			this[4] = zindex;
		}
	});
	
	// Fenster nach vorne schieben
	$('#fenster'+id).css('zIndex', zindex);
	// falls minimiert wieder einblenden
	if(!wopen) {
		//$('#fenster'+id).fadeIn(250);
		win_blendin(id);
	}
	
	zindex++;
	
	// evtl windowbar aktualisieren
	if(update) wbar_update();
}

/**
 * Fenster aus Content erzeugen
 * @param cid int ID des Tabs/Contents
 * @param name string Name des Fensters
 * @param width int/false Breite des Fensters; 0/false = variable Breite
 */
function win_fromcontent(cid, name, width) {
	var fid = fensternr;
	content = $('#content'+cid).html();
	win_open('', name, content, width, $('#content'+cid).data('link'));
	// Formwerte übertragen (dirty)
	$('#fenster'+fid+' .fcc').html('');
	$('#content'+cid+' > *').clone(true).appendTo('#fenster'+fid+' .fcc');
	$('#fenster'+fid+' .hl1').remove();
	
	// Selects übertragen
	transferform('content', cid, 'fenster', fid);
	
	// Tab evtl schließen
	if(settings['closeontransfer'] && tabcount > 1) {
		// verhindern, dass sich das Fenster gleich minimiert
		var wmoc = false;
		if(settings['wminoncontent']) {
			wmoc = true;
			settings['wminoncontent'] = false;
		}
		tab_close(cid);
		
		settings['wminoncontent'] = wmoc;
	}
}

/**
 * Fenster aus Content erzeugen von innen
 * @param el DOM Element des In-Fenster-Laden-Buttons
 * @param name string Name des Fensters
 * @param width int/false Breite des Fensters; 0/false = variable Breite
 */
function win_fromcontent2(el, name, width) {
	var cid = el.parentNode.parentNode.parentNode.id.replace(/content/g, '');
	win_fromcontent(cid, name, width);
}

/**
 * Daten aus Fenster-Array auslesen
 * @param id int ID des Fensters
 *
 * @return array Fensterdaten @see fenster
 * @return false Fenster existiert nicht
 */
function win_data(id) {
	for(var i in fenster) {
		if(i >= 0 && typeof(fenster[i][0]) != 'undefined' && fenster[i][0] == id) {
			return fenster[i];
		}
	}
	return false;
}

/**
 * Dialog zum Umbenennen eines Fensters öffnen
 * @param id int ID des Fensters
 */
 /*
  * DEPRECATED
  *
function win_rename1(id) {
	// Name des Fensters ermitteln
	var data = win_data(id);
	var name = data[2].replace(/"/g, '&quot');
	
	// Content erzeugen
	var content = '<br /><br /><div align="center"><form action="#" onsubmit="return win_rename2('+id+', this.name.value, '+fensternr+')">Name: <input type="text" class="text" name="name" value="'+name+'" /><br /><br /><input type="submit" class="button" value="umbenennen" /> <input type="button" class="button" value="abbrechen" onclick="win_close('+fensternr+')" /></form></div><br /><br />';
	// Dialog als Fenster öffnen
	var fneu = win_open('', 'Fenster umbenennen', content, 400, '');
	$('#fenster'+fneu+' input:text').select();
}
*/

/**
 * Fenster aus Dialog heraus umbenennen
 * @param id int ID des Fensters
 * @param name string Name des Fensters
 * @param win int ID des Dialogfensters
 *
 * @return false Absenden verhindern
 */
 /*
  * DEPRECATED
  *
function win_rename2(id, name, win) {
	// Name HTML-fähig machen
	name = name.replace(/</g, '&lt;');
	name = name.replace(/>/g, '&gt;');
	name = name.replace(/"/g, '&quot;');
	name = name.replace(/&/g, '&amp;');
	
	// Fenster umbenennen
	jQuery.each(fenster, function(){
		if(this[0] == id) {
			$('#fenster'+id+' .fhllabel').html(name);
			this[2] = name;
		}
	});
	
	// Dialog schließen
	win_close(win);
	
	// windowbar aktualisieren
	wbar_update();
	
	return false;
}
*/

/**
 * Fenster neu laden
 * @param id int ID des Fensters
 *
 * @return false Abbruch bei Fehler
 */
function win_reload(id) {
	// Adresse ermitteln
	var link = $('#fenster'+id).data('link');
	if(link == null || link == '') return false;
	
	// Seite laden
	page_load(4, false, link, id, false);
}

/**
 * Fehlermeldung in ein Fenster laden
 * @param type int 4/5 - bestehendes/neues Fenster @see page_load()
 * @param el int ID des Fensters
 * @param msg string Fehlermeldung
 */
function win_error(type, el, msg) {
	// Fehlermeldung ausgeben
	var content = page_error(msg);
	
	// bestehendes Fenster
	if(type == 4) {
		$('#fenster'+el+' .fhllabel').html('Fehler!');
		$('#fenster'+el+' .fhlfavs').css('display','none');
		$('#fenster'+el+' .fcc').html(content);
		
		// Name und Icon in der windowbar aktualisieren
		jQuery.each(fenster, function(){
			if(this[0] == el) {
				this[1] = '';
				this[2] = 'Fehler!';
			}
		});
	}
	// neues Fenster
	else {
		win_open('', 'Fehler!', content, false, '');
	}
	
	// windowbar aktualisieren
	wbar_update();
}

/**
 * ID des parent-Fensters ermitteln
 * @param el DOM Element innerhalb des Fensters
 *
 * @return int Fenster-ID
 */
function win_getid(el) {
	var id = $(el).parents('.fenster').attr('id');
	if(id != null) {
		id = id.replace(/fenster/g, '');
		return id;
	}
	else return 0;
}

/**
 * Fenster schließen, die ein bestimmtes Element enthalten
 * @param sel str jQuery-Selektor
 */
function parentwin_close(sel) {
	var id = $(sel).parents('.fenster').attr('id');
	
	if(id != null) {
		id = id.replace(/[^\d]/g, '');
		win_close(id);
	}
}


// Schnellzugriffsleiste
// offen? (wird von HTML überschrieben)
var szgr = true;


/**
 * Schnellzugriffsleiste öffnen / schließen
 */
function szgr_toggle() {
	// schließen
	if(szgr) {
		$('#szgr').slideUp('normal');
		$('#szgrlink').html('Schnellzugriffsleiste &ouml;ffnen');
	}
	// öffnen
	else {
		$('#szgr').slideDown('normal');
		$('#szgrlink').html('Schnellzugriffsleiste schlie&szlig;en');
	}
	// Status ändern
	szgr = !szgr;
}

/**
 * Formular der Schnellzugriffsleiste abschicken
 * @param type Typ der Suche @see ficon
 * @param val Formular-Eingabe
 *
 * @return false Verhinderung des normalen Submits
 */
function szgr_send(type, val) {
	// abbrechen, wenn nichts eingegeben
	if(val == '') return false;
	// Adresse erzeugen
	var addr = 'index.php?p=show_'+type+'&id='+encodeURIComponent(val);
	// Seite laden
	page_load(settings['szgrtype'], false, addr, false, false);
	return false;
}



// Startmenü
// ist das Startmenü offen?
var startmenu = false;

/**
 * Startmenü öffnen
 */
function sm_show() {
	// Starmenü nicht offen -> öffnen
	if(!startmenu) {
		// Chronik erzeugen
		var content = '';
		jQuery.each(dbhistory, function() {
			if(this[2] == 4) this[2] = 5;
			else if(this[2] == 1) this[2] = 2;
			content += '<a class="fav dbhistorylink" data-link="'+this[0]+'" data-type="'+this[2]+'">'+this[1]+'</a>';
		});
		content += '<div id="historyspacer"></div>';
		// Chronik ausblenden und Link einblenden
		$('#historyc').hide().html(content);
		if(dbhistory.length) $('#historylink').show();
		else $('#historylink').hide();
		
		// Startmenü einblenden
		$('#startmenu').fadeIn(200);
		window.setTimeout('startmenu = true;', 100);
	}
	// schon offen -> schließen
	else {
		sm_hide();
	}
}

/**
 * Startmenü schließen
 */
function sm_hide() {
	if(startmenu) {
		if(!dbhistory_active) {
			$('#startmenu').fadeOut(200);
			window.setTimeout('startmenu = false;', 100);
		}
		else dbhistory_active = false;
	}
}

/**
 * Größe des Startmenüs aktualisieren
 */
function sm_updatesize() {
	// Höhe des Startmenüs ermitteln
	var height = $('#startmenuc').height()+25;
	var shrink = false;
	// Höhe und Overflow des Startmenüs festlegen, wenn es höher als der Bildschirm ist
	// Firefox, Opera, Safari und Chrome
	if(window.innerHeight) {
		if(height+38 > window.innerHeight) {
			$('#startmenu').css({'height' : (window.innerHeight-40)+'px', 'overflow' : '', 'overflowY' : 'auto'});
			shrink = true;
		}
	}
	// IE
	else if(document.documentElement.clientWidth) {
		if(height+38 > document.documentElement.clientHeight) {
			$('#startmenu').css({'height' : (document.documentElement.clientHeight-40)+'px', 'overflow' : 'auto'});
			shrink = true;
		}
	}
	// klein genug -> normale Größe wiederherstellen
	if(!shrink) {
		$('#startmenu').css({'height' : '', 'overflow' : 'hidden'});
	}
}


// Kontextmenü
// ist das Kontextmenü offen?
var contextmenu = false;

/**
 * Kontextmenü schließen
 */
function cm_close() {
	if(contextmenu) {
		$('#contextmenu').hide();
	}
}

/**
 * Kontextmenü öffnen
 * @param e Event des Rechtsklicks
 * @param type int
 *		0 - benutzerdefiniert
 *		1 - normaler Link
 *		2 - Tab
 *		3 - Fenster
 * @param value string zusätzliche Daten
 *		type 0 - array(HTML, Linkanzahl)
 *		type 1 - Link-Adresse
 *		type 2 - Tab-ID
 *		type 3 - array(Link, Fenster-ID)
 */
function cm_open(e, type, value) {
	// wie viele Links sind im Kontextmenü?
	var lcount = 0;
	var content = '';
	// type 0 - benutzerdefiniert
	if(type == 0) {
		var content = value[0];
		lcount = value[1];
	}
	// type 1 - normaler Link
	if(type == 1) {
		content = '<a href="javascript:page_load(2, false, \''+value+'\', false, false)">in neuem DB-Tab öffnen</a><a href="javascript:page_load(3, false, \''+value+'\', false, false)">in neuem Browser-Tab öffnen</a>';
		if($(e.target).parents('.fenster').attr('id') != null && settings['winlinknew'] && $(e.target).hasClass('winlink')) {
			var id = $(e.target).parents('.fenster').attr('id').replace(/fenster/g, '');
			content += '<a href="javascript:page_load(4, false, \''+value+'\', '+id+', false)">im selben DB-Fenster öffnen</a>';
		}
		else {
			content += '<a href="javascript:page_load(5, false, \''+value+'\', false, false)">in neuem DB-Fenster öffnen</a>';
		}
		// Favorit -> Link zum Bearbeiten und Entfernen
		if(e.target.className == 'fav') {
			content += '<a href="javascript:fav_edit(\''+e.target.id+'\')">Favorit bearbeiten</a><a href="javascript:void(check=window.confirm(\'Soll der Favorit wirklich gelöscht werden?\'));if(check){fav_del(\''+e.target.id+'\')}">aus Favoriten entfernen</a>';
			lcount += 5;
		}
		// kein Favorit
		else {
			content += '<a href="javascript:fav_add(\''+value+'\', 3)">zu Favoriten hinzufügen</a>';
			lcount += 4;
		}
		
		
		var el = $(e.target);
		
		// Planeten-Link
		if(el.hasClass('link_planet') && el.data('id')) {
			content += '<a href="' + ODServer + '/game/?op=orbit&amp;index='+el.data('id')+'" target="_blank">Orbit in OD &ouml;ffnen</a>';
			content += '<a href="' + ODServer + '/game/?op=fleet&amp;pre_pid_set='+el.data('id')+'" target="_blank">Schiffe hierher schicken</a>';
			lcount += 2;
		}
		
		// System-Link
		else if(el.hasClass('link_system') && el.data('id')) {
			content += '<a href="' + ODServer + '/game/?op=system&amp;sys='+el.data('id')+'" target="_blank">System in OD &ouml;ffnen</a>';
			lcount += 1;
		}
	}
	// type 2 - Tab
	else if(type == 2) {
		// ID bereinigen
		value = value.replace(/[^\d]/g, '');
		// Link im Tab verankert?
		// - Favoriten
		// - in neuem Tab öffnen
		// - Tab aktualisieren
		if(typeof($('#content'+value).data('link')) != 'undefined' && $('#content'+value).data('link') != '') {
			var addr = $('#content'+value).data('link');
			content = '<a href="javascript:fav_add('+value+', 1)">zu Favoriten hinzufügen</a><a href="javascript:tab_reload('+value+')">Tab neu laden</a><a href="javascript:page_load(3, false, \''+addr+'\', false, false)">in neuem Browser-Tab öffnen</a>';
			lcount += 3;
		}
		else content = '';
		// in DB-Fenster öffnen -> richtiger Content drin?
		if(typeof($('#content'+value+' .favbutton').attr('onclick')) != 'undefined') {
			var oncl = $('#content'+value+' .favbutton').attr('onclick');
			
			var pattern = /win_fromcontent2\((.*)\)/;
			oncl = pattern.exec(oncl);
			if(oncl != null) {
				oncl = oncl[0].replace(/win_fromcontent2\(this,/, 'win_fromcontent('+value+',');
				oncl = oncl.replace(/"/g, '\'');
				content += '<a href="javascript:'+oncl+'">in DB-Fenster öffnen</a>';
				lcount += 1;
			}
		}
		// Tab umbenennen und schließen
		//content += '<a href="javascript:tab_rename1('+value+')">Tab umbenennen</a>';
		content += '<a href="javascript:tab_close(\'tab'+value+'\')">Tab schließen</a>';
		//lcount += 2;
		lcount++;
	}
	// type 3 - Fenster
	else {
		var addr = value[0];
		var win = value[1];
		// Link im Fenster verankert?
		// - Favoriten
		// - Fenster neu laden
		// - in neuem Browser-Tab öffnen
		if(typeof($('#fenster'+win).data('link')) != 'undefined' && $('#fenster'+win).data('link') != '') {
			var addr = $('#fenster'+win).data('link');
			content += '<a href="javascript:fav_add('+win+', 2)">zu Favoriten hinzufügen</a><a href="javascript:win_reload('+win+')">Fenster neu laden</a><a href="javascript:page_load(3, false, \''+addr+'\', false, false)">in neuem Browser-Tab öffnen</a>';
			lcount += 3;
		}
		// Fenster in DB-Tab laden, umbenennen
		content += '<a href="javascript:tab_fromwin('+win+')">Inhalt in DB-Tab laden</a>';
		//<a href="javascript:win_rename1('+win+')">Fenster umbenennen</a>';
		// minimieren / wiederherstellen
		var open = false;
		var wdata = win_data(win);
		if(wdata) open = wdata[3];
		// Fenster offen -> minimieren
		if(open) {
			content += '<a href="javascript:win_min('+win+')">Fenster minimieren</a>';
		}
		// Fenster minimiert -> öffnen
		else {
			content += '<a href="javascript:win_reopen('+win+', true)">Fenster &ouml;ffnen</a>';
		}
		// Fenster schließen
		content += '<a href="javascript:win_close('+win+')">Fenster schließen</a>';
		//lcount += 4;
		lcount += 3;
	}
	// Content zuweisen
	$('#contextmenuc').html(content);
	
	// Position zuweisen
	var height = (lcount*30)+12;
	if(e.pageX) {
		var x = e.pageX-$(window).scrollLeft()+10,
			y = e.pageY-$(window).scrollTop()+5;
	}
	else {
		var target = $(e.target),
			position = target.offset(),
			x = position.left-$(window).scrollLeft()+target.width()-2,
			y = position.top-$(window).scrollTop()+target.height()-2;
	}
	// zu weit unten -> über Mauszeiger
	if(y+height > $(window).height()-30) y = y-height;
	// zu weit rechts -> links
	if(x > $(window).width()-220) x -= 195;
	
	// zuweisen
	$('#contextmenu').stop().css({'display' : 'none', 'left' : x+'px', 'top' : y+'px', 'width' : '175px', 'height' : '', 'opacity' : '', 'filter' : ''});
	
	// Kontextmenü einblenden
	$('#contextmenu').show('fast');
	
	contextmenu = true;
}

// Tooltip
var tooltip = false;

/**
 * Position des Tooltips aktualisieren
 * @param e Event des mouseover/mousemove
 */
function tt_pos(e) {
	var height = $('#tooltip').height(),
		width = $('#tooltip').width(),
		x, y;
	
	// absolute Positionierung auf Touch-Geräten
	if(e.type == 'touchmousedown') {
		var target = $(e.target),
			position = target.offset();
		
		x = position.left-20;
		y = position.top+15;
		
		$('#tooltip').css('position', 'absolute');
	}
	// traditionelle fixe Maus-Positionierung
	else {
		x = e.pageX-document.documentElement.scrollLeft+10;
		y = e.pageY-document.documentElement.scrollTop+15;
		
		// Chrome Fix
		if(navigator.userAgent.indexOf('Chrome') > -1) {
			y -= $('body').scrollTop();
		}
		
		// zu weit unten -> über Mauszeiger
		if(y+height > $(window).height()-10) y = y-height-20;
		// zu weit rechts -> links
		if(x+width > $(window).width()-30) x = x-width-30;
		
		// aber auch nicht zu weit nach links und zu weit nach oben kommen
		if(x < 0) x = 0;
		if(y < 0) y = 0;
	}
	
	
	// Position übernehmen
	$('#tooltip').css({'left' : x+'px', 'top' : y+'px'});
}

/**
 * Formular-Werte zwischen Tabs und Fenstern übertragen (Selects und Textareas)
 * @param from string content/fenster
 * @param fromid int ID des Ursprungs
 * @param to string content/fenster
 * @param id int ID des Ziels
 */

function transferform(from, fromid, to, toid) {
	var forms = [];
	var i = 1;
	$('#'+to+toid).find('form').each(function() {
		forms[i] = $(this);
		i++;
	});
	
	var i = 1;
	$('#'+from+fromid).find('form').each(function() {
		var form = $(this);
		var newform = forms[i];
		i++;
		
		// Selects übertragen
		$(this).find('select').each(function() {
			$(newform).find('select[name='+$(this).attr('name')+']').val($(this).val());
		});
		
		// Textareas übertragen
		$(this).find('textarea').each(function() {
			$(newform).find('textarea[name='+$(this).attr('name')+']').val($(this).val());
		});
	});
}


/**
 * AJAX-Formular bei Klick auf einen Button mit anderer Adresse abschicken
 * @param el DOM Button
 * @param addr string neue Ziel-Adresse
 */
function form_submit(el, addr) {
	// AJAX: &ajax an die addr hängen
	addr = addajax(addr);
	// Action-Attribut ändern und onSubmit triggern
	$(el).parents('form').attr('action', addr).trigger('onsubmit');
}

/**
 * Formular innerhalb einer Seite abschicken
 * @param f DOM abzuschickendes Formular
 * @param addr string Ziel-Adresse
 * @param r DOM Ausgabe-Element
 *
 * @return false normalen Submit verhindern
 */
function form_send(f, addr, r) {
	// Safari, Chrome und Opera: falsches Abschicken verhindern
	if(addr == '#') return false;
	
	addr = addajax(addr);
	
	// Formular-Buttons deaktivieren
	$('input[type=button], input[type=submit]', f).attr('disabled', 'disabled');
	
	var fdata = $(f).serialize();
	
	// Lade-Animation
	if(r) $(r).html('<div align="center"><img src="img/layout/ajax.gif" width="24" height="24" /></div>');
	
	// AJAX-Request
	$.ajax({
		type: 'post',
		url: addr,
		data: fdata,
		success: function(data, status, xhr){
			// Fehler ausgeben
			if(xhr.responseText.substr(0,5) != '<?xml') {
				var error = xhr.responseText;
				error = error.replace(/\r\n/g, '');
				error = error.replace(/\n/g, '');
				error = error.replace(/<\?xml(.*)$/, '');
				error = 'Es ist ein Fehler aufgetreten:<br />Adresse: '+addr+'<br />'+error;
			}
			else {
				var error = $(data).find('error').text();
			}
			
			if(error) {
				// Fehlermeldung ausgeben
				if(r) $(r).html('<div class="error center">'+error+'</div>');
				else alert(error);
			}
			else {
				// Content anzeigen
				if(r) $(r).html($(data).find('content').text());
				
				// JavaScript ausführen
				var script = $(data).find('script').text();
				if(script) eval(script);
			}
			
			// Formularbuttons reaktivieren
			$('input[type=button], input[type=submit]', f).removeAttr('disabled');
		},
		error: function(e, msg) {
			if(e.status != 0) {
				// Fehlermeldung ausgeben
				var content = e.responseText.replace(/<\?xml([.\s]*)$/, '');

				if(r) $(r).html('<span class="error">Es ist ein Fehler aufgetreten!<br />Fehlermeldung: '+msg+' '+e.status+'<br />Adresse: '+addr+'<br /><br />'+content+'</span>');
				else {
					content = content.replace(/<br \/>/g, "\n");
					content = content.replace(/<(|\/)b>/g, '');
					alert('Fehler: '+msg+' '+e.status+' '+addr+"\n\n"+content);
				}
			}
			// Formularbuttons reaktivieren
			$('input[type=button], input[type=submit]', f).removeAttr('disabled');
		}
	});
	
	// normalen Submit verhindern
	return false;
}

/**
 * Suchformular abschicken
 * @param f DOM Formular-Element
 * @param addr string Ziel-Adresse (muss ? enthalten)
 *
 * @return false normalen Submit verhindern
 */
function form_sendget(f, addr) {
	// Absende-Button deaktivieren
	$(f).find('input[type=submit]').attr('disabled', 'disabled');
	
	// Adresse erzeugen
	addr = addajax(addr);
	addr = addr+'&'+$(f).find('input').filter(function(index) {
			return (this.value != '');
		}).serialize()+'&'+$(f).find('select').filter(function(index) {
		return (this.value != '');
	}).serialize();
	
	// abschließendes & entfernen
	addr = addr.replace(/&+$/, '');
	
	// Fenster-ID ermitteln
	var id = $(f).parents('.fenster').attr('id');
	
	// Link ist in Fenster
	if(id != null) {
		id = id.replace(/[^\d]/g, '');
		page_load(4, false, addr, id, false);
	}
	// Link ist in Tab
	else {
		page_load(1, false, addr, acttab, false);
	}
	
	// normalen Submit verhindern
	return false;
}

// Favoriten
/**
 * Seite zu Favoriten hinzufügen - Dialog öffnen
 * @param el
 *		type 1+2: Element des Fav-Buttons / int ID des Tabs/Fensters
 *		type 3: string Adresse des Fav-Links
 * @param type int
 *		1 - Tab
 *		2 - Fenster
 *		3 - einfacher Link
 *
 * @return false Abbruch bei Fehler
 */
function fav_add(el, type) {
	var name = '';
	
	// Tab
	if(type == 1) {
		// ID ermitteln
		if(typeof(el) == 'number') var id = el;
		else var id = el.parentNode.parentNode.parentNode.id.replace(/content/g, '');
		// else var id = el.parents('.content, .fenster').id.replace(/(content|fenster)/g, '');
		// Link ermitteln
		var link = $('#content'+id).data('link');
		if(link == null || link == '') return false;
		// Name ermitteln
		jQuery.each(tabs, function() {
			if(this[0] == id) name = this[1].replace(/"/g, '&quot;');
		});
		var mode = 1;
	}
	// Fenster
	else if(type == 2) {
		// ID ermitteln
		if(typeof(el) == 'number') var id = el;
		else var id = el.parentNode.parentNode.parentNode.id.replace(/fenster/g, '');
		// Link ermitteln
		var link = $('#fenster'+id).data('link');
		if(link == null || link == '') return false;
		// Name ermitteln
		jQuery.each(fenster, function() {
			if(this[0] == id) name = this[2].replace(/"/g, '&quot;');
		});
		var mode = 5;
	}
	// einfacher Link
	else {
		var link = el;
		var mode = 1;
	}
	
	// ajax aus Adresse entfernen
	if(link.indexOf('&ajax') != -1) link = link.replace(/&ajax/g, '');
	if(link.indexOf('?ajax') != -1) link = link.replace(/\?ajax/g, '');
	
	// Dialog-Content erzeugen
	var content = '<br /><br /><div align="center"><form action="#" onsubmit="return fav_add2(\''+link+'\', this.name.value, this.type.value, '+fensternr+')">Name: <input type="text" class="text" name="name" value="'+name+'" /><br /><br />Standardverhalten: <select name="type" size="1"><option value="1">im aktiven Tab &ouml;ffnen</option><option value="2">in einem neuen DB-Tab &ouml;ffnen</option><option value="3">in einem neuen Browser-Tab &ouml;ffnen</option><option value="5">in einem DB-Fenster &ouml;ffnen</option></select><br /><br /><br /><input type="submit" class="button" value="hinzuf&uuml;gen" /> <input type="button" class="button" value="abbrechen" onclick="win_close('+fensternr+')" /></form></div><br /><br />';
	// Dialog als Fenster öffnen
	var fneu = win_open('', 'Zu Favoriten hinzuf&uuml;gen', content, 400, '');
	$('#fenster'+fneu+' select').val(mode);
	$('#fenster'+fneu+' input:text').select();
}

/**
 * Seite zu Favoriten hinzufügen - absenden
 * @param link string Adresse des Favoriten
 * @param name string
 * @param type int @see page_load()
 * @param win int ID des Dialogfensters
 *
 * @return false normalen Submit verhindern
 */
function fav_add2(link, name, type, win) {
	// Validierung
	if(name.replace(/\s/g, '') == '') {
		alert('kein Name eingegeben!');
		return false;
	}
	
	// POST-Array erzeugen
	var post = {
		'link' : link,
		'name' : name,
		'typ' : type
	};
	
	// AJAX-Request -> eintragen und Startmenü neu laden
	ajaxcall('index.php?p=ajax_general&sp=fav_add', $('#favoriten'), post, false);
	
	// Dialog schließen
	win_close(win);
	
	// normalen Submit verhindern
	return false;
}

/**
 * Eintrag aus den Favoriten löschen
 * @param id string ID des Favoriten-Links
 */
function fav_del(id) {
	// ID in Zahl umwandeln
	id = id.replace(/fav/, '');
	
	// AJAX-Request -> löschen und Startmenü neu laden
	ajaxcall('index.php?p=ajax_general&sp=fav_del&id='+id, $('#favoriten'), false, false);
}

/**
 * Dialog zum Bearbeiten eines Favoriten öffnen
 * @param id string ID des Favoriten-Links
 */
function fav_edit(id) {
	var el = $('#'+id);
	
	// ID in Zahl umwandeln
	id = id.replace(/fav/, '');
	
	// Daten ermitteln
	var name = el.html().replace(/"/g, '&quot;');
	var link = el.data('link');
	var type = el.data('type');
	
	// Dialog-Content erzeugen
	var content = '<br /><br /><div align="center"><form action="#" onsubmit="return fav_edit2('+id+', \''+link+'\', this.name.value, this.type.value, '+fensternr+')">Name: <input type="text" class="text" name="name" value="'+name+'" /><br /><br />Standardverhalten: <select name="type" size="1"><option value="1">im aktiven Tab &ouml;ffnen</option><option value="2">in einem neuen DB-Tab &ouml;ffnen</option><option value="3">in einem neuen Browser-Tab &ouml;ffnen</option><option value="5">in einem DB-Fenster &ouml;ffnen</option></select><br /><br /><br /><input type="submit" class="button" value="&auml;ndern" /> <input type="button" class="button" value="abbrechen" onclick="win_close('+fensternr+')" /></form></div><br /><br />';
	// Dialog als Fenster öffnen
	var fneu = win_open('', 'Favorit bearbeiten', content, 400, '');
	$('#fenster'+fneu+' select').val(type);
	$('#fenster'+fneu+' input:text').select();
}

/**
 * Favorit bearbeiten - absenden
 * @param id int ID des Favoriten
 * @param link string Adresse des Favoriten
 * @param name string
 * @param type int @see page_load()
 * @param win int ID des Dialogfensters
 *
 * @return false normalen Submit verhindern
 */
function fav_edit2(id, link, name, type, win) {
	// Validierung
	if(name.replace(/\s/g, '') == '') {
		alert('kein Name eingegeben!');
		return false;
	}
	
	// POST-Array erzeugen
	var post = {
		'id' : id,
		'link' : link,
		'name' : name,
		'typ' : type
	};
	
	// AJAX-Request -> editieren und Startmenü neu laden
	ajaxcall('index.php?p=ajax_general&sp=fav_edit', $('#favoriten'), post, false);
	
	// Dialog schließen
	win_close(win);
	
	// normalen Submit verhindern
	return false;
}

// Browser-History
var href = document.location.href;

/**
 * Adresse auf Änderungen überprüfen und eventuell andere Seite laden
 */
function history_check() {
	if(href != document.location.href && document.location.href.indexOf('#') != -1) {
		href = document.location.href;
		var addr = 'index.php?'+href.replace(/^(.*)\#(.*)$/, '$2');
		var found = false;
		
		// Seite im CSW?
		$('#content'+acttab+' .cswlink').each(function() {
			if(!found && $(this).data('link') == addr) {
				found = true;
				var reload = false;
				if($(this).data('reload') == 'true') reload = true;
				contentswitch(this, $(this).data('pos'), reload);
			}
		});
		
		// Seite in einem Tab?
		if(!found) {
			jQuery.each(tabs, function() {
				if(!found && $('#content'+this[0]).data('link') == addr) {
					found = true;
					tab_click('tab'+this[0]);
				}
			});
		}
		
		// Seite in den aktiven Tab laden
		if(!found) {
			page_load(1, false, addr, acttab, false);
		}
	}
}

/**
 * vor/zurück (HTML5 History-API)
 */
function history_checkapi(href) {
	// seltsame Fehler vermeiden
	if(href.indexOf('?') == -1) {
		return false;
	}
	
	var addr = 'index.php?'+href.replace(/^(.*)\?(.*)(?:|\#(.*))$/, '$2');
	var found = false;
	
	// Seite im CSW?
	$('#content'+acttab+' .cswlink').each(function() {
		if(!found && $(this).data('link') == addr) {
			found = true;
			var reload = false;
			if($(this).data('reload') == 'true') reload = true;
			contentswitch(this, $(this).data('pos'), reload);
		}
	});
	
	// Seite in einem Tab?
	if(!found) {
		jQuery.each(tabs, function() {
			if(!found && $('#content'+this[0]).data('link') == addr) {
				found = true;
				tab_click('tab'+this[0]);
			}
		});
	}
	
	// Seite in den aktiven Tab laden
	if(!found) {
		page_load(1, false, addr, acttab, false);
	}
}

// DB-interne History
var dbhistory = [];
var dbhistory_active = false;

/**
 * Beriech der DB-internen History im Startmenü anzeigen
 */
function dbhistory_open() {
	dbhistory_active = true;
	$('#historyc').slideDown(250);
	$('#historylink').slideUp(250);
	window.setTimeout('sm_updatesize();$("#startmenu").scrollTop(10000);',250);
	
}
/**
 * Eintrag zur DB-internen History hinzufügen
 * @param addr string Link zur aufgerufenen Seite
 * @param name string Name der Seite
 * @param type int Typ des Seitenaufrufs @see page_load()
 */
function dbhistory_add(addr, name, type) {
	// Eintrag schon in der History drin? -> nur nach hinten verschieben
	var hlen = dbhistory.length;
	for(var i=0;i<hlen;i++) {
		if(dbhistory[i][0] == addr && dbhistory[i][1] == name) {
			var dbh2 = [];
			for(var k=0;k<hlen;k++) {
				if(k != i) dbh2.push(dbhistory[k]);
			}
			dbhistory = dbh2;
			break;
		}
	}
	// ab 8 Einträgen History kürzen
	if(dbhistory.length >= 8) {
		dbhistory = dbhistory.slice(dbhistory.length-7);
	}
	// neues Element anhängen
	dbhistory.push([addr, name, type]);
}

// Einscannen

/**
 * parst den Quelltext einer OD-Seite ein
 * @param f DOM Formular
 * @param r DOM Ausgabe-Element
 *
 * @return false Abbruch bei Fehler
 */
function quelltext(f, r) {
	// Ausgabefeld leeren
	r.html('');
	
	// Eingabe in DOM-Tree umwandeln
	var input = f.input.value;
	
	// Grafiken beim Parsen nicht laden
	input = input.replace(/src="/g, 'src="file:///');
	
	var tree = $($.parseHTML(input));
	var ctree = tree.find('#layout-main');
	
	// Ausgabe-Container
	var out = {};
	
	try {
		// Überprüfung auf richtige OD-Welt
		var world = tree.find('div.world');
		if(world.length && world.html().indexOf(ODWorld) == -1) {
			throw 'Falsche OD-Welt!';
		}
		
		//
		// Unbekanntes System
		//
		if(ctree.find('td > a[onmouseover*="setter(\'\',\'\',\' \',\' \',\'\')"]').length) {
			out['typ'] = 'sysun';
			
			// Einscanner-Ally ermitteln
			if(tree.find('.statusbar a.alliance').length) {
				out['scannerally'] = tree.find('.statusbar a.alliance').attr('href').replace(/[^\d]/g, '');
			}
			
			// ID und Name
			try {
				out['id'] = ctree.find('#sysid > input').val();
				out['name'] = ctree.find('#sysid').prev('strong').html().replace(/&nbsp;/g, '');
			}
			catch(e) {
				throw 'Konnte Name und ID nicht ermitteln!';
			}
			
			// Planetendaten
			var pl = [];
			var data;
			
			if(ctree.find('table[width="800"] td[valign="top"] > table').length != 7) {
				throw 'Konnte nicht alle Planeten ermitteln!';
			}
			
			ctree.find('table[width="800"] td[valign="top"] > table').each(function(i) {
				// Planet vorhanden
				if($(this).find('td').length) {
					pl[i] = {};
					
					try {
						// ID
						pl[i]['id'] = $(this).find('tr:first-child img').attr('id').replace(/x/, '');
						
						// Typ
						pl[i]['typ'] = $(this).find('tr:first-child > td').css('background-image').replace(/^.*planet(\d+)_s.*$/, '$1');
					}
					catch(e) {
						throw 'Konnte ID und Typ nicht ermitteln ('+i+')';
					}
					
					// Name und Werte
					try {
						data = $(this).find('tr:first-child a').attr('onmouseover');
						pl[i]['name'] = data.replace(/^.*\'<b>(.*)<\/b>:\'\);setter.*$/, '$1');
						
						// kein Genesis -> Werte
						if(data.indexOf('!&lt;\/b&gt;&lt;\/center&gt;') == -1 && data.indexOf('demateriali') == -1) {
							data = data.replace(/','.*$/, '');
							data = data.replace(/http:\/\/[a-zA-Z0-9]*\.*omega/g, '');
							data = data.split('<br>');
							
							pl[i]['erz'] = data[4].replace(/[^\d+]/g, '');
							pl[i]['wolfram'] = data[5].replace(/[^\d+]/g, '');
							pl[i]['kristall'] = data[6].replace(/[^\d+]/g, '');
							pl[i]['fluor'] = data[7].replace(/[^\d+]/g, '');
							pl[i]['bev'] = data[9].replace(/[^\d+]/g, '');
							pl[i]['groesse'] = data[11].replace(/[^\d+]/g, '');
							
							// Workaround: Unbewohnbare Planis Größe 0
							if(pl[i]['groesse'] == '' || pl[i]['groesse'] == '0000') {
								pl[i]['groesse'] = 0;
							}
						}
					}
					catch(e) {
						throw 'Konnte Planetenwerte nicht ermitteln ('+i+')';
					}
					
				}
				// Kein Planet vorhanden
				else {
					pl[i] = '';
				}
			});
			
			// ans Ausgabe-Objekt hängen
			out['pl'] = pl;
		}
		//
		// Bekanntes System
		//
		else if(ctree.find('font#sysid > input').length) {
			out['typ'] = 'system';
			
			// ID und Name
			try {
				out['id'] = ctree.find('#sysid > input').val();
				out['name'] = ctree.find('#sysid').prev('strong').html().replace(/&nbsp;/g, '');
			}
			catch(e) {
				throw 'Konnte Name und ID nicht ermitteln!';
			}
			
			// Planetendaten
			var pl = [];
			var data, p;
			
			if(ctree.find('table[width="800"] td[valign="top"] > table').length != 7) {
				throw 'Konnte nicht alle Planeten ermitteln!';
			}
			
			ctree.find('table[width="800"] td[valign="top"] > table').each(function(i) {
				// Planet vorhanden
				if($(this).find('td').length) {
					pl[i] = {};
					
					try {
						// ID
						pl[i]['id'] = $(this).find('tr:first-child img').attr('id').replace(/x/, '');
						
						// Typ
						pl[i]['typ'] = $(this).find('tr:first-child > td').css('background-image').replace(/^.*planet(\d+)_s.*$/, '$1');
					}
					catch(e) {
						throw 'Konnte ID und Typ nicht ermitteln ('+i+')';
					}
					
					// Name und Werte
					try {
						data = $(this).find('tr:first-child a').attr('onmouseover');
						pl[i]['name'] = data.match(/\'([^\']*):\'\);setter\(/)[1];
						
						data = data.replace(/','.*$/, '');
						data = data.split('<br>');
						
						// Workaround: Unbewohnbare Planis Größe und Bevölkerung 0
						if(data.length == 6) {
							pl[i]['groesse'] = 0;
							pl[i]['bev'] = 0;
						}
						else {
							pl[i]['bev'] = data[4].replace(/[^\d+]/g, '');
							
							var groesse = data[6].match(/e: (\d+)\',/);
							
							if(groesse) {
								pl[i]['groesse'] = groesse[1];
							}
							else {
								throw 'Konnte Größe nicht ermitteln ('+i+')';
							}
						}
						
						data = $(data[3]);
						pl[i]['erz'] = data.find('tr:nth-child(2) > td:nth-child(2)').html().replace(/[^\d+]/g, '');
						pl[i]['wolfram'] = data.find('tr:nth-child(4) > td:nth-child(2)').html().replace(/[^\d+]/g, '');
						pl[i]['kristall'] = data.find('tr:nth-child(5) > td:nth-child(2)').html().replace(/[^\d+]/g, '');
						pl[i]['fluor'] = data.find('tr:nth-child(6) > td:nth-child(2)').html().replace(/[^\d+]/g, '');
					}
					catch(e) {
						throw 'Konnte Planetenwerte nicht ermitteln ('+i+')'+e;
					}
					
					// Inhaber
					p = /setter\(\'(.+)\',[\r\n\s]*\'(\d+)\',\'.*\',\'(.*)\',\'(.*)\'\);/;
					data = p.exec($(this).find('tr:first-child a').attr('onmouseover'));
					if(data == null) throw 'Konnte Inhaber nicht ermitteln! ('+i+')';
					else {
						pl[i]['inhaber'] = data[2];
						pl[i]['allianz'] = data[3];
						
						// verschleiert
						if(data[2] == 0) {
							if(data[1].indexOf('Lux') != -1) {
								pl[i]['inhaber'] = -2;
							}
							else if(data[1].indexOf('Altrasse') != -1) {
								pl[i]['inhaber'] = -3;
							}
						}
					}
					
					// Gate
					if($(this).find('tr:last-child img[src*="tor.gif"]').length) {
						pl[i]['gate'] = 1;
					}
					// Myrigate
					p = /<b>(?:Absprungpunkt nach|Jump-off point to) .*<\/b>(?:\s*)\((\d+)\)\'/;
					data = p.exec($(this).find('tr:last-child a').attr('onmouseover'));
					if(data != null) {
						pl[i]['mgate'] = data[1];
					}
					// Riss
					p = /<b> Zielpunkt von (?:.+)<\/b>(?:\s*)\((\d+)\)\'/;
					data = p.exec($(this).find('tr:last-child a').attr('onmouseover'));
					if(data != null) {
						pl[i]['riss'] = data[1];
					}
					
					// Bergbau
					if($(this).find('tr:last-child img[src*="flotte-bergbau.gif"]').length) {
						pl[i]['bb'] = 1;
					}
					
					// Natives
					else if($(this).find('tr:last-child img[src*="flotte_natives.gif"]').length) {
						pl[i]['natives'] = 1;
					}
					
					// Kolo
					else if($(this).find('tr:last-child img[src*="flotte-kolo.gif"]').length) {
						pl[i]['kolo'] = 1;
					}
					
					// Genesis
					else if($(this).find('tr:last-child img[src*="flotte-genesis.gif"]').length) {
						pl[i]['genesis'] = 1;
					}
					
					// Terraformer
					else if($(this).find('tr:last-child img[src*="flotte_terraform.gif"]').length) {
						pl[i]['tf'] = 1;
					}
					
					// Invasion
					else if($(this).find('tr:last-child img[src*="flotte_invasion.gif"]').length) {
						pl[i]['inva'] = 1;
					}
					
					// Besatzung
					else if($(this).find('tr:last-child img[src*="flotte-besatzer.gif"]').length) {
						pl[i]['besatzung'] = 1;
					}
					
					// Orbit leer
					else if($(this).find('tr:last-child img[src*="orbit_blank.gif"]').length) {
						pl[i]['leer'] = 1;
					}
				}
				// Kein Planet vorhanden
				else {
					pl[i] = '';
				}
			});
			
			// ans Ausgabe-Objekt hängen
			out['pl'] = pl;
		}
		//
		// Planetenansicht / Scan
		//
		else if(ctree.find('tr.bewtr').length && ctree.find('a[href^="?op=orbit"]').length) {
			out['typ'] = 'planet';
			
			var data;
			var p;
			
			out['id'] = ctree.find('a[href^="?op=orbit"]').attr('href').replace(/[^\d]/g, '');
			
			// Name
			var name = ctree.find('a[href^="?op=renamer"]').html();
			if(name == null) {
				// Name im Planetenscan
				name = ctree.find('table table tr:first-child > td:last-child > b').html();
				
				if(name == null) {
					throw 'Konnte Name nicht ermitteln!';
				}
			}
			
			out['name'] = name;
			
			
			// Typ
			try {
				out['pltyp'] = ctree.find('td[width="600"][background]').attr('background').replace(/^.*planet(\d+)\..*$/, '$1');
			}
			catch(e) {
				throw 'Konnte Planetentyp nicht ermitteln!';
			}
			
			// verschleierter Inhaber
			try {
				var data = ctree.find('a[href^="?op=orbit"]').parents('tr').next().find('b');
				if(data) {
					out['inhabername'] = data.html();
				}
			}
			catch(e) {
				// nichts unternehmen
			}
			
			// Werte
			data = ctree.find('img[src*="icon_planet_info.gif"]').parent().attr('onmouseover');
			if(data == null) {
				throw 'Konnte Planetenwerte nicht ermitteln!';
			}
			
			data = data.replace(/','.*$/, '');
			data = data.split('<br>');
			
			out['erz'] = data[1].replace(/[^\d]/g, '');
			out['wolfram'] = data[3].replace(/[^\d]/g, '');
			out['kristall'] = data[4].replace(/[^\d]/g, '');
			out['fluor'] = data[5].replace(/[^\d]/g, '');
			out['bev'] = data[7].replace(/[^\d]/g, '');
			out['groesse'] = data[8].replace(/[^\d]/g, '');
			
			// aktuelle Bevölkerung
			out['bevakt'] = out['bev'] * (50 - ctree.find('.bewtr > td[bgcolor="#BBBBBB"]').length) / 50;
			
			try {
				// Ressproduktion
				if(ctree.find('#erzproduktion > font').length) {
					out['erzp'] = ctree.find('#erzproduktion > font').html().replace(/[^\-\d]/g, '');
				}
				else {
					out['erzp'] = ctree.find('#erzproduktion').html().replace(/[^\-\d]/g, '');
				}
				
				out['metallp'] = ctree.find('#metallproduktion').html().replace(/[^\d]/g, '');
				out['wolframp'] = ctree.find('#wolframproduktion').html().replace(/[^\d]/g, '');
				out['kristallp'] = ctree.find('#kristallproduktion').html().replace(/[^\d]/g, '');
				out['fluorp'] = ctree.find('#flourproduktion').html().replace(/[^\d]/g, '');
				
				// Ressvorrat
				out['erzm'] = ctree.find('#erzdais').html().replace(/[^\d]/g, '');
				out['metallm'] = ctree.find('#metallda').html().replace(/[^\d]/g, '');
				out['wolframm'] = ctree.find('#wolframda').html().replace(/[^\d]/g, '');
				out['kristallm'] = ctree.find('#kristallda').html().replace(/[^\d]/g, '');
				out['fluorm'] = ctree.find('#flourda').html().replace(/[^\d]/g, '');
			}
			catch(e) {
				throw 'Konnte Ressproduktion und -vorrat nicht ermitteln!';
			}
			// Forschung und Industrie
			try {
				out['forschung'] = ctree.find('#forprod').parent().parent().prev().find('b').html().replace(/[^\d]/g, '');
				out['industrie'] = ctree.find('#indprod').parent().parent().prev().find('b').html().replace(/[^\d]/g, '');
			}
			catch(e) {
				throw 'Konnte Forschung und Industrie nicht ermitteln!';
			}
			
			// Gebäude
			try {
				// Planetengebäude
				data = ctree.find('td[width="600"][background]');
				for(var i=1; i<=36; i++) {
					out['g'+i] = data.find('img[name="pod'+i+'"]').attr('src').replace(/^.*\/img\/(?:buildings\/|misc\/)*/, '').replace(/blank.gif/, '');
				}
				
				// Orbitgebäude
				data = data.prev();
				for(i=1; i<=12; i++) {
					out['o'+i] = data.find('img[name="wpod'+i+'"]').attr('src').replace(/^.*\/img\/(?:buildings\/|misc\/)*/, '');
				}
				
				// Spezialgebäude
				data = ctree.find('td[colspan="8"] table');
				for(i=1; i<=10; i++) {
					out['s'+i] = data.find('img[name="spod'+i+'"]').attr('src').replace(/^.*\/img\/(?:buildings\/|misc\/)*/, '');
				}
			}
			catch(e) {
				throw 'Konnte Gebäude nicht ermitteln!';
			}
			
			// Spieler
			try {
				out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
			}
			catch(e) {
				throw 'Konnte Spieler nicht ermitteln!';
			}
			
			// Geld, Einnahmen und Gesamt-FP
			if(ctree.find('#creditsda').html() != '') {
				try {
					// Vermögen und Steuereinnahmen
					out['konto'] = ctree.find('#creditsda').html().replace(/[^\d\-]/g, '');
					out['steuer'] = ctree.find('img[src*="credits_small.gif"]').last().next().children('b').html().replace(/^(\S*) .*$/, '$1').replace(/[^\d]/g, '');
					
					// Gesamt-FP
					data = ctree.find('img[src*="research_small.gif"]').next().children('b').html();
					// Forscherdrang
					if(data.indexOf('+') != -1) {
						data = data.split('+');
						data[0] = data[0].replace(/[^\d]/g, '');
						data[1] = data[1].replace(/[^\d]/g, '');
						out['fpges'] = parseInt(data[0]) + parseInt(data[1]);
					}
					else {
						out['fpges'] = data.replace(/[^\d]/g, '');
					}
				}
				catch(e) {
					throw 'Konnte Einnahmen und Gesamtforschung nicht ermitteln!'+e;
				}
			}
			
			// Schiffbau
			if(ctree.find('table[width="821"] td:last-child img[src*="ships"]').length) {
				out['schiff'] = ctree.find('input[name="bauzeit"]').val();
			}
		}
		//
		// Planetenübersicht
		//
		else if(ctree.find('a[href="?op=planlist&order=name"]').length) {
			out['typ'] = 'poview';
			
			// Validität überprüfen
			if(!ctree.find('tr[pname]').first().is('tr[pname][pslots][perz][pwolfram][pkristall][pflour][pverz][pvmetall][pvwolfram][pvkristall][pvflour][pgrafik]')) {
				throw 'Planetenübersicht invalid!';
			}
			
			// Inhaber
			try {
				out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
			}
			catch(e) {
				throw 'Konnte Inhaber nicht ermitteln!';
			}
			
			// Planeten auswerten
			out['pl'] = [];
			
			var geb;
			var pl;
			var data2;
			var data3;
			
			// Planetenzeilen durchgehen
			ctree.find('tr[pname]').each(function(index) {
				pl = {};
				
				try {
					// Planeten-ID und System-ID
					pl['id'] = $(this).find('a[href^="?op=planet&index"]').attr('href').replace(/[^\d]/g, '');
					pl['sid'] = $(this).find('a[href^="?op=system&sys"]').attr('href').replace(/[^\d]/g, '');
					
					// Name und Werte
					pl['name'] = $(this).attr('pname');
					pl['gr'] = $(this).attr('pslots');
					
					pl['rw'] = [
						$(this).attr('perz'),
						$(this).attr('pwolfram'),
						$(this).attr('pkristall'),
						$(this).attr('pflour')
					].join('X');
					
					pl['rv'] = [
						$(this).attr('pverz').replace(/[^\d]/g, ''),
						$(this).attr('pvmetall').replace(/[^\d]/g, ''),
						$(this).attr('pvwolfram').replace(/[^\d]/g, ''),
						$(this).attr('pvkristall').replace(/[^\d]/g, ''),
						$(this).attr('pvflour').replace(/[^\d]/g, '')
					].join('X');
					
					// Oberfläche (trafficoptimiert)
					pl['scr'] = $(this).attr('pgrafik');
					pl['scr'] = pl['scr'].replace(/^.*planet(\d*)_s\.jpg/, 'typ=$1');
					pl['scr'] = pl['scr'].replace(/&s/g, 'X');
					pl['scr'] = pl['scr'].replace(/=/g, 'Y');
					
					// Schiff in der Werft
					if($(this).find('img[src*="ships"]').length) {
						pl['schiff'] = $(this).find('td[remtimea]').attr('remtimea');
					}
					
					// Aktion(en) am Planeten
					if($(this).children('td:last-child').html() != '') {
						pl['inva'] = $(this).children('td:last-child').html();
						pl['inva'] = pl['inva'].replace(/(<strong>|<\/strong>|<font color="#[0F]+">|<\/font>)/g, '');
					}
				}
				catch(e) {
					throw 'Fehler beim Einscannen der Planetenübersicht!';
				}
				
				// ans Planeten-Array hängen
				out['pl'].push(pl);
			});
		}
		//
		// Orbit
		//
		else if(ctree.find('#jumpDialog').length) {
			out['typ'] = 'orbit';
			
			var data;
			
			// ID
			try {
				out['id'] = ctree.find('a[href*="&typ=e"]').attr('href').replace(/^.*=(\d+)&typ.*$/, '$1');
			}
			catch(e) {
				throw 'Konnte ID nicht ermittel!';
			}
			
			// Inhaber
			try {
				// hat bekannten Inhaber
				if(ctree.find('form[name="gotoplan"] a[href*="op=usershow"]').length) {
					out['inhaber'] = ctree.find('form[name="gotoplan"] a[href*="op=usershow"]').attr('href').replace(/^.*welch=(\d+)$/, '$1');
				}
				else {
					data = ctree.find('form[name="gotoplan"] tr:first-child font').html();
					
					// verschleierte Altrasse
					if(data.indexOf('Altrasse') != -1) {
						out['inhaber'] = -3;
					}
					// verschleierter Lux
					else if(data.indexOf('Lux') != -1) {
						out['inhaber'] = -2;
					}
					// frei
					else {
						out['inhaber'] = 0;
					}
				}
			}
			catch(e) {
				throw 'Konnte Inhaber nicht ermitteln!';
			}
			
			// fremde Schiffe
			out['frs'] = ctree.find('a[href*="typ=f"]').parent().clone().children('a').remove().end().html();
			
			// DOM in Plaintext umwandeln leider noch nötig
			if(input == false) {
				var input = ctree.html();
			}
			
			// Umod
			if(input.indexOf('sich gerade einen Urlaub. Der Planet ist nicht einnehmbar!') != -1 || input.indexOf('treating himself a holiday') != -1) {
				out['umod'] = 1;
			}
			
			// Besatzung
			if(input.indexOf('<b>Dieser Planet ist gerade besetzt!') != -1 || input.indexOf('<b>Dieser Planet wird gerade besetzt!') != -1 || input.indexOf('<b>This planet is occupied at present!') != -1) {
				
				out['bes'] = 1;
				
				// Besatzer
				// TODO Grafikpfad wahrscheinlich falsch
				data = ctree.find('img[src*="warn.gif"]').parent().next().find('a[href*="usershow"]');
				if(data.length) {
					out['besuser'] = data.attr('href').replace(/[^\d]/g, '');
				}
			}
			
			// Ende-Pattern für Kolos und Invas
			var p = /(?:bernahmezeitpunkt|Completion date): (?:.{3}), ([^<]+)<br/;
			
			// Kolo
			if(input.indexOf('<b>Dieser Planet wird gerade kolonisiert.') != -1  || input.indexOf('<b>This planet is currently being colonised') != -1) {
				
				out['kolo'] = 1;
				
				// Ende
				data = p.exec(input);
				if(data == null) {
					throw 'Konnte Kolodaten nicht ermitteln!';
				}
				
				out['koloende'] = data[1];
				
				// Kolonisator
				data = ctree.find('div[name="alldiv"] a[href*="usershow"]');
				if(data.length) {
					out['kolouser'] = data.attr('href').replace(/[^\d]/g, '');
				}
			}
			
			// Inva
			if(input.indexOf('<b>Dieser Planet wird gerade invadiert!') != -1 || input.indexOf('<b>This planet is currently being invaded') != -1) {
				
				out['inv'] = 1;
				
				data = p.exec(input);
				if(data == null) {
					throw 'Konnte Invadaten nicht ermitteln!';
				}
				
				out['invende'] = data[1];
				
				// Aggressor
				data = ctree.find('div[name="alldiv"] a[href*="usershow"]');
				if(data.length) {
					out['invauser'] = data.attr('href').replace(/[^\d]/g, '');
				}
			}
			
			// Genesis
			if(input.indexOf('An diesem Planeten wird ein Genesis-Projekt gestartet.') != -1 || input.indexOf('At this planet a genesis project is initiated.') != -1) {
				
				out['gen'] = 1;
				
				p = /(?:ndung|Firing): (?:.{3}), ([^<]+)<br/;
				
				data = p.exec(input);
				if(data == null) {
					throw 'Konnte Genesis-Daten nicht ermitteln!';
				}
				
				out['genende'] = data[1];
				
				// Genesis-Benutzer
				var genuser = ctree.find('.box td:contains("enesis") a[href^="?op=usershow"]'); 
				if(genuser.length) {
					out['genuser'] = genuser.attr('href').replace(/[^\d]/g, '');
				}
			}
			
			// Reso / Todesstern
			if(input.indexOf('sich gerade ein Resonator auf, um diesen Planeten und alle Schiffe im Orbit zu vernichten!') != -1
				|| input.indexOf('<b>A resonator is currently charging up so that it can destroy this planet and all ships in orbit around it') != -1
				|| input.indexOf('sich gerade ein Schiff auf, um diesen Planeten zu vernichten!') != -1) {
				
				out['reso'] = 1;
				
				p = /(?:ndung|Firing): (?:.{3}), ([^<]+)</;
				data = p.exec(input);
				
				if(data == null) {
					throw 'Konnte Reso-Daten nicht ermitteln!';
				}
				
				out['resoende'] = data[1];
			}
			
			// Natives
			if(input.indexOf('>Achtung - In diesem Orbit stehen sehr starke Schiffe!') != -1) {
				out['natives'] = 1;
			}
			
			// Sprunggenerator
			if(input.indexOf('><b>Sprunggenerator aktiv</b></font>') != -1) {
				out['sprung'] = 1;
			}
			
			// Bergbau
			if(input.indexOf('>An diesem Planeten läuft ein Bergbauvorgang.<') != -1 || input.indexOf('>An diesem Planeten l&auml;uft ein Bergbauvorgang.<') != -1 || input.indexOf('>A mining operation is active.<') != -1) {
				out['bb'] = 1;
			}
			
			// Terraforming
			if(input.indexOf('>Dieser Planet wird mittels Terraforming verbessert.<') != -1) {
				out['tf'] = 1;
			}
		}
		//
		// Einstellungen
		//
		else if(ctree.find('form[action="?op=settings"]').length) {
			out['typ'] = 'einst';
			
			// Spieler
			try {
				out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
			}
			catch(e) {
				throw 'Konnte Spieler nicht ermitteln!';
			}
			
			// Format: AA-HandelNeutral-HandelAlly-HandelNAP-KS[1,2,3]-Steuern[1,2,3]
			
			// AA
			out['einst'] = '0';
			// HandelNeutral
			out['einst'] += ctree.find('#pn2:checked').length;
			// HandelAlly
			out['einst'] += ctree.find('#pv2:checked').length;
			// HandelNAP
			out['einst'] += ctree.find('#nap2:checked').length;
			// Kampfsystem
			if(ctree.find('#km1:checked').length) {
				out['einst'] += '1';
			}
			else if(ctree.find('#km2:checked').length) {
				out['einst'] += '2';
			}
			else {
				out['einst'] += '3';
			}
			// Steuern
			if(ctree.find('#sm1:checked').length) {
				out['einst'] += '1';
			}
			else if(ctree.find('#sm2:checked').length) {
				out['einst'] += '2';
			}
			else {
				out['einst'] += '3';
			}
		}
		//
		// Flottenübersicht - Steuern
		//
		else if(ctree.find('a.active[href*="tab=5"]').length) {
			out['typ'] = 'floview';
			
			// Spieler
			try {
				out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
			}
			catch(e) {
				throw 'Konnte Spieler nicht ermitteln!';
			}
			
			
			// zuletzt gezahlte Flottensteuer
			var data = $(ctree.children('.box')[5]).find('b');
			
			out['steuer'] = $(data[2]).html().replace(/[^\d+]/g, '');
			
			// Kommandopunkte und Schiffe
			var $kopRows = ctree.find('tr.quickjump:has(b)'),
				$kop = $kopRows.find('td:last-child'),
				$kopMax = $kopRows.next().find('td:last-child'),
				$shipCount = ctree.find('tr.tablecolor + tr:not(.tablecolor)').find('td:eq(1)');
			
			if($kopRows.length != 2) {
				throw 'Konnte Kommandopunkte nicht ermitteln!';
			}
			
			try {
				out['pkop'] = $kop.first().html().replace(/[^\d+]/g, '');
				out['kop'] = $kop.last().html().replace(/[^\d+]/g, '');
				out['pkopmax'] = $kopMax.first().html().replace(/[^\d+]/g, '');
				out['kopmax'] = $kopMax.last().html().replace(/[^\d+]/g, '');
			}
			catch(e) {
				throw 'Fehler beim Ermitteln der Kommandopunkte!';
			}
			
			try {
				out['schiffe'] = parseInt($shipCount.eq(0).html().replace(/[^\d+]/g, '')) + parseInt($shipCount.eq(1).html().replace(/[^\d+]/g, ''));
			}
			catch(e) {
				throw 'Konnte Anzahl der Schiffe nicht ermitteln!';
			}
			
		}
		//
		// Bergbauschiffe
		//
		else if(ctree.find('a.active[href*="tab=2"]').length) {
			out['typ'] = 'floviewbbs';
			
			// Spieler
			try {
				out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
			}
			catch(e) {
				throw 'Konnte Spieler nicht ermitteln!';
			}
			
			
			out['bb'] = [];
			
			try {
				ctree.find('.tabletrans td:nth-child(6) > a[href*=orbit]:first-child').each(function() {
					out['bb'].push($(this).attr('href').replace(/[^\d]/g, ''));
				});
			}
			catch(e) {
				throw 'Fehler beim Erfassen der Bergbauschiffe!';
			}
			
			out['bb'] = out['bb'].join('-');
		}
		//
		// Sitterliste
		//
		else if(ctree.find('a[href="?op=sitter&was=auftraege"]').length) {
			out['typ'] = 'sitter';
			
			// Spieler
			try {
				out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
			}
			catch(e) {
				throw 'Konnte Spieler nicht ermitteln!';
			}
			
			// Sitter von anderen
			var sfrom = [];
			ctree.find('a[href*="sitter&umloggen"]').each(function() {
				sfrom.push($.trim($(this).clone().children('font').remove().end().html()));
			});
			
			out['sitterfrom'] = sfrom;
			
			// Sitter zu anderen
			var sto = [];
			ctree.find('td:last-child > .box td[width]:first-child').each(function() {
				sto.push($.trim($(this).clone().children('font').remove().end().html()));
			});
			
			out['sitterto'] = sto;
		}
		//
		// Forschung
		//
		else if(ctree.find('a[href="?op=tech&tree=geb"]').length && ctree.find('img[src*="basiscamp.gif"], img[src*="titanid_s.gif"], img[src*="ion1.gif"]').length) {
			
			out['typ'] = 'forschung';
			
			// Spieler
			try {
				out['uid'] = tree.find('div.statusbar a.user').attr('href').replace(/^.*=(\d+)$/, '$1');
			}
			catch(e) {
				throw 'Konnte Spieler nicht ermitteln!';
			}
			
			out['f'] = [];
			out['fn'] = [];
			out['ff'] = [];
			out['kategorie'] = 0;
			
			var kategorien = {
				1: 'basiscamp.gif',
				2: 'titanid_s.gif',
				3: 'ion1.gif'
			};
			
			ctree.find('img[titel]').each(function() {
				
				var $this = $(this),
					path = $this.attr('src');
				
				// Lokale Grafikpakete abfangen
				if(path.indexOf('/static/img/') == -1) {
					throw 'Grafikpfade ungültig!';
				}
				
				if(out['kategorie'] == 0) {
					for(var i in kategorien) {
						if(path.indexOf(kategorien[i]) != -1) {
							out['kategorie'] = i;
							break;
						}
					}
				}
				
				out['f'].push(path.replace(/^.*\/img\//, ''));
				out['fn'].push($this.attr('titel'));
				out['ff'].push($this.hasClass('opacity2') ? 1 : 0);
			});
			
			if(!out['f'].length) {
				throw 'Konnte Forschungen nicht ermitteln!';
			}
			
			// laufende Forschung
			var current = ctree.find('.tabletranslight .box td:first-child img');
			
			if(current.length) {
				out['current'] = current.attr('src').replace(/^.*\/img\//, '');
				out['current_end'] = ctree.find('#returntim').siblings('b').html();
			}
			
		}
		//
		// Unbekannter Quelltext
		//
		else {
			r.append("Quelltext unbekannt!");
			return false;
		}
	}
	// Fehlerbehandlung
	catch(e) {
		r.append(e);
		return false;
	}
	
	
	// Inputfeld leeren
	f.input.value = '';
	
	// Daten senden
	ajaxcall('index.php?p=scan&sp=scan&force', r, out, true);
}

/**
 * Mainscreen einscannen -> Galaxie eintragen
 * @param f DOM Formular
 * @param r DOM Ausgabe-Element
 *
 * @return false Abbruch bei Fehler
 */
function quelltext_mainscreen(f, r) {
	
	var input = f.input.value;
	
	// Result-Element leeren
	r.html('');
	
	// Ausgabe-Objekt definieren
	var out = {};
	
	// Verschmelzungen reparieren
	if(typeof(f.elements['repair']) != 'undefined' && f.elements['repair'].checked == true) {
		out['repair'] = 1;
	}
	
	// mehrfach kopierten Quelltext entfernen
	input = input.replace(/<\/html>(.*)$/, '</html>');
	
	// Bug (Chrome): fehlendes <
	input = input.replace(/\std width=/g, '<td width=');
	
	
	var tree = $($.parseHTML(input)),
		ctree = tree.find('#layout-main'),
		link_all = ctree.find("a[href*='op=main&order=id&first=0&last=5000&galax=']");
	
	if(link_all.length) {
		
		try {
			// Galaxie ermitteln
			out['gala'] = link_all.attr('href').replace(/^.+&galax=/, '');
			
			// Anzahl Systeme
			var systems = link_all.parent().html().match(/^[a-zA-Z\s]+:\s*(\d+)\s*</)[1];
			
			// Systemdaten ermitteln
			var data = [];
			
			var sys_data = ctree.find('.tabletrans-fleet, .tabletranslight-fleet');
			
			if($(sys_data[0]).children().length != 7 || $(sys_data[0]).find('a').length != 1) {
				throw 'Mainscreen-Quelltext ungültig!';
			}
			
			if(sys_data.length != systems) {
				r.html('<div class="error">Der Mainscreen ist nicht vollst&auml;ndig!</div>');
				return false;
			}
			
			sys_data.each(function() {
				var $this = $(this),
					$children = $this.children(),
					sys = [];
				
				data.push([
				     $this.find('a').attr('href').replace(/^.+&sys=(\d+)&.+$/, '$1'),
				     $this.find('a').text(),
				     $children.eq(3).text(),
				     $children.eq(4).text(),
				     $children.eq(5).text()
				]);
			});
			
			out['s'] = data;
		}
		// Fehler
		catch(e) {
			alert(e);
			return false;
		}
	}
	// unbekannter Quelltext
	else {
		r.html('<div class="error">unbekannter Quelltext!</div>');
		return false;
	}
	
	// Inputfeld leeren
	f.input.value = '';
	
	// Workaround PHP max_input_vars
	out = {'data' : $.param(out)};
	
	// Daten senden
	ajaxcall('index.php?p=admin&sp=galaxien_parse', r, out, true);
}


// Planet anzeigen

/**
 * Planeten-Kommentar edititieren
 * @param id int Planeten-ID
 * @param el DOM Element des Kommentar-Containers
 * @param edit bool ist schon ein Kommentar eingetragen?
 */
function kommentar_edit(id, el, edit) {
	var k = '';
	var label = 'hinzuf&uuml;gen';
	// alten Kommentar auslesen
	if(edit) {
		k = $(el).find('.kommentarc').html();
		k = k.replace(/<br \/>(?:\n*)/gi, '\n');
		k = k.replace(/<br>(?:\n*)/gi, '\n');
		if(edit) label = 'bearbeiten';
	}
	
	// Ausgabe
	$(el).html('<form action="#" onsubmit="return form_send(this, \'index.php?p=show_planet&id='+id+'&sp=kommentar&ajax\', $(this.parentNode))"><div class="kommentar" style="float:left"></div> &nbsp; &nbsp; Kommentar '+label+'<div style="width:80%;text-align:center;margin-top:5px"><textarea name="kommentar" style="width:100%;height:40px">'+k+'</textarea><br /><input type="submit" class="button" style="margin-top:3px" value="abschicken" /></div></form>');
	$(el).find('textarea').select();
}

// Invasionen
function openinvas() {
	var addr = 'index.php?p=ajax_general&sp=openinvas&ajax';
	// Request absetzen
	$.ajax({
		type: 'get',
		url: addr,
		success: function(data){
			// Content ermitteln und an Update-Funktion übergeben
			var content = $(data).find('content').text();
			openinvas_update(content);
		},
		error: function(e, msg) {
			if(e.status != 0) {
				// Fehlermeldung
				var content = e.responseText.replace(/<\?xml([.\s]*)$/, '');
				content = content.replace(/<br \/>/g, "\n");
				content = content.replace(/<(|\/)b>/g, '');
				alert("Es ist ein Fehler aufgetreten!\n\nFehlermeldung: "+msg+' '+e.status+"\n\nAdresse: "+addr+"\n\n"+content);
			}
		}
	});
}

/**
 * Anzeige der offenen Invasionen ändern
 * @param invas int Anzahl der Invasionen
 */
function openinvas_update(invas) {
	// Beschriftung ändern
	var out = invas+' offene Aktion';
	if(invas != 1) out += 'en';
	out += '!';
	
	$('#headerinva a').html(out);
	
	// Sichtbarkeit ändern
	if(invas > 0) {
		$('#headerinva').show(200);
	}
	else {
		$('#headerinva').hide(200);
	}
}

/**
 * Inva-Kommentar edititieren
 * @param id int Inva-ID
 * @param el DOM Element des Kommentar-Containers
 * @param edit bool ist schon ein Kommentar eingetragen?
 */
function invakommentar_edit(id, el, edit) {
	var k = '';
	var label = 'hinzuf&uuml;gen';
	// alten Kommentar auslesen
	if(edit) {
		k = $(el).find('.kommentarc').html();
		k = k.replace(/<br \/>(?:\n*)/gi, '\n');
		k = k.replace(/<br>(?:\n*)/gi, '\n');
		if(edit) label = 'bearbeiten';
	}
	
	// Ausgabe
	$(el).html('<form action="#" onsubmit="return form_send(this, \'index.php?p=inva&sp=inva_kommentar&id='+id+'&ajax\', $(this.parentNode))"><div class="kommentar" style="float:left"></div> &nbsp; &nbsp; Kommentar '+label+'<div style="width:80%;text-align:center;margin-top:5px"><textarea name="kommentar" style="width:100%;height:40px">'+k+'</textarea><br /><input type="submit" class="button" style="margin-top:3px" value="abschicken" /></div></form>');
	$(el).find('textarea').select();
}

/**
 * Suchnavigation erzeugen (Planetenansicht)
 * @param id int Planeten-ID
 * @param search int Timestamp der Suche / Identifier der Container
 * @param t int Timestamp des Fensters
 */
function searchnav(id, search, t) {
	var vals = $('#snav'+search).val();
	var search2 = search+'-'+id+'-'+t;
	
	// Suche nicht mehr verfügbar
	if(vals == null) {
		$('#snavbox'+search2).slideUp(200);
	}
	// Suchergebnisse analysieren
	else {
		var vals = vals.split('-');
		// Position des aktuellen Planeten ermitteln
		var pos = -1;
		for(var i in vals) {
			if(vals[i] == id) {
				pos = parseInt(i);
			}
		}
		// ID nicht in den Ergebnissen (umgeblättert?) oder nur 1 Treffer
		if(pos == -1) {
			$('#snavbox'+search2).slideUp(200);
		}
		// nur 1 Treffer
		else if(vals.length < 2) {
			$('#snavbox'+search2).hide();
		}
		else {
			var content = '<table border="0" style="width:100%"><tr><td style="width:35%;text-align:left;font-weight:bold">';
			// vorheriger Planet
			if(pos > 0) {
				content += '<a class="link contextmenu" link="index.php?p=show_planet&id='+vals[pos-1]+'&nav='+search+'">&laquo; vorheriger Planet ('+vals[pos-1]+')</a>';
			}
			content += '</td><td style="width:30%;text-align:center">Planeten-Navigation</td><td style="width:35%;text-align:right;font-weight:bold">';
			// nächster Planet
			if(pos < vals.length-1) {
				content += '<a class="link contextmenu" link="index.php?p=show_planet&id='+vals[pos+1]+'&nav='+search+'">n&auml;chster Planet ('+vals[pos+1]+') &raquo;</a>';
			}
			content += '</td></tr></table>';
			// Content in die Navibox füllen
			$('#snavbox'+search2).html(content);
		}
	}
}

/**
 * System-Navigation erzeugen (Systemansicht)
 * @param id int System-ID
 * @param search int Timestamp der Suche / Identifier der Container
 * @param t int Timestamp des Fensters
 */
function systemnav(id, search, t) {
	var vals = $('#sysnav'+search).val();
	var search2 = search+'-'+id+'-'+t;
	
	// Suche nicht mehr verfügbar
	if(vals == null) {
		$('#sysnavbox'+search2).slideUp(200);
	}
	// Suchergebnisse analysieren
	else {
		var vals = vals.split('-');
		// Position des aktuellen Planeten ermitteln
		var pos = -1;
		for(var i in vals) {
			if(vals[i] == id) {
				pos = parseInt(i);
			}
		}
		// ID nicht in den Ergebnissen (umgeblättert?) oder nur 1 Treffer
		if(pos == -1) {
			$('#sysnavbox'+search2).slideUp(200);
		}
		// nur 1 Treffer
		else if(vals.length < 2) {
			$('#sysnavbox'+search2).hide();
		}
		else {
			var content = '<table border="0" style="width:100%"><tr><td style="width:35%;text-align:left;font-weight:bold">';
			// vorheriger Planet
			if(pos > 0) {
				content += '<a class="link contextmenu" link="index.php?p=show_system&id='+vals[pos-1]+'&nav='+search+'">&laquo; vorheriges System ('+vals[pos-1]+')</a>';
			}
			content += '</td><td style="width:30%;text-align:center">System-Navigation</td><td style="width:35%;text-align:right;font-weight:bold">';
			// nächster Planet
			if(pos < vals.length-1) {
				content += '<a class="link contextmenu" link="index.php?p=show_system&id='+vals[pos+1]+'&nav='+search+'">n&auml;chstes System ('+vals[pos+1]+') &raquo;</a>';
			}
			content += '</td></tr></table>';
			// Content in die Navibox füllen
			$('#sysnavbox'+search2).html(content);
		}
	}
}

/**
 * Userberechtigungen einzeln anpassen
 * Klick auf einen Radiobutton -> Farbe der Zelle verändern
 */
function rechte_click(el) {
	var val = $(el).val();
	var td = $(el.parentNode).siblings('td:last-child');
	
	// vom Rechtelevel abhängig; evtl Transparenz
	if(val == -1) {
		$(td).css('color', '#ffffff');
		if($(td).hasClass('rechtedisabled')) {
			$(td).css('opacity', '0.4');
		}
	}
	else {
		// Transparenz entfernen
		$(td).css('opacity', '1');
		
		// Berechtigung erlaubt
		if(val == 1) {
			$(td).css('color', '#00aa00');
		}
		// Berechtigung gesperrt
		else {
			$(td).css('color', '#ff3322');
		}
	}
}

/**
 * Allianzstatus ändern -> Tabellenzeile verschieben
 * @param id int Allianz-ID
 * @param status int neuer Allianzstatus
 */
function allianz_status(id, status) {
	// Inhalt kopieren
	var c = '<tr class="allianzrow'+id+'"';
	// ausgegraut
	if($('.allianzrow'+id).css('opacity') == '0.4') {
		c += ' style="opacity:0.4"';
	}
	c += '>'+$('.allianzrow'+id).html()+'</tr>';
	// Zeile löschen
	$('.allianzrow'+id).remove();
	// neue Zeile einfügen
	$('.allianzstatus'+status).append(c);
	// "keine"-Zeile löschen
	$('.allianzstatus'+status+' .allianzenkeine').remove();
	// Select-Wert ändern
	$('.allianzrow'+id+' select').val(status);
}

/**
 * Allianzübersicht nach Tag und Namen filtern
 * @param el DOM parent-Element
 * @param val string Suchstring
 */
function allianz_filter(el, val) {
	// htmlspecialchars
	val = val.replace(/&/g, '&amp;');
	val = val.replace(/"/g, '&quot;');
	val = val.replace(/</g, '&lt;');
	val = val.replace(/>/g, '&gt;');
	
	// RegExp bauen
	val = val.replace(/\\/g, '\\\\');
	val = val.replace(/\(/g, '\\(');
	val = val.replace(/\)/g, '\\)');
	val = val.replace(/\[/g, '\\[');
	val = val.replace(/\]/g, '\\]');
	var p = new RegExp(val, 'i');
	
	$(el).find('tr').each(function() {
		// die headlines und "keine"-Zeilen nicht durchsuchen
		if(this.className != 'allianzenheadline' && this.className != 'allianzenkeine') {
			var v1 = $(this).find('td:nth-child(2) a').html();
			var v2 = $(this).find('td:nth-child(3) a').html();
			
			// anzeigen
			if(val == '' || v1.search(p) != -1 || v2.search(p) != -1) {
				$(this).show();
			}
			// ausblenden
			else {
				$(this).hide();
			}
		}
	});
}

/**
 * Myrigateliste nach Galaxie filtern
 * @param el DOM parent-Element
 * @param val int Galaxie
 */
function mgate_filter(el, val) {
	// alle enzeigen
	if(val == '') {
		$(el).find('.filter').show();
	}
	// filtern
	else {
		$(el).find('.filter[name!=gala'+val+']').hide();
		$(el).find('.filter[name=gala'+val+']').show();
	}
}

/**
 * Systeme oder Planeten scouten -> Startposition ändern
 * @param id int neuer Startpunkt
 * @param el DOM Button-Objekt
 */
 function scout_weiter(id, el) {
	var form = $(el).parents('.icontent').find('form');
	$(form).find('input[name=start]').val(id);
	form.trigger('onsubmit');
 }
 
 /**
  * Marker einer Route setzen
  */
 function route_marker(el) {
	// Marker entfernen
	if($(el).parents('tr').hasClass('trhighlight')) {
		$(el).parents('tr').removeClass('trhighlight');
		
		ajaxcall('index.php?p=route&sp=setmarker&id='+$(el).parents('table').attr('name')+'&marker=0&ajax', false, false, false);
	}
	// Marker setzen
	else {
		// Highlight entfernen
		$(el).parents('table').find('tr').removeClass('trhighlight');
		// neues Highlight setzen
		$(el).parents('tr').addClass('trhighlight');
		
		ajaxcall('index.php?p=route&sp=setmarker&id='+$(el).parents('table').attr('name')+'&marker='+$(el).parents('td').attr('name')+'&ajax', false, false, false);
	}
}


/**
 * Funktionen für die Werft-Seite
 */
var werftPage = {
	
	/**
	 * Werft-Bedarf aus einer Schnellauswahl-Liste übernehmen
	 * @param el DOM des <select>-Elements
	 */
	setBedarf : function(el)  {
		
		var $el = $(el),
			val = $el.val().split('-'),
			$form = $el.parent().find('form'),
			
			inputNames = ['erz', 'metall', 'wolfram', 'kristall', 'fluor'];
		
		if(val.length != 5) {
			return;
		}
		
		for(var i in inputNames) {
			$form.find('input[name="' + inputNames[i] + '"]').val(val[i]);
		}
		
	}
	
};
 
/**
 * Funktionen für die Einstellungen-Seite
 */
var settingsPage = {
	
	/**
	 * Eintrag zu den FoW-Suchfilter-Einstellungen hinzufügen
	 * @param el Parent-Element der Suchfilter
	 */
	addFoWSearch : function(el) {
		
		// ID des neuen Containers generieren
		el.data('max', el.data('max')+1);
		var id = el.data('timestamp')+el.data('max');
		
		el.append('<div id="'+id+'"><div class="closebutton" title="Eintrag l&ouml;schen" onclick="$(this.parentNode).remove()"></div><div class="fowsearch1">Name: <input type="text" class="text" name="sname[]" value="" /><br />die <input type="text" class="text smalltext" style="width:30px" name="scount[]" value="1" /> <select name="sorder[]" size="1"><option value="0">n&auml;chsten</option><option value="1">entferntesten</option></select> Treffer<br /><select name="sout[]"><option value="1">nur au&szlig;erhalb des Systems</option><option value="0">alle Planeten finden</option></select></div><div class="fowsearch3"><a onclick="page_load(5, \'FoW-Suchfilter konfigurieren\', \'index.php?p=settings&amp;sp=fow_editsearch&amp;target='+id+'\', false, {filter : $(this.parentNode.parentNode).find(\'input[type=hidden]\').val()})"><img src="img/layout/leer.gif" class="icon hoverbutton configbutton" title="konfigurieren" /> </a></div><div class="fowsearch2"><a onclick="page_load(5, \'FoW-Suchfilter konfigurieren\', \'index.php?p=settings&amp;sp=fow_editsearch&amp;target='+id+'\', false, {filter : $(this.parentNode.parentNode).find(\'input[type=hidden]\').val()})"><img src="img/layout/leer.gif" class="icon hoverbutton configbutton" title="konfigurieren" /> <i>noch nicht konfiguriert</i></a></div><input type="hidden" name="sval[]" value="" /></div>');
		
	},
	
	/**
	 * Suchfilter-Einstellung abschicken
	 * @param form DOM Formular
	 * @param target string ID des Filter-Containers
	 */
	editFoWSearch : function(form, target) {
		
		// Such-String generieren
		var val = $(form).find('input').filter(function(index) {
			return (this.value != '');
		}).serialize(),
			val2 = $(form).find('select').filter(function(index) {
			return (this.value != '');
		}).serialize();
		
		if(val2 != '') {
			val += '&'+val2;
		}
		
		$('#'+target+' input[type=hidden]').val(val);
		
		// Beschreibung laden
		ajaxcall('index.php?p=settings&sp=fow_searchdesc', $('#'+target+' .fowsearch2'), {filter : val}, true);
		
		// Fenster schließen
		parentwin_close(form);
		
		return false;
	}
	
};

/**
 * Funktionen für die Tools-Seite
 */
var toolsPage = {
	
	/**
	 * API-Key ändern mit vorheriger Bestätigung
	 */
	changeApiKey : function() {
		
		if(window.confirm("Möchtest du deinen API-Key wirklich ändern?")) {
			ajaxcall('index.php?p=tools&sp=apikey', false, false, false)
		}
		
	}	
	
};

/**
 * Funktionen für die Forschungs-Seite
 */
var forschungPage = {
	
	/**
	 * Forschungs-Filter anwenden
	 * @param el DOM Icon, auf das geklickt wurde
	 */
	filter: function(el) {
		
		var $container = $(el).parents('.forschung_container'),
			$filterIcons = $container.find('.icon_forschung_filter_active'),
			$rows = $container.find('tr:gt(0)'),
			filter = [];
		
		$filterIcons.each(function() {
			filter.push($(this).data('forschung'));
		});
		
		$rows.removeClass('forschung_hidden');
		
		$rows.each(function() {
			
			var $this = $(this);
			
			for(var i in filter) {
				if(!$this.find('img[data-forschung="' + filter[i] + '"]').length) {
					$this.addClass('forschung_hidden');
					return;
				}
			}
		});
	}
	
};

/**
 * Funktionen für den Verwaltungs-Bereich
 */
var adminPage = {
	
	/**
	 * DB-Exportformular abschicken
	 * @param {DOM} form
	 */
	backupExport: function(form) {
		document.location.href = 'index.php?p=admin&sp=backup_export&' + $(form).serialize();
		return false;
	}
	
}

/**
 * Touch-Event-Abstraktionen
 * - touchmousedown: wird nach 200ms ohne Bewegung gefeuert
 */
var contextmenuTriggered = false;

(function() {
	var timeoutMousedown = false,
		timeoutContextmenu = false,
		target = false;
	
	$(document).on('touchstart', function(e) {
		target = e.target;
		
		timeoutMousedown = window.setTimeout(function() {
			$(target).trigger('touchmousedown');
			timeoutMousedown = false;
		}, 200);
		
		timeoutContextmenu = window.setTimeout(function() {
			timeoutContextmenu = false;
			contextmenuTriggered = true;
			
			$(target).trigger('touchcontextmenu');
		}, 600);
		
	}).on('touchmove', function() {
		if(timeoutMousedown) {
			window.clearTimeout(timeoutMousedown);
			timeoutMousedown = false;
		}
		if(timeoutContextmenu) {
			window.clearTimeout(timeoutContextmenu);
			timeoutContextmenu = false;
		}
	}).on('touchend', function(e) {
		if(timeoutContextmenu) {
			window.clearTimeout(timeoutContextmenu);
			timeoutContextmenu = false;
		}
		
		if(contextmenuTriggered) {
			
			window.setTimeout(function() {
				contextmenuTriggered = false;
			}, 400);
			
		}
		
	});
	
})();


var formHelpers = {
	toggleElement: function(el, toggle) {
		if(toggle) {
			el.show().focus();
		}
		else {
			el.hide();
		}
	}
};
