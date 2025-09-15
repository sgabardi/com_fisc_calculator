<?php
namespace FiscCalculator\Component\Fisc_calculator\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

class ProjectsModel extends ListModel
{
    protected function getListQuery()
    {
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('p.id, p.description, c.name AS customer_name')
              ->from('#__fisc_calculator_project AS p')
              ->leftJoin('#__fisc_customer_facility AS c ON p.customer_id = c.id');

        return $query;
    }
}
