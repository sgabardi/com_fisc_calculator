<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');
?>

<form action="<?php echo Route::_('index.php?option=com_fisc_calculator&view=projectform'); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">

    <fieldset>
        <?php echo $this->form->renderField('customer_id'); ?>
        <?php echo $this->form->renderField('description'); ?>
        <?php echo $this->form->renderField('assigned_user_id'); ?>
    </fieldset>

    <div>
        <button type="submit" class="btn btn-primary validate">Speichern</button>
    </div>
	<input type="hidden" name="jform[id]" value="<?php echo (int) $this->form->getValue('id'); ?>">

    <input type="hidden" name="task" value="projectform.save" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
