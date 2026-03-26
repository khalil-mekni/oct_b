<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/debug-class', function () {
    $path = app_path('GraphQL/Mutations/EmbballageMutator.php');

    return response()->json([
        'app_path' => app_path(),
        'expected_file' => $path,
        'file_exists' => File::exists($path),
        'class_exists' => class_exists(\App\GraphQL\Mutations\EmbballageMutator::class),
    ]);
});

