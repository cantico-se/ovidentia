<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";

function getAddonName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select title from ".BAB_ADDONS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function addonCreate($what, $name, $description, $folder, $initfile, $usload, $asload, $ucreate, $udelete, $gcreate, $gdelete, $sload, $bsection, $benabled, $id)
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

		function temp($what, $name, $description, $folder, $initfile, $usload, $asload, $ucreate, $udelete, $gcreate, $gdelete, $sload, $bsection, $benabled, $id)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->enabled = bab_translate("Enabled");
			$this->folder = bab_translate("Folder");
			$this->initfile = bab_translate("Initialization file");
			$this->usload = bab_translate("User section load");
			$this->asload = bab_translate("Administration section load");
			$this->ucreate = bab_translate("User created");
			$this->udelete = bab_translate("User deleted");
			$this->gcreate = bab_translate("Group created");
			$this->gdelete = bab_translate("Group deleted");
			$this->section = bab_translate("Use section");
			$this->enabled = bab_translate("Enabled");
			$this->delete = bab_translate("Delete");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			if( $what == "mod")
				$this->add = bab_translate("Update");
			else
				{
				$what = "add";
				$this->add = bab_translate("Add");
				}
			$this->what = $what;
			$this->id = $id == "" ? 0: $id;
			$this->vname = $name == ""? "": $name;
			$this->vdescription = $description == ""? "": $description;
			$this->vinitfile = $initfile == ""? "": $initfile;
			$this->vfolder = $folder == ""? "": $folder;
			$this->vusload = $usload == ""? "getUserSectionMenus": $usload;
			$this->vasload = $asload == ""? "getAdminSectionMenus": $asload;
			$this->vucreate = $ucreate == ""? "onUserCreate": $ucreate;
			$this->vudelete = $udelete == ""? "onUserDelete": $udelete;
			$this->vgcreate = $gcreate == ""? "onGroupCreate": $gcreate;
			$this->vgdelete = $gdelete == ""? "onGroupDelete": $gdelete;
			$this->vsload = $sload == ""? "onSectionCreate": $sload;
			if( $bsection == "Y")
				{
				$this->bsecyes = "selected";
				$this->bsecno = "";
				}
			else
				{
				$this->bsecno = "selected";
				$this->bsecyes = "";
				}
			if( $benabled == "N")
				{
				$this->benano = "selected";
				$this->benayes = "";
				}
			else
				{
				$this->benayes = "selected";
				$this->benano = "";
				}
			}
		}

	$temp = new temp($what, $name, $description, $folder, $initfile, $usload, $asload, $ucreate, $udelete, $gcreate, $gdelete, $sload, $bsection, $benabled, $id);
	$babBody->babecho(	bab_printTemplate($temp,"addons.html", "addoncreate"));
	}

function addonsList()
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
		var $view;
		var $viewurl;

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->disabled = bab_translate("Disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->view = bab_translate("View");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ADDONS_TBL."";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=addons&idx=mod&item=".$this->arr['id'];
				$this->viewurl = $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$this->arr['id'];
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
	$babBody->babecho(	bab_printTemplate($temp, "addons.html", "addonslist"));
	}

function addonDelete($id)
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

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this add-on");
			$this->title = getAddonName($id);
			$this->warning = bab_translate("WARNING: This operation will delete add-on with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=addons&idx=del&item=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=addons&idx=mod&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function addonModify($id)
	{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_ADDONS_TBL." where id='".$id."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		addonCreate("mod", $arr['title'], $arr['description'], $arr['folder'], $arr['initfile'], $arr['usload'], $arr['asload'], $arr['ucreate'], $arr['udelete'], $arr['gcreate'], $arr['gdelete'], $arr['sload'], $arr['section'], $arr['enabled'], $id);	
		}
	}

function addAddon($name, $description, $folder, $initfile, $usload, $asload, $ucreate, $udelete, $gcreate, $gdelete, $sload, $bsection, $benabled)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select * from ".BAB_ADDONS_TBL." where title='".$name."'");
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This addon already exists");
		return false;
		}
	else
		{
		if(!get_cfg_var("magic_quotes_gpc"))
			{
			$description = addslashes($description);
			$name = addslashes($name);
			}
		$req = "insert into ".BAB_ADDONS_TBL." (title, description, folder, initfile, usload, asload, ucreate, udelete, gcreate, gdelete, sload, section, enabled) VALUES ('" .$name. "', '" . $description. "', '" . $folder. "', '" . $initfile. "', '" . $usload. "', '" . $asload. "', '" . $ucreate. "', '" . $udelete. "', '" . $gcreate. "', '" . $gdelete. "', '" . $sload. "', '" . $section. "', '" . $benabled. "')";
		$db->db_query($req);
		if( $section == "Y")
			{
			$id = $db->db_insert_id();
			$arr = $db->db_fetch_array($db->db_query("select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='0'"));
			$req = "insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$id. "', '0', '4', '" . ($arr[0]+1). "')";
			$db->db_query($req);
			}
		}
	return true;
	}

