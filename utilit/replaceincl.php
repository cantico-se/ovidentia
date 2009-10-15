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


/**
 * Replacement object
 * @see bab_replace_get	to get a bab_replace instance
 * This object replace all $XXXX() in html string
 * @since 6.4.0
 */
class bab_replace {

	/**
	* @access private
	*/
	var $ext_url;
	var $ignore_macro = array();
	

	
	/**
	* @static
	* @access private
	*/
	function _var(&$txt,$var,$new)
		{
		$txt = preg_replace("/".preg_quote($var,"/")."/", $new, $txt);
		}
		
	/**
	* @access private
	*/
	function _make_link($url,$text,$popup = 0,$url_popup = false,$classname = false)
		{
		if (isset($this->ext_url)) {
			$url = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($url);
			$popup = 0;
			}
		if ($classname !== false) {
			$classname = 'class="' . $classname . '"';
		}
		$url = ($popup == 1 || $popup == true) && $url_popup != false ? $url_popup : $url;
		if ($popup == 1 || $popup === true)
			{
			return '<a ' . $classname . ' href="'.bab_toHtml($url).'" onclick="bab_popup(this.href);return false;">'.$text.'</a>';
			}
		elseif ($popup == 2) {
			return '<a ' . $classname . ' target="_blank" href="'.bab_toHtml($url).'">'.$text.'</a>';
			}
		else {
			return '<a ' . $classname . ' href="'.bab_toHtml($url).'">'.$text.'</a>';
			}
		}
		
	/**
	* @access public
	* @param	string	$macro			ex : OVML
	*/
	function addIgnoreMacro($macro) {
		$this->ignore_macro[$macro] = 1;
	}
	
	/**
	* @access public
	* @param	string	$macro			ex : OVML
	*/
	function removeIgnoreMacro($macro) {
		unset($this->ignore_macro[$macro]);
	}
	
	/**
	 * Test ignored macro, a macro is ignored if the test is done more than 5 time
	 * @access private
	 * @param	string	$macro			ex : OVML
	 * @return	boolean
	 */
	function isMacroIgnored($macro, $params) {
		static $ignore_stack = array();
		
		if (isset($this->ignore_macro[$macro])) {
			
			if (isset($ignore_stack[$macro.$params])) {
				$ignore_stack[$macro.$params]++;
			} else {
				$ignore_stack[$macro.$params] = 1;
			}
			
			
			return $ignore_stack[$macro.$params] > 5;
		}
			
		return false;
	}
	


	/**
	* external links for email
	* @access public
	* @param	string	&$txt
	*/
	function email(&$txt)
		{
		$this->ext_url = true;
		$this->ref($txt);
		unset($this->ext_url);
		}


