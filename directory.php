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
include "base.php";
include $babInstallPath."utilit/dirincl.php";
include $babInstallPath."utilit/ldap.php";
include $babInstallPath."utilit/tempfile.php";
include $babInstallPath."admin/register.php";

function trimQuotes($str)
{
	if( $str[strlen($str) - 1] == "\"" && $str[0] == "\"")
		return substr(substr($str, 1), 0, strlen($str)-2);
	else
		return $str;
}

function listUserAds()
{
	global $babBody;

	class temp
		{
		var $db;
		var $res;
		var $count;
		var $directories;
		var $urlname;
		var $emptyname;
		var $emptyurl;
		var $name;
		var $description;
		var $desctxt;
		var $ldapid = array();
		var $dbid = array();

		function temp()
			{
			$this->directories = bab_translate("Directories");
			$this->desctxt = bab_translate("Description");
			$this->databasetitle = bab_translate("Databases Directories list");
			$this->ldaptitle = bab_translate("Ldap Directories list");
			$this->emptyname = bab_translate("Empty");
			$this->db = $GLOBALS['babDB'];
			$this->badd = false;
			$res = $this->db->db_query("select id from ".BAB_LDAP_DIRECTORIES_TBL."");
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_LDAPDIRVIEW_GROUPS_TBL, $row['id']))
					{
					array_push($this->ldapid, $row['id']);
					}
				}
			$this->countldap = count($this->ldapid);
			$res = $this->db->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL."");
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
					{
					if( $row['id_group'] > 0 )
						{
						list($bdir) = $this->db->db_fetch_array($this->db->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$row['id_group']."'"));
						if( $bdir == 'Y' )
							array_push($this->dbid, $row['id']);		
						}
					else
						array_push($this->dbid, $row['id']);
					}
				}
			$this->countdb = count($this->dbid);
			}

		function getnextldap()
			{
			static $i = 0;
			if( $i < $this->countldap)
				{
				$arr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_LDAP_DIRECTORIES_TBL." where id='".$this->ldapid[$i]."'"));
				$this->description = $arr['description'];
				$this->url = $GLOBALS['babUrlScript']."?tg=directory&idx=sldap&id=".$this->ldapid[$i];
				$this->urlname = $arr['name'];
				$i++;
				return true;
				}
			else
				return false;
			}
		
		function getnextdb()
			{
			static $i = 0;
			if( $i < $this->countdb)
				{
				$arr = $this->db->db_fetch_array($this->db->db_query("select name, description, id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$this->dbid[$i]."'"));
				$this->description = $arr['description'];
				$this->url = $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$this->dbid[$i];
				$this->emptyurl = $GLOBALS['babUrlScript']."?tg=directory&idx=empdb&id=".$this->dbid[$i];
				$this->urlname = $arr['name'];
				$this->badd = bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $this->dbid[$i]);
				if( $this->badd && $arr['id_group'] != 0 )
					$this->badd = false;
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "directory.html", "useradlist"));
}

function browseLdapDirectory($id, $pos)
{
	global $babBody;

	class temp
		{
		var $count;
		var $allname;
		var $cntxt;
		var $bteltxt;
		var $hteltxt;
		var $emailtxt;
		var $addname;
		var $id;
		var $pos;
		var $allselected;
		var $allurl;
		var $count;
		var $ldap;
		var $entries;
		var $db;
		var $accid;
		var $cn;
		var $url;
		var $btel;
		var $htel;
		var $email;
		var $urlmail;
		var $selectname;
		var $selecturl;
		var $selected;
		var $badd;

		function temp($id, $pos)
			{
			$this->allname = bab_translate("All");
			$this->cntxt = bab_translate("Name");
			$this->bteltxt = bab_translate("Business Phone");
			$this->hteltxt = bab_translate("Home Phone");
			$this->emailtxt = bab_translate("Email");
			$this->addname = bab_translate("Add");
			$this->badd = false;
			$this->id = $id;
			$this->pos = $pos;
			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=directory&idx=sldap&id=".$id."&pos=";
			$this->count = 0;
			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * , DECODE(password, \"".$GLOBALS['BAB_HASH_VAR']."\") as adpass from ".BAB_LDAP_DIRECTORIES_TBL." where id='".$id."'");
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$this->ldap = new babLDAP($arr['host'], "", $arr['basedn'], $arr['userdn'], $arr['adpass'], true);
				$this->ldap->connect();
				$this->entries = $this->ldap->search("(|(cn=".$pos."*))", array("cn", "telephonenumber", "mail", "homephone"));
				if( is_array($this->entries))
					{
					$this->count = $this->entries['count'];
					}
				}

			/* find prefered mail account */
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$GLOBALS['BAB_SESS_USERID']."' and prefered='Y'";
			$res = $this->db->db_query($req);
			if( !$res || $this->db->db_num_rows($res) == 0 )
				{
				$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$GLOBALS['BAB_SESS_USERID']."'";
				$res = $this->db->db_query($req);
				}

			if( $this->db->db_num_rows($res) > 0 )
				{
				$arr = $this->db->db_fetch_array($res);
				$this->accid = $arr['id'];
				}
			else
				$this->accid = 0;			
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->cn = "";
				$this->url = "";
				$this->btel = "";
				$this->htel = "";
				$this->email = "";
				$this->cn = quoted_printable_decode($this->entries[$i]['cn'][0]);
				$this->url = $GLOBALS['babUrlScript']."?tg=directory&idx=dldap&id=".$this->id."&cn=".$this->cn."&pos=".$this->pos;
				$this->btel = quoted_printable_decode($this->entries[$i]['telephonenumber'][0]);
				$this->htel = quoted_printable_decode($this->entries[$i]['homephone'][0]);
				$this->email = $this->entries[$i]['mail'][0];
				$this->urlmail = $GLOBALS['babUrlScript']."?tg=mail&idx=compose&accid=".$this->accid."&to=".$this->email;
				$i++;
				return true;
				}
			else
				{
				$this->ldap->close();
				return false;
				}
			}

		function getnextselect()
			{
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=directory&idx=sldap&id=".$this->id."&pos=".$this->selectname;
				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else
					$this->selected = 0;
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id, $pos);
	$babBody->babecho( bab_printTemplate($temp, "directory.html", "adldapbrowse"));
}

