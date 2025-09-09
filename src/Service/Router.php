<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Fisc_calculator
 * @author     Stefan Gabardi <stefan@gabardi.at>
 * @copyright  2024 Stefan Gabardi
 * @license    copyrighted
 */

namespace Fisccalculator\Component\Fisc_calculator\Site\Service;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Menu\AbstractMenu;

/**
 * Class Fisc_calculatorRouter
 *
 */
class Router extends RouterView
{
	private $noIDs;
	/**
	 * The category factory
	 *
	 * @var    CategoryFactoryInterface
	 *
	 * @since  1.0.0
	 */
	private $categoryFactory;

	/**
	 * The category cache
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	private $categoryCache = [];

	public function __construct(SiteApplication $app, AbstractMenu $menu, CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
	{
		$params = Factory::getApplication()->getParams('com_fisc_calculator');
		$this->noIDs = (bool) $params->get('sef_ids');
		$this->categoryFactory = $categoryFactory;
		
		
			$calculators = new RouterViewConfiguration('calculators');
			$this->registerView($calculators);
			$ccCalculator = new RouterViewConfiguration('calculator');
			$ccCalculator->setKey('id')->setParent($calculators);
			$this->registerView($ccCalculator);
			$calculatorform = new RouterViewConfiguration('calculatorform');
			$calculatorform->setKey('id');
			$this->registerView($calculatorform);
			$calculatoritems = new RouterViewConfiguration('calculatoritems');
			$this->registerView($calculatoritems);
			$ccCalculatoritem = new RouterViewConfiguration('calculatoritem');
			$ccCalculatoritem->setKey('id')->setParent($calculatoritems);
			$this->registerView($ccCalculatoritem);
			$calculatoritemform = new RouterViewConfiguration('calculatoritemform');
			$calculatoritemform->setKey('id');
			$this->registerView($calculatoritemform);

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}


	
		/**
		 * Method to get the segment(s) for an calculator
		 *
		 * @param   string  $id     ID of the calculator to retrieve the segments for
		 * @param   array   $query  The request that is built right now
		 *
		 * @return  array|string  The segments of this item
		 */
		public function getCalculatorSegment($id, $query)
		{
			return array((int) $id => $id);
		}
			/**
			 * Method to get the segment(s) for an calculatorform
			 *
			 * @param   string  $id     ID of the calculatorform to retrieve the segments for
			 * @param   array   $query  The request that is built right now
			 *
			 * @return  array|string  The segments of this item
			 */
			public function getCalculatorformSegment($id, $query)
			{
				return $this->getCalculatorSegment($id, $query);
			}
		/**
		 * Method to get the segment(s) for an calculatoritem
		 *
		 * @param   string  $id     ID of the calculatoritem to retrieve the segments for
		 * @param   array   $query  The request that is built right now
		 *
		 * @return  array|string  The segments of this item
		 */
		public function getCalculatoritemSegment($id, $query)
		{
			return array((int) $id => $id);
		}
			/**
			 * Method to get the segment(s) for an calculatoritemform
			 *
			 * @param   string  $id     ID of the calculatoritemform to retrieve the segments for
			 * @param   array   $query  The request that is built right now
			 *
			 * @return  array|string  The segments of this item
			 */
			public function getCalculatoritemformSegment($id, $query)
			{
				return $this->getCalculatoritemSegment($id, $query);
			}

	
		/**
		 * Method to get the segment(s) for an calculator
		 *
		 * @param   string  $segment  Segment of the calculator to retrieve the ID for
		 * @param   array   $query    The request that is parsed right now
		 *
		 * @return  mixed   The id of this item or false
		 */
		public function getCalculatorId($segment, $query)
		{
			return (int) $segment;
		}
			/**
			 * Method to get the segment(s) for an calculatorform
			 *
			 * @param   string  $segment  Segment of the calculatorform to retrieve the ID for
			 * @param   array   $query    The request that is parsed right now
			 *
			 * @return  mixed   The id of this item or false
			 */
			public function getCalculatorformId($segment, $query)
			{
				return $this->getCalculatorId($segment, $query);
			}
		/**
		 * Method to get the segment(s) for an calculatoritem
		 *
		 * @param   string  $segment  Segment of the calculatoritem to retrieve the ID for
		 * @param   array   $query    The request that is parsed right now
		 *
		 * @return  mixed   The id of this item or false
		 */
		public function getCalculatoritemId($segment, $query)
		{
			return (int) $segment;
		}
			/**
			 * Method to get the segment(s) for an calculatoritemform
			 *
			 * @param   string  $segment  Segment of the calculatoritemform to retrieve the ID for
			 * @param   array   $query    The request that is parsed right now
			 *
			 * @return  mixed   The id of this item or false
			 */
			public function getCalculatoritemformId($segment, $query)
			{
				return $this->getCalculatoritemId($segment, $query);
			}

	/**
	 * Method to get categories from cache
	 *
	 * @param   array  $options   The options for retrieving categories
	 *
	 * @return  CategoryInterface  The object containing categories
	 *
	 * @since   1.0.0
	 */
	private function getCategories(array $options = []): CategoryInterface
	{
		$key = serialize($options);

		if (!isset($this->categoryCache[$key]))
		{
			$this->categoryCache[$key] = $this->categoryFactory->createCategory($options);
		}

		return $this->categoryCache[$key];
	}
}
