<?php

function isUserManager($item)
	{
	global $BAB_SESS_USERID;
	$db = new db_mysql();
	$req = "select * from faqcat where id='$item'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $BAB_SESS_USERID == $arr[id_manager])
			return true;
		}
	return false;
	}

function listCategories()
	{
	global $body;
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
			$this->db = new db_mysql();
			$this->count = count($arrid);
			$this->arrid = $arrid;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from faqcat where id='".$this->arrid[$i]."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->arr = $this->db->db_fetch_array($res);
					$this->arr[description] = nl2br($this->arr[description]);
					$this->urlcategory = $GLOBALS[babUrl]."index.php?tg=faq&idx=questions&item=".$this->arr[id];
					$this->namecategory = $this->arr[category];
					}
				$i++;
				return true;
				}		
			else
				return false;
			}
		}
	$db = new db_mysql();
	$req = "select * from faqcat";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if(isAccessValid("faqcat_groups", $row[id]))
			{
			array_push($arrid, $row[id]);
			}
		}

	$temp = new temp($arrid);
	$body->babecho(	babPrintTemplate($temp,"faq.html", "categorylist"));

	return count($arrid);
	}


function listQuestions($idcat)
	{
	global $body;
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
			$this->db = new db_mysql();
			$req = "select * from faqqr where idcat='$id'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->question = $this->arr[question];
				$this->questionurl = $GLOBALS[babUrl]."index.php?tg=faq&idx=viewq&item=".$this->idcat."&idq=".$this->arr[id];
				//$this->arr[response] = nl2br($this->arr[response]);
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($idcat);
	$body->babecho(	babPrintTemplate($temp,"faq.html", "questionlist"));
	return true;
	}

function viewQuestion($idcat, $id)
	{
	global $body;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $return;
		var $returnurl;

		function temp($idcat, $id)
			{
			$this->db = new db_mysql();
			$req = "select * from faqqr where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->returnurl = $GLOBALS[babUrl]."index.php?tg=faq&idx=questions&item=".$idcat;
			$this->return = babTranslate("Return to Questions");
			}

		}

	$temp = new temp($idcat, $id);
	$body->babecho(	babPrintTemplate($temp,"faq.html", "viewquestion"));
	return true;
	}

function faqPrint($idcat)
	{
	global $body;
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
			$this->indexquestions = "Index questions";
			$this->sitename = $babSiteName;
			$this->urlsite = $babUrl;
			$this->db = new db_mysql();
			$req = "select * from faqcat where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr1 = $this->db->db_fetch_array($this->res);
			$req = "select * from faqqr where idcat='$id'";
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
	echo babPrintTemplate($temp,"faqprint.html");
	}

function listAdmQuestions($idcat)
	{
	global $body;
	if( !isset($idcat))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid category !!");
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
			$this->db = new db_mysql();
			$req = "select * from faqqr where idcat='$id'";
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
				$this->editurl = $GLOBALS[babUrl]."index.php?tg=faq&idx=ModifyQ&item=".$this->idcat."&idq=".$this->arr[id];
				$this->editname = babTranslate("Edit");
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($idcat);
	$body->babecho(	babPrintTemplate($temp,"faq.html", "admquestionlist"));
	return true;
	}

function addQuestion($idcat)
	{
	global $body;
	class temp
		{
		var $question;
		var $response;
		var $add;
		var $idcat;

		function temp($id)
			{
			$this->question = babTranslate("Question");
			$this->response = babTranslate("Response");
			$this->add = babTranslate("Add");
			$this->idcat = $id;
			}
		}

	$temp = new temp($idcat);
	$body->babecho(	babPrintTemplate($temp,"faq.html", "admquestioncreate"));
	}

function modifyQuestion($item, $idq)
	{
	global $body;
	if( !isset($idq))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid question !!");
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

		function temp($idcat, $idq)
			{
			$this->question = babTranslate("Question");
			$this->response = babTranslate("Response");
			$this->add = babTranslate("Update Question");
			$this->idcat = $idcat;
			$this->db = new db_mysql();
			$req = "select * from faqqr where id='$idq'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			}
		}

	$temp = new temp($item, $idq);
	$body->babecho(	babPrintTemplate($temp,"faq.html", "admquestionmodify"));
	}

