<?php

return [

    /*
     * Here you should specify the distinguished names of LDAP groups users
     * must have to authenticate and stay logged in to your application.
     */
    'allowed_groups' => [
        // 'cn=Accounting,ou=Groups,dc=acme,dc=group',
    ],

    /*
     * Set the name of the field that you use as your application's "identifier" field
     * Using 'username' is the default as it is available in Laravel out of the box.
     */
    'identifier' => 'username',

    // Set the number of days where we should consider the password as expiring
    'password_expiry_threshold' => 5,

    // Set the name of the field to check password expiration against
    'password_expiration_field' => 'password_updated_at',

    /*
     * Number of seconds between 1601-01-01 00:00:00 and 1970-01-01 00:00:00
     * This is to account for the difference in a UNIX and LDAP timestamp.
     */
    'windows_epoch_interval' => 11644473600,

    /*
     * Disable ldap while running unit tests
     */
    'disable_while_testing' => false,

    /*
     * Disable ldap while in local environment
     */
    'disable_on_local' => false,

];
