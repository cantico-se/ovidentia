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
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 */



/**
 * Provides an icon theme.
 */
class Func_Icons extends bab_Functionality
{
	/**
	 * @return string
	 * @static
	 */
	public function getDescription()
	{
		return bab_translate('Provides an icon theme.');
	}


	/**
	 * Includes all necessary CSS files to the current page.
	 *
	 * @return bool		false in case of error
	 */
	public function includeCss()
	{
		return true;
	}

	/**
	 * Returns the css file relative url corresponding to the icon theme.
	 *
	 * @return string
	 */
	public function getCss()
	{
		return '';
	}


	// Stock icon names.

	//-----------------------------------------------------------------------
	// Actions
	//-----------------------------------------------------------------------
	const ACTIONS_HELP							= 'actions-help';
	/**
	 * The icon for creating a new folder.
	 */
	const ACTIONS_FOLDER_NEW					= 'actions-folder-new';
	/**
	 * The icon for creating a new document.
	 */
	const ACTIONS_DOCUMENT_NEW					= 'actions-document-new';
	/**
	 * The icon for printing a document.
	 */
	const ACTIONS_DOCUMENT_PRINT				= 'actions-document-print';
	/**
	 * The icon for editing a document.
	 */
	const ACTIONS_DOCUMENT_EDIT					= 'actions-document-edit';
	/**
	 * The icon for accessing document properties.
	 */
	const ACTIONS_DOCUMENT_PROPERTIES			= 'actions-document-properties';
	/**
	 * The icon for saving a document.
	 */
	const ACTIONS_DOCUMENT_SAVE					= 'actions-document-save';
	const ACTIONS_DOCUMENT_DOWNLOAD				= 'actions-document-download';
	const ACTIONS_DOCUMENT_UPLOAD				= 'actions-document-upload';
	/**
	 * The icon for creating a new article category.
	 */
	const ACTIONS_ARTICLE_CATEGORY_NEW			= 'actions-article-category-new';
	/**
	 * The icon for creating a new article topic.
	 */
	const ACTIONS_ARTICLE_TOPIC_NEW				= 'actions-article-topic-new';
	/**
	 * The icon for creating a new article.
	 */
	const ACTIONS_ARTICLE_NEW					= 'actions-article-new';
	/**
	 * The icon for creating a new event.
	 */
	const ACTIONS_EVENT_NEW						= 'actions-event-new';
	/**
	 * The icon for creating a new note.
	 */
	const ACTIONS_NOTE_NEW						= 'actions-note-new';
	/**
	 * The icon for creating a new user.
	 */
	const ACTIONS_USER_NEW						= 'actions-user-new';
	/**
	 * The icon for creating a new user group.
	 */
	const ACTIONS_USER_GROUP_NEW				= 'actions-user-group-new';
	/**
	 * The icon for searching.
	 */
	const ACTIONS_EDIT_FIND						= 'actions-edit-find';
	/**
	 * The icon for searching a user.
	 */
	const ACTIONS_EDIT_FIND_USER				= 'actions-edit-find-user';
	/**
	 * The icon for copying.
	 */
	const ACTIONS_EDIT_COPY						= 'actions-edit-copy';
	/**
	 * The icon for cutting.
	 */
	const ACTIONS_EDIT_CUT						= 'actions-edit-cut';
	/**
	 * The icon for pasting.
	 */
	const ACTIONS_EDIT_PASTE					= 'actions-edit-paste';
	/**
	 * The icon for deleting.
	 */
	const ACTIONS_EDIT_DELETE					= 'actions-edit-delete';
	/**
	 * The icon for accessing user properties.
	 */
	const ACTIONS_USER_PROPERTIES				= 'actions-user-properties';
	/**
	 * The icon for accessing user group properties.
	 */
	const ACTIONS_USER_GROUP_PROPERTIES			= 'actions-user-group-properties';

