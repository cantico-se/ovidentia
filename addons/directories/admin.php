<?php
include $babAddonPhpPath."adincl.php";
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
			$this->directories = ad_translate("Directories");
			$this->desctxt = ad_translate("Description");
			$this->databasetitle = ad_translate("Databases Directories list");
			$this->add = ad_translate("Add");
			$this->gmodify = ad_translate("Modify");
			$this->gview = ad_translate("View");
			$this->gadd = ad_translate("Add");
			$this->urladdldap = $GLOBALS['babAddonUrl']."admin&idx=ldap";
			$this->urladddb = $GLOBALS['babAddonUrl']."admin&idx=db";
			$this->db = $GLOBALS['babDB'];
			$this->resldap = $this->db->db_query("select * from ".ADDON_DIRECTORIES_TBL." where ldap='Y'");
			$this->countldap = $this->db->db_num_rows($this->resldap);
			$this->resdb = $this->db->db_query("select * from ".ADDON_DIRECTORIES_TBL." where ldap='N'");
			$this->countdb = $this->db->db_num_rows($this->resdb);
			}

		function getnextldap()
			{
			static $i = 0;
			if( $i < $this->countldap)
				{
				$arr = $this->db->db_fetch_array($this->resldap);
				$this->description = $arr['description'];
				$this->url = $GLOBALS['babAddonUrl']."admin&idx=mldap&id=".$arr['id'];
				$this->urlname = $arr['name'];
				$this->gviewurl = $GLOBALS['babAddonUrl']."admin&idx=gview&id=".$arr['id'];
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
				$this->url = $GLOBALS['babAddonUrl']."admin&idx=mdb&id=".$arr['id'];
				$this->urlname = $arr['name'];
				$this->gviewurl = $GLOBALS['babAddonUrl']."admin&idx=gview&id=".$arr['id'];
				$this->gaddurl = $GLOBALS['babAddonUrl']."admin&idx=gadd&id=".$arr['id'];
				$this->gmodifyurl = $GLOBALS['babAddonUrl']."admin&idx=gmodify&id=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."admin.html", "adlist"));
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
			$this->name = ad_translate("Name");
			$this->description = ad_translate("Description");
			$this->no = ad_translate("No");
			$this->yes = ad_translate("Yes");
			$this->password = ad_translate("Password");
			$this->repassword = ad_translate("Confirm");
			$this->host = ad_translate("Host");
			$this->basedn = ad_translate("BaseDN");
			$this->userdn = ad_translate("User DN");
			$this->type = "ldap";
			$this->add = ad_translate("Add");

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
	$babBody->babecho(	bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."admin.html", "ldapadd"));
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
			$this->name = ad_translate("Name");
			$this->description = ad_translate("Description");
			$this->password = ad_translate("Password");
			$this->repassword = ad_translate("Confirm");
			$this->host = ad_translate("Host");
			$this->basedn = ad_translate("BaseDN");
			$this->userdn = ad_translate("User DN");
			$this->add = ad_translate("Modify");
			$this->delete = ad_translate("Delete");

			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".ADDON_DIRECTORIES_TBL." where id='".$id."'");
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
	$babBody->babecho(bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."admin.html", "ldapmodify"));
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
			$this->name = ad_translate("Name");
			$this->description = ad_translate("Description");
			$this->field = ad_translate("Fields");
			$this->defaultvalue = ad_translate("Default Value");
			$this->rw = ad_translate("Modifiable");
			$this->required = ad_translate("Required");
			$this->multilignes = ad_translate("Multilignes");
			$this->add = ad_translate("Add");
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".ADDON_FIELDS_TBL);
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
				$this->fieldn = ad_translate($arr['description']);
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
	$babBody->babecho( bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."admin.html", "dbadd"));
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

		function temp($id)
			{
			$this->id = $id;
			$this->name = ad_translate("Name");
			$this->description = ad_translate("Description");
			$this->field = ad_translate("Fields");
			$this->defaultvalue = ad_translate("Default Value");
			$this->rw = ad_translate("Modifiable");
			$this->required = ad_translate("Required");
			$this->multilignes = ad_translate("Multilignes");
			$this->add = ad_translate("Modify");
			$this->delete = ad_translate("Delete");
			$this->db = $GLOBALS['babDB'];
			$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".ADDON_DIRECTORIES_TBL." where id='".$id."'"));
			$this->vname = $arr['name'];
			$this->vdescription = $arr['description'];
			$this->res = $this->db->db_query("select * from ".ADDON_DIRECTORIES_FIELDS_TBL." where id_directory='".$id."'");
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
				$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".ADDON_FIELDS_TBL." where id='".$arr['id_field']."'"));
				$this->fieldn = ad_translate($arr['description']);
				$this->fieldv = $arr['name'];
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id);
	$babBody->babecho( bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."admin.html", "dbmodify"));
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
			$this->resf = $babDB->db_query("select id, id_field from ".ADDON_DIRECTORIES_FIELDS_TBL." where id_directory='".$id."' and ordering='0'");
			$this->countf = $babDB->db_num_rows($this->resf);
			$this->resfd = $babDB->db_query("select id, id_field from ".ADDON_DIRECTORIES_FIELDS_TBL." where id_directory='".$id."' and ordering!='0' order by ordering asc");
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
				$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".ADDON_FIELDS_TBL." where id='".$arr['id_field']."'"));
				$this->fieldval = ad_translate($arr['description']);
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
				$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".ADDON_FIELDS_TBL." where id='".$arr['id_field']."'"));
				$this->fieldval = ad_translate($arr['description']);
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id);
	$babBody->babecho( bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."admin.html", "dbdisplay"));
	}

