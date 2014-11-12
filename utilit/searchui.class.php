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


class Func_SearchUi extends bab_functionality
{
    public function getDescription()
    {
        return bab_translate('Search user interface');
    }
    
    
    /**
     * Mandatory method to get the search interface
     * @param string $realmName Optional serach realm name
     * @param string $keyword Optional search keyword(s) to initialize search results
     * @return string
     */
    public function getUrl($realmName = null, $keyword = null) {
        throw new Exception('Not implemented');
    }
    
    
    
    /**
     * Get search form as HTML string
     * @param string bab_SearchRealm
     * @return string
     */
    public function getSearchFormHtml(bab_SearchRealm $realm = null) {
        throw new Exception('Not implemented');
    }
    
    
    /**
     * Optional article url to the preview
     * @param int $id_article
     * @param string $keyword   A keyword to highlight in the preview
     * @return string
     */
    public function getArticlePopupUrl($id_article, $keyword) {
        return null;
    }
    
    /**
     * Optional contact url to the preview
     * @param int $id_contact
     * @param string $keyword   A keyword to highlight in the preview
     * @return string
     */
    public function getContactPopupUrl($id_contact, $keyword) {
        return null;
    }
    
    /**
     * Optional directory entry url to the preview popup
     * @param int $id_entry
     * @param string $keyword   A keyword to highlight in the preview
     * @return string
     */
    public function getDirEntryPopupUrl($id_entry, $keyword) {
        return null;
    }
    
    
    /**
     * Optional filemanager file url to the preview popup
     * @param int $id_file 
     * @param string $keyword   A keyword to highlight in the preview
     * @return string
     */
    public function getFilePopupUrl($id_file, $keyword) {
        return null;
    }
}