	const ACTIONS_USER_GROUP_DELETE				= 'actions-user-group-delete';

	const ACTIONS_MAIL_SEND						= 'actions-mail-send';
	const ACTIONS_SET_ACCESS_RIGHTS				= 'actions-set-access-rights';

	/**
	 * @since 8.7.0
	 */
	const ACTIONS_LIST_FILTER					= 'actions-list-filter';

	const ACTIONS_LIST_ADD						= 'actions-list-add';
	const ACTIONS_LIST_ADD_USER					= 'actions-list-add-user';
	const ACTIONS_LIST_REMOVE					= 'actions-list-remove';
	const ACTIONS_LIST_REMOVE_USER				= 'actions-list-remove-user';

	const ACTIONS_GO_HOME						= 'actions-go-home';
	const ACTIONS_GO_UP							= 'actions-go-up';
	const ACTIONS_GO_DOWN						= 'actions-go-down';
	const ACTIONS_GO_FIRST						= 'actions-go-first';
	const ACTIONS_GO_LAST						= 'actions-go-last';
	const ACTIONS_GO_NEXT						= 'actions-go-next';
	const ACTIONS_GO_PREVIOUS					= 'actions-go-previous';

	const ACTIONS_ARROW_DOWN					= 'actions-arrow-down';
	const ACTIONS_ARROW_UP						= 'actions-arrow-up';
	const ACTIONS_ARROW_LEFT					= 'actions-arrow-left';
	const ACTIONS_ARROW_RIGHT					= 'actions-arrow-right';
	const ACTIONS_ARROW_DOWN_DOUBLE				= 'actions-arrow-down-double';
	const ACTIONS_ARROW_UP_DOUBLE				= 'actions-arrow-up-double';
	const ACTIONS_ARROW_LEFT_DOUBLE				= 'actions-arrow-left-double';
	const ACTIONS_ARROW_RIGHT_DOUBLE			= 'actions-arrow-right-double';

	const ACTIONS_CONTEXT_MENU					= 'actions-context-menu';

	const ACTIONS_VIEW_LIST_DETAILS				= 'actions-view-list-details';
	const ACTIONS_VIEW_LIST_TEXT				= 'actions-view-list-text';
	const ACTIONS_VIEW_LIST_TREE				= 'actions-view-list-tree';
	const ACTIONS_VIEW_LIST_CARDS				= 'actions-view-list-cards';

	const ACTIONS_VIEW_CALENDAR_LIST			= 'actions-view-calendar-list';
	const ACTIONS_VIEW_CALENDAR_DAY				= 'actions-view-calendar-day';
	const ACTIONS_VIEW_CALENDAR_WEEK			= 'actions-view-calendar-week';
	const ACTIONS_VIEW_CALENDAR_WORKWEEK		= 'actions-view-calendar-workweek';
	const ACTIONS_VIEW_CALENDAR_MONTH			= 'actions-view-calendar-month';
	const ACTIONS_VIEW_CALENDAR_TIMELINE		= 'actions-view-calendar-timeline';

	const ACTIONS_VIEW_PIM_CALENDAR				= 'actions-view-pim-calendar';
	const ACTIONS_VIEW_PIM_JOURNAL				= 'actions-view-pim-journal';
	const ACTIONS_VIEW_PIM_MAIL					= 'actions-view-pim-mail';
	const ACTIONS_VIEW_PIM_NEWS					= 'actions-view-pim-news';
	const ACTIONS_VIEW_PIM_NOTES				= 'actions-view-pim-notes';
	const ACTIONS_VIEW_PIM_SUMMARY				= 'actions-view-pim-summary';
	const ACTIONS_VIEW_PIM_TASKS				= 'actions-view-pim-tasks';

	const ACTIONS_VIEW_HISTORY					= 'actions-view-history';
	const ACTIONS_VIEW_REFRESH					= 'actions-view-refresh';

