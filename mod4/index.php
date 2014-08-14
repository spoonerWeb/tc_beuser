<?php
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tc_beuser').'mod4/class.tx_tcbeuser_module4.php');


if(GeneralUtility::_POST('ajaxCall')) {
	$method   = GeneralUtility::_POST('method');
	$groupId  = GeneralUtility::_POST('groupId');
	$open     = GeneralUtility::_POST('open');
	$backPath = GeneralUtility::_POST('backPath');

	$userView = GeneralUtility::makeInstance('tx_tcbeuser_overview');
	$content  = $userView->handleMethod( $method, $groupId, $open, $backPath );

	echo $content;
} else {
	// Make instance:
	$SOBE = GeneralUtility::makeInstance('tx_tcbeuser_module4');
	$SOBE->init();

	// Include files?
	foreach($SOBE->include_once as $INC_FILE) {
		include_once($INC_FILE);
	}

	$SOBE->main();
	$SOBE->printContent();
}



?>
