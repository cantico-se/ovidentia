<?php
include $babAddonPhpPath."wrincl.php";
include $babInstallPath."utilit/mailincl.php";


function wr_workDescription($id)
{
	global $babDB;
	$res = $babDB->db_query("select * from ".ADDON_WR_WORKSLIST_TBL." where id='".$id."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['description'];
		}
	else
		return "";
}

function wr_isUserTaskManager($id)
{
	global $babDB;
	$res = $babDB->db_query("select manager from ".ADDON_WR_WORKSLIST_TBL." where id='".$id."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['manager'] == $GLOBALS['BAB_SESS_USERID'])
			return true;
		}
	return false;
}

function wr_isUserTaskSuper($id)
{
	global $babDB;
	$res = $babDB->db_query("select ".ADDON_WR_WORKSOTHERS_GROUPS_TBL.".id from ".ADDON_WR_WORKSOTHERS_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_group=".ADDON_WR_WORKSOTHERS_GROUPS_TBL.".id_group and ".BAB_USERS_GROUPS_TBL.".id_object='".$GLOBALS['BAB_SESS_USERID']."' and ".ADDON_WR_WORKSOTHERS_GROUPS_TBL.".id_object='".$id."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		return true;
		}
	else
		return false;
}

function wr_isUserTaskAgent($id)
{
	global $babDB;
	$res = $babDB->db_query("select ".ADDON_WR_WORKSAGENTS_GROUPS_TBL.".id from ".ADDON_WR_WORKSAGENTS_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_group=".ADDON_WR_WORKSAGENTS_GROUPS_TBL.".id_group and ".BAB_USERS_GROUPS_TBL.".id_object='".$GLOBALS['BAB_SESS_USERID']."' and ".ADDON_WR_WORKSAGENTS_GROUPS_TBL.".id_object='".$id."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		return true;
		}
	else
		return false;
}


