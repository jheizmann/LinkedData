<?php
/**
 * @file
 * @ingroup LinkedData_Tests
 */

require_once 'PHPUnit/Framework.php';

/**
 * Test suite for the meta-data query printer.
 * Start the triple store with these options before running the test:
 * msgbroker=none client=MyStore driver=ontobroker-quad wsport=8090 console run=D:\MediaWiki\SMWTripleStore\resources\lod_wiki_tests\OntologyBrowserSparql\initDebug.sparul reasoner=owl restfulws
 * 
 * @author thsc
 *
 */
class TestTripleStoreAccessSuite extends PHPUnit_Framework_TestSuite
{
	const GRAPH = "http://example.com/booksGraph";
	
	public static $mTriples = array(
		array("ex:HitchhickersGuide", "ex:title", "The Hitchhiker's Guide to the Galaxy", "xsd:string"),
		array("ex:HitchhickersGuide", "ex:price", "10.20", "xsd:double"),
		array("ex:HitchhickersGuide", "ex:pages", "224", "xsd:int"),
		array("ex:HitchhickersGuide", "ex:reallyCool", "true", "xsd:boolean"),
		array("ex:HitchhickersGuide", "ex:published", "1979-04-02T13:41:09+01:00", "xsd:dateTime"),
		array("ex:HitchhickersGuide", "ex:amazon", "http://www.amazon.com/Hitchhikers-Guide-Galaxy-25th-Anniversary/dp/1400052920/ref=sr_1_1?ie=UTF8&s=books&qid=1272987287&sr=1-1", "xsd:anyURI")
	);
	
	
	public static function suite() {
		
		$suite = new TestTripleStoreAccessSuite();
		$suite->addTestSuite('TestTripleStoreAccess');
		$suite->addTestSuite('TestPersistentTripleStoreAccess');
		return $suite;
	}
	
	protected function setUp() {
    	
	}
	
	protected function tearDown() {
		$tsa = new LODTripleStoreAccess();
		$tsa->dropGraph(self::GRAPH);
		$tsa->flushCommands();
	}

}


/**
 * This class test the TripleStoreAccess without persistence..
 * 
 * @author thsc
 *
 */
class TestTripleStoreAccess extends PHPUnit_Framework_TestCase {

	protected $backupGlobals = FALSE;
		
    function setUp() {
    }

    function tearDown() {

    }

    /**
     * Tests the creation a LODSourceDefinition object.
     */
    function testCreateTSA() {
    	$tsa = new LODTripleStoreAccess();
    	$this->assertNotNull($tsa);
    }
    
