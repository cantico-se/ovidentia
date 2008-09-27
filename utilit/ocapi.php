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
				$ocinfo = $babDB->db_fetch_assoc($res);
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
		$arr = $babDB->db_fetch_assoc($res);
//		$ocrootentities[$idoc] = array('id' => $arr['id'], 'name' => $arr['name'], 'description' => $arr['description']);
		$ocrootentities[$idoc] = $arr;
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
function bab_OCGet($iIdSessUser, $iIdOrgChart)
{
	global $babDB, $babBody;

	if(false === bab_OCIsAccessValid($iIdSessUser, $iIdOrgChart))
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	
	$sQuery = 
		'SELECT ' .
			'orgChart.*, ' .
			'dbDir.name sDirName, ' .
			'dbDir.id_group ' .
		'FROM ' .
			BAB_ORG_CHARTS_TBL . ' orgChart ' .
		'LEFT JOIN ' .
			BAB_DB_DIRECTORIES_TBL . ' dbDir ON dbDir.id = orgChart.id_directory ' .
		'WHERE ' . 
			'orgChart.id = ' . $babDB->quote($iIdOrgChart);
			
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


function bab_OCGetRoot($iIdSessUser, $iIdOrgChart)
{
	global $babDB, $babBody;

	if(false === bab_OCIsAccessValid($iIdSessUser, $iIdOrgChart))
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	
	$sQuery = 
		'SELECT ' .
			'orgChart.*, ' .
			'dbDir.name sDirName, ' .
			'dbDir.id_group, ' .
			'ocTree.id id_node, ' .
			'ocEntity.id id_entity ' .
		'FROM ' .
			BAB_ORG_CHARTS_TBL . ' orgChart ' .
		'LEFT JOIN ' .
			BAB_DB_DIRECTORIES_TBL . ' dbDir ON dbDir.id = orgChart.id_directory, ' .
			BAB_OC_TREES_TBL . ' ocTree ' .
		'LEFT JOIN ' .
			BAB_OC_ENTITIES_TBL . ' ocEntity ON ocEntity.id_node = ocTree.id ' .
		'WHERE ' . 
			'orgChart.id = ' . $babDB->quote($iIdOrgChart) . ' AND ' . 
			'ocTree.id_user = ' . $babDB->quote($iIdOrgChart) . ' AND ' .
			'ocTree.id_parent = ' . $babDB->quote(0);
			
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


/**
 * Gets a value that indicates whether an organizational chart exists.
 * 
 * @param	string	$sName			The identifier of the organizational
 * @param	int		$iIdDelegation	The delegation identifier 
 *  
 * @return	bool	True if the organizational chart exist, False on error
 */
function bab_OCExist($iIdSessUser, $sName, $iIdDelegation)
{
	global $babDB, $babBody;
	if(false === bab_OCIsAccessValid($iIdSessUser, $iIdOrgChart))
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	
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
function bab_OCLock($iIdSessUser, $iIdOrgChart)
{
	global $babDB, $babBody;
	
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	if(true === bab_OCIsLocked($iIdSessUser, $iIdOrgChart))
	{
		if(bab_OCIsLockedBy($iIdSessUser, $iIdOrgChart, $iIdSessUser))
		{
			return true;
		}
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	$sQuery = 
		'UPDATE ' .	
			BAB_ORG_CHARTS_TBL . ' ' . 
		'SET ' .
			'`edit` = ' . $babDB->quote('Y') . ', ' .
			'`edit_author` = ' . $babDB->quote($iIdSessUser) . ', ' .
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
function bab_OCUnlock($iIdSessUser, $iIdOrgChart)
{
	global $babDB, $babBody;
	
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	if(false === bab_OCIsLockedBy($iIdSessUser, $iIdOrgChart, $iIdSessUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
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
function bab_OCIsLocked($iIdSessUser, $iIdOrgChart)
{
	global $babBody;
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	$aData = bab_OCGet($iIdSessUser, $iIdOrgChart);	
	return (false !== $aData && 'Y' === (string) $aData['edit'] && 0 !== (int) $aData['edit_author']);
}


/**
 * Gets a value that indicates whether an organizational chart is locked by a specific user.
 * 
 * @param	int		$iIdOrgChart	The identifier of the organizational chart
 * 
 * @return	bool	True on success, False on error
 */
function bab_OCIsLockedBy($iIdSessUser, $iIdOrgChart, $iIdUser)
{
	global $babBody;
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	$aData = bab_OCGet($iIdSessUser, $iIdOrgChart);	
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
function bab_OCGetLockInfo($iIdSessUser, $iIdOrgChart)
{
	$aLockInfo = array('iIdUser' => 0, 'sNickName' => '', 'sFirstName' => '', 'sLastName' => '');
	
	//this function test rights
	$aData = bab_OCGet($iIdSessUser, $iIdOrgChart);
	if(false !== $aData && 'Y' === (string) $aData['edit'])
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

define('BAB_OC_DELETE_ENTITY_ONLY', '0');
define('BAB_OC_DELETE_ENTITY_AND_CHILDREN', '1');
define('BAB_OC_DELETE_CHILDREN_ONLY', '2');

define('BAB_OC_MOVE_ENTITY_AND_CHILDREN', '1');
define('BAB_OC_MOVE_ENTITY_ONLY', '0');

define('BAB_OC_MOVE_TYPE_AS_PREVIOUS_SIBLING', '1');
define('BAB_OC_MOVE_TYPE_AS_NEXT_SIBLING', '2');
define('BAB_OC_MOVE_TYPE_AS_CHILD', '0');







function bab_OCIsAccessValid($iIdSessUser, $iIdOrgChart)
{
	$bHaveUpdateRight	= bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser);
	$bHaveViewRight		= bab_isAccessValid(BAB_OCVIEW_GROUPS_TBL, $iIdOrgChart, $iIdSessUser);
	return (false !== $bHaveUpdateRight || false !== $bHaveViewRight);
}






//Entity functions

function bab_OCCreateEntity($iIdSessUser, $iIdOrgChart, $iIdParentEntity, $sName, $sDescription, $sNote, $iPosition, $mixedGroup = null, $iIdParentGroup = BAB_REGISTERED_GROUP)
{
	global $babBody;
	
	$sName = trim($sName);
	if(0 === strlen($sName))
	{
		$babBody->addError(bab_translate("ERROR: You must provide a name"). ' !');
		return false;
	}
	
	if(false === bab_OCIsLockedBy($iIdSessUser, $iIdOrgChart, $iIdSessUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	//If is locked by $iIdSessUser so the user have update rights
	/*
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	//*/
	
	require_once dirname(__FILE__) . '/grpincl.php';
	if(false === bab_isGroup($iIdParentGroup))
	{
		$babBody->addError(bab_translate("Error: The parent group is not a valid group"));
		return false;
	}
	
	$aOrgChart = bab_OCGet($iIdSessUser, $iIdOrgChart);
	if(false === $aOrgChart)
	{
		$babBody->addError(bab_translate("Error: Cannot get organization chart information"));
		return false;
	}
	
	$iIdParentNode = 0;
	if(0 !== $iIdParentEntity)
	{
		//This function test right and set error on babBody
		$aData = bab_OCGetEntityEx($iIdSessUser, $iIdParentEntity);
		if(false === $aData)
		{
			return false;
		}
		
		$iIdParentNode = (int) $aData['id_node'];
	}

	//This function set error on babBody
	$iIdTreeNode = bab_OCTreeCreateNode($iIdOrgChart, $iIdParentNode, $iPosition);
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
					$iIdGroup = bab_groupIsChildOf($iIdParentGroup, $sName);
					if(false === $iIdGroup)
					{
						$iIdGroup = bab_addGroup($sName, $sDescription, $iIdManager, $iIdDelegation, $iIdParentGroup);
					}
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

	$bIsGroup = true;
	if(0 !== $iIdGroup)
	{
		$bIsGroup = bab_isGroup($iIdParentGroup);
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

	if(0 === $iIdEntity || !$bIsGroup)
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
				BAB_GROUPS_TBL . ' ' .
			'SET ' . 
				'id_ocentity = ' . $babDB->quote($iIdEntity) . ' ' . 
			'WHERE ' .
				'id = ' . $babDB->quote($iIdGroup);
		
		//bab_debug($sQuery);	
		$babDB->db_query($sQuery);
	}
	
	$iIdSuperiorRole	= bab_OCCreateRole($iIdSessUser, $iIdEntity, bab_translate("Superior"), '', BAB_OC_ROLE_SUPERIOR, 'N');
	$iIdTempEmpRole 	= bab_OCCreateRole($iIdSessUser, $iIdEntity, bab_translate("Temporary employee"), '', BAB_OC_ROLE_TEMPORARY_EMPLOYEE, 'N');
	$iIdMemberRole 		= bab_OCCreateRole($iIdSessUser, $iIdEntity, bab_translate("Members"), '', BAB_OC_ROLE_MEMBER, 'Y');
	
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
						$iIdUser = (int) $aData['id'];
						bab_OCCreateRoleUser($iIdSessUser, $iIdMemberRole, $iIdUser);
					}
				}
			}
		}
	}
	return $iIdEntity;
}


function bab_OCGetEntityEx($iIdSessUser, $iIdEntity)
{
	global $babBody;

	global $babDB;
	$sQuery = 
		'SELECT ' . 
			'* ' .
		'FROM ' . 
			BAB_OC_ENTITIES_TBL . ' ' .
		'WHERE ' . 
			'id = ' . $babDB->quote($iIdEntity);

	//bab_debug($sQuery);
	$aEntity = false;
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult && 1 === $babDB->db_num_rows($oResult));
	{
		$aEntity = $babDB->db_fetch_assoc($oResult);
	}

	if(false === $aEntity)
	{
		$babBody->addError(bab_translate("Error: Unknown entity"));
		return false;
	}
		
	$iIdOrgChart = (int) $aEntity['id_oc'];
	if(false === bab_OCIsAccessValid($iIdSessUser, $iIdOrgChart))
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	
	return $aEntity;
}


function bab_OCUpdateEntity($iIdSessUser, $iIdEntity, $sName, $sDescription)
{
	global $babBody, $babDB;
	
	$aEntity = bab_OCGetEntityEx($iIdSessUser, $iIdEntity);
	if(false !== $aEntity)
	{
		global $babBody;
		
		$iIdOrgChart = (int) $aEntity['id_oc'];
		if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
		{
			$babBody->addError(bab_translate("Acces denied"));
			return false;
		}
		
		if(0 === strlen(trim($sName)))
		{
			$babBody->addError(bab_translate("ERROR: You must provide a name")." !");
			return false;
		}
		
		$sQuery = 
			'UPDATE ' . 
				BAB_OC_ENTITIES_TBL . ' ' .
			'SET ' . 
				'name = ' . $babDB->quote($sName) . ', ' . 
				'description = ' . $babDB->quote($sDescription) . ' ' . 
			'WHERE ' .
				'id = ' . $babDB->quote($iIdEntity);
		
		//bab_debug($sQuery);	
		$babDB->db_query($sQuery);
		
		require_once dirname(__FILE__) . '/grpincl.php';
		
		$iIdManager	= 0;
		$iIdGroup	= (int) $aEntity['id_group'];
		
		bab_updateGroup($iIdGroup, $sName, $sDescription, $iIdManager);
		
		return true;
	}
	return false;
}


function bab_OCDeleteEntity($iIdSessUser, $iIdEntity, $iDeleteType)
{
	$aEntity = bab_OCGetEntityEx($iIdSessUser, $iIdEntity);
	if(false !== $aEntity)
	{
		global $babBody;
		
		$iIdOrgChart = (int) $aEntity['id_oc'];
		if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
		{
			$babBody->addError(bab_translate("Acces denied"));
			return false;
		}
		
		$aOrgChart				= bab_OCGet($iIdSessUser, $iIdOrgChart);
		$iIdNode				= (int)		$aEntity['id_node'];
		$iIdEntityGroup			= (int) 	$aEntity['id_group'];
		$sIsOrgChartPrimary		= (string)	$aOrgChart['isprimary'];
		$iIdOrgChartGroup		= (int) 	$aOrgChart['id_group'];
		$oBabTree = new bab_dbtree(BAB_OC_TREES_TBL, $iIdOrgChart);
		
		switch($iDeleteType)
		{
			case BAB_OC_DELETE_CHILDREN_ONLY:
				break;
				
			case BAB_OC_DELETE_ENTITY_AND_CHILDREN:
				break;
				
			case BAB_OC_DELETE_ENTITY_ONLY:
			default:
				if($oBabTree->remove($iIdNode))
				{
					removeOrgChartEntityEx($iIdSessUser, $iIdEntity, $iIdEntityGroup, 
						$sIsOrgChartPrimary, $iIdOrgChartGroup);
					return true;
				}
				break;
		}
		
	}
	return false;
}

function removeOrgChartEntityEx($iIdSessUser, $iIdEntity, $iIdEntityGroup, $sIsOrgChartPrimary, $iIdOrgChartGroup)
{
	bab_OCDeleteRoleByEntityId($iIdSessUser, $iIdEntity);
	
	if('Y' === (string) $sIsOrgChartPrimary && 1 === (int) $iIdOrgChartGroup)
	{
		global $babDB;
		
		$sQuery = 
			'UPDATE ' .
				BAB_GROUPS_TBL . ' ' .
			'SET ' .
				'id_ocentity = ' . $babDB->quote(0) . ' ' .
			'WHERE ' .
				'id = ' . $babDB->quote($iIdEntityGroup);
		
		//bab_debug($sQuery);
		$babDB->db_query($sQuery);
	}
	
	$sQuery = 'DELETE FROM ' . BAB_OC_ENTITIES_TBL . ' WHERE id = '	. $babDB->quote($iIdEntity);
	//bab_debug($sQuery); 
	$babDB->db_query($sQuery);
	
	$sQuery = 'DELETE FROM ' . BAB_VAC_PLANNING_TBL . ' WHERE id_entity = '	. $babDB->quote($iIdEntity);
	//bab_debug($sQuery);
	$babDB->db_query($sQuery); 
}


function bab_OCMoveEntity($iIdSessUser, $iIdSrcEntity, $iIdTrgEntity, $iMove, $iMoveType)
{
	global $babBody;
	
	$aGoodValue = array(
		BAB_OC_MOVE_ENTITY_AND_CHILDREN => BAB_OC_MOVE_ENTITY_AND_CHILDREN,
		BAB_OC_MOVE_ENTITY_ONLY => BAB_OC_MOVE_ENTITY_ONLY);
		
	if(!array_key_exists($iMove, $aGoodValue))
	{
		$babBody->addError(bab_translate("Error: Movement unknown"));
		return false;
	}
	
	$aGoodValue = array(
		BAB_OC_MOVE_TYPE_AS_PREVIOUS_SIBLING => BAB_OC_MOVE_TYPE_AS_PREVIOUS_SIBLING,
		BAB_OC_MOVE_TYPE_AS_NEXT_SIBLING => BAB_OC_MOVE_TYPE_AS_NEXT_SIBLING,
		BAB_OC_MOVE_TYPE_AS_CHILD => BAB_OC_MOVE_TYPE_AS_CHILD);
		
	if(!array_key_exists($iMoveType, $aGoodValue))
	{
		$babBody->addError(bab_translate("Error: Type of movement unknown"));
		return false;
	}

	$sFunctionName = '';
	if((int) BAB_OC_MOVE_ENTITY_AND_CHILDREN === (int) $iMove)
	{
		$sFunctionName = 'moveTree';
	}
	else if((int) BAB_OC_MOVE_ENTITY_ONLY === (int) $iMove)
	{
		$sFunctionName = 'move';
	}
	else
	{//Should never happen
		return false;
	}
	
	$aSrcEntity = bab_OCGetEntityEx($iIdSessUser, $iIdSrcEntity);
	if(false === $aSrcEntity)
	{
		$babBody->addError(bab_translate("Error: Cannot get source entity information"));
		return false;
	}
	
	$aTrgEntity = bab_OCGetEntityEx($iIdSessUser, $iIdTrgEntity);
	if(false === $aTrgEntity)
	{
		$babBody->addError(bab_translate("Error: Cannot get target entity information"));
		return false;
	}
	
	if((int) $aSrcEntity['id_oc'] !== (int) $aTrgEntity['id_oc'])
	{
		$babBody->addError(bab_translate("Error: Entity are not in the same organization chart"));
		return false;
	}
	
	$iIdOrgChart = (int) $aSrcEntity['id_oc'];
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	$iIdSrcNode = (int) $aSrcEntity['id_node'];
	$iIdParentNode = (int) $aTrgEntity['id_node'];
	
	$oBabTree = new bab_dbtree(BAB_OC_TREES_TBL, $iIdOrgChart);
	switch($iMoveType)
	{
		case BAB_OC_MOVE_TYPE_AS_PREVIOUS_SIBLING:
			//bab_debug('previous sibling ' . $sFunctionName . ' src => ' . $iIdSrcNode . ' trg => ' . $iIdParentNode);
			$oBabTree->$sFunctionName($iIdSrcNode, 0, $iIdParentNode, false);
			return true;
		case BAB_OC_MOVE_TYPE_AS_NEXT_SIBLING:
			//bab_debug('next sibling ' . $sFunctionName . ' src => ' . $iIdSrcNode . ' trg => ' . $iIdParentNode);
			$oBabTree->$sFunctionName($iIdSrcNode, 0, $iIdParentNode);
			return true;
		case BAB_OC_MOVE_TYPE_AS_CHILD:
			//bab_debug('as child ' . $sFunctionName . ' src => ' . $iIdSrcNode . ' trg => ' . $iIdParentNode);
			$oBabTree->$sFunctionName($iIdSrcNode, $iIdParentNode);
			return true;
	}
	return false;
}







//Role functions

function bab_OCCreateRole($iIdSessUser, $iIdEntity, $sName, $sDescription, $iType, $sCardinality)
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
	
	$aEntity = bab_OCGetEntityEx($iIdSessUser, $iIdEntity);
	if(false === $aEntity)
	{
		return false;
	}
	
	$iIdOrgChart = (int) $aEntity['id_oc'];
	if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
	{
		$babBody->addError(bab_translate("Acces denied"));
		return false;
	}
	
	global $babDB;

	$sQuery = 
		'INSERT INTO ' . BAB_OC_ROLES_TBL . ' ' .
			'(' .
				'`id`, ' .
				'`name`, `description`, `id_oc`, `id_entity`, ' .
				'`type`, `cardinality` ' .
			') ' .
		'VALUES ' . 
			'(\'\', ' . 
				$babDB->quote($sName) . ', ' . 
				$babDB->quote($sDescription) . ', ' . 
				$babDB->quote($iIdOrgChart) . ', ' . 
				$babDB->quote($iIdEntity) . ', ' . 
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


function bab_OCGetRoleById($iIdSessUser, $iIdRole)
{
	global $babBody;
	
	$aRole = array();
	
	global $babDB;
	$sQuery = 
		'SELECT ' . 
			'* ' .
		'FROM ' . 
			BAB_OC_ROLES_TBL . ' ' .
		'WHERE ' . 
			'id = ' . $babDB->quote($iIdRole);
			
	//bab_debug($sQuery);
	$aRole = false;
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		if(1 === $iNumRows)
		{
			$aRole = $babDB->db_fetch_assoc($oResult);
		}
	}

	if(false === $aRole)
	{
		return false;
	}
		
	$iIdOrgChart = (int) $aRole['id_oc'];
	if(false === bab_OCIsAccessValid($iIdSessUser, $iIdOrgChart))
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	return $aRole;
}


function bab_OCGetRoleByType($iIdSessUser, $iIdEntity, $iType)
{
	global $babBody, $babDB;
	
	$aRole = array();
	
	static $aGoodType = array(
		BAB_OC_ROLE_CUSTOM => BAB_OC_ROLE_CUSTOM, 
		BAB_OC_ROLE_SUPERIOR => BAB_OC_ROLE_SUPERIOR,
		BAB_OC_ROLE_TEMPORARY_EMPLOYEE => BAB_OC_ROLE_TEMPORARY_EMPLOYEE, 
		BAB_OC_ROLE_MEMBER => BAB_OC_ROLE_MEMBER);
		
	$aWhereClauseItem = array();
		
	if(!array_key_exists($iType, $aGoodType))
	{			
		$babBody->addError(bab_translate("Error: Wrong role type"));
		return false;
	}
	
	$aWhereClauseItem[] = 'type = ' . $babDB->quote($iType);
	$aWhereClauseItem[] = 'id_entity = ' . $babDB->quote($iIdEntity);
	
	$sQuery = 
		'SELECT ' . 
			'* ' .
		'FROM ' . 
			BAB_OC_ROLES_TBL . ' ' .
		'WHERE ' . 
			implode(' AND ', $aWhereClauseItem);
			
	//bab_debug($sQuery);
	$iIdOrgChart = 0;
	$iNumRows = 0;
	$aRole	= array();
	$aDatas	= array();
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$iIdOrgChart = (int) $aDatas['id_oc'];
			$aRole[] = $aDatas;
		}
	}
		
	if($iNumRows > 0 && false === bab_OCIsAccessValid($iIdSessUser, $iIdOrgChart))
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	return $aRole;
}


function bab_OCGetRoleByName($iIdSessUser, $iIdEntity, $sName, $iType)
{
	global $babBody, $babDB;
	
	$aRole = array();
	
	static $aGoodType = array(
		BAB_OC_ROLE_CUSTOM => BAB_OC_ROLE_CUSTOM, 
		BAB_OC_ROLE_SUPERIOR => BAB_OC_ROLE_SUPERIOR,
		BAB_OC_ROLE_TEMPORARY_EMPLOYEE => BAB_OC_ROLE_TEMPORARY_EMPLOYEE, 
		BAB_OC_ROLE_MEMBER => BAB_OC_ROLE_MEMBER);
		
	$aWhereClauseItem = array();
		
	if(!array_key_exists($iType, $aGoodType))
	{			
		$babBody->addError(bab_translate("Error: Wrong role type"));
		return false;
	}
	
	if(0 === strlen(trim($sName)))
	{
		$babBody->addError(bab_translate("Error: The role name is not valide"));
		return false;
	}
	
	$aWhereClauseItem[] = 'type = ' . $babDB->quote($iType);
	$aWhereClauseItem[] = 'id_entity = ' . $babDB->quote($iIdEntity);
	$aWhereClauseItem[] = 'name LIKE ' . $babDB->quote($babDB->db_escape_like($sName));
	
	$sQuery = 
		'SELECT ' . 
			'* ' .
		'FROM ' . 
			BAB_OC_ROLES_TBL . ' ' .
		'WHERE ' . 
			implode(' AND ', $aWhereClauseItem);
			
	//bab_debug($sQuery);
	$iIdOrgChart = 0;
	$iNumRows = 0;
	$aRole	= array();
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		if(false !== ($aRole = $babDB->db_fetch_assoc($oResult)))
		{
			$iIdOrgChart = (int) $aRole['id_oc'];
		}
	}
		
	if($iNumRows > 0 && false === bab_OCIsAccessValid($iIdSessUser, $iIdOrgChart))
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	return $aRole;
}


function bab_OCGetRoleByEntityId($iIdSessUser, $iIdEntity, $iType = null)
{
	global $babBody, $babDB;
	
	$aRole = array();
	
	static $aGoodType = array(
		BAB_OC_ROLE_CUSTOM => BAB_OC_ROLE_CUSTOM, 
		BAB_OC_ROLE_SUPERIOR => BAB_OC_ROLE_SUPERIOR,
		BAB_OC_ROLE_TEMPORARY_EMPLOYEE => BAB_OC_ROLE_TEMPORARY_EMPLOYEE, 
		BAB_OC_ROLE_MEMBER => BAB_OC_ROLE_MEMBER);
		
	$aWhereClauseItem = array();
		
	if(!is_null($iType))
	{
		if(!array_key_exists($iType, $aGoodType))
		{			
			$babBody->addError(bab_translate("Error: Wrong role type"));
			return false;
		}
		
		$aWhereClauseItem[] = 'type = ' . $babDB->quote($iType);
	}
	
	$aWhereClauseItem[] = 'id_entity = ' . $babDB->quote($iIdEntity);
	
	$sQuery = 
		'SELECT ' . 
			'* ' .
		'FROM ' . 
			BAB_OC_ROLES_TBL . ' ' .
		'WHERE ' . 
			implode('AND ', $aWhereClauseItem);
			
	//bab_debug($sQuery);
	$iIdOrgChart = 0;
	$aDatas	= false;
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumRows = $babDB->db_num_rows($oResult);
		while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$iIdOrgChart = (int) $aDatas['id_oc'];
			$aRole[$aDatas['id']] = $aDatas;
		}
	}
		
	if(false === bab_OCIsAccessValid($iIdSessUser, $iIdOrgChart))
	{
		$babBody->addError(bab_translate("Error: Right insufficient"));
		return false;
	}
	return $aRole;
}


