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


/**
* @internal SEC1 PR 12/04/2007 FULL
*/

include_once "base.php";
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $babInstallPath."utilit/calincl.php";


function calendarsCategories()
	{
	global $babBody;
	class calendarsCategoriesCls
		{
		var $nametxt;
		var $urlname;
		var $url;
		var $desc;
		var $desctxt;
		var $bgcolor;
		var $bgcolortxt;
				
		var $arr = array();
		var $db;
		var $count;
		var $countcal;
		var $res;
		var $altbg = true;

		function calendarsCategoriesCls()
			{
			global $babDB;
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->bgcolortxt = bab_translate("Color");
			$this->add = bab_translate("Add");
			$this->t_delete = bab_translate('Delete');
			$this->t_delete_checked = bab_translate("Delete checked items");
			$this->t_confirm_delete = bab_translate("Do you want to delete selected items?");
			$this->urladdcat = $GLOBALS['babUrlScript'].'?tg=admcals&idx=addc';
			
			if ($delete_category = bab_pp('delete_category')) {
				foreach($delete_category as $id_category) {
					deleteCalendarCategory($id_category);
				}
				
				Header("Location:". $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
				exit;
			}
			
			$this->res = $babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL." ORDER BY name,description ");
			$this->countcal = $babDB->db_num_rows($this->res);
			}
			
		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countcal)
				{
				$this->altbg = !$this->altbg;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=modc&idcat=".$this->arr['id']);
				$this->urlname = bab_toHtml($this->arr['name']);
				$this->desc = bab_toHtml($this->arr['description']);
				$this->bgcolor = bab_toHtml($this->arr['bgcolor']);
				$this->id_category = (int) $this->arr['id'];
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		}

	$temp = new calendarsCategoriesCls();
	$babBody->babecho(	bab_printTemplate($temp, "admcals.html", "categorieslist"));
	}



function calendarsAddCategory($catname, $catdesc, $bgcolor)
	{
	global $babBody;
	class calendarsAddCategoryCls
		{
		var $name;
		var $description;
		var $bgcolor;
		var $groupsname;
		var $idgrp;
		var $count;
		var $add;
		var $db;
		var $arrgroups = array();
		var $userid;

		function calendarsAddCategoryCls($catname, $catdesc, $bgcolor)
			{
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->bgcolortxt = bab_translate("Color");
			$this->addtxt = bab_translate("Add Category");
			$this->idcat = '';
			$this->add = 'addcat';
			$this->tgval = 'admcals';
			$this->name = bab_toHtml($catname);
			$this->desc = bab_toHtml($catdesc);
			$this->bgcolor = bab_toHtml($bgcolor);
			$this->selctorurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=selectcolor&idx=popup&callback=setColor");
			}
		}

	$temp = new calendarsAddCategoryCls($catname, $catdesc, $bgcolor);
	$babBody->babecho( bab_printTemplate($temp,"admcals.html", "categorycreate"));
	}


