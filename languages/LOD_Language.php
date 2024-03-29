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
/**
 * Base class for all LinkedData language classes.
 * @author Thomas Schweitzer
 */
abstract class LODLanguage {

	//-- Constants --
	
	//---IDs of parser functions ---
	const PF_RMAPPING = 1;
	const PF_LSD	 = 2; // LOD source definition
	const PF_SMAPPING = 3;

	//---IDs of parser function parameters ---
	const PFP_MAPPING_TARGET	= 100;
	const PFP_MAPPING_SOURCE	= 101;
	
	const PFP_LSD_ID						= 200;
	const PFP_LSD_CHANGEFREQ				= 201;
	const PFP_LSD_DATADUMPLOCATION			= 202;
	const PFP_LSD_DESCRIPTION				= 203;
	const PFP_LSD_HOMEPAGE					= 204;
	const PFP_LSD_LABEL						= 205;
	const PFP_LSD_LASTMOD					= 206;
	const PFP_LSD_LINKEDDATAPREFIX			= 207;
	const PFP_LSD_SAMPLEURI					= 208;
	const PFP_LSD_SPARQLENDPOINTLOCATION	= 209;
	const PFP_LSD_SPARQLGRAPHNAME			= 210;
	const PFP_LSD_URIREGEXPATTERN			= 211;
	const PFP_LSD_VOCABULARY				= 212;
	const PFP_LSD_SPARQLGRAPHPATTERN		= 213;
	const PFP_LSD_PREDICATETOCRAWL			= 214;
        const PFP_LSD_LEVELSTOCRAWL			= 215;
	
	const PFP_SILK_MAPPING_MINT_NAMESPACE	= 300;
	const PFP_SILK_MAPPING_MINT_LABEL_PREDICATE	= 301;
	
	
	// the special message arrays ...
	protected $mNamespaces;
	protected $mNamespaceAliases = array();
	protected $mParserFunctions = array();
	protected $mParserFunctionsParameters = array();

	/**
	 * Function that returns an array of namespace identifiers.
	 */

	public function getNamespaces() {
		return $this->mNamespaces;
	}

	/**
	 * Function that returns an array of namespace aliases, if any.
	 */
	public function getNamespaceAliases() {
		return $this->mNamespaceAliases;
	}
	
	/**
	 * This method returns the language dependent name of a parser function.
	 * 
	 * @param int $parserFunctionID
	 * 		ID of the parser function i.e. one of PF_MAPPING...
	 * 
	 * @return string 
	 * 		The language dependent name of the parser function.
	 */
	public function getParserFunction($parserFunctionID) {
		return $this->mParserFunctions[$parserFunctionID];
	}
	
	/**
	 * This method returns the language dependent name of a parser function 
	 * parameter.
	 * 
	 * @param int $parserFunctionParameterID
	 * 		ID of the parser function parameter i.e. one of ...
	 * 
	 * @return string 
	 * 		The language dependent name of the parser function.
	 */
	public function getParserFunctionParameter($parserFunctionParameterID) {
		return $this->mParserFunctionsParameters[$parserFunctionParameterID];
	}
	

}


