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
include_once $babInstallPath."utilit/afincl.php";
//define("BAB_DEBUG_FA", 1);

function getApprovalSchemaName($id)
{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$id."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
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
			if( !isset($idschi) || $idschi == "")
				$idschi = makeFlowInstance($idsch, "test");

			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$idsch."'");
			$arr = $db->db_fetch_array($res);
			$this->formula = $arr['formula'];
			$this->idschi = $idschi;
			$this->idsch = $idsch;
			$this->resultat = $resf;
			$this->order = $arr['forder'];

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
				if( in_array($this->arrusers[$i], $this->arrnfusers))
					$this->username .= " *";
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

function schemaCreate($formula, $idsch, $schname, $schdesc, $order, $bdel)
{
	global $babBody;
	class temp
		{
		var $all;
		var $atleastone;

		function temp($formula, $idsch, $schname, $schdesc, $order, $bdel)
			{
			$this->all = bab_translate("All");
			$this->atleastone = bab_translate("At least one");
			$this->userslisttxt = bab_translate("Users list");
			$this->order = bab_translate("Follow order");
			$this->schnametxt = bab_translate("Name");
			$this->schdesctxt = bab_translate("Description");
			$this->fieldval = "";
			$this->fieldid = "";
			$this->schdescval = $schdesc == ""? "": $schdesc;
			$this->schnameval = $schname == ""? "": $schname;
			$this->idsch = $idsch == ""? "": $idsch;
			$this->userslisturl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->findex = 0;
			$this->frow = 0;
			$this->rows = 5;
			$this->cols = 4;
			if( !empty($order) && $order == "Y")
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
					$name = bab_getUserName($this->arrf[$j]);
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

	$temp = new temp($formula, $idsch, $schname, $schdesc, $order, $bdel);
	$babBody->babecho(	bab_printTemplate($temp,"apprflow.html", "schemacreate"));

}

function modifySchema($idsch)
{
	global $idx;

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$idsch."'");
	if( $res && $db->db_num_rows($res) > 0)
	{
		$arr = $db->db_fetch_array($res);
		if( $arr['refcount'] == 0 )
			schemaCreate($arr['formula'], $idsch, $arr['name'], $arr['description'], $arr['forder'], true);
		else
			schemaCreate($arr['formula'], $idsch, $arr['name'], $arr['description'], $arr['forder'], false);
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

		function temp()
			{
			global $babBody;
			$this->schnametxt = bab_translate("Name");
			$this->schdesctxt = bab_translate("Description");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
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
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$arr = $this->db->db_fetch_array($this->res);
				$this->urltxt = $arr['name'];
				$this->url = $GLOBALS['babUrlScript']."?tg=apprflow&idx=mod&idsch=".$arr['id'];
				if( $this->debugfa )
					{
					$this->testurl = $GLOBALS['babUrlScript']."?tg=apprflow&idx=test&idsch=".$arr['id'];
					}
				$this->schdescval = $arr['description'];
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


function saveSchema($rows, $cols, $order, $schname, $schdesc, $idsch)
{
	global $babBody;

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
			if(!empty($GLOBALS[$f]))
				{
				$f = "fh".(($cols * $i)+($j+1));
				if(!empty($GLOBALS[$f]))
					$tab[] = $GLOBALS[$f];
				}
			}

		if( count($tab) > 0 )
			{
			$result[] = implode($op, $tab);
			}

	}

	if( count($result) > 0 )
		$ret = implode(",", $result);
	else
		$ret = "";

	if( empty($schname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return $ret;
		}

	if( empty($ret))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide at least one approver !!");
		return $ret;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$schname = addslashes($schname);
		$schdesc = addslashes($schdesc);
		}

	if( empty($order))
		{
		$order = "N";
		}
	$db = $GLOBALS['babDB'];
	if( !isset($idsch) || $idsch == "")
		{
		$req = "select * from ".BAB_FLOW_APPROVERS_TBL." where name='".$schname."'";	
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$babBody->msgerror = bab_translate("This flow approvers already exists");
			return $ret;
			}
		else
			{
			$req = "insert into ".BAB_FLOW_APPROVERS_TBL." (name, description, formula, forder, id_dgowner) VALUES ('" .$schname. "', '" . $schdesc. "', '" .  $ret. "', '" .  $order. "', '" .  $babBody->currentAdmGroup. "')";
			$db->db_query($req);
			}
		}
	else
		{
		$req = "select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$idsch."'";	
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$req = "update ".BAB_FLOW_APPROVERS_TBL." set name='".$schname."', description='".$schdesc."', formula='".$ret."', forder='".$order."' where id='".$idsch."'";
			$db->db_query($req);
			if( $arr['formula'] != $ret )
				{
				$res = $db->db_query("select * from ".BAB_FA_INSTANCES_TBL." where idsch='".$idsch."'");
				while( $arr = $db->db_fetch_array($res))
					updateSchemaInstance($arr['id']);
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

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['approbations'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "list";

if( !isset($res))
	$res = "-1";

if( defined("BAB_DEBUG_FA") && isset($test) && $test == "testsc")
{
	for( $k = 0; $k < count($userids); $k++)
	{
	$sel = "us".$userids[$k];
	if( $$sel == "Y")
		$bool = true;
	else
		$bool = false;
	$res = updateFlowInstance($idschi, $userids[$k], $bool);
	}
}

if( isset($add))
	{
	if( isset($addb))
		{
		$formula = saveSchema($rows, $cols, $order, $schname, $schdesc, $idsch);
		if( $formula != "")
			switch($add)
			{
			case "sch":
				$idx = "new";
				break;
			case "modsch":
				$idx = "mod";
				break;
			}
		}
	else if( isset($delb))
		{
		$idx = "delsc";
		}
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteSchema($idsch);
	}

switch($idx)
	{
	case "delsc":
		$babBody->title = bab_translate("Delete schema");
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=new");
		$babBody->addItemMenu("delsc", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=delsc");
		schemaDelete($idsch);
		break;

	case "mod":
		$babBody->title = bab_translate("Modify approbation schema");
		modifySchema($idsch);
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=mod");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=new");
		break;
	case "new":
		$babBody->title = bab_translate("Create a new approbation schema");
		schemaCreate($formula, $idsch, $schname, $schdesc, $order, false);
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=new");
		break;
	case "test":
		if( defined("BAB_DEBUG_FA"))
		{
			$babBody->title = bab_translate("Test an approbation schema");
			testSchema($idsch, $idschi, $res);
			$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
			$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=new");
			$babBody->addItemMenu("test", "Test", $GLOBALS['babUrlScript']."?tg=apprflow&idx=test");
			break;
		}
		/* no break */
	case "list":
		$babBody->title = bab_translate("Approbation schemas list");
		listSchemas();
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=new");
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>