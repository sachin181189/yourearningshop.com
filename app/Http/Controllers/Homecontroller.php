<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Slider;
use App\Models\Banner;
use App\Models\Products;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brands;
use App\Models\Vendor;
use App\Models\User;
use Session;
use DB;
use Mail;

class Homecontroller extends Controller
{
    public function shop()
    {
        $alldata = array();
        $cateproductdata = array();
        $latest_product = Products::select('product.*','category.category')
        ->join('category', 'category.id', '=', 'product.category_id')
            ->where('product.status',1)
            ->orderBy('product.id','desc')
            ->skip(0)->take(10)
            ->get();
            
            // echo "<pre>";
            // print_r($latest_product);
            // die();
            
            $best_seller_product = Products::select('product.*','category.category')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->where('product.status',1)
            ->where('product.is_best_seller',1)
            ->orderBy('product.id','desc')
            ->skip(0)->take(10)
            ->get();
            $deal_of_the_week = Products::select('product.*','category.category')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->where('product.status',1)
            ->where('product.is_todays_deal',1)
            ->orderBy('product.id','desc')
            ->skip(0)->take(10)
            ->get();
            $trending_product = Products::select('product.*','category.category')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->where('product.status',1)
            ->where('product.is_trending',1)
            ->orderBy('product.id','desc')
            ->skip(0)->take(10)
            ->get();
            $featured_product = Products::select('product.*','category.category')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->where('product.status',1)
            ->where('product.is_featured',1)
            ->orderBy('product.id','desc')
            ->skip(0)->take(10)
            ->get();
        
        
        $slider = Slider::select()
        ->where('status',1)
        ->get();
        $banner = Banner::select()
        ->where('status',1)
        ->get();
        $category = Category::select('id','category','slug','image_icon')
        ->where('status',1)
        ->orderBy('id','desc')
        ->get();
        
        $mainmenu = Category::select()
        ->where('status',1)
        ->where('show_in_menu',1)
        ->get();

        $brand = Brands::select()
        ->where('status',1)
        ->get();
        
        foreach($mainmenu as $cat)
        {
            $category_id = $cat->id;
            $category_product = Products::select()
            ->where('status',1)
            ->where('category_id',$category_id)
            ->orderBy('id','desc')
            ->skip(0)->take(10)
            ->get();
            $catpdata['id'] = $cat['id'];
            $catpdata['slug'] = $cat['slug'];
            $catpdata['category'] = $cat['category'];
            $catpdata['category_product'] = $category_product;
            $cateproductdata[] = $catpdata;
        }

        foreach($category as $cat)
        {
            $subcategory = Subcategory::select('id','subcategory','slug')
            ->where('status',1)
            ->where('category',$cat->id)
            ->orderBy('id','desc')
            ->get();
            if(count($subcategory) == 0)
            {
                $catdata['is_subcategory'] = 0;
            }
            else
            {
                $catdata['is_subcategory'] = 1;
            }
            $catdata['id'] = $cat['id'];
            $catdata['slug'] = $cat['slug'];
            $catdata['image_icon'] = $cat['image_icon'];
            $catdata['category'] = $cat['category'];
            $catdata['subcategory'] = $subcategory;
            $alldata[] = $catdata;
            
        }
        if(Session::get('user_id'))
        {
            $user_id = Session::get('user_id');
        }
        else
        {
            $user_id = $this->get_client_ip();
        }
        $recentviewed = DB::table('recently_viewed_product')->select('product.*','category.category')
        ->join('product', 'product.id', '=', 'recently_viewed_product.product_id')
        ->join('category', 'category.id', '=', 'product.category_id')
        ->where('recently_viewed_product.user_id',$user_id)
        ->skip(0)->take(10)
        ->orderBy('updated_at','DESC')
        ->get();
        if(count($alldata) == 0)
        {
            $alldata = array();
        }
        return view('index')
        ->with('sliderlist', $slider)
        ->with('latest', $latest_product)
        ->with('best_seller', $best_seller_product)
        ->with('featured', $featured_product)
        ->with('trending', $trending_product)
        ->with('category', $alldata)
        ->with('brand', $brand)
        ->with('cateproductdata',$cateproductdata)
        ->with('deal_of_the_week',$deal_of_the_week)
        ->with('recentviewed',$recentviewed)
        ->with('banner', $banner);
        
    }
    public function index()
    {
        $testimonial = DB::table('tbl_testimonial')->select('tbl_testimonial.*')
        ->get();
        $company_document = DB::table('tbl_company_document')->select('tbl_company_document.*')
        ->get();
        $videodata = DB::table('tbl_video')->limit(3)->get();
        
        $users = DB::table('users')->count();
        $brands = DB::table('brand')->count();
        $vendors = DB::table('vendor')->count();
        return view('home')->with('testimonial', $testimonial)
        ->with('users', $users)->with('brands', $brands)
        ->with('vendors', $vendors)
        ->with('videos', $videodata)
        ->with('company_document', $company_document);
    }
    public function get_client_ip() 
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
           $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    public static function getMenu()
    {
        return Category::select()
        ->where('status',1)
        ->where('show_in_menu',1)
        ->get();
    }
    public static function allCategory()
    {
        $category = Category::select('id','category','slug')
        ->where('status',1)
        ->orderBy('sequence','asc')
        ->get();
        $alldata = array();
        foreach($category as $cat)
        {
            $subcategory = Subcategory::select('id','subcategory','slug')
            ->where('status',1)
            ->where('category',$cat->id)
            ->orderBy('id','desc')
            ->get();
            if(count($subcategory) == 0)
            {
                $catdata['is_subcategory'] = 0;
            }
            else
            {
                $catdata['is_subcategory'] = 1;
            }
            $catdata['id'] = $cat['id'];
            $catdata['slug'] = $cat['slug'];
            $catdata['image_icon'] = $cat['image_icon'];
            $catdata['category'] = $cat['category'];
            $catdata['subcategory'] = $subcategory;
            $alldata[] = $catdata;
        }
        return $alldata;
    }
    public static function getSideMenu()
    {
        $category = Category::select('id','category','slug')
        ->where('status',1)
        ->orderBy('id','desc')
        ->get();
        
        foreach($category as $cat)
        {
            $subcategory = Subcategory::select('id','subcategory','slug')
            ->where('status',1)
            ->where('category',$cat->id)
            ->orderBy('id','desc')
            ->get();
            if(count($subcategory) == 0)
            {
                $catdata['is_subcategory'] = 0;
            }
            else
            {
                $catdata['is_subcategory'] = 1;
            }
            $catdata['id'] = $cat['id'];
            $catdata['slug'] = $cat['slug'];
            $catdata['category'] = $cat['category'];
            $catdata['subcategory'] = $subcategory;
            $alldata[] = $catdata;
            
        }
        return $alldata;
    }
    public function setPincodeSession(Request $request)
    {
        $data = $request->input();
        $pincode = $data['pincode'];
        Session::put('pincode',$pincode);
    }
    public function getVendorIdByPincode($pincode)
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
    public function achivers_list()
    {
        $achivers_list = DB::table('tbl_achivers')->select('tbl_achivers.*','users.fname','users.image')
        ->join('users', 'users.id', '=', 'tbl_achivers.user_id')
        ->where('tbl_achivers.status',1)
        ->get();
        return view('achivers_list')->with('achivers_list',$achivers_list);
    }
    public function achivers_detail(Request $request)
    {
        $id = $request->id;
        $achivers_detail = DB::table('tbl_achivers')->select('tbl_achivers.*','users.fname','users.image')
        ->join('users', 'users.id', '=', 'tbl_achivers.user_id')
        ->where('tbl_achivers.id',$id)
        ->get();
        return view('achivers_detail')->with('achivers_detail',$achivers_detail);
    }
    public function send_mail()
    {
        $data = array('name'=>"Virat Gandhi");
       Mail::send('mail', $data, function($message) {
         $message->to('er.sachingaun@gmail.com', 'Tutorials Point')->subject
            ('Laravel HTML Testing Mail');
      });
      echo "HTML Email Sent. Check your inbox.";
    }
    public function save_newsletter(Request $request)
    {
        $data = $request->input();
        $email = $data['email'];
        $checkemail = DB::table('tbl_newsletter')
        ->where('email',$email)
        ->get();
        if(count($checkemail) > 0)
        {
            echo 'already_exist';
        }
        else
        {
            $save = DB::table('tbl_newsletter')->insert(
                [
                    'email' => $email
                ]
            );
            if($save)
            {
                echo 'saved';
            }
            else
            {
                echo 'error';
            }
        }
    }
    public function royality_income_plan()
    {
        $business_plan = DB::table('tbl_business_plan')->select()->where('type',0)
        ->get();
        return view('income_plan')->with('business_plan',$business_plan);
    }
    public function investment_plan()
    {
        $business_plan = DB::table('tbl_business_plan')->select()->where('type',1)
        ->get();
        return view('investment_plan')->with('business_plan',$business_plan);
    }
}
