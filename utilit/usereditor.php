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
require_once 'base.php';


/**
 * Default user editor (create/modify form for the user account)
 */
class Func_UserEditor extends bab_functionality {
	
	/**
	 * If editor must use a specific id_directory
	 * @var int
	 */
	protected $id_directory = null;
	
	
	
	/**
	 * 
	 * @var bool
	 */
	protected $access_control = true;
	
	
	/**
	 * @see Func_UserEditor::getDirectory() 
	 * @var array
	 */
	private $directory = null;
	
	
	
	

	public function getDescription() {
		return bab_translate('Default create/modify form for the user account');
	}
	
	
	/**
	 * If editor must use a specific group directory, if not specified, the registred users directory is used
	 * @param	int	$id_directory
	 */
	public function setDirectory($id_directory) {
		$this->id_directory = (int) $id_directory;
		$this->directory = null;
		return $this;
	}
	
	
	/**
	 * This method can be used to activate or disable access control on directory configuration
	 * access control is activated by default
	 * @param bool $access_control
	 */
	public function setAccessControl($access_control) {
		$this->access_control = $access_control;
		return $this;
	}
	
	
	/**
	 * Get directory informations
	 * @throws Exception if directory does not exists or not a group directory
	 * @return Array
	 */
	protected function getDirectory() {
		
		if (null === $this->directory)
		{
			global $babDB;
			
			$req = "
			SELECT
				d.id,
				d.name,
				d.description,
				d.id_group,
				d.id_dgowner,
				d.user_update,
				d.show_update_info,
				d.disable_email 
			FROM
				".BAB_DB_DIRECTORIES_TBL." d,
				".BAB_GROUPS_TBL." g
			WHERE
				g.id>'0'
				AND g.id=d.id_group
				AND g.directory='Y'
			";
			
			
			if (isset($this->id_directory))
			{
				$req .= ' AND d.id='.$babDB->quote($this->id_directory);
			} else {
				$req .= ' AND g.id='.$babDB->quote(BAB_REGISTERED_GROUP);
			}
			
			$res = $babDB->db_query($req);
			
			if (0 === $babDB->db_num_rows($res))
			{
				throw new Exception('The directory does not exists or is not a group directory');
			}
			
			$this->directory = $babDB->db_fetch_assoc($res);
		
		}
		
		return $this->directory;
	}

	
	/**
	 * Can add directory entry
	 * @throws Exception if directory does not exists or not a group directory
	 *
	 * @return bool
	 */
	protected function canAddDirectoryEntry()
	{
		if (!$this->access_control && !isset($this->id_directory))
		{
			return true;
		}
		
		$directory = $this->getDirectory();
		
		if (!$this->access_control)
		{
			return true;
		}
		
		if (bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $directory['id']))
		{
			return true;
		}
		
