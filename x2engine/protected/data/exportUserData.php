<?php
/*********************************************************************************
 * The X2CRM by X2Engine Inc. is free software. It is released under the terms of 
 * the following BSD License.
 * http://www.opensource.org/licenses/BSD-3-Clause
 * 
 * X2Engine Inc.
 * P.O. Box 66752
 * Scotts Valley, California 95067 USA
 * 
 * Company website: http://www.x2engine.com 
 * Community and support website: http://www.x2community.com 
 * 
 * Copyright (C) 2011-2012 by X2Engine Inc. www.X2Engine.com
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 * - Redistributions of source code must retain the above copyright notice, this 
 *   list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this 
 *   list of conditions and the following disclaimer in the documentation and/or 
 *   other materials provided with the distribution.
 * - Neither the name of X2Engine or X2CRM nor the names of its contributors may be 
 *   used to endorse or promote products derived from this software without 
 *   specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
 * IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, 
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 ********************************************************************************/

/**
 * @file exportUserData.php
 * 
 * A command-line script for exporting non-application (human-entered) data into
 * an SQL script. Requires the "mysqldump" utility to be installed on the system.
 * 
 * The SQL generated by this script can be used as an alternate method for 
 * exporting data, reinstalling and importing data into the fresh installation.
 * Note, however that it does not save custom modules or any of the tables 
 * listed in $tblsExclude for these reasons:
 * 
 * - x2_auth tables: there is no easy, reliable way of distinguishing 
 *		user-entered data in this table from default application data.
 * - x2_sessions/x2_temp_files: This data is entirely ephemeral
 * - x2_timezones/x2_timezone_points: This is static data inserted during 
 *		installation and doesn't need to be exported.
 * 
 * Note also that any files in the uploads folder will also need to be backed up,
 * if the data is to be re-used elsewhere; references to files on the server 
 * will otherwise point to nonexistent files.
 */

$conf = realpath(dirname(__FILE__).'/../config/X2Config.php');
// [edition] => [array of table names]
$tblEditions = require(dirname(__FILE__).DIRECTORY_SEPARATOR.'nonFreeTables.php');
$nonFreeEditions = require(dirname(__FILE__).DIRECTORY_SEPARATOR.'editions.php');
$allEditions = array_keys($tblEditions);
$specTemplate = array_fill_keys($allEditions,array());

if ($conf) {
	if ((include $conf) !== 1) {
		die('Configuration import failed.');
	}
} else {
	die("Configuration file not found. This script must be run in protected/data.\n");
}


