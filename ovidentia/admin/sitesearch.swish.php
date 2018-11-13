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
 * Get options for the swish-e search engine
 * @return string
 */
function site_menu11($id_site)
{

    class site_menu11_template
    {
        /**
         * @param string $id_site
         */
        function __construct($id_site)
        {
            $db = bab_getDB();

            $this->item = bab_rp('item');
            $this->t_record = bab_translate("Record");
            $this->t_swishcmd = bab_translate("Swish-e command (swish-e.exe)");
            $this->t_pdftotext = bab_translate("Command to transform pdf documents into text (pdftotext.exe)");
            $this->t_xls2csv = bab_translate("Command to transform excel documents into csv (xls2csv.exe)");
            $this->t_catdoc = bab_translate("Command to transform word documents into text (catdoc.exe)");
            $this->t_docx2txt = bab_translate("Command to transform docx documents into text (docx2txt.exe)");
            $this->t_unzip = bab_translate("Command to transform open office documents into xml (unzip.exe)");


            if (isset($_POST['item'])) {
                $this->arr = $_POST;
            } else {
                $res = $db->db_query("SELECT * FROM " . BAB_SITES_SWISH_TBL . " WHERE id_site='" . $id_site . "'");
                if (! $this->arr = $db->db_fetch_assoc($res)) {
                    $this->arr = array(
                        'swishcmd' => '',
                        'pdftotext' => '',
                        'xls2csv' => '',
                        'catdoc' => '',
                        'docx2txt' => '',
                        'unzip' => ''
                    );
                }
            }

            array_walk($this->arr, create_function('&$v,$k', '$v = bab_toHtml($v);'));
        }
    }

    $template = new site_menu11_template($id_site);
    return bab_printTemplate($template, 'sitesearch.swish.html');
}



function record_site_menu11($id_site)
{
    array_walk($_POST, create_function('&$v,$k', '$v = $GLOBALS[\'babDB\']->db_escape_string($v);'));

    $db = bab_getDB();
    $res = $db->db_query("SELECT id FROM " . BAB_SITES_SWISH_TBL . " WHERE id_site=" . $db->quote($id_site));

    if ($db->db_num_rows($res) > 0) {
        $db->db_query("UPDATE " . BAB_SITES_SWISH_TBL . " SET
                swishcmd = " . $db->quote(bab_pp('swishcmd')) . ",
                pdftotext = " . $db->quote(bab_pp('pdftotext')) . ",
                xls2csv = " . $db->quote(bab_pp('xls2csv')) . ",
                catdoc = " . $db->quote(bab_pp('catdoc')) . ",
                docx2txt = " . $db->quote(bab_pp('docx2txt')) . ",
                unzip = " . $db->quote(bab_pp('unzip')) . "
            WHERE id_site=" . $db->quote($id_site));
    } else {
        $db->db_query("INSERT INTO " . BAB_SITES_SWISH_TBL . "
            (id_site, swishcmd, pdftotext, xls2csv, catdoc, docx2txt, unzip)
            VALUES
            (
                " . $db->quote($id_site) . ",
                " . $db->quote(bab_pp('swishcmd')) . ",
                " . $db->quote(bab_pp('pdftotext')) . ",
                " . $db->quote(bab_pp('xls2csv')) . ",
                " . $db->quote(bab_pp('catdoc')) . ",
                " . $db->quote(bab_pp('docx2txt')) . ",
                " . $db->quote(bab_pp('unzip')) . "
            )
        ");
    }
}
