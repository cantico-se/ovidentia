<?php

function favorites_list()
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
			$this->favorites = bab_translate("Favorites", "favorites");
			$this->bmdeltext = bab_translate("Delete", "favorites");
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from favorites_list where id_owner='".$GLOBALS['BAB_SESS_USERID']."'");
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


function favorites_add()
	{
	global $babBody;
	class temp
		{
		var $url;
		var $description;
		var $add;

		function temp()
			{
			$this->url = bab_translate("Url", "favorites");
			$this->description = bab_translate("Description", "favorites");
			$this->add = bab_translate("Add", "favorites");
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
	$res = $db->db_query("insert into favorites_list (url, description, id_owner) values ('".$url."', '".$description."', '".$GLOBALS['BAB_SESS_USERID']."')");
}

if( $idx == "del")
	{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("delete from favorites_list where id='".$id."'");
	$idx = "list";
	}

switch($idx)
	{
	case "new":
		$babBody->title = bab_translate("Add favorite", "favorites");
		favorites_add();
		$babBody->addItemMenu("list", bab_translate("List", "favorites"), $GLOBALS['babAddonUrl']."main&idx=list");
		$babBody->addItemMenu("new", bab_translate("Add", "favorites"), $GLOBALS['babAddonUrl']."main&idx=new");
		break;

	case "list":
	default:
		$babBody->title = bab_translate("Your favorites", "favorites");
		favorites_list();
		$babBody->addItemMenu("list", bab_translate("List", "favorites"), $GLOBALS['babAddonUrl']."main&idx=list");
		$babBody->addItemMenu("new", bab_translate("Add", "favorites"), $GLOBALS['babAddonUrl']."main&idx=new");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>

