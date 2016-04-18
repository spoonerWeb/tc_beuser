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

    # GroupAdmin Module
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        'GroupAdmin',
        'bottom',
        '',
        array(
            'routeTarget' => dkd\TcBeuser\Controller\GroupAdminController::class . '::mainAction',
            'access' => 'group,user',
            'name' => 'tcTools_GroupAdmin',
            'workspaces' => 'online',
            'labels' => array(
                'tabs_images' => array(
                    'tab' => 'EXT:tc_beuser/Resources/Public/Images/moduleGroupAdmin.gif',
                ),
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModuleGroupAdmin.xml',
            )
        )
    );

    # FilemountsView Module
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        'FilemountsView',
        'bottom',
        '',
        array(
            'routeTarget' => dkd\TcBeuser\Controller\FilemountsViewController::class . '::mainAction',
            'access' => 'group,user',
            'name' => 'tcTools_FilemountsView',
            'workspaces' => 'online',
            'labels' => array(
                'tabs_images' => array(
                    'tab' => 'EXT:tc_beuser/Resources/Public/Images/moduleFilemountsView.gif',
                ),
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModuleFilemountsView.xml',
            )
        )
    );

    # Overview Module
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        'Overview',
        'bottom',
        '',
        array(
            'routeTarget' => dkd\TcBeuser\Controller\OverviewController::class . '::mainAction',
            'access' => 'group,user',
            'name' => 'tcTools_Overview',
            'workspaces' => 'online',
            'labels' => array(
                'tabs_images' => array(
                    'tab' => 'EXT:tc_beuser/Resources/Public/Images/moduleOverview.gif',
                ),
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModuleOverview.xml',
            )
        )
    );

    # Overview Module
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'PermissionModule',
        'bottom',
        '',
        array(
            'routeTarget' => dkd\TcBeuser\Controller\PermissionModuleController::class . '::mainAction',
            'access' => 'group,user',
            'name' => 'web_PermissionModule',
            'workspaces' => 'online',
            'labels' => array(
                'tabs_images' => array(
                    'tab' => 'EXT:tc_beuser/Resources/Public/Images/modulePermission.png',
                ),
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModulePermission.xml',
            )
        )
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
        'PermissionAjaxController::dispatch',
        'dkd\\TcBeuser\\Controller\\PermissionAjaxController->dispatch'
    );
}