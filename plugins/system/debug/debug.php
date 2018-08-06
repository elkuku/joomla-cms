<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Debug
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DebugBar;
use DebugBar\Storage\FileStorage;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\Debug\DataCollector\InfoCollector;
use Joomla\Plugin\System\Debug\DataCollector\LanguageErrorsCollector;
use Joomla\Plugin\System\Debug\DataCollector\LanguageFilesCollector;
use Joomla\Plugin\System\Debug\DataCollector\LanguageStringsCollector;
use Joomla\Plugin\System\Debug\DataCollector\ProfileCollector;
use Joomla\Plugin\System\Debug\DataCollector\QueryCollector;
use Joomla\Plugin\System\Debug\DataCollector\SessionCollector;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\Event\ConnectionEvent;

JLoader::register('DebugMonitor', __DIR__ . '/debugmonitor.php');

/**
 * Joomla! Debug plugin.
 *
 * @since  1.5
 */
class PlgSystemDebug extends CMSPlugin
{
	/**
	 * xdebug.file_link_format from the php.ini.
	 *
	 * @var    string
	 * @since  1.7
	 */
	protected $linkFormat = '';

	/**
	 * True if debug lang is on.
	 *
	 * @var    boolean
	 * @since  3.0
	 */
	private $debugLang = false;

	/**
	 * Holds log entries handled by the plugin.
	 *
	 * @var    LogEntry[]
	 * @since  3.1
	 */
	private $logEntries = array();

	/**
	 * Holds SHOW PROFILES of queries.
	 *
	 * @var    array
	 * @since  3.1.2
	 */
	private $sqlShowProfiles = array();

	/**
	 * Holds all SHOW PROFILE FOR QUERY n, indexed by n-1.
	 *
	 * @var    array
	 * @since  3.1.2
	 */
	private $sqlShowProfileEach = array();

	/**
	 * Holds all EXPLAIN EXTENDED for all queries.
	 *
	 * @var    array
	 * @since  3.1.2
	 */
	private $explains = array();

	/**
	 * Holds total amount of executed queries.
	 *
	 * @var    int
	 * @since  3.2
	 */
	private $totalQueries = 0;

	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  3.3
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    DatabaseDriver
	 * @since  3.8.0
	 */
	protected $db;

	/**
	 * @var DebugBar
	 * @since __DEPLOY_VERSION__
	 */
	private $debugBar;

	/**
	 * The query monitor.
	 *
	 * @var    DebugMonitor
	 * @since  4.0.0
	 */
	private $queryMonitor;

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  &$subject  The object to observe.
	 * @param   array                $config    An optional associative array of configuration settings.
	 *
	 * @since   1.5
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		// Get the application if not done by JPlugin. This may happen during upgrades from Joomla 2.5.
		if (!$this->app)
		{
			$this->app = Factory::getApplication();
		}

		// Get the db if not done by JPlugin. This may happen during upgrades from Joomla 2.5.
		if (!$this->db)
		{
			$this->db = Factory::getDbo();
		}

		$this->debugLang = $this->app->get('debug_lang');

		// Skip the plugin if debug is off
		if ($this->debugLang === '0' && $this->app->get('debug') === '0')
		{
			return;
		}

		$this->app->getConfig()->set('gzip', 0);
		ob_start();
		ob_implicit_flush(false);

		$this->linkFormat = ini_get('xdebug.file_link_format');

		// Attach our query monitor to the database driver
		$this->queryMonitor = new DebugMonitor((bool) JDEBUG);

		$this->db->setMonitor($this->queryMonitor);

		$this->debugBar = new DebugBar;
		$this->debugBar->setStorage(new FileStorage($this->app->get('tmp_path')));

