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
include 'base.php';
include_once $babInstallPath.'utilit/uiutil.php';
include_once $babInstallPath.'utilit/dirincl.php';
include_once $babInstallPath.'admin/acl.php';



function isDirectoryGroup($id)
{
	global $babDB;
	list($id_group) = $babDB->db_fetch_row($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
	return $id_group;
}

function getDirectoryFieldName($fxid)
{
	global $babDB;
	$name = '';
	list($id_field) = $babDB->db_fetch_row($babDB->db_query("select id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id=".$babDB->quote($fxid).""));
	if( $id_field )
	{
		if( $id_field > BAB_DBDIR_MAX_COMMON_FIELDS )
		{
		list($name) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id=".$babDB->quote($id_field - BAB_DBDIR_MAX_COMMON_FIELDS).""));		
		}
		else
		{
		list($name) = $babDB->db_fetch_row($babDB->db_query("select description from ".BAB_DBDIR_FIELDS_TBL." where id=".$babDB->quote($id_field).""));	
		$name = bab_translate($name);
		}
	}
	return $name;
}

function listAds()
{
	global $babBody;

	class temp
	{
		var $db;
		var $resdb;
		var $countdb;
		var $resldap;
		var $countldap;
		var $directories;
		var $urlname;
		var $name;
		var $description;
		var $desctxt;
		var $typetxt;
		var $databasetitle;
		var $add;
		var $urladdldap;
		var $urladddb;
		var $gview;
		var $gmodify;
		var $gadd;
		var $gviewurl;
		var $gmodifyurl;
		var $gaddurl;
		var $grouptxt;
		var $group;
		var $altbg = true;

		function temp()
		{
			global $babBody;
			$this->directories		= bab_translate("Directories");
			$this->desctxt			= bab_translate("Description");
			$this->grouptxt			= bab_translate("Group");
			$this->ldaptitle		= bab_translate("Ldap Directories list");
			$this->databasetitle	= bab_translate("Databases Directories list");
			$this->add				= bab_translate("Add");
			$this->gview			= bab_translate("Rights");
			$this->grights			= bab_translate("Rights");
			$this->urladdldap		= bab_toHtml($GLOBALS['babUrlScript'].'?tg=admdir&idx=ldap');
			$this->urladddb			= bab_toHtml($GLOBALS['babUrlScript'].'?tg=admdir&idx=db');
			$this->db				= $GLOBALS['babDB'];
			$this->resldap			= $this->db->db_query("select * from ".BAB_LDAP_DIRECTORIES_TBL." where id_dgowner='".$this->db->db_escape_string($babBody->currentAdmGroup)."' ORDER BY name");
			$this->countldap		= $this->db->db_num_rows($this->resldap);
			$this->resdb			= $this->db->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." where id_dgowner='".$this->db->db_escape_string($babBody->currentAdmGroup)."' ORDER BY name");
			$this->countdb			= $this->db->db_num_rows($this->resdb);
		}

		function getnextldap()
		{
			static $i = 0;
			if($i < $this->countldap)
			{
				$this->altbg		= !$this->altbg;
				$arr				= $this->db->db_fetch_array($this->resldap);
				$this->description	= bab_toHtml($arr['description']);
				$this->url			= bab_toHtml($GLOBALS['babUrlScript'].'?tg=admdir&idx=mldap&id='.$arr['id']);
				$this->urlname		= bab_toHtml($arr['name']);
				$this->gviewurl		= bab_toHtml($GLOBALS['babUrlScript'].'?tg=admdir&idx=gviewl&id='.$arr['id']);
				$i++;
				return true;
			}
			else
				return false;
		}

		function getnextdb()
		{
			static $i = 0;
			if($i < $this->countdb)
			{
				$arr = $this->db->db_fetch_array($this->resdb);
				if($arr['id_group'] != '0')
				{
					list($this->bshow) = $this->db->db_fetch_row($this->db->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$this->db->db_escape_string($arr['id_group'])."'"));
					if($this->bshow == 'Y')
					{
						$this->altbg = !$this->altbg;
					}
					
					if($arr['id_group'] == BAB_REGISTERED_GROUP)
					{
						$this->group = bab_toHtml(bab_getGroupName($arr['id_group'], false));
					}
					else
					{
						$this->group = bab_toHtml(bab_getGroupName($arr['id_group']));
					}
				}
				else
				{
					$this->altbg = !$this->altbg;
					$this->bshow = 'Y';
					$this->group = '';
				}
				
				$this->description	= bab_toHtml($arr['description']);
				$this->url			= bab_toHtml($GLOBALS['babUrlScript'].'?tg=admdir&idx=mdb&id='.$arr['id']);
				$this->urlname		= bab_toHtml($arr['name']);
				$this->grightsurl	= bab_toHtml($GLOBALS['babUrlScript'].'?tg=admdir&idx=db_rights&id='.$arr['id']);
				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, 'admdir.html', 'adlist'));
}

function dirGroups() // liste des annuaires de groupes
	{
	global $babBody;
	class dirGroupsCls
		{

		var $altbg = true;

		function dirGroupsCls()
			{
			global $babBody;
			$this->fullname = bab_translate("Groups");
			$this->directory = bab_translate("Site directory");
			$this->modify = bab_translate("Update");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";
			$tree = new bab_grptree();
			$this->groups = $tree->getGroups(BAB_ALLUSERS_GROUP);
			unset($this->groups[BAB_UNREGISTERED_GROUP]);
			$this->altbg=false;
			if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
				{
				$this->bdgdirectories = true;
				}
			else
				{
				if( $babBody->currentDGGroup['directories'] == 'Y' )
					{
					$this->bdgdirectories = true;
					}
				else
					{
					$this->bdgdirectories = false;
					}
				}
			
			}

		function getnext()
			{
			if (list(,$this->arr) = each($this->groups))
				{
				$this->altbg = !$this->altbg;
				$this->grpid = $this->arr['id'];
				$this->urlname = bab_toHtml($this->arr['name']);

				if( $this->arr['directory'] == "Y")
					{
					$this->dircheck = "checked";
					}
				else
					{
					$this->dircheck = "";
					}

				return true;
				}
			else
				return false;

			}
		}

	$temp = new dirGroupsCls();
	$babBody->babecho(	bab_printTemplate($temp, "admdir.html", "dirgroups"));
	}


