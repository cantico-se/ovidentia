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

require_once dirname(__FILE__).'/userinfosincl.php';
require_once dirname(__FILE__).'/urlincl.php';

function bab_browseUsers($pos, $cb)
{
	class bab_browseUsersTpl
	{
		public $fullname;
		public $urlname;
		public $url;
		public $email;
		public $status;
				
		public $fullnameval;
		public $emailval;

		public $arr = array();
		public $db;
		public $count;
		public $res;

		public $pos;
		public $altbg = true;
		public $userid;

		public $nickname;

		public function __construct($pos, $cb)
		{
			global $babDB;
		    $babBody = bab_getBody();

			$this->allname = bab_translate("All");
			$this->nickname = bab_translate("Login ID");
			$this->cb = $cb;

			if (!bab_isUserAdministrator() && $babBody->babsite['browse_users'] == 'N') {
				$req = "select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
				$resgroups = $babDB->db_query($req);

				$reqa = "select distinct ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname, ".BAB_USERS_TBL.".nickname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".bab_userInfos::queryAllowedUsers();
				if ($babDB->db_num_rows($resgroups) > 0 ) {
					$arr = $babDB->db_fetch_array($resgroups);
					$reqa .= " and ( ".BAB_USERS_GROUPS_TBL.".id_group='".$babDB->db_escape_string($arr['id'])."'";
					while($arr = $babDB->db_fetch_array($resgroups)) {
						$reqa .= " or ".BAB_USERS_GROUPS_TBL.".id_group='".$babDB->db_escape_string($arr['id'])."'";
					}
					$reqa .= ") and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id";
				}
			} else {
				$reqa = "select * from ".BAB_USERS_TBL." where ".bab_userInfos::queryAllowedUsers();
			}

			if (mb_strlen($pos) > 0 && $pos[0] == "-" ) {
				$this->pos = isset($pos[1]) ? $pos[1] : '';
				$this->ord = $pos[0];
				$reqa .= " and lastname like '".$babDB->db_escape_like($this->pos)."%' order by lastname, firstname asc";
				$this->fullname = bab_composeUserName(bab_translate("Lastname"), bab_translate("Firstname"));
				$this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=".$this->pos."&cb=".$cb);
			} else {
				$this->pos = $pos;
				$this->ord = "";
				$reqa .= " and firstname like '".$babDB->db_escape_like($this->pos)."%' order by firstname, lastname asc";
				$this->fullname = bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname"));
				$this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=-".$this->pos."&cb=".$cb);
			}
			$this->res = $babDB->db_query($reqa);
			$this->count = $babDB->db_num_rows($this->res);

    		if( empty($this->pos)) {
    			$this->allselected = 1;
    		} else {
    			$this->allselected = 0;
    		}
            $this->allurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=&cb=".$cb);
    	}

    	public function getnext()
    	{
    		global $babDB;
    		static $i = 0;
    
    		if ($i < $this->count) {
    			$this->arr = $babDB->db_fetch_array($this->res);
    			$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$this->arr['id']."&pos=".$this->ord.$this->pos."&cb=").$this->cb;
    			$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
    			$this->firstlast = bab_toHtml($this->firstlast, BAB_HTML_JS);
    			if ($this->ord == "-") {
    				$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
    			} else {
    				$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
    			}
    			$this->urlname = bab_toHtml($this->urlname);
    			$this->userid = bab_toHtml($this->arr['id']);
    			$this->nicknameval = bab_toHtml($this->arr['nickname']);
    			$this->altbg = !$this->altbg;
    			$i++;
    			return true;
    		}
    		return false;
    	}
    
    	public function getnextselect()
    	{
    		global $BAB_SESS_USERID, $babDB;
    		static $k = 0;
    		static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    
    		if ($k < 26) {
    			$this->selectname = mb_substr($t, $k, 1);
    			$this->selecturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=".$this->ord.$this->selectname."&cb=").$this->cb;
    
    			if ($this->pos == $this->selectname) {
    				$this->selected = 1;
    			} else {
    				if ($this->ord == "-") {
    					$req = "select * from ".BAB_USERS_TBL." where lastname like '".$babDB->db_escape_like($this->selectname)."%'";
    				} else {
    					$req = "select * from ".BAB_USERS_TBL." where firstname like '".$babDB->db_escape_like($this->selectname)."%'";
    				}
    				$res = $babDB->db_query($req);
    				if ($babDB->db_num_rows($res) > 0) {
    					$this->selected = 0;
    				} else {
    					$this->selected = 1;
    				}
    			}
    			$k++;
    			return true;
    		}
    		return false;
    	}
	}

	$temp = new bab_browseUsersTpl($pos, $cb);

	$babBody = bab_getBody();
	$babBody->babPopup(bab_printTemplate($temp, 'lusers.html', 'browseusers'));

	die();
}



