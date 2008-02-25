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
include_once "base.php";
include_once $GLOBALS['babInstallPath']."utilit/delegincl.php";


class bab_CategoryCache
{
	var $aCache = array();
	
	function bab_CategoryCache()
	{
		
	}
	
	function getCategory($iIdCategory)
	{
		if(!array_key_exists($iIdCategory, $this->aCache))
		{
			$aDatas = $this->getCategoryInfo($iIdCategory);
			if(false !== $aDatas)
			{
				$aCache[$iIdCategory] = $aDatas;
			}
			else
			{
				$aCache[$iIdCategory] = 0;
			}
		}
		return $aCache[$iIdCategory];
	}
	
	function getCategoryInfo($iIdCategory)
	{
		global $babDB;
		$oResult = $babDB->db_query('select * from ' . BAB_DG_CATEGORIES_TBL . ' WHERE id IN( ' . $babDB->quote($iIdCategory) . ')');
		if(false !== $oResult && 0 < $babDB->db_num_rows($oResult))
		{
			$aDatas = $babDB->db_fetch_assoc($this->oResCategories);
			if(false !== $aDatas)
			{
				return $aDatas;
			}
		}
		return false;
	}
	
}


class bab_Delegations extends bab_handler
{
	var $index;
	var $count;
	var $res;

	var $oCategoryCache = null;
	
	function bab_Delegations( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		
		$this->oCategoryCache = new bab_CategoryCache();
		
		$delegationid = $ctx->get_value('delegationid');
		$userid = $ctx->get_value('userid');
		$filter = $ctx->get_value('filter');
		if( strtoupper($filter) == "NO" )
			{
			$filter = false;
			}
		else
			{
			$filter = true;
			}

		if( $userid === false || $userid === '' )
			{
			$userid = $GLOBALS['BAB_SESS_USERID'];
			}

		if( $filter == false || $userid != '')
			{

			if( $delegationid === false || $delegationid === '' )
				{
				if( $filter == false )
					{
					$this->res = $babDB->db_query("SELECT dgt.* FROM ".BAB_DG_GROUPS_TBL." dgt order by dgt.name asc");
					}
				else
					{
					$this->res = $babDB->db_query("SELECT dgt.* FROM ".BAB_DG_GROUPS_TBL." dgt LEFT JOIN ".BAB_USERS_GROUPS_TBL." ugt ON ugt.id_group = dgt.id_group WHERE ugt.id_object='".$babDB->db_escape_string($userid)."' order by dgt.name asc");
					}
				}
			else
				{
				if( $filter == false )
					{
					$delegationid = explode(',', $delegationid);
					$this->res = $babDB->db_query("SELECT dgt.* FROM ".BAB_DG_GROUPS_TBL." dgt WHERE dgt.id IN (".$babDB->quote($delegationid).") order by dgt.name asc");
					}
				else
					{
					$delegationid = explode(',', $delegationid);
					$this->res = $babDB->db_query("SELECT dgt.* FROM ".BAB_DG_GROUPS_TBL." dgt LEFT JOIN ".BAB_USERS_GROUPS_TBL." ugt ON ugt.id_group = dgt.id_group WHERE ugt.id_object='".$babDB->db_escape_string($userid)."' AND dgt.id IN (".$babDB->quote( $delegationid).") order by dgt.name asc");
					}
				}
			$this->count = $babDB->db_num_rows($this->res);
			}
		else
			{

			$this->count = 0;
			}

		$this->ctx->curctx->push('CCount', $this->count);
	}
	
	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DelegationName', $arr['name']);
			$this->ctx->curctx->push('DelegationDescription', $arr['description']);
			$this->ctx->curctx->push('DelegationColor', $arr['color']);
			$this->ctx->curctx->push('DelegationId', $arr['id']);
			$this->ctx->curctx->push('DelegationGroupId', $arr['id_group']);
			$this->ctx->curctx->push('DelegationGroupName', bab_getGroupName($arr['id_group']));
			$this->ctx->curctx->push('DelegationCategoryId', $arr['iIdCategory']);
			$this->ctx->curctx->push('DelegationCategoryName', '');
			$this->ctx->curctx->push('DelegationCategoryDescription', '');
			$this->ctx->curctx->push('DelegationCategoryColor', '');
			
