<?php
/**
 * @file
 * @ingroup LinkedData
 */

/**
 * Mapping Language API: LODMLR2RMapping
 * Based on http://www4.wiwiss.fu-berlin.de/bizer/r2r/spec/
 * Author: Christian Becker
 */
 
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the LinkedData extension. It is not a valid entry point.\n" );
}

/**
 * Parent class for native R2R mappings
 */
abstract class LODMLR2RMapping extends LODMLMapping {
	
	protected $prefixes;
	
	protected static function property() {
		return null;
	}
	
	/** 
	 * @param	string	$uri
	 * @param	array<property> => array<string>	$properties	
	 * @param	array<LODMLMapping>	$otherMappings
	 */
	public function __construct($uri, $properties, $otherMappings) {
		parent::__construct($uri, $properties, $otherMappings);
		$this->setPrefixes();
	}
	
	/**
	 * Parses the first triple of a target pattern and shifts the target pattern
	 * @param	string	$pattern	Input pattern, e.g. "?SUBJ rdfs:label 'String constant'"
	 * @return	array
	 *				'subj', 'pred', 'obj': Target subject, predicate and object
	 *				'tail': Remaining part, if any, or an empty string.
	 */
	private static function parseTargetPattern($targetPattern) {
		/*
		 * A target pattern is either a triple pattern or a path.
		 * A path has the form: Resource Property, followed by one or more Resource Property parts, and ends with an Object.
		 * All tokens are separated by whitespace; however, literals may be supplied using single quotes.
		 * A special case are object literals indicated by three single quotes ('''), which permit enclosing
		 * @see http://www4.wiwiss.fu-berlin.de/bizer/r2r/spec/#modifiers
		 */
		 if (!preg_match("/^(.+?)\s+(.+?)\s+((.+?)(\s+.+)?)$/m", $targetPattern, $matches)) {
		 	throw new Exception("Target pattern '$targetPattern' could not be parsed");
		 }
		 
		 return array(	'subj'	=> $matches[1],
		 				'pred'	=> ($matches[2] == "a" ? "<" . LOD_ML_RDF_TYPE . ">" : $matches[2]),
		 				'obj'	=> $matches[4],
		 				'tail'	=> ($matches[3] != $matches[4] ? $matches[3] : "")
		 			);
	}
	
	/**
	 * Populates $prefixes from the supplied r2r:prefixDefinitions literal, e.g. "foaf: <http://xmlns.com/foaf/0.1/> . dbpedia: <http://dbpedia.org/ontology/>"
	 */
	private function setPrefixes() {
		$this->prefixes = array();
		preg_match_all("/(\S+?):\s*<(.+?)>/m", $this->properties[LOD_ML_R2R_PREFIXDEFINITIONS][0], $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$this->prefixes[$match[1]] = $match[2];
		}
	}
	
	/**
	 * @return	array<string> -> string	The internal prefix lookup map
	 */
	public function getPrefixes() {
		return $this->prefixes;
	}
	
	/**
	 * Converts URIs from N3 syntax
	 * Prefixed identifiers are converted using $this->prefixes; sharp brackets are removed from fully qualified URIs
	 * @param	string	$uri	Prefixed identifier or absolute URI
	 * @return	string	Absolute URI
	 */
	private function getAbsoluteURI($uri) {
		 if (preg_match("/^<(.+)>$/m", $uri, $matches)) {
			/* it's already absolute */		 	
		 	return $matches[1];
		 } else {
		 	/* construct full uri based on prefix lookup */
			list($prefix, $localName) = explode(":", $uri, 2);
			if (!array_key_exists($prefix, $this->prefixes)) {
				throw new Exception("Prefix '$prefix' is not defined");
			}
			return $this->prefixes[$prefix] . $localName;
		 }
	}
	
	/**
	 * Returns the source pattern in its string representation
	 * @return	string
	 */
	public function getSourcePattern() {
		return $this->properties[LOD_ML_R2R_SOURCEPATTERN][0];
	}
	
	/**
	 * Returns the target patterns in their string representation
	 * @return	array<string>
	 */
	public function getTargetPatterns() {
		return $this->properties[LOD_ML_R2R_TARGETPATTERN];
	}
	
	/**
	 * Returns an array of all properties generated by the mapping
	 * @return	array<uri>
	 */
	public function getTargetProperties() {
		$targetProperties = array();
		
		foreach ($this->getTargetPatterns() as $pattern) {
			do {
				$parsed = self::parseTargetPattern($pattern);
				$targetProperties[] = $this->getAbsoluteURI($parsed['pred']);	
				$pattern = $parsed['tail'];
			} while ($pattern);
		}
		
		return array_unique($targetProperties);
	}
	
	/**
	 * Returns the transformations in their string representation
	 * @return	array<string>
	 */
	public function getTransformations() {
		return ($this->properties[LOD_ML_R2R_TRANSFORMATION] ? $this->properties[LOD_ML_R2R_TRANSFORMATION] : array());
	}
		
}

?>