	const ACTIONS_ZOOM_IN						= 'actions-zoom-in';
	const ACTIONS_ZOOM_OUT						= 'actions-zoom-out';
	const ACTIONS_ZOOM_ORIGINAL					= 'actions-zoom-original';
	const ACTIONS_ZOOM_FIT_BEST					= 'actions-zoom-fit-best';
	const ACTIONS_ZOOM_FIT_WIDTH				= 'actions-zoom-fit-width';
	const ACTIONS_ZOOM_FIT_HEIGHT				= 'actions-zoom-fit-height';

	const ACTIONS_DIALOG_OK						= 'actions-dialog-ok';
	const ACTIONS_DIALOG_CANCEL					= 'actions-dialog-cancel';

	const ACTIONS_ARCHIVE_CREATE				= 'actions-archive-create';
	const ACTIONS_ARCHIVE_EXTRACT				= 'actions-archive-extract';

	const ACTIONS_AUTHENTICATE					= 'actions-authenticate';
	const ACTIONS_LOGIN							= 'actions-login';
	const ACTIONS_LOGOUT						= 'actions-logout';

	const ACTIONS_SHARE                         = 'actions-share';

	/**
	 * @since 8.7.0
	 */
	const ACTIONS_COMMENT						= 'actions-comment';


	//-----------------------------------------------------------------------
	// Applications
	//-----------------------------------------------------------------------

	const APPS_CALENDAR							= 'apps-calendar';
	const APPS_DIRECTORIES						= 'apps-directories';
	const APPS_FILE_MANAGER						= 'apps-file-manager';
	const APPS_NOTES							= 'apps-notes';
	const APPS_STATISTICS						= 'apps-statistics';
	const APPS_MAIL								= 'apps-mail';
	const APPS_VACATIONS						= 'apps-vacations';
	const APPS_ARTICLES							= 'apps-articles';
	const APPS_FORUMS							= 'apps-forums';
	const APPS_ORGCHARTS						= 'apps-orgcharts';
	const APPS_SUMMARY							= 'apps-summary';
	const APPS_FAQS								= 'apps-faqs';
	const APPS_TASK_MANAGER						= 'apps-task-manager';
	const APPS_APPROBATIONS						= 'apps-approbations';
	const APPS_CONTACTS							= 'apps-contacts';
	const APPS_THESAURUS						= 'apps-thesaurus';
	const APPS_SECTIONS							= 'apps-sections';
	const APPS_DELEGATIONS						= 'apps-delegations';

	const APPS_CALCULATOR						= 'apps-calculator';
	const APPS_EDITOR							= 'apps-editor';
	const APPS_PHOTO							= 'apps-photo';

	const APPS_USERS							= 'apps-users';
	const APPS_GROUPS							= 'apps-groups';

	const APPS_SITEMAP							= 'apps-sitemap';
	const APPS_FORM								= 'apps-form';
	const APPS_SHOP								= 'apps-shop';
	const APPS_CATALOG							= 'apps-catalog';
	const APPS_CRM								= 'apps-crm';
	const APPS_MULTIMEDIA						= 'apps-multimedia';
	const APPS_PORTLET							= 'apps-portlet';




	const APPS_PREFERENCES_SITE					= 'apps-preferences-site';
	const APPS_PREFERENCES_USER					= 'apps-preferences-user';
	const APPS_PREFERENCES_AUTHENTICATION		= 'apps-preferences-authentication';
	const APPS_PREFERENCES_SEARCH_ENGINE		= 'apps-preferences-search-engine';
	const APPS_PREFERENCES_WEBSERVICES			= 'apps-preferences-webservices';
	const APPS_PREFERENCES_DATE_TIME_FORMAT		= 'apps-preferences-date-time-format';
	const APPS_PREFERENCES_CALENDAR				= 'apps-preferences-calendar';
	const APPS_PREFERENCES_MAIL_SERVER			= 'apps-preferences-mail-server';
	const APPS_PREFERENCES_WYSIWYG_EDITOR		= 'apps-preferences-wysiwyg-editor';
	const APPS_PREFERENCES_UPLOAD				= 'apps-preferences-upload';

