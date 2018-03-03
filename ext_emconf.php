<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Crealistiques FAL Clear Page Cache',
	'description' => 'Automatically clear the cache for pages which include changed/new files by uploads content element.',
	'category' => 'backend',
	'author' => 'Steffen Wargalla',
	'author_email' => 'sw@crealistiques.de',
    'author_company' => 'Crealistiques',
    'state' => 'beta',
	'clearCacheOnLoad' => 1,
	'version' => '8.7.10',
	'constraints' => array(
		'depends' => array(
			'typo3' => '8.7.*',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
    'autoload' => array(
        'psr-4' => array(
            'Crealistiques\\CreFalclearpagecache\\' => 'Classes'
        ),
    ),
);

