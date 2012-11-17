<?php
/**
 * Sample test case.
 */
class AbilityToSearchByQueryTest extends PHPUnit_Framework_TestCase
{

	protected $_elasticaClient = null;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		// connect to elastic search cluster
		$this->_elasticaClient = new Elastica_Client(array(
			'servers' => array(
				array('host' => 'localhost', 'port' => 9200),
				array('host' => 'localhost', 'port' => 9201)
			)
		));

		// load index
		$elasticaIndex = $this->_elasticaClient->getIndex('AbilityToSearchByQuery');
		
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
		
		
		// define mapping
		
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->_elasticaClient->getIndex('AbilityToSearchByQuery')->delete();
		$this->_elasticaClient = null;
	}


	public function testAbilityToSearchByQuery() {
		$this->markTestAsIncomlete();
	}

}

