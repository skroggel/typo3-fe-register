<?php

return [
    'fe_register:cleanup' => [
        'class' => \Madj2k\FeRegister\Command\CleanupCommand::class,
        'schedulable' => true,
    ],
    'fe_register:anonymize' => [
        'class' => \Madj2k\FeRegister\Command\AnonymizeCommand::class,
        'schedulable' => true,
    ],
    'fe_register:encryptionKey' => [
        'class' => \Madj2k\FeRegister\Command\EncryptionKeyCommand::class,
        'schedulable' => false,
    ],
];
