<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;



// 窗口管理路由
Route::get('/window', [app\controller\WindowController::class, 'index']); // 窗口列表
Route::post('/window/save', [app\controller\WindowController::class, 'save']); // 保存
Route::post('/window/delete', [app\controller\WindowController::class, 'delete']);


Route::get('/', [app\controller\QueueController::class, 'admin']); // 管理员页面
Route::get('/queue/display', [app\controller\QueueController::class, 'display']); // 叫号屏幕
Route::get('/queue/take', [app\controller\QueueController::class, 'takePage']); // 取号页面
Route::post('/queue/take', [app\controller\QueueController::class, 'take']); // 取号
Route::post('/queue/call', [app\controller\QueueController::class, 'call']); // 叫号
Route::post('/queue/complete', [app\controller\QueueController::class, 'complete']); // 完成
Route::post('/queue/pass', [app\controller\QueueController::class, 'pass']);  // 跳过
Route::post('/queue/cancel', [app\controller\QueueController::class, 'cancel']); // 取消
Route::get('/queue/qrcode', [app\controller\QueueController::class, 'qrcode']); // 二维码
Route::get('/queue/status/{number}', [app\controller\QueueController::class, 'status']); // 状态
Route::get('/queue/current', [app\controller\QueueController::class, 'current']); // 当前叫号


Route::disableDefaultRoute();