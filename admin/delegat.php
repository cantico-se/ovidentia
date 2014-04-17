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
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";
include_once $GLOBALS['babInstallPath']."utilit/delegincl.php";
include_once $GLOBALS['babInstallPath']."utilit/topincl.php";
include_once $GLOBALS['babInstallPath']."utilit/pathUtil.class.php";
include_once $GLOBALS['babInstallPath']."utilit/path.class.php";



function delgatList($res)
{
	global $babBody;
	class temp
	{

		var $delegtxt;
		var $delegdesctxt;
		var $url;
		var $urltxt;
		var $delegval;
		var $res;
		var $count;
		var $memberstxt;
		var $urlmem;
		var $altbg = true;

		var $addtxt = '';

		function temp($res)
		{
			global $babDB;
			$this->delegtxt			= bab_translate("Delegation");
			$this->delegdesctxt		= bab_translate("Description");
			$this->delegadmintxt	= bab_translate("Managing administrators");
			$this->memberstxt		= bab_translate("Managing administrators");
			$this->grpmtxt			= bab_translate("Managed group");
			$this->sAddCaption		= bab_translate("Add");
			$this->sAddUrl			= bab_toHtml($GLOBALS['babUrlScript']."?tg=delegat&idx=new");


			$this->res = $res;
			$this->count = $babDB->db_num_rows($this->res);
			$this->c= 0;
		}

		function getnext()
		{
			global $babDB;
			static $i = 0;
			if($i < $this->count)
			{
				$this->altbg	= !$this->altbg;
				$arr			= $babDB->db_fetch_array($this->res);
				$this->delegval	= bab_toHtml($arr['description']);
				$this->urltxt	= bab_toHtml($arr['name']);
				$this->color	= bab_toHtml($arr['color'] ? $arr['color'] : 'FFFFFF');
				$this->url		= bab_toHtml($GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$arr['id']);
				$this->urlmem	= bab_toHtml($GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$arr['id']);
				$this->grpmval	= bab_toHtml(bab_Groups::getGroupPathName($arr['id_group'], NULL));
				$this->c++;
				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	$temp = new temp($res);
	$babBody->babecho(	bab_printTemplate($temp, "delegat.html", "delegationlist"));
}


function displayCategoriesListForm()
{
	global $babBody;

	class categoriesListForm
	{
		var $nametxt;
		var $urlname;
		var $url;
		var $desc;
		var $desctxt;
		var $bgcolor;
		var $bgcolortxt;

		var $arr = array();
		var $db;
		var $count;
		var $countcal;
		var $res;
		var $altbg = true;

		function categoriesListForm()
		{
			global $babDB;
			$this->nametxt 			= bab_translate("Name");
			$this->desctxt 			= bab_translate("Description");
			$this->bgcolortxt 		= bab_translate("Color");
			$this->add 				= bab_translate("Add");
			$this->t_delete 		= bab_translate('Delete');
			$this->t_delete_checked	= bab_translate("Delete checked items");
			$this->t_confirm_delete	= bab_translate("Do you want to delete selected items?");
			$this->urladdcat 		= bab_toHtml($GLOBALS['babUrlScript'].'?tg=delegat&idx=displayAddCategorieForm');

			if($delete_category = bab_pp('delete_category'))
			{
				foreach($delete_category as $id_category)
				{
					deleteCategory($id_category);
				}

				Header("Location:". $GLOBALS['babUrlScript']."?tg=delegat&idx=displayCategoriesListForm");
				exit;
			}

			$this->res = $babDB->db_query("select * from ".BAB_DG_CATEGORIES_TBL." ORDER BY name,description ");
			$this->countcal = $babDB->db_num_rows($this->res);
		}

		function getnext()
		{
			global $babDB;
			static $i = 0;
			if($i < $this->countcal)
			{
				$this->altbg		= !$this->altbg;
				$this->arr			= $babDB->db_fetch_array($this->res);
				$this->url 			= bab_toHtml($GLOBALS['babUrlScript']."?tg=delegat&idx=displayModifyCategorieForm&idcat=".$this->arr['id']);
				$this->urlname		= bab_toHtml($this->arr['name']);
				$this->desc 		= bab_toHtml($this->arr['description']);
				$this->bgcolor		= bab_toHtml($this->arr['bgcolor']);
				$this->id_category	= (int) $this->arr['id'];
				$i++;
				return true;
			}
			else
			{
				$i = 0;
				return false;
			}
		}
	}

	$oForm = new categoriesListForm();
	$babBody->babecho(bab_printTemplate($oForm, 'delegat.html', 'categorieslist'));
}


function displayAddCategorieForm($catname, $catdesc, $bgcolor)
{
	global $babBody;
	class calendarsAddCategoryCls
	{
		var $name;
		var $description;
		var $bgcolor;
		var $groupsname;
		var $idgrp;
		var $count;
		var $add;
		var $db;
		var $arrgroups = array();
		var $userid;

		function calendarsAddCategoryCls($catname, $catdesc, $bgcolor)
		{
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->bgcolortxt = bab_translate("Color");
			$this->addtxt = bab_translate("Add Category");
			$this->idcat = '';
			$this->add = 'addCategory';
			$this->tgval = 'delegat';
			$this->name = bab_toHtml($catname);
			$this->desc = bab_toHtml($catdesc);
			$this->bgcolor = bab_toHtml($bgcolor);
			$this->selctorurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=selectcolor&idx=popup&callback=setColor");
		}
	}

	$temp = new calendarsAddCategoryCls($catname, $catdesc, $bgcolor);
	$babBody->babecho( bab_printTemplate($temp, "delegat.html", "categorycreate"));
}


function displayModifyCategorieForm()
{
	global $babBody;
	class odifyCategorieForm
		{
		var $name;
		var $description;
		var $bgcolor;
		var $groupsname;
		var $idgrp;
		var $count;
		var $add;
		var $arrgroups = array();
		var $userid;

		function odifyCategorieForm()
			{
			global $babDB;

			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->bgcolortxt = bab_translate("Color");
			$this->addtxt = bab_translate("Update");

			$this->idcat = $idcat = bab_rp('idcat');
			$catname = bab_rp('catname');
			$catdesc = bab_rp('catdesc');
			$bgcolor = bab_rp('bgcolor');

			$this->add = 'updateCategory';
			$this->tgval = 'delegat';
			$arr = $babDB->db_fetch_array($babDB->db_query("SELECT * FROM ".BAB_DG_CATEGORIES_TBL." WHERE id=".$babDB->quote($idcat)));
			if( !empty($catname))
				{
				$this->name = bab_toHtml($catname);
				}
			else
				{
				$this->name = bab_toHtml($arr['name']);
				}
			if( !empty($catdesc))
				{
				$this->desc = bab_toHtml($catdesc);
				}
			else
				{
				$this->desc = bab_toHtml($arr['description']);
				}

			if( !empty($bgcolor))
				{
				$this->bgcolor = bab_toHtml($bgcolor);
				}
			else
				{
				$this->bgcolor = bab_toHtml($arr['bgcolor']);
				}
			$this->selctorurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=selectcolor&idx=popup&callback=setColor");
			}
		}

	$temp = new odifyCategorieForm();
	$babBody->babecho( bab_printTemplate($temp,"delegat.html", "categorycreate"));
}


function groupDelegatMembers($id)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $fullnameval;
		var $usersbrowurl;
		var $userid;
		var $userstxt;
		var $delusers;

		function temp($id)
			{
			global $babDB;
			$this->id = $id;
			$this->usertxt = bab_translate("User");
			$this->addtxt = bab_translate("Add");
			$this->fullname = bab_translate("Fullname");
			$this->delusers = bab_translate("Delete users");
			$this->checkall = bab_translate("Check all");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->res = $babDB->db_query("select * from ".BAB_DG_ADMIN_TBL." where id_dg=".$id);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->fullnameval = bab_getUserName($arr['id_user']);
				$this->userid = $arr['id_user'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "delegat.html", "delegatmembers"));
	}

function groupDelegatModify($gname, $description, $id = '')
{
	global $babBody;

	class temp
	{
		var $name;
		var $description;
		var $add;
		var $delete;
		var $bdel;
		var $what;
		var $arr = array();
		var $delegitem;
		var $delegitemdesc;
		var $checked;

		var $bCategoriesAvailable	= false;
		var $oResCategories			= null;

		var $sCategoryName			= '';
		var $sCategoryDesc			= '';
		var $iIdCategory			= 0;
		var $sCategoryColor			= '';
		var $iPostedIdCategory		= 0;
		var $sCategorySelected		= '';
		var $sCategoryCaption		= '';

		var $id;

		function temp($gname, $description, $id)
		{
			global $babDB;
			$this->name				= bab_translate("Name");
			$this->description		= bab_translate("Description");
			$this->add				= bab_translate("Record");
			$this->delete			= bab_translate("Delete");
			$this->t_color			= bab_translate("Color");
			$this->alert_msg		= bab_translate("It is necessary to remove all associations with the users groups");
			$this->grp_members		= bab_translate("Managed group");
			$this->functions		= bab_translate("Deputy functions");
			$this->none				= bab_translate("None");
			$this->sCategoryCaption	= bab_translate("Category");
			$this->tcheck			= bab_translate("Check all");
			$this->tuncheck			= bab_translate("Uncheck all");
			$this->sSelectImageCaption	= bab_translate('Select a picture');
			$this->sImageAlreadyUploaded = bab_translate('Associated picture');
			$this->sImageRemove 	= bab_translate('Delete this picture');
			$this->sImageRemoveUrl 	= $GLOBALS['babUrlScript'] . '?tg=delegat&idx=delImg&idDeleg=' .$id;
			$this->sImageRemoveConfirm = bab_translate("This action will remove the image delegation. This action can not be undone.");
			$this->iMaxImgFileSize	= (int) $GLOBALS['babMaxImgFileSize'];
			$this->bUploadPathValid	= is_dir($GLOBALS['babUploadPath']);
			$this->bImageUploadEnable	= (0 !== $this->iMaxImgFileSize && $this->bUploadPathValid);

			$this->bImageAlreadyUploaded = '';
			$uploadPath = new bab_Path($GLOBALS['babUploadPath'],'delegation','image','DG'.$id);
			if($uploadPath->isDir() && $id  != ''){
				foreach($uploadPath as $file){
					if(is_file($file->tostring())){
						$this->bImageAlreadyUploaded = 1;
						$this->sImageURL = $GLOBALS['babUrlScript'] . '?tg=delegation&idx=getImage&iWidth=120&iHeight=90&iIdDeleg=' .$id;
						$this->sImageName = $file->getBasename();
					}
				}
			}

			$this->processDisabledUploadReason();

			$db			= $GLOBALS['babDB'];
			$this->db	= $db;
			$res		= $db->db_query("select * from ".BAB_DG_GROUPS_TBL." where id=".$db->quote($id));
			$this->arr	= $db->db_fetch_array($res);
			$this->id	= $id;

			$iIdCategory = 0;

			if(!empty($this->id))
			{
				$this->idGrp		= $this->arr['id_group'];
				$this->bdel			= true;
				$this->colorvalue	= isset($_POST['color']) ? $_POST['color'] : $this->arr['color'] ;
				$battach			= isset($_POST['battach']) ? $_POST['battach'] : $this->arr['battach'] ;
				$iIdCategory		= $this->arr['iIdCategory'];
			}
			else
			{
				$this->idGrp		= false;
				$this->bdel			= false;
				$this->colorvalue	= isset($_POST['color']) ? $_POST['color'] : '' ;
				$battach			= isset($_POST['battach']) ? $_POST['battach'] : 'N' ;
			}


			$tree = new bab_grptree();
			$this->groups = $tree->getIndentedGroups(NULL);

			//bab_debug($this->groups);

			unset($this->groups[BAB_UNREGISTERED_GROUP]);
			$this->count2 = count($this->groups);


			if($gname != '')
			{
				$this->grpname = bab_toHtml($gname);
				$this->grpdesc = bab_toHtml($description);
			}
			else
			{
				$this->grpname = bab_toHtml($this->arr['name']);
				$this->grpdesc = bab_toHtml($this->arr['description']);
			}

			$this->tgval = "delegat";
			$this->what = "mod";


			global $babDB;
			$this->iPostedIdCategory	= (int) bab_rp('iIdCategory', $iIdCategory);
			$this->oResCategories		= $babDB->db_query("select * from " . BAB_DG_CATEGORIES_TBL);
			$this->bCategoriesAvailable	= (false !== $this->oResCategories && 0 < $babDB->db_num_rows($this->oResCategories));
		}

		function processDisabledUploadReason()
		{
			$this->sDisabledUploadReason = '';
			if(false == $this->bImageUploadEnable)
			{
				$this->sDisabledUploadReason = bab_translate("Loading image is not active because");
				$this->sDisabledUploadReason .= '<UL>';

				if('' == $GLOBALS['babUploadPath'])
				{
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The upload path is not set");
				}
				else if(!is_dir($GLOBALS['babUploadPath']))
				{
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The upload path is not a dir");
				}

				if(0 == $this->iMaxImgFileSize)
				{
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The maximum size for a defined image is zero byte");
				}
				$this->sDisabledUploadReason .= '</UL>';
			}
		}

		function getNextCategory()
		{
			global $babDB;

			$this->sCategoryName		= '';
			$this->iIdCategory			= 0;
			$this->sCategoryColor		= '';
			$this->sCategorySelected	= '';

			if(false !== $this->oResCategories)
			{
				if(false != ($aDatas = $babDB->db_fetch_assoc($this->oResCategories)))
				{
					$this->sCategoryName	= $aDatas['name'];
					$this->sCategoryDesc	= $aDatas['description'];
					$this->iIdCategory		= $aDatas['id'];
					$this->sCategoryColor	= $aDatas['bgcolor'];
					if($this->iPostedIdCategory === (int) $this->iIdCategory)
					{
						$this->sCategorySelected = 'selected="selected"';
					}
					return true;
				}
			}
			return false;
		}

		function getnext()
			{
			global $babDB;
			
			$babDG = bab_getDelegationsObjects();
			
			static $i = 0;
			if( $i < count($babDG))
				{
				$this->delegitem = $babDG[$i][0];
				$this->delegitemdesc = bab_toHtml($babDG[$i][1]);

				if( $this->arr[$babDG[$i][0]] == 'Y')
					$this->checked = 'checked="checked"';
				else
					$this->checked = '';
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextgroup()
		{
			static $i = 0;

			$aDatas = each($this->groups);

			if(false !== $aDatas)
			{
				$this->arrgroups = $aDatas['value'];

				$this->arrgroups['select'] = "";
				if($this->idGrp == $this->arrgroups['id'])
				{
					$this->arrgroups['select'] = 'selected="selected"';
				}

				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	$temp = new temp($gname, $description, $id);
	$babBody->babecho(	bab_printTemplate($temp,"delegat.html", "delegatcreate"));
}


function deleteDelegatGroup($id)
	{
	global $babBody,$babDB;

	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function temp($id)
			{
			global $babDB;
			$this->message = bab_translate("Are you sure you want to delete this delegation group");
			list($this->title) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_DG_GROUPS_TBL." where id='".$id."'"));

			$this->t_delete_all = bab_translate("Delete all objects created in the delegation");
			$this->t_set_to_admin = bab_translate("Attach objects to all site");

			$this->t_confirm = bab_translate("Confirm");
			$this->id = $id;
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"delegat.html", "delegatdelete"));
	}


function addDelegatGroup($name, $description, $color, $delegitems, $iIdCategory)
	{
	global $babBody, $babDB;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( !isset($_POST['group']) || $_POST['group'] == 'NULL')
		{
		$babBody->msgerror = bab_translate("ERROR: You must indicate the delegated group !!");
		return false;
		}

	$res = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL." where name='".$babDB->db_escape_string($name)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This delegation group already exists");
		return false;
		}
	else
		{

		$req1 = "(name, description, color";
		$req2 = "('" .$babDB->db_escape_string($name). "', '" . $babDB->db_escape_string($description). "', '" . $babDB->db_escape_string($color). "'";
		for( $i = 0; $i < count($delegitems); $i++)
			{
			$req1 .= ", ". $babDB->db_escape_string($delegitems[$i]);
			$req2 .= ", 'Y'";
			}

		$group = $_POST['group'] == 'NULL' ? 'NULL' : "'".$babDB->db_escape_string($_POST['group'])."'";

		$req1 .= ",iIdCategory ";
		$req2 .= ", " . "'".$babDB->db_escape_string($iIdCategory)."'";

		$req1 .= ",id_group )";
		$req2 .= ", ".$group." )";
		$babDB->db_query("insert into ".BAB_DG_GROUPS_TBL." ".$req1." VALUES ".$req2);
		$id = $babDB->db_insert_id();

		if (isset($_FILES['delegPicture']))
		{
			$tmp_file = $_FILES['delegPicture']['tmp_name'];
			if( is_uploaded_file($tmp_file) ){
				$type_file = $_FILES['delegPicture']['type'];
	
				if( !strstr($type_file, 'jpg') && !strstr($type_file, 'jpeg') && !strstr($type_file, 'bmp') && !strstr($type_file, 'gif') && !strstr($type_file, 'png') )
				{
					$babBody->msgerror = bab_translate("Invalid image extension");
				}else{
					$uploadPath = new bab_Path($GLOBALS['babUploadPath'],'delegation','image','DG'.$id);
					$uploadPath->createDir();
					$uploadPath->push($_FILES['delegPicture']['name']);
	
					if( !move_uploaded_file($tmp_file, $uploadPath->tostring()) ){
						$babBody->msgerror = bab_translate("The file could not be uploaded");
					}
				}
			}
		}

		if( !bab_addTopicsCategory($name, $description, 'Y', '', '', 0, $id ))
			{
			return false;
			}

		}

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
	}

function bab_deleteImage(){
	$id = bab_gp('idDeleg','');
	if($id == ''){
		return;
	}
	$uploadPath = new bab_Path($GLOBALS['babUploadPath'],'delegation','image','DG'.$id);
	if($uploadPath->isDir()){
		$uploadPath->deleteDir();
	}
}

function modifyDelegatGroup($name, $description, $color, $delegitems, $id, $iIdCategory)
	{
	global $babBody, $babDB;

	$babDG = bab_getDelegationsObjects();
	
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}



	$res = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL."
	where id!='".$babDB->db_escape_string($id)."' and name='".$babDB->db_escape_string($name)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("Group of delegation with the same name already exists!");
		return false;
		}
	else
		{
		$req = "update ".BAB_DG_GROUPS_TBL." set
			name='".$babDB->db_escape_string($name)."',
			description='".$babDB->db_escape_string($description)."',
			color='".$babDB->db_escape_string($color)."',
			iIdCategory='".$babDB->db_escape_string((int) $iIdCategory)."'";
		$cnt = count($delegitems);
		for( $i = 0; $i < count($babDG); $i++)
			{
			if( $cnt > 0 && in_array($babDG[$i][0], $delegitems))
				$req .= ", ". $babDG[$i][0]."='Y'";
			else
				$req .= ", ". $babDG[$i][0]."='N'";
			}

		$group = $_POST['group'] == 'NULL' ? 'NULL' : "'".$babDB->db_escape_string($_POST['group'])."'";

		$req .= ", id_group=".$group;

		$babDB->db_query($req ." where id='".$babDB->db_escape_string($id)."'");

		if (isset($_FILES['delegPicture']['tmp_name']))
		{
			$tmp_file = $_FILES['delegPicture']['tmp_name'];
			if( is_uploaded_file($tmp_file) ){
				$type_file = $_FILES['delegPicture']['type'];
	
				if( !strstr($type_file, 'jpg') && !strstr($type_file, 'jpeg') && !strstr($type_file, 'bmp') && !strstr($type_file, 'gif') && !strstr($type_file, 'png') )
				{
					$babBody->msgerror = bab_translate("Invalid image extension");
				}else{
					$uploadPath = new bab_Path($GLOBALS['babUploadPath'],'delegation','image','DG'.$id);
					if($uploadPath->isDir()){
						$uploadPath->deleteDir();
					}
					$uploadPath->createDir();
					$uploadPath->push($_FILES['delegPicture']['name']);
	
					if( !move_uploaded_file($tmp_file, $uploadPath->tostring()) ){
						$babBody->msgerror = bab_translate("The file could not be uploaded");
					}
				}
			}
		}
	}

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
	exit;
	}

function updateDelegatMembers()
{
	global $babBody;
	$db = &$GLOBALS['babDB'];

	if (!empty($_POST['nuserid']) && !empty($_POST['id']))
	{
	$res = $db->db_query("SELECT COUNT(*) FROM ".BAB_DG_ADMIN_TBL." WHERE id_dg='".$_POST['id']."' AND id_user='".$_POST['nuserid']."'");
	list($n) = $db->db_fetch_array($res);
	if ($n > 0)
		{
		$babBody->msgerror = bab_translate("The user is in the list");
		return false;
		}

	$db->db_query("INSERT INTO ".BAB_DG_ADMIN_TBL." (id_dg,id_user) VALUES ('".$_POST['id']."','".$_POST['nuserid']."')");

	bab_siteMap::clearAll();

	include_once $GLOBALS['babInstallPath'].'utilit/urlincl.php';
	header('location:'.bab_url::request('tg', 'idx', 'id'));
	exit;

	return true;
	}

}

function deleteDelegatMembers()
{
	$db = &$GLOBALS['babDB'];

	if (isset($_POST['users']) && count($_POST['users']) > 0 && !empty($_POST['id']))
	{
	$db->db_query("DELETE FROM ".BAB_DG_ADMIN_TBL." WHERE id_dg='".$_POST['id']."' AND id_user IN('".implode("','",$_POST['users'])."')");
	}



	bab_siteMap::clearAll();

	include_once $GLOBALS['babInstallPath'].'utilit/urlincl.php';
	header('location:'.bab_url::request('tg', 'idx', 'id'));
	exit;
}


function addCategory($catname, $catdesc, $bgcolor)
{
	global $babDB, $babBody;

	if(empty($catname))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
	}

	$oResult = $babDB->db_query("select * from " . BAB_DG_CATEGORIES_TBL . " WHERE name LIKE '" .
		$babDB->db_escape_like($catname) . "'");

	if(false !== $oResult && 0 < $babDB->db_num_rows($oResult))
	{
		$babBody->addError(bab_translate("ERROR: A category with the same name already exit")." !");
		return false;
	}

	$babDB->db_query("insert into ".BAB_DG_CATEGORIES_TBL." (name, description, bgcolor) values ('" .$babDB->db_escape_string($catname). "', '".$babDB->db_escape_string($catdesc)."', '".$babDB->db_escape_string($bgcolor)."')");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=displayCategoriesListForm");
	exit;
}