function bab_OCDeleteRoleById($iIdSessUser, $iIdRole)
{
	global $babBody, $babDB;
	
	$aRole = bab_OCGetRoleById($iIdSessUser, $iIdRole);
	if(false !== $aRole)
	{
		$iIdOrgChart = (int) $aRole['id_oc'];
		if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
		{
			$babBody->addError(bab_translate("Acces denied"));
			return false;
		}
		
		if(bab_OCDeleteRoleUserByRoleId($iIdUserSess, $iIdRole))
		{
			static $aGoodType = array(
				BAB_OC_ROLE_CUSTOM => BAB_OC_ROLE_CUSTOM, 
				BAB_OC_ROLE_SUPERIOR => BAB_OC_ROLE_SUPERIOR,
				BAB_OC_ROLE_TEMPORARY_EMPLOYEE => BAB_OC_ROLE_TEMPORARY_EMPLOYEE, 
				BAB_OC_ROLE_MEMBER => BAB_OC_ROLE_MEMBER);
				
			$aWhereClauseItem = array();
				
			if(!is_null($iType))
			{
				if(!array_key_exists($iType, $aGoodType))
				{			
					$babBody->addError(bab_translate("Error: Wrong role type"));
					return false;
				}
				
				$aWhereClauseItem[] = 'type = ' . $babDB->quote($iType);
			}
			
			$aWhereClauseItem[] = 'id = ' . $babDB->quote($iIdRole);
			
			$sQuery = 
				'DELETE FROM ' . 
					BAB_OC_ROLES_TBL . ' ' .
				'WHERE ' .
					implode('AND ', $aWhereClauseItem);
			
			//bab_debug($sQuery);
			return $babDB->db_query($sQuery);
		}
	}
	return false;
}


