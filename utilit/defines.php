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
define("BAB_ADDONS_GROUPS_TBL", "bab_addons_groups");
define("BAB_ADDONS_TBL", "bab_addons");
define("BAB_ARTICLES_TBL", "bab_articles");
define("BAB_CAL_EVENTS_TBL", "bab_cal_events");
define("BAB_CALACCESS_USERS_TBL", "bab_calaccess_users");
define("BAB_CALENDAR_TBL", "bab_calendar");
define("BAB_CALOPTIONS_TBL", "bab_caloptions");
define("BAB_CATEGORIESCAL_TBL", "bab_categoriescal");
define("BAB_COMMENTS_TBL", "bab_comments");
define("BAB_CONTACTS_TBL", "bab_contacts");
define("BAB_DB_DIRECTORIES_TBL", "bab_db_directories");
define("BAB_DBDIRVIEW_GROUPS_TBL", "bab_dbdirview_groups");
define("BAB_DBDIRADD_GROUPS_TBL", "bab_dbdiradd_groups");
define("BAB_DBDIRUPDATE_GROUPS_TBL", "bab_dbdirupdate_groups");
define("BAB_DBDIR_FIELDS_TBL", "bab_dbdir_fields");
define("BAB_DBDIR_FIELDSEXTRA_TBL", "bab_dbdir_fieldsextra");
define("BAB_DBDIR_ENTRIES_TBL", "bab_dbdir_entries");
define("BAB_DG_GROUPS_TBL", "bab_dg_groups");
define("BAB_DG_USERS_GROUPS_TBL", "bab_dg_users_groups");
define("BAB_FAQCAT_TBL", "bab_faqcat");
define("BAB_FAQCAT_GROUPS_TBL", "bab_faqcat_groups");
define("BAB_FAQQR_TBL", "bab_faqqr");
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
define("BAB_FILES_TBL", "bab_files");
define("BAB_FORUMS_TBL", "bab_forums");
define("BAB_FORUMSPOST_GROUPS_TBL", "bab_forumspost_groups");
define("BAB_FORUMSREPLY_GROUPS_TBL", "bab_forumsreply_groups");
define("BAB_FORUMSVIEW_GROUPS_TBL", "bab_forumsview_groups");
define("BAB_GROUPS_TBL", "bab_groups");
define("BAB_HOMEPAGES_TBL", "bab_homepages");
define("BAB_IMAGES_TEMP_TBL", "bab_images_temp");
define("BAB_INI_TBL", "bab_ini");
define("BAB_LDAP_DIRECTORIES_TBL", "bab_ldap_directories");
define("BAB_LDAPDIRVIEW_GROUPS_TBL", "bab_ldapdirview_groups");
define("BAB_MAIL_ACCOUNTS_TBL", "bab_mail_accounts");
define("BAB_MAIL_DOMAINS_TBL", "bab_mail_domains");
define("BAB_MAIL_SIGNATURES_TBL", "bab_mail_signatures");
define("BAB_MIME_TYPES_TBL", "bab_mime_types");
define("BAB_NOTES_TBL", "bab_notes");
define("BAB_POSTS_TBL", "bab_posts");
define("BAB_PRIVATE_SECTIONS_TBL", "bab_private_sections");
define("BAB_RESOURCESCAL_TBL", "bab_resourcescal");
define("BAB_SECTIONS_TBL", "bab_sections");
define("BAB_SECTIONS_GROUPS_TBL", "bab_sections_groups");
define("BAB_SECTIONS_ORDER_TBL", "bab_sections_order");
define("BAB_SECTIONS_STATES_TBL", "bab_sections_states");
define("BAB_SITES_TBL", "bab_sites");
define("BAB_THREADS_TBL", "bab_threads");
define("BAB_TOPICS_TBL", "bab_topics");
define("BAB_TOPICS_CATEGORIES_TBL", "bab_topics_categories");
define("BAB_TOPICSCOM_GROUPS_TBL", "bab_topicscom_groups");
define("BAB_TOPICSSUB_GROUPS_TBL", "bab_topicssub_groups");
define("BAB_TOPICSVIEW_GROUPS_TBL", "bab_topicsview_groups");
define("BAB_USERS_TBL", "bab_users");
define("BAB_USERS_GROUPS_TBL", "bab_users_groups");
define("BAB_USERS_LOG_TBL", "bab_users_log");
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
define("BAB_VAC_USERS_RIGHTS_TBL", "bab_vac_users_rights");
define("BAB_VAC_ENTRIES_TBL", "bab_vac_entries");
define("BAB_VAC_ENTRIES_ELEM_TBL", "bab_vac_entries_elem");
?>