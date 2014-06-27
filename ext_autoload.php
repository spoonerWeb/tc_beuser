<?php
$extpath = t3lib_extMgm::extPath('tc_beuser');

return array(
	'tx_tcbeuser_access' => $extpath . 'class.tx_tcbeuser_access.php',
	'tx_tcbeuser_compatibility' => $extpath . 'class.tx_tcbeuser_compatibility.php',
	'tx_tcbeuser_config' => $extpath . 'class.tx_tcbeuser_config.php',
	'tx_tcbeuser_editform' => $extpath . 'class.tx_tcbeuser_editform.php',
	'tx_tcbeuser_grouptree' => $extpath . 'class.tx_tcbeuser_grouptree.php',
	'tx_tcbeuser_hooks' => $extpath . 'class.tx_tcbeuser_hooks.php',
	'tx_tcbeuser_overview' => $extpath . 'class.tx_tcbeuser_overview.php',
	'tx_tcbeuser_recordlist' => $extpath . 'class.tx_tcbeuser_recordlist.php',
);

?>