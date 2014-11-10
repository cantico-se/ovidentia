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
     * Mandatory method to get the serach interface
     * @param string $realmName Optional serach realm name
     * @return string
     */
    public function getUrl($realmName = null) {
        throw new Exception('Not implemented');
    }
    
    
    /**
     * Optional article url to the preview
     * @param string $keyword   A keyword to highlight in the preview
     * @return string
     */
    public function getArticlePopupUrl($keyword) {
        return null;
    }
    
    /**
     * Optional contact url to the preview
     * @param string $keyword   A keyword to highlight in the preview
     * @return string
     */
    public function getContactPopupUrl($keyword) {
        return null;
    }
    
    /**
     * Optional directory entry url to the preview popup
     * @param string $keyword   A keyword to highlight in the preview
     * @return string
     */
    public function getDirEntryPopupUrl($keyword) {
        return null;
    }
    
    /**
     * Optional FAQ question url to the preview popup
     * @param string $keyword   A keyword to highlight in the preview
     * @return string
     */
    public function getFaqQuestionPopupUrl($keyword) {
        return null;
    }
    
    /**
     * Optional filemanager file url to the preview popup
     * @param string $keyword   A keyword to highlight in the preview
     * @return string
     */
    public function getFilePopupUrl($keyword) {
        return null;
    }
}
