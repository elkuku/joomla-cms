<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

class ContentHTMLHelper
{
	/**
	 * Simple highlight for SQL queries.
	 *
	 * @param   string $query  The query to highlight.
	 * @param   string $prefix The current table prefix.
	 *
	 * @return  string
	 *
	 * @since   2.5
	 */
	public static function highlightQuery($query, $prefix): string
	{
		$newlineKeywords = '#\b(FROM|LEFT|INNER|OUTER|WHERE|SET|VALUES|ORDER|GROUP|HAVING|LIMIT|ON|AND|CASE)\b#i';

		$query = htmlspecialchars($query, ENT_QUOTES);

		$query = preg_replace($newlineKeywords, '<br>&#160;&#160;\\0', $query);

		$regex = array(

			// Tables are identified by the prefix.
			'/(=)/'                          => '<b class="dbg-operator">$1</b>',

			// All uppercase words have a special meaning.
			'/(?<!\w|>)([A-Z_]{2,})(?!\w)/x' => '<span class="dbg-command">$1</span>',

			// Tables are identified by the prefix.
			'/(' . $prefix . '[a-z_0-9]+)/'  => '<span class="dbg-table">$1</span>',

		);

		$query = preg_replace(array_keys($regex), array_values($regex), $query);

		$query = str_replace('*', '<b style="color: red;">*</b>', $query);

		return $query;
	}

	/**
	 * Renders call stack and back trace in HTML.
	 *
	 * @param   array $callStack The call stack and back trace array.
	 *
	 * @return  string  The call stack and back trace in HMTL format.
	 *
	 * @since   3.5
	 */
	public static function renderCallStack(array $callStack = array()): string
	{
		$html = '';

		if ($callStack !== null)
		{
			$html .= '<div>';
			$html .= '<table class="table dbg-query-table">';
			$html .= '<thead>';
			$html .= '<tr>';
			$html .= '<th>#</th>';
			$html .= '<th>' . Text::_('PLG_DEBUG_CALL_STACK_CALLER') . '</th>';
			$html .= '<th>' . Text::_('PLG_DEBUG_CALL_STACK_FILE_AND_LINE') . '</th>';
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$count = \count($callStack);

			foreach ($callStack as $call)
			{
				// Don't back trace log classes.
				if (isset($call['class']) && strpos($call['class'], 'Log') !== false)
				{
					$count--;
					continue;
				}

				$html .= '<tr>';

				$html .= '<td>' . $count . '</td>';

				$html .= '<td>';

				if (isset($call['class']))
				{
					// If entry has Class/Method print it.
					$html .= htmlspecialchars($call['class'] . $call['type'] . $call['function']) . '()';
				}
				elseif (isset($call['args']))
				{
					// If entry has args is a require/include.
					$html .= htmlspecialchars($call['function']) . ' ' . self::formatLink($call['args'][0]);
				}
				else
				{
					// It's a function.
					$html .= htmlspecialchars($call['function']) . '()';
				}

				$html .= '</td>';

				$html .= '<td>';

				// If entry doesn't have line and number the next is a call_user_func.
				if (!isset($call['file']) && !isset($call['line']))
				{
					$html .= Text::_('PLG_DEBUG_CALL_STACK_SAME_FILE');
				}
				// If entry has file and line print it.
				else
				{
					$html .= self::formatLink(htmlspecialchars($call['file']), htmlspecialchars($call['line']));
				}

				$html .= '</td>';

				$html .= '</tr>';
				$count--;
			}

			$html .= '</tbody>';
			$html .= '</table>';
			$html .= '</div>';

			if (!ini_get('xdebug.file_link_format'))
			{
				$html .= '<div>[<a href="https://xdebug.org/docs/all_settings#file_link_format" target="_blank" rel="noopener noreferrer">';
				$html .= Text::_('PLG_DEBUG_LINK_FORMAT') . '</a>]</div>';
			}
		}

		return $html;
	}

	/**
	 * Render the backtrace.
	 *
	 * @param   \Exception  $error  The Exception object to be rendered.
	 *
	 * @return  string     Rendered backtrace.
	 *
	 * @since   2.5
	 */
	public static function renderBacktrace($error): string
	{
		return LayoutHelper::render('joomla.error.backtrace', array('backtrace' => $error->getTrace()));
	}

