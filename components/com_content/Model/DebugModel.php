<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Content\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\Model\ItemModel;

/**
 * Content Component Debug Model
 *
 * @since  __DEPLOY_VERSION__
 */
class DebugModel extends ItemModel
{
	/**
	 * Model context string.
	 *
	 * @var   string
	 * @since __DEPLOY_VERSION__
	 */
	protected $_context = 'com_content.debug';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since  __DEPLOY_VERSION__
	 *
	 * @return void
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$id = $app->input->get('id');
		$this->setState('request.id', $id);

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);
	}

	/**
	 * Method to get reports data.
	 *
	 * @param   integer  $id  The id of the report.
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 * @return  object|boolean  Menu item data object on success, boolean false
	 */
	public function getReports($id = null)
	{
		$id = $id ?? $this->getState('request.id');

		$path = Factory::getApplication()->get('tmp_path') . "/$id.json";

		if (false === File::exists($path))
		{
			throw new \UnexpectedValueException('Invalid request ID', 500);
		}

		return json_decode(file_get_contents($path), true);
	}

	/**
	 * Get the last ten emtries.
	 *
	 * @return array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getLastTen(): array
	{
		$data = [];
		$path = Factory::getApplication()->get('tmp_path');

		$files = Folder::files($path, '.json', false, true);

		foreach ($files as $file)
		{
			$info = json_decode(file_get_contents($file), true);

			if (isset($info['__meta']))
			{
				$data[] = $info['__meta'];
			}
		}

		usort(
			$data, function($aArr, $bArr) {
				$a = $aArr['datetime'];
				$b = $bArr['datetime'];

				if ($a === $b)
				{
					return 0;
				}

				return $a > $b ? -1 : 1;
			}
		);

		return $data;
	}
}