function search_options()
{
	global $babBody;

	class temp
		{
		var $search_view_fields = array();

		function temp()
			{
			global $babBody;
			$this->listftxt = '---- '.bab_translate("Fields").' ----';
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->update = bab_translate("Update");

			$this->db = & $GLOBALS['babDB'];
			list($tmp) = $this->db->db_fetch_array($this->db->db_query("SELECT search_view_fields FROM ".BAB_DBDIR_OPTIONS_TBL.""));
			
			if (empty($tmp))
				$tmp = '2,4';
			
			$this->resdb = $this->db->db_query("SELECT id,description FROM ".BAB_DBDIR_FIELDS_TBL."");
			$this->resdf = $this->db->db_query("SELECT id,description FROM ".BAB_DBDIR_FIELDS_TBL." WHERE id IN(".$this->db->db_escape_string($tmp).")");
			}

		function getnext()
			{
			if ($this->arr = $this->db->db_fetch_array($this->resdb))
				{
				$this->arr['description'] = translateDirectoryField($this->arr['description']);
				return true;
				}
			else
				return false;
			}

		function getnextdf()
			{
			if ($this->arr = $this->db->db_fetch_array($this->resdf))
				{
				$this->arr['description'] = translateDirectoryField($this->arr['description']);
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, 'admdir.html', 'dbscripts'));
	$babBody->babecho(	bab_printTemplate($temp, 'admdir.html', 'search'));
}



function addAdLdap($name, $description, $servertype, $decodetype, $host, $basedn, $userdn)
	{
	global $babBody;
	class temp
		{
		var $vname;
		var $vdescription;
		var $name;
		var $description;
		var $type;
		var $add;
		var $ldap;
		var $no;
		var $yes;
		var $password;
		var $repassword;
		var $host;
		var $basedn;
		var $userdn;

		var $vhost;
		var $vbasedn;
		var $vuserdn;

		function temp($name, $description, $servertype, $decodetype, $host, $basedn, $userdn)
			{
			global $babLdapServerTypes;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->servertypetxt = bab_translate("Server type");
			$this->decodetypetxt = bab_translate("Server charset");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Confirm");
			$this->host = bab_translate("Host");
			$this->basedn = bab_translate("BaseDN");
			$this->userdn = bab_translate("User DN");
			$this->type = "ldap";
			$this->add = bab_translate("Add");

			$this->vname = $name == '' ? '' : $name;
			$this->vdescription = $description == '' ? '' : $description;
			$this->vhost = $host == '' ? '' : $host;
			$this->vbasedn = $basedn == '' ? '' : $basedn;
			$this->vuserdn = $userdn == '' ? '' : $userdn;
			$this->vservettype = $servertype;
			$this->vdecodetype = $decodetype;
			$this->count = count($babLdapServerTypes);
			}

		function getnextservertype()
			{
			global $babLdapServerTypes;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->stid = $i;
				$this->stval = $babLdapServerTypes[$i];
				if( $this->vservettype == $i )
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
				return false;
			}

		function getnextdecodetype()
			{
			static $encodingTypes = null;
			static $i = 0;

			if (null === $encodingTypes) {
				include_once $GLOBALS['babInstallPath'].'utilit/ldap.php';
				$encodingTypes = bab_getLdapEncoding();
			}


			if( $i < count($encodingTypes))
				{
				$this->stid = $i;
				$this->stval = $encodingTypes[$i];
				if( $this->vdecodetype == $i )
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
				return false;
			}
		}

	$temp = new temp($name, $description, $servertype, $decodetype, $host, $basedn, $userdn);
	$babBody->babecho(bab_printTemplate($temp,'admdir.html', 'ldapadd'));
	}

function modifyLdap($id)
	{
	global $babBody;
	class temp
		{
		var $vname;
		var $vdescription;
		var $name;
		var $description;
		var $add;
		var $ldap;
		var $password;
		var $repassword;
		var $host;
		var $basedn;
		var $userdn;

		var $vhost;
		var $vbasedn;
		var $vuserdn;
		var $id;

		function temp($id)
			{
			global $babLdapServerTypes;
			$this->id = $id;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->servertypetxt = bab_translate("Server type");
			$this->decodetypetxt = bab_translate("Server charset");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Confirm");
			$this->host = bab_translate("Host");
			$this->basedn = bab_translate("BaseDN");
			$this->userdn = bab_translate("User DN");
			$this->add = bab_translate("Modify");
			$this->delete = bab_translate("Delete");

			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".BAB_LDAP_DIRECTORIES_TBL." where id='".$db->db_escape_string($id)."'");
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$this->vname = $arr['name'];
				$this->vdescription = $arr['description'];
				$this->vhost = $arr['host'];
				$this->vbasedn = $arr['basedn'];
				$this->vuserdn = $arr['userdn'];
				$this->vservettype = $arr['server_type'];
				$this->vdecodetype = $arr['decoding_type'];

				}
			$this->count = count($babLdapServerTypes);
			}

		function getnextservertype()
			{
			global $babLdapServerTypes;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->stid = $i;
				$this->stval = $babLdapServerTypes[$i];
				if( $this->vservettype == $i )
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
				return false;
			}

		function getnextdecodetype()
			{
			static $encodingTypes;
			static $i = 0;

			if (null === $encodingTypes) {
				include_once $GLOBALS['babInstallPath'].'utilit/ldap.php';
				$encodingTypes = bab_getLdapEncoding();
			}

			if( $i < count($encodingTypes))
				{
				$this->stid = $i;
				$this->stval = $encodingTypes[$i];
				if( $this->vdecodetype == $i )
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
				return false;
			}
		}

	$temp = new temp($id);
	$babBody->babecho(bab_printTemplate($temp,'admdir.html', 'ldapmodify'));
	}

function addAdDb($adname, $description)
	{
	global $babBody;
	class temp
		{
		var $vname;
		var $vdescription;
		var $name;
		var $description;
		var $multilignes;
		var $db;
		var $res;
		var $fieldn;
		var $fieldid;
		var $field;
		var $defaultvalue;
		var $rw;
		var $required;
		var $add;
		var $count;
		var $arr = array();
		var $reqchecked;
		var $mlchecked;
		var $dzchecked = '';

		function temp($adname, $description)
			{
			$this->vname = $adname == '' ? '' : $adname;
			$this->vdescription = $description == '' ? '' : $description;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->field = bab_translate("Fields");
			$this->defaultvalue = bab_translate("Default Value");
			$this->disabledtxt = bab_translate("Disabled");
			$this->rw = bab_translate("Modifiable");
			$this->required = bab_translate("Required");
			$this->multilignes = bab_translate("Multilignes");
			$this->add = bab_translate("Add");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->displayinfoupdate = bab_translate("Display the date and the author of update");
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL);
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;
			}

		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldn = translateDirectoryField($arr['description']);
				$this->fieldv = $arr['name'];
				$this->fieldid = $arr['id'];
				$this->reqchecked = '';
				$this->rwchecked = '';
				$this->mlchecked = '';
				if (in_array( $this->fieldid, array(2, 4)) )
					$this->disabled = true;
				else 
					$this->disabled = false;
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($adname, $description);
	$babBody->babecho( bab_printTemplate($temp,'admdir.html', 'dbadd'));
	}


function modifyDb($id)
	{
	global $babBody;
	class temp
		{
		var $field;
		var $defaultvalue;
		var $rw;
		var $required;
		var $add;
		var $count;
		var $arr = array();
		var $bdel;
		var $bfields;
		var $allowuserupdate;
		var $no;
		var $yes;
		var $noselected;
		var $yesselected;
		var $ballowuserupdate;

		function temp($id)
			{
			$this->id = $id;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->field = bab_translate("Fields");
			$this->defaultvalue = bab_translate("Default Value");
			$this->rw = bab_translate("Modifiable");
			$this->required = bab_translate("Required");
			$this->multilignes = bab_translate("Multilignes");
			$this->add = bab_translate("Modify");
			$this->delete = bab_translate("Delete");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->addftxt = bab_translate("Add new field");
			$this->disabledtxt = bab_translate("Disabled");
			$this->allowuserupdate = bab_translate("Allow user update personal information");
			$this->displayinfoupdate = bab_translate("Display the date and the author of update");
			$this->fieldrights_title = bab_translate("Rights");
			$this->imgrights_url = $GLOBALS['babSkinPath'] . 'images/Puces/access.png';
			$this->imgrights_url2 = $GLOBALS['babSkinPath'] . 'images/Puces/access2.png';

			$this->bdel = true;
			$this->bfields = true;
			$this->ballowuserupdate = false;
			$this->db = $GLOBALS['babDB'];
			$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." where id='".$this->db->db_escape_string($id)."'"));
			$this->vname = $arr['name'];
			$this->vdescription = $arr['description'];
			if( $arr['id_group'] != 0 )
				{
				$iddir = 0;
				$this->bdel = false;
				if( $arr['id_group'] != 1 )
					{
					$this->bfields = false;
					}
				else
					{
					$this->ballowuserupdate = true;
					}
				if( $arr['user_update'] == 'Y')
					{
					$this->noselected = '';
					$this->yesselected = 'selected';
					}
				else
					{
					$this->noselected = 'selected';
					$this->yesselected = '';
					}
				}
			else
				{
				$iddir = $id;
				}
			if( $arr['show_update_info'] == 'Y')
				{
				$this->noduselected = '';
				$this->yesduselected = 'selected';
				}
			else
				{
				$this->noduselected = 'selected';
				$this->yesduselected = '';
				}

			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$this->db->db_escape_string($iddir)."' and id_field < '".BAB_DBDIR_MAX_COMMON_FIELDS."' order by id_field asc");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				{
				$this->count = $this->db->db_num_rows($this->res);
				}
			else
				{
				$this->count = 0;
				}
			
			$this->resfx = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$this->db->db_escape_string($iddir)."' and id_field > '".BAB_DBDIR_MAX_COMMON_FIELDS."' order by id asc");
			if( $this->resfx && $this->db->db_num_rows($this->resfx) > 0)
				{
				$this->countfx = $this->db->db_num_rows($this->resfx);
				}
			else
				{
				$this->countfx = 0;
				}

			$this->altbg = true;
			$this->addfurl = $GLOBALS['babUrlScript'].'?tg=admdir&idx=addf&id='.$this->id;
			}

		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldid = $arr['id_field'];
				if( $arr['modifiable'] == 'Y')
					$this->rwchecked = 'checked';
				else
					$this->rwchecked = '';

				if( $arr['required'] == 'Y')
					$this->reqchecked = 'checked';
				else
					$this->reqchecked = '';
				if( $arr['multilignes'] == 'Y')
					$this->mlchecked = 'checked';
				else
					$this->mlchecked = '';
				if( $arr['disabled'] == 'Y')
					$this->dzchecked = 'checked';
				else
					$this->dzchecked = '';
				if ((!$this->bdel && in_array( $this->fieldid, array( 2, 4, 6) )) || ($this->bdel && in_array( $this->fieldid, array(2, 4) )) )
					$this->disabled = true;
				else 
					$this->disabled = false;

				if ( $this->fieldid < 7)
					{
					$this->addvalurl = false;
					}
				else 
					{
					$this->addvalurl = $GLOBALS['babUrlScript'].'?tg=admdir&idx=addval&id='.$this->id.'&fxid='.$arr['id'];
					}

				$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where id='".$this->db->db_escape_string($arr['id_field'])."'"));
				$this->fieldn = translateDirectoryField($rr['description']);
				$this->fieldv = $rr['name'];
				if( $arr['default_value'] != 0 )
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select field_value from ".BAB_DBDIR_FIELDSVALUES_TBL." where id='".$this->db->db_escape_string($arr['default_value'])."'"));
					$this->defvalue = $rr['field_value'];
					}
				else
					{
					$this->defvalue = '';
					}
				$this->fieldrights_url = $GLOBALS['babUrlScript'].'?tg=admdir&idx=fieldrights&id='.$this->id.'&fxid='.$arr['id'];
				list($this->fncount) =  $this->db->db_fetch_row($this->db->db_query("select count(id_object) from ".BAB_DBDIRFIELDUPDATE_GROUPS_TBL." where id_object=".$this->db->quote($arr['id']).""));
				$i++;
				return true;
				}
			else
				{
				$this->altbg = !$this->altbg;
				return false;
				}
			}

		function getnextfieldx()
			{
			static $i = 0;
			if( $i < $this->countfx)
				{
				$this->altbg = !$this->altbg;
				$arr = $this->db->db_fetch_array($this->resfx);
				$this->fieldid = $arr['id_field'];
				if( $arr['modifiable'] == 'Y')
					$this->rwchecked = 'checked';
				else
					$this->rwchecked = '';

				if( $arr['required'] == 'Y')
					$this->reqchecked = 'checked';
				else
					$this->reqchecked = '';
				if( $arr['multilignes'] == 'Y')
					$this->mlchecked = 'checked';
				else
					$this->mlchecked = '';

				if( $arr['disabled'] == 'Y')
					$this->dzchecked = 'checked';
				else
					$this->dzchecked = '';

				$this->disabled = false;

				$this->addvalurl = $GLOBALS['babUrlScript'].'?tg=admdir&idx=addval&id='.$this->id.'&fxid='.$arr['id'];

				$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$this->db->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
				$this->fieldn = translateDirectoryField($rr['name']);
				if( $arr['default_value'] != 0 )
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select field_value from ".BAB_DBDIR_FIELDSVALUES_TBL." where id='".$this->db->db_escape_string($arr['default_value'])."'"));
					$this->defvalue = $rr['field_value'];
					}
				else
					{
					$this->defvalue = '';
					}
				$this->fieldrights_url = $GLOBALS['babUrlScript'].'?tg=admdir&idx=fieldrights&id='.$this->id.'&fxid='.$arr['id'];
				list($this->fncount) = $this->db->db_fetch_row($this->db->db_query("select count(id_object) from ".BAB_DBDIRFIELDUPDATE_GROUPS_TBL." where id_object=".$this->db->quote($arr['id']).""));
				$i++;
				return true;
				}
			else
				{
				$this->altbg = !$this->altbg;
				return false;
				}
			}
		}

	$temp = new temp($id);
	$babBody->babecho( bab_printTemplate($temp,'admdir.html', 'dbmodify'));
	}

