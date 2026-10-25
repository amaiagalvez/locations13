<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Official catalog import
    |--------------------------------------------------------------------------
    |
    | The `locations:import-euskadi` command reads a CSV prepared on disk, so no
    | network ever reaches the import. The path lives here so a host can point the
    | command at its own file, and `--file=` overrides it for a single run.
    |
    */

    'import' => [
        'file' => storage_path('app/locations/euskadi.csv'),
    ],

];
