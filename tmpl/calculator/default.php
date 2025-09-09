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

$canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_fisc_calculator.' . $this->item->id);

if (!$canEdit && Factory::getApplication()->getIdentity()->authorise('core.edit.own', 'com_fisc_calculator' . $this->item->id))
{
	$canEdit = Factory::getApplication()->getIdentity()->id == $this->item->created_by;
}$wa = $this->document->getWebAssetManager();	$dbField = '';
foreach($this->calcfields as $cf) {	$dbField.= "{ name: '".$cf->title."', positive: ".$cf->positive.", timeDependent: ".$cf->timedependent.", percent: ".$cf->percentage.", value: ".$cf->groundvalue." },";}$wa->addInlineScript("
var databaseFields = [
    ".$dbField."
];

// Funktion zum Generieren der Formularfelder
function generateFormFields() {
    var form = jQuery('#formfields');
    form.empty();
    fappend = '<div class=\"row\">';
    jQuery.each(databaseFields, function(index, field) {    	var printPercent = (field.percent == 1 ? ' in %' : '');		var printTime = (field.timeDependent == 1 ? ' pro Monat' : '');
        var inputType = field.timeDependent ? 'number' : 'number';
        var fieldNameLowerCase = field.name.toLowerCase().replace(/ /g, '_');
        fappend += '<div class=\"col-md-6\"><label class=\"control-label\">' + field.name + printPercent + printTime+ ': </label></div><div class=\"col-md-6\"><input class=\"calcinput\" type=\"' + inputType + '\" name=\"' + fieldNameLowerCase + '\" value=\"' + field.value + '\" id=\"' + fieldNameLowerCase + '\" /></div>';
    });
    fappend += '</div>';
    form.append(fappend);    // Füge ein Event-Handling für die Eingabefelder hinzu
    form.find('input.calcinput').on('change', function() {
        calculateSums();
    });
}

function calculateSums() {
    var resultOutput = '<div class=\"row\">';

    // Datumsdifferenz in Monaten berechnen
    var startDateStr = jQuery('#startdatum').val();
    var endDateStr = jQuery('#enddatum').val();
    var startDate = new Date(startDateStr);
    var endDate = new Date(endDateStr);
    var timeDifferenceInMonths = (endDate.getFullYear() - startDate.getFullYear()) * 12 + endDate.getMonth() - startDate.getMonth() + 1;    if (isNaN(timeDifferenceInMonths)) {
    	timeDifferenceInMonths = 1;
	}
    resultOutput += '<div class=\"col-md-4\"><label class=\"control-label\">Differenz in Monaten</label></div><div class=\"col-md-4\"><input class=\"calcresult\" type=\"text\" value=\"' + timeDifferenceInMonths.toLocaleString() + '\" readonly/></div><div class=\"col-md-4\"></div><hr />';

    // Iteriere über die databaseFields und berechne die Werte
    var totalResult = 0;
    jQuery.each(databaseFields, function (index, field) {
        var fieldNameLowerCase = field.name.toLowerCase().replace(/ /g, '_');
        var fieldValue = parseFloat(jQuery('#' + fieldNameLowerCase).val()) || 0;

        // Berechne den Wert basierend auf timeDependent und percent
        var calculatedValue = (field.timeDependent ? fieldValue * timeDifferenceInMonths : fieldValue);

        // Wenn positive: 0 und percent: 0, berechne den Prozentwert und ziehe diesen vom originalen Wert ab
        if (field.positive === 0 && field.percent === 0) {
            var percentValue = (field.percent / 100) * fieldValue;
            calculatedValue = fieldValue - percentValue;
        }

        // Wenn positive: 0 und percent: 0, ziehe den Wert von der Summe ab
        if (field.positive === 0 && field.percent === 0) {
            totalResult -= calculatedValue;
        } else if(field.percent === 1) {
            totalResult -= (totalResult/100)*calculatedValue;
        } else {
            totalResult += calculatedValue;
        }

        // Formatieren Sie die Zahlen mit Tausendertrennzeichen
        var formattedValue = calculatedValue.toLocaleString();
        var formattedTotal = totalResult.toLocaleString();

		var printPercent = (field.percent == 1 ? ' in %' : '');
        resultOutput += '<div class=\"col-md-4\"><label class=\"control-label\">' + field.name + printPercent + '</label></div><div class=\"col-md-4\"><input class=\"calcresult\" type=\"text\" value=\"' + formattedValue + '\" readonly/></div><div class=\"col-md-4 midresult\">' + formattedTotal + '</div><hr />';
    });

    resultOutput += '<h4 class=\"calcfullresult\">' + totalResult.toLocaleString() + '</h4>';
    jQuery('#result').html(resultOutput);
}
// Initialisierung
jQuery(document).ready(function() {
    generateFormFields();
	calculateSums();
    // Hier kannst du die Berechnung der Summen auslösen (zum Beispiel beim Klick auf einen Button)
    jQuery('#enddatum').change(function() {
         calculateSums();
    });
});
");


?><div class="row">	<div class="col-md-8">		<h5><?php echo $this->item->title; ?></h5>	</div></div><div class="row">	<div class="col-md-7">		<div class="card">			<div class="card-header">				<h6>Beschreibung</h6>			</div>						<div class="card-body">				<?php echo nl2br($this->item->description); ?>			</div>		</div>				<div class="card">			<div class="card-header">				<h6>Ergebnis</h6>			</div>						<div class="card-body">				<div id="result"></div>			</div>		</div>	</div>		<div class="col-md-5">		<div class="card">			<div class="card-header">				<h6>Dateneingabe</h6>			</div>			<div class="card-body">				<form id="calculatorForm">					<? if($this->item->timedependent == 1): ?>					<div class="row">						<div class="col-md-6">							<label class="control-label">Startdatum</label>							<input type="date" class="calcinput" id="startdatum" />						</div>						<div class="col-md-6">							<label class="control-label">Enddatum</label>							<input type="date" class="calcinput" id="enddatum" />						</div>					</div>					<? endif; ?>					<hr />					<div id="formfields"></div>				</form>				<hr />			</div>		</div>	</div></div>

<?php $canCheckin = Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_fisc_calculator.' . $this->item->id) || $this->item->checked_out == Factory::getApplication()->getIdentity()->id; ?>
	<?php if($canEdit && $this->item->checked_out == 0): ?>

	<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_fisc_calculator&task=calculator.edit&id='.$this->item->id); ?>"><?php echo Text::_("COM_FISC_CALCULATOR_EDIT_ITEM"); ?></a>
	<?php elseif($canCheckin && $this->item->checked_out > 0) : ?>
	<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_fisc_calculator&task=calculator.checkin&id=' . $this->item->id .'&'. Session::getFormToken() .'=1'); ?>"><?php echo Text::_("JLIB_HTML_CHECKIN"); ?></a>

<?php endif; ?>

<?php if (Factory::getApplication()->getIdentity()->authorise('core.delete','com_fisc_calculator.calculator.'.$this->item->id)) : ?>

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
                'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_fisc_calculator&task=calculator.remove&id=' . $this->item->id, false, 2) .'" class="btn btn-danger">' . Text::_('COM_FISC_CALCULATOR_DELETE_ITEM') .'</a>'
            ),
            Text::sprintf('COM_FISC_CALCULATOR_DELETE_CONFIRM', $this->item->id)
        ); ?>

<?php endif; ?>