<?php
include $babAddonPhpPath."wrincl.php";
include $babInstallPath."admin/acl.php";

$wr_fields_export = array(
	'id' => array("user","service","office","room","tel","date_request","wtype", "worker", "date_start", "date_end", "status", "wtype2"),
	'name' => array("Demandeur","Service","Bureau","Pièce", "Téléphone du demandeur","Date de demande","Type de travaux", "Responsable", "Date de début", "Date de fin", "Statut", "Catégorie de travaux")
	);

function listWorks()
{
	global $babBody;

	class temp
		{
		var $nametxt;
		var $managertxt;
		var $desctxt;
		var $res;
		var $count;
		var $name;
		var $desc;
		var $manager;
		var $url;

		function temp()
			{
			global $babDB;
			$this->nametxt = wr_translate("Libellé");
			$this->managertxt = wr_translate("Manager");
			$this->desctxt = wr_translate("Description");
			$this->res = $babDB->db_query("select * from ".ADDON_WR_WORKSLIST_TBL."");
			$this->count = $babDB->db_num_rows($this->res );
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->name = $arr['name'];
				$this->desc = $arr['description'];
				$this->manager = bab_getUserName($arr['manager']);
				$this->url = $GLOBALS['babAddonUrl']."admin&idx=mod&id=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."admin.html", "workslist"));
}

function listWorkTypes()
{
	global $babBody;

	class temp
		{
		var $nametxt;
		var $add;
		var $res;
		var $count;
		var $name;
		var $wtid;
		var $url;

		function temp()
			{
			global $babDB;
			$this->nametxt = wr_translate("Catégorie de travaux");
			$this->add = wr_translate("Ajouter");
			$this->res = $babDB->db_query("select * from ".ADDON_WR_WORKSTYPES_TBL."");
			$this->count = $babDB->db_num_rows($this->res );
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->name = $arr['name'];
				$this->wtid = $arr['id'];
				$this->url = $GLOBALS['babAddonUrl']."admin&idx=modt&id=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."admin.html", "worktypeslist"));
}

function modifyWorkType($id, $wtname)
{

	global $babBody;

	class temp
		{
		var $bdel;
		var $what;
		var $name;
		var $add;
		var $delete;
		var $wtname;
		var $id;

		function temp($id, $wtname)
			{
			global $babDB;
			$this->bdel = true;
			$this->what = "modwt";
			$this->name = wr_translate("Catégorie de travaux");
			$this->add = wr_translate("Modifier");
			$this->delete = wr_translate("Supprimer");
			$this->wtname = $wtname == ""? "": $wtname;
			$this->id = $id == ""? "": $id;
			$res = $babDB->db_query("select * from ".ADDON_WR_WORKSTYPES_TBL." where id='".$id."'");
			$arr = $babDB->db_fetch_array($res);
			if( !empty($wtname))
				{
				$this->wtname = $wtname;
				}
			else
				{
				$this->wtname = $arr['name'];
				}
			}
		}

	$temp = new temp($id, $wtname);
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."admin.html", "addworktype"));
}

function addWorkType($wtname)
{

	global $babBody;

	class temp
		{
		var $bdel;
		var $what;
		var $name;
		var $add;
		var $wtname;
		var $id;

		function temp($wtname)
			{
			global $babDB;
			$this->bdel = false;
			$this->what = "addwt";
			$this->name = wr_translate("Catégorie de travaux");
			$this->add = wr_translate("Ajouter");
			$this->wtname = $wtname == ""? "": $wtname;
			$this->id = "";
			if( !empty($wtname))
				{
				$this->wtname = $wtname;
				}
			else
				{
				$this->wtname = "";
				}
			}
		}

	$temp = new temp($wtname);
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."admin.html", "addworktype"));
}

