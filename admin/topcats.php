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
include_once $babInstallPath."admin/acl.php";
include_once $babInstallPath."utilit/topincl.php";

function topcatCreate($idp)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $enabled;
		var $no;
		var $yes;
		var $add;
		var $arrtmpl;
		var $counttmpl;
		var $arrdisptmpl;
		var $countdisptmpl;
		var $templatetxt;
		var $templateval;
		var $templateid;
		var $disptmpltxt;
		var $topcattxt;
		var $topcatid;
		var $topcatval;
		var $nonetxt;
		var $idp;
		var $selected;

		function temp($idp)
			{
			global $babBody, $babDB;

			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->enabled = bab_translate("Section enabled");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->add = bab_translate("Add");
			$this->templatetxt = bab_translate("Section template");
			$this->disptmpltxt = bab_translate("Display template");
			$this->topcattxt = bab_translate("Topics category parent");
			$this->nonetxt = "--- ".bab_translate("None")." ---";
			$this->idp = $idp;
			$file = "topicssection.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$this->arrtmpl = $tpl->getTemplates($filepath);
				}
			$this->counttmpl = count($this->arrtmpl);

			$file = "topcatdisplay.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$this->arrdisptmpl = $tpl->getTemplates($filepath);
				}
			$this->countdisptmpl = count($this->arrdisptmpl);

			$this->res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'");
			$this->count = $babDB->db_num_rows($this->res);

			}

		function getnexttemplate()
			{
			static $i = 0;
			if($i < $this->counttmpl)
				{
				$this->templateid = $this->arrtmpl[$i];
				$this->templateval = $this->arrtmpl[$i];
				$i++;
				return true;
				}
			return false;
			}

		function getnextdisptemplate()
			{
			static $i = 0;
			if($i < $this->countdisptmpl)
				{
				$this->templateid = $this->arrdisptmpl[$i];
				$this->templateval = $this->arrdisptmpl[$i];
				$i++;
				return true;
				}
			return false;
			}

		function getnexttopcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->topcatid = $this->arr['id'];
				$this->topcatval = $this->arr['title'];
				if( $this->idp == $this->topcatid)
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($idp);
	$babBody->babecho( bab_printTemplate($temp,"topcats.html", "topcatcreate"));
	}

function topcatsList($idp)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $url;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $catchecked;
		var $disabled;
		var $checkall;
		var $uncheckall;
		var $update;
		var $topcount;
		var $topcounturl;
		var $topics;
		var $topcats;
		var $topcatcount;
		var $topcatcounturl;
		var $arrparents = array();
		var $countparents;
		var $parentval;
		var $parenturl;
		var $burl;
		var $altbg = true;

		function temp($idp)
			{
			global $babBody;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->disabled = bab_translate("Section disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->topics = bab_translate("Number of topics");
			$this->topcats = bab_translate("Number of topics categories");
			$this->db = $GLOBALS['babDB'];
			$req = "select c.* 
				FROM 
					".BAB_TOPICS_CATEGORIES_TBL." c, 
					".BAB_TOPCAT_ORDER_TBL." o 
				WHERE 
					id_dgowner=".$this->db->quote($babBody->currentAdmGroup)." 
					AND c.id=o.id_topcat 
					AND c.id_parent=".$this->db->quote($idp)." 
					AND type='1' 
				ORDER BY o.ordering
				";
			
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->idp = $idp;

			if( $idp != 0)
				{
				$res = $this->db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id='".$idp."'");
				while($arr = $this->db->db_fetch_array($res))
					{
					if( $arr['id_parent'] == 0 )
						break;
					$this->arrparents[] = $arr['id_parent'];
					$res = $this->db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id='".$arr['id_parent']."'");
					}
				$this->arrparents[] = 0;
				$this->arrparents = array_reverse($this->arrparents);	
				$this->arrparents[] = $idp;
				}
			$this->countparents = count($this->arrparents);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$this->arr['id']."&idp=".$this->idp;
				$r = $this->db->db_fetch_array($this->db->db_query("select count(*) as total from ".BAB_TOPICS_TBL." where id_cat='".$this->arr['id']."'"));
				$this->topcount = $r['total'];
				$this->topcounturl = $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$this->arr['id'];
				if( $this->arr['enabled'] == "N")
					$this->catchecked = "checked";
				else
					$this->catchecked = "";
				$r = $this->db->db_fetch_array($this->db->db_query("select count(*) as total from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='".$this->arr['id']."'"));
				$this->topcatcount = $r['total'];
				$this->topcatcounturl = $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->countparents)
				{
				if( $this->arrparents[$i] == 0 )
					$this->parentval = bab_translate("Top");
				else
					$this->parentval = bab_getTopicCategoryTitle($this->arrparents[$i]);
				$this->parenturl = $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$this->arrparents[$i];
				if( $i == $this->countparents - 1 )
					$this->burl = false;
				else
					$this->burl = true;
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idp);
	$babBody->babecho(	bab_printTemplate($temp, "topcats.html", "topcatslist"));
	return $temp->count;
	}


function orderTopcat($idp)
	{
	global $babBody;
	class temp
		{		

		var $sorta;
		var $sortd;
		var $idp;

		function temp($idp)
			{
			global $babBody, $BAB_SESS_USERID;
			if( $idp == 0 )
				$catname = bab_translate("Top");
			else
				$catname = bab_getTopicCategoryTitle($idp);

			$this->idp = $idp;
			$this->catname = "---- ".$catname." ----";
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->sorta = bab_translate("Sort ascending");
			$this->sortd = bab_translate("Sort descending");
			$this->create = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			if( $idp == 0 && $babBody->isSuperAdmin )
				$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where id_parent='0' order by ordering asc";
			else
				$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$idp."' order by ordering asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				if( $arr['type'] == 1)
					$this->topicval = bab_getTopicCategoryTitle($arr['id_topcat']);
				else if( $arr['type'] == 2)
					$this->topicval = bab_getCategoryTitle($arr['id_topcat']);
				else
					$this->topicval = "";

				$this->topicid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($idp);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp,"topcats.html", "topcatorder"));
	return $temp->count;
	}


