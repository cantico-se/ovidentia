<?php
include "base.php";
include $babInstallPath."utilit/dirincl.php";
include $babInstallPath."admin/acl.php";

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

		function temp()
			{
			$this->directories = bab_translate("Directories");
			$this->desctxt = bab_translate("Description");
			$this->databasetitle = bab_translate("Databases Directories list");
			$this->add = bab_translate("Add");
			$this->gmodify = bab_translate("Modify");
			$this->gview = bab_translate("View");
			$this->gadd = bab_translate("Add");
			$this->urladdldap = $GLOBALS['babUrlScript']."?tg=admdir&idx=ldap";
			$this->urladddb = $GLOBALS['babUrlScript']."?tg=admdir&idx=db";
			$this->db = $GLOBALS['babDB'];
			$this->resldap = $this->db->db_query("select * from ".BAB_LDAP_DIRECTORIES_TBL."");
			$this->countldap = $this->db->db_num_rows($this->resldap);
			$this->resdb = $this->db->db_query("select * from ".BAB_DB_DIRECTORIES_TBL."");
			$this->countdb = $this->db->db_num_rows($this->resdb);
			}

		function getnextldap()
			{
			static $i = 0;
			if( $i < $this->countldap)
				{
				$arr = $this->db->db_fetch_array($this->resldap);
				$this->description = $arr['description'];
				$this->url = $GLOBALS['babUrlScript']."?tg=admdir&idx=mldap&id=".$arr['id'];
				$this->urlname = $arr['name'];
				$this->gviewurl = $GLOBALS['babUrlScript']."?tg=admdir&idx=gviewl&id=".$arr['id'];
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
				$arr = $this->db->db_fetch_array($this->resdb);
				$this->description = $arr['description'];
				$this->url = $GLOBALS['babUrlScript']."?tg=admdir&idx=mdb&id=".$arr['id'];
				$this->urlname = $arr['name'];
				$this->gviewurl = $GLOBALS['babUrlScript']."?tg=admdir&idx=gviewd&id=".$arr['id'];
				$this->gaddurl = $GLOBALS['babUrlScript']."?tg=admdir&idx=gadd&id=".$arr['id'];
				$this->gmodifyurl = $GLOBALS['babUrlScript']."?tg=admdir&idx=gmodify&id=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admdir.html", "adlist"));
}

function addAdLdap($name, $description, $host, $basedn, $userdn)
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

		function temp($name, $description, $ldap, $host, $basedn, $userdn)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Confirm");
			$this->host = bab_translate("Host");
			$this->basedn = bab_translate("BaseDN");
			$this->userdn = bab_translate("User DN");
			$this->type = "ldap";
			$this->add = bab_translate("Add");

			$this->vname = $name == "" ? "" : $name;
			$this->vdescription = $description == "" ? "" : $description;
			$this->vhost = $host == "" ? "" : $host;
			$this->vbasedn = $basedn == "" ? "" : $basedn;
			$this->vuserdn = $userdn == "" ? "" : $userdn;
			if( $ldap == "Y" )
				$this->yselected = "selected";
			else
				$this->nselected = "selected";
			}
		}

	$temp = new temp($name, $description, $ldap, $host, $basedn, $userdn);
	$babBody->babecho(	bab_printTemplate($temp,"admdir.html", "ldapadd"));
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
			$this->id = $id;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Confirm");
			$this->host = bab_translate("Host");
			$this->basedn = bab_translate("BaseDN");
			$this->userdn = bab_translate("User DN");
			$this->add = bab_translate("Modify");
			$this->delete = bab_translate("Delete");

			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".BAB_LDAP_DIRECTORIES_TBL." where id='".$id."'");
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$this->vname = $arr['name'];
				$this->vdescription = $arr['description'];
				$this->vhost = $arr['host'];
				$this->vbasedn = $arr['basedn'];
				$this->vuserdn = $arr['userdn'];
				}
			}
		}

	$temp = new temp($id);
	$babBody->babecho(bab_printTemplate($temp,"admdir.html", "ldapmodify"));
	}