		return false;
	}
	

	
	/**
	 * Can edit directory entry
	 * @throws Exception if directory does not exists or not a group directory
	 * 
	 * @param int	$id_user
	 * 
	 * @return bool
	 */
	protected function canEditDirectoryEntry($id_user)
	{
		if (!$this->access_control && !isset($this->id_directory))
		{
			return true;
		}
		
		$directory = $this->getDirectory();
		
		if (!$this->access_control)
		{
			return true;
		}
		
		if (bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $directory['id']))
		{
			return true;
		}
		
		if (0 === (int) $id_user)
		{
			throw new Exception('Incorrect user parameter');
			return false;
		}
		
		if (((int) $id_user) === bab_getUserId() && 'Y' === $directory['user_update'])
		{
			return true;
		}
		
		return false;
	}
	
	
	
	
	/**
	 * @param Widget_InputWidget	$widget
	 * @param string				$label
	 *
	 * @return Widget_Displayable_Interface
	 */
	protected function labeledField(Widget_InputWidget $widget, $label) {
		$W = bab_Widgets();
	
		$layout = $W->FlowLayout();
	
		$layout->addItem($label = $W->Label($label)->setSizePolicy('dir-label'));
		$layout->addItem($widget->setAssociatedLabel($label)->setSizePolicy('dir-field'));
		
		$layout->addClass('dir-row')->addClass('labeledfield');
	
		return $layout;
	}
	
	
	
	
	/**
	 * @param Widget_CheckBox	$widget
	 * @param string			$label
	 *
	 * @return Widget_Displayable_Interface
	 */
	protected function boolField(Widget_CheckBox $widget, $label) {
		$W = bab_Widgets();
		
		$layout = $W->HBoxLayout()->setVerticalAlign('middle');
		
		$layout->addItem($widget);
		$layout->addItem($W->Label($label)->setAssociatedWidget($widget));
		
		$layout->addClass('dir-row')->addClass('boolfield');
		
		return $layout;
	}
	
	
	
	
	
	
	/**
	 * Get fields relative to bab_users, excluding lastname, firstname, email
	 * @param	int	| null	$id_user
	 * 
	 * @return Widget_Frame
	 */
	protected function getUserFrame($id_user)
	{
		$W = bab_Widgets();
		
		$frame = $W->Frame(null, $W->VBoxLayout())->addClass('user');
		
		$frame->addItem($this->labeledField($W->LineEdit()->setMaxSize(255)->setMandatory()->setName('nickname'), bab_translate('Login ID')));
		
		$password1 = $this->labeledField($W->LineEdit()->obfuscate()->setMandatory()->setName('password1'), bab_translate('Password'));
		$password2 = $this->labeledField($W->LineEdit()->obfuscate()->setMandatory()->setName('password2'), bab_translate('Retype Password'));
		$sendpwd = $this->boolField($W->CheckBox()->setName('sendpwd'), bab_translate('Send an e-mail to the user with its new password'));
		
		if (null !== $id_user)
		{
			$frame->addItem($this->boolField($W->CheckBox()
					->setAssociatedDisplayable($password1, array(1))
					->setAssociatedDisplayable($password2, array(1))
					->setAssociatedDisplayable($sendpwd, array(1))
					->setName('changepwd'), bab_translate('Change password')));
		}
		
		$frame->addItem($password1);
		$frame->addItem($password2);
		
		
		if (null === $id_user)
		{
			$frame->addItem($this->boolField($W->CheckBox()->setName('notifyuser'), bab_translate('Notify user')));
			$frame->addItem($this->boolField($W->CheckBox()->setName('sendpwd'), bab_translate('Send password with email')));
		} else {
			$frame->addItem($sendpwd);
		}
		
		return $frame;
	}
	
	
	/**
	 * Get fields relative to bab_dbdir_entries
	 * @return Widget_Frame
	 */
	protected function getDirectoryFrame($fields = null)
	{
		$W = bab_Widgets();
		
		$frame = $W->Frame(null, $W->VBoxLayout())->addClass('directory');
		if (null === $fields)
		{
			$directory = $this->getDirectory();
			$fields = bab_admGetDirEntry($directory['id'], BAB_DIR_ENTRY_ID_DIRECTORY, $directory['id']);
		}
		
		foreach($fields as $fieldname => $f)
		{
			if ('jpegphoto' === $fieldname)
			{
				continue;
			}
			
			if (isset($f['multilignes']) && $f['multilignes'])
			{
				$widget = $W->TextEdit();
			} elseif(isset($f['multi_values'])) {
				$widget = $W->Select();
				$widget->addOption('', '');
				foreach($f['multi_values']['options'] as $arr)
				{
					$widget->addOption($arr['id'], $arr['field_value']);
				}
				
				$widget->setValue($f['multi_values']['default_value']);
				
			} else {
				$widget = $W->LineEdit();
			}
			
			if (isset($f['default_value_text']) && !isset($f['multi_values']))
			{
				$widget->setValue($f['default_value_text']);
			}
			
			if (isset($f['required']) && $f['required'])
			{
				$widget->setMandatory();
			}
			
			
			$frame->addItem($this->labeledField($widget->setName($fieldname), $f['name']));
		}
		
		return $frame;
	}
	
	
	/**
	 * Get fields relative to bab_dbdir_entries if the directory is not modifiable, only the mandatory for user creation : lastname, firstname, email
	 * @return Widget_Frame
	 */
	protected function getDefaultDirectoryFrame()
	{
		$fields = array(

			'sn'		=> array('name' => bab_translate('Lastname') 	, 'value' => '' ),
			'givenname' => array('name' => bab_translate('Firstname') 	, 'value' => '' ),
			'email' 	=> array('name' => bab_translate('Email') 		, 'value' => '' )
		
		);
		
		return $this->getDirectoryFrame($fields);
	}
	
	
	
	
	
	/**
	 * @param	int	$id_user	the user to modify or null for a creation form
	 * @return Widget_Form
	 */
	public function getForm($id_user = null) 
	{
		$W = bab_Widgets();
		
		$form = $W->Form();
		$form->colon();
		$form->setName('user');
		$form->addClass('bab-user-editor');
		
		
		$form->addItem($this->getUserFrame($id_user));
		
		if ((null === $id_user && $this->canAddDirectoryEntry()) || $this->canEditDirectoryEntry($id_user))
		{
			$form->addItem($this->getDirectoryFrame());
		} else {
			$form->addItem($this->getDefaultDirectoryFrame());
		}
		
		
		if (null !== $id_user)
		{
			$form->setValues(array('user' => $this->getValues($id_user)));
		}
		
		
		return $form;
	}
	
	
	/**
	 * Get an array of values to use in form when a user is modified
	 * @return array
	 */
	protected function getValues($id_user)
	{
		$values = bab_getUserInfos($id_user);

		return $values;
	}
	

}