function addTopCat($name, $description, $benabled, $template, $disptmpl, $topcatid)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
		}

	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where title='".$db->db_escape_string($name)."' and id_parent='".$db->db_escape_string($topcatid)."' and id_dgowner='".$db->db_escape_string($babBody->currentAdmGroup)."'");
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This topic category already exists");
		}
	else
		{
		$req = "insert into ".BAB_TOPICS_CATEGORIES_TBL." (title, description, enabled, template, id_dgowner, id_parent, display_tmpl) VALUES (
		'" .$db->db_escape_string($name). "',
		'" . $db->db_escape_string($description). "',
		'" . $db->db_escape_string($benabled). "', 
		'" . $db->db_escape_string($template). "',
		'" . $db->db_escape_string($babBody->currentAdmGroup). "', 
		'" . $db->db_escape_string($topcatid). "', 
		'" . $db->db_escape_string($disptmpl). "'
		)";
		$db->db_query($req);

		$id = $db->db_insert_id();
		$req = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." so, ".BAB_TOPICS_CATEGORIES_TBL." tc where so.position='0' and so.type='3' and tc.id=so.id_section and tc.id_dgowner='".$db->db_escape_string($babBody->currentAdmGroup)."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		if( empty($arr[0]))
			{
			$req = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." so where so.position='0'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			if( empty($arr[0]))
				$arr[0] = 0;
			}
		$db->db_query("update ".BAB_SECTIONS_ORDER_TBL." set ordering=ordering+1 where position='0' and ordering > '".$db->db_escape_string($arr[0])."'");
		$req = "insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$db->db_escape_string($id). "', '0', '3', '" . $db->db_escape_string(($arr[0]+1)). "')";
		$db->db_query($req);

		$res = $db->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$db->db_escape_string($topcatid)."'");
		$arr = $db->db_fetch_array($res);
		if( isset($arr[0]))
			$ord = $arr[0] + 1;
		else
			$ord = 1;
		$db->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, ordering, id_parent) VALUES ('" .$db->db_escape_string($id). "', '1', '" . $db->db_escape_string($ord). "', '".$db->db_escape_string($topcatid)."')");
		}
	}

function disableTopcats($topcats, $idp)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id_parent='".$idp."'";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($topcats) > 0 && in_array($row['id'], $topcats))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update ".BAB_TOPICS_CATEGORIES_TBL." set enabled='".$enabled."' where id='".$row['id']."'";
		$db->db_query($req);
		}
	}

function saveOrderTopcats($idp, $listtopcats)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];
	
	for($i=0; $i < count($listtopcats); $i++)
		{
		$db->db_query("update ".BAB_TOPCAT_ORDER_TBL." set ordering='".($i+1)."' where id='".$listtopcats[$i]."'");
		}
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "List";

if( !isset($idp))
	$idp = 0;

if( isset($add))
	{
	addTopCat($name, $description, $benabled, $template, $disptmpl, $topcatid);
	$idp = $topcatid;
	}
elseif( isset($tagsman) )
{
	maclGroups();
}
elseif( isset($update))
	{
	if( $update == "disable" )
		disableTopcats($topcats, $idp);
	if( $update == "order" )
		{
		saveOrderTopcats($idp, $listtopcats);
		}
	}

switch($idx)
	{
	case 'tags':
		$babBody->title = bab_translate("Tags");
		$macl = new macl("topcats", "List", 1, "tagsman");
        $macl->addtable( BAB_TAGSMAN_GROUPS_TBL,bab_translate("Who can manage tags?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		if( $idp != 0 || ( $idp == 0 && $babBody->isSuperAdmin ))
			{
			$babBody->addItemMenu("Order", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Order&idp=".$idp);
			}
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create&idp=".$idp);
		$babBody->addItemMenu("tags", bab_translate("Tags"), $GLOBALS['babUrlScript']."?tg=topcats&idx=tags");
		break;
	case "Order":
		orderTopcat($idp);
		$babBody->title = bab_translate("Order a topic category");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		if( $idp != 0 || ( $idp == 0 && $babBody->isSuperAdmin ))
			{
			$babBody->addItemMenu("Order", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Order&idp=".$idp);
			}
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create&idp=".$idp);
		$babBody->addItemMenu("tags", bab_translate("Tags"), $GLOBALS['babUrlScript']."?tg=topcats&idx=tags&idp=".$idp);
		break;
	case "Create":
		topcatCreate($idp);
		$babBody->title = bab_translate("Create a topic category");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		if( $idp != 0 || ( $idp == 0 && $babBody->isSuperAdmin ))
			{
			$babBody->addItemMenu("Order", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Order&idp=".$idp);
			}
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create&idp=".$idp);
		$babBody->addItemMenu("tags", bab_translate("Tags"), $GLOBALS['babUrlScript']."?tg=topcats&idx=tags&idp=".$idp);
		break;
	case "List":
	default:
		topcatsList($idp);
		$babBody->title = bab_translate("topics categories list");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		if( $idp != 0 || ( $idp == 0 && $babBody->isSuperAdmin ))
			{
			$babBody->addItemMenu("Order", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Order&idp=".$idp);
			}
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create&idp=".$idp);
		$babBody->addItemMenu("tags", bab_translate("Tags"), $GLOBALS['babUrlScript']."?tg=topcats&idx=tags&idp=".$idp);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>