<?php
define("ADDON_OW_CONFIGURATION_TBL", "ow_configuration");
define("ADDON_OW_USERS_TBL", "ow_users");

function ow_translate($str)
	{
		return bab_translate($str, $GLOBALS['babAddonFolder']);
	}
?>