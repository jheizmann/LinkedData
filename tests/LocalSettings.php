/**
 * @file
 * @ingroup LinkedData_Tests
 */

$smwgDeployVersion = false;

//$smwgMessageBroker='localhost';
$smwgWebserviceEndpoint='localhost:8092';
$smwgEnableObjectLogicRules=true;
$smwgWebserviceProtocol="rest";

#Import SMW, SMWHalo and the Gardening extension
include_once('extensions/SemanticMediaWiki/includes/SMW_Settings.php');
enableSemantics('http://wiki', true);
 
include_once('extensions/SMWHalo/includes/SMW_Initialize.php');
enableSMWHalo('SMWHaloStore2', "SMWTripleStoreQuad");

include_once('extensions/ARCLibrary/ARCLibrary.php');
enableARCLibrary();

include_once('extensions/LinkedData/includes/LOD_Initialize.php');
enableLinkedData(); 

