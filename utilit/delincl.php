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

function bab_deleteSection($id)
{
	$db = $GLOBALS['babDB'];

	// delete refernce group
	$req = "delete from ".BAB_SECTIONS_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);	

	// delete from ".BAB_SECTIONS_ORDER_TBL
	$req = "delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='$id' and type='2'";
	$res = $db->db_query($req);	

	// delete from BAB_SECTIONS_STATES_TBL
	$req = "delete from ".BAB_SECTIONS_STATES_TBL." where id_section='$id' and type='2'";
	$res = $db->db_query($req);	

	// delete section
	$req = "delete from ".BAB_SECTIONS_TBL." where id='$id'";
	$res = $db->db_query($req);
}

function bab_deleteTopicCategory($id)
{
	$db = $GLOBALS['babDB'];

	// delete from BAB_SECTIONS_ORDER_TBL
	$req = "delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$id."' and type='3'";
	$res = $db->db_query($req);	

	// delete from BAB_TOPCAT_ORDER_TBL
	$req = "delete from ".BAB_TOPCAT_ORDER_TBL." where id_topcat='".$id."' and type='1'";
	$res = $db->db_query($req);	

	// delete from BAB_SECTIONS_STATES_TBL
	$req = "delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$id."' and type='3'";
	$res = $db->db_query($req);	

	// delete all topics/articles/comments
	$res = $db->db_query("select * from ".BAB_TOPICS_TBL." where id_cat='".$id."'");
	while( $arr = $db->db_fetch_array($res))
		bab_confirmDeleteTopic($arr['id']);

	list($idparent) = $db->db_fetch_array($db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$id."'"));
	$db->db_query("update ".BAB_TOPICS_CATEGORIES_TBL."  set id_parent='".$idparent."' where id_parent='".$id."'");

	// delete topic category
	$req = "delete from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$id."'";
	$res = $db->db_query($req);

	return $idparent;
}

function bab_confirmDeleteTopic($id)
	{

	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_ARTICLES_TBL." where id_topic='$id'";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		// delete article and comments
		bab_confirmDeleteArticle($arr['id']);
		}
	$req = "delete from ".BAB_TOPICSCOM_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);
	
	$req = "delete from ".BAB_TOPICSSUB_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	// delete from BAB_TOPCAT_ORDER_TBL
	$req = "delete from ".BAB_TOPCAT_ORDER_TBL." where id_topcat='".$id."' and type='2'";
	$res = $db->db_query($req);	

	$req = "delete from ".BAB_TOPICS_TBL." where id='$id'";
	$res = $db->db_query($req);
	}

function bab_confirmDeleteArticles($items)
{
	$arr = explode(",", $items);
	$cnt = count($arr);
	$db = $GLOBALS['babDB'];
	for($i = 0; $i < $cnt; $i++)
		{
		bab_confirmDeleteArticle($arr[$i]);
		}
}

function bab_confirmDeleteArticle($article)
	{
	include_once $GLOBALS['babInstallPath']."utilit/imgincl.php";
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	// delete comments
	$db = $GLOBALS['babDB'];
	$req = "delete from ".BAB_COMMENTS_TBL." where id_article='".$article."'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$article."'";
	$res = $db->db_query($req);

	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$article."'"));
	deleteImages($arr['head'], $article, "art");
	deleteImages($arr['body'], $article, "art");
	
	if( $arr['idfai'] != 0 )
		deleteFlowInstance($arr['idfai']);
	// delete article
	$req = "delete from ".BAB_ARTICLES_TBL." where id='".$article."'";
	$res = $db->db_query($req);
	}

function bab_deleteComments($com)
	{
	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_COMMENTS_TBL." where id_parent='".$com."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res))
		{
		while( $arr = $db->db_fetch_array($res))
			{
			bab_deleteComments($arr['id']);
			}
		}

	$arr = $db->db_fetch_array($db->db_query("select idfai from ".BAB_COMMENTS_TBL." where id='".$com."'"));
	if( $arr['idfai'] != 0)
		{
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		deleteFlowInstance($arr['idfai']);
		}
	$req = "delete from ".BAB_COMMENTS_TBL." where id='".$com."'";
	$res = $db->db_query($req);	
	}

function bab_deleteApprobationSchema($id)
{
	$db = $GLOBALS['babDB'];

	// delete schema
	$req = "delete from ".BAB_FLOW_APPROVERS_TBL." where id='".$id."'";
	$res = $db->db_query($req);
}

function bab_deleteForum($id)
{
	$db = $GLOBALS['babDB'];
	// delete all posts
	$res = $db->db_query("select id from ".BAB_THREADS_TBL." where forum='".$id."'");
	while( $arr = $db->db_fetch_array($res))
		{
		$db->db_query("delete from ".BAB_POSTS_TBL." where id_thread='".$arr['id']."'");
		}

	$db->db_query("delete from ".BAB_THREADS_TBL." where forum='".$id."'");

	$req = "delete from ".BAB_FORUMSVIEW_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);
	
	$req = "delete from ".BAB_FORUMSPOST_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_FORUMSREPLY_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_FORUMS_TBL." where id='$id'";
	$res = $db->db_query($req);
}

