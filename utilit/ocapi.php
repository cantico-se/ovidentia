<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
// 
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
include_once $GLOBALS['babInstallPath'].'utilit/treeincl.php';

function bab_OCGetRootEntity($idoc='')
{
	static $ocrootentities = array();
	global $babBody, $babDB;

	if( empty($idoc))
	{
		if( !empty($babBody->idprimaryoc))
		{
			$idoc = $babBody->idprimaryoc;
		}
		else
		{
			$res = $babDB->db_query("select oct.id from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where ddt.id_group='1' and oct.isprimary='Y'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$ocinfo = $babDB->db_fetch_array($res);
				$idoc = $ocinfo['id'];
				$babBody->idprimaryoc = $idoc;
			}
			else
			{
				return array();
			}
		}
	}

	if( isset($ocentities[$idoc]))
	{
		return $ocentities[$idoc];
	}

	$ocrootentities[$idoc] = array();

	$res = $babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." oet left join ".BAB_OC_TREES_TBL."  ott on oet.id_node=ott.id where oet.id_oc='".$idoc."' and ott.id_parent=0");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$arr = $babDB->db_fetch_array($res);
		$ocrootentities[$idoc] = array('id' => $arr['id'], 'name' => $arr['name'], 'description' => $arr['description']);
	}

	return $ocrootentities[$idoc];
}

function bab_OCGetEntities($idoc='')
{
	static $ocentities = array();
	global $babBody, $babDB;

	if( empty($idoc))
	{
		if( !empty($babBody->idprimaryoc))
		{
			$idoc = $babBody->idprimaryoc;
		}
		else
		{
			$res = $babDB->db_query("select oct.id from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where ddt.id_group='1' and oct.isprimary='Y'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$ocinfo = $babDB->db_fetch_array($res);
				$idoc = $ocinfo['id'];
				$babBody->idprimaryoc = $idoc;
			}
			else
			{
				return array();
			}
		}
	}

	if( isset($ocentities[$idoc]))
	{
		return $ocentities[$idoc];
	}
	$ocentities[$idoc] = array();

	$res = $babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id_oc='".$idoc."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		while ($arr = $babDB->db_fetch_array($res))
		{
			$ocentities[$idoc][] = array('id' => $arr['id'], 'name' => $arr['name'], 'description' => $arr['description']);
		}
	}

	return $ocentities[$idoc];
}


function bab_OCGetEntity($ide)
{
	global $babDB;
	
	$res = $babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id='".$ide."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$arr = $babDB->db_fetch_array($res);
		return array( 'name' => $arr['name'], 'description' => $arr['description']);

	}

	return false;
}


function bab_OCGetChildsEntities($idroot='', $idoc='')
{
	global $babBody, $babDB;

	if( empty($idoc))
	{
		if( !empty($babBody->idprimaryoc))
		{
			$idoc = $babBody->idprimaryoc;
		}
		else
		{
			$res = $babDB->db_query("select oct.id from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where ddt.id_group='1' and oct.isprimary='Y'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$ocinfo = $babDB->db_fetch_array($res);
				$idoc = $ocinfo['id'];
				$babBody->idprimaryoc = $idoc;
			}
			else
			{
				return array();
			}
		}
	}

	if( !empty($idroot))
	{
		$res = $babDB->db_query("select id_node from ".BAB_OC_ENTITIES_TBL." where id_oc='".$idoc."' and id='".$idroot."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			$arr = $babDB->db_fetch_array($res);
			$rootnode = $arr['id_node'];
		}
		else
		{
			$rootnode = 0;
		}

	}
	else
	{
		$rootnode = 0;
	}

	$babTree  = new bab_arraytree(BAB_OC_TREES_TBL, $idoc, "", $rootnode);
	$arr = $babTree->getChilds($babTree->rootid);
	$ret = array();
	if( count($arr) > 0 )
	{
		$res = $babDB->db_query("select * from ".BAB_OC_ENTITIES_TBL." where id_oc='".$idoc."' and id_node in (".implode(',', $arr).")  ORDER BY name");
		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			while ($arr = $babDB->db_fetch_array($res))
			{
				$ret[] = array('id' => $arr['id'], 'name' => $arr['name'], 'description' => $arr['description']);
			}
		}
	}
	return $ret;
}