function browseDbDirectory($id, $pos, $xf, $badd)
{
	global $babBody;

	class temp
		{
		var $count;

		function temp($id, $pos, $xf, $badd)
			{
			$this->allname = bab_translate("All");
			$this->addname = bab_translate("Add");
			$this->id = $id;
			$this->pos = $pos;
			$this->badd = $badd;
			$this->xf = $xf;
			if( $pos[0] == "-" )
				{
				$this->pos = $pos[1];
				$this->ord = "";
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "-";
				}

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".($this->ord == "-"? "":$this->ord)."&xf=".$this->xf;
			$this->addurl = $GLOBALS['babUrlScript']."?tg=directory&idx=adbc&id=".$id;
			$this->count = 0;
			$this->db = $GLOBALS['babDB'];
			$arr = $this->db->db_fetch_array($this->db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $id))
				{
				$this->idgroup = $arr['id_group'];
				$this->rescol = $this->db->db_query("select id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and ordering!='0' order by ordering asc");
				$this->countcol = $this->db->db_num_rows($this->rescol);
				}
			else
				{
				$this->countcol = 0;
				$this->count = 0;
				}

			/* find prefered mail account */
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$GLOBALS['BAB_SESS_USERID']."' and prefered='Y'";
			$res = $this->db->db_query($req);
			if( !$res || $this->db->db_num_rows($res) == 0 )
				{
				$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$GLOBALS['BAB_SESS_USERID']."'";
				$res = $this->db->db_query($req);
				}

			if( $this->db->db_num_rows($res) > 0 )
				{
				$arr = $this->db->db_fetch_array($res);
				$this->accid = $arr['id'];
				}
			else
				$this->accid = 0;			
			}

		function getnextcol()
			{
			static $i = 0;
			static $tmp = array();
			if( $i < $this->countcol)
				{
				$arr = $this->db->db_fetch_array($this->rescol);
				$arr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
				$this->coltxt = bab_translate($arr['description']);
				$this->colurl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$this->id."&pos=".$this->ord.$this->pos."&xf=".$arr['name'];
				$tmp[] = $arr['name'];
				$i++;
				return true;
				}
			else
				{
				if( count($tmp) > 0 )
					{
					$tmp[] = "id";
					if( $this->xf == "" )
						$this->xf = $tmp[0];
					for( $i=0; $i < count($tmp); $i++)
						$tmp[$i] = BAB_DBDIR_ENTRIES_TBL.".".$tmp[$i];
					if( !in_array('email', $tmp))
						$tmp[] = 'email';
					$this->select = implode($tmp, ",");
					if( $this->idgroup > 1 )
						{
						$req = "select ".$this->select." from ".BAB_DBDIR_ENTRIES_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_group='".$this->idgroup."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_DBDIR_ENTRIES_TBL.".id_user and ".BAB_DBDIR_ENTRIES_TBL.".".$this->xf." like '".$this->pos."%' and ".BAB_DBDIR_ENTRIES_TBL.".id_directory='".($this->idgroup != 0? 0: $this->id)."' order by ".$this->xf." ";
						}
					else
						{
						$req = "select ".$this->select." from ".BAB_DBDIR_ENTRIES_TBL." where ".BAB_DBDIR_ENTRIES_TBL.".".$this->xf." like '".$this->pos."%' and ".BAB_DBDIR_ENTRIES_TBL.".id_directory='".($this->idgroup != 0? 0: $this->id)."' order by ".$this->xf." ";
						}

					if( $this->ord == "-" )
						{
						$req .= "asc";
						}
					else
						{
						$req .= "desc";
						}

					$this->res = $this->db->db_query($req);
					$this->count = $this->db->db_num_rows($this->res);
					}
				else
					$this->count = 0;

				return false;
				}
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arrf = $this->db->db_fetch_array($this->res);
				$this->urlmail = $GLOBALS['babUrlScript']."?tg=mail&idx=compose&accid=".$this->accid."&to=".$this->arrf['email'];
				$this->email = $this->arrf['email'];
				$this->url = $GLOBALS['babUrlScript']."?tg=directory&idx=ddb&id=".$this->id."&idu=".$this->arrf['id']."&pos=".$this->ord.$this->pos."&xf=".$this->xf;
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextcolval()
			{
			static $i = 0;
			if( $i < $this->countcol)
				{
				$this->coltxt = bab_translate($this->arrf[$i]);
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextselect()
			{
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$this->id."&pos=".($this->ord == "-"? "":$this->ord).$this->selectname."&xf=".$this->xf;
				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else
					$this->selected = 0;
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id, $pos, $xf, $badd);
	$babBody->babecho( bab_printTemplate($temp, "directory.html", "adbrowse"));
}

function summaryLdapContact($id, $cn)
{
	global $babBody;

	class temp
		{

		function temp($id, $cn)
			{
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto' and x_name!=''");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;

			$res = $this->db->db_query("select * , DECODE(password, \"".$GLOBALS['BAB_HASH_VAR']."\") as adpass from ".BAB_LDAP_DIRECTORIES_TBL." where id='".$id."'");
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->ldap = new babLDAP($arr['host'], "", $arr['basedn'], $arr['userdn'], $arr['adpass'], true);
				$this->ldap->connect();
				$this->entries = $this->ldap->search("(|(cn=".$cn."))");
				$this->ldap->close();
				$this->name = $this->entries[0]['cn'][0];
				$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimgl&id=".$id."&cn=".$cn;
				}
			$this->bfieldv = true;
			$this->showph = true;
			}

		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldn = bab_translate($arr['description']);
				$this->fieldv = quoted_printable_decode($this->entries[0][$arr['x_name']][0]);
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id, $cn);
	echo bab_printTemplate($temp, "directory.html", "summaryldapcontact");
}


function summaryDbContact($id, $idu)
{
	global $babBody;

	class temp
		{

		function temp($id, $idu)
			{
			$this->db = $GLOBALS['babDB'];
			list($idgroup, $allowuu) = $this->db->db_fetch_array($this->db->db_query("select id_group, user_update from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));

			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;
			$res = $this->db->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".($idgroup != 0? 0: $id)."' and id='".$idu."'");
			$this->showph = false;
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$this->arr = $this->db->db_fetch_array($res);
				$this->name = $this->arr['givenname']. " ". $this->arr['sn'];
				if( $this->arr['plen'] > 0 )
					$this->showph = true;

				$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$id."&idu=".$idu;

				if( $idgroup != 0 )
					$this->del = false;
				else
					{
					$allowuu = "N";
					$this->del = bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id);
					}

				$this->modify = bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $id);

				if( $this->modify == false && $allowuu == "Y" && $this->arr['id_user'] == $GLOBALS['BAB_SESS_USERID'] )
					$this->modify = true;

				if( $this->modify )
					{
					$this->modifytxt = bab_translate("Modify");
					$this->modifyurl = $GLOBALS['babUrlScript']."?tg=directory&idx=dbmod&id=".$id."&idu=".$idu;
					}

				if( $this->del )
					{
					$this->deltxt = bab_translate("Delete");
					$this->delurl = $GLOBALS['babUrlScript']."?tg=directory&idx=deldbc&id=".$id."&idu=".$idu;
					}

				}
			else
				{
				$this->name = "";
				$this->urlimg = "";
				}
			}
		
		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldn = bab_translate($arr['description']);
				$this->fieldv = $this->arr[$arr['name']];
				if( strlen($this->arr[$arr['name']]) > 0 )
					$this->bfieldv = true;
				else
					$this->bfieldv = false;
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id, $idu);
	echo bab_printTemplate($temp, "directory.html", "summarydbcontact");
}

function modifyDbContact($id, $idu, $fields, $refresh)
{
	global $babBody;

	class temp
		{
		var $refresh;

		function temp($id, $idu, $fields, $refresh)
			{
			global $babBody;
			$this->helpfields = bab_translate("Those fields must be filled");
			$this->file = bab_translate("File");
			$this->update = bab_translate("Update");
			$this->id = $id;
			$this->idu = $idu;
			$this->fields = $fields;
			$this->what = "dbc";
			$this->badd = bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id);
			$this->bupd = bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $id);
			$this->buserinfo = false;
			$this->refresh = $refresh;

			if( !empty($babBody->msgerror))
				{
				$this->msgerror = $babBody->msgerror;
				$this->error = true;
				}
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;
			
			$arr = $this->db->db_fetch_array($this->db->db_query("select id_group, user_update from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			$this->idgroup = $arr['id_group'];

			$this->showph = false;
			$res = $this->db->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and id='".$idu."'");
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$this->arr = $this->db->db_fetch_array($res);
				$this->name = $this->arr['givenname']. " ". $this->arr['sn'];
				if( $this->arr['plen'] > 0 )
					{
					$this->showph = true;
					$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$this->id."&idu=".$idu;
					}

				if( $this->bupd == false && $arr['user_update'] == "Y" && $this->arr['id_user'] == $GLOBALS['BAB_SESS_USERID'] )
					$this->bupd = true;

				}
			else
				{
				$this->name = "";
				$this->urlimg = "";
				}

			$res = $this->db->db_query("select modifiable from ".BAB_DBDIR_FIELDSEXTRA_TBL." join ".BAB_DBDIR_FIELDS_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and id_field=".BAB_DBDIR_FIELDS_TBL.".id and ".BAB_DBDIR_FIELDS_TBL.".name='jpegphoto'");

			$this->modify = false;
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				if( $this->badd || ($this->bupd && $arr['modifiable'] == "Y"))
					{
					$this->modify = true;
					}
				}
			}
		
		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldn = bab_translate($arr['description']);
				$this->fieldv = $arr['name'];
				if( isset($this->fields[$arr['name']]) )
					$this->fvalue = $this->fields[$arr['name']];
				else
					$this->fvalue = $this->arr[$arr['name']];
				$res = $this->db->db_query("select multilignes, required, modifiable from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and id_field='".$arr['id']."'");

				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$arr = $this->db->db_fetch_array($res);
					if( $this->badd || ($this->bupd && $arr['modifiable'] == "Y"))
						{
						$this->modify = true;
						}
					else
						$this->modify = false;

					$this->fieldt = $arr['multilignes'];
					$this->required = $arr['required'];
					}
				else
					{
					$this->required = "N";
					$this->fieldt = "N";
					$this->modify = false;
					}

				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id, $idu, $fields, $refresh);
	echo bab_printTemplate($temp, "directory.html", "modifycontact");
}

