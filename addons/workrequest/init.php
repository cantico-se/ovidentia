<?php
/*
CREATE TABLE wr_workslist (
   id int(11) unsigned NOT NULL auto_increment,
   name varchar(255),
   description varchar(255),
   wtype smallint(6) unsigned NOT NULL default '0',
   manager int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE wr_workstypes (
   id int(11) unsigned NOT NULL auto_increment,
   name varchar(255),
   PRIMARY KEY (id)
);

CREATE TABLE wr_worksusers_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE wr_worksagents_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE wr_worksothers_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE wr_taskslist (
  id int(11) unsigned NOT NULL auto_increment,
  user int(11) unsigned NOT NULL default '0',
  service varchar(255) NOT NULL default '',
  office varchar(255) NOT NULL default '',
  room varchar(255) NOT NULL default '',
  tel varchar(20) NOT NULL default '',
  date_request date NOT NULL default '0000-00-00',
  date_desired date NOT NULL default '0000-00-00',
  deadline varchar(40) NOT NULL default '',
  description text NOT NULL,
  wtype int(11) unsigned NOT NULL default '0',
  worker int(11) unsigned NOT NULL default '0',
  worker_tel varchar(20) NOT NULL default '',
  date_start date NOT NULL default '0000-00-00',
  date_end date NOT NULL default '0000-00-00',
  status smallint(6) unsigned NOT NULL default '0',
  information text NOT NULL,
  date_update date NOT NULL default '0000-00-00',
  user_update int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY user (user),
  KEY date_request (date_request),
  KEY worker (worker),
  KEY status (status)
);
*/
function workrequest_getAdminSectionMenus(&$url, &$text)
{
	static $i=0;
	if( $i )
	{
		return false;
	}
	else
	{
		require_once( $GLOBALS['babAddonPhpPath']."wrincl.php");
		$url = $GLOBALS['babAddonUrl']."admin";
		$text = wr_translate("Travaux");
		$i++;
		return true;
	}
}

function workrequest_getUserSectionMenus(&$url, &$text)
{
	global $babDB;
	static $i=0;
	if( $i )
	{
		return false;
	}
	else
	{
		require_once( $GLOBALS['babAddonPhpPath']."wrincl.php");
		$access = false;

		$res = $babDB->db_query("select distinct ".ADDON_WR_WORKSUSERS_GROUPS_TBL.".id from ".ADDON_WR_WORKSUSERS_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".ADDON_WR_WORKSUSERS_GROUPS_TBL.".id_group='1' or (".BAB_USERS_GROUPS_TBL.".id_group=".ADDON_WR_WORKSUSERS_GROUPS_TBL.".id_group and ".BAB_USERS_GROUPS_TBL.".id_object='".$GLOBALS['BAB_SESS_USERID']."')");

		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$access = true;
			}
		else
			{
			$res = $babDB->db_query("select distinct ".ADDON_WR_WORKSAGENTS_GROUPS_TBL.".id from ".ADDON_WR_WORKSAGENTS_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".ADDON_WR_WORKSAGENTS_GROUPS_TBL.".id='1' or (".BAB_USERS_GROUPS_TBL.".id_group=".ADDON_WR_WORKSAGENTS_GROUPS_TBL.".id_group and ".BAB_USERS_GROUPS_TBL.".id_object='".$GLOBALS['BAB_SESS_USERID']."')");

			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$access = true;
				}
			else
				{
				$res = $babDB->db_query("select distinct ".ADDON_WR_WORKSOTHERS_GROUPS_TBL.".id from ".ADDON_WR_WORKSOTHERS_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".ADDON_WR_WORKSOTHERS_GROUPS_TBL.".id='1' or (".BAB_USERS_GROUPS_TBL.".id_group=".ADDON_WR_WORKSOTHERS_GROUPS_TBL.".id_group and ".BAB_USERS_GROUPS_TBL.".id_object='".$GLOBALS['BAB_SESS_USERID']."')");

				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$access = true;
					}
				else
					{			
					$res = $babDB->db_query("select ".ADDON_WR_WORKSLIST_TBL.".id from ".ADDON_WR_WORKSLIST_TBL." where manager='".$GLOBALS['BAB_SESS_USERID']."'");
					if( $res && $babDB->db_num_rows($res) > 0 )
						{
						$access = true;
						}
					}
				}
			}

		if( $access )
			{
			$url = $GLOBALS['babAddonUrl']."main";
			$text = wr_translate("Travaux");
			$i++;
			}
		return $access;
	}
}

function workrequest_onUserCreate( $id )
{
}

function workrequest_onUserDelete( $id )
{
}

function workrequest_onGroupCreate( $id )
{
}

function workrequest_onGroupDelete( $id )
{
}

function workrequest_onSectionCreate( &$title, &$content)
{
	return false;
}
?>