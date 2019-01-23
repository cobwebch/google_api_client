<?php

$EM_CONF[$_EXTKEY] = [
	'title' => 'Google API service',
	'description' => 'Google API service and demo',
	'category' => 'plugin',
	'author' => 'Fabien Udriot',
	'author_email' => 'fudriot@cobweb.ch',
	'author_company' => 'Cobweb',
	'state' => 'stable',
	'version' => '1.0.0',
    'autoload' => [
        'psr-4' => ['Cobweb\\GoogleDocsIntegration\\' => 'Classes']
    ],
	'constraints' => [
		'depends' => [
			'typo3' => '6.2.0-8.7.99',
        ],
		'conflicts' => [
        ],
		'suggests' => [
        ],
    ],
];
