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
include_once 'base.php';
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $babInstallPath.'admin/acl.php';
include_once $babInstallPath.'utilit/dirincl.php';
include_once $babInstallPath.'utilit/forumincl.php';

function modifyForum($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid forum")." !!";
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $nbmsgdisplay;
		var $active;
		var $update;
		var $delete;

		var $db;
		var $arr = array();
		var $arr2 = array();
		var $res;
		var $notification;

		function temp($id)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->update = bab_translate("Update Forum");
			$this->nbmsgdisplay = bab_translate("Messages Per Page");
			$this->moderation = bab_translate("Moderation");
			$this->active = bab_translate("Active");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->notification = bab_translate("Notify moderator");
			$this->delete = bab_translate("Delete");
			$this->show_email_txt = bab_translate("Display user's email address");
			$this->show_authordetails_txt = bab_translate("Display user's personal informations");
			$this->use_flatview_txt = bab_translate("Use flat view");
			$this->allow_moderatorupdate_txt = bab_translate("Allow moderators to modify posts");
			$this->allow_authorupdate_txt = bab_translate("Allow authors to modify their posts");

			$req = "select * from ".BAB_FORUMS_TBL." where id='".$babDB->db_escape_string($id)."'";
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$this->arr['id'] = bab_toHtml($this->arr['id']);
			$this->arr['name'] = bab_toHtml($this->arr['name']);
			$this->arr['description'] = bab_toHtml($this->arr['description']);
			$this->arr['display'] = bab_toHtml($this->arr['display']);
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"forums.html", "forummodify"));
	}

function deleteForum($id)
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
			$this->message = bab_translate("Are you sure you want to delete this forum");
			$this->title = bab_toHtml(bab_getForumName($id));
			$this->warning = bab_translate("WARNING: This operation will delete the forum and all posts"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=forum&idx=Delete&category=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function displayForumFields($id)
	{
	global $babBody;
	class temp
		{
		function temp($id)
			{
			global $babDB, $babBody;
			$this->id = $id;
			$this->infotxt = bab_translate("Specify which fields will be displayed instead of full name");
			$this->listftxt = '---- '.bab_translate("Fields").' ----';
			$this->listdftxt = '---- '.bab_translate("Fields to display").' ----';

			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->update = bab_translate("Update");
			
			$iddir = 0;
			$this->resf = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$babDB->db_escape_string($iddir)."' AND id_field!=5");
			$this->countf = $babDB->db_num_rows($this->resf);
			$this->resfd = $babDB->db_query("select id, id_field from ".BAB_FORUMS_FIELDS_TBL." where id_forum='".$babDB->db_escape_string($id)."' order by field_order asc");
			$this->countfd = $babDB->db_num_rows($this->resfd);
			}

		function getnextf()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->fid = $arr['id_field'];
				if( $this->fid < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
					$this->fieldval = translateDirectoryField($arr['description']);
					}
				else
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($this->fid - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
					$this->fieldval = translateDirectoryField($rr['name']);
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextdf()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countfd)
				{
				$arr = $babDB->db_fetch_array($this->resfd);
				$this->fid = $arr['id_field'];
				if( $this->fid < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
					$this->fieldval = translateDirectoryField($arr['description']);
					}
				else
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($this->fid - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
					$this->fieldval = translateDirectoryField($rr['name']);
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id);
	$babBody->babecho( bab_printTemplate($temp,'forums.html', 'forumdisplay'));
	}
	
	
function updateForum($id, $name, $description, $moderation, $notification, $nbmsgdisplay, $active)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !!";
		return;
		}

	$query = "select * from ".BAB_FORUMS_TBL." where name='".$babDB->db_escape_string($name)."' and id!='".$babDB->db_escape_string($id)."'";	
	$res = $babDB->db_query($query);
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This forum already exists");
		return false;
		}

	if (!is_numeric($nbmsgdisplay) || empty($nbmsgdisplay))
		{
		$nbmsgdisplay = 20;
		}


	$bdisplayemailaddress = bab_rp('bdisplayemailaddress', 'N');
	$bdisplayemailaddress = $bdisplayemailaddress == 'Y'? 'Y' : 'N';

	$bdisplayauhtordetails = bab_rp('bdisplayauhtordetails', 'N');
	$bdisplayauhtordetails = $bdisplayauhtordetails == 'Y'? 'Y' : 'N';

	$bflatview = bab_rp('bflatview', 'Y');
	$bflatview = $bflatview == 'N'? 'N' : 'Y';

	$bupdatemoderator = bab_rp('bupdatemoderator', 'Y');
	$bupdatemoderator = $bupdatemoderator == 'N'? 'N' : 'Y';

	$bupdateauthor = bab_rp('bupdateauthor', 'N');
	$bupdateauthor = $bupdateauthor == 'Y'? 'Y' : 'N';

	$query = "UPDATE ".BAB_FORUMS_TBL." set 
		name='".$babDB->db_escape_string($name)."', 
		description='".$babDB->db_escape_string($description)."', 
		moderation='".$babDB->db_escape_string($moderation)."', 
		notification='".$babDB->db_escape_string($notification)."', 
		display='".$babDB->db_escape_string($nbmsgdisplay)."', 
		active='".$babDB->db_escape_string($active)."', 
		bdisplayemailaddress='".$babDB->db_escape_string($bdisplayemailaddress)."', 
		bdisplayauhtordetails='".$babDB->db_escape_string($bdisplayauhtordetails)."', 
		bflatview='".$babDB->db_escape_string($bflatview)."', 
		bupdatemoderator='".$babDB->db_escape_string($bupdatemoderator)."', 
		bupdateauthor='".$babDB->db_escape_string($bupdateauthor)."' 
	where 
		id = '".$babDB->db_escape_string($id)."'";

	$babDB->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums&idx=List");
	}

