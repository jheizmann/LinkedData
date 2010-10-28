<?php
/**
 * @file
 * @ingroup LinkedDataStorage
 */

/*  Copyright 2010, ontoprise GmbH
*   This file is part of the LinkedData-Extension.
*
*   The LinkedData-Extension is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 3 of the License, or
*   (at your option) any later version.
*
*   The LinkedData-Extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * This file initializes and deletes all MySQL database tables and
 * triple store content that is used by the Linked Data Extension. 
 *
 * @author Thomas Schweitzer
 *
 */

global $lodgIP;
require_once $lodgIP . '/storage/LOD_DBHelper.php';

/**
 * This class encapsulates methods that care about the database tables and 
 * triple store graphs of the LinkedData extension.
 * 
 * @author Thomas Schweiter 
 *
 */
class LODStorageSQL {
	
	const TRIPLE_PERSISTENCE_TABLE	= "lod_triple_persistence"; 
	const MAPPING_PERSISTENCE_TABLE	= "lod_mapping_persistence"; 
	const QUERY_TABLE				= "lod_rating_query"; 
	const QUERY_RESULT_TABLE		= "lod_rating_query_result"; 
	
	
	/**
	 * Initializes the database tables of the Linked Data extensions.
	 * These are:
	 * - lod_mapping_persistence
	 * - lod_triple_persistence
	 *
	 */
	public function initDatabaseTables() {

		print "Setting up the Linked Data Extension...\n";

		$db =& wfGetDB( DB_MASTER );

		$verbose = true;
		LODDBHelper::reportProgress("Setting up LinkedData ...\n",$verbose);

		// lod_mapping_persistence:
		//		persistence of mappings that are defined in wiki articles
		$table = $db->tableName(self::MAPPING_PERSISTENCE_TABLE);

		LODDBHelper::setupTable($table, array(
				'mapping_id'	=> 'INT(8) UNSIGNED NOT NULL AUTO_INCREMENT',
	            'source' 		=> 'Text CHARACTER SET utf8 COLLATE utf8_bin',
	            'target' 		=> 'Text CHARACTER SET utf8 COLLATE utf8_bin',
	            'mapping_text' 	=> 'Text CHARACTER SET utf8 COLLATE utf8_bin'),
				$db, $verbose, 'mapping_id, source(128), target(128)');
			
			
		// lod_triple_persistence
		//		persistence of triples in the triple store			
		$table = $db->tableName(self::TRIPLE_PERSISTENCE_TABLE);
		LODDBHelper::setupTable($table, array(
				'triple_set_id'	=> 'VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin',
	            'component'		=> 'VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin',
	            'triples' 		=> 'Text CHARACTER SET utf8 COLLATE utf8_bin'),
				$db, $verbose);
				
		// lod_rating_query:
		//		Stores queries in an article for the rating feature
		$table = $db->tableName(self::QUERY_TABLE);

		LODDBHelper::setupTable($table, array(
				'query_id'	=> 'INT(8) UNSIGNED NOT NULL AUTO_INCREMENT',
	            'query' 		=> 'Text CHARACTER SET utf8 COLLATE utf8_bin',
	            'article_name'	=> 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin'),
				$db, $verbose, 'query_id');
				
		// lod_rating_query_result:
		//		Stores the result of queries in an article for the rating feature
		$table = $db->tableName(self::QUERY_RESULT_TABLE);

		LODDBHelper::setupTable($table, array(
				'query_id'	=> 'INT(8) UNSIGNED NOT NULL',
				'row'		=> 'INT(8) UNSIGNED NOT NULL',
	            'variable'	=> 'VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin',
	            'value' 	=> 'Text CHARACTER SET utf8 COLLATE utf8_bin',
	            'datatype'	=> 'VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_bin'),
				$db, $verbose, 'query_id, row, variable');
				
				
		LODDBHelper::reportProgress("   ... done!\n",$verbose);
		
		print "   ... done!\n";
		
		return true;

	}
	
	/**
	 * Drops all database tables of the LinkedData Extension.
	 */
	public function dropDatabaseTables() {
		
		print("Deleting source definitions...");
		LODAdministrationStore::getInstance()->deleteAllSourceDefinitions();
		print("done.\n");
		
		print("Deleting all database content and tables generated by the Linked Data Extension ...\n\n");
		$db =& wfGetDB( DB_MASTER );
		$tables = array(
			self::MAPPING_PERSISTENCE_TABLE,
			self::TRIPLE_PERSISTENCE_TABLE,
			self::QUERY_TABLE.
			self::QUERY_RESULT_TABLE);
		foreach ($tables as $table) {
			$name = $db->tableName($table);
			$db->query('DROP TABLE' . ($wgDBtype=='postgres'?'':' IF EXISTS'). $name, 'LODStorageSQL::dropDatabaseTables');
			LODDBHelper::reportProgress(" ... dropped table $name.\n", $verbose);
		}		
		
		print("All data removed successfully.\n");
	}

	
	/*
	 * Functions for the persistency layer of the triple store. 
	 * 
	 */
	
	/**
	 * Persists all triples given in $trig for the component $component with the
	 * ID $tripleSetID.
	 * Several triple sets can be added using the same component and ID. 
	 * 
	 * @param string $component
	 * 		Name of a component
	 * @param string $tripleSetID
	 * 		ID of a triple set. The ID is local to the component i.e. other 
	 * 		components may use the same ID. Yet the pair <component, ID> can 
	 * 		still be distinguished.
	 * @param string $trig
	 * 		Triples in TriG format.
	 * 
	 * @return bool success
	 * 		<true>, if the operation was successful,
	 * 		<false> otherwise
	 */
	public function persistTriples($component, $tripleSetID, $trig) {
		$db = & wfGetDB( DB_MASTER );
		$t = $db->tableName(self::TRIPLE_PERSISTENCE_TABLE);

		$setValues = array(
            'component'     => $component,
            'triple_set_id' => $tripleSetID,
            'triples'	    => $trig);
		
		return $db->insert($t, $setValues);
	}
	
	/**
	 * Deletes all persistent triples of the component $component with the
	 * ID $id.
	 * @param string $component
	 * 		Component to which the triples belong
	 * @param string $id
	 * 		ID with respect to the component. If the ID is <null>, all triples
	 * 		of the component are deleted.
	 */
	public function deletePersistentTriples($component, $id = null) {
		$db = & wfGetDB( DB_MASTER );
		$t = $db->tableName(self::TRIPLE_PERSISTENCE_TABLE);

		$condition = array("component" => $component);
		if (!is_null($id)) {
			$condition["triple_set_id"] = $id;
		}
		$db->delete($t, $condition);
	}
	
	/**
	 * Reads the triples of the $component with the $tripleSetID from the database
	 * and returns them in TriG format. Several TriG serialization may be returned.
	 * 
	 * @param string $component
	 * 		Name of a component
	 * @param string $tripleSetID
	 * 		ID of a triple set. The ID is local to the component i.e. other 
	 * 		components may use the same ID. Yet the pair <component, ID> can 
	 * 		still be distinguished. If this parameter is <null>, all triples of
	 * 		the $component are read.
	 * @return array<string> $trig
	 * 		TriG serializations of the triples.
	 */
	public function readPersistentTriples($component, $tripleSetID) {
		$db = & wfGetDB( DB_SLAVE );

		$cond = array("component" => $component);
		if (!is_null($tripleSetID)) {
			$cond["triple_set_id"] = $tripleSetID;
		}
		$res = $db->select($db->tableName(self::TRIPLE_PERSISTENCE_TABLE),
										   array("triples"), $cond);
										   
		$trig = array();
		while ($row = $db->fetchObject($res)) {
			$trig[] = $row->triples;
		}
		$db->freeResult($res);
										   
		return $trig;
	}
	
	/**
	 * Deletes the complete content of table TRIPLE_PERSISTENCE_TABLE
	 */
	public function deleteAllPersistentTriples() {
		$db = & wfGetDB( DB_MASTER );
		$db->delete($db->tableName(self::TRIPLE_PERSISTENCE_TABLE), "*");
		
	}
	
	
	/*
	 * Functions for storing queries and their results for the rating feature.
	 * 
	 */
	
	/**
	 * Stores a query and the article where it is defined. Returns an ID for that
	 * query.
	 * 
	 * @param string $queryString
	 * 		The query to store.
	 * @param string $articleName
	 * 		The full name of the article (i.e. with namespace) where the query 
	 * 		is defined.
	 * @return int
	 * 		Each query that is stored with this function gets a unique ID. 
	 * @throws DBError
	 * 		... when a database error occurs
	 */
	public function addQuery($queryString, $articleName) {
		$db = & wfGetDB( DB_MASTER );
		$t = $db->tableName(self::QUERY_TABLE);

		$setValues = array(
            'query'			=> $queryString,
            'article_name'	=> $articleName);
		
		$db->insert($t, $setValues);
		$queryID = $db->insertID();
		
		return $queryID;
	}
	
	/**
	 * Retrieves the content of a query by the given $queryID.
	 * 
	 * @param int $queryID
	 * 		ID of the query to retrieve
	 * @return string / null
	 * 		The content of the query of <null> if it does not exist.
	 */
	public function getQueryByID($queryID) {
		$db = & wfGetDB( DB_SLAVE );

		$cond = array("query_id" => $queryID);
		$res = $db->select($db->tableName(self::QUERY_TABLE), array("query"), $cond);
										   
		$row = $db->fetchObject($res);
		$query = $row === false ? null : $row->query;
		$db->freeResult($res);
										   
		return $query;
		
	}
	
	/**
	 * Deletes all queries and their results that were store for the article
	 * with the given name.
	 * @param string $articleName
	 * 		Name of the article that contains the queries.
	 */
	public function deleteQueries($articleName) {
		$db = & wfGetDB( DB_MASTER );
		$t = $db->tableName(self::QUERY_TABLE);
		
		$this->deleteQueryResults($articleName);

		$condition = array("article_name" => $articleName);
		$db->delete($t, $condition);
		
	}
	
	/**
	 * Stores a result row of the query with the ID $queryID. A result row is 
	 * stored with the 0-based index of the row, the variable in the query, the 
	 * value and the data type
	 * @param int $queryID
	 * 		ID of the query to which the result belongs
	 * @param int $row
	 * 		The row where the variable binding occurs
	 * @param string $variable
	 * 		The variable that is bound to the value
	 * @param string $value
	 * 		The value of the variable
	 * @param string $datatype
	 * 		The data type of the value
	 * @throws DBError
	 * 		... when a database error occurs
	 */
	public function storeQueryResultRow($queryID, $row, $variable, $value, $datatype) {
		$db = & wfGetDB( DB_MASTER );
		$t = $db->tableName(self::QUERY_RESULT_TABLE);

		$setValues = array(
            'query_id'	=> $queryID,
            'row'		=> $row,
            'variable'	=> $variable,
            'value'		=> $value,
            'datatype'	=> $datatype
		);
		
		$db->insert($t, $setValues);
		
	}
	
	/**
	 * Reads a complete result row from the database with all variable bindings.
	 * 
	 * @param int $queryID
	 * 		ID of the query.
	 * @param int $row
	 * 		0-based index of the row that contains the variable bindings
	 * @return array / null
	 * 		<null> if the $row is invalid
	 * 		otherwise:
	 * 		array(string variable name => array(string value, string datatype)) 
	 */
	public function readQueryResultRow($queryID, $row){
		$db = & wfGetDB( DB_SLAVE );

		$cond = array("query_id"	=> $queryID,
					  "row"			=> $row);
		$res = $db->select($db->tableName(self::QUERY_RESULT_TABLE), 
											array("variable", "value", "datatype"), 
											$cond);
		$result = $res->numRows() === 0 ? null : array();					   
		while ($row = $db->fetchObject($res)) {
			$result[$row->variable] = array($row->value, $row->datatype);
		}
		$db->freeResult($res);
										   
		return $result;
		
	}
	
	/**
	 * Deletes the results of all queries that are defined in the article
	 * with the given name.
	 *  
	 * @param $articleName
	 * 		Name of the article that contains the queries whose results will
	 * 		be deleted.
	 */
	public function deleteQueryResults($articleName) {
		// Find the IDs of all queries of the article
		$db = & wfGetDB( DB_MASTER );

		$cond = array("article_name" => $articleName);
		$res = $db->select($db->tableName(self::QUERY_TABLE), 
											array("query_id"), 
											$cond);
											
		$t = $db->tableName(self::QUERY_RESULT_TABLE);
		while ($row = $db->fetchObject($res)) {
			// Delete all results with the query ID
			$queryID = $row->query_id;
			
			$cond = array("query_id" => $queryID);
			$db->delete($t, $cond);
		}
		$db->freeResult($res);
		
	}
	
}