function modifyAddon($what, $name, $description, $folder, $initfile, $usload, $asload, $ucreate, $udelete, $gcreate, $gdelete, $sload, $bsection, $benabled, $item)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select section from ".BAB_ADDONS_TBL." where id='".$item."'");
	if( $db->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("ERROR: This add-on doesn't exist");
		return false;
		}
	else
		{
		$arr = $db->db_fetch_array($res);
		if(!get_cfg_var("magic_quotes_gpc"))
			{
			$description = addslashes($description);
			$name = addslashes($name);
			}
		$req = "update ".BAB_ADDONS_TBL." set title='".$name."', description='".$description."', folder='".$folder."', initfile='".$initfile."', usload='".$usload."', asload='".$asload."', ucreate='".$ucreate."', udelete='".$udelete."', gcreate='".$gcreate."', gdelete='".$gdelete."', sload='".$sload."', section='".$bsection."', enabled='".$benabled."' where id='".$item."'";
		$db->db_query($req);
		if( $arr['section'] == "N" && $bsection == "Y")
			{
			$arr = $db->db_fetch_array($db->db_query("select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='0'"));
			$req = "insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$item. "', '0', '4', '" . ($arr[0]+1). "')";
			$db->db_query($req);
			}
		else if( $arr['section'] == "Y" && $bsection == "N")
			{
			$db->db_query("delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$item."' and type='4'");
			}
		return true;
		}
	}

function disableAddons($addons)
	{
	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_ADDONS_TBL."";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($addons) > 0 && in_array($row['id'], $addons))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update ".BAB_ADDONS_TBL." set enabled='".$enabled."' where id='".$row['id']."'";
		$db->db_query($req);
		}
	}

function confirmDeleteAddon($id)
	{
	$db = $GLOBALS['babDB'];

	// delete from BAB_SECTIONS_ORDER_TBL
	$req = "delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$id."' and type='4'";
	$res = $db->db_query($req);	

	// delete from BAB_ADDONS_TBL
	$req = "delete from ".BAB_ADDONS_TBL." where id='".$id."'";
	$res = $db->db_query($req);	
	}

/* main */
if( !isset($idx))
	$idx = "list";

if( isset($what))
	{
	if( $what == "add")
		{
		if(!addAddon($name, $description, $folder, $initfile, $usload, $asload, $ucreate, $udelete, $gcreate, $gdelete, $sload, $bsection, $benabled))
			{
			$idx = "create";
			}
		}
	else if( $what == "mod")
		{
		if( isset($bdelete))
			$idx = "del";
		else
			if(!modifyAddon($what, $name, $description, $folder, $initfile, $usload, $asload, $ucreate, $udelete, $gcreate, $gdelete, $sload, $bsection, $benabled, $item))
				$idx = "create";
		}
	}

if( isset($update))
	{
	if( $update == "disable" )
		disableAddons($addons);
	}

if( isset($acladd))
	{
	aclUpdate($table, $item, $groups, $what);
	}

if( isset($action) && $action == "Yes")
	{
	if($idx == "del")
		{
		confirmDeleteAddon($item);
		$idx = "list";
		}
	}

switch($idx)
	{
	case "del":
		$babBody->title = bab_translate("Delete Add-on");
		addonDelete($item);
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=addons&idx=create");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=addons&idx=mod&item=".$item);
		$babBody->addItemMenu("del", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=addons&idx=del&item=".$item);
		break;
	case "mod":
		$babBody->title = bab_translate("Modify Add-on")." :".getAddonName($item);
		addonModify($item);
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=addons&idx=create");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=addons&idx=mod&item=".$item);
		$babBody->addItemMenu("view", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$item);
		break;
	case "view":
		$babBody->title = bab_translate("Access to Add-on")." :".getAddonName($item);
		aclGroups("addons", "mod", BAB_ADDONS_GROUPS_TBL, $item, "acladd");
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=addons&idx=create");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=addons&idx=mod&item=".$item);
		$babBody->addItemMenu("view", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=addons&idx=view&item=".$item);
		break;

	case "create":
		$babBody->title = bab_translate("Create an add-on");
		addonCreate($what, $name, $description, $folder, $initfile, $usload, $asload, $ucreate, $udelete, $gcreate, $gdelete, $sload, $bsection, $benabled, $item);
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=addons&idx=create");
		break;

	case "list":
	default:
		addonsList();
		$babBody->title = bab_translate("Add-ons list");
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=addons&idx=list");
		$babBody->addItemMenu("create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=addons&idx=create");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>