function bab_OCDeleteRoleByEntityId($iIdSessUser, $iIdEntity, $iType = null)
{
	$aEntity = bab_OCGetEntityEx($iIdSessUser, $iIdEntity);
	if(false !== $aEntity)
	{
		global $babBody, $babDB;
		
		$iIdOrgChart = (int) $aEntity['id_oc'];
		if(false === bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $iIdOrgChart, $iIdSessUser))
		{
			$babBody->addError(bab_translate("Acces denied"));
			return false;
		}
		
		$aRole = bab_OCGetRoleByEntityId($iIdSessUser, $iIdEntity, $iType);
		if(false === $aRole)
		{
			$babBody->addError(bab_translate("Error : cannot get role entity list"));
			return false;
		}
		
		foreach($aRole as $iRoleId => $aRoleItem)
		{
			bab_OCDeleteRoleUserByRoleId($iIdSessUser, $iRoleId);
		}
		
		$aWhereClauseItem = array();
			
		if(!is_null($iType))
		{
			$aWhereClauseItem[] = 'type = ' . $babDB->quote($iType);
		}
		
		$aWhereClauseItem[] = 'id_entity = ' . $babDB->quote($iIdEntity);
		
		global $babDB;
		
		$sQuery = 
			'DELETE FROM ' . 
				BAB_OC_ROLES_TBL . ' ' .
			'WHERE ' .
				implode('AND ', $aWhereClauseItem);
		
		//bab_debug($sQuery);
		return $babDB->db_query($sQuery);
	}
	return false;
}







