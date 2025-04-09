<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use DB;

class RazorpayPaymentController extends Controller
{
    public function store(Request $request)
    {
        $input = $request->all();
        $productarray = Session::get('productarr');
        $orderarray = Session::get('orderarr');
        $orderarray["payment_id"]= $input['razorpay_payment_id'];
        $payment = $input['razorpay_payment_id'];
  
        if(count($input)  && !empty($input['razorpay_payment_id'])) {
                    try {
                        DB::beginTransaction();
                        $orderarray["payment_status"]= 1;
                         $orderarray["order_status"]= 1;
                        $saveorder = DB::table('order')->insertGetId($orderarray);
                            if($saveorder)
                            {
                                $saveproduct = DB::table('order_product')->insert($productarray);
                                if($saveproduct)
                                {
                                    DB::commit();
                                    return true;
                                }
                            }
                            else
                            {
                                 DB::rollback();
                                 return false;
                            }
                    } catch (Throwable $e) {
                        DB::rollback();
                        return false;
                    }
        }
    }
    public function paymentSuccess()
    {
        Session::forget('productarr');
        Session::forget('orderarr');
        Session::forget('grand_total');
        $user_id = Session::get('user_id');
        DB::table('cart')->where('user_id', $user_id)->delete();
        return view('payment_success');
    }
    public function paymentFailed()
    {
        return view('payment_failed');
    }
}