function deleteAd($id)
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
			$this->message = ad_translate("Are you sure you want to delete this directory");
			$this->title = getDirectoryName($id);
			$this->warning = ad_translate("WARNING: This operation will delete directory and all references"). "!";
			$this->urlyes = $GLOBALS['babAddonUrl']."admin&idx=list&id=".$id."&action=Yes";
			$this->yes = ad_translate("Yes");
			$this->urlno = $GLOBALS['babAddonUrl']."admin&idx=list";
			$this->no = ad_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function addLdapDirectory($name, $description, $host, $basedn, $userdn, $password1, $password2)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = ad_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( empty($host))
		{
		$babBody->msgerror = ad_translate("ERROR: You must provide a host address !!");
		return false;
		}

	if( $password1 != $password2)
		{
		$babBody->msgerror = ad_translate("ERROR: Passwords not match !!");
		return;
		}

	if( strtolower(ini_get("magic_quotes_gpc")) == "off" || !get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".ADDON_DIRECTORIES_TBL." where name='".$name."' and ldap='Y'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = ad_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "insert into ".ADDON_DIRECTORIES_TBL." (name, description, ldap, host, basedn, userdn, password) VALUES ('" .$name. "', '" . $description. "', 'Y', '" . $host. "', '" . $basedn. "', '" . $userdn. "', ENCODE(\"".$password1."\",\"".$GLOBALS['BAB_HASH_VAR']."\"))";
		$db->db_query($req);
		}
	return true;
	}

function addDbDirectory($name, $description, $fields, $rw, $rq, $ml)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = ad_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( strtolower(ini_get("magic_quotes_gpc")) == "off" || !get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".ADDON_DIRECTORIES_TBL." where name='".$name."' and ldap='N'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = ad_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "insert into ".ADDON_DIRECTORIES_TBL." (name, description, ldap, host, basedn, userdn, password) VALUES ('" .$name. "', '" . $description. "', 'N', '', '', '', '')";
		$db->db_query($req);
		$id = $db->db_insert_id();
		$res = $db->db_query("select * from ".ADDON_FIELDS_TBL);
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
			$req = "insert into ".ADDON_DIRECTORIES_FIELDS_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes) VALUES ('" .$id. "', '" . $arr['id']. "', '".$fields[$arr['name']]."', '".$modifiable."', '".$required."', '".$multilignes."')";
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
		$babBody->msgerror = ad_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( empty($host))
		{
		$babBody->msgerror = ad_translate("ERROR: You must provide a host address !!");
		return false;
		}

	if( !empty($password1) || !empty($password2))
		{
		if( $password1 != $password2)
			{
			$babBody->msgerror = ad_translate("ERROR: Passwords not match !!");
			return false;
			}
		}

	if( strtolower(ini_get("magic_quotes_gpc")) == "off" || !get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".ADDON_DIRECTORIES_TBL." where name='".$name."' and id!='".$id."' and ldap='Y'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = ad_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "update ".ADDON_DIRECTORIES_TBL." set name='".$name."', description='".$description."', ldap='Y', host='".$host."', basedn='".$basedn."', userdn='".$userdn."'";
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
		$babBody->msgerror = ad_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( strtolower(ini_get("magic_quotes_gpc")) == "off" || !get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select name from ".ADDON_DIRECTORIES_TBL." where name='".$name."' and id!='".$id."' and ldap='N'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = ad_translate("ERROR: This directory already exists");
		return false;
		}
	else
		{
		$req = "update ".ADDON_DIRECTORIES_TBL." set name='".$name."', description='".$description."', ldap='N', host='', basedn='', userdn='', password='' where id='".$id."'";
		$db->db_query($req);
		$res = $db->db_query("select * from ".ADDON_FIELDS_TBL);
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
			$req = "update ".ADDON_DIRECTORIES_FIELDS_TBL." set default_value='".$fields[$arr['name']]."', modifiable='".$modifiable."', required='".$required."', multilignes='".$multilignes."' where id_directory='".$id."' and id_field='".$arr['id']."'";
			$db->db_query($req);
			}
		}
	return true;
	}