function bab_adminBrowseUsers($pos, $cb)
{
    class bab_adminBrowseUsersTpl
    {
        public $fullname;
        public $urlname;
        public $url;
        public $email;
        public $status;

        public $fullnameval;
        public $emailval;

        public $arr = array();
        public $db;
        public $count;
        public $res;
        public $altbg = true;

        public $pos;

        public $userid;

        public $nickname;

        public function __construct($pos, $cb)
        {
            global $babBody, $babDB;

            $this->allname = bab_translate("All");
            $this->nickname = bab_translate("Login ID");
            $this->cb = $cb;
            	
            $currentDGGroup = bab_getCurrentDGGroup();

            switch ($babBody->nameorder[0]) {
                case "L":
                    $this->namesearch = 'lastname';
                    $this->namesearch2 = 'firstname';
                    break;
                case "F":
                default:
                    $this->namesearch = 'firstname';
                    $this->namesearch2 = 'lastname';
                    break;
            }

            if (mb_strlen($pos) > 0 && $pos[0] == "-" ) {
                $this->pos = mb_strlen($pos)>1? $pos[1]: '';
                $this->ord = $pos[0];
                if (bab_getCurrentAdmGroup() == 0) {
                    $req = "select * from ".BAB_USERS_TBL." where ".bab_userInfos::queryAllowedUsers()." and ".$this->namesearch2." like '".$babDB->db_escape_like($this->pos)."%' order by ".$this->namesearch2.", ".$this->namesearch." asc";
                } else {
                    $req .= "select distinct u.* from ".BAB_USERS_TBL." u, ".BAB_USERS_GROUPS_TBL." ug, ".BAB_GROUPS_TBL." g where ".bab_userInfos::queryAllowedUsers('u')." and ug.id_object=u.id and ug.id_group=g.id AND g.lf>='".$babDB->db_escape_string($currentDGGroup['lf'])."' AND g.lr<='".$babDB->db_escape_string($currentDGGroup['lr'])."' and u.".$this->namesearch2." like '".$babDB->db_escape_like($this->pos)."%' order by u.".$this->namesearch2.", u.".$this->namesearch." asc";
                }

                $this->fullname = bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname"));
                $this->fullnameurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&pos=".$this->pos."&cb=".$this->cb;
            } else {
                $this->pos = $pos;
                $this->ord = "";
                if (bab_getCurrentAdmGroup() == 0) {
                    $req = "select * from ".BAB_USERS_TBL." where ".bab_userInfos::queryAllowedUsers()." and ".$this->namesearch." like '".$babDB->db_escape_like($this->pos)."%' order by ".$this->namesearch.", ".$this->namesearch2." asc";
                } else {
                    $req = "select distinct u.* from ".BAB_USERS_TBL." u, ".BAB_USERS_GROUPS_TBL." ug, ".BAB_GROUPS_TBL." g where ".bab_userInfos::queryAllowedUsers('u')." and ug.id_object=u.id and ug.id_group=g.id AND g.lf>='".$babDB->db_escape_string($currentDGGroup['lf'])."' AND g.lr<='".$babDB->db_escape_string($currentDGGroup['lr'])."' and u.".$this->namesearch." like '".$babDB->db_escape_like($this->pos)."%' order by u.".$this->namesearch.", u.".$this->namesearch2." asc";
                }

                $this->fullname = bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname"));
                $this->fullnameurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&pos=-".$this->pos."&cb=".$this->cb;
            }

            $this->res = $babDB->db_query($req);
            $this->count = $babDB->db_num_rows($this->res);

            if (empty($this->pos)) {
                $this->allselected = 1;
            } else {
                $this->allselected = 0;
            }
            $this->allurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&pos=&cb=".$this->cb;
        }

        public function getnext()
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->count) {
                $this->arr = $babDB->db_fetch_array($this->res);
                $this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
                $this->firstlast = str_replace("'", "\'", $this->firstlast);
                $this->firstlast = str_replace('"', "'+String.fromCharCode(34)+'",$this->firstlast);
                if( $this->ord == "-" )
                    $this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
                else
                    $this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
                $this->urlname = bab_toHtml($this->urlname);
                $this->userid = $this->arr['id'];
                $this->nicknameval = bab_toHtml($this->arr['nickname']);
                $this->altbg = !$this->altbg;
                $i++;
                return true;
            }
            return false;
        }

        public function getnextselect()
        {
            global $babBody, $BAB_SESS_USERID;
            static $k = 0;
            static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            if ($k < 26) {
                $this->selectname = mb_substr($t, $k, 1);
                $this->selecturl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=".$this->ord.$this->selectname."&cb=".$this->cb;

                $this->selected = 0;
                if( $this->pos == $this->selectname) {
                    $this->selected = 1;
                }
                $k++;
                return true;
            }
            return false;
        }
    }

    $temp = new bab_adminBrowseUsersTpl($pos, $cb);
    
    $babBody = bab_getBody();
    $babBody->babPopup(bab_printTemplate($temp, 'lusers.html', 'browseusers'));

    die();
}