function confirmDeleteForum($id)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteForum($id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums");
	}

function updateForumFields($id, $listfd)
	{
	global $babDB;
	$babDB->db_query("delete from ".BAB_FORUMS_FIELDS_TBL." where id_forum='".$babDB->db_escape_string($id)."'");
	for($i=0; $i < count($listfd); $i++)
		{
		$babDB->db_query('insert '.BAB_FORUMS_FIELDS_TBL.' (id_forum, id_field, field_order ) values ('.$babDB->quote($id).','.$babDB->quote($listfd[$i]).','.$babDB->quote($i + 1).')');
		}
	}	
	
/* main */
if( !bab_isUserAdministrator() && !bab_isDelegated('forums'))
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}



if(!isset($idx))
	{
	$idx = "Modify";
	}

if( isset($update) && $update == "updateforum")
	{
	if( isset($submit))
		{
		updateForum($item, $fname, $description, $moderation, $notification, $nbmsgdisplay, $active);
		}
	elseif( isset($bdelete))
		{
		$idx = "Delete";
		}
	}

if( isset($update) && $update == "displayfield")
	{
	updateForumFields($item, $listfd);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums&idx=List");
	}
	

if( isset($aclview))
	{
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums&idx=list");
	exit;
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteForum($category);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=forums&idx=List");
	exit;
	}

switch($idx)
	{
	case "rights":
		$babBody->title = bab_getForumName($item) . ": ".bab_translate("List of groups");
		$macl = new macl("forum", "Modify", $item, "aclview");
        $macl->addtable( BAB_FORUMSVIEW_GROUPS_TBL,bab_translate("Who can read posts?"));
		$macl->addtable( BAB_FORUMSPOST_GROUPS_TBL,bab_translate("Who can post?"));
        $macl->addtable( BAB_FORUMSREPLY_GROUPS_TBL,bab_translate("Who can reply?"));
		$macl->addtable( BAB_FORUMSFILES_GROUPS_TBL,bab_translate("Who can join dependent files?"));
		$macl->addtable( BAB_FORUMSMAN_GROUPS_TBL,bab_translate("Who can manage this forum?"));
		$macl->filter(0,0,1,1,1);
		$macl->addtable( BAB_FORUMSNOTIFY_GROUPS_TBL,bab_translate("Who is notified when a new post is registered?"));
		$macl->filter(0,0,1,0,1);
        $macl->babecho();
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("rights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=forum&idx=rights&item=".$item);
		$babBody->addItemMenu("displaydf", bab_translate("Display"), $GLOBALS['babUrlScript']."?tg=forum&idx=displaydf&item=".$item);
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete a forum");
		deleteForum($item);
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("rights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=forum&idx=rights&item=".$item);
		$babBody->addItemMenu("displaydf", bab_translate("Display"), $GLOBALS['babUrlScript']."?tg=forum&idx=displaydf&item=".$item);
		break;

	case "displaydf":
		$babBody->title = bab_getForumName($item) . ": ".bab_translate("display of the identity of the contributor");
		displayForumFields($item);
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("rights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=forum&idx=rights&item=".$item);
		$babBody->addItemMenu("displaydf", bab_translate("Display"), $GLOBALS['babUrlScript']."?tg=forum&idx=displaydf&item=".$item);
		break;
	
	default:
	case "Modify":
		$babBody->title = bab_translate("Modify a forum");
		modifyForum($item);
		$babBody->addItemMenu("List", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forums&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=forum&idx=Modify&item=".$item);
		$babBody->addItemMenu("rights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=forum&idx=rights&item=".$item);
		$babBody->addItemMenu("displaydf", bab_translate("Display"), $GLOBALS['babUrlScript']."?tg=forum&idx=displaydf&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>