	/**
	* replace macro in string
	* @access public
	* @param	string	&$txt
	*/
	function ref(&$txt)
	{
	global $babBody, $babDB;
	
	$reg = "/\\\$([A-Z]*?)\((.*?)\)/";
	if (preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			if (!$this->isMacroIgnored($m[1][$k], $m[2][$k]))
				{
				$var = $m[0][$k];
				$varname = $m[1][$k];
				$param = explode(',',$m[2][$k]);

				if (count($param) > 0)
					{
					switch ($varname)
						{
						case 'ARTICLEPOPUP':
							$popup = true;
						case 'ARTICLE':
							$title_topic = count($param) > 1 ? trim($param[0],'"') : false;
							$title_object = count($param) > 1 ? trim($param[1],'"') : trim($param[0],'"');
							if (!isset($popup)) $popup = false;
							if ($title_topic)
								{
								$res = $babDB->db_query("select a.id,a.id_topic,a.title,a.restriction from ".BAB_TOPICS_TBL." t, ".BAB_ARTICLES_TBL." a where t.category='".$babDB->db_escape_string($title_topic)."' AND a.id_topic=t.id AND a.title='".$babDB->db_escape_string($title_object)."'");
								if( $res && $babDB->db_num_rows($res) > 0)
									$arr = $babDB->db_fetch_array($res);
								else
									$title_topic = false;
								}
							if (!$title_topic)
								{
								$res = $babDB->db_query("select id,id_topic,title,restriction from ".BAB_ARTICLES_TBL." where title LIKE '%".$babDB->db_escape_like($title_object)."%'");
								if( $res && $babDB->db_num_rows($res) > 0)
									$arr = $babDB->db_fetch_array($res);
								}
							if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']) && bab_articleAccessByRestriction($arr['restriction']))
								{
								$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id']."&topics=".$arr['id_topic'],$title_object,$popup,$GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id']);
								}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'ARTICLEID':
							if (!is_numeric($param[0]))
								break;
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$popup = isset($param[2]) ? $param[2] : false;
							$connect = isset($param[3]) ? $param[3] : false;
							$res = $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								$title_object = empty($title_object) ? $arr['title'] : $title_object;
								if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']) && ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])))
									{
									$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id']."&topics=".$arr['id_topic'],$title_object,$popup,$GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id'],'bab-article-'.$arr['id']);
									}
								elseif (!$GLOBALS['BAB_SESS_LOGGED'] && $connect)
									{
									$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id']."&topics=".$arr['id_topic']),$title_object,0,false,'bab-article-'.$arr['id']);
									}

								}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'ARTICLEFILEID':
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$res = $babDB->db_query("select aft.*, at.id_topic, at.restriction from ".BAB_ART_FILES_TBL." aft left join ".BAB_ARTICLES_TBL." at on aft.id_article=at.id where aft.id='".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']) && ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])))
									{
									$title_object = empty($title_object) ? (empty($arr['description'])? $arr['name']: $arr['description']) : $title_object;
									$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$arr['id_topic']."&idf=".$arr['id'],$title_object);
									}

								}
							bab_replace::_var($txt,$var,$title_object);
							break;

						case 'CONTACT':
							$title_object = $param[0].' '.$param[1];
							$res = $babDB->db_query("select * from ".BAB_CONTACTS_TBL." where  owner='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and firstname LIKE '%".$babDB->db_escape_string($param[0])."%' and lastname LIKE '%".$babDB->db_escape_like($param[1])."%'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								$title_object = $this->_make_link($GLOBALS['babUrlScript'].'?tg=contact&idx=modify&item='.$arr['id'].'&bliste=0',$title_object,true);
								}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'CONTACTID':
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$res = $babDB->db_query("select * from ".BAB_CONTACTS_TBL." where  owner='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and id= '".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								$title_object = empty($title_object) ? bab_composeUserName($arr['firstname'],$arr['lastname']) : $title_object;
								$title_object = $this->_make_link($GLOBALS['babUrlScript'].'?tg=contact&idx=modify&item='.$arr['id'].'&bliste=0',$title_object,true);
								}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'DIRECTORYID':
							$id_object = trim($param[0]);
							$title_object = isset($param[1]) ? $param[1] : '';
							$res = $babDB->db_query("select id,sn,givenname,id_directory from ".BAB_DBDIR_ENTRIES_TBL." where id= '".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								if( $arr['id_directory'] == 0  )
									{
									$iddir = isset($param[2]) ? trim($param[2]): '' ;
									}
								else
									{
									$iddir = $arr['id_directory'];
									}

								if ( $iddir && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $iddir))
									{
									$title_object = empty($title_object) ? bab_composeUserName($arr['sn'],$arr['givenname']) : $title_object;
									$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".((int) $iddir)."&userid=".$arr['id'],$title_object,true);
									}
								}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'FAQ':
							$title_object = $param[1];
							$res = $babDB->db_query("select * from ".BAB_FAQCAT_TBL." where category='".$babDB->db_escape_string($param[0])."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['id']))
									{
									$req = "select * from ".BAB_FAQQR_TBL." where question='".$babDB->db_escape_string($param[1])."'";
									$res = $babDB->db_query($req);
									if( $res && $babDB->db_num_rows($res) > 0)
										{
										$arr = $babDB->db_fetch_array($res);
										$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&idcat=".$arr['idcat']."&idq=".$arr['id'],$title_object,true);
										}
									}
								}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'FAQID':
							$id_object = (int) $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$popup = isset($param[2]) ? $param[2] : false;
							$res = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where id='".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['idcat']))
									{
									$title_object = empty($title_object) ? $arr['question'] : $title_object;
									$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=faq&idx=listq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$id_object."#".$id_object,$title_object,$popup,$GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&idcat=".$arr['idcat']."&idq=".$id_object);
									
									}
								}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'FILE':
							$id_object = (int) $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
							$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($id_object)."' and state='' and confirmed='Y'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								if (bab_isAccessFileValid($arr['bgroup'], $arr['id_owner']))
									{
									$title_object = empty($title_object) ? $arr['name'] : $title_object;
									if( bab_getFileContentDisposition() == '')
										{
										$inl = empty($GLOBALS['files_as_attachment']) ? '&inl=1' : '';
										}
									else
										{
										$inl ='';
										}

										$sPath = removeEndSlah($arr['path']);
										$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=fileman&sAction=getFile".$inl."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($sPath)."&file=".urlencode($arr['name']).'&idf='.$arr['id'],$title_object,2,false,'bab-file-' . $arr['id']);
									}
								}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'FOLDER':
							$id_object = (int) $param[0];
							$path_object = isset($param[1]) ? $param[1] : '';
							$title_object = isset($param[2]) ? $param[2] : '';
							
							$res = $babDB->db_query("select id,folder from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($id_object)."' and active='Y'");
							if( $res && $babDB->db_num_rows($res) > 0)
							{
								$arr = $babDB->db_fetch_array($res);
								require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
								
								$oFmFolder = BAB_FmFolderHelper::getFmFolderById($arr['id']);
								if (!is_null($oFmFolder))
								{
									$oOwnerFmFolder = null;
									$sPath = $oFmFolder->getName() . ((mb_strlen(trim($path_object)) > 0 ) ? '/' . $path_object : '');
									
									$iOldDelegation = bab_getCurrentUserDelegation();
									bab_setCurrentUserDelegation($oFmFolder->getDelegationOwnerId());

									BAB_FmFolderHelper::getInfoFromCollectivePath($sPath, $oFmFolder->getId(), $oOwnerFmFolder);

									bab_setCurrentUserDelegation($iOldDelegation);
									
									if(!is_null($oOwnerFmFolder) && (bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $oOwnerFmFolder->getId()) || bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oOwnerFmFolder->getId())))
									{
										$title_object = empty($title_object) ? $arr['folder'] : $title_object;
										$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$arr['id']."&gr=Y&path=".urlencode($sPath),$title_object);
									}
								}
							}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'LINKPOPUP':
							$url_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : $url_object;
							$popup = isset($param[2]) ? $param[2] : 2;
							$title_object = $this->_make_link($GLOBALS['babUrlScript']."?tg=link&idx=popup&url=".urlencode($url_object),$title_object, $popup);
							bab_replace::_var($txt,$var,$title_object);
							break;

						case 'VAR':
							$title_object = $param[0];
							switch($title_object)
								{
								case "BAB_SESS_USERID":
								case "BAB_SESS_NICKNAME":
								case "BAB_SESS_USER":
								case "BAB_SESS_FIRSTNAME":
								case "BAB_SESS_LASTNAME":
								case "BAB_SESS_EMAIL":
									$title_object = $GLOBALS[$title_object];
									break;
								case "babslogan":
								case "adminemail":
								case "adminname":
									$title_object = $babBody->babsite[$title_object];
									break;
								default:
									$title_object = '';
									break;
								}
							bab_replace::_var($txt,$var,$title_object);
							break;
							
						case 'OVML':
							$args = array();
							if( ($cnt = count($param)) > 1 )
							{
								for( $i=1; $i < $cnt; $i++)
								{
									$tmp = explode('=', $param[$i]);
									if( is_array($tmp) && count($tmp) == 2 )
										{
										$args[trim($tmp[0])] = trim($tmp[1], '"');
										}
								}
							}
							bab_replace::_var($txt,$var,preg_replace("/\\\$OVML\(.*\)/","",trim(bab_printOvmlTemplate($param[0], $args))));
							break;


						case 'OVMLCACHE':
							$args = array();
							if( ($cnt = count($param)) > 1 )
							{
								for( $i=1; $i < $cnt; $i++)
								{
									$tmp = explode('=', $param[$i]);
									if( is_array($tmp) && count($tmp) == 2 )
										{
										$args[trim($tmp[0])] = trim($tmp[1], '"');
										}
								}
							}
							bab_replace::_var($txt,$var,preg_replace("/\\\$OVMLCACHE\(.*\)/","",trim(bab_printCachedOvmlTemplate($param[0], $args))));
							break;					
							
						}
					}
				}
			else
				{
				bab_replace::_var($txt,$m[1][$k],'');
				}
			}
		}
	}
}