//Role user functions

function bab_OCCreateRoleUser($iIdSessUser, $iIdRole, $iIdUser)
{
	global $babDB, $babBody;
	
	$aRole = bab_OCGetRoleById($iIdSessUser, $iIdRole);
	if(false !== $aRole)
	{
		$iIdEntity		= (int) $aRole['id_entity'];
		$iIdOrgChart	= (int) $aRole['id_oc'];
		
		if(bab_OCIsLockedBy($iIdSessUser, $iIdOrgChart, $iIdSessUser))
		{
			$sQuery = 
				'SELECT ' .
					'* ' .
				'FROM  ' . 
					BAB_OC_ROLES_USERS_TBL . ' ' . 
				'WHERE ' .
					'id_role = ' . $babDB->quote($iIdRole) . ' AND ' .
					'id_user = ' . $babDB->quote($iIdUser);
			//bab_debug($sQuery);
			$oResult = $babDB->db_query($sQuery);
			if(false === $oResult)
			{
				return false;
			}
			
			$iNumRows = $babDB->db_num_rows($oResult);
			if($iNumRows > 0 )
			{
				$babBody->addError(bab_translate("User already exist!"));
				return;
			}
			
			$sPrimary = 'Y';
			
			$sQuery = 
				'SELECT ' .
					'ocrut.id ' .
				'FROM ' . 
					BAB_OC_ROLES_USERS_TBL . ' ocrut ' . 
				'LEFT JOIN ' . 
					BAB_OC_ROLES_TBL . ' ocrt on ocrut.id_role = ocrt.id ' . 
				'WHERE ' .
					'ocrt.id_oc = ' . $babDB->quote($iIdOrgChart) . ' AND ' .
					'ocrut.id_user = ' . $babDB->quote($iIdUser) . ' AND ' .
					'ocrut.isprimary = \'Y\'';
					
			//bab_debug($sQuery);
			$oResult = $babDB->db_query($sQuery);
			if(false === $oResult)
			{
				return false;
			}
			
			$iNumRows = $babDB->db_num_rows($oResult);
			if($iNumRows > 0 )
			{
				$sPrimary = 'N';
			}
	
			$aOrgChart	= bab_OCGet($iIdSessUser, $iIdOrgChart);
			$aEntity	= bab_OCGetEntityEx($iIdSessUser, $iIdEntity);
			
			if(false === $aOrgChart || false === $aEntity)
			{
				return false;
			}
			
			if('Y' === (string) $aOrgChart['isprimary'] && 1 === (int) $aOrgChart['id_group'] && 0 !== (int) $aEntity['id_group'])
			{
				$sQuery = 
					'SELECT ' . 
						'id_user ' . 
					'FROM ' . 
						BAB_DBDIR_ENTRIES_TBL . ' ' . 
					'WHERE ' .
						'id = '.$babDB->quote($iIdUser);
					
				//bab_debug($sQuery);
				$oResult = $babDB->db_query($sQuery);
				if(false === $oResult)
				{
					return false;
				}
				
				$iNumRows = $babDB->db_num_rows($oResult);
				if($iNumRows > 0 )
				{
					if(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
					{
						require_once $GLOBALS['babInstallPath'].'utilit/userincl.php';
						bab_addUserToGroup($aDatas['id_user'], $aEntity['id_group'], false);
					}
				}
			}
			
			$sQuery = 
				'INSERT INTO ' . BAB_OC_ROLES_USERS_TBL . ' ' .
					'(' .
						'`id`, ' .
						'`id_role`, `id_user`, `isprimary` ' .
					') ' .
				'VALUES ' . 
					'(\'\', ' . 
						$babDB->quote($iIdRole) . ', ' . 
						$babDB->quote($iIdUser) . ', ' . 
						$babDB->quote($sPrimary) . 
					')'; 
		
			//bab_debug($sQuery);
			$oResult = $babDB->db_query($sQuery);
			if(false !== $oResult)
			{
				return $babDB->db_insert_id();
			}
		}
	}
	return false;
}


function bab_OCGetRoleUserByUserId($iIdSessUser, $iIdEntity, $iIdUser)
{
	//This function test right
	if(false !== bab_OCGetEntityEx($iIdSessUser, $iIdEntity))
	{
		global $babDB;
		
		$sQuery = 
			'SELECT ' .
				'ru.* ' .
			'FROM ' . 
				BAB_OC_ROLES_USERS_TBL . ' ru ' .
			'LEFT JOIN ' .
				BAB_OC_ROLES_TBL . ' r on r.id = ru.id_role ' . 
			'WHERE ' .
				'r.id_entity = ' . $babDB->quote($iIdEntity) . ' AND ' .
				'ru.id_user = ' . $babDB->quote($iIdUser);
	
		$aRoleUser = false;
		$aDatas = false;
		//bab_debug($sQuery);
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult)
		{
			$iNumRows = $babDB->db_num_rows($oResult);	
			if(0 < $iNumRows)
			{
				while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
				{
					$aRoleUser[$aDatas['id']] = $aDatas;
				}
				return $aRoleUser;
			}
		}
	}
	return false;
}