function bab_OCGetSuperior($identity)
{
	global $babDB;
	
	$res = $babDB->db_query("SELECT det.id_user, det.sn lastname, det.givenname firstname, det.mn middlename FROM ".BAB_OC_ROLES_USERS_TBL." ocrut LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role left join ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ocrut.id_user WHERE ocrt.id_entity='".$identity."'  AND ocrt.type = '1'");
	if( $res && $babDB->db_num_rows($res) == 1 )
	{
		$arr = $babDB->db_fetch_array($res);
		return $arr;
	}

	return 0;
}

function bab_OCGetSuperiors($idoc='')
{
	global $babBody, $babDB;
	
	if( empty($idoc))
	{
		if( !empty($babBody->idprimaryoc))
		{
			$idoc = $babBody->idprimaryoc;
		}
		else
		{
			$res = $babDB->db_query("select oct.id from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where ddt.id_group='1' and oct.isprimary='Y'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$ocinfo = $babDB->db_fetch_array($res);
				$idoc = $ocinfo['id'];
				$babBody->idprimaryoc = $idoc;
			}
			else
			{
				return array();
			}
		}
	}

	$ret = array();

	$res = $babDB->db_query("SELECT det.id_user, det.sn lastname, det.givenname firstname, det.mn middlename FROM ".BAB_OC_ROLES_USERS_TBL." ocrut LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role  left join ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ocrut.id_user WHERE ocrt.id_oc='".$idoc."'  AND ocrt.type = '1'");
	if( $res && $babDB->db_num_rows($res) > 1 )
	{
		while($arr = $babDB->db_fetch_array($res))
			{
			$ret[] = $arr;
			}
	}

	return $ret;
}

function bab_OCGetTemporaryEmployee($identity)
{
	global $babDB;
	
	$res = $babDB->db_query("SELECT det.id_user, det.sn lastname, det.givenname firstname, det.mn middlename  FROM ".BAB_OC_ROLES_USERS_TBL." ocrut LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role  left join ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ocrut.id_user WHERE ocrt.id_entity='".$identity."'  AND ocrt.type = '2'");
	if( $res && $babDB->db_num_rows($res) == 1 )
	{
		$arr = $babDB->db_fetch_array($res);
		return $arr;
	}

	return 0;
}

function bab_OCGetTemporaryEmployees($idoc='')
{
	global $babBody, $babDB;
	
	if( empty($idoc))
	{
		if( !empty($babBody->idprimaryoc))
		{
			$idoc = $babBody->idprimaryoc;
		}
		else
		{
			$res = $babDB->db_query("select oct.id from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where ddt.id_group='1' and oct.isprimary='Y'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$ocinfo = $babDB->db_fetch_array($res);
				$idoc = $ocinfo['id'];
				$babBody->idprimaryoc = $idoc;
			}
			else
			{
				return array();
			}
		}
	}

	$ret = array();

	$res = $babDB->db_query("SELECT det.id_user, det.sn lastname, det.givenname firstname, det.mn middlename  FROM ".BAB_OC_ROLES_USERS_TBL." ocrut LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role  left join ".BAB_DBDIR_ENTRIES_TBL." det on det.id=ocrut.id_user WHERE ocrt.id_oc='".$idoc."'  AND ocrt.type = '2'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		while($arr = $babDB->db_fetch_array($res))
			{
			$ret[] = $arr;
			}
	}

	return $ret;
}

