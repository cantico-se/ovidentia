<?php
// actions
define('BAB_TM_ACTION_SET_RIGHT', 'setRight');

define('BAB_TM_ACTION_ADD_PROJECT_SPACE', 'addProjectSpace');
define('BAB_TM_ACTION_MODIFY_PROJECT_SPACE', 'modifyProjectSpace');
define('BAB_TM_ACTION_DELETE_PROJECT_SPACE', 'deleteProjectSpace');
define('BAB_TM_ACTION_SAVE_PROJECTS_CONFIGURATION', 'saveProjectConfiguration');
define('BAB_TM_ACTION_SAVE_PERSONNAL_TASK_CONFIGURATION', 'savePersonnalTaskConfiguration');

define('BAB_TM_ACTION_ADD_OPTION', 'addOption');
define('BAB_TM_ACTION_DEL_OPTION', 'delOption');
define('BAB_TM_ACTION_ADD_SPECIFIC_FIELD', 'addSpecificField');
define('BAB_TM_ACTION_MODIFY_SPECIFIC_FIELD', 'modifySpecificField');
define('BAB_TM_ACTION_DELETE_SPECIFIC_FIELD', 'deleteSpecificField');
define('BAB_TM_ACTION_ADD_CATEGORY', 'addCategory');
define('BAB_TM_ACTION_MODIFY_CATEGORY', 'modifyCategory');
define('BAB_TM_ACTION_DELETE_CATEGORY', 'deleteCategory');
define('BAB_TM_ACTION_ADD_PROJECT', 'addProject');
define('BAB_TM_ACTION_MODIFY_PROJECT', 'modifyProject');
define('BAB_TM_ACTION_MODIFY_PROJECT_PROPERTIES', 'modifyProjectProperties');
define('BAB_TM_ACTION_DELETE_PROJECT', 'deleteProject');
define('BAB_TM_ACTION_UPDATE_WORKING_HOURS', 'updateWorkingHours');
define('BAB_TM_ACTION_ADD_PROJECT_COMMENTARY', 'addProjectCommentary');
define('BAB_TM_ACTION_MODIFY_PROJECT_COMMENTARY', 'modifyProjectCommentary');
define('BAB_TM_ACTION_DELETE_PROJECT_COMMENTARY', 'deleteProjectCommentary');
define('BAB_TM_ACTION_ADD_TASK_COMMENTARY', 'addTaskCommentary');
define('BAB_TM_ACTION_MODIFY_TASK_COMMENTARY', 'modifyTaskCommentary');
define('BAB_TM_ACTION_DELETE_TASK_COMMENTARY', 'deleteTaskCommentary');
define('BAB_TM_ACTION_ADD_TASK', 'addTask');
define('BAB_TM_ACTION_MODIFY_TASK', 'modifyTask');
define('BAB_TM_ACTION_DELETE_TASK', 'deleteTask');
define('BAB_TM_ACTION_CREATE_SPECIFIC_FIELD_INSTANCE', 'createSpecificFieldInstance');
define('BAB_TM_ACTION_MODIFY_NOTICE_EVENT', 'modifyNoticeEvent');

// idx
define('BAB_TM_IDX_DISPLAY_MENU', 'displayMenu');
define('BAB_TM_IDX_DISPLAY_WORKING_HOURS_FORM', 'displayWorkingHoursForm');
define('BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST', 'displayProjectsSpacesList');
define('BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_FORM', 'displayProjectsSpacesForm');
define('BAB_TM_IDX_DISPLAY_DELETE_PROJECTS_SPACES_FORM', 'displayDeleteProjectsSpacesForm');
define('BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_RIGHTS_FORM', 'displayProjectsSpacesRightsForm');
define('BAB_TM_IDX_DISPLAY_PROJECTS_CONFIGURATION_FORM', 'displayProjectsConfigurationForm');
define('BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_CONFIGURATION_FORM', 'displayPersonnalTaskConfigurationForm');
define('BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_LIST', 'displaySpecificFieldList');
define('BAB_TM_IDX_DISPLAY_SPECIFIC_FIELD_FORM', 'displaySpecificFieldForm');
define('BAB_TM_IDX_DISPLAY_DELETE_SPECIFIC_FIELD_FORM', 'displayDeleteSpecificFieldForm');
define('BAB_TM_IDX_DISPLAY_CATEGORIES_LIST', 'displayCategoriesList');
define('BAB_TM_IDX_DISPLAY_CATEGORY_FORM', 'displayCategoryForm');
define('BAB_TM_IDX_DISPLAY_DELETE_CATEGORY_FORM', 'displayDeleteCategoryForm');
define('BAB_TM_IDX_DISPLAY_PROJECT_SPACE_MENU', 'displayProjectSpaceMenu');
define('BAB_TM_IDX_DISPLAY_PROJECT_FORM', 'displayProjectsForm');
define('BAB_TM_IDX_DISPLAY_DELETE_PROJECT_FORM', 'displayDeleteProjectForm');
define('BAB_TM_IDX_DISPLAY_PROJECT_RIGHTS_FORM', 'displayProjectRightsForm');
define('BAB_TM_IDX_DISPLAY_PROJECT_COMMENTARY_LIST', 'displayProjectCommentaryList');
define('BAB_TM_IDX_DISPLAY_DELETE_PROJECT_COMMENTARY', 'displayDeleteProjectCommentary');
define('BAB_TM_IDX_DISPLAY_COMMENTARY_FORM', 'displayCommentaryForm');
define('BAB_TM_IDX_DISPLAY_TASK_COMMENTARY_LIST', 'displayTaskCommentaryList');

