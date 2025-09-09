<?php
namespace FiscCalculator\Component\Fisc_calculator\Site\Model;

use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Factory;

class ProjectModel extends BaseModel
{
    public function getItems($projectId)
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select('e.id, p.ref, p.title, s.sales_lc AS min_price, s.max_price, s.cogs_lc')
            ->from('#__fisc_example AS e')
            ->join('INNER', '#__fisc_product_item AS p ON e.product_item_id = p.id')
            ->join('LEFT', '#__fisc_sales AS s ON p.id = s.product_item_id')
            ->where('e.project_id = ' . (int) $projectId);
        $db->setQuery($query);
        return $db->loadObjectList();
    }
}
