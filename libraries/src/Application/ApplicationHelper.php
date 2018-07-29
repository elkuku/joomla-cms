<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Application;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;

/**
 * Application helper functions
 *
 * @since  1.5
 */
class ApplicationHelper
{
	/**
	 * Client information array
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected static $_clients = array();

	/**
	 * Return the name of the request component [main component]
	 *
	 * @param   string  $default  The default option
	 *
	 * @return  string  Option (e.g. com_something)
	 *
	 * @since   1.6
	 */
	public static function getComponentName($default = null)
	{
		static $option;

		if ($option)
		{
			return $option;
		}

		$input = Factory::getApplication()->input;
		$option = strtolower($input->get('option'));

		if (empty($option))
		{
			$option = $default;
		}

		$input->set('option', $option);

		return $option;
	}

	/**
	 * Provides a secure hash based on a seed
	 *
	 * @param   string  $seed  Seed string.
	 *
	 * @return  string  A secure hash
	 *
	 * @since   3.2
	 */
	public static function getHash($seed)
	{
		return md5(Factory::getConfig()->get('secret') . $seed);
	}

	/**
	 * This method transliterates a string into a URL
	 * safe string or returns a URL safe UTF-8 string
	 * based on the global configuration
	 *
	 * @param   string  $string    String to process
	 * @param   string  $language  Language to transliterate to if unicode slugs are disabled
	 *
	 * @return  string  Processed string
	 *
	 * @since   3.2
	 */
	public static function stringURLSafe($string, $language = '')
	{
		if (Factory::getConfig()->get('unicodeslugs') == 1)
		{
			$output = OutputFilter::stringURLUnicodeSlug($string);
		}
		else
		{
			if ($language === '*' || $language === '')
			{
				$languageParams = ComponentHelper::getParams('com_languages');
				$language = $languageParams->get('site');
			}

			$output = OutputFilter::stringURLSafe($string, $language);
		}

		return $output;
	}

	/**
	 * Gets information on a specific client id.  This method will be useful in
	 * future versions when we start mapping applications in the database.
	 *
	 * This method will return a client information array if called
	 * with no arguments which can be used to add custom application information.
	 *
	 * @param   integer  $id      A client identifier
	 * @param   boolean  $byName  If True, find the client by its name
	 *
	 * @return  mixed  Object describing the client or false if not known
	 *
	 * @since   1.5
	 */
	public static function getClientInfo($id = null, $byName = false)
	{
		// Only create the array if it is empty
		if (empty(self::$_clients))
		{
			$obj = new \stdClass;

			// Site Client
			$obj->id = 0;
			$obj->name = 'site';
			$obj->path = JPATH_SITE;
			self::$_clients[0] = clone $obj;

			// Administrator Client
			$obj->id = 1;
			$obj->name = 'administrator';
			$obj->path = JPATH_ADMINISTRATOR;
			self::$_clients[1] = clone $obj;

			// Installation Client
			$obj->id = 2;
			$obj->name = 'installation';
			$obj->path = JPATH_INSTALLATION;
			self::$_clients[2] = clone $obj;
		}

		// If no client id has been passed return the whole array
		if ($id === null)
		{
			return self::$_clients;
		}

		// Are we looking for client information by id or by name?
		if (!$byName)
		{
			if (isset(self::$_clients[$id]))
			{
				return self::$_clients[$id];
			}
		}
		else
		{
			foreach (self::$_clients as $client)
			{
				if ($client->name == strtolower($id))
				{
					return $client;
				}
			}
		}

		return;
	}

	/**
	 * Adds information for a client.
	 *
	 * @param   mixed  $client  A client identifier either an array or object
	 *
	 * @return  boolean  True if the information is added. False on error
	 *
	 * @since   1.6
	 */
	public static function addClientInfo($client)
	{
		if (is_array($client))
		{
			$client = (object) $client;
		}

		if (!is_object($client))
		{
			return false;
		}

		$info = self::getClientInfo();

		if (!isset($client->id))
		{
			$client->id = count($info);
		}

		self::$_clients[$client->id] = clone $client;

		return true;
	}
}