			if(0 !== (int) $arr['iIdCategory'])
			{
				$aDatas = $this->oCategoryCache->getCategory($arr['iIdCategory']);
				if(false !== $aDatas)
				{
					$this->ctx->curctx->push('DelegationCategoryName', $aDatas['name']);
					$this->ctx->curctx->push('DelegationCategoryDescription', $aDatas['description']);
					$this->ctx->curctx->push('DelegationCategoryColor', $aDatas['bgcolor']);
				}
			}
			
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_Delegation extends bab_Delegations
{

	function bab_Delegation( &$ctx)
	{
		$delegationid = $ctx->get_value('delegationid');
		if( $delegationid !== false && !empty($delegationid) )
			{
			$this->bab_Delegations($ctx);
			}
		else
			{
			$this->bab_handler($ctx);
			$this->count = 0;
			$this->ctx->curctx->push('CCount', $this->count);
			}
	}

}

class bab_DelegationsManaged extends bab_handler
{
	var $index;
	var $count;
	var $res;

	var $oCategoryCache = null;
	
	function bab_DelegationsManaged( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		
		$this->oCategoryCache = new bab_CategoryCache();
		
		$delegationid = $ctx->get_value('delegationid');
		$userid = $ctx->get_value('userid');

		if( $userid === false || $userid === '' )
			{
			$userid = $GLOBALS['BAB_SESS_USERID'];
			}

		if( $userid != '')
			{
			if( $delegationid === false || $delegationid === '' )
				{
				$this->res = $babDB->db_query("select dgt.* from ".BAB_DG_ADMIN_TBL." dat left join ".BAB_DG_GROUPS_TBL." dgt on dat.id_dg=dgt.id where dat.id_user='".$babDB->db_escape_string($userid)."' order by dgt.name asc");
				}
			else
				{
				$delegationid = explode(',', $delegationid);
				$this->res = $babDB->db_query("select dgt.* from ".BAB_DG_ADMIN_TBL." dat left join ".BAB_DG_GROUPS_TBL." dgt on dat.id_dg=dgt.id where dat.id_user='".$babDB->db_escape_string($userid)."' where id IN (".$babDB->quote($delegationid).") order by dgt.name asc");
				}
			$this->count = $babDB->db_num_rows($this->res);
			}
		else
			{
			$this->count = 0;
			}

		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DelegationName', $arr['name']);
			$this->ctx->curctx->push('DelegationDescription', $arr['description']);
			$this->ctx->curctx->push('DelegationColor', $arr['color']);
			$this->ctx->curctx->push('DelegationId', $arr['id']);
			$this->ctx->curctx->push('DelegationGroupId', $arr['id_group']);
			$this->ctx->curctx->push('DelegationGroupName', bab_getGroupName($arr['id_group']));
			$this->ctx->curctx->push('DelegationCategoryName', '');
			$this->ctx->curctx->push('DelegationCategoryDescription', '');
			$this->ctx->curctx->push('DelegationCategoryColor', '');
			
			if(0 !== (int) $arr['iIdCategory'])
			{
				$aDatas = $this->oCategoryCache->getCategory($arr['iIdCategory']);
				if(false !== $aDatas)
				{
					$this->ctx->curctx->push('DelegationCategoryName', $aDatas['name']);
					$this->ctx->curctx->push('DelegationCategoryDescription', $aDatas['description']);
					$this->ctx->curctx->push('DelegationCategoryColor', $aDatas['bgcolor']);
				}
			}
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_DelegationManaged extends bab_Delegations
{

	function bab_DelegationManaged( &$ctx)
	{
		$delegationid = $ctx->get_value('delegationid');
		if( $delegationid !== false && !empty($delegationid) )
			{
			$this->bab_bab_Delegations($ctx);
			}
		else
			{
			$this->bab_handler($ctx);
			$this->count = 0;
			$this->ctx->curctx->push('CCount', $this->count);
			}
	}

}


class bab_DelegationItems extends bab_handler
{
	var $index;
	var $count;
	var $arr;

	function bab_DelegationItems( &$ctx)
	{
		global $babDB, $babDG;
		$this->bab_handler($ctx);
		$delegationid = $ctx->get_value('delegationid');

		if( $delegationid !== false && $delegationid !== '' )
			{
			$res = $babDB->db_query("select dgt.* from ".BAB_DG_GROUPS_TBL." dgt where dgt.id='".$babDB->db_escape_string($delegationid)."'");
			$this->arr = $babDB->db_fetch_array($res);

			$this->count = count($babDG);
			}
		else
			{
			$this->count = 0;
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB, $babDG;

		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$count = count($babDG);
			if( isset($this->arr[$babDG[$this->idx][0]]) && $this->arr[$babDG[$this->idx][0]] == 'Y')
			{
				$value = 1;
			}
			else
			{
				$value = 0;
			}
			$this->ctx->curctx->push('DelegationItemName', $babDG[$this->idx][1]);
			$this->ctx->curctx->push('DelegationItemValue', $value);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_DelegationAdministrators extends bab_handler
{
	var $index;
	var $count;
	var $arr;

	function bab_DelegationAdministrators( &$ctx)
	{
		global $babDB, $babDG;
		$this->bab_handler($ctx);
		$delegationid = $ctx->get_value('delegationid');

		if( $delegationid !== false && $delegationid !== '' )
			{
			$this->res = $babDB->db_query("select id_user from ".BAB_DG_ADMIN_TBL." where id_dg='".$babDB->db_escape_string($delegationid)."'");
			$this->count = $babDB->db_num_rows($this->res);
			}
		else
			{
			$this->count = 0;
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB, $babDG;

		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DelegationUserId', $arr['id_user']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}




class bab_DelegationsCategories extends bab_handler
{
	var $iIndex		= 0;
	var $iCount		= 0;
	var $oResult	= null;

	function bab_DelegationsCategories(&$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$this->iCount	= 0;
		$categoryid		= $ctx->get_value('categoryid');
		
		if($categoryid === false || $categoryid === '')
		{
			$this->oResult = $babDB->db_query('SELECT * FROM '.BAB_DG_CATEGORIES_TBL.' order by name asc');
			if(false !== $this->oResult)
			{
				$this->iCount = $babDB->db_num_rows($this->oResult);
			}
		}
		else
		{
			$categoryid = explode(',', $categoryid);
			$this->oResult = $babDB->db_query('SELECT * FROM '.BAB_DG_CATEGORIES_TBL.' WHERE id IN(' .  $babDB->quote($categoryid) . ') order by name asc');
			if(false !== $this->oResult)
			{
				$this->iCount = $babDB->db_num_rows($this->oResult);
			}
		}
		$this->ctx->curctx->push('CCount', $this->iCount);
	}
	
	function getnext()
	{
		global $babDB;

		if($this->idx < $this->iCount)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			
			$aDatas = $babDB->db_fetch_array($this->oResult);
			if(false !== $aDatas)
			{
				$this->ctx->curctx->push('DelegationCategoryName', $aDatas['name']);
				$this->ctx->curctx->push('DelegationCategoryDescription', $aDatas['description']);
				$this->ctx->curctx->push('DelegationCategoryColor', $aDatas['color']);
				$this->ctx->curctx->push('DelegationCategoryId', $aDatas['id']);
			}
			else
			{
				$this->ctx->curctx->push('DelegationCategoryName', '');
				$this->ctx->curctx->push('DelegationCategoryDescription', '');
				$this->ctx->curctx->push('DelegationCategoryColor', '');
				$this->ctx->curctx->push('DelegationCategoryId', 0);
			}
			
			$this->idx++;
			$this->iIndex = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_DelegationsCategory extends bab_DelegationsCategories
{

	function bab_DelegationsCategory(&$ctx)
	{
		parent::bab_DelegationsCategories($ctx);
		
		$delegationid = $ctx->get_value('delegationid');
		if($delegationid !== false && !empty($delegationid) )
		{
			$this->bab_Delegations($ctx);
		}
		else
		{
			$this->bab_handler($ctx);
			$this->count = 0;
			$this->ctx->curctx->push('CCount', $this->count);
		}
	}
}
?>