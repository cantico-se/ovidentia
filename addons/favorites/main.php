<?php

function listbms()
{
	global $babBody;

	class temp
		{
		var $db;
		var $res;
		var $count;
		var $favorites;
		var $bmurl;
		var $bmtext;
		var $bmdelurl;
		var $bmdeltext;

		function temp()
			{
			$this->favorites = "Favorites";
			$this->bmdeltext = "Delete";
			$this->favorites = "Favorites";
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from bm_list where id_owner='".$GLOBALS['BAB_SESS_USERID']."'");
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->bmtext = $arr['description'];
				$this->bmurl = $arr['url'];
				$this->bmdelurl = $GLOBALS['babAddonUrl']."main&idx=del&id=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "bmlist"));
}


function addbm()
	{
	global $babBody;
	class temp
		{
		var $url;
		var $description;
		var $add;

		function temp()
			{
			$this->url = "Url";
			$this->description = "Description";
			$this->add = "Add";
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."main.html", "bmadd"));
	}

/* main */
if( !isset($idx ))
	$idx = "list";

if( isset($add) && $add == "bm")
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("insert into bm_list (url, description, id_owner) values ('".$url."', '".$description."', '".$GLOBALS['BAB_SESS_USERID']."')");
}

if( $idx == "del")
	{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("delete from bm_list where id='".$id."'");
	$idx = "list";
	}

switch($idx)
	{
	case "new":
		$babBody->title = "Add favori";
		addbm();
		$babBody->addItemMenu("list", "Liste", $GLOBALS['babAddonUrl']."main&idx=list");
		$babBody->addItemMenu("new", "Add", $GLOBALS['babAddonUrl']."main&idx=new");
		break;

	case "list":
	default:
		$babBody->title = "Your favorites";
		listbms();
		$babBody->addItemMenu("list", "Liste", $GLOBALS['babAddonUrl']."main&idx=list");
		$babBody->addItemMenu("new", "Add", $GLOBALS['babAddonUrl']."main&idx=new");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>