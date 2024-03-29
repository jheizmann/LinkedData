/**
 * @file
 * @ingroup LinkedData_Tests
 */

$smwgDeployVersion = false;
$smwghConvertColoumns="utf8";

include_once('deployment/Deployment.php');

include_once('extensions/ARCLibrary/ARCLibrary.php');
enableARCLibrary();

//$smwgMessageBroker='localhost';
$smwgHaloWebserviceEndpoint='localhost:8092';
$smwgHaloEnableObjectLogicRules=true;
$smwgWebserviceProtocol="rest";
define('SMWH_FORCE_TS_UPDATE', true);

#Import SMW, SMWHalo and the Gardening extension
include_once('extensions/SemanticMediaWiki/includes/SMW_Settings.php');
enableSemantics('http://wiki', true);
 
include_once('extensions/SMWHalo/includes/SMW_Initialize.php');
enableSMWHalo('SMWHaloStore2', "SMWTripleStoreQuad");

include_once('extensions/LinkedData/includes/LOD_Initialize.php');
enableLinkedData(); 

###Each extension wich depends on SMWHalo depends also on arclibrary, scriptmanager and deployment framework####
require_once('deployment/Deployment.php');
require_once("extensions/ScriptManager/SM_Initialize.php");
include_once('extensions/ARCLibrary/ARCLibrary.php');
enableARCLibrary();
################################################################################################################