    /**
     * Tests if the triples store is properly connected.
     */
    function testTSConnectionStatus() {
    	global $smwgWebserviceEndpoint;
    	
    	$we = $smwgWebserviceEndpoint;
    	$tsa = new LODTripleStoreAccess();
    	
    	// Verify that connection with TS fails with invalid connections settings 
    	$smwgWebserviceEndpoint = 'localhost:1234'; 
    	$connected = $tsa->isConnected();
    	$this->assertFalse($connected);
    	
    	// Verify a proper connection
    	$smwgWebserviceEndpoint = $we; 
    	$connected = $tsa->isConnected();
    	$this->assertTrue($connected);
    	
    	
    }
    
    
    /**
     * Tests operations on the triple store.
     *
     */
    function testTripleStore() {
		
    	$namespace = "http://example.com/";
    	$prefixes = "PREFIX ex:<$namespace> ".
    			  TSNamespaces::getW3CPrefixes();
    	$triples = array();
		foreach (TestTripleStoreAccessSuite::$mTriples as $t) {		
			$triples[] = new LODTriple($t[0], $t[1], $t[2], $t[3]);
		}
		$graph = TestTripleStoreAccessSuite::GRAPH;
		
		// Inserts triples into the triple store
		$tsa = new LODTripleStoreAccess();
		$tsa->addPrefixes($prefixes);
		$tsa->createGraph($graph);
		$tsa->insertTriples($graph, $triples);
		
		//***testTripleStore#1***
		// Test if SPARUL commands can be sent to triple store
		$this->assertTrue($tsa->flushCommands(), "***testTripleStore#1*** failed.");
		
		// Query inserted triples
		$query = $prefixes."SELECT ?s ?p ?o FROM <$graph> WHERE { ?s ?p ?o . }";
		
		$result = $tsa->queryTripleStore($query, $graph);
		
		//***testTripleStore#2***
		// Test if all expected variables are present
		$variables = $result->getVariables();
		$this->assertContains("s", $variables, "***testTripleStore#2.1*** failed");
		$this->assertContains("p", $variables, "***testTripleStore#2.2*** failed");
		$this->assertContains("o", $variables, "***testTripleStore#2.3*** failed");
		
		//***testTripleStore#3***
		// Test if all expected triples are present
		foreach (TestTripleStoreAccessSuite::$mTriples as $t) {
			$prop = str_replace("ex:", $namespace, $t[1]);
			$row = $result->getRowsWhere("p", $prop);
			$this->assertEquals(1, count($row), "***testTripleStore#3.1*** failed");
			$row = $row[0];
			$this->assertEquals($row->getResult("s")->getValue(), 
								str_replace("ex:", $namespace, $t[0]),
								"***testTripleStore#3.2*** failed");
			$this->assertEquals($row->getResult("o")->getValue(), 
								$t[2],
								"***testTripleStore#3.3*** failed");
		}
		
		//***testTripleStore#4***
		// Test if triples can be deleted
		
		$prop = TestTripleStoreAccessSuite::$mTriples[0][1];
		$tsa->addPrefixes($prefixes);
		$tsa->deleteTriples($graph, "?s $prop ?o", "?s $prop ?o");
		$this->assertTrue($tsa->flushCommands(), "***testTripleStore#4.1*** failed.");
		$query = $prefixes."SELECT ?s ?o FROM <$graph> WHERE { ?s $prop ?o . }";
		
		$result = $tsa->queryTripleStore($query, $graph);
		// Make sure the triple is deleted.
		$this->assertEquals(0, count($result->getRows()), "***testTripleStore#4.2*** failed.");

		// Make sure that another triple is still available
		$prop = TestTripleStoreAccessSuite::$mTriples[1][1];
		$query = $prefixes."SELECT ?s ?o FROM <$graph> WHERE { ?s $prop ?o . }";
		$result = $tsa->queryTripleStore($query, $graph);
		$this->assertNotNull($result, "***testTripleStore#4.3*** failed.");
		$this->assertEquals(1, count($result->getRows()), "***testTripleStore#4.4*** failed.");
		
		//***testTripleStore#5***
		// Test if the complete graph can be deleted.
		$tsa->dropGraph($graph);
		$tsa->flushCommands();
		
		$query = $prefixes."SELECT ?s ?p ?o FROM <$graph> WHERE { ?s ?p ?o . }";
		
		$result = $tsa->queryTripleStore($query, $graph);
		// Make sure the graph is deleted.
		$this->assertTrue($result == null || count($result->getRows()) == 0, "***testTripleStore#5*** failed.");
		
    }
    
}


/**
 * This class test the TripleStoreAccess with persistence.
 * 
 * @author thsc
 *
 */
class TestPersistentTripleStoreAccess extends PHPUnit_Framework_TestCase {

	protected $backupGlobals = FALSE;
		
    function setUp() {
    }

    function tearDown() {
		LODStorage::getDatabase()->deleteAllPersistentTriples();
    }
    
    /**
     * The persistent triple store access is implemented in the class 
     * LODPersistentTripleStoreAccess. Check if this class can be created.
     */
    function testCreatePTSA() {
    	$ptsa = new LODPersistentTripleStoreAccess();
    	$this->assertNotNull($ptsa);
    	
    }
    
    /**
     * Persistent data for the triple store is stored in a MySQL table.
     * Check if the table exists.
     * 
     */
    function testPersistenceDB() {
    	$tableName = 'lod_triple_persistence';
    	
		$db =& wfGetDB( DB_SLAVE );
		$sql = "show tables like '$tableName';";

		$res = $db->query($sql);
		$num = $res->numRows();
		$db->freeResult($res);
		
		$this->assertEquals(1, $num, "The database table for persistent triples does not exist.");
    }
    
