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
* @internal SEC1 NA 05/12/2006 FULL
*/

include_once 'base.php';
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $babInstallPath.'utilit/afincl.php';

//define("BAB_DEBUG_FA", 1);

function listSchemasInstances()
{
	global $babBody;
	class listSchemasInstancesCls
		{
		var $title;
		var $urltxt;
		var $url;
		var $description;
		var $altbg = true;

		function listSchemasInstancesCls()
			{
			global $babBody, $babDB;
			$this->schnametxt = bab_translate("Name");
			$this->schdesctxt = bab_translate("Description");
			$this->schtypetxt = bab_translate("Type");
			$this->roles = bab_translate("Roles");
			$this->nominative = bab_translate("Nominative");
			$this->groups = bab_translate("Groups");
			$this->orgnametxt = bab_translate("Charts");
			$this->none = bab_translate("None");
			$this->instancetxt = bab_translate("Instance");
			$this->updatetxt = bab_translate("Update");
			$req = "select fai.id as instnbr, fai.iduser, fai.extra, fa.*, oc.name as orgname from ".BAB_FA_INSTANCES_TBL." fai left join  ".BAB_FLOW_APPROVERS_TBL." fa on fa.id=fai.idsch left join ".BAB_ORG_CHARTS_TBL." oc on oc.id=fa.id_oc where fa.id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' order by fa.name asc";
			$this->res = $babDB->db_query($req);

			$this->count = $babDB->db_num_rows($this->res);

			$req = "select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc";
			$this->sares = $babDB->db_query($req);
			if( !$this->sares )
				{
				$this->sacount = 0;
				}
			else
				{
				$this->sacount = $babDB->db_num_rows($this->sares);
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$arr = $babDB->db_fetch_array($this->res);
				$this->urltxt = $arr['instnbr'].'('.$arr['extra'].')';
				$this->currentsa = $arr['id'];
				$this->idschi = $arr['instnbr'];
				switch($arr['satype'])
					{
					case 1:
						$this->schtypeval = $this->roles;
						break;
					case 2:
						$this->schtypeval = $this->groups;
						break;
					default:
						$this->schtypeval = $this->nominative;
						break;
					}

				$this->schdescval = $arr['name'];
				if( $arr['orgname'] )
					{
					$this->orgnameval = $arr['orgname'];
					}
				else
					{
					$this->orgnameval = '';
					}

				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextschapp(&$skip)
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
				if( $this->said == $this->currentsa )
					{
					$skip = true;
					}
				$i++;
				return true;
				}
			else
				{
				if( $this->sacount > 0 )
					{
					$babDB->db_data_seek($this->sares, 0);
					}
				$i = 0;
				return false;
				}
			}
		}

	$temp = new listSchemasInstancesCls();
	$babBody->babecho(bab_printTemplate($temp, "apprflow.html", "schemasinstanceslist"));
}



function getApprovalSchemaName($id)
{
	global $babDB;
	$query = "select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
}

function getRoleName($id)
{
	global $babDB;
	$query = "select name from ".BAB_OC_ROLES_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
}


function testSchema($idsch, $idschi, $resf)
{
	global $babBody;
	class temp
		{
		function temp($idsch, $idschi, $resf)
			{
			global $babDB;
			if( !isset($idschi) || $idschi == "")
				$idschi = makeFlowInstance($idsch, "test");

			$res = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$babDB->db_escape_string($idsch)."'");
			$arr = $babDB->db_fetch_array($res);
			$this->formula = $arr['formula'];
			$this->idschi = $idschi;
			$this->idsch = $idsch;
			$this->resultat = $resf;
			$this->order = $arr['forder'];
			$this->type = $arr['satype'];

			if(isset($resf) && $resf > -1)
				{
				deleteFlowInstance($idschi);
				$this->count = 0;
				}
			else
				{
				$this->arrusers = getWaitingApproversFlowInstance($idschi);
				$this->arrnfusers = getWaitingApproversFlowInstance($idschi, true);
				$this->count = count( $this->arrusers );
				}
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->username = "[".$this->arrusers[$i]."] ".bab_getUserName($this->arrusers[$i]);
				if( count($this->arrnfusers) > 0 && in_array($this->arrusers[$i], $this->arrnfusers))
					{
					$this->username .= " *";
					}
				$this->userid = $this->arrusers[$i];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idsch, $idschi, $resf);
	$babBody->babecho(bab_printTemplate($temp,"apprflow.html", "schematest"));
}