function bab_OCGetCollaborators($identity)
{
	global $babDB;
	
	$ret = array();

	$res = $babDB->db_query("SELECT det.id_user, det.sn lastname, det.givenname firstname, det.mn middlename 
							 FROM ".BAB_OC_ROLES_USERS_TBL." ocrut
							 LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role
							 LEFT JOIN ".BAB_DBDIR_ENTRIES_TBL." det ON det.id = ocrut.id_user
							  WHERE ocrt.id_entity = '".$identity."'
							   AND ocrt.type != '1' and ocrut.isprimary = 'Y'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		while( $arr = $babDB->db_fetch_array($res))
		{
			$ret[] = $arr;
		}
	}
	return $ret;
}

function bab_OCGetUserEntities($iduser, $idoc='')
{
	global $babBody, $babDB;

	$ret = array();
	$ret['superior'] = array();
	$ret['temporary'] = array();
	$ret['members'] = array();

	if( empty($idoc))
	{
		if( !empty($babBody->idprimaryoc))
		{
			$idoc = $babBody->idprimaryoc;
		}
		else
		{
			$res = $babDB->db_query("select oct.id from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where ddt.id_group='1' and oct.isprimary='Y'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$ocinfo = $babDB->db_fetch_array($res);
				$idoc = $ocinfo['id'];
				$babBody->idprimaryoc = $idoc;
			}
			else
			{
				return $ret;
			}
		}
	}

	$res = $babDB->db_query("SELECT ocrt.id_entity, ocet.name as entity_name, ocet.description as entity_description, type  FROM ".BAB_OC_ROLES_TBL." ocrt LEFT JOIN ".BAB_OC_ROLES_USERS_TBL." ocrut ON ocrt.id = ocrut.id_role  left join ".BAB_OC_ENTITIES_TBL." ocet on ocet.id=ocrt.id_entity LEFT JOIN ".BAB_DBDIR_ENTRIES_TBL." det ON det.id=ocrut.id_user WHERE det.id_user='".$iduser."' and ocrt.id_oc='".$idoc."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		while( $arr = $babDB->db_fetch_array($res))
			{
			$rr = array( 'id'=> $arr['id_entity'], 'name' => $arr['entity_name'] , 'description' => $arr['entity_description'] );
			switch($arr['type'])
				{
				case '1':
					$ret['superior'][] = $rr;
					break;
				case '2':
					$ret['temporary'][] = $rr;
					break;
				default:
					$ret['members'][] = $rr;
					break;
				}
			}
	}

	return $ret;

}



/**
 * Returns an ordered mysql result set containing the members of the entity $entityId.
 * 
 * Results fetched from the result set have the following structure:
 * array(
 * 		'id_dir_entry' => directory entry id (@see bab_getDirEntry)
 * 		'role_type' =>  1 = Superior, 2 = Temporary employee, 3 = Members, 0 = Other collaborators
 * 		'role_name' => The role title
 * 		'user_disabled' => 1 = disabled, 0 = not disabled
 * 		'user_confirmed' => 1 = confirmed, 0 = not confirmed
 * 		'sn' =>	The member's surname (last name)
 * 		'givenname' => The member's given name (first name)
 * )
 * The result set is ordered by role types (in order 1,2,3,0) and by user name (according to ovidentia name ordering rules).
 * 
 * @param int $entityId			Id of orgchart entity.
 * 
 * @return resource		The mysql resource or FALSE on error
 */
