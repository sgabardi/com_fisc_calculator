<?php
namespace FiscCalculator\Component\Fisc_calculator\Site\Controller;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

class ProjectformController extends FormController
{
    public function save($key = null, $urlVar = null)
{
    $app   = \Joomla\CMS\Factory::getApplication();
    $input = $app->input;

    $data = $input->get('jform', [], 'array');

    // Übernahme von sales_path_id, falls gegeben
    $salesPathId = $input->getInt('sales_path_id');
    if ($salesPathId) {
        $data['sales_path_id'] = $salesPathId;
    }

    $model = $this->getModel('Projectform');
    $result = $model->save($data);

    if (!$result) {
        $this->setRedirect(
            \Joomla\CMS\Router\Route::_('index.php?option=com_fisc_calculator&view=projectform&layout=edit&id=' . (int) ($data['id'] ?? 0), false),
            'Fehler beim Speichern: ' . $model->getError(),
            'error'
        );
        return false;
    }

    if ($salesPathId) {
        // Redirect auf salesproject View
        $this->setRedirect(
            \Joomla\CMS\Router\Route::_('index.php?option=com_fisc_salesproject&view=salesproject&id=' . $salesPathId, false),
            'Projekt mit Sales Path gespeichert.'
        );
    } else {
        $this->setRedirect(
            \Joomla\CMS\Router\Route::_('index.php?option=com_fisc_calculator&view=projects', false),
            'Projekt gespeichert.'
        );
    }

    return true;
}

}