	const APPS_FACEBOOK							= 'apps-facebook';
	const APPS_FLICKR							= 'apps-flickr';
	const APPS_TWITTER							= 'apps-twitter';
	const APPS_GOOGLEPLUS						= 'apps-googleplus';
	const APPS_PINTEREST						= 'apps-pinterest';
	const APPS_LINKEDIN							= 'apps-linkedin';
	const APPS_VIADEO							= 'apps-viadeo';
	const APPS_RSS								= 'apps-rss';

	//-----------------------------------------------------------------------
	// Mimetypes
	//-----------------------------------------------------------------------
	/**
	 * The icon for a pdf document.
	 */
	const MIMETYPES_APPLICATION_PDF				= 'mimetypes-application-pdf';
	const MIMETYPES_AUDIO_X_GENERIC				= 'mimetypes-audio-x-generic';
	const MIMETYPES_TEXT_X_GENERIC				= 'mimetypes-text-x-generic';
	const MIMETYPES_IMAGE_X_GENERIC				= 'mimetypes-image-x-generic';
	const MIMETYPES_VIDEO_X_GENERIC				= 'mimetypes-video-x-generic';
	const MIMETYPES_PACKAGE_X_GENERIC			= 'mimetypes-package-x-generic';
	const MIMETYPES_TEXT_HTML					= 'mimetypes-text-html';
	const MIMETYPES_UNKNOWN						= 'mimetypes-unknown';
	const MIMETYPES_SIGNATURE					= 'mimetypes-signature';
	const MIMETYPES_OFFICE_DOCUMENT				= 'mimetypes-x-office-document';
	const MIMETYPES_OFFICE_PRESENTATION			= 'mimetypes-x-office-presentation';
	const MIMETYPES_OFFICE_SPREADSHEET			= 'mimetypes-x-office-spreadsheet';

	//-----------------------------------------------------------------------
	// Objects
	//-----------------------------------------------------------------------
	const OBJECTS_USER							= 'objects-user';
	const OBJECTS_GROUP							= 'objects-group';
	const OBJECTS_MANAGER						= 'objects-manager';
	const OBJECTS_PUBLICATION_ARTICLE			= 'objects-publication-article';
	const OBJECTS_PUBLICATION_TOPIC				= 'objects-publication-topic';
	const OBJECTS_PUBLICATION_CATEGORY			= 'objects-publication-category';
	const OBJECTS_INVOICE						= 'objects-invoice';
	const OBJECTS_CONTRACT						= 'objects-contract';
	const OBJECTS_CONTACT						= 'objects-contact';
	const OBJECTS_ORGANIZATION					= 'objects-organization';
	const OBJECTS_TAG							= 'objects-tag';
	const OBJECTS_NOTE							= 'objects-note';
	const OBJECTS_EMAIL							= 'objects-email';
	const OBJECTS_SHOPPING_CART					= 'objects-shopping-cart';
	const OBJECTS_PRODUCT						= 'objects-product'; // catalog or shop product
	const OBJECTS_PRODUCT_CATEGORY				= 'objects-product-category';
	const OBJECTS_RESOURCE						= 'objects-resource';
	const OBJECTS_RESOURCE_CATEGORY				= 'objects-resource-category';
	const OBJECTS_WAITING_ITEM					= 'objects-waiting-item'; // waiting for approval
	const OBJECTS_FACTORY						= 'objects-factory';
	const OBJECTS_ADDRESS_BOOK					= 'objects-address-book';
	const OBJECTS_PHONE							= 'objects-phone';
	const OBJECTS_MOBILE						= 'objects-mobile';
	const OBJECTS_MAP_MARKER					= 'objects-map-marker';
	/**
	 * @since 8.7.0
	 */
	const OBJECTS_BOOK							= 'objects-book';
	/**
	 * @since 8.7.0
	 */
	const OBJECTS_LAPTOP						= 'objects-laptop';
	/**
	 * @since 8.7.0
	 */
	const OBJECTS_DESKTOP						= 'objects-desktop';


