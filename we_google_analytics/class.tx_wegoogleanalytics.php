<?php
/**************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Becker - websedit AG <extensions@websedit.de>
*
*  All rights reserved
*
*  This script is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; version 2 of the License.
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
/**
 * Based on the extension 'm1_google_analytics'
 * m1_google_analytics written by:
 * Dimitri Tarassenko (mitka@mitka.us),
 * Bjoern Kraus (kraus@phoenixwebsolutions.de)
 */

require_once (PATH_tslib.'class.tslib_pibase.php');
require_once (PATH_tslib.'class.tslib_content.php');

/**
 * This extension is used to insert the Google Analytics
 * Tracking code into your website
 *
 * @author Andreas Becker - websedit AG <extensions@websedit.de>
 *
 */
class tx_wegoogleanalytics extends tslib_pibase {

	public $prefixId = 'tx_wegoogleanalytics';
	public $scriptRelPath = 'tx_wegoogleanalytics.php';
	public $extKey = 'we_google_analytics';

	/**
	 * Insert Google Analytics Code before the output of the page
	 * 
	 * @param str &$content Pagecontent
	 * @param str $conf     TyposcriptConfig
	 * @return Void
	 */
	public function main ( &$content, $conf ) {
		$this->content = &$content;
        $this->conf = $conf;

		if (!$this->conf['account']) {
			return;
		}
		$content = $this->process($this->content);
	}

	/**
	 * Processes the configuration given by TypoScript
	 *
	 * @param str $con Pagecontent
	 * @return str Pagecontent
	 */
	protected function process( $con ) {
		// validate given account number
		if (preg_match('#^\b(UA|MO)-\d{4,10}-\d{1,4}\b$#i', $this->conf['account'])) {
			$accountCheckPassed = 1;
		} else {
			$accountCheckPassed = 0;
		}
		
		$content = $con;
		if ($accountCheckPassed) {
			switch($this->conf['type']){
				case 'mobile':
					$content = $this->insertMobileGaCode($con);
					break;
				case 'sync':
					$content = $this->insertSyncGaCode($con);
					break;
				case 'async':
				default:
					$content = $this->insertAsyncGaCode($con);
					// Async is default
			}
		} else {
			$errorMessage = '<!--'.chr(10);
			$errorMessage .= '     Ooops: Syntaxcheck of Google Analytics Account Number failed!'.chr(10);
			$errorMessage .= '     Maybe misspelled entry in config.tx_we_google_analytics.account.'.chr(10);
			$errorMessage .= '     You used '.htmlspecialchars($this->conf['account']).chr(10);
			$errorMessage .= '     Please use the following format UA-xxxx-y ,'.chr(10);
			$errorMessage .= '     or for mobile tracking, use MO-xxxx-y.'.chr(10);
			$errorMessage .= '-->'.chr(10);
			$content = $this->insertTrackerCode($con, $errorMessage, 'headEnd');
		}
		return $content;
	}

	/**
	 * Google Analytics Mobile Tracking Code (extended)
	 * 
	 * @param bool $ano Anonymize IP (1) or not (0)
	 * @param str  $acc Google Analytics Account Number
	 * @return str TrackerUrl
	 *
	 */
	protected function googleAnalyticsGetImageUrl($ano) {
		// Copyright 2009 Google Inc. All Rights Reserved.
		$url = '';
		$url .= t3lib_extMgm::extRelPath($this->extKey).'ga.php?';
		$url .= 'utmac=' . $this->conf['account'];
		$url .= '&utmn=' . rand(0, 0x7fffffff);
		$referer = t3lib_div::GPvar('HTTP_REFERER');
		$query = t3lib_div::GPvar('QUERY_STRING');
		$path = t3lib_div::GPvar('REQUEST_URI');
		if (empty($referer)) {
		  $referer = '-';
		}
		$url .= '&utmr=' . urlencode($referer);
		if (!empty($path)) {
		  $url .= '&utmp=' . urlencode($path);
		}
		$url .= '&guid=ON';
		// Extended to implement anonymizeIP function
		if ($ano) {
			$url .= '&ano='.$ano;
		}
		return str_replace('&', '&amp;', $url);
	}

	/**
	 * Google Analytics Mobile Tracking Code
	 * 
	 * @param str $con Pagecontent
	 * @return str Pagecontent
	 * 
	 */
	protected function insertMobileGaCode($con) {
		$anonym = ($this->conf['anonymized'] == 1) ? 1 : 0;
		$gaMobileAfterContent = '<img src="'. $this->googleAnalyticsGetImageUrl($anonym).'" alt="" />';
		$content = $this->insertTrackerCode($con, $gaMobileAfterContent, 'bodyEnd');
		return $content;
	}

