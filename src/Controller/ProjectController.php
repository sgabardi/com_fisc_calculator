<?php
declare(strict_types=1);

namespace FiscCalculator\Component\Fisc_calculator\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

class ProjectController extends BaseController
{
    public function addCase(): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json', true);
        if (!Session::checkToken('post')) {
            echo json_encode(['success'=>false,'message'=>'Invalid token']); $app->close();
        }

        $pid  = (int)$app->input->post->get('project_id');
        $name = trim((string)$app->input->post->get('case_name'));

        try {
            $id = $this->getModel('Project')->createCase($pid, $name);
            echo json_encode(['success'=> (bool)$id, 'case_id'=> (int)$id]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        $app->close();
    }

    public function renameCase(): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json', true);
        if (!Session::checkToken('post')) {
            echo json_encode(['success'=>false,'message'=>'Invalid token']); $app->close();
        }

        $cid  = (int)$app->input->post->get('case_id');
        $name = trim((string)$app->input->post->get('case_name'));

        try {
            $ok = $this->getModel('Project')->updateCaseName($cid, $name);
            echo json_encode(['success'=> (bool)$ok]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        $app->close();
    }

    public function deleteCase(): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json', true);
        if (!Session::checkToken('post')) {
            echo json_encode(['success'=>false,'message'=>'Invalid token']); $app->close();
        }

        $cid = (int)$app->input->post->get('case_id');

        try {
            $ok = $this->getModel('Project')->deleteCase($cid);
            echo json_encode(['success'=> (bool)$ok]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        $app->close();
    }

    public function addItem(): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json', true);
        if (!Session::checkToken('post')) {
            echo json_encode(['success'=>false,'message'=>'Invalid token']); $app->close();
        }

        $caseId    = (int)$app->input->post->get('case_id');
        $productId = (int)$app->input->post->get('product_id');
        $ref       = $app->input->post->getString('ref');
        $title     = $app->input->post->getString('title');

        try {
            $model  = $this->getModel('Project');
            $itemId = $productId > 0
                ? $model->addCaseItemByProductId($caseId, $productId)
                : $model->addCaseItemFromCatalog($caseId, $ref, $title);

            $stats = $productId > 0 ? $model->getAggregatesForProduct($productId) : ['min_price'=>0,'max_price'=>0,'mean_price'=>0,'cogs_lc'=>0];

            echo json_encode(['success'=> (bool)$itemId, 'item_id'=> (int)$itemId, 'stats'=>$stats]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        $app->close();
    }

    public function saveFreePrice(): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json', true);
        if (!Session::checkToken('post')) {
            echo json_encode(['success'=>false,'message'=>'Invalid token']); $app->close();
        }

        $itemId = (int)$app->input->post->get('item_id');
        $field  = $app->input->post->getCmd('field');
        $value  = (float)$app->input->post->getString('value','0');

        try {
            $ok = $this->getModel('Project')->updateFreePrice($itemId, $field, $value);
            echo json_encode(['success'=> (bool)$ok]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        $app->close();
    }
}