		$this->setupLogging();
	}

	/**
	 * Add the CSS for debug.
	 * We can't do this in the constructor because stuff breaks.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function onAfterDispatch()
	{
		// Only if debugging or language debug is enabled.
		if ((JDEBUG || $this->debugLang) && $this->isAuthorisedDisplayDebug())
		{
			HTMLHelper::_('stylesheet', 'plg_system_debug/debug.css', array('version' => 'auto', 'relative' => true));
			HTMLHelper::_('script', 'plg_system_debug/debug.min.js', array('version' => 'auto', 'relative' => true));
		}

		// Disable asset media version if needed.
		if (JDEBUG && (int) $this->params->get('refresh_assets', 1) === 0)
		{
			$this->app->getDocument()->setMediaVersion(null);
		}

		// Only if debugging is enabled for SQL query popovers.
		if (JDEBUG && $this->isAuthorisedDisplayDebug())
		{
			HTMLHelper::_('bootstrap.popover', '.hasPopover', array('placement' => 'top'));
		}
	}

	/**
	 * Show the debug info.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onAfterRespond()
	{
		// Do not render if debugging or language debug is not enabled.
		if (!JDEBUG && !$this->debugLang)
		{
			return;
		}

		// User has to be authorised to see the debug information.
		if (!$this->isAuthorisedDisplayDebug())
		{
			return;
		}

		// Only render for HTML output.
		if (Factory::getDocument()->getType() !== 'html')
		{
			return;
		}

		if ('com_content' === $this->app->input->get('option') && 'debug' === $this->app->input->get('view'))
		{
			// Com_content debug view - @since 4.0
			return;
		}

		// Capture output.
		$contents = ob_get_contents();

		if ($contents)
		{
			ob_end_clean();
		}

		// No debug for Safari and Chrome redirection.
		if (strpos($contents, '<html><head><meta http-equiv="refresh" content="0;') === 0
			&& strpos(strtolower($_SERVER['HTTP_USER_AGENT'] ?? ''), 'webkit') !== false)
		{
			echo $contents;

			return;
		}

		// @todo Remove when a standard autoloader is available.
		JLoader::registerNamespace('Joomla\\Plugin\\System\\Debug', __DIR__, false, false, 'psr4');

		// Load language.
		$this->loadLanguage();

		$this->debugBar->addCollector(new InfoCollector($this->params, $this->debugBar->getCurrentRequestId()));
		$this->debugBar->addCollector(new RequestDataCollector);

		if (JDEBUG)
		{
			if ($this->params->get('session', 1))
			{
				$this->debugBar->addCollector(new SessionCollector($this->params));
			}

			if ($this->params->get('profile', 1))
			{
				$this->debugBar->addCollector(new ProfileCollector($this->params));
			}

			if ($this->params->get('memory', 1))
			{
				$this->debugBar->addCollector(new MemoryCollector);
			}

			if ($this->params->get('queries', 1))
			{
				$this->debugBar->addCollector(new QueryCollector($this->params, $this->queryMonitor));
			}

			if (!empty($this->logEntries) && $this->params->get('logs', 1))
			{
				$this->collectLogs();
			}
		}

		if ($this->debugLang)
		{
			$this->debugBar->addCollector(new LanguageFilesCollector($this->params));
			$this->debugBar->addCollector(new LanguageStringsCollector($this->params));
			$this->debugBar->addCollector(new LanguageErrorsCollector($this->params));
		}

		$debugBarRenderer = $this->debugBar->getJavascriptRenderer();

		$debugBarRenderer->setBaseUrl(JUri::root(true) . '/media/vendor/debugbar/');

		$contents = str_replace('</head>', $debugBarRenderer->renderHead() . '</head>', $contents);

		echo str_replace('</body>', $debugBarRenderer->render() . '</body>', $contents);
	}

	/**
	 * Setup logging functionality.
	 *
	 * @return $this
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function setupLogging(): self
	{
		// Log the deprecated API.
		if ($this->params->get('log-deprecated'))
		{
			JLog::addLogger(array('text_file' => 'deprecated.php'), JLog::ALL, array('deprecated'));
		}

		// Log everything (except deprecated APIs, these are logged separately with the option above).
		if ($this->params->get('log-everything'))
		{
			JLog::addLogger(array('text_file' => 'everything.php'), JLog::ALL, array('deprecated', 'databasequery'), true);
		}

		if ($this->params->get('logs', 1))
		{
			$priority = 0;

			foreach ($this->params->get('log_priorities', array()) as $p)
			{
				$const = '\\Joomla\\CMS\\Log\\Log::' . strtoupper($p);

				if (defined($const))
				{
					$priority |= constant($const);
				}
			}

			// Split into an array at any character other than alphabet, numbers, _, ., or -
			$categories = array_filter(preg_split('/[^A-Z0-9_\.-]/i', $this->params->get('log_categories', '')));
			$mode = $this->params->get('log_category_mode', 0);

			JLog::addLogger(array('logger' => 'callback', 'callback' => array($this, 'logger')), $priority, $categories, $mode);
		}

		// Log deprecated class aliases
		foreach (JLoader::getDeprecatedAliases() as $deprecation)
		{
			JLog::add(
				sprintf(
					'%1$s has been aliased to %2$s and the former class name is deprecated. The alias will be removed in %3$s.',
					$deprecation['old'],
					$deprecation['new'],
					$deprecation['version']
				),
				JLog::WARNING,
				'deprecation-notes'
			);
		}

		return $this;
	}

	/**
	 * Method to check if the current user is allowed to see the debug information or not.
	 *
	 * @return  boolean  True if access is allowed.
	 *
	 * @since   3.0
	 */
	private function isAuthorisedDisplayDebug(): bool
	{
		static $result = null;

		if ($result !== null)
		{
			return $result;
		}

		// If the user is not allowed to view the output then end here.
		$filterGroups = (array) $this->params->get('filter_groups', null);

		if (!empty($filterGroups))
		{
			$userGroups = Factory::getUser()->get('groups');

			if (!array_intersect($filterGroups, $userGroups))
			{
				$result = false;

				return false;
			}
		}

		$result = true;

		return true;
	}

	/**
	 * Disconnect handler for database to collect profiling and explain information.
	 *
	 * @param   ConnectionEvent  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onAfterDisconnect(ConnectionEvent $event)
	{
		return;
		if (!JDEBUG)
		{
			return;
		}

		$db = $event->getDriver();

		$this->totalQueries = $db->getCount();

		if ($db->getServerType() === 'mysql')
		{
			try
			{
				// Check if profiling is enabled.
				$db->setQuery("SHOW VARIABLES LIKE 'have_profiling'");
				$hasProfiling = $db->loadResult();

				if ($hasProfiling)
				{
					// Run a SHOW PROFILE query.
					$db->setQuery('SHOW PROFILES');
					$this->sqlShowProfiles = $db->loadAssocList();

					if ($this->sqlShowProfiles)
					{
						foreach ($this->sqlShowProfiles as $qn)
						{
							// Run SHOW PROFILE FOR QUERY for each query where a profile is available (max 100).
							$db->setQuery('SHOW PROFILE FOR QUERY ' . (int) $qn['Query_ID']);
							$this->sqlShowProfileEach[(int) ($qn['Query_ID'] - 1)] = $db->loadAssocList();
						}
					}
				}
				else
				{
					$this->sqlShowProfileEach[0] = array(array('Error' => 'MySql have_profiling = off'));
				}
			}
			catch (Exception $e)
			{
				$this->sqlShowProfileEach[0] = array(array('Error' => $e->getMessage()));
			}
		}

		if (in_array($db->getServerType(), ['mysql', 'postgresql'], true))
		{
			$log = $this->queryMonitor->getLog();

			foreach ($log as $k => $query)
			{
				$dbVersion56 = $db->getServerType() === 'mysql' && version_compare($db->getVersion(), '5.6', '>=');

				if ((stripos($query, 'select') === 0) || ($dbVersion56 && ((stripos($query, 'delete') === 0) || (stripos($query, 'update') === 0))))
				{
					try
					{
						$db->setQuery('EXPLAIN ' . ($dbVersion56 ? 'EXTENDED ' : '') . $query);
						$this->explains[$k] = $db->loadAssocList();
					}
					catch (Exception $e)
					{
						$this->explains[$k] = array(array('Error' => $e->getMessage()));
					}
				}
			}
		}
	}

	/**
	 * Store log messages so they can be displayed later.
	 * This function is passed log entries by JLogLoggerCallback.
	 *
	 * @param   LogEntry  $entry  A log entry.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function logger(LogEntry $entry)
	{
		$this->logEntries[] = $entry;
	}

	/**
	 * Collect log messages.
	 *
	 * @return $this
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function collectLogs(): self
	{
		if (!$this->logEntries)
		{
			return $this;
		}

		$logDeprecated = $this->params->get('log-deprecated', 0);
		$logDeprecatedCore = $this->params->get('log-deprecated-core', 0);

		$this->debugBar->addCollector(new MessagesCollector('log'));

		if ($logDeprecated)
		{
			$this->debugBar->addCollector(new MessagesCollector('deprecated'));
			$this->debugBar->addCollector(new MessagesCollector('deprecation-notes'));
		}
		if ($logDeprecatedCore)
		{
			$this->debugBar->addCollector(new MessagesCollector('deprecated-core'));
		}

		foreach ($this->logEntries as $entry)
		{
			switch ($entry->category)
			{
				case 'deprecation-notes':
					if ($logDeprecated)
					{
						$this->debugBar[$entry->category]->addMessage($entry->message);
					}
				break;
				case 'deprecated':
					if (!$logDeprecated && !$logDeprecatedCore)
					{
						continue;
					}
					$file = $entry->callStack[2]['file'] ?? '';
					$line = $entry->callStack[2]['line'] ?? '';

					if (!$file)
					{
						// In case trigger_error is used
						$file = $entry->callStack[4]['file'] ?? '';
						$line = $entry->callStack[4]['line'] ?? '';
					}

					$category = $entry->category;
					$relative = str_replace(JPATH_ROOT, '', $file);

					if (0 === strpos($relative, '/libraries/joomla')
						|| 0 === strpos($relative, '/libraries/cms')
						|| 0 === strpos($relative, '/libraries/src'))
					{
						if (!$logDeprecatedCore)
						{
							continue;
						}
						$category .= '-core';
					}
					elseif (!$logDeprecated)
					{
						continue;
					}

					$message = [
						'message' => $entry->message,
						'caller' => $file . ':' . $line,
						// @todo 'stack' => $entry->callStack;
					];
					$this->debugBar[$category]->addMessage($message, 'warning');
				break;

				case 'databasequery':
					// Should be collected by its own collector
				break;

				default:
					switch ($entry->priority)
					{
						case Log::EMERGENCY:
						case Log::ALERT:
						case Log::CRITICAL:
						case Log::ERROR:
							$level = 'error';
							break;
						case Log::WARNING:
							$level = 'warning';
							break;
						case Log::NOTICE:
						case Log::INFO:
						case Log::DEBUG:
							$level = 'info';
							break;
						default:
							$level = 'info';
					}
					$this->debugBar['log']->addMessage($entry->category . ' - ' . $entry->message, $level);
					break;
			}
		}

		return $this;
	}
}
