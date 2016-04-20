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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Module 'User Admin' for the 'tc_beuser' extension.
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 * @author Ivan Kartolo <ivan.kartolo@dkd.de>
 * @package TYPO3
 * @subpackage tx_tcbeuser
 */
class UserAdminController extends BaseScriptClass
{

    /**
     * Name of the module
     *
     * @var string
     */
    protected $moduleName = 'tcTools_UserAdmin';

    public $jsCode;
    public $pageinfo;

    /** @var $tceforms \TYPO3\CMS\Backend\Form\FormEngine */
    public $tceforms;

    /**
     * @var object tx_tcbeuser_config $permChecker helps checking BE user permissions
     */
    public $permChecker;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    public $moduleTemplate;

    /**
     * iconFactory to get TYPO3 icon
     *
     * @var iconFactory
     */
    protected $iconFactory;

    /**
     * @var \dkd\TcBeuser\Utility\EditFormUtility
     */
    protected $editForm;

    /**
     * Return URL script, processed. This contains the script (if any) that we should
     * RETURN TO from the FormEngine script IF we press the close button. Thus this
     * variable is normally passed along from the calling script so we can properly return if needed.
     *
     * @var string
     */
    public $retUrl;

    /**
     * Contains the parts of the REQUEST_URI (current url). By parts we mean the result of resolving
     * REQUEST_URI (current url) by the parse_url() function. The result is an array where eg. "path"
     * is the script path and "query" is the parameters...
     *
     * @var array
     */
    public $R_URL_parts;

    /**
     * Contains the current GET vars array; More specifically this array is the foundation for creating
     * the R_URI internal var (which becomes the "url of this script" to which we submit the forms etc.)
     *
     * @var array
     */
    public $R_URL_getvars;

    /**
     * Set to the URL of this script including variables which is needed to re-display the form. See main()
     *
     * @var string
     */
    public $R_URI;

    /**
     * working only with be_users table
     *
     * @var string
     */
    public $table = 'be_users';

    /**
     * Data value from GP
     *
     * @var string
     */
    public $data;

    /**
     * Command from GP
     *
     * @var string
     */
    public $cmd;

    /**
     * Disable RTE from GP
     *
     * @var string
     */
    public $disableRT;

    /**
     * Constructor
     */
    public function __construct()
    {
        $GLOBALS['MCONF'] = $this->MCONF = array(
            'name' => $this->moduleName
        );

        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);

