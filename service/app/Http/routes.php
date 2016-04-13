<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->get('/version', function () {
    return '0.1.0';
});

$app->get('test', function () {
    return 0;
});

$app->get('/phpinfo', function () {
    return phpinfo();
});

$app->get('list/{id}', function ($id) {
//    $list = List::find($id);
    return $id;
});

$app->get('/cache/{data}', function ($data) {
    Cache::put('test', $data, 1);
    return 'OK';
});

$app->get('/cache', function () {
    return Cache::get('test');
});

$app->get('crypt', function () {
    return Crypt::encrypt('love');
});

$app->get('/mail/{mail}', function ($mail) {
    $to = $mail;
    $subject = '欢迎使用Follow3';
    $message = file_get_contents('activate.html');
    $message = wordwrap($message, 70, "\r\n");
    $headers = "MIME-Version: 1.0" . "\r\n"
        . 'Content-type: text/html; charset=iso-8859-1' . "\r\n"
        . 'From: Follow3@lhzbxx.top' . "\r\n"
        . 'X-Mailer: PHP/' . phpversion();;
    mail($to, $subject, $message, $headers);
});

$app->post('auth/register', 'AuthController@register');

$app->post('auth/login', function (Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        return 'No';
    }
});