function addDbContact($id, $fields)
{
	global $babBody;

	class temp
		{
		var $helpfields;
		var $file;
		var $update;
		var $id;
		var $idu;
		var $fields;
		var $what;
		var $modify;
		var $showph;
		var $msgerror;
		var $error;
		var $db;
		var $res;
		var $count;
		var $name;
		var $urlimg;
		var $idgroup;
		var $buserinfo;
		var $nickname;
		var $password;
		var $repassword;
		var $notifyuser;
		var $sendpassword;
		var $yes;
		var $no;
		var $fieldn;
		var $fieldv;
		var $fvalue;
		var $fieldt;
		var $required;
		var $refresh;

		function temp($id, $fields)
			{
			global $babBody;
			$this->helpfields = bab_translate("Those fields must be filled");
			$this->file = bab_translate("File");
			$this->update = bab_translate("Update");
			$this->id = $id;
			$this->idu = "";
			$this->fields = $fields;
			$this->what = "dbac";
			$this->modify = true;
			$this->showph = false;
			$this->refresh = '';

			if( !empty($babBody->msgerror))
				{
				$this->msgerror = $babBody->msgerror;
				$this->error = true;
				}

			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;

			$this->name = "";
			$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$id."&idu=";
			$this->name = bab_translate("Add new contact");

			list($this->idgroup) = $this->db->db_fetch_array($this->db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			if( $this->idgroup >= 1 )
				{
				$this->buserinfo = true;
				$this->nickname = bab_translate("Nickname");
				$this->password = bab_translate("Password");
				$this->repassword = bab_translate("Retype Password");
				$this->notifyuser = bab_translate("Notify user");
				$this->sendpassword = bab_translate("Send password with email");
				$this->yes = bab_translate("Yes");
				$this->no = bab_translate("No");
				}
			else
				$this->buserinfo = false;
			}
		
		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldn = bab_translate($arr['description']);
				$this->fieldv = $arr['name'];
				if( isset($this->fields[$arr['name']]) )
					$this->fvalue = $this->fields[$arr['name']];
				else
					$this->fvalue = "";
				$res = $this->db->db_query("select multilignes, required, modifiable, default_value from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and id_field='".$arr['id']."'");

				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$arr = $this->db->db_fetch_array($res);
					$this->fieldt = $arr['multilignes'];
					$this->required = $arr['required'];
					if( !empty( $arr['default_value']) && empty($this->fvalue))
						$this->fvalue = $arr['default_value'];
					}
				else
					{
					$this->required = "N";
					$this->fieldt = "N";
					}

				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id, $fields);
	echo bab_printTemplate($temp, "directory.html", "modifycontact");
}


