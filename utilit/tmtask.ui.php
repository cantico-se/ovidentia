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
include "base.php";


bab_Widgets()->includePhpClass('Widget_Frame');



class bab_TaskFullFrame extends Widget_Frame 
{
	protected $task;
	
	public function __construct(BAB_TM_Task $task)
	{
		$W = bab_Widgets();
	
		parent::__construct(null, $W->VBoxLayout());
	
		$this->task = $task;
		$this->addItem($this->getTaskFrame());
		
		if ($comments = $this->getCommentsFrame())
		{
			$this->addItem($comments);
		}
	
		$this->addClass('widget-bordered');
	}
	
	
	protected function labeledItem($name, $value)
	{
		$W = bab_Widgets();
		
		return $W->FlowItems($W->Label($name)->colon(), $W->Label($value))->setSpacing(.5,'em');
	}
	
	
	protected function labeledDate($name, $value)
	{
		if ($value !== '0000-00-00 00:00:00')
		{
			return $this->labeledItem($name, bab_shortDate(bab_mktime($value)));
		}
		
		return null;
	}
	
	
	protected function getTaskFrame()
	{
		$W = bab_Widgets();
		$frame = $W->Frame(null, $hbox = $W->HBoxLayout()->addClass('widget-full-width'))->addClass('task-fullframe');

		$hbox->addItem($vbox = $W->VBoxLayout());
		$hbox->addItem($this->getTaskActions());
		
		$vbox->addItem($W->Title($this->task->sShortDescription, 3));
		$vbox->addItem($this->labeledItem(bab_translate('Number'), $this->task->sTaskNumber));
		$vbox->addItem($this->labeledItem(bab_translate('Description'), $this->task->sDescription));
		
	//	$vbox->addItem($this->authorCardFrame($this->task->iIdUserCreated));
		
		if ($dates = $this->getTaskDates())
		{
			$vbox->addItem($dates);
		}
		
		
		return $frame;
	}
	
	
	protected function getTaskDates()
	{
		$W = bab_Widgets();
		$table = $W->TableView();
		$table->setCanvasOptions($table->Options()->width(30,'em'));
		$row = 0;
		
		if ('0000-00-00 00:00:00' !== $this->task->sPlannedStartDate)
		{
			$table->addItem($W->Label(bab_translate('Planned start date')), $row, 0);
			$table->addItem($W->Label(bab_shortDate(bab_mktime($this->task->sPlannedStartDate))), $row, 1);
			$row++;
		}
		
		if ('0000-00-00 00:00:00' !== $this->task->sPlannedEndDate)
		{
			$table->addItem($W->Label(bab_translate('Planned end date')), $row, 0);
			$table->addItem($W->Label(bab_shortDate(bab_mktime($this->task->sPlannedEndDate))), $row, 1);
			$row++;
		}
		
		
		
		if ('0000-00-00 00:00:00' !== $this->task->sStartDate)
		{
			$table->addItem($W->Label(bab_translate('Start date')), $row, 0);
			$table->addItem($W->Label(bab_shortDate(bab_mktime($this->task->sStartDate))), $row, 1);
			$row++;
		}
		
		if ('0000-00-00 00:00:00' !== $this->task->sEndDate)
		{
			$table->addItem($W->Label(bab_translate('End date')), $row, 0);
			$table->addItem($W->Label(bab_shortDate(bab_mktime($this->task->sEndDate))), $row, 1);
			$row++;
		}
		
		if ($row === 0)
		{
			return null;
		}
		
		return $table;
	}
	
	
	
	/**
	 * @return Widget_Displayable_Interface
	 */
	protected function getTaskActions()
	{
		$W = bab_Widgets();
		bab_functionality::includeOriginal('Icons');
		
		$frame = $W->Frame();
		$frame->addClass(Func_Icons::ICON_LEFT_24);
		
		$url = bab_url::get_request('tg', 'iIdTask', 'sFromIdx', 'isProject', 'iIdProjectSpace', 'iIdProject');
		$url->idx = BAB_TM_IDX_DISPLAY_TASK_FORM;
		
		$frame->addItem($W->Link($W->Icon(bab_translate('Edit'), Func_Icons::ACTIONS_DOCUMENT_EDIT), $url->toString()));
		
		return $frame;
	}
	
	
	protected function authorCardFrame($id_user)
	{
		$username = bab_getUserName($id_user);
		$W = bab_Widgets();
		$url = bab_getUserDirEntryLink($id_user, BAB_DIR_ENTRY_ID_USER);
		
		if (false != $url)
		{
			$namewidget = $W->Link($username, $url)->setOpenMode(Widget_Link::OPEN_POPUP);
		} else {
			$namewidget = $W->Label($username);
		}
		
		
		
		$entry = bab_getDirEntry($id_user);
		if ($entry && isset($entry['jpegphoto']['photo']))
		{
			$photo = $entry['jpegphoto']['photo'];
			/*@var $photo bab_dirEntryPhoto */
			$photo->setThumbSize(24, 24);
			
			$avatar = $W->Image($photo->getUrl());
			
			return $W->FlowItems($avatar, $namewidget)->setVerticalAlign('middle')->setHorizontalSpacing(.5,'em');
		}
		
		return $namewidget;
	}
	
	/**
	 * 
	 * @return Widget_Section
	 */
	private function CommentForm()
	{
		$W = bab_Widgets();
		$form = $W->Form()->setName('comment');
		
		$form->addItem($W->TextEdit()->addClass('widget-full-width'));
		$form->addItem($W->SubmitButton()->setLabel(bab_translate('Send')));
		
		return $W->Section(bab_translate('Add a comment on the task'), $form)->setFoldable(true, true);
	}
	
	
	private function CommentFrame(Array $comment)
	{
		require_once dirname(__FILE__).'/dateTime.php';
		$W = bab_Widgets();
		
		$frame = $W->Frame()->addClass('task-comment');
		
		
		$created = BAB_DateTimeUtil::relativeDate($comment['created'], true, true);
		$author = $this->authorCardFrame($comment['idUserCreated']);
		
		$frame->addItem($W->HBoxItems($author, $W->Label($created))->setHorizontalSpacing(2,'em')->setVerticalAlign('middle'));
		$frame->addItem($W->RichText($comment['commentary'])->addClass('comment-content'));
		
		return $frame;
	}
	
	
	/**
	 * @return Widget_Section
	 */
	protected function getCommentsFrame()
	{
		global $babDB;
		$W = bab_Widgets();
		
		$layout = $W->VBoxLayout()->setVerticalSpacing(1,'em');
		
		$layout->addItem($this->CommentForm());

		$I = $this->task->selectCommentaries();
		
		foreach($I as $comment)
		{
			$layout->addItem($this->CommentFrame($comment));
		}
		
		return $W->Section(bab_translate('Comments'), $layout)->setFoldable(false);
	}
}