function updateCategory($idcat, $catname, $catdesc, $bgcolor)
{
	global $babDB, $babBody;

	if(empty($catname))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
	}

	$oResult = $babDB->db_query("select * from " . BAB_DG_CATEGORIES_TBL . " WHERE name LIKE '" .
		$babDB->db_escape_like($catname) . "' AND id NOT IN('" . $idcat . "')");

	if(false !== $oResult && 0 < $babDB->db_num_rows($oResult))
	{
		$babBody->addError(bab_translate("ERROR: A category with the same name already exit")." !");
		return false;
	}

	$babDB->db_query("update ".BAB_DG_CATEGORIES_TBL." set name='".$babDB->db_escape_string($catname)."', description='".$babDB->db_escape_string($catdesc)."', bgcolor='".$babDB->db_escape_string($bgcolor)."' where id='".$babDB->db_escape_string($idcat)."'");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=displayCategoriesListForm");
	exit;
}


function deleteCategory($idcat)
{
	global $babDB, $babBody;
	$babDB->db_query("delete from ".BAB_DG_CATEGORIES_TBL." WHERE id=".$babDB->quote($idcat));
}

function confirmDeleteDelegatGroup($id)
{
	bab_deleteDelegation((int) $id, (0 == $_POST['doaction']) );
	
}

