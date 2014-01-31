oddbtool.fow = function(page, data) {
	// System-ID ermitteln
	var url = page.location;
	var p = /system&sys=(\d+)/;
	var id = p.exec(url);
	if(id == null) {
		id = $('#sysid input[name="sys"]',page).val();
	}
	else id = id[1];
	
	
	var allyids = [];
	var uids = [];
	var plcount = 0;
	
	// Daten mit den Usernamen erweitern
	if(data && data['typ'] == 'system') {
		var input = oddbtool.getQuelltext(page);
		for(var i=0;i<=6;i++) {
			// Zähler erhöhen
			if(typeof(data['pl'][i]) == 'object') {
				plcount++;
			}
			// Planet existiert und hat Inhaber
			if(typeof(data['pl'][i]) == 'object' && data['pl'][i]['inhaber'] > 0) {
				
				p = 'setter\\(\\\'(.+)\\\',[\\r\\n\\s]*\\\''+data['pl'][i]['inhaber']+'\\\',\\\'(.*)\\\',\\\'(?:.*)\\\',\\\'(?:.*)\\\'\\);';
				
				p = new RegExp(p);
				var data2 = p.exec(input);
				if(data2 != null) {
					// HTML dekodieren und komische Symbole anzeigbar machen
					data['pl'][i]['username'] = oddbtool.str_replace(oddbtool.charmap_search, oddbtool.charmap_replace, data2[1]);
					data['pl'][i]['allytag'] = oddbtool.str_replace(oddbtool.charmap_search, oddbtool.charmap_replace, data2[2]);
					
					
					
					// AllyID an das Abfrage-Array anhängen
					if(data['pl'][i]['allianz'] != '' && !oddbtool.in_array(data['pl'][i]['allianz'], allyids)) {
						allyids.push(data['pl'][i]['allianz']);
					}
					
					// SpielerID an das Abfrage-Array anhängen
					if(!oddbtool.in_array(data['pl'][i]['inhaber'], uids)) {
						uids.push(data['pl'][i]['inhaber']);
					}
				}
			}
			// kein Inhaber oder verschleiert
			else if(typeof(data['pl'][i]) == 'object') {
				data['pl'][i]['allytag'] = '';
				
				// frei
				if(data['pl'][i]['inhaber'] == 0) {
					data['pl'][i]['username'] = '- keiner -';
				}
				// Lux
				else if(data['pl'][i]['inhaber'] == -2) {
					data['pl'][i]['username'] = 'Seze Lux';
				}
				// Altrasse
				else if(data['pl'][i]['inhaber'] == -3) {
					data['pl'][i]['username'] = 'Altrasse';
				}
			}
		}
	}
	// bei unbekanntem System Planetenzähler auf 1 setzen
	else if(data && data['typ'] == 'sysun') {
		plcount = 1;
	}
	
	// Debug-Ausgabe
	//$('#oddbtoolwin',page).append('<br><br>'+decodeURIComponent(jQuery.param(data)));
	
	
	// Adresse erzeugen
	var addr = oddbtool.prefs.url+'index.php?p=fow&oddb&version='+oddbtool.version+'&id='+id+'&plcount='+plcount;
	// AllyID-Abfrage-Array anhängen
	if(allyids.length) {
		addr += '&status='+encodeURIComponent(allyids.join('+'));
	}
	// UserID-Abfrage-Array anhängen
	if(uids.length) {
		addr += '&umod='+encodeURIComponent(uids.join('+'));
	}
	// Account-Allianz
	var accally2 = $('.statusbar .alliance',page).attr('href');
	if(accally2 != null) {
		accally2 = accally2.replace(/[^\d]/g, '');
		addr += '&ally='+accally2;
	}
	
	// Meldung anzeigen
	$('#oddbtoolwin',page).append('<span id="oddbtoolfow"><a href="'+addr+'" target="_blank">Rufe Systemdaten ab</a>... </span><br>');
	
	if(!data) {
		$('#oddbtoolfow',page).append('<span style="color:red">fehlgeschlagen! (unbekannter Quelltext)</span>');
		return false;
	}
	
	// iFrame erzeugen
	$('body',page).append('<iframe id="oddbtooliframe"></iframe>');
	
	// normale Inhaber-Anzeige ausblenden
	$('#anzeigerd',page).hide();
	
	// Request absenden
	jQuery.ajax({
		type: 'get',
		url: addr,
		data: false,
		success: function(result){
			// Fehler ausgeben
			var error = $(result).find('error').text();
			if(error) {
				// Loginfehler ändern
				if(error.indexOf('mehr eingeloggt') != -1) error = 'nicht eingeloggt!';
				
				// ausgeben
				$('#oddbtoolfow',page).append('<span style="color:red">'+error+'</span>');
				return false;
			}
			// Authorisierung
			var auth = $(result).find('auth').text();
			if(auth == 'false') {
				$('#oddbtoolfow',page).append('<span style="color:red">nicht eingeloggt!</span>');
				return false;
			}
			
			// erfolgreich-Meldung
			$('#oddbtoolfow',page).append('<span style="color:#00ff00">erfolgreich!</span>');
			
			// Tabellenbreite richtig setzen
			$('#layout-main table[width=800] td[valign=top] table[width=100]',page).css('width', '110px');
			
			
			// Positionen
			var xbase = 304;
			var ybase = 263;
			var xadd = 110;
			var yadd = 17;
			
			var x = 0;
			var y = 0;
			var content = '';
			var content2 = '';
			
			// Berechtigung zum Toxxen und Raiden
			var toxxrechte = $(result).find('toxxrechte').text();
			
			// Daten abgleichen
			for(var i=0;i<=6;i++) {
				if(typeof(data['pl'][i]) == 'object') {
					var pl = $(result).find('planet[pid='+data['pl'][i]['id']+']');
					// Planet in DB -> Informationen erweitern
					if($(pl).text() != '') {
						var sc = $(pl).find('scanDate');
						data['pl'][i]['scan'] = '<span style="color:';
						if($(sc).attr('current') == 1) data['pl'][i]['scan'] += '#00aa00';
						else data['pl'][i]['scan'] += '#ff3322';
						data['pl'][i]['scan'] += '">Scan: '+$(sc).text()+'</span>';
						
						data['pl'][i]['updateoverview'] = $(pl).find('scanOview').text();
						data['pl'][i]['unscannbar'] = $(pl).find('scanFailed').text();
						data['pl'][i]['gpl'] = $(pl).find('gebplanet').text();
						data['pl'][i]['gor'] = $(pl).find('geborbit').text();
						data['pl'][i]['orbiter'] = $(pl).find('orbiter').text();
						data['pl'][i]['erzmenge'] = $(pl).find('erzmenge').text();
						data['pl'][i]['metallmenge'] = $(pl).find('metallmenge').text();
						data['pl'][i]['wolframmenge'] = $(pl).find('wolframmenge').text();
						data['pl'][i]['kristallmenge'] = $(pl).find('kristallmenge').text();
						data['pl'][i]['fluormenge'] = $(pl).find('fluormenge').text();
						data['pl'][i]['additional'] = $(pl).find('additional').text();
						data['pl'][i]['additional2'] = $(pl).find('additional2').text();
						data['pl'][i]['additional3'] = $(pl).find('additional3').text();
						data['pl'][i]['comment'] = $(pl).find('comment').text();
						
						// wenn System unsichtbar, mehr Informationen holen
						if(data['typ'] == 'sysun') {
							// Resswerte und Bevölkerung
							data['pl'][i]['erz'] = $(pl).find('erz').text();
							data['pl'][i]['wolfram'] = $(pl).find('wolfram').text();
							data['pl'][i]['kristall'] = $(pl).find('kristall').text();
							data['pl'][i]['fluor'] = $(pl).find('fluor').text();
							data['pl'][i]['bev'] = $(pl).find('population').text();
							data['pl'][i]['groesse'] = $(pl).attr('size');
							
							// Inhaber und Allianz
							data['pl'][i]['inhaber'] = $(pl).find('userid').text();
							data['pl'][i]['username'] = $(pl).find('userName').text();
							data['pl'][i]['rasse'] = $(pl).find('userRace').text();
							data['pl'][i]['allianz'] = $(pl).find('userAllianceId').text();
							data['pl'][i]['allytag'] = $(pl).find('userAllianceTag').text();
							
							// Orbit
							var orbit = $(pl).find('orbit');
							if($(orbit).text() != '') {
								var o = '';
								
								if($(orbit).attr('type') == 'G') {
									data['pl'][i]['gate'] = 1;
									o = '<img src="'+oddbtool.prefs.url+'img/orbit/gate.gif" width="100" height="90">';
								}
								else if($(orbit).attr('type') == 'M') {
									data['pl'][i]['mgate'] = 1;
									o = '<img src="'+oddbtool.prefs.url+'img/orbit/mgate.gif" width="100" height="90">';
								}
								else if($(orbit).attr('type') == 'S') {
									data['pl'][i]['mgate'] = 1;
									o = '<img src="'+oddbtool.prefs.url+'img/orbit/sprunggenerator.gif" width="100" height="90">';
								}
								else {
									data['pl'][i]['riss'] = 1;
									o = '<img src="'+oddbtool.prefs.url+'img/orbit/riss.gif" width="100" height="90">';
								}
								
								// Orbit-Overlay
								x = xbase + i*xadd;
								y = ybase + i*yadd + 100;
								
								content += '<div class="oddbtoolplanet" style="left:'+x+'px; top:'+y+'px; background-image:url('+oddbtool.prefs.url+'img/layout/bg.gif)"><a href="index.php?op=orbit&index='+data['pl'][i]['id']+'" onmouseover="dlt(\'Eigene Schiffe: 0\', \''+data['pl'][i]['name']+' Orbit:\')" onmouseout="nd()">'+o+'</a></div>';
							}
						}
						// Planet verschleiert und in der DB bekannt
						else if(data['pl'][i]['inhaber'] < 0 && $(pl).find('userid').text() > 0) {
							// Inhaber und Allianz kopieren
							data['pl'][i]['inhaber'] = $(pl).find('userid').text();
							data['pl'][i]['username'] = $(pl).find('userName').text();
							data['pl'][i]['rasse'] = $(pl).find('userRace').text();
							data['pl'][i]['allianz'] = $(pl).find('userAllianceId').text();
							data['pl'][i]['allytag'] = $(pl).find('userAllianceTag').text();
						}
						// System sichtbar
						else {
							// Urlaubsmodus übertragen
							if($(result).find('umod'+data['pl'][i]['inhaber']).length) {
								data['pl'][i]['username'] += '<span style="color:#ff3322"><sup>zzZ</sup></span>';
							}
							// Bevölkerung formatieren
							data['pl'][i]['bev'] = oddbtool.ressmenge(data['pl'][i]['bev']);
						}
						
						
						// voranstehende 0 des Typs entfernen
						data['pl'][i]['typ'] = data['pl'][i]['typ'].replace(/^0/, '');
						
						x = xbase + i*xadd;
						y = ybase + i*yadd;
						
						// Tooltip erzeugen
						var tooltip = '<br>Inhaber: <b>'+data['pl'][i]['username']+'</b><br><br>';
						
						if(data['pl'][i]['additional3'] != '') {
							tooltip += '<div class="oddbtooladd3">'+data['pl'][i]['additional3']+'</div>';
						}
						
						// ohne Scan Ressmengen zurücksetzen
						if(!(data['pl'][i]['updateoverview'] > 0)) {
							data['pl'][i]['erzmenge'] = '';
							data['pl'][i]['metallmenge'] = '';
							data['pl'][i]['wolframmenge'] = '';
							data['pl'][i]['kristallmenge'] = '';
							data['pl'][i]['fluormenge'] = '';
						}
						
						// unbewohnbar
						if(data['pl'][i]['groesse'] == 0) {
							data['pl'][i]['groesse'] = '<span style="color:red">unbewohnbar</span>';
							data['pl'][i]['bev'] = "0";
						}
                        
                        // Punkte berechnen
                        var plpunkte = 0;
                        var maxresswert = parseFloat(data['pl'][i]['erz']);
                        if (parseFloat(data['pl'][i]['wolfram']) > maxresswert) { maxresswert = parseFloat(data['pl'][i]['wolfram']); }
                        if (parseFloat(data['pl'][i]['kristall']) > maxresswert) { maxresswert = parseFloat(data['pl'][i]['kristall']); }
                        if (parseFloat(data['pl'][i]['fluor']) <= maxresswert) {
                            plpunkte = parseFloat(data['pl'][i]['groesse'] * data['pl'][i]['bev'].replace('.','').replace('.','') / 100000) + parseFloat(maxresswert) + parseFloat(data['pl'][i]['fluor']);    
                        } else {
                            plpunkte = parseFloat(data['pl'][i]['groesse'] * data['pl'][i]['bev'].replace('.','').replace('.','') / 100000) + (2 * parseFloat(data['pl'][i]['fluor']));
                        }
                  
						tooltip += '<table style="border:0;padding:0;margin:0;width:auto"><tr><td><img src="http://static.omega-day.com/img/research/ress_ore_small.gif"></td>' +
									'<td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['erz']+' %</td><td style="padding-left:10px">'+data['pl'][i]['erzmenge']+'</td></tr>' +
									'<tr><td><img src="http://static.omega-day.com/img/research/ress_steel_small.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['erz']+' %</td>' +
									'<td style="padding-left:10px">'+data['pl'][i]['metallmenge']+'</td></tr>' +
									' <tr><td><img src="http://static.omega-day.com/img/research/ress_wolfram_small.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['wolfram']+' %</td>' +
									'<td style="padding-left:10px">'+data['pl'][i]['wolframmenge']+'</td></tr>' +
									'<tr><td><img src="http://static.omega-day.com/img/research/ress_crystal_small.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['kristall']+' %</td>' +
									'<td style="padding-left:10px">'+data['pl'][i]['kristallmenge']+'</td></tr>' +
									'<tr><td><img src="http://static.omega-day.com/img/research/ress_fluor_small.gif"></td><td style="padding-left:10px;white-space:nowrap">'+data['pl'][i]['fluor']+' %</td>' +
									'<td style="padding-left:10px">'+data['pl'][i]['fluormenge']+'</td></tr></table>' + 
									(data['pl'][i]['bev'] && data['pl'][i]['bev'] != '0' ? '<br>Bev&ouml;lkerung: '+data['pl'][i]['bev'] : '') + 
									'<br>Gr&ouml;&szlig;e: '+data['pl'][i]['groesse'] + 
									(data['pl'][i]['orbiter'] && data['pl'][i]['orbiter'] > 0 ? '<br>Orbiter-Angriff: '+data['pl'][i]['orbiter'] : '') + 
									'<br><br>'+data['pl'][i]['additional']+'<br>Punkte: ' + plpunkte;
						
						data['pl'][i]['name'] = oddbtool.str_replace(oddbtool.charmap_search, oddbtool.charmap_replace, data['pl'][i]['name']);
						tooltip = tooltip.replace(/'/g, "\\'");                                                                          
						tooltip = tooltip.replace(/"/g, '&quot;');
						
						// Kommentar?
						if(data['pl'][i]['comment'] == 1) {
							data['pl'][i]['comment'] = '<div style="width:22px;height:18px;background-image:url('+oddbtool.prefs.url+'img/layout/sprite32.png);background-position:-480px -54px;float:left;margin-top:-5px"></div>';
						}
						
						// Unscannbar
						if(data['pl'][i]['unscannbar'] > data['pl'][i]['updateoverview']) {
							data['pl'][i]['comment'] += '<div style="position:absolute;top:110px;left:15px;font-weight:bold;color:#ff3322">unscannbar!</div>';
						}
						
						// Planet-Overlay
						if(data['pl'][i]['updateoverview'] > 0) {
							// Gebäude aufbereiten
							data['pl'][i]['gpl'] = '0+'+data['pl'][i]['gpl'];
							data['pl'][i]['gor'] = '0+'+data['pl'][i]['gor'];
							var gpl = data['pl'][i]['gpl'].split('+');
							var gor = data['pl'][i]['gor'].split('+');
							
							// mit leeren Werten füllen
							for(var k=1;k<=36;k++) {
								if(typeof(gpl[k]) == 'undefined') {
									gpl[k] = 0;
								}
							}
							for(var k=1;k<=12;k++) {
								if(typeof(gor[k]) == 'undefined') {
									gor[k] = 0;
								}
							}
							
							// Ressmengen entfernen, wenn kein voller Scan
							if(data['pl'][i]['updateoverview'] == 0) {
								data['pl'][i]['erzmenge'] = '';
								data['pl'][i]['metallmenge'] = '';
								data['pl'][i]['wolframmenge'] = '';
								data['pl'][i]['kristallmenge'] = '';
								data['pl'][i]['fluormenge'] = '';
							}
							
							// Trenner bei der Bevölkerung
							if(data['pl'][i]['bev'].indexOf('.') == -1) {
								var number = '' + data['pl'][i]['bev'];
								var mod = number.length % 3;
								var output = (mod > 0 ? (number.substring(0,mod)) : '');
								for (var k=0 ; k < Math.floor(number.length / 3); k++) {
									if ((mod == 0) && (k == 0)) {
										output += number.substring(mod+ 3 * k, mod + 3 * k + 3);
									}
									else {
										output += '.' + number.substring(mod + 3 * k, mod + 3 * k + 3);
									}
								}
								data['pl'][i]['bev'] = output;
							}
							
							// Gebäude auf dem Planet
							content += '<a class="oddbtoollink" href="index.php?op=planet&index='+data['pl'][i]['id']+'" onmouseover="dlt(\''+tooltip+'\', \''+data['pl'][i]['name']+' ('+data['pl'][i]['id']+'):\')" onmouseout="nd()"><table border="0 cellpadding="0" cellspacing="0" class="oddbtoolplanet" style="cursor:pointer;left:'+x+'px;top:'+y+'px"><tr><td style="background-position:-'+oddbtool.geb[gpl[36]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[35]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[29]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[23]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[30]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[34]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[32]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[24]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[18]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[10]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[19]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[25]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[28]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[14]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[6]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[2]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[7]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[15]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[22]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[13]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[5]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[1]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[3]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[11]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[31]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[17]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[9]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[4]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[8]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[16]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gpl[33]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[27]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[21]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[12]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[20]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gpl[26]]+'px 0px"></td></tr><tr><td colspan="6" style="background-image:none;text-align:center;font-size:11px;padding-bottom:3px;padding-top:3px">'+data['pl'][i]['scan']+data['pl'][i]['comment']+'</td></tr></table></a>';
							
							// Gebäude im Orbit
							y -= 32;
							
							content += '<table border="0 cellpadding="0" cellspacing="0" class="oddbtoolplanet" style="left:'+x+'px;top:'+y+'px"><tr><td style="background-position:-'+oddbtool.geb[gor[1]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[2]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[3]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[4]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[5]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[6]]+'px 0px"></td></tr><tr><td style="background-position:-'+oddbtool.geb[gor[7]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[8]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[9]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[10]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[11]]+'px 0px"></td><td style="background-position:-'+oddbtool.geb[gor[12]]+'px 0px"></td></tr></table>';
						}
						else {
							// Planet-Overlay ohne Scan
							content += '<a class="oddbtoollink" href="index.php?op=planet&index='+data['pl'][i]['id']+'" onmouseover="dlt(\''+tooltip+'\', \''+data['pl'][i]['name']+' ('+data['pl'][i]['id']+'):\')" onmouseout="nd()"><table border="0 cellpadding="0" cellspacing="0" class="oddbtoolplanet" style="cursor:pointer;left:'+x+'px;top:'+y+'px"><tr><td style="height:90px;background-image:none"></td></tr><tr><td style="background-image:none;text-align:center;font-size:11px;padding-bottom:3px">'+data['pl'][i]['comment']+'</td></tr></table></a>';
						}
					}
					// Planet nicht in DB
					else {
						data['pl'][i]['updateoverview'] = 0;
					}
					
					
					
					// Orbit-Overlay (Invas)
					if(typeof(data['pl'][i]['additional2']) != 'undefined' && data['pl'][i]['additional2'] != '') {
						x = xbase + i*xadd;
						y = ybase + i*yadd + 110;
						
						$('#layout-main a[href="?op=orbit&index='+data['pl'][i]['id']+'"]',page).addClass('oddbtoolorbitoverride').append('<div class="oddbtoolplanet oddbtoolorbit" id="oddbtoolorbit'+data['pl'][i]['id']+'">'+data['pl'][i]['additional2']+'</div>');
					}
					
					// Inhaber-Overlay
					if(data['typ'] == 'system' || $(result).find('systemUpdate').text() > 0 || ($(pl).text() != '' && $(pl).find('userid').text() != -1)) {
						x = xbase + i*xadd;
						y = ybase + i*yadd - 75;
						
						content += '<div class="oddbtoolplanet" style="line-height:15px;text-align:center;left:'+x+'px;top:'+y+'px">';
						if(data['pl'][i]['inhaber'] > 0) {
							// Rasse
							if(data['pl'][i]['rasse'] != '') {
								// aufbereiten
								for(var k in oddbtool.rassen) {
									if(data['pl'][i]['rasse'].indexOf(k) != -1) {
										data['pl'][i]['rasse'] = oddbtool.rassen[k];
										break;
									}
								}
							}
							
							content += '<img src="http://static.omega-day.com/img/misc/blank.gif" alt="" class="oddbtoolrasse oddbtoolr'+data['pl'][i]['rasse']+'"><a href="index.php?op=usershow&welch='+data['pl'][i]['inhaber']+'">'+data['pl'][i]['username']+'</a>';
							if(data['pl'][i]['allianz'] > 0) {
								content += '<br><a href="index.php?op=allyshow&welch='+data['pl'][i]['allianz']+'" style="font-size:10px">'+data['pl'][i]['allytag']+'</a>';
								var status = $(result).find('status'+data['pl'][i]['allianz']).text();
								if(status != '') {
									content += '<br><span style="font-size:10px;color:#888888">'+status+'</span>';
								}
							}
						}
						// frei
						else if(data['pl'][i]['inhaber'] == 0) {
							content += '- keiner -';
						}
						// frei
						else if(data['pl'][i]['inhaber'] == -2) {
							content += '<span style="color:#ffff88;font-weight:bold;font-style:italic">Seze Lux</span>';
						}
						// frei
						else if(data['pl'][i]['inhaber'] == -3) {
							content += '<span style="color:#ffff88;font-weight:bold;font-style:italic">Altrasse</span>';
						}
						// unbekannt
						else {
							content += '- unbekannt -';
						}
						content += '</div>';
					}
					
					// Menü DB-Aktionen
					if($(pl).text() != '' && $(pl).find('rechte').text() == 1) {
						x = xbase + i*xadd + 85;
						y = ybase + i*yadd + 110;
						
						content += '<div id="oddbtoolmenu'+data['pl'][i]['id']+'" class="oddbtoolmenu" style="top:'+(y+15)+'px;left:'+(x-130)+'px;display:none"><a href="'+oddbtool.prefs.url+'index.php?p=show_planet&id='+data['pl'][i]['id']+'" target="_blank">in der DB &ouml;ffnen</a><a href="javascript:void(0)" onclick="var kf = document.getElementById(\'oddbtoolkommentar\'); kf.style.left = \''+(x-130)+'px\'; kf.style.top = \''+(y-5)+'px\'; kf.style.display=\'block\'; document.getElementById(\'oddbtoolkiframe\').src=\''+oddbtool.prefs.url+'index.php?p=show_planet&id='+data['pl'][i]['id']+'&sp=kommentar_editgame\';">Kommentar &auml;ndern</a>';
						if(toxxrechte) {
							// raiden und toxxen
							content += '<div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=ajax_general&amp;sp=raid&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>als geraidet markiert</a>\'">als geraidet markieren</a></div><div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=ajax_general&amp;sp=toxx&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>als getoxxt markiert</a>\'">als getoxxt markieren</a></div>';
							// Bergbau und Terraformer entfernen
							content += '<div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=show_planet&amp;sp=removebbstf&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>Bergbau / Terraformer entfernt</a>\'">Bergbau / Terraformer entfernen</a></div>';
							// Orbiter löschen und Ress auf 0
							if(data['pl'][i]['updateoverview'] > 0) {
								content += '<div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=show_planet&amp;sp=orbiter_del&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>Orbiter gel&ouml;scht</a>\'">Orbiter l&ouml;schen</a></div><div><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=show_planet&amp;sp=ress_del&amp;id='+data['pl'][i]['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'<a href=&quot;javascript:void(0)&quot; style=&quot;font-style:italic&quot;>Ress auf 0 gesetzt</a>\'">Ress auf 0 setzen</a></div>';
							}
						}
						content += '</div><div id="oddbtoolpfeil'+data['pl'][i]['id']+'" class="oddbtoolpfeil" style="top:'+y+'px;left:'+x+'px"></div>';
					}
				}
			}
			
			// Headline anzeigen (Gala - ID - sichtbar - Scan - DB  - Reserv)
			content += '<div id="oddbtoolheadline">Gala '+$(result).find('galaxie').text()+' &nbsp; &nbsp; Sys '+data['id']+' &nbsp; &nbsp; <span style="color:';
			if(data['typ'] == 'system') content += '#00aa00">sichtbar';
			else content += '#ff3322">nicht sichtbar';
			content += '</span> &nbsp; &nbsp; <span style="color:';
			if($(result).find('system > scanDate').attr('current') == 1) content += '#00aa00';
			else content += '#ff3322';
			content += '">Scan: '+$(result).find('system > scanDate').text()+'</span> &nbsp; &nbsp; &nbsp; <a href="'+oddbtool.prefs.url+'index.php?p=show_system&id='+data['id']+'" target="_blank">in DB &ouml;ffnen</a> &nbsp; &nbsp; &nbsp; ';
			if(data['typ'] != 'system') {
				if($(result).find('reserv').length) {
					content += '<span style="font-style:italic;font-size:11px">reserviert '+$(result).find('reserv').text()+'</span>';
				}
				else {
					content += '<span><a href="javascript:void(0)" onclick="document.getElementById(\'oddbtooliframe\').src = \''+oddbtool.prefs.url+'index.php?p=ajax_general&amp;sp=reserve&amp;sys='+data['id']+'&amp;ajax&amp;plugin\';this.parentNode.innerHTML = \'Scan reserviert\'">Scan reservieren</a></span>';
				}
			}
			content += '</div>';
			
			// Kommentar-Formular
			content += '<div id="oddbtoolkommentar"><a onclick="this.parentNode.style.display=\'none\';">X</a><br /><iframe name="oddbtoolkiframe" id="oddbtoolkiframe" src="about:blank"></iframe></div>';
			
			
			// Inhalt an den body anhängen
			$('body',page).append(content);
			
			// Planetengrafiken verschieben
			$('table[width="100"] tr:first-child td').css('background-position', '2px -2px');
			
			
			// Headline zentrieren
			var offset = $('#layout-main table[width=600]',page).offset();
			if(offset) {
				offset = parseInt(offset.left);
				offset -= 25;
				$('#oddbtoolheadline',page).css('left',offset+'px');
			}
			
			// systemInfo-Tabelle
			if($(result).find('systemInfo').text() != '') {
				$('body',page).append('<div id="oddbtoolfowtbl">'+$(result).find('systemInfo').text()+'</div>');
				
				$('#oddbtoolfowtbl tr:even',page).css('backgroundColor', 'rgba(255,255,255,0.05)');
			}
			
		},
		error: function(e, msg) {
			if(e.status != 0) {
				// Fehlermeldung
				var content = e.responseText.replace(/<\?xml([.\s]*)$/, '');
				content = content.substr(0,200);
				
				$('#oddbtoolfow',page).append('<span style="color:red">Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'<br /><br />Adresse: '+addr+'<br /><br />'+content+'</span>');
			}
		}
	});
};
