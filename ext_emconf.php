<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "fe_register"
 *
 * Auto generated by Extension Builder 2014-06-12
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
	'title' => 'FE Register',
	'description' => '',
	'category' => 'plugin',
	'author' => 'Steffen Kroggel, Maximilian Fäßler',
	'author_email' => 'developer@steffenkroggel.de, maximilian@faesslerweb.de',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '9.5.14',
	'constraints' => [
		'depends' => [
            'typo3' => '9.5.0-9.5.99',
            'ajax_api' => '9.5.0-9.5.99',
            'accelerator' => '9.5.2-9.5.99',
            'core_extended' => '9.5.4-9.5.99',
            'postmaster' => '9.5.0-9.5.99',
        ],
		'conflicts' => [
		],
		'suggests' => [
            'sr_freecap' => '2.5.6-2.5.99',
        ],
	],
];
