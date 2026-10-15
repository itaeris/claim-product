<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Employee used by the claim desk
    |--------------------------------------------------------------------------
    |
    | The prototype has a single signed-in employee. The seeder creates this
    | account and the web middleware signs them in for the claim desk.
    |
    */

    'employee_email' => env('CLAIMLY_EMPLOYEE_EMAIL', 'aeris@claimly.test'),

];
