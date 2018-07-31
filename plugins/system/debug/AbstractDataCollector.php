<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Debug
 *
 * @copyright   Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Debug;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataFormatter\DataFormatterInterface;
use Joomla\Registry\Registry;

/**
 * AbstractDataCollector
 *
 * @since  __DEPLOY_VERSION__
 */
abstract class AbstractDataCollector extends DataCollector implements Renderable
{
	/**
	 * @var Registry
	 * @since __DEPLOY_VERSION__
	 */
	protected $params;

	/**
	 * @var DataFormatter
	 * @since __DEPLOY_VERSION__
	 */
	private static $defaultDataFormatter;

	/**
	 * AbstractDataCollector constructor.
	 *
	 * @param   Registry  $params  Parameters.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function __construct(Registry $params)
	{
		$this->params = $params;
	}

	/**
	 * Get a data formatter.
	 *
	 * @since __DEPLOY_VERSION__
	 * @return DataFormatterInterface
	 */
	public function getDataFormatter(): DataFormatterInterface
	{
		if ($this->dataFormater === null)
		{
			$this->dataFormater = self::getDefaultDataFormatter();
		}

		return $this->dataFormater;
	}

	/**
	 * Returns the default data formater
	 *
	 * @since __DEPLOY_VERSION__
	 * @return DataFormatterInterface
	 */
	public static function getDefaultDataFormatter(): DataFormatterInterface
	{
		if (self::$defaultDataFormatter === null)
		{
			self::$defaultDataFormatter = new DataFormatter;
		}

		return self::$defaultDataFormatter;
	}
}