        $this->moduleTemplate->getPageRenderer()->loadJquery();
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/FieldSelectBox');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');
        $this->moduleTemplate->addJavaScriptCode(
            'jumpToUrl',
            '
                function jumpToUrl(URL) {
                    window.location.href = URL;
                    return false;
                }
                '
        );
    }

    /**
     * Entrance from the backend module. This replace the _dispatch
     *
     * @param ServerRequestInterface $request The request object from the backend
     * @param ResponseInterface $response The reponse object sent to the backend
     *
     * @return ResponseInterface Return the response object
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->getLanguageService()->includeLLFile('EXT:tc_beuser/Resources/Private/Language/locallangUserAdmin.xml');
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_alt_doc.xml');


        $this->preInit();
        if ($this->doProcessData()) {
            // Checks, if a save button has been clicked (or the doSave variable is sent)
            $this->processData();
        }

        $this->main();
        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * the main call
     */
    public function main()
    {
        $this->init();

        //TODO more access check!?
        $access = $this->getBackendUser()->modAccess($this->MCONF, true);

        if ($access || $this->getBackendUser()->isAdmin()) {
            // We need some uid in rootLine for the access check, so use first webmount
            $webmounts = $this->getBackendUser()->returnWebmounts();
            $this->pageinfo['uid'] = $webmounts[0];
            $this->pageinfo['_thePath'] = '/';

            $title = $this->getLanguageService()->getLL('title');
            $this->moduleTemplate->setTitle($title);

            $this->content = $this->moduleTemplate->header($title);
            $this->content .= $this->moduleContent();

            $this->generateMenu();
        }

        $this->getBackendUser()->user['admin'] = 0;
    }

    /**
     * First initialization.
     *
     * @return void
     */
    public function preInit()
    {
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
        $this->R_URL_getvars['edit'] = $this->editconf ?: array($this->table => array('new'));

        // Set other internal variables:
        $this->R_URL_getvars['returnUrl'] = $this->retUrl;
        $this->R_URI = $this->R_URL_parts['path'] . '?' . ltrim(GeneralUtility::implodeArrayForUrl(
            '',
            $this->R_URL_getvars
        ), '&');



        if ($this->closeDoc > 0) {
            $this->closeDocument();
        }
    }

    /**
     * Detects, if a save command has been triggered.
     *
     * @return boolean True, then save the document (data submitted)
     */
    public function doProcessData()
    {
        $out = $this->doSave ||
            isset($_POST['_savedok']) ||
            isset($_POST['_saveandclosedok']);
        return $out;
    }

    /**
     * Do processing of data, submitting it to TCEmain.
     *
     * @return void
     */
    public function processData()
    {
        if ($this->getBackendUser()->user['admin'] != 1) {
            //make fake Admin
            TcBeuserUtility::fakeAdmin();
            $fakeAdmin = 1;
        }
        // GPvars specifically for processing:
        $this->data = GeneralUtility::_GP('data');
        $this->cmd = GeneralUtility::_GP('cmd') ? GeneralUtility::_GP('cmd') : array();
        $this->disableRTE = GeneralUtility::_GP('_disableRTE');

        //check data with fe user
        if (is_array($this->data)) {
            $table = array_keys($this->data);
            $uid = array_keys($this->data[$table[0]]);
            $data = $this->data[$table[0]][$uid[0]];
            $fePID = intval($this->extConf['pidFE']);
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                '*',
                'fe_users',
                'pid = ' . $fePID .
                BackendUtility::deleteClause('fe_users') .
                ' AND username = ' .
                $this->getDatabaseConnection()->fullQuoteStr($data['username'], 'fe_users')
            );

            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                if ((trim($data['realName']) == trim($row['name'])) && (trim($data['email']) == trim($row['email']))) {
                    $notSync = 0;
                } else {
                    if (strpos($uid[0], 'new') !== false) {
                        $feExist = 1;
                    } else {
                        $notSync = 1;
                    }
                }
            }
        }

        if ($notSync || $feExist) {
            $notSync ? $this->error[] = array('error',$this->getLanguageService()->getLL('data-sync')) : '';
            $feExist ? $this->error[] = array('error',$this->getLanguageService()->getLL('fe-exist')) : '';
        } else {
            // See tce_db.php for relevate options here:
            // Only options related to $this->data submission are included here.
            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
            $tce = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
            $tce->stripslashes_values = 0;

            // Setting default values specific for the user:
            $TCAdefaultOverride = $this->getBackendUser()->getTSConfigProp('TCAdefaults');
            if (is_array($TCAdefaultOverride)) {
                $tce->setDefaultsFromUserTS($TCAdefaultOverride);
            }

            // Setting internal vars:
            if ($this->getBackendUser()->uc['neverHideAtCopy']) {
                $tce->neverHideAtCopy = 1;
            }
            $tce->debug = 0;
            $tce->disableRTE = $this->disableRTE;

            // Loading TCEmain with data:
            $tce->start($this->data, $this->cmd);
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
            if ($httpHost!=$refInfo['host'] &&
                $this->vC!=$this->getBackendUser()->veriCode() &&
                !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']) {
                $tce->log(
                    '',
                    0,
                    0,
                    0,
                    1,
                    "Referer host '%s' and server host '%s' did not match and veriCode was not valid either!",
                    1,
                    array($refInfo['host'], $httpHost)
                );
            } else {
                // Perform the saving operation with TCEmain:
                $tce->process_uploads($_FILES);
                $tce->process_datamap();
                $tce->process_cmdmap();

                // If there was saved any new items, load them:
                if (count($tce->substNEWwithIDs_table)) {
                    // Resetting editconf:
                    $this->editconf = array();

                    // Traverse all new records and forge the content of ->editconf
                    // so we can continue to EDIT these records!
                    foreach ($tce->substNEWwithIDs_table as $nKey => $nTable) {
                        $editId = $tce->substNEWwithIDs[$nKey];
                        // translate new id to the workspace version:
                        if ($versionRec = BackendUtility::getWorkspaceVersionOfRecord($this->getBackendUser()->workspace, $nTable, $editId, 'uid')) {
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
                    $nRec = BackendUtility::getRecord($nTable, $nUid, 'pid,uid');

                    // Setting a blank editconf array for a new record:
                    $this->editconf = array();
                    if ($this->getNewIconMode($nTable)=='top') {
                        $this->editconf[$nTable][$nRec['pid']] = 'new';
                    } else {
                        $this->editconf[$nTable][-$nRec['uid']] = 'new';
                    }
                }

                $tce->printLogErrorMessages(
                    isset($_POST['_saveandclosedok']) ?
                        $this->retUrl :
                        $this->R_URL_parts['path'].'?'.GeneralUtility::implodeArrayForUrl('', $this->R_URL_getvars)    // popView will not be invoked here, because the information from the submit button for save/view will be lost .... But does it matter if there is an error anyways?
                );
            }
        }
        if (isset($_POST['_saveandclosedok']) || $this->closeDoc<0) {
            //If any new items has been save, the document is CLOSED because if not, we just get that element re-listed as new. And we don't want that!
            $this->closeDocument(abs($this->closeDoc));
        }

        if ($fakeAdmin) {
            TcBeuserUtility::removeFakeAdmin();
        }
    }

    /**
     * close the document and send to the previous page
     */
    public function closeDocument()
    {
        HttpUtility::redirect($this->retUrl);
    }

    public function init()
    {
        parent::init();

        TcBeuserUtility::switchUser(GeneralUtility::_GP('SwitchUser'));
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        // Set up menus:
        $this->menuConfig();

        $this->id = 0;
        $this->search_field = GeneralUtility::_GP('search_field');
        $this->pointer = MathUtility::forceIntegerInRange(GeneralUtility::_GP('pointer'), 0, 100000);
        $this->table = 'be_users';

        // if going to edit a record, a menu item is dynamicaly added to
        // the dropdown which is otherwise not visible
        $SET = GeneralUtility::_GET('SET');
        if (isset($SET['function']) && $SET['function'] == 'edit') {
            $this->MOD_SETTINGS['function'] = $SET['function'];
            $this->MOD_MENU['function']['edit'] = $this->getLanguageService()->getLL('edit-user');
            $this->doc->form = '<form action="'.htmlspecialchars($this->R_URI).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR_checkSubmit(1);">';
            $this->editconf = GeneralUtility::_GET('edit');
        }

        //import fe user
        if ($SET['function'] == 'import') {
            $this->MOD_SETTINGS['function'] = $SET['function'];
        }

        if ($SET['function'] == 'action') {
            $this->MOD_SETTINGS['function'] = $SET['function'];
        }
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return void
     */
    public function menuConfig()
    {
        $this->MOD_MENU = array(
            'function' => array(
                '1' => $this->getLanguageService()->getLL('list-users'),
                '2'    => $this->getLanguageService()->getLL('create-user'),
                '3' => $this->getLanguageService()->getLL('create-user-wizard'),
            ),
            'hideDeactivatedUsers' => '0'
        );
        parent::menuConfig();
    }

    /**
     * Generates the module content
     *
     * @return string
     */
    public function moduleContent()
    {
        $content = '';
        if (!empty($this->editconf)) {
            $this->MOD_SETTINGS['function'] = 'edit';
        }

        switch ((string)$this->MOD_SETTINGS['function']) {
            case '1':
                // list users
                BackendUtility::lockRecords();

                // get buttons for the header
                $this->getButtons();

                // the content
                $content .=  $this->getUserList();
                break;

            case '2':
                $data = GeneralUtility::_GP('data');
                $dataKey = is_array($data) ? array_keys($data[$this->table]): array();
                if (is_numeric($dataKey[0])) {
                    $this->editconf = array($this->table => array($dataKey[0] => 'edit'));
                } else {
                    // create new user
                    $this->editconf = array($this->table => array(0 => 'new'));
                }

                $content .= $this->getUserEdit();

                // get Save, close, etc button
                $this->getSaveButton();
                break;

            case '3':
                //show list of fe users
                $this->table = 'fe_users';

                // get buttons for the header
                $this->getButtons();

                // the content
                $content .= $this->getUserList();
                break;

            case 'edit':
                // edit user
                $content .= $this->getUserEdit();

                // get Save, close, etc button
                $this->getSaveButton();
                break;

            case 'import':
                $this->feID = GeneralUtility::_GP('feID');
                $this->R_URI = $this->retUrl = BackendUtility::getModuleUrl($GLOBALS['MCONF']['name']);
                $data = GeneralUtility::_GP('data');
                $dataKey = is_array($data) ? array_keys($data[$this->table]): array();
                if (is_numeric($dataKey[0])) {
                    $this->editconf = array($this->table => array($dataKey[0] => 'edit'));
                } else { // create new user
                    $this->editconf = array($this->table => array(0 =>'new'));
                }
                $content .= $this->getUserEdit();

                // get Save, close, etc button
                $this->getSaveButton();
                break;

            case 'action':
                $this->processData();
                HttpUtility::redirect(GeneralUtility::_GP('redirect'));
                break;
        }

        return $content;
    }

    /**
     * Get user list in a table
     *
     * @return string
     */
    public function getUserList()
    {
        $content = '';
        /** @var \dkd\TcBeuser\Utility\RecordListUtility $dblist */
        $dblist = GeneralUtility::makeInstance('dkd\\TcBeuser\\Utility\\RecordListUtility');
        $dblist->permChecker = &$this->permChecker;
        $dblist->backPath = $this->doc->backPath;
        $dblist->script = BackendUtility::getModuleUrl($this->moduleName);
        $dblist->alternateBgColors = true;
        $dblist->userMainGroupOnly = true;

        $dblist->calcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);
        $dblist->showFields = array('realName', 'username', 'usergroup');
        $dblist->disableControls = array_merge($dblist->disableControls, array('import'=>true));

        //Setup for analyze Icon
        $dblist->analyzeLabel = $this->getLanguageService()->getLL('analyze', 1);
        $dblist->analyzeParam = 'beUser';

        if ($this->MOD_SETTINGS['hideDeactivatedUsers']) {
            $dblist->hideDisabledRecords = true;
        }

        //dkd-kartolo
        //prepare to list fe_users
        if ($this->table != 'fe_users') {
            $pid = 0;
            $sortField = 'realName';
        } else {
            $pid = intval($this->extConf['pidFE']);
            $sortField = 'username';
            $dblist->showFields = array('username','name','email');
            $dblist->disableControls = array(
                'history' => true,
                'new' => true,
                'edit' => true,
                'detail' => true,
                'delete' => true,
                'hide' => true
            );
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'username',
                'be_users',
                '1 '.BackendUtility::deleteClause('be_users')
            );
            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                $exclude[] = "'".$row['username']."'";
            }
            $dblist->excludeBE = '('.implode(',', $exclude).')';
        }

        $dblist->start($pid, $this->table, $this->pointer, $this->search_field);

        // default sorting, needs to be set after $dblist->start()
        $sort = GeneralUtility::_GET('sortField') ? GeneralUtility::_GET('sortField') : $sortField;
        if (is_null($sort)) {
            $dblist->sortField = $sortField;
        }
        $dblist->generateList();
        $content .= $dblist->HTMLcode ? $dblist->HTMLcode : $this->getLanguageService()->getLL('not-found').'<br />';
        $content .= '<br />' .
            '<div class="checkbox">' .
            '<label for="SET[hideDeactivatedUsers]">' .
            BackendUtility::getFuncCheck(
                $this->id,
                'SET[hideDeactivatedUsers]',
                $this->MOD_SETTINGS['hideDeactivatedUsers'],
                '',
                '&search_field='.$this->search_field.'&sortField='.$sort.'&sortRev='.GeneralUtility::_GET('sortRev')
            ) .
            $this->getLanguageService()->getLL('hide-deaktivated-users') .
            '</label>' .
            '</div>';

        // Add JavaScript functions to the page:

        $this->moduleTemplate->addJavaScriptCode(
            'RecordListInlineJS',
            '
				function jumpExt(URL,anchor) {	//
					var anc = anchor?anchor:"";
					window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL) {	//
					window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}
				function jumpToUrl(URL) {
					window.location.href = URL;
					return false;
				}

				function setHighlight(id) {	//
					top.fsMod.recentIds["tcTools"]=id;
					top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

					if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
						top.content.nav_frame.refresh_nav();
					}
				}
				' . $this->moduleTemplate->redirectUrls($dblist->listURL()) . '
				' . $dblist->CBfunctions() . '
				function editRecords(table,idList,addParams,CBflag) {	//
					window.location.href="' . BackendUtility::getModuleUrl('record_edit', array('returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'))) . '&edit["+table+"]["+idList+"]=edit"+addParams;
				}
				function editList(table,idList) {	//
					var list="";

						// Checking how many is checked, how many is not
					var pointer=0;
					var pos = idList.indexOf(",");
					while (pos!=-1) {
						if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
							list+=idList.substr(pointer,pos-pointer)+",";
						}
						pointer=pos+1;
						pos = idList.indexOf(",",pointer);
					}
					if (cbValue(table+"|"+idList.substr(pointer))) {
						list+=idList.substr(pointer)+",";
					}

					return list ? list : idList;
				}

				if (top.fsMod) top.fsMod.recentIds["tcTools"] = ' . (int)$this->id . ';
			'
        );


        // searchbox toolbar
        if (!$this->modTSconfig['properties']['disableSearchBox'] && ($dblist->HTMLcode || !empty($dblist->searchString))) {
            $searchBox = $dblist->getSearchBox();
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ToggleSearchToolbox');

            $searchButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton();
            $searchButton
                ->setHref('#')
                ->setClasses('t3js-toggle-search-toolbox')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.searchIcon'))
                ->setIcon($this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL));
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                $searchButton,
                ButtonBar::BUTTON_POSITION_LEFT,
                90
            );
        }

        // make new user link
        $content .= ($this->table != 'fe_users') ?
            '<!--
				Link for creating a new record:
			-->
