<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/dbutil.php";
include $babInstallPath."utilit/uiutil.php";
include $babInstallPath."utilit/template.php";
include $babInstallPath."utilit/userincl.php";
include $babInstallPath."utilit/calincl.php";

$babMonths = array(1=>babTranslate("January"), babTranslate("February"), babTranslate("March"), babTranslate("April"),
                        babTranslate("May"), babTranslate("June"), babTranslate("July"), babTranslate("August"),
                        babTranslate("September"), babTranslate("October"), babTranslate("November"), babTranslate("December"));

$babDays = array(babTranslate("Sunday"), babTranslate("Monday"),
				babTranslate("Tuesday"), babTranslate("Wednesday"), babTranslate("Thursday"),
				babTranslate("Friday"), babTranslate("Saturday"));

function isEmailValid ($email)
	{
	return (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'. '@'. '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.' . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $email));
	}

function bab_mktime($time)
	{
	$arr = explode(" ", $time);
	$arr0 = explode("-", $arr[0]);
	$arr1 = explode(":", $arr[1]);
	return mktime( $arr1[0],$arr1[1],$arr1[2],$arr0[1],$arr0[2],$arr0[0]);
	}

function bab_strftime($time, $hour=true)
	{
	global $babDays, $babMonths;
	if( $time < 0)
		return "";
	if( !$hour )
		return $babDays[date("w", $time)]." ".date("j", $time)." ".$babMonths[date("n", $time)]." ".date("Y", $time); 
	else
		return $babDays[date("w", $time)]." ".date("j", $time)." ".$babMonths[date("n", $time)]." ".date("Y", $time)." ".date("H", $time).":".date("i", $time); 
	}

function babPrintTemplate( &$class, $file, $section="")
	{
	global $babInstallPath;
	$filepath = "templates/". $file;
	if( !file_exists( $filepath ) )
		{
		$filepath = $babInstallPath.$filepath;
		}
	$tpl = new Template();
	return $tpl->printTemplate($class,$filepath, $section);
	}

function composeName( $firstname, $lastname)
	{
	return trim($firstname . " " . $lastname);
	}

function browserAgent()
	{
	global $HTTP_USER_AGENT;
	//$tab = explode(";", $HTTP_USER_AGENT);
	//if( ereg("([^(]*)([0-9].[0-9]{1,2})",$tab[1],$res))
	if( stristr($HTTP_USER_AGENT, "opera"))
		{
		return "opera";
		}
	if( stristr($HTTP_USER_AGENT, "msie"))
		{
		return "msie";
		}
	if( stristr($HTTP_USER_AGENT, "mozilla"))
		{
		if(stristr($HTTP_USER_AGENT, "gecko"))
			return "nn6";
		else
			return "nn4";
		}
	return "";
	}

function browserVersion()
	{
	global $HTTP_USER_AGENT;
	$tab = explode(";", $HTTP_USER_AGENT);
	if( ereg("([^(]*)([0-9].[0-9]{1,2})",$tab[1],$res))
		{
		return trim($res[2]);
		}
	return 0;
	}

function babTranslate($str)
{
	if( empty($GLOBALS[babLanguage]) || empty($str))
		return $str;
	$filename = "lang/lang-".$GLOBALS[babLanguage].".xml";
	if( !file_exists($filename))
	{
	$file = @fopen($filename, "w");
	fclose($file);
	}
	$file = @fopen($filename, "r");
	$txt = fread($file, filesize($filename));
	fclose($file);
	$reg = "/<".$GLOBALS[babLanguage].">(.*)<\/".$GLOBALS[babLanguage].">/s";
	if( preg_match($reg, $txt, $m))
	{
		$reg = "/<string\s+([^>]*)>(.*?)<\/string>/s";
		preg_match_all($reg, $m[1], $m1);
		for ($i = 0; $i < count($m1[1]); $i++ )
		{
			$reg = "/id=\"(.*?)\"/s";
			if( preg_match($reg, $m1[1][$i], $m2))
			{
				if( $m2[1] == $str)
				{
					return $m1[2][$i];
				}
			}
		}

	$out = "<".$GLOBALS[babLanguage].">".$m[1];
	$out .= "<string id=\"".$str."\">".$str."</string>\r\n";
	$out .= "</".$GLOBALS[babLanguage].">";
	$file = fopen($filename, "w");
	fputs($file, $out);
	fclose($file);
	}
	return $str;
}

class babSection
{
var $title;
var $content;
var $hidden;
var $position;
var $close;
var $boxurl;
var $bbox;

function babSection($title = "Section", $content="<br>This is a sample of content<br>")
{
	global $HTTP_GET_VARS;
	$this->title = $title;
	$this->content = $content;
	$this->hidden = false;
	$this->position = 0;
	$this->close = 0;
	$this->boxurl = "";
	$this->bbox = 0;
}

function getTitle() { return $this->title;}
function setTitle($title) {	$this->title = $title;}
function getContent() {	return $this->content;}

function setContent($content)
{
	$this->content = $content;
}

function getPosition()
{
	return $this->position;
}

function setPosition($pos)
{
	$this->position = $pos;
}

function isVisible()
{

	return !$this->hidden;
}

function show()
{
	$this->hidden = false;
}

function hide()
{
	$this->hidden = true;
}

function close()
{
	$this->close = 1;
}

function open()
{
	$this->close = 0;
}

function printout()
{
	global $babInstallPath, $sectionTitlesBgnd;

	$file = "sectiontemplate.html";
	$filepath = "templates/". $file;
	if( !file_exists( $filepath) )
		{
		$filepath = $babInstallPath."templates/".$file;
		}

	$str = implode("", @file($filepath));

	$tpl = new Template();
	$section = preg_quote($this->title);
	$reg = "/".$tpl->startPatternI."begin\s+".$section."\s+".$tpl->endPatternI."(.*)".$tpl->startPatternI."end\s+".$section."\s+".$tpl->endPatternI."(.*)/s";
	$res = preg_match($reg, $str, $m);
	if( $res )
		$usetpl = true;
	else
		$usetpl = false;

	if( $usetpl )
		return babPrintTemplate($this,$file, $this->title);
	else
		{
		return babPrintTemplate($this,$file, "default");
		}
}

}  /* end of class babSection */


class babSectionTemplate extends babSection
{
var $file;
var $section;
function babSectionTemplate($file, $section="")
	{
	$this->file = $file;
	$this->section = $section;
	}

function printout()
	{
	return babPrintTemplate($this,$this->file, $this->section);		
	}
}

class adminSection extends babSectionTemplate
{
var $array_urls = array();
var $head;
var $foot;
var $key;
var $val;
var $titlebgnd;

function adminSection()
	{
	global $sectionTitlesBgnd;
	$this->babSectionTemplate("adminsection.html", "template");
	$this->array_urls[babTranslate("Sites")] = $GLOBALS[babUrl]."index.php?tg=sites";
	$this->array_urls[babTranslate("Sections")] = $GLOBALS[babUrl]."index.php?tg=sections";
	$this->array_urls[babTranslate("Users")] = $GLOBALS[babUrl]."index.php?tg=users";
	$this->array_urls[babTranslate("Groups")] = $GLOBALS[babUrl]."index.php?tg=groups";
	$this->array_urls[babTranslate("Faq")] = $GLOBALS[babUrl]."index.php?tg=admfaqs";
	$this->array_urls[babTranslate("Topics")] = $GLOBALS[babUrl]."index.php?tg=topics";
	$this->array_urls[babTranslate("Forums")] = $GLOBALS[babUrl]."index.php?tg=forums";
	$this->array_urls[babTranslate("Vacation")] = $GLOBALS[babUrl]."index.php?tg=admvacs";
	$this->array_urls[babTranslate("Calendar")] = $GLOBALS[babUrl]."index.php?tg=admcals";
	$this->array_urls[babTranslate("Mail")] = $GLOBALS[babUrl]."index.php?tg=maildoms&userid=0&bgrp=y";
	$this->title = babTranslate("Administration");
	$this->head = babTranslate("This section is for Administration");
	$this->foot = babTranslate("");
	}

function addUrl()
	{
	static $i = 0;
	if( $i < count($this->array_urls))
		{
		$array_keys = array_keys($this->array_urls);
		$array_vals = array_values($this->array_urls);
		$this->val = $array_vals[$i];
		$this->key = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}
}

class userSection extends babSectionTemplate
{
var $array_urls = array();
var $head;
var $foot;
var $key;
var $val;
var $titlebgnd;

function userSection()
	{
	global $BAB_SESS_USERID, $sectionTitlesBgnd;
	$pgrpid = getPrimaryGroupId($BAB_SESS_USERID);
	$faq = false;
	$db = new db_mysql();
	$req = "select * from faqcat";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if(isAccessValid("faqcat_groups", $row[id]))
			{
			$faq = true;
			break;
			}
		}
	$req = "select * from vacationsman_groups where id_object='$GLOBALS[BAB_SESS_USERID]' or supplier='$GLOBALS[BAB_SESS_USERID]'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 || useVacation($GLOBALS[BAB_SESS_USERID]))
		$vac = true;

	$req = "select * from topics where id_approver='".$GLOBALS[BAB_SESS_USERID]."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		$mtopics = true;
	else
		$mtopics = false;

	$bemail = mailAccessLevel();
	if( $bemail == 1 || $bemail == 2)
		$bemail = true;
	else
		$bemail = false;

	$this->babSectionTemplate("usersection.html", "template");
	if( $mtopics )
		$this->array_urls[babTranslate("Topics")] = $GLOBALS[babUrl]."index.php?tg=topics&userid=".$GLOBALS[BAB_SESS_USERID];
	$this->array_urls[babTranslate("Summary")] = $GLOBALS[babUrl]."index.php?tg=calview";
	$this->array_urls[babTranslate("Options")] = $GLOBALS[babUrl]."index.php?tg=options";
	$this->array_urls[babTranslate("Notes")] = $GLOBALS[babUrl]."index.php?tg=notes";
	if( $faq )
		$this->array_urls[babTranslate("Faq")] = $GLOBALS[babUrl]."index.php?tg=faq";
	if( $vac )
		$this->array_urls[babTranslate("Vacation")] = $GLOBALS[babUrl]."index.php?tg=vacation";
	$idcal = getCalendarid($BAB_SESS_USERID, 1);
	if( (getCalendarId(1, 2) != 0  || getCalendarId($pgrpid, 2) != 0) &&  $idcal != 0 )
		$this->array_urls[babTranslate("Calendar")] = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&calid=".$idcal;
	if( $bemail )
		$this->array_urls[babTranslate("Mail")] = $GLOBALS[babUrl]."index.php?tg=inbox";
	$this->array_urls[babTranslate("Contacts")] = $GLOBALS[babUrl]."index.php?tg=contacts";
	$this->title = babTranslate("User's section");
	$this->head = babTranslate("You are logged as").":<br><center><b>";
	if( !empty($GLOBALS[BAB_SESS_USER]))
		$this->head .= $GLOBALS[BAB_SESS_USER];
	else
		$this->head .= babTranslate("Anonymous");

	$this->head .= "</b></center><br>";
	$this->foot = babTranslate("");
	}

function addUrl()
	{
	static $i = 0;
	if( $i < count($this->array_urls))
		{
		$array_keys = array_keys($this->array_urls);
		$array_vals = array_values($this->array_urls);
		$this->url = $array_vals[$i];
		$this->text = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}
}


class topicsSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $db;
var $arrid = array();
var $newartcount;
var $newcomcount;
var $count;
var $newa;
var $newc;
var $titlebgnd;
var $waitingc;
var $waitinga;
var $waitingcimg;
var $waitingaimg;

function topicsSection()
	{
	global $sectionTitlesBgnd;
	$this->babSectionTemplate("topicssection.html", "template");
	$this->title = babTranslate("Topics");
	$this->head = babTranslate("List of different topics");
	$this->foot = babTranslate("Topics with asterisk have waiting articles or comments ");
	$this->waitingc = babTranslate("Waiting comments");
	$this->waitinga = babTranslate("Waiting articles");
	$this->waitingaimg = babPrintTemplate($this, "config.html", "babWaitingArticle");
	$this->waitingcimg = babPrintTemplate($this, "config.html", "babWaitingComment");
	$this->db = new db_mysql();
	$req = "select * from topics";
	$res = $this->db->db_query($req);
	while( $row = $this->db->db_fetch_array($res))
		{
		if(isAccessValid("topicsview_groups", $row[id]) || isUserApprover($row[id]))
			{
			array_push($this->arrid, $row[id]);
			}
		}
	$this->count = count($this->arrid);
	$this->newartcount = 0;
	$this->newcomcount = 0;
	}

function topicsGetNext()
	{
	global $BAB_SESS_USERID;
	static $i = 0;
	if( $i < $this->count)
		{
		$req = "select * from topics where id='".$this->arrid[$i]."'";
		$res = $this->db->db_query($req);
		$this->newa = "";
		$this->newc = "";
		if( $res && $this->db->db_num_rows($res) > 0)
			{
			$this->arr = $this->db->db_fetch_array($res);
			if( $BAB_SESS_USERID == $this->arr[id_approver])
				{
				$req = "select * from articles where id_topic='".$this->arr[id]."' and confirmed='N'";
				$res = $this->db->db_query($req);
				$this->newartcount = $this->db->db_num_rows($res);

				$req = "select * from comments where id_topic='".$this->arr[id]."' and confirmed='N'";
				$res = $this->db->db_query($req);
				$this->newcomcount = $this->db->db_num_rows($res);
				if( $this->newartcount > 0 )
					{
					$this->newa = "a";
					}
				else
					{
					$this->newa = "";
					}
				if( $this->newcomcount > 0)
					{
					$this->newc = "c";
					}
				else
					{
					$this->newc = "";
					}
				}
			else
				$this->new = "";
			$this->text = $this->arr[category];
			$this->url = $GLOBALS[babUrl]."index.php?tg=articles&topics=".$this->arr[id]."&new=".$this->newartcount."&newc=".$this->newcomcount;
			}
		$i++;
		return true;
		}
	else
		return false;
	}
}

class forumsSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $db;
var $arrid = array();
var $count;
var $waiting;

function forumsSection()
	{
	global $sectionTitlesBgnd;
	$this->babSectionTemplate("forumssection.html", "template");
	$this->title = babTranslate("Forums");
	$this->head = babTranslate("List of different forums");
	//$this->foot = babTranslate("");
	$this->db = new db_mysql();
	$req = "select * from forums";
	$res = $this->db->db_query($req);
	while( $row = $this->db->db_fetch_array($res))
		{
		if(isAccessValid("forumsview_groups", $row[id]))
			{
			array_push($this->arrid, $row[id]);
			}
		}
	$this->count = count($this->arrid);
	$this->foot = "";
	}

function forumsGetNext()
	{
	global $BAB_SESS_USERID;
	static $i = 0;
	if( $i < $this->count)
		{
		$req = "select * from forums where id='".$this->arrid[$i]."'";
		$res = $this->db->db_query($req);
		if( $res && $this->db->db_num_rows($res) > 0)
			{
			$this->arr = $this->db->db_fetch_array($res);
			$this->text = $this->arr[name];
			$this->url = $GLOBALS[babUrl]."index.php?tg=threads&forum=".$this->arr[id];
			$this->waiting = "";
			if( $BAB_SESS_USERID == $this->arr["moderator"])
				{
				$req = "select count(posts.id) as total from posts join threads where threads.active='Y' and threads.forum='".$this->arr[id];
				$req .= "' and posts.confirmed='N' and threads.id=posts.id_thread";
				$res = $this->db->db_query($req);
				$ar = $this->db->db_fetch_array($res);
				if( $ar[total] > 0)
					{
					$this->waiting = "*";
					}
				}
			}
		$i++;
		return true;
		}
	else
		return false;
	}
}

