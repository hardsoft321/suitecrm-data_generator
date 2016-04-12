{**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package data_generator
 *}
{literal}
<style>
.options {max-height: 50px; overflow:auto;}
</style>
{/literal}
<table class="list view">
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Модуль</td>
    <td>{$module_info.module_name}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Класс</td>
    <td>{$module_info.object_name}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Таблица</td>
    <td>{$module_info.table_name}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Количество всех записей</td>
    <td>{$module_info.count}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Количество неудаленных записей</td>
    <td>{$module_info.count_not_deleted}</td>
</tr>
<tr class="{cycle values='oddListRowS1,evenListRowS1'}">
    <td>Количество сгенерированных записей</td>
    <td>{$module_info.count_generated}</td>
</tr>
</table>
<br />
<h2>Сгенерировать</h2>
<br />
<form action="index.php" method="POST">
    <input type="hidden" name="module" value="DataGenerator" />
    <input type="hidden" name="action" value="Generate" />
    <input type="hidden" name="target_module" value="{$module_info.module_name}" />
    <label for="add_count">Количество</label> <input type="text" id="add_count" name="add_count" /> <input type="submit" value="Сгенерировать" /><br/>
    <small>Отрицательное количество удаляет нужное количество ранее сгенерированных записей</small>
    {if !empty($fields_info)}
    <div>
    <h3>Поля</h3>
    <table class="list view">
        <tr>
            <th>Имя</th>
            <th>Тип</th>
            <th>Длина</th>
            <th>Генератор</th>
        </tr>
        {foreach from=$fields_info item=field}
        <tr class="{cycle values='oddListRowS1,evenListRowS1'}">
            <td>{$field.name}</td>
            <td>{$field.type}</td>
            <td>{$field.len}</td>
            <td>
                {if $field.type == 'parent'}
                Связь с одним из модулей <div class="options">{foreach from=$field.options key=key item=opt}{$key} - {$opt.name} {$opt.percent}%<br />{/foreach}</div>

                {elseif !empty($field.options)}
                Варианты<div class="options">{foreach from=$field.options key=key item=opt}{$key} - {$opt}<br />{/foreach}</div>

                {elseif !empty($field.dictionary)}
                Из словаря "{$field.dictionary}"

                {elseif !empty($field.dictionary_pattern)}
                Из словарей "{$field.dictionary_pattern}"

                {elseif !empty($field.range_min_len) && !empty($field.range_max_len)}
                Строка {$field.range_min_len} - {$field.range_max_len} символов{if !empty($field.alphabet)}, Алфавит "{$field.alphabet}"{/if}

                {elseif !empty($field.range_min_datetime) && !empty($field.range_max_datetime)}
                Дата {$field.range_min_datetime} - {$field.range_max_datetime}

                {elseif !empty($field.range_min_date) && !empty($field.range_max_date)}
                Дата {$field.range_min_date} - {$field.range_max_date}

                {elseif $field.type == 'currency'}
                Число

                {elseif $field.type == 'link'}
                {$field.relate_min_count} - {$field.relate_max_count} связей с модулем {$field.module}

                {elseif !empty($field.table) && !empty($field.range_max_relate)}
                Поле id одной из {$field.range_max_relate} {if !empty($field.generated)}сгенерированных {/if}записей таблицы {$field.table}
                {/if}

                {if !empty($field.empty_chance) && $field.empty_chance > 0} (пусто с вероятностью {$field.empty_chance}){/if}
            </td>
        </tr>
        {/foreach}
    </table>
    </div>
    {/if}
</form>