function addAdDb($adname, $description)
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

		function temp($adname, $description)
			{
			$this->vname = $adname == "" ? "" : $adname;
			$this->vdescription = $description == "" ? "" : $description;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->field = bab_translate("Fields");
			$this->defaultvalue = bab_translate("Default Value");
			$this->rw = bab_translate("Modifiable");
			$this->required = bab_translate("Required");
			$this->multilignes = bab_translate("Multilignes");
			$this->add = bab_translate("Add");
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
				$this->fieldn = bab_translate($arr['description']);
				$this->fieldv = $arr['name'];
				$this->fieldid = $arr['id'];
				$this->reqchecked = "";
				$this->rwchecked = "";
				$this->mlchecked = "";
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($adname, $description);
	$babBody->babecho( bab_printTemplate($temp,"admdir.html", "dbadd"));
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
			$this->bdel = true;
			$this->bfields = true;
			$this->db = $GLOBALS['babDB'];
			$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			$this->vname = $arr['name'];
			$this->vdescription = $arr['description'];
			if( $arr['id_group'] != 0 )
				{
				$iddir = 0;
				$this->bdel = false;
				if( $arr['id_group'] != 1 )
					$this->bfields = false;
				}
			else
				{
				$iddir = $id;
				}
			$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$iddir."'");
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
				$this->fieldid = $arr['id_field'];
				$this->defvalue = $arr['default_value'];
				if( $arr['modifiable'] == "Y")
					$this->rwchecked = "checked";
				else
					$this->rwchecked = "";

				if( $arr['required'] == "Y")
					$this->reqchecked = "checked";
				else
					$this->reqchecked = "";
				if( $arr['multilignes'] == "Y")
					$this->mlchecked = "checked";
				else
					$this->mlchecked = "";
				$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
				$this->fieldn = bab_translate($arr['description']);
				$this->fieldv = $arr['name'];
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id);
	$babBody->babecho( bab_printTemplate($temp,"admdir.html", "dbmodify"));
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
			$this->listftxt = "---- ".bab_translate("Fields")." ----";
			$this->listdftxt = "---- ".bab_translate("Fields to display")." ----";
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->update = bab_translate("Update");
			$arr = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			if( $arr['id_group'] != 0 )
				{
				$iddir = 0;
				}
			else
				{
				$iddir = $id;
				}
			$this->resf = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$iddir."' and ordering='0'");
			$this->countf = $babDB->db_num_rows($this->resf);
			$this->resfd = $babDB->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$iddir."' and ordering!='0' order by ordering asc");
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
				$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
				$this->fieldval = bab_translate($arr['description']);
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
				$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
				$this->fieldval = bab_translate($arr['description']);
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id);
	$babBody->babecho( bab_printTemplate($temp,"admdir.html", "dbdisplay"));
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
			$this->warning = bab_translate("WARNING: This operation will delete directory and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admdir&idx=list&id=".$id."&action=Yes&type=";
			if( $table == BAB_DB_DIRECTORIES_TBL )
				$this->urlyes .= "l";
			else
				$this->urlyes .= "d";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admdir&idx=list";
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id, $table);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function addLdapDirectory($name, $description, $host, $basedn, $userdn, $password1, $password2)
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

	if( strtolower(ini_get("magic_quotes_gpc")) == "off" || !get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".BAB_LDAP_DIRECTORIES_TBL." where name='".$name."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "insert into ".BAB_LDAP_DIRECTORIES_TBL." (name, description, host, basedn, userdn, password) VALUES ('" .$name. "', '" . $description. "', '" . $host. "', '" . $basedn. "', '" . $userdn. "', ENCODE(\"".$password1."\",\"".$GLOBALS['BAB_HASH_VAR']."\"))";
		$db->db_query($req);
		}
	return true;
	}

function addDbDirectory($name, $description, $fields, $rw, $rq, $ml)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( strtolower(ini_get("magic_quotes_gpc")) == "off" || !get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".BAB_DB_DIRECTORIES_TBL." where name='".$name."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "insert into ".BAB_DB_DIRECTORIES_TBL." (name, description) VALUES ('" .$name. "', '" . $description. "')";
		$db->db_query($req);
		$id = $db->db_insert_id();
		$res = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL);
		while( $arr = $db->db_fetch_array($res))
			{
			if( count($rw) > 0 && in_array($arr['id'], $rw))
				$modifiable = "Y";
			else
				$modifiable = "N";
			if( count($rq) > 0 && in_array($arr['id'], $rq))
				$required = "Y";
			else
				$required = "N";
			if( count($ml) > 0 && in_array($arr['id'], $ml))
				$multilignes = "Y";
			else
				$multilignes = "N";
			$req = "insert into ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes) VALUES ('" .$id. "', '" . $arr['id']. "', '".$fields[$arr['name']]."', '".$modifiable."', '".$required."', '".$multilignes."')";
			$db->db_query($req);
			}
		}
	return true;
	}