function calendarsPublic()
	{
	global $babBody;

	class calendarsPublicCls
		{
		var $altbg = true;
		function calendarsPublicCls()
			{
			global $babDB, $babBody;

			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->disabledtxt = bab_translate("Disabled");
			$this->rightstxt = bab_translate("Rights");
			$this->t_delete = bab_translate("Delete");
			$this->add = bab_translate("Add");
			$this->urladdcal = $GLOBALS['babUrlScript'].'?tg=admcals&idx=addp';
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");

			$this->res = $babDB->db_query("select cpt.*, ct.actif, ct.id as idcal from ".BAB_CAL_PUBLIC_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.type='".BAB_CAL_PUB_TYPE."' and id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' ORDER BY cpt.name");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
		
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->name = bab_toHtml($arr['name']);
				$this->description = bab_toHtml($arr['description']);
				$this->idcal = bab_toHtml($arr['idcal']);
				$this->nameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=modp&grpid=".$arr['id']."&idcal=".$arr['idcal']);
				$this->rightsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=rigthsp&idcal=".$arr['idcal']);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=delp&idcal=".$arr['idcal']);
				if( $arr['actif'] == 'Y')
					{
					$this->calchecked = '';
					}
				else
					{
					$this->calchecked = 'checked';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}

	$temp = new calendarsPublicCls();
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendarslist"));
	}


function bab_orderDomain(){
	global $babBody, $babDB;
	$domainslist = bab_pp('domainslist');
	
	if($domainslist){
		for($i=0; $i < count($domainslist); $i++)
		{
			$babDB->db_query("update ".BAB_CAL_DOMAINS_TBL." set `order`='".($i+1)."' where id='".$domainslist[$i]."'");
		}
		$babBody->addError(bab_translate('Ordering done'));
		return true;
	}
	$babBody->addError(bab_translate('Error, order lost'));
	return false;
}

function bab_calendarsOrderDomains()
{
	global $babBody;
	class temp
	{		

		var $sorta;
		var $sortd;
		var $id;

		function temp()
		{
			global $babBody, $babDB, $BAB_SESS_USERID;
			
			$id = bab_gp('id');
			if(!$id){
				$id = 0;
				$domainname = bab_translate('Domains');
			}else{
				$sql = "select * FROM ".BAB_CAL_DOMAINS_TBL." WHERE id = ".$babDB->quote($id);
				$res = $babDB->db_query($sql);
				if($res && $arr = $babDB->db_fetch_assoc($res)){
					$domainname = $arr['name'];
				}else{
					$id = 0;
					$domainname = bab_translate('Domains');
				}
			}
			
			$this->id = $id;
			$this->topdomainname = "---- ".$domainname." ----";
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->sorta = bab_translate("Sort ascending");
			$this->sortd = bab_translate("Sort descending");
			$this->create = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			
			$req = "select * FROM ".BAB_CAL_DOMAINS_TBL." WHERE id_parent = ".$babDB->quote($id)." ORDER BY `order` ASC, name ASC";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
		}

		function getnext()
		{
			static $i = 0;
			if( $i < $this->count){
				$arr = $this->db->db_fetch_array($this->res);
				
				$this->iddomain = bab_toHtml($arr['id']);
				$this->domainname = bab_toHtml($arr['name']);
				$i++;
				return true;
			}else{
				return false;
			}
		}
	}
	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "sites.html", "scripts"));
	$babBody->babecho(bab_printTemplate($temp,"domain.html", "domainorder"));
	return $temp->count;
}

function bab_rmDomain()
{
	global $babDB, $babBody;
	
	$id = bab_gp('id');
	if($id){
		$sql = "DELETE FROM ".BAB_CAL_DOMAINS_TBL." WHERE id = ".$babDB->quote($id)." OR ( id_parent=".$babDB->quote($id)." AND id_parent != '0')";
		$babDB->db_query($sql);
		
		$babBody->addError(bab_translate('Deletion done'));
		return true;
	}
	return false;	
}

function bab_saveDomain()
{
	global $babDB, $babBody;
	
	$id = bab_pp('id');
	$name = bab_pp('name');
	$id_parent = bab_pp('id_parent', 0);
	
	if($name){
		$name = str_replace(array(',', ':', '/'), '', $name);//Those chars a forbiden due to caldav backend
		if($id_parent != 0){
			$sql = "select * FROM ".BAB_CAL_DOMAINS_TBL." WHERE id = ".$babDB->quote($id_parent);
			$res = $babDB->db_query($sql);
			if($res && $arr = $babDB->db_fetch_assoc($res)){
				if($arr['id_parent'] != 0){
					$babBody->addError(bab_translate('Error in the insertion'));
					return false;
				}
			}else{
				$id_parent = 0;
			}
		}
		if($id){
			$babDB->db_query("
				UPDATE ".BAB_CAL_DOMAINS_TBL."
				SET name = ".$babDB->quote($name).",
					id_parent = ".$babDB->quote($id_parent)."
				WHERE id = ".$babDB->quote($id)
			);
			
			$babBody->addError(bab_translate('Update done'));
		}else{
			$babDB->db_query("
				INSERT INTO ".BAB_CAL_DOMAINS_TBL." (name, id_parent)
				VALUES (".$babDB->quote($name).", ".$babDB->quote($id_parent).")"
			);
			
			$babBody->addError(bab_translate('Addition done'));
		}
		return true;
	}
	return false;	
}

