<?php
namespace dkd\TcBeuser\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006 Ingo Renner <ingo.renner@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use dkd\TcBeuser\Utility\TcBeuserUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Module 'User Admin' for the 'tc_beuser' extension.
 *
 * @author	Ingo Renner <ingo.renner@dkd.de>
 * @package	TYPO3
 * @subpackage	tx_tcbeuser
 */
class UserAdminController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	var $content;

	/** @var $doc \TYPO3\CMS\Backend\Template\DocumentTemplate */
	var $doc;
	var $jsCode;
	var $MOD_MENU = array();
	var $MOD_SETTINGS = array();
	var $pageinfo;

	/** @var $tceforms \TYPO3\CMS\Backend\Form\FormEngine */
	var $tceforms;

	/**
	 * @var	object tx_tcbeuser_config	$permChecker helps checking BE user permissions
	 */
	var $permChecker;

	function main() {
		$this->init();

		//TODO more access check!?
		$access = $GLOBALS['BE_USER']->modAccess($this->MCONF, true);

		if ($access || $GLOBALS['BE_USER']->isAdmin()) {
			// We need some uid in rootLine for the access check, so use first webmount
			$webmounts = $GLOBALS['BE_USER']->returnWebmounts();
			$this->pageinfo['uid'] = $webmounts[0];
			$this->pageinfo['_thePath'] = '/';

			$title = $GLOBALS['LANG']->getLL('title');
			$menu  = BackendUtility::getFuncMenu(
				$this->id,
				'SET[function]',
				$this->MOD_SETTINGS['function'],
				$this->MOD_MENU['function']
			);

			$moduleContent = $this->moduleContent();

			// all necessary JS code needs to be set before this line!
			$this->tceforms = GeneralUtility::makeInstance('\\TYPO3\\CMS\\Backend\\Form\\FormEngine');
			$this->tceforms->backPath = $GLOBALS['BACK_PATH'];
			$this->doc->JScode = $this->tceforms->JSbottom('editform');
			$this->doc->JScode .= $this->doc->wrapScriptTags($this->jsCode);

			$this->content  = '';
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->section(
				'',
				$this->doc->funcMenu(
					$this->doc->header($title),
					$menu
				)
			);
			$this->content .= $this->doc->divider(5);
			$this->content .= $moduleContent;


			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $this->docHeaderButtons['csh'];
			$markers['FUNC_MENU'] = BackendUtility::getFuncMenu($this->id, 'SET[mode]', $this->MOD_SETTINGS['mode'], $this->MOD_MENU['mode']);
			$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
			$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);

			$this->content = $this->doc->render($GLOBALS['LANG']->getLL('permissions'), $this->content);

		}

		$GLOBALS['BE_USER']->user['admin'] = 0;
	}

	/**
	 * First initialization.
	 *
	 * @return	void
	 */
	function preInit() {
		// Setting GPvars:
		$this->editconf = GeneralUtility::_GP('edit');
		$this->defVals = GeneralUtility::_GP('defVals');
		$this->overrideVals = GeneralUtility::_GP('overrideVals');
		$this->columnsOnly = GeneralUtility::_GP('columnsOnly');
		$this->returnUrl = GeneralUtility::_GP('returnUrl');
		$this->closeDoc = GeneralUtility::_GP('closeDoc');
		$this->doSave = GeneralUtility::_GP('doSave');
		$this->returnEditConf = GeneralUtility::_GP('returnEditConf');

		// Setting override values as default if defVals does not exist.
		if (!is_array($this->defVals) && is_array($this->overrideVals)) {
			$this->defVals = $this->overrideVals;
		}

		//get pid FE
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tc_beuser']);

		// Setting return URL
		$this->retUrl = $this->returnUrl ? $this->returnUrl
			: BackendUtility::getModuleUrl($GLOBALS['MCONF']['name'], array('SET[function]' => 1));

		// Make R_URL (request url) based on input GETvars:
		$this->R_URL_parts = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
		$this->R_URL_getvars = GeneralUtility::_GET();
		$this->R_URL_getvars['edit'] = $this->editconf;

		if ($this->closeDoc > 0) {
			$this->closeDocument();
		}

	}

	/**
	 * Detects, if a save command has been triggered.
	 *
	 * @return	boolean		True, then save the document (data submitted)
	 */
	function doProcessData() {
		$out = $this->doSave || isset($_POST['_savedok_x']) || isset($_POST['_saveandclosedok_x']) || isset($_POST['_savedokview_x']) || isset($_POST['_savedoknew_x']);
		return $out;
	}

	/**
	 * Do processing of data, submitting it to TCEmain.
	 *
	 * @return	void
	 */
	function processData() {
		if($GLOBALS['BE_USER']->user['admin'] != 1) {
			//make fake Admin
			TcBeuserUtility::fakeAdmin();
			$fakeAdmin = 1;
		}
		// GPvars specifically for processing:
		$this->data = GeneralUtility::_GP('data');
		$this->cmd = GeneralUtility::_GP('cmd')?GeneralUtility::_GP('cmd'):array();
		$this->disableRTE = GeneralUtility::_GP('_disableRTE');

		//check data with fe user
		if(is_array($this->data)) {
			$table = array_keys($this->data);
			$uid = array_keys($this->data[$table[0]]);
			$data = $this->data[$table[0]][$uid[0]];
			$fePID = intval($this->extConf['pidFE']);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'fe_users',
				'pid = '.$fePID.BackendUtility::deleteClause('fe_users').' AND username = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($data['username'],'fe_users')
			);

			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if( (trim($data['realName']) == trim($row['name'])) && (trim($data['email']) == trim($row['email']))) {
					$notSync = 0;
				} else {
					if (strpos($uid[0],'NEW') !== FALSE) {
						$feExist = 1;
					} else {
						$notSync = 1;
					}
				}
			}
		}

		if($notSync || $feExist ) {
			$notSync ? $this->error[] = array('error',$GLOBALS['LANG']->getLL('data-sync')) : '';
			$feExist ? $this->error[] = array('error',$GLOBALS['LANG']->getLL('fe-exist')) : '';
		} else {
			// See tce_db.php for relevate options here:
			// Only options related to $this->data submission are included here.
			/** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
			$tce = GeneralUtility::makeInstance('\\TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 0;

			// Setting default values specific for the user:
			$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
			if (is_array($TCAdefaultOverride)) {
				$tce->setDefaultsFromUserTS($TCAdefaultOverride);
			}

			// Setting internal vars:
			if ($GLOBALS['BE_USER']->uc['neverHideAtCopy']) {
				$tce->neverHideAtCopy = 1;
			}
			$tce->debug = 0;
			$tce->disableRTE = $this->disableRTE;

			// Loading TCEmain with data:
			$tce->start($this->data,$this->cmd);
			if (is_array($this->mirror)) {
				$tce->setMirror($this->mirror);
			}

			// If pages are being edited, we set an instruction about updating the page tree after this operation.
			if (isset($this->data['pages'])) {
				BackendUtility::setUpdateSignal('updatePageTree');
			}


			// Checking referer / executing
			$refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
			$httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
			if ($httpHost!=$refInfo['host'] && $this->vC!=$GLOBALS['BE_USER']->veriCode() && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']) {
				$tce->log('',0,0,0,1,"Referer host '%s' and server host '%s' did not match and veriCode was not valid either!",1,array($refInfo['host'],$httpHost));
				debug('Error: Referer host did not match with server host.');
			} else {

				// Perform the saving operation with TCEmain:
				$tce->process_uploads($_FILES);
				$tce->process_datamap();
				$tce->process_cmdmap();

				// If there was saved any new items, load them:
				if (count($tce->substNEWwithIDs_table)) {

					// Resetting editconf:
					$this->editconf = array();

					// Traverse all new records and forge the content of ->editconf so we can continue to EDIT these records!
					foreach($tce->substNEWwithIDs_table as $nKey => $nTable) {
						$editId = $tce->substNEWwithIDs[$nKey];
						// translate new id to the workspace version:
						if ($versionRec = BackendUtility::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $nTable, $editId,'uid')) {
							$editId = $versionRec['uid'];
						}

						$this->editconf[$nTable][$editId] = 'edit';
						if ($nTable=='pages' && $this->retUrl!='dummy.php' && $this->returnNewPageId) {
							$this->retUrl .= '&id='.$tce->substNEWwithIDs[$nKey];
						}
					}
				}

				// See if any records was auto-created as new versions?
				if (count($tce->autoVersionIdMap)) {
					$this->fixWSversioningInEditConf($tce->autoVersionIdMap);
				}

				// If a document is saved and a new one is created right after.
				if (isset($_POST['_savedoknew_x']) && is_array($this->editconf)) {

					// Finding the current table:
					reset($this->editconf);
					$nTable = key($this->editconf);

					// Finding the first id, getting the records pid+uid
					reset($this->editconf[$nTable]);
					$nUid = key($this->editconf[$nTable]);
					$nRec = BackendUtility::getRecord($nTable,$nUid,'pid,uid');

					// Setting a blank editconf array for a new record:
					$this->editconf = array();
					if ($this->getNewIconMode($nTable)=='top') {
						$this->editconf[$nTable][$nRec['pid']] = 'new';
					} else {
						$this->editconf[$nTable][-$nRec['uid']] = 'new';
					}
				}

				$tce->printLogErrorMessages(
					isset($_POST['_saveandclosedok_x']) ?
						$this->retUrl :
						$this->R_URL_parts['path'].'?'.GeneralUtility::implodeArrayForUrl('',$this->R_URL_getvars)	// popView will not be invoked here, because the information from the submit button for save/view will be lost .... But does it matter if there is an error anyways?
				);
			}
		}
		if (isset($_POST['_saveandclosedok_x']) || $this->closeDoc<0) {	//  || count($tce->substNEWwithIDs)... If any new items has been save, the document is CLOSED because if not, we just get that element re-listed as new. And we don't want that!
			$this->closeDocument(abs($this->closeDoc));
		}

		if($fakeAdmin){
			TcBeuserUtility::removeFakeAdmin();
		}
	}

	/**
	 * close the document and send to the previous page
	 */
	function closeDocument() {
		if($this->retUrl == 'dummy.php') {
			$this->retUrl = BackendUtility::getModuleUrl($GLOBALS['MCONF']['name'], array('SET[function]' => 1));
		}
		$retUrl = explode('/', $this->retUrl);
		$this->retUrl = $retUrl[count($retUrl)-1];
		Header('Location: '.$this->retUrl);
		exit;
	}

	function init() {
		parent::init();

		TcBeuserUtility::switchUser(GeneralUtility::_GP('SwitchUser'));

		// Initializing document template object:
		/** @var $doc \TYPO3\CMS\Backend\Template\DocumentTemplate */
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:tc_beuser/Resources/Private/Templates/module.html');
		$this->doc->form = '<form action="'.htmlspecialchars($this->R_URI).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR_checkSubmit(1);">';
		$this->doc->loadJavascriptLib('js/jsfunc.updateform.js');
		$this->doc->getPageRenderer()->loadPrototype();
		// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();
		// Set up menus:
		$this->menuConfig();

		// JavaScript
		$this->doc->postCode .= $this->doc->wrapScriptTags('
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["web"] = 0;
		');

		$this->doc->postCode .= $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL) {
				document.location = URL;
			}
		');

		$this->id = 0;
		$this->search_field = GeneralUtility::_GP('search_field');
		$this->pointer = MathUtility::forceIntegerInRange(GeneralUtility::_GP('pointer'), 0, 100000);
		$this->table = 'be_users';

		// if going to edit a record, a menu item is dynamicaly added to
		// the dropdown which is otherwise not visible
		$SET = GeneralUtility::_GET('SET');
		if(isset($SET['function']) && $SET['function'] == 'edit') {
			$this->MOD_SETTINGS['function'] = $SET['function'];
			$this->MOD_MENU['function']['edit'] = $GLOBALS['LANG']->getLL('edit-user');
			$this->doc->form = '<form action="'.htmlspecialchars($this->R_URI).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR_checkSubmit(1);">';
			$this->editconf = GeneralUtility::_GET('edit');
		}

		//import fe user
		if($SET['function'] == 'import'){
			$this->MOD_SETTINGS['function'] = $SET['function'];
		}

		if($SET['function'] == 'action'){
			$this->MOD_SETTINGS['function'] = $SET['function'];
		}
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig() {
		$this->MOD_MENU = array (
			'function' => array (
				'1' => $GLOBALS['LANG']->getLL('list-users'),
				'2'	=> $GLOBALS['LANG']->getLL('create-user'),
				'3' => $GLOBALS['LANG']->getLL('create-user-wizard'),
			),
			'hideDeactivatedUsers' => '0'
		);
		parent::menuConfig();
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent() {
		$content = '';
		if(!empty($this->editconf)) {
			$this->MOD_SETTINGS['function'] = 'edit';
		}

		switch((string)$this->MOD_SETTINGS['function']) {
			case '1':
				// list users
				BackendUtility::lockRecords();
				$content .= $this->doc->section(
					'',
					$this->getUserList()
				);
//				$content .= $debug_content;
				break;
			case '2':
				$data = GeneralUtility::_GP('data');
				$dataKey = is_array($data) ? array_keys($data[$this->table]): array();
				if(is_numeric($dataKey[0])) {
					$this->editconf = array($this->table => array($dataKey[0] => 'edit'));
				} else {
					// create new user
					$this->editconf = array($this->table => array(0 => 'new'));
				}
				$content .= $this->doc->section(
					'',
					$this->getUserEdit()
				);
				break;
			case '3':

				//show list of fe users
				$this->table = 'fe_users';
				$content .= $this->doc->section(
					'',
					$this->getUserList()
				);
//				$content .= $debug_content;
				break;
			case 'edit':
				// edit user
				$content .= $this->doc->section(
					'',
					$this->getUserEdit()
				);
//				$content .= $debug_content;
				break;
			case 'import':
				$this->feID = GeneralUtility::_GP('feID');
				$this->R_URI = $this->retUrl = BackendUtility::getModuleUrl($GLOBALS['MCONF']['name']);
				$data = GeneralUtility::_GP('data');
				$dataKey = is_array($data) ? array_keys($data[$this->table]): array();
				if(is_numeric($dataKey[0])){
					$this->editconf = array($this->table => array($dataKey[0] => 'edit'));
				}else{ // create new user
					$this->editconf = array($this->table => array(0=>'new'));
				}
				$content .= $this->doc->section(
					'',
					$this->getUserEdit()
				);
//				$content .= $debug_content;
				break;
			case 'action':
				$this->processData();
				Header('Location: '.GeneralUtility::locationHeaderUrl(GeneralUtility::_GP('redirect')));
				break;
		}

		return $content;
	}

	function printContent() {
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	function getUserList() {
		$content = '';
		/** @var dkd\TcBeuser\Utility\RecordListUtility $dblist */
		$dblist = GeneralUtility::makeInstance('dkd\\TcBeuser\\Utility\\RecordListUtility');
		$dblist->permChecker = &$this->permChecker;
		$dblist->backPath = $this->doc->backPath;
		$dblist->script = GeneralUtility::getIndpEnv('SCRIPT_NAME');
		$dblist->alternateBgColors = true;
		$dblist->userMainGroupOnly = true;

		$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
		$dblist->showFields = array('realName', 'username', 'usergroup');
		$dblist->disableControls = array_merge( $dblist->disableControls, array('import'=>true) );

//Setup for analyze Icon
		$dblist->analyzeLabel = $GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:analyze',1);
		$dblist->analyzeParam = 'beUser';

		if($this->MOD_SETTINGS['hideDeactivatedUsers']) {
			$dblist->hideDisabledRecords = true;
		}

		//dkd-kartolo
		//prepare to list fe_users
		if($this->table != 'fe_users') {
			$pid = 0;
			$sortField = 'realName';
		} else {
			$pid = intval($this->extConf['pidFE']);
			$sortField = 'username';
			$dblist->showFields = array('username','name','email');
			$dblist->disableControls = array (
				'history' => true,
				'new' => true,
				'edit' => true,
				'detail' => true,
				'delete' => true,
				'hide' => true
			);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'username',
				'be_users',
				'1 '.BackendUtility::deleteClause('be_users')
			);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$exclude[] = "'".$row['username']."'";
			}
			$dblist->excludeBE = '('.implode(',',$exclude).')';
		}

		$dblist->start($pid, $this->table, $this->pointer, $this->search_field);

		// default sorting, needs to be set after $dblist->start()
		$sort = GeneralUtility::_GET('sortField');
		if(is_null($sort)) {
			$dblist->sortField = $sortField;
		}
		$dblist->generateList();
		$content .= $dblist->HTMLcode ? $dblist->HTMLcode : $GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:not-found').'<br />';
		$content .= '<br />'.BackendUtility::getFuncCheck(
				$this->id,
				'SET[hideDeactivatedUsers]',
				$this->MOD_SETTINGS['hideDeactivatedUsers'],
				'',
				'&search_field='.$this->search_field.'&sortField='.$sort.'&sortRev='.GeneralUtility::_GET('sortRev')
			).' '.$GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:hide-deaktivated-users');
		$content .= $dblist->getSearchBox(
			false,
			$GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:search-user',1)
		);
		// make new user link
		$content .= ($this->table != 'fe_users') ?
			'<!--
				Link for creating a new record:
			-->
<div id="typo3-newRecordLink">
<a href="' . BackendUtility::getModuleUrl($GLOBALS['MCONF']['name'], array('SET[function]' => 2)) . '">' .
			'<img' . IconUtility::skinImg($this->doc->backPath, 'gfx/new_el.gif', 'width="11" height="12"') . ' alt="' . $GLOBALS['LANG']->getLL('create-user') . '" />' .
			$GLOBALS['LANG']->getLL('create-user') .
			'</a>' : '';

		$this->jsCode .= $this->doc->redirectUrls($dblist->listURL())."\n";

		return $content;
	}

	function getUserEdit() {
		$content = '';

		// the default field to show
		$showColumn = 'disable,username,password,usergroup,realName,email,lang';

		// get hideColumnGroup from TS and remove it from the showColumn
		if ($GLOBALS['BE_USER']->userTS['tc_beuser.']['hideColumnGroup']) {
			$removeColumnArray = explode(',', $GLOBALS['BE_USER']->userTS['tc_beuser.']['hideColumnUser']);
			$defaultColumnArray = explode(',', $showColumn);

			foreach ($removeColumnArray as $col) {
				$defaultColumnArray = GeneralUtility::removeArrayEntryByValue($defaultColumnArray, $col);
			}

			$showColumn = implode(',', $defaultColumnArray);
		}

		/** @var \TYPO3\CMS\Backend\Form\FormEngine tceforms */
		$this->tceforms = GeneralUtility::makeInstance('\\TYPO3\\CMS\\Backend\\Form\\FormEngine');
		$this->tceforms->backPath = $this->doc->backPath;
		$this->tceforms->initDefaultBEMode();
		$this->tceforms->doSaveFieldName = 'doSave';
		$this->tceforms->localizationMode = GeneralUtility::inList('text,media',$this->localizationMode) ? $this->localizationMode : '';	// text,media is keywords defined in TYPO3 Core API..., see "l10n_cat"
		$this->tceforms->returnUrl = $this->R_URI;
		$this->tceforms->disableRTE = true; // not needed anyway, might speed things up

		// Setting external variables:
		#if ($GLOBALS['BE_USER']->uc['edit_showFieldHelp']!='text')	$this->tceforms->edit_showFieldHelp='text';

		// Creating the editing form, wrap it with buttons, document selector etc.
		//show only these columns

		$this->editForm = GeneralUtility::makeInstance('dkd\\TcBeuser\\Utility\\EditFormUtility');
		$this->editForm->tceforms = &$this->tceforms;
		$this->editForm->columnsOnly = $showColumn;
		$this->editForm->editconf = $this->editconf;
		$this->editForm->feID = $this->feID;
		$this->editForm->error = $this->error;
		$this->editForm->inputData = $this->data;

		$editForm = $this->editForm->makeEditForm();
		$this->viewId = $this->editForm->viewId;

		if ($editForm) {
			// ingo.renner@dkd.de
			reset($this->editForm->elementsData);
			$this->firstEl = current($this->editForm->elementsData);

			if ($this->viewId) {
				// Module configuration:
				$this->modTSconfig = BackendUtility::getModTSconfig($this->viewId,'mod.xMOD_alt_doc');
			} else $this->modTSconfig=array();

			$panel  = $this->makeButtonPanel();
			$formContent = $this->compileForm($panel,'','',$editForm);

			$content .= $this->tceforms->printNeededJSFunctions_top().
				$formContent.
				$this->tceforms->printNeededJSFunctions();
		}

		return $content;
	}

	/**
	 * ingo.renner@dkd.de: from alt_doc.php, modified
	 *
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	string		HTML code, comprised of images linked to various actions.
	 */
	function makeButtonPanel() {
		$panel='';

		// Render SAVE type buttons:
		// The action of each button is decided by its name attribute. (See doProcessData())
		if (!$this->errorC && !$GLOBALS['TCA'][$this->firstEl['table']]['ctrl']['readOnly']) {

			// SAVE button:
			$panel.= '<input type="image" class="c-inputButton" name="_savedok"'.IconUtility::skinImg($this->doc->backPath,'gfx/savedok.gif','').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc',1).'" />';

			// SAVE / CLOSE
			$panel.= '<input type="image" class="c-inputButton" name="_saveandclosedok"'.IconUtility::skinImg($this->doc->backPath,'gfx/saveandclosedok.gif','').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc',1).'" />';
		}

		// CLOSE button:
		$panel.= '<a href="#" onclick="document.editform.closeDoc.value=1; document.editform.submit(); return false;">'.
			'<img'.IconUtility::skinImg($this->doc->backPath,'gfx/closedok.gif','width="21" height="16"').' class="c-inputButton" title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc',1).'" alt="" />'.
			'</a>';

		// UNDO buttons:
		if (!$this->errorC && !$GLOBALS['TCA'][$this->firstEl['table']]['ctrl']['readOnly'] && count($this->elementsData)==1) {
			if ($this->firstEl['cmd']!='new' && GeneralUtility::testInt($this->firstEl['uid'])) {

				// Undo:
				$undoButton = 0;
				$undoRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_history', 'tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->firstEl['table'], 'sys_history').' AND recuid='.intval($this->firstEl['uid']), '', 'tstamp DESC', '1');
				if ($undoButtonR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($undoRes)) {
					$undoButton = 1;
				}
				if ($undoButton) {
					$aOnClick = 'window.location.href=\'show_rechis.php?element='.rawurlencode($this->firstEl['table'].':'.$this->firstEl['uid']).'&revert=ALL_FIELDS&sumUp=-1&returnUrl='.rawurlencode($this->R_URI).'\'; return false;';
					$panel .= '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
						'<img'.IconUtility::skinImg($this->doc->backPath,'gfx/undo.gif','width="21" height="16"').' class="c-inputButton" title="'.htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('undoLastChange'),BackendUtility::calcAge(time()-$undoButtonR['tstamp'],$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')))).'" alt="" />'.
						'</a>';
				}

				// If only SOME fields are shown in the form, this will link the user to the FULL form:
				if ($this->columnsOnly) {
					$panel .= '<a href="'.htmlspecialchars($this->R_URI.'&columnsOnly=').'">'.
						'<img'.IconUtility::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' class="c-inputButton" title="'.$GLOBALS['LANG']->getLL('editWholeRecord',1).'" alt="" />'.
						'</a>';
				}
			}
		}
		return $panel;
	}

	/**
	 * Put together the various elements (buttons, selectors, form) into a table
	 *
	 * @param	string		The button panel HTML
	 * @param	string		Document selector HTML
	 * @param	string		Clear-cache menu HTML
	 * @param	string		HTML form.
	 * @param	string		Language selector HTML for localization
	 * @return	string		Composite HTML
	 */
	function compileForm($panel,$docSel,$cMenu,$editForm, $langSelector='') {
		$formContent='';
		$formContent.='

			<!--
			 	Header of the editing page.
				Contains the buttons for saving/closing, the document selector and menu selector.
				Shows the path of the editing operation as well.
			-->
			<table border="0" cellpadding="0" cellspacing="1" width="470" id="typo3-altdoc-header">
				<tr>
					<td nowrap="nowrap" valign="top">'.$panel.'</td>
					<td nowrap="nowrap" valign="top" align="right">'.$docSel.$cMenu.'</td>
				</tr>';

		if ($langSelector) {
			$langSelector ='<div id="typo3-altdoc-lang-selector">'.$langSelector.'</div>';
		}
		$pagePath = '<div id="typo3-altdoc-page-path">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.path',1).': '.htmlspecialchars($this->generalPathOfForm).'</div>';

		$formContent.='
				<tr>
					<td colspan="2"><div id="typo3-altdoc-header-info-options">'.$pagePath.$langSelector.'<div></td>
				</tr>
			</table>

			<!--
			 	EDITING FORM:
			-->

			'.$editForm.'

			<!--
			 	Saving buttons (same as in top)
			-->

			'.$panel.
			'<input type="hidden" name="returnUrl" value="'.htmlspecialchars($this->retUrl).'" />
			<input type="hidden" name="viewUrl" value="'.htmlspecialchars($this->viewUrl).'" />';

		if ($this->returnNewPageId) {
			$formContent .= '<input type="hidden" name="returnNewPageId" value="1" />';
		}
		$formContent .= '<input type="hidden" name="popViewId" value="'.htmlspecialchars($this->viewId).'" />';
		if ($this->viewId_addParams) {
			$formContent .= '<input type="hidden" name="popViewId_addParams" value="'.htmlspecialchars($this->viewId_addParams).'" />';
		}
		$formContent .= '<input type="hidden" name="closeDoc" value="0" />';
		$formContent .= '<input type="hidden" name="doSave" value="0" />';
		$formContent .= '<input type="hidden" name="_serialNumber" value="'.md5(microtime()).'" />';
		$formContent .= '<input type="hidden" name="_disableRTE" value="'.$this->tceforms->disableRTE.'" />';

		return $formContent;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return 	array		all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'view' => '',
			'shortcut' => ''
		);
		// CSH
		$buttons['csh'] = BackendUtility::cshItem('_MOD_web_info', '', $GLOBALS['BACK_PATH'], '', TRUE);
		// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}
		return $buttons;
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tc_beuser/mod2/index.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tc_beuser/mod2/index.php']);
}
