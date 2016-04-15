<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// fe_users modified
$be_users_cols = array(
    'tc_beuser_switch_to' => array(
        'label' => 'LLL:EXT:tc_beuser/locallang_tca.xml:be_users.tc_beuser_switch_to',
        'exclude' => '1',
        'config' => array(
            'type' => 'check'
        )
    )
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $be_users_cols);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCATypes('be_users', '--div--;tc_beuser,tc_beuser_switch_to');