function displayDb($id)
	{
	global $babBody;
	class temp
		{
		function temp($id)
			{
			global $babDB;
			$this->id = $id;
			$this->infotxt = bab_translate("Specify which fields will be displayed when browsing directory");
			$this->listftxt = '---- '.bab_translate("Fields").' ----';
			$this->listdftxt = '---- '.bab_translate("Fields to display").' ----';
			$this->ovmllisttxt = bab_translate("OVML file to be used for list");
			$this->ovmldetailtxt = bab_translate("OVML file to be used for detail");
			$this->browsetxt = bab_translate("Browse");
			$this->browseurl = $GLOBALS['babUrlScript'].'?tg=editorovml';

			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->update = bab_translate("Update");
			$arr = $babDB->db_fetch_array($babDB->db_query("select id_group, ovml_detail, ovml_list from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
			if( $arr['id_group'] != 0 )
				{
				$iddir = 0;
				}
			else
				{
				$iddir = $id;
				}
			$this->ovmllistval = $arr['ovml_list'];
			$this->ovmldetailval = $arr['ovml_detail'];

			$this->resf = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$babDB->db_escape_string($iddir)."' and ordering='0' AND id_field<>5");
			$this->countf = $babDB->db_num_rows($this->resf);
			$this->resfd = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$babDB->db_escape_string($iddir)."' and ordering!='0' AND id_field<>5 order by ordering asc");
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
	$babBody->babecho(	bab_printTemplate($temp, 'admdir.html', 'dbscripts'));
	$babBody->babecho( bab_printTemplate($temp,'admdir.html', 'dbdisplay'));
	}


function dbListOrder($id)
	{
	global $babBody;
	class temp
		{
		function temp($id)
			{
			global $babDB;
			$this->id = $id;
			$this->listftxt = '---- '.bab_translate("Fields").' ----';
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->update = bab_translate("Update");
			$arr = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
			if( $arr['id_group'] != 0 )
				{
				$iddir = 0;
				}
			else
				{
				$iddir = $id;
				}
			$this->resf = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$babDB->db_escape_string($iddir)."' and disabled='N' order by list_ordering asc");
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
	$babBody->babecho(	bab_printTemplate($temp, 'admdir.html', 'dbscripts'));
	$babBody->babecho( bab_printTemplate($temp,'admdir.html', 'dblistorder'));
	}

function deleteAd($id, $table)
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

		function temp($id, $table)
			{
			$this->message = bab_translate("Are you sure you want to delete this directory");
			$this->title = getDirectoryName($id, $table);
			$this->warning = bab_translate("WARNING: This operation will delete directory and all references"). '!';
			$this->urlyes = $GLOBALS['babUrlScript'].'?tg=admdir&idx=list&id='.$id.'&action=Yes&type=';
			if( $table == BAB_DB_DIRECTORIES_TBL )
				{
				$this->urlyes .= 'd';
				}
			else if( $table == BAB_LDAP_DIRECTORIES_TBL )
				$this->urlyes .= 'l';
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript'].'?tg=admdir&idx=list';
			$this->no = bab_translate("No");
			}
		}
	$temp = new temp($id, $table);
	$babBody->babecho(	bab_printTemplate($temp,'warning.html', 'warningyesno'));
	}


function showDbFieldValuesModify($id, $idfieldx)
{
	global $babBodyPopup;
	class temp
		{
		function temp($id, $idfieldx)
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->addtxt = bab_translate("Add a value");
			$this->savetxt = bab_translate("Save");
			$this->fvdeftxt = bab_translate("Default value");
			$this->yestxt = bab_translate("Yes");
			$this->notxt = bab_translate("No");
			$this->multivaluestxt = bab_translate("Use a listbox");
			$this->t_fields_values = bab_translate("Values");
			$this->t_value  = bab_translate("Value");
			$this->t_delvalue = bab_translate("Delete value");
			$this->js_error = bab_translate("You must enter two or more values");
			$this->id = $id;
			$this->idfield = $idfieldx;
			$this->res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSVALUES_TBL." where id_fieldextra='".$babDB->db_escape_string($idfieldx)."' order by id asc");
			$this->count = $babDB->db_num_rows($this->res);
			$this->fvalnum = 1;
			$rr = $babDB->db_fetch_array($babDB->db_query("select id_field, default_value, multi_values from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id='".$babDB->db_escape_string($idfieldx)."'"));
			$this->fvdefid = $rr['default_value'];

			if( $rr['multi_values'] == 'Y' )
				{
				$this->yesselected = 'selected';
				$this->noselected = '';
				$this->value = '';
				}
			else
				{
				$this->yesselected = '';
				$this->noselected = 'selected';
				$arr = $babDB->db_fetch_array($this->res);
				$this->value = bab_toHtml($arr['field_value']);
				if ($this->count > 0)
					$babDB->db_data_seek($this->res, 0);
				}


			if( $rr['id_field'] > BAB_DBDIR_MAX_COMMON_FIELDS )
				{
				$this->bdelete = true;
				$this->deltxt = bab_translate("Delete this field");
				$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($rr['id_field']-BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
				$this->fieldval = $rr['name'];
				}
			else
				{
				$this->bdelete = false;
				}
			}

		function getnextdbfieldvalue()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->fval = bab_toHtml($arr['field_value']);
				$this->fvdefselected = '';
				if( $arr['id'] == $this->fvdefid )
					{
					$this->fvdefselected = 'selected';
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				if( $this->count > 0 )
					{
					$babDB->db_data_seek($this->res, 0);
					}
				return false;
				}
			}
		}
	$temp = new temp($id, $idfieldx);
	$babBodyPopup->babecho(bab_printTemplate($temp, 'admdir.html', 'dbfieldvalues'));
}