function bab_OCDeleteUserRolesByRoleUserIds($iIdSessUser, $aIdRoleUser, $iIdUser)
{
	foreach($aIdRoleUser as $IdRoleUser)
	{
		bab_OCDeleteRoleUserByRoleUserId($iIdSessUser, $IdRoleUser, $iIdUser);
	}
}


function bab_OCDeleteRoleUserByRoleUserId($iIdSessUser, $IdRoleUser)
{
	global $babDB;
	
	$iIdRole = 0;
	$aUserInfo = array();
	
	$sQuery =
		'SELECT ' .
			'id_role, ' .
			'id_user, ' .
			'isprimary ' .
		'FROM ' . 
			BAB_OC_ROLES_USERS_TBL . ' ' .
		'WHERE ' .
			'id = ' . $babDB->quote($IdRoleUser);
			
	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false === $oResult)
	{
		return false;		
	}
	
	$iNumRows = $babDB->db_num_rows($oResult);	
	if(0 < $iNumRows)
	{
		$iIndex = 0;
		if(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$iIdRole = (int) $aDatas['id_role'];
			$aUserInfo[] = array('iIdUser' => $aDatas['id_user'], 'sIsPrimary' => $aDatas['isprimary']);
		}
	}

	if(0 == count($aUserInfo))
	{
		return false;
	}

	$aRole = bab_OCGetRoleById($iIdSessUser, $iIdRole);
	if(false !== $aRole)
	{
		$iIdOrgChart = (int) $aRole['id_oc'];
		if(bab_OCIsLockedBy($iIdSessUser, $iIdOrgChart, $iIdSessUser))
		{
			$iIdEntity	= (int) $aRole['id_entity'];
			foreach($aUserInfo as $iKey => $aItem)
			{
				$iIdUser = (int) $aItem['iIdUser'];
				$sIsPrimary = (string) $aItem['sIsPrimary'];
					
				$sQuery = 
					'DELETE FROM ' . 
						BAB_OC_ROLES_USERS_TBL . ' ' .
					'WHERE ' .
						'id_role = ' . $babDB->quote($iIdRole) . ' AND ' .
						'id_user = ' . $babDB->quote($iIdUser);
				//bab_debug($sQuery);
				$bRet = $babDB->db_query($sQuery);
				if(false !== $bRet)
				{
					bab_OCCommonDeleteRoleUserAction($iIdSessUser, $iIdOrgChart, $iIdEntity, $iIdUser, $sIsPrimary);
				}
			}
			return true;
		}
	}
	return false;
}


