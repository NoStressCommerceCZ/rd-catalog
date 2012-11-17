<?php
echo "<pre>";

function __autoload_elastica ($class) {
	$path = str_replace('_', DIRECTORY_SEPARATOR, $class);

	$file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $path . '.php';
	
	if (file_exists($file_name)) {
		require_once($file_name);
	}
}
spl_autoload_register('__autoload_elastica');

$elasticaClient = new Elastica_Client(array(
	'servers' => array(
		array('host' => 'localhost', 'port' => 9200),
		array('host' => 'localhost', 'port' => 9201)
	)
));

// Load index
$elasticaIndex = $elasticaClient->getIndex('rd_catalog_data_v1');
$type = $elasticaIndex->getType('product');

// Define a Query. We want a string query.
$elasticaQueryString 	= new Elastica_Query_QueryString();
$elasticaQueryString->setDefaultOperator('AND');
$elasticaQueryString->setQuery('*:*');

// Create the actual search object with some data.
$elasticaQuery 		= new Elastica_Query();
$elasticaQuery->setQuery($elasticaQueryString);
$elasticaQuery->setFrom(1);
$elasticaQuery->setLimit(4);


////Search on the index.
//$elasticaResultSet 	= $type->search($elasticaQuery);
//
//$elasticaResults 	= $elasticaResultSet->getResults();
//$totalResults 		= $elasticaResultSet->getTotalHits();
//
//var_dump(array(
//	'$totalResults' => $totalResults 
//));
//
//foreach ($elasticaResults as $elasticaResult) {
////	Elastica_Result
//	var_dump($elasticaResult->getScore());
//	var_dump($elasticaResult->getData());
//}
//
//echo "</pre>";

// http://www.elasticsearch.org/guide/reference/api/search/filter.html

// Filter for being of color blue
$elasticaFilterColorBlue	= new Elastica_Filter_Term();
$elasticaFilterColorBlue->setTerm('attribute_1', 'AAA0002');


// Add filter to the search object.
$elasticaQuery->setFilter($elasticaFilterColorBlue);

//Search on the index.
$elasticaResultSet 	= $type->search($elasticaQuery);

$elasticaResults 	= $elasticaResultSet->getResults();
$totalResults 		= $elasticaResultSet->getTotalHits();

var_dump(array(
	'$totalResults' => $totalResults 
));

foreach ($elasticaResults as $elasticaResult) {
//	Elastica_Result
	var_dump($elasticaResult->getScore());
	var_dump($elasticaResult->getData());
}

echo "</pre>";