	/**
	 * Google Analytics sync (Traditional)
	 * 
	 * @param str $con Pagecontent
	 * @return str Pagecontent
	 * 
	 */
	protected function insertSyncGaCode($con) {
	  
		$anonymizeIp = '';
		if ($this->conf['anonymized'] == 1) {
			$anonymizeIp = '_gat._anonymizeIp();';
		}
		
		$trackpageload = '';
		if ($this->conf['trackpageload'] == 1) {
			$trackpageload = 'pageTracker._trackPageLoadTime();';
		}

		/* Remove _gaq. and _gat. if set
		 * also remove configs without underscore (e.g. account) */
		$gaConf = array();
		foreach ($this->conf as $param => $val) {
			$param = htmlspecialchars($param);
			if (is_array($val)) {
				foreach ($val as $paramTwo => $valTwo) {
					$paramTwo = htmlspecialchars($paramTwo);
					$valTwo = htmlspecialchars($valTwo);
					if (substr($paramTwo, 0, 1) == '_') {
						$gaConf[$paramTwo] = $valTwo;
					}
				}
			} else {
				$val = htmlspecialchars($val);
				if (substr($param, 0, 1) == '_') {
					$gaConf[$param] = $val;
				}
			}
		}

		$options = '';
		foreach ($gaConf as $param => $val) {
			if ($val != 'true' && $val != 'false' && $val != '1' && $val != '0' && strpos($val, ',') === FALSE) {
				$val = '"'.$val.'"';
			}
			$val = str_replace('&amp;', '&', $val);
			$options .= 'pageTracker.'.$param.'('.$val.');'.chr(10);
		}

		$gaSyncBeforeContent = chr(10).'<script type="text/javascript">
/* <![CDATA[ */
 var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
 document.write("\<script src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'>\<\/script>" );
/* ]]> */
</script>
<script type="text/javascript">
/* <![CDATA[ */
 try{
  var pageTracker = _gat._getTracker("'.$this->conf['account'].'");
  '.$anonymizeIp.'
  '.$options.'
  pageTracker._initData();
 } catch(err) {}
/* ]]> */
</script>';

		$gaSyncAfterContent = '<script type="text/javascript">
/* <![CDATA[ */
 try{
  pageTracker._trackPageview();
  '.$trackpageload.'
 } catch(err) {}
/* ]]> */
</script>';

		$con = $this->insertTrackerCode($con, $gaSyncBeforeContent, 'bodyBegin');
		$content = $this->insertTrackerCode($con, $gaSyncAfterContent, 'bodyEnd');
		return $content;
	}

