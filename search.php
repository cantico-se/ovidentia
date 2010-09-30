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
include_once $babInstallPath."utilit/searchapi.php";
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/forumincl.php";
include_once $babInstallPath."utilit/fileincl.php";
include_once $babInstallPath."utilit/calincl.php";
include_once $babInstallPath."utilit/calapi.php";
include_once $babInstallPath."utilit/dirincl.php";
include_once $babInstallPath."utilit/searchincl.php";

$babLimit = 5;
$navbaritems = 10;
define ("FIELDS_TO_SEARCH", 3);

function highlightWord( $w, $text)
{
	return bab_highlightWord( $w, $text);
}



function bab_getSearchItems() {

	$searchItems = array();
	foreach (bab_Search::getRealms() as $realm)
	{
		$name = $realm->getName();

		if ($realm->displayInSearchEngine() && $realm->isAccessValid()) {
			$searchItems[$name] = $realm;
		}
	}

	bab_sort::sortObjects($searchItems, 'getSortKey');

	return $searchItems;
}





function searchKeyword($item , $option = 'OR', $hideForm = false)
{
	global $babBody;

	class tempb
	{
		public $search;
		public $all;
		public $in;
		public $update;
		public $itemvalue;
		public $itemname;
		public $arr = array();
		public $what;
		public $what2;
		public $dirarr = array();

		public $t_show_search_form;
		public $t_hide_search_form;
		
		public $search_form_folded;


		public function __construct($item, $option, $hideForm)
		{

			global  $babDB,$babBody;

			$this->search_form_folded = $hideForm ? '1' : '0';
			$this->t_show_search_form = bab_translate('Change search...');
			$this->t_hide_search_form = bab_translate('Hide search form');
			$this->t_in = bab_translate('In');
			$this->t_all = bab_translate('All');
			$this->t_search = bab_translate('Search');

			$this->item = $item;
			$this->fields = false !== bab_rp('what', false) ? ($_POST + $_GET) : array();

			if (!isset($this->fields['what'])) $this->fields['what'] = '';
			if (!isset($this->fields['what2'])) $this->fields['what2'] = '';
			if (!isset($this->fields['advenced'])) $this->fields['advenced'] = '';

			$this->htmlfields = array();
			foreach($this->fields as $key=>$val)
			{
				if (is_string($val))
				$this->htmlfields[$key] = bab_toHtml($val);
			}

			$this->what = $this->fields['what'];
			$this->htmlfields['what'] = bab_toHtml($this->fields['what']);
			$this->htmlfields['what2'] = bab_toHtml($this->fields['what2']);

			$this->field = bab_rp('field');
			$this->order = bab_toHtml(bab_rp('order'));
			$this->index = bab_searchEngineInfos();

			$this->searchItems = bab_getSearchItems();

			// get html form for current item

			if (isset($this->searchItems[$this->item]))
			{
				$this->search_form = $this->searchItems[$this->item]->getSearchFormHtml();
			}
			else
			{
				$this->search_form = bab_SearchDefaultForm::getHTML();
			}	

			require_once dirname(__FILE__).'/utilit/delegincl.php';
			
			$this->delegations = bab_getUserVisiblesDelegations();
			$this->displaydelegation = count($this->delegations) > 2;
			
			$this->t_delegation = bab_translate('Delegation');
		}



		/**
		 * Template method
		 */
		public function getnextitem()
		{
			if (list($this->itemvalue, $realm) = each($this->searchItems)) {
				$this->itemname = bab_toHtml($realm->getDescription());
				$this->selected = $this->itemvalue == $this->item ? 'selected' : '';
				return true;
			}
			return false;
		}
		
		
		public function getnextdelegation()
		{
			if (list($uid, $arr) = each($this->delegations)) {
				$this->uid = bab_toHtml($uid);
				$this->name = bab_toHtml($arr['name']);
				$this->color = bab_toHtml($arr['color']);
				$this->selected = $uid === bab_rp('delegation');
				return true;
			}
		}
	}


	$tempb = new tempb($item, $option, $hideForm);
	$babBody->addStyleSheet('search.css');
	$babBody->babEcho(bab_printTemplate($tempb, 'search.html', 'searchform'));
}





/**
 * Navigation
 */
class temp_nav
{

	private $baseurl = null;


