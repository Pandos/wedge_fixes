<?php
/**
 * Wedge
 *
 * Contains several database operations that are not common but useful, such as listing available tables.
 *
 * @package wedge
 * @copyright 2010-2012 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

class wedbExtra
{
	protected static $instance; // container for self

	public static function getInstance()
	{
		// Quero ergo sum
		if (self::$instance == null)
			self::$instance = new self();

		return self::$instance;
	}

	// Backup $table to $backup_table.
	public static function backup_table($table, $backup_table)
	{
		global $db_prefix;

		$table = str_replace('{db_prefix}', $db_prefix, $table);

		// First, get rid of the old table.
		wesql::query('
			DROP TABLE IF EXISTS {raw:backup_table}',
			array(
				'backup_table' => $backup_table,
			)
		);

		// Can we do this the quick way?
		$result = wesql::query('
			CREATE TABLE {raw:backup_table} LIKE {raw:table}',
			array(
				'backup_table' => $backup_table,
				'table' => $table
		));
		// If this failed, we go old school.
		if ($result)
		{
			$request = wesql::query('
				INSERT INTO {raw:backup_table}
				SELECT *
				FROM {raw:table}',
				array(
					'backup_table' => $backup_table,
					'table' => $table
				));

			// Old school or no school?
			if ($request)
				return $request;
		}

		// At this point, the quick method failed.
		$result = wesql::query('
			SHOW CREATE TABLE {raw:table}',
			array(
				'table' => $table,
			)
		);
		list (, $create) = wesql::fetch_row($result);
		wesql::free_result($result);

		$create = preg_split('/[\n\r]/', $create);

		$auto_inc = '';
		// Default engine type.
		$engine = 'MyISAM';
		$charset = '';
		$collate = '';

		foreach ($create as $k => $l)
		{
			// Get the name of the auto_increment column.
			if (strpos($l, 'auto_increment'))
				$auto_inc = trim($l);

			// For the engine type, see if we can work out what it is.
			if (strpos($l, 'ENGINE') !== false || strpos($l, 'TYPE') !== false)
			{
				// Extract the engine type.
				preg_match('~(ENGINE|TYPE)=(\w+)(\sDEFAULT)?(\sCHARSET=(\w+))?(\sCOLLATE=(\w+))?~', $l, $match);

				if (!empty($match[1]))
					$engine = $match[1];

				if (!empty($match[2]))
					$engine = $match[2];

				if (!empty($match[5]))
					$charset = $match[5];

				if (!empty($match[7]))
					$collate = $match[7];
			}

			// Skip everything but keys...
			if (strpos($l, 'KEY') === false)
				unset($create[$k]);
		}

		if (!empty($create))
			$create = '(
				' . implode('
				', $create) . ')';
		else
			$create = '';

		$request = wesql::query('
			CREATE TABLE {raw:backup_table} {raw:create}
			ENGINE={raw:engine}' . (empty($charset) ? '' : ' CHARACTER SET {raw:charset}' . (empty($collate) ? '' : ' COLLATE {raw:collate}')) . '
			SELECT *
			FROM {raw:table}',
			array(
				'backup_table' => $backup_table,
				'table' => $table,
				'create' => $create,
				'engine' => $engine,
				'charset' => empty($charset) ? '' : $charset,
				'collate' => empty($collate) ? '' : $collate,
			)
		);

		if ($auto_inc != '')
		{
			if (preg_match('~\`(.+?)\`\s~', $auto_inc, $match) != 0 && substr($auto_inc, -1, 1) == ',')
				$auto_inc = substr($auto_inc, 0, -1);

			wesql::query('
				ALTER TABLE {raw:backup_table}
				CHANGE COLUMN {raw:column_detail} {raw:auto_inc}',
				array(
					'backup_table' => $backup_table,
					'column_detail' => $match[1],
					'auto_inc' => $auto_inc,
				)
			);
		}

		return $request;
	}

	// Optimize a table - return data freed!
	public static function optimize_table($table)
	{
		global $db_name, $db_prefix;

		$table = str_replace('{db_prefix}', $db_prefix, $table);

		// Get how much overhead there is.
		$request = wesql::query('
				SHOW TABLE STATUS LIKE {string:table_name}',
				array(
					'table_name' => str_replace('_', '\_', $table),
				)
			);
		$row = wesql::fetch_assoc($request);
		wesql::free_result($request);

		$data_before = isset($row['Data_free']) ? $row['Data_free'] : 0;
		$request = wesql::query('
				OPTIMIZE TABLE `{raw:table}`',
				array(
					'table' => $table,
				)
			);
		if (!$request)
			return -1;

		// How much left?
		$request = wesql::query('
				SHOW TABLE STATUS LIKE {string:table}',
				array(
					'table' => str_replace('_', '\_', $table),
				)
			);
		$row = wesql::fetch_assoc($request);
		wesql::free_result($request);

		$total_change = isset($row['Data_free']) && $data_before > $row['Data_free'] ? $data_before / 1024 : 0;

		return $total_change;
	}

	// List all the tables in the database.
	public static function list_tables($db = false, $filter = false)
	{
		global $db_name;

		$db = $db == false ? $db_name : $db;
		$db = trim($db);
		$filter = $filter == false ? '' : ' LIKE \'' . $filter . '\'';

		$request = wesql::query('
			SHOW TABLES
			FROM `{raw:db}`
			{raw:filter}',
			array(
				'db' => $db[0] == '`' ? strtr($db, array('`' => '')) : $db,
				'filter' => $filter,
			)
		);
		$tables = array();
		while ($row = wesql::fetch_row($request))
			$tables[] = $row[0];
		wesql::free_result($request);

		return $tables;
	}

	// Get the version number.
	public static function get_version()
	{
		$request = wesql::query('
			SELECT VERSION()',
			array(
			)
		);
		list ($ver) = wesql::fetch_row($request);
		wesql::free_result($request);

		return $ver;
	}

}

?>