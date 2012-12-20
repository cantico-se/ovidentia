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
	
	/**
	 * @see Func_UserEditor::getDirectoryFields() 
	 * @var array
	 */
	private $fields = null;
	
	
	/**
	 * 
	 * @var Widget_ImagePicker
	 */
	private $imagePicker = null;
	
	

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
		if (null === $id_user)
		{
			return false;
		}
		
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
	function labeledField(Widget_InputWidget $widget, $label) {
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
		
		if (null === $id_user || $this->canEditNickname($id_user))
		{
			$frame->addItem($this->labeledField($W->LineEdit()->setMaxSize(255)->setMandatory()->setName('nickname'), bab_translate('Login ID')));
		}
		
		if (null === $id_user || $this->canEditPassword($id_user))
		{
		
			$password1 = $this->labeledField($W->LineEdit()->obfuscate()->setMandatory()->setName('password1')->setAutoComplete(false), bab_translate('Password'));
			$password2 = $this->labeledField($W->LineEdit()->obfuscate()->setMandatory()->setName('password2')->setAutoComplete(false), bab_translate('Retype Password'));
			$sendpwd = $this->boolField($W->CheckBox()->setName('sendpwd'), bab_translate('Send an e-mail to the user with its new password'));
			
			if (null !== $id_user)
			{
				$frame->addItem($this->boolField($W->CheckBox()
						->setAssociatedDisplayable($password1, array(1))
						->setAssociatedDisplayable($password2, array(1))
						->setAssociatedDisplayable($sendpwd, array(1))
						->setName('setpwd'), bab_translate('Change password')));
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
			
		}
		
		return $frame;
	}
	
	
	/**
	 * 
	 * @return Ambigous <multitype:, boolean, multitype:multitype:number bab_QueryIterator  , bab_dirEntryPhoto, unknown, multitype:number bab_QueryIterator >
	 */
	protected function getDirectoryFields()
	{
		if (null === $this->fields)
		{
			$directory = $this->getDirectory();
			$this->fields = bab_admGetDirEntry($directory['id'], BAB_DIR_ENTRY_ID_DIRECTORY, $directory['id']);
		}
		
		return $this->fields;
	}
	
	
	
	protected function getPhotoFrame()
	{
		$fields = $this->getDirectoryFields();
	
		if (!isset($fields['jpegphoto']) || !isset($fields['jpegphoto']['modifiable']) || false === $fields['jpegphoto']['modifiable'])
		{
			return null;
		}
		
		$W = bab_Widgets();
		
		$this->imagePicker = $W->ImagePicker()
			->setDimensions(300, 300)
			->setName('jpegphoto')
			->oneFileMode()
			->setEncodingMethod(null)
			->setTitle(bab_translate(bab_translate('Set the photo')))
		;

		// empty temporary image path
		$this->imagePicker->cleanup();
		
		return $this->imagePicker;
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
			$fields = $this->getDirectoryFields();
		}
		
		foreach($fields as $fieldname => $f)
		{
			if (!isset($f['modifiable']) || true !== $f['modifiable'])
			{
				continue;
			}
			
			
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
				$values = array();
				$default = $f['multi_values']['default_value'];
				
				foreach($f['multi_values']['options'] as $arr)
				{
					$values[$arr['id']] = $arr['field_value'];
					$widget->addOption($arr['field_value'], $arr['field_value']);
				}
				
				if (isset($values[$default]))
				{
					$widget->setValue($values[$default]);
				}
				
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
	 * @return Widget_Displayable_Interface
	 */
	protected function getButtons()
	{
		$W = bab_Widgets();
		
		$frame = $W->Frame()->addClass('buttons');
		$frame->addItem($W->SubmitButton()->setLabel(bab_translate('Save')));
		
		return $frame;
	}
	
	
	/**
	 * Test if a user can be created
	 * admin or access right in creation in a group directory
	 * @return bool
	 */
	protected function canCreateUser()
	{
		if (!$this->access_control)
		{
			return true;
		}
		
		if (bab_isUserAdministrator())
		{
			return true;
		}
		
		$directory = $this->getDirectory();
		if ($directory['id_group'] > 0 && bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $directory['id']))
		{
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Test if a user can be modified
	 * @param int $id_user
	 * @return bool
	 */
	protected function canEditUser($id_user)
	{
		if (!$this->access_control)
		{
			return true;
		}
		
		if (bab_isUserAdministrator())
		{
			return true;
		}
		
		$directory = $this->getDirectory();
		if ($directory['id_group'] > 0 && bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $directory['id']))
		{
			return true;
		}
		
		if ('Y' === $directory['user_update'] && $id_user === bab_getUserId())
		{
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Test if the password of user can be modified
	 * @param int $id_user
	 */
	protected function canEditPassword($id_user)
	{
		if (!$this->access_control)
		{
			return true;
		}
		
		if (bab_isUserAdministrator())
		{
			return true;
		}
		
		global $babBody;
		/*@var $babBody babBody */

		if ('Y' === $babBody->babsite['change_password'] && $id_user === bab_getUserId())
		{
			return true;
		}
		
		return false;
	}
	
	
	
	/**
	 * Test if the nickname of user can be modified
	 * @param int $id_user
	 */
	protected function canEditNickname($id_user)
	{
		if (!$this->access_control)
		{
			return true;
		}
	
		if (bab_isUserAdministrator())
		{
			return true;
		}
	
		global $babBody;
		/*@var $babBody babBody */
	
		if ('Y' === $babBody->babsite['change_nickname'] && $id_user === bab_getUserId())
		{
			return true;
		}
	
		return false;
	}
	
	
	
	
	/**
	 * @param	int	$id_user	the user to modify or null for a creation form
	 * @return Widget_Form
	 */
	public function getForm($id_user = null) 
	{
		if (null === $id_user && !$this->canCreateUser())
		{
			throw new Exception(bab_translate('Access denied'));
		}
		
		if (null !== $id_user && !$this->canEditUser($id_user))
		{
			throw new Exception(bab_translate('Access denied'));
		}
		
		
		$W = bab_Widgets();
		
		$Icons = bab_functionality::get('Icons');
		/*@var $Icons Func_Icons */
		$Icons->includeCss();
		
		$form = $W->Form();
		$form->colon();
		$form->setName('user');
		$form->addClass('bab-user-editor');
		
		if ((null === $id_user && $this->canAddDirectoryEntry()) || $this->canEditDirectoryEntry($id_user))
		{
			if ($photo = $this->getPhotoFrame())
			{
				$form->addItem($photo);
			}
		}
		
		
		$form->addItem($this->getUserFrame($id_user));
		
		if ((null === $id_user && $this->canAddDirectoryEntry()) || $this->canEditDirectoryEntry($id_user))
		{
			$form->addItem($this->getDirectoryFrame());
		} else {
			$form->addItem($this->getDefaultDirectoryFrame());
		}
		
		$form->addItem($this->getButtons());
		
		if (null !== $id_user)
		{
			$form->setHiddenValue('user[id]', $id_user);
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
		include_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';
		include_once $GLOBALS['babInstallPath'].'utilit/userinfosincl.php';
	
		$directory = getDirEntry($id_user, BAB_DIR_ENTRY_ID_USER, NULL, false);
	
		if (!$directory) {
			throw new Exception(sprintf('No directory entry for user %d', $id_user));
		}
	
		$infos = bab_userInfos::getForDirectoryEntry($id_user);
	
		if (!$infos) {
			throw new Exception(sprintf('Failed to get user infos for id user %d', $id_user));
		}
	
		foreach($directory as $field => $arr) {
			$infos[$field] = $arr['value'];
		}

		
		if (isset($this->imagePicker))
		{
			if (isset($directory['jpegphoto']['photo']))
			{
				$photo = $directory['jpegphoto']['photo'];
				/*@var $photo bab_dirEntryPhoto */
				
				$tmpPath = $this->imagePicker->getFolder();
				try {
					$tmpPath->deleteDir();
				} catch(bab_FolderAccessRightsException $e) {
					
				}
				$tmpPath->createDir();
				$tmpPath->push('photo.jpg');
				
				
				file_put_contents($tmpPath->tostring(), $photo->getData());
				
				$tmpPath->pop();
				
				
				
			}

		}

		return $infos;
	}
	
	

	
	
	/**
	 * Save from posted array
	 * and send the notifications if necessary
	 * return id_user
	 * 
	 * @throws Exception
	 * 
	 * @return int
	 */
	public function save(Array $user)
	{
		global $babBody;
		$id_user = isset($user['id']) ? ((int) $user['id']) : null;
		$send_pwd = isset($user['sendpwd']) ? $user['sendpwd'] : null;
		
		if (null === $id_user && !$this->canCreateUser())
		{
			throw new Exception(bab_translate('Access denied'));
		}
		
		if (null !== $id_user && !$this->canEditUser($id_user))
		{
			throw new Exception(bab_translate('Access denied'));
		}
		
		// verify directory mandatory fields
		
		if ((null === $id_user && $this->canAddDirectoryEntry()) || $this->canEditDirectoryEntry($id_user))
		{
			$fields = $this->getDirectoryFields();
			foreach($fields as $fieldname => $f) {
				if (isset($f['modifiable']) && isset($f['required']) && true === $f['modifiable'] && true === $f['required'])
				{
					if (!isset($user[$fieldname]) || '' === $user[$fieldname])
					{
						throw new Exception(sprintf(bab_translate('The fields %s is mandatory'), $f['name']));
					}
				}
			}
		}
		
		
		
		if (!isset($id_user))
		{
			if (mb_strlen($user['password1']) < 6)
			{
				throw new Exception(bab_translate('Password must have at least 6 characters'));
			}
			
			if (false === $id_user = bab_registerUser($user['sn'], $user['givenname'], '', $user['email'], $user['nickname'], $user['password1'], $user['password2'], 1, $error))
			{
				throw new Exception($error);
			}
			
			// if in group directory, attach user to group
			
			$directory = $this->getDirectory();
			if ($directory['id_group'] > BAB_REGISTERED_GROUP)
			{
				bab_attachUserToGroup($id_user, $directory['id_group']);
			}
			
			
			// delegated administrator, add the created user in the main delegation group
			if($babBody->currentAdmGroup != 0 &&
					$babBody->currentDGGroup['id_group'] != BAB_ALLUSERS_GROUP &&
					$babBody->currentDGGroup['id_group'] != BAB_REGISTERED_GROUP &&
					$babBody->currentDGGroup['id_group'] != BAB_UNREGISTERED_GROUP &&
					$babBody->currentDGGroup['users'] == 'Y')
			{
				bab_attachUserToGroup($id_user, $babBody->currentDGGroup['id_group']);
			}
		}
		
		
		if (isset($user['setpwd']) && $user['setpwd'])
		{
			if (mb_strlen($user['password1']) < 6)
			{
				throw new Exception(bab_translate('Password must have at least 6 characters'));
			}
			
			
			
			if ($user['password1'] !== $user['password2'])
			{
				throw new Exception(bab_translate('ERROR: Passwords not match !!'));
			}
			
			// set the password key for modification
			
			$user['password'] = $user['password1'];
			
			unset($user['password1']);
			unset($user['password2']);
			unset($user['setpwd']);
		}
		
		
		if ((!isset($user['id']) && $this->canAddDirectoryEntry()) || $this->canEditDirectoryEntry($id_user))
		{
			
			if (isset($fields['jpegphoto']['modifiable']) && true === $fields['jpegphoto']['modifiable'])
			{
			
				$user['jpegphoto'] = ''; // delete photo
				
				// update photo
				
				$W = bab_Widgets();
				$imagePicker = $W->ImagePicker();
				$files = $imagePicker->getTemporaryFiles('jpegphoto');
				if (isset($files))
				{
					foreach($files as $filePickerItem)
					{
						/*@var $filePickerItem Widget_FilePickerItem */
						$user['jpegphoto'] = bab_fileHandler::move($filePickerItem->getFilePath()->tostring());
						break;
					}
				}
			}
			
		} else {
			
			// only user fields can be modified
			
			$allowed_fields = array('sn' => 1, 'givenname' => 1, 'email' => 1);
			
			if (!isset($user['id']) || $this->canEditPassword($id_user))
			{
				$allowed_fields['password'] = 1;
			}
			
			if (!isset($user['id']) || $this->canEditNickname($id_user))
			{
				$allowed_fields['nickname'] = 1;
			}
			
			foreach($user as $key => $value)
			{
				if (!isset($allowed_fields[$key]))
				{
					unset($user[$key]);
				}
			}
			
		}
		
		
		if (!bab_updateUserById($id_user, $user, $error))
		{
			throw new Exception($error);
			return false;
		}
		
		
		
		
		
		
		// notifications
		
		if (!$id_user && !empty($user['notifyuser']))
		{
			// notify the user about creation of the account
			bab_registerUserNotify($user['sn'].' '.$user['givenname'], $user['email'], $user['nickname'], (empty($user['sendpwd']) ? null : $user['password1']));
		}
		
		if ($id_user && !empty($send_pwd))
		{
			// send the new password by email
			require_once $GLOBALS['babInstallPath'].'admin/register.php';	
			notifyUserPassword($user['password'], bab_getUserEmail($id_user), bab_getUserNickname($id_user));
		}
		
		return $id_user;
	}
	
	
	
	/**
	 * Get editor as a babPage widget
	 * @param 	int			$id_user
	 * @param	bab_url		$backurl
	 * 
	 * @return Widget_BabPage
	 */
	public function getAsPage($id_user, bab_url $backurl)
	{
		$babPage = bab_Widgets()->BabPage();
		$babPage->addStyleSheet($GLOBALS['babInstallPath'].'styles/usereditor.css');
		
		if (isset($_POST['user']))
		{
			try {
				if ($this->save($_POST['user']))
				{
					$backurl->location();
				}
			} catch(Exception $e)
			{
				$babPage->addError($e->getMessage());
			}
		}
		$editor = $this->getForm($id_user);
		$editor->setSelfPageHiddenFields();
		$babPage->addItem($editor);
		
		if (isset($_POST['user']))
		{
			$editor->setValues(array('user' => bab_pp('user')));
		}
		
		return $babPage;
	}

}
