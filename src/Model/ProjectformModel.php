<?php
namespace FiscCalculator\Component\Fisc_calculator\Site\Model;

use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;

class ProjectformModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_fisc_calculator.projectform', 'projectform', [
            'control' => 'jform',
            'load_data' => $loadData
        ]);
    }

    protected function loadFormData()
    {
        return Factory::getApplication()->getUserState('com_fisc_calculator.edit.projectform.data', []);
    }

   public function save($data)
	{
	    $db = Factory::getDbo();
	
	    $id = isset($data['id']) ? (int) $data['id'] : 0;
	    $customerId = (int) ($data['customer_id'] ?? 0);
	    $description = trim($data['description'] ?? '');
	    $userId = (int) ($data['assigned_user_id'] ?? 0);
	
	    if (!$customerId || !$description) {
	        $this->setError('Kunde und Beschreibung sind erforderlich.');
	        return false;
	    }
	
	    try {
	        if ($id > 0) {
	            // UPDATE
	            $query = $db->getQuery(true)
	                ->update('#__fisc_calculator_projects')
	                ->set([
	                    'customer_id = ' . $db->quote($customerId),
	                    'description = ' . $db->quote($description),
	                    'assigned_user_id = ' . $db->quote($userId)
	                ])
	                ->where('id = ' . (int) $id);
	            $db->setQuery($query);
	            $db->execute();
	            return $id;
	        } else {
	            // INSERT
	            $query = $db->getQuery(true)
	                ->insert('#__fisc_calculator_project')
	                ->columns(['customer_id', 'description', 'assigned_user_id'])
	                ->values(
	                    $db->quote($customerId) . ', ' .
	                    $db->quote($description) . ', ' .
	                    $db->quote($userId)
	                );
	            $db->setQuery($query);
	            $db->execute();
	            return $db->insertid();
	        }
	    } catch (\Exception $e) {
	        $this->setError($e->getMessage());
	        return false;
	    }
	}

}