	public function __construct($nbrows,$navpos, $navitem, $limit)
	{
		global $navbaritems;
		$this->navbaritems = $navbaritems;
		$this->limit = $limit;
		$this->nbrows = (string) $nbrows;
		$this->navitem = $navitem;


		if ($this->navitem !== bab_rp('navitem')) {
			$navpos = 0;
		}


		$this->navpos = $navpos;
		if (($navpos+$this->limit) > $nbrows ) $this->navposend = $navpos+($nbrows - $navpos);
		else $this->navposend = $navpos+$this->limit;
		$this->results = bab_translate("Results");
		$this->pages = bab_translate("Pages");
		$this->to = bab_translate("To");
		$this->from = bab_translate("From");
		$this->countpages = ceil($nbrows/$this->limit);

		if (1 === $this->countpages) {
			$this->pages = bab_translate("Page");
		}


		$this->baseurl = bab_url::request_gp();
		$this->baseurl = bab_url::mod($this->baseurl, 'navitem', $this->navitem);

		if ( $navpos <= 0 ) $this->previous = false;
		else
		{
			$this->previous = bab_translate("Previous");
			$previous_pos = $this->navpos - $this->limit;
			$this->urlprev = bab_url::mod($this->baseurl, 'navpos', $previous_pos);
		}

		if ( $navpos + $this->limit >= $nbrows ) $this->next = false;
		else
		{
			$this->next = bab_translate("Next");
			$next_pos = $this->navpos + $this->limit;
			$this->urlnext = bab_url::mod($this->baseurl, 'navpos', $next_pos);
		}

		$this->count = ceil($nbrows/$this->limit);
		if ( $this->count > $this->navbaritems )
		$this->count = $this->navbaritems;
			
		if (1 === $this->count) {
			$this->results = bab_translate("Result");
		}

		if ((ceil($this->navpos/$this->limit) - ($this->navbaritems/2)) < 0 ) $this->start = 0;
		else $this->start = ceil($this->navpos/$this->limit) - ($this->navbaritems/2);
		$this->page = $this->start + 1;
	}



