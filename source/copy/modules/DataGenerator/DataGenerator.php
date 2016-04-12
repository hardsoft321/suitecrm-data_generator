<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package data_generator
 */
class DataGenerator extends SugarBean
{
    public $object_name = 'DataGenerator';
    public $module_dir = 'DataGenerator';
    public static $ID_PREFIX = 'dgnrt'; //префикс не должен быть пустым или совпадать с /[A-Fa-f0-9\-]*/, так как по префиксу работает удаление
    public static $ALPHABET = '""\'\'-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZйцукенгшщзхъфывапролджэячсмитьбюЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ         ';

    public function ACLAccess($view,$is_owner='not_set')
    {
        return $GLOBALS['current_user']->isAdmin();
    }

    public static function getModuleInfo($bean)
    {
        global $db;
        $mod = array();
        $mod['module_name'] = $bean->module_name;
        $mod['object_name'] = $bean->getObjectName();
        $table_name = $bean->getTableName();
        if(!empty($table_name)) {
            $mod['table_name'] = $table_name;
            $sql = "SELECT count(*) AS count FROM $table_name";
            $dbRes = $db->query($sql);
            if($row = $db->fetchByAssoc($dbRes, false)) {
                $mod['count'] = $row['count'];
            }
            if(!empty($bean->field_defs['deleted'])) {
                $sql = "SELECT count(*) AS count FROM $table_name WHERE deleted = 0";
                $dbRes = $db->query($sql);
                if($row = $db->fetchByAssoc($dbRes, false)) {
                    $mod['count_not_deleted'] = $row['count'];
                }
            }
            $sql = "SELECT count(*) AS count FROM $table_name WHERE id LIKE '".self::$ID_PREFIX."%'";
            $dbRes = $db->query($sql);
            if($row = $db->fetchByAssoc($dbRes, false)) {
                $mod['count_generated'] = $row['count'];
            }
            if(!empty($bean->field_defs['deleted'])) {
                $sql = "SELECT count(*) AS count FROM $table_name WHERE id LIKE '".self::$ID_PREFIX."%' AND deleted = 0";
                $dbRes = $db->query($sql);
                if($row = $db->fetchByAssoc($dbRes, false)) {
                    $mod['count_generated_not_deleted'] = $row['count'];
                }
            }
            $mod['id_prefix'] = self::$ID_PREFIX;
        }
        return $mod;
    }