function browseArticlesAuthors($pos, $cb)
	{
	global $babBody;
	class temp
		{
		function temp($pos, $cb)
			{
			global $babBody, $babDB;

			$this->allname = bab_translate("All");
			$this->nickname = bab_translate("Login ID");
			$this->cb = $cb;
			$this->altbg = false;

			switch ($babBody->nameorder[0]) {
				case "L":
					$this->namesearch = 'lastname';
					$this->namesearch2 = 'firstname';
				break;
				case "F":
				default:
					$this->namesearch = 'firstname';
					$this->namesearch2 = 'lastname';
				break; }

			if( mb_strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = mb_strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];

				$req = "select distinct ut.* from ".BAB_USERS_TBL." ut left join ".BAB_ARTICLES_TBL." at on ut.id=at.id_author where at.id_author!=0 and at.id_topic in (".$babDB->quote(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)).") and ut.".$this->namesearch2." like '".$babDB->db_escape_like($this->pos)."%' order by ut.".$this->namesearch2.", ut.".$this->namesearch." asc";

				$this->fullname = bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=".$this->pos."&cb=".$this->cb;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select distinct ut.* from ".BAB_USERS_TBL." ut left join ".BAB_ARTICLES_TBL." at on ut.id=at.id_author where at.id_author!=0 and at.id_topic in (".$babDB->quote(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)).")  and ut.".$this->namesearch." like '".$babDB->db_escape_like($this->pos)."%' order by ut.".$this->namesearch.", ut.".$this->namesearch2." asc";

				$this->fullname = bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=-".$this->pos."&cb=".$this->cb;
				}

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=&cb=".$this->cb;
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->firstlast = str_replace("'", "\'", $this->firstlast);
				$this->firstlast = str_replace('"', "'+String.fromCharCode(34)+'",$this->firstlast);
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);

				$this->urlname = bab_toHtml($this->urlname);
				$this->userid = $this->arr['id'];
				$this->nicknameval = bab_toHtml($this->arr['nickname']);
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextselect()
			{
			global $babBody, $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = mb_substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=".$this->ord.$this->selectname."&cb=".$this->cb;

				$this->selected = 0;
				if( $this->pos == $this->selectname)
					$this->selected = 1;
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	
	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;

	$temp = new temp($pos, $cb);

	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp, "lusers.html", "browseusers"));
	printBabBodyPopup();
	die();

	}
?>