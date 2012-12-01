<?php
/**
 *  test case.
 */
class FacetsTest extends PHPUnit_Framework_TestCase
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

	public function providerMatchAllAndReturnFacet()
    {
        return array(
         array('yesno', array('yes'=>50, 'no'=>50), 100),
         array('size', array('S'=>34, 'L'=>33, 'M'=>33), 100),
         array('size', array('S'=>34, 'L'=>33, 'M'=>33), 100),
         array('age', array('baby'=>25, 'teenager'=>25, 'adult'=>25, 'retired'=>25), 100)
        );
    }
	
	/**
     * @dataProvider providerMatchAllAndReturnFacet
     */
	public function testMatchAllAndReturnFacet($attribute, $terms, $hits) {
		
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		$type = $elasticaIndex->getType(self::INDEX_TYPE);
		
		// search query
		$matchAllQuery = new Elastica_Query_MatchAll();
		$elasticaQuery = new Elastica_Query($matchAllQuery);
		
		// add facet
		$facename = $attribute . '_facet';
		$elasticaFacet 	= new Elastica_Facet_Terms($facename);
		$elasticaFacet->setField($attribute);
		$elasticaFacet->setOrder('reverse_count');

		$elasticaQuery->addFacet($elasticaFacet);
		
		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);

		// getting result
		
		// check number of hit
		$this->assertEquals($hits, $elasticaResultSet->getTotalHits());
		
		// check number facet
		$elasticaFacets = $elasticaResultSet->getFacets();
		$found_terms = $elasticaFacets[$facename]['terms'];
		
		$this->assertEquals(count($found_terms), count($terms));
		foreach ( $found_terms as $found_term ) {
			$this->assertContains($found_term['term'], array_keys($terms));
			$this->assertEquals($found_term['count'], $terms[$found_term['term']]);
		} 
	}
	
	public function providerComplexAttributeFilterWithFaces()
    {
        return array(
        	
        	array(
        		array(
	        		'yesno' => array('yes'),
        		),
        		array(
//        			'yesno' => array(
//        				'yes'	=> 0,
//        				'no'	=> 50
//        			),
        			'size'	=> array(
        				'S'	=> 17,
	        			'M'	=> 17,
	        			'L'	=> 16,
        			)
        		),
        		50
        	),
        	
//        	array(
//        		array(
//	        		'yesno' => array('yes'),
//	        		'size' =>  array('S'),
//	        		'age' =>  array('baby'),
//	        	),
//        		9
//        	),
//        	array(
//        		array(
//	        		'yesno' => array('yes'),
//	        		'size' => array('S'),
//	        		'age' => array('baby'),
//	        		'colour' => array('Green'),
//	        	),
//        		1
//        	),
//        	array(
//        		array(
//	        		'yesno' => array('yes'),
//	        		'size' => array('S'),
//	        		'age' => array('baby'),
//	        		'colour' => array('Green', 'Yellow'),
//	        	),
//        		3
//        	),
//        	array(
//        		array(
//	        		'yesno' => array('yes'),
//	        		'size' => array('S', 'L'),
//	        		'age' => array('baby'),
//	        		'colour' => array('Green', 'Yellow'),
//	        	),
//        		6
//        	),
        );
    }
	
	/**
     * @dataProvider providerComplexAttributeFilterWithFaces
     */
	public function testComplexAttributeFilterWithFaces($attributes, $facets, $hits) {
		
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
		
		// add facet
		
		foreach ($facets as $attribute => $values) {
			$facename = $attribute;
			$elasticaFacet 	= new Elastica_Facet_Terms($facename);
			$elasticaFacet->setField($attribute);
			$elasticaFacet->setOrder('reverse_count');
			$elasticaFacet->setAllTerms(true);
			
			$elasticaQuery->addFacet($elasticaFacet);
			
		}

		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);

		// getting result
		
		// check number of hit
		$this->assertEquals($hits, $elasticaResultSet->getTotalHits());
		
		// check number facet
		$elasticaFacets = $elasticaResultSet->getFacets();
		
		$log = new Elastica_Log($this->_elasticaClient);
		$log->log(var_export($elasticaFacets, 1));
		
		$this->assertEquals(count($elasticaFacets), count($facets));
		foreach ( $elasticaFacets as $found_attribute => $found_terms ) {
			$this->assertEquals(count($found_terms['terms']), count($facets[$found_attribute]));
			
			$terms = $facets[$found_attribute];
			
			foreach ( $found_terms['terms'] as $found_term ) {
				
				$this->assertContains($found_term['term'], array_keys($terms));
				foreach ($terms as $term=>$term_count) {
					if ( $term == $found_term['term']) {
						$this->assertEquals($found_term['count'], $term_count);
					}
				}
			}
		} 
			
	}
	
}