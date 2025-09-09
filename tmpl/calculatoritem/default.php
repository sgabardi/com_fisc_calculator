<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Fisc_calculator
 * @author     Stefan Gabardi <stefan@gabardi.at>
 * @copyright  2024 Stefan Gabardi
 * @license    copyrighted
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

$canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_fisc_calculator');

if (!$canEdit && Factory::getApplication()->getIdentity()->authorise('core.edit.own', 'com_fisc_calculator'))
{
	$canEdit = Factory::getApplication()->getIdentity()->id == $this->item->created_by;
}
?>

<div class="item_fields">

	<table class="table">
		

		<tr>
			<th><?php echo Text::_('COM_FISC_CALCULATOR_FORM_LBL_CALCULATORITEM_TITLE'); ?></th>
			<td><?php echo $this->item->title; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_FISC_CALCULATOR_FORM_LBL_CALCULATORITEM_DESCRIPTION'); ?></th>
			<td><?php echo nl2br($this->item->description); ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_FISC_CALCULATOR_FORM_LBL_CALCULATORITEM_POSITIVE'); ?></th>
			<td><?php echo $this->item->positive; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_FISC_CALCULATOR_FORM_LBL_CALCULATORITEM_GROUNDVALUE'); ?></th>
			<td><?php echo $this->item->groundvalue; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_FISC_CALCULATOR_FORM_LBL_CALCULATORITEM_TIMEDEPENDENT'); ?></th>
			<td><?php echo $this->item->timedependent; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_FISC_CALCULATOR_FORM_LBL_CALCULATORITEM_CALCULATOR_ID'); ?></th>
			<td><?php echo $this->item->calculator_id; ?></td>
		</tr>

	</table>

</div>

<?php $canCheckin = Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_fisc_calculator.' . $this->item->id) || $this->item->checked_out == Factory::getApplication()->getIdentity()->id; ?>
	<?php if($canEdit && $this->item->checked_out == 0): ?>

	<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_fisc_calculator&task=calculatoritem.edit&id='.$this->item->id); ?>"><?php echo Text::_("COM_FISC_CALCULATOR_EDIT_ITEM"); ?></a>
	<?php elseif($canCheckin && $this->item->checked_out > 0) : ?>
	<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_fisc_calculator&task=calculatoritem.checkin&id=' . $this->item->id .'&'. Session::getFormToken() .'=1'); ?>"><?php echo Text::_("JLIB_HTML_CHECKIN"); ?></a>

<?php endif; ?>

<?php if (Factory::getApplication()->getIdentity()->authorise('core.delete','com_fisc_calculator.calculatoritem.'.$this->item->id)) : ?>

	<a class="btn btn-danger" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal">
		<?php echo Text::_("COM_FISC_CALCULATOR_DELETE_ITEM"); ?>
	</a>

	<?php echo HTMLHelper::_(
                                    'bootstrap.renderModal',
                                    'deleteModal',
                                    array(
                                        'title'  => Text::_('COM_FISC_CALCULATOR_DELETE_ITEM'),
                                        'height' => '50%',
                                        'width'  => '20%',
                                        
                                        'modalWidth'  => '50',
                                        'bodyHeight'  => '100',
                                        'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_fisc_calculator&task=calculatoritem.remove&id=' . $this->item->id, false, 2) .'" class="btn btn-danger">' . Text::_('COM_FISC_CALCULATOR_DELETE_ITEM') .'</a>'
                                    ),
                                    Text::sprintf('COM_FISC_CALCULATOR_DELETE_CONFIRM', $this->item->id)
                                ); ?>

<?php endif; ?>