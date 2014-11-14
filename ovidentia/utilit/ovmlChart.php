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

require_once $GLOBALS['babInstallPath'].'utilit/ocapi.php';
include_once $GLOBALS['babInstallPath'].'utilit/omlincl.php';




/**
 * OVML Container <OCOrgUserEntities [orgChartId=""] userId="" [roleType=""]>
 *
 * List of organizational chart entities for which a user has a role.
 *
 * Returned OVML variables are :
 * - OVEntityId
 * - OVEntityName
 * - OVEntityDescription
 */
class Func_Ovml_Container_OrgUserEntities extends Func_Ovml_Container
{
	var $aEntity = array();
	
	var $iIndex = 0;
	var $iCount = 0;
	
	public function setOvmlContext(babOvTemplate $ctx)
	{
		parent::setOvmlContext($ctx);
		
		$iIdOrgChart	= (int) $ctx->get_value('orgChartId');
		$iIdUser		= (int) $ctx->get_value('userId');
		$sRoleType		= (string) $ctx->get_value('roleType');
		
		if(0 === $iIdOrgChart)
		{
			$aPrimaryChart = bab_OCGetRootEntity();
			if(0 !== count($aPrimaryChart))
			{
				$iIdOrgChart = (int) $aPrimaryChart['id_oc'];
			}			
		}
		
		$aRoleType = null;
		if(0 !== mb_strlen(trim($sRoleType)))
		{
			$aType = explode(',', $sRoleType);
			if(false !== $aType)
			{
				$aRoleType = $aType;
			}
		}
		
		$oOrgChartUtil = new bab_OrgChartUtil($iIdOrgChart);
		$this->aEntity = $oOrgChartUtil->getUserEntities($iIdUser, $aRoleType);
		//bab_debug($this->aEntity);
		
		if(is_array($this->aEntity))
		{
			$this->iCount = count($this->aEntity);
			$this->ctx->curctx->push('CCount', $this->iCount);
		}
		else
		{
			$this->ctx->curctx->push('CCount', 0);
		}
	}

	public function getnext()
	{
		$this->ctx->curctx->push('CIndex', $this->idx);
		$this->ctx->curctx->push('EntityId', 0);
		$this->ctx->curctx->push('EntityName', '');
		$this->ctx->curctx->push('EntityDescription', '');

		if($this->iIndex < $this->iCount)
		{
			$aDatas = each($this->aEntity);
			if(false !== $aDatas)
			{
				$this->ctx->curctx->push('EntityId', $aDatas['value']['id']);
				$this->ctx->curctx->push('EntityName', $aDatas['value']['name']);
				$this->ctx->curctx->push('EntityDescription', $aDatas['value']['description']);
				
				$this->idx++;
				$this->iIndex = $this->idx;
				return true;
			}
		}
		
		$this->idx = 0;
		return false;
	}
}


/**
 * OVML Container <OCOrgPathToEntity entityId="" [order=""] [includeEntity=""]>
 *
 * List all entities related to an entity of an organization chart.
 *
 * Returned OVML variables are :
 * - OVEntityId
 * - OVEntityName
 * - OVEntityDescription
 */
class Func_Ovml_Container_OrgPathToEntity extends Func_Ovml_Container
{
	var $aEntity	= array();
	var $iIndex		= 0;
	var $iCount		= 0;
	var $oResult	= false;
	
	public function setOvmlContext(babOvTemplate $ctx)
	{
		parent::setOvmlContext($ctx);
		
		$iIdEntity		= (int) $ctx->get_value('entityId');
		$bIncludeEntity	= ('1' == (int) $ctx->get_value('includeEntity'));
		$sOrder			= (string) $ctx->get_value('order');

		$sQuery = bab_OCGetPathToNodeQuery($iIdEntity, $bIncludeEntity, $sOrder);		
		//bab_debug($sQuery);
		
		$this->ctx->curctx->push('CCount', 0);
		
		global $babDB;
		
		$this->oResult = $babDB->db_query($sQuery);
		if(false !== $this->oResult)
		{
			$iNumRows = $babDB->db_num_rows($this->oResult);
			$this->iCount = $iNumRows;
			$this->ctx->curctx->push('CCount', $iNumRows);
		}
	}

	public function getnext()
	{
		$this->ctx->curctx->push('CIndex', $this->idx);
		$this->ctx->curctx->push('EntityId', 0);
		$this->ctx->curctx->push('EntityName', '');
		$this->ctx->curctx->push('EntityDescription', '');

		if($this->iIndex < $this->iCount)
		{
			global $babDB;
			if(false !== ($aDatas = $babDB->db_fetch_assoc($this->oResult)))
			{
				$this->ctx->curctx->push('EntityId', $aDatas['iIdEntity']);
				$this->ctx->curctx->push('EntityName', $aDatas['sName']);
				$this->ctx->curctx->push('EntityDescription', $aDatas['sDescription']);
				
				$this->idx++;
				$this->iIndex = $this->idx;
				return true;
			}
		}
		
		$this->idx = 0;
		return false;
	}
}
	
	