function modifyAdLdap($id, $name, $description, $host, $basedn, $userdn, $password1, $password2)
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

	if( strtolower(ini_get("magic_quotes_gpc")) == "off" || !get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".BAB_LDAP_DIRECTORIES_TBL." where name='".$name."' and id!='".$id."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "update ".BAB_LDAP_DIRECTORIES_TBL." set name='".$name."', description='".$description."', host='".$host."', basedn='".$basedn."', userdn='".$userdn."'";
		if( !empty($password1) )
			$req .= ", password='ENCODE(\"".$password1."\",\"".$GLOBALS['BAB_HASH_VAR']."\")'";
		$req .= " where id='".$id."'";
		$db->db_query($req);
		}
	return true;
	}

function modifyAdDb($id, $name, $description, $fields, $rw, $rq, $ml)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( strtolower(ini_get("magic_quotes_gpc")) == "off" || !get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".BAB_DB_DIRECTORIES_TBL." where name='".$name."' and id!='".$id."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$arr = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
		if( $arr['id_group'] != 0)
			$iddir = 0;
		else
			$iddir = $id;
		$req = "update ".BAB_DB_DIRECTORIES_TBL." set name='".$name."', description='".$description."' where id='".$id."'";
		$db->db_query($req);
		$res = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL);
		while( $arr = $db->db_fetch_array($res))
			{
			if( count($rw) > 0 && in_array($arr['id'], $rw))
				$modifiable = "Y";
			else
				$modifiable = "N";
			if( count($rq) > 0 && in_array($arr['id'], $rq))
				$required = "Y";
			else
				$required = "N";
			if( count($ml) > 0 && in_array($arr['id'], $ml))
				$multilignes = "Y";
			else
				$multilignes = "N";
			$req = "update ".BAB_DBDIR_FIELDSEXTRA_TBL." set default_value='".$fields[$arr['name']]."', modifiable='".$modifiable."', required='".$required."', multilignes='".$multilignes."' where id_directory='".$iddir."' and id_field='".$arr['id']."'";
			$db->db_query($req);
			}
		}
	return true;
	}