function confirmDeleteDirectory($id)
	{
	$db = $GLOBALS['babDB'];

	$arr = $db->db_fetch_array($db->db_query("select ldap from ".ADDON_DIRECTORIES_TBL." where id='".$id."'"));
	
	if( $arr['ldap'] == "Y")
		{
		$db->query("delete * from ".ADDON_DIRECTORIES_FIELDS_TBL." where id_directory='".$id."'");
		$db->query("delete * from ".ADDON_DBENTRIES_TBL." where id_directory='".$id."'");
		}

	// delete directory
	$res = $db->db_query("delete from ".ADDON_DIRECTORIES_TBL." where id='".$id."'");
	Header("Location: ". $GLOBALS['babAddonUrl']."admin&idx=list");
	}

function dbUpdateDiplay($id, $listfd)
{
	global $babDB;
	$babDB->db_query("update ".ADDON_DIRECTORIES_FIELDS_TBL." set ordering='0' where id_directory='".$id."'");
	for($i=0; $i < count($listfd); $i++)
		{
		$babDB->db_query("update ".ADDON_DIRECTORIES_FIELDS_TBL." set ordering='".($i + 1)."' where id_directory='".$id."' and id_field='".$listfd[$i]."'");
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
			case "db":
				$idx = "del";
				break;
		}
	}
}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteDirectory($id);
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
	case "gview":
		$babBody->title = getDirectoryName($id);
		aclGroups($GLOBALS['babAddonTarget']."/admin", "list", ADDON_DIRVIEW_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("gview", ad_translate("View"), $GLOBALS['babAddonUrl']."admin&idx=gview&id=".$id);
		$babBody->addItemMenu("gmodify", ad_translate("Modify"), $GLOBALS['babAddonUrl']."admin&idx=gmodify&id=".$id);
		$babBody->addItemMenu("gadd", ad_translate("Add"), $GLOBALS['babAddonUrl']."admin&idx=gadd&id=".$id);
		break;

	case "gmodify":
		$babBody->title = getDirectoryName($id);
		aclGroups($GLOBALS['babAddonTarget']."/admin", "list", ADDON_DIRUPDATE_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("gview", ad_translate("View"), $GLOBALS['babAddonUrl']."admin&idx=gview&id=".$id);
		$babBody->addItemMenu("gmodify", ad_translate("Modify"), $GLOBALS['babAddonUrl']."admin&idx=gmodify&id=".$id);
		$babBody->addItemMenu("gadd", ad_translate("Add"), $GLOBALS['babAddonUrl']."admin&idx=gadd&id=".$id);
		break;
	
	case "gadd":
		$babBody->title = getDirectoryName($id);
		aclGroups($GLOBALS['babAddonTarget']."/admin", "list", ADDON_DIRADD_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("gview", ad_translate("View"), $GLOBALS['babAddonUrl']."admin&idx=gview&id=".$id);
		$babBody->addItemMenu("gmodify", ad_translate("Modify"), $GLOBALS['babAddonUrl']."admin&idx=gmodify&id=".$id);
		$babBody->addItemMenu("gadd", ad_translate("Add"), $GLOBALS['babAddonUrl']."admin&idx=gadd&id=".$id);
		break;

	case "del":
		$babBody->title = ad_translate("Delete directory");
		deleteAd($id);
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("del", ad_translate("Delete"), $GLOBALS['babAddonUrl']."admin&idx=del&id=".$id);
		break;

	case "mldap":
		$babBody->title = ad_translate("Modify directory");
		modifyLdap($id);
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("mldap", ad_translate("Modify"), $GLOBALS['babAddonUrl']."admin&idx=mldap&id=".$id);
		break;

	case "dispdb":
		$babBody->title = ad_translate("Modify directory");
		displayDb($id);
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("mdb", ad_translate("Modify"), $GLOBALS['babAddonUrl']."admin&idx=mdb&id=".$id);
		$babBody->addItemMenu("dispdb", ad_translate("Display"), $GLOBALS['babAddonUrl']."admin&idx=dispdb&id=".$id);
		break;

	case "mdb":
		$babBody->title = ad_translate("Modify directory");
		modifyDb($id);
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("mdb", ad_translate("Modify"), $GLOBALS['babAddonUrl']."admin&idx=mdb&id=".$id);
		$babBody->addItemMenu("dispdb", ad_translate("Display"), $GLOBALS['babAddonUrl']."admin&idx=dispdb&id=".$id);
		break;

	case "ldap":
		$babBody->title = ad_translate("Add new ldap directory");
		addAdLdap($adname, $description, $host, $basedn, $userdn);
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("ldap", ad_translate("New"), $GLOBALS['babAddonUrl']."admin&idx=ldap");
		break;

	case "db":
		$babBody->title = ad_translate("Add new database directory");
		addAdDb($adname, $description);
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("db", ad_translate("New"), $GLOBALS['babAddonUrl']."admin&idx=db");
		break;

	case "list":
	default:
		$babBody->title = ad_translate("Ldap Directories list");
		listAds();
		$babBody->addItemMenu("list", ad_translate("Directories"), $GLOBALS['babAddonUrl']."admin&idx=list");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>