	/**
	 * Template method
	 */
	public function getnext()
	{
		static $i = 0;
		if( $i < $this->count && $this->page < $this->countpages)
		{
			$this->page = $this->start + $i + 1;
			$pos = $this->limit*($this->start+$i);
			$this->urlpage = bab_url::mod($this->baseurl, 'navpos', $pos);
			if ( (ceil($this->navpos/$this->limit) == $this->start + $i) )
			$this->selected = true;
			else
			$this->selected = false;
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


/**
 * Display search results
 */
function startSearch( $item, $what, $option, $navpos )
{
	global $babBody;

	class temp
	{
		var $what;
		var $search;
		var $counttot;
		var $altbg = true;

		public $primary_search;
		public $secondary_search;


		function temp( $item, $what, $option ,$navpos )
		{
			global $BAB_SESS_USERID, $babLimit, $babBody, $babDB;

			$this->search = bab_translate("Search");

			$this->total = bab_translate("Number of results in research");
			$this->popup = bab_translate("Open in a popup");


			$navpos = (int) $navpos;
			$this->fields = false !== bab_rp('what', false) ? ($_POST + $_GET) : array();


			$this->primary_search 	= trim($what);
			$this->secondary_search = isset($this->fields['what2']) ? trim($this->fields['what2']) : '';


			$this->option = $option;


			$this->what = urlencode(trim($what." ".$this->secondary_search));
			$this->navpos = $navpos;


			if ((empty($item) || !in_array($item, array('articles', 'directories', 'calendars', 'files')))
				&& empty($this->primary_search)
				&& empty($this->secondary_search)
			)
			{
				$babBody->addError(bab_translate('Your search is empty'));
				return;
			}


				


			// number of results lines

			$limit = $babLimit;
			if (!empty($item)) {
				$limit = 15;
			}



				

			// initialize addons context for old API
			$addons = bab_getInstance('bab_addonsSearch');
			$addons->setSearchParam($this->primary_search, $this->secondary_search, $option, $limit);



			// trouver les criteres en fonction du formulaire affichee
				
				
			$nbresult = 0;
			$html = '';

			$realms = bab_getSearchItems();
			bab_sort::sortObjects($realms, 'getSortKey');

			foreach ($realms as $realm)
			{
				if (empty($item) && $realm->displayInSearchEngine())
				{
					$criteria 			= bab_SearchDefaultForm::getCriteria($realm);
					$fieldlesscriteria 	= bab_SearchDefaultForm::getFieldLessCriteria($realm);
				}

				elseif ($item === $realm->getName())
				{
					$criteria 			= $realm->getSearchFormCriteria();
					$fieldlesscriteria 	= $realm->getSearchFormFieldLessCriteria();
				}
				else
				{
					$criteria 			= NULL;
					$fieldlesscriteria 	= NULL;
				}


				if (!empty($this->primary_search) && method_exists($realm, 'setPrimarySearch'))
				{
					$realm->setPrimarySearch($this->primary_search);
				}

				// $fieldlesscriteria is a criteria for swish-e
				//

				if ($fieldlesscriteria)
				{
					$realm->setFieldLessCriteria($fieldlesscriteria);
				}

				if($criteria)
				{
					$this->setRequestSort($realm);


					$search_res = $realm->search($criteria);

					if ($search_res instanceOf bab_SearchResultCollection) {
						$res_collection = $search_res;
					} else {
						$res_collection = array($search_res);
					}


					foreach($res_collection as $res) {

						$navpos = (int) bab_rp('navpos');
						$count = $res->count();

						if ($count)
						{
							$res->rewind();
							
							if ($res->getRealm()->getName() === bab_rp('navitem'))
							{
								$res->seek($navpos);
							}
								
							$html .= '<div class="bab_SearchRealm '.get_class($realm).'">';
							$html .= '<h5>'.bab_toHtml($res->getRealm()->getDescription()).'</h5>';
							$html .= '<div class="bab_SearchRecords">';
							$html .= $res->getHtml($limit);
							$html .= '</div>';
							$html .= $this->navbar($count, $res->getRealm()->getName(), $limit);
							$html .= '</div>';

							$nbresult += $count;
						}
					}
				}
			}

			if (1 === $nbresult) {
				$babBody->setTitle(bab_translate('Search page with one result'));
			} else {
				$babBody->setTitle(bab_sprintf(bab_translate('Search page with %d results'), $nbresult));
			}
				
			$babBody->babEcho('<div class="bab_SearchResults">'.$html.'</div>');

			// end

			if( !$nbresult && $item == 'tags')
			{
				$babBody->msgerror = bab_translate("Search result is empty");
			}
			if( $item == 'tags')
			{
				$babBody->msgerror .= ' ('.bab_translate("You do not have access rights").')';
			}

		}


		/**
		 * Get navigation bar HTML
		 * @param	int		$nbrows
		 * @param	string	$navitem
		 * @param	int		$limit
		 * @return 	string
		 */
		private function navbar($nbrows, $navitem, $limit) {
			$temp = new temp_nav($nbrows,$this->navpos, $navitem, $limit);
			return bab_printTemplate($temp,"search.html","navbar");
		}






		/**
		 * Search in realm with default fields
		 * @param	string	$realm
		 *
		 */
		private function setRequestSort(bab_SearchRealm $realm)
		{
			// apply requested ordering

			$sortmethods = $realm->getSortMethods();
			$sortrequest = bab_rp('field');

			$order = mb_strtoupper(bab_rp('order', 'ASC'));

			if ('DESC' === $order) {
				$sortrequest .= 'desc';
			}

			if (isset($sortmethods[$sortrequest])) {
				$realm->setSortMethod($sortrequest);
			} else {
				if (!empty($sortrequest)) {
					bab_debug('This sort request ('.$sortrequest.') is not compatible with the search realm ('.get_class($realm).')', DBG_WARNING, 'Search');
				}
			}
		}
	}

	$temp = new temp($item, $what, $option,$navpos);
}


class bab_searchVisuPopup
{
	function bab_searchVisuPopup()
	{
		include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";

		$GLOBALS['babBodyPopup'] = new babBodyPopup();
	}


	function printHTML($file,$tpl)
	{
		$GLOBALS['babBodyPopup']->title = $GLOBALS['babBody']->title;
		$GLOBALS['babBodyPopup']->msgerror = $GLOBALS['babBody']->msgerror;
		$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($this, $file, $tpl));
		printBabBodyPopup();
		die();
	}
}

function viewArticle($article,$w)
{
	global $babBody;

	class temp extends bab_searchVisuPopup
	{

		var $content;
		var $head;
		var $arr = array();
		var $count;
		var $res;
		var $more;
		var $topics;
		var $babMeta;
		var $babCss;
		var $close;
		var $altbg = false;


		function temp($article,$w)
		{
			global $babDB;

			$this->bab_searchVisuPopup();
			$this->close = bab_translate("Close");
			$this->attachmentxt = bab_translate("Associated documents");
			$this->commentstxt = bab_translate("Comments");
			$this->t_name = bab_translate("Name");
			$this->t_description = bab_translate("Description");
			$this->t_index = bab_translate("Result in file");
			$this->tags_txt = bab_translate("Keywords of the thesaurus");
			$req = "select * from ".BAB_ARTICLES_TBL." where id=".$babDB->quote($article);
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$this->title = bab_toHtml($this->arr['title']);
			$this->countf = 0;
			$this->countcom = 0;
			$this->w = $w;
			if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $this->arr['id_topic']) && bab_articleAccessByRestriction($this->arr['restriction']))
			{
				$GLOBALS['babWebStat']->addArticle($this->arr['id']);

				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($this->arr['body']);
				$editor->setFormat($this->arr['body_format']);

				$this->content = highlightWord($w, $editor->getHtml());

				$editor = new bab_contentEditor('bab_article_body');
				$editor->setContent($this->arr['head']);
				$editor->setFormat($this->arr['head_format']);

				$this->head = highlightWord($w, $editor->getHtml());

				$this->resf = $babDB->db_query("
					
					SELECT f.*, i.file_path FROM  
						".BAB_ART_FILES_TBL." f
						LEFT JOIN ".BAB_INDEX_ACCESS_TBL." i ON i.id_object = f.id
					WHERE id_article=".$babDB->quote($article)." 
					 GROUP BY f.id
				");

				$this->countf = $babDB->db_num_rows($this->resf);

				$this->found_in_index = array();

				if( $this->countf > 0 )
				{
					$this->battachments = true;
					if (bab_searchEngineInfos()) {
						$found_files = bab_searchIndexedFiles($this->w, false, false, 'bab_art_files');
						bab_debug($found_files);
							
						foreach($found_files as $arr) {
							$this->found_in_index[bab_removeUploadPath($arr['file'])] = 1;
						}
					}
				}
				else
				{
					$this->battachments = false;
				}

				$this->rescom = $babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id_article=".$babDB->quote($article)." and confirmed='Y' order by date desc");
				$this->countcom = $babDB->db_num_rows($this->rescom);

				require_once dirname(__FILE__) . '/utilit/tagApi.php';

				$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
				$oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'articles', 'article', $article));
				$oIterator->orderAsc('tag_name');
				$this->restags = $oIterator;
				$this->counttags = $oIterator->count();
			}
			else
			{
				$this->content = "";
				$this->head = bab_translate("Access denied");
			}
		}