function deleteQuestion($item, $idq)
	{
	global $body;
	
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
			$this->message = babTranslate("Are you sure you want question");
			$this->title = "";
			$this->warning = babTranslate("WARNING: This operation will delete question and its response"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=faq&idx=questions&item=".$item."&idq=".$idq."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=faq&idx=ModifyQ&item=".$item."&idq=".$idq;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($item, $idq);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}


function saveQuestion($item, $question, $response)
	{
	if( empty($question) || empty($response))
		{
		$body->msgerror = babTranslate("ERROR: You must provide question and response !!");
		return;
		}
	$db = new db_mysql();
	$query = "insert into faqqr (idcat, question, response) values ('" .$item. "', '" .$question. "', '" . $response. "')";
	$db->db_query($query);
	
	}

function updateQuestion($idq, $question, $response)
	{
	if( empty($question) || empty($response))
		{
		$body->msgerror = babTranslate("ERROR: You must provide question and response !!");
		return;
		}
	$db = new db_mysql();
	$query = "update faqqr set question='$question', response='$response' where id = '$idq'";
	$db->db_query($query);

	}

function confirmDeleteQuestion($item, $idq)
	{
	$db = new db_mysql();
	$req = "delete from faqqr where id = '$idq'";
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
	Header("Location: index.php?tg=faq&idx=questions&item=".$item);
	}

switch($idx)
	{
	case "questions":
		$body->title = "questions / Responses";
		if( isAccessValid("faqcat_groups", $item))
			{
			listQuestions($item);
			$body->addItemMenu("Categories", babTranslate("Categories"),$GLOBALS[babUrl]."index.php?tg=faq&idx=Categories");
			$body->addItemMenu("Print Friendly", babTranslate("Print Friendly"),$GLOBALS[babUrl]."index.php?tg=faq&idx=Print&item=$item");
			$body->addItemMenuAttributes("Print Friendly", "target=_blank");
			$body->addItemMenu("questions", babTranslate("Questions"),$GLOBALS[babUrl]."index.php?tg=faq&idx=questions&item=".$item);
			if( isUserManager($item))
				$body->addItemMenu("Add Question", babTranslate("Add Question"), $GLOBALS[babUrl]."index.php?tg=faq&idx=Add Question&item=$item");
			//	$body->addItemMenu("Questions", babTranslate("Questions"), $GLOBALS[babUrl]."index.php?tg=faq&idx=Questions&item=".$item);
			}
		break;

	case "viewq":
		$body->title = "questions / Responses";
		if( isAccessValid("faqcat_groups", $item))
			{
			viewQuestion($item, $idq);
			$body->addItemMenu("Categories", babTranslate("Categories"),$GLOBALS[babUrl]."index.php?tg=faq&idx=Categories");
			$body->addItemMenu("Print Friendly", babTranslate("Print Friendly"),$GLOBALS[babUrl]."index.php?tg=faq&idx=Print&item=$item");
			$body->addItemMenuAttributes("Print Friendly", "target=_blank");
			$body->addItemMenu("questions", babTranslate("Questions"),$GLOBALS[babUrl]."index.php?tg=faq&idx=questions&item=".$item);
			if( isUserManager($item))
				$body->addItemMenu("ModifyQ", babTranslate("Edit"),$GLOBALS[babUrl]."index.php?tg=faq&idx=ModifyQ&item=".$item."&idq=".$idq);
			//	$body->addItemMenu("Questions", babTranslate("Questions"), $GLOBALS[babUrl]."index.php?tg=faq&idx=Questions&item=".$item);
			}
		break;

	case "Delete":
		$body->title = babTranslate("Delete question");
		if( isUserManager($item))
			{
			deleteQuestion($item, $idq);
			$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=faq&idx=Delete&item=$item&idq=$idq");
			}
		break;
	/*
	case "Questions":
		$body->title = babTranslate("List of questions");
		if(isUserManager($item) && listAdmQuestions($item))
			{
			$body->addItemMenu("Questions", babTranslate("Questions"), $GLOBALS[babUrl]."index.php?tg=faq&idx=Questions&item=$item");
			$body->addItemMenu("Add Question", babTranslate("Add Question"), $GLOBALS[babUrl]."index.php?tg=faq&idx=Add Question&item=$item");
			}		
		break;
	*/

	case "Add Question":
		$body->title = babTranslate("Add question");
		if( isUserManager($item))
			{
			addQuestion($item);
			$body->addItemMenu("questions", babTranslate("Questions"), $GLOBALS[babUrl]."index.php?tg=faq&idx=questions&item=$item");
			$body->addItemMenu("Add Question", babTranslate("Add Question"), $GLOBALS[babUrl]."index.php?tg=faq&idx=Add Question&item=$item");
			}
		break;

	case "ModifyQ":
		$body->title = babTranslate("Modify question");
		if( isUserManager($item))
			{
			modifyQuestion($item, $idq);
			$body->addItemMenu("questions", babTranslate("Questions"), $GLOBALS[babUrl]."index.php?tg=faq&idx=questions&item=$item");
			$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=faq&idx=Delete&item=$item&idq=$idq");
			}
		break;

	case "Print":
		if( isAccessValid("faqcat_groups", $item))
			faqPrint($item);
		exit();
		break;

	default:
	case "Categories":
		$body->title = babTranslate("List of all faqs");
		if( listCategories() > 0 )
			{
			$body->addItemMenu("Categories", babTranslate("Categories"),$GLOBALS[babUrl]."index.php?tg=faq&idx=Categories");
			}
		break;
	}

$body->setCurrentItemMenu($idx);


?>