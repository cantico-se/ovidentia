<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/

/**
* @internal SEC1 PR 2006-12-12 FULL
*/


include_once "base.php";


/**
 * Database object
 * Use $babDB, global babDatabase instance
 */ 
class babDatabase
{
	
	private $db_die_on_fail;

	
	
	public function __construct()
		{
		$this->db_die_on_fail = true;
		}
		
		
	private static function connect($host, $login, $password, $dbname)
		{

		$dblink = mysql_connect($host, $login, $password);
		if( $dblink )
			{
			$res = mysql_select_db($dbname, $dblink);
			if( $res == false )
				{
				if (is_file('install.php')) {
				die('Welcome to Ovidentia.<br />To install this distribution, launch the <a href="install.php">install.php</a>.');
				} else {
				$this->db_print_error("Cannot select database : " . $dbname);
				}
				return $res;
				}
			}
		else
			{
			if (is_file('install.php')) {
				die('Welcome to Ovidentia.<br />To install this distribution, launch the <a href="install.php">install.php</a>.');
				} else {
				$this->db_print_error( "Cannot connect to database : " . $dbName);
				}
			}

		return $dblink;
		}
		
	public function db_print_error($text) {
		if (function_exists('bab_isUserAdministrator') && bab_isUserAdministrator()) {
			include_once $GLOBALS['babInstallPath'].'utilit/devtools.php';
			bab_debug_print_backtrace(true);
		}
		
		
		$str = "<h2>" . $text . "</h2>\n";
		$str .= "<p><b>Database Error: ";
		$str .= $this->db_error();
		$str .= "</b></p>\n";

		
		if ($this->db_die_on_fail)
			{
			$error_reporting = (int) ini_get('error_reporting');
			if (E_USER_ERROR === ($error_reporting & E_USER_ERROR)) {
				echo $str;
			}

			echo "<p>This script cannot continue, terminating.";
			die();
			}
		else
			return $str;
		}
		

	public function db_connect()
		{
		static $idlink = false;
		if( $idlink == false)
			{
			$idlink = self::connect($GLOBALS['babDBHost'], $GLOBALS['babDBLogin'], $GLOBALS['babDBPasswd'], $GLOBALS['babDBName']);
			}
		return $idlink;
		}

	public function db_close()
		{
		return mysql_close($this->db_connect());
		}

	public function db_setCharset()
		{
			require_once $GLOBALS['babInstallPath'].'utilit/addonapi.php';
			if('utf8' == bab_charset::getDatabase())
			{
				$this->db_query("SET NAMES utf8");
			}			
		}

	public function db_create_db($dbname)
		{
		return mysql_create_db($dbname, $this->db_connect());
		}

	public function db_drop_db($dbname)
		{
		return mysql_drop_db($dbname, $this->db_connect());
		}

	/**
	 * sends an unique query (multiple queries are not supported)
	 * @param	string	$query
	 * @return	resource|false
	 */
	public function db_query($query)
		{
			
		$res = false;
		$res = mysql_query($query, $this->db_connect());
		if (!$res)
			{
			$this->db_print_error("Can't execute query : <br><pre>" . htmlspecialchars($query) . "</pre>");
			}

		return $res;
		
		}

	public function db_num_rows($result)
		{
		if (!$result) {
			return 0;
			}
		return mysql_num_rows($result);
		}

	public function db_fetch_array($result)
		{
		return mysql_fetch_array($result);
		}

	public function db_fetch_assoc($result)
		{
		return mysql_fetch_assoc($result);
		}

	function db_fetch_row($result)
		{
		return mysql_fetch_row($result);
		}

	public function db_result($result, $row, $field)
		{
		return mysql_result($result, $row, $field);
		}

	public function db_affected_rows()
		{
		return mysql_affected_rows($this->db_connect());
		}

	public function db_insert_id()
		{
		return mysql_insert_id($this->db_connect());
		}

	public function db_data_seek($res, $row)
		{
		return mysql_data_seek($res, $row);
		}

	public function db_escape_string($str)
		{
		return mysql_escape_string($str);
		//return mysql_real_escape_string($str, $this->db_connect());
		}

	/**
	 * Special chars for LIKE query
	 * @param	string $str
	 * @return	string
	 */
	public function db_escape_like($str)
		{
		$str = str_replace('\\','\\\\',$str);
		$str = str_replace('%','\%',$str);
		$str = str_replace('?','\?',$str);
		return $this->db_escape_string($str);
		}

	/**
	 * Encode array or string for query and add quotes
	 * @param	array|string	$param
	 * @return	string
	 */
	public function quote($param) 
		{
			if (is_array($param)) {

				foreach($param as &$value) {
					$value = $this->db_escape_string($value);
				}
				unset($value);

				return "'".implode("','",$param)."'";
			} else {
				return "'".$this->db_escape_string($param)."'";
			}
		}
		
	/**
	 * Encode array or string for query and add quotes or if the value is NULL, return the NULL string
	 * 
	 * @since 7.1.94
	 * 
	 * @param	array|string|null	$param
	 * @return	string
	 */ 
	public function quoteOrNull($param)
		{
			if (null === $param) {
				return 'NULL';
			}
			
			return $this->quote($param);
		}
		

	/**
	 * Adds backticks (`) to an SQL identifier (database, table or column name). 
	 * @see http://dev.mysql.com/doc/refman/4.1/en/identifiers.html
	 * @since	6.4.95
	 * @param	string	$identifier
	 * @return	string	The backticked identifier.
	 */
	public function backTick($identifier) 
		{
			// Backticks are allowed in an identifier but should be backticked.
			$identifier = '`' . str_replace('`', '``', $identifier) . '`';

			return $identifier;
		}

	public function db_free_result($result)
		{
		return mysql_free_result($result);
		}
		
		
	/**
	 * Get error info
	 * return false if no error on the last query
	 * return the error string if error on the last query
	 * @since	6.4.95
	 * @return 	false|string
	 */
	public function db_error()
		{
		$error = mysql_error();
		return empty($error) ? false : $error;
		}
		
		
	/**
	 * Get error manager status and enable or disable error manager
	 * @since	6.4.95
	 * @param	boolean	[$status]
	 * @return 	boolean
	 */
	public function errorManager($status = NULL) {
		if (NULL === $status) {
			return $this->db_die_on_fail;
		}
		$this->db_die_on_fail = $status;
		return $status;
	}
	
	/**
	 * Query without error manager
	 * @since	6.4.95
	 * @param	string	$query
	 * @return	resource|false
	 */
	public function db_queryWem($query) {
		return mysql_query($query, $this->db_connect());
	}
}