function addWork($wname, $wdesc, $managerid)
{

	global $babBody;

	class temp
		{
		var $bdel;
		var $what;
		var $name;
		var $add;
		var $manager;
		var $description;
		var $usersbrowurl;
		var $wname;
		var $wdesc;
		var $id ;
		var $managerid;
		var $managerval;
		var $worktype;
		var $wtid;
		var $wtlabel;
		
		function temp($wname, $wdesc, $managerid)
			{
			global $babDB;
			$this->bdel = false;
			$this->what = "addw";
			$this->name = wr_translate("Libellé");
			$this->add = wr_translate("Ajouter");
			$this->manager = wr_translate("Manager");
			$this->description = wr_translate("Description");
			$this->worktype = wr_translate("Catégorie de travaux");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->wname = $wname == ""? "": $wname;
			$this->wdesc = $wdesc == ""? "": $wdesc;
			$this->id = "";
			$this->selected = "";
			if( !empty($managerid))
				{
				$this->managerid = $managerid;
				$this->managerval = bab_getUserName($managerid);
				}
			else
				{
				$this->managerid = "";
				$this->managerval = "";
				}

			$this->res = $babDB->db_query("select * from ".ADDON_WR_WORKSTYPES_TBL."");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->wtlabel = $arr['name'];
				$this->wtid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($wname, $wdesc, $managerid);
	$babBody->babecho( bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."admin.html", "addwork"));
}


function modifyWork($id, $wname, $wdesc, $managerid)
{

	global $babBody;

	class temp
		{
		var $bdel;
		var $what;
		var $name;
		var $manager;
		var $description;
		var $usersbrowurl;
		var $wname;
		var $wdesc;
		var $id;
		var $managerid;
		var $managerval;

		function temp($id, $wname, $wdesc, $managerid)
			{
			global $babDB;
			$this->bdel = true;
			$this->what = "mod";
			$this->add = wr_translate("Modifier");
			$this->delete = wr_translate("Supprimer");
			$this->name = wr_translate("Libellé");
			$this->manager = wr_translate("Manager");
			$this->description = wr_translate("Description");
			$this->worktype = wr_translate("Catégorie de travaux");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->wname = $wname == ""? "": $wname;
			$this->wdesc = $wdesc == ""? "": $wdesc;
			$this->id = $id == ""? "": $id;
			$res = $babDB->db_query("select * from ".ADDON_WR_WORKSLIST_TBL." where id='".$id."'");
			$arr = $babDB->db_fetch_array($res);
			$this->wtype = $arr['wtype'];
			if( !empty($managerid))
				{
				$this->managerid = $managerid;
				$this->managerval = bab_getUserName($managerid);
				}
			else
				{
				$this->managerid = $arr['manager'];
				$this->managerval = bab_getUserName($arr['manager']);
				}
			if( !empty($wname))
				{
				$this->wname = $wname;
				}
			else
				{
				$this->wname = $arr['name'];
				}

			if( !empty($wdesc))
				{
				$this->wdesc = $wdesc;
				}
			else
				{
				$this->wdesc = $arr['description'];
				}
			$this->res = $babDB->db_query("select * from ".ADDON_WR_WORKSTYPES_TBL."");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->wtlabel = $arr['name'];
				$this->wtid = $arr['id'];
				if( $this->wtype == $this->wtid )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id, $wname, $wdesc, $managerid);
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."admin.html", "addwork"));
}

function workAdd($wname, $wdesc, $managerid, $wtype)
{
	global $babBody, $babDB;

	if( empty($wname))
		{
		$babBody->msgerror = bab_translate("Erreur: Vous devez donnez un nom");
		return false;
		}

	if( empty($managerid))
		{
		$babBody->msgerror = bab_translate("Erreur: Vous devez désigner un gestionnaire");
		return false;
		}

	$res = $babDB->db_query("select * from ".ADDON_WR_WORKSLIST_TBL." where name='".$wname."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = wr_translate("Ce travail existe déjà");
		return false;
		}
	else
		{
		if( !bab_isMagicQuotesGpcOn())
			{
			$wdesc = addslashes($wdesc);
			$wname = addslashes($wname);
			}
		if( empty($managerid))
			$managerid = 0;
		$req = "insert into ".ADDON_WR_WORKSLIST_TBL." (name, description, manager, wtype) VALUES ('" .$wname. "', '" . $wdesc. "', '" . $managerid. "', '" . $wtype. "')";
		$babDB->db_query($req);
		return true;
		}

}

function workTypeAdd($wname)
{
	global $babBody, $babDB;

	if( empty($wname))
		{
		$babBody->msgerror = bab_translate("Erreur: Vous devez donnez un non à un type de travaux");
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$wname = addslashes($wname);
		}

	$res = $babDB->db_query("select * from ".ADDON_WR_WORKSTYPES_TBL." where name='".$wname."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = wr_translate("Ce type de travaux existe déjà!");
		return false;
		}
	else
		{
		$req = "insert into ".ADDON_WR_WORKSTYPES_TBL." set name='".$wname."'";
		$babDB->db_query($req);
		return true;
		}

}

