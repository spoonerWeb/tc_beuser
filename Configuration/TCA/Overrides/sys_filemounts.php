<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
    // enabling regular BE users to edit filemounts
    $GLOBALS['TCA']['sys_filemounts']['ctrl']['adminOnly'] = 0;
}
