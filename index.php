<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />

		<style type="text/css">
			html {
				height: 100%;
				width: 100%
			}
			body {
				height: 100%;
				margin: 0;
				padding: 0
			}

			#map_canvas {
				/*display: none;*/
			}

			.status {
				float: right;
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
						markers.push(new google.maps.Circle({
							center : map.getCenter(),
							radius : getMapRadius() * 1000,
							map : map,
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

			/********************************* Variaveis globais***********************************************************/
			var map;
			var markers = [];
			var lastfm;
			var sparqler;

			sparqler = new SPARQL.Service("http://dbpedia.org/sparql");
			sparqler.addDefaultGraph('http://dbpedia.org');
			sparqler.setPrefix("geonames", "http://www.geonames.org/ontology#");
			sparqler.setOutput("json");

			/********************************* Funcões *******************************************************************/

			/** Remove os marcadores do mapa*/
			function deleteMarkers() {
				for (var i = 0; i < markers.length; i++) {
					markers[i].setMap(null);
				}
				markers = [];
			}

			function initialize() {

				// opções iniciais do mapa
				var mapOptions = {
					center : new google.maps.LatLng(-34.397, 150.644),
					zoom : 10,
					mapTypeId : google.maps.MapTypeId.ROADMAP
				};

				// instância o mapa
				map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);

				// Try W3C Geolocation (Preferred)
				if (navigator.geolocation) {
					navigator.geolocation.getCurrentPosition(function(position) {
						var initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
						map.setCenter(initialLocation);
					});
				}

				map.addListener("dragend", function() {
					Debugger.log("center_changed");
					refreshEvents(getMapCenter(), getMapRadius());
				});

				map.addListener("zoom_changed", function() {
					Debugger.log("zoom_changed");
					refreshEvents(getMapCenter(), getMapRadius());

				});

				/* Create a cache object */
				var cache = new LastFMCache();

				/* Create a LastFM object */
				lastfm = new LastFM({
					apiKey : '83f4027ae14bb9a53a2f83a16f62d795',
					apiSecret : '5861a37f79df7ab6bdf808ff0ef24da9',
					cache : cache
				});

				/* ///sparqler = new SPARQL.Service("http://dbpedia.org/sparql");
				 sparqler = new SPARQL.Service("http://dbpedia.org/sparql");
				 sparqler.addDefaultGraph('http://dbpedia.org/');

				 sparqler.setPrefix("owl","http://www.w3.org/2002/07/owl#");
				 sparqler.setPrefix("dbpedia", "http://dbpedia.org/resource/");
				 sparqler.setPrefix("dbpedia-owl", "http://dbpedia.org/ontology/");
				 sparqler.setPrefix("dbpprop", "http://dbpedia.org/property/");
				 sparqler.setPrefix("geonames", "http://www.geonames.org/ontology#");

				 sparqler.setOutput("json");*/
			}

			/***********************************************************************************************************************************************/
			function refreshEvents(geolocation, distance) {
				$('#eventos').empty();
				deleteMarkers();
				Debugger.putCircle();

				params = {
					limit : 10,
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

				lastfm.geo.getEvents(params, {
					success : function(data) {

						$('#status').text(data.events['@attr'].total + ' resultado(s) encontrado(s).');
						Debugger.log(new Date() + ' - Resultado:');
						
						var myLatlng;
						
						/* Use data. */
						$(data.events.event).each(function(i, e) {
		
							if (e.venue.location['geo:point']['geo:lat'] != "") {
								/* Define um ponto no mapa*/
								myLatlng = new google.maps.LatLng(e.venue.location['geo:point']['geo:lat'], e.venue.location['geo:point']['geo:long']);
							} else {
								//TODO:  procurar no GeoNames usando SPARQL
								//var results = searchSPARQL();
							}

							var marker = new google.maps.Marker({
								position : myLatlng,
								map : map,
								title : e.title
							});

							/*adiciona o novo marcador ao conjunto de marcadores*/
							markers.push(marker);
							Debugger.log(e.title + ' / ' + e.venue.location.city);

						});

					},
					error : function(code, message) {
					}
				});
			}

			/* retorna o resultado da consulta sparql */
			function querySuccess(data) {
				if (result.head.vars.length > 0) {
					// TODO: A pesquisa retornou o resultado
					if (data.results.bindings.length) {
						$(data.results.bindings).each(function(i, entry) {
							$(data.head.vars).each(function(j, field) {
								/* Cria o marcador para cada resultado obtido */ 
								/* Ou retorna um vetor com os resultados*/
							});
						});
					}
				}
			}
			
			/* retorna erro caso a consulta sparql não seja executada corretamente */
			function queryFails(err) {
				console.log("Erro ao executar a consulta sparql: " + err.message);
			}

			function searchSPARQL(region, music_style) {
				/* Consulta sparql */
				var sparql_query = "";
 				var querySparql = sparqler.createQuery();
				querySparql.query(sparql_query, querySuccess, queryFails);
			}

			/***********************************************************************************************************************************************/

			function getCurrentLocation() {

				var resultLocation = undefined;

				function success(position) {
					resultLocation = position.coords;
					console.log("Latitude: " + resultLocation.latitude);
					console.log("Longitude: " + resultLocation.longitude);
					console.log("Accuracy: " + resultLocation.accuracy);
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

			function getMapCenter() {
				return {
					coords : {
						latitude : map.getCenter().lat(),
						longitude : map.getCenter().lng()
					}
				};
			}

			var rad = function(x) {
				return x * Math.PI / 180;
			};

			var getDistance = function(p1, p2) {
				var R = 6378137;
				// Earth’s mean radius in meter
				var dLat = rad(p2.lat() - p1.lat());
				var dLong = rad(p2.lng() - p1.lng());
				var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(rad(p1.lat())) * Math.cos(rad(p2.lat())) * Math.sin(dLong / 2) * Math.sin(dLong / 2);
				var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
				var d = R * c;
				// returns the distance in meter
				return d;
			};

			function getMapDistance() {
				var pos_inicial = new google.maps.LatLng(map.getBounds().getSouthWest().lat(), map.getCenter().lng());
				var pos_final = new google.maps.LatLng(map.getBounds().getNorthEast().lat(), map.getCenter().lng());

				return getDistance(pos_inicial, pos_final);
			}

			function getMapRadius() {
				var dist = getMapDistance() / 1000 / 2;
				if (dist > 100) {
					dist = 100;
				}

				Debugger.log('getMapRadius: ' + dist);
				return dist;
			}

			/***********************************************************************************************************************************************/
			function procurar() {

			}

			/***********************************************************************************************************************************************/
			$(function() {
				initialize();
				Debugger.debugMode = true;
				initLocation = getCurrentLocation();
				refreshEvents(initLocation, getMapRadius());
				$('#procurarB').click(procurar);
			});
		</script>
	</head>
	<body>

		<fieldset>
			<input type="text" placeholder="cidade, pais, etc" id="local" />
			<input type="text" placeholder="gênero musical" id="genero" />
			<input type="button" value="Procurar" id="procurarB" />
			<div id="status" class="status">
				Pronto
			</div>
		</fieldset>
		<!--
		<ul id="eventos">
		</ul>
		-->
		<div id="map_canvas" style="width:100%; height:100%"></div>

	</body>

</html>