<div id="typo3-newRecordLink">
<a href="' . BackendUtility::getModuleUrl($this->moduleName, array('SET[function]' => 2)) . '">' .
            $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() .
            ' ' .
            $this->getLanguageService()->getLL('create-user') .
            '</a>' : '';

        $content = '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">' .
            $content .
            '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';

        return $searchBox . $content;
    }

    /**
     * Show edit form
     *
     * @return string
     */
    public function getUserEdit()
    {
        $content = '';

        // the default field to show
        $showColumn = 'disable,username,password,usergroup,realName,email,lang';

        // get hideColumnGroup from TS and remove it from the showColumn
        if ($this->getBackendUser()->userTS['tc_beuser.']['hideColumnGroup']) {
            $removeColumnArray = explode(',', $this->getBackendUser()->userTS['tc_beuser.']['hideColumnUser']);
            $defaultColumnArray = explode(',', $showColumn);

            foreach ($removeColumnArray as $col) {
                $defaultColumnArray = ArrayUtility::removeArrayEntryByValue($defaultColumnArray, $col);
            }

            $showColumn = implode(',', $defaultColumnArray);
        }

        // Setting external variables:
        #if ($this->getBackendUser()->uc['edit_showFieldHelp']!='text')	$this->tceforms->edit_showFieldHelp='text';

        // Creating the editing form, wrap it with buttons, document selector etc.
        //show only these columns

        /** @var FormResultCompiler formResultCompiler */
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);

        /** @var \dkd\TcBeuser\Utility\EditFormUtility editForm */
        $this->editForm = GeneralUtility::makeInstance('dkd\\TcBeuser\\Utility\\EditFormUtility');
        $this->editForm->formResultCompiler = $formResultCompiler;
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
                $this->modTSconfig = BackendUtility::getModTSconfig($this->viewId, 'mod.xMOD_alt_doc');
            } else {
                $this->modTSconfig=array();
            }

            $content = $formResultCompiler->JStop();
            $content .= $this->compileForm($editForm);
            $content .= $formResultCompiler->printNeededJSFunctions();
            $content .= '</form>';
        }

        return $content;
    }

    /**
     * Put together the various elements (buttons, selectors, form) into a table
     *
     * @param string $editForm HTML form.
     * @return string Composite HTML
     */
    public function compileForm($editForm)
    {
        $formContent = '
			<!-- EDITING FORM -->
			<form
            action="' . htmlspecialchars($this->R_URI) . '"
            method="post"
            enctype="multipart/form-data"
            name="editform"
            id="EditDocumentController"
            onsubmit="TBE_EDITOR.checkAndDoSubmit(1); return false;">
			' . $editForm . '

			<input type="hidden" name="returnUrl" value="' . htmlspecialchars($this->retUrl) . '" />
			<input type="hidden" name="viewUrl" value="' . htmlspecialchars($this->viewUrl) . '" />';
        if ($this->returnNewPageId) {
            $formContent .= '<input type="hidden" name="returnNewPageId" value="1" />';
        }
        $formContent .= '<input type="hidden" name="popViewId" value="' . htmlspecialchars($this->viewId) . '" />';
        if ($this->viewId_addParams) {
            $formContent .= '<input type="hidden" name="popViewId_addParams" value="' . htmlspecialchars($this->viewId_addParams) . '" />';
        }
        $formContent .= '
			<input type="hidden" name="closeDoc" value="0" />
			<input type="hidden" name="doSave" value="0" />
			<input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />
			<input type="hidden" name="_scrollPosition" value="" />';
        return $formContent;
    }


    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return array All available buttons as an assoc. array
     */
    protected function getSaveButton()
    {
        $lang = $this->getLanguageService();
        // Render SAVE type buttons:
        // The action of each button is decided by its name attribute. (See doProcessData())
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if (!$this->errorC && !$GLOBALS['TCA'][$this->firstEl['table']]['ctrl']['readOnly']) {
            $saveSplitButton = $buttonBar->makeSplitButton();
            // SAVE button:
            $saveButton = $buttonBar->makeInputButton()
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'))
                ->setName('_savedok')
                ->setValue('1')
                ->setForm('EditDocumentController')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL));
            $saveSplitButton->addItem($saveButton, true);

            // SAVE / CLOSE
            $saveAndCloseButton = $buttonBar->makeInputButton()
                ->setName('_saveandclosedok')
                ->setClasses('t3js-editform-submitButton')
                ->setValue('1')
                ->setForm('EditDocumentController')
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-document-save-close',
                    Icon::SIZE_SMALL
                ));
            $saveSplitButton->addItem($saveAndCloseButton);
            $buttonBar->addButton($saveSplitButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        }
        // CLOSE button:
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setClasses('t3js-editform-close')
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                'actions-document-close',
                Icon::SIZE_SMALL
            ));
        $buttonBar->addButton($closeButton);

        $cshButton = $buttonBar->makeHelpButton()->setModuleName('xMOD_csh_corebe')->setFieldName('TCEforms');
        $buttonBar->addButton($cshButton);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // Shortcut
        $shortCutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($this->moduleName)
            ->setDisplayName($this->MOD_MENU['function'][$this->MOD_SETTINGS['function']])
            ->setGetVariables([
                'M',
                'id',
                'edit_record',
                'pointer',
                'new_unique_uid',
                'search_field',
                'search_levels',
                'showLimit'
            ])
            ->setSetVariables(array_keys($this->MOD_MENU));
        $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Generate the ModuleMenu
     */
    protected function generateMenu()
    {
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('UserAdminMenu');
        foreach ($this->MOD_MENU['function'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    BackendUtility::getModuleUrl(
                        $this->moduleName,
                        [
                            'id' => $this->id,
                            'SET' => [
                                'function' => $controller
                            ]
                        ]
                    )
                )
                ->setTitle($title);
            if ($controller == $this->MOD_SETTINGS['function']) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }
}
