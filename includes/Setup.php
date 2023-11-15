<?php
namespace MediaWiki\Extension\NaylorAMS;

class Setup {
	public static function init() {
		global $wgPluggableAuth_ExtraLoginFields;
		$config = Config::newInstance();
		$GLOBALS['wgPluggableAuth_ExtraLoginFields'] = (array)new ExtraLoginFields( $config );
		$wgPluggableAuth_ExtraLoginFields = (array)new ExtraLoginFields( $config );
	}
}
