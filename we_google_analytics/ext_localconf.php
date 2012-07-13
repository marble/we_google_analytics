<?php
if (!defined ("TYPO3_MODE")) die ("Access denied.");

if(TYPO3_MODE=='FE') require_once(t3lib_extMgm::extPath('we_google_analytics').'class.tx_wegoogleanalytics.php');
#####################################################
## Hook for HTML-modification on the page   #########
#####################################################
// hook is called after Caching! => for modification of pages with COA_/USER_INT objects. 
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:we_google_analytics/class.tx_wegoogleanalytics_fehook.php:&tx_wegoogleanalytics_fehook->intPages'; 
// hook is called before Caching! => for modification of pages on their way in the cache.
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = 'EXT:we_google_analytics/class.tx_wegoogleanalytics_fehook.php:&tx_wegoogleanalytics_fehook->noIntPages'; 
#####################################################
?>