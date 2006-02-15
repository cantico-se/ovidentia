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
include_once $babInstallPath."utilit/dirincl.php";
include_once $babInstallPath."utilit/ldap.php";
include_once $babInstallPath."utilit/tempfile.php";
include_once $babInstallPath."admin/register.php";

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
		var $altbg = true;

		function temp()
			{
			$this->directories = bab_translate("Directories");
			$this->desctxt = bab_translate("Description");
			$this->databasetitle = bab_translate("Databases Directories list");
			$this->ldaptitle = bab_translate("Ldap Directories list");
			$this->adminurlname = bab_translate("Management");
			$this->db = $GLOBALS['babDB'];
			$this->badd = false;
			$res = $this->db->db_query("select id from ".BAB_LDAP_DIRECTORIES_TBL." ORDER BY name");
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_LDAPDIRVIEW_GROUPS_TBL, $row['id']))
					{
					array_push($this->ldapid, $row['id']);
					}
				}
			$this->countldap = count($this->ldapid);

			$this->dbid = array_keys(bab_getUserDirectories());
			$this->countdb = count($this->dbid);

			if ($this->countldap == 0 && $this->countdb == 0)
				{
				$GLOBALS['babBody']->msgerror = bab_translate("Access denied");
				}
			}

		function getnextldap()
			{
			static $i = 0;
			if( $i < $this->countldap)
				{
				$this->altbg = !$this->altbg;
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
				$this->altbg = !$this->altbg;
				$arr = $this->db->db_fetch_array($this->db->db_query("select name, description, id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$this->dbid[$i]."'"));
				$this->description = $arr['description'];
				$this->adminurl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$this->dbid[$i];
				$this->url = $GLOBALS['babUrlScript']."?tg=directory&idx=sdbovml&directoryid=".$this->dbid[$i];
				$this->urlname = $arr['name'];
				$this->badd = bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $this->dbid[$i]);
				$this->baddmod = $this->badd || bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $this->dbid[$i]);
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
		var $altbg = true;

		function temp($id, $pos)
			{
			$this->allname = bab_translate("All");
			$this->sntxt = bab_translate("Name");
			$this->givennametxt = bab_translate("Firstname");
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
				$GLOBALS['babWebStat']->addLdapDirectory($id);
				$this->ldap = new babLDAP($arr['host'], "", true);
				$this->ldap->connect();
				$this->ldap->bind($arr['userdn'], $arr['adpass']);
				$this->entries = $this->ldap->search($arr['basedn'], "(|(sn=".$pos."*))", array("sn","givenname","cn", "telephonenumber", "mail", "homephone"));
				if( is_array($this->entries))
					{
					$this->count = $this->entries['count'];
					$this->order = array();
					for ($i = 0 ; $i < $this->count ; $i++)
						{
						$this->order[$i] = utf8_decode($this->entries[$i]['sn'][0]);
						}

					natcasesort($this->order);
					$this->order = array_keys($this->order);
					}
				}

			/* find prefered mail account */
			$this->db = &$GLOBALS['babDB'];
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
				$o = $this->order[$i];
				$this->altbg = !$this->altbg;
				$this->cn = "";
				$this->url = "";
				$this->btel = "";
				$this->htel = "";
				$this->email = "";
				$this->sn = utf8_decode($this->entries[$o]['sn'][0]);
				$this->givenname = utf8_decode($this->entries[$o]['givenname'][0]);
				$this->url = $GLOBALS['babUrlScript']."?tg=directory&idx=dldap&id=".$this->id."&cn=".urlencode(quoted_printable_decode($this->entries[$o]['cn'][0]))."&pos=".$this->pos;
				$this->btel = isset($this->entries[$o]['telephonenumber'][0])?utf8_decode($this->entries[$o]['telephonenumber'][0]):"";
				$this->htel = isset($this->entries[$o]['homephone'][0])?utf8_decode($this->entries[$o]['homephone'][0]):"";
				$this->email = isset($this->entries[$o]['mail'][0])?utf8_decode($this->entries[$o]['mail'][0]):"";
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
		var $altbg = true;
		function temp($id, $pos, $xf, $badd)
			{
			$this->allname = bab_translate("All");
			$this->addname = bab_translate("Add");
			$this->assignname = bab_translate("Assign");
			$this->id = $id;
			$this->pos = $pos;
			$this->badd = $badd;
			$this->xf = $xf;
			if( substr($pos,0,1) == "-" )
				{
				$this->pos = substr($pos,1);
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
			if ($_GET['idx'] == 'sdbovml')
				{
				$this->allurl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdbovml&directoryid=".$id."&pos=".($this->ord == "-"? "":$this->ord)."&xf=".$this->xf;
				}
			else
				{
				$this->allurl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".($this->ord == "-"? "":$this->ord)."&xf=".$this->xf;
				}
			$this->addurl = $GLOBALS['babUrlScript']."?tg=directory&idx=adbc&id=".$id;
			$this->count = 0;
			$this->db = &$GLOBALS['babDB'];
			$arr = $this->db->db_fetch_array($this->db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $id))
				{
				$GLOBALS['babWebStat']->addDatabaseDirectory($id);
				$this->idgroup = $arr['id_group'];
				$this->rescol = $this->db->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and ordering!='0' order by ordering asc");
				$this->countcol = $this->db->db_num_rows($this->rescol);
				}
			else
				{
				$GLOBALS['babBody']->msgerror = bab_translate("Access denied");
				$this->countcol = 0;
				$this->count = 0;
				}

			$this->bassign = false;
			if( bab_isAccessValid(BAB_DBDIRBIND_GROUPS_TBL, $id) && $arr['id_group'] && $arr['id_group'] != BAB_REGISTERED_GROUP )
				{
				$this->bassign = true;
				$this->assignurl = $GLOBALS['babUrlScript']."?tg=directory&idx=assign&id=".$id;
				}
			$this->bgroup = $arr['id_group'] > 0;

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

			$this->select = array();
			}

		function getnextcol()
			{
			static $i = 0;
			static $tmp = array();
			static $leftjoin = array();
			if( $i < $this->countcol)
				{
				$arr = $this->db->db_fetch_array($this->rescol);
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
					$this->coltxt = translateDirectoryField($rr['description']);
					$filedname = $rr['name'];
					$tmp[] = $filedname;
					$this->select[] = 'e.'.$filedname;
					}
				else
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$this->coltxt = translateDirectoryField($rr['name']);
					$filedname = "babdirf".$arr['id'];

					$leftjoin[] = 'LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$arr['id']." ON lj".$arr['id'].".id_fieldx='".$arr['id']."' AND e.id=lj".$arr['id'].".id_entry";
					$tmp[] = $filedname;
					$this->select[] = "lj".$arr['id'].'.field_value '.$filedname."";
					}

				if ($_GET['idx'] == 'sdbovml')
					{
					$this->colurl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdbovml&directoryid=".$this->id."&pos=".$this->ord.$this->pos."&xf=".$filedname;
					}
				else
					{
					$this->colurl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$this->id."&pos=".$this->ord.$this->pos."&xf=".$filedname;
					}
				$i++;
				return true;
				}
			else
				{
				if( count($tmp) > 0)
					{
					if( $this->xf == "" )
						{
						$this->xf = $tmp[0];
						}


					if( $this->idgroup > 1 )
						{
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e,
								".BAB_USERS_GROUPS_TBL." u,
								".BAB_USERS_TBL." u2 
									".implode(' ',$leftjoin)." 
									WHERE u.id_group='".$this->idgroup."' 
									AND u2.id=e.id_user 
									AND u2.disabled='0' 
									AND u.id_object=e.id_user 
									AND e.id_directory='0'";
						}
					elseif (1 == $this->idgroup) {
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e,
						".BAB_USERS_TBL." u 
						".implode(' ',$leftjoin)." 
						WHERE 
							u.id=e.id_user 
							AND u.disabled='0' 
							AND e.id_directory='0'";
						}
					else
						{
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e ".implode(' ',$leftjoin)." WHERE e.id_directory='".$this->id ."'";
						}


					$this->select[] = 'e.id';
					if( !in_array('email', $this->select))
						$this->select[] = 'e.email';

					if (!empty($this->pos) && false === strpos($this->xf, 'babdirf'))
						$like = " AND e.`".$this->xf."` LIKE '".$this->pos."%'";
					elseif (0 === strpos($this->xf, 'babdirf'))
						{
						$idfield = substr($this->xf,7);
						$like = " AND lj".$idfield.".field_value LIKE '".$this->pos."%'";
						}
					else
						$like = '';

					$req = "select ".implode(',', $this->select)." from ".$req." ".$like." order by `".$this->xf."` ";
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
				$this->altbg = !$this->altbg;
				$this->arrf = $this->db->db_fetch_array($this->res);
				$this->urlmail = $GLOBALS['babUrlScript']."?tg=mail&idx=compose&accid=".$this->accid."&to=".$this->arrf['email'];
				$this->email = $this->arrf['email'];
				
				if ($_GET['idx'] == 'sdbovml')
					{
					$this->url = $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$this->id."&userid=".$this->arrf['id']."&pos=".$this->ord.$this->pos."&xf=".$this->xf;
					}
				else
					{
					$this->url = $GLOBALS['babUrlScript']."?tg=directory&idx=ddb&id=".$this->id."&idu=".$this->arrf['id'];
					}
				$this->urledir = $GLOBALS['babUrlScript']."?tg=directory&idx=ddbed&id=".$this->id."&idu=".$this->arrf['id'];
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
				$this->coltxt = nl2br(stripslashes(bab_translate($this->arrf[$i])));
				$this->mailcol = $this->arrf[$i] == $this->email && $this->email != '' ? true : false;
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
				if ($_GET['idx'] == 'sdbovml')
					{
					$this->selecturl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdbovml&directoryid=".$this->id."&pos=".($this->ord == "-"? "":$this->ord).$this->selectname."&xf=".$this->xf;
					}
				else
					{
					$this->selecturl = $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$this->id."&pos=".($this->ord == "-"? "":$this->ord).$this->selectname."&xf=".$this->xf;
					}
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
	return $temp->bgroup;

}

function browseDbDirectoryWithOvml($badd)
{
	global $babBody, $babDB;

	$args = &$_GET;

	if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $args['directoryid']))
		{
		$arr = $babDB->db_fetch_array($babDB->db_query("select id_group, ovml_list from ".BAB_DB_DIRECTORIES_TBL." where id='".$args['directoryid']."'"));

		if( !empty($arr['ovml_list']))
			{
			$GLOBALS['babWebStat']->addDatabaseDirectory($args['directoryid']);
			$args['DirectoryUrl'] = $GLOBALS['babUrlScript']."?tg=directory&idx=sdbovml";
			if( !isset($args['order'])) { $args['order'] = 'asc'; }
			if( !isset($args['orderby'])) { $args['orderby'] = ''; }
			if( !isset($args['like'])) { $args['like'] = 'A'; }
			$babBody->babecho(bab_printOvmlTemplate( $arr['ovml_list'], $args ));
			}
		else
			{
			if( !isset($GLOBALS['pos'])) { $GLOBALS['pos'] = 'A'; }
			if( !isset($GLOBALS['xf'])) { $GLOBALS['xf'] = ''; }
			return browseDbDirectory($args['directoryid'], $GLOBALS['pos'], $GLOBALS['xf'], $badd);
			}
		return $arr['id_group'];
		}
	else
		return '';
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
				$this->ldap = new babLDAP($arr['host'], "", true);
				$this->ldap->connect();
				$this->ldap->bind($arr['userdn'], $arr['adpass']);
				$this->entries = $this->ldap->search($arr['basedn'],"(|(cn=".$cn."))");
				$this->ldap->close();
				$this->name = utf8_decode($this->entries[0]['cn'][0]);
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
				$this->fieldn = translateDirectoryField($arr['description']);
				$this->fieldv = isset($this->entries[0][$arr['x_name']][0]) ? utf8_decode($this->entries[0][$arr['x_name']][0]) : '';
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
			$this->file = bab_translate("Photo");
			$this->update = bab_translate("Update");
			$this->id = $id;
			
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

			$this->db = &$GLOBALS['babDB'];
			
			$arr = $this->db->db_fetch_array($this->db->db_query("select id_group, user_update from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			$this->idgroup = $arr['id_group'];
			$allowuu = $arr['user_update'];

			$personnal = false;

			if (false === $idu)
				{
				$req = "select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'";
				list($idu) = $this->db->db_fetch_array($this->db->db_query($req));
				$personnal = true;
				}
			else
				{
				$req = "select id from ".BAB_DBDIR_ENTRIES_TBL." where id='".$idu."' AND id_user='".$GLOBALS['BAB_SESS_USERID']."'";
				$res =$this->db->db_query($req);
				$personnal = $this->db->db_num_rows($res) > 0;
				}

			$this->idu = $idu;

			if ( (!$this->bupd && $allowuu == 'N') || (!$this->bupd && $allowuu == 'Y' && !$personnal ) )
				{
				die( bab_translate('Access denied'));
				}

			$this->showph = false;
			$res = $this->db->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and id='".$idu."'");
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$this->arr = $this->db->db_fetch_array($res);
				$res = $this->db->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$idu."'");
				while( $arr = $this->db->db_fetch_array($res))
					{
					$this->arr['babdirf'.$arr['id_fieldx']] = $arr['field_value'];
					}

				$this->name = stripslashes($this->arr['givenname']. " ". $this->arr['sn']);
				if( $this->arr['plen'] > 0 )
					{
					$this->showph = true;
					$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$this->id."&idu=".$idu;
					$this->delete = bab_translate("Delete this picture");
					}
				
				if( $this->bupd == false && $allowuu == "Y" && $this->arr['id_user'] == $GLOBALS['BAB_SESS_USERID'] )
					$this->bupd = true;
				}
			else
				{
				$this->name = "";
				$this->urlimg = "";
				}

			$res = $this->db->db_query("select modifiable, required from ".BAB_DBDIR_FIELDSEXTRA_TBL." join ".BAB_DBDIR_FIELDS_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and id_field=".BAB_DBDIR_FIELDS_TBL.".id and ".BAB_DBDIR_FIELDS_TBL.".name='jpegphoto'");

			$this->modify = false;
			$this->phrequired = false;
			$this->delph = false;
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				if( $this->badd || ($this->bupd && $arr['modifiable'] == "Y"))
					{
					$this->modify = true;
					if ($this->bupd && $arr['modifiable'] == "Y")
						$this->delph = true;
					}

				if ($arr['required'] == 'Y')
					{
					$this->phrequired = true;
					$this->delph = false;
					}
				}

			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and disabled='N' order by list_ordering asc");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				{
				$this->count = $this->db->db_num_rows($this->res);
				}
			else
				{
				$this->count = 0;
				}
			
			}
		
		function getnextfield(&$skip)
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$res = $this->db->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'");
					$rr = $this->db->db_fetch_array($res);
					$this->fieldn = translateDirectoryField($rr['description']);
					$this->fieldv = $rr['name'];
					}
				else
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$this->fieldn = translateDirectoryField($rr['name']);
					$this->fieldv = "babdirf".$arr['id'];
					}

				if( $this->fieldv == 'jpegphoto' )
					{
					$skip = true;
					$i++;
					return true;
					}

				if( isset($this->fields[$this->fieldv]) )
					{
					$this->fvalue = stripslashes($this->fields[$this->fieldv]);
					}
				else
					{
					$this->fvalue = isset($this->arr[$this->fieldv])? stripslashes($this->arr[$this->fieldv]): '';
					}

				$this->fvalue = htmlentities($this->fvalue);

				$this->resfxv = $this->db->db_query("select field_value from ".BAB_DBDIR_FIELDSVALUES_TBL." where id_fieldextra='".$arr['id']."'");
				$this->countfxv = $this->db->db_num_rows($this->resfxv); 

				$this->required = $arr['required'];
				if( $this->countfxv == 0  )
					{
					$this->multivalues = false;
					}
				elseif( $this->countfxv > 1  )
					{
					$this->multivalues = true;
					}
				else
					{
					$this->multivalues = $arr['multi_values'] == 'Y'? true: false;
					}
				$this->fieldt = $arr['multilignes'];

				if( $this->badd || ($this->bupd && $arr['modifiable'] == "Y"))
					{
					$this->modify = true;
					}
				else
					{
					$this->modify = false;
					if( empty($this->fvalue))
						{
						$skip =true;
						$i++;
						return true;
						}
					}


				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextfxv()
			{
			static $i = 0;
			if( $i < $this->countfxv)
				{
				$arr = $this->db->db_fetch_array($this->resfxv);
				$this->fxvvalue = $arr['field_value'];
				if( $this->fvalue == $this->fxvvalue )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
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
			$this->file = bab_translate("Photo");
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

			$this->db = &$GLOBALS['babDB'];

			$this->name = "";
			$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$id."&idu=";
			$this->name = bab_translate("Add new contact");


			

			list($this->idgroup) = $this->db->db_fetch_array($this->db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			if( $this->idgroup >= 1 )
				{
				$iddir = 0;
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
				{
				$iddir = $id;
				$this->buserinfo = false;
				}


			$this->phrequired = false;
			
			$res = $this->db->db_query("
				select 
					modifiable, required 
				from 
					".BAB_DBDIR_FIELDSEXTRA_TBL." 
				join ".BAB_DBDIR_FIELDS_TBL." f 
					
				where 
					id_directory='".($this->idgroup > 0 ? 0 : $this->id)."' 
					and id_field=f.id 
					and f.name='jpegphoto' 
					AND disabled ='N' 
				");

			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_assoc($res);
				$this->phrequired = &$arr['required'];
				$this->modify = true;
				}
			else
				$this->modify = false;

			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$iddir."' and disabled='N' order by id_field asc");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				{
				$this->count = $this->db->db_num_rows($this->res);
				}
			else
				{
				$this->count = 0;
				}
			
			}
		
		function getnextfield(&$skip)
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->modify = true;
				$arr = $this->db->db_fetch_array($this->res);
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$res = $this->db->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'");
					$rr = $this->db->db_fetch_array($res);
					$this->fieldn = translateDirectoryField($rr['description']);
					$this->fieldv = $rr['name'];
					}
				else
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$this->fieldn = translateDirectoryField($rr['name']);
					$this->fieldv = "babdirf".$arr['id'];
					}

				if( $this->fieldv == 'jpegphoto' )
					{
					$skip = true;
					$i++;
					return true;
					}

				if( isset($this->fields[$this->fieldv]) )
					{
					if (bab_isMagicQuotesGpcOn())
						$this->fvalue = stripslashes($this->fields[$this->fieldv]);
					else
						$this->fvalue = $this->fields[$this->fieldv];
					}
				else
					{
					$this->fvalue = "";
					}

				$this->resfxv = $this->db->db_query("select field_value from ".BAB_DBDIR_FIELDSVALUES_TBL." where id_fieldextra='".$arr['id']."' ORDER BY field_value");
				$this->countfxv = $this->db->db_num_rows($this->resfxv); 

				$this->required = $arr['required'];
				if( $this->countfxv == 0  )
					{
					$this->multivalues = false;
					}
				elseif( $this->countfxv > 1  )
					{
					$this->multivalues = true;
					}
				else
					{
					$this->multivalues = $arr['multi_values'] == 'Y'? true: false;
					}

				$this->fieldt = $arr['multilignes'];
				if( !empty( $arr['default_value'] ) && empty($this->fvalue) && $this->countfxv > 0)
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select field_value from ".BAB_DBDIR_FIELDSVALUES_TBL." where id='".$arr['default_value']."'"));
					$this->fvalue = $rr['field_value'];
					}

				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextfxv()
			{
			static $i = 0;
			if( $i < $this->countfxv)
				{
				$arr = $this->db->db_fetch_array($this->resfxv);
				$this->fxvvalue = $arr['field_value'];
				if( $this->fvalue == $this->fxvvalue )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
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
			global $babDB;
			$this->id = $id;
			$this->export = bab_translate("Export");
			$this->separator = bab_translate("Separator");
			$this->other = bab_translate("Other");
			$this->comma = bab_translate("Comma");
			$this->tab = bab_translate("Tab");

			$this->infotxt = bab_translate("Specify which fields will be exported");
			$this->listftxt = "---- ".bab_translate("Fields")." ----";
			$this->listdftxt = "---- ".bab_translate("Fields to export")." ----";

			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");

			$arr = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			if( $arr['id_group'] != 0 )
				{
				$iddir = 0;
				}
			else
				{
				$iddir = $id;
				}

			$this->resfd = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXPORT_TBL." where id_directory='".$id."' and id_user='".$GLOBALS['BAB_SESS_USERID']."' AND id_field<>5 order by ordering asc");
			$this->countfd = $babDB->db_num_rows($this->resfd);
			$arrexp = array(5);
			if( $this->countfd )
				{
				while( $arr = $babDB->db_fetch_array($this->resfd) )
					{
					$arrexp[] = $arr['id_field'];
					}
				$babDB->db_data_seek($this->resfd,0);
				}
		
			$this->resf = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$iddir."' and id_field NOT IN(".implode(',',$arrexp).")  order by list_ordering asc");
			$this->countf = $babDB->db_num_rows($this->resf);

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
					$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
					$this->fieldval = translateDirectoryField($arr['description']);
					}
				else
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($this->fid - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
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
					$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
					$this->fieldval = translateDirectoryField($arr['description']);
					}
				else
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($this->fid - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
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
				$this->t_dupinfo = bab_translate("Entries with the same nickname are duplicates");
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
				{
				$this->t_dupinfo = bab_translate("Entries with the same e-mail address are duplicates");
				$this->buserinfo = false;
				}

			$this->id = $id;
			$this->pfile = $pfile;

			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."'");
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

		function getnextfield(&$skip)
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$res = $this->db->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'");
					$rr = $this->db->db_fetch_array($res);
					$this->ofieldname = translateDirectoryField($rr['description']);
					$this->ofieldv = $rr['name'];
					}
				else
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$this->ofieldname = translateDirectoryField($rr['name']);
					$this->ofieldv = "babdirf".$arr['id'];
					}

				$this->required = $arr['required'];
				if( $this->ofieldv == 'jpegphoto' )
					{
					$skip = true;
					$i++;
					return true;
					}

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
				if( isset($this->ofieldname) && strtolower($this->ofieldname) == strtolower($this->ffieldname) )
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