		function getnextdoc()
		{
			global $babDB, $arrtop;
			static $i = 0;
			if( $i < $this->countf)
			{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->docurl = $GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->arr['id_topic']."&article=".$this->arr['id']."&idf=".$arr['id'];
				$this->docname = highlightWord($this->w, bab_toHtml($arr['name']));
				$this->docdescription = highlightWord($this->w, bab_toHtml($arr['description']));
				$this->in_index = isset($this->found_in_index['articles/'.$this->arr['id'].','.$arr['name']]);
				$this->altbg = !$this->altbg;
				$i++;
				return true;
			}
			else
			{
				$i = 0;
				return false;
			}
		}

		function getnextcom()
		{
			global $babDB;
			static $i = 0;
			if( $i < $this->countcom)
			{
				$arr = $babDB->db_fetch_array($this->rescom);
				$this->altbg = !$this->altbg;
				$this->commentdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
				$this->authorname = highlightWord($this->w,bab_toHtml($arr['name']));
				$this->commenttitle = highlightWord($this->w,bab_toHtml($arr['subject']));

				$editor = new bab_contentEditor('bab_article_comment');
				$editor->setContent($arr['message']);
				$editor->setFormat($arr['message_format']);
				$this->commentbody = highlightWord($this->w,$editor->getHtml());

				$i++;
				return true;
			}
			else
			{
				$babDB->db_data_seek($this->rescom,0);
				$i=0;
				return false;
			}
		}
		function getnexttag()
		{
			if($this->restags instanceof bab_TagIterator)
			{
				if($this->restags->valid())
				{
					$oTag = $this->restags->current();
					$this->tagname = bab_toHtml($oTag->getName());
					$this->restags->next();
					return true;
				}
			}
			return false;
		}
	}

	$temp = new temp($article,$w);
	$temp->printHTML("search.html", "viewart");
}

function viewComment($topics, $article, $com, $w)
{
	global $babBody;

	class ctp extends bab_searchVisuPopup
	{
		var $subject;
		var $add;
		var $topics;
		var $article;
		var $arr = array();
		var $babCss;

		function ctp($topics, $article, $com, $w)
		{
			global $babDB;

			$this->bab_searchVisuPopup();
			$this->title = bab_toHtml(bab_getArticleTitle($article));
			$this->subject = bab_translate("Subject");
			$this->by = bab_translate("By");
			$this->date = bab_translate("Date");
			$this->topics = $topics;
			$this->article = $article;
			$req = "select * from ".BAB_COMMENTS_TBL." where id=".$babDB->quote($com);
			$res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($res);
			$this->arr['date'] = bab_toHtml(bab_strftime(bab_mktime($this->arr['date'])));
			$this->arr['subject'] = highlightWord( $w, bab_toHtml($this->arr['subject']));

			include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';
			$editor = new bab_contentEditor('bab_article_comment');
			$editor->setContent($this->arr['message']);
			$editor->setFormat($this->arr['message_format']);
			$this->arr['message'] = highlightWord( $w, $editor->getHtml());
		}
	}

	$ctp = new ctp($topics, $article, $com, $w);
	$ctp->printHTML("search.html", "viewcom");
}