/* main */
if( !bab_isUserAdministrator() )
	{
	$babBody->title = bab_translate("Access denied");
	exit;
	}

if( !isset($idx))
	$idx = "list";

if( isset($add))
	{
	if( isset($submit))
		{
		if( $add == 'mod' )
			{
			if (!empty($_POST['id']))
				{
				if(!modifyDelegatGroup($_POST['gname'], $_POST['description'], $_POST['color'], (isset($_POST['delegitems'])? $_POST['delegitems']: array()), $_POST['id'], bab_pp('iIdCategory')))
					$idx = "mod";
				else
					$idx = 'list';
				}
			else
				{
				if( !addDelegatGroup($_POST['gname'], $_POST['description'], $_POST['color'], (isset($_POST['delegitems'])? $_POST['delegitems']: array()), bab_pp('iIdCategory')))
					$idx = 'new';
				else
					$idx = 'list';
				}
			}

		}
	else if( isset($deleteg) )
		{
		$idx = "gdel";
		}
	}


if (isset($_POST['action']))
switch($_POST['action'])
	{
	case 'add':
		updateDelegatMembers();
		break;
	case 'del':
		deleteDelegatMembers();
		break;
	case 'delete':
		confirmDeleteDelegatGroup($_POST['id']);
		$idx = 'list';
		break;
	case 'addCategory':
		if(!addCategory(bab_rp('catname'), bab_rp('catdesc'), bab_rp('bgcolor')))
		{
			$idx = 'displayAddCategorieForm';
		}
		break;

	case 'updateCategory':
		if(!updateCategory(bab_rp('idcat'), bab_rp('catname'), bab_rp('catdesc'), bab_rp('bgcolor')))
		{
			$idx = 'displayModifyCategorieForm';
		}
		break;
	}