function schemaCreate($formula, $idsch, $schname, $schdesc, $order, $bdel, $ocid, $type=0)
{
	global $babBody;
	class temp
		{
		var $all;
		var $atleastone;

		function temp($formula, $idsch, $schname, $schdesc, $order, $bdel, $ocid, $type)
			{
			global $babDB;

			$this->all = bab_translate("All");
			$this->atleastone = bab_translate("At least one");
			$this->order = bab_translate("Follow order");
			$this->schnametxt = bab_translate("Name");
			$this->schdesctxt = bab_translate("Description");
			$this->t_empty_cell = bab_translate("Delete");
			$this->fieldval = "";
			$this->fieldid = "";
			$this->schdescval = $schdesc == ""? "": $schdesc;
			$this->schnameval = $schname == ""? "": $schname;
			$this->idsch = $idsch == ""? "": $idsch;
			$this->type = $type;
			$this->ocid = $ocid;
			$this->ocname = '';

			switch($type)
				{
				case 1:
					$this->userslisttxt = bab_translate("Roles list");
					list($this->ocname) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_ORG_CHARTS_TBL." where id='".$babDB->db_escape_string($this->ocid)."'"));
					$this->userslisturl = $GLOBALS['babUrlScript']."?tg=admoc&idx=browr&ocid=".$this->ocid."&cb=";
					break;
				case 2:
					$this->userslisttxt = bab_translate("Groups list");
					$this->ocid = 0;
					$this->userslisturl = $GLOBALS['babUrlScript']."?tg=groups&idx=brow&cb=";
					break;
				default:
					$this->userslisttxt = bab_translate("Users list");
					$this->ocid = 0;
					$this->userslisturl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
					break;
				}

			$this->findex = 0;
			$this->frow = 0;
			$this->rows = 5;
			$this->cols = 4;
			if( empty($order) || $order == "Y")
				$this->ordchecked = "checked";
			else
				$this->ordchecked = "";

			if( $formula != "")
				{
				$this->arr = explode(",", $formula);
				}

			$this->del = bab_translate("Delete");
			$this->bdel = $bdel;
			if( !empty($idsch))
				{
				$this->what = "modsch";
				$this->add = bab_translate("Modify");
				}
			else
				{
				$this->what = "sch";
				$this->add = bab_translate("Add");
				}
			}


		function getnextrow()
			{
			static $i = 0;
			if( $i < $this->rows)
				{
				$this->arrf = array();
				$this->frow++;
				$this->allselected = "";
				if( isset($this->arr[$i]))
					{
					if( strchr($this->arr[$i], "&"))
						$this->op = "&";
					else
						$this->op = "|";
					if( $this->op == "&" )
						$this->allselected = "selected";
					$this->arrf = explode($this->op, $this->arr[$i]);
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextcol()
			{
			static $j = 0;
			if( $j < $this->cols)
				{
				if( count($this->arrf) > $j )
					{
					switch($this->type )
						{
						case 1:
							if( $this->arrf[$j] == 0 )
								{
								$name = bab_translate("Immediat superior");
								}
							else
								{
								$name = getRoleName($this->arrf[$j]);
								$rr = bab_getOrgChartRoleUsers($this->arrf[$j]);
								if( count($rr) == 0 )
									{
									$name .= "(?)";
									}
								else
									{
									$name .= "(".$rr['name'][0].")";
									}
								}
							break;
						case 2:
							$name = bab_getGroupName($this->arrf[$j]);
							break;
						default:
							$name = bab_getUserName($this->arrf[$j]);
							break;
						}
					$this->fieldval = $name ? $name : '???';
					$this->fieldid = $name ? $this->arrf[$j] : '';
					}
				else
					{
					$this->fieldval = "";
					$this->fieldid = "";
					}
				$this->findex++;
				$j++;
				return true;
				}
			else
				{
				$j = 0;
				return false;
				}
			}
		}

	$temp = new temp($formula, $idsch, $schname, $schdesc, $order, $bdel, $ocid, $type);
	$babBody->addStyleSheet('apprflow.css');
	$babBody->babecho(	bab_printTemplate($temp,"apprflow.html", "schemacreate"));

}

function modifySchema($idsch)
{
	global $babDB, $idx;

	$res = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$babDB->db_escape_string($idsch)."'");
	if( $res && $babDB->db_num_rows($res) > 0)
	{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['refcount'] == 0 )
			schemaCreate($arr['formula'], $idsch, $arr['name'], $arr['description'], $arr['forder'], true, $arr['id_oc'], $arr['satype']);
		else
			schemaCreate($arr['formula'], $idsch, $arr['name'], $arr['description'], $arr['forder'], false, $arr['id_oc'], $arr['satype']);
	}
	else
	{
		$idsch = "";
		$idx = "new";
	}
}