	//-----------------------------------------------------------------------
	// Places
	//-----------------------------------------------------------------------
	/**
	 * The icon for a generic folder.
	 */
	const PLACES_FOLDER							= 'places-folder';
	const PLACES_FOLDER_RED						= 'places-folder-red';
	const PLACES_FOLDER_BOOKMARKS				= 'places-folder-bookmarks';
	const PLACES_USER_HOME						= 'places-user-home';
	const PLACES_USER_TRASH						= 'places-user-trash';
	const PLACES_USER_APPLICATIONS				= 'places-user-applications';
	const PLACES_ADMINISTRATOR_HOME				= 'places-administrator-home';
	const PLACES_ADMINISTRATOR_APPLICATIONS		= 'places-administrator-applications';
	const PLACES_MAIL_FOLDER_INBOX				= 'places-mail-folder-inbox';


	//-----------------------------------------------------------------------
	// Categories
	//-----------------------------------------------------------------------
	const CATEGORIES_APPLICATIONS_EDUCATION		= 'categories-applications-education';
	const CATEGORIES_PREFERENCES_DESKTOP		= 'categories-preferences-desktop';
	const CATEGORIES_PREFERENCES_OTHER			= 'categories-preferences-other';

	//-----------------------------------------------------------------------
	// Status
	//-----------------------------------------------------------------------
	const STATUS_DIALOG_ERROR					= 'status-dialog-error';
	const STATUS_DIALOG_INFORMATION				= 'status-dialog-information';
	const STATUS_DIALOG_PASSWORD				= 'status-dialog-password';
	const STATUS_DIALOG_QUESTION				= 'status-dialog-question';
	const STATUS_DIALOG_WARNING					= 'status-dialog-warning';
	const STATUS_CONTENT_LOADING				= 'status-content-loading';
	
	//-----------------------------------------------------------------------
	// MEDICAL
	//-----------------------------------------------------------------------
	const MEDICAL_AMBULANCE      = 'medical-ambulance';
	const MEDICAL_H_SQUARE       = 'medical-h-square';
	const MEDICAL_HEART          = 'medical-heart';
	const MEDICAL_HEART_O        = 'medical-heart-o';
	const MEDICAL_HEARTBEAT      = 'medical-heartbeat';
	const MEDICAL_HOSPITAL_O     = 'medical-hospital-o';
	const MEDICAL_MEDKIT         = 'medical-medkit';
	const MEDICAL_PLUS_SQUARE    = 'medical-plus-square';
	const MEDICAL_STETHOSCOPE    = 'medical-stethoscope';
	const MEDICAL_USER_MD        = 'medical-user-md';
	const MEDICAL_WHEELCHAIR     = 'medical-wheelchair';
	const MEDICAL_WHEELCHAIR_ALT = 'medical-wheelchair-alt';
	
	//-----------------------------------------------------------------------
	// CURRENCY
	//-----------------------------------------------------------------------
	const CURRENCY_YUAN          = 'currency-jpy';
	const CURRENCY_YEN           = 'currency-jpy';
	const CURRENCY_DOLLAR        = 'currency-usd';
	const CURRENCY_EURO          = 'currency-eur';
	const CURRENCY_POUND         = 'currency-gbp';
	const CURRENCY_RUPEE         = 'currency-inr';
	const CURRENCY_WON           = 'currency-krw';
	const CURRENCY_MONEY         = 'currency-money';
	const CURRENCY_RUBLE         = 'currency-rub';
	const CURRENCY_LIRA          = 'currency-try';
	