function dbEntryDirectories($id, $idu)
{
	global $babBody;

	class dbEntryDirectoriesCls
		{
		function dbEntryDirectoriesCls($id, $idu)
			{
			global $babDB;

			list($iduser) = $babDB->db_fetch_row($babDB->db_query("select id_user from ".BAB_DBDIR_ENTRIES_TBL." where id='".$idu."'"));
			if( $iduser == 0 )
				{
				die( bab_translate('Access denied') );
				}

			$this->directorytxt = bab_translate("Directories");
			$this->desctxt = bab_translate("Description");
			$this->membertxt = bab_translate("is member of the following directories");

			$this->fullname = bab_getUserName($iduser);
			$groups = bab_getUserGroups($iduser);
			$res = $babDB->db_query("select id, name, description, id_group from ".BAB_DB_DIRECTORIES_TBL." where id_group!=0 order by name asc");

			$this->iddirectories = array();
			while ( $arr = $babDB->db_fetch_array($res))
				{
				if (bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL,$arr['id']))
					{
					if( $arr['id_group'] == BAB_REGISTERED_GROUP )
						{
						$this->iddirectories[] = $arr;
						}
					else if( count($groups) > 0 && in_array($arr['id_group'],$groups['id']))
						{
						$this->iddirectories[] = $arr;
						}
					}
				}

			$this->count = count($this->iddirectories);
			}
		
		function getnextdb()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->iddirectories[$i];
				$this->dbname = $arr['name'];
				$this->dbdescription = $arr['description'];
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

	$temp = new dbEntryDirectoriesCls($id, $idu);
	echo bab_printTemplate($temp, "directory.html", "dbentrydirectories");
}





