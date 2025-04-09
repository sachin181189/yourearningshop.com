<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Coupon;
use DB;
use Session;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Wishlist;
use App\Models\Vendor;
use Redirect;
use Image;
use App\Models\Http\Helper;

class Usercontroller extends Controller
{
    public function sendsms()
    {
        Helper::send_otp('9650266972','25365');
        // $curl = curl_init();
        //     curl_setopt_array($curl, [
        //       CURLOPT_URL => "https://api.msg91.com/api/v5/flow/",
        //       CURLOPT_RETURNTRANSFER => true,
        //       CURLOPT_ENCODING => "",
        //       CURLOPT_MAXREDIRS => 10,
        //       CURLOPT_TIMEOUT => 30,
        //       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //       CURLOPT_CUSTOMREQUEST => "POST",
        //       CURLOPT_POSTFIELDS => "{\n  \"flow_id\": \"61d1987d5663af5d6840f9a5\",\n  \"sender\": \"YESTPL\",\n  \"mobiles\": \"919650266972\",\n  \"var\": \"123456\"}",
        //       CURLOPT_HTTPHEADER => [
        //         "authkey: 367399AOz6qCpzY61482cf5P1",
        //         "content-type: application/JSON"
        //       ],
        //     ]);
            
        //     $response = curl_exec($curl);
        //     $err = curl_error($curl);
            
        //     curl_close($curl);
            
        //     if ($err) {
        //       echo "cURL Error #:" . $err;
        //     } else {
        //       echo $response;
        //     }
    }
    public function monthlyCommission(Request $req)
    {
        $user_id = $req->input('user_id');
        $lastMonthStartDate = date("Y-m-d", strtotime("first day of previous month"));
        $lastMonthEndDate = date("Y-m-d", strtotime("last day of previous month"));
        $todayDate = date('Y-m-d');
        
        $checkSettlement = db::table('referred_user_commission')
                ->where('create_date','>=', "$lastMonthEndDate")
                ->where('from_user_id', $user_id)
                ->get();
        
        $totalCommission = DB::table('order_product')->select("order_product.*")
                    ->whereRaw("((order_product.created_at = '$lastMonthStartDate' OR order_product.created_at > '$lastMonthStartDate') AND (order_product.created_at = '$lastMonthEndDate' OR order_product.created_at < '$lastMonthEndDate')) AND order_product.user_id='$user_id' AND order_product.status=4")
                    ->get();
        
        $totalAmount = 0;   
        foreach($totalCommission as $tc)
        {
            $totalAmount = $totalAmount+($tc->qty * $tc->offer_price);
        }        
        $monthlyCommission = (1 * $totalAmount)/100;
        
        // print_r($totalAmount);
        // echo "<br>";
        // print_r($monthlyCommission);
        // die();
        
        if(count($checkSettlement) == 0)
        {
            $getUser = DB::table('users')->select('id', 'referral_code')->where('id', $user_id)->get();
            foreach($getUser as $gu)
            {
                $referral_code = $gu->referral_code;
            }
                
            for($i=1;$i<6;$i++)
            {
                $getUserId = DB::table('users')->select('id', 'parent_referral_code')->where('referral_code', '=', $referral_code)->get();
                foreach($getUserId as $gui)
                {
                    $referral_code = $gui->parent_referral_code;
                    $userId[] = $gui->id;
                }
            }
            
            // print_r($userId);
            // die;
            
            foreach($userId as $ui)
            {
                $checkUser = DB::table('user_wallet')->select('id', 'wallet_amount')->where('user_id', '=', $ui)->get();
                if(count($checkUser) > 0)
                {
                    foreach($checkUser as $cu)
                    {
                        $wallet_amount = $cu->wallet_amount;
                    }
                    
                    $transactionData = array(
                        'wallet_amount' => $monthlyCommission+$wallet_amount,
                        'yes_amount' => (10*($monthlyCommission+$wallet_amount))/100,
                    );
                    $userCommission = array(
                        'from_user_id' => $user_id,
                        'to_user_id' => $ui,
                        'amount' => $monthlyCommission
                    );
                    DB::table('user_wallet')->where('user_id', $ui)->update($transactionData);
                    DB::table('referred_user_commission')->insert($userCommission);
                }
                else
                {
                    $transactionData = array(
                        'user_id' => $ui,
                        'wallet_amount' => $monthlyCommission,
                        'yes_amount' => (10*$monthlyCommission)/100,
                    );
                    $userCommission = array(
                        'from_user_id' => $user_id,
                        'to_user_id' => $ui,
                        'amount' => $monthlyCommission
                    );
                    DB::table('user_wallet')->insert($transactionData);
                    DB::table('referred_user_commission')->insert($walletTransaction);
                }
            }
        }
        
        echo json_encode(true);
    }
    
