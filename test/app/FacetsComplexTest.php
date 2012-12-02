<?php
/**
 *  test case.
 */
class FacetsComplexTest extends PHPUnit_Framework_TestCase
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

	public function providerComplexAttributeFilterWithFaces()
    {
        return array(
        	
        	array(
        		new Elastica_Query_MatchAll(),
        		array(
	        		'yesno' => array('yes'),
        		),
        		array(
        			'yesno' => array(
        				'yes'	=> 50,
        				'no'	=> 50
        			),
        			'size'	=> array(
        				'S'	=> 17,
	        			'M'	=> 17,
	        			'L'	=> 16,
        			)
        		),
        		50
        	),

        	array(
        		new Elastica_Query_MatchAll(),
        		array(
	        		'yesno' => array('yes', 'no'),
        		),
        		array(
        			'yesno' => array(
        				'yes'	=> 50,
        				'no'	=> 50
        			),
        			'size'	=> array(
        				'S'	=> 34,
	        			'M'	=> 33,
	        			'L'	=> 33,
        			)
        		),
        		100
        	),
        	
        	array(
        		new Elastica_Query_MatchAll(),
        		array(
	        		'yesno' => array('yes'),
        			'size'  => array('S'),
        		),
        		array(
        			'yesno' => array(
        				'yes'	=> 17,
        				'no'	=> 17
        			),
        			'size'	=> array(
        				'S'	=> 17,
	        			'M'	=> 17,
	        			'L'	=> 16,
        			)
        		),
        		17
        	),
        	
        	array(
        		new Elastica_Query_MatchAll(),
        		array(
	        		'yesno' => array('yes'),
        			'size'  => array('S', 'L'),
        		),
        		array(
        			'yesno' => array(
        				'yes'	=> 33,
        				'no'	=> 34
        			),
        			'size'	=> array(
        				'S'	=> 17,
	        			'M'	=> 17,
	        			'L'	=> 16,
        			)
        		),
        		33
        	),

        	array(
        		new Elastica_Query_MatchAll(),
        		array(
	        		'yesno' => array('yes'),
        			'size'  => array('S', 'L'),
        		),
        		array(
        			'yesno' => array(
        				'yes'	=> 33,
        				'no'	=> 34
        			),
        			'size'	=> array(
        				'S'	=> 17,
	        			'M'	=> 17,
	        			'L'	=> 16,
        			)
        		),
        		33
        	),
        	
			array(
        		new Elastica_Query_QueryString('Lorem'),
        		array(
	        		'yesno' => array('yes'),
        			'size'  => array('S'),
        		),
        		array(
        			'yesno' => array(
        				'yes'	=> 16,
        				'no'	=> 16
        			),
        			'size'	=> array(
        				'S'	=> 16,
	        			'M'	=> 16,
	        			'L'	=> 15,
        			),
        		),
        		16
        	),
        	
        );
    }
	
	/**
     * @dataProvider providerComplexAttributeFilterWithFaces
     */
	public function testComplexAttributeFilterWithFaces($search_query, $attributes, $facets, $hits) {
		
		$elasticaIndex = $this->_elasticaClient->getIndex(self::INDEX_NAME);
		
		$type = $elasticaIndex->getType(self::INDEX_TYPE);
		
		// search query
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
		   $search_query,
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
			$elasticaFacet->setGlobal(true);	// ignore original query
			
			if ( !empty($attributes) ) {
				$filter_and = new Elastica_Filter_And();
				$filter_and->addFilter(
					new Elastica_Filter_Query($search_query)
				);	// !!!
				
				foreach ($attributes as $filter_attribute => $values) {
				
					if ($attribute == $filter_attribute ) continue;
					
					$filter_or = new Elastica_Filter_Or();
					foreach ($values as $value ) {
				        $term = new Elastica_Filter_Term();
				        $term->setTerm($filter_attribute, $value);
				        
				        $filter_or->addFilter($term);
					}
					
					$filter_and->addFilter($filter_or);
				}
				
				$elasticaFacet->setFilter($filter_and);
				
				$elasticaQuery->addFacet($elasticaFacet);
			}
		}

		// let's do search 
		$elasticaResultSet 	= $type->search($elasticaQuery);

		// getting result
		
		// check number of hit
		$this->assertEquals($hits, $elasticaResultSet->getTotalHits());
		
		// check number facet
		$elasticaFacets = $elasticaResultSet->getFacets();
				
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