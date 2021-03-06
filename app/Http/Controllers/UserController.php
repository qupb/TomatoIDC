<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MailDrive\UserMailController;
use App\SettingModel;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * User setting action
     * 用户设置操作
     */
    public function userProfileAction(Request $request)
    {
        $this->validate($request, [
            'password' => 'nullable|string|min:6',
            'name' => 'nullable|string|max:16',
            'qq' => 'digits_between:5,11|integer|nullable',
            'phone' => 'digits_between:6,15|integer|nullable',
            'signature' => 'min:3|max:999|string|nullable'
        ]);

        $user = User::where('id', Auth::id())->first();
        if ($user->name != $request['name']) { //验证是否重名
            $this->validate($request, [
                'name' => 'unique:users,name'
            ]);
        }
        //TODO 提高性能
        if (!empty($request['password'])) {
            User::where('id', Auth::id())
                ->update(['password' => Hash::make($request['password'])]);
            Auth::logout();
        }
        $user->name == $request['name'] ?: User::where('id', Auth::id())->update(['name' => $request['name']]);
        $user->qq == $request['qq'] ?: User::where('id', Auth::id())->update(['qq' => $request['qq']]);
        $user->phone == $request['phone'] ?: User::where('id', Auth::id())->update(['phone' => $request['phone']]);
        $user->signature == $request['signature'] ?: User::where('id', Auth::id())->update(['signature' => $request['signature']]);

        return back();
    }

    /**
     * 用户更改个人信息操作
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function userEditAction(Request $request)
    {
        AdminController::checkAdminAuthority(Auth::user());
        $this->validate($request, [
            'password' => 'nullable|string|min:6',
            'name' => 'nullable|string|max:16',
            'qq' => 'digits_between:5,11|integer|nullable',
            'phone' => 'digits_between:6,15|integer|nullable',
            'account' => 'numeric|nullable',
            'signature' => 'min:3|max:999|string|nullable',
            'id' => 'exists:users,id|required'
        ]);

        $user = User::where('id', $request['id'])->first();
        if ($user->name != $request['name']) { //验证是否重名
            $this->validate($request, [
                'name' => 'unique:users,name'
            ]);
        }
        //TODO 提高性能
        if (!empty($request['password'])) {
            User::where('id', Auth::id())
                ->update(['password' => Hash::make($request['password'])]);
            Auth::logout();
        }
        $user->name == $request['name'] ?: User::where('id', $request['id'])->update(['name' => $request['name']]);
        $user->qq == $request['qq'] ?: User::where('id', $request['id'])->update(['qq' => $request['qq']]);
        $user->phone == $request['phone'] ?: User::where('id', $request['id'])->update(['phone' => $request['phone']]);
        $user->signature == $request['signature'] ?: User::where('id', $request['id'])->update(['signature' => $request['signature']]);
        $user->account == $request['account'] ?: User::where('id', $request['id'])->update(['account' => round(abs($request['account']),2)]);

        return back();
    }

    /**
     * encrypt validate url
     * @param $user
     * @return string
     */
    protected function encryptUserEmailValidateUrl($user)
    {
        $encrypt = md5($user->email.substr(env('APP_KEY'),15,31).$user->id.$user->updated_at);//如果用户更新信息，那么URL将失效
        $url = action('IndexController@userEmailTokenValidate',['token'=>$encrypt,'id'=>Auth::id()]);
        return $url;
    }

    /**
     * Send user validate mail action
     * @return \Illuminate\Http\JsonResponse
     */
    public function userEmailValidateSendAction($user = null)
    {
//        dd(123);
        if (empty($user)){
            $user = Auth::user();
        }

        if (!empty($user->email_vaildate)){
            return redirect('/home');
        }

        $url = $this->encryptUserEmailValidateUrl($user);
        $mail = new UserMailController();
        $mail->sendMailFun(Auth::user(),'UserEmailValidate',$url);
        return response()->json([
            'status' => 'success',
            'date' => []
        ]);
    }
}
