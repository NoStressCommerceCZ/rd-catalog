<?php
/**
 * This script concist from several parts
 * 
 * 1) Loading data from csv file
 * 2) Define Analysis
 * 3) Define Mapping
 * 4) Loading documents into ElasticSearch
 * 5) Custom search
 */

function __autoload_elastica ($class) {
	$path = str_replace('_', DIRECTORY_SEPARATOR, $class);

	$file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $path . '.php';
	
	if (file_exists($file_name)) {
		require_once($file_name);
	}
}
spl_autoload_register('__autoload_elastica');


// 1) Loading data from csv file
$input_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'rd_catalog_data_v1.csv';

$row = 0;
$line1 = array();	// first line of CSV file

$es_index = array();

$docs = array();

if (($handle = fopen($input_file, "r")) !== FALSE) {
	while (($data = fgetcsv($handle, 100000, "\t")) !== FALSE) {
		$row++;

		switch ($row) {
			case 1:
					
				$num = count($data);
				for ($c=1; $c < $num; $c++) {
					$bom = pack("CCC", 0xef, 0xbb, 0xbf);
					if (0 == strncmp($data[$c], $bom, 3)) {
						echo "BOM detected - file is UTF-8\n";
						$data[$c] = substr($data[$c], 3);
					}
					
					$es_index[$c]['code'] = $data[$c];
					
					$line1[$data[$c]] = $c ;
				}

				break;
			case 2:
				
				$num = count($data);
				for ($c=1; $c < $num; $c++) {
					$es_index[$c]['type'] = (string) $data[$c];
				}

				break;
			case 3:
				$num = count($data);
				for ($c=1; $c < $num; $c++) {
					$es_index[$c]['boost'] = (float)$data[$c];
				}

				break;
			default:

				for ($c=1; $c < $num; $c++) {
					$d[$es_index[$c]['code']] = $data[$c];
				}
				
				$docs[] = $d;
				
				break;
		}

	}

	fclose($handle);
}


//var_dump($es_index);
//var_dump($docs);


$elasticaClient = new Elastica_Client(array(
	'servers' => array(
		array('host' => 'localhost', 'port' => 9200),
		array('host' => 'localhost', 'port' => 9201)
	)
));

// 2) Define Analysis

// Load index
$elasticaIndex = $elasticaClient->getIndex('rd_catalog_data_v1');

// Create the index new
$elasticaIndex->create(array(
	'number_of_shards' => 2,
	'number_of_replicas' => 2,
	'analysis' => array(
		'analyzer' => array(
			'indexAnalyzer' => array(
				'type' => 'custom',
				'tokenizer' => 'standard',
				'filter' => array('lowercase', 'mySnowball')
		),
		'searchAnalyzer' => array(
				'type' => 'custom',
				'tokenizer' => 'standard',
				'filter' => array('standard', 'lowercase', 'mySnowball')
			)
		),
		'filter' => array(
			'mySnowball' => array(
				'type' => 'snowball',
				'language' => 'German'
			)
		)
	)
	), 
	true
);

// 3) Define Mapping
//  http://www.elasticsearch.org/guide/reference/mapping/
// 	http://www.elasticsearch.org/guide/reference/mapping/core-types.html


// Load type
$elasticaType = $elasticaIndex->getType('product');

// Define mapping
$mapping = new Elastica_Type_Mapping();
$mapping->setType($elasticaType);
$mapping->setParam('index_analyzer', 'indexAnalyzer');
$mapping->setParam('search_analyzer', 'searchAnalyzer');

// Define boost field
$mapping->setParam('_boost', array('name' => '_boost', 'null_value' => 1.0));

// Set mapping
$properties = array();
foreach ($es_index as $i => $index) {
	$properties[$index['code']] = array(
		'type' => $index['type'],
		'_boost'	=> $index['boost'], 
		'include_in_all' => true,
	);
}

$mapping->setProperties($properties);

// Send mapping to type
$mapping->send();

// 4) Loading documents into ElasticSearch

foreach ($docs as $d) {
	
	$id = $d['sku'];
	$tweetDocument = new Elastica_Document($id, $d);
	
	// Add tweet to type
	$elasticaType->addDocument($tweetDocument);

}

// Refresh Index
$elasticaType->getIndex()->refresh();

