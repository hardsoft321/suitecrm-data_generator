<?php
require_once 'modules/DataGenerator/DataGenerator.php';

/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package data_generator
 */
class DataGeneratorViewList extends SugarView
{
    public $type = 'index';
    public $showTitle = true;
    public $view = 'index';

    function preDisplay()
    {
        $this->tpl = get_custom_file_if_exists('modules/DataGenerator/tpls/index.tpl');
    }

    function display()
    {
        $this->ss->assign('modules', $this->getModulesInfo());
        $this->ss->assign('relationships', $this->getM2MRelationships());
        echo $this->getModuleTitle($this->showTitle);
        echo $this->ss->display($this->tpl);
    }

    protected function _getModuleTitleParams($browserTitle = false)
    {
        global $mod_strings;
        $params = array($this->_getModuleTitleListParam($browserTitle));
        $params[] = $mod_strings['LBL_DATA_GENERATOR_TITLE'];
        return $params;
    }

    protected function getModulesInfo()
    {
        global $beanList, $beanFiles;
        $modules = array();
        foreach($beanList as $module => $class) {
            if(empty($beanFiles[$class]) || !file_exists($beanFiles[$class])) {
                continue;
            }
            require_once($beanFiles[$class]);
            $bean = new $class();
            if(!empty($bean->module_name) && !empty($bean->field_defs['id'])) {
                $modules[$class] = DataGenerator::getModuleInfo($bean);
            }
        }
        ksort($modules);
        return $modules;
    }

    protected function getM2MRelationships()
    {
        global $db;
        $sql = "SELECT * FROM relationships WHERE join_table IS NOT NULL AND deleted = 0 ORDER BY join_table, relationship_name";
        $dbRes = $db->query($sql);
        $relationships = array();
        while($row = $db->fetchByAssoc($dbRes, false)) {
            $relationships[$row['relationship_name']] = DataGenerator::getRelationshipInfo($row);
        }
        ksort($relationships);
        return $relationships;
    }
}
