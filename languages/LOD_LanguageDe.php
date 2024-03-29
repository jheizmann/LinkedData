<?php
/*
 * Copyright (C) Vulcan Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @file
 * @ingroup LinkedData_Language
 */
/*
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if (!defined('MEDIAWIKI')) die();

global $lodgIP;
include_once($lodgIP . '/languages/LOD_Language.php');


/**
 * German language labels for important LinkedData labels (namespaces, ,...).
 *
 * @author Thomas Schweitzer
 */
class LODLanguageDe extends LODLanguage {

	protected $mNamespaces = array(
		LOD_NS_LOD       => 'LOD',
		LOD_NS_LOD_TALK  => 'LOD_Diskussion',
		LOD_NS_MAPPING       => 'Mapping',
		LOD_NS_MAPPING_TALK  => 'Mapping_Diskussion'
	);

	protected $mParserFunctions = array(
		LODLanguage::PF_RMAPPING			=> 'r2rZuordnung',
		LODLanguage::PF_LSD				=> 'quelldefinition',
		LODLanguage::PF_SMAPPING			=> 'silkZuordnung',
		
	);
	
	protected $mParserFunctionsParameters = array(
		LODLanguage::PFP_MAPPING_TARGET				=> 'ziel', 
		LODLanguage::PFP_MAPPING_SOURCE				=> 'quelle', 
		
		LODLanguage::PFP_LSD_ID						=> "id",
    	LODLanguage::PFP_LSD_CHANGEFREQ				=> "änderungsrate",
		LODLanguage::PFP_LSD_DATADUMPLOCATION		=> "dumport",
		LODLanguage::PFP_LSD_DESCRIPTION			=> "Beschreibung",
		LODLanguage::PFP_LSD_HOMEPAGE				=> "homepage",
		LODLanguage::PFP_LSD_LABEL					=> "Bezeichnung",
		LODLanguage::PFP_LSD_LASTMOD				=> "letzteänderung",
		LODLanguage::PFP_LSD_LINKEDDATAPREFIX		=> "linkeddatapräfix",
		LODLanguage::PFP_LSD_SAMPLEURI				=> "beispieluri",
		LODLanguage::PFP_LSD_SPARQLENDPOINTLOCATION	=> "sparqlendpunkt",
		LODLanguage::PFP_LSD_SPARQLGRAPHNAME		=> "sparqlgraphname",
		LODLanguage::PFP_LSD_SPARQLGRAPHPATTERN		=> "sparqlgraphpattern",
		LODLanguage::PFP_LSD_URIREGEXPATTERN		=> "uriregexmuster",
		LODLanguage::PFP_LSD_VOCABULARY				=> "vokabular",
		LODLanguage::PFP_LSD_PREDICATETOCRAWL		=> "zufolgendesprÃ¤dikat",
		
		LODLanguage::PFP_SILK_MAPPING_MINT_NAMESPACE				=> 'mintNamespace',
		LODLanguage::PFP_SILK_MAPPING_MINT_LABEL_PREDICATE				=> 'mintLabelPredicate',
		
	);
		
}