function bab_OCSelectEntityCollaborators($entityId)
{
	global $babDB, $babBody;

	$sql = 'SELECT users.id_user AS id_dir_entry,';
	$sql .= '      roles.type AS role_type,';
	$sql .= '      roles.name AS role_name,';
	$sql .= '      babusers.disabled AS user_disabled,';
	$sql .= '      babusers.is_confirmed AS user_confirmed,';
	$sql .= '      dir_entries.sn,';
	$sql .= '      dir_entries.givenname';
	$sql .= ' FROM ' . BAB_OC_ROLES_USERS_TBL . ' AS users';
	$sql .= ' LEFT JOIN ' . BAB_OC_ROLES_TBL . ' AS roles ON users.id_role = roles.id';
	$sql .= ' LEFT JOIN ' . BAB_DBDIR_ENTRIES_TBL . ' AS dir_entries ON users.id_user = dir_entries.id';
	$sql .= ' LEFT JOIN ' . BAB_USERS_TBL . ' AS babusers ON dir_entries.id_user = babusers.id';
	$sql .= ' WHERE roles.id_entity = ' . $babDB->quote($entityId);
	$sql .= ' ORDER BY (roles.type - 1 % 4) ASC, '; // We want role types to appear in the order 1,2,3,0
	$sql .= ($babBody->nameorder[0] === 'F') ?
					' dir_entries.givenname ASC, dir_entries.sn ASC'
					: ' dir_entries.sn ASC, dir_entries.givenname ASC';

	$members = $babDB->db_query($sql);

	return $members;
}




//-------------------------------------------------------------------------

/**
 * Gets a value that indicates whether a user have administration right on an organization chart.
 * 
 * @return	bool	True on success, false othewise 
 */
function bab_OCHaveAdminRight()
{
	global $babBody;
	
	return ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || 
		(isset($babBody->currentDGGroup['orgchart']) && $babBody->currentDGGroup['orgchart'] == 'Y');
}


/**
 * Creates an organizational chart.
 * 
 * If an organizationnal chart with the same name in
 * the delegation already exist nothing is created.
 * 
 * @param	string	$sName			The name of the organizational chart
 * @param	string	$sDescription	The description of the organizational chart
 * @param	int		$iIdDelegation	The delegation identifier of the organizational chart
 * @param	int		$iIdDirectory	The directory identifier (bab_db_directories identifier)
 * 
 * @return	int		The identifier of the created organizational chart on success, 
 * 					0 on error 
 */
function bab_OCCreate($sName, $sDescription, $iIdDelegation, $iIdDirectory)
{
	global $babBody;
	if(false === bab_OCHaveAdminRight())
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	if(true === bab_OCExist($sName, $iIdDelegation))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	require_once(dirname(__FILE__) . '/dirincl.php');
	$sDirectoryName = getDirectoryName($iIdDirectory, BAB_DB_DIRECTORIES_TBL);
	if(0 === strlen($sDirectoryName))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	global $babDB;

	$sQuery = 
		'INSERT INTO ' . BAB_ORG_CHARTS_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`name`, `description`, `isprimary`, `edit`, ' .
				'`edit_author`, `edit_date`, `id_dgowner`, `id_directory`, ' .
				'`type`, `id_first_node`, `id_closed_nodes` ' .
			') ' .
		'VALUES ' . 
			'(\'\', ' . 
				$babDB->quote($sName) . ', ' . 
				$babDB->quote($sDescription) . ', ' . 
				$babDB->quote('N') . ', ' . 
				$babDB->quote('N') . ', ' . 
				$babDB->quote(0) . ', ' . 
				$babDB->quote('0000-00-00 00:00:00') . ', ' . 
				$babDB->quote($iIdDelegation) . ', ' . 
				$babDB->quote($iIdDirectory) . ', ' . 
				$babDB->quote(0) . ', ' . 
				$babDB->quote(0) . ', ' . 
				$babDB->quote('') . 
			')'; 

	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		return $babDB->db_insert_id();
	}
	return 0;
}


/**
 * Deletes an organizational chart.
 * 
 * @param	int		$iIdOrgChart	The identifier of the organizational chart
 * 
 * @return	bool	True on success, False on error
 */