function assignList($id, $pos)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $urlname;
		var $url;
				
		var $fullnameval;

		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $idvr;

		var $pos;
		var $selected;
		var $allselected;
		var $allurl;
		var $allname;
		var $checkall;
		var $uncheckall;
		var $deletealt;
		var $modify;
		var $altbg = true;


		function temp($id, $pos)
			{
			$this->allname = bab_translate("All");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->modify = bab_translate("Assign");
			$this->t_close = bab_translate("Close");

			$this->id = $id;
			$this->refresh = 1;

			$this->db = $GLOBALS['babDB'];

			$arrgrpids = array();
			$res = $this->db->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL." where id != '".$id."' and id_group != 0");
			while( $arr = $this->db->db_fetch_array($res))
				{
				if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $arr['id']) )
					{
					$arrgrpids[] = $arr['id_group'];
					}
				}

			$this->bview = false;
			if( count($arrgrpids) )
				{

				$this->bview = true;
//*
				if( in_array(BAB_REGISTERED_GROUP, $arrgrpids))
					{
					$arrgrpids = false;
					}

				if( isset($pos[0]) && $pos[0] == "-" )
					{
					$this->pos = $pos[1];
					$this->ord = $pos[0];
					if( $arrgrpids === false )
						{
						$req = "select ut.id, ut.firstname, ut.lastname from ".BAB_USERS_TBL." ut where ut.disabled=0 and lastname like '".$this->pos."%' order by lastname, firstname asc";
						}
					else
						{
						$req = "select distinct ut.id, ut.firstname, ut.lastname from ".BAB_USERS_TBL." ut left join ".BAB_USERS_GROUPS_TBL." ug on ut.id=ug.id_object where ut.disabled=0 and ug.id in (".implode(',', $arrgrpids).") and lastname like '".$this->pos."%' order by lastname, firstname asc";
						}

					$this->fullname = bab_translate("Lastname"). " " . bab_translate("Firstname");

					$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=vacadma&idx=lvrp&chg=&pos=".$this->ord.$this->pos."&idvr=".$this->idvr;
					}
				else
					{
					$this->pos = $pos;
					$this->ord = "";
					if( $arrgrpids === false )
						{
						$req = "select ut.id, ut.firstname, ut.lastname from ".BAB_USERS_TBL." ut where  ut.disabled=0 and  firstname like '".$this->pos."%' order by firstname, lastname asc";
						}
					else
						{
						$req = "select distinct ut.id, ut.firstname, ut.lastname from ".BAB_USERS_TBL." ut left join ".BAB_USERS_GROUPS_TBL." ug on ut.id=ug.id_object where ut.disabled=0 and ug.id in (".implode(',', $arrgrpids).") and firstname like '".$this->pos."%' order by firstname, lastname asc";
						}

					$this->fullname = bab_translate("Firstname"). " " . bab_translate("Lastname");
					$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=directory&idx=assign&chg=&pos=".$this->ord.$this->pos."&id=".$this->id;
					}
				$this->res = $this->db->db_query($req);
				$this->count = $this->db->db_num_rows($this->res);

				if( empty($this->pos))
					$this->allselected = 1;
				else
					$this->allselected = 0;
				$this->allurl = $GLOBALS['babUrlScript']."?tg=directory&idx=assign&pos=&id=".$this->id;
				}
			else
				{
				$this->count = 0;
				$GLOBALS['babBody']->msgerror = bab_translate("Access denied");
				}