	/**
	 * Google Analytics async
	 * 
	 * @param str $con Pagecontent
	 * @return str Pagecontent
	 *
	 */
	protected function insertAsyncGaCode($con) {
	  
		$anonymizeIp = "";
		if ($this->conf['anonymized'] == 1) {
			$anonymizeIp = " ['_gat._anonymizeIp'],";
		}

		$trackfiles = "";
		$trackfiletypes = "";
		/* If filetracking is enabled, clean the userinput */
		if ($this->conf['trackfiles'] == 1) {
		  if(!empty($this->conf['trackfiles.']['path'])){
			$trackfiles = str_replace(' ','',trim($this->conf['trackfiles.']['path']));
			$trackfiles = str_replace(',','|',addslashes(strip_tags($trackfiles)));              
			$trackfiles = str_replace('/','\/',addslashes($trackfiles));
		  }else{
			/* default values, if no other path is specified */
			$trackfiles = 'fileadmin|uploads|typo3temp';
		  }
		  /* Track only given filetypes */
		  if(!empty($this->conf['trackfiles.']['types'])){
			$trackfiletypes = str_replace(' ','',trim($this->conf['trackfiles.']['types']));
			$trackfiletypes = str_replace(',','|',addslashes(strip_tags($trackfiletypes)));              
			$trackfiletypes = str_replace('/','\/',addslashes($trackfiletypes));
		  }else{
			$trackfiletypes = "\w{1,3}";
		  }
		}
		
		$trackpageload = "";
		if ($this->conf['trackpageload'] == 1) {
			$trackpageload = ", ['_trackPageLoadTime']";
		}

		/* Remove _gaq. and _gat. if set
		 * also remove configs without underscore (e.g. account) */
		$gaConf = array();
		foreach ($this->conf as $param => $val) {
			$param = htmlspecialchars($param);
			if (is_array($val)) {
				if (substr($param, 0, 1) !== '_' or $param === '_gaq' or $param === '_gat') {
					foreach ($val as $paramTwo => $valTwo) {
						$paramTwo = htmlspecialchars($paramTwo);
						$valTwo = htmlspecialchars($valTwo);
						if (substr($paramTwo, 0, 1) == '_') {
							$gaConf[$paramTwo] = $valTwo;
						}
					}
				} else {
					/* Allow multiple calls for one param:
					 * 	_addOrganic {
					 * 		1 = 'suche.web.de', 'su'
					 * 		2 = 'suche.t-online.de','q'
					 * 		3 = 'suche.gmx.net','su'
					 * 		4 = 'search.1und1.de','q'
					 * 		5 = 'suche.freenet.de','query'
					 * 	}
					 * will give:
					 *	 ['_addOrganic.', suche.web.de', 'su],
					 *   ['_addOrganic.', suche.t-online.de','q],
					 *   ['_addOrganic.', suche.gmx.net','su],
					 *   ['_addOrganic.', search.1und1.de','q],
					 *   ['_addOrganic.', suche.freenet.de','query], ... 
					 */
					if (isset($gaConf[$param])) {
						if (is_array($gaConf[$param])) {
							'pass';
						} else {
							$gaConf[$param] = array($gaConf[$param]);
						}
					} else {
						$gaConf[$param] = array();
					}
					foreach ($val as $paramTwo => $valTwo) {
						$paramTwo = htmlspecialchars($paramTwo);
						$valTwo = htmlspecialchars($valTwo);
						$gaConf[$param][] = $valTwo;
					}
				}
			} else {
				$val = htmlspecialchars($val);
				if (substr($param, 0, 1) == '_') {
					$gaConf[$param] = $val;
				}
			}
		}

		$options = '';
		foreach ($gaConf as $param => $val0) {
			if (!is_array($val0)) {
				$val0 = array($val0);
			}
			foreach ($val0 as $val) {
				if ($val != 'true' && $val != 'false' && $val != '1' && $val != '0' && strpos($val, ',') === FALSE) {
					$val = "'" . $val . "'";
				}
				$val = str_replace('&amp;', '&', $val);
				$options .= " ['" . $param . "', ".$val. "],";
			}
		}

		$gaAsync .= '<script type="text/javascript">'.chr(10);
		$gaAsync .= '/* <![CDATA[ */'.chr(10);
		$gaAsync .= " var _gaq = [['_setAccount', '".$this->conf['account']."'],".$anonymizeIp.$options." ['_trackPageview']".$trackpageload."];".chr(10);
#		$gaAsync .= " (function(d, t) {
#  var g = d.createElement(t); g.async = true;
#  g.src = ('https:' == d.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
#  var s = d.getElementsByTagName(t)[0]; s.parentNode.insertBefore(g, s);
# })(document, 'script');
#/* ]]> */
#</script>";

		$gaAsync .= " (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
/* ]]> */
</script>";

		/* Add filetracking code to the links in the document */
		if($trackfiles){
		  //$con = preg_replace( '/((\<a\s([^\>]*?)href\=[\"\\\'](\/?('.$trackfiles.')\/(.*?))[\"\\\']([^\>]*?))\>)/i', '$2 onclick="_gaq.push([\'_trackEvent\', \'Downloads\', \'File\', \'$4\']); ">', $con);
		  $con = preg_replace('/(<a\s*.*?href\s*=\s*[\"\\\'](\/?('.$trackfiles.')(.*?))('.$trackfiletypes.')[\"\\\']([^\>]*?))/i', "$1 onclick=\"_gaq.push(['_trackEvent', 'Downloads', '$5', '$4$5']);\"", $con);
		  
		}

		$content = $this->insertTrackerCode($con, $gaAsync, 'headEnd');
		return $content;
	}

	/**
	 * Inserts the Tracker Code on the given position (headEnd, bodyBegin, bodyEnd)
	 *
	 * @param str $con      Pagecontent
	 * @param str $gaCode   Google Analytics Tracker Code
	 * @param str $position Position of the Tracker Code in the html document
	 * @return str          Pagecontent
	 */
	protected function insertTrackerCode($con, $gaCode, $position) {
		switch($position) {
			case 'headEnd':
				$content = str_replace('</head>', $gaCode.chr(10).'</head>', $con);
				break;
			case 'bodyBegin':
				$content = preg_replace('/(<body)([^>]*)>/', '\\1\\2>'.$gaCode.chr(10), $con);
				break;
			case 'bodyEnd':
				$content = str_replace('</body>', $gaCode.chr(10).'</body>', $con);
				break;
			default:
			// none
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/we_google_analytics/class.tx_wegoogleanalytics.php']) {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/we_google_analytics/class.tx_wegoogleanalytics.php']);
}

?>