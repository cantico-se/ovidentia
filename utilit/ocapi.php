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
	
	return ($babBody->isSuperAdmin && 0 === (int) $babBody->currentAdmGroup) || 
		(isset($babBody->currentDGGroup['orgchart']) && 'Y' === (string) $babBody->currentDGGroup['orgchart']);
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
	
	$sName = trim($sName);
	if(0 === strlen($sName))
	{
		$babBody->addError(bab_translate("ERROR: You must provide a name"). ' !');
		return false;
	}
	
	if(false === bab_OCHaveAdminRight())
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	if(true === bab_OCExist($sName, $iIdDelegation))
	{
		$babBody->addError(bab_translate("ERROR: This organization chart already exists"));
		return false;
	}
	
	require_once(dirname(__FILE__) . '/dirincl.php');
	$sDirectoryName = getDirectoryName($iIdDirectory, BAB_DB_DIRECTORIES_TBL);
	if(0 === strlen($sDirectoryName))
	{
		$babBody->addError(bab_translate("ERROR: The directory does not exist"));
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
		$babBody->addError(bab_translate("Acces denied"));
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
 * This function use a local cache.
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
	static $aOrgChartInfo = array(); 

	if(!array_key_exists($iIdOrgChart, $aOrgChartInfo))
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
			if(1 === $iNumRows)
			{
				$aOrgChartInfo[$iIdOrgChart] = $babDB->db_fetch_assoc($oResult);
				return $aOrgChartInfo[$iIdOrgChart];
			}
		}
		return false;
	}
	else
	{
		return $aOrgChartInfo[$iIdOrgChart];
	}
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
			'name LIKE ' . $babDB->quote($babDB->db_escape_like($sName));
			
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
	
	if(false === bab_OCIsLockedBy($iIdOrgChart, $iIdUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}

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
 * Gets a value that indicates whether an organizational chart is locked by a specific user.
 * 
 * @param	int		$iIdOrgChart	The identifier of the organizational chart
 * 
 * @return	bool	True on success, False on error
 */
function bab_OCIsLockedBy($iIdOrgChart, $iIdUser)
{
	$aData = bab_OCGet($iIdOrgChart);	
	return (false !== $aData && 'Y' === (string) $aData['edit'] && (int) $iIdUser === (int) $aData['edit_author']);
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

define('BAB_OC_TREES_PREVIOUS_SIBLING', '1');
define('BAB_OC_TREES_NEXT_SIBLING', '2');
define('BAB_OC_TREES_LAST_CHILD', '0');


function bab_OCCreateRole($iIdEntity, $sName, $sDescription, $iType, $sCardinality)
{
	global $babBody;
	
	$sName = trim($sName);
	if(0 === strlen($sName))
	{
		$babBody->addError(bab_translate("ERROR: You must provide a name"). ' !');
		return false;
	}
	
	static $aGoodType = array(
		BAB_OC_ROLE_CUSTOM => BAB_OC_ROLE_CUSTOM, 
		BAB_OC_ROLE_SUPERIOR => BAB_OC_ROLE_SUPERIOR, 
		BAB_OC_ROLE_TEMPORARY_EMPLOYEE => BAB_OC_ROLE_TEMPORARY_EMPLOYEE, 
		BAB_OC_ROLE_MEMBER => BAB_OC_ROLE_MEMBER 
	);
	
	if(!array_key_exists($iType, $aGoodType))
	{
		$babBody->addError(bab_translate("ERROR: The specified role type is not valid"));
		return false;
	}
	
	static $aGoodCardinality = array('Y' => 'Y', 'N' => 'N');
	if(!array_key_exists($sCardinality, $aGoodCardinality))
	{
		$babBody->addError(bab_translate("ERROR: The specified cardinality is not valid"));
		return false;
	}
	
	$aEntity = bab_OCGetEntityEx($iIdEntity);
	if(false === $aEntity)
	{
		return false;
	}
	
	$iIdOrgChart = (int) $aEntity['id_oc'];
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdUser))
	{
		$babBody->addError(bab_translate("ERROR: You do not have right to create a role"));
		return false;
	}
	
	global $babDB;

	$sQuery = 
		'INSERT INTO ' . BAB_OC_ROLES_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`name`, `description`, `id_oc`, `id_entity`, ' .
				'`id_group`, `type`, `cardinality` ' .
			') ' .
		'VALUES ' . 
			'(\'\', ' . 
				$babDB->quote($sName) . ', ' . 
				$babDB->quote($sDescription) . ', ' . 
				$babDB->quote($iIdOrgChart) . ', ' . 
				$babDB->quote($iType) . ', ' . 
				$babDB->quote($sCardinality) . 
			')'; 

	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		return $babDB->db_insert_id();
	}
	
	return false;
}