function bab_OCDelete($iIdOrgChart)
{
	//TODO
	//Finish this function
	global $babBody;
	if(false === bab_OCHaveAdminRight())
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	$aData = bab_OCGet($iIdOrgChart);
	if(false === $aData || 'Y' === (string) $aData['isprimary'])
	{
		$babBody->addError(bab_translate("8 Acces denied"));
		return false;
	}
	
	global $babDB;
	$sQuery = 'DELETE FROM ' . BAB_ORG_CHARTS_TBL . ' WHERE id = ' . $babDB->quote($iIdOrgChart); 
	//bab_debug($query);
	return $babDB->db_query($sQuery);
}


/**
 * Get an organizational chart.
 * 
 * The array have those keys :
 * 
 * <ul>
 * 	<li>id
 * 	<li>name
 * 	<li>description
 *  <li>edit
 *  <li>edit_author
 *  <li>edit_date
 *  <li>id_dgowner
 *  <li>id_directory
 *  <li>type
 *  <li>id_first_node
 *  <li>id_closed_nodes
 * </ul>
 * 
 * @param	int	$iIdOrgChart	The identifier of the organizational chart
 * 
 * @return	array|false			array on success, false otherwise
 */
function bab_OCGet($iIdOrgChart)
{
	global $babDB;

	$sQuery = 
		'SELECT ' .
			'* ' .
		'FROM ' .
			BAB_ORG_CHARTS_TBL . ' ' .
		'WHERE ' . 
			'id = ' . $babDB->quote($iIdOrgChart);
	
	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		if(0 < $iNumRows)
		{
			return $babDB->db_fetch_assoc($oResult);
		}
	}
	return false;
}


/**
 * Gets a value that indicates whether an organizational chart exists.
 * 
 * @param	string	$sName			The identifier of the organizational
 * @param	int		$iIdDelegation	The delegation identifier 
 *  
 * @return	bool	True if the organizational chart exist, False on error
 */
function bab_OCExist($sName, $iIdDelegation)
{
	global $babDB;
	
	$sName = trim($sName);
	if(0 === strlen($sName))
	{
		return false;
	}
	
	$sQuery = 
		'SELECT ' . 
			'id ' .
		'FROM ' . 
			BAB_ORG_CHARTS_TBL . ' ' .
		'WHERE ' . 
			'id_dgowner = ' . $babDB->quote($iIdDelegation) . ' AND ' .
			'name LIKE ' . $babDB->quote($sName);
			
	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	return (false !== $oResult && 0 !== $babDB->db_num_rows($oResult));
}


/**
 * Locks an organizational chart.
 * 
 * @param	int		$iIdOrgChart	The identifier of the organizational chart
 * @param	int		$iIdUser		The user identifier
 * 
 * @return	bool	True on success, False on error
 */
function bab_OCLock($iIdOrgChart, $iIdUser)
{
	global $babBody;
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	if(true === bab_OCIsLocked($iIdOrgChart))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	global $babDB;
	$sQuery = 
		'UPDATE ' .	
			BAB_ORG_CHARTS_TBL . ' ' . 
		'SET ' .
			'`edit` = ' . $babDB->quote('Y') . ', ' .
			'`edit_author` = ' . $babDB->quote($iIdUser) . ', ' .
			'`edit_date` = ' . $babDB->quote(date("Y-m-d H:i:s")) . ' ' .
		'WHERE ' . 
			'`id` = ' . $babDB->quote($iIdOrgChart);
			
	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		return (0 !== $babDB->db_affected_rows($oResult));			
	}
	return false;
}


/**
 * Unlocks an organizational chart.
 * 
 * @param	int		$iIdOrgChart	The identifier of the organizational chart
 * @param	int		$iIdUser		The user identifier
 *  * 
 * @return	bool	True on success, False on error
 */
function bab_OCUnlock($iIdOrgChart, $iIdUser)
{
	global $babBody;
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	$aData = bab_OCGet($iIdOrgChart);	
	if(false !== $aData && 'Y' === (string) $aData['edit'] && (int) $iIdUser === (int) $aData['edit_author'])
	{
		global $babDB;
		$sQuery = 
			'UPDATE ' .	
				BAB_ORG_CHARTS_TBL . ' ' . 
			'SET ' .
				'`edit` = ' . $babDB->quote('N') . ' ' .
			'WHERE ' . 
				'`id` = ' . $babDB->quote($iIdOrgChart);
				
		//bab_debug($sQuery);
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult)
		{
			return (0 !== $babDB->db_affected_rows($oResult));			
		}
		return false;
	}
	else
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
}


