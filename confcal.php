<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function categoryCalModify($userid, $id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("You must choose a valid category !!");
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->bgcolor = babTranslate("Color");
			$this->modify = babTranslate("Modify Category");
			$this->db = new db_mysql();
			$req = "select * from categoriescal where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->userid = $userid;
			}
		}

	$temp = new temp($userid, $id);
	$body->babecho(	babPrintTemplate($temp,"confcals.html", "categorycalmodify"));
	}

function resourceCalModify($userid, $id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid resource !!");
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->modify = babTranslate("Modify Resource");
			$this->db = new db_mysql();
			$req = "select * from resourcescal where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->userid = $userid;
			}
		}

	$temp = new temp($userid, $id);
	$body->babecho(	babPrintTemplate($temp,"confcals.html", "resourcecalmodify"));
	}

function categoryCalDelete($userid, $id)
	{
	global $body;
	
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
			$this->message = babTranslate("Are you sure you want to delete this calendar category");
			$this->title = getCategoryCalName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the category with all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=confcal&idx=delcat&category=".$id."&action=Yes&userid=".$userid;
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=confcal&idx=modifycat&item=".$id."&userid=".$userid;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($userid,$id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function resourceCalDelete($userid, $id)
	{
	global $body;
	
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
			$this->message = babTranslate("Are you sure you want to delete this calendar resource");
			$this->title = getResourceCalName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the resource with all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=confcal&idx=delres&resource=".$id."&action=Yes&userid=".$userid;
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=confcal&idx=modifyres&item=".$id."&userid=".$userid;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($userid,$id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function modifyCategoryCal($userid, $oldname, $name, $description, $bgcolor, $id)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("You must provide a name !!");
		return;
		}

	$db = new db_mysql();
	$query = "select * from categoriescal where name='$oldname'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$body->msgerror = babTranslate("The category doesn't exist");
		}
	else
		{
		$query = "update categoriescal set name='$name', description='$description', bgcolor='$bgcolor' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: index.php?tg=confcals&idx=listcat&userid=".$userid);
	}

function modifyResourceCal($userid, $oldname, $name, $description, $id)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("You must provide a name !!");
		return;
		}

	$db = new db_mysql();
	$query = "select * from resourcescal where name='$oldname'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$body->msgerror = babTranslate("The resource doesn't exist");
		}
	else
		{
		$query = "update resourcescal set name='$name', description='$description' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: index.php?tg=confcals&idx=listres&userid=".$userid);
	}

function confirmDeletecategoriescal($userid, $id)
	{
	$db = new db_mysql();

	$req = "select* from categoriescal where id='$id'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	// delete category
	$req = "delete from categoriescal where id='$id'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=confcals&idx=listcat&userid=".$userid);
	}

function confirmDeleteresourcescal($userid, $id)
	{
	$db = new db_mysql();

	$req = "select* from resourcescal where id='$id'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	// delete category
	$req = "delete from resourcescal where id='$id'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=confcals&idx=listres&userid=".$userid);
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
	if( !isUserAdministrator())
		{
		return;
		}
	//array_push($grpid, 1);
	}
else
	{
	$db = new db_mysql();
	$req = "select * from groups where manager='".$userid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		//while( $arr = $db->db_fetch_array($res))
		//	array_push($grpid, $arr[id]);
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
		$body->title = babTranslate("Delete calendar category");
		$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=".$userid);
		$body->addItemMenu("modifycat", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=confcal&idx=modifycat&item=".$item);
		$body->addItemMenu("delcat", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=confcal&idx=delcat&item=".$item."&userid=".$userid);
		$body->addItemMenu("listres", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=".$userid);
		break;
	case "delres":
		resourceCalDelete($userid, $item);
		$body->title = babTranslate("Delete calendar resource");
		$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=".$userid);
		$body->addItemMenu("listres", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=".$userid);
		$body->addItemMenu("modifyres", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=confcal&idx=modifyres&item=".$item);
		$body->addItemMenu("delres", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=confcal&idx=delres&item=".$item."&userid=".$userid);
		break;
	case "modifyres":
		resourceCalModify($userid, $item);
		$body->title = getResourceCalName($item) . " ". babTranslate("resource");
		$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=".$userid);
		$body->addItemMenu("listres", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=".$userid);
		$body->addItemMenu("modifyres", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=confcal&idx=modifyres&item=".$item);
		$body->addItemMenu("delres", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=confcal&idx=delres&item=".$item."&userid=".$userid);
		break;
	case "modifycat":
	default:
		categoryCalModify($userid, $item);
		$body->title = getCategoryCalName($item) . " ". babTranslate("category");
		$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=".$userid);
		$body->addItemMenu("modifycat", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=confcal&idx=modifycat&item=".$item);
		$body->addItemMenu("delcat", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=confcal&idx=delcat&item=".$item."&userid=".$userid);
		$body->addItemMenu("listres", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=".$userid);
		break;
	}

$body->setCurrentItemMenu($idx);

?>