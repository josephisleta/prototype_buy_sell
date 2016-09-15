<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index');

Route::get('/register', 'OAuthController@register');
Route::get('/login', 'OAuthController@authenticate');

Route::group(['middleware' => 'user'], function () {
    Route::get('/user/home',           ['as' => 'user_home',           'uses' => 'UserController@home']);
    Route::get('/user/items',          ['as' => 'user_items',          'uses' => 'UserController@items']);
    Route::get('/user/profile',        ['as' => 'user_profile',        'uses' => 'UserController@viewProfile']);
    Route::post('/user/profile/update', ['as' => 'user_profile_update', 'uses' => 'UserController@updateProfile']);

    // No specs yet
    Route::get('/user/likesViews',     ['as' => 'user_likes_views', 'uses' => 'UserController@likesViews']);
    Route::get('/user/follow',         ['as' => 'user_follow',      'uses' => 'UserController@follow']);
    Route::get('/user/unfollow',       ['as' => 'user_unfollow',    'uses' => 'UserController@unfollow']);
    Route::get('/user/profile/ratings',['as' => 'user_ratings',     'uses' => 'UserController@viewRatings']);

    Route::get('/item/sell',          ['as' => 'item_sell',    'uses' => 'ItemController@viewAdd']);
    Route::post('/item/sell/save',    ['as' => 'item_save',    'uses' => 'ItemController@addSave']);
    Route::get('/item/view',          ['as' => 'item_view',    'uses' => 'ItemController@view']);
    Route::get('/item/like',          ['as' => 'item_like',    'uses' => 'ItemController@like']);
    Route::get('/item/comment',       ['as' => 'item_comment', 'uses' => 'ItemController@comment']);
    Route::get('/item/delete',        ['as' => 'item_delete',  'uses' => 'ItemController@delete']);
    Route::get('/item/search',        ['as' => 'item_search',  'uses' => 'ItemController@search']);
    Route::get('/item/list/category', ['as' => 'item_search',  'uses' => 'ItemController@viewByCategory']);

    // No specs yet
    Route::get('/item/buy',           ['as' => 'item_buy',           'uses' => 'ItemController@buy']);
    Route::get('/item/buy/rate',      ['as' => 'item_rate_purchase', 'uses' => 'ItemController@ratePurchase']);
});
