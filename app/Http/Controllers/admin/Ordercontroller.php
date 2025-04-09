<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;
use PDF;
use Mail;


class Ordercontroller extends Controller
{
    public function orderList(Request $request)
    {
        $query = Order::select('order.*','users.fname','users.lname')
        ->join('users', 'users.id', '=', 'order.user_id')
        ->join('order_product', 'order_product.order_id', '=', 'order.order_id');
        if(@$_GET['date'])
        {
            $query = $query->where('order.order_date',$_GET['date']);
        }
        if(@$_GET['payment_type'])
        {
            $query = $query->where('order.payment_method',$_GET['payment_type']);
        }
        if(@$_GET['pin_code'])
        {
            $vendor_id = [];
            $getVendor =DB::table('vendor_pincode')->select('vendor_id')
            ->where('pincode',$_GET['pin_code'])
            ->groupBy('vendor_id')
            ->get();
            if(count($getVendor) > 0)
            {
                foreach($getVendor as $gv)
                {
                    array_push($vendor_id,$gv->vendor_id);
                }
                if(count($vendor_id) > 0)
                {
                    $query = $query->whereIn('order_product.vendor_id',$vendor_id);
                }
            }
        }
        $order = $query->groupBy('order_product.order_id')
        ->get();
        return view('admin/order_list')->with('order', $order);
    }
    public function orderDetail(Request $request)
    {
        $id = $request->id;
        $order = Order::select('order.id','order.order_id','order.coupon_code','order.coupon_amount','order.shipping_charge','order.payment_id','order.payment_method',
        'order.sub_total','order.grand_total','order.order_status','order.payment_status','order.order_date',
        'users.fname','users.lname','order.assigned_driver',
        'user_address.fname as sfname','user_address.lname as slname','user_address.email','user_address.phone',
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

        $driver = Driver::select('driver.id','driver.driver_name')
        ->where('status',1)
        ->get();
        return view('admin/order_detail')->with('order', $order)->with('product',$product)->with('driver',$driver);
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
    public function generateInvoice(Request $request)
    {
        $data = $request->input();
        $orderid = $data['orderid'];
        $type = $data['type'];
        $order = Order::select('order.id','order.order_id','order.coupon_code','order.coupon_amount','order.shipping_charge','order.payment_id','order.payment_method',
        'order.sub_total','order.grand_total','order.order_status','order.payment_status','order.order_date',
        'users.fname','users.lname','order.assigned_driver','order.invoice_no','order.invoice_file','order.vendor_invoice_file','order.vendor_invoice_no',
        'user_address.fname as sfname','user_address.lname as slname','user_address.email','user_address.phone',
        'user_address.address','user_address.city','user_address.state','user_address.address','user_address.zip',
        'address_type.address_type as addr_type')
        ->join('users', 'users.id', '=', 'order.user_id')
        ->join('user_address', 'user_address.id', '=', 'order.shipping_address')
        ->join('address_type', 'address_type.id', '=', 'user_address.address_type')
        ->where('order.order_id',$orderid)
        ->get();
    
        $product = OrderProduct::select('order_product.*','product.product_name','product.product_image','vendor.gst_no','vendor.vendor_name',
        'vendor.company_name','vendor.address','vendor.city','vendor.state','vendor.zip'
        )
        ->join('product', 'product.id', '=', 'order_product.product_id')
        ->join('vendor', 'vendor.id', '=', 'order_product.vendor_id')
        ->where('order_product.order_id',$orderid)
        ->get();
        $invoice_no =  date('ymdHis').rand(1,1000);
        $cdata = array(
            'product'=>$product,
            'order'=>$order,
            'invoice_no'=>$invoice_no
        );

        if($type == 1)
        {
            $pdf = PDF::loadView('admin/invoice_message', compact('cdata'))->setPaper('a4');
            $path = public_path('invoice/');
            
            $fileName =  $invoice_no.'.pdf' ;
            $pdf->save($path . '/' . $fileName);
            if($pdf->save($path . '/' . $fileName))
            {
                $ginvoice = DB::table('order')
                ->where('order_id', $orderid)
                ->update(
                    [
                        'invoice_no' => $invoice_no,
                        'invoice_file' => $fileName,
                        'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
                if($ginvoice)
                {
                    // sendSms($mobile,$message);
                    echo 'Changed';
                }
                else
                {
                    return "Failed";
                }
            }
            else
            {
                return "Failed";
            }
        }
        else
        {
            $pdf = PDF::loadView('vendor/vendor_invoice', compact('cdata'))->setPaper('a4');
            $path = public_path('vendor_invoice/');
            $invoice_no =  date('ymdHis').rand(1,1000);
            $fileName =  $invoice_no.'.pdf' ;
            $pdf->save($path . '/' . $fileName);
            if($pdf->save($path . '/' . $fileName))
            {
                $ginvoice = DB::table('order')
                ->where('order_id', $orderid)
                ->update(
                    [
                        'vendor_invoice_no' => $invoice_no,
                        'vendor_invoice_file' => $fileName,
                        'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
                if($ginvoice)
                {
                    // sendSms($mobile,$message);
                    echo 'Changed';
                }
                else
                {
                    return "Failed";
                }
            }
            else
            {
                return "Failed";
            }
        }
    }
    public function sendMail($filename, $path, $mailto, $from_mail, $from_name, $subject, $message)
    {  
        
        $file = $path.$filename;
        $file_size = filesize($file);
        $handle = fopen($file, "r");
        $content = fread($handle, $file_size);
        fclose($handle);
        $content = chunk_split(base64_encode($content));
        $uid = md5(uniqid(time()));
        $header = "From: ".$from_name." <".$from_mail.">\r\n";
        $header .= "MIME-Version: 1.0\n";
        $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
        $header .= "This is a multi-part message in MIME format.\r\n";
        $header .= "--".$uid."\r\n";
        $header .= 'Content-Type: text/HTML; charset=ISO-8859-1' . "\r\n";
        $header .= 'Content-Transfer-Encoding: 8bit'. "\n\r\n";
        $header .= $message."\r\n\r\n";
        $header .= "--".$uid."\r\n";
        $header .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; // use different content types here
        $header .= "Content-Transfer-Encoding: base64\r\n";
        $header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
        $header .= $content."\r\n\r\n";
        $header .= "--".$uid."--";
        if (mail($mailto, $subject, "", $header)) 
        {
        echo true; // or use booleans here
        }
         else 
         {
        echo false;
        }
 
    }
    public function paymentList()
    {
        $query = Order::select('order.*','users.fname','users.lname')
        ->join('users', 'users.id', '=', 'order.user_id')
        ->join('order_product', 'order_product.order_id', '=', 'order.order_id');
        if(@$_GET['date'])
        {
            $query = $query->where('order.order_date',$_GET['date']);
        }
        if(@$_GET['payment_type'])
        {
            $query = $query->where('order.payment_method',$_GET['payment_type']);
        }
        $order = $query->groupBy('order_product.order_id')
        ->get();
        return view('admin/payment_list')->with('order', $order);
    }
    public function filterByPaymentMethod(Request $request)
    {
        $data = $request->input();
        $payment_type = $data['payment_type'];
        return redirect()->to('admin/payment-list/'.$payment_type);
    }
    public function assignDriver(Request $request)
    {
        $data = $request->input();
        $hidden_order_id = $data['hidden_order_id'];
        $driver = $data['driver'];
        $assigndriver = DB::table('order')
                ->where('order_id', $hidden_order_id)
                ->update(
                    [
                        'assigned_driver' => $driver,
                        'updated_at'=>date('Y-m-d H:i:s')
                    ]
                );
        if($assigndriver)
        {
            $assigndriver = DB::table('order_product')
            ->where('order_id', $hidden_order_id)
            ->update(
                [
                    'assigned_driver' => $driver,
                    'updated_at'=>date('Y-m-d H:i:s')
                ]
            );
            return  redirect('admin/view-order/'.$hidden_order_id)->with('success', 'Driver assigned !');
        }
        else
        {
            return  redirect('admin/view-order/'.$hidden_order_id)->with('error', 'Something went wrong !');
        }
    }
    
}