if( $idx == 'list' )
{
	$dgres = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL." order by name asc");
	if( !$dgres || $babDB->db_num_rows($dgres) == 0 )
		$idx = 'new';
}

if( isset($aclupdate))
	{
	include_once $babInstallPath.'admin/acl.php';
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
	exit;
	}


switch($idx)
	{
	case "bg":
		browseGroups_dg($cb);
		exit;
		break;
	case "acl":
		include_once $babInstallPath.'admin/acl.php';
		$babBody->title = bab_translate("ACL delegation");
		$macl = new macl("delegat", "Modify", $id, "aclupdate", false);
        $macl->addtable( BAB_DG_ACL_GROUPS_TBL,bab_translate("ACL to use with this delegation"));
        $macl->babecho();
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("gdel", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=delegat&idx=gdel&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("acl", bab_translate("ACL"), $GLOBALS['babUrlScript']."?tg=delegat&idx=acl&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "gdel":
		deleteDelegatGroup($id);
		$babBody->title = bab_translate("Delete delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("gdel", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=delegat&idx=gdel&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("acl", bab_translate("ACL"), $GLOBALS['babUrlScript']."?tg=delegat&idx=acl&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "mem":
		groupDelegatMembers($_REQUEST['id']);
		$babBody->title = bab_translate("Administrators of delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$_REQUEST['id']);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$_REQUEST['id']);
		$babBody->addItemMenu("acl", bab_translate("ACL"), $GLOBALS['babUrlScript']."?tg=delegat&idx=acl&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "mod":
		if( !isset($gname))	$gname = '';
		if( !isset($description)) $description = '';
		groupDelegatModify($gname, $description, $id);
		$babBody->title = bab_translate("Modify delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("acl", bab_translate("ACL"), $GLOBALS['babUrlScript']."?tg=delegat&idx=acl&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "new":
		if( !isset($gname))	$gname = '';
		if( !isset($description)) $description = '';
		groupDelegatModify($gname, $description);
		$babBody->title = bab_translate("Create delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;

	case 'displayAddCategorieForm':
		displayAddCategorieForm(bab_rp('catname'), bab_rp('catdesc'), bab_rp('bgcolor'));
		$babBody->title = bab_translate("Add a delegation category");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("displayCategoriesListForm", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=delegat&idx=displayCategoriesListForm");
		$babBody->addItemMenu("displayAddCategorieForm", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=delegat&idx=displayAddCategorieForm");
		break;

	case "displayModifyCategorieForm":
		displayModifyCategorieForm();
		$babBody->title = bab_translate("Modify a delegation category");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("displayCategoriesListForm", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=delegat&idx=displayCategoriesListForm");
		$babBody->addItemMenu("displayModifyCategorieForm", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=displayModifyCategorieForm");
		break;

	case 'displayCategoriesListForm':
		displayCategoriesListForm();
		$babBody->title = bab_translate("Categories list");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("displayCategoriesListForm", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=delegat&idx=displayCategoriesListForm");
		break;

	case 'delImg':
		bab_deleteImage();
		Header("Location:". $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".bab_gp('idDeleg'));
		break;

	case "list":
	default:
		delgatList($dgres);
		$babBody->title = bab_translate("Delegations list");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("displayCategoriesListForm", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=delegat&idx=displayCategoriesListForm");
		break;
	}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','AdminDelegations');
?>
