<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
define("BAB_FLOW_APPROVERS_TBL", "bab_flow_approvers");
define("BAB_FA_INSTANCES_TBL", "bab_fa_instances");
define("BAB_FAR_INSTANCES_TBL", "bab_far_instances");
define("BAB_DEBUG_FA", 1);


function testSchema($idsch, $idschi, $resf)
{
	global $babBody;
	class temp
		{
		function temp($idsch, $idschi, $resf)
			{
			if( !isset($idschi) || $idschi == "")
				$idschi = makeFlowInstance($idsch, "");

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

function schemaCreate($formula, $idsch, $schname, $schdesc, $order)
{
	global $babBody;
	class temp
		{
		var $all;
		var $atleastone;

		function temp($formula, $idsch, $schname, $schdesc, $order)
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
					$this->fieldval = bab_getUserName($this->arrf[$j]);
					$this->fieldid = $this->arrf[$j];
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

	$temp = new temp($formula, $idsch, $schname, $schdesc, $order);
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
		schemaCreate($arr['formula'], $idsch, $arr['name'], $arr['description'], $arr['forder']);
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
			$this->schnametxt = bab_translate("Name");
			$this->schdesctxt = bab_translate("Description");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FLOW_APPROVERS_TBL."";
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
			$req = "insert into ".BAB_FLOW_APPROVERS_TBL." (name, description, formula, forder) VALUES ('" .$schname. "', '" . $schdesc. "', '" .  $ret. "', '" .  $order. "')";
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

function makeFlowInstance($idsch, $extra)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$idsch."'");
	$result = array();
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$db->db_query("insert into ".BAB_FA_INSTANCES_TBL." (idsch, extra) VALUES ('".$idsch."', '".$extra."')");
		$id = $db->db_insert_id();
		updateSchemaInstance($id);
		return $id;
		}
	return "";
}


function evalFlowInstance($idschi)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and result='0'");
	if( $res && $db->db_num_rows($res) > 0 )
		return 0;

	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	$result = array();
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$arr = explode(",", $arr['formula']);
		for( $i= 0; $i < count($arr); $i++)
			{
			if( strchr($arr[$i], "&"))
				$op = "&";
			else if( strchr($arr[$i], "|"))
				$op = "|";
			else
				$op = "";

			switch($op)
				{
				case "&":
					$rr = explode($op, $arr[$i]);
					for( $k = 0; $k < count($rr); $k++)
						{
						$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$rr[$k]."' and result=''");
						if( $res && $db->db_num_rows($res) > 0)
							{
							return -1;
							}
						}
					break;
				case "|":
					$rr = explode($op, $arr[$i]);
					for( $k = 0; $k < count($rr); $k++)
						{
						$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$rr[$k]."' and result=''");
						if( $res && $db->db_num_rows($res) > 0)
							{
							return -1;
							}
						}
					break;
				default:
					$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$arr[$i]."'");
					$tab = $db->db_fetch_array($res);
					if( $tab['result'] == '')
					{
						return -1;
					}
					break;
				}
			}
		return 1;
		}
	return -1;
}

function deleteFlowInstance($idschi)
{
	$db = $GLOBALS['babDB'];
	$db->db_query("delete from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."'");
	$db->db_query("delete from ".BAB_FA_INSTANCES_TBL." where id='".$idschi."'");
}

function updateFlowInstance($idschi, $iduser, $bool)
{

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$iduser."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $bool)
			$result = "1";
		else
			$result ="0";
		$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='".$result."' where id='".$arr['id']."'");
		
		if( $result == 0 )
			{
			$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='x' where idschi='".$idschi."' and result=''");
			}
		else
			{
			$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
			$result = array();
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$arr = explode(",", $arr['formula']);
				for( $i= 0; $i < count($arr); $i++)
					{
					if( strchr($arr[$i], "&"))
						$op = "&";
					else if( strchr($arr[$i], "|"))
						$op = "|";
					else
						$op = "";

					if( $op != "")
						{
						$rr = explode($op, $arr[$i]);
						if( count($rr) > 1 && $op == "|" && in_array($iduser, $rr))
							{
							for( $k = 0; $k < count($rr); $k++)
								{
								if( $rr[$k] != $iduser )
									$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='x' where idschi='".$idschi."' and iduser='".$rr[$k]."'");
								}
							}
						}
					}
				}
			}
		}
	return evalFlowInstance($idschi);
}