//*/
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->selected = "";

				$this->altbg = !$this->altbg;

				
				$this->url = $GLOBALS['babUrlScript']."?tg=directory&idx=assign&id=".$this->id."&pos=".$this->ord.$this->pos;
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);

				$this->userid = $this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextselect()
			{
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = $t[$k];
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=directory&idx=assign&pos=".$this->ord.$this->selectname."&id=".$this->id;
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

	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = $GLOBALS['babBody']->msgerror;
	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp, "directory.html", "assignlist"));
	printBabBodyPopup();
	}


function confirmAssignEntry($id, $fields, $idauser, $idatype)
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

		function temp($id, $fields, $idauser, $idatype)
			{
			global $babDB;

			$this->id = $id;
			$this->idauser = $idauser;
			$this->fields =& $fields;
			$arr = $babDB->db_fetch_array($babDB->db_query("select ut.nickname,det.sn, det.givenname, det.mn from ".BAB_DBDIR_ENTRIES_TBL." det left join ".BAB_USERS_TBL." ut on ut.id = det.id_user where id_user='".$idauser."' and id_directory='0'"));
			list($this->directoryname) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			$this->fullnametxt = bab_translate("Fullname");
			$this->nicknametxt = bab_translate("Nickname");
			$this->usernickname = $arr['nickname'];
			$this->userfullname = bab_getUserName($idauser);
			if( $idatype == 'nickname' )
				{
				$this->warning = bab_translate("WARNING: User with this nickname already exist");
				}
			else
				{
				$this->warning = bab_translate("WARNING: User with this fullname already exist");
				}

			$this->message = bab_translate("Would you like to assign this user to the current directory");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			}

		function getnextfield()
			{
			if (list($this->fieldname, $this->fieldvalue) = each($this->fields))
				return true;
			else
				return false;
			}
		}

	$temp = new temp($id, $fields, $idauser, $idatype);
	echo bab_printTemplate($temp,"directory.html", "confirmassignuser");

}


