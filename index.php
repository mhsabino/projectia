<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
        <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
        
		<style type="text/css">
			html {
				height: 100%;
				width: 100%
			}
			body {
				height: 100%;
				margin: 0;
				padding: 0;
                font-family: 'Open Sans', sans-serif;
			}
            
            #topBar
            {
                padding: 0.8em;
            }
            #topBar span
            {
                margin-left: 0.3em;
                margin-right: 0.5em;
            }

			#map_canvas {
				/*display: none;*/
			}

			.status, .debugmode {
				float: right;
                margin-left: 1em;
			}
            
            input[type=text]
            {
                padding: 0.3em;
            }
		</style>

		<!-- jQuery 2.1.1 -->
		<script type="text/javascript" src="js/jquery-2.1.1.min.js"></script>
		<script type="text/javascript" src="js/sparql.js"></script>

		<!-- API do Last.FM -->
		<script type="text/javascript" src="js/lastfm.api.js"></script>
		<script type="text/javascript" src="js/lastfm.api.cache.js"></script>
		<script type="text/javascript" src="js/lastfm.api.md5.js"></script>

		<!-- API do Google Maps -->
		<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=AIzaSyAYrlFcKWs6keQufW3Jo-jhzfDpBVX1eoM&sensor=false"></script>

		<script type="text/javascript">
			var Debugger = {
				debugMode : false,
				debugCircle : true,

				putCircle : function() {
					if (this.debugMode && this.debugCircle) {
						Mapa.markers.push(new google.maps.Circle({
							center : Mapa.map.getCenter(),
							radius : Mapa.getMapRadius() * 1000,
							map : Mapa.map,
							strokeColor : '#FF0000',
							strokeOpacity : 0.5,
							strokeWeight : 2,
							fillColor : '#FF0000',
							fillOpacity : 0.10,
						}));
					}
				},

				log : function(msg) {
					if (this.debugMode) {
						console.log(msg)
					}
				}
			};
            
            /*** Mapa ********************************************************************************************************************************/
            var Mapa = {
                map : null,
                markers : [],
                
                initialize : function() {
                    // opções iniciais do mapa
                    var mapOptions = {
                        center : new google.maps.LatLng(-34.397, 150.644),
                        zoom : 10,
                        mapTypeId : google.maps.MapTypeId.ROADMAP
                    };

                    // instância o mapa
                    this.map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);

                    // Try W3C Geolocation (Preferred)
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            var initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                            Mapa.map.setCenter(initialLocation);
                        });
                    }

                    /*
                    map.addListener("dragend", function() {
                        Debugger.log("center_changed");
                        refreshEvents(getMapCenter(), getMapRadius());
                    });

                    map.addListener("zoom_changed", function() {
                        Debugger.log("zoom_changed");
                        refreshEvents(getMapCenter(), getMapRadius());

                    });  */                   
                },
                
                
                /* colocar um marcador no map */
                putMarker : function(params) {
                    
                    params['map'] = this.map;
                    
                    // criar o marcador
                    marker = new google.maps.Marker(params);

                    // adiciona o novo marcador ao conjunto de marcadores
                    this.markers.push(marker);
                    
                    return marker;
                },
                
                removeMarker : function(index) {
                    this.markers[index].setMap(null);
                    this.markers.splice(index, 1);
                },
                
                deleteMarkers : function() {
                    for (var i = 0; i < this.markers.length; i++) {
                        this.markers[i].setMap(null);
                    }
                    this.markers = [];
                },
                
                getMapCenter : function () {
                    return {
                        coords : {
                            latitude : this.map.getCenter().lat(),
                            longitude : this.map.getCenter().lng()
                        }
                    };
                },

                getMapDistance : function () {
                    function getDistance(p1, p2) {

                        function rad(x) {
                            return x * Math.PI / 180;
                        };

                        var R = 6378137;
                        // Earth’s mean radius in meter
                        var dLat = rad(p2.lat() - p1.lat());
                        var dLong = rad(p2.lng() - p1.lng());
                        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + 
                                Math.cos(rad(p1.lat())) * Math.cos(rad(p2.lat())) * Math.sin(dLong / 2) * Math.sin(dLong / 2);
                        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                        var d = R * c;
                        // returns the distance in meter
                        return d;
                    };                              
                    
                    var pos_inicial = new google.maps.LatLng(this.map.getBounds().getSouthWest().lat(), this.map.getCenter().lng());
                    var pos_final = new google.maps.LatLng(this.map.getBounds().getNorthEast().lat(), this.map.getCenter().lng());

                    return getDistance(pos_inicial, pos_final);
                },
                
                getMapRadius : function () {

                    dist = this.getMapDistance() * 1.5 / 1000 / 2;

                    // limitar em 100km
                    if (dist > 100) {
                        dist = 100;
                    }

                    //Debugger.log('getMapRadius: ' + dist);
                    return dist;
                }    
            }

            /*** LastFMSvc **************************************************************************************************************************/
            var LastFMSvc = {
                
                lastfm : null,
                
                initialize : function() {                    
                    // Create a cache object
                    cache = new LastFMCache();

                    // Create a LastFM object
                    this.lastfm = new LastFM({
                        apiKey : '83f4027ae14bb9a53a2f83a16f62d795',
                        apiSecret : '5861a37f79df7ab6bdf808ff0ef24da9',
                        cache : cache
                    });
                },
                
                procurarEventos : function(params, successCallback, errorCallback) {
                    this.lastfm.geo.getEvents(params, {
                        success : successCallback,
                        error : errorCallback
                    });
                }
            }
            
            /*** Sparqler ***************************************************************************************************************************/
            var Sparqler = { 
                
                sparqler : null,
                
                initialize: function(sparqlEndpoint) {
                    this.sparqler = new SPARQL.Service(sparqlEndpoint);
                    this.sparqler.addDefaultGraph('http://dbpedia.org');
                    //sparqler.setPrefix("geonames", "http://www.geonames.org/ontology#");
                    this.sparqler.setOutput("json");
                },
                
                consultar : function(queryString, successCallback, failCallback) {
                    queryOptions = { 
                        success : successCallback, 
                        failure : failCallback
                    };
                    
                    querySparql = this.sparqler.createQuery();
                    querySparql.query(queryString, queryOptions);
                }
            }
            
			/***********************************************************************************************************************************************/
                    
			function getCurrentLocation() {

				var resultLocation = undefined;

				function success(position) {
					resultLocation = position.coords;
					Debugger.log("Latitude: " + resultLocation.latitude);
					Debugger.log("Longitude: " + resultLocation.longitude);
					Debugger.log("Accuracy: " + resultLocation.accuracy);
				}

				function error(err) {
					console.log("ERRO: " + err.message);
				}

				// verifica se o navegador tem superte a geolocation
				if (navigator.geolocation) {
					navigator.geolocation.getCurrentPosition(success, error);
				}

				return resultLocation;
			}        

			/********************************* Funcões *******************************************************************/

			function initialize() {
                
                // inicializar os controles
                
                Mapa.initialize();                
                LastFMSvc.initialize();                
                Sparqler.initialize($('#endpoint').val());                
			}
            
            function addInfoDiv(marker, event, data)
            {
                infoDiv = $('<div>').addClass('info-event');
                
                $('<h3>').text(event.title).appendTo(infoDiv);
                $('<p>').text('Artista: ' + event.artists.headliner).appendTo(infoDiv);
                $('<p>').text('Data: ' + event['startDate']).appendTo(infoDiv);
                $('<p>').text('Local: ' + event['venue']['name']).appendTo(infoDiv);
                
                generos = '';
                $(data.results.bindings).each(function(i, e){
                   generos = generos + e['genero']['value'] + ', ';
                });
                 $('<p>').text('Generos: ' + generos).appendTo(infoDiv);
                
                
                var infowindow = new google.maps.InfoWindow({
                  content: $(infoDiv).html(),
                  maxWidth : 300  
                });

                google.maps.event.addListener(marker, 'click', function() {
                    infowindow.open(Mapa.map,marker);
                });
            }

			/***********************************************************************************************************************************************/
			function refreshEvents(geolocation, distance) {
                
                function successPesquisa(data) {

                    function searchDBPediaArtist(artist, genre, event) {
                
                        /* retorna o resultado da consulta sparql */
                        function querySuccess(data) {
                            // verificar a pesquisa retornou resultados
                            if (data.results.bindings.length > 0) {
                                //Debugger.log('resultados encontrados!');

                                addInfoDiv(Mapa.putMarker({ 
                                    title : event.title,
                                    icon : 'img/green-dot.png',
                                    position : new google.maps.LatLng(event.venue.location['geo:point']['geo:lat'], 
                                                                      event.venue.location['geo:point']['geo:long'])
                                }), event, data);
                                
                                //addInfoDiv(marker, event, data);
                                    
                            } // if
                            else {
                                
                                if (Debugger.debugMode) {
                                    // se estiver em modo de depuracao, colocar o marcador "mais claro"
                                    // pois indica que eh um resultado indesejado
                                    Mapa.putMarker({ 
                                        title : event.title,
                                        //icon : {path: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'},
                                        position : new google.maps.LatLng(event.venue.location['geo:point']['geo:lat'], 
                                                                          event.venue.location['geo:point']['geo:long']),
                                        opacity : 0.4
                                    });
                                }
                            }
                        } // function

                        /* retorna erro caso a consulta sparql não seja executada corretamente */
                        function queryFails(err) {
                            Debugger.log("Erro ao executar a consulta sparql: " + err.message);
                        }

                        //eventPosition = ;                        
                        
                        /* Consulta sparql */
                        queryString = 'select distinct * \
                                        where \
                                        { \
                                          { ?x a dbpedia-owl:MusicalArtist } UNION { ?x a dbpedia-owl:Band  } . \
                                          ?x rdfs:label ?nome FILTER ( regex(?nome, "'+artist+'", "i") && LANGMATCHES(LANG(?nome), "pt") ) . \
                                          ?x dbpedia-owl:genre ?g . \
                                          ?g rdfs:label ?genero FILTER ( regex(?genero, "'+genre+'", "i") && LANGMATCHES(LANG(?genero), "pt") ) . \
                                        }';

                        Sparqler.consultar(queryString, querySuccess, queryFails);
                    } // function

                    $('#status').text(data.events['@attr'].total + ' resultado(s) encontrado(s).');
                    Debugger.log(new Date() + ' - Resultado:');            

                    /* Use data. */
                    $(data.events.event).each(function(i, e) {

                        if (e.venue.location['geo:point']['geo:lat'] != "") {

                            searchDBPediaArtist(e.artists.headliner, $('#genero').val(), e);
                            
                            Debugger.log(e.title + ' / ' + e.venue.location.city);
                        }
                    });
                    
                    /*
                    if (data.events['@attr'].page < data.events['@attr'].totalPages) {
                        params['page'] = data.events['@attr'].page + 1;
                        LastFMSvc.procurarEventos(params, successPesquisa, errorPesquisa);
                    }
                    */

                };

                function errorPesquisa(code, message) {
                    console.log('Erro no retorno do Last.FM! Codigo: '+code+' Mensagem: '+message);
                }
                
				Mapa.deleteMarkers();
				Debugger.putCircle();

				params = {
					limit : $('#limite').val(),
					lat : 0,
					long : 0,
					distance : 0
				};

				if (geolocation) {
					params.lat = geolocation.coords.latitude;
					params.long = geolocation.coords.longitude;
				}

				if (distance) {
					params.distance = distance;
				}

				$('#status').text('Procurando...');
                
                LastFMSvc.procurarEventos(params, successPesquisa, errorPesquisa);
                
			} // function

			/***********************************************************************************************************************************************/
			$(function() {
				initialize();
				Debugger.debugMode = $('#debugmodeCk').prop('checked');
				
                /*initLocation = getCurrentLocation();
				
                refreshEvents(initLocation, getMapRadius());
*/
                $('#procurarB').click(function() { 
                    Debugger.debugMode = $('#debugmodeCk').prop('checked');
                    Sparqler.initialize($('#endpoint').val());  
                    refreshEvents(Mapa.getMapCenter(), Mapa.getMapRadius()); 
                });
			});
		</script>
	</head>
	<body>

		<fieldset id="topBar">
			<!--<input type="text" placeholder="cidade, pais, etc" id="local" />-->
			<span>Genero Musical <input type="text" placeholder="rock, jazz, samba ..." id="genero" style="width: 200px;" /></span>
            <span>Limite <input type="text" id="limite" value="30" style="width: 50px" /></span>
			<input type="button" value="Procurar" id="procurarB" />
            <span class="status"><input type="text" placeholder="http://sparql.org/sparql" id="endpoint" style="width: 200px;" value="http://dbpedia.org/sparql" /></span>
            <div class="debugmode"><input type="checkbox" id="debugmodeCk" checked="false" >Modo de depuração</input></div>            
			<div id="status" class="status">
				Pronto
			</div>
		</fieldset>
		<div id="map_canvas" style="width:100%; height:100%"></div>

	</body>

</html>
