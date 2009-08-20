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
include "base.php";



/**
 * HTMLarea javascript file
 */
function editor_js($uid)
	{
	class temp
		{
		function temp($uid)
			{
			$this->uid = $uid;

			$this->t_css = bab_translate("CSS styles");
			$this->t_bab_image = bab_translate("Insert image");
			$this->t_bab_file = bab_translate("Insert file link");
			$this->t_bab_article = bab_translate("Insert article link");
			$this->t_bab_faq = bab_translate("Insert FAQ link");
			$this->t_bab_ovml = bab_translate("Insert OVML file");
			$this->t_bab_contdir = bab_translate("Insert contact link");
			$this->t_killword = bab_translate("Clear MSOffice tags");
			$this->t_removeformat = bab_translate("Remove formatting");
			$this->t_bab_unlink = bab_translate("Remove link");
			$this->t_font = bab_translate("Font");
			$this->t_size = bab_translate("Size");
			$this->t_format = bab_translate("Format");
			$this->t_heading = bab_translate("Heading");
			$this->t_paragraph = bab_translate("Paragraph");
			$this->t_address = bab_translate("Address");
			$this->t_formated = bab_translate("Formated");
			$this->t_bab_functions = bab_translate("Ovidentia functions");
	
			$this->linebreak = "\n";
			if ($GLOBALS['babSkin'] == "ovidentia")
				{
				$filename = $GLOBALS['babInstallPath']."skins/".$GLOBALS['babSkin']."/styles/".$GLOBALS['babStyle'];
				}
			else
				{
				$filename = "skins/".$GLOBALS['babSkin']."/styles/".$GLOBALS['babStyle'];
				}

			$this->arr_classname = false;
			$this->css_styles = '';

			if (is_file($filename))
				{
				$buffer = '';
				$fp = fopen($filename, "rb");
				while (!feof($fp)) {
					$buffer .= fread($fp, 8192);
				}
			    fclose($fp);

				$fcontents = preg_replace("/\r?\n|\r/", '', $buffer);
				$this->css_styles = $fcontents;

				$this->css_styles .= "body { padding:.5em; background-color:#fff; color:#000; }";
				
				$values = array();

				preg_match("/\/\*BAB_EDITOR_PAGE_BEGIN\*\/.*\{(.*)\}\s+\/\*BAB_EDITOR_PAGE_END\*\//s",  $fcontents, $m);

				if (isset($m[1]))
					$this->css_styles .= "body {".$m[1]."}";

				$this->css_styles = str_replace("'","\'",$this->css_styles);
				
				preg_match("/\/\*BAB_EDITOR_CSS_BEGIN\*\/(.*)\/\*BAB_EDITOR_CSS_END\*\//s",  $fcontents, $m);
				if (isset($m[1]))
					{
					$this->css_styles .= $m[1];
					if (preg_match_all("/\.([a-zA-Z0-9\_\-]*?)\s*\{/", $m[1], $m2))
						{
						$values = array_merge($values,$m2[1]);
						}
					}
				if (count($values) > 0)
					$this->arr_classname = $values;
				}
			}

		function getnextbodyclass()
			{
				
			if ($this->arr_classname !== false && list(,$this->classname) = each($this->arr_classname))
				{
				$this->text = str_replace('_',' ',$this->classname);
				return true;
				}
			else
				return false;
			}
		}


	header("Content-type: application/x-javascript");
	
	$temp =& new temp($uid);
	die(bab_printTemplate($temp, 'editor.js'));

	}


/**
 * HTMLarea css file
 */
function get_css_style_list()
	{
	if ($GLOBALS['babSkin'] == "ovidentia")

		$filename = $GLOBALS['babInstallPath']."skins/".$GLOBALS['babSkin']."/styles/".$GLOBALS['babStyle'];
	else
		$filename = "skins/".$GLOBALS['babSkin']."/styles/".$GLOBALS['babStyle'];

	$str = implode('',file($filename));

	$get=false;
	$counter = 0;

	header("Content-type: text/css");

	preg_match("/\/\*BAB_EDITOR_CSS_BEGIN\*\/(.*)\/\*BAB_EDITOR_CSS_END\*\//s",  $str, $m);
	preg_match("/\/\*BAB_EDITOR_PAGE_BEGIN\*\/.*\{(.*)\}\s+\/\*BAB_EDITOR_PAGE_END\*\//s",  $str, $n);

	if (isset($n[1]))
		{
		echo "body {".$n[1]."}\n";
		}
		
	if (isset($m[1]))
		{
		echo $m[1];
		}


	die();
	}



/* main */


switch($idx)
	{
	case "js":
		editor_js($_GET['uid']);
		break;
	case "css":
		get_css_style_list();
	default:
		break;
	}


?>