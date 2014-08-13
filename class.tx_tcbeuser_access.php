<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Ingo Renner (ingo.renner@dkd.de)
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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * methods for access checks
 * $Id: class.tx_tcbeuser_access.php,v 1.1 2006/08/14 08: 02: 16 dkd-renner Exp
 * $
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 */
class tx_tcbeuser_access {

	function getFakePageInfo() {
		#['perms_userid']
	}

	function readPageAccess($conf, $exitOnError) {
		$access = false;

		if(BackendUserAuthentication::modAccess($conf, $exitOnError)) {
			$access = true;
		}

		return $access;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_access.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tc_beuser/class.tx_tcbeuser_access.php']);
}

?>