function bab_calendarsEditDomain()
{
	global $babDB, $babBody;
	$W = bab_Widgets();
	$W->includeCss();
	
	$page = $W->BabPage();
	$page->addStyleSheet($GLOBALS['babInstallPath'].'styles/domain.css');
	
	$Form = $W->Form()->setId('bab-domaine-form')
		->setHiddenValue('tg', bab_rp('tg'))
		->setHiddenValue('idx', 'domain')
		->setHiddenValue('action', 'saveDomain')
		->addItem(
			$W->VBoxItems(
				$W->FlowItems(
					$tmplbl = $W->Label(bab_translate('Label'))->colon(),
					$label = $W->LineEdit()->setName('name')->setMandatory(true, bab_translate('The label is mandatory'))
				)->setHorizontalSpacing(.5,'em'),
				$W->SubmitButton()->validate(true)->setLabel(bab_translate('Save'))
			)->setVerticalSpacing(1,'em')
		);
	
	$id = bab_gp('id');
	$idParent = bab_gp('idparent');
	if($id){
		$res = $babDB->db_query("select * FROM ".BAB_CAL_DOMAINS_TBL." WHERE id = ".$babDB->quote($id));
		if($res && $arr = $babDB->db_fetch_assoc($res)){
			$idParent = $arr['id_parent'];
			$label->setValue($arr['name']);
			$Form->setHiddenValue('id', $arr['id']);
		}else{
			$idParent = '0';
		}
	}
	if($idParent){
		$babBody->title = bab_translate("Value");
		$Form->setHiddenValue('id_parent', $idParent);
	}
	if($idParent === '0'){
		$babBody->title = bab_translate("Domain");
		$Form->setHiddenValue('id_parent', $idParent);
	}
	
	$page->addItem($Form);
	
	
	$page->displayHtml();
}

function bab_calendarsDomain()
{
	global $babDB;
	$W = bab_Widgets();
	$W->includeCss();
	
	$page = $W->BabPage();
	
	$treeView = $W->SimpleTreeView('bab-domain-tree');
	$treeView->setPersistent(true);
	
	$res = $babDB->db_query("select * FROM ".BAB_CAL_DOMAINS_TBL." ORDER BY `order` ASC, name ASC");
	
	$domaines = array();
	while($res && $arr = $babDB->db_fetch_assoc($res)){
		$domaines[$arr['id_parent']][] = array('name' => $arr['name'], 'id' => $arr['id']);
	}
	//var_dump($domaines);
	$element =& $treeView->createElement(0, '', bab_translate('Domains'), '', '');
	$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
	$element->addAction(
		'addDomain',
		bab_translate('Add a domain'),
		$GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png',
		'?tg=admcals&idx=adddomain&idparent=0',
		''
	);
	$element->addAction(
		'orderDomain',
		bab_translate('Order domains'),
		$GLOBALS['babSkinPath'] . 'images/Puces/a-z.gif',
		'?tg=admcals&idx=orderdomain',
		''
	);
	$treeView->appendElement($element, null);
	
	foreach($domaines as $idparent => $domainesParent){
		foreach($domainesParent as $order => $domaine){
			$element =& $treeView->createElement($domaine['id'], '', $domaine['name'], '', '');
			
			if($idparent == 0){
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/Puces/folder_add.png');
				$element->addAction(
						'editDomain',
						bab_translate('Edit this domain'),
						$GLOBALS['babSkinPath'] . 'images/Puces/edit.png',
						'?tg=admcals&idx=editdomain&id='.$domaine['id'],
						''
				);
				$element->addAction(
						'addValueDomain',
						bab_translate('Add a value'),
						$GLOBALS['babSkinPath'] . 'images/Puces/edit_add.png',
						'?tg=admcals&idx=addvalue&idparent='.$domaine['id'],
						''
				);
				$element->addAction(
						'orderValueDomain',
						bab_translate('Order values'),
						$GLOBALS['babSkinPath'] . 'images/Puces/a-z.gif',
						'?tg=admcals&idx=ordervalue&id='.$domaine['id'],
						''
				);
				$element->addAction(
						'rmDomain',
						bab_translate('Remove this domain'),
						$GLOBALS['babSkinPath'] . 'images/Puces/edit_remove.png',
						'?tg=admcals&idx=domain&action=rmDomain&id='.$domaine['id'],
						"return confirm('".bab_translate('This will remove this domain and all those values?')."')"
				);
			}else{
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/Puces/check-green.gif');
				$element->addAction(
						'editValue',
						bab_translate('Edit this value'),
						$GLOBALS['babSkinPath'] . 'images/Puces/edit.png',
						'?tg=admcals&idx=editvalue&id='.$domaine['id'],
						''
				);
				$element->addAction(
						'rmDomainValue',
						bab_translate('Remove this value'),
						$GLOBALS['babSkinPath'] . 'images/Puces/edit_remove.png',
						'?tg=admcals&idx=domain&action=rmDomain&id='.$domaine['id'],
						"return confirm('".bab_translate('This will remove this value?')."')"
				);
			}
			$treeView->appendElement($element, $idparent);
		}
	}
	
	$page->addItem($treeView);
	
	$page->displayHtml();
}