function bab_deleteFaq($id)
{
	$db = $GLOBALS['babDB'];

	// delete questions/responses for this faq
	$req = "delete from ".BAB_FAQQR_TBL." where idcat='$id'";
	$res = $db->db_query($req);	

	// delete faq from groups
	$req = "delete from ".BAB_FAQCAT_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);	

	// delete faq
	$req = "delete from ".BAB_FAQCAT_TBL." where id='$id'";
	$res = $db->db_query($req);
}

function bab_deleteUploadDir($path)
	{
	if (file_exists($path))
		{
		if (is_dir($path))
			{
			$handle = opendir($path);
		    while($filename = readdir($handle))
				{
		        if ($filename != "." && $filename != "..")
					{
			        bab_deleteUploadDir($path."/".$filename);
					}
				}
			closedir($handle);
			@rmdir($path);
			} 
		else
			{
			@unlink($path);
			}
		}
	}


function bab_deleteUploadUserFiles($gr, $id)
	{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
	$pathx = bab_getUploadFullPath($gr, $id);
	$babDB->db_query("delete from ".BAB_FILES_TBL." where id_owner='".$id."' and bgroup='".$gr."'");
	@bab_deleteUploadDir($pathx);
	}


function bab_deleteFolder($id)
{
	global $babDB;
	// delete files owned by this group
	$res = $babDB->db_query("select id from ".BAB_FILES_TBL." where id_owner='".$fid."' and bgroup='Y'");
	while( $arr = $babDB->db_fetch_array($res))
	{
	$babDB->db_query("delete from ".BAB_FM_FILESVER_TBL." where id_file='".$arr['id']."'");
	$babDB->db_query("delete from ".BAB_FM_FILESLOG_TBL." where id_file='".$arr['id']."'");
	}
	
	bab_deleteUploadUserFiles("Y", $fid);

	$res = $babDB->db_query("select id from ".BAB_FM_FIELDS_TBL." where id_folder='".$fid."'");
	while( $arr = $babDB->db_fetch_array($res))
		$babDB->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_field='".$arr['id']."'");

	$babDB->db_query("delete from ".BAB_FM_FIELDS_TBL." where id_folder='".$fid."'");

	// delete folder
	$babDB->db_query("delete from ".BAB_FM_FOLDERS_TBL." where id='".$fid."'");
}

function bab_deleteLdapDirectory($id)
{
	global $babDB;
	$babDB->db_query("delete from ".BAB_LDAP_DIRECTORIES_TBL." where id='".$id."'");
}

function bab_deleteDbDirectory($id)
{
	global $babDB;
	$arr = $babDB->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
	if( $arr['id_group'] != 0)
		return;
	$babDB->db_query("delete from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$id."'");
	$babDB->db_query("delete from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".$id."'");
	$babDB->db_query("delete from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'");
}

function bab_deleteGroup($id)
{
	if( $id <= 3)
		return;

	$db = $GLOBALS['babDB'];
	$db->db_query("delete from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_group='".$id."'");	
	$db->db_query("delete from ".BAB_TOPICSCOM_GROUPS_TBL." where id_group='".$id."'");	
	$db->db_query("delete from ".BAB_TOPICSSUB_GROUPS_TBL." where id_group='".$id."'");	
	$db->db_query("delete from ".BAB_SECTIONS_GROUPS_TBL." where id_group='".$id."'");	
	$db->db_query("delete from ".BAB_FAQCAT_GROUPS_TBL." where id_group='".$id."'");	
	$db->db_query("delete from ".BAB_USERS_GROUPS_TBL." where id_group='".$id."'");	
	$db->db_query("delete from ".BAB_CATEGORIESCAL_TBL." where id_group='".$id."'");
	$db->db_query("delete from ".BAB_FMDOWNLOAD_GROUPS_TBL." where id_group='".$id."'");	
	$db->db_query("delete from ".BAB_FMUPDATE_GROUPS_TBL." where id_group='".$id."'");	
	$db->db_query("delete from ".BAB_FMUPLOAD_GROUPS_TBL." where id_group='".$id."'");	

	$res = $db->db_query("select * from ".BAB_RESOURCESCAL_TBL." where id_group='".$id."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		
		while( $arr = $db->db_fetch_array($res))
			{
			$res2 = $db->db_query("select * from ".BAB_CALENDAR_TBL." where owner='".$arr['id']."' and type='3'");
			$r = $db->db_fetch_array($res2);

			// delete resource's events
			$db->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id_cal='".$r['id']."'");	

			// delete resource from calendar
			$db->db_query("delete from ".BAB_CALENDAR_TBL." where owner='".$arr['id']."' and type='3'");	

			// delete resource
			$db->db_query("delete from ".BAB_RESOURCESCAL_TBL." where id_group='".$id."'");
			}
		}

	$res = $db->db_query("select * from ".BAB_CALENDAR_TBL." where owner='".$id."' and type='2'");
	$arr = $db->db_fetch_array($res);

	// delete group's events
	$db->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id_cal='".$arr['id']."'");	

	// delete user from calendar
	$db->db_query("delete from ".BAB_CALENDAR_TBL." where owner='".$id."' and type='2'");	

	// delete user from BAB_MAIL_DOMAINS_TBL
	$db->db_query("delete from ".BAB_MAIL_DOMAINS_TBL." where owner='".$id."' and bgroup='Y'");	

    // delete group
	$db->db_query("delete from ".BAB_GROUPS_TBL." where id='".$id."'");
	bab_callAddonsFunction('onGroupDelete', $id);
}
?>