function showDbAddField($id, $fieldn, $fieldv)
{
	global $babBodyPopup;
	class temp
		{
		function temp($id, $fieldn, $fieldv)
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->savetxt = bab_translate("Add");
			$this->fieldnametxt = bab_translate("Field");
			$this->fieldvaltxt = bab_translate("Default value");
			$this->id = $id;
			$this->fieldn = bab_toHtml($fieldn);
			$this->fieldv = bab_toHtml($fieldv);
			}
		}

	$temp = new temp($id, $fieldn, $fieldv);
	$babBodyPopup->babecho(bab_printTemplate($temp, 'admdir.html', 'dbaddfield'));
}

function addLdapDirectory($name, $description, $servertype, $decodetype, $host, $basedn, $userdn, $password1, $password2)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( empty($host))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a host address !!");
		return false;
		}

	if( $password1 != $password2)
		{
		$babBody->msgerror = bab_translate("ERROR: Passwords not match !!");
		return;
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".BAB_LDAP_DIRECTORIES_TBL." where name='".$db->db_escape_string($name)."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "insert into ".BAB_LDAP_DIRECTORIES_TBL." (name, description, server_type, decoding_type, host, basedn, userdn, password, id_dgowner) VALUES ('" .$db->db_escape_string($name). "', '" . $db->db_escape_string($description). "', '" . $db->db_escape_string($servertype). "', '" . $db->db_escape_string($decodetype). "', '" . $db->db_escape_string($host). "', '" . $db->db_escape_string($basedn). "', '" . $db->db_escape_string($userdn). "', ENCODE(\"".$password1."\",\"".$GLOBALS['BAB_HASH_VAR']."\"), '".$db->db_escape_string($babBody->currentAdmGroup)."')";
		$db->db_query($req);
		}
	return true;
	}

