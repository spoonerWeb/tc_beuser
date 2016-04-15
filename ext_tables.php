<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
    $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);

        // add module before 'Help'
    if (!isset($GLOBALS['TBE_MODULES']['txtcbeuserM1'])) {
        $temp_TBE_MODULES = array();
        foreach ($GLOBALS['TBE_MODULES'] as $key => $val) {
            if ($key == 'help') {
                $temp_TBE_MODULES['txtcbeuserM1'] = '';
                $temp_TBE_MODULES[$key] = $val;
            } else {
                $temp_TBE_MODULES[$key] = $val;
            }
        }

        $GLOBALS['TBE_MODULES'] = $temp_TBE_MODULES;
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txtcbeuserM1', '', '', $extPath . 'mod1/');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txtcbeuserM1', 'txtcbeuserM2', 'bottom', $extPath . 'mod2/');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txtcbeuserM1', 'txtcbeuserM3', 'bottom', $extPath . 'mod3/');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txtcbeuserM1', 'txtcbeuserM5', 'bottom', $extPath . 'mod5/');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txtcbeuserM1', 'txtcbeuserM4', 'bottom', $extPath . 'mod4/');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('web', 'txtcbeuserM6', '', $extPath . 'mod6/');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
        'PermissionAjaxController::dispatch',
        'dkd\\TcBeuser\\Controller\\PermissionAjaxController->dispatch'
    );
}