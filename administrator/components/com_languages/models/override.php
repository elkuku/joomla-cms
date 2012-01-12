<?php
/**
 * @version		$Id$
 * @package		Joomla.Administrator
 * @subpackage	com_languages
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Languages Override Model
 *
 * @package			Joomla.Administrator
 * @subpackage	com_languages
 * @since				2.5
 */
class LanguagesModelOverride extends JModelAdmin
{
	/**
	 * Method to get the record form.
	 *
	 * @param		array		$data			Data for the form.
	 * @param		boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 *
	 * @return	mixed		A JForm object on success, false on failure
	 *
	 * @since		2.5
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form
		$form = $this->loadForm('com_languages.override', 'override', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		$client		= $this->getState('filter.client', 'site');
		$language	= $this->getState('filter.language', 'en-GB');
		$langName	= JLanguage::getInstance($language)->getName();
		if (!$langName)
		{
			// If a language only exists in frontend, it's meta data cannot be
			// loaded in backend at the moment, so fall back to the language tag
			$langName = $language;
		}
		$form->setValue('client', null, JText::_('COM_LANGUAGES_VIEW_OVERRIDE_CLIENT_'.strtoupper($client)));
		$form->setValue('language', null, JText::sprintf('COM_LANGUAGES_VIEW_OVERRIDE_LANGUAGE', $langName, $language));
		$form->setValue('file', null, JPath::clean(constant('JPATH_'.strtoupper($client)).DS.'language'.DS.'overrides'.DS.$language.'.override.ini'));

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed The data for the form
	 *
	 * @since	 2.5
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_languages.edit.override.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param		string	$pk	The key name.
	 *
	 * @return	mixed		Object on success, false otherwise.
	 *
	 * @since		2.5
	 */
	public function getItem($pk = null)
	{
		require_once JPATH_COMPONENT.'/helpers/languages.php';

		$pk	= (!empty($pk)) ? $pk : JRequest::getCmd('id');
		$filename = constant('JPATH_'.strtoupper($this->getState('filter.client'))).DS.'language'.DS.'overrides'.DS.$this->getState('filter.language', 'en-GB').'.override.ini';
		$strings = LanguagesHelper::parseFile($filename);

		$result = new stdClass();
		$result->key			= '';
		$result->override	= '';
		if (isset($strings[$pk]))
		{
			$result->key			= $pk;
			$result->override	= $strings[$pk];
		}

		return $result;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param		array		$data The form data.
	 *
	 * @return	boolean	True on success, false otherwise.
	 *
	 * @since		2.5
	 */
	public function save($data)
	{
		$app = JFactory::getApplication();
		require_once JPATH_COMPONENT.'/helpers/languages.php';

		$client		= $app->getUserState('com_languages.overrides.filter.client', 0) ? 'administrator' : 'site';
		$language	= $app->getUserState('com_languages.overrides.filter.language', 'en-GB');

		// Parse the override.ini file in oder to get the keys and strings
		$filename	= constant('JPATH_'.strtoupper($client)).DS.'language'.DS.'overrides'.DS.$language.'.override.ini';
		$strings	= LanguagesHelper::parseFile($filename);

		if (isset($strings[$data['id']]))
		{
			// If an existent string was edited check whether
			// the name of the constant is still the same
			if ($data['key'] == $data['id'])
			{
				// If yes, simply override it
				$strings[$data['key']] = $data['override'];
			}
			else
			{
				// If no, delete the old string and prepend the new one
				unset($strings[$data['id']]);
				$strings = array($data['key'] => $data['override']) + $strings;
			}
		}
		else
		{
			// If it is a new override simply prepend it
			$strings = array($data['key'] => $data['override']) + $strings;
		}

		// Write override.ini file with the strings
		$registry = new JRegistry();
		$registry->loadObject($strings);

		if (!JFile::write($filename, $registry->toString('INI')))
		{
			return false;
		}

		return true;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return	void
	 *
	 * @since		2.5
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication();

		$client = $app->getUserStateFromRequest('com_languages.overrides.filter.client', 'filter_client', 0, 'int') ? 'administrator' : 'site';
		$this->setState('filter.client', $client);

		$language = $app->getUserStateFromRequest('com_languages.overrides.filter.language', 'filter_language', 'en-GB', 'cmd');
		$this->setState('filter.language', $language);
	}
}