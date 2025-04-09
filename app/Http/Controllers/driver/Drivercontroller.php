<?php

namespace App\Http\Controllers\driver;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderProduct;
use URL;
use Session;


class Drivercontroller extends Controller
{
    public function index()
    {
        return view('driver/login');
    }
    public function login(Request $req)
    {
        $req->validate([
            'mobile'=>'required',
            'password'=>'required'
        ]);
        $mobile = $req->input('mobile');
        $password = $req->input('password');
        $users = Driver::select()
                     ->where('mobile', $mobile)
                     ->where('password', md5($password))
                     ->get();
         
        if(count($users) == 1)
        {
            foreach($users as $usr)
            {
                $id = $usr['id'];
                Session::put('driver_id', $id);
            }
            return redirect('driver/dashboard');
        }
        else
        {
            return redirect('/driver')->with('error', 'Invalid user !');;
        }
    }
    public function dashboard(Request $req)
    {
        $driver = Session::get('driver_id');
        $assigned_order = OrderProduct::select('order.id')
        ->join('order', 'order.order_id', '=', 'order_product.order_id')
        ->where('order_product.assigned_driver',$driver)
        ->whereIn('order_product.status',array(2, 3))
        ->get();

        $delivered_order = OrderProduct::select('order.id')
        ->join('order', 'order.order_id', '=', 'order_product.order_id')
        ->where('order_product.assigned_driver',$driver)
        ->where('order_product.status',4)
        ->get();
        
        return view('driver/dashboard')->with('assigned_order', count($assigned_order))->with('delivered_order', count($delivered_order));
    }
    public function driverOrderList()
    {
        // https://www.yourearningshop.com/driver/driver-order-list
        
        $driver = Session::get('driver_id');
        $order = OrderProduct::select('order.id','order.order_id','users.fname','users.lname','order.order_date','order.sub_total','order.user_id','order.payment_method',
        'order.grand_total','order.order_status','user_address.fname as sfname','user_address.lname as slname','user_address.email','user_address.phone',
        'user_address.address','user_address.city','user_address.state','user_address.address','user_address.zip','order.payment_method')
        ->join('users', 'users.id', '=', 'order_product.user_id')
        ->join('order', 'order.order_id', '=', 'order_product.order_id')
        ->join('user_address', 'user_address.id', '=', 'order.shipping_address')
        ->where('order_product.assigned_driver',$driver)
        ->whereIn('order_product.status',array(2, 3))
        ->get();
        return view('driver/order_list')->with('order', $order);
    }
    public function dOrderList()
    {
        $driver = Session::get('driver_id');
        $order = Order::select('order.id','order.order_id','users.fname','users.lname','order.order_date','order.sub_total','order.user_id',
        'order.grand_total','order.order_status','user_address.fname as sfname','user_address.lname as slname','user_address.email','user_address.phone',
        'user_address.address','user_address.city','user_address.state','user_address.address','user_address.zip','order.payment_method')
        ->join('users', 'users.id', '=', 'order.user_id')
        ->join('user_address', 'user_address.id', '=', 'order.shipping_address')
        ->where('order.assigned_driver',$driver)
        ->whereIn('order.order_status',array(4, 5,6))
        ->get();
        return view('driver/order_list')->with('order', $order);
    }
    public function driverOrderDetail(Request $request)
    {
        $id = $request->id;
        $order = Order::select('order.id','order.order_id','order.coupon_code','order.coupon_amount','order.shipping_charge','order.payment_id','order.payment_method',
        'order.sub_total','order.grand_total','order.order_status','order.payment_status','order.order_date',
        'users.fname','users.lname',
        'user_address.fname as sfname','user_address.lname as slname','user_address.email','user_address.phone','order.user_id',
        'user_address.address','user_address.city','user_address.state','user_address.address','user_address.zip','address_type.address_type as addr_type')
        ->join('users', 'users.id', '=', 'order.user_id')
        ->join('user_address', 'user_address.id', '=', 'order.shipping_address')
        ->join('address_type', 'address_type.id', '=', 'user_address.address_type')
        ->where('order.order_id',$id)
        ->get();

        $product = OrderProduct::select('order_product.*','product.product_name','product.product_image')
        ->join('product', 'product.id', '=', 'order_product.product_id')
        ->where('order_product.order_id',$id)
        ->get();
        return view('driver/order_detail')->with('order', $order)->with('product',$product);
    }
    public function driverProfile()
    {
        $id = Session::get('driver_id');
        $profile = Driver::select()
        ->where('id',$id)
        ->get();
        return view('driver/driver_profile')->with('driver_profile', $profile);
    }
    public function updateDriverProfile(Request $request)
    {

        $data = $request->input();
        if($data['password'] != '' && $data['cpassword'] != '')
        {
            if($data['password'] == $data['cpassword'])
            {
                $updatedriverprofile = DB::table('driver')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'alternate_mobile' => $data['alternate_mobile'],
                        'password' => md5($data['password']),
                        'updated_at'=>date('y-m-d')
                    ]
                );
            }
            else
            {
                return redirect('/profile')->with('error', 'Password not match !');
            }

        }
        else
        {
            $updatedriverprofile = DB::table('driver')
            ->where('id', $data['hidden_id'])
            ->update(
                [
                    'alternate_mobile' => $data['alternate_mobile'],
                    'updated_at'=>date('y-m-d')
                ]
            );
        }
        if($updatedriverprofile)
        {
            return  redirect('/driver/driver-profile')->with('success', 'Data saved !');
        }
        else
        {
            return redirect('/driver/driver-profile')->with('error', 'Something went wrong !');
        }
    }
    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect('/driver');
    }
    public function forgetPassword()
    {
        return view('driver/forget_password');
    }
    public function sendResetCode(Request $request)
    {
        $data = $request->input();
        $mobile = $data['mobile'];
        $otp = rand(10000,99999);
        $updateotp = DB::table('driver')
            ->where('mobile', $mobile)
            ->update(
                [
                    'otp' => $otp,
                    'updated_at'=>date('y-m-d')
                ]
            );
        if($updateotp)
        {
            Session::put('mobile_otp', $mobile);
            return  redirect('/driver/verify-otp')->with('success', 'OTP sent to your mobile Please check ..'.$otp);
        }
        else
        {
            return  redirect('/driver/forget-password')->with('error', 'Something went wrong !');
        }
        
    }
    public function resetPassword(Request $request)
    {
        if ($request->session()->has('mobile_otp')) 
        {
        $data = $request->input();
        if($data['password'] == $data['cpassword'])
            {
                $updateadminpassword = DB::table('driver')
                ->where('mobile', Session::get('mobile_otp'))
                ->update(
                    [
                        'password' => md5($data['password']),
                        'otp' => '',
                        'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
                if($updateadminpassword)
                {
                    $request->session()->flush();
                    return redirect('/driver')->with('success', 'Password reset !');
                }
                else
                {
                    return redirect('/driver/change-password')->with('msg', 'Something went wrong !');
                }
            }
            else
            {
                return redirect('/driver/change-password')->with('msg', 'Password not match !');
            }
        }
        else
        {
            return redirect('/driver/page-expired');
        }
    }
    public function verifyOtp(Request $request)
    {
        if ($request->session()->has('mobile_otp')) 
        {
            $mobile = Session::get('mobile_otp');
            return view('driver/verify_otp')->with('mobile',$mobile);
        }
        else
        {
            return redirect('/driver/page-expired');
        }
    }
    public function verify(Request $request)
    {
        if ($request->session()->has('mobile_otp')) 
        {
            $data = $request->input();
            $otp = $data['otp'];
            $mobile = Session::get('mobile_otp');
            $veri = Driver::select()
            ->where('mobile',$mobile)
            ->where('otp',$otp)
            ->get();
            if(count($veri) > 0)
            {
                return  redirect('/driver/change-password')->with('success', 'OTP Verified !');
            }
            else
            {
                return  redirect('/driver/verify-otp')->with('error', 'Invalid otp !');
            }
        }
        else
        {
            return redirect('/driver/page-expired');
        }
    }
    public function viewResetForm(Request $request)
    {
        if ($request->session()->has('mobile_otp')) 
        {
            return view('driver/reset_password');
        }
        else
        {
            return redirect('/driver/page-expired');
        }
    }
    public function pageExpired()
    {
        return view('driver/page_expired');
    }
    public function changeOrderStatus(Request $request)
    {
        $data = $request->input();
        $orderid = $data['orderid'];
        $status = $data['status'];
        $user_id = $data['user_id'];
        $payment_via = $data['payment_via'];
        
        if($status == 3)
        {
            $updatestatus = DB::table('order')
            ->where('order_id', $orderid)
            ->update(
                [
                    'order_status' => $status,
                    'updated_at'=>date('Y-m-d H:i:s')
                ]
            );
            if($updatestatus)
            {
                DB::table('order_product')
                ->where('order_id', $orderid)
                ->update(
                    [
                        'status' => $status,
                        'updated_at'=>date('Y-m-d H:i:s')
                    ]
                );
                $message = 'Your order '.$orderid.' has been shipped and will soon reach you. To track your order, login to YES.';
                // $this->sendSms($user_id,$message);
                return "Changed";
            }
            else
            {
                return "Failed";
            }
            
        }
        else if($status == 4)
        {
            $updatestatus = DB::table('order')
            ->where('order_id', $orderid)
            ->update(
                [
                    'order_status' => $status,
                    'payment_method' => $payment_via,
                    'updated_at'=>date('y-m-d')
                ]
            );
            if($updatestatus)
            {
                DB::table('order_product')
                ->where('order_id', $orderid)
                ->update(
                    [
                        'status' => $status,
                        'updated_at'=>date('Y-m-d H:i:s')
                    ]
                );
                $message = 'Your order '.$orderid.' has been successfully delivered by our agent. To rate your order and agent, login to your YES account and go to My Orders.';
                // $this->sendSms($user_id,$message);
                return "Changed";
            }
            else
            {
                return "Failed";
            }

        }
    }
    // public function sendSms($user_id,$message)
    // {
    //     $user = User::select()
    //     ->where('id', $user_id)
    //     ->get();
    //     foreach($user as $u)
    //     {
    //         $mobileno = $u->phone;
    //     }
        
    //     $message = urlencode($message);
    //     $sender = 'KORNER'; 
    //     $apikey = '1070wmm54yr4d5539ngt2f72q8450er114wm';
    //     $baseurl = 'https://instantalerts.co/api/web/send?apikey='.$apikey;
    
    //     $url = $baseurl.'&sender='.$sender.'&to=91'.$mobileno.'&message='.$message;    
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_POST, false);
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     $response = curl_exec($ch);
    //     curl_close($ch);
    
    //     // Use file get contents when CURL is not installed on server.
    //     if(!$response){
    //          $response = file_get_contents($url);
    //     }
    //     return;
    // }
}
