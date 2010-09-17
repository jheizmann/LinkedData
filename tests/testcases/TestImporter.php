<?php
/**
 * @file
 * @ingroup LinkedData_Tests
 */

require_once 'PHPUnit/Framework.php';


/**
 * This class tests the class LODImporter
 */
class TestImporter extends PHPUnit_Framework_TestCase {

	protected $backupGlobals = FALSE;
	
	private static $inUrl = "http://mes.cdn.s3.amazonaws.com/smw-lde/ABA-Ace.rdf";
	private static $inContentType = "application/rdf+xml";
	private $importer;
	private $store;
	private $mappingStore;
	private $lsd1;
	private $lsd2;
	private $mapping;

	function setUp() {
		$this->importer = new LODImporter();
		$this->store = LODAdministrationStore::getInstance();
		$this->mappingStore = new LODMappingTripleStore();
		LODMappingStore::setStore($this->mappingStore);
		$this->lsd1 = new LODSourceDefinition("ds1");
		$this->lsd2 = new LODSourceDefinition("ds2");
		$this->store->storeSourceDefinition($this->lsd1);
		$this->store->storeSourceDefinition($this->lsd2);

		$this->mappingText = <<<END
@prefix r2r: <http://www4.wiwiss.fu-berlin.de/bizer/r2r/> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix foaf: <http://xmlns.com/foaf/0.1/> .
@prefix dbpedia: <http://dbpedia.org/ontology/> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix mp: <http://www4.wiwiss.fu-berlin.de/R2Rmappings/> .
@prefix smw: <http://www.example.org/smw/> .
@prefix brain: <http://brain-map.org/gene/0.1#> .

mp:BrainGeneToSmwGene
a r2r:ClassMapping ;
r2r:prefixDefinitions  "brain: <http://brain-map.org/gene/0.1#> . smw: <http://www.example.org/smw/>";
r2r:sourcePattern "?SUBJ a brain:gene" ;
r2r:targetPattern "?SUBJ a smw:Gene" ;
.
END;

		$this->mapping = new LODMapping($this->mappingText, "ds1", "ds2");
		$this->mappingStore->addMapping($this->mapping);
	}

	function tearDown() {
	}

	function testLoadDataFromDumpAndTranslate() {
		$temporaryGraph = $this->importer->loadDataFromDump($this->lsd1, self::$inUrl, false);
		$this->assertNotNull($temporaryGraph);
		
		$importGraph = $this->importer->translate($this->lsd1, $this->lsd2, $temporaryGraph, false);		
		$this->assertNotNull($importGraph);
	}
	
	function testLoadDataStream() {
		$input = fopen(self::$inUrl, "r");
		$temporaryGraph = $this->importer->loadData($this->lsd1, $input, self::$inContentType, false);
		$this->assertNotNull($temporaryGraph);
		fclose($input);
	}

	function testLoadDataString() {
		$input = file_get_contents(self::$inUrl);
		$temporaryGraph = $this->importer->loadData($this->lsd1, $input, self::$inContentType, false);
		$this->assertNotNull($temporaryGraph);
	}
}