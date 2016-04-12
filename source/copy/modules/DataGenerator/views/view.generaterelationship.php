<?php
require_once 'modules/DataGenerator/DataGenerator.php';

/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package data_generator
 */
class DataGeneratorViewGenerateRelationship extends SugarView
{
    public $type = 'generaterelationship';
    public $showTitle = true;
    public $view = 'GenerateRelationship';

    function preDisplay()
    {
        global $db;
        $this->tpl = get_custom_file_if_exists('modules/DataGenerator/tpls/GenerateRelationship.tpl');
        $relationship_name = $_REQUEST['target_relationship'];
        if(empty($relationship_name)) {
            $this->errors[] = 'Empty target relationship';
        }
        else {
            $sql = "SELECT * FROM relationships WHERE relationship_name = '".$db->quote($relationship_name)."'";
            $dbRes = $db->limitQuery($sql, 0, 1);
            if($row = $db->fetchByAssoc($dbRes, false)) {
                $this->relationship_row = $row;
            }
            else {
                $this->errors[] = 'Not found';
            }
        }
    }

    function display()
    {
        if(!empty($this->errors) || empty($this->relationship_row)) {
            return;
        }
        $relationshipInfo = DataGenerator::getRelationshipInfo($this->relationship_row);
        $this->ss->assign('rel_info', $relationshipInfo);
        $fieldsInfo = DataGenerator::getRelationshipFieldsInfo($this->relationship_row);
        $this->ss->assign('fields_info', $fieldsInfo);
        echo $this->getModuleTitle($this->showTitle);
        echo $this->ss->display($this->tpl);
    }

    protected function _getModuleTitleParams($browserTitle = false)
    {
        global $mod_strings;
        $params = array($this->_getModuleTitleListParam($browserTitle));
        $params[] = !empty($this->relationship_row) ? "Связь ".$this->relationship_row['relationship_name'] : '-';
        return $params;
    }
}
