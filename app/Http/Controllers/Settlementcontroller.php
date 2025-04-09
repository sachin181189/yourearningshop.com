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

class Settlementcontroller extends Controller
{
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
}