    /**
     * Check if triples that are added to the triple store are stored in the
     * database.
     */
    function testPersistTriples() {
    	$ptsa = new LODPersistentTripleStoreAccess();
		
    	$namespace = "http://example.com/";
    	$prefixes = "PREFIX ex:<$namespace> ".
    			  TSNamespaces::getW3CPrefixes();
    	$triples = array();
		foreach (TestTripleStoreAccessSuite::$mTriples as $t) {		
			$triples[] = new LODTriple($t[0], $t[1], $t[2], $t[3]);
		}
		$graph = TestTripleStoreAccessSuite::GRAPH;
		
		// Inserts triples into the triple store
		$ptsa->addPrefixes($prefixes);
		$ptsa->createGraph($graph);
		$ptsa->insertTriples($graph, $triples);
		
		//***testPersistentTripleStore#1***
		// Test if SPARUL commands can be sent to triple store
		$this->assertTrue($ptsa->flushCommands("TestTripleStoreAccess", "ID-1"), "***testPersistentTripleStore#1*** failed.");
		
		
		//***testPersistentTripleStore#2***
		// Test if the database contains the expected content
		
		$expected = <<<EXP
@prefix ex: <http://example.com/> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .

<http://example.com/booksGraph> {
	ex:HitchhickersGuide ex:title "The Hitchhiker's Guide to the Galaxy"^^xsd:string . 
	ex:HitchhickersGuide ex:price "10.20"^^xsd:double . 
	ex:HitchhickersGuide ex:pages "224"^^xsd:int . 
	ex:HitchhickersGuide ex:reallyCool "true"^^xsd:boolean . 
	ex:HitchhickersGuide ex:published "1979-04-02T13:41:09+01:00"^^xsd:dateTime . 
	ex:HitchhickersGuide ex:amazon "http://www.amazon.com/Hitchhikers-Guide-Galaxy-25th-Anniversary/dp/1400052920/ref=sr_1_1?ie=UTF8&s=books&qid=1272987287&sr=1-1"^^xsd:anyURI . 
}
EXP;
		$this->compareContent("TestTripleStoreAccess", "ID-1", $expected, "***testPersistentTripleStore#2*** failed.");
		
    }

    /**
     * Check if triples that are added to the triple store are stored in the
     * database. Different graphs, sets of triples and IDs are tested
     */
    function testPersistTriples2() {
    	$ptsa = new LODPersistentTripleStoreAccess();
		
    	$namespace = "http://example.com/";
    	$prefixes = "PREFIX ex:<$namespace> ".
    			  TSNamespaces::getW3CPrefixes();
    	$triples = array();
		foreach (TestTripleStoreAccessSuite::$mTriples as $t) {		
			$triples[] = new LODTriple($t[0], $t[1], $t[2], $t[3]);
		}
		$graph = TestTripleStoreAccessSuite::GRAPH;
		
		// Inserts triples into the triple store
		$ptsa->addPrefixes($prefixes);
		$ptsa->createGraph($graph);
		$ptsa->insertTriples($graph, $triples);
		
		//***testPersistTriples2#1***
		// Test if SPARUL commands can be sent to triple store
		$this->assertTrue($ptsa->flushCommands("TestTripleStoreAccess", "ID-2"), "***testPersistTriples2#1*** failed.");

		$graph = "http://example.com/anotherBooksGraph";
		
		// Inserts triples into the triple store
		$ptsa->addPrefixes($prefixes);
		$ptsa->createGraph($graph);
		// Insert all triples separately
		$triples = array();
		foreach (TestTripleStoreAccessSuite::$mTriples as $t) {		
			$triples[0] = new LODTriple($t[0], $t[1], $t[2], $t[3]);
			$ptsa->insertTriples($graph, $triples);
		}
		
		//***testPersistTriples2#2***
		// Test if SPARUL commands can be sent to triple store
		$this->assertTrue($ptsa->flushCommands("TestTripleStoreAccess", "ID-2"), "***testPersistTriples2#2*** failed.");
		
		
		//***testPersistTriples2#3***
		// Test if the database contains the expected content
		
		$expected = <<<EXP
@prefix ex: <http://example.com/> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .

<http://example.com/booksGraph> {
	ex:HitchhickersGuide ex:title "The Hitchhiker's Guide to the Galaxy"^^xsd:string . 
	ex:HitchhickersGuide ex:price "10.20"^^xsd:double . 
	ex:HitchhickersGuide ex:pages "224"^^xsd:int . 
	ex:HitchhickersGuide ex:reallyCool "true"^^xsd:boolean . 
	ex:HitchhickersGuide ex:published "1979-04-02T13:41:09+01:00"^^xsd:dateTime . 
	ex:HitchhickersGuide ex:amazon "http://www.amazon.com/Hitchhikers-Guide-Galaxy-25th-Anniversary/dp/1400052920/ref=sr_1_1?ie=UTF8&s=books&qid=1272987287&sr=1-1"^^xsd:anyURI . 
}