function viewPost($thread, $post, $w)
{
	global $babBody;

	class temp extends bab_searchVisuPopup
	{

		var $postmessage;
		var $postsubject;
		var $postdate;
		var $postauthor;
		var $title;
		var $babCss;

		function temp($thread, $post, $w)
		{
			global $babDB;

			$post = (int) $post;
			$this->bab_searchVisuPopup();
			$req = "select forum from ".BAB_THREADS_TBL." where id=".$babDB->quote($thread);
			$arr = $babDB->db_fetch_array($babDB->db_query($req));

			$this->t_files = bab_translate("Dependent files");
			$this->t_found_in_index = bab_translate("Result in file");
			$this->files = bab_getPostFiles($arr['forum'], $post);

			$GLOBALS['babBody']->title = bab_toHtml(bab_getForumName($arr['forum']));
			$req = "select * from ".BAB_POSTS_TBL." where id=".$babDB->quote($post);
			$arr = $babDB->db_fetch_array($babDB->db_query($req));
			$this->postdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
			$this->postauthor = bab_toHtml($arr['author']);
			$this->author = bab_getForumContributor($arr['forum'], $arr['id_author'], $this->author);
			$this->postsubject = highlightWord( $w, bab_toHtml($arr['subject']));

			include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';
			$editor = new bab_contentEditor('bab_forum_post');
			$editor->setContent($arr['message']);
			$editor->setFormat($arr['message_format']);
			$this->postmessage = highlightWord( $w, $editor->getHtml());

			if ($this->files && bab_searchEngineInfos()) {
				$found_files = bab_searchIndexedFiles($w, false, false, 'bab_forumsfiles');

				foreach($found_files as $arr) {
					$this->found_in_index[bab_removeUploadPath($arr['file'])] = 1;
				}
			}
		}

		function getnextfile()
		{
			if ($file = current($this->files))
			{

				$this->url = bab_toHtml($file['url']);
				$this->name = bab_toHtml($file['name']);
				$this->size = bab_toHtml($file['size']);


				next($this->files);
				$this->in_index = isset($this->found_in_index['forums/'.basename($file['path'])]);
				return true;
			}
			else
			return false;
		}
	}

	$temp = new temp($thread, $post, $w);
	$temp->printHTML("search.html", "viewfor");
}

function viewQuestion($idcat, $id, $w)
{
	global $babBody;
	class temp extends bab_searchVisuPopup
	{
		var $arr = array();
		var $res;
		var $babCss;

		function temp($idcat, $id, $w)
		{
			global $babDB;
			$this->bab_searchVisuPopup();
			$req = "select * from ".BAB_FAQQR_TBL." where id=".$babDB->quote($id);
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$this->arr['question'] = highlightWord( $w, bab_toHtml($this->arr['question']));

			include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';
			$editor = new bab_contentEditor('bab_faq_response');
			$editor->setContent($this->arr['response']);
			$editor->setFormat($this->arr['response_format']);
			$this->arr['response'] = highlightWord( $w, $editor->getHtml());

			$req = "select category from ".BAB_FAQCAT_TBL." where id=".$babDB->quote($idcat);
			$a = $babDB->db_fetch_array($babDB->db_query($req));
			$this->title = highlightWord( $w,  bab_toHtml($a['category']));
		}
			
	}

	$temp = new temp($idcat, $id, $w);
	$temp->printHTML("search.html", "viewfaq");
}

