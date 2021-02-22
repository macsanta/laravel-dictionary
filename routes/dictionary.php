<?php

Route::get('/dictionary', '\KamilZawada\LaravelDictionary\DictionaryController@dashboard')->name('dictionary.dashboard');
Route::get('/dictionary/languages', '\KamilZawada\LaravelDictionary\DictionaryController@languages')->name('dictionary.languages');
Route::get('/dictionary/groups/{language?}', '\KamilZawada\LaravelDictionary\DictionaryController@groups')->name('dictionary.groups');
Route::get('/dictionary/{language}/{group}/{keyword?}', '\KamilZawada\LaravelDictionary\DictionaryController@index')->name('dictionary.index');
Route::patch('/dictionary/update', '\KamilZawada\LaravelDictionary\DictionaryController@update')->name('dictionary.update');
Route::post('/dictionary', '\KamilZawada\LaravelDictionary\DictionaryController@store')->name('dictionary.store');
Route::delete('/dictionary', '\KamilZawada\LaravelDictionary\DictionaryController@destroy')->name('dictionary.delete');
Route::post('/dictionary/importfromviews', '\KamilZawada\LaravelDictionary\DictionaryController@importFromViews')->name('dictionary.importFromViews');
Route::get('/dictionary/export', '\KamilZawada\LaravelDictionary\DictionaryController@export')->name('dictionary.export');
Route::post('/dictionary/import', '\KamilZawada\LaravelDictionary\DictionaryController@import')->name('dictionary.import');

