<?php
/**
 * Sample test case.
 */
class AbilityToSearchByQueryTest extends PHPUnit_Framework_TestCase
{
	
	const INDEX_NAME='test_index';
	const INDEX_TYPE='test_type';
	
	/**
	 * @var Elastica_Client
	 */
	protected $_elasticaClient = null;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		// connect to elastic search cluster
		$this->_elasticaClient = new Elastica_Client(array('log' => PHPUNIT_ELASTICA_LOG_FILE));

		// load index
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		
		// Create the index new
		$elasticaIndex->create(array(
			'number_of_shards' => 4,
			'number_of_replicas' => 1,
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
		
		
		// define mapping
		
		//  http://www.elasticsearch.org/guide/reference/mapping/
		// 	http://www.elasticsearch.org/guide/reference/mapping/core-types.html
		
		
		// Load type
		$elasticaType = $elasticaIndex->getType(self::INDEX_TYPE);
		
		// Define mapping
		$mapping = new Elastica_Type_Mapping();
		$mapping->setType($elasticaType);
		$mapping->setParam('index_analyzer', 'indexAnalyzer');
		$mapping->setParam('search_analyzer', 'searchAnalyzer');
		
		// Define boost field
		$mapping->setParam('_boost', array('name' => '_boost', 'null_value' => 1.0));
		
		// Set mapping
		$properties = array();
		
		$properties['sku'] = array(
			'type' => 'string',
			'_boost'	=> 1, 
			'include_in_all' => true,
		);
		
		$properties['name'] = array(
			'type' => 'string',
			'_boost'	=> 2, 
			'include_in_all' => true,
		);
		
		$properties['price'] = array(
			'type' => 'float',
			'_boost'	=> 1, 
			'include_in_all' => true,
		);
		
		$properties['description'] = array(
			'type' => 'string',
			'_boost'	=> 1, 
			'include_in_all' => true,
		);
		
		$mapping->setProperties($properties);

		// Send mapping to type
		$mapping->send();
		
		
		$elasticaType = $this->_elasticaClient
			->getIndex(self::INDEX_NAME)
			->getType(self::INDEX_TYPE);
		
		$id = 'sku-1';
		
		$document = new Elastica_Document($id, array(
			'sku' => 'sku-1',
			'name' => 'Test Product',
			'price' => 19.99,
			'desctiption' => 'Lorem ipsum dolor sit amet.',
		));
		
		$elasticaType->addDocument($document);
		
		$id = 'sku-2';
		
		$document = new Elastica_Document($id, array(
			'sku' => 'sku-2',
			'name' => 'Beta Product ',
			'price' => 9.99,
			'desctiption' => 'Lorem ipsum dolor sit amet.',
		));
		
		$elasticaType->addDocument($document);
		
		$this->_elasticaClient->getIndex(self::INDEX_NAME)->refresh();
		
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->_elasticaClient->getIndex(self::INDEX_NAME)->delete();
		$this->_elasticaClient = null;
	}


	public function testSearchByBetaQueryStringAndOnlyOneResultHasToBeReturned() {
		
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		$type = $elasticaIndex->getType(self::INDEX_TYPE);
		
		// search query
		$elasticaQueryString = new Elastica_Query_QueryString();
		$elasticaQueryString->setQuery('BETA');
		
		$elasticaQuery 	= new Elastica_Query();
		$elasticaQuery->setQuery($elasticaQueryString);

		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);

		// getting result
		$elasticaResults = $elasticaResultSet->getResults();
		$document = current($elasticaResults);
		$data = $document->getData();
		
		$this->assertEquals('sku-2', $data['sku']);
		$this->assertEquals(1, $elasticaResultSet->getTotalHits());
		
	}
	
	public function testSearchByNotIndexedStringAndEmptyResultHasToBeReturned() {
		
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		$type = $elasticaIndex->getType(self::INDEX_TYPE);
		
		// search query
		$elasticaQueryString = new Elastica_Query_QueryString();
		$elasticaQueryString->setQuery('ThisIsNotExistingQueryString');
		
		$elasticaQuery 	= new Elastica_Query();
		$elasticaQuery->setQuery($elasticaQueryString);

		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);
		
		$this->assertEquals(0, $elasticaResultSet->getTotalHits());
		
	}
	
	public function testSearchByStartStartQueryStringAndAllResultsHaveToBeReturned() {
		
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		$type = $elasticaIndex->getType(self::INDEX_TYPE);
		
		// search query
		$elasticaQueryString = new Elastica_Query_QueryString();
		$elasticaQueryString->setQuery('*:*');
		
		$elasticaQuery 	= new Elastica_Query();
		$elasticaQuery->setQuery($elasticaQueryString);

		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);

		$this->assertEquals(2, $elasticaResultSet->getTotalHits());
		
	}

}