function viewFile($id, $w)
{
	global $babBody;
	class temp extends bab_searchVisuPopup
	{
		var $arr = array();
		var $res;
		var $babCss;
		var $description;
		var $keywords;
		var $modified;
		var $postedby;
		var $modifiedtxt;
		var $postedbytxt;
		var $createdtxt;
		var $created;
		var $modifiedbytxt;
		var $modifiedby;
		var $sizetxt;
		var $size;
		var $download;
		var $geturl;
		var $altbg = true;

		function temp($id, $w)
		{
			global $babDB;
			$this->bab_searchVisuPopup();
			$this->description = bab_translate("Description");
			$this->keywords = bab_translate("Keywords");
			$this->modifiedtxt = bab_translate("Modified");
			$this->createdtxt = bab_translate("Created");
			$this->postedbytxt = bab_translate("Posted by");
			$this->modifiedbytxt = bab_translate("Modified by");
			$this->download = bab_translate("Download");
			$this->sizetxt = bab_translate("Size");
			$this->pathtxt = bab_translate("Path");
			$this->t_name = bab_translate("Older versions");
			$this->t_versiondate = bab_translate("Date");
			$this->t_index = bab_translate("Result in file");

			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$req = "select * from ".BAB_FILES_TBL." where id=".$babDB->quote($id)." and state='' and confirmed='Y'";
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$access = bab_isAccessFileValid($this->arr['bgroup'], $this->arr['id_owner']);
			if( $access )
			{
				$GLOBALS['babBody']->title = bab_toHtml($this->arr['name']);
				$this->arr['description'] = highlightWord( $w, bab_toHtml($this->arr['description']));


				require_once dirname(__FILE__) . '/utilit/tagApi.php';

				$this->arr['keywords'] = '';
				$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');

				$oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'files', 'file', $id));
				$oIterator->orderAsc('tag_name');
				foreach($oIterator as $oTag)
				{
					$this->arr['keywords'] .= $oTag->getName() . ', ';
				}
				$this->arr['keywords'] = highlightWord( $w, bab_toHtml($this->arr['keywords']));
				$this->modified = bab_toHtml(bab_shortDate(bab_mktime($this->arr['modified']), true));
				$this->created = bab_toHtml(bab_shortDate(bab_mktime($this->arr['created']), true));
				$this->postedby = bab_toHtml(bab_getUserName($this->arr['author']));
				$this->modifiedby = bab_toHtml(bab_getUserName($this->arr['modifiedby']));

				$sPath = removeEndSlah($this->arr['path']);

				$iid = $this->arr['id_owner'];




				$sUploadPath = BAB_FileManagerEnv::getCollectivePath($this->arr['iIdDgOwner']);
				if($this->arr['bgroup'] == "Y")
				{
					$fstat = stat($sUploadPath . $this->arr['path'] . $this->arr['name']);
					$oFmFolder = BAB_FmFolderSet::getRootCollectiveFolder($this->arr['path']);
					if(!is_null($oFmFolder))
					{
						$iid = $oFmFolder->getId();
					}
				}
				else
				{
					$fstat = stat($sUploadPath . $this->arr['path'] . $this->arr['name']);
				}
				$this->geturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&sAction=getFile&id=".$iid."&gr=".$this->arr['bgroup']."&path=".urlencode($sPath)."&file=".urlencode($this->arr['name']).'&idf='.$this->arr['id']);
				$this->size = bab_formatSizeFile($fstat[7])." ".bab_translate("Kb");
				if( $this->arr['bgroup'] == "Y") {
					$this->rootpath = '';
					$this->resff = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder=".$babDB->quote($this->arr['id_owner']));
					$this->countff = $babDB->db_num_rows($this->resff);
				}
				else
				{
					$this->rootpath = bab_translate("Private folder");
					$this->countff = 0;
				}
				$this->path = bab_toHtml($this->rootpath."/".$this->arr['path']);

				$this->resversion = $babDB->db_query("
					SELECT 
						UNIX_TIMESTAMP(date) versiondate, 
						CONCAT(f.name,' ',v.ver_major,'.',v.ver_minor) name,
						a.file_path 
					FROM 
						".BAB_FILES_TBL." f,
						".BAB_FM_FILESVER_TBL." v,
						".BAB_INDEX_ACCESS_TBL." a 
					WHERE 
						f.id = v.id_file 
						AND v.id_file=".$babDB->quote($this->arr['id'])." 
						AND a.id_object = v.id 
						AND a.id_object_access = f.id_owner

					ORDER BY v.ver_major DESC,v.ver_minor DESC
					");

				$this->countversions = $babDB->db_num_rows($this->resversion);

				if (bab_searchEngineInfos()) {
					$found_files = bab_searchIndexedFiles(trim($w), false, false, 'bab_files');


					foreach($found_files as $arr) {
						$this->found_in_index[bab_removeFmUploadPath($arr['file'])] = 1;
					}
				}
			}
			else
			{
				$GLOBALS['babBody']->msgerror = bab_translate("Access denied");
				$this->arr['description'] = "";
				$this->arr['keywords'] = "";
				$this->created = "";
				$this->modifiedby = "";
				$this->modified = "";
				$this->postedby = "";
				$this->geturl = "";
				$this->countff = 0;
				$this->path ='';
				$this->size = '';
			}
		}

		function getnextfield()
		{
			global $babDB;
			static $i = 0;
			if( $i < $this->countff)
			{
				$arr = $babDB->db_fetch_array($this->resff);
				$this->field = bab_toHtml(bab_translate($arr['name']));
				$this->fieldval = '';
				$res = $babDB->db_query("select fvalue from ".BAB_FM_FIELDSVAL_TBL." where  id_field=".$babDB->quote($arr['id'])." and id_file=".$babDB->quote($this->arr['id']));
				if( $res && $babDB->db_num_rows($res) > 0)
				{
					list($this->fieldval) = $babDB->db_fetch_array($res);
					$this->fieldval = bab_toHtml($this->fieldval);
				}
				$i++;
				return true;
			}
			else
			{
				$i = 0;
				return false;
			}
		}


		function getnextversion()
		{
			global $babDB;
			if ($arr = $babDB->db_fetch_assoc($this->resversion))
			{
				$this->altbg = !$this->altbg;
				$this->name = bab_toHtml($arr['name']);
				$this->versiondate = bab_toHtml(bab_longDate($arr['versiondate']));
				$this->in_index = isset($this->found_in_index[$arr['file_path']]);
				return true;
			}
			return false;
		}
	}

	$temp = new temp($id, $w);
	$temp->printHTML("search.html", "viewfil");
	return true;
}


