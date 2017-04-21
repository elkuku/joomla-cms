<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Debug
 *
 * @copyright   Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace plgSystemDebug\DataCollector;

use DebugBar\DataCollector\AssetProvider;
use Joomla\Registry\Registry;
use plgSystemDebug\AbstractDataCollector;

/**
 * InfoDataCollector
 *
 * @since  version
 */
class InfoDataCollector extends AbstractDataCollector implements AssetProvider
{
	private $name = 'info';

	private $requestId;

	/**
	 * InfoDataCollector constructor.
	 *
	 * @param   Registry  $params     Parameters
	 * @param   string    $requestId  Request ID
	 *
	 * @since  version
	 */
	public function __construct(Registry $params, $requestId)
	{
		$this->requestId = $requestId;

		return parent::__construct($params);
	}

	/**
	 * Called by the DebugBar when data needs to be collected
	 *
	 * @since  version
	 *
	 * @return array Collected data
	 */
	public function collect()
	{
		return [
			'phpVersion' => PHP_VERSION,
			'joomlaVersion' => JVERSION,
			'requestId' => $this->requestId
		];
	}

	/**
	 * Returns the unique name of the collector
	 *
	 * @since  version
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns a hash where keys are control names and their values
	 * an array of options as defined in {@see DebugBar\JavascriptRenderer::addControl()}
	 *
	 * @since  version
	 * @return array
	 */
	public function getWidgets()
	{
		return [
			'info' => [
				'widget'  => 'PhpDebugBar.Widgets.InfoWidget',
				'map'     => $this->name,
				'default' => '{}',
			]
		];
	}

	/**
	 * Returns an array with the following keys:
	 *  - base_path
	 *  - base_url
	 *  - css: an array of filenames
	 *  - js: an array of filenames
	 *
	 * @since  version
	 * @return array
	 */
	public function getAssets()
	{
		return array(
			'js' => \JUri::root(true) . '/media/plg_system_debug/widgets/info/widget.js',
			'css' => \JUri::root(true) . '/media/plg_system_debug/widgets/info/widget.css',
		);
	}
}
