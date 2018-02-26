<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */

require_once dirname(__FILE__).'/dirincl.php';

class Func_LdapEditor_Ovidentia extends Func_LdapEditor {
    
    public function getDescription() {
        return bab_translate('Default create/modify ldap/ad configuration');
    }
    /**
     * Get editor as a babPage widget
     * @param 	int			$id_user
     * @param	bab_url		$backurl
     *
     * @return Widget_BabPage
     */
    public function getAsPage($id_site)
    {
    
        $babPage = bab_Widgets()->BabPage();
    
        try {
            $editor = $this->getForm($id_site);
            $editor->setSelfPageHiddenFields();
            $babPage->addItem($editor);
    
        } catch (Exception $e) {
            $babPage->addError($e->getMessage());
        }
    
    
        return $babPage;
    }
    
    /**
     * @return Widget_Form
     */
    public function getForm($site)
    {
        $message = bab_translate('Access denied');
        
        $W = bab_Widgets();
        
        $Icons = bab_functionality::get('Icons');
        /*@var $Icons Func_Icons */
        $Icons->includeCss();
        
        $form = $W->Form(null, $W->VBoxLayout()->setVerticalSpacing(1,'em'));
        $form->colon();
        $form->setName('ldap');
        $form->addClass('bab-user-editor');
        
        $form->addItem($W->Label(4654136846384));
        
        return $form;
    }
}

class Func_LdapEditor extends bab_functionality {
    public function getDescription() {
        return bab_translate('Create/modify ldap/ad configuration');
    }
    /**
     * Get editor as a babPage widget
     * @param 	int			$id_user
     * @param	bab_url		$backurl
     *
     * @return Widget_BabPage
     */
    public function getAsPage($id_site)
    {
        return null;
    }
    
    /**
     * @return Widget_Form
     */
    public function getForm($site)
    {
        return null;
    }
}