class babMenu
{
var $curItem = "";
var $items = array();

function babMenu()
{
}

function addItem($title, $txt, $url, $enabled=true)
{
	$this->items[$title]["text"] = $txt;
	$this->items[$title]["url"] = $url;
	$this->items[$title]["enabled"] = $enabled;
}

function addItemAttributes($title, $attr)
{
	$this->items[$title]["attributes"] = $attr;
}

function setCurrent($title, $enabled=false)
{
	foreach($this->items as $key => $val)
		{
		if( !strcmp($key, $title))
			{
			$this->curItem = $key;
			$this->items[$key]["enabled"] = $enabled;
			break;
			}
		}
}
}  /* end of class babMenu */

class babBody
{
var $sections = array();
var $menu;
var $msgerror;
var $content;
var $title;
var $message;
var $script;

function babBody()
{
	$this->menu = new babMenu();
	$this->message = "";
	$this->script = "";
	$this->title = "";
	$this->msgerror = "";
	$this->content = "";
}

function resetContent()
{
	$this->content = "";
}

function babecho($txt)
{
	$this->content .= $txt;
}

function loadSection($title, $pos=-1)
{
	global $body;
	$add = false;
	$db = new db_mysql();
	$req = "select * from sections where title='$title'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$add = isAccessValid("sections_groups", $arr[id]);
		}
	if( $add )
		{
		if( $arr[script] == "Y")
			eval("\$arr[content] = \"$arr[content]\";");
		$sec = new babSection($arr[title], $arr[content]);
		if($pos != -1)
			$sec->setPosition($pos);
		else
			$sec->setPosition($arr[position]);
		$body->addSection($sec);
		}
}

