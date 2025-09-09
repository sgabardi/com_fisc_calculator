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
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Session\Session;
use \Joomla\CMS\User\UserFactoryInterface;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$user       = Factory::getApplication()->getIdentity();
$userId     = $user->get('id');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_fisc_calculator') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'calculatorform.xml');
$canEdit    = $user->authorise('core.edit', 'com_fisc_calculator') && file_exists(JPATH_COMPONENT .  DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'calculatorform.xml');
$canCheckin = $user->authorise('core.manage', 'com_fisc_calculator');
$canChange  = $user->authorise('core.edit.state', 'com_fisc_calculator');
$canDelete  = $user->authorise('core.delete', 'com_fisc_calculator');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_fisc_calculator.list');
?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post"
	  name="adminForm" id="adminForm">	<div class="row">		<div class="col-md-8">			<h5>Berechnungstypen</h5>		</div>				<div class="col-md-4">			<?php if ($canCreate) : ?>
			<a href="<?php echo Route::_('index.php?option=com_fisc_calculator&task=calculatorform.edit&id=0', false, 0); ?>"
			   class="btn btn-success btn-small"><i
					class="icon-plus"></i>
				<?php echo Text::_('COM_FISC_CALCULATOR_ADD_ITEM'); ?></a>
		<?php endif; ?>		</div>	</div>		<div class="row">		<div class="col-md-4">			<div class="card">				<div class="card-header">					<h6>Filter</h6>				</div>								<div class="card-body">					<?php if(!empty($this->filterForm)) { echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); } ?>				</div>			</div>		</div>				<div class="col-md-8">			<div class="card">				<div class="card-header">					<h6>Rechner</h6>				</div>								<div class="card-body">					<ul class="repair_items_list">
												<?php foreach ($this->items as $i => $item) : ?>						<li style="border-left: 6px solid #64C8C9">							<b><a href="<?php echo Route::_('index.php?option=com_fisc_calculator&view=calculator&id='.(int) $item->id); ?>">
							<?php echo $this->escape($item->title); ?></a></b><br /><hr />						<?php echo $item->description; ?></li>						<?php endforeach; ?>					</ul>				</div>			</div>		</div>	</div>		<input type="hidden" name="task" value="" />		<input type="hidden" name="boxchecked" value="0" />		<input type="hidden" name="filter_order" value="" />		<input type="hidden" name="filter_order_Dir" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php
	if($canDelete) {
		$wa->addInlineScript("
			jQuery(document).ready(function () {
				jQuery('.delete-button').click(deleteItem);
			});

			function deleteItem() {

				if (!confirm(\"" . Text::_('COM_FISC_CALCULATOR_DELETE_MESSAGE') . "\")) {
					return false;
				}
			}
		", [], [], ["jquery"]);
	}
?>