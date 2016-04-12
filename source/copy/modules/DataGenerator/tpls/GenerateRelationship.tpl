{**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package data_generator
 *}
<table class="list view">
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Модуль 1</td>
    <td>{$rel_info.lhs_module}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Модуль 2</td>
    <td>{$rel_info.rhs_module}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Таблица</td>
    <td>{$rel_info.join_table}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Количество всех записей</td>
    <td>{$rel_info.count}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Количество неудаленных записей</td>
    <td>{$rel_info.count_not_deleted}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Количество сгенерированных записей</td>
    <td>{$rel_info.count_generated}</td>
</tr>
</table>
<br />
<h2>Сгенерировать</h2>
<br />
<form action="index.php" method="POST">
    <input type="hidden" name="module" value="DataGenerator" />
    <input type="hidden" name="action" value="GenerateRelationship" />
    <input type="hidden" name="target_relationship" value="{$rel_info.relationship_name}" />
    <label for="add_count">Количество</label> <input type="text" id="add_count" name="add_count" /> <input type="submit" value="Сгенерировать" /><br/>
    <small>Отрицательное количество удаляет нужное количество ранее сгенерированных записей</small>
    {if !empty($fields_info)}
    <div>
    <h3>Поля</h3>
    <table class="list view">
        <tr>
            <th>Имя</th>
            <th>Генератор</th>
        </tr>
        {foreach from=$fields_info item=field}
        <tr class="{cycle values='oddListRowS1,evenListRowS1'}">
            <td>{$field.name}</td>
            <td>
                {if !empty($field.value)}
                Значение {$field.value}

                {elseif !empty($field.table)}
                Поле {$field.select_field} одной из {$field.range_max_relate} {if !empty($field.generated)}сгенерированных {/if}записей таблицы {$field.table}

                {elseif !empty($field.range_min_datetime) && !empty($field.range_max_datetime)}
                Дата {$field.range_min_datetime} - {$field.range_max_datetime}

                {/if}
            </td>
        </tr>
        {/foreach}
    </table>
    </div>
    {/if}
</form>
