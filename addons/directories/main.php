<?php
include $babAddonPhpPath."adincl.php";
include $babAddonPhpPath."ldap.php";
include $babInstallPath."utilit/tempfile.php";

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
			$this->directories = ad_translate("Directories");
			$this->desctxt = ad_translate("Description");
			$this->databasetitle = ad_translate("Databases Directories list");
			$this->ldaptitle = ad_translate("Ldap Directories list");
			$this->emptyname = ad_translate("Empty");
			$this->db = $GLOBALS['babDB'];
			$this->badd = false;
			$res = $this->db->db_query("select id from ".ADDON_DIRECTORIES_TBL." where ldap='Y'");
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(ADDON_DIRVIEW_GROUPS_TBL, $row['id']))
					{
					array_push($this->ldapid, $row['id']);
					}
				}
			$this->countldap = count($this->ldapid);
			$res = $this->db->db_query("select id from ".ADDON_DIRECTORIES_TBL." where ldap='N'");
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(ADDON_DIRVIEW_GROUPS_TBL, $row['id']))
					{
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
				$arr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".ADDON_DIRECTORIES_TBL." where id='".$this->ldapid[$i]."'"));
				$this->description = $arr['description'];
				$this->url = $GLOBALS['babAddonUrl']."main&idx=sldap&id=".$this->ldapid[$i];
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
				$arr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".ADDON_DIRECTORIES_TBL." where id='".$this->dbid[$i]."'"));
				$this->description = $arr['description'];
				$this->url = $GLOBALS['babAddonUrl']."main&idx=sdb&id=".$this->dbid[$i];
				$this->emptyurl = $GLOBALS['babAddonUrl']."main&idx=empdb&id=".$this->dbid[$i];
				$this->urlname = $arr['name'];
				$this->badd = bab_isAccessValid(ADDON_DIRADD_GROUPS_TBL, $this->dbid[$i]);
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "useradlist"));
}