function addDbDirectory($name, $description, $displayiu, $fields, $rw, $rq, $ml, $dz)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".BAB_DB_DIRECTORIES_TBL." where name='".$db->db_escape_string($name)."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "insert into ".BAB_DB_DIRECTORIES_TBL." (name, description, show_update_info, id_dgowner) VALUES ('" .$db->db_escape_string($name). "', '" . $db->db_escape_string($description). "', '" .$db->db_escape_string($displayiu). "', '" .$db->db_escape_string($babBody->currentAdmGroup). "')";
		$db->db_query($req);
		$id = $db->db_insert_id();
		$res = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL);
		$k = 0;
		while( $arr = $db->db_fetch_array($res))
			{
			if( count($rw) > 0 && in_array($arr['id'], $rw))
				$modifiable = 'Y';
			else
				$modifiable = 'N';
			if( count($rq) > 0 && in_array($arr['id'], $rq))
				$required = 'Y';
			else
				$required = 'N';
			if( count($ml) > 0 && in_array($arr['id'], $ml))
				$multilignes = 'Y';
			else
				$multilignes = 'N';
			if( count($dz) > 0 && in_array($arr['id'], $dz))
				$disabled = 'Y';
			else
				$disabled = 'N';
			switch($arr['name'])
				{
				case 'givenname':
					$ordering = 1; break;
				case 'sn':
					$ordering = 2; break;
				default:
					$ordering = 0; break;
				}

			$req = "insert into ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, disabled, ordering, list_ordering) VALUES ('" .$db->db_escape_string($id). "', '" . $db->db_escape_string($arr['id']). "', '0', '".$db->db_escape_string($modifiable)."', '".$db->db_escape_string($required)."', '".$db->db_escape_string($multilignes)."', '".$db->db_escape_string($disabled)."', '".$db->db_escape_string($ordering)."', '".($k++)."')";
			$db->db_query($req);
			$fxid = $db->db_insert_id();
			$fieldval = trim($fields[$arr['name']]); 
			if( !empty($fieldval))
				{
				$db->db_query("insert into ".BAB_DBDIR_FIELDSVALUES_TBL." (id_fieldextra, field_value) VALUES ('" .$db->db_escape_string($fxid)."', '".$db->db_escape_string($fieldval)."')");		
				$fvid = $db->db_insert_id();			
				$db->db_query("update ".BAB_DBDIR_FIELDSEXTRA_TBL." set default_value='".$db->db_escape_string($fvid)."' where id='".$db->db_escape_string($fxid)."'");
				}
			}
		}
	return true;
	}

function modifyAdLdap($id, $name, $description, $servertype, $decodetype, $host, $basedn, $userdn, $password1, $password2)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( empty($host))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a host address !!");
		return false;
		}

	if( !empty($password1) || !empty($password2))
		{
		if( $password1 != $password2)
			{
			$babBody->msgerror = bab_translate("ERROR: Passwords not match !!");
			return false;
			}
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".BAB_LDAP_DIRECTORIES_TBL." where name='".$db->db_escape_string($name)."' and id!='".$db->db_escape_string($id)."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "update ".BAB_LDAP_DIRECTORIES_TBL." set name='".$db->db_escape_string($name)."', description='".$db->db_escape_string($description)."', server_type='".$db->db_escape_string($servertype)."', decoding_type='".$db->db_escape_string($decodetype)."', host='".$db->db_escape_string($host)."', basedn='".$db->db_escape_string($basedn)."', userdn='".$db->db_escape_string($userdn)."'";
		if( !empty($password1) )
			$req .= ", password=ENCODE(\"".$password1."\",\"".$GLOBALS['BAB_HASH_VAR']."\")";
		$req .= " where id='".$db->db_escape_string($id)."'";
		$db->db_query($req);
		bab_sitemap::clearAll();
		}
	return true;
	}

function modifyAdDb($id, $name, $description, $displayiu, $rw, $rq, $ml, $dz, $allowuu)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".BAB_DB_DIRECTORIES_TBL." where name='".$db->db_escape_string($name)."' and id!='".$db->db_escape_string($id)."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$arr = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$db->db_escape_string($id)."'"));
		if( $arr['id_group'] != 0)
			{
			$iddir = 0;
			}
		else
			{
			$iddir = $id;
			$allowuu = 'N';
			}

		$req = "update ".BAB_DB_DIRECTORIES_TBL." set name='".$db->db_escape_string($name)."', description='".$db->db_escape_string($description)."', show_update_info='".$db->db_escape_string($displayiu)."'";

		if( $arr['id_group'] == 1)
			{
			$req .= ", user_update='".$db->db_escape_string($allowuu)."'";
			}
			
		$req .= " where id='".$db->db_escape_string($id)."'";
		$db->db_query($req);

		if( $arr['id_group'] == 0 || $arr['id_group'] == BAB_REGISTERED_GROUP)
			{
			$res = $db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$db->db_escape_string($iddir)."'");
			while( $arr = $db->db_fetch_array($res))
				{
				if( count($rw) > 0 && in_array($arr['id_field'], $rw))
					$modifiable = 'Y';
				else
					$modifiable = 'N';
				if( count($rq) > 0 && in_array($arr['id_field'], $rq))
					$required = 'Y';
				else
					$required = 'N';
				if( count($ml) > 0 && in_array($arr['id_field'], $ml))
					$multilignes = 'Y';
				else
					$multilignes = 'N';
				if( count($dz) > 0 && in_array($arr['id_field'], $dz))
					$disabled = 'Y';
				else
					$disabled = 'N';
				$req = "update ".BAB_DBDIR_FIELDSEXTRA_TBL." set modifiable='".$modifiable."', required='".$required."', multilignes='".$multilignes."', disabled='".$disabled."' where id='".$db->db_escape_string($arr['id'])."'";
				$db->db_query($req);
				}
			}
		}
	return true;
	}


