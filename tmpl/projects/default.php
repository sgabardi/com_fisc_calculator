<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

?>
<div class="row">
	<div class="col-md-8">
		<h3>Preisberechnungen</h3>
	</div>
	
	<div class="col-md-4" style="text-align:right">
		<a href="<?= Route::_('index.php?option=com_fisc_calculator&view=projectform&layout=edit'); ?>" class="btn btn-success btn-small"><i class="icon-plus"></i>
			hinzufügen</a>
		</div>
</div><? if (empty($this->items)) {
    echo "<p>Keine Projekte gefunden.</p>";
    return;
} ?>
<div class="row">
    <?php foreach ($this->items as $item) : ?>		<div class="card" id="item3">
				<div class="card-body">
					<div class="row">
						<div class="col-md-5"><small>Projektname</small>
							
							<h6>
								<a href="<?php echo Route::_('index.php?option=com_fisc_calculator&view=project&id=' . (int) $item->id); ?>">
									<?php echo htmlspecialchars($item->customer_name); ?></a></h6>
						</div>
						
						<div class="col-md-5"><small>Projektbeschreibung</small><br />
							
							<?php echo htmlspecialchars($item->description); ?></div>
						
				
								
						<div class="col-md-2">							<a href="<?php echo Route::_('index.php?option=com_fisc_calculator&view=projectpdf&reporttype=1&id=' . (int) $item->id); ?>" target="_blank" class="btn btn-primary btn-outer">
							Berechnungsblatt Kunde</i>
							</a>							<a href="<?php echo Route::_('index.php?option=com_fisc_calculator&view=projectpdf&reporttype=2&id=' . (int) $item->id); ?>" target="_blank" class="btn btn-primary btn-outer">
							Berechnungsblatt intern</i>
							</a></div>
					</div>
				</div>
				
				
			</div>    <?php endforeach; ?>
</div>
