<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;

use App\Models\Admin;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Products;
use App\Models\Order;
use App\Models\Brands;
use App\Models\Category;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;
use Image;
use URL;
use App\Models\OrderProduct;

class Usercontroller extends Controller
{
    public function index()
    {
    	return view('admin/login');
    }
    public function dashboard()
    {
        $vendorcount = Vendor::count();
        $usercount = User::count();
        $productcount = Products::count();
        $ordercount = Order::count();
        $brandcount = Brands::count();
        $storecount = DB::table('tblstorerequest')->count();

    	return view('admin/dashboard')
        ->with('vendorcount', $vendorcount)
        ->with('usercount', $usercount)
        ->with('productcount', $productcount)
        ->with('ordercount', $ordercount)
        ->with('brandcount', $brandcount)
        ->with('storecount', $storecount);
    }
    public function monthlyReport()
    {
        return DB::select('SELECT MONTH(created_at) AS order_month, SUM(grand_total) AS `grand_total` FROM `order` WHERE `payment_status` = 1 GROUP BY MONTH(created_at)');
        
    }
    public function login(Request $req)
    {
        $email = $req->input('email');
        $password = $req->input('password');
        $users = Admin::select()
                     ->where('email', $email)
                     ->where('password', md5($password))
                     ->get();
         
        if(count($users) == 1)
        {
            foreach($users as $val)
            {
                $id = $val->id;
                $role = $val->role;
            }
            Session::put('admin_id', $id);
            Session::put('role', $role);
            return redirect('admin/dashboard');
        }
        else
        {
            return redirect('yes-admin')->with('msg', 'Invalid credential !');;
        }
    }
    public function vendorList()
    {
        $vendor = Vendor::select()
        ->get();
        return view('admin/vendor_list')->with('vendor', $vendor);
    }
    public function addNewVendor(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $vendor = Vendor::orderBy('id', 'DESC')
                ->where('id', $id)
                ->get();
            $pincode = DB::table('vendor_pincode')
                    ->where('vendor_id', $id)
                    ->get();
            return view('admin/new_vendor')->with('vendor', $vendor)->with('id',$id)->with('pincode',$pincode);
        }
        else
        {
            return view('admin/new_vendor')->with('id','');;
        }
    }
    public function saveVendor(Request $request)
    {
        
        $request->validate([
            'vendor_name'=>'required',
            'company_name'=>'required',
            'email'=>'required',
            'mobile'=>'required',
            'gst_no'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required',
            'service_description'=>'required',
            'password'=>'required',
            'status'=>'required',
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10048',
        ]);
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
   
        if(Request()->file->move(public_path('cheque_image'), $imageName))
        {
            $image = $imageName;
        }
        else
        {
            return redirect('admin/add-new-vendor')->with('error', 'Cheque can not be blank !');
        }

        $data = $request->input();
        
        $checkIsPaid = DB::table('vendor')->select('id')->where('id', '=', $data['hidden_id'])->where('is_paid', '=', 1)->get();

        $savevendor = DB::table('vendor')->insertGetId(
            [
             'store_id' => 'YES'.rand(1,100),
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
             'user_type' => 1,
             'bank_name' => $data['bank_name'],
             'account_no' => $data['account_no'],
             'branch_name' => $data['branch_name'],
             'ifsc_code' => $data['ifsc_code'],
             'account_holder' => $data['account_holder'],
             'account_type' => $data['account_type'],
             'otp' => '',
             'service_description' => $data['service_description'],
             'password' => md5($data['password']),
             'status' => $data['status'],
             'cheque_image' => $image,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($savevendor)
        {
            $pincodearr = array();
            $pincode = $data['pincode'];
            foreach($pincode as $p)
            {
                $arr = array(
                    'pincode' => $p,
                    'vendor_id' => $savevendor
                );
                $pincodearr[] = $arr;
            }
            DB::table('vendor_pincode')->insert($pincodearr);
            
            $paid_amount = $data['paid_amount'];
            $is_paid = $data['is_paid'];
            $parent_referral_code = $data['hidden_referral_code'];
            $userId = array();
            
            if(count($checkIsPaid) == 0)
            {
                if($is_paid == 1)
                {
                    $royalty_income_first_user = ($paid_amount*5)/100;
                    $royalty_income_for_other = ($paid_amount*1)/100;
                    
                    for($i=1;$i<6;$i++)
                    {
                        $getUserId = DB::table('users')->select('id', 'parent_referral_code')->where('referral_code', '=', $parent_referral_code)->get();
                        foreach($getUserId as $gui)
                        {
                            $parent_referral_code = $gui->parent_referral_code;
                            $userId[] = $gui->id;
                        }
                    }
                }
                
                $index = 1;
                foreach($userId as $ui)
                {
                    $checkUser = DB::table('user_wallet')->select('id', 'wallet_amount')->where('user_id', '=', $ui)->get();
                    if(count($checkUser) > 0)
                    {
                        // echo $checkUser['wallet_amount'];
                        foreach($checkUser as $cu)
                        {
                            $wallet_amount = $cu->wallet_amount;
                        }
                        
                        if($index == 1)
                        {
                            $transactionData = array(
                                'wallet_amount' => $royalty_income_first_user+$wallet_amount,
                                'yes_amount' => (10*($royalty_income_first_user+$wallet_amount))/100,
                            );
                            $walletTransaction = array(
                                'user_id' => $ui,
                                'vendor_id' => $savevendor,
                                'transaction_amount' => $royalty_income_first_user,
                                'message' => "Royalty Income Successfully Added In Your Wallet",
                                'type' => 0,
                                'status' => 1
                            );
                        }
                        else
                        {
                            $transactionData = array(
                                'wallet_amount' => $royalty_income_for_other+$wallet_amount,
                                'yes_amount' => (10*($royalty_income_for_other+$wallet_amount))/100,
                            );
                            $walletTransaction = array(
                                'user_id' => $ui,
                                'vendor_id' => $savevendor,
                                'transaction_amount' => $royalty_income_for_other,
                                'message' => "Royalty Income Successfully Added In Your Wallet",
                                'type' => 0,
                                'status' => 1
                            );
                        }
                        DB::table('user_wallet')->where('user_id', $ui)->update($transactionData);
                        DB::table('wallet_transaction')->insert($walletTransaction);
                    }
                    else
                    {
                        if($index == 1)
                        {
                            $transactionData = array(
                                'user_id' => $ui,
                                'wallet_amount' => $royalty_income_first_user,
                                'yes_amount' => (10*($royalty_income_first_user))/100,
                            );
                            $walletTransaction = array(
                                'user_id' => $ui,
                                'vendor_id' => $savevendor,
                                'transaction_amount' => $royalty_income_first_user,
                                'message' => "Royalty Income Successfully Added In Your Wallet",
                                'type' => 0,
                                'status' => 1
                            );
                        }
                        else
                        {
                            $transactionData = array(
                                'user_id' => $ui,
                                'wallet_amount' => $royalty_income_for_other,
                                'yes_amount' => (10*($royalty_income_for_other))/100,
                            );
                            $walletTransaction = array(
                                'user_id' => $ui,
                                'vendor_id' => $savevendor,
                                'transaction_amount' => $royalty_income_for_other,
                                'message' => "Royalty Income Successfully Added In Your Wallet",
                                'type' => 0,
                                'status' => 1
                            );
                        }
                        DB::table('user_wallet')->insert($transactionData);
                        DB::table('wallet_transaction')->insert($walletTransaction);
                    }
                    $index++;
                }
            }
            
            return  redirect('admin/add-new-vendor')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-vendor')->with('error', 'Data not saved !');
        }
    }
    public function updateVendor(Request $request)
    {
        $request->validate([
            'vendor_name'=>'required',
            'company_name'=>'required',
            'email'=>'required',
            'mobile'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required',
            'service_description'=>'required',
            'password'=>'required',
            'status'=>'required'
        ]);
        
        $userId = array();
        $data = $request->input();
        if($data['hidden_password'] == $data['password'])
        {
            $password = $data['password'];
        }
        else
        {
            $password = md5($data['password']);
        }
        if($request->hasFile('file'))
        { 
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
    
            if(Request()->file->move(public_path('cheque_image'), $imageName))
            {
                $image = $imageName;
            }
            else
            {
                $image = 'default.png';
            }
            $fFilePath = 'public/cheque_image/'.$data['hidden_image'];
            if(file_exists( public_path().'/cheque_image/'.$data['hidden_image'])){
                unlink($fFilePath);
            }
        }
        else
        {
                $image = $data['hidden_image'];
        }
        
        $checkIsPaid = DB::table('vendor')->select('id')->where('id', '=', $data['hidden_id'])->where('is_paid', '=', 1)->get();
        
        $updatevendor = DB::table('vendor')
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
                     'parent_referral_code' => $data['hidden_referral_code'],
                     'paid_amount' => $data['paid_amount'],
                     'is_paid' => $data['is_paid'],
                     'otp' => '',
                     'password' => $password,
                     'service_description' => $data['service_description'],
                     'status' => $data['status'],
                     'cheque_image' => $image,
                     'status' => $data['status'],
                     'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
        if($updatevendor)
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
            
            $paid_amount = $data['paid_amount'];
            $is_paid = $data['is_paid'];
            $parent_referral_code = $data['hidden_referral_code'];
            
            if(count($checkIsPaid) == 0)
            {
                if($is_paid == 1)
                {
                    $royalty_income_first_user = ($paid_amount*5)/100;
                    $royalty_income_for_other = ($paid_amount*1)/100;
                    
                    for($i=1;$i<6;$i++)
                    {
                        $getUserId = DB::table('users')->select('id', 'parent_referral_code')->where('referral_code', '=', $parent_referral_code)->get();
                        foreach($getUserId as $gui)
                        {
                            $parent_referral_code = $gui->parent_referral_code;
                            $userId[] = $gui->id;
                        }
                    }
                }
                
                $index = 1;
                foreach($userId as $ui)
                {
                    $checkUser = DB::table('user_wallet')->select('id', 'wallet_amount')->where('user_id', '=', $ui)->get();
                    if(count($checkUser) > 0)
                    {
                        // echo $checkUser['wallet_amount'];
                        foreach($checkUser as $cu)
                        {
                            $wallet_amount = $cu->wallet_amount;
                        }
                        
                        if($index == 1)
                        {
                            $transactionData = array(
                                'wallet_amount' => $royalty_income_first_user+$wallet_amount,
                                'yes_amount' => (10*($royalty_income_first_user+$wallet_amount))/100,
                            );
                            $walletTransaction = array(
                                'user_id' => $ui,
                                'vendor_id' => $data['hidden_id'],
                                'transaction_amount' => $royalty_income_first_user,
                                'message' => "Royalty Income Successfully Added In Your Wallet",
                                'type' => 0,
                                'status' => 1
                            );
                        }
                        else
                        {
                            $transactionData = array(
                                'wallet_amount' => $royalty_income_for_other+$wallet_amount,
                                'yes_amount' => (10*($royalty_income_for_other+$wallet_amount))/100,
                            );
                            $walletTransaction = array(
                                'user_id' => $ui,
                                'vendor_id' => $data['hidden_id'],
                                'transaction_amount' => $royalty_income_for_other,
                                'message' => "Royalty Income Successfully Added In Your Wallet",
                                'type' => 0,
                                'status' => 1
                            );
                        }
                        DB::table('user_wallet')->where('user_id', $ui)->update($transactionData);
                        DB::table('wallet_transaction')->insert($walletTransaction);
                    }
                    else
                    {
                        if($index == 1)
                        {
                            $transactionData = array(
                                'user_id' => $ui,
                                'wallet_amount' => $royalty_income_first_user,
                                'yes_amount' => (10*($royalty_income_first_user+$wallet_amount))/100,
                            );
                            $walletTransaction = array(
                                'user_id' => $ui,
                                'vendor_id' => $data['hidden_id'],
                                'transaction_amount' => $royalty_income_first_user,
                                'message' => "Royalty Income Successfully Added In Your Wallet",
                                'type' => 0,
                                'status' => 1
                            );
                        }
                        else
                        {
                            $transactionData = array(
                                'user_id' => $ui,
                                'wallet_amount' => $royalty_income_for_other,
                                'yes_amount' => (10*($royalty_income_for_other+$wallet_amount))/100,
                            );
                            $walletTransaction = array(
                                'user_id' => $ui,
                                'vendor_id' => $data['hidden_id'],
                                'transaction_amount' => $royalty_income_for_other,
                                'message' => "Royalty Income Successfully Added In Your Wallet",
                                'type' => 0,
                                'status' => 1
                            );
                        }
                        DB::table('user_wallet')->insert($transactionData);
                        DB::table('wallet_transaction')->insert($walletTransaction);
                    }
                    
                    
                    
                    $index++;
                }
            }
            
            return  redirect('admin/edit-vendor/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-vendor/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function customerList()
    {
        $customer = User::select()
        ->get();
        return view('admin/customer_list')->with('customerList', $customer);
    }
    public function customerDetail(Request $request)
    {
        $id = $request->id;
        $customer_detail = User::select()
        ->where('id',$id)
        ->first();
        
        $customer_wallet = DB::table('user_wallet')->select()
        ->where('user_id',$id)
        ->first();
        
        $customer_address = DB::table('user_address')->select()
        ->where('user_id',$id)
        ->get();
        
        $billing_address = DB::table('billing_address')->select()
        ->where('user_id',$id)
        ->get();
        
        $order = Order::select('order.*','users.fname','users.lname')
        ->join('users', 'users.id', '=', 'order.user_id')
        ->where('users.id',$id)
        ->get();
        
        $customer_bp_transaction = DB::table('tbl_user_bp_settlement')->select('tbl_user_bp_settlement.*','users.fname','users.lname')
        ->join('users', 'users.id', '=', 'tbl_user_bp_settlement.reffered_user_id')
        ->where('tbl_user_bp_settlement.user_id',$id)
        ->get();
        
        $customer_dp_transaction = DB::table('tbl_user_dp_settlement')->select('tbl_user_dp_settlement.*','vendor.vendor_name')
        ->join('vendor', 'vendor.id', '=', 'tbl_user_dp_settlement.vendor_id')
        ->where('tbl_user_dp_settlement.user_id',$id)
        ->get();
        
        return view('admin/customer_detail')
        ->with('customer_detail', $customer_detail)
        ->with('customer_address',$customer_address)
        ->with('order', $order)
        ->with('customer_wallet',$customer_wallet)
        ->with('customer_bp_transaction', $customer_bp_transaction)
        ->with('customer_dp_transaction', $customer_dp_transaction)
        ->with('billing_address',$billing_address);
    }
    public function changeStatus(Request $request)
    {
        $data = $request->input();
        $user_id = $data['userid'];
        $status = $data['status'];
        $updatestatus = DB::table('users')
                ->where('id', $user_id)
                ->update(
                    [
                        'status' => $status
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
    public function changeUserStatus(Request $request)
    {
        $data = $request->input();
        $id = $data['id'];
        $status = $data['status'];
        $updatestatus = DB::table('admins')
                ->where('id', $id)
                ->update(
                    [
                        'status' => $status
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
    public function userList()
    {
        $user = Admin::select()
        ->get();
        return view('admin/user_list')->with('userList', $user);
    }
    public function addNewUser(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $user = Admin::orderBy('id', 'DESC')
                ->where('id', $id)
                ->get();
            return view('admin/new_user')->with('user', $user)->with('id',$id);
        }
        else
        {
            return view('admin/new_user')->with('id','');;
        }
    }
    public function saveUser(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'email'=>'required',
            'phone'=>'required',
            'role'=>'required'
        ]);
        $data = $request->input();
        $saveuser = DB::table('admins')->insertGetId(
            [
             'name' => $data['name'],
             'email' => $data['email'],
             'phone' => $data['phone'],
             'role' => $data['role'],
             'password' => md5($data['password']),
             'plain_password' => $data['password'],
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($saveuser)
        {
            return  redirect('admin/add-new-user')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-user')->with('error', 'Data not saved !');
        }
    }
    public function updateUser(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'email'=>'required',
            'phone'=>'required',
            'role'=>'required'
        ]);
        $data = $request->input();
        $updateuser = DB::table('admins')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                         'name' => $data['name'],
                         'email' => $data['email'],
                         'phone' => $data['phone'],
                         'role' => $data['role'],
                         'password' => md5($data['password']),
                         'plain_password' => $data['password'],
                         'updated_at'=>date('y-m-d')
                    ]
                );
        
        if($updateuser)
        {
            return  redirect('admin/edit-user/'.$data['hidden_id'])->with('success', 'Data updated !');
        }
        else
        {
             return redirect('admin/edit-user/'.$data['hidden_id'])->with('error', 'Data not updated !');
        }
    }
    public function logout(Request $request)
    {
        Session::flush();
        return redirect('yes-admin');
    }
    public function adminProfile()
    {
        $id = Session::get('admin_id');
        $profile = Admin::select()
        ->where('id',$id)
        ->get();
        return view('admin/admin_profile')->with('admin_profile', $profile);
    }
    public function updateAdminProfile(Request $request)
    {
        $data = $request->input();
        if($data['password'] != '' && $data['cpassword'] != '')
        {
            if($data['password'] == $data['cpassword'])
            {
                $updateadminprofile = DB::table('admins')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'password' => md5($data['password']),
                        'updated_at'=>date('y-m-d')
                    ]
                );
            }
            else
            {
                return redirect('admin/profile')->with('error', 'Password not match !');
            }

        }
        else
        {
            $updateadminprofile = DB::table('admins')
            ->where('id', $data['hidden_id'])
            ->update(
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'updated_at'=>date('y-m-d')
                ]
            );
        }
        if($updateadminprofile)
        {
            return  redirect('admin/profile')->with('success', 'Data saved !');
        }
        else
        {
            return redirect('admin/profile')->with('error', 'Something went wrong !');
        }
    }
    public function forgetPassword()
    {
        return view('admin/forget_password');
    }
    public function sendResetCode(Request $request)
    {
        $data = $request->input();
        $mailto = $data['email'];
        $from_name = "YES";
        $from_mail = "yourearningshop@gmail.com";
        $subject = "Forget Password.";
        $base_64 = base64_encode($mailto);
        $url = URL::to('change-password?token='.$base_64);
        $message = "<a href='".$url."'>Click here to reset your password</a>";
        $saveToken = DB::table('admins')
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
            if($sendm)
            {
                return  redirect('admin/forget-password')->with('success', 'Link sent to your mail Please check mail ..');
            }
            else
            {
                return  redirect('admin/forget-password')->with('error', 'Something went wrong !');
            }
        }
        else
        {
            return  redirect('admin/forget-password')->with('error', 'Something went wrong !');
        }
        
    }
    public function viewResetForm()
    {
        $encpEmail = $_GET['token'];
        $plainEmail = base64_decode($encpEmail);
        $admintok = Admin::select()
        ->where('token',$encpEmail)
        ->get();
        if(count($admintok) > 0)
        {
            return view('admin/reset_password')->with('email',$plainEmail);
        }
        else
        {
            return  redirect('admin/')->with('error', 'Token Expired !');
        }
    }
    public function resetPassword(Request $request)
    {
        $data = $request->input();
        if($data['password'] == $data['cpassword'])
            {
                $updateadminpassword = DB::table('admins')
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
                    return redirect('admin/')->with('success', 'Password reset !');
                }
                else
                {
                    return redirect('admin/reset-password')->with('error', 'Something went wrong !');
                }
            }
            else
            {
                return redirect('admin/reset-password')->with('error', 'Password not match !');
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
    public static function getNotification()
    {
        return DB::table('notification')
        ->where('status',1)
        ->get();
    }
    public static function getMessageCount()
    {
        return DB::table('contactus')
        ->get();
    }
    public function contactusList()
    {
        $contact = DB::table('contactus')
        ->get();
        return view('admin/contactuslist')->with('contact', $contact);
    }
    public function deleteMessage(Request $request)
    {
        $data = $request->input();
        $id = $data['msgid'];
        $removemessage = DB::table('contactus')
        ->where('id', $id)
        ->delete();
        if($removemessage)
        {
            echo 'Changed';
        }
        else
        {
            echo 'error';
        }
    }
    public function readNotification(Request $request)
    {
        $data = $request->input();
        $id = $data['id'];
        $removenotification = DB::table('notification')
        ->where('id', $id)
        ->delete();
        if($removenotification)
        {
            return 'Changed';
        }
        else
        {
             return "Failed";
        }
    }
    public function driverList()
    {
        $driver = Driver::orderBy('id', 'DESC')
        ->get();
        return view('admin/driver_list')->with('driver', $driver);
    }
    public function uploadImage($rimage,$imageName,$folder)
    {
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
    public function addNewDriver(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $driver = Driver::select()
                ->where('id', $id)
                ->get();
            return view('admin/new_driver')->with('driver', $driver)->with('id',$id);
        }
        else
        {
            return view('admin/new_driver')->with('id','');;
        }
    }
    public function saveDriver(Request $request)
    {
        $data = $request->input();
        $request->validate([
            'driver_name'=>'required',
            'email'=>'required',
            'mobile'=>'required',
            'password'=>'required',
            'address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ]);
        if($request->hasFile('driving_licence_front'))
        { 
            $imageName = rand().time().'.'.Request()->driving_licence_front->getClientOriginalExtension();
            $rimage = $request->driving_licence_front;
            $folder = 'driver_licence/';
            $driving_licence_front = $this->uploadImage($rimage,$imageName,$folder);
        }
        else
        {
            $driving_licence_front = $data['hidden_driving_licence_front'];
        }
        if($request->hasFile('driving_licence_back'))
        { 
            $imageName = rand().time().'.'.Request()->driving_licence_back->getClientOriginalExtension();
            $rimage = $request->driving_licence_back;
            $folder = 'driver_licence/';
            $driving_licence_back = $this->uploadImage($rimage,$imageName,$folder);
        }
        else
        {
            $driving_licence_back = $data['hidden_driving_licence_back'];
        }

        $savedriverid = DB::table('driver')->insertGetId(
            [
             'driver_name' => $data['driver_name'],
             'email' => $data['email'],
             'mobile' => $data['mobile'],
             'password' => md5($data['password']),
             'address' => $data['address'],
             'alternate_mobile' => $data['alternate_mobile'],
             'city' => $data['city'],
             'state' => $data['state'],
             'zip' => $data['zip'],
             'bank_name' => $data['bank_name'],
             'account_no' => $data['account_no'],
             'branch_name' => $data['branch_name'],
             'ifsc_code' => $data['ifsc_code'],
             'account_holder' => $data['account_holder'],
             'account_type' => $data['account_type'],
             'status' => $data['status'],
             'driving_licence_front'=>$driving_licence_front,
             'driving_licence_back'=>$driving_licence_back,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($savedriverid)
        {
            return  redirect('admin/edit-driver-aadhar/'.$savedriverid)->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-driver-aadhar/'.$savedriverid)->with('error', 'Data not saved !');
        }
    }
    function editDriverAadhar(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $aadhar = Driver::select('aadhar_front','aadhar_back')
                ->where('id', $id)
                ->get();
            return view('admin/update_adhar')->with('aadhar', $aadhar)->with('id',$id);
        }
    }
    function updateAadhar(Request $request)
    {
        $data = $request->input();
        if($request->hasFile('aadhar_front'))
        {
            $imageName = rand().time().'.'.Request()->aadhar_front->getClientOriginalExtension();
            $rimage = $request->aadhar_front;
            $folder = 'driver_aadhar_image/';
            $aadhar_front = $this->uploadImage($rimage,$imageName,$folder);
        }
        else
        {
            $aadhar_front = $data['hidden_aadhar_front'];
        }
        if($request->hasFile('aadhar_back'))
        {
            $imageName = rand().time().'.'.Request()->aadhar_back->getClientOriginalExtension();
            $rimage = $request->aadhar_back;
            $folder = 'driver_aadhar_image/';
            $aadhar_back = $this->uploadImage($rimage,$imageName,$folder);
        }
        else
        {
            $aadhar_back = $data['hidden_aadhar_back'];
        }

        $updateaadhar = DB::table('driver')
        ->where('id', $data['hidden_id'])
        ->update(
            [
             'aadhar_front'=>$aadhar_front,
             'aadhar_back'=>$aadhar_back,
             'updated_at'=>date('y-m-d H:i:s')
             ]
        );
        if($updateaadhar)
        {
            return  redirect('admin/edit-driver-rc/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-driver-rc/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    function editDriverRc(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $rcimage = Driver::select('rc_image','driver_image')
                ->where('id', $id)
                ->get();
            return view('admin/update_rc')->with('rcimage', $rcimage)->with('id',$id);
        }
    }
    function updateRc(Request $request)
    {
        $data = $request->input();
        if($request->hasFile('rc_image'))
        {
            $imageName = rand().time().'.'.Request()->rc_image->getClientOriginalExtension();
            $rimage = $request->rc_image;
            $folder = 'driver_rc_image/';
            $rc_image = $this->uploadImage($rimage,$imageName,$folder);
        }
        else
        {
            $rc_image = $data['hidden_rc_image'];
        }

        if($request->hasFile('driver_image'))
        { 
            $imageName = rand().time().'.'.Request()->driver_image->getClientOriginalExtension();
            $rimage = $request->driver_image;
            $folder = 'driver_image/';
            $driver_image = $this->uploadImage($rimage,$imageName,$folder);
        }
        else
        {
            $driver_image = $data['hidden_driver_image'];
        }

        $updateaadhar = DB::table('driver')
        ->where('id', $data['hidden_id'])
        ->update(
            [
             'rc_image'=>$rc_image,
             'driver_image'=>$driver_image,
             'updated_at'=>date('y-m-d H:i:s')
             ]
        );
        if($updateaadhar)
        {
            return  redirect('admin/edit-driver-rc/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-driver-rc/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function updateDriver(Request $request)
    {
        $data = $request->input();
        $request->validate([
            'driver_name'=>'required',
            'email'=>'required',
            'mobile'=>'required',
            'password'=>'required',
            'address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ]);
        if($request->hasFile('driving_licence_front'))
        { 
            $imageName = rand().time().'.'.Request()->driving_licence_front->getClientOriginalExtension();
            $rimage = $request->driving_licence_front;
            $folder = 'driver_licence/';
            $driving_licence_front = $this->uploadImage($rimage,$imageName,$folder);
        }
        else
        {
            $driving_licence_front = $data['hidden_driving_licence_front'];
        }
        if($request->hasFile('driving_licence_back'))
        { 
            $imageName = rand().time().'.'.Request()->driving_licence_back->getClientOriginalExtension();
            $rimage = $request->driving_licence_back;
            $folder = 'driver_licence/';
            $driving_licence_back = $this->uploadImage($rimage,$imageName,$folder);
        }
        else
        {
            $driving_licence_back = $data['hidden_driving_licence_back'];
        }

        $updatedriver = DB::table('driver')
        ->where('id', $data['hidden_id'])
        ->update(
            [
             'driver_name' => $data['driver_name'],
             'email' => $data['email'],
             'mobile' => $data['mobile'],
             'password' => md5($data['password']),
             'address' => $data['address'],
             'alternate_mobile' => $data['alternate_mobile'],
             'city' => $data['city'],
             'state' => $data['state'],
             'zip' => $data['zip'],
             'bank_name' => $data['bank_name'],
             'account_no' => $data['account_no'],
             'branch_name' => $data['branch_name'],
             'ifsc_code' => $data['ifsc_code'],
             'account_holder' => $data['account_holder'],
             'account_type' => $data['account_type'],
             'status' => $data['status'],
             'driving_licence_front'=>$driving_licence_front,
             'driving_licence_back'=>$driving_licence_back,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($updatedriver)
        {
            return  redirect('admin/edit-driver-aadhar/'.$data['hidden_id'])->with('success', 'Data updated !');
        }
        else
        {
             return redirect('admin/edit-driver-aadhar/'.$data['hidden_id'])->with('error', 'Data not updated !');
        }
    }
    public function achiversList()
    {
        $user = DB::table('tbl_achivers')->select('tbl_achivers.*','users.fname')
         ->join('users', 'users.id', '=', 'tbl_achivers.user_id')
        ->get();
        
        return view('admin/achivers_list')->with('achivers', $user);
    }
    public function add_new_achivers(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $achivers = DB::table('tbl_achivers')
                ->where('id', $id)
                ->get();
            $user = User::select()
            ->where('status', 1)
            ->get();

            return view('admin/new_achivers')->with('achivers', $achivers)->with('id',$id)->with('user',$user);
        }
        else
        {
            $user = User::select()
            ->where('status', 1)
            ->get();
            return view('admin/new_achivers')->with('id','')->with('user',$user);
        }
    }
    public function save_achivers(Request $request)
    {
        $request->validate([
            'rank'=>'required',
            'user'=>'required',
            'about'=>'required',
            'status'=>'required'
        ]);

        $data = $request->input();
        $saveachivers = DB::table('tbl_achivers')->insert(
            [
             'rank' => $data['rank'],
             'user_id' => $data['user'],
             'status' => $data['status'],
             'about' => $data['about'],
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($saveachivers)
        {
            return  redirect('admin/add-new-achivers')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-achivers')->with('error', 'Data not saved !');
        }
    }
    public function update_achivers(Request $request)
    {
        $request->validate([
            'rank'=>'required',
            'user'=>'required',
            'about'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();

        $updateachivers = DB::table('tbl_achivers')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                         'rank' => $data['rank'],
                         'user_id' => $data['user'],
                         'status' => $data['status'],
                         'about' => $data['about'],
                         'created_at'=>date('y-m-d'),
                         'updated_at'=>date('y-m-d')
                    ]
                );
        if($updateachivers)
        {
            return  redirect('admin/edit-achivers/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-achivers/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function remove_achivers(Request $request)
    {
        $data = $request->input();
        $id = $data['achiverid'];
        $removeachiver = DB::table('tbl_achivers')
        ->where('id', $id)
        ->delete();
        if($removeachiver)
        {
            echo 'Changed';
        }
        else
        {
            echo 'error';
        }
    }
    
    // Testimonial 
    public function testimonialList()
    {
        $user = DB::table('tbl_testimonial')->select('tbl_testimonial.*')
        ->get();
        
        return view('admin/testimonial_list')->with('testimonial', $user);
    }
    public function add_new_testimonial(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $testimonial = DB::table('tbl_testimonial')
                ->where('id', $id)
                ->get();

            return view('admin/new_testimonial')->with('testimonial', $testimonial)->with('id',$id);
        }
        else
        {
            return view('admin/new_testimonial')->with('id','');
        }
    }
    public function save_testimonial(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'role'=>'required',
            'message'=>'required',
            'image_url'=>'required',
            'status'=>'required'
        ]);

        $data = $request->input();
        $savetestimonial = DB::table('tbl_testimonial')->insert(
            [
             'name' => $data['name'],
             'role' => $data['role'],
             'message' => $data['message'],
             'image_url' => $data['image_url'],
             'status' => $data['status'],
             'create_date'=>date('y-m-d'),
             'update_date'=>date('y-m-d')
             ]
        );
        if($savetestimonial)
        {
            return  redirect('admin/add-new-testimonial')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-testimonialname')->with('error', 'Data not saved !');
        }
    }
    public function update_testimonial(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'role'=>'required',
            'message'=>'required',
            'image_url'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();

        $updatetestimonial = DB::table('tbl_testimonial')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                         'name' => $data['name'],
                         'role' => $data['role'],
                         'status' => $data['status'],
                         'message' => $data['message'],
                         'image_url' => $data['image_url'],
                         'create_date'=>date('y-m-d'),
                         'update_date'=>date('y-m-d')
                    ]
                );
        if($updatetestimonial)
        {
            return  redirect('admin/edit-testimonial/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-testimonial/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function remove_testimonial(Request $request)
    {
        $data = $request->input();
        $id = $data['testimonialid'];
        $removetestimonial = DB::table('tbl_testimonial')
        ->where('id', $id)
        ->delete();
        if($removetestimonial)
        {
            echo 'Changed';
        }
        else
        {
            echo 'error';
        }
    }
    public function show_connection(Request $request)
    {
        $data = $request->input();
        $referral_id = $data['referral_id'];
        $user = User::select('fname','id','image')
        ->where('parent_referral_code',$referral_id)
        ->get();
        echo json_encode($user);
    }
    public function vendor_detail(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $vendor_detail = DB::table('vendor')
                ->where('id', $id)
                ->get();
            $order = OrderProduct::select('order.id','order_product.order_id','users.fname','users.lname','order.order_date','order.sub_total','order.grand_total','order.order_status')
            ->join('order', 'order.order_id', '=', 'order_product.order_id')
            ->join('users', 'users.id', '=', 'order.user_id')
            ->where('order_product.vendor_id',$id)
            ->groupBy('order.order_id')
            ->get();
            
            $transaction_settlement = DB::table('tbl_store_transaction_settlement')
            ->where('vendor_id', $id)
            ->get();

            return view('admin/vendor_detail')->with('vendor_detail', $vendor_detail)->with('order', $order)->with('transaction_settlement', $transaction_settlement);
        }
        else
        {
            return view('admin/vendor_detail')->with('id','');
        }
    }
    public function driver_detail(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $driver_detail = DB::table('driver')
                ->where('id', $id)
                ->get();
            $order = OrderProduct::select('order.id','order_product.order_id','users.fname','users.lname','order.order_date','order.sub_total','order.grand_total','order.order_status')
            ->join('order', 'order.order_id', '=', 'order_product.order_id')
            ->join('users', 'users.id', '=', 'order.user_id')
            ->where('order_product.assigned_driver',$id)
            ->groupBy('order.order_id')
            ->get();

            return view('admin/driver_detail')->with('driver_detail', $driver_detail)->with('order', $order);
        }
        else
        {
            return view('admin/driver_detail')->with('id','');
        }
    }
}