function confirmDeleteDirectory($id, $type)
	{
	include_once $GLOBALS['babInstallPath'].'utilit/delincl.php';
	
	if( $type == 'd')
		{
		bab_deleteDbDirectory($id);
		}
	else if( $type == 'l')
		{
		bab_deleteLdapDirectory($id);
		}
	Header('Location: '. $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
	}

function dbUpdateDiplay($id, $listfd)
{
	global $babDB;
	list($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
	$babDB->db_query("update ".BAB_DBDIR_FIELDSEXTRA_TBL." set ordering='0' where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($id))."'");
	for($i=0; $i < count($listfd); $i++)
		{
		$babDB->db_query("update ".BAB_DBDIR_FIELDSEXTRA_TBL." set ordering='".($i + 1)."' where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($id))."' and id_field='".$babDB->db_escape_string($listfd[$i])."'");
		}
}

function dbUpdateOvmlFile($id, $ovmllist, $ovmldetail)
{
	global $babDB;

	$babDB->db_query("update ".BAB_DB_DIRECTORIES_TBL." set ovml_list='".$babDB->db_escape_string($ovmllist)."', ovml_detail='".$babDB->db_escape_string($ovmldetail)."' where id='".$babDB->db_escape_string($id)."'");

}

function dbUpdateListOrder($id, $listfd)
{
	global $babDB;
	list($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));

	$updated = array();
	for($i=0; $i < count($listfd); $i++)
		{
		$babDB->db_query("update ".BAB_DBDIR_FIELDSEXTRA_TBL." set list_ordering='".($i + 1)."' where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($id))."' and id_field='".$babDB->db_escape_string($listfd[$i])."'");
		$updated[$listfd[$i]] = true;
		}

	$res = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($id))."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		if( !isset($arr['id_field']))
		{
		$babDB->db_query("update ".BAB_DBDIR_FIELDSEXTRA_TBL." set list_ordering='".($i + 1)."' where id='".$babDB->db_escape_string($arr['id'])."");
		$i++;
		}
	}

}

function deleteFieldsExtra($id, $fxid)
{
	global $babDB;
	$res = $babDB->db_query("select id_directory, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id='".$babDB->db_escape_string($fxid)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
		{
			return;
		}

		$babDB->db_query("delete from ".BAB_DBDIR_FIELDSVALUES_TBL." where id_fieldextra='".$babDB->db_escape_string($fxid)."'");
		$babDB->db_query("delete from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$babDB->db_escape_string($fxid)."'");
		$babDB->db_query("delete from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'");
		$babDB->db_query("delete from ".BAB_DBDIRFIELDUPDATE_GROUPS_TBL." where id_object='".$babDB->db_escape_string($fxid)."'");
		$babDB->db_query("delete from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id='".$babDB->db_escape_string($fxid)."'");
		if( $arr['id_directory'] == 0 )
		{
		$babDB->db_query("delete from ".BAB_SITES_FIELDS_REGISTRATION_TBL." where id_field='".$babDB->db_escape_string($arr['id_field'])."'");
		$babDB->db_query("delete from ".BAB_LDAP_SITES_FIELDS_TBL." where id_field='".$babDB->db_escape_string($arr['id_field'])."'");
		}
	}
}

function updateFieldsExtraValues($id, $fxid, $fields_values, $fvdef,$value, $mvyn)
{
	global $babDB;
	$addslashes = false;

	$rr = $babDB->db_fetch_array($babDB->db_query("select id_field, default_value from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id='".$babDB->db_escape_string($fxid)."'"));

	if( !isset($rr['id_field']) || $rr['id_field'] < 7 )
	{
		return;
	}


	if( $rr['id_field'] > BAB_DBDIR_MAX_COMMON_FIELDS )
	{
		if( isset($GLOBALS['fieldname']) && !empty($GLOBALS['fieldname']))
		{

			$babDB->db_query("update ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." set name='".$babDB->db_escape_string($GLOBALS['fieldname'])."' where id='".$babDB->db_escape_string(($rr['id_field']-BAB_DBDIR_MAX_COMMON_FIELDS))."'");
		}
	}

	$existing = array();
	$res = $babDB->db_query("SELECT * FROM ".BAB_DBDIR_FIELDSVALUES_TBL." WHERE id_fieldextra = '".$babDB->db_escape_string($fxid)."'");
	while ($arr = $babDB->db_fetch_array($res))
		{
		$existing[$arr['field_value']] = $arr['id'];
		}
	
	function fieldvalue(&$existing,$value)
		{
		global $babDB,$fxid,$addslashes;

		if (isset($existing[$value]))
			{
			$id = $existing[$value];
			unset($existing[$value]);
			return $id;
			}
		else
			{
			$value = trim($value);
			$babDB->db_query("INSERT INTO ".BAB_DBDIR_FIELDSVALUES_TBL." (id_fieldextra, field_value) VALUES ('".$babDB->db_escape_string($fxid)."','".$babDB->db_escape_string($value)."')");
			return $babDB->db_insert_id();
			}
		}

	$default_value = 0;

	if ($mvyn == 'Y')
		{
		foreach($fields_values as $value)
			{
			$tmp = fieldvalue($existing,$value);
			if ($value == $fvdef)
				$default_value = $tmp;
			}
		}
	else
		{
		$default_value = fieldvalue($existing,$value);
		}

	$babDB->db_query("UPDATE ".BAB_DBDIR_FIELDSEXTRA_TBL." SET  multi_values = '".$babDB->db_escape_string($mvyn)."', default_value='".$babDB->db_escape_string($default_value)."' WHERE id='".$babDB->db_escape_string($fxid)."'");

	foreach($existing as $id) {
		$babDB->db_query("DELETE FROM ".BAB_DBDIR_FIELDSVALUES_TBL." WHERE id='".$babDB->db_escape_string($id)."'");
		}
}


function addDbField($id, $fieldn, $fieldv, &$message)
{
	global $babDB;

	if( empty($fieldn))
		{
		$message = bab_translate("ERROR: You must provide a name !!");
		return false;
		}


	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
	if( $arr['id_group'] != 0 )
		{
		$iddir = 0;
		}
	else
		{
		$iddir = $id;
		}

	$res = $babDB->db_query("select id from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id_directory='".$babDB->db_escape_string($iddir)."' and name='".$babDB->db_escape_string($fieldn)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$message = bab_translate("ERROR: This field already exists");
		return false;
		}
	else
		{
		$rr = $babDB->db_fetch_array($babDB->db_query("select max(list_ordering) from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$babDB->db_escape_string($iddir)."'"));
		$babDB->db_query("insert into ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." ( id_directory, name) values ('".$babDB->db_escape_string($iddir)."','".$babDB->db_escape_string($fieldn)."')");
		$id = $babDB->db_insert_id();
		if( $iddir == 0 )
			{
			$res = $babDB->db_query("select id from ".BAB_SITES_TBL."");
			while( $row = $babDB->db_fetch_array($res))
				{
				$babDB->db_query("insert into ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes) values ('".$babDB->db_escape_string($row['id'])."', '".$babDB->db_escape_string((BAB_DBDIR_MAX_COMMON_FIELDS + $id))."','N','N', 'N')");
				$babDB->db_query("insert into ".BAB_LDAP_SITES_FIELDS_TBL." (id_field, id_site) values ('".$babDB->db_escape_string((BAB_DBDIR_MAX_COMMON_FIELDS + $id))."', '".$babDB->db_escape_string($row['id'])."')");
				}
			}
		
		$req = "insert into ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES ('" .$babDB->db_escape_string($iddir). "', '" . $babDB->db_escape_string((BAB_DBDIR_MAX_COMMON_FIELDS + $id)). "', '0', 'N', 'N', 'N', '0', '".$babDB->db_escape_string(($rr[0]+1))."')";
		$babDB->db_query($req);
		$fxid = $babDB->db_insert_id();
		if( !empty($fieldv))
			{
			$babDB->db_query("insert into ".BAB_DBDIR_FIELDSVALUES_TBL." (id_fieldextra, field_value) VALUES ('" .$babDB->db_escape_string($fxid)."', '".$babDB->db_escape_string(trim($fieldv))."')");		
			$fvid = $babDB->db_insert_id();			
			$babDB->db_query("update ".BAB_DBDIR_FIELDSEXTRA_TBL." set default_value='".$babDB->db_escape_string($fvid)."' where id='".$babDB->db_escape_string($fxid)."'");
			}
		}
	return true;
}


function record_search_options()
{
	global $babBody;
	$db = &$GLOBALS['babDB'];

	if (!isset($_POST['listfd']))
	{
		$babBody->msgerror = bab_translate("You must define one collumn at least");
		return false;
	}

	$listfd = implode(',',$_POST['listfd']);

	list($n) = $db->db_fetch_array($db->db_query("SELECT COUNT(*) FROM ".BAB_DBDIR_OPTIONS_TBL));
	if ($n > 0)
		$db->db_query("UPDATE ".BAB_DBDIR_OPTIONS_TBL." SET search_view_fields='".$db->db_escape_string($listfd)."'");
	else
		$db->db_query("INSERT INTO ".BAB_DBDIR_OPTIONS_TBL." (search_view_fields) VALUES ('".$db->db_escape_string($listfd)."')");

	return true;
}

function updateDirGroups($dirgrpids) // enregistrement des modifications aux annuaires de groupes
{

	global $babBody, $babDB;

	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");

	if ($babBody->currentAdmGroup > 0)
		{
		$babDB->db_query("update ".BAB_GROUPS_TBL." set directory='N' where  lf>='".$babBody->currentDGGroup['lf']."' AND lr<='".$babBody->currentDGGroup['lr']."'");
		}
	else
		{
		$babDB->db_query("update ".BAB_GROUPS_TBL." set directory='N'");
		}

	for( $i=0; $i < count($dirgrpids); $i++)
	{
		$babDB->db_query("update ".BAB_GROUPS_TBL." set directory='Y' where id='".$babDB->db_escape_string($dirgrpids[$i])."'");

		$res = $babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$dirgrpids[$i]."'");
		if( !$res || $babDB->db_num_rows($res) == 0 )
		{
			$babDB->db_query("insert into ".BAB_DB_DIRECTORIES_TBL." (name, description, id_group, id_dgowner) values ('".$babDB->db_escape_string(bab_getGroupName($dirgrpids[$i], false))."','','".$babDB->db_escape_string($dirgrpids[$i])."', '".$babBody->currentAdmGroup."')");
		}
		else
		{
			$babDB->db_query("update ".BAB_DB_DIRECTORIES_TBL." set id_dgowner=".$babBody->currentAdmGroup." where id_group='".$babDB->db_escape_string($dirgrpids[$i])."'");
		}
		
	}
	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admdir");
	exit;
}


/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['directories'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'list');

if( isset($add))
{
	switch($add)
	{
		case 'ldap':
			if( !addLdapDirectory($adname, $description, $servertype, $decodetype, $host, $basedn, $userdn, $password1, $password2))
				{
				$idx = 'new';
				}
			break;
		case 'db':
			if (!isset($ml)) { $ml = array(); }
			if (!isset($rw)) { $rw = array(); }
			if (!isset($dz)) { $dz = array(); }
			if (!isset($req)) { $req = array(); }
			if( !addDbDirectory($adname, $description, $displayiu, $fields, $rw, $req, $ml, $dz))
				{
				$idx = 'new';
				}
			break;
	}
}

if( isset($modify))
{
	if( !empty($admod))
	{
		switch($modify)
		{
			case 'ldap':
				if( !modifyAdLdap($id, $adname, $description, $servertype, $decodetype, $host, $basedn, $userdn, $password1, $password2))
				{
				$idx = 'mldap';
				}
				break;

			case 'db':
				if (!isset($ml)) { $ml = array(); }
				if (!isset($rw)) { $rw = array(); }
				if (!isset($dz)) { $dz = array(); }
				if (!isset($req)) { $req = array(); }
				if (!isset($allowuu)) { $allowuu= ''; }
				if( !modifyAdDb($id, $adname, $description, $displayiu, $rw, $req, $ml, $dz, $allowuu))
				{
				$idx = 'mdb';
				}
				break;
		}
	}
	else if( !empty($delete))
	{
		switch($modify)
		{
			case 'ldap':
				$idx = 'delldap';
				break;
			case 'db':
				$idx = 'deldb';
				break;
		}
	}
	else
	{
		switch($modify)
		{
			case 'dbfval':
				if( !isset($fvdef)) { $fvdef=0;}
				if( !isset($mvyn)) { $mvyn='';}
				if( isset($adfdel))
					{
					deleteFieldsExtra($id, $fxid);
					}
				else
					{
					$fields_values = isset($_POST['fields_values']) ? $_POST['fields_values'] : array();

					updateFieldsExtraValues($id, $fxid, $fields_values, $fvdef, $value, $mvyn);
					}
				if( isset($adfsav) || isset($adfdel))
					{
					$idx='unload';
					$popupmessage = bab_translate("Update done");
					$refreshurl = $GLOBALS['babUrlScript'].'?tg=admdir&idx=mdb&id='.$id.'';
					}
				break;
			case 'addfield':
				$message = '';
				if( !addDbField($id, $fieldn, $fieldv, $message))
					{
					$idx = 'addf';
					}
				else
					{
					$idx='unload';
					$popupmessage = bab_translate("Update done");
					$refreshurl = $GLOBALS['babUrlScript'].'?tg=admdir&idx=mdb&id='.$id.'';
					}
				break;
		}
	}
}

if( isset($action) && $action == 'Yes')
	{
	confirmDeleteDirectory($id, $type);
	}

if( isset($aclview))
	{
	maclGroups();
	Header('Location: '. $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
	}

if( isset($aclfield))
	{
	maclGroups();
	Header('Location: '. $GLOBALS['babUrlScript'].'?tg=admdir&idx=mdb&id='.$id);
	}

if( isset($update) )
	{
	if( $update == 'displaydb' )
		{
		if(!dbUpdateDiplay($id, $listfd))
			$idx = 'list';
		}
	elseif( $update == 'ovmldb' )
		{
		if(!dbUpdateOvmlFile($id, $ovmllist, $ovmldetail))
			$idx = 'list';
		}
	elseif( $update == 'dblistord' )
		{
		if(!dbUpdateListOrder($id, $listfields))
			$idx = 'list';
		}
	elseif( 'search' == $_POST['update'] && $babBody->isSuperAdmin)
		{
		if (!record_search_options())
			{
			$idx = 'search';
			}
		}
	elseif( $update == 'gdirs')
		{
		if (!isset($dirgrpids)) { $dirgrpids = array(); }
		updateDirGroups($dirgrpids);
		$idx = 'list';
		}
	}

switch($idx)
	{
	case 'unload':
		if( !isset($popupmessage)) { $popupmessage ='';}
		if( !isset($refreshurl)) { $refreshurl = '';}
		popupUnload($popupmessage, $refreshurl);
		exit;

	case 'addval':
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("List of values");
		showDbFieldValuesModify($id, $fxid);
		printBabBodyPopup();
		exit;
		break;

	case 'addf':
		if( !isset($message)) { $message = '';}
		if( !isset($fieldn)) { $fieldn = '';}
		if( !isset($fieldv)) { $fieldv = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = $message;
		$babBodyPopup->title = bab_translate("Add new field");
		showDbAddField($id, $fieldn, $fieldv);
		printBabBodyPopup();
		exit;
		break;

	case 'gviewl':
		$babBody->title = getDirectoryName($id, BAB_LDAP_DIRECTORIES_TBL);
		$macl = new macl('admdir', 'list', $id, 'aclview');
        $macl->addtable( BAB_LDAPDIRVIEW_GROUPS_TBL, bab_translate("View"));
        $macl->babecho();
        
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('gviewl', bab_translate("View"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=gviewl&id='.$id);
		break;

	case 'fieldrights':
		$babBody->title = getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
		$idgroup =  isDirectoryGroup($id);
		$fieldname = getDirectoryFieldName($fxid);

		$macl = new macl('admdir', 'mdb', $fxid, 'aclfield');
		$macl->set_hidden_field('id', $id);
        $macl->addtable( BAB_DBDIRFIELDUPDATE_GROUPS_TBL, bab_translate("Who can update this field").':  '.$fieldname);
        $macl->babecho();

		$babBody->addItemMenu('mdb', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('mdb', bab_translate("Modify"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=mdb&id='.$id);
		$babBody->addItemMenu('fieldrights', bab_translate("Field rights"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=fieldrights&id='.$id.'&fxid='.$fxid);
		break;

	case 'db_rights':
		$babBody->title = getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
		$idgroup =  isDirectoryGroup($id);

		$macl = new macl('admdir', 'list', $id, 'aclview');
        $macl->addtable( BAB_DBDIRVIEW_GROUPS_TBL, bab_translate("View"));
		$macl->addtable( BAB_DBDIRUPDATE_GROUPS_TBL, bab_translate("Modify"));
		$macl->addtable( BAB_DBDIRADD_GROUPS_TBL, bab_translate("Add"));
		
		$macl->addtable( BAB_DBDIRDEL_GROUPS_TBL, bab_translate("Delete"));
		if( $idgroup == 0 || $idgroup > BAB_ADMINISTRATOR_GROUP )
			{
			$macl->addtable( BAB_DBDIREMPTY_GROUPS_TBL, bab_translate("Empty"));
			}

		$macl->addtable( BAB_DBDIRIMPORT_GROUPS_TBL, bab_translate("Import"));
		$macl->addtable( BAB_DBDIREXPORT_GROUPS_TBL, bab_translate("Export"));
		if( $idgroup )
			{
			$macl->addtable( BAB_DBDIRBIND_GROUPS_TBL, bab_translate("Assign a user to a directory"));
			$macl->addtable( BAB_DBDIRUNBIND_GROUPS_TBL, bab_translate("Unassign a user from a directory"));
			}
        $macl->babecho();

		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('db_rights', bab_translate("Rights"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=db_rights&id='.$id);
		break;

	case 'gmodify':
		$babBody->title = getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
		aclGroups('admdir', 'list', BAB_DBDIRUPDATE_GROUPS_TBL, $id, 'aclview');
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('gviewd', bab_translate("View"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=gviewd&id='.$id);
		$babBody->addItemMenu('gmodify', bab_translate("Modify"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=gmodify&id='.$id);
		$babBody->addItemMenu('gadd', bab_translate("Add"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=gadd&id='.$id);
		break;
	
	case 'gadd':
		$babBody->title = getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
		aclGroups('admdir', 'list', BAB_DBDIRADD_GROUPS_TBL, $id, 'aclview');
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('gviewd', bab_translate("View"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=gviewd&id='.$id);
		$babBody->addItemMenu('gmodify', bab_translate("Modify"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=gmodify&id='.$id);
		$babBody->addItemMenu('gadd', bab_translate("Add"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=gadd&id='.$id);
		break;

	case 'delldap':
		$babBody->title = bab_translate("Delete directory");
		deleteAd($id, BAB_LDAP_DIRECTORIES_TBL);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('del', bab_translate("Delete"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=del&id='.$id);
		break;

	case 'deldb':
		$babBody->title = bab_translate("Delete directory");
		deleteAd($id, BAB_DB_DIRECTORIES_TBL);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('del', bab_translate("Delete"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=del&id='.$id);
		break;

	case 'mldap':
		$babBody->title = bab_translate("Modify directory");
		modifyLdap($id);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('mldap', bab_translate("Modify"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=mldap&id='.$id);
		break;

	case 'dispdb':
		$babBody->title = bab_translate("Modify directory");
		displayDb($id);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('mdb', bab_translate("Modify"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=mdb&id='.$id);
		$babBody->addItemMenu('dispdb', bab_translate("Display"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=dispdb&id='.$id);
		$babBody->addItemMenu('lorddb', bab_translate("Order"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=lorddb&id='.$id);
		break;

	case 'lorddb':
		$babBody->title = bab_translate("Modify directory");
		dbListOrder($id);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('mdb', bab_translate("Modify"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=mdb&id='.$id);
		$babBody->addItemMenu('dispdb', bab_translate("Display"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=dispdb&id='.$id);
		$babBody->addItemMenu('lorddb', bab_translate("Order"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=lorddb&id='.$id);
		break;

	case 'mdb':
		$babBody->title = bab_translate("Modify directory");
		modifyDb($id);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('mdb', bab_translate("Modify"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=mdb&id='.$id);
		$babBody->addItemMenu('dispdb', bab_translate("Display"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=dispdb&id='.$id);
		$babBody->addItemMenu('lorddb', bab_translate("Order"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=lorddb&id='.$id);
		break;

	case 'ldap':
		$babBody->title = bab_translate("Add new ldap directory");
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('ldap', bab_translate("New"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=ldap');
		if (!function_exists('ldap_connect'))
			{
			$babBody->msgerror = bab_translate("You must have LDAP enabled on the server");
			break;
			}
		if( !isset($adname) ) { $adname ='';}
		if( !isset($description) ) { $description ='';}
		if( !isset($servertype) ) { $servertype =0;}
		if( !isset($decodetype) ) { $decodetype =0;}
		if( !isset($host) ) { $host ='';}
		if( !isset($basedn) ) { $basedn ='';}
		if( !isset($userdn) ) { $userdn ='';}
		addAdLdap($adname, $description, $servertype, $decodetype, $host, $basedn, $userdn);
		break;

	case 'db':
		$babBody->title = bab_translate("Add new database directory");
		if( !isset($adname) ) { $adname ='';}
		if( !isset($description) ) { $description ='';}
		addAdDb($adname, $description);
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu('db', bab_translate("New"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=db');
		break;

	case 'search':
		if( $babBody->isSuperAdmin )
		{
		$babBody->title = bab_translate("Fields to display for a search in all directories");
		search_options();
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu("ldg", bab_translate("Groups directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=ldg");
		$babBody->addItemMenu('search', bab_translate("Search options"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=search');
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	case 'ldg':
		$babBody->title = bab_translate("Groups directories");
		dirGroups();
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("ldg", bab_translate("Groups directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=ldg");
		if( $babBody->isSuperAdmin )
		{
		$babBody->addItemMenu("search", bab_translate("Search options"), $GLOBALS['babUrlScript']."?tg=admdir&idx=search");
		}
	break;

	case 'list':
	default:
		$babBody->title = bab_translate("Directories");
		listAds();
		$babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=list');
		$babBody->addItemMenu("ldg", bab_translate("Groups directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=ldg");
		if( $babBody->isSuperAdmin )
		{
		$babBody->addItemMenu('search', bab_translate("Search options"), $GLOBALS['babUrlScript'].'?tg=admdir&idx=search');
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>