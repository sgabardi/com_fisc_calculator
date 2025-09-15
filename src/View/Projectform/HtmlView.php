<?php
namespace FiscCalculator\Component\Fisc_calculator\Site\View\Projectform;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $form;

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        parent::display($tpl);
    }
}
