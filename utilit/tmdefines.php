<?php
// Tables
define('BAB_TSKMGR_WEEK_DAYS_TBL', 'bab_tskmgr_week_days');
define('BAB_TSKMGR_WORKING_HOURS_TBL', 'bab_tskmgr_week_days');

define('BAB_TSKMGR_PROJECTS_SPACES_TBL', 'bab_tskmgr_projects_spaces');
define('BAB_TSKMGR_PROJECT_CREATOR_GROUPS_TBL', 'bab_tskmgr_project_creator_groups');
define('BAB_TSKMGR_PERSONNAL_TASK_CREATOR_GROUPS_TBL', 'bab_tskmgr_personnal_task_creator_groups');
define('BAB_TSKMGR_DEFAULT_PROJECTS_MANAGERS_GROUPS_TBL', 'bab_tskmgr_default_projects_managers_groups');
define('BAB_TSKMGR_DEFAULT_PROJECTS_SUPERVISORS_GROUPS_TBL', 'bab_tskmgr_default_projects_supervisors_groups');
define('BAB_TSKMGR_DEFAULT_PROJECTS_VISUALIZERS_GROUPS_TBL', 'bab_tskmgr_default_projects_visualizers_groups');
define('BAB_TSKMGR_DEFAULT_PROJECTS_RESPONSIBLE_GROUPS_TBL', 'bab_tskmgr_default_projects_responsible_groups');
define('BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL', 'bab_tskmgr_default_projects_configuration');
define('BAB_TSKMGR_SPECIFIC_FIELDS_BASE_CLASS_TBL', 'bab_tskmgr_specific_fields_base_class');
define('BAB_TSKMGR_SPECIFIC_FIELDS_TEXT_CLASS_TBL', 'bab_tskmgr_specific_fields_text_class');
define('BAB_TSKMGR_SPECIFIC_FIELDS_AREA_CLASS_TBL', 'bab_tskmgr_specific_fields_text_class');
define('BAB_TSKMGR_SPECIFIC_FIELDS_RADIO_CLASS_TBL', 'bab_tskmgr_specific_fields_radio_class');
define('BAB_TSKMGR_SPECIFIC_FIELDS_INSTANCE_LIST_TBL', 'bab_tskmgr_specific_fields_instance_list');
define('BAB_TSKMGR_CATEGORIES_TBL', 'bab_tskmgr_categories');


// actions
define('BAB_TM_ACTION_SET_RIGHT', 'setRight');

define('BAB_TM_ACTION_ADD_PROJECT_SPACE', 'addProjectSpace');
define('BAB_TM_ACTION_MODIFY_PROJECT_SPACE', 'modifyProjectSpace');
define('BAB_TM_ACTION_DELETE_PROJECT_SPACE', 'deleteProjectSpace');
define('BAB_TM_ACTION_SAVE_DEFAULT_PROJECTS_CONFIGURATION', 'saveDefaultProjectConfiguration');

define('BAB_TM_ACTION_ADD_OPTION', 'addOption');
define('BAB_TM_ACTION_DEL_OPTION', 'delOption');
define('BAB_TM_ACTION_ADD_SPECIFIC_FIELD', 'addSpecificField');
define('BAB_TM_ACTION_MODIFY_SPECIFIC_FIELD', 'modifySpecificField');
define('BAB_TM_ACTION_DELETE_SPECIFIC_FIELD', 'deleteSpecificField');

define('BAB_TM_ACTION_ADD_CATEGORY', 'addCategory');
define('BAB_TM_ACTION_MODIFY_CATEGORY', 'modifyCategory');
define('BAB_TM_ACTION_DELETE_CATEGORY', 'deleteCategory');

// idx
define('BAB_TM_IDX_DISPLAY_ADMIN_MENU', 'displayAdminMenu');
define('BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM', 'displayWorkingHoursForm');
define('BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST', 'displayProjectsSpacesList');
define('BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM', 'displayProjectsSpacesForm');
define('BAB_TM_IDX_DISPLAY_DELETE_PROJECTS_SPACES_FORM', 'displayDeleteProjectsSpacesForm');
define('BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_RIGHTS_FORM', 'displayProjectsSpacesRightsForm');
define('BAB_TM_IDX_DISPLAY_DEFAULT_PROJECTS_CONFIGURATION_FORM', 'displayDefaultProjectsConfigurationForm');
define('BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST', 'displaySpecificFieldList');
define('BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM', 'displaySpecificFieldForm');
define('BAB_TM_IDX_DISPLAY_DELETE_SPECIFIC_FIELD_FORM', 'displayDeleteSpecificFieldForm');
define('BAB_TM_IDX_DISPLAY_CATEGORIES_LIST', 'displayCategoriesList');
define('BAB_TM_IDX_DISPLAY_CATEGORY_FORM', 'displayCategoryForm');
define('BAB_TM_IDX_DISPLAY_DELETE_CATEGORY_FORM', 'displayDeleteCategoryForm');


//
define('BAB_TM_YES', 1);
define('BAB_TM_NO', 0);


//Task numerotation
define('BAB_TM_MANUAL', 0);
define('BAB_TM_SEQUENTIAL', 1);
define('BAB_TM_YEAR_SEQUENTIAL', 2);
define('BAB_TM_YEAR_MONTH_SEQUENTIAL', 3);
?>