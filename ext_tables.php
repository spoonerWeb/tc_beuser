<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
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

		// enabling regular BE users to edit BE users, goups and filemounts
	$GLOBALS['TCA']['be_users']['ctrl']['adminOnly'] = 0;
	$GLOBALS['TCA']['be_groups']['ctrl']['adminOnly'] = 0;
	$GLOBALS['TCA']['sys_filemounts']['ctrl']['adminOnly'] = 0;

	//wizard for the password generator
	$wizConfig = array(
		'type' => 'userFunc',
		'userFunc' => 'dkd\\TcBeuser\\Utility\\PwdWizardUtility->main',
		'params' => array('type' => 'password')
	);

	$confField = 'tx_tcbeuser';

	$TCA['be_users']['columns']['password']['config']['wizards'][$confField] = $wizConfig;
	$TCA['be_users']['columns']['usergroup']['config']['itemsProcFunc'] = 'dkd\\TcBeuser\\Utility\\TcBeuserUtility->getGroupsID';
	$TCA['be_groups']['columns']['subgroup']['config']['itemsProcFunc'] = 'dkd\\TcBeuser\\Utility\\TcBeuserUtility->getGroupsID';
}

$tempCol = array(
	'members' => array(
		'label' => 'User',
		'config' => array(
			'type' => 'select',
			'foreign_table' => 'be_users',
			'foreign_table_where' => 'ORDER BY username ASC',
			'size' => '10',
			'maxitems' => 100,
			'iconsInOptionTags' => 1,
		)
	)
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $tempCol, 1);

unset ($TCA['be_users']['columns']['usergroup']['config']['wizards']);

$TCA['be_users']['columns']['usergroup']['config']['size'] = '10';
?>