function importDbFile($id)
	{
	global $babBody;
	class temp
		{
		var $import;
		var $name;
		var $id;
		var $separator;
		var $other;
		var $comma;
		var $tab;

		function temp($id)
			{
			$this->id = $id;
			$this->import = bab_translate("Import");
			$this->name = bab_translate("File");
			$this->separator = bab_translate("Separator");
			$this->other = bab_translate("Other");
			$this->comma = bab_translate("Comma");
			$this->tab = bab_translate("Tab");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"directory.html", "dbimpfile"));
	}

function exportDbFile($id)
	{
	global $babBody;
	class temp
		{
		var $export;
		var $name;
		var $id;
		var $separator;
		var $other;
		var $comma;
		var $tab;

		function temp($id)
			{
			$this->id = $id;
			$this->export = bab_translate("Export");
			$this->separator = bab_translate("Separator");
			$this->other = bab_translate("Other");
			$this->comma = bab_translate("Comma");
			$this->tab = bab_translate("Tab");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"directory.html", "dbexpfile"));
	}

function mapDbFile($id, $file, $tmpfile, $wsepar, $separ)
	{
	global $babBody;
	class temp
		{
		var $res;
		var $count;
		var $db;
		var $id;

		function temp($id, $pfile, $wsepar, $separ)
			{
			$this->db = $GLOBALS['babDB'];
			$this->helpfields = bab_translate("Those fields must be filled");
			$this->process = bab_translate("Import");
			$this->handling = bab_translate("Handling duplicates");
			$this->duphand0 = bab_translate("Allow duplicates to be created");
			$this->duphand1 = bab_translate("Replace duplicates with items imported");
			$this->duphand2 = bab_translate("Do not import duplicates");
			list($this->idgroup) = $this->db->db_fetch_array($this->db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			if( $this->idgroup >= 1 )
				{
				$this->buserinfo = true;
				$this->nickname = bab_translate("Nickname");
				$this->password = bab_translate("Default password");
				$this->repassword = bab_translate("Retype default password");
				$this->notifyuser = bab_translate("Notify users");
				$this->sendpassword = bab_translate("Send password with email");
				$this->yes = bab_translate("Yes");
				$this->no = bab_translate("No");
				}
			else
				$this->buserinfo = false;

			$this->id = $id;
			$this->pfile = $pfile;
			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL);
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;
			switch($wsepar)
				{
				case "1":
					$separ = ",";
					break;
				case "2":
					$separ = "\t";
					break;
				default:
					if( empty($separ))
						$separ = ",";
					break;
				}
			$fd = fopen($pfile, "r");
			$this->arr = fgetcsv( $fd, 4096, $separ);
			fclose($fd);
			$this->separ = $separ;
			}

		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$res = $this->db->db_query("select required from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and id_field='".$arr['id']."'");
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$rr = $this->db->db_fetch_array($res);
					$this->required = $rr['required'];
					}
				else
					$this->required = false;
				
				$this->ofieldname = bab_translate($arr['description']);
				$this->ofieldv = $arr['name'];
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		
		function getnextval()
			{
			static $i = 0;
			static $k = 0;
			if( $i < count($this->arr))
				{
				$this->ffieldid = $i;
				$this->ffieldname = $this->arr[$i];
				if( strtolower($this->ofieldname) == strtolower($this->ffieldname) )
					$this->fselected = "selected";
				else
					$this->fselected = "";
				$i++;
				return true;
				}
			else
				{
				$k++;
				$i = 0;
				return false;
				}
			}

		}

	$tmpdir = get_cfg_var('upload_tmp_dir');
	if( empty($tmpdir))
		$tmpdir = session_save_path();

	$tf = new babTempFiles($tmpdir);
	$nf = $tf->tempfile($tmpfile, $file);
	if( empty($nf))
		{
		$babBody->msgerror = bab_translate("Cannot create temporary file");
		return;
		}
	$temp = new temp($id, $nf, $wsepar, $separ);
	$babBody->babecho(	bab_printTemplate($temp,"directory.html", "dbmapfile"));
	}

