<?php
/**
 *  test case.
 */
class AbilityToFilterByComplexAndOrAttributeFilterTest extends PHPUnit_Framework_TestCase
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
		
		$properties['yesno'] = array(
			'type' => 'string',
			'_boost'	=> 1, 
			'include_in_all' => true,
			'index'	=> 'not_analyzed',
		);
		
		$properties['size'] = array(
			'type' => 'string',
			'_boost'	=> 1, 
			'include_in_all' => true,
			'index'	=> 'not_analyzed',
		);
		
		$properties['age'] = array(
			'type' => 'string',
			'_boost'	=> 1, 
			'include_in_all' => true,
			'index'	=> 'not_analyzed',
		);
		
		$properties['colour'] = array(
			'type' => 'string',
			'_boost'	=> 1, 
			'include_in_all' => true,
			'index'	=> 'not_analyzed',
		);
		
		
		$mapping->setProperties($properties);

		// Send mapping to type
		$mapping->send();
		
		
		$elasticaType = $this->_elasticaClient
			->getIndex(self::INDEX_NAME)
			->getType(self::INDEX_TYPE);

		$i = 1;
		$product_name[$i++] = 'Alfa Romeo';
		$product_name[$i++] = 'Mercedes';
		$product_name[$i++] = 'Skoda';
		$product_name[$i++] = 'Lada';
		$product_name[$i++] = 'Audi';
		$product_name[$i++] = 'Fiat';
		
		$i = 1;
		$product_yesno[$i++] = 'yes';
		$product_yesno[$i++] = 'no';
		
		$i = 1;
		$product_size[$i++] = 'S';
		$product_size[$i++] = 'L';
		$product_size[$i++] = 'M';

		$i = 1;
		$product_age[$i++] = 'baby';
		$product_age[$i++] = 'teenager';
		$product_age[$i++] = 'adult';
		$product_age[$i++] = 'retired';
		
		$i = 1;
		$product_colour[$i++] = 'Black';
		$product_colour[$i++] = 'Red';
		$product_colour[$i++] = 'Yellow';
		$product_colour[$i++] = 'Green';
		$product_colour[$i++] = 'White';
		
		
		
		for ($i=1; $i<=100; $i++ ) {
			
			$id = 'sku-'.$i;
			
			$document = new Elastica_Document($id, array(
				'sku' 		=> $id,
				'name' 		=> (isset($product_name[$i]) ? $product_name[$i]: sprintf("Test Product %04d", $i)),
				'price' 	=> $i + 0.99,
				'desctiption' => 'Lorem ipsum dolor sit amet.',
			
				'yesno' 	=> ($product_yesno[1+(($i-1)%count($product_yesno))]),
				'size' 		=> ($product_size[1+(($i-1)%count($product_size))]),
				'age' 		=> ($product_age[1+(($i-1)%count($product_age))]),
				'colour' 	=> ($product_colour[1+(($i-1)%count($product_colour))]),
			));
			
			$elasticaType->addDocument($document);
		}
		
		
		
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

	public function providerAtomicFilter()
    {
        return array(
          array('yesno', 'yes', 50),
          array('size', 'S', 34),
          array('age', 'baby', 25),
          array('colour', 'White', 20),
        );
    }
	
	/**
     * @dataProvider providerAtomicFilter
     */
	public function testAtomicFilter($attribute, $value, $hits) {
		
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		$type = $elasticaIndex->getType(self::INDEX_TYPE);
		
		// search query
		$matchAllQuery = new Elastica_Query_MatchAll();
        
        $term = new Elastica_Filter_Term();
        $term->setTerm($attribute, $value);
        
        
        // Filtered query using the query string and a filter
		$filteredQuery = new Elastica_Query_Filtered(
		   $matchAllQuery,
		   $term
		);	
		
		$elasticaQuery = new Elastica_Query($filteredQuery);

		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);

		// getting result
		
		// check number of hit
		$this->assertEquals($hits, $elasticaResultSet->getTotalHits());
		
		//check that returned hits are matchind Attribute is Value
		foreach ($elasticaResultSet->getResults() as $document ) {
			$data = $document->getData();
			$this->assertEquals($value, $data[$attribute]);
		}
		
	}
	
	public function providerAtomicAttributeFilter()
    {
        return array(
          array('yesno', array('yes', 'no'), 100),
          array('size', array('S', 'L'), 67),
          array('age', array('baby', 'teenager'), 50),
          array('colour', array('White', 'Black'), 40),
        );
    }
	
	/**
     * @dataProvider providerAtomicAttributeFilter
     */
	public function testAtomicAttributeFilter($attribute, $values, $hits) {
		
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		$type = $elasticaIndex->getType(self::INDEX_TYPE);
		
		// search query
		$matchAllQuery = new Elastica_Query_MatchAll();
        
		$filter_or = new Elastica_Filter_Or();
		
		foreach ($values as $value ) {
	        $term = new Elastica_Filter_Term();
	        $term->setTerm($attribute, $value);
	        
	        $filter_or->addFilter($term);
		}
        
        
        // Filtered query using the query string and a filter
		$filteredQuery = new Elastica_Query_Filtered(
		   $matchAllQuery,
		   $filter_or
		);	
		
		$elasticaQuery = new Elastica_Query($filteredQuery);

		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);

		// getting result
		
		// check number of hit
		$this->assertEquals($hits, $elasticaResultSet->getTotalHits());
		
		//check that returned hits are matchind Attribute is Value
		foreach ($elasticaResultSet->getResults() as $document ) {
			$data = $document->getData();
			$this->assertContains($data[$attribute], $values );
		}
		
	}
	
	public function providerComplexAttributeFilter()
    {
        return array(
        	
        	array(
        		array(
	        		'yesno' => array('yes'),
	        		'size' =>  array('S'),
        		),
        		17
        	),
        	
        	array(
        		array(
	        		'yesno' => array('yes'),
	        		'size' =>  array('S'),
	        		'age' =>  array('baby'),
	        	),
        		9
        	),
        	array(
        		array(
	        		'yesno' => array('yes'),
	        		'size' => array('S'),
	        		'age' => array('baby'),
	        		'colour' => array('Green'),
	        	),
        		1
        	),
        	array(
        		array(
	        		'yesno' => array('yes'),
	        		'size' => array('S'),
	        		'age' => array('baby'),
	        		'colour' => array('Green', 'Yellow'),
	        	),
        		3
        	),
        	array(
        		array(
	        		'yesno' => array('yes'),
	        		'size' => array('S', 'L'),
	        		'age' => array('baby'),
	        		'colour' => array('Green', 'Yellow'),
	        	),
        		6
        	),
        );
    }
	
	/**
     * @dataProvider providerComplexAttributeFilter
     */
	public function testComplexAttributeFilter($attributes, $hits) {
		
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		
		$type = $elasticaIndex->getType(self::INDEX_TYPE);
		
		// search query
		$matchAllQuery = new Elastica_Query_MatchAll();
        
		$filter_and = new Elastica_Filter_And();
		
		foreach ($attributes as $attribute => $values) {
		
			$filter_or = new Elastica_Filter_Or();
			foreach ($values as $value ) {
		        $term = new Elastica_Filter_Term();
		        $term->setTerm($attribute, $value);
		        
		        $filter_or->addFilter($term);
			}
			
			$filter_and->addFilter($filter_or);
		}
        
        
        // Filtered query using the query string and a filter
		$filteredQuery = new Elastica_Query_Filtered(
		   $matchAllQuery,
		   $filter_and
		);	
		
		$elasticaQuery = new Elastica_Query($filteredQuery);

		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);

		// getting result
		
		// check number of hit
		$this->assertEquals($hits, $elasticaResultSet->getTotalHits());
		
		//check that returned hits are matchind Attribute is Value
		foreach ($elasticaResultSet->getResults() as $document ) {
			
			$data = $document->getData();
			foreach ($attributes as $attribute => $values) {
				$this->assertContains($data[$attribute], $values );
			}
		}
	}
	
}