	//-----------------------------------------------------------------------
	// CHART
	//-----------------------------------------------------------------------
	CONST CHART_AREA_CHART   = 'chart-area-chart';
	CONST CHART_BAR_CHART    = 'chart-bar-chart';
	CONST CHART_LINE_CHART   = 'chart-line-chart';
	CONST CHART_PIE_CHART    = 'chart-pie-chart';
	
	//-----------------------------------------------------------------------
	// TRANSPORTATION
	//-----------------------------------------------------------------------
	CONST TRANSPORTATION_CAR         = 'transportation-car';
	CONST TRANSPORTATION_BICYCLE     = 'transportation-bicycle';
	CONST TRANSPORTATION_BUS         = 'transportation-bus';
	CONST TRANSPORTATION_MOTORCYCLE  = 'transportation-motorcycle';
	CONST TRANSPORTATION_PLANE       = 'transportation-plane';
	CONST TRANSPORTATION_SHIP        = 'transportation-ship';
	CONST TRANSPORTATION_SUBWAY      = 'transportation-subway';
	CONST TRANSPORTATION_TAXI        = 'transportation-taxi';
	CONST TRANSPORTATION_TRAIN       = 'transportation-train';
	CONST TRANSPORTATION_TRUCK       = 'transportation-truck';



	// CSS classes to apply to an html element containing icons
	// to set their format.
	/**
	 * Icon on left 16x16 pixels
	 */
	const ICON_LEFT_16	= 'icon-left-16 icon-left icon-16x16';
	/**
	 * Icon to the left of the label, 24x24 pixels
	 */
	const ICON_LEFT_24	= 'icon-left-24 icon-left icon-24x24';
	/**
	 * Icon to the left of the label, 32x32 pixels
	 */
	const ICON_LEFT_32	= 'icon-left-32 icon-left icon-32x32';
	/**
	 * Icon to the left of the label, 48x48 pixels
	 */
	const ICON_LEFT_48	= 'icon-left-48 icon-left icon-48x48';
	/**
	 * Icon to the left of the label, 64x64 pixels
	 */
	const ICON_LEFT_64	= 'icon-left-64 icon-left icon-64x64';

	/**
	 * Icon above of the label, 16x16 pixels
	 */
	const ICON_TOP_16	= 'icon-top-16 icon-top icon-16x16';
	/**
	 * Icon above of the label, 24x24 pixels
	 */
	const ICON_TOP_24	= 'icon-top-24 icon-top icon-24x24';
	/**
	 * Icon above of the label, 32x32 pixels
	 */
	const ICON_TOP_32	= 'icon-top-32 icon-top icon-32x32';
	/**
	 * Icon above of the label, 48x48 pixels
	 */
	const ICON_TOP_48	= 'icon-top-48 icon-top icon-48x48';
	/**
	 * Icon above of the label, 64x64 pixels
	 */
	const ICON_TOP_64	= 'icon-top-64 icon-top icon-64x64';

	/**
	 * Icon on left symbolic (black and white)
	 */
	const ICON_LEFT_SYMBOLIC =  'icon-left-symbolic icon-left icon-symbolic';
}



/**
 * Provides the default icon theme.
 */
class Func_Icons_Default extends Func_Icons
{
	/**
	 * @return string
	 * @static
	 */
	public function getDescription()
	{
		return bab_translate('Provides the default icon theme.');
	}


	/**
	 * Includes all necessary CSS files to the current page.
	 *
	 * @return bool		false in case of error
	 */
	public function includeCss()
	{
		global $babBody;
		$babBody->addStyleSheet('icons_default.css');
		return true;
	}

	/**
	 * Returns the css file relative url corresponding to the icon theme.
	 *
	 * @return string
	 */
	public function getCss()
	{
		return $GLOBALS['babInstallPath'].'styles/icons_default.css';
	}
}