function confirmDeleteDirectory($id, $type)
	{
	$db = $GLOBALS['babDB'];

	
	if( $type == "d")
		{
		$arr = $db->db_fetch_array($db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
		if( $arr['id_group'] != 0)
			return;
		$db->db_query("delete from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".$id."'");
		$db->db_query("delete from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='".$id."'");
		$db->db_query("delete from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'");
		}
	else if( $type == "l")
		{
		$db->db_query("delete from ".BAB_LDAP_DIRECTORIES_TBL." where id='".$id."'");
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
	}

function dbUpdateDiplay($id, $listfd)
{
	global $babDB;
	$babDB->db_query("update ".BAB_DBDIR_FIELDSEXTRA_TBL." set ordering='0' where id_directory='".$id."'");
	for($i=0; $i < count($listfd); $i++)
		{
		$babDB->db_query("update ".BAB_DBDIR_FIELDSEXTRA_TBL." set ordering='".($i + 1)."' where id_directory='".$id."' and id_field='".$listfd[$i]."'");
		}
}
/* main */
$adminid = bab_isUserAdministrator();
if( $adminid <= 0 )
{
	$babBody->msgerror = "Access denied";
	return;
}

if( !isset($idx ))
	$idx = "list";

if( isset($add))
{
	switch($add)
	{
		case "ldap":
			if( !addLdapDirectory($adname, $description, $host, $basedn, $userdn, $password1, $password2))
				{
				$idx = "new";
				}
			break;
		case "db":
			if( !addDbDirectory($adname, $description, $fields, $rw, $req, $ml))
				{
				$idx = "new";
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
			case "ldap":
				if( !modifyAdLdap($id, $adname, $description, $host, $basedn, $userdn, $password1, $password2))
				{
				$idx = "mldap";
				}
				break;

			case "db":
				if( !modifyAdDb($id, $adname, $description, $fields, $rw, $req, $ml))
				{
				$idx = "mdb";
				}
				break;
		}
	}
	else if( !empty($delete))
	{
		switch($modify)
		{
			case "ldap":
				$idx = "delldap";
				break;
			case "db":
				$idx = "deldb";
				break;
		}
	}
}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteDirectory($id, $type);
	}

if( isset($aclview))
	{
	aclUpdate($table, $item, $groups, $what);
	$id = $item;
	}

if( isset($update) )
	{
	if( $update == "displaydb" )
		{
		if(!dbUpdateDiplay($id, $listfd))
			$idx = "list";
		}
	}

switch($idx)
	{
	case "gviewl":
		$babBody->title = getDirectoryName($id, BAB_LDAP_DIRECTORIES_TBL);
		aclGroups("admdir", "list", BAB_LDAPDIRVIEW_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("gviewl", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gviewl&id=".$id);
		break;

	case "gviewd":
		$babBody->title = getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
		aclGroups("admdir", "list", BAB_DBDIRVIEW_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("gviewd", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gviewd&id=".$id);
		$babBody->addItemMenu("gmodify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gmodify&id=".$id);
		$babBody->addItemMenu("gadd", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gadd&id=".$id);
		break;

	case "gmodify":
		$babBody->title = getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
		aclGroups("admdir", "list", BAB_DBDIRUPDATE_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("gviewd", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gviewd&id=".$id);
		$babBody->addItemMenu("gmodify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gmodify&id=".$id);
		$babBody->addItemMenu("gadd", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gadd&id=".$id);
		break;
	
	case "gadd":
		$babBody->title = getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
		aclGroups("admdir", "list", BAB_DBDIRADD_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("gviewd", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gviewd&id=".$id);
		$babBody->addItemMenu("gmodify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gmodify&id=".$id);
		$babBody->addItemMenu("gadd", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admdir&idx=gadd&id=".$id);
		break;

	case "delldap":
		$babBody->title = bab_translate("Delete directory");
		deleteAd($id, BAB_LDAP_DIRECTORIES_TBL);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("del", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=admdir&idx=del&id=".$id);
		break;

	case "deldb":
		$babBody->title = bab_translate("Delete directory");
		deleteAd($id, BAB_DB_DIRECTORIES_TBL);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("del", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=admdir&idx=del&id=".$id);
		break;

	case "mldap":
		$babBody->title = bab_translate("Modify directory");
		modifyLdap($id);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("mldap", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admdir&idx=mldap&id=".$id);
		break;

	case "dispdb":
		$babBody->title = bab_translate("Modify directory");
		displayDb($id);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("mdb", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admdir&idx=mdb&id=".$id);
		$babBody->addItemMenu("dispdb", bab_translate("Display"), $GLOBALS['babUrlScript']."?tg=admdir&idx=dispdb&id=".$id);
		break;

	case "mdb":
		$babBody->title = bab_translate("Modify directory");
		modifyDb($id);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("mdb", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admdir&idx=mdb&id=".$id);
		$babBody->addItemMenu("dispdb", bab_translate("Display"), $GLOBALS['babUrlScript']."?tg=admdir&idx=dispdb&id=".$id);
		break;

	case "ldap":
		$babBody->title = bab_translate("Add new ldap directory");
		addAdLdap($adname, $description, $host, $basedn, $userdn);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("ldap", bab_translate("New"), $GLOBALS['babUrlScript']."?tg=admdir&idx=ldap");
		break;

	case "db":
		$babBody->title = bab_translate("Add new database directory");
		addAdDb($adname, $description);
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		$babBody->addItemMenu("db", bab_translate("New"), $GLOBALS['babUrlScript']."?tg=admdir&idx=db");
		break;

	case "list":
	default:
		$babBody->title = bab_translate("Ldap Directories list");
		listAds();
		$babBody->addItemMenu("list", bab_translate("Directories"), $GLOBALS['babUrlScript']."?tg=admdir&idx=list");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>