function loadSections()
{
	global $body, $LOGGED_IN, $BAB_SESS_USERID;
	$add = false;
	$db = new db_mysql();
	$req = "select * from sections_order order by ordering asc";
	$res = $db->db_query($req);
	while( $arr =  $db->db_fetch_array($res))
		{
		$add = false;
		if( $arr[private] == "Y")
			{
			switch( $arr[id_section] )
				{
				case 1: // admin
					if( isset($LOGGED_IN) && $LOGGED_IN && isUserAdministrator())
						{
						$add = true;
						$sec = new adminSection();
						}
					break;
				case 2: // month
					$add = true;
					$sec = new babMonthA();
					break;
				case 3: // topics
					$sec = new topicsSection();
					if( $sec->count > 0 )
						{
						$add = true;
						}
					break;
				case 4: // Forums
					$sec = new forumsSection();
					if( $sec->count > 0 )
						{
						$add = true;
						}
					break;
				case 5: // user's section
					if( isset($LOGGED_IN) && $LOGGED_IN)
						{
						$add = true;
						$sec = new userSection();
						}
					break;
				}
			}
		else
			{
			$add = isAccessValid("sections_groups", $arr[id_section]);
			if( $add )
				{
				$req = "select * from sections where id='".$arr[id_section]."'";
				$res2 = $db->db_query($req);
				if( $res2 && $db->db_num_rows($res2) > 0)
					{
					$arr2 = $db->db_fetch_array($res2);
					if( $arr2[script] == "Y")
						eval("\$arr2[content] = \"$arr2[content]\";");
					$sec = new babSection($arr2[title], $arr2[content]);
					}
				else
					$add = false;
				}
			}
		if( $add )
			{
			$sec->setPosition($arr[position]);
			$req = "select * from sections_states where id_section='".$arr[id_section]."' and id_user='".$BAB_SESS_USERID."' and private='".$arr[private]."'";
			$res2 = $db->db_query($req);
			$sec->bbox = 1;
			if( $res2 && $db->db_num_rows($res2) > 0)
				{
				$arr2 = $db->db_fetch_array($res2);
				if( $arr2[closed] == "Y")
					{
					$sec->close = 1;
					$sec->boxurl = $GLOBALS[babUrl]."index.php?tg=options&idx=ob&s=".$arr[id_section]."&w=".$arr[private];
					}
				else
					{
					$sec->boxurl = $GLOBALS[babUrl]."index.php?tg=options&idx=cb&s=".$arr[id_section]."&w=".$arr[private];
					$sec->close = 0;
					}
				}
			else if(!empty($BAB_SESS_USERID))
				{
				$sec->boxurl = $GLOBALS[babUrl]."index.php?tg=options&idx=cb&s=".$arr[id_section]."&w=".$arr[private];
				$sec->close = 0;
				}
			else
				{
				$sec->close = 0;
				$sec->bbox = 0;
				}
			$body->addSection($sec);
			}
		}
}

