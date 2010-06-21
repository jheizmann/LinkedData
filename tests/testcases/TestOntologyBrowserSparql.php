<?php
/**
 * @file
 * @ingroup LinkedData_Tests
 */

require_once 'PHPUnit/Framework.php';
require_once 'extensions/SMWHalo/specials/SMWOntologyBrowser/SMW_OntologyBrowserAjaxAccess.php';

/**
 * This class tests the Ajax functions of the Ontology Browser for retrieving schema
 * information via SPARQL.
 * The triple store must be running for this test.
 *
 * @author thsc
 *
 */
class TestOntologyBrowserSparql extends PHPUnit_Framework_TestCase {

	protected $backupGlobals = FALSE;

	protected static $mBaseURI = 'http://www.example.org/smw-lde/';
	protected $mGraph1 = "http://smw/ToyotaGraph";
	protected $mGraph2 = "http://smw/VolkswagenGraph";
	protected $mProvGraph;
	protected $mDSIGraph;

	protected $mFilePath = "file://D:/MediaWiki/HaloSMWExtension/extensions/LinkedData/tests/resources/OntologyBrowserSparql/";
	protected $mGraph1N3 = "ToyotaGraph.n3";
	protected $mGraph2N3 = "VolkswagenGraph.n3";
	protected $mProvGraphN3 = "ProvenanceGraph.n3";
	protected $mDSIGraphN3 = "DataSourceInformationGraph.n3";

	function setUp() {
		$this->mProvGraph = self::$mBaseURI."smwGraphs/ProvenanceGraph";
		$this->mDSIGraph = self::$mBaseURI."smwGraphs/DataSourceInformationGraph";
				
		
		$tsa = new LODTripleStoreAccess();
		$tsa->createGraph($this->mGraph1);
		$tsa->createGraph($this->mGraph2);
		$tsa->createGraph($this->mProvGraph);
		$tsa->createGraph($this->mDSIGraph);
		$tsa->loadFileIntoGraph("{$this->mFilePath}ToyotaGraph.n3", $this->mGraph1, "n3");
		$tsa->loadFileIntoGraph("{$this->mFilePath}VolkswagenGraph.n3", $this->mGraph2, "n3");
		$tsa->loadFileIntoGraph("{$this->mFilePath}ProvenanceGraph.n3", $this->mProvGraph, "n3");
		$tsa->loadFileIntoGraph("{$this->mFilePath}DataSourceInformationGraph.n3", $this->mDSIGraph, "n3");
		$tsa->flushCommands();
		 
	}

	function tearDown() {
		$tsa = new LODTripleStoreAccess();
		$tsa->dropGraph($this->mGraph1);
		$tsa->dropGraph($this->mGraph2);
		$tsa->dropGraph($this->mProvGraph);
		$tsa->createGraph($this->mDSIGraph);
		$tsa->flushCommands();
		 
	}

	/**
	 * Tests retrieving all instances of category "Automobile".
	 */
	function testGetInstances() {
		$wiki = wfMsg("smw_ob_source_wiki");
		$r = smwf_ob_OntologyBrowserAccess("getInstance", "Automobile##80##0", $wiki);
		$this->assertTrue(strpos($r,'title_url="Prius" title="Prius"') !== false);
		$this->assertTrue(strpos($r,'superCat="Hybrid"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') === false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') === false);
		 
		$r = smwf_ob_OntologyBrowserAccess("getInstance", "Automobile##80##0", "Toyota");
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') !== false);
		$this->assertTrue(strpos($r,'superCat="Hybrid"') !== false);
		$this->assertTrue(strpos($r,'superCat="Automobile"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') === false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') === false);
		 

		$r = smwf_ob_OntologyBrowserAccess("getInstance", "Automobile##80##0", "Volkswagen");
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') !== false);
		$this->assertTrue(strpos($r,'superCat="Hybrid"') === false);
		$this->assertTrue(strpos($r,'superCat="Automobile"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') === false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') === false);

		$r = smwf_ob_OntologyBrowserAccess("getInstance", "Automobile##80##0", "$wiki,Volkswagen,Toyota");
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') !== false);
		$this->assertTrue(strpos($r,'superCat="Hybrid"') !== false);
		$this->assertTrue(strpos($r,'superCat="Automobile"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius" title="Prius"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') !== false);
		
	}

	/**
	 * Tests retrieving all annotations of instances.
	 */
	function testGetAnnotations() {
		$wiki = wfMsg("smw_ob_source_wiki");

		// Check the content of the wiki
		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Prius##80##0", $wiki);
		$this->assertTrue(strpos($r,'title_url="HasPower" title="HasPower"') !== false);
		$this->assertTrue(strpos($r,'<![CDATA[136 ]]>') !== false);

		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Prius-II##80##0", $wiki);
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);

		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Prius-III##80##0", $wiki);
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);
		 
		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Golf-I##80##0", $wiki);
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);
		 
		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Golf-VI##80##0", $wiki);
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);
		 