function processImportDbFile( $pfile, $id, $separ )
	{
	global $babBody;

	$db = $GLOBALS['babDB'];
	list($idgroup) = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));

	$arridfx = array();
	$arrnamef = array();
	$res = $db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $id)."'");
	while( $arr = $db->db_fetch_array($res))
		{
		if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
			$rr = $db->db_fetch_array($db->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
			$fieldname = $rr['name'];
			$arrnamef[] = $fieldname;
			}
		else
			{
			$rr = $db->db_fetch_array($db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
			$fieldname = "babdirf".$arr['id'];
			$arridfx[] = $arr['id'];
			}

		if( $arr['required'] == "Y" && (!isset($GLOBALS[$fieldname]) || $GLOBALS[$fieldname] == "" ))
			{
			$babBody->msgerror = bab_translate("You must complete required fields");
			return false;
			}

		}

	if( $idgroup > 0 )
		{
		if( empty($GLOBALS['password1']) || empty($GLOBALS['password2']) || strlen($GLOBALS['nickname']) == 0)
			{
			$babBody->msgerror = bab_translate("You must complete required fields");
			return false;
			}

		if( !isset($GLOBALS['sn']) || $GLOBALS['sn'] == "" || !isset($GLOBALS['givenname']) || $GLOBALS['givenname'] == "")
			{
			$babBody->msgerror = bab_translate( "You must complete firstname and lastname fields !!");
			return false;
			}

		if ( strlen($GLOBALS['password1']) < 6 )
			{
			$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
			return false;
			}

		if( $GLOBALS['password1'] != $GLOBALS['password2'])
			{
			$babBody->msgerror = bab_translate("Passwords not match !!");
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

							for( $k =0; $k < count($arrnamef); $k++ )
								{
								if( isset($GLOBALS[$arrnamef[$k]]) && $GLOBALS[$arrnamef[$k]] != "")
									{
									$req .= $arrnamef[$k]."='".addslashes($arr[$GLOBALS[$arrnamef[$k]]])."',";
									}
								}

							$bupdate = false;
							if( !empty($req))
								{
								$req = substr($req, 0, strlen($req) -1);
								$req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
								$req .= " where id_directory='0' and id_user='".$rrr['id']."'";
								$db->db_query($req);
								$bupdate = true;
								}

							if( count($arridfx) > 0 )
								{
								list($idu) = $db->db_fetch_array($db->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$rrr['id']."'"));
								for( $k=0; $k < count($arridfx); $k++ )
									{
									if( isset($arr[$GLOBALS["babdirf".$arridfx[$k]]]) )
										{
										$bupdate = true;
										$rs = $db->db_query("select id from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$arridfx[$k]."' and  id_entry='".$idu."'");
										if( $rs && $db->db_num_rows($rs) > 0 )
											{
											$db->db_query("update ".BAB_DBDIR_ENTRIES_EXTRA_TBL." set field_value='".addslashes($arr[$GLOBALS["babdirf".$arridfx[$k]]])."' where id_fieldx='".$arridfx[$k]."' and id_entry='".$idu."'");
											}
										else
											{
											$db->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." ( field_value, id_fieldx, id_entry) values ('".addslashes($arr[$GLOBALS["babdirf".$arridfx[$k]]])."', '".$arridfx[$k]."', '".$idu."')");
											}
										}
									}
								}

							$db->db_query("update ".BAB_USERS_TBL." set nickname='".$arr[$GLOBALS['nickname']]."', firstname='".addslashes($arr[$GLOBALS['givenname']])."', lastname='".addslashes($arr[$GLOBALS['sn']])."', email='".addslashes($arr[$GLOBALS['email']])."', hashname='".$hashname."', password='".$password1."' where id='".$rrr['id']."'");
							if( $bupdate )
								{
								$db->db_query("update ".BAB_DBDIR_ENTRIES_TBL." set date_modification=now(), id_modifiedby='".$GLOBALS['BAB_SESS_USERID']."' where id_directory='0' and id_user='".$rrr['id']."'");
								}
							break;
							}
						}
					else
						{
						$res2 = $db->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where email='".(isset($arr[$GLOBALS['email']]) ? $arr[$GLOBALS['email']] : '')."' and id_directory='".$id."'");
						if( $res2 && $db->db_num_rows($res2 ) > 0 )
							{
							if( $GLOBALS['duphand'] == 2 )
								break;
							else
								{
								$arr2 = $db->db_fetch_array($res2);
								}
							

							$req = "";
							for( $k =0; $k < count($arrnamef); $k++ )
								{
								if( isset($GLOBALS[$arrnamef[$k]]) && $GLOBALS[$arrnamef[$k]] != "")
									{
									$req .= $arrnamef[$k]."='".addslashes($arr[$GLOBALS[$arrnamef[$k]]])."',";
									}
								}

							$bupdate = false;
							if( !empty($req))
								{
								$req = substr($req, 0, strlen($req) -1);
								$req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
								$req .= " where id='".$arr2['id']."'";
								$db->db_query($req);
								$bupdate = true;
								}

							if( count($arridfx) > 0 )
								{
								$bupdate = true;
								for( $k=0; $k < count($arridfx); $k++ )
									{
									if( isset($arr[$GLOBALS["babdirf".$arridfx[$k]]]) )
										{
										$bupdate = true;
										$rs = $db->db_query("select id from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$arridfx[$k]."' and  id_entry='".$arr2['id']."'");
										if( $rs && $db->db_num_rows($rs) > 0 )
											{
											$db->db_query("update ".BAB_DBDIR_ENTRIES_EXTRA_TBL." set field_value='".addslashes($arr[$GLOBALS["babdirf".$arridfx[$k]]])."' where id_fieldx='".$arridfx[$k]."' and id_entry='".$arr2['id']."'");
											}
										else
											{
											$db->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." ( field_value, id_fieldx, id_entry) values ('".addslashes($arr[$GLOBALS["babdirf".$arridfx[$k]]])."', '".$arridfx[$k]."', '".$arr2['id']."')");
											}
										}
									}
								}
							if( $bupdate )
								{
								$db->db_query("update ".BAB_DBDIR_ENTRIES_TBL." set date_modification=now(), id_modifiedby='".$GLOBALS['BAB_SESS_USERID']."' where id='".$arr2['id']."'");
								}
							break;
							}
						}
					/* no break; */
				case 0: // Allow duplicates to be created
					$req = "";
					$arrv = array();
					for( $k =0; $k < count($arrnamef); $k++ )
						{
						if( isset($GLOBALS[$arrnamef[$k]]) && $GLOBALS[$arrnamef[$k]] != "")
							{
							$req .= $arrnamef[$k].",";
							$val = isset($arr[$GLOBALS[$arrnamef[$k]]]) ? $arr[$GLOBALS[$arrnamef[$k]]] : '';
							array_push( $arrv, $val);
							}
						}

					if( !empty($req))
						{
						$req = "insert into ".BAB_DBDIR_ENTRIES_TBL." (".$req."id_directory,date_modification,id_modifiedby) values (";
						for( $i = 0; $i < count($arrv); $i++)
							$req .= "'". addslashes($arrv[$i])."',";
						$req .= "'".($idgroup !=0 ? 0: $id)."')";
						$req .= "now(), '".$GLOBALS['BAB_SESS_USERID']."',";
						$db->db_query($req);
						$idu = $db->db_insert_id();
						if( $idgroup > 0 )
							{
							$replace = array( " " => "", "-" => "");
							$hashname = md5(strtolower(strtr($arr[$GLOBALS['givenname']].$arr[$GLOBALS['mn']].$arr[$GLOBALS['sn']], $replace)));
							$hash=md5($arr[$GLOBALS['nickname']].$GLOBALS['BAB_HASH_VAR']);
							$db->db_query("insert into ".BAB_USERS_TBL." set nickname='".$arr[$GLOBALS['nickname']]."', firstname='".addslashes($arr[$GLOBALS['givenname']])."', lastname='".addslashes($arr[$GLOBALS['sn']])."', email='".addslashes($arr[$GLOBALS['email']])."', hashname='".$hashname."', password='".$password1."', confirm_hash='".$hash."', date=now(), is_confirmed='1', changepwd='1', lang='".$GLOBALS['babLanguage']."'");
							$iduser = $db->db_insert_id();
							$db->db_query("insert into ".BAB_CALENDAR_TBL." (owner, type) values ('".$iduser."', '1')");
							$db->db_query("update ".BAB_DBDIR_ENTRIES_TBL." set id_user='".$iduser."' where id='".$idu."'");
							if( $idgroup > 1 )
								{
								bab_addUserToGroup($iduser, $idgroup);
								}
							}

						if( count($arridfx) > 0 )
							{
							for( $k=0; $k < count($arridfx); $k++ )
								{
								$val = isset($arr[$GLOBALS["babdirf".$arridfx[$k]]]) ? addslashes($arr[$GLOBALS["babdirf".$arridfx[$k]]]) : '';
								$db->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." (id_fieldx, id_entry, field_value) values('".$arridfx[$k]."','".$idu."','".$val."')");
								}
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
	$db = &$GLOBALS['babDB'];
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
		$ldap = new babLDAP($arr['host'], "", true);
		$ldap->connect();
		$ldap->bind($arr['userdn'], $arr['adpass']);
	
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

function updateDbContact($id, $idu, $fields, $file, $tmp_file, $photod)
	{
	global $babBody;
	$db = &$GLOBALS['babDB'];

	list($idgroup, $allowuu) = $db->db_fetch_array($db->db_query("select id_group, user_update from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));


	$usertbl = $db->db_fetch_assoc($db->db_query("select id_user,givenname,mn,sn from ".BAB_DBDIR_ENTRIES_TBL." where id='".$idu."'"));

	$iduser = &$usertbl['id_user'];


	if(bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $id) || ($idgroup != '0' && $allowuu == "Y" && $iduser == $GLOBALS['BAB_SESS_USERID']))
		{
		$res = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL."");
		$req = "";
		while( $arr = $db->db_fetch_array($res))
			{

			$rr = $db->db_fetch_array($db->db_query("select required, modifiable from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup !=0 ? 0: $id)."' and id_field='".$arr['id']."'"));

			if ($arr['name'] == 'jpegphoto' && $rr['required'] == "Y" && (empty($file) || $file == "none"))
				{
				$tmp = $db->db_fetch_assoc($db->db_query("select photo_data from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".($idgroup !=0 ? 0: $id)."' and id='".$idu."'"));

				if (empty($tmp['photo_data']))
					{
					$babBody->msgerror = bab_translate("You must complete required fields");
					return false;
					}
				}


			if( isset($fields[$arr['name']]))
				{
				if( $arr['name'] != 'jpegphoto' && $rr['required'] == "Y" && empty($fields[$arr['name']]))
					{
					$babBody->msgerror = bab_translate("You must complete required fields");
					return false;
					}

				$req .= $arr['name']."='".addslashes($fields[$arr['name']])."',";
				}

			if (($arr['name'] == 'sn' && empty($fields['sn']) && $rr['modifiable'] == 'Y') || ($arr['name'] == 'givenname' && empty($fields['sn']) && $rr['modifiable'] == 'Y'))
				{
				$babBody->msgerror = bab_translate( "You must complete firstname and lastname fields !!");
				return false;
				}
			}


		if ( !empty($fields['email']) && !bab_isEmailValid($fields['email']))
			{
			$babBody->msgerror = bab_translate("Your email is not valid !!");
			return false;
			}

		if( $idgroup > 0)
			{

			$replace = array( " " => "", "-" => "");


			if (!isset($fields['givenname']))
				$fields['givenname'] = $usertbl['givenname'];
				
			if (!isset($fields['mn']))
				$fields['mn'] = $usertbl['mn'];

			if (!isset($fields['sn']))
				$fields['sn'] = $usertbl['sn'];
			
			
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
			if ($babBody->babsite['imgsize'] > 0 && $babBody->babsite['imgsize']*1000 < filesize($tmp_file))
				{
				$babBody->msgerror = bab_translate("The image file is too big, maximum is :").$babBody->babsite['imgsize'].bab_translate("Kb");
				return false;
				}
			$fp=fopen($tmp_file,"rb");
			if( $fp )
				{
				$cphoto = addslashes(fread($fp,filesize($tmp_file)));
				fclose($fp);
				}
			}


		foreach( $fields as $key => $value )
			{
			$value = trim($value);
			if( empty($value) && substr($key, 0, strlen("babdirf")) == 'babdirf' )
				{
				$tmp = substr($key, strlen("babdirf"));
				$rs = $db->db_query("select d.name from ".BAB_DBDIR_FIELDSEXTRA_TBL." e,".BAB_DBDIR_FIELDS_DIRECTORY_TBL." d where e.id='".$tmp."' AND e.required='Y' AND d.id=(e.id_field-'".BAB_DBDIR_MAX_COMMON_FIELDS."')");
				if( $rs && $db->db_num_rows($rs) > 0 )
					{
					list($name) = $db->db_fetch_array($rs);
					$babBody->msgerror = bab_translate( "You must complete").' '.$name;
					return false;
					}
				}
			}




		if( !empty($cphoto))
			$req .= " photo_data='".$cphoto."'";
		elseif ($photod == "delete")
			$req .= " photo_data=''";
		else
			$req = substr($req, 0, strlen($req) -1);

		$bupdate = false;

		if( !empty($req))
			{
			$req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
			$req .= " where id='".$idu."'";
			$db->db_query($req);
			$bupdate = true;
			}
		

		foreach( $fields as $key => $value )
			{
			if( substr($key, 0, strlen("babdirf")) == 'babdirf' )
				{
				$tmp = substr($key, strlen("babdirf"));
				if( !bab_isMagicQuotesGpcOn())
					{
					$value = addslashes($value);
					}

				$bupdate = true;
				$rs = $db->db_query("select id from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$tmp."' and  id_entry='".$idu."'");
				if( $rs && $db->db_num_rows($rs) > 0 )
					{
					$db->db_query("update ".BAB_DBDIR_ENTRIES_EXTRA_TBL." set field_value='".$value."' where id_fieldx='".$tmp."' and id_entry='".$idu."'");
					}
				else
					{
					$db->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." ( field_value, id_fieldx, id_entry) values ('".$value."', '".$tmp."', '".$idu."')");
					}
				}
			}

		if( $bupdate )
			{
			$db->db_query("update ".BAB_DBDIR_ENTRIES_TBL." set date_modification=now(), id_modifiedby='".$GLOBALS['BAB_SESS_USERID']."' where id='".$idu."'");
			}
		}

	return true;
	}

function confirmAddDbContact($id, $fields, $file, $tmp_file, $password1, $password2, $nickname, $notifyuser, $sendpwd)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];
	$bassign = false;

	list($idgroup) = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));

	if ( !empty($fields['email']) && !bab_isEmailValid($fields['email']))
		{
		$babBody->msgerror = bab_translate("Your email is not valid !!");
		return 0;
		}

	if( !empty($file) && $file != "none")
		{
		if ($babBody->babsite['imgsize'] > 0 && $babBody->babsite['imgsize']*1000 < filesize($tmp_file))
			{
			$babBody->msgerror = bab_translate("The image file is too big, maximum is :").$babBody->babsite['imgsize'].bab_translate("Kb");
			return 0;
			}
		}

	if( $idgroup > 0 )
		{

		if( bab_isAccessValid(BAB_DBDIRBIND_GROUPS_TBL, $id) && $idgroup != BAB_REGISTERED_GROUP)
			{
			$bassign = true;
			}

		if( empty($nickname))
			{
			$babBody->msgerror = bab_translate("You must complete required fields");
			return 0;
			}

		if( $bassign )
			{
			$res = $db->db_query("select id from ".BAB_USERS_TBL." where nickname='".$db->db_escape_string($nickname)."'");
			if( $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$GLOBALS['idauser'] = $arr['id'];
				$GLOBALS['idatype'] = 'nickname';
				//**************
				return 2;
				}
			}

		if( empty($fields['sn']) || empty($fields['givenname']))
			{
			$babBody->msgerror = bab_translate( "You must complete firstname and lastname fields !!");
			return 0;
			}

		if( $bassign )
			{
			$res = $db->db_query("select id_user from ".BAB_DBDIR_ENTRIES_TBL." where givenname='".$db->db_escape_string($fields['givenname'])."' and sn='".$db->db_escape_string($fields['sn'])."' and mn='".$db->db_escape_string($fields['mn'])."' and id_directory='0'");
			if( $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$GLOBALS['idauser'] = $arr['id_user'];
				$GLOBALS['idatype'] = 'fullname';
				//**************
				return 2;
				}
			}


		if( empty($password1) || empty($password2))
			{
			$babBody->msgerror = bab_translate("You must complete required fields");
			return 0;
			}

		if( $password1 != $password2)
			{
			$babBody->msgerror = bab_translate("Passwords not match !!");
			return 0;
			}

		if ( strlen($password1) < 6 )
			{
			$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
			return 0;
			}
		}

	$res = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL."");
	$req = "";
	while( $arr = $db->db_fetch_array($res))
		{
		$rr = $db->db_fetch_array($db->db_query("select required from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup !=0 ? 0: $id)."' and id_field='".$arr['id']."'"));
		if( $arr['name'] != 'jpegphoto' && $rr['required'] == "Y" && empty($fields[$arr['name']]))
			{
			$babBody->msgerror = bab_translate("You must complete required fields");
			return 0;
			}

		if ( $arr['name'] == 'jpegphoto' && $rr['required'] == "Y" && (empty($file) || $file == "none"))
			{
			$tmp = $db->db_fetch_assoc($db->db_query("select photo_data from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".($idgroup !=0 ? 0: $id)."' and id='".$idu."'"));

			if (empty($tmp['photo_data']))
				{
				$babBody->msgerror = bab_translate("You must complete required fields");
				return 0;
				}
			}

		if( isset($fields[$arr['name']]) && $arr['name'] != 'jpegphoto')
			{
			if( $idgroup > 0 )
				$req .= $arr['name']."='".addslashes($fields[$arr['name']])."',";
			else
				$req .= $arr['name'].",";
			}
		}


	if( $idgroup > 0 )
		{
		$iduser = registerUser(stripslashes($fields['givenname']), stripslashes($fields['sn']), stripslashes($fields['mn']), $fields['email'], $nickname, $password1, $password2, true);
		if( $iduser == false )
			{
			return 0;
			}
		if( $idgroup > 1 )
			{
			bab_addUserToGroup($iduser, $idgroup);
			}
		
		if( $notifyuser == "Y" )
			{
			if( !bab_isMagicQuotesGpcOn())
				{
				$firstname = addslashes($fields['givenname']);
				$lastname = addslashes($fields['sn']);
				}
			else
				{
				$firstname = $fields['givenname'];
				$lastname = $fields['sn'];
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
			if( isset($fields[$arr['name']]))
				{
				$req .= "'".addslashes($fields[$arr['name']])."',";
				}
			}
		if( !empty($cphoto))
			$req .= "'".$cphoto."',";

		if( $idgroup > 0 )
			$req .= "'0', '".$iduser."')";
		else
			$req .= "'".$id."', '0')";
		$db->db_query($req);
		$iddbu = $db->db_insert_id();
		}

	foreach( $fields as $key => $value )
		{
		if( substr($key, 0, strlen("babdirf")) == 'babdirf' )
			{
			$tmp = substr($key, strlen("babdirf"));
			if( !bab_isMagicQuotesGpcOn())
				{
				$value = addslashes($value);
				}
			$db->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." (id_fieldx, id_entry, field_value) values ('".$tmp."','".$iddbu."','".$value."')");
			}
		}

	
	$db->db_query("update ".BAB_DBDIR_ENTRIES_TBL." set date_modification=now(), id_modifiedby='".$GLOBALS['BAB_SESS_USERID']."' where id='".$iddbu."'");
	return 1;
	}


function confirmEmptyDb($id)
	{
	global $babDB;
	list($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
	if( $idgroup != 0 ) /* Ovidentia directory */
		return;
	$res = $babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".$id."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$babDB->db_query("delete from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$arr['id']."'");
	}
	$babDB->db_query("delete from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".$id."'");
	}

function deleteDbContact($id, $idu)
	{
	$db = $GLOBALS['babDB'];
	list($idgroup) = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
	if( $idgroup != 0)
		{
		include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
		list($iddu) = $db->db_fetch_array($db->db_query("select id_user from ".BAB_DBDIR_ENTRIES_TBL." where id='".$idu."'"));	
		bab_deleteUser($iddu);
		return;
		}
	}

function unassignDbContact($id, $idu)
	{
	$db = $GLOBALS['babDB'];
	list($idgroup) = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
	if( $idgroup != 0  && $idgroup != BAB_REGISTERED_GROUP )
		{
		list($iddu) = $db->db_fetch_array($db->db_query("select id_user from ".BAB_DBDIR_ENTRIES_TBL." where id='".$idu."'"));	
		bab_removeUserFromGroup($iddu, $idgroup);
		return;
		}
	}

function exportDbDirectory($id, $wsepar, $separ, $listfd)
{

	$db = &$GLOBALS['babDB'];
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


	if( $GLOBALS['BAB_SESS_USERID'])
		{
		$db->db_query("delete from ".BAB_DBDIR_FIELDSEXPORT_TBL." where id_directory='".$id."' and id_user='".$GLOBALS['BAB_SESS_USERID']."'");

		for($i=0; $i < count($listfd); $i++)
			{
			$db->db_query("insert into ".BAB_DBDIR_FIELDSEXPORT_TBL." (id_user, id_directory, id_field, ordering) values ('".$GLOBALS['BAB_SESS_USERID']."','".$id."','".$listfd[$i]."','".($i + 1)."')");
			}
		}


	$output = "";
	if( $idgroup > 0 )
		{
		$output .= '"'.str_replace('"','""',bab_translate("Nickname")).'"'.$separ;
		}

	$arrnamef = array();
	$leftjoin = array();
	$select = array();

	if( $GLOBALS['BAB_SESS_USERID'])
		{
		$res = $db->db_query("select dbf.* from ".BAB_DBDIR_FIELDSEXPORT_TBL." dbfex left join ".BAB_DBDIR_FIELDSEXTRA_TBL." dbf on dbf.id_field=dbfex.id_field where dbf.id_directory='".($idgroup != 0? 0: $id)."' and dbfex.id_user='".$GLOBALS['BAB_SESS_USERID']."' and dbfex.id_directory='".$id."' order by dbfex.ordering asc");
		}
	else
		{
		$res = $db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $id)."' order by list_ordering asc");
		}

	while( $arr = $db->db_fetch_array($res))
		{
		if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
			$rr = $db->db_fetch_array($db->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
			$fieldn = translateDirectoryField($rr['description']);
			$arrnamef[] = $rr['name'];
			$select[] = 'e.'.$rr['name'];
			}
		else
			{
			$rr = $db->db_fetch_array($db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
			$fieldn = translateDirectoryField($rr['name']);
			$arrnamef[] = "babdirf".$arr['id'];

			$leftjoin[] = 'LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$arr['id']." ON lj".$arr['id'].".id_fieldx='".$arr['id']."' AND e.id=lj".$arr['id'].".id_entry";
			$select[] = "lj".$arr['id'].'.field_value '."babdirf".$arr['id']."";
			}
		$output .= '"'.str_replace('"','""',translateDirectoryField($fieldn)).'"'.$separ;
		}

	$output = substr($output, 0, -1);
	$output .= "\n";

	if( $idgroup > 1 )
		{
		$req = " ".BAB_DBDIR_ENTRIES_TBL." e,
				".BAB_USERS_GROUPS_TBL." u ".implode(' ',$leftjoin)." 
					WHERE u.id_group='".$idgroup."' 
					AND u.id_object=e.id_user 
					AND e.id_directory='0'";
		}
	else
		{
		$req = " ".BAB_DBDIR_ENTRIES_TBL." e ".implode(' ',$leftjoin)." WHERE e.id_directory='".(1 == $idgroup ? 0 : $id )."'";
		}

	$select[] = 'e.id_user';

	$req = "select ".implode(',', $select)." from ".$req;
	$res2 = $db->db_query($req);

	while( $row = $db->db_fetch_array($res2))
		{
		if( $idgroup > 0 )
			{
			list($nickname) = $db->db_fetch_array($db->db_query("select nickname from ".BAB_USERS_TBL." where id='".$row['id_user']."'"));
			$output .= '"'.str_replace('"','""',$nickname).'"'.$separ;
			}

		for( $k=0; $k < count($arrnamef); $k++ )
			{
			$output .= '"'.str_replace(array("\r","\n",'"'),array('',' ','""'),stripslashes($row[$arrnamef[$k]])).'"'.$separ;
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


function assignDbContact($id, $userids)
{
	global $babDB;

	list($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
	if( $idgroup && $idgroup != BAB_REGISTERED_GROUP )
	{
		for( $i=0; $i < count($userids); $i++ )
		{
		bab_addUserToGroup($userids[$i], $idgroup);
		}
	}
}

/* main */
if( isset($directoryid)) { $id = $directoryid; }

if( !isset($idx ))
	$idx = 'list';

if( isset($pfile) && !empty($pfile) && bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id))
	{
	processImportDbFile($pfile, $id, $separ);
	}

if( isset($action) && $action == 'Yes'  && bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id))
	{
	confirmEmptyDb($id);
	}

if( isset($modify))
	{
		if( $modify == 'dbc'  && bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $id))
			{
			$idx = 'dbmod';
			if(!isset($photof_name) ) { $photof_name = '';}
			if(!isset($photof) ) { $photof = '';}
			if(!isset($photod) ) { $photod = '';}
			if(updateDbContact($id, $idu, $fields, $photof_name,$photof,$photod))
				{
				$msg = bab_translate("Your contact has been updated");
				$idx = 'dbcunload';
				$fields = array();
				}
			}
		else if( $modify == 'dbac' && bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id))
			{
			if(!isset($photof_name) ) { $photof_name = '';}
			if(!isset($photof) ) { $photof = '';}
			if(!isset($password1) ) { $password1 = '';}
			if(!isset($password2) ) { $password2 = '';}
			if(!isset($nickname) ) { $nickname = '';}
			if(!isset($notifyuser) ) { $notifyuser = '';}
			if(!isset($sendpwd) ) { $sendpwd = '';}
			$ret = confirmAddDbContact($id, $fields, $photof_name,$photof, $password1, $password2, $nickname, $notifyuser, $sendpwd);

			switch($ret)
				{
				case 2:
					$idx = 'cassign';
					break;
				case 1:
					$msg = bab_translate("Your contact has been added");
					$idx = 'dbcunload';
					$fields = array();
					break;
				case 0:
				default:
					$idx = 'adbc';
					break;
				}
			}
		elseif( $modify == 'assign' && bab_isAccessValid(BAB_DBDIRBIND_GROUPS_TBL, $id))
			{
			assignDbContact($id, $userids);
			$msg = bab_translate("Your contacts has been assigned");
			$idx = 'dbcunload';
			}
		elseif( $modify == 'cassign' )
			{
			if( isset($byes) && bab_isAccessValid(BAB_DBDIRBIND_GROUPS_TBL, $id))
				{
				assignDbContact($id, array($idauser));
				$msg = bab_translate("Your contact has been assigned");
				$idx = 'dbcunload';
				$fields = array();
				}
			else
				{
				$idx = 'adbc';
				}
			}
	}
else if (isset($expfile) && bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id))
{
	exportDbDirectory($id, $wsepar, $separ, $listfd);
	$idx = 'sdb';
}


