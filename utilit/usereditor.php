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
	 * If the form is used for public user registration
	 * @var bool
	 */
	protected $register = false;
	
	
	/**
	 * If the form is used in register mod it is ask if condition of use
	 * @var bool
	 */
	protected $condition = false;
	
	
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
	 * @see Func_UserEditor::getRegistrationFields()
	 * @var array
	 */
	private $registration_fields = null;
	
	
	/**
	 * 
	 * @var Widget_ImagePicker
	 */
	private $imagePicker = null;
	
	
	/**
	 * @var Widget_SuggestPlaceName
	 */
	private $hcity;
	
	/**
	 * @var Widget_SuggestPostalCode
	 */
	private $hpostalcode;
	
	/**
	 * @var Widget_SuggestCountry
	 */
	private $hcountry;
	
	/**
	 * @var Widget_SuggestPlaceName
	 */
	private $bcity;
	
	/**
	 * @var Widget_SuggestPostalCode
	 */
	private $bpostalcode;
	
	/**
	 * @var Widget_SuggestCountry
	 */
	private $bcountry;
	

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
	 * Test if the users can modify their own directory entry
	 * @return bool
	 */
	protected function canUserEditHisOwnEntry()
	{
		$directory = $this->getDirectory();

		if ('Y' === $directory['user_update'])
		{
			return true;
		}

		if ($directory['id_group'] != BAB_REGISTERED_GROUP)
		{
			global $babDB;
			
			$res = $babDB->db_query("
				SELECT user_update FROM  
					
					".BAB_DB_DIRECTORIES_TBL." d,
					".BAB_GROUPS_TBL." g 
					
				WHERE
					g.id>'0'
					AND g.id=d.id_group
					AND g.directory='Y'
					AND g.id=".$babDB->quote(BAB_REGISTERED_GROUP)."
			");
			
			$registered_users = $babDB->db_fetch_assoc($res);
			
			if (!empty($registered_users) && 'Y' === $registered_users['user_update'])
			{
				return true;
			}
		}
		
		return false;
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
		
		if (BAB_REGISTERED_GROUP === (int) $directory['id_group'] && (bab_isUserAdministrator() || bab_isDelegated('users')))
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
		
		if (((int) $id_user) === bab_getUserId() && $this->canUserEditHisOwnEntry())
		{
			return true;
		}
		
		
		if (BAB_REGISTERED_GROUP === (int) $directory['id_group'] && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
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
	 * @return Widget_Displayable_Interface
	 */
	protected function getNicknameWidget($id_user)
	{
		$W = bab_Widgets();
		return $this->labeledField($W->LineEdit()->setMaxSize(255)->setMandatory()->setName('nickname'), bab_translate('Login ID'));
	}
	
	/**
	 * @return Widget_Displayable_Interface
	 */
	protected function getPasswordWidget($id_user)
	{
		$W = bab_Widgets();
		$frame = $W->Frame(null, $W->VBoxLayout());
		
		$password1 = $this->labeledField($W->LineEdit()->obfuscate()->setMandatory()->setName('password1')->setAutoComplete(false), bab_translate('Choose password'));
		$password2 = $this->labeledField($W->LineEdit()->obfuscate()->setMandatory()->setName('password2')->setAutoComplete(false), bab_translate('Retype choosen password'));
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
		
		if(!$this->register){
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
			if ($nickname = $this->getNicknameWidget($id_user))
			{
				$frame->addItem($nickname);
			}
		}
		
		if (null === $id_user || $this->canEditPassword($id_user))
		{
			if ($password = $this->getPasswordWidget($id_user))
			{
				$frame->addItem($password);
			}
		}
		
		return $frame;
	}
	
	
	/**
	 * Get directory fields
	 * output must be in the same format as getDirEntry function
	 * @return Array
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
	
	
	
	protected function getFormDirectoryFields()
	{
		if ($this->register)
		{
			return $this->getRegistrationFields();
		} else {
			return $this->getDirectoryFields();
		}
	}
	
	
	
	
	protected function getPhotoFrame()
	{
		$fields = $this->getFormDirectoryFields();
	
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
	 * 
	 * @param string	 	$fieldname
	 * @param array 		$f	
	 */
	protected function getDirectoryFieldFrame($fieldname, $f)
	{
		$W = bab_Widgets();
		if (!$this->register && (!isset($f['modifiable']) || true !== $f['modifiable']))
		{
			return false;
		}
			
			
		if ('jpegphoto' === $fieldname)
		{
			return false;
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
			
			switch($fieldname)
			{
				case 'btel':
					$widget = $W->TelLineEdit()->setTelType(Widget_TelLineEdit::BUSINESS);
					break;
					
				case 'htel':
					$widget = $W->TelLineEdit()->setTelType(Widget_TelLineEdit::HOME);
					break;
					
				case 'mobile':
					$widget = $W->TelLineEdit()->setTelType(Widget_TelLineEdit::MOBILE);
					break;
					
				case 'bfax':
					$widget = $W->TelLineEdit()->setTelType(Widget_TelLineEdit::FAX);
					break;
					
				case 'email':
					$widget = $W->EmailLineEdit();
					if (isset($this->imagePicker) && method_exists($this->imagePicker, 'setGravatarEmailField'))
					{
						$this->imagePicker->setGravatarEmailField($widget);
					}
					break;
					
					
				case 'bcity':
				case 'hcity':
					$this->$fieldname = $widget = $W->SuggestPlaceName();
					break;

				case 'bpostalcode':
				case 'hpostalcode':
					$this->$fieldname = $widget = $W->SuggestPostalCode();
					break;
					
				case 'bcountry':
				case 'hcountry':
					$this->$fieldname = $widget = $W->SuggestCountry();
					break;
					
				
				default:
					$widget = $W->LineEdit();
			}
			
			
		}
			
		if (isset($f['default_value_text']) && !isset($f['multi_values']))
		{
			$widget->setValue($f['default_value_text']);
		}
		
		if ( isset($f['required']) && $f['required'])
		{
			$widget->setMandatory();
		}
		
		return $this->labeledField($widget->setName($fieldname), $f['name']);
	}
	
	
	/**
	 * Get fields for registration
	 * output must be in the same format as getDirEntry function
	 * @return array
	 */
	private function getRegistrationFields()
	{
		if (!isset($this->registration_fields))
		{
		
			global $babDB;
			$site = $this->getSiteSettings();
			$this->registration_fields = array();
			
			$res = $babDB->db_query("
				SELECT  
					sfrt.*, 
					sfxt.id as idfx, 
					sfxt.default_value as default_value, 
					sfxt.multilignes, 	# Y or N
					sfxt.multi_values 	# Y or N
					
				FROM 
					".BAB_SITES_FIELDS_REGISTRATION_TBL." sfrt 
						left join ".BAB_DBDIR_FIELDSEXTRA_TBL." sfxt on sfxt.id_field=sfrt.id_field 
					
				WHERE sfrt.id_site='".$babDB->db_escape_string($site['id'])."' and sfrt.registration='Y' and sfxt.id_directory='0'
			");
			
			
			while($arr = $babDB->db_fetch_array($res)){
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
				{
					$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
					$fieldName = $rr['name'];
					$fieldLabel = translateDirectoryField($rr['description']);
				}
				else
				{
					$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
					$fieldName = "babdirf".$arr['idfx'];
					$fieldLabel = translateDirectoryField($rr['name']);
				}
				
				
				$f = array(
						
					'name' => $fieldLabel,
					'value' => '',
					'modifiable' => true,
					'required' => ($arr['required'] == 'Y'),
					'multilignes' => ($arr['multilignes'] == 'Y')
				);
				
				
				if ('Y' === $arr['multi_values'])
				{
					require_once dirname(__FILE__).'/iterator/iterator.php';
					$options = new bab_QueryIterator;
					$options->setQuery('SELECT * FROM bab_dbdir_fieldsvalues WHERE id_fieldextra='.$babDB->quote($arr['idfx']).' ORDER BY field_value ASC');
				
					$f['multi_values'] = array(
							'default_value' => (int) $arr['default_value'],
							'options' => $options
					);
				}
				
				
				$this->registration_fields[$fieldName] = $f;
			}
		}
		
		return $this->registration_fields;
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
			$fields = $this->getFormDirectoryFields();
		}

		
		foreach($fields as $fieldname => $f)
		{
			$field = $this->getDirectoryFieldFrame($fieldname, $f);
			
			if(!$field){
				continue;
			}
			
			$frame->addItem($field);
		}
		
		// create associations beetween fields if possible
		
		if (isset($this->bcity) && isset($this->bpostalcode))
		{
			$this->bcity->setRelatedPostalCode($this->bpostalcode);
			$this->bpostalcode->setRelatedPlaceName($this->bcity);
			
			if (isset($this->bcountry))
			{
				$this->bcity->setRelatedCountry($this->bcountry);
				$this->bpostalcode->setRelatedCountry($this->bcountry);
			}
		}
		
		if (isset($this->hcity) && isset($this->hpostalcode))
		{
			$this->hcity->setRelatedPostalCode($this->hpostalcode);
			$this->hpostalcode->setRelatedPlaceName($this->hcity);
		
			if (isset($this->hcountry))
			{
				$this->hcity->setRelatedCountry($this->hcountry);
				$this->hpostalcode->setRelatedCountry($this->hcountry);
			}
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
		$frame->addItem($W->SubmitButton()->setLabel(bab_translate('Save'))->validate());
		
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
		
		if (bab_isDelegated('users'))
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
		
		if (bab_getCurrentAdmGroup() != 0)
		{
			return true;
		}
		
		
		$directory = $this->getDirectory();
		if ($directory['id_group'] > 0 && bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $directory['id']))
		{
			return true;
		}
		
		if ($this->canUserEditHisOwnEntry() && ((int) $id_user) === bab_getUserId())
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return array
	 */
	private function getSiteSettings()
	{
		require_once dirname(__FILE__).'/settings.class.php';
		$settings = bab_getInstance('bab_Settings');
		/*@var $settings bab_Settings */
		return $settings->getSiteSettings();
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
		
		$site = $this->getSiteSettings();

		if ('Y' === $site['change_password'] && $id_user === bab_getUserId())
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
	
		$site = $this->getSiteSettings();
	
		if ('Y' === $site['change_nickname'] && $id_user === bab_getUserId())
		{
			return true;
		}
	
		return false;
	}
	
	/**
	 * Activate registration options on user form
	 * 
	 */
	public function setRegister()
	{
		$site = $this->getSiteSettings();
		if($site['registration'] == 'Y'){
			$this->setDirectory(1);
			$this->register = true;
			$this->access_control = false;
			$this->condition = ($site['display_disclaimer']=='Y');
		}
		return $this;
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
		
		$form = $W->Form(null, $W->VBoxLayout()->setVerticalSpacing(1,'em'));
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
		
		
		
		if ((null === $id_user && $this->canAddDirectoryEntry()) || $this->canEditDirectoryEntry($id_user))
		{
			$form->addItem($this->getDirectoryFrame());
		} else {
			$form->addItem($this->getDefaultDirectoryFrame());
		}
		
		$form->addItem($this->getUserFrame($id_user));
		
		if($this->register){
			$form->addItem($this->getRegistrationFrame());
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
	 * 
	 * @param array $profile
	 * @return Widget_Displayable_Interface
	 */
	protected function getProfileWidget(Array $profile)
	{
		global $babDB;
		$W = bab_Widgets();
		
		$res = $babDB->db_query("
			SELECT
				gt.id,
				gt.name 
			FROM
				".BAB_PROFILES_GROUPSSET_TBL." pgt
				LEFT JOIN ".BAB_GROUPS_TBL." gt on pgt.id_group=gt.id
			WHERE
				pgt.id_object =".$babDB->quote($profile['id'])
		);
		
		
		if ($profile['multiplicity'] == 'Y')
		{
			$container = $W->Frame();
			$output = $W->Frame(null, $W->VBoxLayout())->addItem($W->Label($profile['name'])->colon())->addItem($container);
		} else {
			$container = $W->Select();
			$output = $this->labeledField($container, $profile['name']);
			
			if ($profile['required'] == 'Y')
			{
				$container->setMandatory();
			} else {
				$container->addOption('', '');
			}
		}
		
		$container->setName(array('profile', $profile['id']));
		
		while($arr = $babDB->db_fetch_assoc($res))
		{
			if ($profile['multiplicity'] == 'Y')
			{
				$container->addItem($this->labeledField($W->CheckBox()->setName($arr['id']), $arr['name']));
			} else {
				$container->addOption($arr['id'], $arr['name']);
			}
		}
		
		return $output;
	}
	
	
	
	/**
	 * A frame with optional field displayed only on registration form
	 * @return Widget_Frame
	 */
	protected function getRegistrationFrame()
	{
		global $babDB;
		$W = bab_Widgets();
		$frame = $W->Frame(null, $W->VBoxLayout()->setVerticalSpacing(1,'em'));
		
		// profils
		$respf = $babDB->db_query("select * from ".BAB_PROFILES_TBL." where inscription='Y'");
		while ($pf = $babDB->db_fetch_assoc($respf))
		{
			$frame->addItem($this->getProfileWidget($pf));
		}
		
		
		if($this->condition){
			$frame->addItem(
				$W->VBoxItems(
					$W->HBoxItems(
						$tmp = $W->CheckBox()->setMandatory(true, bab_translate('You have to accept the agreement'))->setCheckedValue('1')->setName('checkCondition'),
						$W->Label(bab_translate("I have read and accept the agreement").' ('),
						$W->Link(bab_translate("Read"), "javascript: Start('?tg=login&cmd=showdp', 'OviRegisterDP', 'width=600,height=1000,status=no,resizable=yes,top=10,left=200,scrollbars=yes');"),
						$W->Label(')')->setAssociatedWidget($tmp)
					)->setHorizontalSpacing(5),
					$W->Label('')
				)->setVerticalSpacing(10,'px')
			);
		}
		
		$site = $this->getSiteSettings();
		
		if ($site['email_confirm'] == 'Y')
		{
			$frame->addItem($W->label(bab_translate("Please provide a valid email.")));
			$frame->addItem($W->label(bab_translate("We will send you an email for confirmation before you can use our services")));
		}
		else
		{
			if($site['email_confirm'] != 2)
			{
				$frame->addItem($W->VBoxItems($W->label(bab_translate("Your account will be activated only after validation")), $W->label(''))->addClass('bab-user-editor-center')->setVerticalSpacing(25,'px'),0);
			}
		}
		
		$captcha = bab_functionality::get('Captcha');
		if($captcha){
			$captchaLayout = $W->HBoxItems(
					$W->Html($captcha->getGetSecurityHtmlData()),
					$W->HBoxItems(
							$tmp = $W->Label(bab_translate('Enter the letters in the image above')),
							$W->LineEdit()->setAssociatedLabel($tmp)
							->setSize(15)
							->setMandatory(true, bab_translate('You must fill the security code'))
							->setName('captcha')
					)
			)->setHorizontalSpacing(1,'em');
			$frame->addItem($captchaLayout);
		}
		
		return $frame;
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
		
		$site = $this->getSiteSettings();
		
		if(!$GLOBALS['BAB_SESS_LOGGED'] && $site['registration'] == 'Y'){
			$this->register = true;
		}
		
		if($this->register){
			$captcha = bab_functionality::get('Captcha');
			if($captcha){
				/*@var $captcha Func_Captcha */
				if (!$captcha->securityCodeValid($user['captcha']))
				{
					throw new Exception(sprintf(bab_translate('The security code is not correct')));
				}
			}
		}
		
		$id_user = isset($user['id']) ? ((int) $user['id']) : null;
		$id_user_original = $id_user;
		$send_pwd = isset($user['sendpwd']) ? $user['sendpwd'] : null;
		
		
		if (!$this->register && null === $id_user && !$this->canCreateUser())
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
			$fields = $this->getFormDirectoryFields();
			foreach($fields as $fieldname => $f) {
				if (!$this->register && (isset($f['modifiable']) && isset($f['required']) && true === $f['modifiable'] && true === $f['required']))
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
			// User creation
			
			if (mb_strlen($user['password1']) < 6)
			{
				throw new Exception(bab_translate('Password must have at least 6 characters'));
			}
			
			
			$isconfirmed = 0;
			
			if($this->register)
			{
				
				$site = $this->getSiteSettings();
				switch( $site['email_confirm'] )
				{
					case 1: // Don't validate adresse email
						$isconfirmed = 0;
						break;
					case 2: // Confirm account without address email validation
						$isconfirmed = 1;
						break;
					default: //Confirm account by validationg address email
						$isconfirmed = 0;
					break;
				}
			}	
			

			require_once $GLOBALS['babInstallPath'].'admin/register.php';
			
			
			if (false === $id_user = bab_registerUser($user['sn'], $user['givenname'], '', $user['email'], $user['nickname'], $user['password1'], $user['password2'], $isconfirmed, $error))
			{
				throw new Exception($error);
			}
				

			
			
			// if in group directory, attach user to group
			
			$directory = $this->getDirectory();
			if ($directory['id_group'] > BAB_REGISTERED_GROUP)
			{
				bab_attachUserToGroup($id_user, $directory['id_group']);
			}
			
			$currentDGGroup = bab_getCurrentDGGroup();
			
			// delegated administrator, add the created user in the main delegation group
			if(bab_getCurrentAdmGroup() != 0 &&
					$currentDGGroup['id_group'] != BAB_ALLUSERS_GROUP &&
					$currentDGGroup['id_group'] != BAB_REGISTERED_GROUP &&
					$currentDGGroup['id_group'] != BAB_UNREGISTERED_GROUP &&
					bab_isDelegated('users'))
			{
				bab_attachUserToGroup($id_user, $currentDGGroup['id_group']);
			}
			
		}
		
		
		if (isset($user['setpwd']) && $user['setpwd'])
		{
			// password modification
			
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
		
		
		if (isset($user['profile']) && count($user['profile']) > 0)
		{
			$this->saveProfiles($id_user, $user['profile']);
		}
		
		
		if(!$this->register){
			// notifications
			
			if (!$id_user_original && !empty($user['notifyuser']))
			{
				// notify the user about creation of the account
				bab_registerUserNotify($user['sn'].' '.$user['givenname'], $user['email'], $user['nickname'], (empty($user['sendpwd']) ? null : $user['password1']));
			}
			
			if ($id_user_original && !empty($send_pwd))
			{
				// send the new password by email
				require_once $GLOBALS['babInstallPath'].'admin/register.php';	
				notifyUserPassword($user['password'], bab_getUserEmail($id_user), bab_getUserNickname($id_user));
			}
			
		} else {
			
			$registration_exception = null;
			$site = $this->getSiteSettings();
			
			$message = bab_translate("Thank You For Registering at our site");
			$fullname = bab_composeUserName($user['sn'] , $user['givenname']);
			
			if( $site['email_confirm'] == 2){
				
				// Confirm account without address email validation
				$AuthOvidentia = bab_functionality::get('PortalAuthentication/AuthOvidentia');
				/*@var $AuthOvidentia Func_PortalAuthentication_AuthOvidentia */
				$iIdUser = $AuthOvidentia->authenticateUserByLoginPassword($user['nickname'], $user['password1']);
				if(!is_null($iIdUser) && $AuthOvidentia->userCanLogin($iIdUser))
				{
					$AuthOvidentia->setUserSession($iIdUser, 0);
				}
				
				$warning = "( ". bab_translate("Account user is already confirmed")." )";
				
				
			}elseif( $site['email_confirm'] == 1 ){
				
				
				$warning = "( ". bab_translate("To let user log on your site, you must confirm his registration")." )";
				
				
			}else{
				
				$hash=md5($user['nickname'].bab_getHashVar());
				$message .= ', '.bab_translate("You will receive an email which let you confirm your registration.");
				$link = $GLOBALS['babUrlScript']."?tg=login&cmd=confirm&hash=$hash&name=". urlencode($user['nickname']);
				$warning = "";
				if (!notifyUserRegistration($link, $fullname, $user['email']))
				{
					$warning = "( ". bab_translate("The user has not received his confirmation email")." )";
					$registration_exception = new Exception(bab_translate("ERROR: Email message can't be sent !!"));
				}
			}
			
			
			notifyAdminRegistration($fullname, $user['email'], $warning);
			
			if (isset($registration_exception))
			{
				throw $registration_exception;
			}
			
			/*@var $babBody babBody */
			$babBody->addNextPageMessage($message);
		}
		
		return $id_user;
	}
	
	
	
	
	/**
	 * Save the user profiles
	 * @param int $id_user
	 * @param array $profiles		posted array
	 */
	protected function saveProfiles($id_user, $profiles)
	{
		global $babDB;
		
		$query = 'SELECT * from bab_profiles WHERE id IN('.$babDB->quote(array_keys($profiles)).')';
		
		if ($this->register)
		{
			$query .= " AND inscription='Y'";
		}
		
		$res = $babDB->db_query($query);
		while($profile = $babDB->db_fetch_assoc($res))
		{
			$values = $profiles[$profile['id']];
			
			$this->saveProfile($id_user, $profile, $values);
		}
	}
	
	
	/**
	 * Save a profile values for one user
	 * 
	 * @throws Exception
	 * 
	 * @param	int		$id_user
	 * @param	array	$profile
	 * @param	mixed	$values			one group id for a select or multiple group id in checkbox values
	 */
	protected function saveProfile($id_user, Array $profile, $values)
	{
		$id_groups = array();
		if ('Y' == $profile['multiplicity'])
		{
			foreach($values as $idg => $checked)
			{
				if ($checked && $idg)
				{
					$id_groups[] = $idg;
				}
			}	
		} elseif ($values) {
			$id_groups[] = $values;
		}
		
		
		if ('Y' == $profile['required'] && empty($id_groups))
		{
			throw new Exception(sprintf(bab_translate('At least one option must be selected on profile %s'), $profile['name']));
		}
		
		foreach($id_groups as $id_group)
		{
			bab_addUserToGroup($id_user, $id_group);
		}
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
			} catch (Exception $e) {
				$babPage->addError($e->getMessage());
			}
		}
		
		try {
			$editor = $this->getForm($id_user);
			$editor->setSelfPageHiddenFields();
			$babPage->addItem($editor);
			
			if (isset($_POST['user']))
			{
				$editor->setValues(array('user' => bab_pp('user')));
			}
		} catch (Exception $e) {
			$babPage->addError($e->getMessage());
		}
				
		
		return $babPage;
	}

}
