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

function topcatCreate()
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
		var $templatetxt;
		var $templateval;
		var $templateid;

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->enabled = bab_translate("Enabled");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->add = bab_translate("Add");
			$this->templatetxt = bab_translate('Template');
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
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"topcats.html", "topcatcreate"));
	}

function topcatsList()
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

		function temp()
			{
			global $babBody;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->disabled = bab_translate("Disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Disable");
			$this->topics = bab_translate("Number of topics");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$this->arr['id'];
				$r = $this->db->db_fetch_array($this->db->db_query("select count(*) as total from ".BAB_TOPICS_TBL." where id_cat='".$this->arr['id']."'"));
				$this->topcount = $r['total'];
				$this->topcounturl = $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$this->arr['id'];
				if( $this->arr['enabled'] == "N")
					$this->catchecked = "checked";
				else
					$this->catchecked = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "topcats.html", "topcatslist"));
	return $temp->count;
	}


function addTopCat($name, $description, $benabled, $template)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$name = addslashes($name);
		$template = addslashes($template);
		}

	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where title='".$name."'");
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This topic category already exists");
		}
	else
		{
		$req = "insert into ".BAB_TOPICS_CATEGORIES_TBL." (title, description, enabled, template, id_dgowner) VALUES ('" .$name. "', '" . $description. "', '" . $benabled. "', '" . $template. "', '" . $babBody->currentAdmGroup. "')";
		$db->db_query($req);

		$id = $db->db_insert_id();
		$req = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='0'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		$req = "insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$id. "', '0', '3', '" . ($arr[0]+1). "')";
		$db->db_query($req);
		}
	}

function disableTopcats($topcats)
	{
	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_TOPICS_CATEGORIES_TBL."";
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

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "List";

if( isset($add))
	addTopCat($name, $description, $benabled, $template);

if( isset($update))
	{
	if( $update == "disable" )
		disableTopcats($topcats);
	}

switch($idx)
	{
	case "Create":
		topcatCreate();
		$babBody->title = bab_translate("Create a topic category");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create");
		break;
	case "List":
	default:
		if( topcatsList() >  0)
		{
		$babBody->title = bab_translate("topics categories list");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create");
		}
		else
		{
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats&idx=Create");
			exit;
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>