function addSection($sec)
{
	array_push($this->sections, $sec);
}

function showSection($title)
{
	for( $i = 0; $i < count($this->sections); $i++)
		{
		if( !strcmp($this->sections[$i]->getTitle(), $title))
			{
			$this->sections[$i]->show();
			}
		}
}

function hideSection($title)
{
	for( $i = 0; $i < count($this->sections); $i++)
		{
		if( !strcmp($this->sections[$i]->getTitle(), $title))
			{
			$this->sections[$i]->hide();
			}
		}
}

function addItemMenu($title, $txt, $url, $enabled=true)
{
	$this->menu->addItem($title, $txt, $url, $enabled);
}

function addItemMenuAttributes($title, $attr)
{
	$this->menu->addItemAttributes($title, $attr);
}

function setCurrentItemMenu($title, $enabled=false)
{
	$this->menu->setCurrent($title, $enabled);
}

function printout()
{
    if(!empty($this->msgerror))
		{
		$this->message = babPrintTemplate($this,"warning.html", "texterror");
		//return "";
		}
	else if(!empty($this->title))
		{
		$this->message = babPrintTemplate($this,"warning.html", "texttitle");
		}
	return $this->content;
}

}  /* end of class babBody */

class babMonthA  extends babSection
{
var $currentMonth;
var $currentYear;
var $curmonth;
var $curyear;
var $day3;

var $days;
var $daynumber;
var $now;
var $w;
var $event;
var $dayurl;
var $babCalendarStartDay;

var $db;

function babMonthA($month = "", $year = "")
	{
	global $BAB_SESS_USERID;

	$this->db = new db_mysql();

	if(empty($month))
		$this->currentMonth = Date("n");
	else
		{
		$this->currentMonth = $month;
		}
	$this->callback = $callback;
	
	if(empty($year))
		{
		$this->currentYear = Date("Y");
		}
	else
		{
		$this->currentYear = $year;
		}

	if( !empty($BAB_SESS_USERID))
		{
		$req = "select * from caloptions where id_user='".$BAB_SESS_USERID."'";
		$res = $this->db->db_query($req);
		$this->babCalendarStartDay = 0;
		if( $res && $this->db->db_num_rows($res) > 0)
			{
			$arr = $this->db->db_fetch_array($res);
			$this->babCalendarStartDay = $arr[startday];
			}
		}
	else
		$this->babCalendarStartDay = 0;

	}

function printout()
	{
	global $babMonths, $BAB_SESS_USERID;
	$this->curmonth = $babMonths[date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear))];
	$this->curyear = $this->currentYear;
	$this->days = date("t", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$this->daynumber = date("w", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$this->now = date("j");
	$this->w = 0;
	$todaymonth = date("n");
	$todayyear = date("Y");
	$this->idcal = getCalendarId($BAB_SESS_USERID, 1);
	$idgrp = getPrimaryGroupId($BAB_SESS_USERID);
	$this->idgrpcal = getCalendarId($idgrp, 2);
	return babPrintTemplate($this,"montha.html", "");
	}

	function getnextday3()
		{
		global $babDays;
		static $i = 0;
		if( $i < 7)
			{
			$a = $i + $this->babCalendarStartDay;
			if( $a > 6)
				$a -=  7;
			$this->day3 = substr($babDays[$a], 0, 1);
			$i++;
			return true;
			}
		else
			return false;
		}

	function getnextweek()
		{
		if( $this->w < 7)
			{
			$this->w++;
			return true;
			}
		else
			{
			return false;
			}
		}

	function getnextday()
		{
		static $d = 0;
		static $total = 0;
		if( $d < 7)
			{
			$this->bgcolor = 0;
			$this->event = 0;

			$a = $this->daynumber - $this->babCalendarStartDay;
			if( $a < 0)
				$a += 7;

			if( $this->w == 1 &&  $d < $a)
				{
				$this->day = "&nbsp;";
				}
			else
				{
				$total++;

				if( $total > $this->days)
					return false;
				$this->day = $total;
				$mktime = mktime(0,0,0,$this->currentMonth, $total,$this->currentYear);
				$daymin = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
				$daymax = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
				$req = "select * from cal_events where id_cal='".$this->idcal."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
				$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax')";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->event = 1;
					$this->dayurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$total."&month=".$this->currentMonth. "&year=".$this->currentYear. "&calid=".$this->idcal;
					$this->day = "<b>".$total."</b>";
					}
				else
					{
					$req = "select * from cal_events where id_cal='".$this->idgrpcal."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
					$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax')";
					$res = $this->db->db_query($req);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$this->event = 1;
						$this->dayurl = $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewd&day=".$total."&month=".$this->currentMonth. "&year=".$this->currentYear. "&calid=".$this->idgrpcal;
						$this->day = "<b>".$total."</b>";
						}
					}
				if( $total == $this->now && date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear)) == date("n") && $this->currentYear == date("Y"))
					{
					$this->bgcolor = 1;
					}

				}
			if( $total > $this->days)
				{
				return false;
				}
			$d++;
			return true;
			}
		else
			{
			$d = 0;
			return false;
			}
		}
}