function listSchemas()
{
	global $babBody;
	class temp
		{
		var $title;
		var $urltxt;
		var $url;
		var $description;
		var $altbg = true;

		function temp()
			{
			global $babBody, $babDB;
			$this->schnametxt = bab_translate("Name");
			$this->schdesctxt = bab_translate("Description");
			$this->schtypetxt = bab_translate("Type");
			$this->roles = bab_translate("Roles");
			$this->nominative = bab_translate("Nominative");
			$this->groups = bab_translate("Groups");
			$this->orgnametxt = bab_translate("Charts");
			$req = "select fa.*, oc.name as orgname from ".BAB_FLOW_APPROVERS_TBL." fa left join ".BAB_ORG_CHARTS_TBL." oc on oc.id=fa.id_oc where fa.id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' order by fa.name asc";
			$this->res = $babDB->db_query($req);

			$this->count = $babDB->db_num_rows($this->res);
			if( defined("BAB_DEBUG_FA"))
				{
				$this->testurltxt = "Test";
				$this->debugfa = true;
				}
			else
				$this->debugfa = false;
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$arr = $babDB->db_fetch_array($this->res);
				$this->urltxt = $arr['name'];
				switch($arr['satype'])
					{
					case 1:
						$this->schtypeval = $this->roles;
						break;
					case 2:
						$this->schtypeval = $this->groups;
						break;
					default:
						$this->schtypeval = $this->nominative;
						break;
					}

				$this->url = $GLOBALS['babUrlScript']."?tg=apprflow&idx=mod&idsch=".$arr['id'];
				if( $this->debugfa )
					{
					$this->testurl = $GLOBALS['babUrlScript']."?tg=apprflow&idx=test&idsch=".$arr['id'];
					}
				$this->schdescval = $arr['description'];
				if( $arr['orgname'] )
					{
					$this->orgnameval = $arr['orgname'];
					}
				else
					{
					$this->orgnameval = '';
					}

				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "apprflow.html", "schemaslist"));
}


