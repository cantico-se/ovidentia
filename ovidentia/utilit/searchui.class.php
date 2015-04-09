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
    public function getUrl($realmName = null, $keyword = null)
    {
        throw new Exception('Not implemented');
    }
    
    
    /**
     * Get TG value for the search functionality (search form page)
     * @return string
     */
    public function getTg()
    {
        require_once dirname(__FILE__).'/urlincl.php';
        $url = new bab_url($this->getUrl());
        
        return $url->tg;
    }
    
    
    
    /**
     * Get search form as HTML string
     * @param string bab_SearchRealm
     * @return string
     */
    public function getSearchFormHtml(bab_SearchRealm $realm = null)
    {
        throw new Exception('Not implemented');
    }
    
    
    /**
     * Create the search form criteria from the submited form result
     * @param bab_SearchRealm $realm
     * @return bab_SearchCriteria
     */
    public function getSearchFormCriteria(bab_SearchRealm $realm)
    {
        throw new Exception('Not implemented');
    }
    
    
    /**
     * get a criteria without field criterions from a search query made with the form generated with the method <code>getSearchFormHtml()</code>
     *
     * @param bab_SearchRealm $realm
     *
     * @return bab_SearchCriteria
     */
    public function getSearchFormFieldLessCriteria(bab_SearchRealm $realm)
    {
        throw new Exception('Not implemented');
    }
    
    
    /**
     * Optional article url to the preview
     * @param int $id_article
     * @return string
     */
    public function getArticlePopupUrl($id_article) {
        return null;
    }
    
    /**
     * Optional contact url to the preview
     * @param int $id_contact
     * @return string
     */
    public function getContactPopupUrl($id_contact) {
        return null;
    }
    
    /**
     * Optional directory entry url to the preview popup
     * @param int $id_entry
     * @return string
     */
    public function getDirEntryPopupUrl($id_entry) {
        return null;
    }
    
    
    /**
     * Optional filemanager file url to the preview popup
     * @param int $id_file 
     * @return string
     */
    public function getFilePopupUrl($id_file) {
        return null;
    }
    

    /**
     * Get a string to highlight on a result page
     * @param string $realmName search realm name
     * @return string
     */
    public function highlightKeyword($realmName) {
        return null;
    }
}
