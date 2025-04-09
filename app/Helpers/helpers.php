<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (!function_exists('update_auth_key')) {
    function update_auth_key($user_id)
    {
        $auth_key = Str::random(8);

        $update_auth = DB::table('users')
        ->where('id', $user_id)
        ->update(
            [
                'auth_key' => $auth_key,
                'updated_at'=>date('Y-m-d H:i:s')
            ]
        );
        if($update_auth){
            return $auth_key;
        }else{
            return false;
        }
    }
}
if (!function_exists('send_otp')) {
    function send_otp($phone,$otp)
    {

        $send_otp = true;
        if($send_otp){
            return true;
        }else{
            return false;
        }
    }
}