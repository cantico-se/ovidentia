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
/**
* @internal SEC1 NA 14/12/2006 FULL
*/
include_once 'base.php';
include_once $GLOBALS['babInstallPath'].'utilit/imgincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/afincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/artincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/forumincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
include_once $GLOBALS['babInstallPath'].'admin/acl.php';

function bab_deleteSection($id)
{
	global $babDB;

	// delete refernce group
	aclDelete(BAB_SECTIONS_GROUPS_TBL, $id);

	// delete from ".BAB_SECTIONS_ORDER_TBL
	$req = "delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$babDB->db_escape_string($id)."' and type='2'";
	$res = $babDB->db_query($req);	

	// delete from BAB_SECTIONS_STATES_TBL
	$req = "delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$babDB->db_escape_string($id)."' and type='2'";
	$res = $babDB->db_query($req);	

	// delete section
	$req = "delete from ".BAB_SECTIONS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);
}

function bab_deleteTopicCategory($id)
{
	global $babDB;

	// delete from BAB_SECTIONS_ORDER_TBL
	$req = "delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$babDB->db_escape_string($id)."' and type='3'";
	$res = $babDB->db_query($req);	

	// delete from BAB_TOPCAT_ORDER_TBL
	$req = "delete from ".BAB_TOPCAT_ORDER_TBL." where id_topcat='".$babDB->db_escape_string($id)."' and type='1'";
	$res = $babDB->db_query($req);	

	// delete from BAB_SECTIONS_STATES_TBL
	$req = "delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$babDB->db_escape_string($id)."' and type='3'";
	$res = $babDB->db_query($req);	

	// delete all topics/articles/comments
	$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id_cat='".$babDB->db_escape_string($id)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		bab_confirmDeleteTopic($arr['id']);
		}

	list($idparent) = $babDB->db_fetch_array($babDB->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
	$babDB->db_query("update ".BAB_TOPICS_CATEGORIES_TBL."  set id_parent='".$babDB->db_escape_string($idparent)."' where id_parent='".$babDB->db_escape_string($id)."'");

	// delete topic category
	list($iIdDelegation) = $babDB->db_fetch_array($babDB->db_query("SELECT id_dgowner from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
	$req = "delete from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);

	
	require_once dirname(__FILE__) . '/artincl.php';
	
	$oPubPathsEnv = new bab_PublicationPathsEnv();
	if($oPubPathsEnv->setEnv($iIdDelegation))
	{
		bab_deleteUploadDir($oPubPathsEnv->getCategoryImgPath($id));
		bab_deleteImageCategory($id);
	}
	return $idparent;
}

function bab_confirmDeleteTopic($id)
{
	global $babDB;
	$req = "select id from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);
	while( $arr = $babDB->db_fetch_array($res))
		{
		bab_confirmDeleteArticle($arr['id']);
		}

	$req = "select id, idfai from ".BAB_ART_DRAFTS_TBL." where id_topic='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		while( $arr = $babDB->db_fetch_array($res))
			{
			if( $arr['idfai'] != 0 )
				{
				deleteFlowInstance($arr['idfai']);
				}
			}
		}
	
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set result='0', idfai='0', id_topic='0' where id_topic='".$babDB->db_escape_string($id)."'");

	aclDelete(BAB_TOPICSCOM_GROUPS_TBL, $id);
	aclDelete(BAB_TOPICSSUB_GROUPS_TBL, $id);
	aclDelete(BAB_TOPICSVIEW_GROUPS_TBL, $id);
	aclDelete(BAB_TOPICSMOD_GROUPS_TBL, $id);
	aclDelete(BAB_TOPICSMAN_GROUPS_TBL, $id);


	// delete from BAB_TOPCAT_ORDER_TBL
	$req = "delete from ".BAB_TOPCAT_ORDER_TBL." where id_topcat='".$babDB->db_escape_string($id)."' and type='2'";
	$res = $babDB->db_query($req);	

	list($iIdCat) = $babDB->db_fetch_array($babDB->db_query("SELECT id_cat from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($id)."'"));
	list($iIdDelegation) = $babDB->db_fetch_array($babDB->db_query("SELECT id_dgowner from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($iIdCat)."'"));
	$req = "delete from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);

	require_once dirname(__FILE__) . '/artincl.php';
	
	$oPubPathsEnv = new bab_PublicationPathsEnv();
	if($oPubPathsEnv->setEnv($iIdDelegation))
	{
		bab_deleteUploadDir($oPubPathsEnv->getTopicImgPath($id));
		bab_deleteImageTopic($id);
	}
}


function bab_deleteDraft($idart)
	{
	global $babDB;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['idfai'] != 0 )
			{
			deleteFlowInstance($arr['idfai']);
			}
		deleteImages($arr['head'], $idart, "draft");
		deleteImages($arr['body'], $idart, "draft");
		bab_deleteDraftFiles($idart);
		$babDB->db_query("delete from ".BAB_ART_DRAFTS_NOTES_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");
		$babDB->db_query("delete from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");

		require_once dirname(__FILE__) . '/tagApi.php';
		$oReferenceMgr	= bab_getInstance('bab_ReferenceMgr');
		$oReference		= bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $idart);
		$oReferenceMgr->removeByReference($oReference);

		require_once dirname(__FILE__) . '/artincl.php';
		
		$oPubPathsEnv = new bab_PublicationPathsEnv();
		$iIdDelegation = 0; //Dummy value
		if($oPubPathsEnv->setEnv($iIdDelegation))
			{
			bab_deleteUploadDir($oPubPathsEnv->getDraftArticleImgPath($idart));
			bab_deleteImageDraftArticle($idart);
			}
		}
	}


function bab_confirmDeleteArticles($items)
{
	global $babDB;
	$arr = explode(",", $items);
	$cnt = count($arr);
	if( $cnt > 0 )
	{
		for($i = 0; $i < $cnt; $i++)
			{
			bab_confirmDeleteArticle($arr[$i]);
			}
	}
}

/**
 * Deletes the specified article and the associated data:
 *  - files
 *  - tags
 *  - images and associated folder
 * Existing drafts are unlinked form the article but are not deleted. 
 *
 * @param int	$article		The article id.
 */
function bab_confirmDeleteArticle($article)
	{
	// delete comments
	global $babDB;
	$req = "delete from ".BAB_COMMENTS_TBL." where id_article='".$babDB->db_escape_string($article)."'";
	$res = $babDB->db_query($req);

	$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$babDB->db_escape_string($article)."'";
	$res = $babDB->db_query($req);

	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'"));
	deleteImages($arr['head'], $article, "art");
	deleteImages($arr['body'], $article, "art");
	
	$res = $babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_article='0' where id_article='".$babDB->db_escape_string($article)."'");

	bab_deleteArticleFiles($article);

	require_once dirname(__FILE__) . '/tagApi.php';
	$oReferenceMgr	= bab_getInstance('bab_ReferenceMgr');
	$oReference		= bab_Reference::makeReference('ovidentia', '', 'articles', 'article', $article);
	$oReferenceMgr->removeByReference($oReference);
	
	// delete article
	$req = "delete from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
	$res = $babDB->db_query($req);

	require_once dirname(__FILE__) . '/artincl.php';
	
	$oPubPathsEnv = new bab_PublicationPathsEnv();
	$iIdDelegation = 0; //Dummy value
	if($oPubPathsEnv->setEnv($iIdDelegation))
		{
		bab_deleteUploadDir($oPubPathsEnv->getArticleImgPath($article));
		bab_deleteImageArticle($article);
		}
	}

function bab_deleteComments($com)
	{
	global $babDB;
	$req = "select id from ".BAB_COMMENTS_TBL." where id_parent='".$babDB->db_escape_string($com)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res))
		{
		while( $arr = $babDB->db_fetch_array($res))
			{
			bab_deleteComments($arr['id']);
			}
		}

	$arr = $babDB->db_fetch_array($babDB->db_query("select idfai from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($com)."'"));
	if( $arr['idfai'] != 0)
		{
		deleteFlowInstance($arr['idfai']);
		}
	$req = "delete from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($com)."'";
	$res = $babDB->db_query($req);	
	}


function bab_deleteComment($com)
	{
	global $babDB;
	$arr = $babDB->db_fetch_array($babDB->db_query("select idfai from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($com)."'"));
	if( $arr['idfai'] != 0)
		{
		deleteFlowInstance($arr['idfai']);
		}
	$res = $babDB->db_query("update ".BAB_COMMENTS_TBL." set id_parent='0' where id_parent='".$babDB->db_escape_string($com)."'");	

	$req = "delete from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($com)."'";
	$res = $babDB->db_query($req);	
	}

function bab_deleteApprobationSchema($id)
{
	global $babDB;

	// delete schema
	$req = "delete from ".BAB_FLOW_APPROVERS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);
}

function bab_deletePostFiles($idforum, $idpost)
{
	$files = bab_getPostFiles($idforum,$idpost);
	foreach($files as $f)
		{
		@unlink($f['path']);
		}
}

/**
 * Deletes a forum thread and all the posts and files linked to it.
 *
 * @param int	$idforum
 * @param int	$idthread
 */
function bab_deleteThread($idforum, $idthread)
{
	global $babDB;

	/* delete all posts owned by this thread */
	$respost = $babDB->db_query("SELECT id from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($idthread)."'");
	while (list($id_post) = $babDB->db_fetch_array($respost))
		{
		bab_deletePostFiles($idforum, $id_post);
		}
	$babDB->db_query("delete from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($idthread)."'");

	// delete this thread
	$babDB->db_query("delete from ".BAB_THREADS_TBL." where id='".$babDB->db_escape_string($idthread)."'");
}

function bab_deleteForum($id)
{
	global $babDB;
	// delete all threads
	$res = $babDB->db_query("select id from ".BAB_THREADS_TBL." where forum='".$babDB->db_escape_string($id)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		bab_deleteThread($id, $arr['id']);
		}


	aclDelete(BAB_FORUMSVIEW_GROUPS_TBL, $id);
	aclDelete(BAB_FORUMSPOST_GROUPS_TBL, $id);
	aclDelete(BAB_FORUMSREPLY_GROUPS_TBL, $id);
	aclDelete(BAB_FORUMSMAN_GROUPS_TBL, $id);
	aclDelete(BAB_FORUMSNOTIFY_GROUPS_TBL, $id);

	$req = "delete from ".BAB_FORUMS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);
}

function bab_deleteFaq($id)
{
	global $babDB;

	// delete questions/responses for this faq
	$babDB->db_query("delete from ".BAB_FAQQR_TBL." where idcat='".$babDB->db_escape_string($id)."'");	

	$babDB->db_query("delete from ".BAB_FAQ_TREES_TBL." where id_user='".$babDB->db_escape_string($id)."'");	

	// delete faq from groups
	aclDelete(BAB_FAQCAT_GROUPS_TBL, $id);
	aclDelete(BAB_FAQMANAGERS_GROUPS_TBL, $id);

	$babDB->db_query("delete from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$babDB->db_escape_string($id)."'");

	// delete faq
	$babDB->db_query("delete from ".BAB_FAQCAT_TBL." where id='".$babDB->db_escape_string($id)."'");
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


function bab_deleteUploadUserFiles($iIdUser)
{
	require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
	
	$oFileManagerEnv =& getEnvObject();
	$sUserUploadPath = $oFileManagerEnv->getFmUploadPath() . 'users/U' . $iIdUser . '/';
	
	if(is_dir($sUserUploadPath))
	{
		$oFmFolderCliboardSet	= new BAB_FmFolderCliboardSet();
		$oIdOwner				=& $oFmFolderCliboardSet->aField['iIdOwner'];
		$oGroup					=& $oFmFolderCliboardSet->aField['sGroup'];
		
		$oCriteria				= $oIdOwner->in($iIdUser);
		$oCriteria				= $oCriteria->_and($oGroup->in('N'));
		
		$oFmFolderCliboardSet->remove($oCriteria);
		
		$oFolderFileSet				= new BAB_FolderFileSet();
	
		$oFolderFileSet->bUseAlias	= false;
		$oIdOwner 					=& $oFolderFileSet->aField['iIdOwner'];
		$oGroup 					=& $oFolderFileSet->aField['sGroup'];
		
		$oCriteria 					= $oIdOwner->in($iIdUser);
		$oCriteria 					= $oCriteria->_and($oGroup->in('N'));
		
		$oFolderFileSet->remove($oCriteria);
		
		BAB_FmFolderSet::removeDir($sUserUploadPath);
	}
}


function bab_deleteFolder($fid)
{
	global $babDB;
	
	$bDbRecordOnly = false;
	$oFmFolderSet = new BAB_FmFolderSet();
	$oId =& $oFmFolderSet->aField['iId'];
	$oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
	
	$oFmFolder = $oFmFolderSet->get($oId->in($fid));
	if(!is_null($oFmFolder))
	{
		$oCriteria = $oId->in($fid);
		$oCriteria = $oCriteria->_or($oRelativePath->like($babDB->db_escape_like($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/') . '%'));
		//bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
		$oFmFolderSet->remove($oCriteria, $bDbRecordOnly);
	}
}

function bab_deleteLdapDirectory($id)
{
	global $babDB;
	$babDB->db_query("delete from ".BAB_LDAP_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'");
}

function bab_deleteDbDirectory($id)
{
	global $babDB;
	$arr = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
	if( $arr['id_group'] != 0)
		return;
	$res = $babDB->db_query("select id from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$babDB->db_escape_string($id)."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$babDB->db_query("delete from ".BAB_DBDIR_FIELDSVALUES_TBL." where id_fieldextra='".$babDB->db_escape_string($arr['id'])."'");
		$babDB->db_query("delete from ".BAB_DBDIRFIELDUPDATE_GROUPS_TBL." where id_object='".$babDB->db_escape_string($arr['id'])."'");
	}
	$babDB->db_query("delete from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_DBDIR_FIELDSEXPORT_TBL." where id_directory='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id_directory='".$babDB->db_escape_string($id)."'");
	$res = $babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".$babDB->db_escape_string($id)."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$babDB->db_query("delete from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$babDB->db_escape_string($arr['id'])."'");
	}
	$babDB->db_query("delete from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'");

	aclDelete(BAB_DBDIRVIEW_GROUPS_TBL, $id);
	aclDelete(BAB_DBDIRADD_GROUPS_TBL, $id);
	aclDelete(BAB_DBDIRUPDATE_GROUPS_TBL, $id);
	aclDelete(BAB_DBDIRDEL_GROUPS_TBL, $id);
	aclDelete(BAB_DBDIREXPORT_GROUPS_TBL, $id);
	aclDelete(BAB_DBDIRIMPORT_GROUPS_TBL, $id);
	aclDelete(BAB_DBDIRBIND_GROUPS_TBL, $id);
	aclDelete(BAB_DBDIRUNBIND_GROUPS_TBL, $id);
	aclDelete(BAB_DBDIREMPTY_GROUPS_TBL, $id);
}


function bab_deleteGroupAclTables($id)
{

	aclDeleteGroup(BAB_TOPICSVIEW_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_TOPICSCOM_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_TOPICSSUB_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_TOPICSVIEW_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_TOPICSMOD_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_TOPICSMAN_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_SECTIONS_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_FAQCAT_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_USERS_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_FMDOWNLOAD_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_FMUPDATE_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_FMUPLOAD_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_FMMANAGERS_GROUPS_TBL, $id);

	aclDeleteGroup(BAB_OCVIEW_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_OCUPDATE_GROUPS_TBL, $id);
	
	aclDeleteGroup(BAB_CAL_PUB_GRP_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_CAL_PUB_MAN_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_CAL_PUB_VIEW_GROUPS_TBL, $id);

	aclDeleteGroup(BAB_CAL_RES_GRP_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_CAL_RES_MAN_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_CAL_RES_VIEW_GROUPS_TBL, $id);

	aclDeleteGroup(BAB_FORUMSVIEW_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_FORUMSPOST_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_FORUMSREPLY_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_FORUMSMAN_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_FORUMSNOTIFY_GROUPS_TBL, $id);

	aclDeleteGroup(BAB_DBDIRVIEW_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_DBDIRADD_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_DBDIRUPDATE_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_DBDIRDEL_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_DBDIREXPORT_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_DBDIRIMPORT_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_DBDIRBIND_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_DBDIRUNBIND_GROUPS_TBL, $id);
	aclDeleteGroup(BAB_DBDIREMPTY_GROUPS_TBL, $id);
	
	aclDeleteGroup(BAB_PROFILES_GROUPS_TBL, $id);

	aclDeleteGroup(BAB_DG_ACL_GROUPS_TBL, $id);
}


function bab_deleteGroup($id)
{
	global $babDB;
	if( $id <= BAB_ADMINISTRATOR_GROUP)
		return;

	bab_deleteGroupAclTables($id);

	// delete user from BAB_MAIL_DOMAINS_TBL
	$babDB->db_query("delete from ".BAB_MAIL_DOMAINS_TBL." where owner='".$babDB->db_escape_string($id)."' and bgroup='Y'");	

	// delete group directory
	$res = $babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$babDB->db_escape_string($id)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		bab_deleteDbDirectory($arr['id']);
		}

	$res = $babDB->db_query("SELECT id_set FROM ".BAB_GROUPS_SET_ASSOC_TBL." where id_group='".$babDB->db_escape_string($id)."'");
	while ($arr = $babDB->db_fetch_assoc($res))
		{
		$babDB->db_query("update ".BAB_GROUPS_TBL." set nb_groups=nb_groups-'1' where id='".$babDB->db_escape_string($arr['id_set'])."'");
		}
	$babDB->db_query("delete from ".BAB_GROUPS_SET_ASSOC_TBL." where id_group='".$babDB->db_escape_string($id)."'");

	$babDB->db_query("update ".BAB_OC_ENTITIES_TBL." set id_group='0' where id_group='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("update ".BAB_DG_GROUPS_TBL." set id_group=NULL where id_group='".$babDB->db_escape_string($id)."'");
		
	$babDB->db_query("DELETE FROM ".BAB_PROFILES_GROUPSSET_TBL." WHERE id_group=".$babDB->quote($id));
	
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");

	// delete group
	
	include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";
	$tree =& new bab_grptree();
	$tree->remove($id);

	include_once $GLOBALS['babInstallPath']."utilit/eventincl.php";
	
	if (!class_exists('bab_eventGroupDeleted')) {
		class bab_eventGroupDeleted extends bab_event {
			/**
			 * @public
			 */
			var $id_group;
			
			
			function  bab_eventGroupDeleted($id_group) {
				$this->id_group = $id_group;
			}
		}
	}
	
	$event = new bab_eventGroupDeleted($id);
	bab_fireEvent($event);
	
	/**
	 * @deprecated
	 */
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	bab_callAddonsFunction('onGroupDelete', $id);
}


function bab_deleteSetOfGroup($id)
	{
	global $babDB;
	if( $id <= BAB_ADMINISTRATOR_GROUP)
		return;


	$res = $babDB->db_query("SELECT * FROM ".BAB_GROUPS_SET_ASSOC_TBL." WHERE id_set='".$babDB->db_escape_string($id)."'");
	while ($arr = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("UPDATE ".BAB_GROUPS_TBL." SET nb_set=nb_set-'1' WHERE id='".$babDB->db_escape_string($arr['id_group'])."'");
		}

	$babDB->db_query("DELETE FROM ".BAB_GROUPS_SET_ASSOC_TBL." WHERE id_set='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("DELETE FROM ".BAB_GROUPS_TBL." WHERE id='".$babDB->db_escape_string($id)."' AND nb_groups>='0'");

	$id += BAB_ACL_GROUP_TREE;

	bab_deleteGroupAclTables($id);
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	bab_callAddonsFunction('onGroupDelete', $id);
	}

function bab_deleteUser($id)
	{
	global $babDB;

	$req = "select id from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);
	while( $arr = $babDB->db_fetch_array($res))
		{
		bab_deleteDraft($arr['id']);
		}

	// delete notes owned by this user
	$res = $babDB->db_query("delete from ".BAB_NOTES_TBL." where id_user='".$babDB->db_escape_string($id)."'");	

	// delete user from groups
	$res = $babDB->db_query("delete from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($id)."'");
	$res = $babDB->db_query("UPDATE ".BAB_GROUPS_TBL." SET manager='0' WHERE manager='".$babDB->db_escape_string($id)."'");
					
	$res = $babDB->db_query("select * from ".BAB_CALENDAR_TBL." where owner='".$babDB->db_escape_string($id)."' and type='1'");
	$arr = $babDB->db_fetch_array($res);

	include_once $GLOBALS['babInstallPath']."utilit/calincl.php";
	bab_deleteCalendar($arr['id']);
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_user='".$babDB->db_escape_string($id)."'");	
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_user='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_CALACCESS_USERS_TBL." where id_user='".$babDB->db_escape_string($id)."'");

	// delegation administrators
	$babDB->db_query("DELETE from ".BAB_DG_ADMIN_TBL." where id_user='".$babDB->db_escape_string($id)."'");	

	// delete user from BAB_USERS_LOG_TBL
	$res = $babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where id_user='".$babDB->db_escape_string($id)."'");	

	// delete user from BAB_MAIL_SIGNATURES_TBL
	$res = $babDB->db_query("delete from ".BAB_MAIL_SIGNATURES_TBL." where owner='".$babDB->db_escape_string($id)."'");	

	// delete user from BAB_MAIL_ACCOUNTS_TBL
	$res = $babDB->db_query("delete from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$babDB->db_escape_string($id)."'");	

	// delete user from BAB_MAIL_DOMAINS_TBL
	$res = $babDB->db_query("delete from ".BAB_MAIL_DOMAINS_TBL." where owner='".$babDB->db_escape_string($id)."' and bgroup='N'");	

	// delete user from contacts
	$res = $babDB->db_query("delete from ".BAB_CONTACTS_TBL." where owner='".$babDB->db_escape_string($id)."'");	

	// delete user from BAB_SECTIONS_STATES_TBL
	$res = $babDB->db_query("delete from ".BAB_SECTIONS_STATES_TBL." where id_user='".$babDB->db_escape_string($id)."'");	

	// delete files owned by this user
	bab_deleteUploadUserFiles($id);

	// delete user from BAB_DBDIR_ENTRIES_TBL
	list($iddu) = $babDB->db_fetch_array($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$babDB->db_escape_string($id)."'"));	
	$babDB->db_query("delete from ".BAB_OC_ROLES_USERS_TBL." where id_user='".$babDB->db_escape_string($iddu)."'");
	$res = $babDB->db_query("delete from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$babDB->db_escape_string($iddu)."'");	
	$res = $babDB->db_query("delete from ".BAB_DBDIR_ENTRIES_TBL." where id='".$babDB->db_escape_string($iddu)."'");	
	$babDB->db_query("delete from ".BAB_DBDIR_FIELDSEXPORT_TBL." where id_user='".$babDB->db_escape_string($id)."'");

	// delete user from VACATION
	$babDB->db_query("delete from ".BAB_VAC_MANAGERS_TBL." where id_user='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_VAC_PLANNING_TBL." where id_user='".$babDB->db_escape_string($id)."'");
	$res = 	$babDB->db_query("select id from ".BAB_VAC_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($id)."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$babDB->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$babDB->db_escape_string($arr['id'])."'");
		$babDB->db_query("delete from ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($arr['id'])."'");
	}

	$babDB->db_query("delete from ".BAB_USERS_UNAVAILABILITY_TBL." where id_user='".$babDB->db_escape_string($id)."' or id_substitute='".$babDB->db_escape_string($id)."'");
	
	// delete user
	$res = $babDB->db_query("delete from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($id)."'");
	
	bab_tskmgr_deleteUserContext($id);
	
	include_once $GLOBALS['babInstallPath']."utilit/eventdirectory.php";
	$event = new bab_eventUserDeleted($id);
	bab_fireEvent($event);
	
	/**
	 * @deprecated
	 */
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	bab_callAddonsFunction('onUserDelete', $id);
}

function bab_deleteOrgChart($id)
{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/treeincl.php";

	$ocinfo = $babDB->db_fetch_array($babDB->db_query("select oct.*, ddt.id as id_dir, ddt.id_group from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where oct.id='".$babDB->db_escape_string($id)."'"));
	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_ORG_CHARTS_TBL." where id='".$babDB->db_escape_string($id)."'"));
	if( $ocinfo['isprimary'] == 'Y' && $ocinfo['id_group'] == 1)
	{
		return;
	}

	$ru = array();
	$res = 	$babDB->db_query("select id from ".BAB_OC_ROLES_TBL." where id_oc='".$babDB->db_escape_string($id)."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$ru[] = $arr['id'];
	}
	if( count($ru) > 0 )
	{
		$babDB->db_query("delete from ".BAB_OC_ROLES_USERS_TBL." where id_role IN (".$babDB->quote($ru).")");
	}

	$entities = array();
	$res = 	$babDB->db_query("select id from ".BAB_OC_ENTITIES_TBL." where id_oc='".$babDB->db_escape_string($id)."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$entities[] = $arr['id'];
	}

	if( count($entities) > 0 )
	{
		$babDB->db_query("delete from ".BAB_VAC_PLANNING_TBL." where id_entity IN (".$babDB->quote($entities).")");
	}

	$babDB->db_query("delete from ".BAB_OC_ROLES_TBL." where id_oc='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_OC_ENTITIES_TBL." where id_oc='".$babDB->db_escape_string($id)."'");
	aclDelete(BAB_OCUPDATE_GROUPS_TBL, $id);
	aclDelete(BAB_OCVIEW_GROUPS_TBL, $id);
	$babDB->db_query("delete from ".BAB_ORG_CHARTS_TBL." where id='".$babDB->db_escape_string($id)."'");

	$babTree = new bab_dbtree(BAB_OC_TREES_TBL, $id);
	$rootinfo = $babTree->getRootInfo();
	if( !$rootinfo)
	{
		return;
	}
	$babTree->removeTree($rootinfo['id']);
}


function bab_tskmgr_deleteUserContext($iIdUser)
{
	global $babDB;
	
	$sTableName	= 'bab_tskmgr_user' . $iIdUser . '_additional_fields';
	$aIsTable	= $babDB->db_fetch_array($babDB->db_query("SHOW TABLES LIKE '". $sTableName . "'"));
	
	$sQuery = 
		'SELECT ' .
			'idTask iIdTask ' . 
		'FROM ' .
			BAB_TSKMGR_TASKS_INFO_TBL . ' ' . 
		'WHERE ' . 
			'idOwner = ' . $babDB->quote($iIdUser) . ' AND ' .
			'isPersonnal = ' . $babDB->quote(1); //1 == 'BAB_TM_YES'
	
	//bab_debug($sQuery);
	$oResultTaskInfo = $babDB->db_query($sQuery);
	if(false !== $oResultTaskInfo)
	{
		$iNumRows = $babDB->db_num_rows($oResultTaskInfo);
		if(0 < $iNumRows)
		{
			$aDatas = array();
			while(false !== ($aDatasTaskInfo = $babDB->db_fetch_assoc($oResultTaskInfo)))
			{
				$iIdTask = (int) $aDatasTaskInfo['iIdTask'];
				
				//bab_tskmgr_deleteTaskAdditionalFields
				{				
					if($aIsTable[0] == $sTableName)
					{
						$sQuery = 
							'DELETE FROM ' . 
								$sTableName . ' ' . 
							'WHERE ' . 
								'iIdTask = ' . $babDB->quote($iIdTask);
								 
						//bab_debug($sQuery);
						$babDB->db_query($sQuery);
						
					}
				}

				//bab_deleteTaskLinks
				{
					$sQuery = 
						'DELETE FROM ' . 
							BAB_TSKMGR_LINKED_TASKS_TBL . ' ' .
						'WHERE ' .
							'idTask = ' . $babDB->quote($iIdTask);
							
					//bab_debug($sQuery);
					$babDB->db_query($sQuery);
				}
				
				//bab_deleteTaskResponsibles
				{
					$sQuery = 
						'DELETE FROM ' . 
							BAB_TSKMGR_TASKS_RESPONSIBLES_TBL . ' ' .
						'WHERE ' .
							'idTask = ' . $babDB->quote($iIdTask);
					
					//bab_debug($sQuery);
					$babDB->db_query($sQuery);
				}
				
				//The other
				{
					$sQuery = 
						'DELETE FROM ' . 
							BAB_TSKMGR_TASKS_INFO_TBL . ' ' .
						'WHERE ' .
							'idTask = ' . $babDB->quote($iIdTask);
							
					//bab_debug($sQuery);
					$babDB->db_query($sQuery);
				
					$sQuery = 
						'DELETE FROM ' . 
							BAB_TSKMGR_TASKS_TBL . ' ' .
						'WHERE ' .
							'id = ' . $babDB->quote($iIdTask);
							
					//bab_debug($sQuery);
					$babDB->db_query($sQuery);
											
					$sQuery = 
						'DELETE FROM ' . 
							BAB_TSKMGR_TASKS_COMMENTS_TBL . ' ' .
						'WHERE ' .
							'idTask = ' . $babDB->quote($iIdTask);
							
					//bab_debug($sQuery);
					$babDB->db_query($sQuery);
				}
			}
		}
	}

	if($aIsTable[0] == $sTableName)
	{
		$sQuery = 'DROP TABLE `' . $sTableName . '`';	
			
		//bab_debug($sQuery);
		$babDB->db_query($sQuery);
	}
}






/**
 * Delete a folder recursively
 * return true on success
 * @param	string	$dir		folder to delete
 * @param	string	&$msgerror	this string will be empty on succes and not empty on failure
 * @return	bool
 */
function bab_deldir($dir, &$msgerror) {
	$current_dir = opendir($dir);
	while($entryname = readdir($current_dir)){
		if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!="..")){
			if (false === bab_deldir($dir.'/'.$entryname, $msgerror)) {
				return false;
			}
		} elseif ($entryname != "." and $entryname!="..") {
			if (false === unlink($dir.'/'.$entryname)) {
				$msgerror = bab_sprintf(bab_translate('The file is not deletable : %s'), $dir.'/'.$entryname);
				return false;
			}
		}
	}
	closedir($current_dir);
	if (false === rmdir($dir)) {
		$msgerror = bab_sprintf(bab_translate('The folder is not deletable : %s'), $dir.'/'.$entryname);
		return false;
	}
	return true;
}