function calendarsResource()
	{
	global $babBody;

	class calendarsResourceCls
		{
		var $altbg = true;

		function calendarsResourceCls()
			{
			global $babDB, $babBody;

			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->disabledtxt = bab_translate("Disabled");
			$this->rightstxt = bab_translate("Rights");
			$this->t_delete = bab_translate("Delete");
			$this->add = bab_translate("Add");
			$this->urladdcal = $GLOBALS['babUrlScript'].'?tg=admcals&idx=addr';
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");

			$this->res = $babDB->db_query("select cpt.*, ct.actif, ct.id as idcal from ".BAB_CAL_RESOURCES_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.type='".BAB_CAL_RES_TYPE."' and id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' ORDER BY cpt.name");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
		
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->name = bab_toHtml($arr['name']);
				$this->description = bab_toHtml($arr['description']);
				$this->idcal = bab_toHtml($arr['idcal']);
				$this->nameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=modr&grpid=".$arr['id']."&idcal=".$arr['idcal']);
				$this->rightsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=rigthsr&idcal=".$arr['idcal']);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=delr&idcal=".$arr['idcal']);
				if( $arr['actif'] == 'Y')
					{
					$this->calchecked = '';
					}
				else
					{
					$this->calchecked = 'checked';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}

	$temp = new calendarsResourceCls();
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendarslist"));
	}

function calendarsAddPublic($name, $desc, $idsa)
	{
	global $babBody;

	class calendarsAddPublicCls
		{

		function calendarsAddPublicCls($name, $desc, $idsa)
			{
			global $babBody, $babDB;
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->addtxt = bab_translate("Add");
			$this->approbationtxt = bab_translate("Approbation schema");
			$this->nonetxt = bab_translate("None");
			$this->calname = bab_toHtml($name);
			$this->caldesc = bab_toHtml($desc);
			$this->calidsa = bab_toHtml($idsa);
			$this->add = "addp";
			$this->idcal = '';
			$this->tgval = 'admcals';
			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' order by name asc");
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $babDB->db_num_rows($this->sares);
			}

		function getnextschapp()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->saname = bab_toHtml($arr['name']);
				$this->said = bab_toHtml($arr['id']);
				if( $this->said == $this->calidsa )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new calendarsAddPublicCls($name, $desc, $idsa);
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendaraddp"));
	}

function calendarsAddResource($name, $desc, $idsa)
	{
	global $babBody;

	class calendarsAddResourceCls
		{

		function calendarsAddResourceCls($name, $desc, $idsa)
			{
			global $babBody, $babDB;
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->addtxt = bab_translate("Add");
			$this->approbationtxt = bab_translate("Approbation schema");
			$this->nonetxt = bab_translate("None");
			$this->t_availability_lock = bab_translate("The availability of the resource is mandatory to create an event");
			$this->calname = bab_toHtml($name);
			$this->caldesc = bab_toHtml($desc);
			$this->calidsa = bab_toHtml($idsa);
			$this->add = "addr";
			$this->idcal = '';
			$this->tgval = 'admcals';
			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' order by name asc");
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $babDB->db_num_rows($this->sares);
			}

		function getnextschapp()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->saname = bab_toHtml($arr['name']);
				$this->said = bab_toHtml($arr['id']);
				if( $this->said == $this->calidsa )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new calendarsAddResourceCls($name, $desc, $idsa);
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendaraddr"));
	}

function calendarsDelResource($idcal)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($idcal)
			{
			$this->message = bab_translate("Are you sure you want to delete this calendar");
			$this->title = bab_toHtml(bab_getCalendarOwnerName($idcal, BAB_CAL_RES_TYPE));
			$this->warning = bab_translate("WARNING: This operation will delete the calendar and all associated events"). "!";
			$this->urlyes = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=res&idcal=".$idcal."&action=Yes");
			$this->yes = bab_translate("Yes");
			$this->urlno = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=res");
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($idcal);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function calendarsDelPublic($idcal)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($idcal)
			{
			$this->message = bab_translate("Are you sure you want to delete this calendar");
			$this->title = bab_getCalendarOwnerName($idcal, BAB_CAL_PUB_TYPE);
			$this->warning = bab_translate("WARNING: This operation will delete the calendar and all associated events"). "!";
			$this->urlyes = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=pub&idcal=".$idcal."&action=Yes");
			$this->yes = bab_translate("Yes");
			$this->urlno = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($idcal);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function addPublicCalendar($calname, $caldesc, $calidsa)
{
	global $babDB, $babBody;

	if( empty($calname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}


	$babDB->db_query("insert into ".BAB_CAL_PUBLIC_TBL." (name, description, id_dgowner, idsa) values ('" .$babDB->db_escape_string($calname). "', '".$babDB->db_escape_string($caldesc)."', '".$babDB->db_escape_string($babBody->currentAdmGroup)."', '".$babDB->db_escape_string($calidsa)."')");
	$idowner = $babDB->db_insert_id();
	$babDB->db_query("insert into ".BAB_CALENDAR_TBL." (owner, type) values ('" .$babDB->db_escape_string($idowner). "', '".BAB_CAL_PUB_TYPE."')");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
	exit;
}

function addResourceCalendar($calname, $caldesc, $calidsa)
{
	global $babDB, $babBody;

	if( empty($calname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}
		
	
	$availability_lock = isset($_POST['availability_lock']) ? '1' : '0';
	

	$babDB->db_query("
		insert into ".BAB_CAL_RESOURCES_TBL." 
			(name, description, id_dgowner, idsa, availability_lock) 
		VALUES 
			(
				'" .$babDB->db_escape_string($calname). "', 
				'".$babDB->db_escape_string($caldesc)."', 
				'".$babDB->db_escape_string($babBody->currentAdmGroup)."', 
				'".$babDB->db_escape_string($calidsa)."',
				".$babDB->quote($availability_lock)."
			)
	");
	
	$idowner = $babDB->db_insert_id();
	
	$babDB->db_query("insert into ".BAB_CALENDAR_TBL." (owner, type) values ('" .$babDB->db_escape_string($idowner). "', '".BAB_CAL_RES_TYPE."')");
	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
	exit;
}

function addCategoryCalendar($catname, $catdesc, $bgcolor)
{
	global $babDB, $babBody;

	if( empty($catname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	$babDB->db_query("insert into ".BAB_CAL_CATEGORIES_TBL." (name, description, bgcolor) values ('" .$babDB->db_escape_string($catname). "', '".$babDB->db_escape_string($catdesc)."', '".$babDB->db_escape_string($bgcolor)."')");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
	exit;
}

function updatePublicCalendars($calids)
{
	global $babDB, $babBody;
	
	$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where type='".BAB_CAL_PUB_TYPE."'");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( count($calids) > 0 && in_array($row['id'], $calids))
			$enabled = "N";
		else
			$enabled = "Y";

		$babDB->db_query("update ".BAB_CALENDAR_TBL." set actif='".$babDB->db_escape_string($enabled)."' where id='".$babDB->db_escape_string($row['id'])."'");
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
	exit;
}

function updateResourceCalendars($calids)
{
	global $babDB, $babBody;
	
	$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where type='".BAB_CAL_RES_TYPE."'");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( count($calids) > 0 && in_array($row['id'], $calids))
			$enabled = "N";
		else
			$enabled = "Y";

		$babDB->db_query("update ".BAB_CALENDAR_TBL." set actif='".$babDB->db_escape_string($enabled)."' where id='".$babDB->db_escape_string($row['id'])."'");
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
	exit;
}


function deleteCalendarCategory($idcat)
{
	global $babDB, $babBody;
	
	$babDB->db_query("delete from ".BAB_CAL_CATEGORIES_TBL." WHERE id=".$babDB->quote($idcat));
	$babDB->db_query("update ".BAB_CAL_EVENTS_TBL." set id_cat='0' WHERE id_cat=".$babDB->quote($idcat));
	$babDB->db_query("update ".BAB_VAC_COLLECTIONS_TBL." set id_cat='0' WHERE id_cat=".$babDB->quote($idcat));
}

/* main */
if( !bab_isUserAdministrator() && $babBody->currentDGGroup['calendars'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'pub');

if( bab_rp('addc'))
{
	if( "addp" == bab_rp('addc') )
	{
		if( addPublicCalendar(bab_rp('calname'), bab_rp('caldesc'), bab_rp('calidsa')))
		{
			$idx = "pub";
		}
		else
		{
			$idx = "addp";
		}
	}elseif( "addr" == bab_rp('addc') )
	{
		if( addResourceCalendar(bab_rp('calname'), bab_rp('caldesc'), bab_rp('calidsa')))
		{
			$idx = "res";
		}
		else
		{
			$idx = "addr";
		}
	}
}
elseif( bab_rp('sublist'))
{
	if( $idx == "pub" )
	{
		$calids = bab_rp('calids', array());
		updatePublicCalendars($calids);
	}elseif( $idx == "res" )
	{
		$calids = bab_rp('calids', array());
		updateResourceCalendars($calids);
	}
}
elseif("Yes" == bab_rp('action'))
{
	if( $idx == "pub" )
	{
		bab_deleteCalendar(bab_rp('idcal'));
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		exit;
	}elseif( $idx == "res" )
	{
		bab_deleteCalendar(bab_rp('idcal'));
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		exit;
	}
}
elseif("saveDomain" == bab_pp('action')){
	bab_saveDomain();
}
elseif("rmDomain" == bab_gp('action')){
	bab_rmDomain();
}
elseif("orderDomain" == bab_pp('action')){
	bab_orderDomain();
}
elseif( "addcat" == bab_rp('add') && bab_isUserAdministrator())
{
	if( !addCategoryCalendar(bab_rp('catname'), bab_rp('catdesc'), bab_rp('bgcolor')))
	{
		$idx = "addc";
	}
}

switch($idx)
	{
	case "addc":
		if( bab_isUserAdministrator() )
		{
			calendarsAddCategory(bab_rp('catname'), bab_rp('catdesc'), bab_rp('bgcolor'));
			$babBody->title = bab_translate("Add event category");
			$babBody->addItemMenu("pub", bab_translate("Public"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
			$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
			$babBody->addItemMenu("addc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addc");
		}
		break;
	case "cats":
		if( bab_isUserAdministrator() )
		{
			calendarsCategories();
			$babBody->title = bab_translate("Calendar categories list");
			$babBody->addItemMenu("pub", bab_translate("Public"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
			$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
		/*$babBody->addItemMenu("addc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addc");*/
		}
		break;

	case "delr":
		calendarsDelResource(bab_rp('idcal'));
		$babBody->title = bab_translate("Delete resource calendar");
		$babBody->addItemMenu("pub", bab_translate("Public"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("delr", bab_translate("Del"), $GLOBALS['babUrlScript']."?tg=admcals&idx=delp");
		if( bab_isUserAdministrator() )
		{
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
		}
		break;

	case "delp":
		calendarsDelPublic(bab_rp('idcal'));
		$babBody->title = bab_translate("Delete public calendar");
		$babBody->addItemMenu("pub", bab_translate("Public"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("delp", bab_translate("Del"), $GLOBALS['babUrlScript']."?tg=admcals&idx=delr");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		if( bab_isUserAdministrator() )
		{
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
		}
		break;

	case "addr":
		calendarsAddResource(bab_rp('calname'), bab_rp('caldesc'), bab_rp('calidsa'));
		$babBody->title = bab_translate("Add resource calendar");
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("addr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addr");
		if( bab_isUserAdministrator() )
		{
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
		}
		break;
	case "addp":
		calendarsAddPublic(bab_rp('calname'), bab_rp('caldesc'), bab_rp('calidsa'));
		$babBody->title = bab_translate("Add public calendar");
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("addp", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addp");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		if( bab_isUserAdministrator() )
		{
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
		}
		break;
	case "res":
		calendarsResource();
		$babBody->title = bab_translate("Resources calendars List");
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		/*$babBody->addItemMenu("addr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addr");*/
		if( bab_isUserAdministrator() )
		{
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
		}
		break;
	case "domain":
		if( bab_isUserAdministrator() )
		{
			bab_calendarsDomain();
			$babBody->title = bab_translate("Domains");
			$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
			$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
		}
		break;
	case "adddomain":
	case "addvalue":
	case "editdomain":
	case "editvalue":
		if( bab_isUserAdministrator() )
		{
			bab_calendarsEditDomain();
			$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
			$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu($idx, bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=".$idx);
		}
		break;
	case "ordervalue":
	case "orderdomain":
		if( bab_isUserAdministrator() )
		{
			bab_calendarsOrderDomains();
			$babBody->title = bab_translate("Domains");
			$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
			$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
			$babBody->addItemMenu($idx, bab_translate("Order Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=".$idx);
		}
		break;
	case "pub":
	default:
		calendarsPublic();
		$babBody->title = bab_translate("Public calendars List");
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		/*$babBody->addItemMenu("addp", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addp");*/
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		if( bab_isUserAdministrator() )
		{
			$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
			$babBody->addItemMenu("domain", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=admcals&idx=domain");
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','AdminCalendars');
?>
