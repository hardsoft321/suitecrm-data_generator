{**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package data_generator
 *}
{if !empty($modules)}
<h3>Модули</h3>
<table class="list view">
<tr>
    <th>Модуль</th>
    <th>Таблица</th>
    <th>Количество неудаленных записей / Количество всех записей</th>
</tr>
{foreach from=$modules item=module_info}
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td><a href="index.php?module=DataGenerator&action=GenerateView&target_module={$module_info.module_name}">{$module_info.module_name}</a></td>
    <td>{$module_info.table_name}</td>
    <td>{$module_info.count_not_deleted} / {$module_info.count}</td>
</tr>
{/foreach}
</table>
{/if}

{if !empty($relationships)}
<h3>Связи</h3>
<table class="list view">
<tr>
    <th>Имя связи</th>
    <th>Модуль 1</th>
    <th>Модуль 2</th>
    <th>Таблица</th>
    <th>Количество неудаленных записей / Количество всех записей</th>
</tr>
{foreach from=$relationships item=rel_info}
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td><a href="index.php?module=DataGenerator&action=GenerateRelationshipView&target_relationship={$rel_info.relationship_name}">{$rel_info.relationship_name}</a></td>
    <td>{$rel_info.lhs_module}</td>
    <td>{$rel_info.rhs_module}</td>
    <td>{$rel_info.join_table}</td>
    <td>{$rel_info.count_not_deleted} / {$rel_info.count}</td>
</tr>
{/foreach}
</table>
{/if}
