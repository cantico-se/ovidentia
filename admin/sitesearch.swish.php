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


function site_menu11($id_site)
{
	global $babBody;

	class temp
		{
		function temp($id_site)
			{
			$this->db = &$GLOBALS['babDB'];

			$this->item = bab_rp('item');
			$this->t_record = bab_translate("Record");
			$this->t_swishcmd = bab_translate("Swish-e command (swish-e.exe)");
			$this->t_pdftotext = bab_translate("Command to transform pdf documents into text (pdftotext.exe)");
			$this->t_xls2csv = bab_translate("Command to transform excel documents into csv (xls2csv.exe)");
			$this->t_catdoc = bab_translate("Command to transform word documents into text (catdoc.exe)");
			$this->t_unzip = bab_translate("Command to transform open office documents into xml (unzip.exe)");
			
			
			if (isset($_POST['item']))
				{
				$this->arr = $_POST;
				}
			else
				{
				$res = $this->db->db_query("SELECT * FROM ".BAB_SITES_SWISH_TBL." WHERE id_site='".$id_site."'");
				if (!$this->arr = $this->db->db_fetch_assoc($res))
					{
					$this->arr = array(
							'swishcmd' => '',
							'pdftotext' => '',
							'xls2csv' => '',
							'catdoc' => '',
							'unzip' => ''
						);
					}
				}

			array_walk($this->arr, create_function('&$v,$k','$v = bab_toHtml($v);'));
			}
		}

	$temp = new temp($id_site);
	$babBody->babecho(bab_printTemplate($temp,"sitesearch.swish.html"));
}



function record_site_menu11($id_site)
{

	array_walk($_POST, create_function('&$v,$k','$v = $GLOBALS[\'babDB\']->db_escape_string($v);'));
	
	$db = &$GLOBALS['babDB'];
	$res = $db->db_query("SELECT id FROM ".BAB_SITES_SWISH_TBL." WHERE id_site='".$id_site."'");

	if ($db->db_num_rows($res) > 0)
		{
		$db->db_query("UPDATE ".BAB_SITES_SWISH_TBL." SET  
				swishcmd = '".$_POST['swishcmd']."',
				pdftotext = '".$_POST['pdftotext']."',
				xls2csv = '".$_POST['xls2csv']."',
				catdoc = '".$_POST['catdoc']."',
				unzip = '".$_POST['unzip']."' 
			WHERE id_site='".$id_site."'");
		}
	else
		{
		$db->db_query("INSERT INTO ".BAB_SITES_SWISH_TBL." 
				(id_site, swishcmd, pdftotext, xls2csv, catdoc, unzip)
			VALUES
				(
					'".$id_site."',
					'".$_POST['swishcmd']."',
					'".$_POST['pdftotext']."',
					'".$_POST['xls2csv']."',
					'".$_POST['catdoc']."',
					'".$_POST['unzip']."'
				)
			");

		}
}
?>