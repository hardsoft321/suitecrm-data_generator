<?php
$manifest = array(
    'name' => 'data_generator',
    'acceptable_sugar_versions' => array(),
    'acceptable_sugar_flavors' => array('CE'),
    'author' => 'hardsoft321',
    'description' => 'Модуль генерации данных',
    'is_uninstallable' => true,
    'published_date' => '2016-04-12',
    'type' => 'module',
    'version' => '0.9.0',
);
$installdefs = array(
    'id' => 'data_generator',
    'copy' => array(
        array(
            'from' => '<basepath>/source/copy',
            'to' => '.'
        ),
    ),
);
