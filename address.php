<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function listAddress($pos)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $nameval;
		var $url;
		var $email;
		var $emailval;
		var $checkval;
		var $status;
				
		var $fullnameval;
		var $emailval;

		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $countgrpm;
		var $resgrpm;

		var $pos;
		var $selected;
		var $allselected;
		var $allurl;
		var $allname;
		var $urlmail;
		var $babCss;

		var $totourl;
		var $toccurl;
		var $tobccurl;
		var $totoname;
		var $toccname;
		var $tobccname;

		var $checkall;
		var $uncheckall;

		var $closeurl;
		var $closeurl;

		function temp($pos)
			{
			global $BAB_SESS_USERID;
			$this->fullname = bab_translate("Contact")." / ".bab_translate("List");
			$this->email = bab_translate("Email");
			$this->allname = bab_translate("All");
			$this->totoname = bab_translate("To") ." ->";
			$this->toccname = bab_translate("Cc")." ->";
			$this->tobccname = bab_translate("Bcc")." ->";
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->closename = bab_translate("Close");
			$this->totourl = "javascript:updateDestination('to')";
			$this->toccurl = "javascript:updateDestination('cc')";
			$this->tobccurl = "javascript:updateDestination('bcc')";
			$this->closeurl = "javascript:this.close()";
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");

			$this->db = $GLOBALS['babDB'];

			$req = "select * from contacts where owner='".$BAB_SESS_USERID."' and firstname like '".$pos."%' order by firstname, lastname asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			$this->pos = $pos;

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=address&idx=list&pos=";
			$req = "select distinct p3.id, p3.firstname, p3.lastname, p3.email from users as p3, users_groups as p1,  users_groups as p2 where p1.id_object='".$BAB_SESS_USERID."' and p1.id_group = p2.id_group and p3.id=p2.id_object and firstname like '".$pos."%' order by firstname, lastname asc";
			$this->resgrpm = $this->db->db_query($req);
			$this->countgrpm = $this->db->db_num_rows($this->resgrpm);

			$req = "select groups.id, groups.name from  groups, users_groups as p1 where p1.id_object='".$BAB_SESS_USERID."' and p1.id_group = groups.id and groups.name like '".$pos."%' order by groups.name asc";
			$this->resgrp = $this->db->db_query($req);
			$this->countgrp = $this->db->db_num_rows($this->resgrp);

			}

		function getnextcontact()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
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

		function getnextgroupmember()
			{
			static $j = 0;
			if( $j < $this->countgrpm)
				{
				$arr = $this->db->db_fetch_array($this->resgrpm);
				$this->nameval = bab_composeUserName($arr['firstname'],$arr['lastname']);
				$this->emailval = $arr['email'];
				$this->checkval = $this->nameval."(g)";
				$j++;
				return true;
				}
			else
				{
				return false;
				}

			}

		function getnextgroup()
			{
			static $j = 0;
			if( $j < $this->countgrp)
				{
				$arr = $this->db->db_fetch_array($this->resgrp);
				$this->nameval = $arr['name'];
				$this->emailval = "";
				$this->checkval = $this->nameval."(g)";
				$j++;
				return true;
				}
			else
				{
				return false;
				}

			}

		function getnextselect()
			{
			global $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=address&idx=list&pos=".$this->selectname;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					$req = "select * from contacts where firstname like '".$this->selectname."%'";
					$res = $this->db->db_query($req);
					if( $this->db->db_num_rows($res) < 1 )
						{
						$req = "select distinct p3.id, p3.firstname, p3.lastname, p3.email from users as p3, users_groups as p1,  users_groups as p2 where p1.id_object='".$BAB_SESS_USERID."' and p1.id_group = p2.id_group and p3.id=p2.id_object and firstname like '".$this->selectname."%'";
						$res = $this->db->db_query($req);
						if( $this->db->db_num_rows($res) > 0 )
							{
							$this->selected = 0;
							}
						else
							{
							$req = "select groups.id from  groups, users_groups as p1 where p1.id_object='".$BAB_SESS_USERID."' and p1.id_group = groups.id and groups.name like '".$this->selectname."%'";
							$res = $this->db->db_query($req);
							if( $this->db->db_num_rows($res) > 0 )
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
	echo bab_printTemplate($temp, "address.html", "addresslist");
	}

/* main */
if( !isset($pos))
	$pos = "A";

if( !isset($idx))
	$idx = "list";

switch($idx)
	{
	case "list":
		$babBody->title = bab_translate("Users list");
		listAddress($pos);
		$babBody->addItemMenu("list", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=address&idx=list");
		//$babBody->addItemMenu("Find", bab_translate("Find"), $GLOBALS['babUrlScript']."?tg=users&idx=Find");
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>