function browseLdapDirectory($id, $pos)
{
	global $babBody;

	class temp
		{
		var $count;

		function temp($id, $pos)
			{
			$this->allname = ad_translate("All");
			$this->cntxt = ad_translate("Name");
			$this->bteltxt = ad_translate("Business Phone");
			$this->hteltxt = ad_translate("Home Phone");
			$this->emailtxt = ad_translate("Email");
			$this->addname = ad_translate("Add");
			$this->id = $id;
			$this->pos = $pos;
			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babAddonUrl']."main&idx=sldap&id=".$id."&pos=";
			$this->count = 0;
			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * , DECODE(password, \"".$GLOBALS['BAB_HASH_VAR']."\") as adpass from ".ADDON_DIRECTORIES_TBL." where id='".$id."'");
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if( $arr['ldap'] == "Y")
					{
					$this->ldap = new babLDAP($arr['host'], "", $arr['basedn'], $arr['userdn'], $arr['adpass'], true);
					$this->ldap->connect();
					$this->entries = $this->ldap->search("(|(cn=".$pos."*))", array("cn", "telephonenumber", "mail", "homephone"));
					if( is_array($this->entries))
						{
						$this->count = $this->entries['count'];
						}
					}
				else
					{
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
				$this->url = $GLOBALS['babAddonUrl']."main&idx=dldap&id=".$this->id."&cn=".$this->cn."&pos=".$this->pos;
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
				$this->selecturl = $GLOBALS['babAddonUrl']."main&idx=sldap&id=".$this->id."&pos=".$this->selectname;
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
	$babBody->babecho( bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "adbrowse"));
}

function browseDbDirectory($id, $pos, $badd)
{
	global $babBody;

	class temp
		{
		var $count;

		function temp($id, $pos, $badd)
			{
			$this->allname = ad_translate("All");
			$this->cntxt = ad_translate("Name");
			$this->bteltxt = ad_translate("Business Phone");
			$this->hteltxt = ad_translate("Home Phone");
			$this->emailtxt = ad_translate("Email");
			$this->addname = ad_translate("Add");
			$this->id = $id;
			$this->pos = $pos;
			$this->badd = $badd;
			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babAddonUrl']."main&idx=sdb&id=".$id."&pos=";
			$this->addurl = $GLOBALS['babAddonUrl']."main&idx=adbc&id=".$id;
			$this->count = 0;
			$this->db = $GLOBALS['babDB'];
			if(bab_isAccessValid(ADDON_DIRVIEW_GROUPS_TBL, $id))
				{
				$this->res = $this->db->db_query("select id, sn, givenname, email, btel, htel from ".ADDON_DBENTRIES_TBL." where givenname like '".$pos."%' and id_directory='".$id."' order by givenname, sn");
				$this->count = $this->db->db_num_rows($this->res);
				}
			else
				{
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

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->cn = $arr['givenname']. " ". $arr['sn'];
				$this->urlmail = $GLOBALS['babUrlScript']."?tg=mail&idx=compose&accid=".$this->accid."&to=".$arr['email'];
				$this->email = $arr['email'];
				$this->url = $GLOBALS['babAddonUrl']."main&idx=ddb&id=".$this->id."&idu=".$arr['id']."&pos=".$this->pos;
				$this->btel = $arr['btel'];
				$this->htel = $arr['htel'];
				$i++;
				return true;
				}
			else
				{
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
				$this->selecturl = $GLOBALS['babAddonUrl']."main&idx=sdb&id=".$this->id."&pos=".$this->selectname;
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

	$temp = new temp($id, $pos, $badd);
	$babBody->babecho( bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "adbrowse"));
}

function summaryLdapContact($id, $cn)
{
	global $babBody;

	class temp
		{
		var $babCss;
		var $babMeta;

		function temp($id, $cn)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babMeta = bab_printTemplate($this,"config.html", "babMeta");

			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".ADDON_FIELDS_TBL." where name !='jpegphoto' and x_name!=''");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;

			$res = $this->db->db_query("select * , DECODE(password, \"".$GLOBALS['BAB_HASH_VAR']."\") as adpass from ".ADDON_DIRECTORIES_TBL." where id='".$id."'");
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				if( $arr['ldap'] == "Y")
					{
					$this->ldap = new babLDAP($arr['host'], "", $arr['basedn'], $arr['userdn'], $arr['adpass'], true);
					$this->ldap->connect();
					$this->entries = $this->ldap->search("(|(cn=".$cn."))");
					$this->ldap->close();
					$this->name = $this->entries[0]['cn'][0];
					$this->urlimg = $GLOBALS['babAddonUrl']."main&idx=getimgl&id=".$id."&cn=".$cn;
					}
				else
					{
					$this->name = "";
					$this->urlimg = "";
					}
				}
			$this->bfieldv = true;
			}

		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldn = ad_translate($arr['description']);
				$this->fieldv = quoted_printable_decode($this->entries[0][$arr['x_name']][0]);
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id, $cn);
	echo bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "summarycontact");
}


function summaryDbContact($id, $idu)
{
	global $babBody;

	class temp
		{
		var $babCss;
		var $babMeta;

		function temp($id, $idu)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babMeta = bab_printTemplate($this,"config.html", "babMeta");
			$this->del = bab_isAccessValid(ADDON_DIRADD_GROUPS_TBL, $id);
			$this->modify = bab_isAccessValid(ADDON_DIRUPDATE_GROUPS_TBL, $id);
			if( $this->modify )
				{
				$this->modifytxt = ad_translate("Modify");
				$this->modifyurl = $GLOBALS['babAddonUrl']."main&idx=dbmod&id=".$id."&idu=".$idu;
				}

			if( $this->del )
				{
				$this->deltxt = ad_translate("Delete");
				$this->delurl = $GLOBALS['babAddonUrl']."main&idx=deldbc&id=".$id."&idu=".$idu;
				}

			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".ADDON_FIELDS_TBL." where name !='jpegphoto'");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;
			$res = $this->db->db_query("select * from ".ADDON_DBENTRIES_TBL." where id_directory='".$id."' and id='".$idu."'");
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$this->arr = $this->db->db_fetch_array($res);
				$this->name = $this->arr['givenname']. " ". $this->arr['sn'];
				$this->urlimg = $GLOBALS['babAddonUrl']."main&idx=getimg&id=".$id."&idu=".$idu;
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
				$this->fieldn = ad_translate($arr['description']);
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
	echo bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "summarycontact");
}

function modifyDbContact($id, $idu, $fields)
{
	global $babBody;

	class temp
		{
		var $babCss;
		var $babMeta;

		function temp($id, $idu, $fields)
			{
			global $babBody;
			$this->file = ad_translate("File");
			$this->update = ad_translate("Update");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babMeta = bab_printTemplate($this,"config.html", "babMeta");
			$this->id = $id;
			$this->idu = $idu;
			$this->fields = $fields;
			$this->what = "dbc";
			$this->badd = bab_isAccessValid(ADDON_DIRADD_GROUPS_TBL, $id);
			$this->bupd = bab_isAccessValid(ADDON_DIRUPDATE_GROUPS_TBL, $id);

			if( !empty($babBody->msgerror))
				{
				$this->msgerror = $babBody->msgerror;
				$this->error = true;
				}
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".ADDON_FIELDS_TBL." where name !='jpegphoto'");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;
			$res = $this->db->db_query("select * from ".ADDON_DBENTRIES_TBL." where id_directory='".$id."' and id='".$idu."'");
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$this->arr = $this->db->db_fetch_array($res);
				$this->name = $this->arr['givenname']. " ". $this->arr['sn'];
				$this->urlimg = $GLOBALS['babAddonUrl']."main&idx=getimg&id=".$this->id."&idu=".$idu;
				}
			else
				{
				$this->name = "";
				$this->urlimg = "";
				}
			$res = $this->db->db_query("select modifiable from ".ADDON_DIRECTORIES_FIELDS_TBL." join ".ADDON_FIELDS_TBL." where id_directory='".$this->id."' and id_field=".ADDON_FIELDS_TBL.".id and ".ADDON_FIELDS_TBL.".name='jpegphoto'");

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
				$this->fieldn = ad_translate($arr['description']);
				$this->fieldv = $arr['name'];
				if( isset($this->fields[$arr['name']]) )
					$this->fvalue = $this->fields[$arr['name']];
				else
					$this->fvalue = $this->arr[$arr['name']];
				$res = $this->db->db_query("select multilignes, required, modifiable from ".ADDON_DIRECTORIES_FIELDS_TBL." where id_directory='".$this->id."' and id_field='".$arr['id']."'");

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
					}

				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id, $idu, $fields);
	echo bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "modifycontact");
}

