<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";

function isUserManager($item)
	{
	global $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_FAQCAT_TBL." where id='$item'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $BAB_SESS_USERID == $arr['id_manager'])
			return true;
		}
	return false;
	}

function listCategories()
	{
	global $babBody;
	$arrid = array();
	class temp
		{
	
		var $arr = array();
		var $arrid = array();
		var $db;
		var $count;
		var $res;
		var $urlcategory;
		var $namecategory;

		function temp($arrid)
			{
			$this->db = $GLOBALS['babDB'];
			$this->count = count($arrid);
			$this->arrid = $arrid;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from ".BAB_FAQCAT_TBL." where id='".$this->arrid[$i]."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->arr = $this->db->db_fetch_array($res);
					$this->arr['description'] = $this->arr['description'];// nl2br($this->arr['description']);
					$this->urlcategory = $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$this->arr['id'];
					$this->namecategory = $this->arr['category'];
					}
				$i++;
				return true;
				}		
			else
				return false;
			}
		}
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_FAQCAT_TBL."";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
			{
			array_push($arrid, $row['id']);
			}
		}

	$temp = new temp($arrid);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "categorylist"));

	return count($arrid);
	}


function listQuestions($idcat)
	{
	global $babBody;
	class temp
		{
		var $idcat;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $question;
		var $questionurl;

		function temp($id)
			{
			$this->idcat = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where idcat='$id' order by id asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->question = $this->arr['question'];
				$this->questionurl = $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$this->idcat."&idq=".$this->arr['id'];
				//$this->arr['response'] = nl2br($this->arr['response']);
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($idcat);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "questionlist"));
	return true;
	}

function viewQuestion($idcat, $id)
	{
	global $babBody;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $return;
		var $returnurl;

		function temp($idcat, $id)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->arr['response'] = bab_replace($this->arr['response']);
			$this->returnurl = $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$idcat;
			$this->return = bab_translate("Return to Questions");
			}

		}

	$temp = new temp($idcat, $id);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "viewquestion"));
	return true;
	}

function viewPopupQuestion($id)
	{
	global $babBody;

	class temp
		{
	
		var $arr = array();
		var $db;
		var $res;
		var $more;
		var $baCss;
		var $close;


		function temp($id)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->close = bab_translate("Close");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where id='$id'";
			$res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($res);
			$this->arr['response'] = bab_replace($this->arr['response']);
			}
		}
	
	$temp = new temp($id);
	echo bab_printTemplate($temp,"faq.html", "popupquestion");
	}

function faqPrint($idcat)
	{
	global $babBody;
	class temp
		{
		
		var $arr1 = array();
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $index=0;
		var $return;
		var $indexquestions;
		var $sitename;
		var $urlsite;

		function temp($id)
			{
			global $babSiteName, $babUrl;
			$this->return = "Go to Top";
			$this->indexquestions = "Index of questions";
			$this->sitename = $babSiteName;
			$this->urlsite = $babUrl;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQCAT_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr1 = $this->db->db_fetch_array($this->res);
			$req = "select * from ".BAB_FAQQR_TBL." where idcat='$id'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$i++;
				$this->index++;
				return true;
				}
			else
				{
				mysql_data_seek($this->res, 0);
				$this->index = 0;
				return false;
				}
			}
		
		function getnextbis()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$i++;
				$this->index++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idcat);
	echo bab_printTemplate($temp,"faqprint.html");
	}

function listAdmQuestions($idcat)
	{
	global $babBody;
	if( !isset($idcat))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid category !!");
		return false;
		}

	class temp
		{
		var $idcat;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $checked;
		var $editurl;
		var $editname;
		var $idcat;

		function temp($id)
			{
			$this->idcat = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where idcat='$id'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->idcat = $id;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $i == 0)
					$this->checked = "checked";
				else
					$this->checked = "";
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->editurl = $GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$this->idcat."&idq=".$this->arr['id'];
				$this->editname = bab_translate("Edit");
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($idcat);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "admquestionlist"));
	return true;
	}

function addQuestion($idcat)
	{
	global $babBody;
	class temp
		{
		var $question;
		var $response;
		var $add;
		var $idcat;
		var $msie;

		function temp($id)
			{
			$this->question = bab_translate("Question");
			$this->response = bab_translate("Response");
			$this->add = bab_translate("Add");
			$this->idcat = $id;
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp($idcat);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "admquestioncreate"));
	}

function modifyQuestion($item, $idq)
	{
	global $babBody;
	if( !isset($idq))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid question !!");
		return;
		}
	class temp
		{
		var $question;
		var $response;
		var $add;
		var $idcat;

		var $db;
		var $arr = array();
		var $res;
		var $msie;

		function temp($idcat, $idq)
			{
			$this->question = bab_translate("Question");
			$this->response = bab_translate("Response");
			$this->add = bab_translate("Update Question");
			$this->idcat = $idcat;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where id='$idq'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}
	$temp = new temp($item, $idq);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "admquestionmodify"));
	}

