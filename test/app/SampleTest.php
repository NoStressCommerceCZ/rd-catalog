<?php
/**
 * Sample test case.
 */
class SampleTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var Sample
	 */
	private $Sample;


	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->Sample = new Sample();
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Sample = null;

		parent::tearDown();
	}

	public function testReturnOne() {
		$this->assertEquals(1, $this->Sample->returnOne());
	}
	
	public function testPushAndPop()
    {
        $stack = array();
        $this->assertEquals(0, count($stack));
 
        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));
 
        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }


}

