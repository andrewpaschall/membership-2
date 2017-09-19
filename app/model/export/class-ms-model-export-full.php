<?php
/**
 * Class that handles Full Export functions.
 *
 * @since  1.1.3
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Export_Full extends MS_Model {


	/**
	 * Main entry point: Handles the export action.
	 *
	 * This task will exit the current request as the result will be a download
	 * and no HTML page that is displayed.
	 *
	 * @param String $format - export format
	 *
	 * @since  1.1.3
	 */
	public function process( $format ) {

	}
}

?>