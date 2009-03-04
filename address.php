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
/**
* @internal SEC1 NA 05/12/2006 FULL
*/
include_once 'base.php';

function listAddress($pos)
	{
	global $babBody;
	class temp
		{
		public $fullname;
		public $nameval;
		public $url;
		public $email;
		public $emailval;
		public $checkval;
		public $status;
				
		public $fullnameval;
		public $arr = array();
// 
		private $count;
		private $res;
		private $countgrpm;
		private $resgrpm;
		private $resgrp;

		public $pos;
		public $selected;
		public $allselected;
		public $allurl;
		public $allname;
		public $urlmail;
		public $babCss;

		public $totourl;
		public $toccurl;
		public $tobccurl;
		public $totoname;
		public $toccname;
		public $tobccname;

		public $checkall;
		public $uncheckall;

		public $closeurl;

		public $altbg = true;

		public function __construct($pos)
			{
			global $BAB_SESS_USERID,$babDB, $babBody;
			$this->fullname = bab_translate("Contact")." / ".bab_translate("List");
			$this->email = bab_translate("Email");
			$this->allname = bab_translate("All");
			$this->totoname = bab_translate("To") .' ->';
			$this->toccname = bab_translate("Cc").' ->';
			$this->tobccname = bab_translate("Bcc").' ->';
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->closename = bab_translate("Close");
			$this->totourl = "javascript:updateDestination('to')";
			$this->toccurl = "javascript:updateDestination('cc')";
			$this->tobccurl = "javascript:updateDestination('bcc')";
			$this->closeurl = "javascript:this.close()";
			$this->babCss = bab_printTemplate($this,'config.html', 'babCss');


			switch ($babBody->nameorder[0]) {
			case 'L':
				$this->namesearch = 'lastname';
				$this->namesearch2 = 'firstname';
			break; 
			case 'F':
			default:
				$this->namesearch = 'firstname';
				$this->namesearch2 = 'lastname';
			break;
			}


			// personal contacts

			$req = "select * from ".BAB_CONTACTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and ".$this->namesearch." like '".$babDB->db_escape_like($pos)."%' order by ".$this->namesearch.", ".$this->namesearch2." asc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

			$this->pos = $pos;

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=address&idx=list&pos=";

			// group members
			if( !bab_isUserAdministrator() && $babBody->babsite['browse_users'] == 'N')
			{
				$this->loadGroupsMembers();
			} else {
				$this->loadUsers();
			}
			
			// groups
			$req = "select ".BAB_GROUPS_TBL.".id, ".BAB_GROUPS_TBL.".name from  ".BAB_GROUPS_TBL.", ".BAB_USERS_GROUPS_TBL." as p1 where p1.id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."' and p1.id_group = ".BAB_GROUPS_TBL.".id and ".BAB_GROUPS_TBL.".name like '".$babDB->db_escape_like($pos)."%' order by ".BAB_GROUPS_TBL.".name asc";
			$this->resgrp = $babDB->db_query($req);
			$this->countgrp = $babDB->db_num_rows($this->resgrp);

			}


		/**
		 * load all users
		 */
		private function loadUsers()
			{

			global $babDB;

			$req = "
				SELECT DISTINCT p3.id, p4.mn, p3.".$this->namesearch.", p3.".$this->namesearch2.", p3.email 
				FROM 
					".BAB_USERS_TBL." as p3, 
					".BAB_DBDIR_ENTRIES_TBL." as p4 
				WHERE 
					p3.id=p4.id_user 
					AND ".$this->namesearch." like '".$babDB->db_escape_like($this->pos)."%' 
					AND p3.disabled = '0' 

				ORDER BY ".$this->namesearch.", ".$this->namesearch2." asc
			";

			$this->resgrpm = $babDB->db_query($req);
			$this->countgrpm = $babDB->db_num_rows($this->resgrpm);

			}



		/**
		 * load groups member where the user is member of
		 */
		private function loadGroupsMembers()
			{
			global $babDB;
			global $BAB_SESS_USERID;

			$req = "
				SELECT DISTINCT p3.id, p4.mn, p3.".$this->namesearch.", p3.".$this->namesearch2.", p3.email 
				FROM 
					".BAB_USERS_TBL." as p3, 
					".BAB_DBDIR_ENTRIES_TBL." as p4, 
					".BAB_USERS_GROUPS_TBL." as p1,  
					".BAB_USERS_GROUPS_TBL." as p2 
				WHERE 
					p1.id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."' 
					AND p3.id=p4.id_user 
					AND p1.id_group = p2.id_group 
					AND p3.id=p2.id_object 
					AND ".$this->namesearch." like '".$babDB->db_escape_like($this->pos)."%' 
					AND p3.disabled = '0' 

				ORDER BY ".$this->namesearch.", ".$this->namesearch2." asc
			";

			$this->resgrpm = $babDB->db_query($req);
			$this->countgrpm = $babDB->db_num_rows($this->resgrpm);

			}





		public function getnextcontact()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->altbg = !$this->altbg;
				$this->nameval = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->emailval = $this->arr['email'];
				$this->checkval = $this->nameval;
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}

		public function getnextgroupmember()
			{
			global $babDB;
			static $j = 0;
			if( $j < $this->countgrpm)
				{
				$arr = $babDB->db_fetch_array($this->resgrpm);
				$this->altbg = !$this->altbg;
				$this->nameval = bab_composeUserName($arr['firstname'],$arr['lastname']);
				$this->emailval = $arr['email'];
				$this->checkval = $arr['firstname'].' '.$arr['mn'].' '.$arr['lastname']."(g)";
				$j++;
				return true;
				}
			else
				{
				return false;
				}

			}

		public function getnextgroup()
			{
			global $babDB;
			static $j = 0;
			if( $j < $this->countgrp)
				{
				$arr = $babDB->db_fetch_array($this->resgrp);
				$this->altbg = !$this->altbg;
				$this->nameval = $arr['name'];
				$this->emailval = '';
				$this->checkval = $this->nameval.'(g)';
				$j++;
				return true;
				}
			else
				{
				return false;
				}

			}

		public function getnextselect()
			{
			global $BAB_SESS_USERID, $babDB;
			static $k = 0;
			static $t = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			if( $k < 26)
				{
				$this->selectname = mb_substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript'].'?tg=address&idx=list&pos='.$this->selectname;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					$req = "select * from ".BAB_CONTACTS_TBL." where firstname like '".$babDB->db_escape_like($this->selectname)."%'";
					$res = $babDB->db_query($req);
					if( $babDB->db_num_rows($res) < 1 )
						{
						$req = "select distinct p3.id, p3.".$this->namesearch.", p3.".$this->namesearch2.", p3.email from ".BAB_USERS_TBL." as p3, ".BAB_USERS_GROUPS_TBL." as p1,  ".BAB_USERS_GROUPS_TBL." as p2 where p1.id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."' and p1.id_group = p2.id_group and p3.id=p2.id_object and ".$this->namesearch." like '".$babDB->db_escape_like($this->selectname)."%'";
						$res = $babDB->db_query($req);
						if( $babDB->db_num_rows($res) > 0 )
							{
							$this->selected = 0;
							}
						else
							{
							$req = "select ".BAB_GROUPS_TBL.".id from  ".BAB_GROUPS_TBL.", ".BAB_USERS_GROUPS_TBL." as p1 where p1.id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."' and p1.id_group = ".BAB_GROUPS_TBL.".id and ".BAB_GROUPS_TBL.".name like '".$babDB->db_escape_like($this->selectname)."%'";
							$res = $babDB->db_query($req);
							if( $babDB->db_num_rows($res) > 0 )
								{
								$this->selected = 0;
								}
							else
								{
								$this->selected = 1;
								}
							}
						}
					else
						$this->selected = 0;
					}
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($pos);
	$babBody->babpopup(bab_printTemplate($temp, 'address.html', 'addresslist'));	
	}

/* main */
$idx = bab_rp('idx', 'list');
$pos = bab_rp('pos', 'A');


switch($idx)
	{
	case 'list':
		$babBody->title = bab_translate("Users list");
		listAddress($pos);
		$babBody->addItemMenu('list', bab_translate("Users"),$GLOBALS['babUrlScript'].'?tg=address&idx=list');
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>