function listOrgCharts()
{
	global $babBody;
	class listOrgChartsCls
		{
		var $satype;
		var $messagetxt;
		var $donetxt;
		var $res;
		var $count;
		var $orgname;
		var $orgid;

		function listOrgChartsCls()
			{
			global $babBody, $babDB;
			$this->satype = 1;
			$this->messagetxt = bab_translate("Select the organizational chart on which the workflow will be based");
			$this->donetxt = bab_translate("Next");
			$this->res = $babDB->db_query("select b.id, b.name from ".BAB_ORG_CHARTS_TBL." b left join ".BAB_DB_DIRECTORIES_TBL." dd on b.id_directory=dd.id where dd.id_group!=0 and b.id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' order by b.name asc");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnextorg()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->orgname = $arr['name'];
				$this->orgid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new listOrgChartsCls();
	$babBody->babecho(bab_printTemplate($temp, "apprflow.html", "orgchartslist"));
}

function schemaDelete($id)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this approval schema");
			$this->title = getApprovalSchemaName($id);
			$this->warning = bab_translate("WARNING: This operation will delete schema and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=apprflow&idx=delsc&idsch=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=apprflow&idx=list";
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function saveSchema($rows, $cols, $order, $schname, $schdesc, $idsch, $ocid, $type)
{
	global $babBody, $babDB;

	$row = 0;
	$result = array();
	for( $i= 0; $i < $rows; $i++)
	{
		$tab = array();
		$f = "fs".($i+1);
		if( $GLOBALS[$f] == "Y")
			$op = "&";
		else
			$op = "|";
		for( $j= 0; $j < $cols; $j++)
			{
			$f = "fn".(($cols * $i)+($j+1));
			if(isset($GLOBALS[$f]) && $GLOBALS[$f] != '')
				{
				$f = "fh".(($cols * $i)+($j+1));
				if(isset($GLOBALS[$f]) && $GLOBALS[$f] != '')
					$tab[] = $GLOBALS[$f];
				}
			}

		if( count($tab) > 0 )
			{
			$result[] = implode($op, $tab);
			}

	}

	if( count($result) > 0 )
		{
		$ret = implode(",", $result);
		}
	else
		{
		$ret = "";
		$babBody->msgerror = bab_translate("ERROR: You must provide at least one approver !!");
		return $ret;
		}

	if( empty($schname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return $ret;
		}

	if( empty($order))
		{
		$order = "N";
		}
	if( !isset($idsch) || $idsch == "")
		{
		$req = "select id from ".BAB_FLOW_APPROVERS_TBL." where name='".$babDB->db_escape_string($schname)."'";	
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$babBody->msgerror = bab_translate("This flow approvers already exists");
			return $ret;
			}
		else
			{
			$req = "insert into ".BAB_FLOW_APPROVERS_TBL." (name, description, formula, forder, id_dgowner, satype, id_oc) 
			VALUES 
				(
				'" . $babDB->db_escape_string($schname). "', 
				'" . $babDB->db_escape_string($schdesc). "', 
				'" . $babDB->db_escape_string($ret). "', 
				'" . $babDB->db_escape_string($order). "', 
				'" . $babDB->db_escape_string($babBody->currentAdmGroup). "', 
				'" . $babDB->db_escape_string($type). "', 
				'" . $babDB->db_escape_string($ocid). "'
			)";

			$babDB->db_query($req);
			}
		}
	else
		{
		$req = "select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$babDB->db_escape_string($idsch)."'";	
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
			$req = "update ".BAB_FLOW_APPROVERS_TBL." set name='".$babDB->db_escape_string($schname)."', description='".$babDB->db_escape_string($schdesc)."', formula='".$babDB->db_escape_string($ret)."', forder='".$babDB->db_escape_string($order)."' where id='".$babDB->db_escape_string($idsch)."'";
			$babDB->db_query($req);
			if( $arr['formula'] != $ret )
				{
				$res = $babDB->db_query("select * from ".BAB_FA_INSTANCES_TBL." where idsch='".$babDB->db_escape_string($idsch)."'");
				while( $arr = $babDB->db_fetch_array($res))
					{
					updateSchemaInstance($arr['id']);
					// force notifications otherwise approbations will not be seen
					getWaitingApproversFlowInstance($arr['id'], true);
					}
				}
			}
		}
	
	return "";
}

function confirmDeleteSchema($id)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteApprobationSchema($id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
	}

function updateSchemaInstances($instances)
{
	global $babDB, $babBody;

	for( $k = 0; $k < count($instances); $k++ )
	{
		if( !empty($instances[$k]))
		{
			$tt = explode('-', $instances[$k]);
			if( count($tt) == 2 )
			{
				$newsch = intval($tt[0]);
				$inst = intval($tt[1]);
				if( !empty($newsch) && !empty($inst))
				{
					$res = $babDB->db_query("select * from ".BAB_FA_INSTANCES_TBL." where id ='".$babDB->db_escape_string($inst)."'");
					if( $res && $babDB->db_num_rows($res) )
					{
					$arinst = $babDB->db_fetch_array($res);
					$idschi = makeFlowInstance($newsch, $arinst['extra']);
					if( $idschi )
						{
						deleteFlowInstance($arinst['id']);
						$babDB->db_query("update ".BAB_FA_INSTANCES_TBL." set id='".$arinst['id']."' where id='".$idschi."'" );
						$babDB->db_query("update ".BAB_FAR_INSTANCES_TBL." set idschi='".$arinst['id']."' where idschi='".$idschi."'" );
						$nfusers = getWaitingApproversFlowInstance($arinst['id'], true);
						}
					}
				}
			}
		}
	}
}



/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['approbations'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'list');
$res = bab_rp('res', -1);

if( defined("BAB_DEBUG_FA") && ('testsc' == bab_pp('test')))
{
	$userids = bab_pp('userids', array());
	for( $k = 0; $k < count($userids); $k++)
	{
	$sel = "us".$userids[$k];
	if( $$sel == "Y")
		{
		$bool = true;
		}
	else
		{
		$bool = false;
		}
	$res = updateFlowInstance(bab_pp('idschi'), $userids[$k], $bool);
	}
}

if( isset($_POST['add']))
	{
	if( isset($_POST['addb']))
		{
		$order = bab_pp('order', 'N');
		$formula = saveSchema(bab_pp('rows'), bab_pp('cols'), $order, bab_pp('schname'), bab_pp('schdesc'), bab_pp('idsch'), bab_pp('ocid'), bab_pp('type'));
		if( $formula != "")
			switch($_POST['add'])
			{
			case "sch":
				$idx = "new";
				break;
			case "modsch":
				$idx = "mod";
				break;
			}
		}
	else if( isset($_POST['delb']))
		{
		$idx = "delsc";
		}
	}
elseif( isset($_POST['updsch']) && $_POST['updsch'] == 'updsch')
	{
		updateSchemaInstances(isset($_POST['newidsch'])? $_POST['newidsch']: array());
		$idx = 'linst';
	}

if( 'Yes' == bab_rp('action'))
	{
	confirmDeleteSchema(bab_rp('idsch'));
	}

switch($idx)
	{
	case "delsc":
		$babBody->title = bab_translate("Delete schema");
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("newa", bab_translate("Nominative schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newa&type=0");
		$babBody->addItemMenu("newb", bab_translate("Staff schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newb&type=1");
		$babBody->addItemMenu("newc", bab_translate("Group schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newc&type=2");
		$babBody->addItemMenu("delsc", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=delsc");
		schemaDelete($idsch);
		if( isset($GLOBALS['babShowApprobationInstances']) && $GLOBALS['babShowApprobationInstances'] === true)
		{
		$babBody->addItemMenu("linst", bab_translate("Instances"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=linst");
		}
		break;

	case "mod":
		$babBody->title = bab_translate("Modify approbation schema");
		modifySchema($idsch);
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=mod");
		$babBody->addItemMenu("newa", bab_translate("Nominative schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newa&type=0");
		$babBody->addItemMenu("newb", bab_translate("Staff schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newb&type=1");
		$babBody->addItemMenu("newc", bab_translate("Group schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newc&type=2");
		if( isset($GLOBALS['babShowApprobationInstances']) && $GLOBALS['babShowApprobationInstances'] === true)
		{
		$babBody->addItemMenu("linst", bab_translate("Instances"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=linst");
		}
		break;
	case "newb":
		if( !isset($_POST['ocid']) || empty($_POST['ocid']))
		{
		$babBody->title = bab_translate("Choice of the organizational chart");
		listOrgCharts();
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("newa", bab_translate("Nominative schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newa&type=0");
		$babBody->addItemMenu("newb", bab_translate("Staff schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newb&type=1");
		$babBody->addItemMenu("newc", bab_translate("Group schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newc&type=2");
		if( isset($GLOBALS['babShowApprobationInstances']) && $GLOBALS['babShowApprobationInstances'] === true)
		{
		$babBody->addItemMenu("linst", bab_translate("Instances"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=linst");
		}
		break;
		}
		/* no break; */
	case "newa":
	case "newc":
		$babBody->title = bab_translate("Create a new approbation schema");
		$formula = bab_pp('formula');
		$idsch = bab_pp('idsch');
		$schname = bab_pp('schname');
		$schdesc = bab_pp('schdesc');
		$order = bab_pp('order');
		$ocid = bab_pp('ocid');
		schemaCreate($formula, $idsch, $schname, $schdesc, $order, false, $ocid, $type);
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("newa", bab_translate("Nominative schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newa&type=0");
		$babBody->addItemMenu("newb", bab_translate("Staff schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newb&type=1");
		$babBody->addItemMenu("newc", bab_translate("Group schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newc&type=2");
		if( isset($GLOBALS['babShowApprobationInstances']) && $GLOBALS['babShowApprobationInstances'] === true)
		{
		$babBody->addItemMenu("linst", bab_translate("Instances"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=linst");
		}
		break;
	case "test":
		if( defined("BAB_DEBUG_FA"))
		{
			$babBody->title = bab_translate("Test an approbation schema");
			$idschi = bab_rp('idschi');
			testSchema(bab_rp('idsch'), $idschi, $res);
			$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
			$babBody->addItemMenu("newa", bab_translate("Nominative schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newa&type=0");
			$babBody->addItemMenu("newb", bab_translate("Staff schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newb&type=1");
			$babBody->addItemMenu("newc", bab_translate("Group schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newc&type=2");
			$babBody->addItemMenu("test", "Test", $GLOBALS['babUrlScript']."?tg=apprflow&idx=test");
			break;
		}
		/* no break */
	case "list":
		$babBody->title = bab_translate("Approbation schemas list");
		listSchemas();
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("newa", bab_translate("Nominative schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newa&type=0");
		$babBody->addItemMenu("newb", bab_translate("Staff schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newb&type=1");
		$babBody->addItemMenu("newc", bab_translate("Group schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newc&type=2");
		if( isset($GLOBALS['babShowApprobationInstances']) && $GLOBALS['babShowApprobationInstances'] === true)
		{
		$babBody->addItemMenu("linst", bab_translate("Instances"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=linst");
		}
		break;
	case "linst":
		if( isset($GLOBALS['babShowApprobationInstances']) && $GLOBALS['babShowApprobationInstances'] === true)
		{
		$babBody->title = bab_translate("Approbation schemas instances list");
		listSchemasInstances();
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("newa", bab_translate("Nominative schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newa&type=0");
		$babBody->addItemMenu("newb", bab_translate("Staff schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newb&type=1");
		$babBody->addItemMenu("newc", bab_translate("Group schema"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=newc&type=2");
		$babBody->addItemMenu("linst", bab_translate("Instances"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=linst");
		}
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','AdminApprob');
?>
