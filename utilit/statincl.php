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

class bab_WebStatEvent
{
	var $idevt;
	var $ip;
	var $host;
	var $referer;
	var $client;
	var $info;
	var $tg;

	function bab_WebStatEvent()
	{
		register_shutdown_function(array(&$this, 'updateInfo'));
		$this->idevt = 0;
		$this->info = array();

		if ($_SERVER["REMOTE_ADDR"])
			{
			$this->host = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
			$this->ip = $_SERVER["REMOTE_ADDR"];
			}
		else if (getenv("HTTP_X_FORWARDED_FOR"))
			{
			$this->host = gethostbyaddr(getenv("HTTP_X_FORWARDED_FOR"));
			$this->ip = getenv("HTTP_X_FORWARDED_FOR");
			}
		else if (getenv("REMOTE_HOST"))
			{
			$this->host = getenv("REMOTE_HOST");
			$this->ip = gethostbyname(getenv("REMOTE_HOST"));
			}
		if (!$this->ip)
			{
			$this->ip = "unknown";
			}
		$this->referer = isset($_SERVER["HTTP_REFERER"])? $_SERVER["HTTP_REFERER"]: '';
		$this->client = $_SERVER["HTTP_USER_AGENT"];
		$this->url = $_SERVER["REQUEST_URI"];
		$this->tg = isset($GLOBALS['tg'])?$GLOBALS['tg']:'';
		if( $this->tg != 'version' )
			{
			$this->logEvent();
			}
		$this->module($this->tg); 
		$GLOBALS['babUrlStatInfo'] = $GLOBALS['babUrlScript']."?tg=statinfo&statevt=".$this->idevt."";
	}

	function logEvent()
	{
		global $babBody, $babDB, $BAB_SESS_USERID;
		$babDB->db_query("insert into ".BAB_STATS_EVENTS_TBL." (evt_time, evt_tg, evt_id_site, evt_referer, evt_ip, evt_host, evt_client, evt_url, evt_session_id, evt_iduser) values (now(), '".addslashes($this->tg)."', '0', '".addslashes($this->referer)."', '".$this->ip."', '".addslashes($this->host)."', '".addslashes($this->client)."', '".addslashes($this->url)."', '".session_id()."', '0')");
		$this->idevt = $babDB->db_insert_id();
	}

	function addInfo($var, $value)
	{
		$this->info[$var] = $value;
	}

	function addArrayInfo($var, $value)
	{
		if( !isset($this->variables[$var]) || count($this->variables[$var]) == 0 || !in_array($value, $this->variables[$var]))
			{
			$this->info[$var][] = $value;
			}
	}

	function module($name) /* module name: script file */
	{
		$this->addArrayInfo('bab_module', $name);
	}

	function addon($name) /* addon name */
	{
		$this->addArrayInfo('bab_addon', $name);
	}

	function page($name) /* if you want to tag this page */
	{
		$this->addArrayInfo('bab_page', $name);
	}

	function addArticle($id) /* articles read from this page */
	{
		$this->addArrayInfo('bab_articles', $id);
	}
	
	function addOvmlFile($file) /* ovml files parsed */
	{
		$this->addArrayInfo('bab_ovml', $file);
	}

	function addArticleFile($id) /* documents associated downloaded from this request */
	{
		$this->addArrayInfo('bab_artfiles', $id);
	}

	function addFolder($id) /* folder ( FM ) */
	{
		$this->addArrayInfo('bab_fmfolders', $id);
	}

	function addFilesManagerFile($id) /* file ( FM ) downloaded from this page */
	{
		$this->addArrayInfo('bab_fmfiles', $id);
	}

	function addForum($id) /* forum */
	{
		$this->addArrayInfo('bab_forums', $id);
	}

	function addForumPost($id) /* posts read from this page */
	{
		$this->addArrayInfo('bab_posts', $id);
	}

	function addForumThread($id) /* thread view from this page */
	{
		$this->addArrayInfo('bab_threads', $id);
	}

	function addFaq($id) /* faq */
	{
		$this->addArrayInfo('bab_faqs', $id);
	}

	function addFaqsQuestion($id) /* question read from this page */
	{
		$this->addArrayInfo('bab_faqsqr', $id);
	}

	function addSearchWord($word) /* word to search */
	{
		$this->addArrayInfo('bab_searchword', addslashes($word));
	}

	function addExternalLink($url) /* external links */
	{
		$this->addArrayInfo('bab_xlinks', $url);
	}

	function addDatabaseDirectory($id) /* database directory */
	{
		$this->addArrayInfo('bab_dbdirectories', $id);
	}

	function addLdapDirectory($id) /* ldap directory */
	{
		$this->addArrayInfo('bab_ldapdirectories', $id);
	}

	function updateInfo()
	{
		global $babBody, $babDB, $BAB_SESS_USERID;

		if( $this->idevt)
			{
			if( !empty($BAB_SESS_USERID))
				{
				$iduser = $BAB_SESS_USERID;
				}
			else
				{
				$iduser = 0;
				}
			$babDB->db_query("update ".BAB_STATS_EVENTS_TBL." set evt_id_site='".$babBody->babsite['id']."', evt_iduser='".$iduser."', evt_info='".serialize($this->info)."' where id='".$this->idevt."'");
			}
	}
}

if( isset($tg) && $tg == "statinfo" )
{
	$res = $babDB->db_query("select evt_info from ".BAB_STATS_EVENTS_TBL." where id='".$statevt."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$arr = $babDB->db_fetch_array($res);
		$variables = unserialize($arr['evt_info']);
		$arr = parse_url($_SERVER["REQUEST_URI"]);
		$query = explode('&', $arr['query']);
		for( $i = 0; $i < count($query); $i++ )
		{
			$args = explode('=', $query[$i]);	
			if( !empty($args[0]) && !empty($args[1]))
			{
				switch($args[0])
				{
					case "statevt":
					case "tg":
						break;
					default:
						if( substr($args[0], 0, 4) == "bab_" )
							{	
							$arr = explode(',', $args[1]);
							for( $k = 0; $k < count($arr); $k++ )
								{
								if( !isset($variables[$args[0]]) || count($variables[$args[0]]) == 0 || !in_array($arr[$k], $variables[$args[0]]))
									{
									$variables[$args[0]][] = $arr[$k];
									}
								}
							}
						else
							{
							$variables[$args[0]] = $args[1];
							}
						break;
				}
			}
		}

		if( count($variables) > 0 )
		{
			$babDB->db_query("update ".BAB_STATS_EVENTS_TBL." set evt_info='".serialize($variables)."' where id='".$statevt."'");
		}
	
	}
  exit;
}
?>