/**
 * Gets a value that indicates whether an organizational chart is locked.
 * 
 * @param	int		$iIdOrgChart	The identifier of the organizational chart
 * 
 * @return	bool	True on success, False on error
 */
function bab_OCIsLocked($iIdOrgChart)
{
	$aData = bab_OCGet($iIdOrgChart);	
	return (false !== $aData && 'Y' === (string) $aData['edit'] && 0 !== (int) $aData['edit_author']);
}


/**
 * Gets the lock value info.
 * 
 * The array have those keys :
 * 
 * <ul>
 * 	<li>iIdUser 			: User identifier</li>
 * 	<li>sNickName 			: User nickname</li>
 * 	<li>sFirstName 			: User firstname</li>
 *  <li>sLastName 			: User lastname</li>
 * </ul>
 * 
 * @param	int		$iIdOrgChart	The identifier of the organizational chart
 * 
 * @return	array	The lock info
 */
function bab_OCGetLockInfo($iIdOrgChart)
{
	$aLockInfo = array('iIdUser' => 0, 'sNickName' => '', 'sFirstName' => '', 'sLastName' => '');
	
	$aData = bab_OCGet($iIdOrgChart);
	if(false !== $aData)
	{
		$iIdUser = (int) $aData['edit_author'];
		$aLockInfo['iIdUser'] = $iIdUser;
		
		$bComposeUserName = false;
		$aUserName = bab_getUserName($iIdUser, $bComposeUserName);
		
		$aLockInfo['sNickName']		= bab_getUserNickname($iIdUser);
		$aLockInfo['sFirstName']	= $aUserName['firstname'];
		$aLockInfo['sLastName']		= $aUserName['lastname'];
	}
	return $aLockInfo;
}



define('BAB_OC_ROLE_CUSTOM', '0');
define('BAB_OC_ROLE_SUPERIOR', '1');
define('BAB_OC_ROLE_TEMPORARY_EMPLOYEE', '2');
define('BAB_OC_ROLE_MEMBER', '3');


function bab_OCCreateRole($sName, $sDescription, $iIdEntity, $iType, $sCardinality)
{
	
}

function bab_OCDeleteRole($iIdRole)
{
	
}

function bab_OCGetRolesByOrganizationChartId($iIdOrgChart, $iType = null)
{
	
}

function bab_OCGetRolesByEntityId($iIdEntity, $iType = null)
{
	
}

function bab_OCGetRoleById($iIdRole)
{
	
}



function bab_OCCreateRoleUser($iIdRole, $iIdUser)
{
	
}

function bab_OCDeleteRoleUser($iIdRoleUser)
{
	
}

function bab_OCGetRoleUserByEntityId($iIdEntity, $iType)
{
	
}

function bab_OCAppendEntity($iIdParentEntity, $sName, $sDescription, $sNote, $iIdGroup)
{
	
}

function bab_OCRemoveEntity($iIdEntity)
{
	
}

function bab_OCRemoveEntityChild($iIdEntity)
{

}

function bab_OCInsertBeforeEntity($iIdEntity, $sName, $sDescription, $sNote, $iIdGroup)
{
	
}

function bab_OCMoveEntity($iIdEntity, $iIdParent)
{
	
}



/*
function bab_OCCreateEntity($iIdOrgChart, $iIdNode, $sName, $sDescription, $sNote, $iIdGroup)
{
	
}


function bab_OCDeleteEntityByNodeId($iIdNode)
{
	
}


function bab_OCDeleteEntityById($iIdEntity)
{
	
}
//*/