<?php

namespace App\Http\Controllers\vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Admin;
use App\Models\Products;
use App\Models\Category;
use App\Models\Product_Size_Unit;
use App\Models\Product_Type;
use App\Models\Brands;
use Illuminate\Support\Facades\DB;
use URL;
use Session;
use Image;


class Vendorcontroller extends Controller
{
    public function monthlySettlement(Request $req)
    {
        $vendor_id = $req->input('vendor_id');
        $lastMonthStartDate = date("Y-m-d", strtotime("first day of previous month"));
        $lastMonthEndDate = date("Y-m-d", strtotime("last day of previous month"));
        $todayDate = date('Y-m-d');
        
        $checkSettlement = db::table('wallet_transaction')
                ->where('create_date','>=', "$lastMonthEndDate")
                ->where('type', "1")
                ->where('vendor_id', $vendor_id)
                ->get();
        
        $totalSale = DB::table('order_product')->select("order_product.*")
                    ->whereRaw("((order_product.created_at = '$lastMonthStartDate' OR order_product.created_at > '$lastMonthStartDate') AND (order_product.created_at = '$lastMonthEndDate' OR order_product.created_at < '$lastMonthEndDate')) AND order_product.vendor_id='$vendor_id' AND order_product.status=4")
                    ->get();
        
        $totalAmount = 0;   
        foreach($totalSale as $ts)
        {
            $totalAmount = $totalAmount+($ts->qty * $ts->offer_price);
        }        
        $monthlyCommission = (0.2 * $totalAmount)/100;
        
        // print_r($monthlyCommission);
        // die();
        
        if(count($checkSettlement) == 0)
        {
            $getVendor = DB::table('vendor')->select('id', 'parent_referral_code')->where('id', $vendor_id)->get();
            foreach($getVendor as $gv)
            {
                $parent_referral_code = $gv->parent_referral_code;
            }
                
            for($i=1;$i<6;$i++)
            {
                $getUserId = DB::table('users')->select('id', 'parent_referral_code')->where('referral_code', '=', $parent_referral_code)->get();
                foreach($getUserId as $gui)
                {
                    $parent_referral_code = $gui->parent_referral_code;
                    $userId[] = $gui->id;
                }
            }
            
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
                    $walletTransaction = array(
                        'user_id' => $ui,
                        'vendor_id' => $vendor_id,
                        'transaction_amount' => $monthlyCommission,
                        'message' => "Monthly Commission From Vendor Successfully Added In Your Wallet",
                        'type' => 1,
                        'status' => 1
                    );
                    DB::table('user_wallet')->where('user_id', $ui)->update($transactionData);
                    DB::table('wallet_transaction')->insert($walletTransaction);
                }
                else
                {
                    $transactionData = array(
                        'user_id' => $ui,
                        'wallet_amount' => $monthlyCommission,
                        'yes_amount' => (10*$monthlyCommission)/100,
                    );
                    $walletTransaction = array(
                        'user_id' => $ui,
                        'vendor_id' => $vendor_id,
                        'transaction_amount' => $monthlyCommission,
                        'message' => "Monthly Commission From Vendor Successfully Added In Your Wallet",
                        'type' => 1,
                        'status' => 1
                    );
                    DB::table('user_wallet')->insert($transactionData);
                    DB::table('wallet_transaction')->insert($walletTransaction);
                }
            }
        }
        
        echo json_encode(true);
    }
    
    public function dashboard(Request $req)
    {
        $id = Session::get('vendor_id');
        $productcount = Products::where('vendor_id',$id)->count();
        $ordercount = OrderProduct::select('order_id')
        ->where('vendor_id',$id)
        ->groupBy('order_id')
        ->get();
        return view('vendor/dashboard')->with('order', count($ordercount))->with('product', $productcount);
    }
    public function login(Request $req)
    {
        $email = $req->input('email');
        $password = $req->input('password');
        $users = Vendor::select()
                     ->where('email', $email)
                     ->where('password', md5($password))
                     ->get();
         
        if(count($users) == 1)
        {
            foreach($users as $usr)
            {
                $id = $usr['id'];
                Session::put('vendor_id', $id);
            }
            return redirect('vendor/dashboard');
        }
        else
        {
            return redirect('/vendor');
        }
    }
    public function productList()
    {
        $vendor = Session::get('vendor_id');
        $product = Products::select()
        ->where('vendor_id',$vendor)
        ->get();
        return view('vendor/product_list')->with('product', $product);
    }
    public function addNewProduct(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $product = Products::select()
                ->where('id', $id)
                ->get();
            
                $category = Category::select()
                ->where('status',1)
                ->get();

                $brandlist = Brands::select()
                ->where('status',1)
                ->get();
    
                $product_variant = db::table('tblproductvariant')
                ->where('product_id', $id)
                ->get();
                
                foreach($product as $p)
                {
                    $subcategory_id = $p['subcategory_id'];
                }
                $variant_label = db::table('tblsubcategory_variant')
                ->where('subcategory_id', $subcategory_id)
                ->get();
                

            return view('vendor/new_product')->with('product_variant_label',$variant_label)->with('product', $product)->with('id',$id)->with('category',$category)->with('brandlist',$brandlist)->with('product_variant',$product_variant);
        }
        else
        {
            $category = Category::select()
            ->where('status',1)
            ->get();

            $brandlist = Brands::select()
                ->where('status',1)
                ->get();
            $product_variant=array();
            $variant_label = array();
            return view('vendor/new_product')->with('id','')->with('product_variant_label',$variant_label)->with('category',$category)->with('brandlist',$brandlist)->with('product_variant',$product_variant);
        }
    }
    public function saveProduct(Request $request)
    {
        $data = $request->input();
        $arraydata = array();
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8048',
            'category_id'=>'required',
            'status'=>'required'
        ]);
    
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
        
        $rimage = $request->file;

        $image = $this->resizeImage($rimage,$imageName,650,650);

        
	    
        $insertId = DB::table('product')->insertGetId(
            [
             'vendor_id' => Session::get('vendor_id'),
             'product_name' => $data['product_name'],
             'slug' => $data['slug'],
             'category_id' => $data['category_id'],
             'subcategory_id' => $data['sub_category_id'],
             'brand_id' => $data['brand'],
             'product_image' => $image,
             'product_description' => $data['product_description'],
             'return_exchange_policy_type' => $data['return_exchange_policy_type'],
             'return_exchange_days' => $data['return_exchange_days'],
             'status' => $data['status']
             ]
        );
        if($insertId)
        {

    	    if(array_key_exists("variant_name",$data))
            {
                    $variant_name = $data['variant_name'];
                    
                    if(count($variant_name) == 1)
                    {
                        for($i=0; $i<count($data[$data['variant_name'][0]]); $i++)
                        {
                            if($i==0)
                            {
                                $updateproduct = DB::table('product')
                                    ->where('id', $insertId)
                                    ->update(
                                        [
                                            'variant_name1'=>$data['variant_name'][0],
                                            'variant_value1'=>$data[$data['variant_name'][0]][$i],
                                            'price'=>$data['main_price'][$i],
                                            'offer_price'=>$data['main_offer_price'][$i],
                                            'stock'=>$data['main_stock'][$i],
                                            'updated_at'=>date('y-m-d H:i:s')
                                        ]
                                    );
                            }
                            else
                            {
                            $vdata1 = array(
                                'variant_name1'=>$data['variant_name'][0],
                                'variant_value1'=>$data[$data['variant_name'][0]][$i],
                                'price'=>$data['main_price'][$i],
                                'offer_price'=>$data['main_offer_price'][$i],
                                'stock'=>$data['main_stock'][$i],
                                'product_id'=>$insertId
                                );
                                array_push($arraydata,$vdata1);
                            }
                        }
                    }
                    elseif(count($variant_name) == 2)
                    {
                        for($i=0; $i<count($data[$data['variant_name'][0]]); $i++)
                        {
                            if($i==0)
                            {
                                $updateproduct = DB::table('product')
                                    ->where('id', $insertId)
                                    ->update(
                                        [
                                            'variant_name1'=>$data['variant_name'][0],
                                            'variant_value1'=>$data[$data['variant_name'][0]][$i],
                                            'variant_name2'=>$data['variant_name'][1],
                                            'variant_value2'=>$data[$data['variant_name'][1]][$i],
                                            'price'=>$data['main_price'][$i],
                                            'offer_price'=>$data['main_offer_price'][$i],
                                            'stock'=>$data['main_stock'][$i],
                                            'updated_at'=>date('y-m-d H:i:s')
                                        ]
                                    );
                            }
                            else
                            {
                                $vdata1 = array(
                                'variant_name1'=>$data['variant_name'][0],
                                'variant_value1'=>$data[$data['variant_name'][0]][$i],
                                'variant_name2'=>$data['variant_name'][1],
                                'variant_value2'=>$data[$data['variant_name'][1]][$i],
                                'price'=>$data['main_price'][$i],
                                'offer_price'=>$data['main_offer_price'][$i],
                                'stock'=>$data['main_stock'][$i],
                                'product_id'=>$insertId
                                );
                                array_push($arraydata,$vdata1);
                            }
                        }
                        
                    }
                DB::table('tblproductvariant')->insert($arraydata);
            }
            else
            {

                $updateproduct = DB::table('product')
                    ->where('id', $insertId)
                    ->update(
                        [
                            'price'=>$data['main_price'][0],
                            'offer_price'=>$data['main_offer_price'][0],
                            'stock'=>$data['main_stock'][0],
                            'updated_at'=>date('y-m-d H:i:s')
                        ]
                    );
            }
            if(array_key_exists("specification",$data))
	        {
	            $specification = $data['specification'];
	            $speci_value = $data['speci_value'];
           		$count = count($specification);
        		$finalarr = array();
        		for($i=1;$i<=$count;$i++)
        		{
        			$arra = array(
        				'specification'=>$specification[$i-1],
        				'speci_value'=>$speci_value[$i-1],
        				'product_id'=>$insertId
        			);
        			$finalarr[] = $arra;
        		}
        		DB::table('tblproductspecification')->insert($finalarr);
        		
	        }
            return  redirect('vendor/add-new-product')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('vendor/add-new-product')->with('error', 'Data not saved !');
        }
    }
    public function updateProduct(Request $request)
    {
        $request->validate([
            'category_id'=>'required',
            'brand'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();
        $arraydata = array();
        if($request->hasFile('file'))
        { 
            $rimage = $request->file;
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();

            $image = $this->resizeImage($rimage,$imageName,650,650);

            $fFilePath = 'public/product_image/'.$data['hidden_image'];
            if(file_exists( public_path().'/product_image/'.$data['hidden_image'])){
                unlink($fFilePath);
            }
        }
        else
        {
                $image = $data['hidden_image'];
        }
        $updateproduct = DB::table('product')
        ->where('id', $data['hidden_id'])
        ->update(
            [
                'vendor_id' => Session::get('vendor_id'),
                'product_name' => $data['product_name'],
                'category_id' => $data['category_id'],
                'slug' => $data['slug'],
                'subcategory_id' => $data['sub_category_id'],
                'brand_id' => $data['brand'],
                'product_image' => $image,
                'product_description' => $data['product_description'],
                'status' => $data['status'],
                'updated_at'=>date('y-m-d H:i:s')
            ]
        );
        if($updateproduct)
        {
            if(array_key_exists("variant_name",$data))
            {
                $removevar = DB::table('tblproductvariant')
                ->where('product_id', $data['hidden_id'])
                ->delete();
                if($removevar)
                {
                    $variant_name = $data['variant_name'];
    
                    if(count($variant_name) == 1)
                    {
                        for($i=0; $i<count($data[$data['variant_name'][0]]); $i++)
                        {
                            if($i==0)
                            {
                                $updateproduct = DB::table('product')
                                    ->where('id', $data['hidden_id'])
                                    ->update(
                                        [
                                            'variant_name1'=>$data['variant_name'][0],
                                            'variant_value1'=>$data[$data['variant_name'][0]][$i],
                                            'price'=>$data['main_price'][$i],
                                            'offer_price'=>$data['main_offer_price'][$i],
                                            'stock'=>$data['main_stock'][$i],
                                            'updated_at'=>date('y-m-d H:i:s')
                                        ]
                                    );
                            }
                            else
                            {
                            $vdata1 = array(
                                'variant_name1'=>$data['variant_name'][0],
                                'variant_value1'=>$data[$data['variant_name'][0]][$i],
                                'price'=>$data['main_price'][$i],
                                'offer_price'=>$data['main_offer_price'][$i],
                                'stock'=>$data['main_stock'][$i],
                                'product_id'=>$data['hidden_id']
                                );
                                array_push($arraydata,$vdata1);
                            }
                        }
                    }
                    elseif(count($variant_name) == 2)
                    {
                        for($i=0; $i<count($data[$data['variant_name'][0]]); $i++)
                        {
                            if($i == 0)
                            {
                                $updateproduct = DB::table('product')
                                    ->where('id', $data['hidden_id'])
                                    ->update(
                                        [
                                            'variant_name1'=>$data['variant_name'][0],
                                            'variant_value1'=>$data[$data['variant_name'][0]][$i],
                                            'variant_name2'=>$data['variant_name'][1],
                                            'variant_value2'=>$data[$data['variant_name'][1]][$i],
                                            'price'=>$data['main_price'][$i],
                                            'offer_price'=>$data['main_offer_price'][$i],
                                            'stock'=>$data['main_stock'][$i],
                                            'updated_at'=>date('y-m-d H:i:s')
                                        ]
                                    );
                            }
                            else
                            {
                                $vdata1 = array(
                                    'variant_name1'=>$data['variant_name'][0],
                                    'variant_value1'=>$data[$data['variant_name'][0]][$i],
                                    'variant_name2'=>$data['variant_name'][1],
                                    'variant_value2'=>$data[$data['variant_name'][1]][$i],
                                    'price'=>$data['main_price'][$i],
                                    'offer_price'=>$data['main_offer_price'][$i],
                                    'stock'=>$data['main_stock'][$i],
                                    'product_id'=>$data['hidden_id']
                                    );
                                array_push($arraydata,$vdata1);
                            }
                        }
                        
                    }
                    DB::table('tblproductvariant')->insert($arraydata);
                }
            }
            else
            {
                $updateproduct = DB::table('product')
                    ->where('id', $data['hidden_id'])
                    ->update(
                        [
                            'price'=>$data['main_price'][0],
                            'offer_price'=>$data['main_offer_price'][0],
                            'stock'=>$data['main_stock'][0],
                            'updated_at'=>date('y-m-d H:i:s')
                        ]
                    );
            }
            if(array_key_exists("specification",$data))
	        {
	            $specification = $data['specification'];
	            $speci_value = $data['speci_value'];
                $removespec = DB::table('tblproductspecification')
                ->where('product_id', $data['hidden_id'])
                ->delete();

           		$count = count($specification);
        		$finalarr = array();
        		for($i=1;$i<=$count;$i++)
        		{
        			$arra = array(
        				'specification'=>$specification[$i-1],
        				'speci_value'=>$speci_value[$i-1],
        				'product_id'=>$data['hidden_id']
        			);
        			$finalarr[] = $arra;
        		}
        		DB::table('tblproductspecification')->insert($finalarr);
        		
	        }
            return  redirect('vendor/edit-product/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
            return redirect('vendor/edit-product/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function changeProductStatus(Request $request)
    {
        $data = $request->input();
        $productid = $data['productid'];
        $status = $data['status'];
        if($status == 1)
        {
            $column = 'best_deal';
        }
        elseif($status == 2)
        {
            $column = 'hot_deal';

        }
        elseif($status == 3)
        {
            $column = 'is_best_seller';
            
        }
        elseif($status == 4)
        {
            $column = 'is_todays_deal';
            
        }
        $updatestatus = DB::table('product')
                ->where('id', $productid)
                ->update(
                    [
                        $column => 1
                    ]
                );
        if($updatestatus)
        {
            return "Changed";
        }
        else
        {
             return "Failed";
        }
    }
    public function removeProductStatus(Request $request)
    {
        $data = $request->input();
        $productid = $data['productid'];
        $status = $data['status'];
        if($status == 1)
        {
            $column = 'best_deal';
        }
        elseif($status == 2)
        {
            $column = 'hot_deal';

        }
        elseif($status == 3)
        {
            $column = 'is_best_seller';
            
        }
        elseif($status == 4)
        {
            $column = 'is_todays_deal';
            
        }
        $updatestatus = DB::table('product')
                ->where('id', $productid)
                ->update(
                    [
                        $column => 0
                    ]
                );
        if($updatestatus)
        {
            return "Changed";
        }
        else
        {
             return "Failed";
        }
    }
    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect('/vendor');
    }
    public function forgetPassword()
    {
        return view('vendor/forget_password');
    }
    public function sendResetCode(Request $request)
    {
        $data = $request->input();
        $mailto = $data['email'];
        $from_name = "YES";
        $from_mail = "sachin.maydmedia@gmail.com";
        $subject = "Vendor Forget Password.";
        $base_64 = base64_encode($mailto);
        $url = URL::to('vendor/change-password?token='.$base_64);
        $message = "<a href='".$url."'>Click here to reset your password</a>";
        $saveToken = DB::table('vendor')
        ->where('email', $mailto)
        ->update(
            [
                'token' => $base_64,
                'updated_at'=>date('y-m-d H:i:s')
            ]
        );
        if($saveToken)
        {
            $sendm = $this->sendMail($mailto, $from_mail, $from_name, $subject, $message);
            if($sendm == 'true')
            {
                return  redirect('/vendor/forget-password')->with('success', 'Link sent to your mail Please check mail ..');
            }
            else
            {
                return  redirect('/vendor/forget-password')->with('error', 'Something went wrong!');
            }
        }
        else
        {
            return  redirect('/vendor/forget-password')->with('error', 'Something went wrong22 !');
        }
        
    }
    public function viewResetForm()
    {
        $encpEmail = $_GET['token'];
        $plainEmail = base64_decode($encpEmail);
        $admintok = Vendor::select()
        ->where('token',$encpEmail)
        ->get();
        if(count($admintok) > 0)
        {
            return view('vendor/reset_password')->with('email',$plainEmail);
        }
        else
        {
            return  redirect('/vendor')->with('error', 'Token Expired !');
        }
    }
    public function resetPassword(Request $request)
    {
        $data = $request->input();
        if($data['password'] == $data['cpassword'])
            {
                $updateadminpassword = DB::table('vendor')
                ->where('email', $data['email'])
                ->update(
                    [
                        'password' => md5($data['password']),
                        'token' => '',
                        'updated_at'=>date('y-m-d')
                    ]
                );
                if($updateadminpassword)
                {
                    return redirect('/vendor')->with('success', 'Password reset !');
                }
                else
                {
                    return redirect('/vendor/reset-password')->with('error', 'Something went wrong !');
                }
            }
            else
            {
                return redirect('/vendor/reset-password')->with('error', 'Password not match !');
            }

    }
    public function sendMail($mailto, $from_mail, $from_name, $subject, $message)
    {  

        $header = "From: ".$from_name." <".$from_mail.">\r\n";
        $header .= "MIME-Version: 1.0\n";
        $header .= "This is a multi-part message in MIME format.\r\n";
        $header .= $message."\r\n\r\n";
        $send = mail($mailto, $subject, $from_name, $header);
        print_r(error_get_last());
        die();
        if ($send) 
        {
            return "true";
        }
        else 
        {
            return "false";
        }
    }
    public function vendorOrderList()
    {
        $vendor = Session::get('vendor_id');
        $order = OrderProduct::select('order.id','order_product.order_id','users.fname','users.lname','order.order_date','order.sub_total','order.grand_total','order.order_status')
        ->join('order', 'order.order_id', '=', 'order_product.order_id')
        ->join('users', 'users.id', '=', 'order.user_id')
        ->where('order_product.vendor_id',$vendor)
        ->groupBy('order.order_id')
        ->get();
        return view('vendor/order_list')->with('order', $order);
    }
    public function vendorOrderDetail(Request $request)
    {
        $id = $request->id;
        $vendor = Session::get('vendor_id');
        $order = Order::select('order.id','order.order_id','order.coupon_code','order.coupon_amount','order.shipping_charge','order.payment_id','order.payment_method',
        'order.sub_total','order.grand_total','order.order_status','order.payment_status','order.order_date',
        'users.fname','users.lname',
        'user_address.fname as sfname','user_address.lname as slname','user_address.email','user_address.phone',
        'user_address.address','user_address.city','user_address.state','user_address.address','user_address.zip',
        'address_type.address_type as addr_type')
        ->join('users', 'users.id', '=', 'order.user_id')
        ->join('user_address', 'user_address.id', '=', 'order.shipping_address')
        ->join('address_type', 'address_type.id', '=', 'user_address.address_type')
        ->where('order.order_id',$id)
        ->get();
        
        $product = OrderProduct::select('order_product.*','product.product_name','product.product_image')
        ->join('product', 'product.id', '=', 'order_product.product_id')
        ->where('order_product.order_id',$id)
        ->where('order_product.vendor_id',$vendor)
        ->get();
        return view('vendor/order_detail')->with('order', $order)->with('product',$product);
    }
    public function vendorProfile()
    {
        $id = Session::get('vendor_id');
        $profile = Vendor::select()
        ->where('id',$id)
        ->get();
        $pincode = DB::table('vendor_pincode')
                    ->where('vendor_id', $id)
                    ->get();
        return view('vendor/vendor_profile')->with('vendor_profile', $profile)->with('pincode',$pincode);
    }
    public function updateVendorProfile(Request $request)
    {
        $data = $request->input();
        if($data['password'] != '' && $data['cpassword'] != '')
        {
            if($data['password'] == $data['cpassword'])
            {
                $updateadminprofile = DB::table('vendor')
                ->where('id', $data['hidden_id'])
                ->update(
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
                        'bank_name' => $data['bank_name'],
                        'account_no' => $data['account_no'],
                        'branch_name' => $data['branch_name'],
                        'ifsc_code' => $data['ifsc_code'],
                        'account_holder' => $data['account_holder'],
                        'account_type' => $data['account_type'],
                        'otp' => '',
                        'service_description' => $data['service_description'],
                        'password' => md5($data['password']),
                        'updated_at'=>date('y-m-d H:i:s')
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
            $updateadminprofile = DB::table('vendor')
            ->where('id', $data['hidden_id'])
            ->update(
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
                    'bank_name' => $data['bank_name'],
                    'account_no' => $data['account_no'],
                    'branch_name' => $data['branch_name'],
                    'ifsc_code' => $data['ifsc_code'],
                    'account_holder' => $data['account_holder'],
                    'account_type' => $data['account_type'],
                    'otp' => '',
                    'service_description' => $data['service_description'],
                    'updated_at'=>date('y-m-d H:i:s')
                ]
            );
        }
        if($updateadminprofile)
        {
            DB::table('vendor_pincode')->where('vendor_id', '=', $data['hidden_id'])->delete();
            
            $pincodearr = array();
            $pincode = $data['pincode'];
            foreach($pincode as $p)
            {
                $arr = array(
                    'pincode' => $p,
                    'vendor_id' => $data['hidden_id']
                );
                $pincodearr[] = $arr;
            }
            DB::table('vendor_pincode')->insert($pincodearr);
            
            return  redirect('/vendor/vendor-profile')->with('success', 'Data saved !');
        }
        else
        {
            return redirect('/vendor/vendor-profile')->with('error', 'Something went wrong !');
        }
    }
    public function monthlyReport()
    {
        $id = Session::get('vendor_id');
        return DB::select('SELECT MONTH(order_product.created_at) AS order_month, SUM(order_product.offer_price*order_product.qty) AS `grand_total` FROM `order_product` INNER JOIN `order` ON `order`.order_id=order_product.order_id WHERE `order`.`payment_status` = 1 and order_product.vendor_id='.$id.' GROUP BY MONTH(order_product.created_at)');
        
    }
    public function addNewProductGallery(Request $request)
    {
        if(request()->segment(4))
        {
            $product_id = request()->segment(3);
            $product_image = db::table('product_image')
                ->where('product_id', $product_id)
                ->get();

            $id = request()->segment(4);
            $single_product_image = db::table('product_image')
                ->where('id', $id)
                ->get();
                
            return view('vendor/new_product_image')->with('product_image', $product_image)->with('product_id',$product_id)->with('id',$id)->with('single_product_image', $single_product_image);
        }
        else
        {
            $product_id = request()->segment(3);
            $product_image = db::table('product_image')
                ->where('product_id', $product_id)
                ->get();
            $id = '';
            $single_product_image = array();
            return view('vendor/new_product_image')->with('product_image', $product_image)->with('product_id',$product_id)->with('id',$id)->with('single_product_image', $single_product_image);
        }
    }
    public function saveProductImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg'
        ]);
    
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
        
        $rimage = $request->file;

        $image = $this->resizeImage($rimage,$imageName,650,650);

        $data = $request->input();
        $hidden_product_id = $data['hidden_product_id'];
        $saveproduct = DB::table('product_image')->insert(
            [
             'image' => $image,
             'product_id' => $hidden_product_id,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($saveproduct)
        {

            return  redirect('/vendor/add-product-gallery/'.$hidden_product_id)->with('success', 'Data saved !');
        }
        else
        {
             return redirect('/vendor/add-product-gallery/'.$hidden_product_id)->with('error', 'Data not saved !');
        }
    }
    public function updateProductImage(Request $request)
    {
        $data = $request->input();

        if($request->hasFile('file'))
        { 
            $rimage = $request->file;
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();

            $image = $this->resizeImage($rimage,$imageName,650,650);

            $fFilePath = 'public/product_image/'.$data['hidden_image'];
            if(file_exists( public_path().'/product_image/'.$data['hidden_image'])){
                unlink($fFilePath);
            }
        }
        else
        {
                $image = $data['hidden_image'];
        }
        $hidden_id = $data['hidden_id'];
        $hidden_product_id = $data['hidden_product_id'];
        $updateproduct = DB::table('product_image')
        ->where('id', $data['hidden_id'])
        ->update(
            [
             'image' => $image,
             'updated_at'=>date('y-m-d H:i:s')
             ]
        );
        if($updateproduct)
        {

            return  redirect('/vendor/edit-product-gallery/'.$hidden_product_id.'/'.$hidden_id)->with('success', 'Data updated !');
        }
        else
        {
             return redirect('/vendor/edit-product-gallery/'.$hidden_product_id.'/'.$hidden_id)->with('error', 'Data not updated !');
        }
    }
    public function resizeImage($rimage,$imageName,$width,$height)
    {
         // $filename = $rimage->getClientOriginalName();
         $image_resize = Image::make($rimage->getRealPath());
         $image_resize->resize($width,$height);
         $image_resize->save(public_path('product_image/'.$imageName));
         if( $image_resize->save(public_path('product_image/'.$imageName)))
         {
             return $imageName;
         }
         else
         {
            return 'default.png';
         }
    }
    public function getSpecification(Request $request)
    {
        $hidden_id = $request->input('hidden_id');
        $specification = db::table('tblproductspecification')
        ->where('product_id', $hidden_id)
        ->get();
        return json_encode($specification);
    }
    public function changeOrderStatus(Request $request)
    {
        $data = $request->input();
        $orderproductautoid = $data['orderproductautoid'];
        $status = $data['status'];
        $updatestatus = DB::table('order_product')
                ->where('id', $orderproductautoid)
                ->update(
                    [
                        'status' => $status,
                        'updated_at'=>date('y-m-d')
                    ]
                );
        if($updatestatus)
        {
            return "Changed";
        }
        else
        {
             return "Failed";
        }
    }
    public function vendor_earning_detail(Request $req)
    {
        $id = Session::get('vendor_id');
        $store_transaction = db::table('tbl_store_transaction_settlement')
        ->where('vendor_id', $id)
        ->get();
        return view('vendor/vendor_earning_detail')->with('store_transaction', $store_transaction);
    }
    public function product_sell_report(Request $req)
    {
        $id = Session::get('vendor_id');
        if(@$_GET['date'])
        {
            $sell_report = db::table('order_product')->select(DB::raw('COUNT(order_product.offer_price) as total_count'),DB::raw('SUM(order_product.offer_price) as total_sell'),DB::raw('SUM(order_product.product_price) as total_product_price'),'product.product_name')
            ->join('product', 'product.id', '=', 'order_product.product_id')
            ->join('order', 'order.order_id', '=', 'order_product.order_id')
            ->where('order_product.vendor_id', $id)
            ->where('order.order_date', $_GET['date'])
            ->groupBy('order_product.product_id')
            ->get();
        }
        else
        {
            $sell_report = db::table('order_product')->select(DB::raw('COUNT(order_product.offer_price) as total_count'),DB::raw('SUM(order_product.offer_price) as total_sell'),DB::raw('SUM(order_product.product_price) as total_product_price'),'product.product_name')
            ->join('product', 'product.id', '=', 'order_product.product_id')
            ->where('order_product.vendor_id', $id)
            ->groupBy('order_product.product_id')
            ->get();
        }
        return view('vendor/product_sell_report')->with('sell_report', $sell_report);
    }
}
