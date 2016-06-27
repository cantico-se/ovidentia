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
 * @copyright Copyright (c) 2009 by CANTICO ({@link http://www.cantico.fr})
 * 
 */



class bab_AddCommentTemplate
{
    public	$subject;
    public	$subjectval;
    public	$name;
    public	$email;
    public	$message;
    public	$add;
    public	$article;
    public	$username;
    public	$anonyme;
    public	$title;
    public	$titleval;
    public	$com;

    public	$rate_articles = true;
    public	$useCaptcha;

    public function __construct($topics, $article, $subject, $message, $com, $popup = false)
    {
        global $BAB_SESS_USER, $babDB;
        $this->subject = bab_translate('comments-Title');
        $this->name = bab_translate('Your name');
        $this->email = bab_translate('Email');
        $this->message = bab_translate('comments-Comment');
        $this->add = bab_translate('Add comment');
        $this->title = bab_translate('Article');
        $this->article = bab_toHtml($article);
        $this->topics = bab_toHtml($topics);
        $this->subjectval = bab_toHtml($subject);
        
        $this->popup = $popup ? '1' : '0';

        $this->t_rate_this_article = bab_translate('Rate this article:');

        $this->t_bad = bab_translate('Bad');
        $this->t_rather_bad = bab_translate('Rather bad');
        $this->t_average = bab_translate('Average');
        $this->t_rather_good = bab_translate('Rather good');
        $this->t_good = bab_translate('Good');

        $this->com = bab_toHtml($com);

        $req = 'SELECT allow_article_rating FROM ' . BAB_TOPICS_TBL.' WHERE id=' . $babDB->quote($topics);
        $res = $babDB->db_query($req);
        $topic = $babDB->db_fetch_assoc($res);
        $this->rate_articles = ($topic['allow_article_rating'] === 'Y');

        $req = 'SELECT title FROM ' . BAB_ARTICLES_TBL.' WHERE id=' . $babDB->quote($article);
        $res = $babDB->db_query($req);
        $arr = $babDB->db_fetch_assoc($res);
        $this->titleval = bab_toHtml($arr['title']);
        $this->messageval = bab_toHtml($message);
        $this->nameval = '';

        $arr = $babDB->db_fetch_array($babDB->db_query('SELECT idsacom FROM '.BAB_TOPICS_TBL.' WHERE id='.$babDB->quote($topics)));
        if ($arr['idsacom'] != 0) {
            $this->notcom = bab_translate('Note: for this topic, comments are moderated');
        } else {
            $this->notcom = '';
        }

        $this->useCaptcha = false;

        // We use the captcha if it is available as a functionality.
        if (!$GLOBALS['BAB_SESS_LOGGED']) {
            //				$this->rate_articles = false;
            $captcha = bab_functionality::get('Captcha');
            if (false !== $captcha) {
                $this->useCaptcha = true;
                $this->captchaCaption1 = bab_translate('Word Verification');
                $this->captchaSecurityData = $captcha->getGetSecurityHtmlData();
                $this->captchaCaption2 = bab_translate('Enter the letters in the image above');
            }
        }

    }
}







class bab_EditCommentTemplate
{
    public	$subject;
    public	$subjectval;
    public	$name;
    public	$email;
    public	$message;
    public	$add;
    public	$article;
    public	$username;
    public	$anonyme;
    public	$title;
    public	$titleval;
    public	$com;

    public	$rate_articles = true;
    public	$useCaptcha;

    public function __construct($topics, $article, $commentId, $popup = false)
    {
        global $BAB_SESS_USER, $babDB;
        $this->comment_id = bab_toHtml($commentId);

        $req = 'SELECT * FROM ' . BAB_COMMENTS_TBL.' WHERE id=' . $babDB->quote($commentId);
        $res = $babDB->db_query($req);
        $comment = $babDB->db_fetch_assoc($res);

        $this->t_subject = bab_translate('comments-Title');
        $this->t_message = bab_translate('comments-Comment');
        $this->t_save = bab_translate('Save comment');
        $this->t_title = bab_translate('Article');
        $this->article = bab_toHtml($article);
        $this->topics = bab_toHtml($topics);
        $this->subject = bab_toHtml($comment['subject']);
        $this->messageval = bab_toHtml($comment['message']);
        
        $this->popup = $popup ? '1' : '0';
    }
}