/***********************************/
/* Prepare for database operations */
/***********************************/
$pdo = new PDO("mysql:host=localhost;dbname=$dbname",$user,$pass);
$getTbls = $pdo->prepare("SHOW TABLES IN `$dbname`");
$getTbls->execute();
try {
	$allTbls = array_map(function($tr)use($dbname){return $tr["Tables_in_$dbname"];},$getTbls->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
	die("Database error: ".$e->getMessage()."\n");
}

/**
 * Format a string with a value such that it can be used in an SQL statement
 * 
 * @param type $x
 * @return str
 */
function sqlValue($val) {
	global $pdo;
	if ($val === null)
		return "NULL";
	else if (is_int($val))
		return "$val";
	else if (is_bool($val))
		return (string) ((int) $val);
	else // string
		return $pdo->quote($val);
}

/**
 * The command for exporting data:
 */
$command = "mysqldump -tc -u $user -p$pass $dbname ";

$dummy_data = ! isset($argv[1]);
// Ignore pattern for lines in output of mysqldump:
$lPat = '/^(\/\*|\-\-|\s*$';
if ($dummy_data) {
	// Export current app's data as "dummy" (usage example) data
	$lPat.='|(?:UN)?LOCK TABLES)/';
	$out = dirname(__FILE__).DIRECTORY_SEPARATOR.'dummy_data%s.sql';
} else {
	$lPat .= ')/';
	$out = $argv[1];
	if(!realpath($outDir = dirname($out)))
		die("Error: path ".$outDir." does not exist.\n");
}

/**
 * Update the list of tables for each edition with the default tables:
 */
$nonFreeTbls = array_reduce($allEditions,function($a,$e)use($tblEditions){return array_merge($tblEditions[$e],$a);},array());
$tblEditions['opensource'] = array_diff($allTbls,$nonFreeTbls);

/*******************************************/
/* Declare the export specification arrays */
/*******************************************/
/* Here it's specified what data will be exported and how.
 * Each of these arrays follows the basic pattern of $specTemplate:
 * [edition] => [array of table names or ([table name] =>[spec])]
 */

/** 
 * These will be excluded from data export altogether
 */
$tblsExclude = $specTemplate;
$tblsExclude['opensource'] = array(
	'x2_admin',
	'x2_auth_assignment',
	'x2_auth_item',
	'x2_auth_item_child',
	'x2_modules',
	'x2_sessions',
	'x2_temp_files',
	'x2_timezones',
	'x2_timezone_points'
);

/**
 * These will be included, but with specific criteria
 */
$tblsWhere = $specTemplate;
$tblsWhere['opensource'] = array(
	'x2_dropdowns' => 'id>=1000',
	'x2_fields' => 'custom=1',
	'x2_form_layouts' => 'id>=1000',
	'x2_media' => 'id>11',
	'x2_profile' => 'id>2',
	'x2_users' => 'id>2',
	'x2_social' => 'id>1',
	'x2_forwarded_email_patterns' => 'groupName NOT IN ("AppleMail1","GMail1","Outlook1","Unknown1")'
);

/**
 * Update statements will be generated for these tables on which there's no way
 * of inserting it at install time without running into duplicate primary key
 * errors (because it's a record inserted by the installer itself). In each table:
 * 'pk' =>  primary key (string for single-column or array for multi-column)
 * 'fields' => array of fields to update or "*" to update all fields. Must include primary key.
 * 'where' => records for which to generate update statements
 */
$tblsChangeDefault = $specTemplate;
$tblsChangeDefault['opensource'] = array(
	'x2_profile' => array(
		'pk' => 'id',
		'fields' => '*',
		'where' => '`id`=1'
	),
	'x2_users' => array(
		'pk' => 'id',
		'fields' => array('id','firstName', 'lastName', 'officePhone', 'cellPhone', 'showCalendars', 'calendarViewPermission', 'calendarEditPermission', 'calendarFilter', 'setCalendarPermissions'),
		'where' => '`id`=1'
	)
);

/**
 * Switch the order of output generation so that foreign key constraints don't 
 * fail during insertion. List dependencies here.
 */
$insertFirst = $specTemplate;
$insertFirst['opensource'] = array(
	'x2_list_criteria' => array('x2_lists'),
	'x2_list_items' => array('x2_lists'),
	'x2_role_to_workflow' => array('x2_workflow_stages', 'x2_roles', 'x2_workflows'),
	'x2_workflow_stages' => array('x2_workflows')
);
/**
 * This array stores tables to be executed "next"
 */
$insertNext = $specTemplate;

/**
 * The resulting SQL to be written to files 
 */
$allSql = $specTemplate;

/**
 * Assemble the array of combined export specs.
 * 
 * Note that since the "where" conditions are put in the array last, they'll
 * take precedence (so if it's listed in both $tblsExclude and $tblsWhere, 
 * only $tblsWhere will apply).
 */
$allTbls = array();
foreach($allEditions as $edition) {
	$allTbls[$edition] = array_fill_keys($tblEditions[$edition],true);
	foreach($tblsExclude[$edition] as $tbl)
		$allTbls[$edition][$tbl] = false;
	foreach($tblsWhere[$edition] as $tbl=>$where)
		$allTbls[$edition][$tbl] = $where;
}

// The update statement that will be used for updating records post-insertion:
$updateStatement = "UPDATE `%s` SET %s WHERE %s;";

foreach($nonFreeEditions as $edition)
	$allSql[$edition][] = "/* @edition:$edition */";

/*****************************/
/* Generate SQL for the data */
/*****************************/
foreach ($allTbls as $edition => $tbls) {
	
	/**
	 * Generate insertion statements 
	 */
	$eTbls = $tbls;
	while (count($eTbls) > 0) {
		$tblsTmp = $eTbls;
		foreach ($tblsTmp as $tbl => $where) {
			if ($where != false) {
				// This table is to be included in the data export
				if (array_key_exists($tbl,$insertFirst[$edition])) {
					// This table depends on other tables being ready with data
					$skip = False;
					foreach ($insertFirst[$edition][$tbl] as $tblFirst)
						// Check to see if the table has been accounted for already
						if (array_key_exists($tblFirst, $eTbls)) {
							$skip = True;
							break;
						}
					if ($skip) 
						// Not all dependencies of this table have been resolved yet.
						continue;
				}
				$output = array();
				$tblCommand = "$command $tbl" . ($where !== true ? " --where='" . $where . "' " : ' ');
				exec($tblCommand, $output);
				foreach ($output as $line) {
					if (!preg_match($lPat, $line)) {
						$allSql[$edition][] = $line;
					}
				}
			}
			unset($eTbls[$tbl]);
		}
	}

	/**
	 * Generate update statements 
	 */
	foreach ($tblsChangeDefault[$edition] as $tbl => $how) {
		$colSel = $how['fields'];
		if (is_array($how['fields']))
			$colSel = '`' . implode('`,`', $how['fields']) . '`';
		$query = $pdo->prepare("SELECT $colSel FROM `$tbl` WHERE {$how['where']}");
		$query->execute();
		$recs = $query->fetchAll(PDO::FETCH_ASSOC);
		$pk = $how['pk'];
		if(!is_array($pk))
			$pk = array($pk);
		foreach ($recs as $rec) {
			// Generate a "where" clause criterion to refer to this record by its primary key
			$whereSelector = array_map(function($c)use($rec){return "`$c`=".sqlValue($rec[$c]);},$pk);
			// Exclude the primary key from the columns to be updated:
			foreach ($pk as $col)
				unset($rec[$col]);
			$fieldsSet = array();

			foreach ($rec as $col => $val)
				$fieldsSet[] = "`$col`=".sqlValue($val);

			$allSql[$edition][] = sprintf($updateStatement,$tbl, implode(',', $fieldsSet),implode(' AND ',$whereSelector));
		}
	}	
}

if($dummy_data) {
	// Create dummy data files
	foreach($allSql as $edition=>$sqls)
		file_put_contents(sprintf($out,$edition=='opensource'?'':"-$edition"), implode("\n/*&*/\n", $sqls));
} else {
	// Put it all in the same file
	$allOut = array();
	foreach($allSql as $edition=>$sqls)
		foreach($sqls as $sql)
			$allOut[] = $sql;
	file_put_contents($out,implode("\n",$allOut));
	
}

?>
