<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function categoryCalModify($userid, $id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("You must choose a valid category !!");
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $bgcolor;
		var $modify;

		var $db;
		var $arr = array();
		var $res;
		var $userid;

		function temp($userid, $id)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->bgcolor = bab_translate("Color");
			$this->modify = bab_translate("Modify Category");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->userid = $userid;
			}
		}

	$temp = new temp($userid, $id);
	$babBody->babecho(	bab_printTemplate($temp,"confcals.html", "categorycalmodify"));
	}

function resourceCalModify($userid, $id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid resource !!");
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $modify;

		var $db;
		var $arr = array();
		var $res;

		function temp($userid, $id)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->modify = bab_translate("Modify Resource");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_RESOURCESCAL_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->userid = $userid;
			}
		}

	$temp = new temp($userid, $id);
	$babBody->babecho(	bab_printTemplate($temp,"confcals.html", "resourcecalmodify"));
	}

function categoryCalDelete($userid, $id)
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

		function temp($userid,$id)
			{
			$this->message = bab_translate("Are you sure you want to delete this calendar category");
			$this->title = bab_getCategoryCalName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the category with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=confcal&idx=delcat&category=".$id."&action=Yes&userid=".$userid;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=confcal&idx=modifycat&item=".$id."&userid=".$userid;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($userid,$id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function resourceCalDelete($userid, $id)
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

		function temp($userid,$id)
			{
			$this->message = bab_translate("Are you sure you want to delete this calendar resource");
			$this->title = bab_getResourceCalName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the resource with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=confcal&idx=delres&resource=".$id."&action=Yes&userid=".$userid;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=confcal&idx=modifyres&item=".$id."&userid=".$userid;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($userid,$id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function modifyCategoryCal($userid, $oldname, $name, $description, $bgcolor, $id)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name !!");
		return;
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_CATEGORIESCAL_TBL." where name='$oldname'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("The category doesn't exist");
		}
	else
		{
		$query = "update ".BAB_CATEGORIESCAL_TBL." set name='$name', description='$description', bgcolor='$bgcolor' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
	}

function modifyResourceCal($userid, $oldname, $name, $description, $id)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name !!");
		return;
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_RESOURCESCAL_TBL." where name='$oldname'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("The resource doesn't exist");
		}
	else
		{
		$query = "update ".BAB_RESOURCESCAL_TBL." set name='$name', description='$description' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
	}

function confirmDeletecategoriescal($userid, $id)
	{
	$db = $GLOBALS['babDB'];

	$req = "select* from ".BAB_CATEGORIESCAL_TBL." where id='$id'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	// delete category
	$req = "delete from ".BAB_CATEGORIESCAL_TBL." where id='$id'";
	$res = $db->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
	}

function confirmDeleteresourcescal($userid, $id)
	{
	$db = $GLOBALS['babDB'];

	$req = "select* from ".BAB_RESOURCESCAL_TBL." where id='$id'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	// delete category
	$req = "delete from ".BAB_RESOURCESCAL_TBL." where id='$id'";
	$res = $db->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
	}

/* main */
if( !isset($idx))
	$idx = "modifycat";

if( isset($modify) && $modify == "modcat")
	modifyCategoryCal($userid, $oldname, $name, $description, $bgcolor, $item);

if( isset($modify) && $modify == "modres")
	modifyResourceCal($userid, $oldname, $name, $description, $item);

if( isset($action) && $action == "Yes")
	{
	if( isset($category))
		confirmDeletecategoriescal($userid,$category);
	if( isset($resource))
		confirmDeleteresourcescal($userid,$resource);
	}

$grpid = array();
if( $userid == 0 )
	{
	if( !bab_isUserAdministrator())
		{
		return;
		}
	//array_push($grpid, 1);
	}
else
	{
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_GROUPS_TBL." where manager='".$userid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		//while( $arr = $db->db_fetch_array($res))
		//	array_push($grpid, $arr['id']);
		}
	else
		{
		return;
		}
	}

switch($idx)
	{
	case "delcat":
		categoryCalDelete($userid, $item);
		$babBody->title = bab_translate("Delete calendar category");
		$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("modifycat", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=confcal&idx=modifycat&item=".$item);
		$babBody->addItemMenu("delcat", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=confcal&idx=delcat&item=".$item."&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		break;
	case "delres":
		resourceCalDelete($userid, $item);
		$babBody->title = bab_translate("Delete calendar resource");
		$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		$babBody->addItemMenu("modifyres", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=confcal&idx=modifyres&item=".$item);
		$babBody->addItemMenu("delres", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=confcal&idx=delres&item=".$item."&userid=".$userid);
		break;
	case "modifyres":
		resourceCalModify($userid, $item);
		$babBody->title = bab_getResourceCalName($item) . " ". bab_translate("resource");
		$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		$babBody->addItemMenu("modifyres", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=confcal&idx=modifyres&item=".$item);
		$babBody->addItemMenu("delres", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=confcal&idx=delres&item=".$item."&userid=".$userid);
		break;
	case "modifycat":
	default:
		categoryCalModify($userid, $item);
		$babBody->title = bab_getCategoryCalName($item) . " ". bab_translate("category");
		$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("modifycat", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=confcal&idx=modifycat&item=".$item);
		$babBody->addItemMenu("delcat", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=confcal&idx=delcat&item=".$item."&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>