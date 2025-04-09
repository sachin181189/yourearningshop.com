<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\menu;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Slider;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Brands;
use App\Models\Products;
use App\Models\User;
use App\Models\company_profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Homecontroller extends Controller
{
    public function index()
    {
        return csrf_token(); 
        die();
    }
    public function getMenus(Request $request)
    {
        $data = $request->input();
        $user_id = $data['user_id'];
        $menus = menu::select()
        ->where('status',1)
        ->get();
        if($user_id != 0)
        {
            $cartcount = DB::table('cart')
            ->where('user_id', $user_id)
            ->count();
        }
        else
        {
            $cartcount = 0;
        }

        if(count($menus) > 0)
        {
            foreach($menus as $m)
            {
                $mdata['id'] = $m['id'];
                $mdata['name'] = $m['name'];
                $mdata['url'] = $m['url'];
                $menudata[] = $mdata;
            }
            $data = array(
                'status'=>true,
                'data'=>$menudata,
                'cart_count'=>$cartcount

            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'cart_count'=>$cartcount,
                'data'=>array()
            );
        }
        return $data;
        
    }
    public function getBrands(Request $request)
    {
        $data = $request->input();
        $brandList = Brands::select()
        ->where('status',1)
        ->get();

        if(count($brandList) > 0)
        {
            foreach($brandList as $m)
            {
                $bdata['id'] = $m['id'];
                $bdata['brand_name'] = $m['brand_name'];
                $bdata['image'] = url('/public/brand_image').'/'.$m['image'];
                $branddata[] = $bdata;
            }
            $data = array(
                'status'=>true,
                'data'=>$branddata

            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
        
    }
    
    public function getCategory(Request $request)
    {

        $Category = Category::selectRaw('category.*,(Select count(product_name) from product where category.id=product.category_id) as total_item')
        ->where('status',1)
        ->get();

        if(count($Category) > 0)
        {
            foreach($Category as $m)
            {
                $Subategory = Subcategory::selectRaw('subcategory.id,subcategory.subcategory,subcategory.image_icon,(Select count(product_name) from product where subcategory.id=product.subcategory_id) as total_item')
                ->where('status',1)
                ->where('category',$m['id'])
                ->get();
                $subcatt=[];
                foreach($Subategory as $sc)
                {
                    $subcatdata['id'] = $sc['id'];
                    $subcatdata['subcategory'] = $sc['subcategory'];
                    $subcatdata['image_icon'] = url('/public/sub_category_image').'/'.$sc['image_icon'];
                    // $subcatdata['total_item'] = $sc['total_item'];
                    $subcatt[] = $subcatdata;
                }
                $subcatArr = array(
                    'category'=>$m['category'],
                    'image_icon'=>url('/public/category_image').'/'.$m['image_icon'],
                    'id'=>$m['id'],
                    'total_item'=>$m['total_item'],
                    'subcategory'=>$subcatt
                    );
                $catdata[] = $subcatArr;
            }
            $data = array(
                'status'=>true,
                'data'=>$catdata
            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
    }
    
    public function getFeaturedCategory()
    {

        $Category = Category::selectRaw('category.*,(Select count(product_name) from product where category.id=product.category_id) as total_item')
        ->where('status',1)
        ->where('is_featured',1)
        ->get();

        if(count($Category) > 0)
        {
            foreach($Category as $m)
            {
                $catArr = array(
                    'category'=>$m['category'],
                    'image_icon'=>url('/public/category_image').'/'.$m['image_icon'],
                    'id'=>$m['id'],
                    'total_item'=>$m['total_item'],
                    );
                $catdata[] = $catArr;
            }
            $data = array(
                'status'=>true,
                'data'=>$catdata
            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
    }
    
    public function getSubCategory(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'category_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $catId = $data['category_id'];
        
        $SubCategory = SubCategory::selectRaw('subcategory.*,(Select count(product_name) from product where subcategory.id=product.subcategory_id) as total_item')
        ->where('status',1)
        ->where('category',$catId)
        ->get();

        if(count($SubCategory) > 0)
        {
            foreach($SubCategory as $m)
            {
                $subcatArr = array(
                    'subcategory'=>$m['subcategory'],
                    'image_icon'=>url('/public/sub_category_image').'/'.$m['image_icon'],
                    'id'=>$m['id'],
                    'total_item'=>$m['total_item'],
                    );
                $catdata[] = $subcatArr;
            }
            $data = array(
                'status'=>true,
                'data'=>$catdata
            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
    }

    public function getSlider()
    {
        $Slider = Slider::select()
        ->where('status',1)
        ->get();
        if(count($Slider) > 0)
        {
            foreach($Slider as $m)
            {
                $slider = array(
                    'title'=>$m['title'],
                    'slider_image'=>url('/public/slider_image').'/'.$m['slider_image'],
                    'id'=>$m['id']
                    );
                $sliderdata[] = $slider;
            }
            $data = array(
                'status'=>true,
                'data'=>$sliderdata
            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
    }
    public function getBanner()
    {
        $Banner = Banner::select()
        ->where('status',1)
        ->get();
        if(count($Banner) > 0)
        {
            $topbannerdata = array();
            $bottombannerdata = array();
            $centerbannerdata = array();
            foreach($Banner as $m)
            {
                if($m['position'] == 'top')
                {
                    $banner2 = array(
                        'title'=>$m['title'],
                        'position'=>$m['position'],
                        'banner_image'=>url('/public/banner_image').'/'.$m['banner_image'],
                        'id'=>$m['id']
                        );
                    $topbannerdata[] = $banner2;
                }
                if($m['position'] == 'center')
                {
                    $banner3 = array(
                        'title'=>$m['title'],
                        'position'=>$m['position'],
                        'banner_image'=>url('/public/banner_image').'/'.$m['banner_image'],
                        'id'=>$m['id']
                        );
                    $centerbannerdata[] = $banner3;
                }
                if($m['position'] == 'bottom')
                {
                    $banner1 = array(
                        'title'=>$m['title'],
                        'position'=>$m['position'],
                        'banner_image'=>url('/public/banner_image').'/'.$m['banner_image'],
                        'id'=>$m['id']
                        );
                    $bottombannerdata[] = $banner1;
                }
                
            }
            $data = array(
                'status'=>true,
                'topbanner'=>$topbannerdata,
                'centerbanner'=>$centerbannerdata,
                'bottombanner'=>$bottombannerdata
            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
    }
    public function contactUs(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'subject' => 'required',
        'message' => 'required',
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
            foreach($users as $u)
            {
                $fname = $u['fname'];
                $email = $u['email'];
            }
        }
        else
        {
            $rdata = array(
                'status'=>true,
                'msg'=>'Invalid user'
            );
            return  $rdata;
        }
        $savecontactus = DB::table('contactus')->insert(
            [
             'name' => $fname,
             'email' => $email,
             'subject' => $data['subject'],
             'message' => $data['message'],
             'created_at'=>date('Y-m-d H:i:s'),
             'updated_at'=>date('Y-m-d H:i:s')
             ]
        );
        if($savecontactus)
        {
            $rdata = array(
                'status'=>true,
                'msg'=>'Data saved .. We will contact you soon'
            );
            return  $rdata;
        }
        else
        {
            $rdata = array(
                'status'=>false,
                'msg'=>'Something went wrong'
            );
            return  $rdata;
        }
    }
    public function getBlog()
    {
        $Blog = Blog::select()
        ->where('status',1)
        ->get();
        if(count($Blog) > 0)
        {
            foreach($Blog as $m)
            {
                $blog = array(
                    'title'=>$m['title'],
                    'short_description'=>$m['short_description'],
                    'image'=>url('/public/blog_image').'/'.$m['image'],
                    'created_at'=>$newDate = date("Y-m-d", strtotime($m['created_at'])),
                    'id'=>$m['id']
                    );
                $blogdata[] = $blog;
            }
            $data = array(
                'status'=>true,
                'data'=>$blogdata
            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
    }
    public function getBlogDetail(Request $request)
    {
        $data = $request->input();
        $id = $data['id'];
        $Blog = Blog::select()
        ->where('status',1)
        ->where('id',$id)
        ->get();

        $RecentBlog = Blog::select()
        ->where('status',1)
        ->where('id','!=',$id)
        ->get();
        if(count($RecentBlog) > 0)
        {
            foreach($RecentBlog as $rb)
            {
                
                    $rcdata['title'] = $rb['title'];
                    $rcdata['short_description'] = $rb['short_description'];
                    $rcdata['description'] = $rb['description'];
                    $rcdata['created_at'] = date("Y-m-d", strtotime($rb['created_at']));
                    $rcdata['image'] = url('/public/blog_image').'/'.$rb['image'];
                    $rcdata['id'] = $rb['id'];
                    $cdata[] = $rcdata;
                  
            }
        }
        else
        {
            $cdata = array();
        }
        if(count($Blog) > 0)
        {
            foreach($Blog as $m)
            {
                $blog = array(
                    'title'=>$m['title'],
                    'short_description'=>$m['short_description'],
                    'description'=>$m['description'],
                    'created_at'=> date("Y-m-d", strtotime($m['created_at'])),  
                    'image'=>url('/public/blog_image').'/'.$m['image'],
                    'id'=>$m['id']
                    );
            }
            $data = array(
                'status'=>true,
                'data'=>$blog,
                'recent'=>$cdata
            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array(),
                'recent'=>$rblog
            );
        }
        return $data;
    }
    public function subscribe(Request $request)
    {
        $data = $request->input();
        $scount = DB::table('subscribe')
            ->where('email', $data['email'])
            ->count();
        if($scount == 0)
        {
            $savesubscribe = DB::table('subscribe')->insert(
                [
                'email' => $data['email'],
                'created_at'=>date('y-m-d'),
                'updated_at'=>date('y-m-d')
                ]
            );
            if($savesubscribe)
            {
                $rdata = array(
                    'status'=>true,
                    'msg'=>'Subscribe successfully'
                );
                return  $rdata;
            }
            else
            {
                $rdata = array(
                    'status'=>false,
                    'msg'=>'Something went wrong'
                );
                return  $rdata;
            }
        }
        else
        {
            $rdata = array(
                'status'=>false,
                'msg'=>'Already subscribed'
            );
            return  $rdata;
        }
    }
    public function getHomepageProduct()
    {
        $featuredproduct = Products::select('product.id','product.product_name','product.price','product.offer_price',
        'product.color','product.size','product.product_image','category.category','subcategory.subcategory',
        'product.product_description','product.stock','product_size_unit.unit','product.category_id')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->join('product_size_unit', 'product_size_unit.id', '=', 'product.unit')
            ->leftJoin('subcategory', 'subcategory.id', '=', 'product.subcategory_id')
            ->where('product.is_featured', 1)
            ->where('product.status', 1)
            ->get();
        
        $trendingproduct = Products::select('product.id','product.product_name','product.price','product.offer_price',
        'product.color','product.size','product.product_image','category.category','subcategory.subcategory',
        'product.product_description','product.stock','product_size_unit.unit','product.category_id')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->join('product_size_unit', 'product_size_unit.id', '=', 'product.unit')
            ->leftJoin('subcategory', 'subcategory.id', '=', 'product.subcategory_id')
            ->where('product.is_trending', 1)
            ->where('product.status', 1)
            ->get();

            if(count($featuredproduct) > 0)
            {
                foreach($featuredproduct as $u)
                {
                    $udata['id'] = $u['id'];
                    $udata['product_name'] = $u['product_name'];
                    $udata['price'] = number_format($u['price'],2);
                    $udata['offer_price'] = number_format($u['offer_price'],2);
                    $udata['color'] = $u['color'];
                    $udata['size'] = $u['size'];
                    $udata['unit'] = $u['unit'];
                    $udata['category'] = $u['category'];
                    $udata['subcategory'] = $u['subcategory'];
                    $udata['product_description'] = $u['product_description'];
                    $udata['stock'] = $u['stock'];
                    $udata['image'] = url('/public/product_image').'/'.$u['product_image'];
                    $fdata[]=$udata;

                }
            }
            else
            {
                $fdata = array();
            }
            if(count($trendingproduct) > 0)
            {
                foreach($trendingproduct as $u1)
                {
                    $udata1['id'] = $u1['id'];
                    $udata1['product_name'] = $u1['product_name'];
                    $udata1['price'] = number_format($u1['price'],2);
                    $udata1['offer_price'] = number_format($u1['offer_price'],2);
                    $udata1['color'] = $u1['color'];
                    $udata1['size'] = $u1['size'];
                    $udata1['unit'] = $u1['unit'];
                    $udata1['category'] = $u1['category'];
                    $udata1['subcategory'] = $u1['subcategory'];
                    $udata1['product_description'] = $u1['product_description'];
                    $udata1['stock'] = $u1['stock'];
                    $udata1['image'] = url('/public/product_image').'/'.$u1['product_image'];
                    $tfdata[]=$udata1;
                }
            }
            else
            {
                $udata1 = array();
            }
            $data = array(
                'status'=>true,
                'featured'=>$fdata,
                'trending'=>$tfdata
            );
            return  $data;
    }
    public function getFooterData()
    {
        $company_profile = company_profile::select()
        ->get();

        $Category = Category::select()
        ->where('status',1)
        ->skip(0)->take(7)
        ->get();
        
        $data = array(
            'status'=>true,
            'company'=>$company_profile,
            'category'=>$Category
        );
        return  $data;
    }
    public function getBlogComment(Request $request)
    {
        $data = $request->input();
        $blog_id = $data['blog_id'];
        $blogcomment = DB::table('blog_comment')
        ->where('blog_id', $blog_id)
        ->where('status', 1)
        ->get();
        if(count($blogcomment) > 0)
        {
            $data = array(
                'status'=>true,
                'data'=>$blogcomment
            );
            return  $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'No comment yet'
            );
            return  $data;
        }
    }
    public function saveBlogComment(Request $request)
    {
        $data = $request->input();
        $saveblogcomment = DB::table('blog_comment')->insert(
            [
            'name' => $data['name'],
            'email' => $data['email'],
            'blog_id' => $data['blog_id'],
            'comment' => $data['comment']
            ]
        );
        if($saveblogcomment)
        {
            $rdata = array(
                'status'=>true,
                'msg'=>'Comment send successfully'
            );
            return  $rdata;
        }
        else
        {
            $rdata = array(
                'status'=>false,
                'msg'=>'Something went wrong'
            );
            return  $rdata;
        }
    }
    public function getPrivacyPolicy(Request $request)
    {
        $data = $request->input();
        $privacy_policy = DB::table('privacy_policy')
        ->get();
        if(count($privacy_policy) > 0)
        {
            $data = array(
                'status'=>true,
                'data'=>$privacy_policy
            );
            return  $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'No data yet'
            );
            return  $data;
        }
    }
    public function getTermCondition(Request $request)
    {
        $data = $request->input();
        $term_condition = DB::table('term_condition')
        ->get();
        if(count($term_condition) > 0)
        {
            $data = array(
                'status'=>true,
                'data'=>$term_condition
            );
            return  $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'No data yet'
            );
            return  $data;
        }
    }
    public function getAboutUs(Request $request)
    {
        $data = $request->input();
        $aboutus = DB::table('aboutus')
        ->get();
        if(count($aboutus) > 0)
        {
            foreach($aboutus as $ab)
            {
                $abdat['id'] = $ab->id;
                $abdat['banner'] = url('/public/banner_image').'/'.$ab->banner;
                $abdat['content'] = $ab->content;
                $abus[] = $abdat;
            }
            $data = array(
                'status'=>true,
                'data'=>$abus
            );
            return  $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'No data yet'
            );
            return  $data;
        }
    }
    public function getShippingPolicy(Request $request)
    {
        $data = $request->input();
        $shipping_policy = DB::table('shipping_policy')
        ->get();
        if(count($shipping_policy) > 0)
        {
            $data = array(
                'status'=>true,
                'data'=>$shipping_policy
            );
            return  $data;
        }
        else
        {
            $data = array(
                'status'=>false,
                'msg'=>'No data yet'
            );
            return  $data;
        }
    }
    public function getCoupon(Request $request)
    {
        $data = $request->input();
        $couponList = DB::table('coupon')->select()
        ->where('status',1)
        ->get();

        if(count($couponList) > 0)
        {
            foreach($couponList as $m)
            {
                $bdata['id'] = $m->id;
                $bdata['coupon_name'] = $m->coupon_name;
                $bdata['description'] = $m->description;
                $bdata['coupon_code'] = $m->coupon_code;
                $bdata['coupon_type'] = $m->coupon_type;
                $bdata['coupon_val'] = $m->coupon_val;
                $coupondata[] = $bdata;
            }
            $data = array(
                'status'=>true,
                'data'=>$coupondata

            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
        
    }
    public function getNotification(Request $request)
    {
        $data = $request->input();
        $user_id = $data['user_id'];
        $noticeList = DB::table('notification')->select()
        ->where('status',0)
        ->get();

        if(count($noticeList) > 0)
        {
            foreach($noticeList as $m)
            {
                $bdata['id'] = $m->id;
                $bdata['notification'] = $m->notification;
                $bdata['url'] = $m->url;
                $noticedata[] = $bdata;
            }
            $data = array(
                'status'=>true,
                'data'=>$noticedata

            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
        
    }
    public function removeNotification(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'notification_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $notification_id = $data['notification_id'];
        $removenotice = DB::table('notification')->where('id', $notification_id)->delete();
        if($removenotice)
        {
            $data = array(
                'status'=>true,
                'msg'=>'Item removed'
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
    public function getVideo(Request $request)
    {
        $data = $request->input();
        $video_list = DB::table('tbl_video')
        ->where('status', 1)
        ->get();

        if(count($video_list) > 0)
        {
            $data = array(
                'status'=>true,
                'data'=>$video_list

            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
        
    }
    public function document_list(Request $request)
    {
        $data = $request->input();
        $docList = DB::table('tbl_company_document')->select()
        ->where('status',1)
        ->get();

        if(count($docList) > 0)
        {
            foreach($docList as $m)
            {
                $bdata['id'] = $m->id;
                $bdata['document_name'] = $m->document_name;
                $bdata['document'] = url('/public/document').'/'.$m->document;
                $docdata[] = $bdata;
            }
            $data = array(
                'status'=>true,
                'data'=>$docdata

            );
        }
        else
        {
            $data = array(
                'status'=>true,
                'data'=>array()
            );
        }
        return $data;
        
    }
    public function check_app_active()
    {
        $data = array(
                'status'=>true,
                'msg'=>'We facing some issue, try again later.'
            );
        return $data;
    }
}
