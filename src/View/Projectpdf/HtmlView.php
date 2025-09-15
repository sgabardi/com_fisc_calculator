<?php
namespace FiscCalculator\Component\Fisc_calculator\Site\View\Projectpdf;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected int $projectId = 0;
    protected $project;
    protected array $cases = [];
    protected array $caseItems = [];
    protected array $products = [];

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->projectId = (int) $app->input->getInt('id');

        /** @var \FiscCalculator\Component\Fisc_calculator\Site\Model\ProjectModel $model */
        $model = $this->getModel(); // <- WICHTIG: Model holen, sonst $model = null

        if (!$model) {
            throw new \RuntimeException('Project model not found (getModel() returned null).');
        }

        // Projektkopf laden (Name, Kunde, Beschreibung)
        $this->project = $model->getProject($this->projectId);

        // Fälle + Items
        $this->cases = $model->getProjectCases($this->projectId);
        $this->caseItems = [];
        foreach ($this->cases as $c) {
            $cid = (int) $c->id;
            $this->caseItems[$cid] = $model->getItemsByCase($cid);
        }

        // Produktliste fürs Dropdown
        $this->products = $model->getProductOptions(0);

        parent::display($tpl);
    }
}
