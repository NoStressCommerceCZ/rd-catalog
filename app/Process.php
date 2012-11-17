<?php
/**
 *
 * Enter description here ...
 * @author xyz
 *
 */
/**
 *
 * Enter description here ...
 * @author xyz
 *
 */
class Process {

	protected $_docs = null;
	protected $_es_index = null;
	protected $_line1 = null;
	protected $_elastica_client = null;

	public function run($input_file) {
		
		include_once 'autoload.php';
		
		$this->_elastica_client = new Elastica_Client(array(
			'servers' => array(
				array('host' => 'localhost', 'port' => 9200),
				array('host' => 'localhost', 'port' => 9201)
			)
		));
		
		$this->loadingDataFromCsvFile($input_file);
		
		
	}

	/**
	 * 
	 * Load data from input file into memory
	 * 
	 * @param string $input_file
	 */
	public function loadingDataFromCsvFile($input_file) {
		
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
		
		$this->_line1 = $line1;
		$this->_es_index = $es_index;
		$this->_docs = $docs;
		
		return $this;
	}
	
	

}