function viewContact($id, $what)
{
	class temp extends bab_searchVisuPopup
	{
		var $firstname;
		var $lastname;
		var $email;
		var $compagny;
		var $hometel;
		var $mobiletel;
		var $businesstel;
		var $businessfax;
		var $jobtitle;
		var $businessaddress;
		var $homeaddress;
		var $firstnameval;
		var $lastnameval;
		var $emailval;
		var $compagnyval;
		var $hometelval;
		var $mobiletelval;
		var $businesstelval;
		var $businessfaxval;
		var $jobtitleval;
		var $businessaddressval;
		var $homeaddressval;
		var $addcontactval;
		var $cancel;
		var $babCss;
		var $msgerror;

		function temp($id, $what)
		{
			global $babDB, $BAB_SESS_USERID;

			$this->bab_searchVisuPopup();

			$this->firstname = bab_translate("First Name");
			$this->lastname = bab_translate("Last Name");
			$this->email = bab_translate("Email");
			$this->compagny = bab_translate("Compagny");
			$this->hometel = bab_translate("Home Tel");
			$this->mobiletel = bab_translate("Mobile Tel");
			$this->businesstel = bab_translate("Business Tel");
			$this->businessfax = bab_translate("Business Fax");
			$this->jobtitle = bab_translate("Job Title");
			$this->businessaddress = bab_translate("Business Address");
			$this->homeaddress = bab_translate("Home Address");
			$this->cancel = bab_translate("Cancel");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->msgerror = "";

			$req = "select * from ".BAB_CONTACTS_TBL." where id=".$babDB->quote($id);
			$arr = $babDB->db_fetch_array($babDB->db_query($req));
			if( !empty($BAB_SESS_USERID) && $arr['owner'] == $BAB_SESS_USERID )
			{
				$this->firstnameval = $arr['firstname'];
				$this->lastnameval = $arr['lastname'];
				$this->emailval = $arr['email'];
				$this->compagnyval = $arr['compagny'];
				$this->hometelval = $arr['hometel'];
				$this->mobiletelval = $arr['mobiletel'];
				$this->businesstelval = $arr['businesstel'];
				$this->businessfaxval = $arr['businessfax'];
				$this->jobtitleval = $arr['jobtitle'];
				$this->businessaddressval = $arr['businessaddress'];
				$this->homeaddressval = $arr['homeaddress'];
			}
			else
			{
				$this->msgerror = bab_translate("You don't have access to this contact");
				$this->firstnameval = "";
				$this->lastnameval = "";
				$this->emailval = "";
				$this->compagnyval = "";
				$this->hometelval = "";
				$this->mobiletelval = "";
				$this->businesstelval = "";
				$this->businessfaxval = "";
				$this->jobtitleval = "";
				$this->businessaddressval = "";
				$this->homeaddressval = "";
			}
		}
	}

	$temp = new temp($id, $what);
	$temp->printHTML("search.html", "viewcon");
}

