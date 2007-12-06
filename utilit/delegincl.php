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

$GLOBALS['babDG'] = array(	array("users", bab_translate("Users")),
				array("groups", bab_translate("Groups")),
				array("sections", bab_translate("Sections")),
				array("articles", bab_translate("Topics categories")),
				array("faqs", bab_translate("Faq")),
				array("forums", bab_translate("Forums")),
				array("calendars", bab_translate("Calendar")),
				array("mails", bab_translate("Mail")),
				array("directories", bab_translate("Directories")),
				array("approbations", bab_translate("Approbations")),
				array("filemanager", bab_translate("File manager")),
				array("orgchart", bab_translate("Charts")),
				array("taskmanager", bab_translate("Task Manager"))
				);

function bab_setCurrentUserDelegation($iIdDelegation)
{
	$_SESSION['babCurrentUserDelegation'] = (int) $iIdDelegation;
}


function bab_getCurrentUserDelegation()
{
	if(!array_key_exists('babCurrentUserDelegation', $_SESSION))
	{
		global $babBody;
		if(0 !== $babBody->currentAdmGroup)
		{
			$_SESSION['babCurrentUserDelegation'] = $babBody->currentAdmGroup;
		}
		else 
		{
			$_SESSION['babCurrentUserDelegation'] = 0;
		}
	}
	return (int) $_SESSION['babCurrentUserDelegation'];
}
?>
