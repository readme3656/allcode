<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Http\Request;
use App\Http\Services\WechatRobot;
use PHPZxing\PHPZxingDecoder;

Route::get('/', 'HomeController@index')->name('home');
Route::get('/logout','HomeController@logout')->name('logout');
Auth::routes();
Route::any('captcha-test', function(Request $request)
{
    if ($request->method() == 'POST')
    {
        $rules = ['captcha' => 'required|captcha'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
        {
            echo '<p style="color: #ff0000;">Incorrect!</p>';
        }
        else
        {
            echo '<p style="color: #00ff30;">Matched :)</p>';
        }
    }

    $form = '<form method="post" action="captcha-test">';
    $form .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    $form .=  '<img src="data:image/png;base64,'.base64_encode(QrCode::format('png')->size(100)->generate('Make me into an QrCode!')).'">';
    $form .= '<p>' . captcha_img() . '</p>';
    $form .= '<p><input type="text" name="captcha"></p>';
    $form .= '<p><button type="submit" name="check">Check</button></p>';
    $form .= '</form>';
    return $form;
});
Route::get('reset', 'UserController@getReset')->name('resetpwd');
Route::post('reset', 'UserController@postReset');
Route::post('regsms/send', 'Auth\RegisterController@getVerificationCode')->name('regsms.send');
//活码管理设置
Route::name('task.')->group(function (){
    Route::get('task/create','TaskController@create')->name('create');
    Route::post('task/store','TaskController@store')->name('store');
    Route::get('task/qr/{taskId}','TaskController@taskQrcode')->name('taskqrcode');
    Route::get('task/liveCode/{id}','TaskController@liveCode')->name('liveCode');
    Route::get('task/manage','TaskController@manage')->name('manage');
    Route::get('task/edit/{taskId}','TaskController@edit')->name('edit');
    Route::post('task/update/{taskId}','TaskController@updateTask')->name('update');
    Route::get('task/delete/{taskId}','TaskController@delete')->name('delete');
    Route::post('task/search','TaskController@search')->name('search');
    Route::post('task/ajaxSearch','TaskController@ajaxSearch')->name('ajaxSearch');
});

//微信群管理
Route::name('group.')->group(function (){
    Route::get('group/index/{taskId}','GroupController@indexGroup')->name('index');
    Route::post('group/store','GroupController@storeGroup')->name('store');
    Route::post('group/delete/{groupId}','GroupController@delete')->name('delete');
    Route::post('group/deleteAll','GroupController@deleteAll')->name('deleteAll');
    Route::get('group/edit/{id}','GroupController@editGroup')->name('edit');
    Route::post('group/update/{id}','GroupController@updateGroup')->name('update');
    Route::get('group/bean','GroupController@bean')->name('bean_index');
    Route::get('group/export','GroupController@export')->name('export');
});

//落地页路由
Route::name('build.')->group(function (){
    Route::get('build/wechat/{id}','BuilderController@index')->name('wechat');
    Route::get('build/showGroup/{taskId}','BuilderController@showGroup')->name('showGroup');
});

//微信开发路由
Route::group(['middleware' => ['web', 'wechat.wl_oauth']], function () {
//    Route::get('/mpuser', function () {
//        $user = session('wechat.oauth_user.default'); // 拿到授权用户资料
//
//        dd($user);
//    });

    Route::get('payment/wechat/wxpay','Pay\WechatPayController@wxpay')->name('wxpay');
    Route::post('payment/wechat/unifiedorder','Pay\WechatPayController@prepay')->name('wechat.prepay');
});
Route::any('payment/wechat/notify/{account}','Pay\WechatPayController@notify')->name('wechat.notify');

//用户支付设置路由
Route::name('setPay.')->group(function (){
    Route::get('setPay/index','SetPayController@index')->name('index');
    Route::post('setPay/add','SetPayController@paySet')->name('add');
});

//微信群好友设置
Route::name('aboutgroup.')->group(function(){
    Route::get('aboutgroup/index','AboutGroupController@index')->name('index');
    Route::get('aboutgroup/config/{ID}','AboutGroupController@addConfig')->name('config');
    Route::get('aboutgroup/hint','AboutGroupController@hint')->name('aboutgroup.hint');
    Route::get('aboutgroup/getInfo','AboutGroupController@getWxInfo')->name('getInfo');
    Route::post('aboutgroup/saveconfig','AboutGroupController@saveAddConfig')->name('saveconfig');
    Route::get('aboutgroup/getwxInfo','AboutGroupController@getWxInfo')->name('getwxInfo');
    Route::get('aboutgroup/sendmsg','AboutGroupController@sendMsgConfig')->name('sendmsg');
    Route::any('aboutgroup/savemsgconfig', 'AboutGroupController@saveMsgConfig')->name('savemsgconfig');
    Route::any('aboutgroup/keywordlist', 'AboutGroupController@keyWordList')->name('keywordlist');
    Route::any('aboutgroup/addkeyword', 'AboutGroupController@AddKeyWord')->name('addkeyword');
    Route::any('aboutgroup/saveaddkeyword', 'AboutGroupController@saveAddKeyWord')->name('saveaddkeyword');
    Route::any('aboutgroup/linkgroup', 'AboutGroupController@linkGroup')->name('linkgroup');
});

//机器人设置
Route::name('wxrobot.')->group(function(){
    Route::get('wxrobot/login','WxRobotController@showLoginQr')->name('login');
   // Route::get('wxrobot/create','WxRobotController@create')->name('create');
    Route::get('wxrobot/manage','WxRobotController@manage')->name('manage');
    Route::get('wxrobot/manage/{rb_id}/chatroom/add','WxRobotController@showAddChatroom')->name('chatroom.add');
    Route::post('wxrobot/manage/{rb_id}/chatroom/add','WxRobotController@addChatroom');
    Route::post('wxrobot/manage/chatroom/delete','WxRobotController@delChatroom')->name('chatroom.delete');

    Route::get('wxrobot/manage/chatroom/setting/{cid}', 'WxRobotController@showSetChatroom')->name('chatroom.setting');
    Route::post('wxrobot/manage/chatroom/setting/{cid}', 'WxRobotController@setChatroom');

    Route::post('wxrobot/manage/chatroom/addwelcome/{cid}', 'WxRobotController@addChatroomWelcome')->name('chatroom.addwelcome');

    Route::get('wxrobot/chatset/{rb_id}','WxRobotController@chatSet')->name('chatset');
    Route::post('wxrobot/chatset/{rb_id}','WxRobotController@saveChatSet')->name('saveset');

    Route::get('wxrobot/chatset/auto_add_friend/{rb_id}','WxRobotController@chatSetAutoAddFriend')->name('chatset.auto_add_friend');
    Route::post('wxrobot/chatset/auto_add_friend/{rb_id}','WxRobotController@storeAutoAddFriend');

    Route::get('wxrobot/chatset/auto_msg_reply/{rb_id}','WxRobotController@chatSetAutoMsgReply')->name('chatset.auto_msg_reply');
    Route::post('wxrobot/chatset/auto_msg_reply/{rb_id}','WxRobotController@storeAutoMsgReply');

    Route::get('wxrobot/chatset/keyword_pull_group/{rb_id}','WxRobotController@chatSetKeywordPullGroup')->name('chatset.keyword_pull_group');
    Route::post('wxrobot/chatset/keyword_pull_group/{rb_id}','WxRobotController@storeKeywordPullGroup');
    Route::post('wxrobot/chatset/del_keyword_pull_group/{rb_id}','WxRobotController@deleteKeywordPullGroup')->name('chatset.delete_keyword_pull_group');
    //Route::get('wxrobot/chatset/auto','WxRobotController@chatSet')->name('chatset');

    //微信机器人事件回调
    Route::post('wxrobot/events','WxRobotController@wxEventCallBack')->name('event');

    //机器人群列表管理
    Route::get('wxrobot/manage/list/{rb_id}','WxRobotController@wxGroupList')->name('robotlist');

    Route::get('ccf/test/{cid?}', 'WxRobotController@checkChatroomIsBelongToUser');

});



