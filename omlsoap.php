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
include_once $GLOBALS['babInstallPath']."utilit/nusoap/nusoap.php";
error_reporting(E_ALL ^ E_NOTICE);

$BABWS_NAMESPACE = $GLOBALS['babUrlScript'].'?tg=omlsoap';


$babSoapServer = new soap_server;

$babSoapServer->configureWSDL('babsoap', $BABWS_NAMESPACE, $GLOBALS['babUrlScript'].'?tg=omlsoap');

// set schema target namespace
//$babSoapServer->wsdl->schemaTargetNamespace = $BABWS_NAMESPACE;

$babSoapServer->wsdl->addComplexType(
		'babEntryStruct',
		'complexType',
		'struct',
		'all',
		'',
		array(	'name' => array('name'=>'name','type'=>'xsd:string'),
				'value' => array('name'=>'value','type'=>'xsd:string')
			)
		);

$babSoapServer->wsdl->addComplexType(
		'ArrayOfBabEntryStruct',
		'complexType',
		'array',
		'',
		'SOAP-ENC:Array',
		array(),
		array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:babEntryStruct[]')),
		'tns:babEntryStruct'
		);


$babSoapServer->wsdl->addComplexType(
		'ArrayOfarrayBabEntryStruct',
		'complexType',
		'array',
		'',
		'SOAP-ENC:Array',
		array(),
		array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArrayOfBabEntryStruct[]')),
		'tns:ArrayOfBabEntryStruct'
		);


$babSoapServer->wsdl->addComplexType(
   	 'login_return',
   	 'complexType',
   	 'struct',
   	 'all',
  	  '',
	array(
		'id' => array('name'=>'id', 'type'=>'xsd:string'),
		'error' => array('name' =>'error', 'type'=>'xsd:string'),
	)
);


//*/

$babSoapServer->register(
	'babSoapOvml', 
	array('container' => 'xsd:string', 'args' => 'tns:ArrayOfBabEntryStruct'), 
	array('return' => 'tns:ArrayOfarrayBabEntryStruct'), 
	$BABWS_NAMESPACE);

function babSoapOvml($container, $args)
	{
	global $babBody, $babDB;
	if( !bab_isAccessValid(BAB_SITES_WS_GROUPS_TBL, $babBody->babsite['id']) && !bab_isAccessValid(BAB_SITES_WSOVML_GROUPS_TBL, $babBody->babsite['id']))
		{
		return '';
		}

	$_SESSION['BAB_SESS_WSUSER'] = true;
	include_once $GLOBALS['babInstallPath']."utilit/omlincl.php";


	$tpl = new babOvTemplate();

	$tmp = array();

	for( $k=0; $k < count($args); $k++)
		{
		$tmp[$args[$k]['name']] = $args[$k]['value'];
		}
	return $tpl->handle_tag($container, '', $tmp, 'printoutws');
	}


$babSoapServer->register(
        'babSoapOvmlContent',
        array('content' => 'xsd:string', 'args' => 'tns:ArrayOfBabEntryStruct'),
        array('return'=>'xsd:string'),
        $BABWS_NAMESPACE); 

function babSoapOvmlContent($content, $args)
	{
	global $babBody, $babDB;
	$tmp = array();
	if( !bab_isAccessValid(BAB_SITES_WS_GROUPS_TBL, $babBody->babsite['id']) && !bab_isAccessValid(BAB_SITES_WSOVML_GROUPS_TBL, $babBody->babsite['id']))
		{
		return '';
		}

	$_SESSION['BAB_SESS_WSUSER'] = true;

	for( $k=0; $k < count($args); $k++)
		{
		$tmp[$args[$k]['name']] = $args[$k]['value'];
		}	
	return bab_printOvml($content, $tmp);
	}

$babSoapServer->register(
        'babSoapOvmlFile',
        array('file' => 'xsd:string', 'args' => 'tns:ArrayOfBabEntryStruct'),
        array('return'=>'xsd:string'),
        $BABWS_NAMESPACE); 

function babSoapOvmlFile($file, $args=array())
	{
	global $babBody, $babDB;
	$tmp = array();
	if( !bab_isAccessValid(BAB_SITES_WS_GROUPS_TBL, $babBody->babsite['id']) && !bab_isAccessValid(BAB_SITES_WSFILES_GROUPS_TBL, $babBody->babsite['id']))
		{
		return '';
		}

	$_SESSION['BAB_SESS_WSUSER'] = true;
	for( $k=0; $k < count($args); $k++)
		{
		$tmp[$args[$k]['name']] = $args[$k]['value'];
		}	
	return bab_printOvmlTemplate($file, $tmp);
	}

$babSoapServer->register(
        'login',
        array('nickname'=>'xsd:string', 'password'=>'xsd:string'),
        array('return'=>'tns:login_return'),
        $BABWS_NAMESPACE); 

function login($nickname, $password)
	{
	global $babBody, $babDB;
	include_once $GLOBALS['babInstallPath']."admin/register.php";
	include_once $babInstallPath.'utilit/loginIncl.php';
	
	$res = $babDB->db_query("select id from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($nickname)."' and password='". $babDB->db_escape_string(md5(strtolower($password))) ."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		list($iduser) = $babDB->db_fetch_row($res);
		$access = bab_isAccessValidByUser(BAB_SITES_WS_GROUPS_TBL, $babBody->babsite['id'], $iduser);
		}
	else
		{
		$access = bab_isAccessValid(BAB_SITES_WS_GROUPS_TBL, $babBody->babsite['id']);
		}

	if( $access )
		{
		$oAuthObject = @bab_functionality::get('PortalAuthentication/AuthOvidentia');
		if (false === $oAuthObject)
		{
		   // If the default authentication method 'AuthOvidentia' does not exist
		   // for example during first installation we (re)create it.
		   Func_PortalAuthentication_AuthOvidentia::registerAuthType();
		   $oAuthObject = @bab_functionality::get('PortalAuthentication/AuthOvidentia');
		}

       $iIdUser = $oAuthObject->authenticateUser($nickname, $password);           
	   if($oAuthObject->userCanLogin($iIdUser))
			{
			bab_setUserSessionInfo($iIdUser);
			bab_logUserConnectionToStat($iIdUser);
			bab_updateUserConnectionDate($iIdUser);
			bab_createReversableUserPassword($iIdUser, $password);
			bab_addUserCookie($iIdUser, $nickname, 0);
			$_SESSION['BAB_SESS_WSUSER'] = true;
			return array('id'=>session_id(), 'error'=>$error);	
			}
		}
	else
		{
		$babBody->msgerror = bab_translate("Access denied");
		}

	return array('id'=>0, 'error'=>$babBody->msgerror);	
	}

$babSoapServer->register(
        'logout',
        array('session'=>'xsd:string'),
        array('return'=>'xsd:int'),
        $BABWS_NAMESPACE); 

function logout($session)
	{
	global $babBody;
	include_once $GLOBALS['babInstallPath']."admin/register.php";
	include_once $babInstallPath.'utilit/loginIncl.php';
	
	if( isset($_REQUEST['WSSESSIONID']) && $_REQUEST['WSSESSIONID'] == $session && bab_isAccessValid(BAB_SITES_WS_GROUPS_TBL, $babBody->babsite['id']))
		{
		bab_signOff();
		$_SESSION['BAB_SESS_WSUSER'] = false;
		return 1;
		}
	else
		{
		return 0;
		}
	}

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA)? $HTTP_RAW_POST_DATA : '';
$babSoapServer->service($HTTP_RAW_POST_DATA);
exit;
?>