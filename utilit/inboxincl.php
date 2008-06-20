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
include_once 'base.php';

/**
 * Get mail account
 * @param	int		$id_account
 * @return 	array
 */
function bab_getMailAccount($id_account) {
	
	global $babDB, $BAB_HASH_VAR, $BAB_SESS_USERID, $babBody;
	
	static $accounts = array();
	
	if (!isset($accounts[$id_account])) {
	
		if( empty($id_account)) {
			$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass 
			from ".BAB_MAIL_ACCOUNTS_TBL." 
			where 
				owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and prefered='Y'";
			
			$res = $babDB->db_query($req);
			if( !$res || $babDB->db_num_rows($res) == 0 ) {
				$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass 
				from ".BAB_MAIL_ACCOUNTS_TBL." 
				where 
					owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			}
		} else {
			$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass 
			from ".BAB_MAIL_ACCOUNTS_TBL." 
			where 
				id='".$babDB->db_escape_string($id_account)."' 
				and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
		}
		
		$res = $babDB->db_query($req);
		if( !$res || $babDB->db_num_rows($res) == 0 ) {
			$babBody->addError(bab_translate("Error, there is no account"));
			return false;
		}
		
		$accounts[$id_account] = $babDB->db_fetch_array($res);
	}
	
	return $accounts[$id_account];
}




/**
 * Open imap or pop3 stream with ovidentia mail account
 * @param	int		$id_account
 * @return	resource|false
 */
function bab_getMailBox($id_account) {

	global $babDB;

	$arr = bab_getMailAccount($id_account);

	$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$babDB->db_escape_string($arr['domain'])."'";
	$res2 = $babDB->db_query($req);
	if( !$res2 || $babDB->db_num_rows($res2) == 0 )
		{
		$babBody->addError(bab_translate("Error, invalid domain"));
		return false;
	}
		
		
	$arr2 = $babDB->db_fetch_array($res2);
	$protocol = '';
	if( isset($GLOBALS['babImapProtocol']) && count($GLOBALS['babImapProtocol'])) 
		{
		$protocol = '/'.implode('/', $GLOBALS['babImapProtocol']);
	} else {
		if ('imap' === $arr2['access']) {
			$protocol = '/novalidate-cert';
		}
	}

	$cnxstring = "{".$arr2['inserver'].":".$arr2['inport']."/".$arr2['access'].$protocol."}INBOX";
	$mbox = @imap_open($cnxstring, $arr['login'], $arr['accpass']);
	if(!$mbox)
		{
		$babBody->addError(imap_last_error());
		return false;
	}
		
	return $mbox;
}