function emptyDb($id)
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
			$this->message = bab_translate("Are you sure you want to empty this directory");
			$this->title = getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
			$this->warning = bab_translate("WARNING: This operation will delete all entries"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=directory&idx=list&id=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=directory&idx=list";
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function contactDbUnload($msg, $refresh)
	{
	class temp
		{
		var $message;
		var $close;
		var $refresh;

		function temp($msg, $refresh)
			{
			if( empty($refresh))
				$this->refresh = true;
			else
				$this->refresh = false;
			$this->message = $msg;
			$this->close = bab_translate("Close");
			}
		}

	$temp = new temp($msg, $refresh);
	echo bab_printTemplate($temp,"directory.html", "dbcontactunload");
	}

function processImportDbFile( $pfile, $id, $separ )
	{
	global $babBody;

	$db = $GLOBALS['babDB'];
	list($idgroup) = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
	$res = $db->db_query("select id, name from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");
	$req = "";
	while( $arr = $db->db_fetch_array($res))
		{
		$rr = $db->db_fetch_array($db->db_query("select required from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup !=0 ? 0: $id)."' and id_field='".$arr['id']."'"));
		if( $rr['required'] == "Y" && $GLOBALS[$arr['name']] == "")
			{
			$babBody->msgerror = bab_translate("You must complete required fields");
			return false;
			}
		}
	$db->db_data_seek($res,0);
	
	if( $idgroup > 0 )
		{
		if( empty($GLOBALS['password1']) || empty($GLOBALS['password2']) || strlen($GLOBALS['nickname']) == 0)
			{
			echo $babBody->msgerror = bab_translate("You must complete required fields");
			return false;
			}

		if( empty($GLOBALS['sn']) || empty($GLOBALS['givenname']))
			{
			echo $babBody->msgerror = bab_translate( "You must complete firstname and lastname fields !!");
			return false;
			}

		if ( strlen($GLOBALS['password1']) < 6 )
			{
			echo $babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
			return false;
			}

		if( $GLOBALS['password1'] != $GLOBALS['password2'])
			{
			echo $babBody->msgerror = bab_translate("Passwords not match !!");
			return false;
			}
		$password1=md5(strtolower($GLOBALS['password1']));
		}

	$fd = fopen($pfile, "r");
	if( $fd )
		{
		$arr = fgetcsv($fd, 4096, $separ);

		while ($arr = fgetcsv($fd, 4096, $separ))
			{
			switch($GLOBALS['duphand'])
				{
				case 1: // Replace duplicates with items imported
				case 2: // Do not import duplicates
					if( $idgroup > 0 )
						{
						$query = "select * from ".BAB_USERS_TBL." where nickname='".$arr[$GLOBALS['nickname']]."'";
						$res2 = $db->db_query($query);
						if( $db->db_num_rows($res2) > 0 && $GLOBALS['duphand'] == 2 )
							{
							break;
							}
		
						$replace = array( " " => "", "-" => "");
						$hashname = md5(strtolower(strtr($arr[$GLOBALS['givenname']].$arr[$GLOBALS['mn']].$arr[$GLOBALS['sn']], $replace)));
						$query = "select id from ".BAB_USERS_TBL." where hashname='".$hashname."'";	
						$res2 = $db->db_query($query);
						if( $res2 && $db->db_num_rows($res2) > 0 )
							{
							if($GLOBALS['duphand'] == 2 )
								break;
							$rrr = $db->db_fetch_array($res2);
							$req = "";
							while( $row = $db->db_fetch_array($res))
								{
								if( $GLOBALS[$row['name']] != "")
									{
									$req .= $row['name']."='".addslashes($arr[$GLOBALS[$row['name']]])."',";
									}
								}
							$db->db_data_seek($res,0);
							if( !empty($req))
								{
								$req = substr($req, 0, strlen($req) -1);
								$req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
								$req .= " where id_directory='0' and id_user='".$rrr['id']."'";
								$db->db_query($req);
								}
							$db->db_data_seek($res,0);
							$db->db_query("update ".BAB_USERS_TBL." set nickname='".$arr[$GLOBALS['nickname']]."', firstname='".addslashes($arr[$GLOBALS['givenname']])."', lastname='".addslashes($arr[$GLOBALS['sn']])."', email='".addslashes($arr[$GLOBALS['email']])."', hashname='".$hashname."', password='".$password1."' where id='".$rrr['id']."'");
							break;
							}
						}
					else
						{
						$res2 = $db->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where email='".$arr[$GLOBALS['email']]."' and id_directory='".$id."'");
						if( $res2 && $db->db_num_rows($res2 ) > 0 )
							{
							if( $GLOBALS['duphand'] == 2 )
								break;
							while( $arr2 = $db->db_fetch_array($res2))
								{
								$req = "";
								while( $row = $db->db_fetch_array($res))
									{
									if( $GLOBALS[$row['name']] != "" )
										{
										$req .= $row['name']."='".addslashes($arr[$GLOBALS[$row['name']]])."',";
										}
									}
								if( !empty($req))
									{
									$req = substr($req, 0, strlen($req) -1);
									$req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
									$req .= " where id='".$arr2['id']."'";
									$db->db_query($req);
									}
								$db->db_data_seek($res,0);
								}
							
							break;
							}
						}
					/* no break; */
				case 0: // Allow duplicates to be created
					$req = "";
					$arrv = array();
					while( $row = $db->db_fetch_array($res))
						{
						if( $GLOBALS[$row['name']] != "")
							{
							$req .= $row['name'].",";
							array_push( $arrv, $arr[$GLOBALS[$row['name']]]);
							}
						}
					$db->db_data_seek($res,0);
					if( !empty($req))
						{
						$req = "insert into ".BAB_DBDIR_ENTRIES_TBL." (".$req."id_directory) values (";
						for( $i = 0; $i < count($arrv); $i++)
							$req .= "'". addslashes($arrv[$i])."',";
						$req .= "'".($idgroup !=0 ? 0: $id)."')";
						$db->db_query($req);
						$idu = $db->db_insert_id();
						if( $idgroup > 0 )
							{
							$replace = array( " " => "", "-" => "");
							$hashname = md5(strtolower(strtr($arr[$GLOBALS['givenname']].$arr[$GLOBALS['mn']].$arr[$GLOBALS['sn']], $replace)));
							$hash=md5($GLOBALS['nickname'].$GLOBALS['BAB_HASH_VAR']);
							$db->db_query("insert into ".BAB_USERS_TBL." set nickname='".$arr[$GLOBALS['nickname']]."', firstname='".addslashes($arr[$GLOBALS['givenname']])."', lastname='".addslashes($arr[$GLOBALS['sn']])."', email='".addslashes($arr[$GLOBALS['email']])."', hashname='".$hashname."', password='".$password1."', confirm_hash='".$hash."', date=now(), is_confirmed='1', changepwd='1', lang='".$GLOBALS['babLanguage']."'");
							$iduser = $db->db_insert_id();
							$db->db_query("insert into ".BAB_CALENDAR_TBL." (owner, type) values ('".$iduser."', '1')");
							$db->db_query("update ".BAB_DBDIR_ENTRIES_TBL." set id_user='".$iduser."' where id='".$idu."'");
							if( $idgroup > 1 )
								$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_object, id_group) values ('".$iduser."', '".$idgroup."')");
							}
						}
					break;

				}
			}
		fclose($fd);
		unlink($pfile);
		}		
	}

function getDbContactImage($id, $idu)
	{
	$db = $GLOBALS['babDB'];
	list($idgroup) = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
	$res = $db->db_query("select photo_data, photo_type from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".($idgroup !=0 ? 0: $id)."' and id='".$idu."'");
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['photo_data'] != "" )
			{
			header("Content-type: ".$arr['photo_type']);
			echo $arr['photo_data'];
			return;
			}
		}
	$fp=fopen($GLOBALS['babSkinPath']."/images/nophoto.jpg","rb");
	if( $fp )
		{
		header("Content-type: image/jpeg");
		echo fread($fp,filesize($GLOBALS['babSkinPath']."/images/nophoto.jpg"));
		fclose($fp);
		}
	}