function updateSchemaInstance($idschi)
{

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");

	$arr = $db->db_fetch_array($res);
	$tab = explode(",", $arr['formula']);
	for( $i= 0; $i < count($tab); $i++)
		{
		$rr = array();
		if( strchr($tab[$i], "&"))
			$op = "&";
		else
			$op = "|";

		$rr = explode($op, $tab[$i]);
		for($k=0; $k < count($rr); $k++)
			{
			if( count($tabusers) == 0 || (count($tabusers) > 0 && !in_array( $rr[$k], $tabusers )))
				$tabusers[] = $rr[$k];
			}
		}

	$tab = $tabusers;
	$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."'");
	while( $arr3 = $db->db_fetch_array($res))
		{
		if( !in_array($arr3['iduser'], $tab))
			{
			$db->db_query("delete from ".BAB_FAR_INSTANCES_TBL." where id='".$arr3['id']."'");
			}
		else 
			{
			for($j = 0; $j < count($tab); $j++)
				{
					if ($tab[$j] == $arr3['iduser'])
					{
						array_splice($tab, $j, 1);
						break;
					}
				}
			}
		}

	for($j = 0; $j < count($tab); $j++)
		{
		$db->db_query("insert into ".BAB_FAR_INSTANCES_TBL." (idschi, iduser) VALUES ('".$idschi."', '".$tab[$j]."')");
		}

}

function getApproversFlowInstance($idschi)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	$result = array();
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$tab = explode(",", $arr['formula']);
		for( $i= 0; $i < count($tab); $i++)
			{
			if( strchr($tab[$i], "&"))
				$op = "&";
			else
				$op = "|";

			$rr = explode($op, $tab[$i]);
			for( $k = 0; $k < count($rr); $k++)
				{
				$result[] = $rr[$k];
				}
			}
		}
	return $result;
}

function isUserApproverFlowInstance($idschi, $iduser)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$tab = explode(",", $arr['formula']);
		for( $i= 0; $i < count($tab); $i++)
			{
			if( strchr($tab[$i], "&"))
				$op = "&";
			else
				$op = "|";

			$rr = explode($op, $tab[$i]);
			for( $k = 0; $k < count($rr); $k++)
				{
				if( $rr[$k] == $iduser )
					return true;
				}
			}
		}
	return false;
}

function getWaitingApproversFlowInstance($idschi, $notify=false)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	$result = array();
	$notifytab = array();
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$tab = explode(",", $arr['formula']);
		for( $i= 0; $i < count($tab); $i++)
			{
			if( strchr($tab[$i], "&"))
				$op = "&";
			else
				$op = "|";

			$rr = explode($op, $tab[$i]);
			for( $k = 0; $k < count($rr); $k++)
				{
				$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$rr[$k]."'");
				$arr2 = $db->db_fetch_array($res);
				if( $arr2['result'] == "")
					{
					$result[] = $rr[$k];
					if( $notify && $arr2['notified'] == "N" )
						{
						$notifytab[] = $rr[$k];
						$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set notified='Y' where id='".$arr2['id']."'");
						}
					}
				}

			if( $arr['forder'] == "Y" &&  count($result) > 0 )
				{
				if( $notify)
				{
					return $notifytab;
				}
				else
				{
					return $result;
				}
				}
			}
		}

	if( $notify)
	{
		return $notifytab;
	}
	else
	{
		return $result;
	}
}
/* main */
if( !isset($idx))
	$idx = "new";

if( !isset($res))
	$res = "-1";

if( isset($test) && $test == "testsc")
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

switch($idx)
	{
	case "test":
		$babBody->title = bab_translate("Schemas list");
		testSchema($idsch, $idschi, $res);
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=new");
		$babBody->addItemMenu("test", "Test", $GLOBALS['babUrlScript']."?tg=apprflow&idx=test");
		break;
	case "mod":
		$babBody->title = bab_translate("Schemas list");
		modifySchema($idsch);
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=mod");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=new");
		break;
	case "new":
		$babBody->title = bab_translate("Schemas list");
		schemaCreate($formula, $idsch, $schname, $schdesc, $order);
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=new");
		break;
	case "list":
		$babBody->title = bab_translate("Schemas list");
		listSchemas();
		$babBody->addItemMenu("list", bab_translate("Schemas"),$GLOBALS['babUrlScript']."?tg=apprflow&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=apprflow&idx=new");
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>