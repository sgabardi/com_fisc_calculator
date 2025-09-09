<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Fisc_calculator
 * @author     Stefan Gabardi <stefan@gabardi.at>
 * @copyright  2024 Stefan Gabardi
 * @license    copyrighted
 */

namespace Fisccalculator\Component\Fisc_calculator\Site\Dispatcher;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Language\Text;

/**
 * ComponentDispatcher class for Com_Fisc_calculator
 *
 * @since  1.0.0
 */
class Dispatcher extends ComponentDispatcher
{
	/**
	 * Dispatch a controller task. Redirecting the user if appropriate.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function dispatch()
	{
		parent::dispatch();
	}
}