function bab_OCDeleteRoleUserByRoleId($iIdSessUser, $iIdRole)
{
	global $babDB;
	
	$aUserInfo = array();
	
	$sQuery =
		'SELECT ' .
			'id_user, ' .
			'isprimary ' .
		'FROM ' . 
			BAB_OC_ROLES_USERS_TBL . ' ' .
		'WHERE ' .
			'id_role = ' . $babDB->quote($iIdRole);
			
	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false === $oResult)
	{
		return false;		
	}
	
	$iNumRows = $babDB->db_num_rows($oResult);	
	if(0 < $iNumRows)
	{
		$iIndex = 0;
		while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$aUserInfo[] = array('iIdUser' => $aDatas['id_user'], 'sIsPrimary' => $aDatas['isprimary']);
		}
	}

	if(0 == count($aUserInfo))
	{
		return false;
	}
	
	$aRole = bab_OCGetRoleById($iIdSessUser, $iIdRole);
	if(false !== $aRole)
	{
		$iIdOrgChart = (int) $aRole['id_oc'];
		if(bab_OCIsLockedBy($iIdSessUser, $iIdOrgChart, $iIdSessUser))
		{
			$iIdEntity	= (int) $aRole['id_entity'];
			
			$sQuery = 
				'DELETE FROM ' . 
					BAB_OC_ROLES_USERS_TBL . ' ' .
				'WHERE ' .
					'id_role IN(' . $babDB->quote($iIdRole) . ')';
			//bab_debug($sQuery);
			$bRet = $babDB->db_query($sQuery);
			if(false !== $bRet)
			{
				foreach($aUserInfo as $iKey => $aItem)
				{
					$iIdUser = (int) $aItem['iIdUser'];
					$sIsPrimary = (string) $aItem['sIsPrimary'];
					
					bab_OCCommonDeleteRoleUserAction($iIdSessUser, $iIdOrgChart, $iIdEntity, $iIdUser, $sIsPrimary);
				}
				return true;
			}
		}
	}
	return false;
}