function viewDirectoryUser($id, $what)
{
	global $babBody, $babDB, $babInstallPath;
	list($idd, $idu) = $babDB->db_fetch_array($babDB->db_query("select id_directory, id_user from ".BAB_DBDIR_ENTRIES_TBL." where id=".$babDB->quote($id)));
	$access = false;
	if( $idd == 0 )
	{
		$res = $babDB->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL." where id_group != '0'");
		while( $row = $babDB->db_fetch_array($res))
		{
			$idd = $row['id'];
			list($bdir) = $babDB->db_fetch_array($babDB->db_query("select directory from ".BAB_GROUPS_TBL." where id=".$babDB->quote($row['id_group'])));
			if( $bdir == 'Y' && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
			{
				if( $row['id_group'] == 1 )
				{
					$access = true;
					break;
				}

				$res2 = $babDB->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object=".$babDB->quote($idu)." and id_group=".$babDB->quote($row['id_group']));
				if( $res2 && $babDB->db_num_rows($res2) > 0 )
				{
					$access = true;
					break;
				}
			}
		}
	}
	elseif( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $idd))
	{
		$access = true;
	}

	if( $access )
	{
		summaryDbContactWithOvml(array('directoryid'=>$idd, 'userid'=>$id));
	}
	else
	{
		echo bab_translate("Access denied");
	}
}


/**
 * Redirect to addon if necessary
 * @param	string	$item
 */
function bab_gotoAddonIfRedirect($item)
{
	require_once dirname(__FILE__).'/utilit/searchaddonincl.php';
	$id_addon = bab_addonsSearch::getAddonFromItem($item);

	if (false === $id_addon) {
		return false;
	}

	$addons = bab_getInstance('bab_addonsSearch');

	if (isset($addons->tabLinkAddons[$id_addon])) {
		header('location:'.$GLOBALS['babUrlScript']."?tg=addon/".$id_addon."/".$addons->querystring[$id_addon]);
		exit;
	}
}




function bab_searchDirectoryEmails($what, $emailSeparator = '; ')
{
	global $babBody;

	$fields = false !== bab_rp('what', false) ? ($_POST + $_GET) : array();
	$primary_search = trim($what);
	$secondary_search = isset($fields['what2']) ? trim($fields['what2']) : '';


	$realms = bab_getSearchItems();
	bab_sort::sortObjects($realms, 'getSortKey');

	$realm = null;
	foreach ($realms as $arealm) {
		if ($arealm->getName() === 'directories') {
			$realm = $arealm;
			break;
		}
	}

	//	$realm = bab_Search::getRealm('directories');

	$criteria = $realm->getSearchFormCriteria();
	$fieldlesscriteria = $realm->getSearchFormFieldLessCriteria();

	if (!empty($primary_search) && method_exists($realm, 'setPrimarySearch')) {
		$realm->setPrimarySearch($primary_search);
	}

	if ($fieldlesscriteria) {
		$realm->setFieldLessCriteria($fieldlesscriteria);
	}

	if ($criteria) {

		$search_res = $realm->search($criteria);

		if ($search_res instanceOf bab_SearchResultCollection) {
			$res_collection = $search_res;
		} else {
			$res_collection = array($search_res);
		}


		$emails = array();
		foreach ($res_collection as $res) {

			foreach ($res as $o) {
				if (!empty($o->email)) {
					$emails[$o->email] = $o->email;
				}
			}
		}
		echo(implode($emailSeparator, $emails));
	}
}




$what = bab_rp('what');
$idx = bab_rp('idx');
$item = bab_rp('item');
$option = bab_rp('option');
$navpos = (int) bab_rp('navpos');


bab_gotoAddonIfRedirect($item);




switch($idx)
{
	case "browauthor":
		include_once $babInstallPath."utilit/lusersincl.php";
		if( !isset($pos)) { $pos = ''; }
		browseArticlesAuthors($pos, $cb);
		exit;
		break;
	case 'articles':
		viewArticle($id, $w);
		exit;
		break;

	case 'ac':
		viewComment($idt, $ida, $idc, $w);
		exit;
		break;

	case 'forums':
		viewPost($idt, $idp, $w);
		exit;
		break;

	case 'faqs':
		viewQuestion($idc, $idq, $w);
		exit;
		break;

	case 'files':
		viewFile($id, $w);
		exit;
		break;

	case 'contacts':
		viewContact($id, $w);
		exit;
		break;

	case 'directories':
		viewDirectoryUser($id, $w);
		exit;
		break;

	case 'find':
		$babBody->title = bab_translate('Search');
		searchKeyword($item, $option, true);

		$GLOBALS['babWebStat']->addSearchWord($what);
		startSearch($item, $what, $option, $navpos);
		break;

	case 'emails':
		$babBody->title = bab_translate('Emails');
		bab_searchDirectoryEmails($what,bab_rp('sep', ';') );
		die;

	default:
		$babBody->title = bab_translate('Search');
		searchKeyword($item, $option);
		break;
}
$babBody->setCurrentItemMenu($idx);

