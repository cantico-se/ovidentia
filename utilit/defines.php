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
define("BAB_DBDIR_MAX_COMMON_FIELDS", 100);

define("BAB_ALLUSERS_GROUP",		0);
define("BAB_REGISTERED_GROUP",		1);
define("BAB_UNREGISTERED_GROUP",	2);
define("BAB_ADMINISTRATOR_GROUP",	3);

define("BAB_ACL_GROUP_TREE", 1000000000);

define("BAB_AUTHENTIFICATION_OVIDENTIA",	0);
define("BAB_AUTHENTIFICATION_LDAP",			1);
define("BAB_AUTHENTIFICATION_AD",			2); /* Active directory */


define("BAB_DIR_ENTRY_ID_USER",				1);
define("BAB_DIR_ENTRY_ID",					2);
define("BAB_DIR_ENTRY_ID_DIRECTORY",		3);
define("BAB_DIR_ENTRY_ID_GROUP",			4);

/* function bab_toHtml() */

define('BAB_HTML_ENTITIES'		,1);
define('BAB_HTML_P'				,BAB_HTML_ENTITIES << 1);
define('BAB_HTML_BR'			,BAB_HTML_ENTITIES << 2);
define('BAB_HTML_LINKS'			,BAB_HTML_ENTITIES << 3);
define('BAB_HTML_AUTO'			,BAB_HTML_ENTITIES << 4);
define('BAB_HTML_ALL'			,BAB_HTML_ENTITIES | BAB_HTML_P | BAB_HTML_BR | BAB_HTML_LINKS);

/* calendars */
define("BAB_CAL_USER_TYPE", 1);
define("BAB_CAL_PUB_TYPE", 2);
define("BAB_CAL_RES_TYPE", 3);

define("BAB_CAL_ACCESS_VIEW",	0); /* only for viewing */
define("BAB_CAL_ACCESS_UPDATE", 1); /* can modify/delete event if user is creator */
define("BAB_CAL_ACCESS_FULL",	2); /* full access */

define("BAB_CAL_VIEW_MONTH",	0); /* month view */
define("BAB_CAL_VIEW_WEEK",		1); /* week view */
define("BAB_CAL_VIEW_DAY",		2); /* day view */

define("BAB_CAL_STATUS_ACCEPTED", 0);
define("BAB_CAL_STATUS_NONE",  1);
define("BAB_CAL_STATUS_DECLINED",  2);

define("BAB_CAL_RECUR_DAILY",	1);
define("BAB_CAL_RECUR_WEEKLY",	2);
define("BAB_CAL_RECUR_MONTHLY",	3);
define("BAB_CAL_RECUR_YEARLY",	4);