//This function must not be called directly			
function bab_OCCommonDeleteRoleUserAction($iIdSessUser, $iIdOrgChart, $iIdEntity, $iIdUser, $sIsPrimary)
{
	global $babDB;
	
	$sQuery = 
		'DELETE FROM ' . 
			BAB_VAC_PLANNING_TBL . ' ' .
		'WHERE ' .
			'id_user = '	. $babDB->quote($iIdUser);
	//bab_debug($sQuery);
	$babDB->db_query($sQuery);

	if('Y' === $sIsPrimary)
	{		
		$sQuery = 
			'SELECT ' .
				'ocrut.id ' .
			'FROM ' .
				BAB_OC_ROLES_USERS_TBL . ' ocrut ' .
			'LEFT JOIN ' .
				BAB_OC_ROLES_TBL . ' ocrt on ocrt.id = ocrut.id_role ' .
			'WHERE ' .
				'ocrt.id_oc = ' . $babDB->quote($iIdOrgChart) . 'AND ' .
				'ocrut.id_user = ' . $babDB->quote($iIdUser);
		//bab_debug($sQuery);
		$oResult = $babDB->db_query($sQuery);
		if(false !== $oResult)
		{
			$iNumRows = $babDB->db_num_rows($oResult);	
			if(0 < $iNumRows)
			{
				$iIndex = 0;
				while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
				{
					//user must have a primary role, use the first
					$sQuery = 
						'UPDATE ' . 
							BAB_OC_ROLES_USERS_TBL . ' ' .
						'SET ' . 
							'isprimary = ' . $babDB->quote($sIsPrimary) . ' ' .
						'WHERE id = ' . $babDB->quote($aDatas['id']);
					//bab_debug($sQuery);
					$babDB->db_query($sQuery);
					$sIsPrimary = 'N';	
				}
			}
		}
	
		$aOrgChart = bab_OCGet($iIdSessUser, $iIdOrgChart);
		$aEntity = bab_OCGetEntityEx($iIdSessUser, $iIdEntity);
		if(false !== $aOrgChart && false !== $aEntity)
		{
			if('Y' === (string) $aOrgChart['isprimary'] && 1 === (int) $aOrgChart['id_group'] && 0 !== (int) $aEntity['id_group'])
			{
				$sQuery = 
					'SELECT ' . 
						'COUNT(orut.id) as iTotal ' .
					'FROM ' . 
						BAB_OC_ROLES_USERS_TBL . ' orut ' . 
					'LEFT JOIN ' . 
						BAB_OC_ROLES_TBL . ' ort on ort.id = orut.id_role ' .
					'LEFT JOIN ' . 
						BAB_OC_ENTITIES_TBL . ' oct on oct.id = ort.id_entity ' . 
					'WHERE ' .
						'orut.id_user = ' . $babDB->quote($sIsPrimary) . ' AND ' .
						'ort.id_entity = ' . $babDB->quote($iIdEntity);
						
				//bab_debug($sQuery);
				$oResult = $babDB->db_query($sQuery);
				if(false !== $oResult)
				{
					$iNumRows = $babDB->db_num_rows($oResult);	
					if(0 < $iNumRows)
					{
						if(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
						{
							if(0 === (int) $aDatas['iTotal'])
							{
								$sQuery = 
									'SELECT ' . 
										'id_user ' . 
									'FROM ' . 
										BAB_DBDIR_ENTRIES_TBL . ' ' . 
									'WHERE ' . 
										'id = ' . $babDB->quote($iIdUser);
								//bab_debug($sQuery);
								$oResult = $babDB->db_query($sQuery);
								if(false !== $oResult)
								{
									$iNumRows = $babDB->db_num_rows($oResult);	
									if(0 < $iNumRows)
									{
										if(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
										{
											$iIdUser = (int) $aDatas['id_user'];
											$sQuery = 
												'DELETE from ' . 
													BAB_USERS_GROUPS_TBL . ' ' .
												'WHERE ' .
													'id_group = ' . $babDB->quote($aEntity['id_group']) . ' AND ' . 
													'id_object = ' . $babDB->quote($iIdUser);
											//bab_debug($sQuery);
											$babDB->db_query($sQuery);
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
}				



//Tools functions

function bab_OCTreeCreateNode($iIdOrgChart, $iIdParentNode, $iPosition)
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
		global $babBody;
		$babBody->addError(bab_translate("ERROR: The node was not created"));
		return false;
	}
	return $iIdNode;
}


function bab_OCIsEntityChildOfRoot($iIdEntity)
{
	global $babDB;
	
	$sQuery = 
		'SELECT ' .
			'octParent.id iIdRootNode ' .
		'FROM ' .
			BAB_OC_ENTITIES_TBL . ' octChildEntity ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octChild ON octChild.id = octChildEntity.id_node ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octParent ON octParent.id = octChild.id_parent ' .
		'WHERE ' .
			'octChildEntity.id IN(' . $babDB->quote($iIdEntity) . ') AND ' .
			'octParent.id_parent IN (' . $babDB->quote(0) . ')'; 
			
	//bab_debug($sQuery, 1, 'PivotTable');
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		return (1 === $babDB->db_num_rows($oResult));
	}
	return false;	
}


function bab_OCIsEntityParentOf($iIdParentEntity, $iIdEntity)
{
	global $babDB;
	
	$sQuery = 
		'SELECT ' .
			'octParentEntity.id iIdParentEntity ' .
		'FROM ' .
			BAB_OC_ENTITIES_TBL . ' octChildEntity ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octChild ON octChild.id = octChildEntity.id_node ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octParent ON octParent.id = octChild.id_parent ' .
		'LEFT JOIN ' . 
			BAB_OC_ENTITIES_TBL . ' octParentEntity ON octParentEntity.id_node = octParent.id ' .
		'WHERE ' .
			'octChildEntity.id IN(' . $babDB->quote($iIdEntity) . ') AND ' .
			'octParentEntity.id IN (' . $babDB->quote($iIdParentEntity) . ')'; 
//			'octChildEntity.id IN(' . $babDB->quote($iIdEntity) . ')'; 
			
	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		return (1 === $babDB->db_num_rows($oResult));
	}
	return false;	
}

function bab_OCGetNodeRank($iIdNode)
{
	global $babDB;
	
	$sQuery = 
		'SELECT ' .
			'octCh.id iId, ' .
			'octCh.id_parent iIdParent, ' .
			'octCh.lf iLf, ' .
			'octCh.lr iLr, ' .
			'octCh.id_user iIdTree ' .
		'FROM ' .
			BAB_OC_TREES_TBL . ' octNode, ' .
			BAB_OC_TREES_TBL . ' octPr, ' .
			BAB_OC_TREES_TBL . ' octCh ' .
		'WHERE ' .
			'octNode.id = ' . $babDB->quote($iIdNode) . ' AND ' .
			'octPr.id = octNode.id_parent AND ' .
			'octCh.lf > octPr.lf AND octCh.lr < octPr.lr AND ' .
			'octCh.lf <= octNode.lf AND octCh.lr <= octNode.lr';
			
	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		return $babDB->db_num_rows($oResult);
	}
	return 0;	
}


function bab_OCGetChildNodeByPosition($iIdParentEntity, $iPosition)
{
	global $babDB;
	
	$sSetRowQuery = 'SET @iNumRow = 0';
	$sQuery = 
		'SELECT ' .
			'@iNumRow := IF(octPr.id = octCh.id_parent, @iNumRow + 1, @iNumRow + 0) AS iNumRow, ' .
			'octCh.id iId, ' .
			'octCh.id_parent iIdParent, ' .
			'octCh.lf iLf, ' .
			'octCh.lr iLr, ' .
			'octCh.id_user iIdTree, ' .
			'octEntity.name sName, ' .
			'octEntity.description sDescription, ' .
			'octEntity.id iIdEntity ' .
		'FROM ' .
			BAB_OC_ENTITIES_TBL . ' octPrEntity ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octPr ON octPr.id = octPrEntity.id_node ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octCh ON octCh.id_parent = octPr.id ' .
		'LEFT JOIN ' . 
			BAB_OC_ENTITIES_TBL . ' octEntity ON octEntity.id_node = octCh.id ' .
		'WHERE ' .
			'octPrEntity.id IN(' . $babDB->quote($iIdParentEntity) . ') AND ' .
			'octCh.lf > octPr.lf AND octCh.lr < octPr.lr ' . 
		'HAVING iNumRow = ' . $babDB->quote($iPosition) . ' ' .
		'ORDER ' .
			'BY octCh.lf asc';
			
	//bab_debug($sSetRowQuery);
	//bab_debug($sSetRowQuery . ';' . $sQuery);
	$babDB->db_query($sSetRowQuery);
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

function bab_OCGetChildCount($iIdEntity)
{
	global $babDB;
	
	$sQuery = 
		'SELECT ' .
			'COUNT(DISTINCT octCh.id) iChildCount ' .
		'FROM ' .
			BAB_OC_ENTITIES_TBL . ' octEntity ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octPr ON octPr.id = octEntity.id_node ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octCh ON octCh.id_parent = octPr.id ' .
		'WHERE ' .
			'octEntity.id IN (' . $babDB->quote($iIdEntity) . ')'; 
			
	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		if(1 === $babDB->db_num_rows($oResult))
		{
			if(false !== ($aData = $babDB->db_fetch_assoc($oResult)))
			{
				return $aData['iChildCount'];
			}
		}
	}
	return 0;	
}


function bab_OCGetLastChild($iIdEntity)
{
	global $babDB;
	
	$sQuery = 
		'SELECT ' .
			'octChEntity.id iIdEntity ' .
		'FROM ' .
			BAB_OC_ENTITIES_TBL . ' octEntity ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octPr ON octPr.id = octEntity.id_node ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octCh ON octCh.id_parent = octPr.id ' .
		'LEFT JOIN ' . 
			BAB_OC_ENTITIES_TBL . ' octChEntity ON octChEntity.id_node = octCh.id ' .
		'WHERE ' .
			'octEntity.id IN (' . $babDB->quote($iIdEntity) . ') ' .
		'ORDER BY ' .
			'octCh.lr DESC ' .
		'LIMIT 1'; 
			
	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		if(1 === $babDB->db_num_rows($oResult))
		{
			if(false !== ($aData = $babDB->db_fetch_assoc($oResult)))
			{
				return $aData['iIdEntity'];
			}
		}
	}
	return false;	
}


function bab_OCSelectTreeQuery($iIdTree)
{
	global $babDB;
	
	$sQuery = 
		'SELECT ' .
			'octCh.id iId, ' .
			'octCh.id_parent iIdParent, ' .
			'octCh.lf iLf, ' .
			'octCh.lr iLr, ' .
			'octCh.id_user iIdTree, ' .
			'octEntity.name sName, ' .
			'octEntity.description sDescription, ' .
			'octEntity.id iIdEntity, ' .
			'COUNT(DISTINCT octChildcount.id) childcount ' .
		'FROM ' .
			BAB_OC_TREES_TBL . ' octPr, ' .
			BAB_OC_TREES_TBL . ' octCh ' .
		'LEFT JOIN ' . 
			BAB_OC_TREES_TBL . ' octChildcount ON octChildcount.id_parent = octCh.id ' .
		'LEFT JOIN ' . 
			BAB_OC_ENTITIES_TBL . ' octEntity ON octEntity.id_node = octCh.id ' .
		'WHERE ' .
			'octPr.id_user IN(' . $babDB->quote($iIdTree) . ') AND ' .
			'(	(octCh.lf > octPr.lf AND octCh.lr < octPr.lr) OR ' .
			'	octCh.id_user IN (' . $babDB->quote($iIdTree) . ') ) ' . 
		'GROUP ' .
			'BY octCh.lf ' .
		'ORDER ' .
			'BY octCh.lf asc';
			
	//bab_debug($sQuery);
	return $sQuery;
}