function bab_OCDeleteRole($iIdRole)
{
	
}

function bab_OCGetRole($iIdRole)
{
	global $babBody;
	
	static $aRole = array();
	
	if(!array_key_exists($iIdRole, $aRole))
	{
		global $babDB;
		$sQuery = 
			'SELECT ' . 
				'* ' .
			'FROM ' . 
				BAB_OC_ROLES_TBL . ' ' .
			'WHERE ' . 
				'id = ' . $babDB->quote($iIdRole);
				
		//bab_debug($sQuery);
		$aData = false;
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult)
		{
			$iNumRows = $babDB->db_num_rows($oResult);
			if(0 < $iNumRows)
			{
				$aData = $babDB->db_fetch_assoc($oResult);
			}
		}
	
		if(false === $aData)
		{
			$babBody->addError(bab_translate("Error: Unknown role"));
			return false;
		}
		$aRole[$iIdEntity] = $aData;
	}
		
	$iIdOrgChart		= (int) $aRole[$iIdEntity]['id_oc'];
	$iIdUser			= (int) $GLOBALS['BAB_SESS_USERID'];		
	$bHaveUpdateRight	= bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdUser);
	$bHaveViewRight		= bab_isAccessValid(BAB_OCVIEW_GROUPS_TBL, $iIdOrgChart, $iIdUser);
	
	if(false === $bHaveUpdateRight && false === $bHaveViewRight)
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	return $aRole[$iIdEntity];
}



function bab_OCCreateRoleUser($iIdRole, $iIdUser, $sPrimary)
{
	//This function test right
	if(false !== bab_OCGetRole($iIdRole))
	{
		global $babDB;
		
		$sQuery = 
			'INSERT INTO ' . BAB_OC_ROLES_USERS_TBL . ' ' .
				'(' .
					'`id`, ' .
					'`id_role`, `id_user`, `id_oc`, `isprimary` ' .
				') ' .
			'VALUES ' . 
				'(\'\', ' . 
					$babDB->quote($idrole) . ', ' . 
					$babDB->quote($iIdUser) . ', ' . 
					$babDB->quote($$sPrimary) . 
				')'; 
	
		//bab_debug($sQuery);
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult)
		{
			return $babDB->db_insert_id();
		}
	}
	return false;
}

function bab_OCDeleteRoleUser($iIdRoleUser)
{
	
}

function bab_OCGetRoleUserByEntityId($iIdEntity, $iType)
{
	
}

function bab_OCGetEntityEx($iIdEntity)
{
	global $babBody;

	static $aEntity = array();
	
	if(!array_key_exists($iIdEntity, $aEntity))
	{
		global $babDB;
		$sQuery = 
			'SELECT ' . 
				'* ' .
			'FROM ' . 
				BAB_OC_ENTITIES_TBL . ' ' .
			'WHERE ' . 
				'id = ' . $babDB->quote($iIdEntity);
	
		//bab_debug($sQuery);
		$aData = false;
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult && 0 !== $babDB->db_num_rows($oResult));
		{
			$aData = $babDB->db_fetch_assoc($oResult);
		}
	
		if(false === $aData)
		{
			$babBody->addError(bab_translate("Error: Unknown entity"));
			return false;
		}
		
		$aEntity[$iIdEntity] = $aData;
	}
		
	$iIdOrgChart		= (int) $aEntity[$iIdEntity]['id_oc'];
	$iIdUser			= (int) $GLOBALS['BAB_SESS_USERID'];		
	$bHaveUpdateRight	= bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdUser);
	$bHaveViewRight		= bab_isAccessValid(BAB_OCVIEW_GROUPS_TBL, $iIdOrgChart, $iIdUser);
	
	if(false === $bHaveUpdateRight && false === $bHaveViewRight)
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	
	return $aEntity[$iIdEntity];
}


