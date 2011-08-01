<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $babInstallPath.'utilit/sitesincl.php';


function sitesList()
{
	global $babBody;

	class SitesListTpl
	{
		var $name;
		var $urlname;
		var $url;
		var $description;
		var $lang;
		var $email;
		var $homepages;
		var $hprivate;
		var $hpublic;
		var $hprivurl;
		var $hpuburl;

		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function __construct()
		{
			$this->name = bab_translate("Site name");
			$this->description = bab_translate("Description");
			$this->lang = bab_translate("Lang");
			$this->email = bab_translate("Email");
			$this->homepages = bab_translate("Home pages");
			$this->hmanagement = bab_translate("Managers");
			$this->db = &$GLOBALS['babDB'];
			$this->res = bab_getSitesRes();
			$this->count = $this->db->db_num_rows($this->res);
		}

		function getnext()
		{
			static $i = 0;
			if( $i < $this->count)
			{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=site&idx=menusite&item=".$this->arr['id'];
				$this->hmanagementurl = $GLOBALS['babUrlScript']."?tg=site&idx=menu7&item=".$this->arr['id'];
				$this->urlname = $this->arr['name'];
				$i++;
				return true;
			}
			return false;
		}
	}

	$temp = new SitesListTpl();
	$babBody->babecho(	bab_printTemplate($temp, 'sites.html', 'siteslist'));
	return $temp->count;
}




	
function database()
{
	global $babBody;
	class DatabaseTpl
	{

		function __construct()
		{
			$this->db = &$GLOBALS['babDB'];
			$this->t_submit = bab_translate("Export database");
			
			$this->t_structure = bab_translate("Export table structure");
			$this->t_data = bab_translate("Export table data");
			$this->t_drop_table = bab_translate("Add 'DROP TABLE' instructions");
			$this->t_tables = bab_translate("Tables");
			
			if (isset($_POST) && count($_POST) > 0) {
				$this->val = $_POST;
			} else {
				$el_to_init = array('structure', 'data', 'drop_table');
				foreach($el_to_init as $el) {
					$this->val[$el] = true;
				}
			}

			$this->restable = $this->db->db_query('SHOW TABLES');
		}


		function getnexttable()
		{
			if (list($this->table) = $this->db->db_fetch_array($this->restable))
			{
				return true;
			}
			return false;
		}
	}

	$temp = new DatabaseTpl();
	$babBody->babecho(bab_printTemplate($temp, 'sites.html', 'database'));
}



	


/* main */
if( !isset($BAB_SESS_LOGGED) || empty($BAB_SESS_LOGGED) ||  !$babBody->isSuperAdmin)
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx','list');

	
if (isset($_FILES['zipfile'])) {
	if (unzipcore()) {
		$idx = "zipupgrade_message";
	}
}

if (isset($_POST['action']))
	switch($_POST['action'])
		{
		case 'export_database':

			include_once $GLOBALS['babInstallPath']."utilit/sqlincl.php";

			if (count($_POST['tables']) > 0)
				{
				$structure = !empty($_POST['structure']) ? 1 : 0;
				$drop_table = !empty($_POST['drop_table']) ? 1 : 0;
				$data = !empty($_POST['data']) ? 1 : 0;

				$bab_sqlExport = new bab_sqlExport($_POST['tables'], $structure, $drop_table, $data);
				$bab_sqlExport->exportFile();
				}
			break;
		}


if (!isset($message)) {
	$message = '';
}


switch($idx)
{
	case 'phpinfo':
		phpinfo();
		exit;
		break;

	case 'database':
		$babBody->addItemMenu('list', bab_translate("Sites"),$GLOBALS['babUrlScript'].'?tg=sites&idx=list');
		$babBody->addItemMenu('database', bab_translate("Database"),$GLOBALS['babUrlScript'].'?tg=sites&idx=database');
		$babBody->title = bab_translate("Database management");
		database();
		break;

	case 'list':
	default:
		$babBody->title = bab_translate("Sites list");
		if (sitesList() > 0) {
			$babBody->addItemMenu('list', bab_translate("Sites"), $GLOBALS['babUrlScript'] . '?tg=sites&idx=list');
		} else {
			$babBody->title = bab_translate("There is no site");
		}

		$babBody->addItemMenu("create", bab_translate("Create"),$GLOBALS['babUrlScript'].'?tg=site&idx=create');
		$babBody->addItemMenu("database", bab_translate("Database"),$GLOBALS['babUrlScript'].'?tg=sites&idx=database');
		break;
}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','AdminSites');