@prefix ex: <http://example.com/> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .

<http://example.com/anotherBooksGraph> {
	ex:HitchhickersGuide ex:title "The Hitchhiker's Guide to the Galaxy"^^xsd:string . 
	ex:HitchhickersGuide ex:price "10.20"^^xsd:double . 
	ex:HitchhickersGuide ex:pages "224"^^xsd:int . 
	ex:HitchhickersGuide ex:reallyCool "true"^^xsd:boolean . 
	ex:HitchhickersGuide ex:published "1979-04-02T13:41:09+01:00"^^xsd:dateTime . 
	ex:HitchhickersGuide ex:amazon "http://www.amazon.com/Hitchhikers-Guide-Galaxy-25th-Anniversary/dp/1400052920/ref=sr_1_1?ie=UTF8&s=books&qid=1272987287&sr=1-1"^^xsd:anyURI . 
}
EXP;
		$this->compareContent("TestTripleStoreAccess", "ID-2", $expected, "***testPersistTriples2#3*** failed.");
		
    }


    /**
     * Check if triples that were added persistently to the triple store are 
     * deleted from the database and the triple store. 
     */
    function testDeletePersistentTriples() {
    	$ptsa = new LODPersistentTripleStoreAccess();
		
    	$namespace = "http://example.com/";
    	$prefixes = "PREFIX ex:<$namespace> ".
    			  TSNamespaces::getW3CPrefixes();
    	$triples = array();
		foreach (TestTripleStoreAccessSuite::$mTriples as $t) {		
			$triples[] = new LODTriple($t[0], $t[1], $t[2], $t[3]);
		}
		$graph = TestTripleStoreAccessSuite::GRAPH;
		
		// Inserts triples into the triple store
		$ptsa->addPrefixes($prefixes);
		$ptsa->createGraph($graph);
		$ptsa->insertTriples($graph, $triples);
		
		//***testDeletePersistentTriples#1***
		// Test if SPARUL commands can be sent to triple store
		$this->assertTrue($ptsa->flushCommands("TestTripleStoreAccess", "ID-1"),
				 "***testDeletePersistentTriples#1*** failed.");

		// Inserts triples into the triple store
		$graph = "http://example.com/anotherBooksGraph";
		$ptsa->addPrefixes($prefixes);
		$ptsa->createGraph($graph);
		$ptsa->insertTriples($graph, $triples);
		
		//***testDeletePersistentTriples#2***
		// Test if SPARUL commands can be sent to triple store
		$this->assertTrue($ptsa->flushCommands("TestTripleStoreAccess", "ID-2"),
				"***testDeletePersistentTriples#2*** failed.");
		
		$graph = "http://example.com/yetAnotherBooksGraph";
		
		// Inserts triples into the triple store
		$ptsa->addPrefixes($prefixes);
		$ptsa->createGraph($graph);
		// Insert all triples separately
		$triples = array();
		foreach (TestTripleStoreAccessSuite::$mTriples as $t) {		
			$triples[0] = new LODTriple($t[0], $t[1], $t[2], $t[3]);
			$ptsa->insertTriples($graph, $triples);
		}
		
		//***testDeletePersistentTriples#3***
		// Test if SPARUL commands can be sent to triple store
		$this->assertTrue($ptsa->flushCommands("TestTripleStoreAccess", "ID-2"),
				"***testDeletePersistentTriples#3*** failed.");
		
		//***testDeletePersistentTriples#4***
		// Test if all triples for TestTripleStoreAccess,ID-1 can be deleted
		$ptsa->deletePersistentTriples("TestTripleStoreAccess", "ID-1");
		
		// Test if the persisted triples are deleted from the triple store
		$graph = TestTripleStoreAccessSuite::GRAPH;
		$query = $prefixes."SELECT ?s ?p ?o FROM <$graph> WHERE { ?s ?p ?o . }";
		$result = $ptsa->queryTripleStore($query, $graph);
		// please note that this test will fail until
		// http://smwforum.ontoprise.com/smwbugs/show_bug.cgi?id=12784
		// has been implemented.
		$this->assertEquals(0, count($result->getRows()), 
				"Triples were not deleted from the triple store for $graph.");
		
		// Test if the database contains the expected content i.e. no more triples
		// for ID-1
		$this->compareContent("TestTripleStoreAccess", "ID-1", "", 
				"***testDeletePersistentTriples#4.1*** failed.");
		
		$expected = <<<EXP
@prefix ex: <http://example.com/> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .

<http://example.com/anotherBooksGraph> {
	ex:HitchhickersGuide ex:title "The Hitchhiker's Guide to the Galaxy"^^xsd:string . 
	ex:HitchhickersGuide ex:price "10.20"^^xsd:double . 
	ex:HitchhickersGuide ex:pages "224"^^xsd:int . 
	ex:HitchhickersGuide ex:reallyCool "true"^^xsd:boolean . 
	ex:HitchhickersGuide ex:published "1979-04-02T13:41:09+01:00"^^xsd:dateTime . 
	ex:HitchhickersGuide ex:amazon "http://www.amazon.com/Hitchhikers-Guide-Galaxy-25th-Anniversary/dp/1400052920/ref=sr_1_1?ie=UTF8&s=books&qid=1272987287&sr=1-1"^^xsd:anyURI . 
}

