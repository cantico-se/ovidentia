<?php

function changePassword()
	{
	global $body,$BAB_SESS_USERID;
	class temp
		{
		var $oldpwd;
		var $newpwd;
		var $renewpwd;
		var $update;
		var $title;

		function temp()
			{
			$this->oldpwd = babTranslate("Old Password");
			$this->newpwd = babTranslate("New Password");
			$this->renewpwd = babTranslate("Retype New Password");
			$this->update = babTranslate("Update Password");
			$this->title = babTranslate("Change password");
			}
		}

	$db = new db_mysql();
	$req = "select * from users where id='$BAB_SESS_USERID'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	if( $arr[changepwd] != 0)
		{
		$temp = new temp();
		$body->babecho(	babPrintTemplate($temp,"options.html", "changepassword"));
		}
	else
		$body->msgerror = babTranslate("Sorry, You cannot change your password. Please contact administrator");
	}

function changeLanguage()
	{
	global $body;

	class tempa
		{
		var $title;
        var $count;
        var $userlang;
        var $langval;
        var $langselected;
        var $langname;
		var $update;

        var $arrfiles = array();

		function tempa()
			{
        	global $BAB_SESS_USERID;
			$this->title = babTranslate("Prefered language");
			$this->update = babTranslate("Update Language");
            $this->count = 0;

            $db = new db_mysql();
            $req = "select * from users where id='$BAB_SESS_USERID'";
            $res = $db->db_query($req);
            if( $res && $db->db_num_rows($res) > 0 )
                {
    			$arr = $db->db_fetch_array($res);
                $this->userlang = $arr[lang];
                }
            else
                $this->userlang = "";
           
            if( $this->userlang == "")
                $this->userlang = $GLOBAL[babLanguage];

            $this->title .= " : ".$this->userlang;

            $h = opendir('lang/'); 
            while ( $file = readdir($h))
                { 
                if ($file != "." && $file != "..")
                    {
                    if( eregi("lang-([^.]*)", $file, $regs))
                        {
                        if( $file == "lang-".$regs[1].".xml")
                            $this->arrfiles[] = $regs[1]; 
                        }
                    } 
                }
            closedir($h);
            $this->count = count($this->arrfiles);
			}

		function getnextlang()
			{
			static $i = 0;
			if( $i < $this->count)
				{
                $this->langname = $this->arrfiles[$i];
                $this->langval = $this->arrfiles[$i];
                if( $this->userlang == $this->langname )
                    $this->langselected = "selected";
                else
                    $this->langselected = "";
				$i++;
				return true;
				}
			else
				return false;
			}

		}


    $tempa = new tempa();
    $body->babecho(	babPrintTemplate($tempa,"options.html", "changelang"));

    }


function updatePassword($oldpwd, $newpwd1, $newpwd2)
	{
	global $body, $babInstallPath;
	include $babInstallPath."admin/register.php";

	if( empty($oldpwd) || empty($newpwd1) || empty($newpwd2))
		{
		echo $oldpwd ."   ". $newpwd1."     ". $newpwd2;
		$body->msgerror = babTranslate("You must complete all fields !!");
		return;
		}
	if( $newpwd1 != $newpwd2)
		{
		$body->msgerror = babTranslate("Passwords not match !!");
		return;
		}
	if ( strlen($newpwd1) < 6 )
		{
		$body->msgerror = babTranslate("Password must be at least 6 characters !!");
		return;
		}

	userChangePassword( $oldpwd, $newpwd1);
	}


function updateLanguage($lang)
	{
    global $BAB_SESS_USERID;
	if( !empty($lang))
		{
        $db = new db_mysql();
        $req = "update users set lang='".$lang."' where id='".$BAB_SESS_USERID."'";
        $res = $db->db_query($req);
		}
	Header("Location: index.php?tg=options&idx=global");
	}


/* main */
if(!isset($idx))
	{
	$idx = "global";
	}

if( isset($update))
	{
    switch ($update)
        {
        case "password":
        	updatePassword($oldpwd, $newpwd1, $newpwd2);
            break;
        case "lang":
        	updateLanguage($lang);
            break;
        }
	}

switch($idx)
	{

	default:
	case "global":
		//$body->title = babTranslate("");
		$idcal = getCalendarid($BAB_SESS_USERID, 1);
		changePassword();
		changeLanguage();
		$body->addItemMenu("global", babTranslate("Options"), $GLOBALS[babUrl]."index.php?tg=options&idx=global");
		if( (getCalendarId(1, 2) != 0  || getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			$body->addItemMenu("calendar", babTranslate("Calendar"), $GLOBALS[babUrl]."index.php?tg=calopt&idx=options");
		}
		if( mailAccessLevel())
			$body->addItemMenu("options", babTranslate("Mail"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc");
		break;
	}
if(empty($body->msgerror))
	$body->setCurrentItemMenu($idx);

?>