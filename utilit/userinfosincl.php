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
include_once 'base.php';


/**
 * This file is included to query informations on a user
 * @package users
 */
class bab_userInfos {

	/**
	 * get table content for user
	 * @param	int	$id_user
	 * @return array | false
	 */
	public static function getRow($id_user) {

		global $babDB;
		$res = $babDB->db_query('
			SELECT 
				nickname,
				firstname, 
				lastname,
				email,
				disabled,
				password,
				changepwd,
				is_confirmed, 
				date 
			FROM 
				'.BAB_USERS_TBL.' 
			WHERE id='.$babDB->quote($id_user)
		);

		$infos = $babDB->db_fetch_assoc($res);

		return $infos;
	}


	/**
	 * Get informations needed to complete a directory entry
	 * @param	int		$id_user
	 * @see		bab_getUserInfos
	 * 
	 * @return 	array 	with key : disabled, password_md5, changepwd, is_confirmed
	 */
	public static function getForDirectoryEntry($id_user) {

		$row = self::getRow($id_user);

		if (false === $row) {
			return false;
		}

		return array(
			'nickname'		=> $row['nickname'],
			'disabled' 		=> $row['disabled'],
			'password_md5'	=> $row['password'],
			'changepwd'		=> $row['changepwd'],
			'is_confirmed'	=> $row['is_confirmed']
		);
	}
	
	/**
	 * Creation date of account
	 * @param	int		$id_user
	 * @return string ISO datetime
	 */
	public static function getCreationDate($id_user)
	{
		$row = self::getRow($id_user);
		
		if (false === $row) {
			return null;
		}
		
		return $row['date'];
	}


	
	/**
	 * Get array with name informations
	 * @param	int		$id_user
	 * 
	 * @return 	array | false 	with key : firstname, lastname
	 */
	public static function arrName($id_user) {
		
		$row = self::getRow($id_user);

		if (false === $row) {
			return false;
		}

		return array(
			'firstname' => $row['firstname'],
			'lastname' => $row['lastname']
		);
		
	}

	/**
	 * Get firstname and lastname sorted with ovidentia parameters
	 * @param	int		$id_user
	 * 
	 * @return 	string
	 */
	public static function composeName($id_user) {
		
		$row = self::getRow($id_user);

		if (false === $row) {
			return false;
		}

		return bab_composeUserName($row['firstname'], $row['lastname']);
	}

	/**
	 * Get firstname and lastname sorted with ovidentia parameters and add "disabled" information if necessary
	 * @param	int		$id_user
	 * 
	 * @return 	string
	 */
	public static function composeNameAndStatus($id_user) {
		$row = self::getRow($id_user);

		if (false === $row) {
			return false;
		}

		$name = bab_composeUserName($row['firstname'], $row['lastname']);

		if ($row['disabled']) {
			$name .= ' ('.bab_translate('disabled').')';
		}

		return $name;
	}




	/**
	 * Get firstname and lastname sorted with ovidentia parameters the result is HTML with a link to directory entry if available
	 * @link	http://www.gmpg.org/xfn/11
	 * @param	int		$id_user
	 * 
	 * @return 	string
	 */
	public static function composeHtml($id_user) {

		$url = bab_getUserDirEntryLink($id_user, BAB_DIR_ENTRY_ID_USER);

		if (false === $url) {
			return bab_toHtml(self::composeName($id_user));
		}

		return bab_sprintf(
			'<a rel="contact" href="%s" onclick="bab_popup(this.href);return false;">%s</a>', 
			bab_toHtml($url),
			bab_toHtml(self::composeName($id_user))
		);
	}
}





/**
 * bab_UserName object is a tool to get the username at the last time
 * the query to ovidentia user database will be done whene the object is displayed (in the __tostring() method)
 */
class bab_UserName {
	
	private $id_user = null;
	private $method = 'composeNameAndStatus';

	public function __construct($id_user) {
		$this->id_user = $id_user;
	}

	/**
	 * Set method used
	 * @see bab_userInfos
	 * @param string $method
	 *		possibles values are :
	 *		<ul>
	 *			<li>composeNameAndStatus (default)</li>
	 *			<li>composeName</li>
	 *			<li>composeHtml</li>
	 *		</ul>
	 * @return	bab_UserName
	 */
	public function setMethod($method) {
		$this->method = $method;
	}

	public function __tostring() {
		$method = $this->method;
		return (string) bab_userInfos::$method($this->id_user);
	}
}



