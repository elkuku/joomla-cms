<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Layout variables
 * ---------------------
 * None
 */

Factory::getDocument()->addScriptDeclaration(
	'
		jQuery(document).ready(function($){
			if ($("#batch-category-id").length){var batchSelector = $("#batch-category-id");}
			if ($("#batch-menu-id").length){var batchSelector = $("#batch-menu-id");}
			if ($("#batch-position-id").length){var batchSelector = $("#batch-position-id");}
			if ($("#batch-copy-move").length && batchSelector) {
				$("#batch-copy-move").hide();
				batchSelector.on("change", function(){
					if (batchSelector.val() != 0 || batchSelector.val() != "") {
						$("#batch-copy-move").show();
					} else {
						$("#batch-copy-move").hide();
					}
				});
			}
		});
			'
);
?>
<label id="batch-language-lbl" for="batch-language-id" class="modalTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'JLIB_HTML_BATCH_LANGUAGE_LABEL', 'JLIB_HTML_BATCH_LANGUAGE_LABEL_DESC'); ?>">
	<?php echo Text::_('JLIB_HTML_BATCH_LANGUAGE_LABEL'); ?>
</label>
<select name="batch[language_id]" class="inputbox" id="batch-language-id">
	<option value=""><?php echo Text::_('JLIB_HTML_BATCH_LANGUAGE_NOCHANGE'); ?></option>
	<?php echo HTMLHelper::_('select.options', HTMLHelper::_('adminlanguage.existing', true, true), 'value', 'text'); ?>
</select>
