<?php
/**
 * Util function 모음
 * 
 * @author mini
 */
Class iiUtil {
	/**
	 * browser 종류 식별
	 * @param string $agent http agent data
	 * @return array (browser => version)
	 */
	 static public function browser($agent = null) {
		// Declare known browsers to look for 
		$known = array( 'msie', 'firefox', 'safari', 'webkit', 'opera', 'netscape', 'konqueror', 'gecko', 'chrome'); 

		/* 
		 * Clean up agent and build regex that matches phrases for known browsers 
		 * (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor version numbers.  
		 * E.g. "2.0.0.6" is parsed as simply "2.0" 
		 */ 
		$agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']); 
		$pattern = '#(?<browser>' . join( '|', $known ) . ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#'; 

		// Find all phrases (or return empty false if none found) 
		$matches = array();
		 
		if( !preg_match_all( $pattern, $agent, $matches ) ) { 
			return false; 
		} 

		// determine which index to use 
		if( count( $matches['browser'] ) > 1 ) { 
			$i = 1;
		} else { 
			$i = 0; 
		} 

		// return the results 
		return array($matches['browser'][$i] => $matches['version'][$i]); 
	}
}
?>