function workTypeModify($id, $wname)
{
	global $babBody, $babDB;

	if( empty($wname))
		{
		$babBody->msgerror = bab_translate("Erreur: Vous devez donnez un non à un type de travaux");
		return false;
		}

	$res = $babDB->db_query("select * from ".ADDON_WR_WORKSTYPES_TBL." where id='".$id."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['name'] == $wname )
			{
			$babBody->msgerror = wr_translate("Ce type de travaux existe déjà!");
			return false;
			}
		if( !bab_isMagicQuotesGpcOn())
			{
			$wname = addslashes($wname);
			}
		$babDB->db_query("update ".ADDON_WR_WORKSTYPES_TBL." set name='".$wname."' where id='".$id."'");
		}
	return true;
}

function exportTasks()
	{

	global $babBody;
	class temp0
		{
		var $create;
	
		var $moveup;
		var $movedown;

		var $id;
		var $arr = array();
		var $db;
		var $res;

		var $fieldlisttxt;
		var $listexptxt;
		var $title;

		function temp0()
			{
			global $wr_fields_export, $wr_array_status;
			$this->fieldlisttxt = bab_translate("---- Liste des champs disponibles ----");
			$this->listexptxt = bab_translate("---- Liste des champs à exporter ----");
			$this->moveup = bab_translate("Vers le haut");
			$this->movedown = bab_translate("Vers le bas");
			$this->all = wr_translate("Tous");
			$this->create = bab_translate("Exporter");
			$this->count = count($wr_fields_export['id']);
			$this->countstatus = count($wr_array_status);
			}

		function getnextfieldlist()
			{
			global $wr_fields_export;
			static $i = 0;
			if( $i < $this->count )
				{
				$this->fieldid = $wr_fields_export['id'][$i];
				$this->fieldval = wr_translate($wr_fields_export['name'][$i]);
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextstatus()
			{
			global $wr_array_status;
			static $i = 0;
			if( $i < $this->countstatus)
				{
				$this->statusid = $i;
				$this->vstatus = $wr_array_status[$i];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp0 = new temp0();
	$babBody->babecho(	bab_printTemplate($temp0, $GLOBALS['babAddonHtmlPath']."admin.html", "exporttasks"));
	}

function deleteWork($id)
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
			$this->message = wr_translate("Vous êtes sûr de vouloir supprimer ce travail");
			$this->title = wr_getWorkName($id);
			$this->warning = wr_translate("ATTENTION: Ceci supprimera toute référence à cet définition de travail"). "!";
			$this->urlyes = $GLOBALS['babAddonUrl']."admin&idx=del&id=".$id."&action=Yes";
			$this->yes = wr_translate("Oui");
			$this->urlno = $GLOBALS['babAddonUrl']."group&idx=list";
			$this->no = wr_translate("Non");
			}
		}

	$temp = new temp($id);
	$babBody->babecho( bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function confirmDeleteWork($id)
{
	global $babDB;
	//@@ WARN
	$babDB->db_query("delete from ".ADDON_WR_WORKSLIST_TBL." where id='".$id."'");
	$babDB->db_query("update ".ADDON_WR_TASKSLIST_TBL." set wtype='0' where wtype='".$id."'");
}

function workModify( $id, $wname, $wdesc, $managerid, $wtype )
{
	global $babBody, $babDB;

	if( empty($wname))
		{
		$babBody->msgerror = bab_translate("Erreur: Vous devez donnez un nom");
		return false;
		}

	if( empty($managerid))
		{
		$babBody->msgerror = bab_translate("Erreur: Vous devez désigner un gestionnaire");
		return false;
		}

	$babDB->db_query("update ".ADDON_WR_WORKSLIST_TBL." set name='".$wname."', description='".$wdesc."', manager='".$managerid."', wtype='".$wtype."' where id='".$id."'");

	return true;
}


function deleteWorkTypes( $wtlist )
{
	global $babDB;

	for( $i = 0; $i < count($wtlist); $i++ )
	{
		$babDB->db_query("delete from ".ADDON_WR_WORKSTYPES_TBL." where id='".$wtlist[$i]."'");
		$babDB->db_query("update ".ADDON_WR_WORKSLIST_TBL." set wtype='0' where wtype='".$wtlist[$i]."'");
	}
}

function tasksExport($expfields, $filter)
{
	global $babDB, $wr_fields_export, $wr_array_status;
	$cnt = count($expfields);

	if( $cnt == 0 )
		return;

	$req = "select * from ".ADDON_WR_TASKSLIST_TBL."";
	if( $filter != -1)
		$req .= " where status='".$filter."'";

	$req .= " order by date_request desc";

	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res))
	{
		$wr_cnt = count($wr_fields_export['id']);
		$output = "";
		for( $i=0; $i < $cnt; $i++)
		{
			for( $j = 0; $j < $wr_cnt; $j++ )
			{
				if( $wr_fields_export['id'][$j] == $expfields[$i] )
				{
					$output .= wr_translate($wr_fields_export['name'][$j]);
					if( $i != $cnt - 1 )
						$output .= ",";
				}
			}
		}

		$output .= "\n";
		while( $row = $babDB->db_fetch_array($res))
		{
			for( $i=0; $i < $cnt; $i++)
			{
				switch($expfields[$i])
				{
					case 'user':
						$output .= bab_getUserName($row[$expfields[$i]]);
						break;
					case 'worker':
						$output .= bab_getUserName($row[$expfields[$i]]);
						break;
					case 'wtype':
						$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".ADDON_WR_WORKSLIST_TBL." where id='".$row['wtype']."'"));
						$output .= $arr['description'];
						break;
					case 'wtype2':
						$arr = $babDB->db_fetch_array($babDB->db_query("select wtype from ".ADDON_WR_WORKSLIST_TBL." where id='".$row['wtype']."'"));
						$arr = $babDB->db_fetch_array($babDB->db_query("select name from ".ADDON_WR_WORKSTYPES_TBL." where id='".$row['wtype']."'"));
						$output .= $arr['name'];
						break;
					case 'status':
						$output .= wr_translate($wr_array_status[$row[$expfields[$i]]]);
						break;
					case 'date_request':
					case 'date_start':
					case 'date_end':
						if( $row[$expfields[$i]] != '0000-00-00' )
						{
						$rr = explode('-', $row[$expfields[$i]]);
						$output .= $rr[2]."/".$rr[1]."/".$rr[0];
						}
						break;
					default:
						$output .= $row[$expfields[$i]];
						break;
				}
				if( $i != $cnt - 1 )
					$output .= ",";
			}
			$output .= "\n";
		}
		header("Content-Disposition: attachment; filename=\"ovidentia.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
	}
}

/* main */
$adminid = bab_isUserAdministrator();
if( $adminid <= 0 )
{
	$babBody->msgerror = wr_translate("Accès interdit");
	return;
}

if( !isset($idx ))
	$idx = "list";
else if( $idx == "addw")
	{
		if( workAdd($wname, $wdesc, $managerid, $wtype))
			$idx = "list";
	}
else if( $idx == "mod" )
	{
		if( isset($addw))
		{
			if( workModify( $id, $wname, $wdesc, $managerid, $wtype ))
				$idx = "list";
		}
		else if( isset($delw ))
		{
			deleteWork($id);
			$idx = "list";
		}
	}
else if( $idx == "del" && $action== "Yes")
	{
		confirmDeleteWork($id);
		$idx = "list";
	}
else if( $idx == "delwt")
	{
	deleteWorkTypes($wtlist);
	$idx = "typel";
	}
else if( $idx == "addwt" )
	{
		if( workTypeAdd($wname ))
			$idx = "typel";
	}
else if( $idx == "modwt" )
	{
		if( workTypeModify($id, $wname ))
			$idx = "typel";
	}
else if( $idx == "export" )
	{
		tasksExport($expfields, $filter);
	}

if( isset($aclview))
	{
	if( $what == "0" || $what == "2" )
		{
		$id = $item;
		$babBody->msgerror = wr_translate("Les utilisateurs doivent être enregistrés !");
		}
	else
		{
		aclUpdate($table, $item, $groups, $what);
		$id = $item;
		$idx = "mod";
		}
	}


switch($idx)
	{
	case "modt":
		$babBody->title = wr_translate("Modifier une catégorie de travaux");
		modifyWorkType($id, $wname);
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		$babBody->addItemMenu("modt", wr_translate("Modifier"), $GLOBALS['babAddonUrl']."admin&idx=modt");
		$babBody->addItemMenu("addt", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."admin&idx=addt");
		break;

	case "addt":
		$babBody->title = wr_translate("Ajouter une catégorie de travaux");
		addWorkType($wtname);
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		$babBody->addItemMenu("addt", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."admin&idx=addt");
		break;

	case "typel":
		$babBody->title = wr_translate("Liste des catégories de travaux");
		listWorkTypes();
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		$babBody->addItemMenu("addt", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."admin&idx=addt");
		break;

	case "mod":
		$babBody->title = wr_translate("Modifier un type de travaux");
		modifyWork($id, $wname, $wdesc, $managerid);
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("mod", wr_translate("Modifier"), $GLOBALS['babAddonUrl']."admin&idx=mod&id=".$id);
		$babBody->addItemMenu("users", bab_translate("Utilisateurs"), $GLOBALS['babAddonUrl']."admin&idx=users&id=".$id);
		$babBody->addItemMenu("agents", bab_translate("Agents"), $GLOBALS['babAddonUrl']."admin&idx=agents&id=".$id);
		$babBody->addItemMenu("others", bab_translate("Superviseurs"), $GLOBALS['babAddonUrl']."admin&idx=others&id=".$id);
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		break;

	case "users":
		$babBody->title = wr_translate("Liste des groupes pouvant utiliser ce type de travaux");
		aclGroups($GLOBALS['babAddonTarget']."/admin", "users", ADDON_WR_WORKSUSERS_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("mod", wr_translate("Modifier"), $GLOBALS['babAddonUrl']."admin&idx=mod&id=".$id);
		$babBody->addItemMenu("users", bab_translate("Utilisateurs"), $GLOBALS['babAddonUrl']."admin&idx=users&id=".$id);
		$babBody->addItemMenu("agents", bab_translate("Agents"), $GLOBALS['babAddonUrl']."admin&idx=agents&id=".$id);
		$babBody->addItemMenu("others", bab_translate("Superviseurs"), $GLOBALS['babAddonUrl']."admin&idx=others&id=".$id);
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		break;

	case "agents":
		$babBody->title = wr_translate("Liste des groupes pouvant exécuter ce type de travaux");
		aclGroups($GLOBALS['babAddonTarget']."/admin", "agents", ADDON_WR_WORKSAGENTS_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("mod", wr_translate("Modifier"), $GLOBALS['babAddonUrl']."admin&idx=mod&id=".$id);
		$babBody->addItemMenu("users", bab_translate("Utilisateurs"), $GLOBALS['babAddonUrl']."admin&idx=users&id=".$id);
		$babBody->addItemMenu("agents", bab_translate("Agents"), $GLOBALS['babAddonUrl']."admin&idx=agents&id=".$id);
		$babBody->addItemMenu("others", bab_translate("Superviseurs"), $GLOBALS['babAddonUrl']."admin&idx=others&id=".$id);
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		break;

	case "others":
		$babBody->title = wr_translate("Liste des groupes à notifier");
		aclGroups($GLOBALS['babAddonTarget']."/admin", "others", ADDON_WR_WORKSOTHERS_GROUPS_TBL, $id, "aclview");
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("mod", wr_translate("Modifier"), $GLOBALS['babAddonUrl']."admin&idx=mod&id=".$id);
		$babBody->addItemMenu("users", bab_translate("Utilisateurs"), $GLOBALS['babAddonUrl']."admin&idx=users&id=".$id);
		$babBody->addItemMenu("agents", bab_translate("Agents"), $GLOBALS['babAddonUrl']."admin&idx=agents&id=".$id);
		$babBody->addItemMenu("others", bab_translate("Superviseurs"), $GLOBALS['babAddonUrl']."admin&idx=others&id=".$id);
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		break;

	case "add":
		$babBody->title = wr_translate("Ajouter un type de travaux");
		addWork($wname, $wdesc, $managerid);
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("add", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."admin&idx=add");
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		break;
	case "exp":
		$babBody->title = wr_translate("Exporter vers un fichier");
		exportTasks();
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("add", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."admin&idx=add");
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		$babBody->addItemMenu("exp", wr_translate("Export"), $GLOBALS['babAddonUrl']."admin&idx=exp");
		break;

	case "list":
	default:
		$babBody->title = wr_translate("Liste des types de travaux");
		listWorks();
		$babBody->addItemMenu("list", wr_translate("Type de travaux"), $GLOBALS['babAddonUrl']."admin&idx=list");
		$babBody->addItemMenu("add", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."admin&idx=add");
		$babBody->addItemMenu("typel", wr_translate("Catégories"), $GLOBALS['babAddonUrl']."admin&idx=typel");
		$babBody->addItemMenu("exp", wr_translate("Export"), $GLOBALS['babAddonUrl']."admin&idx=exp");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>