    public function index()
    {
        return view('login');
    }
    public function register()
    {
        return view('register');
    }
    public function sendOtp(Request $request)
    {
        $data = $request->input();
        $mobile = $data['mobile'];
        $usercount = User::select()
                ->where('phone', $mobile)
                ->count();
        if($usercount == 0)
        {
            $otp = rand(100000,999999);
            Session::put('mobile_otp', $otp);
            Session::put('register_mobile', $mobile);
            Helper::send_otp($mobile,$otp);
            $resarray = array(
                'msg'=>'Otp send successfully',
                'otp'=>$otp,
                'status'=>200
                );
        }
        else
        {
            $resarray = array(
                'msg'=>'Mobile already registered',
                'status'=>201
                );
        }
        return $resarray;
    }
    public function verifyOtp(Request $request)
    {
        $data = $request->input();
        $mobile = $data['mobile'];
        $otp = $data['otp'];
        if(Session::get('mobile_otp') == $otp)
        {
            $resarray = array(
            'msg'=>'Otp verified',
            'status'=>200
            );
        }
        else
        {
             $resarray = array(
            'msg'=>'Invalid otp',
            'status'=>201
            );
        }
        return $resarray;
    }
    public function sendForgetOtp(Request $request)
    {
        $data = $request->input();
        $mobile = $data['mobile'];
        $usercount = User::select()
                ->where('phone', $mobile)
                ->count();
        if($usercount > 0)
        {
            $otp = rand(100000,999999);
            Session::put('mobile_otp', $otp);
            Session::put('forget_mobile', $mobile);
            Helper::send_otp($mobile,$otp);
            $resarray = array(
                'msg'=>'Otp send successfully',
                'otp'=>$otp,
                'status'=>200
                );
        }
        else
        {
            $resarray = array(
                'msg'=>'Mobile not registered',
                'status'=>201
                );
        }
        return $resarray;
    }
    public function verifyForgetOtp(Request $request)
    {
        $data = $request->input();
        $mobile = $data['mobile'];
        $otp = $data['otp'];
        if(Session::get('mobile_otp') == $otp)
        {
            $resarray = array(
            'msg'=>'Otp verified',
            'status'=>200
            );
        }
        else
        {
             $resarray = array(
            'msg'=>'Invalid otp',
            'status'=>201
            );
        }
        return $resarray;
    }
    public function reset_password(Request $request)
    {
        $request->validate([
            'password'=>'required',
            'confirm_password'=>'required',
        ]);
        $data = $request->input();
        $phone = Session::get('forget_mobile');
        $usercount = User::select()
                ->where('phone', $phone)
                ->count();
                
        if($usercount == 0)
        {
            return redirect('/forget-password')->with('error', 'Mobile not register !');
        }
        else
        {
                if($data['password'] == $data['confirm_password'])
                {
                    $updatepassword = DB::table('users')
                        ->where('phone', $phone)
                        ->update(
                            [
                                'password' => md5($data['password']),
                            ]
                        );
                    if($updatepassword)
                    {
                        
                        return  redirect('/login')->with('success', 'Reset successfully !');
                    }
                    else
                    {
                        return redirect('/forget-password')->with('error', 'Something went wrong !');
                    }
                }
                else
                {
                    return redirect('/forget-password')->with('error', 'Password not match !');
                }
        }
    }
    public function saveUser(Request $request)
    {
        $request->validate([
            'password'=>'required',
            'confirm_password'=>'required',
            'fname'=>'required',
            'gender'=>'required',
            'address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'pincode'=>'required',
            'address_type'=>'required'
        ]);
        $data = $request->input();
        $email = $data['email'];
        $phone = Session::get('register_mobile');
        $pincode = $data['pincode'];
        
        $usercount = User::select()
                ->where('phone', $phone)
                ->count();

        $usercount1 = User::select()
                ->where('email', $email)
                ->count();
                
        if($usercount == 1)
        {
            return redirect('/register')->with('error', 'Mobile already exist !');
        }
        elseif($usercount1 == 1)
        {
            return redirect('/register')->with('error', 'Email already exist !');
        }
        else
        {
                DB::beginTransaction();
                if($data['password'] == $data['confirm_password'])
                {
                    if($data['referred_by'] == '')
                    {
                        $referred_by = '8381818319';
                    }
                    else
                    {
                        $referred_by = $data['referred_by'];
                    }
                    $saveuserid = DB::table('users')->insertGetId(
                        [
                        'fname' => $data['fname'],
                        'email' => $data['email'],
                        'gender' => $data['gender'],
                        'phone' => Session::get('register_mobile'),
                        'parent_referral_code' => $referred_by,
                        'referral_code' => Session::get('register_mobile'),
                        'password' => md5($data['password']),
                        'address' => $data['address'],
                        'city' => $data['city'],
                        'state' => $data['state'],
                        'status' => 0,
                        'flat' => $data['flat'],
                        'area' => $data['area'],
                        'landmark' => $data['landmark'],
                        'zip' => $data['pincode']
                        ]
                    );
                }
                else
                {
                    return redirect('/register')->with('error', 'Password not match !');
                }
                if($saveuserid)
                {
                    $saveuseraddress = DB::table('user_address')->insert(
                        [
                        'user_id' => $saveuserid,
                        'fname' => $data['fname'],
                        'email' => $data['email'],
                        'phone' => Session::get('register_mobile'),
                        'address' => $data['address'],
                        'city' => $data['city'],
                        'state' => $data['state'],
                        'zip' => $data['pincode'],
                        'flat' => $data['flat'],
                        'area' => $data['area'],
                        'landmark' => $data['landmark'],
                        'address_type' => $data['address_type'],
                        'is_default' => 1
                        ]
                    );
                    if($saveuseraddress)
                    {
                        DB::commit();
                        return  redirect('/login')->with('success', 'Registered successfully !');
                    }
                    else
                    {
                        DB::rollBack();
                        return redirect('/register')->with('error', 'Something went wrong !');
                    }
                }
                else
                {
                    DB::rollBack();
                    return redirect('/register')->with('error', 'Something went wrong !');
                }
        }
    }
    public function userLogin(Request $request)
    {
        $request->validate([
            'password'=>'required',
            'auth_id'=>'required'
        ],
        [
            'auth_id.required' => 'Email or Phone is required !',
        ]);
        $data = $request->input();
        $auth_id = $data['auth_id'];

        $getUser = User::select('id','phone','email','fname','referral_code')
        ->where(function($q) use ($auth_id){
          $q->where('email', $auth_id)
            ->orWhere('phone', $auth_id);
        })
        ->where('password',md5($data['password']))
        ->get();
        if(count($getUser) > 0)
        {
            foreach($getUser as $val)
            {
                $id = $val->id;
                $fname = $val->fname." ".$val->lname;
                $phone = $val->phone;
                $email = $val->email;
                $referral_code = $val->referral_code;
            }
            Session::put('user_id', $id);
            Session::put('user_name', $fname);
            Session::put('user_phone', $phone);
            Session::put('user_email', $email);
            Session::put('referral_code', $referral_code);
            if(Session::get('redirect_to'))
            {
                return redirect(Session::get('redirect_to'));
            }
            else
            {
                return redirect('/');
            }
            
        }
        else
        {
            return redirect('login')->with('error', 'Email/Mobile or password was wrong !');
        }
    }
    public function cart()
    {
        return view('cart');
    }
    public function wishlist()
    {
        return view('wishlist');
    }
    public function getcartData(Request $request)
    {
        $user_id = Session::get('user_id');
        return Cart::select('cart.id','cart.product_id','cart.offer_price','cart.price','product.product_name','product.product_image','cart.qty','product.slug')
        ->join('product','product.id', '=', 'cart.product_id')
        ->where('cart.user_id',$user_id)
        ->get();
    }
    public function getWishlistData(Request $request)
    {
        $user_id = Session::get('user_id');
        return Wishlist::select('wishlist.id','product.offer_price','product.price','product.product_name','product.product_image','product.slug','wishlist.product_id',
        'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2')
        ->selectRaw('(select count(*) from tblproductvariant where tblproductvariant.product_id=wishlist.product_id) as counts')
        ->join('product','product.id', '=', 'wishlist.product_id')
        ->where('wishlist.user_id',$user_id)
        ->get();
    }
    public function shoping_list()
    {
        return view('shoping_list');
    }
    public function getshopinglistData(Request $request)
    {
        $user_id = Session::get('user_id');
        return DB::table('tbl_shoping_list')->select('tbl_shoping_list.id','tbl_shoping_list.product_id','tbl_shoping_list.offer_price','tbl_shoping_list.price','product.product_name','product.product_image','tbl_shoping_list.qty','product.slug')
        ->join('product','product.id', '=', 'tbl_shoping_list.product_id')
        ->where('tbl_shoping_list.user_id',$user_id)
        ->get();
    }
    public function removeFromShopingList(Request $request)
    {
        $data = $request->input();
        $shoping_id = $data['shoping_id'];
        $removecart = DB::table('tbl_shoping_list')->where('id', $shoping_id)->delete();
        if($removecart)
        {
            echo "removed";
        }
        else
        {
            echo "error";
        }
    }
    public function checkout()
    {
        $user_id = Session::get('user_id');
        $user_address = DB::table('user_address')->orderBy('is_default','desc')->select('user_address.id','fname','lname','email','phone','address','city','state','zip','address_type.address_type','is_default','address_type.id as type_id')
            ->join('address_type','address_type.id', '=', 'user_address.address_type')
            ->where('user_id',$user_id)
            ->where('is_deleted',0)
            ->get();
            
            $billing_address = DB::table('billing_address')->orderBy('is_default','desc')->select('id','user_name','email','phone','address','city','state','zip','is_default')
            ->where('user_id',$user_id)
            ->where('is_deleted',0)
            ->get();
        
        $user_id = Session::get('user_id');
        $cartdata = Cart::select('cart.id','cart.offer_price','cart.price','product.product_name','product.product_image','cart.qty','product.product_type')
        ->join('product','product.id', '=', 'cart.product_id')
        ->where('cart.user_id',$user_id)
        ->get();
        
        $timeslot = DB::table('time_slot')->select('*')
        ->where('status',1)
        ->get();
        
        $wallet = DB::table('user_wallet')->select('*')
            ->where('user_id',$user_id)
            ->get();
        
        $userdetail = DB::table('users')->select('created_at')
        ->where('id',$user_id)
        ->first();
            
        $first_date = date('Y-m-d',strtotime($userdetail->created_at));
        $last_date = date('Y-m-d',strtotime('last day of this month'));
        
        $order_amount = DB::table('order')->select(DB::raw('SUM(grand_total) as order_amount_of_month'))
            ->where('order_date','>=',$first_date)
            ->where('order_date','<=',$last_date)
            ->first();
        

        
        $user_register_date = date('Y-m-d',strtotime($userdetail->created_at));
        
        return view('checkout')->with('user_register_date',$user_register_date)->with('month_order_amount',$order_amount)->with('user_address',$user_address)->with('billing_address',$billing_address)->with('cartdata',$cartdata)->with('time_slot',$timeslot)->with('wallet',$wallet);
    }
    public function logout()
    {
        Session::flush();
        return redirect('/');
    }
    public function removeCart(Request $request)
    {
        $data = $request->input();
        $cart_id = $data['cart_id'];
        $removecart = DB::table('cart')->where('id', $cart_id)->delete();
        if($removecart)
        {
            echo "removed";
        }
        else
        {
            echo "error";
        }
    }
    public function removeWishlist(Request $request)
    {
        $data = $request->input();
        $wid = $data['wid'];
        $removewishlist = DB::table('wishlist')->where('id', $wid)->delete();
        if($removewishlist)
        {
            echo "removed";
        }
        else
        {
            echo "error";
        }
    }
    public function applyCoupon(Request $request)
    {
        $data = $request->input();
        $coupon_code = $data['coupon_code'];
        return Coupon::select('id','coupon_code','coupon_type','coupon_val')
        ->where('coupon_code',$coupon_code)
        ->get();
    }
    public function aboutus()
    {
        $data = DB::table('aboutus')->get();
        return view('aboutus')->with('content',$data);
    }
    public function company_detail()
    {
        $data = DB::table('tbl_company_document')->get();
        return view('company_detail')->with('content',$data);
    }
    public function videos()
    {
        $data = DB::table('tbl_video')->get();
        return view('videos')->with('content',$data);
    }
    public function privacy_policy()
    {
        $data = DB::table('privacy_policy')->get();
        return view('privacy_policy')->with('content',$data);
    }
    public function term_condition()
    {
        $data = DB::table('term_condition')->get();
        return view('term_condition')->with('content',$data);
    }
    public function shipping_policy()
    {
        $data = DB::table('shipping_policy')->get();
        return view('shipping_policy')->with('content',$data);
    }
    public function saveBillingAddress(Request $request)
    {
        $request->validate([
            'user_name' => 'required',
            'email'=>'required',
            'phone'=>'required',
            'address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ]);
        $data = $request->input();
        $saveaddress = DB::table('billing_address')->insertGetId(
            [
             'user_id' => Session::get('user_id'),
             'user_name' => $data['user_name'],
             'email' => $data['email'],
             'phone' => $data['phone'],
             'address' => $data['address'],
             'city' => $data['city'],
             'state' => $data['state'],
             'zip' => $data['zip'],
             'is_default' => 1,
             'created_at'=>date('y-m-d')
            ]
        );

        if($saveaddress)
        {
             DB::table('billing_address')
            ->where('user_id', Session::get('user_id'))
            ->where('id','!=',$saveaddress)
            ->update(
                [
                    'is_default' => 0,
                    'updated_at'=>date('y-m-d')
                ]
            );
            return Redirect::back()->with('success', 'Billing address saved !');
        }
        else
        {
            return Redirect::back()->with('error', 'Something went wrong !');
        }
    }
    public function saveShippingAddress(Request $request)
    {
        $request->validate([
            'fname' => 'required',
            'lname' => 'required',
            'email'=>'required',
            'phone'=>'required',
            'address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ]);
        $data = $request->input();
        $saveaddress = DB::table('user_address')->insertGetId(
            [
             'user_id' => Session::get('user_id'),
             'fname' => $data['fname'],
             'lname' => $data['lname'],
             'email' => $data['email'],
             'phone' => $data['phone'],
             'address' => $data['address'],
             'city' => $data['city'],
             'state' => $data['state'],
             'zip' => $data['zip'],
             'address_type' => $data['address_type'],
             'is_default' => 1,
             'created_at'=>date('y-m-d')
            ]
        );

        if($saveaddress)
        {
             DB::table('user_address')
            ->where('user_id', Session::get('user_id'))
            ->where('id','!=',$saveaddress)
            ->update(
                [
                    'is_default' => 0,
                    'updated_at'=>date('y-m-d')
                ]
            );
            return redirect::back()->with('success', 'Shipping address saved !');
        }
        else
        {
            return redirect::back()->with('success', 'Shipping address saved !');
        }
    }
    public function confirm_order(Request $request)
    {
        $subtotal = 0;
        $grand_total = 0;
        $shipping_charge = 0;
        $user_id = Session::get('user_id');
        $data = $request->input();
        $payment_method = $data['payment_method'];
        $prefered_time = $data['prefered_time'];
        $shipping_address = $data['shipping_address'];
        if(array_key_exists("same_as_shipping",$_POST))
        {
            $billing_address = $data['shipping_address'];
            $is_same_as_shipping_address = 1;
        }
        else
        {
            $billing_address = $data['billing_address'];
            $is_same_as_shipping_address = 0;
        }
        
        $order_id = "ORD".rand(1000,10000).$user_id;
        $cartdata = Cart::select('cart.id','cart.offer_price','cart.price','product.product_type','product.vendor_id','product.product_name','product.product_image','cart.qty','cart.product_id','cart.variant_value1','cart.variant_value2')
        ->join('product','product.id', '=', 'cart.product_id')
        ->where('cart.user_id',$user_id)
        ->get();
        
        $addressdata = DB::table('user_address')->select('zip')
        ->where('id',$shipping_address)
        ->get();
        foreach($addressdata as $ad)
        {
             $zip = $ad->zip;
        }
        foreach($cartdata as $c)
        {
            if($c->product_type == 2)
            {
                $vendor_id = $c->vendor_id;
            }
            else
            {
                $vendor_id = $this->getVendorIdForGrocerry($zip);
            }
            $subtotal = $subtotal+($c->offer_price*$c->qty);
            $productdt = array(
                'product_id'=>$c->product_id,
                'qty'=>$c->qty,
                'product_price'=>$c->price,
                'offer_price'=>$c->offer_price,
                'variant_value1'=>$c->variant_value1,
                'variant_value2'=>$c->variant_value2,
                'order_id'=>$order_id,
                'user_id'=>$user_id,
                'status'=>1,
                'vendor_id'=>$vendor_id
                );
            $productarr[] = $productdt;
        }
            if($subtotal < 500)
            {
                $shipping_charge = 100;
            }
            elseif($subtotal > 500 && $subtotal < 1000)
            {
                $shipping_charge = 70;
            }
            elseif($subtotal > 1000 && $subtotal < 2000)
            {
                $shipping_charge = 50;
            }
            else
            {
                $shipping_charge = 0;
            }
        $orderarr = array(
            'order_id'=>$order_id,
            'user_id'=>$user_id,
            'coupon_code'=>'',
            'coupon_amount'=>0,
            'shipping_charge'=>$shipping_charge,
            'payment_method'=>$payment_method,
            'sub_total'=>$subtotal,
            'grand_total'=>$subtotal+$shipping_charge,
            'order_date'=>date("Y-m-d"),
            'prefered_time'=>$prefered_time,
            'shipping_address'=>$shipping_address,
            'billing_address'=>$billing_address,
            'is_same_as_shipping_address'=>$is_same_as_shipping_address
            );
        Session::put('order_id', $order_id);
        Session::put('productarr', $productarr);
        Session::put('orderarr', $orderarr);
        Session::put('grand_total', $subtotal+$shipping_charge);
        if($payment_method == 1)
        {
            try {
                        DB::beginTransaction();
                            $productarray = Session::get('productarr');
                            $orderarray = Session::get('orderarr');
                            $orderarray["payment_status"]= 0;
                            $orderarray["order_status"]= 1;
                            $saveorder = DB::table('order')->insertGetId($orderarray);
                            if($saveorder)
                            {
                                $saveproduct = DB::table('order_product')->insert($productarray);
                                if($saveproduct)
                                {
                                    DB::commit();
                                    return redirect('order-success');
                                }
                            }
                            else
                            {
                                 DB::rollback();
                                 return redirect('order-failed');
                            }
                    } catch (Throwable $e) {
                        DB::rollback();
                        return redirect('order-failed');
                    }
        }
        elseif($payment_method == 3)
        {
            try {
                        DB::beginTransaction();
                            $productarray = Session::get('productarr');
                            $orderarray = Session::get('orderarr');
                            $orderarray["payment_status"]= 0;
                            $orderarray["order_status"]= 1;
                            $saveorder = DB::table('order')->insertGetId($orderarray);
                            if($saveorder)
                            {
                                $saveproduct = DB::table('order_product')->insert($productarray);
                                if($saveproduct)
                                {
                                    DB::commit();
                                    return redirect('order-success');
                                }
                            }
                            else
                            {
                                 DB::rollback();
                                 return redirect('order-failed');
                            }
                    } catch (Throwable $e) {
                        DB::rollback();
                        return redirect('order-failed');
                    }
        }
        else
        {
            return redirect('/payment');
        }  
    }
    public function payment()
    {
        return view('payment');
    }
    
    public function getConnection(Request $request)
    {
        $data = $request->input();
        $referral_code = $data['referral_code'];
        $connection = User::selectRaw('users.*,(Select count(id) from users u where u.parent_referral_code=users.referral_code) as total_connection, (Select sum((10*offer_price*qty)/100) from order_product op where op.user_id=users.id AND op.status=4) as total_earning')
                        ->where('parent_referral_code', $referral_code)
                        ->get();
        echo json_encode($connection);
    }
    public function getPercentOfNumber($number, $percent)
    {
        return ($percent / 100) * $number;
    }
    public function myAccount()
    {
        $user_id = Session::get('user_id');
        if($user_id)
        {
            $orderdata = Order::select('order.id','order.order_id','order.grand_total','order.order_status','order.invoice_img','order.invoice_file','order.order_date',
            'order.coupon_code','order.coupon_amount','order.shipping_charge','order.payment_id','order.sub_total','order.payment_status','order.order_status',
            'user_address.fname','user_address.lname','user_address.email','user_address.phone','user_address.address','user_address.city','user_address.state','user_address.zip',
            'billing_address.user_name','billing_address.email as bemail','billing_address.phone as bphone','billing_address.address as baddress','billing_address.city as bcity',
            'billing_address.state as bstate','billing_address.zip as bzip'
            )
            ->join('user_address','user_address.id', '=', 'order.shipping_address')
            ->join('billing_address','billing_address.id', '=', 'order.billing_address')
            ->where('order.user_id',$user_id)
            ->get();
            if(count($orderdata)>0)
            {
            foreach($orderdata as $od)
            {
                $order_id = $od->order_id;
                $order_product_detail = OrderProduct::select('order_product.id','order_product.qty','order_product.product_price','order_product.offer_price',
                'order_product.variant_value1','order_product.variant_value2','product.product_name','product.product_image','order_product.product_id','order_product.order_id','order_product.status')
                ->join('product','product.id', '=', 'order_product.product_id')
                ->where('order_id',$order_id)
                ->get();
                $odata['id'] = $od->id;
                $odata['order_id'] = $od->order_id;
                $odata['grand_total'] = $od->grand_total;
                $odata['invoice_img'] = $od->invoice_img;
                $odata['invoice_file'] = $od->invoice_file;
                $odata['order_date'] = $od->order_date;
                $odata['coupon_code'] = $od->coupon_code;
                $odata['coupon_amount'] = $od->coupon_amount;
                $odata['shipping_charge'] = $od->shipping_charge;
                $odata['payment_id'] = $od->payment_id;
                $odata['sub_total'] = $od->sub_total;
                $odata['order_status'] = $od->order_status;
                $odata['payment_status'] = $od->payment_status;
                $odata['fname'] = $od->fname;
                $odata['lname'] = $od->lname;
                $odata['email'] = $od->email;
                $odata['phone'] = $od->phone;
                $odata['address'] = $od->address;
                $odata['city'] = $od->city;
                $odata['state'] = $od->state;
                $odata['zip'] = $od->zip;
                $odata['user_name'] = $od->user_name;
                $odata['bemail'] = $od->bemail;
                $odata['bphone'] = $od->bphone;
                $odata['baddress'] = $od->baddress;
                $odata['bcity'] = $od->bcity;
                $odata['bstate'] = $od->bstate;
                $odata['bzip'] = $od->bzip;
                $odata['order_product'] = $order_product_detail;
                $orders[]=$odata;
            }
            }
            else
            {
                $orders = array();
            }
            
            $user_detail = User::select('id','fname','lname','email','gender','phone','password','referral_code','image','aadhar_front','aadhar_back')
            ->where('id',$user_id)
            ->get();
            
            $shipping_address = DB::table('user_address')->orderBy('is_default','desc')->
            select('user_address.id','fname','lname','email','phone','address','city','state','zip','address_type.address_type','is_default','address_type.id as type_id')
            ->join('address_type','address_type.id', '=', 'user_address.address_type')
            ->where('user_id',$user_id)
            ->where('is_deleted',0)
            ->get();
            
            $billing_address = DB::table('billing_address')->orderBy('is_default','desc')->select('id','user_name','email','phone','address','city','state','zip','is_default')
            ->where('user_id',$user_id)
            ->where('is_deleted',0)
            ->get();
            
            $user_bp_settlement = DB::table('tbl_user_bp_settlement')->select('users.fname','users.referral_code','tbl_user_bp_settlement.*')
            ->join('users','users.id', '=', 'tbl_user_bp_settlement.reffered_user_id')
            ->where('tbl_user_bp_settlement.user_id',$user_id)
            ->get();
            
            $user_dp_settlement = DB::table('tbl_user_dp_settlement')->select('vendor.vendor_name','tbl_user_dp_settlement.*')
            ->join('vendor','vendor.id', '=', 'tbl_user_dp_settlement.vendor_id')
            ->where('tbl_user_dp_settlement.user_id',$user_id)
            ->get();
            
            
            
            $userincomedata = DB::table('user_wallet')->select('*')->skip(0)->take(1)
            ->where('user_id',$user_id)
            ->get();
            if(count($userincomedata) > 0)
            {
                $wallet_amount = $userincomedata[0]->wallet_amount;
                $yes_amount = $userincomedata[0]->yes_amount;
                $tds_amount = $this->getPercentOfNumber($wallet_amount, 10);
                $process_fee = $this->getPercentOfNumber($wallet_amount, 5);
                $incomedata = array(
                        'status'=>true,
                        'wallet_amount'=>number_format($wallet_amount,2),
                        'yes_amount'=>$yes_amount,
                        'tds_amount'=>number_format($tds_amount,2),
                        'process_fee'=>number_format($process_fee,2),
                        'transferable_money'=>number_format($wallet_amount-$tds_amount-$process_fee,2),
                        'transferable_money1'=>$wallet_amount-$tds_amount-$process_fee
                    );
        }
            else
            {
                $incomedata = array(
                    'wallet_amount'=>0,
                    'yes_amount'=>0,
                    'tds_amount'=>0,
                    'process_fee'=>0,
                    'transferable_money'=>0
                );
        }
        
        
            // Levelwise user count upto 5 level
                $lv1_exclude_userarr = [];
                $lv2_exclude_userarr = [];
                $lv3_exclude_userarr = [];
                $lv4_exclude_userarr = [];
                $lv5_exclude_userarr = [];

                $level1_user_count = 0;
                $level2_user_count = 0;
                $level3_user_count = 0;
                $level4_user_count = 0;
                $level5_user_count = 0;
                $referral_id = $user_detail[0]->referral_code;
                $user_ref_detail = DB::table('users')->select('id')->where('referral_code', $referral_id)->first();
                $user_id = $user_ref_detail->id;
                
                // Go for level 1 settlement
                $level1user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $referral_id)->get();
                if(count($level1user) > 0)
                {
                    foreach($level1user as $lv1)
                    {
                        $level1_user_count = $level1_user_count+1;
                        array_push($lv1_exclude_userarr,$lv1->id);
        
                        // Go for level 2 settlement
                        $level2user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $lv1->referral_code)->whereNotIn('id', $lv2_exclude_userarr)->get();
                                        
                        if(count($level2user) > 0)
                        {
                            foreach($level2user as $lv2)
                            {
                                $level2_user_count = $level2_user_count+1;
                                array_push($lv2_exclude_userarr,$lv2->id);
        
                                // Go for level 3 settlement
                                $level3user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $lv2->referral_code)->whereNotIn('id', $lv3_exclude_userarr)->get();
                                if(count($level3user) > 0)
                                {
                                    foreach($level3user as $lv3)
                                    {
                                        $level3_user_count = $level3_user_count+1;
                                        array_push($lv3_exclude_userarr,$lv3->id);
                                        // Go for level 4 settlement
                                        $level4user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $lv3->referral_code)->whereNotIn('id', $lv4_exclude_userarr)->get();
                                        if(count($level4user) > 0)
                                        {
                                            foreach($level4user as $lv4)
                                            {
                                                $level4_user_count = $level4_user_count+1;
                                                array_push($lv4_exclude_userarr,$lv4->id);
                                                // Go for level 5 settlement
                                                $level5user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $lv4->referral_code)->whereNotIn('id', $lv5_exclude_userarr)->get();
                                                if(count($level5user) > 0)
                                                {
                                                    foreach($level5user as $lv5)
                                                    {
                                                        $level5_user_count = $level5_user_count+1;
                                                        array_push($lv5_exclude_userarr,$lv5->id);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
        }
        
        
            return view('my_account')
            ->with('order',$orders)
            ->with('income_data',$incomedata)
            ->with('user_detail',$user_detail)
            ->with('shipping_address',$shipping_address)
            ->with('user_bp_settlement',$user_bp_settlement)
            ->with('user_dp_settlement',$user_dp_settlement)
            ->with('level1_user_count',$level1_user_count)
            ->with('level2_user_count',$level2_user_count)
            ->with('level3_user_count',$level3_user_count)
            ->with('level4_user_count',$level4_user_count)
            ->with('level5_user_count',$level5_user_count)
            ->with('billing_address',$billing_address);
        }
        else
        {
            return redirect('login');  
        }
    }
    public function updateProfile(Request $request)
    {
        $user_id = Session::get('user_id');
        $data = $request->input();
        $fname = $data['fname'];
        $phone = $data['phone'];
        $gender = $data['gender'];
        if($data['password'] != '' && $data['confirm_password'] != ''){
            if($data['old_password'] == md5($data['password']))
            {
                $password = $data['old_password'];
            }
            else
            {
                if($data['password'] == $data['confirm_password'])
                {
                    $password = md5($data['password']);
                }
                else
                {
                    return redirect('my-account')->with('error', 'Password not match !');
                }
            }
        }
        else
        {
            $password = $data['old_password'];
        }
        if($request->hasFile('file'))
        { 
            $rimage2 = $request->file;
            $imageName2 = time().'.'.Request()->file->getClientOriginalExtension();
            $folder2 = 'user_image/';
            $image = $this->resizeWithDunamicFolderImage($rimage2,$imageName2,400,400,$folder2);

            // $fFilePath = 'public/user_image/'.$data['hidden_image'];
            // if(file_exists( public_path().'/user_image/'.$data['hidden_image'])){
            //     unlink($fFilePath);
            // }
        }
        else
        {
            $image = $data['hidden_image'];
        }
        
        // upload aadhar front start
        if($_FILES['aadhar_front']['name'] != '')
        { 
            $rimage = $request->aadhar_front;
            $imageName = rand().'.'.Request()->aadhar_front->getClientOriginalExtension();
            $folder = 'customer_aadhar/';
            $aadhar_front = $this->uploadWithoutResizeDunamicFolderImage($rimage,$imageName,0,0,$folder);
            
        }
        else
        {
                $aadhar_front = $data['hidden_aadhar_front'];
        }
        // upload aadhar front end
        
        // upload aadhar back start
        if($_FILES['aadhar_back']['name'] != '')
        { 
            $rimage1 = $request->aadhar_back;
            $imageName1 = rand().'.'.Request()->aadhar_back->getClientOriginalExtension();
            $folder1 = 'customer_aadhar/';
            $aadhar_back = $this->uploadWithoutResizeDunamicFolderImage($rimage1,$imageName1,0,0,$folder1);
            
        }
        else
        {
                $aadhar_back = $data['hidden_aadhar_back'];
        }
        // upload aadhar back end
        
        DB::table('users')
            ->where('id', Session::get('user_id'))
            ->update(
                [
                    'fname' => $fname,
                    'phone'=>$phone,
                    'aadhar_front'=>$aadhar_front,
                    'aadhar_back'=>$aadhar_back,
                    'gender'=>$gender,
                    'image'=>$image,
                    'password'=>$password
                ]
            );
        return redirect('my-account')->with('success', 'User profile updated !');
    }
    public function resizeWithDunamicFolderImage($rimage,$imageName,$width,$height,$folder)
    {
         // $filename = $rimage->getClientOriginalName();
         $image_resize = Image::make($rimage->getRealPath());
         $image_resize->resize($width,$height);
         $image_resize->save(public_path($folder.$imageName));
         if( $image_resize->save(public_path($folder.$imageName)))
         {
             return $imageName;
         }
         else
         {
            return 'default.png';
         }
    }
    public function uploadWithoutResizeDunamicFolderImage($rimage,$imageName,$width,$height,$folder)
    {
         // $filename = $rimage->getClientOriginalName();
         $image_resize = Image::make($rimage->getRealPath());
        //  $image_resize->resize($width,$height);
         $image_resize->save(public_path($folder.$imageName));
         if( $image_resize->save(public_path($folder.$imageName)))
         {
             return $imageName;
         }
         else
         {
            return 'default.png';
         }
    }
    public function updateShippingAddress(Request $request)
    {
        $request->validate([
            'fname' => 'required',
            'lname' => 'required',
            'email'=>'required',
            'phone'=>'required',
            'address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ]);
        $data = $request->input();
        $ship_id = $data['ship_id'];
            $shippupdate = DB::table('user_address')
            ->where('id',$ship_id)
            ->update(
                [
                    'fname' => $data['fname'],
                     'lname' => $data['lname'],
                     'email' => $data['email'],
                     'phone' => $data['phone'],
                     'address' => $data['address'],
                     'city' => $data['city'],
                     'state' => $data['state'],
                     'zip' => $data['zip'],
                     'address_type' => $data['address_type'] , 
                     'updated_at' => date('Y-m-d H:i:s') 
                ]
            );
            if($shippupdate)
            {
                return redirect::back()->with('success', 'Shipping address saved !');
            }
            else
            {
                return redirect::back()->with('success', 'Shipping address saved !');
            }
    }
    public function updateBillingAddress(Request $request)
    {
        $request->validate([
            'user_name' => 'required',
            'email'=>'required',
            'phone'=>'required',
            'address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ]);
        $data = $request->input();
        $bill_id = $data['bill_id'];
            $billupdate = DB::table('billing_address')
            ->where('id',$bill_id)
            ->update(
                [
                     'user_name' => $data['user_name'],
                     'email' => $data['email'],
                     'phone' => $data['phone'],
                     'address' => $data['address'],
                     'city' => $data['city'],
                     'state' => $data['state'],
                     'zip' => $data['zip'], 
                     'updated_at' => date('Y-m-d H:i:s') 
                ]
            );
            if($billupdate)
            {
                return redirect::back()->with('success', 'Shipping address saved !');
            }
            else
            {
                return redirect::back()->with('success', 'Shipping address saved !');
            }
    }
    
    public function registerStore()
    {
        return view('register-shop');
    }
    
    public function sendRequest(Request $request)
    {
        $request->validate([
            'fullname'=>'required',
            'email'=>'required',
            'phone'=>'required',
            'pincode'=>'required',
        ]);
        $data = $request->input();
        $email = $data['email'];
        $phone = $data['phone'];
        $pincode = $data['pincode'];
        $getUser =  DB::table('tblstorerequest')
                        ->where(function($q) use ($email,$phone,$pincode){
                          $q->where('email', $email)
                            ->orWhere('mobile', $phone)
                            ->orWhere('pincode', $pincode);
                        })
                    ->where('status',1)
                    ->get();
        if(count($getUser) > 0)
        {
            return redirect('/investment-plan')->with('error', 'Store already exist with this email or mobile no. or with pincode !');
        }
        else
        {
            $saveuseraddress = DB::table('tblstorerequest')->insert(
                [
                'fullname' => $data['fullname'],
                'email' => $data['email'],
                'mobile' => $data['phone'],
                'address' => $data['address'],
                'pincode' => $data['pincode'],
                'status' => 0
                ]
            );
            if($saveuseraddress)
            {
                DB::commit();
                $mailto = 'yourearningshop@gmail.com';
                $from_name = "YES";
                $from_mail = "yourearningshop@gmail.com";
                $subject = "New store request";
                $message = 'New store request recieved from '.$data['fullname'].' , Contact no :  '.$data['phone'];
                $this->sendMail($mailto, $from_mail, $from_name, $subject, $message);
                return  redirect('/investment-plan')->with('success', 'Registered successfully !');
            }
            else
            {
                DB::rollBack();
                return redirect('/investment-plan')->with('error', 'Something went wrong !');
            }
        }
    }
    public function checkRefferalCode(Request $request)
    {
        $data = $request->input();
        $referral_code = $data['referral_code'];
        return DB::table('users')
            ->where('referral_code',$referral_code)
            ->get();
    }
    public function getVendorIdForGrocerry($pincode)
    {

        $picodedata = DB::table('vendor_pincode')->select('vendor_id')->skip(0)->take(1)
        ->where('pincode',$pincode)
        ->get();
        if(count($picodedata) > 0)
        {
            $vendor_id = $picodedata[0]->vendor_id;
        }
        else
        {
            $vendor_id = 0;
        }
            
        return $vendor_id;
    }
    public function checkStoreAvailableForGrocery(Request $request)
    {
        $data = $request->input();
        $pincode = $data['pincode'];
        $picodedata = DB::table('vendor_pincode')->select('vendor_id')->skip(0)->take(1)
        ->where('pincode',$pincode)
        ->get();
        if(count($picodedata) == 0)
        {
            echo "not_available";
        }
        else
        {
            echo "available";
        }
    }
    public function contactUs()
    {
        $companydata = DB::table('company_profile')->select('*')->skip(0)->take(1)
        ->first();
        return view('contact')->with('company_detail',$companydata);
    }
    public static function getCompanyProfile()
    {
        return DB::table('company_profile')->select('*')->skip(0)->take(1)
        ->first();
    }
    public function saveContactUs(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'email'=>'required',
            'subject'=>'required',
            'message'=>'required'
        ]);
        $data = $request->input();
        $savecontactus = DB::table('contactus')->insert(
            [
             'name' => $data['name'],
             'email' => $data['email'],
             'subject' => $data['subject'],
             'message' => $data['message'],
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($savecontactus)
        {
            return redirect('/contact-us')->with('success', 'Message sent. We will contact you soon !');
        }
        else
        {
            return redirect('/contact-us')->with('success', 'Something went wrong.Please try again !');
        }
    }
    public function bulk_add_to_cart(Request $request)
    {
        $user_id = Session::get('user_id');
        if(!$user_id)
        {
            redirect('/login');
        }
        $shoping_list = DB::table('tbl_shoping_list')->select('tbl_shoping_list.id','tbl_shoping_list.product_id','tbl_shoping_list.offer_price','tbl_shoping_list.price','tbl_shoping_list.variant_value2','tbl_shoping_list.variant_value1','tbl_shoping_list.qty')
        ->join('product','product.id', '=', 'tbl_shoping_list.product_id')
        ->where('tbl_shoping_list.user_id',$user_id)
        ->get();
        
        foreach($shoping_list as $sl)
        {
            $product_id = $sl->product_id;
            $qty = $sl->qty;
            $cart_variant1 = $sl->variant_value1;
            $cart_variant2 = $sl->variant_value2;
            $price = $sl->price;
            $offer_price = $sl->offer_price;
            $catdata = Cart::select('cart.id','cart.product_id','cart.qty')
            ->where('user_id',$user_id)
            ->where('product_id',$product_id)
            ->where('variant_value1',$cart_variant1)
            ->where('variant_value2',$cart_variant2)
            ->get();
            if(count($catdata) > 0)
            {
                foreach($catdata as $cd)
                {
                    $cart_qty = $cd['qty'];
                }
                $main_qty = $qty+$cart_qty;
                $addcart = DB::table('cart')
                    ->where('product_id', $product_id)
                    ->where('user_id', $user_id)
                    ->where('variant_value1',$cart_variant1)
                    ->where('variant_value2',$cart_variant2)
                    ->update(
                        [
                            'qty' => $main_qty,
                            'updated_at'=>date('Y-m-d h:i:s')
                        ]
                    );
            }
            else
            {
                $main_qty = $qty;
                $addcart = DB::table('cart')->insert(
                        [
                            'product_id' => $product_id,
                            'user_id' => $user_id,
                            'price' => $price,
                            'offer_price' => $offer_price,
                            'variant_value1' => $cart_variant1,
                            'variant_value2' => $cart_variant2,
                            'qty' => $main_qty,
                        ]
                    );
            }
        }
        redirect('/view-cart');
        
    }
    public function forget_password()
    {
        return view('forgetpassword');
    }
    public function save_news_letter(Request $request)
    {
        $data = $request->input();
        $email = $data['email'];
        $emaildata = DB::table('users')->select('*')->get()
        ->where('email',$email)
        ->get();
        if(count($emaildata) > 0)
        {
            
        }
        else
        {
            
        }
        
    }
    public function change_mobile_no(Request $request)
    {
        $data = $request->input();
        $mobile = $data['mobile'];
        $otp = $data['otp'];
        $user_id = Session::get('user_id');
        if(Session::get('mobile_otp') == $otp)
        {
            $updatedata = array(
                    'phone' => $mobile
                );
            $update = DB::table('users')->where('id', $user_id)->update($updatedata);
            if($update)
            {
                $resarray = array(
                'msg'=>'Mobile no changed',
                'status'=>200
                );
            }
            else
            {
                $resarray = array(
                'msg'=>'Internal server error',
                'status'=>201
                );
            }
        }
        else
        {
             $resarray = array(
            'msg'=>'Invalid otp',
            'status'=>201
            );
        }
        return $resarray;
    }
    public function become_vendor()
    {
        return view('become_vendor');
    }
    public function saveVendor(Request $request)
    {
        $request->validate([
            'vendor_name'=>'required',
            'email'=>'required',
            'mobile'=>'required',
            'address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ]);
        $data = $request->input();
        $email = $data['email'];

        $usercount1 = User::select()
                ->where('email', $email)
                ->count();
                
        if($usercount1 == 1)
        {
            return redirect('/become-a-vendor')->with('error', 'Email already exist !');
        }
        else
        {
                DB::beginTransaction();

                $saveuserid = DB::table('vendor')->insertGetId(
                    [
                    'vendor_name' => $data['vendor_name'],
                    'email' => $data['email'],
                    'company_name' => $data['company_name'],
                    'mobile' => $data['mobile'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'status' => 0,
                    'service_description' => $data['service_description'],
                    'zip' => $data['zip']
                    ]
                );
                if($saveuserid)
                {

                        DB::commit();
                        $mailto = 'yourearningshop@gmail.com';
                        $from_name = "YES";
                        $from_mail = "yourearningshop@gmail.com";
                        $subject = "New vendor request";
                        $message = 'New vendor request recieved from '.$data['vendor_name'].' , Contact no :  '.$data['mobile'];
                        $this->sendMail($mailto, $from_mail, $from_name, $subject, $message);
                        return  redirect('/become-a-vendor')->with('success', 'Registered successfully ! We will contact you soon.');
                }
                else
                {
                    DB::rollBack();
                    return redirect('/become-a-vendor')->with('error', 'Something went wrong !');
                }
        }
    }
    public function store_list()
    {
       $data = DB::table('tblstorerequest')->where('status',1)->get();
        return view('store_list')->with('store_list',$data);
    }
    public function payment_request(Request $request)
    {
        $request->validate([
            'ifsc_code'=>'required',
            'account_holder'=>'required',
            'account_no'=>'required',
            'amount'=>'required'
        ]);
        $data = $request->input();
        $ifsc_code = $data['ifsc_code'];
        $account_holder = $data['account_holder'];
        $account_no = $data['account_no'];
        $amount = filter_var($data['amount'],FILTER_SANITIZE_NUMBER_INT);
        
        $userincomedata = DB::table('user_wallet')->select('*')->skip(0)->take(1)
        ->where('user_id',Session::get('user_id'))
        ->get();
        if(count($userincomedata) > 0)
        {
            $wallet_amount = $userincomedata[0]->wallet_amount;
            $tds_amount = $this->getPercentOfNumber($wallet_amount, 10);
            $process_fee = $this->getPercentOfNumber($wallet_amount, 5);
            $transferable = $wallet_amount-$tds_amount-$process_fee;
            
            if($transferable < $amount)
            {
                $res = array(
                        'status'=>false,
                        'msg'=>'Wallet amount is insufficient'
                        );
                return $res;
            }
        }
        else
        {
            $res = array(
                        'status'=>false,
                        'msg'=>'Wallet amount is insufficient'
                        );
            return $res;
        }
        
        $apiURL = 'https://api.razorpay.com/v1/payouts';
        $postInput = [
            'account_number' => '2323230074356669',
            'amount' => $amount*100,
            'currency' => 'INR',
            'mode' => 'NEFT',
            'purpose' => 'refund',
            'fund_account' => array(
                'account_type'=>'bank_account',
                'bank_account'=>array(
                    'name'=>$account_holder,
                    'ifsc'=>$ifsc_code,
                    'account_number'=>$account_no,
                    ),
                'contact'=>array(
                    'name'=>$account_holder,
                    'email'=>Session::get('user_email'),
                    'contact'=>Session::get('user_phone'),
                    'type'=>'employee',
                    'reference_id'=>'user_id'.Session::get('user_id'),
                    'notes'=>array(
                        'notes_key_1'=>'Wallet amount transfer',
                        'notes_key_2'=>''
                        )
                    )
                ),
            'queue_if_low_balance' => true,
            'reference_id'=>'user_id'.Session::get('user_id'),
            'narration'=>'Wallet amount transfer',
            'notes'=>array(
                'notes_key_1'=>'Wallet amount transfer',
                'notes_key_2'=>'',
                )
        ];

        $data = json_encode($postInput);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_URL,$apiURL);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, "rzp_test_xI4gPw1IFU6Mln:gnH4rPxmzIXW6Ys1uH2ibHSk");
        $result = curl_exec($curl);
        $decode_result = json_decode($result);
        curl_close($curl);
        // print_r($decode_result);
        
        if(empty($decode_result->error->description))
        {
            DB::beginTransaction();
            if($decode_result->status == 'processing')
            {
                $savepayment = DB::table('tbl_payment_request')->insertGetId(
                    [
                    'user_id' => Session::get('user_id'),
                    'response_id' => $decode_result->id,
                    'fund_account_id' => $decode_result->fund_account_id,
                    'amount' => $decode_result->amount,
                    'currency' => $decode_result->currency,
                    'mode' => $decode_result->mode,
                    'fund_account_json' => json_encode($decode_result->fund_account),
                    'status' => $decode_result->status
                    ]
                );
                if($savepayment)
                {
                    $updated_amount = $wallet_amount - $amount;
                        $update = DB::table('user_wallet')
                        ->where('user_id', Session::get('user_id'))
                        ->update(
                            [
                                'wallet_amount' => $updated_amount,
                                'update_date'=>date('Y-m-d H:i:s')
                            ]
                        );
                        if($update)
                        {
                            DB::commit();
                            $res = array(
                            'status'=>true,
                            'msg'=>'Payment request created successfully'
                            );
                            return $res;
                        }
                        else
                        {
                            DB::rollBack();
                            $res = array(
                            'status'=>false,
                            'msg'=>'Something went wrong .'
                            );
                            return $res;
                        }
                        
                        
                }
                else
                {
                    DB::rollBack();
                    $res = array(
                            'status'=>false,
                            'msg'=>'Something went wrong .'
                            );
                       return $res;
                }
            }
            else
            {
                $res = array(
                        'status'=>false,
                        'msg'=>'Internal server error'
                        );
                return $res;
            }
        }
        else
        {
            $res = array(
                        'status'=>false,
                        'msg'=>$decode_result->error->description
                        );
            return $res;
        }
     
    }
    public function cancel_order(Request $request)
    {
        $request->validate([
            'order_id'=>'required',
            'reason'=>'required'
        ]);
    
        
        $data = $request->input();
        $order_id = $data['order_id'];
        $cancel = DB::table('order')
        ->where('order_id', $order_id)
        ->update(
            [
                'order_status' => 5,
                'cancel_note' => $data['reason'] ,
                'cancel_date'=>date('Y-m-d H:i:s')
            ]
        );
        if($cancel)
        {
            $productcancel = DB::table('order_product')
            ->where('order_id', $order_id)
            ->update(
                [
                    'status' => 5
                ]
            );
            return  redirect('/my-account')->with('success', 'Canceled successfully !.');
        }
        else
        {
            return  redirect('/my-account')->with('success', 'Something went wrong , Please try again !.');
        }
    }
    public function return_order(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'product_id' => 'required',
            'reason' => 'required'
        ]);

        $data = $request->input();
        $order_id = $data['order_id'];
        $product_id = $data['product_id'];
        $productreturn = DB::table('order_product')
            ->where('order_id', $order_id)
            ->where('product_id', $product_id)
            ->update(
                [
                    'status' => 6,
                    'return_date' => date('Y-m-d H:i:s'),
                    'return_note' => $data['reason'],
                ]
            );
        if($productreturn)
        {
            return  redirect('/my-account')->with('success', 'Return Requested successfully !.');
        }
        else
        {
            return  redirect('/my-account')->with('error', 'Something went wrong ,Please try again !.');
        }
    }
    public function removeShippingAddress(Request $request)
    {
        $id = $request->id;
        $current_link = $_SERVER['HTTP_REFERER'];
        $removeaddress = DB::table('user_address')
            ->where('id', $id)
            ->update(
                [
                    'is_deleted' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
        if($removeaddress)
        {
            return  redirect($current_link)->with('success', 'Removed successfully !.');
        }
        else
        {
            return  redirect($current_link)->with('error', 'Something went wrong ,Please try again !.');
        }
    }
    public function removeBillingAddress(Request $request)
    {
        $id = $request->id;
        $current_link = $_SERVER['HTTP_REFERER'];
        $removeaddress = DB::table('billing_address')
            ->where('id', $id)
            ->update(
                [
                    'is_deleted' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
        if($removeaddress)
        {
            
            return  redirect($current_link)->with('success', 'Removed successfully !.');
        }
        else
        {
            return  redirect($current_link)->with('error', 'Something went wrong ,Please try again !.');
        }
    }
    
    // curl function start here
    public function store_settlement(Request $request)
    {
        $current_month = date('m');
        $trans_data = DB::table('tbl_store_transaction')->where('month',$current_month)->select('*')->get();
        if(count($trans_data) == 0)
        {
            $order_product_detail = OrderProduct::select('order_product.qty','order_product.offer_price','order_product.vendor_id',
            'tblstorerequest.deposit_amount','tblstorerequest.store_id','tblstorerequest.total_income')
            ->selectRaw("SUM(order_product.offer_price*order_product.qty) as total_sales")
            ->join('vendor','vendor.id', '=', 'order_product.vendor_id')
            ->join('tblstorerequest','tblstorerequest.store_id', '=', 'vendor.store_id')
            ->whereMonth('order_product.created_at',$current_month)
            ->where('order_product.status',4)
            ->where('vendor.user_type',0)
            ->groupBy('vendor.id')
            ->get();
            
            $setting_data = DB::table('tbl_setting')->select('*')->first();
            $dp_percentage = $setting_data->dp_percentage;
            $dp_commission = $setting_data->dp_commission;
            $dp_commission_upto = $setting_data->dp_commission_upto;
            $dp_commission_above_amount = $setting_data->dp_commission_above_amount;
            foreach($order_product_detail as $op)
            {
                $sales_commission_amount = 0;
                $deposit_amount = $op['deposit_amount'];
                $store_id = $op['store_id'];
                $total_sales = $op['total_sales'];
                $total_income = $op['total_income'];
                $store_id = $op['store_id'];
                if($total_sales > 0)
                {
                    if($total_sales <= $dp_commission_upto)
                    {
                        $dp_commission_percentage = $dp_commission;
                    }
                    else
                    {
                        $dp_commission_percentage = $dp_commission_above_amount;
                    }
                    $sales_commission_amount = ($dp_commission_percentage / 100) * $total_sales;
                }
                    
                    $dp_commission_amount = ($dp_percentage / 100) * $deposit_amount;
                    
                    $save_commission_data = array(
                        'store_id'=>$op['store_id'],
                        'vendor_id'=>$op['vendor_id'],
                        'month'=>$current_month,
                        'year'=>date('Y'),
                        'deposit_money_percentage_income'=>$dp_commission_amount,
                        'sales_percentage_income'=>$sales_commission_amount,
                        'total_amount'=>$dp_commission_amount+$sales_commission_amount,
                        'note'=>'Commission',
                        );
                    
                    DB::table('tbl_store_transaction_settlement')->insert($save_commission_data);
                    $total_income = $dp_commission_amount+$sales_commission_amount+$total_income;
                    $store_income_data = array('total_income'=>$total_income);
                    DB::table('tblstorerequest')->where('store_id', $store_id)->update($store_income_data);
    
                $deposit_percentage_commission_amount = ($dp_percentage / 100) * $deposit_amount;
                
        }
        }
    }
    public function customer_bp_settlement()
    {
        $settings = DB::table('tbl_setting')->select('bp_royality_income_percentage')->first();
        $bp_percentage = $settings->bp_royality_income_percentage;
        $date = date('Y-m-d');
        $time = date('H:i:s');
        
        // user array for exclude from loop
        $lv1_exclude_userarr = [];
        $lv2_exclude_userarr = [];
        $lv3_exclude_userarr = [];
        $lv4_exclude_userarr = [];
        $lv5_exclude_userarr = [];
        $referral_id = '8381818319';
        $user_detail = DB::table('users')->select('id')->where('referral_code', $referral_id)->first();
        $user_id = $user_detail->id;
        
        // Go for level 1 settlement
        $level1user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $referral_id)->skip(0)->take(5)->get();
        if(count($level1user) > 0)
        {
            foreach($level1user as $lv1)
            {
                // get amount from user order
                $level1useramount = DB::table('order_product')->select(DB::raw('SUM(offer_price) as amount'))->where('user_id', $lv1->id)->where('status', 4)->whereNotIn('user_id', $lv1_exclude_userarr)->groupBy('user_id')->first();
                
                if($level1useramount)
                {
                    $lv1amount = $level1useramount->amount;
                    $lv1amount = ($bp_percentage / 100) * $lv1amount;
                    $lv1arr = array(
                    'user_id'=>$user_id,
                    'reffered_user_id'=>$lv1->id,
                    'settlement_date'=>date('Y-m-d'),
                    'settlement_time'=>date('H:i:s'),
                    'amount'=>$lv1amount,
                    'type'=>'BP_SETTLEMENT',
                    'level'=>1
                    );
                    DB::table('tbl_user_bp_settlement')->insert($lv1arr);
                    
                    // Update user balance
                    $this->update_user_balance($user_id,$lv1amount);
                }
                array_push($lv1_exclude_userarr,$lv1->id);
                // Go for level 2 settlement
                $level2user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $lv1->referral_code)->whereNotIn('id', $lv2_exclude_userarr)->skip(0)->take(5)->get();
                if(count($level2user) > 0)
                {
                    foreach($level2user as $lv2)
                    {
                        
                        // get amount from user order
                        $level2useramount = DB::table('order_product')->select(DB::raw('SUM(offer_price) as amount'))->where('user_id', $lv2->id)->where('status', 4)->whereNotIn('user_id', $lv2_exclude_userarr)->groupBy('user_id')->first();
                        if($level2useramount)
                        {
                            $lv2amount = $level2useramount->amount;
                            $lv2amount = ($bp_percentage / 100) * $lv2amount;
                            $lv2arr = array(
                            'user_id'=>$user_id,
                            'reffered_user_id'=>$lv2->id,
                            'settlement_date'=>date('Y-m-d'),
                            'settlement_time'=>date('H:i:s'),
                            'amount'=>$lv2amount,
                            'type'=>'BP_SETTLEMENT',
                            'level'=>2
                            );
                            DB::table('tbl_user_bp_settlement')->insert($lv2arr);
                            
                            // Update user balance
                            $this->update_user_balance($user_id,$lv2amount);
                        }

                        array_push($lv2_exclude_userarr,$lv2->id);
                        // Go for level 3 settlement
                        $level3user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $lv2->referral_code)->whereNotIn('id', $lv3_exclude_userarr)->skip(0)->take(5)->get();
                        if(count($level3user) > 0)
                        {
                            foreach($level3user as $lv3)
                            {
                                // get amount from user order
                                $level3useramount = DB::table('order_product')->select(DB::raw('SUM(offer_price) as amount'))->where('user_id', $lv3->id)->where('status', 4)->whereNotIn('user_id', $lv3_exclude_userarr)->groupBy('user_id')->first();
                                if($level3useramount)
                                {
                                    $lv3amount = $level3useramount->amount;
                                    $lv3amount = ($bp_percentage / 100) * $lv3amount;
                                    $lv3arr = array(
                                    'user_id'=>$user_id,
                                    'reffered_user_id'=>$lv1->id,
                                    'settlement_date'=>date('Y-m-d'),
                                    'settlement_time'=>date('H:i:s'),
                                    'amount'=>$lv3amount,
                                    'type'=>'BP_SETTLEMENT',
                                    'level'=>3
                                    );
                                    DB::table('tbl_user_bp_settlement')->insert($lv3arr);
                                    
                                    // Update user balance
                                    $this->update_user_balance($user_id,$lv3amount);
                                }
                                array_push($lv3_exclude_userarr,$lv3->id);
                                // Go for level 4 settlement
                                $level4user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $lv3->referral_code)->whereNotIn('id', $lv4_exclude_userarr)->skip(0)->take(5)->get();
                                if(count($level4user) > 0)
                                {
                                    foreach($level4user as $lv4)
                                    {
                                        // get amount from user order
                                        $level4useramount = DB::table('order_product')->select(DB::raw('SUM(offer_price) as amount'))->where('user_id', $lv4->id)->where('status', 4)->whereNotIn('user_id', $lv4_exclude_userarr)->groupBy('user_id')->first();
                                        if($level4useramount)
                                        {
                                            $lv4amount = $level4useramount->amount;
                                            $lv4amount = ($bp_percentage / 100) * $lv4amount;
                                            $lv4arr = array(
                                            'user_id'=>$user_id,
                                            'reffered_user_id'=>$lv4->id,
                                            'settlement_date'=>date('Y-m-d'),
                                            'settlement_time'=>date('H:i:s'),
                                            'amount'=>$lv4amount,
                                            'type'=>'BP_SETTLEMENT',
                                            'level'=>4
                                            );
                                            DB::table('tbl_user_bp_settlement')->insert($lv4arr);
                                            
                                            // Update user balance
                                            $this->update_user_balance($user_id,$lv4amount);
                                        }
                                        array_push($lv4_exclude_userarr,$lv4->id);
                                        // Go for level 5 settlement
                                        $level5user = DB::table('users')->select('id', 'referral_code')->where('status', 1)->where('parent_referral_code', $lv4->referral_code)->whereNotIn('id', $lv5_exclude_userarr)->skip(0)->take(5)->get();
                                        if(count($level5user) > 0)
                                        {
                                            foreach($level5user as $lv5)
                                            {
                                                // get amount from user order
                                                $level5useramount = DB::table('order_product')->select(DB::raw('SUM(offer_price) as amount'))->where('user_id', $lv5->id)->where('status', 4)->whereNotIn('user_id', $lv5_exclude_userarr)->groupBy('user_id')->first();
                                                if($level5useramount)
                                                {
                                                    $lv5amount = $level5useramount->amount;
                                                    $lv5amount = ($bp_percentage / 100) * $lv5amount;
                                                    $lv5arr = array(
                                                    'user_id'=>$user_id,
                                                    'reffered_user_id'=>$lv5->id,
                                                    'settlement_date'=>date('Y-m-d'),
                                                    'settlement_time'=>date('H:i:s'),
                                                    'amount'=>$lv5amount,
                                                    'type'=>'BP_SETTLEMENT',
                                                    'level'=>5
                                                    );
                                                    DB::table('tbl_user_bp_settlement')->insert($lv5arr);
                                                    
                                                    // Update user balance
                                                    $this->update_user_balance($user_id,$lv5amount);
                                                }
                                                array_push($lv5_exclude_userarr,$lv5->id);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    public function dp_to_user_royality_monthly_settlement()
    {
        $settings = DB::table('tbl_setting')->select('dp_to_bp_sales_royality_income')->first();
        $sales_percentage = $settings->dp_to_bp_sales_royality_income;
        // Get referal user list from store request table

        $store_referal_code_list = DB::table('tbl_user_dp_settlement')->select('users.id','users.referral_code','vendor.id as vendor_id')
        ->join('users','users.referral_code', '=', 'tbl_user_dp_settlement.reffer_by')
        ->join('vendor','vendor.store_id', '=', 'tbl_user_dp_settlement.store_id')
        ->where('users.status', 1)
        ->where_not_null('tbl_user_dp_settlement.reffer_by')
        ->get();
        if(count($store_referal_code_list) > 0)
        {
            foreach($store_referal_code_list as $sl)
            {
                $useramount = DB::table('order_product')->select(DB::raw('SUM(offer_price) as amount'))->where('vendor_id', $sl->vendor_id)->where('status', 4)->groupBy('vendor_id')->first();
                if($useramount)
                {
                    $amount = ($sales_percentage / 100) * $useramount->amount;
                    $savearr = array(
                    'user_id'=>$sl->id,
                    'vendor_id'=>$sl->vendor_id,
                    'settlement_date'=>date('Y-m-d'),
                    'settlement_time'=>date('H:i:s'),
                    'amount'=>$amount,
                    'type'=>'DIPOSIT_INCOME'
                    );
                    DB::table('tbl_user_dp_settlement')->insert($savearr);
                    
                    // Update user balance
                    $this->update_user_balance($sl->id,$amount);
                    
                }
            }
        }
    }
    public function dp_to_user_commission_entire_network_settlement()
    {
        $settings = DB::table('tbl_setting')->select('dp_to_bp_sales_royality_income')->first();
        $dp_to_bp_sales_royality_income = $settings->dp_to_bp_sales_royality_income;
        // Get referal user list from store request table

        $store_referal_code_list = DB::table('tbl_user_dp_settlement')->select('users.id','users.referral_code','vendor.id as vendor_id')
        ->join('users','users.referral_code', '=', 'tbl_user_dp_settlement.reffer_by')
        ->join('vendor','vendor.store_id', '=', 'tbl_user_dp_settlement.store_id')
        ->where('users.status', 1)
        ->where_not_null('tbl_user_dp_settlement.reffer_by')
        ->get();
        if(count($store_referal_code_list) > 0)
        {
            foreach($store_referal_code_list as $sl)
            {
                $store_referal_user_list = DB::table('users')->select('users.id','users.referral_code')
                ->where('users.status', 1)
                ->where('parent_referral_code',$sl->referral_code)
                ->get();
                foreach($store_referal_user_list as $u)
                {
                    $useramount = DB::table('order_product')->select(DB::raw('SUM(offer_price) as amount'))->where('vendor_id', $sl->vendor_id)->where('status', 4)->groupBy('vendor_id')->first();
                    if($useramount)
                    {
                        $amount = ($dp_to_bp_sales_royality_income / 100) * $useramount;
                        $savearr = array(
                        'user_id'=>$u->id,
                        'vendor_id'=>$sl->vendor_id,
                        'settlement_date'=>date('Y-m-d'),
                        'settlement_time'=>date('H:i:s'),
                        'amount'=>$amount,
                        'type'=>'DEPOSIT_COMMISSION'
                        );
                        DB::table('tbl_user_dp_settlement')->insert($savearr);
                        
                        // Update user balance
                        $this->update_user_balance($u->id,$amount);
                    }
                }
            }
        }
    }
    public function update_user_balance($user_id,$amount)
    {
        $user_wallet = DB::table('user_wallet')->select('*')->where('user_id',$user_id)->first();
        if($user_wallet)
        {
            $old_wallet_amount = $user_wallet->wallet_amount;
            $old_yes_amount = $user_wallet->yes_amount;
        }
        else
        {
            $old_wallet_amount = 0;
            $old_yes_amount = 0;
        }
        $new_wallet_amount = $old_wallet_amount+$amount;
        $yes_amount = ($amount*10)/100;
        $new_yes_amount = $old_yes_amount+$yes_amount;
        
        $update_wallet = DB::table('user_wallet')
        ->where('user_id', $user_id)
        ->update(
            [
                'wallet_amount' => $new_wallet_amount,
                'yes_amount' => $new_yes_amount,
                'update_date' => date('Y-m-d H:i:s')
            ]
        );
        if($update_wallet)
        {
            return true;
        }
        else
        {
            return false;
        }
        
    }
    public function sendMail($mailto, $from_mail, $from_name, $subject, $message)
    {  

        $header = "From: ".$from_name." <".$from_mail.">\r\n";
        $header .= "MIME-Version: 1.0\n";
        $header .= "This is a multi-part message in MIME format.\r\n";
        $header .= $message."\r\n\r\n";
        if (mail($mailto, $subject, $from_name, $header)) 
        {
            return true;
        }
        else 
        {
            return false;
        }
    }
    public function sendTransferOtp(Request $request)
    {
        $data = $request->input();
        $mobile = $data['mobile'];
        $usercount = User::select()
                ->where('phone', $mobile)
                ->count();
        if($usercount == 1)
        {
            $otp = rand(100000,999999);
            Session::put('mobile_transfer_otp', $otp);
            Helper::send_otp($mobile,$otp);
            $resarray = array(
                'msg'=>'Otp send successfully',
                'otp'=>$otp,
                'status'=>200
                );
        }
        else
        {
            $resarray = array(
                'msg'=>'Mobile not registered',
                'status'=>201
                );
        }
        return $resarray;
    }
    public function verifyTransferOtp(Request $request)
    {
        $data = $request->input();
        $mobile = $data['mobile'];
        $otp = $data['otp'];
        if(Session::get('mobile_transfer_otp') == $otp)
        {
            $resarray = array(
            'msg'=>'Otp verified',
            'status'=>200
            );
        }
        else
        {
             $resarray = array(
            'msg'=>'Invalid otp',
            'status'=>201
            );
        }
        return $resarray;
    }
    
}