function addDbContact($id, $fields)
{
	global $babBody;

	class temp
		{
		var $babCss;
		var $babMeta;

		function temp($id, $fields)
			{
			global $babBody;
			$this->file = ad_translate("File");
			$this->update = ad_translate("Update");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babMeta = bab_printTemplate($this,"config.html", "babMeta");
			$this->id = $id;
			$this->idu = "";
			$this->fields = $fields;
			$this->what = "dbac";
			$this->modify = true;

			if( !empty($babBody->msgerror))
				{
				$this->msgerror = $babBody->msgerror;
				$this->error = true;
				}

			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".ADDON_FIELDS_TBL." where name !='jpegphoto'");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;

			$this->name = "";
			$this->urlimg = $GLOBALS['babAddonUrl']."main&idx=getimg&id=".$id."&idu=";
			$this->name = ad_translate("Add new contact");
			}
		
		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldn = ad_translate($arr['description']);
				$this->fieldv = $arr['name'];
				if( isset($this->fields[$arr['name']]) )
					$this->fvalue = $this->fields[$arr['name']];
				else
					$this->fvalue = "";
				$res = $this->db->db_query("select multilignes, required, modifiable, default_value from ".ADDON_DIRECTORIES_FIELDS_TBL." where id_directory='".$this->id."' and id_field='".$arr['id']."'");

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
	echo bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "modifycontact");
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
			$this->import = ad_translate("Import");
			$this->name = ad_translate("File");
			$this->separator = ad_translate("Separator");
			$this->other = ad_translate("Other");
			$this->comma = ad_translate("Comma");
			$this->tab = ad_translate("Tab");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "dbfile"));
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
			$this->process = ad_translate("Import");
			$this->handling = ad_translate("Handling duplicates");
			$this->duphand0 = ad_translate("Allow duplicates to be created");
			$this->duphand1 = ad_translate("Replace duplicates with items imported");
			$this->duphand2 = ad_translate("Do not import duplicates");
			$this->id = $id;
			$this->pfile = $pfile;
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".ADDON_FIELDS_TBL);
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;
			$fd = fopen($pfile, "r");
			if( $fd )
				{
				$line = trim(fgets($fd, 4096));
				fclose($fd);
				}
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
			$this->arr = explode( $separ, $line);
			$this->separ = $separ;
			}

		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->ofieldname = $arr['description'];//ad_translate($arr['description']);
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
				$this->ffieldname = trimQuotes($this->arr[$i]);
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
		$babBody->msgerror = ad_translate("Cannot create temporary file");
		return;
		}
	$temp = new temp($id, $nf, $wsepar, $separ);
	$babBody->babecho(	bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "dbmapfile"));
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
			$this->message = ad_translate("Are you sure you want to empty this directory");
			$this->title = getDirectoryName($id);
			$this->warning = ad_translate("WARNING: This operation will delete all entries"). "!";
			$this->urlyes = $GLOBALS['babAddonUrl']."main&idx=list&id=".$id."&action=Yes";
			$this->yes = ad_translate("Yes");
			$this->urlno = $GLOBALS['babAddonUrl']."main&idx=list";
			$this->no = ad_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function contactDbUnload($msg)
	{
	class temp
		{
		var $babCss;
		var $babMeta;
		var $message;
		var $close;

		function temp($msg)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babMeta = bab_printTemplate($this,"config.html", "babMeta");
			$this->message = $msg;
			$this->close = ad_translate("Close");
			}
		}

	$temp = new temp($msg);
	echo bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "dbcontactunload");
	}