function wr_newTask($service, $office, $room, $tel, $ddr, $mdr, $ydr, $ddd, $mdd, $ydd, $dlval, $wtype, $taskdesc)
{
	global $babBody;

	class temp
		{
		var $ddrurl;
		var $dddurl;
		var $dayid;
		var $selected;
		var $monthid;
		var $monthname;
		var $yearid;
		var $yearidval;
		var $applicant;
		var $fullname;
		var $service;
		var $yearbegin;
		var $task;
		var $tasktype;
		var $other;
		var $wtid;
		var $wtname;
		var $res;
		var $count;
		var $vservice;
		var $voffice;
		var $vroom;
		var $vtel;
		var $add;
		var $wtid;
		var $wtname;
		var $dlval;
		var $dlval2;
		var $dlvalsel;
		var $selected;
		var $help;


		function temp($service, $office, $room, $tel, $ddr, $mdr, $ydr, $ddd, $mdd, $ydd, $dlval, $wtype, $taskdesc)
			{
			global $babDB;
			$this->applicant = wr_translate("Demandeur");
			$this->service = wr_translate("Service");
			$this->office = wr_translate("Bureau");
			$this->room = wr_translate("Pièce");
			$this->tel = wr_translate("Téléphone");
			$this->daterequest = wr_translate("Date de demande");
			$this->datedesired = wr_translate("Date souhaitée");
			$this->deadline = wr_translate("Délai");
			$this->task = wr_translate("Travaux");
			$this->tasktype = wr_translate("Types de travaux");
			$this->other = wr_translate("Autre");
			$this->add = wr_translate("Ajouter");
			$this->help = wr_translate("Ces champs sont obligatoires !");

			$this->fullname = $GLOBALS['BAB_SESS_USER'];
			$this->yearbegin = date("Y");
			$this->ddrurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateDR&ymin=0&ymax=2";
			$this->dddurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateDD&ymin=0&ymax=2";
	
			$res = $babDB->db_query("select id from ".ADDON_WR_WORKSLIST_TBL."");
			$this->warrid = array();
			while( $row = $babDB->db_fetch_array($res))
				{
				if(bab_isAccessValid(ADDON_WR_WORKSUSERS_GROUPS_TBL, $row['id']))
					{
					array_push($this->warrid, $row['id']);
					}
				}
			$this->count = count($this->warrid);

			$this->vservice = "";
			$this->voffice = "";
			$this->vroom = "";
			$this->vtel = "";
			if( !empty($office ))
				$this->voffice = $office;
			if( !empty($room ))
				$this->vroom = $room;
			if( !empty($service ))
				$this->vservice = $service;
			if( !empty($tel ))
				$this->vtel = $tel;

			$res = $babDB->db_query("select id, service, office, room, tel from ".ADDON_WR_TASKSLIST_TBL." where user='".$GLOBALS['BAB_SESS_USERID']."' order by id desc limit 1");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->vservice = $this->vservice == "" ? $arr['service']: $this->vservice;
				$this->voffice = $this->voffice == "" ? $arr['office'] : $this->voffice;
				$this->vroom = $this->vroom == "" ? $arr['room'] : $this->vroom;
				$this->vtel = $this->vtel == "" ? $arr['tel'] : $this->vtel;
				}

			if( !empty($dlval ))
				$this->dlval2 = $dlval2;

			if( !empty($wtype ))
				$this->wtype = $wtype;

			if( !empty($taskdesc ))
				$this->taskdesc = $taskdesc;
			else
				$this->taskdesc = "";
			}

		function getnextwtype()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($babDB->db_query("select id, description from ".ADDON_WR_WORKSLIST_TBL." where id='".$this->warrid[$i]."'"));
				$this->wtid = $arr['id'];
				if( $this->wtid == $this->wtype )
					$this->wtsel = "selected";
				else
					$this->wtsel = "";
				$this->wtname = $arr['description'];
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}

		function getnextdl()
			{
			static $i = 0;
			static $dlarray = array(0 => "Aucun", 1 => "Urgent", 2 => "Court", 3 => "Long");
			if( $i < count($dlarray))
				{
				$this->dlval = wr_translate($dlarray[$i]);
				if( $this->dlval == $dlvalsel )
					$this->dlvalsel = "selected";
				else
					$this->dlvalsel = "";
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}

		function getnextday()
			{
			static $i = 1;
			if( $i <= date("t"))
				{
				$this->dayid = $i;
				if( date("j") == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
				
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				return false;
				}

			}

		function getnextmonth()
			{
			global $babMonths;
			static $i = 1;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = $babMonths[$i];
				if( date("n") == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				$i = 1;
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0;
			if( $i < 3)
				{
				$this->yearid = $i+1;
				$this->yearidval = $this->yearbegin + $i;
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		}

	$temp = new temp($service, $office, $room, $tel, $ddr, $mdr, $ydr, $ddd, $mdd, $ydd, $dlval, $wtype, $taskdesc);
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "newtask"));
}


function saveTask($service, $office, $room, $tel, $ddr, $mdr, $ydr, $ddd, $mdd, $ydd, $dlval, $wtype, $taskdesc)
{
	global $babBody, $babDB;

	if( empty($service))
		{
		$babBody->msgerror = wr_translate("Vous devez remplir le champs service");
		return false;
		}
	if( empty($office))
		{
		$babBody->msgerror = wr_translate("Vous devez remplir le champs bureau");
		return false;
		}
	if( empty($room))
		{
		$babBody->msgerror = wr_translate("Vous devez remplir le champs pièce");
		return false;
		}
	if( empty($tel))
		{
		$babBody->msgerror = wr_translate("Vous devez remplir le champs téléphone");
		return false;
		}

	if( empty($taskdesc))
		{
		$babBody->msgerror = wr_translate("Vous devez fournir une description des travaux demandés");
		return false;
		}

	$dddate = sprintf("%04d-%02d-%02d", (date("Y") + $ydd -1), $mdd, $ddd);
	$drdate = date('Y-m-d');

	if( $drdate > $dddate )
		{
		$babBody->msgerror = wr_translate("La date souhaitée doit être supérieure à celle d'aujourd'hui");
		return false;
		}
	if( !bab_isMagicQuotesGpcOn())
		{
		$desc = addslashes($taskdesc);
		$service = addslashes($service);
		$office = addslashes($office);
		$room = addslashes($room);
		$tel = addslashes($tel);
		}
	else
		$desc = $taskdesc;

	$babDB->db_query("insert into ".ADDON_WR_TASKSLIST_TBL." (user, service, office, room, tel, deadline, description, wtype, date_desired, date_request) VALUES ('" .$GLOBALS['BAB_SESS_USERID']. "', '" . $service. "', '" . $office. "', '" . $room. "', '" . $tel. "', '" . $dlval. "', '" . $desc. "', '" . $wtype. "', '" . $dddate. "', CURDATE())");


	class tempc
		{
		function tempc($tasktype, $desc)
			{
			$this->date = wr_translate("Date");
			$this->type = wr_translate("Type de travaux");
			$this->author = wr_translate("Author");
			$this->site = wr_translate("Sîte Web");
			$this->dateval = bab_strftime(mktime());
			$this->author = $GLOBALS['BAB_SESS_USER'];
			$this->tasktype = $tasktype;
			$this->from = wr_translate("De la part de");
			$this->message = $desc;
			}
		}

	$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".ADDON_WR_WORKSLIST_TBL." where id='".$wtype."'"));
	$tasktype = $arr['description'];

	$tempc = new tempc($tasktype, $taskdesc);
	$message = bab_printTemplate($tempc,$GLOBALS['babAddonHtmlPath']."main.html", "notifymembers");

	$messagetxt = bab_printTemplate($tempc,$GLOBALS['babAddonHtmlPath']."main.html", "notifymemberstxt");

    $mail = bab_mail();
	if( $mail == false )
		return false;
    $mail->mailTo($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
    $mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
    $mail->mailSubject(wr_translate("Demande de travaux"));

	$res = $babDB->db_query("select id_group from ".ADDON_WR_WORKSAGENTS_GROUPS_TBL." where id_object='".$wtype."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		while( $row = $babDB->db_fetch_array($res))
			{
			switch($row['id_group'])
				{
				case 0:
				case 1:
					$res2 = $babDB->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
					break;
				case 2:
					return;
				default:
					$res2 = $babDB->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
					break;
				}

			if( $res2 && $babDB->db_num_rows($res2) > 0 )
				{
				$count = 0;
				while(($arr = $babDB->db_fetch_array($res2)))
					{
					$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
					$count++;
					if( $count == 25 )
						{
						$mail->mailBody($message, "html");
						$mail->mailAltBody($messagetxt);
						$mail->send();
						$mail->clearBcc();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->mailBody($message, "html");
					$mail->mailAltBody($messagetxt);
					$mail->send();
					$mail->clearBcc();
					$count = 0;
					}
				}	
			}
		}

	$res = $babDB->db_query("select id_group from ".ADDON_WR_WORKSOTHERS_GROUPS_TBL." where id_object='".$wtype."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		while( $row = $babDB->db_fetch_array($res))
			{
			switch($row['id_group'])
				{
				case 0:
				case 1:
					$res2 = $babDB->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
					break;
				case 2:
					return;
				default:
					$res2 = $babDB->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
					break;
				}

			if( $res2 && $babDB->db_num_rows($res2) > 0 )
				{
				$count = 0;
				while(($arr = $babDB->db_fetch_array($res2)))
					{
					$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
					$count++;
					if( $count == 25 )
						{
						$mail->mailBody($message, "html");
						$mail->mailAltBody($messagetxt);
						$mail->send();
						$mail->clearBcc();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->mailBody($message, "html");
					$mail->mailAltBody($messagetxt);
					$mail->send();
					}
				}	
			}
		}
	return true;
}


function wr_tasksList($filter=0)
{

	global $babBody;

	class temp
		{
		var $description;
		var $worktype;
		var $username;
		var $status;
		var $desctxt;
		var $usertxt;
		var $statustxt;
		var $res;
		var $count;
		var $tel;
		
		function temp($filter)
			{
			global $babDB, $wr_array_status;
			$this->description = wr_translate("Description");
			$this->username = wr_translate("Responsable");
			$this->datereq = wr_translate("Date de demande");
			$this->status = wr_translate("Statut");
			$this->tel = wr_translate("Téléphone");
			$this->all = wr_translate("Tous");
			$this->allsel = "";
			if( $filter == -1)
				{
				$this->filter = -1;
				$this->allsel = "selected";
				}
			else
				$this->filter = $filter;
			$req = "select * from ".ADDON_WR_TASKSLIST_TBL." where user='".$GLOBALS['BAB_SESS_USERID']."'";
			if( $filter != -1)
				$req .= " and status='".$filter."'";
			
			$req .= " order by date_request desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res );
			$this->countstatus = count($wr_array_status);
			}

		function getnext()
			{
			global $babDB, $wr_array_status;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				if( strlen($arr['description']) > 50 )
					$this->desctxt = substr($arr['description'], 0, 50)." ...";
				else
					$this->desctxt = $arr['description'];

				$this->descurl = $GLOBALS['babAddonUrl']."main&idx=view&idxval=ltasks&id=".$arr['id'];
				if( $arr['worker'] != 0 )
					{
					$this->usertxt = bab_getUserName($arr['worker']);
					$this->usertel = $arr['worker_tel'];
					}
				else
					{
					$this->usertxt = "";
					$this->usertel = "";
					}
				$this->statustxt = $wr_array_status[$arr['status']];
				$this->datereqtxt = bab_strftime(bab_mktime($arr['date_request']), false);
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
				if( $this->statusid == $this->filter)
					$this->statussel = "selected";
				else
					$this->statussel = "";
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($filter);
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "taskslist"));
}


function wr_tasksAdminList($filter=-1)
{

	global $babBody;

	class temp
		{
		var $description;
		var $worktype;
		var $username;
		var $status;
		var $desctxt;
		var $usertxt;
		var $statustxt;
		var $res;
		var $count;
		var $tel;
		var $all;
		var $allsel;
		var $countstatus;
		var $vstatus;
		var $statusid;
		var $statussel;
		var $filter;

		function temp($filter)
			{
			global $babDB, $wr_array_status;
			$this->description = wr_translate("Description");
			$this->worker = wr_translate("Responsable");
			$this->status = wr_translate("Statut");
			$this->user = wr_translate("Demandeur");
			$this->all = wr_translate("Tous");
			$this->datereq = wr_translate("Date de demande");
			$this->allsel = "";
			if( $filter == -1)
				{
				$this->filter = -1;
				$this->allsel = "selected";
				}
			else
				$this->filter = $filter;

			$req = "select * from ".ADDON_WR_TASKSLIST_TBL;
			if( $filter != -1)
				$req .= " where status='".$filter."'";
			$req.= " order by date_request desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res );
			$this->countstatus = count($wr_array_status);
			}

		function getnext()
			{
			global $babDB, $wr_array_status;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				if( strlen($arr['description']) > 50 )
					$this->desctxt = substr($arr['description'], 0, 50)." ...";
				else
					$this->desctxt = $arr['description'];

				$this->descurl = $GLOBALS['babAddonUrl']."main&idx=view&idxval=ltus&id=".$arr['id'];
				if( $arr['worker'] != 0 )
					{
					$this->workertxt = bab_getUserName($arr['worker']);
					}
				else
					{
					$this->workertxt = "";
					}
				$this->usertxt = bab_getUserName($arr['user']);
				$this->statustxt = $wr_array_status[$arr['status']];
				$this->datereqtxt = bab_strftime(bab_mktime($arr['date_request']), false);
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
				if( $this->statusid == $this->filter)
					$this->statussel = "selected";
				else
					$this->statussel = "";
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($filter);
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "tasksadminlist"));
}

function wr_tasksAgentList($filter=0)
{

	global $babBody;

	class temp
		{
		var $description;
		var $worktype;
		var $username;
		var $status;
		var $desctxt;
		var $usertxt;
		var $statustxt;
		var $res;
		var $count;
		var $tel;
		
		function temp($filter)
			{
			global $babDB, $wr_array_status;
			$this->description = wr_translate("Description");
			$this->username = wr_translate("Demandeur");
			$this->status = wr_translate("Statut");
			$this->all = wr_translate("Tous");
			$this->datereq = wr_translate("Date de demande");

			$this->allsel = "";
			if( $filter == -1)
				{
				$this->filter = -1;
				$this->allsel = "selected";
				}
			else
				$this->filter = $filter;

			$res = $babDB->db_query("select id from ".ADDON_WR_WORKSLIST_TBL."");
			$this->warrid = array();
			while( $row = $babDB->db_fetch_array($res))
				{
				if(bab_isAccessValid(ADDON_WR_WORKSAGENTS_GROUPS_TBL, $row['id']))
					{
					array_push($this->warrid, $row['id']);
					}
				}
			$this->count = count($this->warrid);
			$this->countstatus = count($wr_array_status);
			}

		function getnexttype()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from ".ADDON_WR_TASKSLIST_TBL." where (worker='".$GLOBALS['BAB_SESS_USERID']."' or worker='0')";
				if( $this->filter != -1)
					$req .= " and status = '".$this->filter."'";
				$req .= " and wtype='".$this->warrid[$i]."' order by date_request desc";
				$this->res2 = $babDB->db_query($req);
				$this->count2 = $babDB->db_num_rows($this->res2 );
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnext()
			{
			global $babDB, $wr_array_status;
			static $i = 0;
			if( $i < $this->count2)
				{
				$arr = $babDB->db_fetch_array($this->res2);
				if( strlen($arr['description']) > 50 )
					$this->desctxt = substr($arr['description'], 0, 50)." ...";
				else
					$this->desctxt = $arr['description'];

				$this->descurl = $GLOBALS['babAddonUrl']."main&idx=view&idxval=ltag&id=".$arr['id'];
				$this->usertxt = bab_getUserName($arr['user']);
				$this->statustxt = $wr_array_status[$arr['status']];
				$this->datereqtxt = bab_strftime(bab_mktime($arr['date_request']), false);
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextstatus()
			{
			global $wr_array_status;
			static $i = 0;
			if( $i < $this->countstatus)
				{
				$this->statusid = $i;
				$this->vstatus = $wr_array_status[$i];
				if( $this->statusid == $this->filter)
					$this->statussel = "selected";
				else
					$this->statussel = "";
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($filter);
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "tasksagentlist"));
}

function viewTask($id, $idxval, $agenttel, $status, $dsd, $dsm, $dsy, $ded, $dem, $dey, $taskinfo)
	{
	global $babBody;

	class temp
		{
		var $username;
		var $service;
		var $office;
		var $room;
		var $tel;
		var $tasktype;
		var $deadline;
		var $desc;
		var $daterequest;
		var $datestart;
		var $vservice;
		var $voffice;
		var $vroom;
		var $vtel;
		var $vtasktype;
		var $vdeadline;
		var $vdesc;
		var $close;
		var $vdaterequest;
		var $status;
		var $arr = array();
		var $vinformation;
		var $information;
		var $agent;
		var $agentname;
		var $agenttel;
		var $vagenttel;
		var $dateend;
		var $vdateend;
		var $update;
		var $taskid;
		var $bdel;
		var $delete;
	
		function temp($id, $idxval, $agenttel, $status, $dsd, $dsm, $dsy, $ded, $dem, $dey, $taskinfo)
			{
			global $babDB, $wr_array_status;
			$this->close = bab_translate("Close");
			$this->applicant = wr_translate("Demandeur");
			$this->service = wr_translate("Service");
			$this->office = wr_translate("Bureau");
			$this->room = wr_translate("Pièce");
			$this->tel = wr_translate("Téléphone");
			$this->desc = wr_translate("Description");
			$this->daterequest = wr_translate("Date de demande");
			$this->datedesired = wr_translate("Date souhaitée");
			$this->datestart = wr_translate("Date de début");
			$this->dateend = wr_translate("Date de fin");
			$this->deadline = wr_translate("Délai");
			$this->tasktype = wr_translate("Type de travaux");
			$this->status = wr_translate("Statut");
			$this->information = wr_translate("Réponse ou complément d'information");
			$this->agent = wr_translate("Pris en charge par");
			$this->agenttel = wr_translate("Téléphone");
			$this->update = wr_translate("Modifier");
			$this->delete = wr_translate("Supprimer");

			$this->idxval = $idxval;
			$this->msgerror = $GLOBALS['WR_MSGERROR'];
			$this->taskid = $id;
			$this->arr = $babDB->db_fetch_array($babDB->db_query("select * from ".ADDON_WR_TASKSLIST_TBL." where id='".$id."'"));
			$this->username = bab_getUserName($this->arr['user']);
			$this->vservice = $this->arr['service'];
			$this->voffice = $this->arr['office'];
			$this->vroom = $this->arr['room'];
			$this->vtel = $this->arr['tel'];
			$this->vdesc = nl2br($this->arr['description']);
			$this->vdaterequest = bab_strftime(bab_mktime($this->arr['date_request']), false);
			$this->vdatedesired = bab_strftime(bab_mktime($this->arr['date_desired']), false);
			$this->datestarturl = $GLOBALS['babUrlScript']."?tg=month&callback=dateDS&ymin=0&ymax=2";
			$this->dateendurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateDE&ymin=0&ymax=2";

			if( empty($dsm) && $this->arr['date_start'] != '0000-00-00')
				{
				$date = bab_strftime(bab_mktime($this->arr['date_start']), false);
				$rr = explode(' ', $date);
				$this->vdatestartd = $rr[1];
				$this->vdatestartmtxt = $rr[2];
				$this->vdatestarty = $rr[3];
				$rr = explode('-', $this->arr['date_start']);
				$this->vdatestartm = $rr[1];
				}
			else
				{
				$this->vdatestartd = !empty($dsd)? $dsd: "";
				$this->vdatestartm = !empty($dsm)? $dsm: "";
				$this->vdatestarty = !empty($dsd)? $dsy: "";
				$this->vdatestartmtxt = !empty($dsm)? $GLOBALS['babMonths'][$dsm]: "";
				}

			if( empty($dem) && $this->arr['date_end'] != '0000-00-00')
				{
				$date = bab_strftime(bab_mktime($this->arr['date_end']), false);
				$rr = explode(' ', $date);
				$this->vdateendd = $rr[1];
				$this->vdateendmtxt = $rr[2];
				$this->vdateendy = $rr[3];
				$rr = explode('-', $this->arr['date_end']);
				$this->vdateendm = $rr[1];
				}
			else
				{
				$this->vdateendd = !empty($ded)? $ded: "";
				$this->vdateendm = !empty($dem)? $dem: "";
				$this->vdateendy = !empty($dey)? $dey: "";
				$this->vdateendmtxt = !empty($dem)? $GLOBALS['babMonths'][$dem]: "";
				}

			$this->vdeadline = wr_translate($this->arr['deadline']);
			if( !empty($taskinfo))
				$this->vinformation = $taskinfo;
			else
				$this->vinformation = $this->arr['information'];
			if( !empty($agenttel))
				$this->vagenttel = $agenttel;
			else
				$this->vagenttel = $this->arr['worker_tel'];
			if( $this->arr['worker'] != 0 )
				$this->agentname = bab_getUserName($this->arr['worker']);
			else
				$this->agentname = "";
			$this->bmodify = false;
			$this->bdel = false;
			if( wr_isUserTaskAgent($this->arr['wtype']))
				$this->bmodify = true;
			
			if( wr_isUserTaskManager($this->arr['wtype']))
				$this->bdel = true;

			$arr = $babDB->db_fetch_array($babDB->db_query("select description from ".ADDON_WR_WORKSLIST_TBL." where id='".$this->arr['wtype']."'"));
			$this->vtasktype = $arr['description'];
			if( $this->bmodify )
				{
				$this->countstatus = count($wr_array_status);
				if( !empty($status))
					$this->arr['status'] = $status;
				}
			else
				{
				$this->vstatus = wr_translate($wr_array_status[$this->arr['status']]);
				}

		}

		function getnextstatus()
			{
			global $wr_array_status;
			static $i = 0;
			if( $i < $this->countstatus)
				{
				$this->statusid = $i;
				$this->vstatus = $wr_array_status[$i];
				if( $this->statusid == $this->arr['status'])
					$this->statussel = "selected";
				else
					$this->statussel = "";
				$i++;
				return true;
				}
			else
				return false;
			}


		}
	
	$temp = new temp($id, $idxval, $agenttel, $status, $dsd, $dsm, $dsy, $ded, $dem, $dey, $taskinfo);
	echo bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "taskview");
	}

function modifyTask($taskid, $agenttel, $status, $dsd, $dsm, $dsy, $ded, $dem, $dey, $taskinfo)
{
	global $babBody, $babDB;
	$update = false;
	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".ADDON_WR_TASKSLIST_TBL." where id='".$taskid."'"));

	if( $dsy != "" )
		$dsdate = sprintf("%04d-%02d-%02d", $dsy, $dsm, $dsd);
	else
		$dsdate = "0000-00-00";
	if( $dey != "" )
		$dedate = sprintf("%04d-%02d-%02d", $dey, $dem, $ded);
	else
		$dedate = "0000-00-00";

	if( $dsy != "" && $dey != "" && $dsdate > $dedate )
		{
		$GLOBALS['WR_MSGERROR'] = wr_translate("La date de fin doit être supérieure ou égale à la date de début");
		return false;
		}

	if( $arr['worker'] == 0 && wr_isUserTaskAgent($arr['wtype']))
		{
		$babDB->db_query("update ".ADDON_WR_TASKSLIST_TBL." set worker='".$GLOBALS['BAB_SESS_USERID']."' where id='".$taskid."'");
		$update = true;
		}

	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".ADDON_WR_TASKSLIST_TBL." where id='".$taskid."'"));
	if( $arr['information'] != $taskinfo )
		$update = true;
	else if( $arr['status'] != $status )
		$update = true;
	else if( $arr['date_start'] != $dsdate )
		$update = true;
	else if( $arr['date_end'] != $dedate )
		$update = true;
	else if( $arr['worker_tel'] != $agenttel )
		$update = true;

	if( !bab_isMagicQuotesGpcOn())
		{
		$taskinfo = addslashes($taskinfo);
		}

	if( $update )
		$babDB->db_query("update ".ADDON_WR_TASKSLIST_TBL." set worker_tel='".$agenttel."', date_start='".$dsdate."', date_end='".$dedate."', status='".$status."', information='".$taskinfo."', user_update='".$GLOBALS['BAB_SESS_USERID']."', date_update=CURDATE() where id='".$taskid."'");
	else
		return true;

	class tempd
		{
		function tempd($tasktype, $arr)
			{
			global $wr_array_status;
			$this->date = wr_translate("Date");
			$this->type = wr_translate("Type de travaux");
			$this->author = wr_translate("Author");
			$this->site = wr_translate("Sîte Web");
			$this->dateval = bab_strftime(bab_mktime($arr['date_request']), false);
			$this->author = bab_getUserName($arr['user']);
			$this->tasktype = $tasktype;
			$this->from = wr_translate("De la part de");
			$this->desc = wr_translate("Description");
			$this->vdesc = nl2br($arr['description']);
			$this->message = wr_translate("A été mis à jour par:");
			$this->agent = wr_translate("Responsable");
			$this->vagent = bab_getUserName($arr['worker']);
			$this->status = wr_translate("Statut");
			$this->vstatus = $wr_array_status[$arr['status']];
			$this->dates = wr_translate("Date de début");
			$this->datesval = bab_strftime(bab_mktime($arr['date_start']), false);
			$this->info = wr_translate("Réponse");
			$this->vinfo = nl2br($arr['information']);
			}
		}

	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".ADDON_WR_TASKSLIST_TBL." where id='".$taskid."'"));


	$rr = $babDB->db_fetch_array($babDB->db_query("select description from ".ADDON_WR_WORKSLIST_TBL." where id='".$arr['wtype']."'"));
	$tasktype = $rr['description'];

	$tempc = new tempd($tasktype, $arr);
	$message = bab_printTemplate($tempc,$GLOBALS['babAddonHtmlPath']."main.html", "notifymembers2");

	$messagetxt = bab_printTemplate($tempc,$GLOBALS['babAddonHtmlPath']."main.html", "notifymemberstxt2");

    $mail = bab_mail();
	if( $mail == false )
		return false;
    $mail->mailTo(bab_getUserEmail($arr['user']), bab_getUserName($arr['user']));
    $mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
    $mail->mailSubject(wr_translate("Statut de votre demande de travaux"));

	$res = $babDB->db_query("select id_group from ".ADDON_WR_WORKSOTHERS_GROUPS_TBL." where id_object='".$arr['wtype']."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		while( $row = $babDB->db_fetch_array($res))
			{
			switch($row['id_group'])
				{
				case 0:
				case 1:
					$res2 = $babDB->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
					break;
				case 2:
					return;
				default:
					$res2 = $babDB->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
					break;
				}

			if( $res2 && $babDB->db_num_rows($res2) > 0 )
				{
				$count = 0;
				while(($arr = $babDB->db_fetch_array($res2)))
					{
					$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
					$count++;
					if( $count == 25 )
						{
						$mail->mailBody($message, "html");
						$mail->mailAltBody($messagetxt);
						$mail->send();
						$mail->clearBcc();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->mailBody($message, "html");
					$mail->mailAltBody($messagetxt);
					$mail->send();
					}
				}	
			}
		}

	return true;
}

function taskUnload($idxval)
	{
	class temp
		{
		var $babCss;
		var $message;
		var $close;
		var $url;

		function temp($idxval)
			{
			$this->message = wr_translate("Mise à jour efféctuée");
			$this->close = wr_translate("Fermer");
			$this->url = $GLOBALS['babAddonUrl']."main&idx=".$idxval;
			}
		}

	$temp = new temp($idxval);
	echo bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "taskunload");
	}

function taskDelete($id)
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
			$this->message = wr_translate("Etes-vous sûr de vouloir supprimer ce bon de travaux");
			$this->title = "";
			$this->warning = wr_translate("ATTENTION, la suppression est définitive"). "!";
			$this->urlyes = $GLOBALS['babAddonUrl']."main&idx=Delete&id=".$id."&action=Yes";
			$this->yes = wr_translate("Oui");
			$this->urlno = $GLOBALS['babAddonUrl']."main&idx=ltasks&id=".$id."&action=No";
			$this->no = wr_translate("Non");
			}
		}

	$temp = new temp($id);
	echo bab_printTemplate($temp,"warning.html", "warningyesno");
	}

/* main */
$GLOBALS['WR_MSGERROR'] ="";
$wr_access = 0x00;

$res = $babDB->db_query("select distinct ".ADDON_WR_WORKSUSERS_GROUPS_TBL.".id from ".ADDON_WR_WORKSUSERS_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".ADDON_WR_WORKSUSERS_GROUPS_TBL.".id_group='1' or (".BAB_USERS_GROUPS_TBL.".id_group=".ADDON_WR_WORKSUSERS_GROUPS_TBL.".id_group and ".BAB_USERS_GROUPS_TBL.".id_object='".$GLOBALS['BAB_SESS_USERID']."')");

if( $res && $babDB->db_num_rows($res) > 0 )
	{
	$wr_access |= WR_ACCESS_USER;
	}

$res = $babDB->db_query("select distinct ".ADDON_WR_WORKSAGENTS_GROUPS_TBL.".id from ".ADDON_WR_WORKSAGENTS_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".ADDON_WR_WORKSAGENTS_GROUPS_TBL.".id_group='1' or (".BAB_USERS_GROUPS_TBL.".id_group=".ADDON_WR_WORKSAGENTS_GROUPS_TBL.".id_group and ".BAB_USERS_GROUPS_TBL.".id_object='".$GLOBALS['BAB_SESS_USERID']."')");

if( $res && $babDB->db_num_rows($res) > 0 )
	{
	$wr_access |= WR_ACCESS_AGENT;
	}

$res = $babDB->db_query("select distinct ".ADDON_WR_WORKSOTHERS_GROUPS_TBL.".id from ".ADDON_WR_WORKSOTHERS_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".ADDON_WR_WORKSOTHERS_GROUPS_TBL.".id_group='1' or (".BAB_USERS_GROUPS_TBL.".id_group=".ADDON_WR_WORKSOTHERS_GROUPS_TBL.".id_group and ".BAB_USERS_GROUPS_TBL.".id_object='".$GLOBALS['BAB_SESS_USERID']."')");

if( $res && $babDB->db_num_rows($res) > 0 )
	{
	$wr_access |= WR_ACCESS_SUPER;
	}

$res = $babDB->db_query("select ".ADDON_WR_WORKSLIST_TBL.".id from ".ADDON_WR_WORKSLIST_TBL." where manager='".$GLOBALS['BAB_SESS_USERID']."'");
if( $res && $babDB->db_num_rows($res) > 0 )
	{
	$wr_access |= WR_ACCESS_MAN;
	}

if( !$wr_access )
{
	$babBody->msgerror = wr_translate("Accès refusé");
	return;
}

if( !isset($idx ))
	{
	if( $wr_access & WR_ACCESS_USER )
		$idx = "ltasks";
	else if( $wr_access & WR_ACCESS_AGENT )
		$idx = "ltag";
	else if( $wr_access & WR_ACCESS_SUPER | $wr_access & WR_ACCESS_MAN )
		$idx = "ltsu";
	}	

if( isset($what))
{
	if( $what == "taskadd" )
		{
		if( !saveTask($wrservice, $office, $room, $tel, $ddr, $mdr, $ydr, $ddd, $mdd, $ydd, $dlval, $wtype, $taskdesc))
			$idx = "newt";
		else
			$idx = "ltasks";
		}
	else if( $what == "taskmod" )
		{
		if( isset($Submit))
			{
			if( !modifyTask($taskid, $agenttel, $status, $dsd, $dsm, $dsy, $ded, $dem, $dey, $taskinfo))
				{
				$id = $taskid;
				$idx = "view";
				}
			else
				{
				$idxval = $idx;
				$idx = "unload";
				}
			}
		else if( isset($bndel))
			{
			$idx = "delete";
			//$babDB->db_query("delete from ".ADDON_WR_TASKSLIST_TBL." where id='".."'");
			//$idx= "unload";
			}
		}
}

if( isset($action) )
	{
	if( $action == "Yes" )
		{
		$babDB->db_query("delete from ".ADDON_WR_TASKSLIST_TBL." where id='".$id."'");
		$idx= "unload";
		}
	else
		{
		$idx = "view";
		}
	}

switch($idx)
	{
	case "delete":
		taskDelete($taskid);
		exit;
		break;

	case "unload":
		taskUnload($idxval);
		exit;
		break;
	case "view":
		viewTask($id, $idxval, $agenttel, $status, $dsd, $dsm, $dsy, $ded, $dem, $dey, $taskinfo);
		exit;
		break;

	case "newt":
		$babBody->title = wr_translate("Bon de travaux");
		wr_newTask($wrservice, $office, $room, $tel, $ddr, $mdr, $ydr, $ddd, $mdd, $ydd, $dlval, $wtype, $taskdesc);
		$babBody->addItemMenu("ltasks", wr_translate("Bons de Travaux"), $GLOBALS['babAddonUrl']."main&idx=ltasks");
		$babBody->addItemMenu("newt", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."main&idx=newt");
		if( $wr_access & WR_ACCESS_AGENT )
			{
			$babBody->addItemMenu("ltag", wr_translate("Demandes"), $GLOBALS['babAddonUrl']."main&idx=ltag");
			}
		if( $wr_access & WR_ACCESS_SUPER |  $wr_access & WR_ACCESS_MAN)
			{
			$babBody->addItemMenu("ltsu", wr_translate("Administration"), $GLOBALS['babAddonUrl']."main&idx=ltsu");
			}
		break;

	case "ltag":
		$babBody->title = wr_translate("Liste des demandes de travaux");
		if( $wr_access & WR_ACCESS_USER )
			{
			$babBody->addItemMenu("ltasks", wr_translate("Bons de Travaux"), $GLOBALS['babAddonUrl']."main&idx=ltasks");
			$babBody->addItemMenu("newt", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."main&idx=newt");
			}
		if( $wr_access & WR_ACCESS_AGENT )
			{
			wr_tasksAgentList($filter);
			$babBody->addItemMenu("ltag", wr_translate("Demandes"), $GLOBALS['babAddonUrl']."main&idx=ltag");
			}
		if( $wr_access & WR_ACCESS_SUPER |  $wr_access & WR_ACCESS_MAN)
			{
			$babBody->addItemMenu("ltsu", wr_translate("Administration"), $GLOBALS['babAddonUrl']."main&idx=ltsu");
			}
		break;

	case "ltsu":
		$babBody->title = wr_translate("Liste des demandes de travaux");
		if( $wr_access & WR_ACCESS_USER )
			{
			$babBody->addItemMenu("ltasks", wr_translate("Bons de Travaux"), $GLOBALS['babAddonUrl']."main&idx=ltasks");
			$babBody->addItemMenu("newt", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."main&idx=newt");
			}
		if( $wr_access & WR_ACCESS_AGENT )
			{
			$babBody->addItemMenu("ltag", wr_translate("Demandes"), $GLOBALS['babAddonUrl']."main&idx=ltag");
			}
		if( $wr_access & WR_ACCESS_SUPER |  $wr_access & WR_ACCESS_MAN)
			{
			wr_tasksAdminList($filter);
			$babBody->addItemMenu("ltsu", wr_translate("Administration"), $GLOBALS['babAddonUrl']."main&idx=ltsu");
			}
		break;

	case "ltasks":
	default:
		$babBody->title = wr_translate("Liste des bons de travaux");
		if( $wr_access & WR_ACCESS_USER )
			{
			wr_tasksList($filter);
			$babBody->addItemMenu("ltasks", wr_translate("Bons de Travaux"), $GLOBALS['babAddonUrl']."main&idx=ltasks");
			$babBody->addItemMenu("newt", wr_translate("Ajouter"), $GLOBALS['babAddonUrl']."main&idx=newt");
			}
		if( $wr_access & WR_ACCESS_AGENT )
			{
			$babBody->addItemMenu("ltag", wr_translate("Demandes"), $GLOBALS['babAddonUrl']."main&idx=ltag");
			}
		if( $wr_access & WR_ACCESS_SUPER ||  $wr_access & WR_ACCESS_MAN)
			{
			$babBody->addItemMenu("ltsu", wr_translate("Administration"), $GLOBALS['babAddonUrl']."main&idx=ltsu");
			}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>

