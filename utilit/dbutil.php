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
		
		
	protected function connect($host, $login, $password, $dbname)
		{

		$dblink = mysqli_connect($host, $login, $password);
		if( $dblink )
			{
			$res = mysqli_select_db($dblink, $dbname );
			if( $res == false )
				{
				if (is_file('install.php')) {
				die('Welcome to Ovidentia.<br />To install this distribution, launch the <a href="install.php">install.php</a>.');
				} else {
				 die("Cannot select database : " . $dbname);
				}
				return $res;
				}
			}
		else
			{
			if (is_file('install.php')) {
				die('Welcome to Ovidentia.<br />To install this distribution, launch the <a href="install.php">install.php</a>.');
				} else {
					die( "Cannot connect to database : " . $dbname);
				}
			}

		return $dblink;
		}
		
	public function db_print_error($text) {
		if (function_exists('bab_isUserAdministrator') && bab_isUserAdministrator()) {
			include_once dirname(__FILE__).'/devtools.php';
			bab_debug_print_backtrace(true);
		}
		
		
		$str = "<h2>" . $text . "</h2>\n";
		$str .= "<p><b>Database Error: ";
		$str .= $this->db_error();
		$str .= "</b></p>\n";

		$display_errors = (int) ini_get('display_errors');
		$error_reporting = (int) ini_get('error_reporting');
		if (E_USER_ERROR === ($error_reporting & E_USER_ERROR) && $display_errors) {
			echo $str;
		}
		
		if ($this->db_die_on_fail)
			{
			echo "<p>This script cannot continue, terminating.";
			die();
		}
		
		return $str;
	}
		

	public function db_connect()
		{
		static $idlink = false;
		if( $idlink == false)
			{
			$idlink = $this->connect($GLOBALS['babDBHost'], $GLOBALS['babDBLogin'], $GLOBALS['babDBPasswd'], $GLOBALS['babDBName']);
			}
		return $idlink;
		}

	public function db_close()
		{
		return mysqli_close($this->db_connect());
		}

	/**
	 * Set mysql connexion charset according to the charset of the database
	 */
	public function db_setCharset()
		{
			$oResult = $this->db_query("SHOW VARIABLES LIKE 'character_set_database'");
			if(false !== $oResult)
			{
				$aDbCharset = $this->db_fetch_assoc($oResult);
				if(false !== $aDbCharset && 'utf8' == $aDbCharset['Value'])
				{
					$this->db_query("SET NAMES utf8");
					return;
				}
			}
			
			$this->db_query("SET NAMES latin1");			
		}

	public function db_create_db($dbname)
		{
		return $this->db_query('CREATE DATABASE '.$this->backTick($dbname));
		}

	public function db_drop_db($dbname)
		{
		return $this->db_query('DROP DATABASE '.$this->backTick($dbname));
		}

	/**
	 * sends an unique query (multiple queries are not supported)
	 * @param	string	$query
	 * @return	resource|false
	 */
	public function db_query($query)
		{
			
		$res = false;
		$res = mysqli_query($this->db_connect(), $query );
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
		return mysqli_num_rows($result);
		}

	public function db_fetch_array($result)
		{
		$arr = mysqli_fetch_array($result);
		if (null === $arr)
		{
			return false;
		}
		return $arr;
		}

	public function db_fetch_assoc($result)
		{
		$arr = mysqli_fetch_assoc($result);
		if (null === $arr)
		{
			return false;
		}
		return $arr;
		}

	function db_fetch_row($result)
		{
		return mysqli_fetch_row($result);
		}

	public function db_result($result, $row, $field)
		{
		trigger_error('Deprecated '.__FUNCTION__);
		}

	public function db_affected_rows()
		{
		return mysqli_affected_rows($this->db_connect());
		}

	public function db_insert_id()
		{
		return mysqli_insert_id($this->db_connect());
		}

	public function db_data_seek($res, $row)
		{
		return mysqli_data_seek($res, $row);
		}

	public function db_escape_string($str)
		{
		return mysqli_real_escape_string($this->db_connect(), $str);
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
		return mysqli_free_result($result);
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
		$error = mysqli_error($this->db_connect());
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
		return mysqli_query($this->db_connect(), $query);
	}
}