function processImportDbFile( $pfile, $id, $separ )
	{
	$fd = fopen($pfile, "r");
	if( $fd )
		{
		$db = $GLOBALS['babDB'];
		$res = $db->db_query("select name from ".ADDON_FIELDS_TBL);
		$line = fgets($fd, 4096);
		
		while (!feof ($fd))
			{
			$line = trim(fgets($fd, 4096));
			if( !empty($line))
				{
				$arr = explode( $separ, $line);
				switch($GLOBALS['duphand'])
					{
					case 1: // Replace duplicates with items imported
					case 2: // Do not import duplicates
						$res2 = $db->db_query("select id from ".ADDON_DBENTRIES_TBL." where email='".trimQuotes($arr[$GLOBALS['email']])."' and id_directory='".$id."'");
						if( $res2 && $db->db_num_rows($res2 ) > 0 )
							{
							if( $GLOBALS['duphand'] == 2 )
								break;
							while( $arr2 = $db->db_fetch_array($res2))
								{
								$req = "";
								while( $row = $db->db_fetch_array($res))
									{
									if( !empty($GLOBALS[$row['name']]))
										{
										$req .= $row['name']."='".addslashes(trimQuotes($arr[$GLOBALS[$row['name']]]))."',";
										}
									}
								if( !empty($req))
									{
									$req = substr($req, 0, strlen($req) -1);
									$req = "update ".ADDON_DBENTRIES_TBL." set " . $req;
									$req .= " where id='".$arr2['id']."'";
									$db->db_query($req);
									}
								$db->db_data_seek($res,0);
								}
							
							break;
							}
						/* no break; */
					case 0: // Allow duplicates to be created
						$req = "";
						$arrv = array();
						while( $row = $db->db_fetch_array($res))
							{
							if( !empty($GLOBALS[$row['name']]))
								{
								$req .= $row['name'].",";
								array_push( $arrv, trimQuotes($arr[$GLOBALS[$row['name']]]));
								}
							}
						$db->db_data_seek($res,0);
						if( !empty($req))
							{
							$req = "insert into ".ADDON_DBENTRIES_TBL." (".$req."id_directory) values (";
							for( $i = 0; $i < count($arrv); $i++)
								$req .= "'". addslashes($arrv[$i])."',";
							$req .= "'".$id."')";
							$db->db_query($req);
							}
						break;

					}
				}
			}
		fclose($fd);
		unlink($pfile);
		}		
	}

