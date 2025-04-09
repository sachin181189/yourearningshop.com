<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cart;
use App\Models\Driver;
use App\Models\Useraddress;
use App\Models\Coupon;
use App\Models\Products;
use App\Models\OrderProduct;
use App\Models\Order;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Validator;
use Image;
use App\Http\Helper;
use Illuminate\Support\Str;

class Usercontroller extends Controller
{
    
    public function check_user(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required|max:255'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $user_id = $data['user_id'];
        $users = User::select()
        ->where('id',$user_id)
        ->get();
        if(count($users) == 1)
        {
            $data = array(
                'status'=>true,
                'msg'=>'User exist.'
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'User not exist.'
            );
            return $data;
        }
    }
    
    public function register(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'fname' => 'required',
        'phone' => 'required',
        'password' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $phone = $data['phone'];
        $email = $data['email'];
        $OTP = rand(10000,99999);
        if($data['referral_code'] == '')
        {
            $parent_referral_code = '9650266972';
        }
        else
        {
            $parent_referral_code = $data['referral_code'];
            $referalcount = User::select()
                ->where('referral_code', $parent_referral_code)
                ->count();
            if($referalcount == 0)
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Invalid referral code'
                );
                return $data;
            }
        }
        
            $usercount = User::select()
                ->where('phone', $phone)
                ->count();
                
            if($usercount == 1)
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Mobile already registered'
                );
                return $data;
            }
            else
            {
                $saveduserid = DB::table('users')->insertGetId(
                    [
                     'fname' => $data['fname'],
                     'email' => $data['email'],
                     'phone' => $data['phone'],
                     'referral_code' => $data['phone'],
                     'auth_key' => Str::random(8),
                     'parent_referral_code' => $parent_referral_code,
                     'password' => md5($data['password']),
                     'created_at'=>date('y-m-d'),
                     'updated_at'=>date('y-m-d')
                    ]
                );
            }
            if($saveduserid)
            {
                $users = User::select()
                    ->where('id',$saveduserid)
                    ->get();
                foreach($users as $u)
                {
                    if($u->image == '')
                    {
                        $image = "default.png";
                    }
                    else
                    {
                        $image = $u->image;
                    }
                    $udata['fname'] = $u['fname'];
                    $udata['email'] = $u['email'];
                    $udata['phone'] = $u['phone'];
                    $udata['auth_key'] = $u['auth_key'];
                    $udata['user_id'] = $u['id'];
                    $udata['wallet_amount'] = "0.00";
                    $udata['referral_code'] = $u['referral_code'];
                    $udata['parent_referral_code'] = $u['parent_referral_code'];
                    $udata['image'] = url('/public/user_image').'/'.$image;
                    $udata['cart_count'] = $this->getCartCommonCount($u['id']);
                }
                $data = array(
                    'status'=>true,
                    'msg'=>'Registration success',
                    'data'=>$udata
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
    }
    
    public function sendOtp(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'phone' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $mobile = $data['phone'];
        $usercount = User::select()
            ->where('phone', $mobile)
            ->count();
        if($usercount == 1)
        {
            $data = array(
                'status'=>false,
                'msg'=>'Mobile already registered'
            );
            return $data;
        }
        else
        {
            $OTP = rand(10000,99999);
            send_otp($mobile,$OTP);
            $data = array(
                'status'=>true,
                'msg'=>'OTP send successfully',
                'OTP'=>$OTP,
                'phone'=>$mobile
            );
            return $data;
        }
    }
    public function sendForgetOtp(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'phone' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $mobile = $data['phone'];
        $usercount = User::select()
            ->where('phone', $mobile)
            ->count();
        if($usercount == 0)
        {
            $data = array(
                'status'=>false,
                'msg'=>'Mobile not registered'
            );
            return $data;
        }
        else
        {
            $OTP = rand(10000,99999);
            send_otp($mobile,$OTP);
            $data = array(
                'status'=>true,
                'msg'=>'OTP send successfully',
                'OTP'=>$OTP,
                'phone'=>$mobile
            );
            return $data;
        }
    }
    public function reset_password(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'phone' => 'required',
        'password' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $phone = $data['phone'];
        
            $usercount = User::select()
                ->where('phone', $phone)
                ->count();
                
            if($usercount == 0)
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Mobile not registered'
                );
                return $data;
            }
            else
            {
                $update = DB::table('users')
                ->where('phone', $phone)
                ->update(
                    [
                        'password' => md5($data['password']),
                        'updated_at'=>date('Y-m-d H:i:s')
                    ]
                );
            }
            if($update)
            {
                $data = array(
                    'status'=>true,
                    'msg'=>'Password changed',
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
    }
    
    public function login(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'auth_id' => 'required|max:255',
        'password' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $auth_id = $data['auth_id'];
        $password = md5($data['password']);
        $users = User::select()
        ->where(function($q) use ($auth_id){
          $q->where('email', $auth_id)
            ->orWhere('phone', $auth_id);
        })
        ->where('password',md5($data['password']))
        ->first();
        if($users)
        {
            $auth_key = update_auth_key($users->id);

            $userdata = DB::table('user_wallet')->select('*')->skip(0)->take(1)
            ->where('user_id',$users->id)
            ->get();
            if(count($userdata) > 0)
            {
                $wallet_amount = $userdata[0]->wallet_amount;
            }
            else
            {
                $wallet_amount = 0;
            }
            if($users->image == '')
            {
                $image = "default.png";
            }
            else
            {
                $image = $users->image;
            }
            $udata['wallet_amount'] = number_format($wallet_amount,2);
            $udata['fname'] = $users->fname;
            $udata['email'] = $users->email;
            $udata['phone'] = $users->phone;
            $udata['auth_key'] = $auth_key;
            $udata['image'] = url('/public/user_image').'/'.$image;
            $udata['user_id'] = $users->id;
            $udata['referral_code'] = $users->referral_code;
            $udata['parent_referral_code'] = $users->parent_referral_code;
            $udata['cart_count'] = $this->getCartCommonCount($users->id);
            
            $data = array(
                'status'=>true,
                'data'=>$udata,
                'msg'=>'Login success'
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'Invalid ID or Password',
                'data'=>(object)[]
            );
            return $data;
        }
    }
    
    public function getCartCommonCount($user_id)
    {
        // $validator =  Validator::make($request->all(),[
        // 'user_id' => 'required'
        // ]);
    
        // if($validator->fails()){
        //     return response()->json([
        //         "error" => 'validation_error',
        //         "message" => $validator->errors(),
        //     ], 200);
        // }
        
        return DB::table('cart')
            ->where('user_id', $user_id)
            ->count();
    }
    public function getCartCount(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $count = DB::table('cart')
            ->where('user_id', $user_id)
            ->count();
        $data = array(
                'status'=>true,
                'count'=>$count,
                'msg'=>'Success'
            );
            return $data;
    }
    
    public function getUserProfile(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();

            $user_id = $data['user_id'];
            $user = User::select()
                    ->where('id', $user_id)
                    ->first();
            
            if($user)
            {
                     $udata['name'] = $user->fname;
                     $udata['email'] = $user->email;
                     $udata['phone'] = $user->phone;
                     $udata['password'] = $user->password;
                     $udata['user_id'] = $user->id;
                     $udata['referral_code'] = $user->referral_code;
                     $udata['parent_referral_code'] = $user->parent_referral_code;
                     $udata['status'] = $user->status;
                    if($user->image != '')
                    {
                        $udata['image'] = url('/public/user_image').'/'.$user->image;
                    }else{
                        $udata['image'] = url('/public/user_image').'/default.png';
                    }
                     $uadata['phone'] = $user->phone;
                     $uadata['flat'] = $user->flat;
                     $uadata['area'] = $user->area;
                     $uadata['landmark'] = $user->landmark;
                     $uadata['city'] = $user->city;
                     $uadata['state'] = $user->state;
                     $uadata['zip'] = $user->zip;
                    $data = array(
                        'status'=>true,
                        'data'=>$udata,
                        'msg'=>'Success',
                        'address'=>$uadata
                    );
                    return $data;
    
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Invalid user',
                    'data'=>(object)[],
                    'address'=>(object)[]
                );
                return $data;
            }
    }
    
    public function addToCart(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'product_id' => 'required',
        'qty' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $user_id = $data['user_id'];
        $product_id = $data['product_id'];
        $qty = $data['qty'];
        $cart_variant1 = $data['cart_variant1'];
        $cart_variant2 = $data['cart_variant2'];


        $product_detail = Products::select('product.price','product.offer_price')
        ->where('product.id',$product_id)
        ->get();

        foreach($product_detail as $p)
        {
             $price = $p['price'];
             $offer_price = $p['offer_price'];
        }

        $cartcount = DB::table('cart')
        ->where('user_id', $user_id)
        ->where('product_id', $product_id)
        ->get();

        if(count($cartcount) > 0)
        {
            $cartnumber = DB::table('cart')
            ->where('user_id', $user_id)
            ->count();

            $updatecart = DB::table('cart')
            ->where('user_id', $user_id)
            ->where('product_id', $product_id)
            ->update(
                [
                    'qty' => $qty,
                    'price' => $price,
                    'offer_price' => $offer_price,
                    'variant_value1' => $cart_variant1,
                    'variant_value2' => $cart_variant2,
                    'updated_at'=>date("y-m-d H:i:s")
                ]
            );
            if($updatecart)
            {
                $data = array(
                    'status'=>true,
                    'msg'=>'Cart quantity updated',
                    'cart_count'=>$cartnumber
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
        else
        {
            $addtocart = DB::table('cart')->insert(
                [
                'qty' => $qty,
                'user_id' => $data['user_id'],
                'product_id' => $data['product_id'],
                'price' => $price,
                'variant_value1' => $cart_variant1,
                'variant_value2' => $cart_variant2,
                'offer_price' => $offer_price
                ]
            );
            if($addtocart)
            {
                $cartnumber = DB::table('cart')
                ->where('user_id', $user_id)
                ->count();
                
                $data = array(
                    'status'=>true,
                    'msg'=>'Item added to cart',
                    'cart_count'=>$cartnumber
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
    }
    
    public function addToCartList(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'product_id' => 'required',
        'qty' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $user_id = $data['user_id'];
        $product_id = $data['product_id'];
        $qty = $data['qty'];


        $product_detail = Products::select('product.price','product.offer_price','product.variant_value1','product.variant_value2')
        ->where('product.id',$product_id)
        ->get();

        foreach($product_detail as $p)
        {
            $price = $p['price'];
            $offer_price = $p['offer_price'];
            $cart_variant1 = $p['variant_value1'];
            $cart_variant2 = $p['variant_value2'];
        }

        $cartcount = DB::table('cart')
        ->where('user_id', $user_id)
        ->where('product_id', $product_id)
        ->get();

        if(count($cartcount) > 0)
        {
            $cartnumber = DB::table('cart')
            ->where('user_id', $user_id)
            ->count();

            $updatecart = DB::table('cart')
            ->where('user_id', $user_id)
            ->where('product_id', $product_id)
            ->update(
                [
                    'qty' => $qty,
                    'price' => $price,
                    'offer_price' => $offer_price,
                    'variant_value1' => $cart_variant1,
                    'variant_value2' => $cart_variant2,
                    'updated_at'=>date("y-m-d H:i:s")
                ]
            );
            if($updatecart)
            {
                $data = array(
                    'status'=>true,
                    'msg'=>'Cart quantity updated',
                    'cart_count'=>$cartnumber
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
        else
        {
            $addtocart = DB::table('cart')->insert(
                [
                'qty' => $qty,
                'user_id' => $data['user_id'],
                'product_id' => $data['product_id'],
                'price' => $price,
                'variant_value1' => $cart_variant1,
                'variant_value2' => $cart_variant2,
                'offer_price' => $offer_price
                ]
            );
            if($addtocart)
            {
                $cartnumber = DB::table('cart')
                ->where('user_id', $user_id)
                ->count();
                
                $data = array(
                    'status'=>true,
                    'msg'=>'Item added to cart',
                    'cart_count'=>$cartnumber
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
    }
    
    public function removeFromCart(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'cart_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $cart_id = $data['cart_id'];
        $removecart = DB::table('cart')->where('id', $cart_id)->delete();
        if($removecart)
        {
            $data = array(
                'status'=>true,
                'msg'=>'Item removed from cart'
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'Something went wrong'
            );
            return $data;
        }
    }
    
    public function addToShopingList(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'product_id' => 'required',
        'qty' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $user_id = $data['user_id'];
        $product_id = $data['product_id'];
        $qty = $data['qty'];
        $cart_variant1 = $data['cart_variant1'];
        $cart_variant2 = $data['cart_variant2'];


        $product_detail = Products::select('product.price','product.offer_price')
        ->where('product.id',$product_id)
        ->get();

        foreach($product_detail as $p)
        {
             $price = $p['price'];
             $offer_price = $p['offer_price'];
        }

        $shopigcount = DB::table('tbl_shoping_list')
        ->where('user_id', $user_id)
        ->where('product_id', $product_id)
        ->get();

        if(count($shopigcount) > 0)
        {
            $updateshoping = DB::table('tbl_shoping_list')
            ->where('user_id', $user_id)
            ->where('product_id', $product_id)
            ->update(
                [
                    'qty' => $qty,
                    'price' => $price,
                    'offer_price' => $offer_price,
                    'variant_value1' => $cart_variant1,
                    'variant_value2' => $cart_variant2,
                    'updated_at'=>date("y-m-d H:i:s")
                ]
            );
            if($updateshoping)
            {
                $data = array(
                    'status'=>true,
                    'msg'=>'Shoping list quantity updated'
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
        else
        {
            $addtocart = DB::table('tbl_shoping_list')->insert(
                [
                'qty' => $qty,
                'user_id' => $data['user_id'],
                'product_id' => $data['product_id'],
                'price' => $price,
                'variant_value1' => $cart_variant1,
                'variant_value2' => $cart_variant2,
                'offer_price' => $offer_price
                ]
            );
            if($addtocart)
            {
                
                $data = array(
                    'status'=>true,
                    'msg'=>'Item added to shoping list'
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
    }
    public function removeFromShopingList(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'shoping_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $shoping_id = $data['shoping_id'];
        $removecart = DB::table('tbl_shoping_list')->where('id', $shoping_id)->delete();
        if($removecart)
        {
            $data = array(
                'status'=>true,
                'msg'=>'Item removed from shoping list'
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'Something went wrong'
            );
            return $data;
        }
    }
    public function getShopingList(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];

        $shopinglist = DB::table('tbl_shoping_list')->select('tbl_shoping_list.product_id','tbl_shoping_list.id','tbl_shoping_list.qty','product.product_name','product.product_image','product.offer_price',
        'product.price','product.stock','tbl_shoping_list.variant_value1','tbl_shoping_list.variant_value2')
        ->join('product', 'product.id', '=', 'tbl_shoping_list.Product_id')
        ->where('tbl_shoping_list.user_id',$user_id)
        ->get();
        
        if(count($shopinglist) > 0)
            {
                $subtotal = 0;
                $totalDiscount = 0;
                $deleveryCharge = 0;
                foreach($shopinglist as $u)
                {
                    $product_price = $u->qty*$u->offer_price;
                    $subtotal = $subtotal+$product_price;
                    
                    $totalDiscount = $totalDiscount+($u->price-$u->offer_price);

                     $udata['id'] = $u->id;
                     $udata['product_id'] = $u->product_id;
                     $udata['product_name'] = $u->product_name;
                     $udata['price'] = number_format($u->price,2);
                     $udata['offer_price'] = number_format($u->offer_price,2);
                     $udata['discount'] = number_format((($u->price-$u->offer_price)*100)/$u->price,2);
                     $udata['qty'] = $u->qty;
                     $udata['stock'] = $u->stock;
                     if($u->variant_value1 != '')
                     {
                         $udata['variant_value1'] = $u->variant_value1;
                     }
                     else
                     {
                         $udata['variant_value1'] = '';
                     }
                     if($u->variant_value2 == '')
                     {
                         $udata['variant_value2'] = '';
                     }
                     else
                     {
                         $udata['variant_value2'] = $u->variant_value2;
                     }
                    //  $udata['total_price'] = number_format($u['offer_price']*$u['qty'],2);
                     $udata['image'] = url('/public/product_image').'/'.$u->product_image;
                     $fdata[]=$udata;
                     
                    
                }

                    $coupon_discount = 0;
                    if($subtotal < 500)
                    {
                        $deleveryCharge = 100;
                    }
                    elseif($subtotal > 500 && $subtotal < 1000)
                    {
                        $deleveryCharge = 70;
                    }
                    elseif($subtotal > 1000 && $subtotal < 2000)
                    {
                        $deleveryCharge = 50;
                    }
                    else
                    {
                        $deleveryCharge = 0;
                    }
                    $grandtotalafterdiscount = $subtotal+$deleveryCharge;
                
                $data = array(
                    'status'=>true,
                    'data'=>$fdata,
                    'grand_total'=>number_format($grandtotalafterdiscount,2),
                    'subtotal'=>number_format($subtotal,2),
                    'totalDiscount'=>number_format($totalDiscount,2),
                    'deleveryCharge'=>number_format($deleveryCharge,2)
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>true,
                    'data'=>array()
                );
                return $data;
            }
    }
    public function bulk_add_to_cart(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        
        $shoping_list = DB::table('tbl_shoping_list')->select('tbl_shoping_list.id','tbl_shoping_list.product_id','tbl_shoping_list.offer_price','tbl_shoping_list.price','tbl_shoping_list.variant_value2','tbl_shoping_list.variant_value1','tbl_shoping_list.qty')
        ->join('product','product.id', '=', 'tbl_shoping_list.product_id')
        ->where('tbl_shoping_list.user_id',$user_id)
        ->get();
        if(count($shoping_list) > 0)
        {
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
        
            $catqtydata = Cart::select('cart.id')
                ->where('user_id',$user_id)
                ->get();
                
            $data = array(
            'status'=>true,
            'cart_qty'=>count($catqtydata),
            'msg'=>'Cart list updated'
            );
            return $data;
        }
        else
        {
            $data = array(
            'status'=>false,
            'msg'=>'Something went wrong'
            );
            return $data;
        }
        
    }
    
    public function getAddressType()
    {
        $addresstype= DB::table('address_type')
                ->where('status', 1)
                ->get();
        if(count($addresstype) > 0)
        {
            foreach($addresstype as $u)
            {
                 $udata['address_type'] = $u->address_type;
                 $udata['id'] = $u->id;
                 $atype[] = $udata;
            }
            $data = array(
                'status'=>true,
                'msg'=>'Success',
                'data'=>$atype
            );
            return $data;

        }
        else
        {
            $data = array(
                'status'=>false,
                'data'=>(object)[],
                'msg'=>'Invalid data'
            );
            return $data;
        }
    }
    
    public function addNewAddress(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'fname' => 'required',
        'phone' => 'required',
        'user_id' => 'required',
        'flat' => 'required',
        'area' => 'required',
        'landmark' => 'required',
        'city' => 'required',
        'state' => 'required',
        'zip' => 'required',
        'is_default' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $checkAddress = DB::table('user_address')
        ->where('user_id',$data['user_id'])
        ->get();

        if(count($checkAddress) == 0)
        {
            $updateAddress = DB::table('users')
                            ->where('id',$data['user_id'])
                            ->update(
                                [
                                    'flat' => $data['flat'],
                                    'area' => $data['area'],
                                    'address' => $data['flat'].",".$data['area'],
                                    'landmark' => $data['landmark'],
                                    'city' => $data['city'],
                                    'state' => $data['state'],
                                    'zip' => $data['zip'],
                                ]
                            );
            
            
            $saveaddress = DB::table('user_address')->insertGetId(
                [
                'user_id' => $data['user_id'],
                'fname' => $data['fname'],
                'phone' => $data['phone'],
                'address' => $data['flat'].",".$data['area'],
                'flat' => $data['flat'],
                'area' => $data['area'],
                'landmark' => $data['landmark'],
                'city' => $data['city'],
                'state' => $data['state'],
                'address_type' => 1,
                'zip' => $data['zip'],
                'is_default' => $data['is_default'],
                'created_at'=>date('y-m-d')
                ]
            );
    
            if($saveaddress)
            {
                if($data['is_default'] == 1)
                {
                    DB::table('user_address')
                    ->where('user_id', $data['user_id'])
                    ->where('id','!=',$saveaddress)
                    ->update(
                        [
                            'is_default' => 0,
                            'updated_at'=>date('y-m-d')
                        ]
                    );
                }
                $data = array(
                    'status'=>true,
                    'msg'=>'Address saved'
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
        else
        {
            $saveaddress = DB::table('user_address')->insertGetId(
                [
                'user_id' => $data['user_id'],
                'fname' => $data['fname'],
                'phone' => $data['phone'],
                'flat' => $data['flat'],
                'address' => $data['flat'].",".$data['area'],
                'area' => $data['area'],
                'landmark' => $data['landmark'],
                'city' => $data['city'],
                'state' => $data['state'],
                'address_type' => 1,
                'zip' => $data['zip'],
                'is_default' => $data['is_default'],
                'created_at'=>date('y-m-d')
                ]
            );
    
            if($saveaddress)
            {
                if($data['is_default'] == 1)
                {
                    DB::table('user_address')
                    ->where('user_id', $data['user_id'])
                    ->where('id','!=',$saveaddress)
                    ->update(
                        [
                            'is_default' => 0,
                            'updated_at'=>date('y-m-d')
                        ]
                    );
                }
                $data = array(
                    'status'=>true,
                    'msg'=>'Address saved'
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
    }
    
    public function updatePermanentAddress(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'flat' => 'required',
        'area' => 'required',
        'landmark' => 'required',
        'city' => 'required',
        'state' => 'required',
        'zip' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $updateAddress = DB::table('users')
        ->where('id',$data['user_id'])
        ->update(
            [
                'flat' => $data['flat'],
                'area' => $data['area'],
                'address' => $data['flat'].",".$data['area'],
                'landmark' => $data['landmark'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip'],
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
                            
        if($updateAddress)
        {
            $data = array(
                'status'=>true,
                'msg'=>'Address Updated'
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'Something went wrong'
            );
            return $data;
        }
    }
    
    public function removeAddress(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'address_id' => 'required',
        'user_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $address_id = $data['address_id'];
        $useraddress = Useraddress::select()
        ->where('id',$address_id)
        ->where('user_id',$data['user_id'])
        ->get();
        if(count($useraddress) == 1)
        {
                foreach($useraddress as $ua)
                {
                    $is_default = $ua['is_default'];
                    $user_id = $ua['user_id'];
                }
                if($is_default == 0)
                {
                    
                        $removeaddress = DB::table('user_address')->where('id', $address_id)->delete();
                        if($removeaddress)
                        {
                            $data = array(
                                'status'=>true,
                                'msg'=>'Address removed'
                            );
                            return $data;
                        }
                        else
                        {
                            $data = array(
                                'status'=>false,
                                'msg'=>'Something went wrong'
                            );
                            return $data;
                        }
                }
                else
                {
                        
                        $removeaddress = DB::table('user_address')->where('id', $address_id)->delete();
                        if($removeaddress)
                        {
                            $useraddress = DB::table('user_address')
                            ->where('user_id',$user_id)
                            ->latest('id')->first();
                            if($useraddress)
                            {
                                $last_address_id = $useraddress->id;
                                $udata = DB::table('user_address')
                                ->where('id', $last_address_id)
                                ->update(
                                    [
                                        'is_default' => 1,
                                        'updated_at'=>date('y-m-d')
                                    ]
                                );
                            }
                            
                            $data = array(
                                'status'=>true,
                                'msg'=>'Address removed'
                            );
                            return $data;
                        }
                        else
                        {
                            $data = array(
                                'status'=>false,
                                'msg'=>'Something went wrong'
                            );
                            return $data;
                        }
                }
        }
        else
        {
            $data = array(
                        'status'=>false,
                        'msg'=>'No Address Found !'
                    );
                    return $data;
        }
    }
    
    public function addToWishlist(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'product_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $product_id = $data['product_id'];
        $wishlistcount = DB::table('wishlist')
        ->where('user_id', $user_id)
        ->where('product_id', $product_id)
        ->get();
        if(count($wishlistcount) > 0)
        {
            $removewishlist = DB::table('wishlist')
            ->where('user_id', $user_id)
            ->where('product_id', $product_id)
            ->delete();

            $wishcount = DB::table('wishlist')
            ->where('user_id', $user_id)
            ->get();
            
            $wcount = count($wishcount);
            

            if($removewishlist)
            {
                $Wishlistcount = Wishlist::select('id')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
            
                if(count($Wishlistcount) == 1)
                {
                    $is_wishlist_added = 1;
                }
                else
                {
                    $is_wishlist_added = 0;
                }
            
                $data = array(
                    'status'=>true,
                    'wcount'=>$wcount,
                    'is_wishlist_added'=>$is_wishlist_added,
                    'msg'=>'Item removed from wishlist'
                );
                return $data;
            }
            else
            {
                $Wishlistcount = Wishlist::select('id')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
            
                if(count($Wishlistcount) == 1)
                {
                    $is_wishlist_added = 1;
                }
                else
                {
                    $is_wishlist_added = 0;
                }
                $data = array(
                    'status'=>false,
                    'wcount'=>$wcount,
                    'is_wishlist_added'=>$is_wishlist_added,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
        else
        {
            $addtowishlist = DB::table('wishlist')->insert(
                [
                'user_id' => $data['user_id'],
                'product_id' => $data['product_id']
                ]
            );
            $wishcount = DB::table('wishlist')
            ->where('user_id', $user_id)
            ->get();
            $wcount = count($wishcount);

            if($addtowishlist)
            {
                $Wishlistcount = Wishlist::select('id')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
            
                if(count($Wishlistcount) == 1)
                {
                    $is_wishlist_added = 1;
                }
                else
                {
                    $is_wishlist_added = 0;
                }
                $data = array(
                    'status'=>true,
                    'wcount'=>$wcount,
                    'is_wishlist_added'=>$is_wishlist_added,
                    'msg'=>'Item added to wishlist'
                );
                return $data;
            }
            else
            {
                $Wishlistcount = Wishlist::select('id')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
            
                if(count($Wishlistcount) == 1)
                {
                    $is_wishlist_added = 1;
                }
                else
                {
                    $is_wishlist_added = 0;
                }
                $data = array(
                    'status'=>false,
                    'is_wishlist_added'=>$is_wishlist_added,
                    'wcount'=>$wcount,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
    }
    
    public function getCartDetail(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];

        $cartlist = Cart::select('cart.product_id','cart.id','cart.qty','product.product_name','product.product_image','product.offer_price',
        'product.price','product.stock','cart.variant_value1','cart.variant_value2')
        ->join('product', 'product.id', '=', 'cart.Product_id')
        ->where('cart.user_id',$user_id)
        ->get();
        
        if(count($cartlist) > 0)
        {
            $subtotal = 0;
            $totalDiscount = 0;
            $deleveryCharge = 0;
            foreach($cartlist as $u)
            {
                $product_price = $u['qty']*$u['offer_price'];
                $subtotal = $subtotal+$product_price;
                
                $totalDiscount = $totalDiscount+($u['price']-$u['offer_price']);

                 $udata['id'] = $u['id'];
                 $udata['product_id'] = $u['product_id'];
                 $udata['product_name'] = $u['product_name'];
                 $udata['price'] = number_format($u['price'],2);
                 $udata['offer_price'] = number_format($u['offer_price']*$u['qty'],2);
                 $udata['total_price'] = number_format($u['offer_price']*$u['qty'],2);
                 $udata['discount'] = number_format((($u['price']-$u['offer_price'])*100)/$u['price'],2);
                 $udata['qty'] = $u['qty'];
                 $udata['stock'] = $u['stock'];
                 if($u['variant_value1'] != '')
                 {
                     $udata['variant_value1'] = $u['variant_value1'];
                 }
                 else
                 {
                     $udata['variant_value1'] = '';
                 }
                 if($u['variant_value2'] == '')
                 {
                     $udata['variant_value2'] = '';
                 }
                 else
                 {
                     $udata['variant_value2'] = $u['variant_value2'];
                 }
                //  $udata['total_price'] = number_format($u['offer_price']*$u['qty'],2);
                 $udata['image'] = url('/public/product_image').'/'.$u['product_image'];
                 $fdata[]=$udata;
                 
                
            }

                $coupon_discount = 0;
                if($subtotal < 500)
                {
                    $deleveryCharge = 100;
                }
                elseif($subtotal > 500 && $subtotal < 1000)
                {
                    $deleveryCharge = 70;
                }
                elseif($subtotal > 1000 && $subtotal < 2000)
                {
                    $deleveryCharge = 50;
                }
                else
                {
                    $deleveryCharge = 0;
                }
                $grandtotalafterdiscount = $subtotal+$deleveryCharge;
            $percent = $totalDiscount/$subtotal;
            $data = array(
                'status'=>true,
                'data'=>$fdata,
                'grand_total'=>number_format($grandtotalafterdiscount,2),
                'subtotal'=>number_format($subtotal,2),
                'totalDiscount'=>number_format($totalDiscount,2),
                'percent_discount'=>number_format( $percent * 100, 2 ),
                'deleveryCharge'=>number_format($deleveryCharge,2),
                'coupon_code'=>'',
                'coupon_discount'=>0,
                'coupon_id'=>'',
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array(),
                'grand_total'=>0,
                'subtotal'=>0,
                'totalDiscount'=>0,
                'percent_discount'=>0,
                'deleveryCharge'=>0,
                'coupon_code'=>'',
                'coupon_discount'=>0,
                'coupon_id'=>''
            );
            return $data;
        }
    }
    
    public function getByNowCartDetail(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];

        $cartlist = DB::table('by_now_cart')->select('by_now_cart.product_id','by_now_cart.id','by_now_cart.qty','product.product_name','product.product_image','product.offer_price',
        'product.price','product.stock','by_now_cart.variant_value1','by_now_cart.variant_value2')
        ->join('product', 'product.id', '=', 'by_now_cart.Product_id')
        ->where('by_now_cart.user_id',$user_id)
        ->get();
        
        if(count($cartlist) > 0)
        {
            $subtotal = 0;
            $totalDiscount = 0;
            $deleveryCharge = 0;
            foreach($cartlist as $u)
            {
                $product_price = $u->qty*$u->offer_price;
                $subtotal = $subtotal+$product_price;
                
                $totalDiscount = $totalDiscount+($u->price-$u->offer_price);

                 $udata['id'] = $u->id;
                 $udata['product_id'] = $u->product_id;
                 $udata['product_name'] = $u->product_name;
                 $udata['price'] = number_format($u->price,2);
                 $udata['offer_price'] = number_format($u->offer_price*$u->qty,2);
                 $udata['total_price'] = number_format($u->offer_price*$u->qty,2);
                 $udata['discount'] = number_format((($u->price-$u->offer_price)*100)/$u->price,2);
                 $udata['qty'] = $u->qty;
                 $udata['stock'] = $u->stock;
                 if($u->variant_value1 != '')
                 {
                     $udata['variant_value1'] = $u->variant_value1;
                 }
                 else
                 {
                     $udata['variant_value1'] = '';
                 }
                 if($u->variant_value2 == '')
                 {
                     $udata['variant_value2'] = '';
                 }
                 else
                 {
                     $udata['variant_value2'] = $u->variant_value2;
                 }
                //  $udata['total_price'] = number_format($u['offer_price']*$u['qty'],2);
                 $udata['image'] = url('/public/product_image').'/'.$u->product_image;
                 $fdata[]=$udata;
                 
                
            }

                $coupon_discount = 0;
                if($subtotal < 500)
                {
                    $deleveryCharge = 100;
                }
                elseif($subtotal > 500 && $subtotal < 1000)
                {
                    $deleveryCharge = 70;
                }
                elseif($subtotal > 1000 && $subtotal < 2000)
                {
                    $deleveryCharge = 50;
                }
                else
                {
                    $deleveryCharge = 0;
                }
            $grandtotalafterdiscount = $subtotal+$deleveryCharge;
            $percent = $totalDiscount/$subtotal;
            $data = array(
                'status'=>true,
                'data'=>$fdata,
                'grand_total'=>number_format($grandtotalafterdiscount,2),
                'subtotal'=>number_format($subtotal,2),
                'totalDiscount'=>number_format($totalDiscount,2),
                'percent_discount'=>number_format( $percent * 100, 2 ),
                'deleveryCharge'=>number_format($deleveryCharge,2),
                'coupon_code'=>'',
                'coupon_discount'=>0,
                'coupon_id'=>'',
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array(),
                'grand_total'=>0,
                'subtotal'=>0,
                'totalDiscount'=>0,
                'percent_discount'=>0,
                'deleveryCharge'=>0,
                'coupon_code'=>'',
                'coupon_discount'=>0,
                'coupon_id'=>''
            );
            return $data;
        }
    }
    
    public function getUserAddress(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $useraddress = Useraddress::select('user_address.*')
        ->where('user_address.user_id',$user_id)
        ->get();
        if(count($useraddress) > 0)
            {
                foreach($useraddress as $u)
                {
                     $udata['id'] = $u['id'];
                     $udata['fname'] = $u['fname'];
                     $udata['phone'] = $u['phone'];
                     $udata['flat'] = $u['flat'];
                     $udata['area'] = $u['area'];
                     $udata['landmark'] = $u['landmark'];
                     $udata['city'] = $u['city'];
                     $udata['state'] = $u['state'];
                     $udata['zip'] = $u['zip'];
                     $udata['is_default'] = $u['is_default'];
                     $fdata[]=$udata;
                }
                $data = array(
                    'status'=>true,
                    'msg'=>'Success',
                    'data'=>$fdata,
                    'addressSaved'=>count($useraddress)
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'No data available',
                    'addressSaved'=>0,
                    'data'=>array()
                );
                return $data;
            }
    }
    
    public function applyCoupon(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'coupon_code' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $coupon_code = $data['coupon_code'];
        $user_id = $data['user_id'];

        $getcoupon = Coupon::select()
            ->where('coupon_code', $coupon_code)
            ->get();
            if(count($getcoupon) > 0)
            {
                foreach($getcoupon as $gc)
                {
                    $coupon_id = $gc['id'];
                    $coupon_code = $gc['coupon_code'];
                    $coupon_type = $gc['coupon_type'];
                    $coupon_val = $gc['coupon_val'];
                }
                $cartlist = Cart::select('cart.product_id','cart.id','cart.qty','product.product_name','product.product_image','product.offer_price',
                'product.price','product.stock','cart.variant_value1','cart.variant_value2')
                ->join('product', 'product.id', '=', 'cart.Product_id')
                ->where('cart.user_id',$user_id)
                ->get();
        
                if(count($cartlist) > 0)
                {
                    $subtotal = 0;
                    $totalDiscount = 0;
                    $deleveryCharge = 0;
                    foreach($cartlist as $u)
                    {
                        $product_price = $u['qty']*$u['offer_price'];
                        $subtotal = $subtotal+$product_price;
                        
                        $totalDiscount = $totalDiscount+($u['price']-$u['offer_price']);
        
                         $udata['id'] = $u['id'];
                         $udata['product_id'] = $u['product_id'];
                         $udata['product_name'] = $u['product_name'];
                         $udata['price'] = number_format($u['price'],2);
                         $udata['offer_price'] = number_format($u['offer_price'],2);
                         $udata['total_price'] = number_format($u['offer_price']*$u['qty'],2);
                         $udata['discount'] = number_format((($u['price']-$u['offer_price'])*100)/$u['price'],2);
                         $udata['qty'] = $u['qty'];
                         $udata['stock'] = $u['stock'];
                         if($u['variant_value1'] != '')
                         {
                             $udata['variant_value1'] = $u['variant_value1'];
                         }
                         else
                         {
                             $udata['variant_value1'] = '';
                         }
                         if($u['variant_value2'] == '')
                         {
                             $udata['variant_value2'] = '';
                         }
                         else
                         {
                             $udata['variant_value2'] = $u['variant_value2'];
                         }
                         $udata['image'] = url('/public/product_image').'/'.$u['product_image'];
                         $fdata[]=$udata;
                    }
        
                        $coupon_discount = 0;
                        if($coupon_type == 'percent')
                        {
                            $coupon_discount = ($coupon_val/ 100) * $subtotal;
                        }
                        else
                        {
                            $coupon_discount = $coupon_val;
                        }
                        if($subtotal < 500)
                        {
                            $deleveryCharge = 100;
                        }
                        elseif($subtotal > 500 && $subtotal < 1000)
                        {
                            $deleveryCharge = 70;
                        }
                        elseif($subtotal > 1000 && $subtotal < 2000)
                        {
                            $deleveryCharge = 50;
                        }
                        else
                        {
                            $deleveryCharge = 0;
                        }
                        $grandtotalafterdiscount = ($subtotal+$deleveryCharge)-$coupon_discount;
                    
                    $data = array(
                        'status'=>true,
                        'data'=>$fdata,
                        'grand_total'=>number_format($grandtotalafterdiscount,2),
                        'subtotal'=>number_format($subtotal,2),
                        'totalDiscount'=>number_format($totalDiscount,2),
                        'coupon_code'=>$coupon_code,
                        'coupon_discount'=>number_format($coupon_discount,2),
                        'coupon_id'=>$coupon_id,
                        'msg'=>'Coupon applied'
                    );
                    return $data;
                }
                else
                {
                    $data = array(
                        'status'=>false,
                        'msg'=>'Invalid cart data'
                    );
                    return $data;
                }
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Invalid coupon'
                );
                return $data;
            }
    }
    

    
    public function getUserOrderList(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $userorder = Order::select('order.*')
        ->where('order.user_id',$user_id)
        ->get();
        if(count($userorder) > 0)
        {
            foreach($userorder as $u)
            {
                if($u['order_status'] == 1)
                {
                    $orderstatus = 'Confirmed';
                }
                elseif($u['order_status'] == 2)
                {
                    $orderstatus = 'Packed';

                }
                elseif($u['order_status'] == 3)
                {
                    $orderstatus = 'Shipped';
                    
                }
                elseif($u['order_status'] == 4)
                {
                    $orderstatus = 'Delivered';
                    
                }
                elseif($u['order_status'] == 5)
                {
                    $orderstatus = 'Canceled';
                    
                }
                elseif($u['order_status'] == 6)
                {
                    $orderstatus = 'Returned';
                    
                }
                $udata['id'] = $u['id'];
                $udata['order_id'] = $u['order_id'];
                $udata['payment_id'] = $u['payment_id'];
                $udata['payment_method'] = $u['payment_method'];
                $udata['sub_total'] = $u['sub_total'];
                $udata['grand_total'] = $u['grand_total'];
                $udata['order_status'] = $orderstatus;
                $udata['order_date'] = $u['order_date'];
                if($u['invoice_file'] != '')
                {
                    $udata['invoice'] = url('/public/invoice').'/'.$u['invoice_file'];
                }
                else
                {
                    $udata['invoice'] = '';
                }
                $fdata[]=$udata;
            }
            $data = array(
                'status'=>true,
                'data'=>$fdata
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
            return $data;
        }
    }
    
    public function updateProfile(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'fname' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        if($request->hasFile('image'))
        { 
            $rimage = $request->image;
            $imageName = time().'.'.Request()->image->getClientOriginalExtension();
            $folder = 'user_image/';
            $image = $this->resizeWithDunamicFolderImage($rimage,$imageName,600,600,$folder);
        }
        else
        {
            $user_detail = DB::table('users')->select('users.image')
            ->where('users.id',$data['user_id'])
            ->get();
            foreach($user_detail as $u)
            {
                $image = $u->image;
            }
        }

        if($data['new_password'] != '')
        {
            $udata = DB::table('users')
            ->where('id', $data['user_id'])
            ->update(
                [
                    'fname' => $data['fname'],
                    'image' => $image,
                    'password' => md5($data['new_password']),
                    'updated_at'=>date('y-m-d H:i:s')
                ]
            );
        }
        else
        {
            $udata = DB::table('users')
            ->where('id', $data['user_id'])
            ->update(
                [
                    'fname' => $data['fname'],
                    'email' => $data['email'],
                    'image' => $image,
                    'phone' => $data['phone'],
                    'updated_at'=>date('y-m-d H:i:s')
                ]
            );
        }
        if($udata)
        {
            $data = array(
                'status'=>true,
                'msg'=>'Profile updated'
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'Something went wrong'
            );
            return $data;
        }
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
    public function orderDetail(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'order_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $order_id = $data['order_id'];
        $order_detail = Order::select('order.*')
        ->where('order.id',$order_id)
        ->get();
        if(count($order_detail) > 0)
        {
            foreach($order_detail as $u)
            {
                $is_cancel = 1;
                if($u['order_status'] == 1)
                {
                    $orderstatus = 'Confirmed';
                }
                elseif($u['order_status'] == 2)
                {
                    $orderstatus = 'Packed';

                }
                elseif($u['order_status'] == 3)
                {
                    $orderstatus = 'Shipped';
                    
                }
                elseif($u['order_status'] == 4)
                {
                    $orderstatus = 'Delivered';
                    $is_cancel = 0;
                    
                }
                elseif($u['order_status'] == 5)
                {
                    $orderstatus = 'Canceled';
                    $is_cancel = 0;
                    
                }
                elseif($u['order_status'] == 6)
                {
                    $orderstatus = 'Returned';
                    $is_cancel = 0;
                    
                }
                $udata['id'] = $u['id'];
                $udata['order_id'] = $u['order_id'];
                $udata['payment_id'] = $u['payment_id'];
                $udata['payment_method'] = $u['payment_method'];
                $udata['sub_total'] = $u['sub_total'];
                $udata['grand_total'] = $u['grand_total'];
                $udata['order_status'] = $orderstatus;
                $udata['order_date'] = $u['order_date'];
                $udata['shipping_charge'] = $u['shipping_charge'];
                $udata['coupon_code'] = $u['coupon_code'];
                $udata['is_cancel_show'] = $is_cancel;
                $udata['coupon_amount'] = $u['coupon_amount'];
                if($u['invoice_file'] != '')
                {
                    $udata['invoice'] = url('/public/invoice').'/'.$u['invoice_file'];
                }
                else
                {
                    $udata['invoice'] = '';
                }
                $shipping_address = $u['shipping_address'];
                $orderid = $u['order_id'];
            }

            $orderproduct = OrderProduct::select('order_product.*','product.product_name','product.product_image','product.return_days','product.is_return_policy')
            ->join('product', 'product.id', '=', 'order_product.product_id')
            ->where('order_product.order_id',$orderid)
            ->get();
            foreach($orderproduct as $u)
            {
                 $ordpdata['product_price'] = number_format($u['product_price'],2);
                 $ordpdata['product_image'] = url('/public/product_image').'/'.$u['product_image'];
                 $ordpdata['offer_price'] = number_format($u['offer_price'],2);
                 $ordpdata['qty'] = $u['qty'];
                 $ordpdata['product_id'] = $u['product_id'];
                 $ordpdata['order_id'] = $u['order_id'];
                 $ordpdata['grand_total'] = number_format($u['qty']*$u['offer_price'],2);
                 $ordpdata['product_name'] = $u['product_name'];
                 if($u['is_return_policy'] == 1 && $u['status'] == 4)
                 {
                    if($orderstatus == 'Delivered')
                    {
                        $return_last_date = date(date("Y-m-d", strtotime($u['delivery_date'])), strtotime("+".$u['return_days']." days"));
                        $current_date = date('Y-m-d');
                        if($return_last_date < $current_date)
                        {
                            $ordpdata['is_return_show'] = 1;
                        }
                        else
                        {
                            $ordpdata['is_return_show'] = 0;
                        }
                    }
                    else
                    {
                        $ordpdata['is_return_show'] = 0;
                    }
                 }
                 else
                 {
                     $ordpdata['is_return_show'] = 0;
                 }
                 if($u['variant_value1'] != '')
                 {
                     $ordpdata['variant_value1'] = $u['variant_value1'];
                 }
                 else
                 {
                     $ordpdata['variant_value1'] = '';
                 }
                 if($u['variant_value2'] == '')
                 {
                     $ordpdata['variant_value2'] = '';
                 }
                 else
                 {
                     $ordpdata['variant_value2'] = $u['variant_value2'];
                 }
                 $opdata[]=$ordpdata;
            }

            $useraddress = Useraddress::select('user_address.*','address_type.address_type')
            ->join('address_type', 'address_type.id', '=', 'user_address.address_type')
            ->where('user_address.id',$shipping_address)
            ->get();
            $adddata = array();
            foreach($useraddress as $ua)
            {
                 $adddata['id'] = $ua['id'];
                 $adddata['fname'] = $ua['fname'];
                 $adddata['email'] = $ua['email'];
                 $adddata['phone'] = $ua['phone'];
                 $adddata['address'] = $ua['address'];
                 $adddata['city'] = $ua['city'];
                 $adddata['state'] = $ua['state'];
                 $adddata['zip'] = $ua['zip'];
                 $adddata['address_type'] = $ua['address_type'];
            }

            $data = array(
                'status'=>true,
                'order_detail'=>$udata,
                'shipping_address'=>$adddata,
                'order_product'=>$opdata
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>'No data available'
            );
            return $data;
        }
    }
    public function getWishlist(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $wishlist = Wishlist::select('wishlist.product_id','wishlist.id','product.product_name','product.product_image','product.offer_price','product.stock')
        ->join('product', 'product.id', '=', 'wishlist.product_id')
        ->where('wishlist.user_id',$user_id)
        ->get();
        if(count($wishlist) > 0)
        {
            foreach($wishlist as $u)
            {
                if($u['stock'] > 0)
                {
                    $stockstatus = 'In Stock';
                    $disabled = 'enabled';
                }
                else
                {
                    $stockstatus = 'Out Of Stock';
                    $disabled = 'disabled';

                }
                 $udata['id'] = $u['id'];
                 $udata['Product_id'] = $u['product_id'];
                 $udata['product_name'] = $u['product_name'];
                 $udata['qty'] = $u['qty'];
                 if($u['variant_value1'] != '')
                 {
                     $udata['variant_value1'] = $u['variant_value1'];
                 }
                 else
                 {
                     $udata['variant_value1'] = '';
                 }
                 if($u['variant_value2'] == '')
                 {
                     $udata['variant_value2'] = '';
                 }
                 else
                 {
                     $udata['variant_value2'] = $u['variant_value2'];
                 }
                 $udata['stock_status'] = $stockstatus;
                 $udata['disabled'] = $disabled;
                 $udata['total_price'] = number_format($u['offer_price']*$u['qty'],2);
                 $udata['offer_price'] = number_format($u['offer_price'],2);
                 $udata['image'] = url('/public/product_image').'/'.$u['product_image'];
                 $fdata[]=$udata;
            }
                $data = array(
                    'status'=>true,
                    'data'=>$fdata
                );
                return $data;
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
            return $data;
        }
    }
    public function moveToCart(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'wishlist_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $wishlist_id = $data['wishlist_id'];
        $wishlist = Wishlist::select('wishlist.product_id','wishlist.id','wishlist.user_id','product.price','product.offer_price')
        ->join('product', 'product.id', '=', 'wishlist.product_id')
        ->where('wishlist.id',$wishlist_id)
        ->get();

        foreach($wishlist as $u)
        {
             $product_id = $u['product_id'];
             $user_id = $u['user_id'];
             $price = $u['price'];
             $offer_price = $u['offer_price'];
        }

        $cartcount = Cart::select()
        ->where('user_id', $user_id)
        ->where('product_id', $product_id)
        ->get();


        if(count($cartcount) > 0)
        {
            foreach($cartcount as $c)
            {
                $qty = $c['qty'];
            }
            $updatecart = DB::table('cart')
            ->where('user_id', $user_id)
            ->where('product_id', $product_id)
            ->update(
                [
                    'qty' => $qty+1,
                    'price' => $price,
                    'offer_price' => $offer_price
                ]
            );
            if($updatecart)
            {
                $removewishlist = DB::table('wishlist')->where('id', $wishlist_id)->delete();
                $data = array(
                    'status'=>true,
                    'msg'=>'Cart quantity updated'
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
        else
        {
            $addtocart = DB::table('cart')->insert(
                [
                'qty' => 1,
                'user_id' => $user_id,
                'product_id' => $product_id,
                'price' => $price,
                'offer_price' => $offer_price
                ]
            );
            if($addtocart)
            {
                $removewishlist = DB::table('wishlist')->where('id', $wishlist_id)->delete();
                $data = array(
                    'status'=>true,
                    'msg'=>'Item added to cart'
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }

    }
    function removeFromWishlist(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'wishlist_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $wishlist_id = $data['wishlist_id'];
        $removewishlist = DB::table('wishlist')->where('id', $wishlist_id)->delete();
        if($removewishlist)
        {
            $data = array(
                'status'=>true,
                'msg'=>'Item removed from wishlist'
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'Something went wrong'
            );
            return $data;
        }
    }
    function vendorRegister(Request $request)
    {
        $data = $request->input();
        $savevendor = DB::table('vendor')->insert(
            [
             'vendor_name' => $data['vendor_name'],
             'company_name' => $data['company_name'],
             'email' => $data['email'],
             'mobile' => $data['mobile'],
             'gst_no' => $data['gst_no'],
             'alternate_mobile' => $data['alternate_mobile'],
             'address' => $data['address'],
             'city' => $data['city'],
             'state' => $data['state'],
             'zip' => $data['zip'],
             'service_description' => $data['service_description'],
             'password' => md5($data['password']),
             'status' => 0,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($savevendor)
        {
            $savenotification = DB::table('notification')->insert(
                [
                 'notification' => $data['vendor_name'].' registered as vendor',
                 'slug' => 'vendor-list',
                 'url' => url('/vendor-list'),
                 'status' => 0,
                 'created_at'=>date('y-m-d'),
                 'updated_at'=>date('y-m-d')
                 ]
            );
            $data = array(
                'status'=>true,
                'msg'=>'Registered successfully'
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>true,
                'msg'=>'Something went wrong'
            );
            return $data;
        }
    }
    public function socialRegister(Request $request)
    {
 
            $data = $request->input();
            $social_user_id = $data['social_user_id'];
            
            $usercount = User::select()
                ->where('social_user_id', $social_user_id)
                ->count();
            if($usercount == 1)
            {
                $userdetail = User::select()
                ->where('social_user_id', $social_user_id)
                ->get();
                foreach($userdetail as $u)
                {
                     $udata['social_user_id'] = $u['social_user_id'];
                     $udata['fname'] = $u['fname'];
                     $udata['email'] = $u['email'];
                     $udata['phone'] = $u['phone'];
                     $udata['user_id'] = $u['id'];
                     $udata['referral_code'] = $u['referral_code'];
                     $udata['parent_referral_code'] = $u['parent_referral_code'];
                }
                $data = array(
                    'status'=>true,
                    'data'=>$udata,
                    'msg'=>'Login successfully'
                );
                return $data;
            }
            else
            {
                $saveuser = DB::table('users')->insert(
                    [
                     'social_user_id' => $data['social_user_id'],
                     'fname' => $data['fname'],
                     'email' => $data['email'],
                     'phone' => $data['phone'],
                     'created_at'=>date('y-m-d'),
                     'updated_at'=>date('y-m-d')
                    ]
                );
                if($saveuser)
                {
                    $userdetail = User::select()
                    ->where('social_user_id', $social_user_id)
                    ->get();

                    foreach($userdetail as $u)
                    {
                        $udata['social_user_id'] = $u['social_user_id'];
                        $udata['fname'] = $u['fname'];
                        $udata['email'] = $u['email'];
                        $udata['phone'] = $u['phone'];
                        $udata['user_id'] = $u['id'];
                        $udata['referral_code'] = $u['referral_code'];
                        $udata['parent_referral_code'] = $u['parent_referral_code'];
                    }
                    $data = array(
                        'status'=>true,
                        'msg'=>'Login successfully',
                        'data'=>$udata
                    );
                    return $data;
                }
                else
                {
                    $data = array(
                        'status'=>false,
                        'msg'=>'Invalid User'
                    );
                    return $data;
                }
            }
    }
    public function setDefaultAddress(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'address_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $address_id = $data['address_id'];
        $user_id = $data['user_id'];
        $adddata = DB::table('user_address')
        ->where('id', $address_id)
        ->update(
            [
                'is_default' => 1,
                'updated_at'=>date('y-m-d H:i:s')
            ]
        );
        if($adddata)
        {
            DB::table('user_address')
            ->where('id','!=', $address_id)
            ->where('user_id', $user_id)
            ->update(
                [
                    'is_default' => 0,
                    'updated_at'=>date('y-m-d H:i:s')
                ]
            );
            $data = array(
                        'msg'=>'Success',
                        'status'=>true
                    );
            return $data;
        }
        else
        {
            $data = array(
                        'status'=>false,
                        'msg'=>'Someting went wrong'
                    );
            return $data;
        }
    }
    public function saveOrder(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'address_id' => 'required',
            'billing_address' => 'required',
            'payment_method' => 'required',
            'user_id' => 'required',
            'wallet_amount' => 'required',
            'delivery_charge' => 'required',
            'subtotal' => 'required',
            'grand_total' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $address_id = $data['address_id'];
        $billing_address = $data['billing_address'];
        $payment_method = $data['payment_method'];
        $payment_id = $data['payment_id'];
        $user_id = $data['user_id'];
        $orderid = "ORD".date("ymdhis")."".$user_id;
        $coupon_code = $data['coupon_code'];
        $coupon_discount = $data['coupon_discount'];
        $subtotal = $data['subtotal'];
        $grand_total = $data['grand_total'];
        $wallet_amount = $data['wallet_amount'];
        $delivery_charge = $data['delivery_charge'];
        DB::beginTransaction();
            $saveorder = DB::table('order')->insert(
            [
                'order_id'=>$orderid,
                'user_id'=>$user_id,
                'coupon_code'=>$coupon_code,
                'billing_address'=>$billing_address,
                'coupon_amount'=>$coupon_discount,
                'shipping_method'=>1,
                'shipping_charge'=>$delivery_charge,
                'payment_id'=>$payment_id,
                'address_type'=>1,
                'payment_method'=>$payment_method,
                'sub_total'=>$subtotal,
                'grand_total'=>$grand_total,
                'shipping_address'=>$address_id,
                'order_status'=>1,
                'payment_status'=>1,
                'order_date'=>date('Y-m-d')
            ]
        );
        if($saveorder)
        {
            $cartdata = Cart::select('cart.id','cart.offer_price','cart.price','product.product_type','product.vendor_id','product.product_name','product.product_image','cart.qty','cart.product_id','cart.variant_value1','cart.variant_value2')
            ->join('product','product.id', '=', 'cart.product_id')
            ->where('cart.user_id',$user_id)
            ->get();
        
            $addressdata = DB::table('user_address')->select('zip')
            ->where('id',$address_id)
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
                    'order_id'=>$orderid,
                    'user_id'=>$user_id,
                    'vendor_id'=>$vendor_id
                    );
                $productarr[] = $productdt;
            }
                $saveproduct = DB::table('order_product')->insert($productarr);
                if($saveproduct)
                {
                    DB::table('cart')->where('user_id', $user_id)->delete();
                    DB::table('by_now_cart')->where('user_id', $user_id)->delete();
                    if($payment_method == 3)
                    {
                        DB::table('user_wallet')->where('user_id', $user_id)->decrement('wallet_amount', $wallet_amount);

                    }
                    DB::commit();
                    $data = array(
                    'status'=>true,
                    'msg'=>'Order placed'
                    );
                    return $data;
                }
                else
                {
                     DB::rollback();
                     $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                    );
                    return $data;
                }
        }
        else
        {
             DB::rollback();
             $data = array(
                    'status'=>false,
                    'msg'=>'Order failed'
                    );
                    return $data;
        }
    }
    public function saveOrder1(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'address_id' => 'required',
            'billing_address' => 'required',
            'payment_method' => 'required',
            'user_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $address_id = $data['address_id'];
        $billing_address = $data['billing_address'];
        $payment_method = $data['payment_method'];
        $payment_id = $data['payment_id'];
        $user_id = $data['user_id'];
        $wallet_amount = $data['wallet_amount'];
        $coupon_id = $data['coupon_id'];
        $orderid = "ORD".date("ymdhis")."".$user_id;

            $getcoupon = Coupon::select()
            ->where('id', $coupon_id)
            ->get();
            if(count($getcoupon) > 0)
            {
                foreach($getcoupon as $gc)
                {
                    $coupon_id = $gc['id'];
                    $coupon_code = $gc['coupon_code'];
                    $coupon_type = $gc['coupon_type'];
                    $coupon_val = $gc['coupon_val'];
                }
            }
            else
            {
                $coupon_code = '';
            }

                $cartlist = Cart::select('cart.qty','product.offer_price','product.price')
                ->join('product', 'product.id', '=', 'cart.Product_id')
                ->where('cart.user_id',$user_id)
                ->get();
        
                if(count($cartlist) > 0)
                {
                    $subtotal = 0;
                    $totalDiscount = 0;
                    $deleveryCharge = 0;
                    foreach($cartlist as $u)
                    {
                        $product_price = $u['qty']*$u['offer_price'];
                        $subtotal = $subtotal+$product_price;
                        $totalDiscount = $totalDiscount+($u['price']-$u['offer_price']);
                    }
        
                        $coupon_discount = 0;
                        if(count($getcoupon) > 0)
                        {
                            if($coupon_type == 'percent')
                            {
                                $coupon_discount = ($coupon_val/ 100) * $subtotal;
                            }
                            else
                            {
                                $coupon_discount = $coupon_val;
                            }
                        }
                        if($subtotal < 500)
                        {
                            $deleveryCharge = 100;
                        }
                        elseif($subtotal > 500 && $subtotal < 1000)
                        {
                            $deleveryCharge = 70;
                        }
                        elseif($subtotal > 1000 && $subtotal < 2000)
                        {
                            $deleveryCharge = 50;
                        }
                        else
                        {
                            $deleveryCharge = 0;
                        }
                        $grandtotalafterdiscount = ($subtotal+$deleveryCharge)-$coupon_discount;
                }
                else
                {
                    DB::rollback();
                     $data = array(
                    'status'=>false,
                    'msg'=>'No data in your cart'
                    );
                    return $data;
                }
        
        
        DB::beginTransaction();
            $saveorder = DB::table('order')->insert(
            [
                'order_id'=>$orderid,
                'user_id'=>$user_id,
                'coupon_code'=>$coupon_code,
                'billing_address'=>$billing_address,
                'coupon_amount'=>$coupon_discount,
                'shipping_method'=>1,
                'shipping_charge'=>$deleveryCharge,
                'payment_id'=>$payment_id,
                'payment_method'=>$payment_method,
                'sub_total'=>$subtotal,
                'grand_total'=>$grandtotalafterdiscount,
                'shipping_address'=>$address_id,
                'order_status'=>1,
                'payment_status'=>1,
                'order_date'=>date('Y-m-d')
            ]
        );
        if($saveorder)
        {
            $cartdata = Cart::select('cart.id','cart.offer_price','cart.price','product.product_type','product.vendor_id','product.product_name','product.product_image','cart.qty','cart.product_id','cart.variant_value1','cart.variant_value2')
            ->join('product','product.id', '=', 'cart.product_id')
            ->where('cart.user_id',$user_id)
            ->get();
        
            $addressdata = DB::table('user_address')->select('zip')
            ->where('id',$address_id)
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
                    'order_id'=>$orderid,
                    'user_id'=>$user_id,
                    'vendor_id'=>$vendor_id
                    );
                $productarr[] = $productdt;
            }
                $saveproduct = DB::table('order_product')->insert($productarr);
                if($saveproduct)
                {
                    DB::table('cart')->where('user_id', $user_id)->delete();
                    DB::table('by_now_cart')->where('user_id', $user_id)->delete();
                    if($payment_method == 3)
                    {
                        DB::table('user_wallet')->where('user_id', $user_id)->decrement('wallet_amount', $wallet_amount);

                    }
                    DB::commit();
                    $data = array(
                    'status'=>true,
                    'msg'=>'Order placed'
                    );
                    return $data;
                }
                else
                {
                     DB::rollback();
                     $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                    );
                    return $data;
                }
        }
        else
        {
             DB::rollback();
             $data = array(
                    'status'=>false,
                    'msg'=>'Order failed'
                    );
                    return $data;
        }
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
    public function customerNetwork(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'referral_code' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $referral_code = $data['referral_code'];
        $userlist = User::select('id','referral_code','fname','lname')
        ->where('parent_referral_code', $referral_code)
        ->get();
        if(count($userlist) > 0)
        {
            $data = array(
            'status'=>true,
            'network'=>$userlist
            );
        }
        else
        {
            $data = array(
            'status'=>false,
            'network'=>array()
            );
        }
            return $data;

    }
    public function achieversList()
    {
        $userlist = User::select('users.id','users.fname','users.lname','tbl_achivers.rank','tbl_achivers.about','users.image')
        ->join('tbl_achivers', 'tbl_achivers.user_id', '=', 'users.id')
        ->where('tbl_achivers.status', 1)
        ->get();
        if(count($userlist) > 0)
        {
            foreach($userlist as $u)
            {
                $adata['id'] = $u['id'];
                $adata['fname'] = $u['fname'];
                $adata['lname'] = $u['lname'];
                if($u['image'] != '')
                {
                    $adata['image'] = url('/public/user_image').'/'.$u['image'];
                }else{
                    $adata['image'] = url('/public/user_image').'/default.png';
                }
                $adata['about'] = $u['about'];
                $adata['rank'] = $u['rank'];
                $data[] = $adata;
                
            }
            $data = array(
            'status'=>true,
            'achievers'=>$data
            );
        }
        else
        {
            $data = array(
            'status'=>false,
            'achievers'=>array()
            );
        }
        return $data;
    }
    public function achieverDetail(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'achiever_id' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $achiever_id = $data['achiever_id'];
        $userlist = User::select('users.id','users.fname','users.lname','tbl_achivers.rank','tbl_achivers.about','users.image')
        ->join('tbl_achivers', 'tbl_achivers.user_id', '=', 'users.id')
        ->where('tbl_achivers.status', 1)
        ->where('tbl_achivers.id', $achiever_id)
        ->get();
        if(count($userlist) > 0)
        {
            foreach($userlist as $u)
            {
                $adata['id'] = $u['id'];
                $adata['fname'] = $u['fname'];
                $adata['lname'] = $u['lname'];
                if($u['image'] != '')
                {
                    $adata['image'] = url('/public/user_image').'/'.$u['image'];
                }else{
                    $adata['image'] = url('/public/user_image').'/default.png';
                }
                $adata['about'] = $u['about'];
                $adata['rank'] = $u['rank'];

            }
            $data = array(
            'status'=>true,
            'achievers'=>$adata
            );
        }
        else
        {
            $data = array(
            'status'=>false,
            'achievers'=>(object)[]
            );
        }
        return $data;
    }
    public function driverOrderList(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'driver_id' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $driver_id = $data['driver_id'];
        $orderlist = OrderProduct::select('order_product.id','order_product.order_id','order_product.status','order_product.product_price','order_product.assigned_driver',
        'order_product.offer_price','order_product.qty','order_product.variant_value1','order_product.variant_value2','product.product_name','order_product.product_id')
        ->join('product', 'product.id', '=', 'order_product.product_id')
        ->where('assigned_driver', $driver_id)
        ->get();
        if(count($orderlist) > 0)
        {
            $assigned_order = array();
            $shipped_order = array();
            $delivered_order = array();
            $cancel_order = array();
            $returned_order = array();
            foreach($orderlist as $ol)
            {
                if($ol['status'] == 2 && $ol['assigned_driver'] != 0)
                {
                    $aorder['id'] = $ol['id'];
                    $aorder['product_id'] = $ol['product_id'];
                    $aorder['order_id'] = $ol['order_id'];
                    $aorder['offer_price'] = $ol['offer_price'];
                    $aorder['product_price'] = $ol['product_price'];
                    $aorder['qty'] = $ol['qty'];
                    $aorder['product_name'] = $ol['product_name'];
                    if($ol['variant_value1'] != '')
                    {
                        $aorder['variant_value1'] = $ol['variant_value1'];
                    }
                    else
                    {
                        $aorder['variant_value1'] = '';
                    }
                    if($ol['variant_value2'] == '')
                    {
                        $aorder['variant_value2'] = '';
                    }
                    else
                    {
                        $aorder['variant_value2'] = $ol['variant_value2'];
                    }
                    $assigned_order[] = $aorder;
                }
                elseif($ol['status'] == 3)
                {
                    $sorder['id'] = $ol['id'];
                    $sorder['product_id'] = $ol['product_id'];
                    $sorder['order_id'] = $ol['order_id'];
                    $sorder['offer_price'] = $ol['offer_price'];
                    $sorder['product_price'] = $ol['product_price'];
                    $sorder['qty'] = $ol['qty'];
                    $sorder['product_name'] = $ol['product_name'];
                    if($ol['variant_value1'] != '')
                    {
                        $sorder['variant_value1'] = $ol['variant_value1'];
                    }
                    else
                    {
                        $sorder['variant_value1'] = '';
                    }
                    if($ol['variant_value2'] == '')
                    {
                        $sorder['variant_value2'] = '';
                    }
                    else
                    {
                        $sorder['variant_value2'] = $ol['variant_value2'];
                    }
                    $shipped_order[] = $sorder;
                }
                elseif($ol['status'] == 4)
                {
                    $dorder['id'] = $ol['id'];
                    $dorder['product_id'] = $ol['product_id'];
                    $dorder['order_id'] = $ol['order_id'];
                    $dorder['offer_price'] = $ol['offer_price'];
                    $dorder['product_price'] = $ol['product_price'];
                    $dorder['qty'] = $ol['qty'];
                    $dorder['product_name'] = $ol['product_name'];
                    if($ol['variant_value1'] != '')
                    {
                        $dorder['variant_value1'] = $ol['variant_value1'];
                    }
                    else
                    {
                        $dorder['variant_value1'] = '';
                    }
                    if($ol['variant_value2'] == '')
                    {
                        $dorder['variant_value2'] = '';
                    }
                    else
                    {
                        $dorder['variant_value2'] = $ol['variant_value2'];
                    }
                    $delivered_order[] = $dorder;
                }
                elseif($ol['status'] == 5)
                {
                    $corder['id'] = $ol['id'];
                    $corder['product_id'] = $ol['product_id'];
                    $corder['order_id'] = $ol['order_id'];
                    $corder['offer_price'] = $ol['offer_price'];
                    $corder['product_price'] = $ol['product_price'];
                    $corder['qty'] = $ol['qty'];
                    $corder['product_name'] = $ol['product_name'];
                    if($ol['variant_value1'] != '')
                    {
                        $corder['variant_value1'] = $ol['variant_value1'];
                    }
                    else
                    {
                        $corder['variant_value1'] = '';
                    }
                    if($ol['variant_value2'] == '')
                    {
                        $corder['variant_value2'] = '';
                    }
                    else
                    {
                        $corder['variant_value2'] = $ol['variant_value2'];
                    }
                    $cancel_order[] = $corder;
                }
                elseif($ol['status'] == 6)
                {
                    $rorder['id'] = $ol['id'];
                    $rorder['product_id'] = $ol['product_id'];
                    $rorder['order_id'] = $ol['order_id'];
                    $rorder['offer_price'] = $ol['offer_price'];
                    $rorder['product_price'] = $ol['product_price'];
                    $rorder['qty'] = $ol['qty'];
                    $rorder['product_name'] = $ol['product_name'];
                    if($ol['variant_value1'] != '')
                    {
                        $rorder['variant_value1'] = $ol['variant_value1'];
                    }
                    else
                    {
                        $rorder['variant_value1'] = '';
                    }
                    if($ol['variant_value2'] == '')
                    {
                        $rorder['variant_value2'] = '';
                    }
                    else
                    {
                        $rorder['variant_value2'] = $ol['variant_value2'];
                    }
                    $returned_order[] = $rorder;
                }
            }
            $data = array(
            'status'=>true,
            'assigned_order'=>$assigned_order,
            'shipped_order'=>$shipped_order,
            'delivered_order'=>$delivered_order,
            'cancel_order'=>$cancel_order,
            'returned_order'=>$returned_order
            );
        }
        else
        {
            $data = array(
            'status'=>false,
            'assigned_order'=>array(),
            'shipped_order'=>array(),
            'delivered_order'=>array(),
            'cancel_order'=>array(),
            'returned_order'=>array()
            );
        }
            return $data;
    }
    public function driver_login(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'mobile' => 'required|max:10',
        'password' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $mobile = $data['mobile'];
        $password = md5($data['password']);
        $users = Driver::select()
          ->where('mobile', $mobile)
          ->where('password', $password)
          ->get();
        if(count($users) == 1)
        {
            foreach($users as $u)
            {
                 $dbpassword = $u['password'];
                if($dbpassword == $password)
                {
                        $udata['driver_id'] = $u['id'];
                        $udata['driver_name'] = $u['driver_name'];
                        $udata['email'] = $u['email'];
                        $udata['mobile'] = $u['mobile'];
                        $udata['driving_licence_front'] = url('/public/driver_licence').'/'.$u['driving_licence_front'];
                        $udata['aadhar_front'] = url('/public/driver_aadhar_image').'/'.$u['aadhar_front'];
                        $udata['driving_licence_back'] = url('/public/driver_licence').'/'.$u['driving_licence_back'];
                        $udata['rc_image'] = url('/public/driver_rc_image').'/'.$u['rc_image'];
                        $udata['aadhar_back'] = url('/public/driver_aadhar_image').'/'.$u['aadhar_back'];
                        $udata['address'] = $u['address'];
                        $udata['driver_image'] = url('/public/driver_image').'/'.$u['driver_image'];
                        $udata['city'] = $u['city'];
                        $udata['state'] = $u['state'];
                        $udata['zip'] = $u['zip'];
                        $udata['alternate_mobile'] = $u['alternate_mobile'];
                        $udata['bank_name'] = $u['bank_name'];
                        $udata['account_no'] = $u['account_no'];
                        $udata['branch_name'] = $u['branch_name'];
                        $udata['ifsc_code'] = $u['ifsc_code'];
                        $udata['account_holder'] = $u['account_holder'];
                        $udata['account_type'] = $u['account_type'];

                    $data = array(
                        'status'=>true,
                        'data'=>$udata,
                        'msg'=>'Login success'
                    );
                    return $data;
                }
                else
                {
                    $data = array(
                        'status'=>false,
                        'data'=>(object)[],
                        'msg'=>'Invalid user',
                    );
                    return $data;
                }
            }
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'Invalid user',
                'data'=>(object)[]
            );
            return $data;
        }
    }
    public function driver_profile(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'driver_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $driver_id = $data['driver_id'];
        $users = Driver::select()
          ->where('id', $driver_id)
          ->get();
        if(count($users) == 1)
        {
            foreach($users as $u)
            {
                $udata['driver_id'] = $u['id'];
                $udata['driver_name'] = $u['driver_name'];
                $udata['email'] = $u['email'];
                $udata['mobile'] = $u['mobile'];
                $udata['driving_licence_front'] = url('/public/driver_licence').'/'.$u['driving_licence_front'];
                $udata['aadhar_front'] = url('/public/driver_aadhar_image').'/'.$u['aadhar_front'];
                $udata['driving_licence_back'] = url('/public/driver_licence').'/'.$u['driving_licence_back'];
                $udata['rc_image'] = url('/public/driver_rc_image').'/'.$u['rc_image'];
                $udata['aadhar_back'] = url('/public/driver_aadhar_image').'/'.$u['aadhar_back'];
                $udata['address'] = $u['address'];
                $udata['driver_image'] = url('/public/driver_image').'/'.$u['driver_image'];
                $udata['city'] = $u['city'];
                $udata['state'] = $u['state'];
                $udata['zip'] = $u['zip'];
                $udata['alternate_mobile'] = $u['alternate_mobile'];
                $udata['bank_name'] = $u['bank_name'];
                $udata['account_no'] = $u['account_no'];
                $udata['branch_name'] = $u['branch_name'];
                $udata['ifsc_code'] = $u['ifsc_code'];
                $udata['account_holder'] = $u['account_holder'];
                $udata['account_type'] = $u['account_type'];
                

                $data = array(
                    'status'=>true,
                    'data'=>$udata,
                    'msg'=>'Login success'
                );
                return $data;
                
            }
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'Invalid user',
                'data'=>(object)[]
            );
            return $data;
        }
    }
    public function change_order_status(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'order_id' => 'required',
            'product_id' => 'required',
            'order_status' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $order_id = $data['order_id'];
        $product_id = $data['product_id'];
        $order_status = $data['order_status'];
        if($order_status <= 2)
        {
            $data = array(
                    'status'=>false,
                    'msg'=>'Order status not allowed'
                );
        }
        else
        {
            $update = DB::table('order_product')
            ->where('order_id', $order_id)
            ->where('product_id', $product_id)
            ->update(
                [
                    'status' => $order_status,
                    'updated_at'=>date('y-m-d H:i:s')
                ]
            );
            if($update)
            {
                if($order_status == 3)
                {
                    $message = 'Order shipped';
                }
                elseif($order_status == 4)
                {
                    $message = 'Order delivered';
                }
                elseif($order_status == 5)
                {
                    $message = 'Order delivered';
                }
                elseif($order_status == 6)
                {
                    $message = 'Order cancled';
                }
                elseif($order_status == 4)
                {
                    $message = 'Order mark as returned';
                }
                $data = array(
                    'status'=>true,
                    'msg'=>$message
                );
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Unable to change order status'
                );
            }
        }
        return $data;

        
    }
    public function userWallet(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'user_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $userdata = DB::table('user_wallet')->select('*')->skip(0)->take(1)
        ->where('user_id',$user_id)
        ->get();
        if(count($userdata) > 0)
        {
            $wallet_amount = $userdata[0]->wallet_amount;
            $yes_amount = $userdata[0]->yes_amount;
            $tds_amount = $this->getPercentOfNumber($wallet_amount, 10);
            $process_fee = $this->getPercentOfNumber($wallet_amount, 5);
                $data = array(
                    'status'=>true,
                    'wallet_amount'=>number_format($wallet_amount,2),
                    'yes_amount'=>$yes_amount,
                    'tds_amount'=>number_format($tds_amount,2),
                    'process_fee'=>number_format($process_fee,2),
                    'transferable_money'=>number_format($wallet_amount-$tds_amount-$process_fee,2)
                );
        }
        else
        {
            $wallet_amount = 0;
            $yes_amount = 0;
            $data = array(
                    'status'=>true,
                    'wallet_amount'=>0,
                    'yes_amount'=>0,
                    'tds_amount'=>0,
                    'process_fee'=>0,
                    'transferable_money'=>0
            );
        }
        return $data;
    }
    function getPercentOfNumber($number, $percent)
    {
        return ($percent / 100) * $number;
    }
    public function recently_viewed_product(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $user_id = $data['user_id'];
        $productlist = Products::select('product.id','product.product_name','product.price','product.offer_price',
            'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
            ->join('recently_viewed_product', 'recently_viewed_product.product_id', '=', 'product.id')
            ->where('recently_viewed_product.user_id', $user_id)
            ->where('product.status', 1)
            ->get();
        if(count($productlist) > 0)
        {
            foreach($productlist as $u)
            {
                 $udata['id'] = $u['id'];
                 $udata['product_name'] = $u['product_name'];
                 $udata['price'] = $u['price'];
                 $udata['offer_price'] = $u['offer_price'];
                 $udata['discount'] = number_format((($u['price']-$u['offer_price'])*100)/$u['price'],2);
                 if($u['variant_name1'] == '')
                 {
                     $udata['variant_name1'] = '';
                 }
                 else
                 {
                     $udata['variant_name1'] = $u['variant_name1'];
                 }
                 if($u['variant_value1'] == '')
                 {
                     $udata['variant_value1'] = '';
                 }
                 else
                 {
                     $udata['variant_value1'] = $u['variant_value1'];
                 }
                 if($u['variant_name2'] == '')
                 {
                     $udata['variant_name2'] = '';
                 }
                 else
                 {
                     $udata['variant_name2'] = $u['variant_name2'];
                 }
                 if($u['variant_value2'] == '')
                 {
                     $udata['variant_value2'] = '';
                 }
                 else
                 {
                     $udata['variant_value2'] = $u['variant_value2'];
                 }
                 $udata['image'] = url('/public/product_image').'/'.$u['product_image'];
                 $fdata[]=$udata;
            }
            $data = array(
                'status'=>true,
                'data'=>$fdata
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>[]
            );
            return $data;
        }
    }

    // public function saveOrder(Request $request)
    // {
    //     $validator =  Validator::make($request->all(),[
    //         'address_id' => 'required',
    //         'payment_method' => 'required',
    //         'payment_id' => 'required',
    //         'user_id' => 'required',
    //         'coupon_code' => 'required',
    //         'coupon_discount' => 'required',
    //         'subtotal' => 'required',
    //         'grand_total' => 'required',
    //     ]);
    
    //     if($validator->fails()){
    //         return response()->json([
    //             "error" => 'validation_error',
    //             "message" => $validator->errors(),
    //         ], 200);
    //     }
    //     $data = $request->input();
    //     $address_id = $data['address_id'];
    //     $payment_method = $data['payment_method'];
    //     $payment_id = $data['payment_id'];
    //     $user_id = $data['user_id'];
    //     // $pdata = $data['product_data'];
    //     // $jsonpdata = json_decode($pdata);
    //     // $productdata = $jsonpdata->data;
    //     $orderid = "ORD".date("ymdhis")."".$user_id;
    //     $coupon_code = $data['coupon_code'];
    //     $coupon_discount = $data['coupon_discount'];
    //     $subtotal = $data['subtotal'];
    //     $grand_total = $data['grand_total'];

    //     $saveorder = DB::table('order')->insert(
    //         [
    //             'order_id'=>$orderid,
    //             'user_id'=>$user_id,
    //             'coupon_code'=>$coupon_code,
    //             'coupon_amount'=>$coupon_discount,
    //             'shipping_method'=>1,
    //             'shipping_charge'=>0.00,
    //             'payment_id'=>$payment_id,
    //             'payment_method'=>$payment_method,
    //             'sub_total'=>$subtotal,
    //             'grand_total'=>$grand_total,
    //             'shipping_address'=>$address_id,
    //             'order_status'=>1,
    //             'payment_status'=>1,
    //             'order_date'=>date('Y-m-d')
    //         ]
    //     );
    //     if($saveorder)
    //     {
         
    //          $getproduct = Cart::select()
    //             ->where('user_id', $user_id)
    //             ->get();
    //         $finalArray = array();
    //         foreach($getproduct as $p)
    //         {
                
    //             $arr = array(
    //                 'product_id'=>$p['product_id'],
    //                 'product_price'=>$p['price'],
    //                 'offer_price'=>$p['offer_price'],
    //                 'qty'=>$p['qty'],
    //                 'order_id'=>$orderid,
    //                 'user_id'=>$user_id
    //             );
                
    //             $finalArray[] = $arr;
    //         }

          
    //         $orderprodsave = OrderProduct::insert($finalArray);
    //         if($orderprodsave)
    //         {
    //             $removeaddress = DB::table('cart')->where('user_id', $user_id)->delete();
    //             $savenotification = DB::table('notification')->insert(
    //             [
    //                 'notification' => $orderid.' recieved at '.date('y-m-d'),
    //                 'slug' => 'order-list',
    //                 'status' => 0,
    //                 'url' => url('/view-order')."/".$orderid,
    //                 'vendor_url'=>url('/vendor-view-order')."/".$orderid,
    //                 'created_at'=>date('y-m-d'),
    //                 'updated_at'=>date('y-m-d')
    //             ]
    //             );
    //             $data = array(
    //                 'status'=>true,
    //                 'msg'=>'Order Placed',
    //                 'order_no'=>$orderid
    //             );
    //             return $data;
    //         }
    //         else
    //         {
    //             $data = array(
    //                 'status'=>false,
    //                 'msg'=>'Something went wrong'
    //             );
    //             return $data;
    //         }
    //     }
    //     else
    //     {
    //         $data = array(
    //             'status'=>false,
    //             'msg'=>'Something went wrong'
    //         );
    //         return $data;
    //     }
    // }
    public function updateShippingAddress(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'fname' => 'required',
        'phone' => 'required',
        'ship_id' => 'required',
        'user_id' => 'required',
        'flat' => 'required',
        'area' => 'required',
        'landmark' => 'required',
        'city' => 'required',
        'state' => 'required',
        'zip' => 'required',
        'is_default' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        
            $updateaddress = DB::table('user_address')
                            ->where('id',$data['ship_id'])
                            ->update(
                [
                 'user_id' => $data['user_id'],
                 'fname' => $data['fname'],
                 'phone' => $data['phone'],
                 'flat' => $data['flat'],
                 'address' => $data['flat'].",".$data['area'],
                 'area' => $data['area'],
                 'landmark' => $data['landmark'],
                 'city' => $data['city'],
                 'state' => $data['state'],
                 'zip' => $data['zip'],
                 'is_default' => $data['is_default'],
                 'created_at'=>date('Y-m-d H:i:s')
                ]
            );
    
            if($updateaddress)
            {
                if($data['is_default'] == 1)
                {
                    DB::table('user_address')
                    ->where('user_id', $data['user_id'])
                    ->where('id','!=',$data['ship_id'])
                    ->update(
                        [
                            'is_default' => 0,
                            'updated_at'=>date('y-m-d')
                        ]
                    );
                }
                $data = array(
                    'status'=>true,
                    'msg'=>'Address updated'
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
    }
    public function getOrderListForContactPage(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'status' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $status = $data['status'];
        
        if($status == 1)
        {
            $userorder = Order::select('order.*')
            ->where('order.user_id',$user_id)
            ->where('order.order_status','<',4)
            ->get();
        }
        elseif($status == 2)
        {
            $userorder = Order::select('order.*')
            ->where('order.user_id',$user_id)
            ->where('order.order_status','<',4)
            ->get();
        }
        elseif($status == 3)
        {
            $userorder = Order::select('order.*')
            ->where('order.user_id',$user_id)
            ->where('order.order_status',4)
            ->get();
        }
        if(count($userorder) > 0)
        {
            foreach($userorder as $u)
            {
                if($u['order_status'] == 1)
                {
                    $orderstatus = 'Confirmed';
                }
                elseif($u['order_status'] == 2)
                {
                    $orderstatus = 'Packed';

                }
                elseif($u['order_status'] == 3)
                {
                    $orderstatus = 'Shipped';
                    
                }
                elseif($u['order_status'] == 4)
                {
                    $orderstatus = 'Delivered';
                    
                }
                elseif($u['order_status'] == 5)
                {
                    $orderstatus = 'Canceled';
                    
                }
                elseif($u['order_status'] == 6)
                {
                    $orderstatus = 'Returned';
                    
                }
                $udata['id'] = $u['id'];
                $udata['order_id'] = $u['order_id'];
                $udata['payment_id'] = $u['payment_id'];
                $udata['payment_method'] = $u['payment_method'];
                $udata['sub_total'] = $u['sub_total'];
                $udata['grand_total'] = $u['grand_total'];
                $udata['order_status'] = $orderstatus;
                $udata['order_date'] = $u['order_date'];
                $fdata[]=$udata;
            }
            $data = array(
                'status'=>true,
                'data'=>$fdata
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
            return $data;
        }
    }
    
    // by now api
    public function byNowAddToCart(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'product_id' => 'required',
        'qty' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $user_id = $data['user_id'];
        $product_id = $data['product_id'];
        $qty = $data['qty'];
        $cart_variant1 = $data['cart_variant1'];
        $cart_variant2 = $data['cart_variant2'];


        $product_detail = Products::select('product.price','product.offer_price')
        ->where('product.id',$product_id)
        ->get();

        foreach($product_detail as $p)
        {
             $price = $p['price'];
             $offer_price = $p['offer_price'];
        }
        
        $cartcount1 = DB::table('cart')
        ->where('user_id', $user_id)
        ->where('product_id', $product_id)
        ->get();
        
        // by now add to cart

             DB::table('by_now_cart')->where('user_id', $user_id)->delete();
            
            $addtocart = DB::table('by_now_cart')->insert(
                [
                'qty' => $qty,
                'user_id' => $data['user_id'],
                'product_id' => $data['product_id'],
                'price' => $price,
                'variant_value1' => $cart_variant1,
                'variant_value2' => $cart_variant2,
                'offer_price' => $offer_price
                ]
            );
            if($addtocart)
            {
                // add to cart
                if(count($cartcount1) > 0)
                {
                    $updatecart1 = DB::table('cart')
                    ->where('user_id', $user_id)
                    ->where('product_id', $product_id)
                    ->update(
                        [
                            'qty' => $qty,
                            'price' => $price,
                            'offer_price' => $offer_price,
                            'variant_value1' => $cart_variant1,
                            'variant_value2' => $cart_variant2,
                            'updated_at'=>date("y-m-d H:i:s")
                        ]
                    );
                }
                else
                {
                    $addtocart1 = DB::table('cart')->insert(
                        [
                        'qty' => $qty,
                        'user_id' => $data['user_id'],
                        'product_id' => $data['product_id'],
                        'price' => $price,
                        'variant_value1' => $cart_variant1,
                        'variant_value2' => $cart_variant2,
                        'offer_price' => $offer_price
                        ]
                    );
                }
                // add to cart
                $deliver_charge = 0;
                $data = array(
                    'status'=>true,
                    'grand_total'=>$price*$qty+$deliver_charge,
                    'delivery_charge'=>$deliver_charge,
                    'msg'=>'Item added to cart',
                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
    }
    public function ByNowSaveOrder(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'address_id' => 'required',
            'billing_address' => 'required',
            'payment_method' => 'required',
            'user_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $address_id = $data['address_id'];
        $billing_address = $data['billing_address'];
        $payment_method = $data['payment_method'];
        $payment_id = $data['payment_id'];
        $user_id = $data['user_id'];
        $coupon_id = $data['coupon_id'];
        $wallet_amount = $data['wallet_amount'];
        $orderid = "ORD".date("ymdhis")."".$user_id;

            $getcoupon = Coupon::select()
            ->where('id', $coupon_id)
            ->get();
            if(count($getcoupon) > 0)
            {
                foreach($getcoupon as $gc)
                {
                    $coupon_id = $gc['id'];
                    $coupon_code = $gc['coupon_code'];
                    $coupon_type = $gc['coupon_type'];
                    $coupon_val = $gc['coupon_val'];
                }
            }
            else
            {
                $coupon_code = '';
            }

                $cartlist = DB::table('by_now_cart')->select('by_now_cart.qty','product.offer_price','product.price')
                ->join('product', 'product.id', '=', 'by_now_cart.Product_id')
                ->where('by_now_cart.user_id',$user_id)
                ->get();
        //         print_r($cartlist);
        // die();
                if(count($cartlist) > 0)
                {
                    $subtotal = 0;
                    $totalDiscount = 0;
                    $deleveryCharge = 0;
                    foreach($cartlist as $u)
                    {
                        $product_price = $u->qty*$u->offer_price;
                        $subtotal = $subtotal+$product_price;
                        $totalDiscount = $totalDiscount+($u->price-$u->offer_price);
                    }
        
                        $coupon_discount = 0;
                        if(count($getcoupon) > 0)
                        {
                            if($coupon_type == 'percent')
                            {
                                $coupon_discount = ($coupon_val/ 100) * $subtotal;
                            }
                            else
                            {
                                $coupon_discount = $coupon_val;
                            }
                        }
                        if($subtotal < 500)
                        {
                            $deleveryCharge = 100;
                        }
                        elseif($subtotal > 500 && $subtotal < 1000)
                        {
                            $deleveryCharge = 70;
                        }
                        elseif($subtotal > 1000 && $subtotal < 2000)
                        {
                            $deleveryCharge = 50;
                        }
                        else
                        {
                            $deleveryCharge = 0;
                        }
                        $grandtotalafterdiscount = ($subtotal+$deleveryCharge)-$coupon_discount;
                }
                else
                {
                    DB::rollback();
                     $data = array(
                    'status'=>false,
                    'msg'=>'No data in your cart'
                    );
                    return $data;
                }
        
        
        DB::beginTransaction();
            $saveorder = DB::table('order')->insert(
            [
                'order_id'=>$orderid,
                'user_id'=>$user_id,
                'coupon_code'=>$coupon_code,
                'billing_address'=>$billing_address,
                'coupon_amount'=>$coupon_discount,
                'shipping_method'=>1,
                'shipping_charge'=>$deleveryCharge,
                'payment_id'=>$payment_id,
                'payment_method'=>$payment_method,
                'sub_total'=>$subtotal,
                'grand_total'=>$grandtotalafterdiscount,
                'shipping_address'=>$address_id,
                'order_status'=>1,
                'payment_status'=>1,
                'order_date'=>date('Y-m-d')
            ]
        );
        if($saveorder)
        {
            $cartdata = DB::table('by_now_cart')->select('by_now_cart.id','by_now_cart.offer_price','by_now_cart.price','product.product_type','product.vendor_id',
            'product.product_name','product.product_image','by_now_cart.qty','by_now_cart.product_id','by_now_cart.variant_value1','by_now_cart.variant_value2')
            ->join('product','product.id', '=', 'by_now_cart.product_id')
            ->where('by_now_cart.user_id',$user_id)
            ->get();
        
            $addressdata = DB::table('user_address')->select('zip')
            ->where('id',$address_id)
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
                    'order_id'=>$orderid,
                    'user_id'=>$user_id,
                    'vendor_id'=>$vendor_id
                    );
                $productarr[] = $productdt;
            }
                $saveproduct = DB::table('order_product')->insert($productarr);
                if($saveproduct)
                {
                    DB::table('by_now_cart')->where('user_id', $user_id)->delete();
                    if($payment_method == 3)
                    {
                        DB::table('user_wallet')->where('user_id', $user_id)->decrement('wallet_amount', $wallet_amount);

                    }
                    DB::commit();
                    $data = array(
                    'status'=>true,
                    'msg'=>'Order placed'
                    );
                    return $data;
                }
                else
                {
                     DB::rollback();
                     $data = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                    );
                    return $data;
                }
        }
        else
        {
             DB::rollback();
             $data = array(
                    'status'=>false,
                    'msg'=>'Order failed'
                    );
                    return $data;
        }
    }
    public function updateAadharBack(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'user_id' => 'required',
            'aadhar_back' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8048',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        
        // upload aadhar back start
        if($_FILES['aadhar_back']['name'] != '')
        { 
            $rimage1 = $request->aadhar_back;
            $imageName1 = rand().'.'.Request()->aadhar_back->getClientOriginalExtension();
            $folder1 = 'customer_aadhar/';
            $aadhar_back = $this->uploadWithoutResizeDunamicFolderImage($rimage1,$imageName1,0,0,$folder1);
            
        }
        // upload aadhar back end
        
        $update = DB::table('users')
            ->where('id', $user_id)
            ->update(
                [
                    'aadhar_back'=>$aadhar_back
                ]
            );
        if($update)
        {
            $data = array(
            'status'=>true,
            'msg'=>'Aadhar Back updated'
            );
            return $data;
        }
        else
        {
            $data = array(
            'status'=>false,
            'msg'=>'Something went wrong'
            );
            return $data;
        }
    }
    public function updateAadharFront(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'user_id' => 'required',
            'aadhar_front' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8048',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        // upload aadhar front start
        if($_FILES['aadhar_front']['name'] != '')
        { 
            $rimage = $request->aadhar_front;
            $imageName = rand().'.'.Request()->aadhar_front->getClientOriginalExtension();
            $folder = 'customer_aadhar/';
            $aadhar_front = $this->uploadWithoutResizeDunamicFolderImage($rimage,$imageName,0,0,$folder);
            
        }
        // upload aadhar front end
        
        $update = DB::table('users')
            ->where('id', $user_id)
            ->update(
                [
                    'aadhar_front'=>$aadhar_front
                ]
            );
        if($update)
        {
            $data = array(
            'status'=>true,
            'msg'=>'Aadhar front updated'
            );
            return $data;
        }
        else
        {
            $data = array(
            'status'=>false,
            'msg'=>'Something went wrong'
            );
            return $data;
        }
    }
    public function update_mobile_no(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'mobile' => 'required',
            'user_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }

        $data = $request->input();
        $mobile = $data['mobile'];
        $user_id = $data['user_id'];
        
        $usercount = User::select()
            ->where('phone', $mobile)
            ->count();
        if($usercount == 1)
        {
            $data = array(
                'status'=>false,
                'msg'=>'Mobile already registered'
            );
            return $data;
        }
        else
        {
            $updatedata = array(
                        'phone' => $mobile
                    );
            $update = DB::table('users')->where('id', $user_id)->update($updatedata);
            if($update)
            {
                $resarray = array(
                'msg'=>'Mobile no changed',
                'status'=>true
                );
        }
            else
            {
                $resarray = array(
                'msg'=>'Internal server error',
                'status'=>false
                );
        }
        }
        return $resarray;
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
    public function cart_to_wishlist(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'product_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $product_id = $data['product_id'];
        $wishlistcount = DB::table('wishlist')
        ->where('user_id', $user_id)
        ->where('product_id', $product_id)
        ->get();
        if(count($wishlistcount) > 0)
        {
            $removewishlist = DB::table('cart')
            ->where('user_id', $user_id)
            ->where('product_id', $product_id)
            ->delete();
            
            $data = array(
                'status'=>true,
                'msg'=>'Item already exist in wishlist'
            );
            return $data;
        }
        else
        {
            $addtowishlist = DB::table('wishlist')->insert(
                [
                    'user_id' => $data['user_id'],
                    'product_id' => $data['product_id']
                ]
            );
            $wishcount = DB::table('wishlist')
            ->where('user_id', $user_id)
            ->get();
            $wcount = count($wishcount);

            if($addtowishlist)
            {
                $removewishlist = DB::table('cart')
                ->where('user_id', $user_id)
                ->where('product_id', $product_id)
                ->delete();
            
                $Wishlistcount = Wishlist::select('id')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
            
                if(count($Wishlistcount) == 1)
                {
                    $is_wishlist_added = 1;
                }
                else
                {
                    $is_wishlist_added = 0;
                }
                $data = array(
                    'status'=>true,
                    'wcount'=>$wcount,
                    'is_wishlist_added'=>$is_wishlist_added,
                    'msg'=>'Item added to wishlist'
                );
                return $data;
            }
            else
            {
                $Wishlistcount = Wishlist::select('id')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
            
                if(count($Wishlistcount) == 1)
                {
                    $is_wishlist_added = 1;
                }
                else
                {
                    $is_wishlist_added = 0;
                }
                $data = array(
                    'status'=>false,
                    'is_wishlist_added'=>$is_wishlist_added,
                    'wcount'=>$wcount,
                    'msg'=>'Something went wrong'
                );
                return $data;
            }
        }
    }
    public function getuserwalletamount(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $checkUser = DB::table('user_wallet')->select('id', 'wallet_amount')->where('user_id', $user_id)->first();
        if($checkUser)
        {
            $wallet_amount = $checkUser->wallet_amount;
        }
        else
        {
            $wallet_amount = 0;
        }
        $data = array(
            'status'=>true,
            'amount'=>$wallet_amount
        );
        return $data;
    }
    public function cancel_order(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'order_id' => 'required',
            'cancel_note' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $order_id = $data['order_id'];
        $cancel = DB::table('order')
        ->where('order_id', $order_id)
        ->update(
            [
                'order_status' => 5,
                'cancel_note' => $data['cancel_note'] ,
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
            $data = array(
                        'msg'=>'Success',
                        'status'=>true
                    );
            return $data;
        }
        else
        {
            $data = array(
                        'status'=>false,
                        'msg'=>'Someting went wrong'
                    );
            return $data;
        }
    }
    public function return_order(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'order_id' => 'required',
            'product_id' => 'required',
            'return_note' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
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
                    'return_note' => $data['return_note'],
                ]
            );
        if($productreturn)
        {
            $data = array(
                        'msg'=>'Success',
                        'status'=>true
                    );
            return $data;
        }
        else
        {
            $data = array(
                        'status'=>false,
                        'msg'=>'Someting went wrong'
                    );
            return $data;
        }
    }
    public function payment_transfer(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'user_id' => 'required',
            'ifsc_code' => 'required',
            'account_holder' => 'required',
            'account_no' => 'required',
            'amount' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $ifsc_code = $data['ifsc_code'];
        $account_holder = $data['account_holder'];
        $account_no = $data['account_no'];
        $user_id = $data['user_id'];
        $amount = filter_var($data['amount'],FILTER_SANITIZE_NUMBER_INT);
        
        $userdetail = DB::table('users')->select('email','phone')
        ->where('id',$user_id)
        ->first();
        
        if($userdetail)
        {
            $phone = $userdetail->phone;
            $email = $userdetail->email;
            $userincomedata = DB::table('user_wallet')->select('*')->skip(0)->take(1)
            ->where('user_id',$user_id)
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
                        'email'=>$email,
                        'contact'=>$phone,
                        'type'=>'employee',
                        'reference_id'=>'user_id'.$user_id,
                        'notes'=>array(
                            'notes_key_1'=>'Wallet amount transfer',
                            'notes_key_2'=>''
                            )
                        )
                    ),
                'queue_if_low_balance' => true,
                'reference_id'=>'user_id'.$user_id,
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
                        'user_id' => $user_id,
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
                            ->where('user_id', $user_id)
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
        else
        {
            $data = array(
                        'status'=>false,
                        'msg'=>'Invalid User'
                    );
            return $data;
        }
     
    }
    public function sendTransactionOtp(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'phone' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $mobile = $data['phone'];
        $usercount = User::select()
            ->where('phone', $mobile)
            ->count();
        if($usercount == 1)
        {
            $OTP = rand(10000,99999);
            Helper::send_transaction_otp($mobile,$OTP);
            $data = array(
                'status'=>true,
                'msg'=>'OTP send successfully',
                'OTP'=>$OTP,
                'phone'=>$mobile
            );
            return $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'Mobile not registered'
            );
            return $data;
        }
    }

}
