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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Module 'User / Group Overview' for the 'tc_beuser' extension.
 *
 * @author	Ingo Renner <ingo.renner@dkd.de>
 * @package	TYPO3
 * @subpackage	tx_tcbeuser
 */
class OverviewController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{

    public $content;
    public $doc;
    public $jsCode;
    public $MOD_MENU     = array();
    public $MOD_SETTINGS = array();
    public $pageinfo;
    public $compareFlags;
    public $be_user;
    public $be_group;
    public $table;

    public function main()
    {
        $this->init();

        //TODO more access check!?
        $access = $GLOBALS['BE_USER']->modAccess($this->MCONF, true);

        if ($access || $GLOBALS['BE_USER']->isAdmin()) {
            // We need some uid in rootLine for the access check, so use first webmount
            $webmounts = $GLOBALS['BE_USER']->returnWebmounts();
            $this->pageinfo['uid'] = $webmounts[0];
            $this->pageinfo['_thePath'] = '/';

            if (GeneralUtility::_GP('beUser')) {
                $this->MOD_SETTINGS['function'] = 2;
            }

            if (GeneralUtility::_GP('beGroup')) {
                $this->MOD_SETTINGS['function'] = 1;
            }

            if ($this->MOD_SETTINGS['function'] == 1) {
                $title = $GLOBALS['LANG']->getLL('overview-groups');
            } elseif ($this->MOD_SETTINGS['function'] == 2) {
                $title = $GLOBALS['LANG']->getLL('overview-users');
            }

            $menu  = BackendUtility::getFuncMenu(
                $this->id,
                'SET[function]',
                $this->MOD_SETTINGS['function'],
                $this->MOD_MENU['function']
            );

            $moduleContent = $this->moduleContent();

            // all necessary JS code needs to be set before this line!
            $this->doc->JScode = $this->doc->wrapScriptTags($this->jsCode);
            $this->doc->JScode .= '
					<script src="' . ExtensionManagementUtility::extRelPath('tc_beuser') . 'mod4/prototype.js" type="text/javascript"></script>
					<script src="' . ExtensionManagementUtility::extRelPath('tc_beuser') . 'mod4/ajax.js" type="text/javascript"></script>';

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

    public function init()
    {
        parent::init();

        TcBeuserUtility::switchUser(GeneralUtility::_GP('SwitchUser'));

        $this->backPath = $GLOBALS['BACK_PATH'];

        // Initializing document template object:
        $this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
        $this->doc->backPath = $GLOBALS['BACK_PATH'];
        $this->doc->setModuleTemplate('EXT:tc_beuser/Resources/Private/Templates/module.html');
        $this->doc->form = '<form action="'.htmlspecialchars($this->R_URI).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR_checkSubmit(1);">';        // JavaScript
        $this->doc->getPageRenderer()->loadPrototype();
        $this->doc->postCode .= $this->doc->wrapScriptTags('
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["web"] = 0;
		');

        $this->doc->postCode .= $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL) {
				document.location = URL;
			}

			var T3_BACKPATH = \''.$this->doc->backPath.'\';
			var ajaxUrl = \'' . BackendUtility::getModuleUrl($GLOBALS['MCONF']['name']) . '\';
		');
        $this->jsCode .= $this->doc->redirectUrls(GeneralUtility::linkThisScript());

        $this->id = 0;

        // update compareFlags
        if (GeneralUtility::_GP('ads')) {
            $this->compareFlags = GeneralUtility::_GP('compareFlags');
            $GLOBALS['BE_USER']->pushModuleData('txtcbeuserM1_txtcbeuserM4/index.php/compare', $this->compareFlags);
        } else {
            $this->compareFlags = $GLOBALS['BE_USER']->getModuleData('txtcbeuserM1_txtcbeuserM4/index.php/compare', 'ses');
        }

        // Setting return URL
        $this->returnUrl = GeneralUtility::_GP('returnUrl');
        $this->retUrl    = $this->returnUrl ? $this->returnUrl : 'dummy.php';

        //init user / group
        $beuser = GeneralUtility::_GET('beUser');
        if ($beuser) {
            $this->be_user = $beuser;
        }
        $begroup = GeneralUtility::_GET('beGroup');
        if ($begroup) {
            $this->be_group = $begroup;
        }
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return	void
     */
    public function menuConfig()
    {
        $this->MOD_MENU = array(
            'function' => array(
                '1' => $GLOBALS['LANG']->getLL('overview-groups'),
                '2' => $GLOBALS['LANG']->getLL('overview-users'),
            )
        );

        $groupOnly = array();
        if ($this->MOD_SETTINGS['function'] == 1) { // groups
            $groupOnly['members'] = $GLOBALS['LANG']->getLL('showCol-members');
        }

        $groupAndUser = array(
            'filemounts'        => $GLOBALS['LANG']->getLL('showCol-filemounts'),
            'webmounts'         => $GLOBALS['LANG']->getLL('showCol-webmounts'),
            'pagetypes'         => $GLOBALS['LANG']->getLL('showCol-pagetypes'),
            'selecttables'      => $GLOBALS['LANG']->getLL('showCol-selecttables'),
            'modifytables'      => $GLOBALS['LANG']->getLL('showCol-modifytables'),
            'nonexcludefields'  => $GLOBALS['LANG']->getLL('showCol-nonexcludefields'),
            'explicitallowdeny' => $GLOBALS['LANG']->getLL('showCol-explicitallowdeny'),
            'limittolanguages'  => $GLOBALS['LANG']->getLL('showCol-limittolanguages'),
            'workspaceperms'    => $GLOBALS['LANG']->getLL('showCol-workspaceperms'),
            'workspacememship'  => $GLOBALS['LANG']->getLL('showCol-workspacememship'),
            'description'       => $GLOBALS['LANG']->getLL('showCol-description'),
            'modules'           => $GLOBALS['LANG']->getLL('showCol-modules'),
            'tsconfig'          => $GLOBALS['LANG']->getLL('showCol-tsconfig'),
            'tsconfighl'        => $GLOBALS['LANG']->getLL('showCol-tsconfighl'),
        );
        $this->MOD_MENU['showCols'] = array_merge($groupOnly, $groupAndUser);

        parent::menuConfig();
    }

    /**
     * Generates the module content
     *
     * @return	void
     */
    public function moduleContent()
    {
        $content = '';

        switch ((string)$this->MOD_SETTINGS['function']) {
            case '1':
                // group view
                $content .= $this->doc->section(
                    '',
                    $this->getGroupView($this->be_group)
                );
                break;
            case '2':
                // user view
                $content .= $this->doc->section(
                    '',
                    $this->getUserView($this->be_user)
                );
                break;
        }

        return $content;
    }

    public function printContent()
    {
        $this->content .= $this->doc->endPage();
        echo $this->content;
    }

    public function getUserView($userUid)
    {
        $content = '';

        if ($this->be_user == 0) {
            //warning - no user selected
            $content .= $GLOBALS['LANG']->getLL('select-user');

            $this->id = 0;
            $this->search_field = GeneralUtility::_GP('search_field');
            $this->pointer = MathUtility::forceIntegerInRange(
                GeneralUtility::_GP('pointer'),
                0,
                100000
            );
            $this->table = 'be_users';

            /** @var dkd\TcBeuser\Utility\RecordListUtility $dblist */
            $dblist = GeneralUtility::makeInstance('dkd\\TcBeuser\\Utility\\RecordListUtility');
            $dblist->backPath = $this->doc->backPath;
            $dblist->script = $this->MCONF['script'];
            $dblist->alternateBgColors = true;
            $dblist->userMainGroupOnly = true;
            $dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
            $dblist->showFields = array('username', 'realName', 'usergroup');
            $dblist->disableControls = array('edit' => true, 'hide' => true, 'delete' => true, 'import' => true);

            //Setup for analyze Icon
            $dblist->analyzeLabel = $GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:analyze', 1);
            $dblist->analyzeParam = 'beUser';

            $dblist->start(0, $this->table, $this->pointer, $this->search_field);
            $dblist->generateList();

            $content .= $dblist->HTMLcode ? $dblist->HTMLcode : '<br />'.$GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:not-found').'<br />';
            $content .= $dblist->getSearchBox(
                false,
                $GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod2/locallang.xml:search-user', 1)
            );
        } else {
            //real content
            $this->table = 'be_users';
            $userRecord = BackendUtility::getRecord($this->table, $userUid);
            $content .= $this->getColSelector();
            $content .= '<br />';
            $content .= $this->getUserViewHeader($userRecord);
            /** @var dkd\TcBeuser\Utility\OverviewUtility $userView */
            $userView = GeneralUtility::makeInstance('dkd\\TcBeuser\\Utility\\OverviewUtility');

            //if there is member in the compareFlags array, remove it. There is no 'member' in user view
            unset($this->compareFlags['members']);
            $content .= $userView->getTable($userRecord, $this->compareFlags);
        }

        return $content;
    }

    public function getGroupView($groupUid)
    {
        $content = '';

        if ($this->be_group == 0) {
            //warning - no user selected
            $content .= $GLOBALS['LANG']->getLL('select-group');

            $this->id = 0;
            $this->search_field = GeneralUtility::_GP('search_field');
            $this->pointer = MathUtility::forceIntegerInRange(
                GeneralUtility::_GP('pointer'),
                0,
                100000
            );
            $this->table = 'be_groups';

            /** @var dkd\TcBeuser\Utility\RecordListUtility $dblist */
            $dblist = GeneralUtility::makeInstance('dkd\\TcBeuser\\Utility\\RecordListUtility');
            $dblist->backPath = $this->doc->backPath;
            $dblist->script = $this->MCONF['script'];
            $dblist->alternateBgColors = true;
            $dblist->userMainGroupOnly = true;
            $dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
            $dblist->showFields = array('title');
            $dblist->disableControls = array('edit' => true, 'hide' => true, 'delete' => true, 'history' => true, 'new' => true, 'import' => true);

            //Setup for analyze Icon
            $dblist->analyzeLabel = $GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod3/locallang.xml:analyze', 1);
            $dblist->analyzeParam = 'beGroup';

            $dblist->start(0, $this->table, $this->pointer, $this->search_field);
            $dblist->generateList();

            $content .= $dblist->HTMLcode ? $dblist->HTMLcode : '<br />'.$GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod3/locallang.xml:not-found').'<br />';
            $content .= $dblist->getSearchBox(
                false,
                $GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/mod3/locallang.xml:search-group', 1)
            );
        } else {
            //real content
            $this->table = 'be_groups';
            $groupRecord = BackendUtility::getRecord($this->table, $groupUid);
            $content .= $this->getColSelector();
            $content .= '<br />';
//			$content .= $this->getUserViewHeader($groupRecord);

            /** @var dkd\TcBeuser\Module\OverviewController $userView */
            $userView = GeneralUtility::makeInstance('dkd\\TcBeuser\\Utility\\OverviewUtility');
            $content .= $userView->getTableGroup($groupRecord, $this->compareFlags);
        }

        return $content;
    }

    public function getColSelector()
    {
        $content = '';
        $i = 0;

        foreach ($this->MOD_MENU['showCols'] as $key => $label) {
            $content .= '<span style="display: block; float: left; width: 180px;">'
                .'<input type="checkbox" value="1" name="compareFlags['.$key.']"'.($this->compareFlags[$key]?' checked="checked"':'').' />'
                .'&nbsp;'.$label.'</span> '.chr(10);

            $i++;
            if ($i == 4) {
                $content .= chr(10).'<br />'.chr(10);
                $i = 0;
            }
        }

        $content .= '<br style="clear: left;" /><br />';
        $content .= '<input type="submit" name="ads" value="Update" />';
        $content .= '<br />';

        return $content;
    }

    public function getUserViewHeader($userRecord)
    {
        $content = '';

        $alttext = BackendUtility::getRecordIconAltText($userRecord, $this->table);
        $recTitle = htmlspecialchars(BackendUtility::getRecordTitle($this->table, $userRecord));

        // icon
        $iconImg = IconUtility::getSpriteIconForRecord(
            $this->table,
            $userRecord,
            array('title' => htmlspecialchars($alttext))
        );
        // controls
        $control = $this->makeUserControl($userRecord);

        $content .= $iconImg.' '.$recTitle.' '.$control;

        return $content;
    }

    public function makeUserControl($userRecord)
    {

        // edit
        $control = '<a href="#" onclick="'.htmlspecialchars(
                $this->editOnClick(
                    '&edit['.$this->table.']['.$userRecord['uid'].']=edit&SET[function]=edit',
                    GeneralUtility::getIndpEnv('REQUEST_URI').'SET[function]=2'
                )
            ).'"><img'.IconUtility::skinImg(
                $this->backPath,
                'gfx/edit2.gif',
                'width="11" height="12"'
            ).' title="edit" alt="" /></a>'.chr(10);

        //info
        // always show info
        $control .= '<a href="#" onclick="' . htmlspecialchars('top.launchView(\'' . $this->table . '\', \'' . $userRecord['uid'] . '\'); return false;') . '">' .
            '<img' . IconUtility::skinImg($this->backPath, 'gfx/zoom2.gif', 'width="12" height="12"') . ' title="" alt="" />' .
            '</a>' . chr(10);

        // hide/unhide
        $hiddenField = $GLOBALS['TCA'][$this->table]['ctrl']['enablecolumns']['disabled'];
        if ($userRecord[$hiddenField]) {
            $params = '&data[' . $this->table . '][' . $userRecord['uid'] . '][' . $hiddenField . ']=0&SET[function]=action';
            $control .= '<a href="#" onclick="return jumpToUrl(\'' . htmlspecialchars($this->actionOnClick($params, -1)) . '\');">' .
                '<img' . IconUtility::skinImg($this->backPath, 'gfx/button_unhide.gif', 'width="11" height="10"') . ' title="unhide" alt="" />' .
                '</a>' . chr(10);
        } else {
            $params = '&data[' . $this->table . '][' . $userRecord['uid'] . '][' . $hiddenField . ']=1&SET[function]=action';
            $control .= '<a href="#" onclick="return jumpToUrl(\'' . htmlspecialchars($this->actionOnClick($params, -1)) . '\');">' .
                '<img' . IconUtility::skinImg($this->backPath, 'gfx/button_hide.gif', 'width="11" height="10"') . ' title="hide" alt="" />' .
                '</a>' . chr(10);
        }

        // delete
        $params = '&cmd['.$this->table.']['.$userRecord['uid'].'][delete]=1&SET[function]=action&vC=' . rawurlencode($GLOBALS['BE_USER']->veriCode()) . '&prErr=1&uPT=1';
        $control .= '<a href="#" onclick="' . htmlspecialchars('if (confirm(' .
                $GLOBALS['LANG']->JScharCode(
                    $GLOBALS['LANG']->getLL('deleteWarning') .
                    BackendUtility::referenceCount(
                        $this->table,
                        $userRecord['uid'],
                        ' (There are %s reference(s) to this record!)'
                    )
                ) . ')) { return jumpToUrl(\'' . $this->actionOnClick($params, BackendUtility::getModuleUrl($GLOBALS['MCONF']['name']), $this->MOD_SETTINGS) . '\'); } return false;'
            ) . '">' .
            '<img' . IconUtility::skinImg($this->backPath, 'gfx/garbage.gif', 'width="11" height="12"') . ' title="' . $GLOBALS['LANG']->getLL('delete', 1) . '" alt="" />' .
            '</a>' . chr(10);

        //TODO: only for admins or authorized user
        // swith user / switch user back
        if (!$userRecord[$hiddenField] && ($GLOBALS['BE_USER']->user['tc_beuser_switch_to'] || $GLOBALS['BE_USER']->isAdmin())) {
            $control .= '<a href="'.GeneralUtility::linkThisScript(array('SwitchUser'=>$userRecord['uid'])).'" target="_top"><img '.IconUtility::skinImg($this->backPath, 'gfx/su.gif').' border="0" align="top" title="'.htmlspecialchars('Switch user to: '.$userRecord['username']).' [change-to mode]" alt="" /></a>'.
                '<a href="'.GeneralUtility::linkThisScript(array('SwitchUser'=>$userRecord['uid'], 'switchBackUser' => 1)).'" target="_top"><img '.IconUtility::skinImg($this->backPath, 'gfx/su_back.gif').' border="0" align="top" title="'.htmlspecialchars('Switch user to: '.$userRecord['username']).' [switch-back mode]" alt="" /></a>'
                .chr(10).chr(10);
        }

        return $control;
    }

    /**
     * ingo.renner@dkd.de: from BackendUtility, modified
     *
     * Returns a JavaScript string (for an onClick handler) which will load the alt_doc.php script that shows the form for editing of the record(s) you have send as params.
     * REMEMBER to always htmlspecialchar() content in href-properties to ampersands get converted to entities (XHTML requirement and XSS precaution)
     * Usage: 35
     *
     * @param	string		$params is parameters sent along to alt_doc.php. This requires a much more details description which you must seek in Inside TYPO3s documentation of the alt_doc.php API. And example could be '&edit[pages][123]=edit' which will show edit form for page record 123.
     * @param	string		$requestUri is an optional returnUrl you can set - automatically set to REQUEST_URI.
     * @return	string
     * @see template::issueCommand()
     */
    public function editOnClick($params, $requestUri = '')
    {
        $retUrl = '&returnUrl=' . ($requestUri == -1 ? "'+T3_THIS_LOCATION+'" : rawurlencode($requestUri ? $requestUri : GeneralUtility::getIndpEnv('REQUEST_URI')));
        return "window.location.href='" . BackendUtility::getModuleUrl('txtcbeuserM1_txtcbeuserM2') . $retUrl . $params . "'; return false;";
    }

    /**
     * create link for the hide/unhide and delete icon.
     * not using tce_db.php, because we need to manipulate user's permission
     *
     * @param	string		param with command (hide/unhide, delete) and records id
     * @param	string		redirect link, after process the command
     * @return	string		jumpTo URL link with redirect
     */
    public function actionOnClick($params, $requestURI = '')
    {
        $redirect = '&redirect=' . ($requestURI == -1 ? "'+T3_THIS_LOCATION+'" : rawurlencode($requestURI ? $requestURI : GeneralUtility::getIndpEnv('REQUEST_URI'))) .
            '&vC=' . rawurlencode($GLOBALS['BE_USER']->veriCode()) . '&prErr=1&uPT=1';
        return BackendUtility::getModuleUrl('txtcbeuserM1_txtcbeuserM2') . $params . $redirect;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return 	array		all available buttons as an assoc. array
     */
    protected function getButtons()
    {
        $buttons = array(
            'csh' => '',
            'view' => '',
            'shortcut' => ''
        );
        // CSH
        $buttons['csh'] = BackendUtility::cshItem('_MOD_web_info', '', $GLOBALS['BACK_PATH'], '', true);
        // Shortcut
        if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
            $buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
        }
        return $buttons;
    }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/mod4/index.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/mod4/index.php']);
}