function getDbContactImage($id, $idu)
	{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select photo_data, photo_type from ".ADDON_DBENTRIES_TBL." where id_directory='".$id."' and id='".$idu."'");
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
	$res = $db->db_query("select * , DECODE(password, \"".$GLOBALS['BAB_HASH_VAR']."\") as adpass from ".ADDON_DIRECTORIES_TBL." where id='".$id."'");

	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['ldap'] == "Y")
			{
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
	$res = $db->db_query("select * from ".ADDON_FIELDS_TBL." where name !='jpegphoto'");
	$req = "";
	while( $arr = $db->db_fetch_array($res))
		{
		if( isset($fields[$arr['name']]))
			{
			$rr = $db->db_fetch_array($db->db_query("select required from ".ADDON_DIRECTORIES_FIELDS_TBL." where id_directory='".$id."' and id_field='".$arr['id']."'"));
			if( $rr['required'] == "Y" && empty($fields[$arr['name']]))
				{
				$babBody->msgerror = ad_translate("You must complete required fields");
				return false;
				}
			$req .= $arr['name']."='".addslashes($fields[$arr['name']])."',";
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
		$req .= " photo_data='".$cphoto."'";
	else
		$req = substr($req, 0, strlen($req) -1);

	if( !empty($req))
		{
		$req = "update ".ADDON_DBENTRIES_TBL." set " . $req;
		$req .= " where id='".$idu."'";
		$db->db_query($req);
		}
	return true;
	}

function confirmAddDbContact($id, $idu, $fields, $file, $tmp_file)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];

	if( empty($fields['email']))
		{
		$babBody->msgerror = ad_translate("Contact must have email address");
		return false;
		}
	
	$res = $db->db_query("select email from ".ADDON_DBENTRIES_TBL." where email='".addslashes($fields['email'])."' and id_directory='".$id."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = ad_translate("Contact with this email already exists");
		return false;
		}
	
	$res = $db->db_query("select * from ".ADDON_FIELDS_TBL." where name !='jpegphoto'");
	$req = "";
	while( $arr = $db->db_fetch_array($res))
		{
		$rr = $db->db_fetch_array($db->db_query("select required from ".ADDON_DIRECTORIES_FIELDS_TBL." where id_directory='".$id."' and id_field='".$arr['id']."'"));
		if( $rr['required'] == "Y" && empty($fields[$arr['name']]))
			{
			$babBody->msgerror = ad_translate("You must complete required fields");
			return false;
			}
		$req .= $arr['name'].",";
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
		$req .= "photo_data,";

	if( !empty($req))
		{
		$req = "insert into ".ADDON_DBENTRIES_TBL." (".$req."id_directory) values (";
		$db->db_data_seek($res, 0);
		while( $arr = $db->db_fetch_array($res))
			{
			$req .= "'".addslashes($fields[$arr['name']])."',";
			}
		if( !empty($cphoto))
			$req .= "'".$cphoto."',";

		$req .= "'".$id."')";
		$db->db_query($req);
		}
	return true;
	}


function confirmEmptyDb($id)
	{
	$db = $GLOBALS['babDB'];
	$db->db_query("delete from ".ADDON_DBENTRIES_TBL." where id_directory='".$id."'");
	}

function deleteDbContact($id, $idu)
	{
	$db = $GLOBALS['babDB'];
	$db->db_query("delete from ".ADDON_DBENTRIES_TBL." where id_directory='".$id."' and id='".$idu."'");
	}

/* main */
if(isset($id) && bab_isAccessValid(ADDON_DIRADD_GROUPS_TBL, $id))
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
				$msg = ad_translate("Your contact has been updated");
				$idx = "dbcunload";
				$fields = array();
				}
			}
		else if( $modify == "dbac" )
			{
			if(!confirmAddDbContact($id, $idu, $fields, $photof_name,$photof))
				$idx = "adbc";
			else
				{
				$msg = ad_translate("Your contact has been added");
				$idx = "dbcunload";
				$fields = array();
				}
			}
	}