function bab_OCCreateEntity($iIdOrgChart, $iIdParentEntity, $sName, $sDescription, $sNote, $iPosition, $mixedGroup = null, $iIdParentGroup = 1)
{
	global $babBody;
	
	$sName = trim($sName);
	if(0 === strlen($sName))
	{
		$babBody->addError(bab_translate("ERROR: You must provide a name"). ' !');
		return false;
	}
	
	$iIdUser = (int) $GLOBALS['BAB_SESS_USERID'];		
	if(false === bab_OCIsLockedBy($iIdOrgChart, $iIdUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
require_once dirname(__FILE__) . '/grpincl.php';
if(/*1 !== $iIdParentGroup &&*/ false === bab_isGroup($iIdParentGroup))
{
	$babBody->addError(bab_translate("Error: Cannot get organization chart information"));
	return false;
}
	
	$aOrgChart = bab_OCGet($iIdOrgChart);
	if(false === $aOrgChart)
	{
		$babBody->addError(bab_translate("Error: Cannot get organization chart information"));
		return false;
	}
	
	$iIdParentNode = 0;
	if(0 !== $iIdParentEntity)
	{
		//This function test right and set error on babBody
		$aData = bab_OCGetEntityEx($iIdParentEntity);
		if(false === $aData)
		{
			return false;
		}
		
		$iIdParentNode = (int) $aData['id_node'];
	}

	//This function set error on babBody
	$iIdTreeNode = bab_OCTreeCreateNode($iIdParentNode, $iPosition);
	if(false === $iIdTreeNode)
	{
		return false;
	}

	$iIdGroup = 0;
	{
		if(is_null($mixedGroup))
		{
			$mixedGroup = 'none';
		}
		
		switch($mixedGroup)
		{
			case 'new':
				if('Y' === (string) $aOrgChart['isprimary'] && 1 === (int) $aOrgChart['id_group'])
				{
					require_once dirname(__FILE__) . '/grpincl.php';
					
					$iIdDelegation = 0;
					$iIdManager = 0;
					
					$iIdGroup = bab_addGroup($sName, $sDescription, $iIdManager, $iIdDelegation, $iIdParentGroup);
				}
				break;
			case 'none':
				$iIdGroup = 0;
				break;
			default:
				$iIdGroup = (int) $mixedGroup;
				break;
		}
	}

	global $babDB;

	$sQuery = 
		'INSERT INTO ' . BAB_OC_ENTITIES_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`name`, `description`, `id_oc`, `id_node`, `id_group` ' .
			') ' .
		'VALUES ' . 
			'(\'\', ' . 
				$babDB->quote($sName) . ', ' . 
				$babDB->quote($sDescription) . ', ' . 
				$babDB->quote($iIdOrgChart) . ', ' . 
				$babDB->quote($iIdTreeNode) . ', ' . 
				$babDB->quote($iIdGroup) . 
			')'; 

	//bab_debug($sQuery);
	$iIdEntity = 0;
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iIdEntity = $babDB->db_insert_id();
	}

	bab_getGroupName($iIdGroup);
	
	if(0 === $iIdEntity)
	{
		$oBabTree = new bab_dbtree(BAB_OC_TREES_TBL, $iIdOrgChart);
		$oBabTree->remove($iIdTreeNode);
		if('new' === (string) $mixedGroup)
		{
			bab_deleteGroup($iIdGroup);
		}
		
		$babBody->addError(bab_translate("Error: Cannot create the entity"));
		return false;
	}

	if('none' !== (string) $mixedGroup)
	{
		$sQuery = 
			'UPDATE ' . 
				BAB_GROUPS_TBL . 
			'SET ' . 
				'id_ocentity = ' . $babDB->quote($iIdEntity) . 
			'WHERE ' .
				'id = ' . $babDB->quote($iIdGroup);
		
		//bab_debug($sQuery);	
		$babDB->db_query($sQuery);
	}
	
	$iIdSuperiorRole	= bab_OCCreateRole($iIdEntity, bab_translate("Superior"), '', BAB_OC_ROLE_SUPERIOR, 'N');
	$iIdTempEmpRole 	= bab_OCCreateRole($iIdEntity, bab_translate("Temporary employee"), '', BAB_OC_ROLE_TEMPORARY_EMPLOYEE, 'N');
	$iIdMemberRole 		= bab_OCCreateRole($iIdEntity, bab_translate("Members"), '', BAB_OC_ROLE_MEMBER, 'Y');
	
	if('none' !== (string) $mixedGroup && 'new' !== (string) $mixedGroup)
	{
		if('Y' === (string) $aOrgChart['isprimary'] && 1 === (int) $aOrgChart['id_group'])
		{
			$sQuery = 
				'SELECT ' .
					'det.id ' .
				'FROM ' . 
					BAB_DBDIR_ENTRIES_TBL . ' det ' .
				'LEFT JOIN ' . 
					BAB_USERS_GROUPS_TBL . ' ugt on det.id_user = ugt.id_object ' . 
				'WHERE ' .
					'ugt.id_group = ' . $babDB->quote($iIdGroup) . ' AND ' .
					'det.id_directory = \'0\'';

			//bab_debug($sQuery);
			$oResult = $babDB->db_query($sQuery);
			if(false !== $oResult)
			{
				$iNumRows = $babDB->db_num_rows($oResult);
				if(0 < $iNumRows)
				{				
					while(false !== ($aData = $babDB->db_fetch_assoc($oResult)))
					{
						$sQuery = 
							'SELECT ' .
								'ocrut.id ' .
							'FROM ' . 
								BAB_OC_ROLES_USERS_TBL . ' ocrut ' .
							'LEFT JOIN ' . 
								BAB_OC_ROLES_TBL . ' ugt on ocrt on ocrut.id_role=ocrt.id ' . 
							'WHERE ' .
								'ocrt.id_oc = ' . $babDB->quote($iIdOrgChart) . ' AND ' .
								'ocrut.id_user = ' . $babDB->quote($aData['id']) . ' AND ' .
								'ocrut.isprimary = \'Y\'';
			
						//bab_debug($sQuery);
												
						$oResult2 = $babDB->db_query($sQuery);
						if(false !== $oResult2)
						{
							$sPrimary = 'Y';
							$iNumRows = $babDB->db_num_rows($oResult2);
							if(0 < $iNumRows)
							{
								$isprimary = 'N';
							}
				
							$iIdUser = (int) $aData['id'];
							bab_OCCreateRoleUser($iIdMemberRole, $iIdUser, $sPrimary);
						}
					}
				}
			}
		}
	}
}

function bab_OCRemoveEntity($iIdEntity)
{
	
}

function bab_OCRemoveEntityChild($iIdEntity)
{

}

function bab_OCMoveEntity($iIdEntity, $iIdParent)
{
	
}

function bab_OCTreeCreateNode($iIdParentNode, $iPosition)
{
	$oBabTree = new bab_dbtree(BAB_OC_TREES_TBL, $iIdOrgChart);
	
	$iIdNode = 0;
	
	//Root node
	if(0 === $iIdParentNode)
	{
		$iIdNode = $oBabTree->add();	
	}
	else
	{//Child node
		switch($iPosition)
		{
			case BAB_OC_TREES_PREVIOUS_SIBLING:
				$iIdNode = (int) $oBabTree->add(0, $iIdParentNode, false);
				break;
			case BAB_OC_TREES_NEXT_SIBLING:
				$iIdNode = (int) $oBabTree->add(0, $iIdParentNode);
				break;
			case BAB_OC_TREES_LAST_CHILD:
			default:
				$iIdNode = (int) $oBabTree->add($iIdParentNode);
				break;
		}
	}

	if(0 === $iIdNode)
	{
		$babBody->addError(bab_translate("ERROR: The node was not created"));
		return false;
	}
	return $iIdNode;
}