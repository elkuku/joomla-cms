<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Debug
 *
 * @copyright   Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Debug\DataCollector;

use DebugBar\DataCollector\AssetProvider;
use Joomla\CMS\Factory;
use Joomla\Plugin\System\Debug\AbstractDataCollector;

/**
 * LanguageFilesDataCollector
 *
 * @since  __DEPLOY_VERSION__
 */
class LanguageFilesCollector extends AbstractDataCollector implements AssetProvider
{
	private $name = 'languageFiles';

	/**
	 * Called by the DebugBar when data needs to be collected
	 *
	 * @since  __DEPLOY_VERSION__
	 *
	 * @return array Collected data
	 */
	public function collect()
	{
		$paths = Factory::getLanguage()->getPaths();
		$loaded = [];

		foreach ($paths as $extension => $files)
		{
			$loaded[$extension] = [];
			foreach ($files as $file => $status)
			{
				$loaded[$extension][$file] = $status;
			}
		}

		return $loaded;
	}

	/**
	 * Returns the unique name of the collector
	 *
	 * @since  __DEPLOY_VERSION__
	 *
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
	 * @since  __DEPLOY_VERSION__
	 *
	 * @return array
	 */
	public function getWidgets()
	{
		return [
			'loaded' => [
				'icon' => 'language',
				'widget' => 'PhpDebugBar.Widgets.languageFilesWidget',
				'map' => $this->name,
				'default' => '[]'
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
	 * @since  __DEPLOY_VERSION__
	 * @return array
	 */
	public function getAssets()
	{
		return array(
			'js' => \JUri::root(true) . '/media/plg_system_debug/widgets/languageFiles/widget.min.js',
			'css' => \JUri::root(true) . '/media/plg_system_debug/widgets/languageFiles/widget.min.css',
		);
	}
}
