<?php
declare(strict_types = 1);

return [
    \Madj2k\FeRegister\Domain\Model\BackendUser::class => [
        'tableName' => 'be_users',
    ],
    \Madj2k\FeRegister\Domain\Model\FrontendUser::class => [
        'tableName' => 'fe_users',
        'recordType' => 0,
    ],
    \Madj2k\FeRegister\Domain\Model\GuestUser::class => [
        'tableName' => 'fe_users',
        'recordType' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
    ],
    \Madj2k\FeRegister\Domain\Model\FrontendUserGroup::class => [
        'tableName' => 'fe_groups',
    ],
];
