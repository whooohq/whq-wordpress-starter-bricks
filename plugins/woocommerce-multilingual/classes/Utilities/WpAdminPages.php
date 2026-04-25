<?php

namespace WCML\Utilities;

class WpAdminPages {

	/**
	 * @return bool
	 */
	public static function isDashboard() {
		global $pagenow;

		return is_admin() && 'index.php' === $pagenow;
	}

	/**
	 * @return bool
	 */
	public static function isPermalinksSettings() {
		global $pagenow;

		return is_admin() && 'options-permalink.php' === $pagenow;
	}
}
