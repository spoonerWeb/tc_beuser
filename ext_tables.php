<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
    $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);

        // add module before 'Help'
    if (!isset($GLOBALS['TBE_MODULES']['tcTools'])) {
        $temp_TBE_MODULES = array();
        foreach ($GLOBALS['TBE_MODULES'] as $key => $val) {
            if ($key == 'help') {
                $temp_TBE_MODULES['tcTools'] = '';
                $temp_TBE_MODULES[$key] = $val;
            } else {
                $temp_TBE_MODULES[$key] = $val;
            }
        }

        $GLOBALS['TBE_MODULES'] = $temp_TBE_MODULES;
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('tcTools', 'txtcbeuserM3', 'bottom', $extPath . 'mod3/');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('tcTools', 'txtcbeuserM5', 'bottom', $extPath . 'mod5/');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('tcTools', 'txtcbeuserM4', 'bottom', $extPath . 'mod4/');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('web', 'txtcbeuserM6', '', $extPath . 'mod6/');


    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        '',
        '',
        '',
        array(
            'access' => 'group,user',
            'name' => 'tcTools',
            'labels' => array(
                'tabs_images' => array(
                    'tab' => 'EXT:tc_beuser/Resources/Public/Images/moduleTcTools.gif',
                ),
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangTcTools.xml',
            ),
        )
    );

    # UserAdmin Module
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        'UserAdmin',
        'bottom',
        '',
        array(
            'routeTarget' => dkd\TcBeuser\Controller\UserAdminController::class . '::mainAction',
            'access' => 'group,user',
            'name' => 'tcTools_UserAdmin',
            'workspaces' => 'online',
            'labels' => array(
                'tabs_images' => array(
                    'tab' => 'EXT:tc_beuser/Resources/Public/Images/moduleUserAdmin.gif',
                ),
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModuleUserAdmin.xml',
            )
        )
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
        'PermissionAjaxController::dispatch',
        'dkd\\TcBeuser\\Controller\\PermissionAjaxController->dispatch'
    );
}