switch($idx)
	{
	case 'deldbc':
		if( bab_isAccessValid(BAB_DBDIRDEL_GROUPS_TBL, $id))
			{
			$msg = bab_translate("Your contact has been deleted");
			deleteDbContact($id, $idu);
			}
		else
			{
			$msg = bab_translate("Access denied");
			}
		/* no break */
	case 'dbcunload':
		if (!isset($refresh)) {$refresh = '';}
		contactDbUnload($msg, $refresh);
		exit();
		break;

	case 'unassign':
		if( bab_isAccessValid(BAB_DBDIRUNBIND_GROUPS_TBL, $id))
			{
			$msg = bab_translate("Your contact has been unassigned");
			unassignDbContact($id, $idu);
			}
		else
			{
			$msg = bab_translate("Access denied");
			exit;
			}
		if (!isset($refresh)) {$refresh = '';}
		contactDbUnload($msg, $refresh);
		exit();
		break;

	case 'ddbed':
		$babBody->title = '';
		dbEntryDirectories($id, $idu);
		exit;
		break;

	case 'dbmod':
		if (!isset($fields)) {$fields = array();}
		if (!isset($refresh)) {$refresh = '';}
		$idu = isset($_REQUEST['idu']) ? $_REQUEST['idu'] : false;
		modifyDbContact($id, $idu, $fields, $refresh);
		exit;
		break;
	case 'getimg':
		getDbContactImage($id, $idu);
		exit;
		break;
	case 'getimgl':
		getLdapContactImage($id, $cn);
		exit;
		break;

	case 'ddbovml':
		$babBody->title = '';
		summaryDbContactWithOvml($_GET);
		exit;
		break;

	case 'ddb':
		$babBody->title = '';
		summaryDbContact($id, $idu);
		exit;
		break;

	case 'cassign':
		confirmAssignEntry($id, $fields, $GLOBALS['idauser'], $GLOBALS['idatype']);
		exit;
		break;

	case 'assign':
		if( !isset($pos)) $pos ='';
		if( isset($chg))
		{
			if( $pos[0] == '-')
				$pos = $pos[1];
			else
				$pos = '-' .$pos;
		}
		assignList($id, $pos);
		exit;
		break;

	case 'adbc':
		$babBody->title = bab_translate("Add entry to").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		if(bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id))
			{
			if (!isset($fields)) { $fields = array() ;}
			addDbContact($id, $fields);
			exit;
			}
		else
			$babBody->msgerror = bab_translate("Access denied");
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		break;

	case 'usdb':
		if( !isset($xf ))
			$xf = '';
		if( !isset($pos ))
			$pos = 'A';
		UBrowseDbDirectory($id, $pos, $xf, $cb);
		exit;
		break;

	case 'sdbovml':
		$babBody->title = bab_translate("Database Directory").": ".getDirectoryName($directoryid,BAB_DB_DIRECTORIES_TBL);
		$bgroup = browseDbDirectoryWithOvml(bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id));
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu('sdbovml', bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=ovml");
		if(bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbimp&id=".$id);
			}

		if(bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbexp&id=".$id);
			}

		if (!$bgroup && bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('empdb', bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=directory&idx=empdb&id=".$id);
			}
		break;

	case 'sdb':
		$babBody->title = bab_translate("Database Directory").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		if( !isset($xf ))
			$xf = '';
		if( !isset($pos ))
			$pos = 'A';
		$bgroup = browseDbDirectory($id, $pos, $xf, bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id));
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu('sdb', bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".$pos);
		if(bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbimp&id=".$id);
			}

		if(bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbexp&id=".$id);
			}

		if (!$bgroup && bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('empdb', bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=directory&idx=empdb&id=".$id);
			}
		break;

	case 'dbimp':
		if( !isset($pos ))
			$pos = 'A';
		$babBody->title = bab_translate("Import file to").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu('sdb', bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".$pos);
		if(bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id))
			{
			importDbFile($id);
			$babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbimp&id=".$id);
			}

		if(bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbexp&id=".$id);
			}
		break;

	case 'dbexp':
		if( !isset($pos ))
			$pos = 'A';
		$babBody->title = bab_translate("Export file from").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu('sdb', bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".$pos);
		if(bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbimp&id=".$id);
			}

		if(bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id))
			{
			exportDbFile($id);
			$babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbexp&id=".$id);
			}
		break;

	case 'dbmap':
		if( !isset($pos ))
			$pos = 'A';
		$babBody->title = bab_translate("Import file to").": ".getDirectoryName($id,BAB_DB_DIRECTORIES_TBL);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu('sdb', bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$id."&pos=".$pos);
		if(bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id))
			{
			mapDbFile($id, $uploadf_name, $uploadf, $wsepar, $separ);
			$babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbimp&id=".$id);
			}
		
		if(bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript']."?tg=directory&idx=dbexp&id=".$id);
			}

		break;

	case 'dldap':
		$babBody->title = bab_translate("Summary of information about").': '.$cn;
		summaryLdapContact($id, $cn);
		exit;
		break;

	case 'sldap':
		if( !isset($pos ))
			$pos = 'A';
		$babBody->title = bab_translate("Ldap Directory").': '.getDirectoryName($id,BAB_LDAP_DIRECTORIES_TBL);
		browseLdapDirectory($id, $pos);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		$babBody->addItemMenu('sldap', bab_translate("Browse"), $GLOBALS['babUrlScript']."?tg=directory&idx=sldap&id=".$id."&pos=".$pos);
		break;

	case 'empdb':
		$babBody->title = bab_translate("Delete Database Directory");
		if(bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id))
			emptyDb($id);
		else
			$babBody->msgerror = bab_translate("Access denied");
		
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		if(bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id))
			{
			$babBody->addItemMenu('empdb', bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=directory&idx=empdb&id=".$id);
			}
		break;

	case 'list':
	default:
		$babBody->title = '';
		listUserAds();
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=directory&idx=list");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>