function getLdapContactImage($id, $cn)
	{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * , DECODE(password, \"".$GLOBALS['BAB_HASH_VAR']."\") as adpass from ".BAB_LDAP_DIRECTORIES_TBL." where id='".$id."'");

	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$ldap = new babLDAP($arr['host'], "", $arr['basedn'], $arr['userdn'], $arr['adpass'], true);
		$ldap->connect();
	
		$res = $ldap->read("cn=".$cn.",".$arr['basedn'], "objectClass=*", array("jpegphoto"));
		if( $res)
			{
			$ei = $ldap->first_entry($res);
			if( $ei)
				{
				$info = $ldap->get_values_len($ei, "jpegphoto");
				header("Content-type: image/jpeg");
				echo $info[0];
				return;
				}
			}
		}

	$fp=fopen($GLOBALS['babSkinPath']."/images/nophoto.jpg","rb");
	if( $fp )
		{
		header("Content-type: image/jpeg");
		echo fread($fp,filesize($GLOBALS['babSkinPath']."/images/nophoto.jpg"));
		fclose($fp);
		}
	}

function updateDbContact($id, $idu, $fields, $file, $tmp_file)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];

	list($idgroup, $allowuu) = $db->db_fetch_array($db->db_query("select id_group, user_update from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));

	list($iduser) = $db->db_fetch_array($db->db_query("select id_user from ".BAB_DBDIR_ENTRIES_TBL." where id='".$idu."'"));

	if(bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $id) || ($idgroup != '0' && $allowuu == "Y" && $iduser == $GLOBALS['BAB_SESS_USERID']))
		{
		$res = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");
		$req = "";
		while( $arr = $db->db_fetch_array($res))
			{
			if( isset($fields[$arr['name']]))
				{
				$rr = $db->db_fetch_array($db->db_query("select required from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup !=0 ? 0: $id)."' and id_field='".$arr['id']."'"));
				if( $rr['required'] == "Y" && empty($fields[$arr['name']]))
					{
					$babBody->msgerror = bab_translate("You must complete required fields");
					return false;
					}
				$req .= $arr['name']."='".addslashes($fields[$arr['name']])."',";
				}
			}

		if( empty($fields['sn']) || empty($fields['givenname']))
			{
			$babBody->msgerror = bab_translate( "You must complete firstname and lastname fields !!");
			return false;
			}

		if ( !empty($fields['email']) && !bab_isEmailValid($fields['email']))
			{
			$babBody->msgerror = bab_translate("Your email is not valid !!");
			return false;
			}

		if( $idgroup > 0)
			{

			$replace = array( " " => "", "-" => "");

			$hashname = md5(strtolower(strtr($fields['givenname'].$fields['mn'].$fields['sn'], $replace)));
			$query = "select * from ".BAB_USERS_TBL." where hashname='".$hashname."' and id!='".$iduser."'";	
			$res = $db->db_query($query);
			if( $db->db_num_rows($res) > 0)
				{
				$babBody->msgerror = bab_translate("Firstname and Lastname already exists !!");
				return false;
				}

			$db->db_query("update ".BAB_USERS_TBL." set firstname='".addslashes($fields['givenname'])."', lastname='".addslashes($fields['sn'])."', email='".addslashes($fields['email'])."', hashname='".$hashname."' where id='".$iduser."'");
			}
		if( !empty($file) && $file != "none")
			{
			$fp=fopen($tmp_file,"rb");
			if( $fp )
				{
				$cphoto = addslashes(fread($fp,filesize($tmp_file)));
				fclose($fp);
				}
			}
		if( !empty($cphoto))
			$req .= " photo_data='".$cphoto."'";
		else
			$req = substr($req, 0, strlen($req) -1);

		if( !empty($req))
			{
			$req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
			$req .= " where id='".$idu."'";
			$db->db_query($req);
			}
		}
	return true;
	}

function confirmAddDbContact($id, $fields, $file, $tmp_file, $password1, $password2, $nickname, $notifyuser, $sendpwd)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];

	list($idgroup) = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));

	if ( !empty($fields['email']) && !bab_isEmailValid($fields['email']))
		{
		$babBody->msgerror = bab_translate("Your email is not valid !!");
		return false;
		}

	$res = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");
	$req = "";
	while( $arr = $db->db_fetch_array($res))
		{
		$rr = $db->db_fetch_array($db->db_query("select required from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup !=0 ? 0: $id)."' and id_field='".$arr['id']."'"));
		if( $rr['required'] == "Y" && empty($fields[$arr['name']]))
			{
			$babBody->msgerror = bab_translate("You must complete required fields");
			return false;
			}
		if( $idgroup > 0 )
			$req .= $arr['name']."='".addslashes($fields[$arr['name']])."',";
		else
			$req .= $arr['name'].",";
		}

	if( $idgroup > 0 )
		{
		if( empty($password1) || empty($password2) || empty($nickname))
			{
			$babBody->msgerror = bab_translate("You must complete required fields");
			return false;
			}
		if( empty($fields['sn']) || empty($fields['givenname']))
			{
			$babBody->msgerror = bab_translate( "You must complete firstname and lastname fields !!");
			return false;
			}

		if( $password1 != $password2)
			{
			$babBody->msgerror = bab_translate("Passwords not match !!");
			return false;
			}
		if ( strlen($password1) < 6 )
			{
			$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
			return false;
			}

		$iduser = registerUser($fields['givenname'], $fields['sn'], $fields['mn'], $fields['email'], $nickname, $password1, $password2, true);
		if( $iduser == false )
			return false;
		if( $idgroup > 1 )
			{
			$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_object, id_group) values ('".$iduser."','".$idgroup."')");
			}
		
		if( $notifyuser == "Y" )
			{
			if( bab_isMagicQuotesGpcOn())
				{
				$firstname = addslashes($fields['givenname']);
				$lastname = addslashes($fields['sn']);
				}
			notifyAdminUserRegistration(bab_composeUserName($firstname , $lastname), $fields['email'], $nickname, $sendpwd == "Y"? $password1: "" );
			}
		}

	if( !empty($file) && $file != "none")
		{
		$fp=fopen($tmp_file,"rb");
		if( $fp )
			{
			$cphoto = addslashes(fread($fp,filesize($tmp_file)));
			fclose($fp);
			}
		}
	if( !empty($cphoto))
		{
		if( $idgroup > 0 )
			{
			$req .= " photo_data='".$cphoto."',";
			}
		else
			$req .= "photo_data,";
		}

	if( $idgroup > 0 && !empty($req))
		{
		list($iddbu) = $db->db_fetch_array($db->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$iduser."'"));
		$req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . substr($req, 0, strlen($req) -1);
		$req .= " where id='".$iddbu."'";
		$db->db_query($req);
		}
	else if( !empty($req))
		{
		$req = "insert into ".BAB_DBDIR_ENTRIES_TBL." (".$req."id_directory, id_user) values (";
		$db->db_data_seek($res, 0);
		while( $arr = $db->db_fetch_array($res))
			{
			$req .= "'".addslashes($fields[$arr['name']])."',";
			}
		if( !empty($cphoto))
			$req .= "'".$cphoto."',";

		if( $idgroup > 0 )
			$req .= "'0', '".$iduser."')";
		else
			$req .= "'".$id."', '0')";
		$db->db_query($req);
		}
	return true;
	}


