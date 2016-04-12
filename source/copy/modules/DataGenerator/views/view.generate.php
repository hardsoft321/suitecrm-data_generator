<?php
require_once 'modules/DataGenerator/DataGenerator.php';

/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package data_generator
 */
class DataGeneratorViewGenerate extends SugarView
{
    public $type = 'generate';
    public $showTitle = true;
    public $view = 'Generate';

    function preDisplay()
    {
        global $beanList, $beanFiles;
        $this->tpl = get_custom_file_if_exists('modules/DataGenerator/tpls/Generate.tpl');
        $module = $_REQUEST['target_module'];
        if(empty($beanList[$module])) {
            $this->errors[] = 'Unknown module';
        }
        else {
            $class = $beanList[$module];
            require_once($beanFiles[$class]);
            $this->target_bean = new $class();
        }
    }

    function display()
    {
        if(!empty($this->errors) || empty($this->target_bean)) {
            return;
        }
        $genClass = 'DataGenerator';
        $genClassFile = 'custom/modules/DataGenerator/'.$this->target_bean->module_name.'DataGenerator.php';
        if(file_exists($genClassFile)) {
            require_once $genClassFile;
            $genClass = $this->target_bean->module_name.'DataGenerator';
        }
        elseif(file_exists('custom/modules/DataGenerator/CustomDataGenerator.php')) {
            require_once 'custom/modules/DataGenerator/CustomDataGenerator.php';
            $genClass = 'CustomDataGenerator';
        }
        $moduleInfo = $genClass::getModuleInfo($this->target_bean);
        $this->ss->assign('module_info', $moduleInfo);
        $fieldsInfo = $genClass::getFieldsInfo($this->target_bean);
        $this->ss->assign('fields_info', $fieldsInfo);
        echo $this->getModuleTitle($this->showTitle);
        echo $this->ss->display($this->tpl);
    }

    protected function _getModuleTitleParams($browserTitle = false)
    {
        global $mod_strings;
        $params = array($this->_getModuleTitleListParam($browserTitle));
        $params[] = !empty($this->target_bean) ? "<a href=\"index.php?module={$this->target_bean->module_name}&action=index\">{$this->target_bean->module_name}</a>" : '-';
        return $params;
    }
}