define("BAB_ADDONS_GROUPS_TBL", "bab_addons_groups");
define("BAB_ADDONS_TBL", "bab_addons");
define("BAB_ARTICLES_TBL", "bab_articles");
define("BAB_ART_FILES_TBL", "bab_art_files");
define("BAB_ART_DRAFTS_NOTES_TBL", "bab_art_drafts_notes");
define("BAB_ART_DRAFTS_TBL", "bab_art_drafts");
define("BAB_ART_DRAFTS_FILES_TBL", "bab_art_drafts_files");
define("BAB_ART_LOG_TBL", "bab_art_log");
define("BAB_CAL_CATEGORIES_TBL", "bab_cal_categories");
define("BAB_CAL_EVENTS_NOTES_TBL", "bab_cal_events_notes");
define("BAB_CAL_USER_OPTIONS_TBL", "bab_cal_user_options");
define("BAB_CAL_EVENTS_TBL", "bab_cal_events");
define("BAB_CAL_EVENTS_OWNERS_TBL", "bab_cal_events_owners");
define("BAB_CAL_EVENTS_REMINDERS_TBL", "bab_cal_events_reminders");
define("BAB_CAL_PUBLIC_TBL", "bab_cal_public");
define("BAB_CAL_PUB_VIEW_GROUPS_TBL", "bab_cal_pub_view_groups");
define("BAB_CAL_PUB_MAN_GROUPS_TBL", "bab_cal_pub_man_groups");
define("BAB_CAL_PUB_GRP_GROUPS_TBL", "bab_cal_pub_grp_groups");
define("BAB_CAL_RES_VIEW_GROUPS_TBL", "bab_cal_res_view_groups");
define("BAB_CAL_RES_ADD_GROUPS_TBL", "bab_cal_res_add_groups");
define("BAB_CAL_RES_MAN_GROUPS_TBL", "bab_cal_res_man_groups");
define("BAB_CAL_RES_GRP_GROUPS_TBL", "bab_cal_res_grp_groups");
define("BAB_CATEGORIESCAL_TBL", "bab_categoriescal");
define("BAB_CAL_RESOURCES_TBL", "bab_cal_resources");
define("BAB_CALACCESS_USERS_TBL", "bab_calaccess_users");
define("BAB_CALENDAR_TBL", "bab_calendar");
define("BAB_CALOPTIONS_TBL", "bab_caloptions");
define("BAB_COMMENTS_TBL", "bab_comments");
define("BAB_CONTACTS_TBL", "bab_contacts");
define("BAB_DB_DIRECTORIES_TBL", "bab_db_directories");
define("BAB_DBDIRVIEW_GROUPS_TBL", "bab_dbdirview_groups");
define("BAB_DBDIRADD_GROUPS_TBL", "bab_dbdiradd_groups");
define("BAB_DBDIRUPDATE_GROUPS_TBL", "bab_dbdirupdate_groups");
define("BAB_DBDIRDEL_GROUPS_TBL",	"bab_dbdirdel_groups");
define("BAB_DBDIREXPORT_GROUPS_TBL", "bab_dbdirexport_groups");
define("BAB_DBDIRIMPORT_GROUPS_TBL", "bab_dbdirimport_groups");
define("BAB_DBDIRBIND_GROUPS_TBL", "bab_dbdirbind_groups");
define("BAB_DBDIRUNBIND_GROUPS_TBL", "bab_dbdirunbind_groups");
define("BAB_DBDIREMPTY_GROUPS_TBL", "bab_dbdirempty_groups");
define("BAB_DBDIR_FIELDS_TBL", "bab_dbdir_fields");
define("BAB_DBDIR_FIELDS_DIRECTORY_TBL", "bab_dbdir_fields_directory");
define("BAB_DBDIR_FIELDSEXTRA_TBL", "bab_dbdir_fieldsextra");
define("BAB_DBDIR_FIELDSVALUES_TBL", "bab_dbdir_fieldsvalues");
define("BAB_DBDIR_CONFIGEXPORT_TBL", "bab_dbdir_configexport");
define("BAB_DBDIR_FIELDSEXPORT_TBL", "bab_dbdir_fieldsexport");
define("BAB_DBDIR_ENTRIES_TBL", "bab_dbdir_entries");
define("BAB_DBDIR_ENTRIES_EXTRA_TBL", "bab_dbdir_entries_extra");
define("BAB_DBDIR_OPTIONS_TBL", "bab_dbdir_options");
define("BAB_DG_GROUPS_TBL", "bab_dg_groups");
define("BAB_DG_USERS_GROUPS_TBL", "bab_dg_users_groups");
define("BAB_DG_ADMIN_TBL", "bab_dg_admin");
define("BAB_FAQ_TREES_TBL", "bab_faq_trees");
define("BAB_FAQCAT_TBL", "bab_faqcat");
define("BAB_FAQMANAGERS_GROUPS_TBL", "bab_faqmanagers_groups");
define("BAB_FAQCAT_GROUPS_TBL", "bab_faqcat_groups");
define("BAB_FAQQR_TBL", "bab_faqqr");
define("BAB_FAQ_SUBCAT_TBL", "bab_faq_subcat");
define("BAB_FA_INSTANCES_TBL", "bab_fa_instances");
define("BAB_FAR_INSTANCES_TBL", "bab_far_instances");
define("BAB_FLOW_APPROVERS_TBL", "bab_flow_approvers");
define("BAB_FM_FIELDS_TBL", "bab_fm_fields");
define("BAB_FM_FIELDSVAL_TBL", "bab_fm_fieldsval");
define("BAB_FM_FILESLOG_TBL", "bab_fm_fileslog");
define("BAB_FM_FILESVER_TBL", "bab_fm_filesver");
define("BAB_FM_FOLDERS_TBL", "bab_fm_folders");
define("BAB_FMUPLOAD_GROUPS_TBL", "bab_fmupload_groups");
define("BAB_FMDOWNLOAD_GROUPS_TBL", "bab_fmdownload_groups");
define("BAB_FMUPDATE_GROUPS_TBL", "bab_fmupdate_groups");
define("BAB_FMMANAGERS_GROUPS_TBL", "bab_fmmanagers_groups");
define("BAB_FILES_TBL", "bab_files");
define("BAB_FORUMS_TBL", "bab_forums");
define("BAB_FORUMSMAN_GROUPS_TBL", "bab_forumsman_groups");
define("BAB_FORUMSPOST_GROUPS_TBL", "bab_forumspost_groups");
define("BAB_FORUMSREPLY_GROUPS_TBL", "bab_forumsreply_groups");
define("BAB_FORUMSVIEW_GROUPS_TBL", "bab_forumsview_groups");
define("BAB_FORUMSFILES_GROUPS_TBL", "bab_forumsfiles_groups");
define("BAB_FORUMSNOTIFY_GROUPS_TBL", "bab_forumsnotify_groups");
define("BAB_GROUPS_TBL", "bab_groups");
define("BAB_GROUPS_SET_ASSOC_TBL", "bab_groups_set_assoc");
define("BAB_HOMEPAGES_TBL", "bab_homepages");
define("BAB_IMAGES_TEMP_TBL", "bab_images_temp");
define("BAB_INI_TBL", "bab_ini");
define("BAB_LDAP_DIRECTORIES_TBL", "bab_ldap_directories");
define("BAB_LDAPDIRVIEW_GROUPS_TBL", "bab_ldapdirview_groups");
define("BAB_LDAP_SITES_FIELDS_TBL", "bab_ldap_sites_fields");
define("BAB_MAIL_ACCOUNTS_TBL", "bab_mail_accounts");
define("BAB_MAIL_DOMAINS_TBL", "bab_mail_domains");
define("BAB_MAIL_SIGNATURES_TBL", "bab_mail_signatures");
define("BAB_MIME_TYPES_TBL", "bab_mime_types");
define("BAB_NOTES_TBL", "bab_notes");
define("BAB_ORG_CHARTS_TBL", "bab_org_charts");
define("BAB_OCVIEW_GROUPS_TBL", "bab_ocview_groups");
define("BAB_OCUPDATE_GROUPS_TBL", "bab_ocupdate_groups");
define("BAB_OC_ENTITIES_TBL", "bab_oc_entities");
define("BAB_OC_TREES_TBL", "bab_oc_trees");
define("BAB_OC_ROLES_TBL", "bab_oc_roles");
define("BAB_OC_ROLES_USERS_TBL", "bab_oc_roles_users");
define("BAB_POSTS_TBL", "bab_posts");
define("BAB_PRIVATE_SECTIONS_TBL", "bab_private_sections");
define("BAB_PROFILES_TBL", "bab_profiles");
define("BAB_PROFILES_GROUPSSET_TBL", "bab_profiles_groupsset");
define("BAB_PROFILES_GROUPS_TBL", "bab_profiles_groups");
define("BAB_RESOURCESCAL_TBL", "bab_resourcescal");
define("BAB_SECTIONS_TBL", "bab_sections");
define("BAB_SECTIONS_GROUPS_TBL", "bab_sections_groups");
define("BAB_SECTIONS_ORDER_TBL", "bab_sections_order");
define("BAB_SECTIONS_STATES_TBL", "bab_sections_states");
define("BAB_SITES_TBL", "bab_sites");
define("BAB_SITES_HPMAN_GROUPS_TBL", "bab_sites_hpman_groups");
define("BAB_SITES_FIELDS_REGISTRATION_TBL", "bab_sites_fields_registration");
define("BAB_SITES_DISCLAIMERS_TBL", "bab_sites_disclaimers");
define("BAB_SITES_NONWORKING_CONFIG_TBL", "bab_sites_nonworking_config");
define("BAB_SITES_NONWORKING_DAYS_TBL", "bab_sites_nonworking_days");
define("BAB_SITES_EDITOR_TBL", "bab_sites_editor");
define("BAB_SITES_SWISH_TBL", "bab_sites_swish");
define("BAB_STATS_EVENTS_TBL", "bab_stats_events");
define("BAB_STATS_ADDONS_TBL", "bab_stats_addons");
define("BAB_STATS_ARTICLES_TBL", "bab_stats_articles");
define("BAB_STATS_ARTICLES_REF_TBL", "bab_stats_articles_ref");
define("BAB_STATS_FAQQRS_TBL", "bab_stats_faqqrs");
define("BAB_STATS_FAQS_TBL", "bab_stats_faqs");
define("BAB_STATS_FMFILES_TBL", "bab_stats_fmfiles");
define("BAB_STATS_FMFOLDERS_TBL", "bab_stats_fmfolders");
define("BAB_STATS_FORUMS_TBL", "bab_stats_forums");
define("BAB_STATS_IMODULES_TBL", "bab_stats_imodules");
define("BAB_STATS_IPAGES_TBL", "bab_stats_ipages");
define("BAB_STATS_MODULES_TBL", "bab_stats_modules");
define("BAB_STATS_OVML_TBL", "bab_stats_ovml");
define("BAB_STATS_PAGES_TBL", "bab_stats_pages");
define("BAB_STATS_POSTS_TBL", "bab_stats_posts");
define("BAB_STATS_PREFERENCES_TBL", "bab_stats_preferences");
define("BAB_STATS_SEARCH_TBL", "bab_stats_search");
define("BAB_STATS_THREADS_TBL", "bab_stats_threads");
define("BAB_STATS_XLINKS_TBL", "bab_stats_xlinks");
define("BAB_STATSMAN_GROUPS_TBL", "bab_statsman_groups");
define("BAB_THREADS_TBL", "bab_threads");
define("BAB_TOPCAT_ORDER_TBL", "bab_topcat_order");
define("BAB_TOPICS_TBL", "bab_topics");
define("BAB_TOPICS_CATEGORIES_TBL", "bab_topics_categories");
define("BAB_TOPICSCOM_GROUPS_TBL", "bab_topicscom_groups");
define("BAB_TOPICSMAN_GROUPS_TBL", "bab_topicsman_groups");
define("BAB_TOPICSMOD_GROUPS_TBL", "bab_topicsmod_groups");
define("BAB_TOPICSSUB_GROUPS_TBL", "bab_topicssub_groups");
define("BAB_TOPICSVIEW_GROUPS_TBL", "bab_topicsview_groups");
define("BAB_USERS_TBL", "bab_users");
define("BAB_USERS_GROUPS_TBL", "bab_users_groups");
define("BAB_USERS_LOG_TBL", "bab_users_log");
define("BAB_USERS_UNAVAILABILITY_TBL", "bab_users_unavailability");
define("BAB_VACATIONS_TBL", "bab_vacations");
define("BAB_VACATIONS_STATES_TBL", "bab_vacations_states");
define("BAB_VACATIONS_TYPES_TBL", "bab_vacations_types");
define("BAB_VACATIONSMAN_GROUPS_TBL", "bab_vacationsman_groups");
define("BAB_VACATIONSVIEW_GROUPS_TBL", "bab_vacationsview_groups");
define("BAB_VAC_MANAGERS_TBL", "bab_vac_managers");
define("BAB_VAC_TYPES_TBL", "bab_vac_types");
define("BAB_VAC_COLLECTIONS_TBL", "bab_vac_collections");
define("BAB_VAC_COLL_TYPES_TBL", "bab_vac_coll_types");
define("BAB_VAC_PERSONNEL_TBL", "bab_vac_personnel");
define("BAB_VAC_RIGHTS_TBL", "bab_vac_rights");
define("BAB_VAC_RIGHTS_RULES_TBL", "bab_vac_rights_rules");
define("BAB_VAC_USERS_RIGHTS_TBL", "bab_vac_users_rights");
define("BAB_VAC_ENTRIES_TBL", "bab_vac_entries");
define("BAB_VAC_ENTRIES_ELEM_TBL", "bab_vac_entries_elem");
define("BAB_VAC_PLANNING_TBL", "bab_vac_planning");
define("BAB_VAC_OPTIONS_TBL", "bab_vac_options");