	/**
	 * Render an HTML table based on a multi-dimensional array.
	 *
	 * @param   array    $table         An array of tabular data.
	 * @param   boolean  &$hasWarnings  Changes value to true if warnings are displayed, otherwise untouched
	 *
	 * @return  string
	 *
	 * @since   3.1.2
	 */
	public static function tableToHtml($table, &$hasWarnings): string
	{
		if (!$table)
		{
			return null;
		}

		$html = array();

		$html[] = '<table class="table dbg-query-table">';
		$html[] = '<thead>';
		$html[] = '<tr>';

		foreach (array_keys($table[0]) as $k)
		{
			$html[] = '<th>' . htmlspecialchars($k) . '</th>';
		}

		$html[]    = '</tr>';
		$html[]    = '</thead>';
		$html[]    = '<tbody>';
		$durations = array();

		foreach ($table as $tr)
		{
			if (isset($tr['Duration']))
			{
				$durations[] = $tr['Duration'];
			}
		}

		rsort($durations, SORT_NUMERIC);

		foreach ($table as $tr)
		{
			$html[] = '<tr>';

			foreach ($tr as $k => $td)
			{
				if ($td === null)
				{
					// Display null's as 'NULL'.
					$td = 'NULL';
				}

				// Treat special columns.
				if ($k === 'Duration')
				{
					if ($td >= 0.001 && ($td === $durations[0] || (isset($durations[1]) && $td === $durations[1])))
					{
						// Duration column with duration value of more than 1 ms and within 2 top duration in SQL engine: Highlight warning.
						$html[]      = '<td class="dbg-warning">';
						$hasWarnings = true;
					}
					else
					{
						$html[] = '<td>';
					}

					// Display duration in milliseconds with the unit instead of seconds.
					$html[] = sprintf('%.2f&nbsp;ms', $td * 1000);
				}
				elseif ($k === 'Error')
				{
					// An error in the EXPLAIN query occurred, display it instead of the result (means original query had syntax error most probably).
					$html[]      = '<td class="dbg-warning">' . htmlspecialchars($td);
					$hasWarnings = true;
				}
				elseif ($k === 'key')
				{
					if ($td === 'NULL')
					{
						// Displays query parts which don't use a key with warning:
						$html[]      = '<td><strong>' . '<span class="dbg-warning hasTooltip" title="'
							. HTMLHelper::_('tooltipText', 'PLG_DEBUG_WARNING_NO_INDEX_DESC') . '">'
							. Text::_('PLG_DEBUG_WARNING_NO_INDEX') . '</span>' . '</strong>';
						$hasWarnings = true;
					}
					else
					{
						$html[] = '<td><strong>' . htmlspecialchars($td) . '</strong>';
					}
				}
				elseif ($k === 'Extra')
				{
					$htmlTd = htmlspecialchars($td);

					// Replace spaces with &nbsp; (non-breaking spaces) for less tall tables displayed.
					$htmlTd = preg_replace('/([^;]) /', '\1&nbsp;', $htmlTd);

					// Displays warnings for "Using filesort":
					$htmlTdWithWarnings = str_replace(
						'Using&nbsp;filesort',
						'<span class="dbg-warning hasTooltip" title="'
						. HTMLHelper::_('tooltipText', 'PLG_DEBUG_WARNING_USING_FILESORT_DESC') . '">'
						. Text::_('PLG_DEBUG_WARNING_USING_FILESORT') . '</span>',
						$htmlTd
					);

					if ($htmlTdWithWarnings !== $htmlTd)
					{
						$hasWarnings = true;
					}

					$html[] = '<td>' . $htmlTdWithWarnings;
				}
				else
				{
					$html[] = '<td>' . htmlspecialchars($td);
				}

				$html[] = '</td>';
			}

			$html[] = '</tr>';
		}

		$html[] = '</tbody>';
		$html[] = '</table>';

		return implode('', $html);
	}

	/**
	 * Render the bars.
	 *
	 * @param   array    &$bars  Array of bar data
	 * @param   string   $class  Optional class for items
	 * @param   integer  $id     Id if the bar to highlight
	 *
	 * @return  string
	 *
	 * @since   3.1.2
	 */
	public static function renderBars(&$bars, $class = '', $id = null): string
	{
		$html = array();

		foreach ($bars as $i => $bar)
		{
			if (isset($bar->pre) && $bar->pre)
			{
				$html[] = '<div class="dbg-bar-spacer" style="width:' . $bar->pre . '%;"></div>';
			}

			$barClass = trim('bar dbg-bar progress-bar ' . ($bar->class ?? ''));

			if ($id !== null && $i === $id)
			{
				$barClass .= ' dbg-bar-active';
			}

			$tip = '';

			if (isset($bar->tip) && $bar->tip)
			{
				$barClass .= ' hasTooltip';
				$tip      = HTMLHelper::_('tooltipText', $bar->tip, '', 0);
			}

			$html[] = '<a class="bar dbg-bar ' . $barClass . '" title="' . $tip . '" style="width: '
				. $bar->width . '%;" href="#dbg-' . $class . '-' . ($i + 1) . '"></a>';
		}

		return '<div class="progress dbg-bars dbg-bars-' . $class . '">' . implode('', $html) . '</div>';
	}

	/**
	 * Replaces the Joomla! root with "JROOT" to improve readability.
	 * Formats a link with a special value xdebug.file_link_format
	 * from the php.ini file.
	 *
	 * @param   string $file The full path to the file.
	 * @param   string $line The line number.
	 *
	 * @return  string
	 *
	 * @since   2.5
	 */
	public static function formatLink($file, $line = ''): string
	{
		return HTMLHelper::_('debug.xdebuglink', $file, $line);
	}

	/**
	 * Render a "No Items" element.
	 *
	 * @return string
	 *
	 * @since version
	 */
	public static function noItems(): string
	{
		return '<div class="no-items">' . Text::_('JNONE') . '</div>';
	}
}