function deleteQuestion($item, $idq)
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

		function temp($item, $idq)
			{
			$this->message = bab_translate("Are you sure you want to delete this question");
			$this->title = "";
			$this->warning = bab_translate("WARNING: This operation will delete question and its response"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idq=".$idq."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$item."&idq=".$idq;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($item, $idq);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function saveQuestion($item, $question, $response)
	{
	if( empty($question) || empty($response))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide question and response !!");
		return;
		}
	$db = $GLOBALS['babDB'];
	$query = "insert into ".BAB_FAQQR_TBL." (idcat, question, response) values ('" .$item. "', '" .$question. "', '" . $response. "')";
	$db->db_query($query);
	
	}

function updateQuestion($idq, $question, $response)
	{
	if( empty($question) || empty($response))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide question and response !!");
		return;
		}
	$db = $GLOBALS['babDB'];
	$query = "update ".BAB_FAQQR_TBL." set question='$question', response='$response' where id = '$idq'";
	$db->db_query($query);

	}

function confirmDeleteQuestion($item, $idq)
	{
	$db = $GLOBALS['babDB'];
	$req = "delete from ".BAB_FAQQR_TBL." where id = '$idq'";
	$res = $db->db_query($req);
	}


/* main */
if(!isset($idx))
	{
	$idx = "Categories";
	}

if( isset($addquestion))
	{
	saveQuestion($item, $question, $response);
	}

if( isset($updatequestion))
	{
	updateQuestion($idq, $question, $response);
	}

if( isset($action) && $action == "Yes" && isUserManager($item))
	{
	confirmDeleteQuestion($item, $idq);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
	}

switch($idx)
	{
	case "questions":
		$babBody->title = bab_translate("Questions and Answers");
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item))
			{
			listQuestions($item);
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			$babBody->addItemMenu("Print Friendly", bab_translate("Print Friendly"),$GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=$item");
			$babBody->addItemMenuAttributes("Print Friendly", "target=_blank");
			$babBody->addItemMenu("questions", bab_translate("Questions"),$GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
			if( isUserManager($item))
				$babBody->addItemMenu("Add Question", bab_translate("Add Question"), $GLOBALS['babUrlScript']."?tg=faq&idx=Add Question&item=$item");
			//	$babBody->addItemMenu("Questions", bab_translate("Questions"), $GLOBALS['babUrlScript']."?tg=faq&idx=Questions&item=".$item);
			}
		break;

	case "viewpq":
		viewPopupQuestion($item);
		exit;

	case "viewq":
		$babBody->title = bab_translate("Questions and Answers");
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item))
			{
			viewQuestion($item, $idq);
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			$babBody->addItemMenu("Print Friendly", bab_translate("Print Friendly"),$GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=$item");
			$babBody->addItemMenuAttributes("Print Friendly", "target=_blank");
			$babBody->addItemMenu("questions", bab_translate("Questions"),$GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
			if( isUserManager($item))
				$babBody->addItemMenu("ModifyQ", bab_translate("Edit"),$GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$item."&idq=".$idq);
			//	$babBody->addItemMenu("Questions", bab_translate("Questions"), $GLOBALS['babUrlScript']."?tg=faq&idx=Questions&item=".$item);
			}
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete question");
		if( isUserManager($item))
			{
			deleteQuestion($item, $idq);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=faq&idx=Delete&item=$item&idq=$idq");
			}
		break;
	/*
	case "Questions":
		$babBody->title = bab_translate("List of questions");
		if(isUserManager($item) && listAdmQuestions($item))
			{
			$babBody->addItemMenu("Questions", bab_translate("Questions"), $GLOBALS['babUrlScript']."?tg=faq&idx=Questions&item=$item");
			$babBody->addItemMenu("Add Question", bab_translate("Add Question"), $GLOBALS['babUrlScript']."?tg=faq&idx=Add Question&item=$item");
			}		
		break;
	*/

	case "Add Question":
		$babBody->title = bab_translate("Add question");
		if( isUserManager($item))
			{
			addQuestion($item);
			$babBody->addItemMenu("questions", bab_translate("Questions"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=$item");
			$babBody->addItemMenu("Add Question", bab_translate("Add Question"), $GLOBALS['babUrlScript']."?tg=faq&idx=Add Question&item=$item");
			}
		break;

	case "ModifyQ":
		$babBody->title = bab_translate("Modify question");
		if( isUserManager($item))
			{
			modifyQuestion($item, $idq);
			$babBody->addItemMenu("questions", bab_translate("Questions"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=$item");
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=faq&idx=Delete&item=$item&idq=$idq");
			}
		break;

	case "Print":
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item))
			faqPrint($item);
		exit();
		break;

	default:
	case "Categories":
		$babBody->title = bab_translate("List of all faqs");
		if( listCategories() > 0 )
			{
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			}
		break;
	}

$babBody->setCurrentItemMenu($idx);


?>