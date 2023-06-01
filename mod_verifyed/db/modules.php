<?php

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_verifyed' => [ 
        'handlers' => [ // Different places where the add-on will display content.
            'displayicon' => [ // The handler unique name.
                'displaydata' => [
                    'icon' => 'mod_verifyed/pix/icon.svg', // Path to the icon, relative to the plugin root directory.
                ],
            ],
        ],
    ],
];