		// Check the content of source "Toyota"
		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Prius##80##0", "Toyota");
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);

		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Prius-II##80##0", "Toyota");
		$this->assertTrue(strpos($r,'title_url="HasPower" title="HasPower"') !== false);
		$this->assertTrue(strpos($r,'<![CDATA[113 ]]>') !== false);

		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Prius-III##80##0", "Toyota");
		$this->assertTrue(strpos($r,'title_url="HasPower" title="HasPower"') !== false);
		$this->assertTrue(strpos($r,'<![CDATA[136 ]]>') !== false);

		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Golf-I##80##0", "Toyota");
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);
		 
		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Golf-VI##80##0", "Toyota");
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);
		 
		// Check the content of source "Volkswagen"
		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Prius##80##0", "Volkswagen");
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);

		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Prius-II##80##0", "Volkswagen");
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);
		 
		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Prius-III##80##0", "Volkswagen");
		$this->assertTrue(strpos($r,'<annotationsList isEmpty="true"') !== false);
		 
		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Golf-I##80##0", "Volkswagen");
		$this->assertTrue(strpos($r,'title_url="HasPower" title="HasPower"') !== false);
		$this->assertTrue(strpos($r,'<![CDATA[50 ]]>') !== false);

		$r = smwf_ob_OntologyBrowserAccess("getAnnotations", "Golf-VI##80##0", "Volkswagen");
		$this->assertTrue(strpos($r,'title_url="HasPower" title="HasPower"') !== false);
		$this->assertTrue(strpos($r,'<![CDATA[105 ]]>') !== false);

	}

	/**
	 * This function tests retrieving all instance with a certain property.
	 */
	function testGetInstancesUsingProperty() {
		$wiki = wfMsg("smw_ob_source_wiki");
		$r = smwf_ob_OntologyBrowserAccess("getInstancesUsingProperty", "HasPower##80##0", $wiki);
		$this->assertTrue(strpos($r,'title_url="Prius" title="Prius"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') === false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') === false);
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') === false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') === false);

		$r = smwf_ob_OntologyBrowserAccess("getInstancesUsingProperty", "HasPower##80##0", "Toyota");
		$this->assertTrue(strpos($r,'title_url="Prius" title="Prius"') === false);
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') === false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') === false);
		 
		$r = smwf_ob_OntologyBrowserAccess("getInstancesUsingProperty", "HasPower##80##0", "Volkswagen");
		$this->assertTrue(strpos($r,'title_url="Prius" title="Prius"') === false);
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') === false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') === false);
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') !== false);
		 
		$r = smwf_ob_OntologyBrowserAccess("getInstancesUsingProperty", "HasPower##80##0", "$wiki,Volkswagen,Toyota");
		$this->assertTrue(strpos($r,'title_url="Prius" title="Prius"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') !== false);
		
	}

	/**
	 * This function tests retrieving all categories for a certain instance.
	 */
	function testGetCategoryForInstance() {
		$source = wfMsg("smw_ob_source_wiki");
		$r = smwf_ob_OntologyBrowserAccess("getCategoryForInstance", ":Prius##80##0", $source);
		$this->assertTrue(strpos($r,'title_url="Automobile" title="Automobile"') !== false);
		$this->assertTrue(strpos($r,'title_url="Hybrid" title="Hybrid"') !== false);

		$r = smwf_ob_OntologyBrowserAccess("getCategoryForInstance", ":Prius-II##80##0", $source);
		$this->assertTrue(strpos($r,'title_url="Automobile" title="Automobile"') === false);
		$this->assertTrue(strpos($r,'title_url="Hybrid" title="Hybrid"') === false);

		$r = smwf_ob_OntologyBrowserAccess("getCategoryForInstance", ":Golf-I##80##0", $source);
		$this->assertTrue(strpos($r,'title_url="Automobile" title="Automobile"') === false);
		$this->assertTrue(strpos($r,'title_url="Hybrid" title="Hybrid"') === false);
		
		$source = "Toyota";
		$r = smwf_ob_OntologyBrowserAccess("getCategoryForInstance", ":Prius##80##0", $source);
		$this->assertTrue(strpos($r,'title_url="Automobile" title="Automobile"') === false);
		$this->assertTrue(strpos($r,'title_url="Hybrid" title="Hybrid"') === false);

		$r = smwf_ob_OntologyBrowserAccess("getCategoryForInstance", ":Prius-II##80##0", $source);
		$this->assertTrue(strpos($r,'title_url="Automobile" title="Automobile"') !== false);
		$this->assertTrue(strpos($r,'title_url="Hybrid" title="Hybrid"') !== false);

		$r = smwf_ob_OntologyBrowserAccess("getCategoryForInstance", ":Golf-I##80##0", $source);
		$this->assertTrue(strpos($r,'title_url="Automobile" title="Automobile"') === false);
		$this->assertTrue(strpos($r,'title_url="Hybrid" title="Hybrid"') === false);
		
		$source = "Volkswagen";
		$r = smwf_ob_OntologyBrowserAccess("getCategoryForInstance", ":Prius##80##0", $source);
		$this->assertTrue(strpos($r,'title_url="Automobile" title="Automobile"') === false);
		$this->assertTrue(strpos($r,'title_url="Hybrid" title="Hybrid"') === false);

		$r = smwf_ob_OntologyBrowserAccess("getCategoryForInstance", ":Prius-II##80##0", $source);
		$this->assertTrue(strpos($r,'title_url="Automobile" title="Automobile"') === false);
		$this->assertTrue(strpos($r,'title_url="Hybrid" title="Hybrid"') === false);

		$r = smwf_ob_OntologyBrowserAccess("getCategoryForInstance", ":Golf-I##80##0", $source);
		$this->assertTrue(strpos($r,'title_url="Automobile" title="Automobile"') !== false);
		$this->assertTrue(strpos($r,'title_url="Hybrid" title="Hybrid"') === false);
		
	}
	
	/**
	 * This function tests retrieving all instances that match a given filter.
	 */
	function testFilterBrowse() {
		$source = wfMsg("smw_ob_source_wiki");
		$r = smwf_ob_OntologyBrowserAccess("filterBrowse", "instance##Pri", $source);
		$this->assertTrue(strpos($r,'title_url="Prius" title="Prius"') !== false);

		$r = smwf_ob_OntologyBrowserAccess("filterBrowse", "instance##Pri", "Toyota");
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') !== false);
		
		$r = smwf_ob_OntologyBrowserAccess("filterBrowse", "instance##Go", "Volkswagen");
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') !== false);

		$r = smwf_ob_OntologyBrowserAccess("filterBrowse", "instance##-I", "$source,Toyota,Volkswagen");
		$this->assertTrue(strpos($r,'title_url="Prius" title="Prius"') === false);
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') === false);

		$r = smwf_ob_OntologyBrowserAccess("filterBrowse", "instance##Pri", "$source,Toyota,Volkswagen");
		$this->assertTrue(strpos($r,'title_url="Prius" title="Prius"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-II" title="Prius-II"') !== false);
		$this->assertTrue(strpos($r,'title_url="Prius-III" title="Prius-III"') !== false);
		$this->assertTrue(strpos($r,'title_url="Golf-I" title="Golf-I"') === false);
		$this->assertTrue(strpos($r,'title_url="Golf-VI" title="Golf-VI"') === false);
		
	}
	
	
}