switch($idx)
	{
	case "deldbc":
		$msg = ad_translate("Your contact has been deleted");
		deleteDbContact($id, $idu);
		/* no break */
	case "dbcunload":
		contactDbUnload($msg);
		exit();
		break;

	case "dbmod":
		modifyDbContact($id, $idu, $fields);
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
		$babBody->title = "Add entry to".": ".getDirectoryName($id);
		if($badd)
			{
			addDbContact($id, $fields);
			exit;
			}
		else
			$babBody->msgerror = "Access denied";
		$babBody->addItemMenu("list", "Directories", $GLOBALS['babAddonUrl']."main&idx=list");
		$babBody->addItemMenu("sdb", "Browse", $GLOBALS['babAddonUrl']."main&idx=sdb&id=".$id);
		$babBody->addItemMenu("dbimp", "Import", $GLOBALS['babAddonUrl']."main&idx=dbimp&id=".$id);
		break;

	case "sdb":
		$babBody->title = "Database Directory".": ".getDirectoryName($id);
		browseDbDirectory($id, $pos, $badd);
		$babBody->addItemMenu("list", "Directories", $GLOBALS['babAddonUrl']."main&idx=list");
		$babBody->addItemMenu("sdb", "Browse", $GLOBALS['babAddonUrl']."main&idx=sdb&id=".$id."&pos=".$pos);
		if($badd)
			{
			$babBody->addItemMenu("dbimp", "Import", $GLOBALS['babAddonUrl']."main&idx=dbimp&id=".$id);
			}
		break;

	case "dbimp":
		$babBody->title = "Import file to".": ".getDirectoryName($id);
		$babBody->addItemMenu("list", "Directories", $GLOBALS['babAddonUrl']."main&idx=list");
		$babBody->addItemMenu("sdb", "Browse", $GLOBALS['babAddonUrl']."main&idx=sdb&id=".$id."&pos=".$pos);
		if($badd)
			{
			importDbFile($id);
			$babBody->addItemMenu("dbimp", "Import", $GLOBALS['babAddonUrl']."main&idx=dbimp&id=".$id);
			}
		break;

	case "dbmap":
		$babBody->title = "Import file to".": ".getDirectoryName($id);
		$babBody->addItemMenu("list", "Directories", $GLOBALS['babAddonUrl']."main&idx=list");
		$babBody->addItemMenu("sdb", "Browse", $GLOBALS['babAddonUrl']."main&idx=sdb&id=".$id."&pos=".$pos);
		if($badd)
			{
			mapDbFile($id, $uploadf_name, $uploadf, $wsepar, $separ);
			$babBody->addItemMenu("dbimp", "Import", $GLOBALS['babAddonUrl']."main&idx=dbimp&id=".$id);
			}
		break;

	case "dldap":
		$babBody->title = "Summary of information about".": ".$cn;
		summaryLdapContact($id, $cn);
		exit;
		break;

	case "sldap":
		$babBody->title = "Ldap Directory".": ".getDirectoryName($id);
		browseLdapDirectory($id, $pos);
		$babBody->addItemMenu("list", "Directories", $GLOBALS['babAddonUrl']."main&idx=list");
		$babBody->addItemMenu("sldap", "Browse", $GLOBALS['babAddonUrl']."main&idx=sldap&id=".$id."&pos=".$pos);
		break;

	case "empdb":
		$babBody->title = "Delete Database Directory";
		if( $badd )
			emptyDb($id);
		else
			$babBody->msgerror = ad_translate("Access denied");

		$babBody->addItemMenu("list", "Directories", $GLOBALS['babAddonUrl']."main&idx=list");
		break;

	case "list":
	default:
		$babBody->title = "";
		listUserAds();
		$babBody->addItemMenu("list", "Directories", $GLOBALS['babAddonUrl']."main&idx=list");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>