    public static function getFieldsInfo($bean)
    {
        $fields = array();
        $viewdefs = null;
        if(file_exists("custom/modules/{$bean->module_name}/metadata/editviewdefs.php")) {
            require "custom/modules/{$bean->module_name}/metadata/editviewdefs.php";
        }
        elseif(file_exists("modules/{$bean->module_name}/metadata/editviewdefs.php")) {
            require "modules/{$bean->module_name}/metadata/editviewdefs.php";
        }
        if(!empty($viewdefs[$bean->module_name]['EditView']['panels'])) {
            foreach($viewdefs[$bean->module_name]['EditView']['panels'] as $panel) {
                foreach($panel as $tr) {
                    foreach($tr as $name) {
                        if(is_array($name)) {
                            if(!empty($name['name'])) {
                                $name = $name['name'];
                            }
                        }
                        if(!empty($name) && is_string($name) && !empty($bean->field_defs[$name])) {
                            $fields[$name] = array(
                                'name' => $name,
                            );
                        }
                    }
                }
            }
        }

        $basicFields = array(
            'name',
            'date_entered',
            'date_modified',
            'assigned_user_id',
            //'created_by',
            //'modified_user_id',
            'SecurityGroups',
            'aclroles',
        );
        foreach($basicFields as $field) {
            if(!empty($bean->field_defs[$field])) {
                $fields[$field] = array(
                    'name' => $field,
                );
            }
        }

        foreach($fields as $key => $field) {
            if(empty($bean->field_defs[$field['name']])) {
                unset($fields[$key]);
                continue;
            }
            $defs = $bean->field_defs[$field['name']];
            if(!empty($defs['source']) && $defs['type'] != 'link' && $defs['type'] != 'parent') {
                unset($fields[$key]);
                continue;
            }

            $fields[$key]['type'] = $defs['type'];

            if(!empty($defs['len'])) {
                $fields[$key]['len'] = $defs['len'];
            }

            $options = array();
            if(!empty($defs['function'])) {
                if (is_array ($defs['function'])) {
                    $options = is_callable("{$defs['function']['name'][0]}::{$defs['function']['name'][1]}")
                        ? call_user_func_array (
                             "{$defs['function']['name'][0]}::{$defs['function']['name'][1]}",
                             $defs['function']['params'])
                        : array();
                }
                else {
                    $options = $defs['function']($bean, $field['name'], $bean->{$field['name']}, 'list_view');
                }
            }
            else {
                if(!empty($defs['options']) && !empty($GLOBALS['app_list_strings'][$defs['options']])) {
                    $options = $GLOBALS['app_list_strings'][$defs['options']];
                }
            }

            if($defs['type'] == 'phone') {
                $fields[$key]['alphabet'] = '0123456789- ';
            }

            if($defs['type'] == 'user_name') {
                $fields[$key]['alphabet'] = '_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            }

            if($defs['type'] == 'enum' || $defs['type'] == 'radioenum' || $defs['type'] == 'multienum' || $defs['type'] == 'multienumfilter') {
                $fields[$key]['options'] = $options;
            }
            elseif($defs['type'] == 'varchar' || !empty($defs['len'])) {
                $fields[$key]['range_min_len'] = 1;
                $fields[$key]['range_max_len'] = !empty($defs['len']) ? $defs['len'] : 255;
            }
            elseif($defs['type'] == 'text') {
                $fields[$key]['range_min_len'] = 200;
                $fields[$key]['range_max_len'] = 512;
            }
            elseif($defs['type'] == 'bool') {
                $fields[$key]['range_min_len'] = 1;
                $fields[$key]['range_max_len'] = 1;
                $fields[$key]['alphabet'] = '01';
            }
            elseif($defs['type'] == 'datetime' || (!empty($defs['dbType']) && $defs['dbType'] == 'datetime')) {
                $fields[$key]['range_min_datetime'] = date('Y-m-d H:i:s', strtotime('-3year'));
                $fields[$key]['range_max_datetime'] = date('Y-m-d H:i:s');
            }
            elseif($defs['type'] == 'date') {
                $fields[$key]['range_min_date'] = date('Y-m-d', strtotime('-3year'));
                $fields[$key]['range_max_date'] = date('Y-m-d');
            }
            elseif($defs['type'] == 'currency') {
            }
            elseif($defs['type'] == 'relate') {
                if($defs['table'] == 'users') {
                    if(empty($usersModuleInfo)) {
                        $usersModuleInfo = self::getModuleInfo(new User());
                    }
                    $fields[$key]['range_max_relate'] = $usersModuleInfo['count_generated_not_deleted'];
                    $fields[$key]['table'] = $defs['table'];
                    $fields[$key]['generated'] = true;
                    $fields[$key]['empty_chance'] = 0.05;
                }
            }
            elseif($defs['type'] == 'link') {
                if($defs['relationship'] == 'securitygroups_users') {
                    if(empty($groupsModuleInfo)) {
                        $groupBean = self::loadBean('SecurityGroups');
                        $groupsModuleInfo = self::getModuleInfo($groupBean);
                        $groupsTableName = $groupBean->getTableName();
                    }
                    $fields[$key]['link'] = $defs['name'];
                    $fields[$key]['module'] = $defs['module'];
                    $fields[$key]['table'] = $groupsTableName;
                    $fields[$key]['range_max_relate'] = $groupsModuleInfo['count_not_deleted'];
                    $fields[$key]['generated'] = false;
                    $fields[$key]['relate_min_count'] = 1;
                    $fields[$key]['relate_max_count'] = 5;
                    $fields[$key]['empty_chance'] = 0.05;
                }
                elseif($defs['relationship'] == 'acl_roles_users') {
                    if(empty($rolesModuleInfo)) {
                        $roleBean = self::loadBean('ACLRoles');
                        $rolesModuleInfo = self::getModuleInfo($roleBean);
                        $rolesTableName = $roleBean->getTableName();
                    }
                    $fields[$key]['link'] = $defs['name'];
                    $fields[$key]['module'] = 'ACLRoles'; //$defs['module'];
                    $fields[$key]['table'] = $rolesTableName;
                    $fields[$key]['range_max_relate'] = $rolesModuleInfo['count_not_deleted'];
                    $fields[$key]['generated'] = false;
                    $fields[$key]['relate_min_count'] = 1;
                    $fields[$key]['relate_max_count'] = 3;
                    $fields[$key]['empty_chance'] = 0.05;
                }
                else {
                    unset($fields[$key]);
                }
            }
            elseif($defs['type'] == 'parent') {
                $fields[$key]['options'] = array();
                $totalRecordCount = 0;
                foreach($options as $module => $moduleName) {
                    $moduleInfo = self::getModuleInfoCached($module);
                    if($moduleInfo) {
                        $recordCount = $moduleInfo['count_generated_not_deleted'];
                        $fields[$key]['options'][$module] = array(
                            'name' => $module,
                            'count' => $recordCount,
                            'table_name' => $moduleInfo['table_name'],
                        );
                        $totalRecordCount += $recordCount;
                    }
                }
                $fields[$key]['totalRecordCount'] = $totalRecordCount;
                if($totalRecordCount > 0) {
                    foreach($fields[$key]['options'] as $module => $moduleGenInfo) {
                        $fields[$key]['options'][$module]['percent'] = $moduleGenInfo['count'] / $totalRecordCount * 100;
                    }
                }
                $fields[$key]['empty_chance'] = 0.05;
            }
        }
        return $fields;
    }

    public static function generate($bean, $options)
    {
        global $db;
        $class = $bean->getObjectName();
        if($options['add_count'] < 0) {
            $sql = "SELECT id FROM ".$bean->getTableName()." WHERE id LIKE '".self::$ID_PREFIX."%'";
            $dbRes = $db->limitQuery($sql, 0, abs($options['add_count']));
            $beansIds = array();
            while($row = $db->fetchByAssoc($dbRes, false)) {
                $beansIds[] = $row['id'];
            }
            if($bean->module_name == 'Users' && !empty($bean->field_defs['SecurityGroups'])) {
                $db->query("DELETE FROM securitygroups_users WHERE user_id IN ('".implode("','", $beansIds)."')");
            }
            if($bean->module_name == 'Users' && !empty($bean->field_defs['aclroles'])) {
                $db->query("DELETE FROM acl_roles_users WHERE user_id IN ('".implode("','", $beansIds)."')");
            }
            $db->query("DELETE FROM ".$bean->getTableName()." WHERE id IN ('".implode("','", $beansIds)."')");
            return;
        }

        $genClass = 'DataGenerator';
        $genClassFile = 'custom/modules/DataGenerator/'.$bean->module_name.'DataGenerator.php';
        if(file_exists($genClassFile)) {
            require_once $genClassFile;
            $genClass = $bean->module_name.'DataGenerator';
        }
        elseif(file_exists('custom/modules/DataGenerator/CustomDataGenerator.php')) {
            require_once 'custom/modules/DataGenerator/CustomDataGenerator.php';
            $genClass = 'CustomDataGenerator';
        }
        $groups_config = self::changeSugarConfigOptions();
        $fields = $genClass::getFieldsInfo($bean);
        for($i=0; $i<$options['add_count']; $i++) {
            $b = $genClass::fillBean($class, $fields);
            $b->save(false);
            $genClass::generateOne2MRelationships($b, $fields);
        }
        self::restoreSugarConfigOptions($groups_config);
    }

    public static function fillBean($class, $fields)
    {
        $bean = new $class();
        foreach($fields as $field) {
            self::fillBeanValue($bean, $field);
        }
        $bean->id = self::$ID_PREFIX.substr(create_guid(), -(36-strlen(self::$ID_PREFIX)));
        $bean->new_with_id = true;
        $bean->update_date_modified = false;
        $bean->skipValidationHooks = true; //Logic
        $bean->workflowData['skipWFHooks'] = true; //Workflow
        $_POST['allowAllFieldsSave'] = 'true'; //SecurityForms
        return $bean;
    }

    public static function fillBeanValue($bean, $field_gen_defs)
    {
        $fieldName = $field_gen_defs['name'];
        if(!empty($field_gen_defs['empty_chance'])) {
            if(rand()/getrandmax() < $field_gen_defs['empty_chance']) {
                $bean->$fieldName = '';
                return;
            }
        }

        if($field_gen_defs['type'] == 'parent') {
            $totalRecordCount = $field_gen_defs['totalRecordCount'];
            $c = 0;
            $rand = rand(1, $totalRecordCount);
            $chosenModuleInfo = '';
            foreach($field_gen_defs['options'] as $module => $moduleInfo) {
                $c += $moduleInfo['count'];
                if($c >= $rand) {
                    $chosenModuleInfo = $moduleInfo;
                    break;
                }
            }
            if($chosenModuleInfo) {
                $bean->parent_type = $chosenModuleInfo['name'];
                $bean->parent_id = self::selectRandomFromTable($chosenModuleInfo['table_name'], true);
            }
        }
        elseif(!empty($field_gen_defs['options'])) {
            $options = array_keys($field_gen_defs['options']);
            if($field_gen_defs['type'] == 'multienum' || $field_gen_defs['type'] == 'multienumfilter') {
                $keys = array_rand($options, min(count($options), 3));
                $bean->$fieldName = encodeMultienumValue(array_intersect_key($options, $keys));
            }
            else {
                $bean->$fieldName = $options[array_rand($options)];
            }
        }
        elseif(!empty($field_gen_defs['dictionary'])) {
            $dict = self::getDictionaryCached('custom/modules/DataGenerator/'.$field_gen_defs['dictionary']);
            $bean->$fieldName = $dict[array_rand($dict)];
        }
        elseif(!empty($field_gen_defs['dictionary_pattern'])) {
            $str = '';
            foreach(explode(':', $field_gen_defs['dictionary_pattern']) as $part) {
                if(is_file('custom/modules/DataGenerator/'.$part)) {
                    $dict = self::getDictionaryCached('custom/modules/DataGenerator/'.$part);
                    $str .= $dict[array_rand($dict)];
                }
                else {
                    $str .= $part;
                }
            }
            $bean->$fieldName = $str;
        }
        elseif(!empty($field_gen_defs['range_min_len']) && !empty($field_gen_defs['range_max_len'])) {
            $length = rand($field_gen_defs['range_min_len'], $field_gen_defs['range_max_len']);
            $alphabet = !empty($field_gen_defs['alphabet']) ? $field_gen_defs['alphabet'] : self::$ALPHABET;
            $alphabetLength = mb_strlen($alphabet);
            $str = '';
            for ($i = 0; $i < $length; $i++) {
                $str .= mb_substr($alphabet, rand(0, $alphabetLength - 1), 1);
            }
            $bean->$fieldName = $str;
        }
        elseif(!empty($field_gen_defs['range_min_datetime']) && !empty($field_gen_defs['range_max_datetime'])) {
            $bean->$fieldName = date('Y-m-d H:i:s', rand(strtotime($field_gen_defs['range_min_datetime']), strtotime($field_gen_defs['range_max_datetime'])));
        }
        elseif(!empty($field_gen_defs['range_min_date']) && !empty($field_gen_defs['range_max_date'])) {
            $bean->$fieldName = date('Y-m-d', rand(strtotime($field_gen_defs['range_min_date']), strtotime($field_gen_defs['range_max_date'])));
        }
        elseif($field_gen_defs['type'] == 'currency') {
            $bean->$fieldName = rand()/getrandmax()*1000000000;
        }
        elseif(!empty($field_gen_defs['table']) && $field_gen_defs['type'] == 'relate') {
            $bean->$fieldName = self::selectRandomFromTable($field_gen_defs['table'], $field_gen_defs['generated']);
        }
    }

    public static function generateOne2MRelationships($bean, $fields)
    {
        foreach($fields as $field_gen_defs) {
            if(!empty($field_gen_defs['empty_chance'])) {
                if(rand()/getrandmax() < $field_gen_defs['empty_chance']) {
                    return '';
                }
            }
            if($field_gen_defs['type'] == 'link') {
                $link = $field_gen_defs['link'];
                if($bean->load_relationship($link)) {
                    $count = rand($field_gen_defs['relate_min_count'], $field_gen_defs['relate_max_count']);
                    for($i=0; $i<$count; $i++) {
                        $related = self::selectRandomFromTable($field_gen_defs['table'], $field_gen_defs['generated']);
                        $bean->$link->add($related);
                    }
                }
            }
        }
    }

    public static function getRelationshipInfo($data)
    {
        global $db;
        $rel = $data;

        $sql = "SELECT count(*) AS count FROM {$data['join_table']} WHERE 1=1";
        if(!empty($data['relationship_role_column'])) {
            $sql .= " AND {$data['relationship_role_column']} = '{$data['relationship_role_column_value']}'";
        }
        $dbRes = $db->query($sql);
        if($row = $db->fetchByAssoc($dbRes, false)) {
            $rel['count'] = $row['count'];
        }

        $sql = "SELECT count(*) AS count FROM {$data['join_table']} WHERE deleted = 0";
        if(!empty($data['relationship_role_column'])) {
            $sql .= " AND {$data['relationship_role_column']} = '{$data['relationship_role_column_value']}'";
        }
        $dbRes = $db->query($sql);
        if($row = $db->fetchByAssoc($dbRes, false)) {
            $rel['count_not_deleted'] = $row['count'];
        }

        $sql = "SELECT count(*) AS count FROM {$data['join_table']} WHERE {$data['join_key_lhs']} LIKE '".self::$ID_PREFIX."%' OR {$data['join_key_rhs']} LIKE '".self::$ID_PREFIX."%'";
        if(!empty($data['relationship_role_column'])) {
            $sql .= " AND {$data['relationship_role_column']} = '{$data['relationship_role_column_value']}'";
        }
        $dbRes = $db->query($sql);
        if($row = $db->fetchByAssoc($dbRes, false)) {
            $rel['count_generated'] = $row['count'];
        }

        return $rel;
    }

    public static function getRelationshipFieldsInfo($data)
    {
        $fields = array();

        $lhsBean = self::loadBean($data['lhs_module']);
        $lhsModuleInfo = self::getModuleInfo($lhsBean);
        $lhsTableName = $lhsBean->getTableName();
        $key = $data['join_key_lhs'];
        $fields[$key]['name'] = $key;
        $fields[$key]['table'] = $lhsTableName;
        $fields[$key]['select_fields'] = $data['lhs_key'];
        $fields[$key]['range_max_relate'] = $lhsModuleInfo['count_generated_not_deleted'];
        $fields[$key]['generated'] = true;

        $rhsBean = self::loadBean($data['rhs_module']);
        $rhsModuleInfo = self::getModuleInfo($rhsBean);
        $rhsTableName = $rhsBean->getTableName();
        $key = $data['join_key_rhs'];
        $fields[$key]['name'] = $key;
        $fields[$key]['table'] = $rhsTableName;
        $fields[$key]['select_fields'] = $data['rhs_key'];
        $fields[$key]['range_max_relate'] = $rhsModuleInfo['count_generated_not_deleted'];
        $fields[$key]['generated'] = true;

        if(!empty($data['relationship_role_column'])) {
            $key = $data['relationship_role_column'];
            $fields[$key]['name'] = $key;
            $fields[$key]['value'] = $data['relationship_role_column_value'];
        }

        $key = 'date_modified';
        $fields[$key]['name'] = $key;
        $fields[$key]['range_min_datetime'] = date('Y-m-d H:i:s', strtotime('-3year'));
        $fields[$key]['range_max_datetime'] = date('Y-m-d H:i:s');

        return $fields;
    }

    public static function generateM2MRelationships($relationship_row, $options)
    {
        global $db;
        $fieldsInfo = DataGenerator::getRelationshipFieldsInfo($relationship_row);
        if($options['add_count'] < 0) {
            $sql = "SELECT id FROM ".$relationship_row['join_table']." WHERE {$relationship_row['join_key_lhs']} LIKE '".self::$ID_PREFIX."%' OR {$relationship_row['join_key_rhs']} LIKE '".self::$ID_PREFIX."%'";
            if(!empty($relationship_row['relationship_role_column'])) {
                $sql .= " AND {$relationship_row['relationship_role_column']} = '{$relationship_row['relationship_role_column_value']}'";
            }
            $dbRes = $db->limitQuery($sql, 0, abs($options['add_count']));
            $identificators = array();
            while($row = $db->fetchByAssoc($dbRes, false)) {
                $identificators[] = $row['id'];
            }
            $db->query("DELETE FROM {$relationship_row['join_table']} WHERE id IN ('".implode("','", $identificators)."')");
            return;
        }

        for($i=0; $i<$options['add_count']; $i++) {
            $row = array();
            $row['id'] = create_guid();
            $row['deleted'] = 0;
            foreach($fieldsInfo as $field_gen_defs) {
                if(!empty($field_gen_defs['value'])) {
                    $row[$field_gen_defs['name']] = $field_gen_defs['value'];
                }
                elseif(!empty($field_gen_defs['table'])) {
                    $row[$field_gen_defs['name']] = self::selectRandomFromTable($field_gen_defs['table'], $field_gen_defs['generated']);
                }
                elseif(!empty($field_gen_defs['range_min_datetime']) && !empty($field_gen_defs['range_max_datetime'])) {
                    $row[$field_gen_defs['name']] = date('Y-m-d H:i:s', rand(strtotime($field_gen_defs['range_min_datetime']), strtotime($field_gen_defs['range_max_datetime'])));
                }
            }

            $columns = implode(',', array_keys($row));
            $values = "'".implode("','", $row)."'";
            $query = "INSERT INTO {$relationship_row['join_table']} ($columns) VALUES ($values)";
            $res = $db->query($query);
            if(!$res) {
                return;
            }
        }
    }

    protected static function selectRandomFromTable($table, $generated)
    {
        global $db;
        $sql = "SELECT id FROM $table WHERE deleted = 0";
        if($generated) {
            $sql.= " AND id LIKE '".self::$ID_PREFIX."%'";
        }
        if($db->dbType == 'oci8') {
            $sql .= " ORDER BY dbms_random.value";
        }
        else {
            $sql .= " ORDER BY RAND()";
        }
        $dbRes = $db->limitQuery($sql, 0, 1);
        $row = $db->fetchByAssoc($dbRes, false);
        return $row ? $row['id'] : '';
    }

    protected static function changeSugarConfigOptions()
    {
        global $sugar_config;
        $config = array();
        $config['securitysuite_inherit_assigned'] = isset($sugar_config['securitysuite_inherit_assigned']) ? $sugar_config['securitysuite_inherit_assigned'] : null;
        $sugar_config['securitysuite_inherit_assigned'] = true;
        $config['securitysuite_inherit_parent'] = isset($sugar_config['securitysuite_inherit_parent']) ? $sugar_config['securitysuite_inherit_parent'] : null;
        $sugar_config['securitysuite_inherit_parent'] = false;
        $config['securitysuite_inherit_creator'] = isset($sugar_config['securitysuite_inherit_creator']) ? $sugar_config['securitysuite_inherit_creator'] : null;
        $sugar_config['securitysuite_inherit_creator'] = false;
        return $config;
    }

    protected static function restoreSugarConfigOptions($config)
    {
        global $sugar_config;
        $sugar_config['securitysuite_inherit_parent']   = $config['securitysuite_inherit_parent'];
        $sugar_config['securitysuite_inherit_assigned'] = $config['securitysuite_inherit_assigned'];
        $sugar_config['securitysuite_inherit_creator']  = $config['securitysuite_inherit_creator'];
    }

    protected function loadBean($module)
    {
        if(class_exists('BeanFactory')) {
            return BeanFactory::newBean($module);
        }
        return loadBean($module);
    }

    protected static function getModuleInfoCached($module)
    {
        static $modules = array();
        if(!isset($modules[$module])) {
            $bean = self::loadBean($module);
            $modules[$module] = $bean ? self::getModuleInfo($bean) : false;
        }
        return $modules[$module];
    }

    protected static function getDictionaryCached($filename)
    {
        static $files = array();
        if(!isset($files[$filename])) {
            $files[$filename] = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        return $files[$filename];
    }
}
