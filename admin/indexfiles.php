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
include_once "base.php";




/**
 * List index files for administrators
 */
function listIndexFiles()
	{
	global $babBody;

	class bab_listIndexFilesCls {

		var $db;
		var $altbg = true;

		function bab_listIndexFilesCls() {
			$this->t_title		= bab_translate("Name");
			$this->t_all		= bab_translate("Index all files");
			$this->t_waiting	= bab_translate("Index waiting files");
			$this->t_onload		= bab_translate("Index on load");
			$this->t_disabled	= bab_translate("Disabled");
			$this->t_update		= bab_translate("Update");
			$this->t_allowed_ip = bab_translate("Allowed IP adress");

			$this->db = &$GLOBALS['babDB'];

			$reg = bab_getRegistryInstance();
			$reg->changeDirectory('/bab/indexfiles/');

			if (isset($_POST['action']) && 'index' == $_POST['action']) {
				$this->db->db_query("UPDATE ".BAB_INDEX_FILES_TBL." SET index_onload='0', index_disabled='0'");

				if (isset($_POST['onload'])) {
					foreach($_POST['onload'] as $id) {
						$this->db->db_query("UPDATE ".BAB_INDEX_FILES_TBL." SET index_onload='1' WHERE id='".$id."'");
					}
				}

				if (isset($_POST['disabled'])) {
					foreach($_POST['disabled'] as $id) {
						$this->db->db_query("UPDATE ".BAB_INDEX_FILES_TBL." SET index_disabled='1' WHERE id='".$id."'");
					}
				}

				$reg->setKeyValue('allowed_ip', $_POST['allowed_ip']);
			}

			$this->res = $this->db->db_query("SELECT * FROM ".BAB_INDEX_FILES_TBL."");
			
		
			$this->allowed_ip = $reg->getValue('allowed_ip');
			if (null == $this->allowed_ip) {
				$this->allowed_ip = '127.0.0.1';
				$reg->setKeyValue('allowed_ip', $this->allowed_ip);
			}
			
		}


		function getnext() {
			
			if ($arr = $this->db->db_fetch_assoc($this->res)) {
				$this->altbg		= !$this->altbg;
				$this->id_index		= $arr['id'];
				$this->title		= bab_toHtml($arr['name']);
				$this->onload		= 1 == $arr['index_onload'];
				$this->disabled		= 1 == $arr['index_disabled'];

				return true;
			}
			return false;
		}

	}	

	$temp = new bab_listIndexFilesCls();
	$babBody->babecho(	bab_printTemplate($temp, "indexfiles.html", "list"));
}




if( !isset($BAB_SESS_LOGGED) || empty($BAB_SESS_LOGGED) ||  !$babBody->isSuperAdmin)
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

bab_cleanGpc();

$babBody->title = bab_translate("Search indexes");

listIndexFiles();



?>