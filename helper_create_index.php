<?php
/**
 * 
 */
/**
 * This class provides a facility for creating ES index for testing puposes 
 */
class HelperCreateIndex {
	
	const INDEX_NAME='test_index';
	const INDEX_TYPE='test_type';
	
    static $_instance;
    
    protected $_elasticaClient=null;
 
 
    /**
     *
     * @return HelperCreateIndex
     */
    static function getInstance() {
        if (! self::$_instance) {
            self::$_instance = new HelperCreateIndex ( );
        }
        return self::$_instance;
    }
    
    public function setElasticaClient($_elastic_client) {
    	$this->_elasticaClient=$_elastic_client;
    	
    	return $this;
    }
    
    /**
     * 
     * @return HelperCreateIndex
     */
    public function createIndex() {
    	
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
			'index'	=> 'not_analyzed',
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
			'index'	=> 'not_analyzed',
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
		$product_description[$i++] = 'Alfa Romeo is an Italian manufacturer of cars. Founded as A.L.F.A. (Anonima Lombarda Fabbrica Automobili) on June 24, 1910, in Milan, [2] the company has been involved in car racing since 1911, and has a reputation for building expensive sports cars.[3] The company was owned by Italian state holding company Istituto per la Ricostruzione Industriale between 1932 and 1986, when it became a part of the Fiat Group,[4] and since February 2007 a part of Fiat Group Automobiles S.p.A.';
		$product_description[$i++] = 'Mercedes is the pre-1927 brand name of German automobile models and engines built by Daimler company';
		$product_description[$i++] = 'Skoda is the automobile manufacturer in the Czech Republic (also the main article about �koda vehicles)';
		$product_description[$i++] = 'Lada is a trademark of the Russian car manufacturer AvtoVAZ based in Tolyatti, Samara Oblast. It was originally the export brand for the models it sold under the Zhiguli name in the domestic Soviet market since June 1970. All AvtoVAZ vehicles are currently sold under the Lada brand.';
		$product_description[$i++] = 'Audi and its subsidiaries design, engineer, manufacture and distribute automobiles and motorcycles under the Audi, Ducati and Lamborghini brands. Audi oversees worldwide operations from its headquarters in Ingolstadt, Bavaria, Germany. Audi-branded vehicles are produced in seven production facilities worldwide; Ducati and Lamborghini each have one production facility located in Italy.';
		$product_description[$i++] = 'Fiat S.p.A., (Fabbrica Italiana Automobili Torino)[4] (Italian Automobile Factory of Turin), is an Italian automobile manufacturer based in Turin. Fiat was founded in 1899 by a group of investors including Giovanni Agnelli. During its more than a century long history, Fiat has also manufactured railway engines and carriages, military vehicles, and aircraft. As of 2009, the Fiat group (not inclusive of its subsidiary Chrysler) was the world\'s ninth largest carmaker and the largest in Italy.[5]';
		
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
				'desctiption' => (isset($product_description[$i]) ? $product_description[$i]: sprintf("Lorem Ipsum shitty nitt pit.")),
			
				'yesno' 	=> ($product_yesno[1+(($i-1)%count($product_yesno))]),
				'size' 		=> ($product_size[1+(($i-1)%count($product_size))]),
				'age' 		=> ($product_age[1+(($i-1)%count($product_age))]),
				'colour' 	=> ($product_colour[1+(($i-1)%count($product_colour))]),
			));
			
			$elasticaType->addDocument($document);
		}
		
		$this->_elasticaClient->getIndex(self::INDEX_NAME)->refresh();
		
		return $this;
		
    }
    
    /**
     * 
     * @return HelperCreateIndex
     */
    public function deleteIndex() {
    	$this->_elasticaClient->getIndex(self::INDEX_NAME)->delete();
    	
    	return $this;
    }
    
}