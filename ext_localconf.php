<?php

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


if (TYPO3_MODE) {
		//hooks non-admin be_users
	$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'][] =
		'EXT:tc_beuser/class.tx_tcbeuser_hooks.php:tx_tcbeuser_hooks->fakeAdmin';

		//registering hooks for be_groups form mod3
	$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
		'EXT:tc_beuser/class.tx_tcbeuser_hooks.php:tx_tcbeuser_hooks';
}


?>