@prefix ex: <http://example.com/> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .

<http://example.com/yetAnotherBooksGraph> {
	ex:HitchhickersGuide ex:title "The Hitchhiker's Guide to the Galaxy"^^xsd:string . 
	ex:HitchhickersGuide ex:price "10.20"^^xsd:double . 
	ex:HitchhickersGuide ex:pages "224"^^xsd:int . 
	ex:HitchhickersGuide ex:reallyCool "true"^^xsd:boolean . 
	ex:HitchhickersGuide ex:published "1979-04-02T13:41:09+01:00"^^xsd:dateTime . 
	ex:HitchhickersGuide ex:amazon "http://www.amazon.com/Hitchhikers-Guide-Galaxy-25th-Anniversary/dp/1400052920/ref=sr_1_1?ie=UTF8&s=books&qid=1272987287&sr=1-1"^^xsd:anyURI . 
}
EXP;
		$this->compareContent("TestTripleStoreAccess", "ID-2", $expected, 
				"***testDeletePersistentTriples#4.2*** failed.");
		
		//***testDeletePersistentTriples#5***
		// Test if all triples for TestTripleStoreAccess,ID-2 can be deleted
		$ptsa->deletePersistentTriples("TestTripleStoreAccess", "ID-2");
		
		// Test if the database contains the expected content
		$this->compareContent("TestTripleStoreAccess", "ID-1", "", 
				"***testDeletePersistentTriples#5.1*** failed.");
		$this->compareContent("TestTripleStoreAccess", "ID-2", "", 
				"***testDeletePersistentTriples#5.2*** failed.");
		
		// Test if the persisted triples are deleted from the triple store
		$graph = "http://example.com/anotherBooksGraph";
		$query = $prefixes."SELECT ?s ?p ?o FROM <$graph> WHERE { ?s ?p ?o . }";
		$result = $ptsa->queryTripleStore($query, $graph);
		$this->assertEquals(0, count($result->getRows()), 
				"Triples were not deleted from the triple store for $graph.");
		
		$graph = "http://example.com/yetAnotherBooksGraph";
		$query = $prefixes."SELECT ?s ?p ?o FROM <$graph> WHERE { ?s ?p ?o . }";
		$result = $ptsa->queryTripleStore($query, $graph);
		$this->assertEquals(0, count($result->getRows()), 
				"Triples were not deleted from the triple store for $graph.");
				
		
    }
    
    
    
    /**
     * Compares the content in the database for $component and $id with the $expected
     * result and prints the $errMsg if the strings do not match.
     */
    private function compareContent($component, $id, $expected, $errMsg) {
		
		// Read the generated TriG from the database
		$store = LODStorage::getDatabase();
		$trigs = $store->readPersistentTriples($component, $id);
		$trig = "";
		foreach($trigs as $t) {
			$trig .= $t;
		}
		
		// Remove whitespaces
		$trig = preg_replace("/\s*/", "", $trig);
		$expected = preg_replace("/\s*/", "", $expected);
		
		$this->assertEquals($expected, $trig, $errMsg);
		
    }
}