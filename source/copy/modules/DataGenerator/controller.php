<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package data_generator
 */
require_once('include/MVC/Controller/SugarController.php');
require_once 'modules/DataGenerator/DataGenerator.php';

class DataGeneratorController extends SugarController
{
    public function preProcess()
    {
        $bean = new DataGenerator();
        if(!$bean->ACLAccess('access')) {
            ACLController::displayNoAccess(true);
            sugar_cleanup(true);
        }
        parent::preProcess();
    }

    protected function action_GenerateView()
    {
        $this->view = 'generate';
    }

    protected function action_GenerateRelationshipView()
    {
        $this->view = 'generaterelationship';
    }

    protected function action_Generate()
    {
        global $beanList, $beanFiles;
        $module = $_POST['target_module'];
        if(empty($beanList[$module])) {
            SugarApplication::appendErrorMessage('Unknown module');
            $this->set_redirect("index.php?module=DataGenerator");
            return;
        }
        if(!set_time_limit(0)) {
            $GLOBALS['log']->error("Can't set time limit");
        }
        $class = $beanList[$module];
        require_once($beanFiles[$class]);
        $bean = new $class();
        DataGenerator::generate($bean, array(
            'add_count' => (int)$_POST['add_count'],
        ));
        $this->set_redirect("index.php?module=DataGenerator&action=GenerateView&target_module=".$bean->module_name);
    }

    protected function action_GenerateRelationship()
    {
        global $db;
        $relationship_name = $_POST['target_relationship'];
        $sql = "SELECT * FROM relationships WHERE relationship_name = '".$db->quote($relationship_name)."'";
        $dbRes = $db->limitQuery($sql, 0, 1);
        $relationship_row = $db->fetchByAssoc($dbRes, false);
        if(empty($relationship_row)) {
            SugarApplication::appendErrorMessage('Unknown relationship');
            $this->set_redirect("index.php?module=DataGenerator");
            return;
        }
        if(!set_time_limit(0)) {
            $GLOBALS['log']->error("Can't set time limit");
        }
        DataGenerator::generateM2MRelationships($relationship_row, array(
            'add_count' => (int)$_POST['add_count'],
        ));
        $this->set_redirect("index.php?module=DataGenerator&action=GenerateRelationshipView&target_relationship=".$relationship_row['relationship_name']);
    }
}