define('BAB_TM_IDX_DISPLAY_PROJECT_TASK_LIST', 'displayProjectTaskList');
define('BAB_TM_IDX_DISPLAY_MY_TASK_LIST', 'displayMyTaskList');


define('BAB_TM_IDX_DISPLAY_TASK_FORM', 'displayTaskForm');
define('BAB_TM_IDX_DISPLAY_DELETE_TASK_FORM', 'displayDeleteTaskForm');

define('BAB_TM_IDX_DISPLAY_NOTICE_EVENT_FORM', 'displayNoticeEventForm');

define('BAB_TM_IDX_DISPLAY_PERSONNAL_TASK_RIGHT', 'displayPersonnalTaskRight');
define('BAB_TM_IDX_DISPLAY_GANTT_CHART', 'displayGanttChart');
define('BAB_TM_IDX_DISPLAY_PROJECT_PROPERTIES_FORM', 'displayProjectPropertiesForm');

//define('BAB_TM_IDX_CLOSE_POPUP', 'closePopup');




define('BAB_TM_NONE', -1);

//
define('BAB_TM_YES', 1);
define('BAB_TM_NO', 0);

define('BAB_TM_LOCKED', 1);
define('BAB_TM_UNLOCKED', 0);

define('BAB_TM_ENABLE', 1);
define('BAB_TM_DISABLE', 0);

//Task numerotation
define('BAB_TM_MANUAL', 0);
define('BAB_TM_SEQUENTIAL', 1);
define('BAB_TM_YEAR_SEQUENTIAL', 2);
define('BAB_TM_YEAR_MONTH_SEQUENTIAL', 3);

//Event Type
define('BAB_TM_TASK', 0);
define('BAB_TM_CHECKPOINT', 1);
define('BAB_TM_TODO', 2);

//duration type
define('BAB_TM_DURATION', 0);
define('BAB_TM_DATE', 1);

//duration unit
define('BAB_TM_DAY', 0);
define('BAB_TM_HOUR', 1);


//participation status
define('BAB_TM_TENTATIVE', 0);
define('BAB_TM_ACCEPTED', 1);
define('BAB_TM_IN_PROGRESS', 2);
define('BAB_TM_ENDED', 3);
define('BAB_TM_REFUSED', 4);

//Link type
define('BAB_TM_END_TO_START', 0);
define('BAB_TM_START_TO_START', 1);

//Task user profil
define('BAB_TM_UNDEFINED', -1);
define('BAB_TM_SUPERVISOR', 0);
define('BAB_TM_PROJECT_MANAGER', 1);
define('BAB_TM_TASK_RESPONSIBLE', 2);
define('BAB_TM_PERSONNAL_TASK_OWNER', 3);

//Event notice
define('BAB_TM_EV_PROJECT_CREATED', 0);
define('BAB_TM_EV_PROJECT_DELETED', 1);
define('BAB_TM_EV_TASK_CREATED', 2);
define('BAB_TM_EV_TASK_UPDATED_BY_MGR', 3);
define('BAB_TM_EV_TASK_UPDATED_BY_RESP', 4);
define('BAB_TM_EV_TASK_DELETED', 5);
define('BAB_TM_EV_NOTICE_ALERT', 6);

define('BAB_TM_EV_NEW_TASK_RESPONSIBLE', 7);
define('BAB_TM_EV_NO_MORE_TASK_RESPONSIBLE', 8);
define('BAB_TM_EV_TASK_RESPONSIBLE_PROPOSED', 9);
define('BAB_TM_EV_TASK_STARTED', 10);

//Specific field class
define ('BAB_TM_TEXT_FIELD', 0);
define ('BAB_TM_TEXT_AREA_FIELD', 1);
define ('BAB_TM_RADIO_FIELD', 2);


?>