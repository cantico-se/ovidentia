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
include_once $babInstallPath."utilit/dirincl.php";


function dirlist()
	{
	global $babBody;

	class temp
		{
		var $count;

		function temp()
			{
			$this->conttitle = bab_translate("Contacts");
			$this->conturl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=contact";
			$this->dirtitle = bab_translate("Directories");
			$this->dirurl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=directory";
			$this->contactif = false;

			$this->db = $GLOBALS['babDB'];
			$res = $this->db->db_query("select id, id_group,name,description from ".BAB_DB_DIRECTORIES_TBL."");
			$this->count = 0;
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
					{
					if( $row['id_group'] > 0 )
						{
						list($bdir) = $this->db->db_fetch_array($this->db->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$row['id_group']."'"));
						if( $bdir == 'Y' )
							{
							$this->dbdir[] = $row;
							$this->count++;
							}
						}
					else
						{
						$this->dbdir[] = $row;
						$this->count++;
						}
					}
				}
			}


		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->dirurl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=directory&id=".$this->dbdir[$i]['id'];
				$this->description = $this->dbdir[$i]['description'];
				$this->title = $this->dbdir[$i]['name'];
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
	echo bab_printTemplate($temp, "editorcontdir.html", "dirlist");
	}


function directory($id, $pos, $xf, $badd)
{
	global $babBody;

	class temp
		{
		var $count;

		function temp($id, $pos, $xf, $badd)
			{
			$this->conttitle = bab_translate("Contacts");
			$this->conturl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=contact";
			$this->dirtitle = bab_translate("Directories");
			$this->dirurl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=directory";

			$this->allname = bab_translate("All");
			$this->addname = bab_translate("Add");
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
			$this->allurl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=directory&id=".$id."&pos=".($this->ord == "-"? "":$this->ord)."&xf=".$this->xf;
			$this->addurl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=directory&id=".$id;
			$this->count = 0;
			$this->db = $GLOBALS['babDB'];
			$arr = $this->db->db_fetch_array($this->db->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$id."'"));
			if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $id))
				{
				$this->idgroup = $arr['id_group'];
				$this->rescol = $this->db->db_query("select id, id_field from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($this->idgroup != 0? 0: $this->id)."' and ordering!='0' order by ordering asc");
				$this->countcol = $this->db->db_num_rows($this->rescol);
				}
			else
				{
				$this->countcol = 0;
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

		function getnextcol()
			{
			static $i = 0;
			static $tmp = array();
			static $sqlf = array();
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
					$sqlf[] = $filedname;
					$leftjoin[] = 'LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$arr['id']." ON lj".$arr['id'].".id_fieldx='".$arr['id']."' AND e.id=lj".$arr['id'].".id_entry";
					$this->select[] = "lj".$arr['id'].'.field_value '."babdirf".$arr['id']."";
					}
				$this->colurl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=directory&id=".$this->id."&pos=".$this->ord.$this->pos."&xf=".$filedname;
				$i++;
				return true;
				}
			else
				{
				if(  count($tmp) > 0 || count($sqlf) > 0 )
					{
					$tmp[] = "id";
					if( $this->xf == "" )
						{
						$this->xf = $tmp[0];
						}

					if( $this->idgroup > 1 )
						{
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e,
								".BAB_USERS_GROUPS_TBL." u ".implode(' ',$leftjoin)." 
									WHERE u.id_group='".$this->idgroup."' 
									AND u.id_object=e.id_user 
									AND e.id_directory='0'";
						}
					else
						{
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e ".implode(' ',$leftjoin)." WHERE e.id_directory='".(1 == $this->idgroup ? 0 : $this->id )."'";
						}

					$this->select[] = 'e.id';
					if( !in_array('email', $this->select))
						$this->select[] = 'e.email';

					if (!empty($this->pos) && false === strpos($this->xf, 'babdirf'))
						$like = " AND `".$this->xf."` LIKE '".$this->pos."%'";
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
				$this->arrf = $this->db->db_fetch_array($this->res);
				$this->urlmail = $GLOBALS['babUrlScript']."?tg=mail&idx=compose&accid=".$this->accid."&to=".$this->arrf['email'];
				$this->email = $this->arrf['email'];
				$this->js_id = $this->arrf['id'];
				$this->js_name = bab_composeUserName($this->arrf['givenname'],$this->arrf['sn']);
				$this->js_iddir = $this->id;
				$this->url = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=directory&id=".$this->id."&idu=".$this->arrf['id']."&pos=".$this->ord.$this->pos."&xf=".$this->xf;
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
				$this->coltxt = bab_translate($this->arrf[$i]);
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
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=directory&id=".$this->id."&pos=".($this->ord == "-"? "":$this->ord).$this->selectname."&xf=".$this->xf;
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
	echo bab_printTemplate($temp, "editorcontdir.html", "editordir");
}


function editorcont()
	{
	
	global $babBody;

	class temp
		{
		var $count;
		function temp()
			{
			$this->conttitle = bab_translate("Contacts");
			$this->conturl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=contact";
			$this->dirtitle = bab_translate("Directories");
			$this->dirurl = $GLOBALS['babUrlScript']."?tg=editorcontdir&idx=directory";
			$this->contactif = true;

			global $BAB_SESS_USERID;
			$req = "select * from ".BAB_CONTACTS_TBL." where owner='".$BAB_SESS_USERID."' order by lastname, firstname asc";
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query($req);
			if( $this->res )
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;
			}


		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->contid = $arr['id'];
				$this->title = bab_composeUserName( $arr['firstname'], $arr['lastname']);
				$tmp = str_replace("\""," ",$this->title);
				$this->js_title = addslashes($tmp);
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
	echo bab_printTemplate($temp, "editorcontdir.html", "editorcont");
	}


/* main */
if(isset($id) && bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id))
	$badd = true;
else
	$badd = false;

if(!isset($idx))
	{
	$idx = "contact";
	}

if( !isset($pos ))
	$pos = "A";

switch($idx)
	{
	case "directory":
		if( !isset($pos )){	$pos = "A"; }
		if( !isset($xf )){	$xf = ""; }
		if ($badd) directory($id, $pos, $xf, $badd);
		elseif (isset($id) && !$badd ) directory($id, $pos, $xf, $badd);
		else dirlist();
		exit;
		break;
	default:
	case "contact":
		editorcont();
		exit;
	}
?>