// Task manager tables
define('BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL', 'bab_tskmgr_project_creator_groups');
define('BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL', 'bab_tskmgr_personnal_task_creator_groups');
define('BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL', 'bab_tskmgr_default_projects_managers_groups');
define('BAB_TSKMGR_DEFAULT_PROJECTS_SUPERVISORS_GROUPS_TBL', 'bab_tskmgr_default_projects_supervisors_groups');
define('BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL', 'bab_tskmgr_default_projects_visualizers_groups');
define('BAB_TSKMGR_DEFAULT_TASK_RESPONSIBLE_GROUPS_TBL', 'bab_tskmgr_default_task_responsible_groups');

define('BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL', 'bab_tskmgr_projects_managers_groups');
define('BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL', 'bab_tskmgr_projects_supervisors_groups');
define('BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL', 'bab_tskmgr_projects_visualizers_groups');
define('BAB_TSKMGR_TASK_RESPONSIBLE_GROUPS_TBL', 'bab_tskmgr_task_responsible_groups');

define('BAB_TSKMGR_WEEK_DAYS_TBL', 'bab_tskmgr_week_days');
define('BAB_TSKMGR_WORKING_HOURS_TBL', 'bab_tskmgr_week_days');
define('BAB_TSKMGR_PROJECTS_SPACES_TBL', 'bab_tskmgr_projects_spaces');
define('BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL', 'bab_tskmgr_default_projects_configuration');
define('BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL', 'bab_tskmgr_specific_fields_base_class');
define('BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL', 'bab_tskmgr_specific_fields_text_class');
define('BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL', 'bab_tskmgr_specific_fields_area_class');
define('BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL', 'bab_tskmgr_specific_fields_radio_class');
define('BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL', 'bab_tskmgr_specific_fields_instance_list');
define('BAB_TSKMGR_CATEGORIES_TBL', 'bab_tskmgr_categories');
define('BAB_TSKMGR_PROJECTS_CONFIGURATION_TBL', 'bab_tskmgr_projects_configuration');
define('BAB_TSKMGR_PROJECTS_TBL', 'bab_tskmgr_projects');
define('BAB_TSKMGR_PROJECTS_COMMENTS_TBL', 'bab_tskmgr_projects_comments');
define('BAB_TSKMGR_PROJECTS_REVISIONS_TBL', 'bab_tskmgr_projects_revisions');
define('BAB_TSKMGR_TASKS_TBL', 'bab_tskmgr_tasks');
define('BAB_TSKMGR_TASKS_COMMENTS_TBL', 'bab_tskmgr_tasks_comments');
define('BAB_TSKMGR_TASKS_WEEK_DAYS_TBL', 'bab_tskmgr_tasks_week_days');
define('BAB_TSKMGR_TASKS_WORKING_HOURS_TBL', 'bab_tskmgr_tasks_working_hours');

?>
