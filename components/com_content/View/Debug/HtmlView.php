<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Content\Site\View\Debug;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\ContentHTMLHelper as ContentHTML;
use Joomla\Registry\Registry;

/**
 * HTML Article View class for the Content component
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The reports
	 *
	 * @var  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $reports = [];

	/**
	 * Meta information
	 *
	 * @var  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $meta;

	/**
	 * Active panel
	 *
	 * @var  string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $panel;

	/**
	 * Active link
	 *
	 * @var  Uri
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $uri;

	/**
	 * Data for the current view
	 *
	 * @var  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $data = [];

	/**
	 * Data for the current view
	 *
	 * @var  Registry
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private $pluginParameters;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		$this->reports = $this->get('Reports');
		$this->uri = Uri::getInstance();
		$this->panel = Factory::getApplication()->input->get('panel', 'info');
		$search = Factory::getApplication()->input->get('search');

		if (isset($this->reports['__meta']))
		{
			$this->meta = $this->reports['__meta'];
			unset ($this->reports['__meta']);
		}

		// The following, plugin related, code shouldn't be necessary once we are in a proper component.
		$plugin = PluginHelper::getPlugin('system', 'debug');
		$this->pluginParameters = new Registry($plugin->params);
		$lang = Factory::getLanguage();
		$lang->load('plg_system_debug', JPATH_ADMINISTRATOR, null, false, true)
		|| $lang->load('plg_system_debug', JPATH_PLUGINS . '/system/debug', null, false, true);

		if ($search)
		{
			switch ($search)
			{
				case 'lastTen':
					$this->data = $this->get('lastTen');
					break;
			}

			$this->setLayout('search');
		}
		else
		{
			switch ($this->panel)
			{
				case 'languageErrors':
					$this->data = $this->getLanguageErrorReport($this->reports['languageErrors']['data'] ?? []);
					break;
				case 'languageStrings':
					$this->data = $this->getLanguageStringsReport($this->reports['languageStrings']['data'] ?? []);
					break;
				case 'queries':
					$this->data = $this->getDatabaseReport($this->reports['queries']['data'] ?? []);
					break;
				case 'profile':
					$this->data = $this->getProfileReport($this->reports['profile']['rawMarks'] ?? []);
					break;
			}
		}

		return parent::display($tpl);
	}

	/**
	 * Get Language strings report.
	 *
	 * @param   array  $orphans  Orphans
	 *
	 * @return array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function getLanguageStringsReport(array $orphans): array
	{
		$stripFirst = $this->pluginParameters->get('strip-first');
		$stripPref = $this->pluginParameters->get('strip-prefix');
		$stripSuff = $this->pluginParameters->get('strip-suffix');

		if (!\count($orphans))
		{
			return [Text::_('JNONE')];
		}

		ksort($orphans, SORT_STRING);

		$guesses = [];

		foreach ($orphans as $key => $occurance)
		{
				if (!isset($guesses[$occurance]))
				{
					$guesses[$occurance] = [];
				}

				// Prepare the key.
				if (strpos($key, '=') > 0)
				{
					$parts = explode('=', $key);
					$key = $parts[0];
					$guess = $parts[1];
				}
				else
				{
					$guess = str_replace('_', ' ', $key);

					if ($stripFirst)
					{
						$parts = explode(' ', $guess);

						if (\count($parts) > 1)
						{
							array_shift($parts);
							$guess = implode(' ', $parts);
						}
					}

					$guess = trim($guess);

					if ($stripPref)
					{
						$guess = trim(preg_replace(\chr(1) . '^' . $stripPref . \chr(1) . 'i', '', $guess));
					}

					if ($stripSuff)
					{
						$guess = trim(preg_replace(\chr(1) . $stripSuff . '$' . \chr(1) . 'i', '', $guess));
					}
				}

				$key = strtoupper(trim($key));
				$key = preg_replace('#\s+#', '_', $key);
				$key = preg_replace('#\W#', '', $key);

				// Prepare the text.
				$guesses[$occurance][] = $key . '="' . $guess . '"';
		}

		return $guesses;
	}

	/**
	 * Get Language errors report.
	 *
	 * @param   array  $errors  Errors
	 *
	 * @return array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function getLanguageErrorReport(array $errors): array
	{
		$data = [];

		foreach ($errors as $error)
		{
			$parts = explode(' : error(s) in line(s) ', $error);
			if (2 === \count($parts))
			{
				$lines = explode(',', $parts[1]);
				foreach ($lines as $line)
				{
					$data[] = HTMLHelper::_('debug.xdebuglink', $parts[0], trim($line));
				}
			}
			else
			{
				$data[] = $error;
			}
		}

		return $data;
	}

	/**
	 * Display logged queries.
	 *
	 * @param   array  $log  The database log.
	 *
	 * @return  array
	 *
	 * @since   2.5
	 */
	protected function getDatabaseReport(array $log): array
	{
		if (!$log)
		{
			return [ContentHTML::noItems()];
		}

		$totalQueries = $this->reports['queries']['count'] ?? 0;
		$timings    = $this->reports['queries']['timings'] ?? [];
		$callStacks = $this->reports['queries']['stacks'] ?? [];
		$profiles = $this->reports['queries']['profiles'] ?? [];
		$explains = $this->reports['queries']['explains'] ?? [];
		$prefix = $this->reports['queries']['prefix'] ?? '';

		$selectQueryTypeTicker = array();
		$otherQueryTypeTicker  = array();

		$timing  = array();
		$maxtime = 0;

		if (isset($timings[0]))
		{
			$startTime         = $timings[0];
			$endTime           = $timings[\count($timings) - 1];
			$totalBargraphTime = $endTime - $startTime;

			if ($totalBargraphTime > 0)
			{
				foreach ($log as $id => $query)
				{
					if (isset($timings[$id * 2 + 1]))
					{
						// Compute the query time: $timing[$k] = array( queryTime, timeBetweenQueries ).
						$timing[$id] = array(
							($timings[$id * 2 + 1] - $timings[$id * 2]) * 1000,
							$id > 0 ? ($timings[$id * 2] - $timings[$id * 2 - 1]) * 1000 : 0,
						);
						$maxtime     = max($maxtime, $timing[$id]['0']);
					}
				}
			}
		}
		else
		{
			$startTime         = null;
			$totalBargraphTime = 1;
		}

		$bars           = array();
		$info           = array();
		$totalQueryTime = 0;
		$duplicates     = array();

		foreach ($log as $id => $query)
		{
			$did = md5($query);

			if (!isset($duplicates[$did]))
			{
				$duplicates[$did] = array();
			}

			$duplicates[$did][] = $id;

			if ($timings && isset($timings[$id * 2 + 1]))
			{
				// Compute the query time.
				$queryTime      = ($timings[$id * 2 + 1] - $timings[$id * 2]) * 1000;
				$totalQueryTime += $queryTime;

				// Run an EXPLAIN EXTENDED query on the SQL query if possible.
				$hasWarnings          = false;
				$hasWarningsInProfile = false;

				if (isset($explains[$id]))
				{
					$explain = ContentHTML::tableToHtml($explains[$id], $hasWarnings);
				}
				else
				{
					$explain = Text::sprintf('PLG_DEBUG_QUERY_EXPLAIN_NOT_POSSIBLE', htmlspecialchars($query));
				}

				// Run a SHOW PROFILE query.
				$profile = '';

				if (isset($profiles[$id]))
				{
					$profile = ContentHTML::tableToHtml($profiles[$id], $hasWarningsInProfile);
				}

				// How heavy should the string length count: 0 - 1.
				$ratio     = 0.5;
				$timeScore = $queryTime / ((\strlen($query) + 1) * $ratio) * 200;

				// Determine color of bargraph depending on query speed and presence of warnings in EXPLAIN.
				if ($timeScore > 10)
				{
					$barClass   = 'bg-danger';
					$labelClass = 'badge-danger';
				}
				elseif ($hasWarnings || $timeScore > 5)
				{
					$barClass   = 'bg-warning';
					$labelClass = 'badge-warning';
				}
				else
				{
					$barClass   = 'bg-success';
					$labelClass = 'badge-success';
				}

				// Computes bargraph as follows: Position begin and end of the bar relatively to whole execution time.
				$barPre   = round($timing[$id][1] / ($totalBargraphTime * 10), 4);
				$barWidth = round($timing[$id][0] / ($totalBargraphTime * 10), 4);
				$minWidth = 0.3;

				if ($barWidth < $minWidth)
				{
					$barPre -= ($minWidth - $barWidth);

					if ($barPre < 0)
					{
						$minWidth += $barPre;
						$barPre   = 0;
					}

					$barWidth = $minWidth;
				}

				$bars[$id] = (object) array(
					'class' => $barClass,
					'width' => $barWidth,
					'pre'   => $barPre,
					'tip'   => sprintf('%.2f&nbsp;ms', $queryTime),
				);
				$info[$id] = (object) array(
					'class'       => $labelClass,
					'explain'     => $explain,
					'profile'     => $profile,
					'hasWarnings' => $hasWarnings,
				);
			}
		}

		// Remove single queries from $duplicates.
		$total_duplicates = 0;

		foreach ($duplicates as $did => $dups)
		{
			if (\count($dups) < 2)
			{
				unset($duplicates[$did]);
			}
			else
			{
				$total_duplicates += \count($dups);
			}
		}

		// Fix first bar width.
		$minWidth = 0.3;

		if ($bars[0]->width < $minWidth && isset($bars[1]))
		{
			$bars[1]->pre -= ($minWidth - $bars[0]->width);

			if ($bars[1]->pre < 0)
			{
				$minWidth     += $bars[1]->pre;
				$bars[1]->pre = 0;
			}

			$bars[0]->width = $minWidth;
		}

		$memoryUsageNow = memory_get_usage();
		$list           = array();

		foreach ($log as $id => $query)
		{
			// Start query type ticker additions.
			$fromStart  = stripos($query, 'from');
			$whereStart = stripos($query, 'where', $fromStart);

			if ($whereStart === false)
			{
				$whereStart = stripos($query, 'order by', $fromStart);
			}

			if ($whereStart === false)
			{
				$whereStart = \strlen($query) - 1;
			}

			$fromString = substr($query, 0, $whereStart);
			$fromString = str_replace(array("\t", "\n"), ' ', $fromString);
			$fromString = trim($fromString);

			// Initialise the select/other query type counts the first time.
			if (!isset($selectQueryTypeTicker[$fromString]))
			{
				$selectQueryTypeTicker[$fromString] = 0;
			}

			if (!isset($otherQueryTypeTicker[$fromString]))
			{
				$otherQueryTypeTicker[$fromString] = 0;
			}

			// Increment the count.
			if (stripos($query, 'select') === 0)
			{
				$selectQueryTypeTicker[$fromString]++;
				unset($otherQueryTypeTicker[$fromString]);
			}
			else
			{
				$otherQueryTypeTicker[$fromString]++;
				unset($selectQueryTypeTicker[$fromString]);
			}

			$text = ContentHTML::highlightQuery($query, $prefix);

			if ($timings && isset($timings[$id * 2 + 1]))
			{
				// Compute the query time.
				$queryTime = ($timings[$id * 2 + 1] - $timings[$id * 2]) * 1000;

				// Timing
				// Formats the output for the query time with EXPLAIN query results as tooltip:
				$htmlTiming = '<div style="margin: 0 0 5px;"><span class="dbg-query-time">';
				$htmlTiming .= Text::sprintf(
					'PLG_DEBUG_QUERY_TIME',
					sprintf(
						'<span class="badge %s">%.2f&nbsp;ms</span>',
						$info[$id]->class,
						$timing[$id]['0']
					)
				);

				if ($timing[$id]['1'])
				{
					$htmlTiming .= ' ' . Text::sprintf(
							'PLG_DEBUG_QUERY_AFTER_LAST',
							sprintf('<span class="badge badge-secondary">%.2f&nbsp;ms</span>', $timing[$id]['1'])
						);
				}

				$htmlTiming .= '</span>';

				if (isset($callStacks[$id][0]['memory']))
				{
					$memoryUsed        = $callStacks[$id][0]['memory'][1] - $callStacks[$id][0]['memory'][0];
					$memoryBeforeQuery = $callStacks[$id][0]['memory'][0];

					// Determine colour of query memory usage.
					if ($memoryUsed > 0.1 * $memoryUsageNow)
					{
						$labelClass = 'badge-danger';
					}
					elseif ($memoryUsed > 0.05 * $memoryUsageNow)
					{
						$labelClass = 'badge-warning';
					}
					else
					{
						$labelClass = 'badge-success';
					}

					$htmlTiming .= ' ' . '<span class="dbg-query-memory">'
						. Text::sprintf(
							'PLG_DEBUG_MEMORY_USED_FOR_QUERY',
							sprintf('<span class="badge ' . $labelClass . '">%.3f&nbsp;MB</span>', $memoryUsed / 1048576),
							sprintf('<span class="badge badge-secondary">%.3f&nbsp;MB</span>', $memoryBeforeQuery / 1048576)
						)
						. '</span>';

					if ($callStacks[$id][0]['memory'][2] !== null)
					{
						// Determine colour of number or results.
						$resultsReturned = (int) $callStacks[$id][0]['memory'][2];

						if ($resultsReturned > 3000)
						{
							$labelClass = 'badge-danger';
						}
						elseif ($resultsReturned > 1000)
						{
							$labelClass = 'badge-warning';
						}
						elseif ($resultsReturned === 0)
						{
							$labelClass = '';
						}
						else
						{
							$labelClass = 'badge-success';
						}

						$htmlResultsReturned = '<span class="badge ' . $labelClass . '">' . $resultsReturned . '</span>';
						$htmlTiming         .= ' <span class="dbg-query-rowsnumber">'
							. Text::sprintf('PLG_DEBUG_ROWS_RETURNED_BY_QUERY', $htmlResultsReturned) . '</span>';
					}
				}

				$htmlTiming .= '</div>';

				// Bar.
				$htmlBar = ContentHTML::renderBars($bars, 'query', $id);

				// Profile query.
				$title = Text::_('PLG_DEBUG_PROFILE');

				if (!$info[$id]->profile)
				{
					$title = '<span class="dbg-noprofile">' . $title . '</span>';
				}

				$htmlProfile = $info[$id]->profile ?: Text::_('PLG_DEBUG_NO_PROFILE');

				$htmlAccordions = HTMLHelper::_(
					'bootstrap.startAccordion', 'dbg_query_' . $id, array(
						'active' => $info[$id]->hasWarnings ? ('dbg_query_explain_' . $id) : '',
					)
				);

				$htmlAccordions .= HTMLHelper::_('bootstrap.addSlide', 'dbg_query_' . $id, Text::_('PLG_DEBUG_EXPLAIN'), 'dbg_query_explain_' . $id)
					. $info[$id]->explain
					. HTMLHelper::_('bootstrap.endSlide');

				$htmlAccordions .= HTMLHelper::_('bootstrap.addSlide', 'dbg_query_' . $id, $title, 'dbg_query_profile_' . $id)
					. $htmlProfile
					. HTMLHelper::_('bootstrap.endSlide');

				// Call stack and back trace.
				if (isset($callStacks[$id]))
				{
					$htmlAccordions .= HTMLHelper::_('bootstrap.addSlide', 'dbg_query_' . $id, Text::_('PLG_DEBUG_CALL_STACK'), 'dbg_query_callstack_' . $id)
						. ContentHTML::renderCallStack($callStacks[$id])
						. HTMLHelper::_('bootstrap.endSlide');
				}

				$htmlAccordions .= HTMLHelper::_('bootstrap.endAccordion');

				$did = md5($query);

				if (isset($duplicates[$did]))
				{
					$dups = array();

					foreach ($duplicates[$did] as $dup)
					{
						if ($dup !== $id)
						{
							$dups[] = '<a class="alert-link" href="#dbg-query-' . ($dup + 1) . '">#' . ($dup + 1) . '</a>';
						}
					}

					$htmlQuery = '<joomla-alert type="danger">' . Text::_('PLG_DEBUG_QUERY_DUPLICATES') . ': ' . implode('&nbsp; ', $dups) . '</joomla-alert>'
						. '<pre class="alert hasTooltip" title="' . HTMLHelper::_('tooltipText', 'PLG_DEBUG_QUERY_DUPLICATES_FOUND') . '">' . $text . '</pre>';
				}
				else
				{
					$htmlQuery = '<pre>' . $text . '</pre>';
				}

				$list[] = '<a name="dbg-query-' . ($id + 1) . '"></a>'
					. $htmlTiming
					. $htmlBar
					. $htmlQuery
					. $htmlAccordions;
			}
			else
			{
				$list[] = '<pre>' . $text . '</pre>';
			}
		}

		$totalTime = 0;

		/*
		foreach (Profiler::getInstance('Application')->getMarks() as $mark)
		{
			$totalTime += $mark->time;
		}
		*/

		if ($totalQueryTime > ($totalTime * 0.25))
		{
			$labelClass = 'badge-danger';
		}
		elseif ($totalQueryTime < ($totalTime * 0.15))
		{
			$labelClass = 'badge-success';
		}
		else
		{
			$labelClass = 'badge-warning';
		}

		$html = array();

		$html[] = '<h4>' . Text::sprintf('PLG_DEBUG_QUERIES_LOGGED', $totalQueries)
			. sprintf(' <span class="badge ' . $labelClass . '">%.2f&nbsp;ms</span>', $totalQueryTime) . '</h4><br>';

		if ($total_duplicates)
		{
			$html[] = '<joomla-alert type="danger">'
				. '<h4>' . Text::sprintf('PLG_DEBUG_QUERY_DUPLICATES_TOTAL_NUMBER', $total_duplicates) . '</h4>';

			foreach ($duplicates as $dups)
			{
				$links = array();

				foreach ($dups as $dup)
				{
					$links[] = '<a class="alert-link" href="#dbg-query-' . ($dup + 1) . '">#' . ($dup + 1) . '</a>';
				}

				$html[] = '<div>' . Text::sprintf('PLG_DEBUG_QUERY_DUPLICATES_NUMBER', \count($links)) . ': ' . implode('&nbsp; ', $links) . '</div>';
			}

			$html[] = '</joomla-alert>';
		}

		$html[] = '<ol><li>' . implode('<hr></li><li>', $list) . '<hr></li></ol>';

		if (!$this->pluginParameters->get('query_types', 1))
		{
			return implode('', $html);
		}

		// Get the totals for the query types.
		$totalSelectQueryTypes = \count($selectQueryTypeTicker);
		$totalOtherQueryTypes  = \count($otherQueryTypeTicker);
		$totalQueryTypes       = $totalSelectQueryTypes + $totalOtherQueryTypes;

		$html[] = '<h4>' . Text::sprintf('PLG_DEBUG_QUERY_TYPES_LOGGED', $totalQueryTypes) . '</h4>';

		if ($totalSelectQueryTypes)
		{
			$html[] = '<h5>' . Text::_('PLG_DEBUG_SELECT_QUERIES') . '</h5>';

			arsort($selectQueryTypeTicker);

			$list = array();

			foreach ($selectQueryTypeTicker as $query => $occurrences)
			{
				$list[] = '<pre>'
					. Text::sprintf('PLG_DEBUG_QUERY_TYPE_AND_OCCURRENCES', ContentHTML::highlightQuery($query, $prefix), $occurrences)
					. '</pre>';
			}

			$html[] = '<ol><li>' . implode('</li><li>', $list) . '</li></ol>';
		}

		if ($totalOtherQueryTypes)
		{
			$html[] = '<h5>' . Text::_('PLG_DEBUG_OTHER_QUERIES') . '</h5>';

			arsort($otherQueryTypeTicker);

			$list = array();

			foreach ($otherQueryTypeTicker as $query => $occurrences)
			{
				$list[] = '<pre>'
					. Text::sprintf('PLG_DEBUG_QUERY_TYPE_AND_OCCURRENCES', ContentHTML::highlightQuery($query, $prefix), $occurrences)
					. '</pre>';
			}

			$html[] = '<ol><li>' . implode('</li><li>', $list) . '</li></ol>';
		}

		return $html;
	}

	/**
	 * Display profile information.
	 *
	 * @param   array  $rawMarks  The profiler marks.
	 *
	 * @return  array
	 *
	 * @since   2.5
	 */
	protected function getProfileReport(array $rawMarks): array
	{
		$html = array();

		if (!$rawMarks)
		{
			$html[] = ContentHTML::noItems();

			return $html;
		}

		$htmlMarks = array();

		$totalTime = 0;
		$totalMem  = 0;
		$marks     = array();

		foreach ($rawMarks as $mark)
		{
			$totalTime += $mark['time'];
			$totalMem  += (float) $mark['memory'];
			$htmlMark  = sprintf(
				Text::_('PLG_DEBUG_TIME')
				. ': <span class="badge badge-secondary label-time">%.2f&nbsp;ms</span> / <span class="badge badge-secondary">%.2f&nbsp;ms</span>'
				. ' '
				. Text::_('PLG_DEBUG_MEMORY')
				. ': <span class="badge badge-secondary badge-memory">%0.3f MB</span> / <span class="badge badge-secondary">%0.2f MB</span>'
				. ' %s: %s',
				$mark['time'],
				$mark['totalTime'],
				$mark['memory'],
				$mark['totalMemory'],
				$mark['prefix'],
				$mark['label']
			);

			$marks[] = (object) array(
				'time'   => $mark['time'],
				'memory' => $mark['memory'],
				'html'   => $htmlMark,
				'tip'    => $mark['label'],
			);
		}

		$avgTime = $totalTime / \count($marks);
		$avgMem  = $totalMem / \count($marks);

		foreach ($marks as $mark)
		{
			if ($mark->time > $avgTime * 1.5)
			{
				$barClass   = 'bg-danger';
				$labelClass = 'badge-danger';
			}
			elseif ($mark->time < $avgTime / 1.5)
			{
				$barClass   = 'bg-success';
				$labelClass = 'badge-success';
			}
			else
			{
				$barClass   = 'bg-warning';
				$labelClass = 'badge-warning';
			}

			if ($mark->memory > $avgMem * 1.5)
			{
				$barClassMem   = 'bg-danger';
				$labelClassMem = 'badge-danger';
			}
			elseif ($mark->memory < $avgMem / 1.5)
			{
				$barClassMem   = 'bg-success';
				$labelClassMem = 'badge-success';
			}
			else
			{
				$barClassMem   = 'bg-warning';
				$labelClassMem = 'badge-warning';
			}

			$barClass    .= " progress-$barClass";
			$barClassMem .= " progress-$barClassMem";

			$bars[] = (object) array(
				'width' => round($mark->time / ($totalTime / 100), 4),
				'class' => $barClass,
				'tip'   => $mark->tip . ' ' . round($mark->time, 2) . ' ms',
			);

			$barsMem[] = (object) array(
				'width' => round((float) $mark->memory / ($totalMem / 100), 4),
				'class' => $barClassMem,
				'tip'   => $mark->tip . ' ' . round($mark->memory, 3) . '  MB',
			);

			$htmlMarks[] = '<div>' . str_replace('badge-time', $labelClass, str_replace('badge-memory', $labelClassMem, $mark->html)) . '</div>';
		}

		$html[] = '<h4>' . Text::_('PLG_DEBUG_TIME') . '</h4>';
		$html[] = ContentHTML::renderBars($bars, 'profile');
		$html[] = '<h4>' . Text::_('PLG_DEBUG_MEMORY') . '</h4>';
		$html[] = ContentHTML::renderBars($barsMem, 'profile');

		$html[] = '<div class="dbg-profile-list">' . implode('', $htmlMarks) . '</div>';

		return $html;
	}
}
