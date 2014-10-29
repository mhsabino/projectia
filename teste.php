<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<title>Teste</title>
		<style>
			div {
				width: 300px;
				height: 300px;
				border: 1px solid black;
			}
		</style>
		<script type="text/javascript" src="js/jquery-2.1.1.min.js"></script>
		<script type="text/javascript" src="js/sparql.js"></script>		
		<script>
			var sparqler;

			sparqler = new SPARQL.Service("http://dbpedia.org/sparql");
			sparqler.addDefaultGraph('http://dbpedia.org');
			sparqler.setPrefix("geonames", "http://www.geonames.org/ontology#");
			sparqler.setOutput("json");

			/* retorna o resultado da consulta sparql */
			function querySuccess(data) {
				if (result.head.vars.length > 0) {
					// TODO: A pesquisa retornou o resultado
					if (data.results.bindings.length) {
						$(data.head.bindings).each(function(i, entry) {
							$(data.results.vars).each(function(j, field) {
								/* Cria o marcador para cada resultado obtido */ 
								/* Ou retorna um vetor com os resultados*/
								$("div").text(entry[field].value);
								$("div").css("background-color", "red");
							});
						});
					}
				}
			}
			
			/* retorna erro caso a consulta sparql não seja executada corretamente */
			function queryFails(err) {
				console.log("Erro ao executar a consulta sparql: " + err.message);
			}

			function searchSPARQL() {
				/* Consulta sparql */
				var sparql_query = "select distinct ?bandName where { ?bandName a dbpedia-owl:Band . ?bandName rdfs:label ?name FILTER ( regex(?name, '^Iron Maiden$', 'i') ) . }";
 				var querySparql = sparqler.createQuery();
				querySparql.query(sparql_query, querySuccess, queryFails);
			}
			
			$(function(){
				$("div").css("background-color", "yellow");
				searchSPARQL();	
			});
			
			
			
		</script>
	</head>
	<body>
		<div>
			
		</div>
	</body>
</html>