function updateUserSettings()
{
	global $BAB_SESS_USERID;
	if( isset($BAB_SESS_USERID) && !empty($BAB_SESS_USERID))
		{
		$db = new db_mysql();

		$req="select * from users where id='$BAB_SESS_USERID'";
		$res=$db->db_query($req);

        if( $res && $db->db_num_rows($res) > 0 )
            {
            $arr = $db->db_fetch_array($res);
            if( $arr[lang] != "")
                {
                $GLOBALS[babLanguage] = $arr[lang];
                }
            /*
            $req="select * from users_log where id_user='$BAB_SESS_USERID'";
            $res=$db->db_query($req);
            if( $res && $db->db_num_rows($res) > 0)
                {
                $arr = $db->db_fetch_array($res);
                if( time() - bab_mktime($arr[dateact]) > $babTimeOut*60)
                    {
                    }
                }
            */
            $req="update users_log set dateact=now() where id_user='$BAB_SESS_USERID'";
            $res=$db->db_query($req);
            }
		}
}



function getSections()
	{
	global $body;
	$body->loadSections();
	}

$font1 = new fontTag("", "verdana, arial, helvetica", 1);
$font2 = new fontTag("white", "verdana, arial, helvetica", 2);
$font3 = new fontTag("", "verdana, arial, helvetica", 3);

$body = new babBody();
$BAB_CONTENT_TITLE = "";
$BAB_HASH_VAR='aqhjlongsmp';
?>
