<?php
/**
 *  test case.
 */
class AbilityToFilterByCombinationOfBasicFiltersTest extends PHPUnit_Framework_TestCase
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

		$helper_index = HelperCreateIndex::getInstance()->setElasticaClient($this->_elasticaClient);
		$helper_index->createIndex();
		
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->_elasticaClient->getIndex(self::INDEX_NAME)->delete();
		$this->_elasticaClient = null;
	}

	public function providerComplexAttributeFilter()
    {
        return array(
        	
        	array(
        		new Elastica_Query_MatchAll(),
        		null,
        		array(
	        		'yesno' => array('yes'),
	        		'size' =>  array('S'),
        		),
        		array('sku' => 'asc'),
        		17,		
        		'sku-1'
        	),
        	
        	array(
        		new Elastica_Query_MatchAll(),
        		new Elastica_Filter_Range(
        			'price',
        			array(
        				'from'	=> 10, 
        				'to'	=> 90
        			)
        		),
        		array(
	        		'yesno' => array('yes'),
	        		'size' =>  array('S', 'L'),
        			'age' =>  array('baby'),
        			'colour' =>  array('Red', 'Black', 'White'),
        		),
        		array('sku' => 'asc'),
        		8,		
        		'sku-17'
        	),
        
        	array(
        		new Elastica_Query_MatchAll(),
        		new Elastica_Filter_Range(
        			'price',
        			array(
        				'from'	=> 10, 
        				'to'	=> 50
        			)
        		),
        		array(
	        		'yesno' => array('yes'),
	        		'size' =>  array('S'),
        		),
        		array('sku' => 'asc'),
        		7,		
        		'sku-13'
        	),
        	
        	array(
        		new Elastica_Query_QueryString('Lada'),
        		new Elastica_Filter_Range(
        			'price',
        			array(
        				'to'	=> 50
        			)
        		),
        		array(),
        		array('sku' => 'asc'),
        		1,		
        		'sku-4'
        	),
        	
        	array(
        		new Elastica_Query_QueryString('Lada'),
        		new Elastica_Filter_Range(
        			'price',
        			array(
        				'from'	=> 50
        			)
        		),
        		array(),
        		array('sku' => 'asc'),
        		0,		
        		'sku-4'
        	),
        	
        );
    }
	
	/**
     * @dataProvider providerComplexAttributeFilter
     */
	public function testComplexAttributeFilter(
		$search_query,
		$price_filter,
		$attributes_filter,
		$sort_by,
		$hits,
		$first_sku
	) {
		
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		
		$type = $elasticaIndex->getType(self::INDEX_TYPE);
		
		$filter_and = new Elastica_Filter_And();
		
		if ($price_filter) {
			$filter_and->addFilter($price_filter);
		}
		
		foreach ($attributes_filter as $attribute => $values) {
		
			$filter_or = new Elastica_Filter_Or();
			foreach ($values as $value ) {
		        $term = new Elastica_Filter_Term();
		        $term->setTerm($attribute, $value);
		        
		        $filter_or->addFilter($term);
			}
			
			$filter_and->addFilter($filter_or);
		}
		
		$log = new Elastica_Log($this->_elasticaClient);
		$log->log( var_export($filter_and->toArray(), 1));
        
        
        // Filtered query using the query string and a filter
		$filteredQuery = new Elastica_Query_Filtered(
		   $search_query,
		   $filter_and
		);	
		
		$elasticaQuery = new Elastica_Query($filteredQuery);
		
		if ( $sort_by ) {
			$elasticaQuery->addSort($sort_by);
		}

		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);

		// check number of hit
		$this->assertEquals($hits, $elasticaResultSet->getTotalHits());
		
		if ( $elasticaResultSet->getTotalHits() > 0 ) {
			// check first expected sku
			$first_doc = current($elasticaResultSet->getResults());
			$data = $first_doc->getData();
			$this->assertEquals($first_sku, $data['sku']);
		}
		
	}
	
}