function confirmEmptyDb($id)
	{
	global $babDB;
	list($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
	if( $idgroup != 0 ) /* Ovidentia directory */
		return;
	$babDB->db_query("delete from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".$id."'");
	}

function deleteDbContact($id, $idu)
	{
	$db = $GLOBALS['babDB'];
	list($idgroup) = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
	if( $idgroup != 0 )
		return;
	$db->db_query("delete from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".$id."' and id='".$idu."'");
	}

function exportDbDirectory($id, $wsepar, $separ)
{

	$db = $GLOBALS['babDB'];
	switch($wsepar)
		{
		case "1":
			$separ = ",";
			break;
		case "2":
			$separ = "\t";
			break;
		default:
			if( empty($separ))
				$separ = ",";
			break;
		}

	list($idgroup, $idname) = $db->db_fetch_array($db->db_query("select id_group, name from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));

	$output = "";
	if( $idgroup > 0 )
		$output .= bab_translate("Nickname").$separ;

	$res = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");
	while( $arr = $db->db_fetch_array($res))
		{
		$output .= bab_translate($arr['description']).$separ;
		}

	$output = substr($output, 0, -1);
	$output .= "\n";

	if( $idgroup > 1 )
	{
	$res2 = $db->db_query("select * from ".BAB_DBDIR_ENTRIES_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_group='".$idgroup."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_DBDIR_ENTRIES_TBL.".id_user and ".BAB_DBDIR_ENTRIES_TBL.".id_directory='0'");
	}
	else
		$res2 = $db->db_query("select * from ".BAB_DBDIR_ENTRIES_TBL." where id_directory ='".($idgroup != 0? 0: $id)."'");

	while( $row = $db->db_fetch_array($res2))
		{
		if( $idgroup > 0 )
			{
			list($nickname) = $db->db_fetch_array($db->db_query("select nickname from ".BAB_USERS_TBL." where id='".$row['id_user']."'"));
			$output .= $nickname.$separ;
			}

		$db->db_data_seek($res,0);
		while( $arr = $db->db_fetch_array($res))
			{
			$output .= $row[$arr['name']].$separ;
			}
		$output = substr($output, 0, -1);
		$output .= "\n";
		}

	header("Content-Disposition: attachment; filename=\"".$idname.".csv\""."\n");
	header("Content-Type: text/plain"."\n");
	header("Content-Length: ". strlen($output)."\n");
	header("Content-transfert-encoding: binary"."\n");
	print $output;
	exit;
}

/* main */
if(isset($id) && bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id))
	$badd = true;
else
	$badd = false;

if( !isset($idx ))
	$idx = "list";

if( !isset($pos ))
	$pos = "A";

if( isset($pfile) && !empty($pfile))
	{
	processImportDbFile($pfile, $id, $separ);
	}

if( isset($action) && $action == "Yes")
	{
	confirmEmptyDb($id);
	}

if( isset($modify))
	{
		if( $modify == "dbc" )
			{
			$idx = "dbmod";
			if(updateDbContact($id, $idu, $fields, $photof_name,$photof))
				{
				$msg = bab_translate("Your contact has been updated");
				$idx = "dbcunload";
				$fields = array();
				}
			}
		else if( $modify == "dbac" && $badd)
			{
			if(!confirmAddDbContact($id, $fields, $photof_name,$photof, $password1, $password2, $nickname, $notifyuser, $sendpwd))
				$idx = "adbc";
			else
				{
				$msg = bab_translate("Your contact has been added");
				$idx = "dbcunload";
				$fields = array();
				}
			}
	}
else if (isset($expfile) && $badd)
{
	exportDbDirectory($id, $wsepar, $separ);
	$idx = "sdb";
}


switch($idx)
	{
	case "deldbc":
		$msg = bab_translate("Your contact has been deleted");
		deleteDbContact($id, $idu);
		/* no break */
	case "dbcunload":
		contactDbUnload($msg, $refresh);
		exit();
		break;

	case "dbmod":
		modifyDbContact($id, $idu, $fields, $refresh);
		exit;
		break;
	case "getimg":
		getDbContactImage($id, $idu);
		exit;
		break;
	case "getimgl":
		getLdapContactImage($id, $cn);
		exit;
		break;

	case "ddb":
		$babBody->title = "";
		summaryDbContact($id, $idu);
		exit;
		break;

	case "adbc":
		$babBody->title = bab_translate("Add entry to").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		if($badd)
			{
			addDbContact($id, $fields);
			exit;
			}
		else
			$babBody->msgerror = bab_translate("Access denied");
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		break;

	case "sdb":
		$babBody->title = bab_translate("Database Directory").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		browseDbDirectory($id, $pos, $xf, $badd);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu("sdb", bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".$pos);
		if($badd)
			{
			$babBody->addItemMenu("dbimp", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbimp&id=".$id);
			$babBody->addItemMenu("dbexp", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbexp&id=".$id);
			}
		break;

	case "dbimp":
		$babBody->title = bab_translate("Import file to").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu("sdb", bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".$pos);
		if($badd)
			{
			importDbFile($id);
			$babBody->addItemMenu("dbimp", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbimp&id=".$id);
			$babBody->addItemMenu("dbexp", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbexp&id=".$id);
			}
		break;

	case "dbexp":
		$babBody->title = bab_translate("Import file to").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu("sdb", bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".$pos);
		if($badd)
			{
			exportDbFile($id);
			$babBody->addItemMenu("dbimp", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbimp&id=".$id);
			$babBody->addItemMenu("dbexp", bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbexp&id=".$id);
			}
		break;

	case "dbmap":
		$babBody->title = bab_translate("Import file to").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu("sdb", bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".$pos);
		if($badd)
			{
			mapDbFile($id, $uploadf_name, $uploadf, $wsepar, $separ);
			$babBody->addItemMenu("dbimp", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbimp&id=".$id);
			}
		break;

	case "dldap":
		$babBody->title = bab_translate("Summary of information about").": ".$cn;
		summaryLdapContact($id, $cn);
		exit;
		break;

	case "sldap":
		$babBody->title = bab_translate("Ldap Directory").": ".getDirectoryName($id,BAB_LDAP_DIRECTORIES_TBL);
		browseLdapDirectory($id, $pos);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu("sldap", bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sldap&id=".$id."&pos=".$pos);
		break;

	case "empdb":
		$babBody->title = bab_translate("Delete Database Directory");
		if( $badd )
			emptyDb($id);
		else
			$babBody->msgerror = bab_translate("Access denied");

		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		break;

	case "list":
	default:
		$babBody->title = "";
		listUserAds();
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>