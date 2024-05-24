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
	'clearCacheOnLoad' => 0,
	'version' => '10.4.7',
	'constraints' => [
		'depends' => [
            'typo3' => '10.4.0-10.4.99',
            'ajax_api' => '10.4.0-12.4.99',
            'accelerator' => '10.4.0-12.4.99',
            'core_extended' => '10.4.0-12.4.99',
            'postmaster' => '10.4.0-12.4.99',
        ],
		'conflicts' => [
		],
		'suggests' => [
            'sr_freecap' => '2.5.6-2.5.99',
        ],
	],
];
