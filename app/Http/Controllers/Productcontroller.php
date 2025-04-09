<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductImage;
use App\Models\Products;
use App\Models\Brands;
use App\Models\Cart;
use App\Models\Wishlist;
use App\Models\Undersubcategory;
use App\Models\User;
use DB;
use Session;

class Productcontroller extends Controller
{
    public function productList(Request $request)
    {
        
        $segment2 = $request->segment(2);
        // $vendor_id = $this->getVendorIdByPincode();
        if($request->segment(3) && $request->segment(2) != 'search')
        {
            
            $segment3 = $request->segment(3);
            $brand = Products::groupBy('brand.id')->groupBy('brand.brand_name')->select('brand.id','brand.brand_name')
            ->join('brand','brand.id','=','product.brand_id')
            ->join('category','category.id','=','product.category_id')
            ->join('subcategory','subcategory.id','=','product.subcategory_id')
            ->where('product.status',1)
            ->where('category.slug',$segment2)
            ->where('subcategory.slug',$segment3)
            ->get();

            $undersubcategory = Products::groupBy('undersubcategory.id')->groupBy('undersubcategory.undersubcategory')->select('undersubcategory.id','undersubcategory.undersubcategory')
            ->join('undersubcategory','undersubcategory.id','=','product.sub_subcategory_id')
            ->join('category','category.id','=','product.category_id')
            ->join('subcategory','subcategory.id','=','product.subcategory_id')
            ->where('product.status',1)
            ->where('category.slug',$segment2)
            ->where('subcategory.slug',$segment3)
            ->get();
        }
        elseif($request->segment(2) == 'search')
        {
            $segment3 = $request->segment(3);
            $brand = Products::groupBy('brand.id')->groupBy('brand.brand_name')->select('brand.id','brand.brand_name')
            ->join('brand','brand.id','=','product.brand_id')
            ->where('product.status',1)
            ->where('product.product_name', 'like', '%' . $segment3 . '%')
            ->get();
            $undersubcategory = Products::groupBy('undersubcategory.id')->groupBy('undersubcategory.undersubcategory')->select('undersubcategory.id','undersubcategory.undersubcategory')
            ->join('undersubcategory','undersubcategory.id','=','product.sub_subcategory_id')
            ->where('product.status',1)
            ->where('product.product_name', 'like', '%' . $segment3 . '%')
            ->get();
        }
        else
        {
            $brand = Products::groupBy('brand.id')->groupBy('brand.brand_name')->select('brand.id','brand.brand_name')
            ->join('brand','brand.id','=','product.brand_id')
            ->join('category','category.id','=','product.category_id')
            ->where('product.status',1)
            ->where('category.slug',$segment2)
            ->get();
            $undersubcategory = Products::groupBy('undersubcategory.id')->groupBy('undersubcategory.undersubcategory')->select('undersubcategory.id','undersubcategory.undersubcategory')
            ->join('undersubcategory','undersubcategory.id','=','product.sub_subcategory_id')
            ->join('category','category.id','=','product.category_id')
            ->where('product.status',1)
            ->where('category.slug',$segment2)
            ->get();
        }
        // print_r($undersubcategory);
        return view('product_list')->with('brand',$brand)->with('undersubcategory',$undersubcategory);
    }
    public function getCategoryProduct(Request $request)
    {
        $data = $request->input();
        $cate_slug = $data['cate_slug'];
        // $vendor_id = $this->getVendorIdByPincode();
        $query = Products::select('product.*','category.slug as cat_slug','category.category')
        ->join('category', 'category.id', '=', 'product.category_id')
        ->where('product.status',1)
        ->where('category.slug',$cate_slug);
        return $query->orderBy('product.id','desc')->get();
    }
    public function getProduct(Request $request)
    {
        $data = $request->input();
        $cate_slug = $data['cate_slug'];
        $subcat_slug = $data['subcat_slug'];
        $sortby = $data['sortby'];
        $brandArr = json_decode($data['brand_id']);
        $underSubCatArr = json_decode($data['under_sub_cat_id']);
        if($cate_slug == 'search')
        {
            $query = Products::select('product.*','category.slug as cat_slug')
                ->join('category','category.id','=','product.category_id')
                ->where('product.status',1)
                ->where('product.product_name', 'like', '%' . $subcat_slug . '%');
                if(count($brandArr) > 0)
                {
                    $query = $query->whereIn('product.brand_id',$brandArr);
                }
                if(count($underSubCatArr) > 0)
                {
                    $query = $query->whereIn('product.sub_subcategory_id',$underSubCatArr);
                }
                if($sortby == 1)
                {
                    return $query->orderBy('product.product_name','asc')->get();
                }
                elseif($sortby == 2)
                {
                    return $query->orderBy('product.offer_price','asc')->get();
                }
                elseif($sortby == 3)
                {
                    return $query->orderBy('product.offer_price','desc')->get();
                }
                else
                {
                    return $query->orderBy('product.id','desc')->get();
                }
            
        }
        else
        {
            if($subcat_slug == '')
            {
                $query = Products::select('product.*','category.slug as cat_slug')
                ->join('category', 'category.id', '=', 'product.category_id')
                ->where('product.status',1)
                ->where('category.slug',$cate_slug);
                if(count($brandArr) > 0)
                {
                    $query = $query->whereIn('product.brand_id',$brandArr);
                }
                if(count($underSubCatArr) > 0)
                {
                    $query = $query->whereIn('product.sub_subcategory_id',$underSubCatArr);
                }
                if($sortby == 1)
                {
                    return $query->orderBy('product.product_name','asc')->get();
                }
                elseif($sortby == 2)
                {
                    return $query->orderBy('product.offer_price','asc')->get();
                }
                elseif($sortby == 3)
                {
                    return $query->orderBy('product.offer_price','desc')->get();
                }
                else
                {
                    return $query->orderBy('product.id','desc')->get();
                }
            }
            else
            {
                $query = Products::select('product.*','category.slug as cat_slug')
                ->join('category', 'category.id', '=', 'product.category_id')
                ->join('subcategory', 'subcategory.id', '=', 'product.subcategory_id')
                ->where('product.status',1)
                ->where('category.slug',$cate_slug)
                ->where('subcategory.slug',$subcat_slug);
                if(count($brandArr) > 0)
                {
                    $query = $query->whereIn('product.brand_id',$brandArr);
                }
                if(count($underSubCatArr) > 0)
                {
                    $query = $query->whereIn('product.sub_subcategory_id',$underSubCatArr);
                }
                if($sortby == 1)
                {
                    return $query->orderBy('product.product_name','asc')->get();
                }
                elseif($sortby == 2)
                {
                    return $query->orderBy('product.offer_price','asc')->get();
                }
                elseif($sortby == 3)
                {
                    return $query->orderBy('product.offer_price','desc')->get();
                }
                else
                {
                    return $query->orderBy('product.id','desc')->get();
                }
            }
        }

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
    public function getProductDetail(Request $request)
    {
        $slug = $request->segment(2);
        $productdetail = Products::select('product.*','category.category','subcategory.subcategory','brand.brand_name','category.slug as cat_slug','subcategory.slug as sub_cat_slug')
        ->join('brand', 'brand.id', '=', 'product.brand_id')
        ->join('category', 'category.id', '=', 'product.category_id')
        ->join('subcategory', 'subcategory.id', '=', 'product.subcategory_id')
        ->where('product.status',1)
        ->where(function($q) use ($slug){
          $q->where('product.slug', $slug)
            ->orWhere('product.id', $slug);
        })
        ->get();
        
        foreach($productdetail as $pd)
        {
            $subcategory_id = $pd->subcategory_id;
            $category = $pd->category_id;
            $offer_price = $pd->offer_price;
            $pid = $pd->id;
        }

        $productimage = ProductImage::select('product_image.image','product_image.id')
        ->where('product_id',$pid)
        ->get();

        $specification = DB::table('tblproductspecification')
        ->where('product_id',$pid)
        ->get();
        
        
        $variaty = DB::table('tblproductvariant')->select('variant_value1')->groupBy('variant_value1')
        ->where('product_id',$pid)
        ->get();
        
        $review = DB::table('product_review')->select('product_review.reviews','product_review.rating','users.fname','users.lname','users.image','product_review.created_at')
        ->join('users', 'users.id', '=', 'product_review.user_id')
        ->where('product_review.product_id',$pid)
        ->take(10)
        ->get();
        
        $similer = Products::select('product.*','category.category')
        ->join('category', 'category.id', '=', 'product.category_id')
        ->where('product.status',1)
        ->where('product.subcategory_id',$subcategory_id)
        ->where('product.id','!=',$pid)
        ->orderBy(DB::raw('RAND()'))
        ->take(10)
        ->get();
        
        $percent_amount = (10 / 100) * $offer_price;
        $plus_percent_amount = $offer_price+$percent_amount;
        $minus_percent_amount = $offer_price-$percent_amount;
        
        $compare_product = Products::select('product.*','category.category')
        ->join('category', 'category.id', '=', 'product.category_id')
        ->where('product.status',1)
        ->where('product.offer_price','>=',$minus_percent_amount)
        ->where('product.offer_price','>=',$plus_percent_amount)
        ->where('product.subcategory_id',$subcategory_id)
        // ->where('product.category_id' ,$category)
        ->where('product.id','!=',$pid)
        ->orderBy(DB::raw('RAND()'))
        ->take(10)
        ->get();
        
        if(Session::get('user_id'))
        {
            $user_id = Session::get('user_id');
        }
        else
        {
            $user_id = $this->get_client_ip();
        }
        
        $recentviewed = DB::table('recently_viewed_product')->select('*')
        ->where('user_id',$user_id)
        ->where('product_id',$pid)
        ->orderBy(DB::raw('RAND()'))
        ->take(10)
        ->get();
        
        if(count($recentviewed) == 0)
        {
            DB::table('recently_viewed_product')->insert(
                [
                    'product_id' => $pid,
                    'user_id' => $user_id,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        else
        {
            DB::table('recently_viewed_product')
                ->where('product_id', $pid)
                ->where('user_id', $user_id)
                ->update(
                    [
                        'product_id' => $pid,
                        'user_id' => $user_id,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                );
        }
        
        
        return view('product_detail')
        ->with('product_detail',$productdetail)
        ->with('specification',$specification)
        ->with('similer',$similer)
        ->with('compare_product',$compare_product)
        ->with('review',$review)
        ->with('variaty',$variaty)
        ->with('product_image',$productimage);
    }
    public function addToCart(Request $request)
    {
        $data = $request->input();
        $user_type = Session::get('user_type');
        if(Session::get('user_id'))
        {
            $qty = $data['qty'];
            $product_id = $data['product_id'];
            $tb_type = $data['tb_type'];
            $cart_variant1 = $data['cart_variant1'];
            $cart_variant2 = $data['cart_variant2'];
            $user_id = Session::get('user_id');
            if($tb_type == 0)
            {
                $productdetail = Products::select('offer_price','price','stock')
                ->where('id',$product_id)
                ->get();
            }
            else
            {
                $productdetail = DB::table('tblproductvariant')->select('offer_price','price','stock')
                ->where('product_id',$product_id)
                ->where('variant_value1',$cart_variant1)
                ->where('variant_value2',$cart_variant2)
                ->get();
            }
                
            foreach($productdetail as $p)
            {
                $price = $p->price;
                $offer_price = $p->offer_price;
                $stock = $p->stock;
            }
            $carts = Cart::select('cart.id','cart.qty')
            ->where('cart.product_id',$product_id)
            ->where('cart.user_id',$user_id)
            ->get();
            
                if(count($carts) > 0)
                {
                    if($qty > 0)
                    {
                        $qty = $qty;
                    }
                    else
                    {
                        $qty = DB::raw('qty'.'+'. $qty);
                    }
                    
                    $updatecart = DB::table('cart')
                    ->where('product_id', $product_id)
                    ->where('user_id', $user_id)
                    ->update(
                        [
                            'qty' => $qty,
                            'price' => $price,
                            'offer_price' => $offer_price,
                            'variant_value1' => $cart_variant1,
                            'variant_value2' => $cart_variant2,
                            'updated_at'=>date('Y-m-d h:i:s')
                        ]
                    );
                    if($updatecart)
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'update'
                        );
                    }
                    else
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'error'
                        );
                    }
                }
                else
                {
                   
                    $savecart = DB::table('cart')->insert(
                        [
                            'product_id' => $data['product_id'],
                            'user_id' => $user_id,
                            'price' => $price,
                            'offer_price' => $offer_price,
                            'variant_value1' => $cart_variant1,
                            'variant_value2' => $cart_variant2,
                            'qty' => $qty,
                        ]
                    );
                    if($savecart)
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'saved'
                        );
                    }
                    else
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'error'
                        );
                    }
                }
            }
        else
        {
            $current_link = $_SERVER['HTTP_REFERER'];
            Session::put('redirect_to', $current_link);
            $retdata = array(
                'status'=>true,
                'type'=>'auth'
                );
        }
        return $retdata;
    }
    public function thumbnailAddToCart(Request $request)
    {
        $data = $request->input();
        if(Session::get('user_id'))
        {
            $qty = 1;
            $product_id = $data['product_id'];
            $tb_type = $data['tb_type'];
            $user_id = Session::get('user_id');
            if($tb_type == 0)
            {
                $productdetail = Products::select('offer_price','price','stock','variant_name1','variant_value1','variant_name2','variant_value2')
                ->where('id',$product_id)
                ->get();
            }
                
            foreach($productdetail as $p)
            {
                $price = $p->price;
                $offer_price = $p->offer_price;
                $stock = $p->stock;
                $stock = $p->stock;
                $cart_variant1 = $p->variant_value1;
                $cart_variant2 = $p->variant_value2;
            }
            $carts = Cart::select('cart.id','cart.qty')
            ->where('cart.product_id',$product_id)
            ->where('cart.user_id',$user_id)
            ->get();
            
                if(count($carts) > 0)
                {
                    if($qty > 0)
                    {
                        $qty = $qty;
                    }
                    else
                    {
                        $qty = DB::raw('qty'.'+'. $qty);
                    }
                    
                    $updatecart = DB::table('cart')
                    ->where('product_id', $product_id)
                    ->where('user_id', $user_id)
                    ->update(
                        [
                            'qty' => $qty,
                            'price' => $price,
                            'offer_price' => $offer_price,
                            'variant_value1' => $cart_variant1,
                            'variant_value2' => $cart_variant2,
                            'updated_at'=>date('Y-m-d h:i:s')
                        ]
                    );
                    if($updatecart)
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'update'
                        );
                    }
                    else
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'error'
                        );
                    }
                }
                else
                {
                   
                    $savecart = DB::table('cart')->insert(
                        [
                            'product_id' => $data['product_id'],
                            'user_id' => $user_id,
                            'price' => $price,
                            'offer_price' => $offer_price,
                            'variant_value1' => $cart_variant1,
                            'variant_value2' => $cart_variant2,
                            'qty' => $qty,
                        ]
                    );
                    if($savecart)
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'saved'
                        );
                    }
                    else
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'error'
                        );
                    }
                }
            }
        else
        {
            $current_link = $_SERVER['HTTP_REFERER'];
            Session::put('redirect_to', $current_link);
            $retdata = array(
                'status'=>true,
                'type'=>'auth'
                );
        }
        return $retdata;
    }
    public function addToShopingList(Request $request)
    {
        $data = $request->input();
        $user_type = Session::get('user_type');
        if(Session::get('user_id'))
        {
            $qty = $data['qty'];
            $product_id = $data['product_id'];
            $tb_type = $data['tb_type'];
            $cart_variant1 = $data['cart_variant1'];
            $cart_variant2 = $data['cart_variant2'];
            $user_id = Session::get('user_id');
            if($tb_type == 0)
            {
                $productdetail = Products::select('offer_price','price','stock')
                ->where('id',$product_id)
                ->get();
            }
            else
            {
                $productdetail = DB::table('tblproductvariant')->select('offer_price','price','stock')
                ->where('product_id',$product_id)
                ->where('variant_value1',$cart_variant1)
                ->where('variant_value2',$cart_variant2)
                ->get();
            }
                
            foreach($productdetail as $p)
            {
                $price = $p->price;
                $offer_price = $p->offer_price;
                $stock = $p->stock;
            }
            $carts = DB::table('tbl_shoping_list')->select('id','qty')
            ->where('product_id',$product_id)
            ->where('user_id',$user_id)
            ->get();
            
                if(count($carts) > 0)
                {
                    if($qty > 0)
                    {
                        $qty = $qty;
                    }
                    else
                    {
                        $qty = DB::raw('qty'.'+'. $qty);
                    }
                    
                    $updatecart = DB::table('tbl_shoping_list')
                    ->where('product_id', $product_id)
                    ->where('user_id', $user_id)
                    ->update(
                        [
                            'qty' => $qty,
                            'price' => $price,
                            'offer_price' => $offer_price,
                            'variant_value1' => $cart_variant1,
                            'variant_value2' => $cart_variant2,
                            'updated_at'=>date('Y-m-d h:i:s')
                        ]
                    );
                    if($updatecart)
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'update'
                        );
                    }
                    else
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'error'
                        );
                    }
                }
                else
                {
                   
                    $savecart = DB::table('tbl_shoping_list')->insert(
                        [
                            'product_id' => $data['product_id'],
                            'user_id' => $user_id,
                            'price' => $price,
                            'offer_price' => $offer_price,
                            'variant_value1' => $cart_variant1,
                            'variant_value2' => $cart_variant2,
                            'qty' => $qty,
                        ]
                    );
                    if($savecart)
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'saved'
                        );
                    }
                    else
                    {
                        $retdata = array(
                        'status'=>true,
                        'type'=>'error'
                        );
                    }
                }
            }
        else
        {
            $current_link = $_SERVER['HTTP_REFERER'];
            Session::put('redirect_to', $current_link);
            $retdata = array(
                'status'=>true,
                'type'=>'auth'
                );
        }
        return $retdata;
    }
    public function addToWishlist(Request $request)
    {
        $data = $request->input();
        if(Session::get('user_id'))
        {

            $product_id = $data['product_id'];
            $user_id = Session::get('user_id');
            $wishlist = Wishlist::select('id')
            ->where('product_id',$product_id)
            ->where('user_id',$user_id)
            ->get();
            
            if(count($wishlist) > 0)
            {

                $deletewishlist = DB::table('wishlist')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->delete();
                if($deletewishlist)
                {
                    echo 'removed';
                }
                else
                {
                    echo 'Error';
                }
            }
            else
            {
               
                $savewishlist = DB::table('wishlist')->insert(
                    [
                     'product_id' => $data['product_id'],
                     'user_id' => $user_id
                    ]
                );
                if($savewishlist)
                {
                    echo 'Saved';
                }
                else
                {
                    echo 'Error';
                }
            }
        }
        else
        {
            $current_link = $_SERVER['HTTP_REFERER'];
            Session::put('redirect_to', $current_link);
            echo 'auth';
        }
    }
    public function productByBrand(Request $request)
    {
            $segment2 = $request->segment(2);
            $category = Products::groupBy('category.id')->select('category.id','category.category')
            ->join('brand','brand.id','=','product.brand_id')
            ->join('category','category.id','=','product.category_id')
            ->where('product.status',1)
            ->where('brand.slug',$segment2)
            ->get();
            
            $brand_slider = DB::table('brand_image')->select('brand_image.image')
            ->join('brand', 'brand.id', '=', 'brand_image.brand_id')
            ->where('brand.slug',$segment2)
            ->get();
            
            $undersubcategory = Products::groupBy('undersubcategory.id')->groupBy('undersubcategory.undersubcategory')->select('undersubcategory.id','undersubcategory.undersubcategory')
            ->join('undersubcategory','undersubcategory.id','=','product.sub_subcategory_id')
            ->join('brand','brand.id','=','product.brand_id')
            ->where('product.status',1)
            ->where('brand.slug',$segment2)
            ->get();
            
            $brand = DB::table('brand')
            ->where('brand.slug',$segment2)
            ->get();

        return view('brand_product')->with('category',$category)->with('brandslider',$brand_slider)->with('brand',$brand);
    }
    public function getBrandProduct(Request $request)
    {
        $data = $request->input();
        $catArr = json_decode($data['category_id']);
        $brand_slug = $data['brand_slug'];
        $sortby = $data['sortby'];
        $query = Products::select('product.*','category.slug as cat_slug')
        ->join('brand', 'brand.id', '=', 'product.brand_id')
        ->join('category','category.id','=','product.category_id')
        ->where('product.status',1)
        ->where('brand.slug',$brand_slug);
        if(count($catArr) > 0)
        {
            $query = $query->whereIn('product.category_id',$catArr);
        }
        if($sortby == 1)
        {
            return $query->orderBy('product.product_name','asc')->get();
        }
        elseif($sortby == 2)
        {
            return $query->orderBy('product.offer_price','asc')->get();
        }
        elseif($sortby == 3)
        {
            return $query->orderBy('product.offer_price','desc')->get();
        }
        else
        {
            return $query->orderBy('product.id','desc')->get();
        }
    }
    public function allBrand(Request $request)
    {
        $brand = DB::table('brand')
            ->where('brand.status',1)
            ->get();
        return view('brands')->with('brand',$brand);
    }
    public function getSecondVariant(Request $request)
    {
        $data = $request->input();
        $type = $data['type'];
        $varval1 = $data['varval1'];
        $product_id = $data['product_id'];
        if($type == 0)
        {
            return DB::table('product')->select('variant_value2','price','offer_price','stock')
            ->where('variant_value1',$varval1)
            ->where('id',$product_id)
            ->get();
        }
        else
        {
            return DB::table('tblproductvariant')->groupBy('id')->select('variant_value2','offer_price','price','stock')
            ->where('variant_value1',$varval1)
            ->where('product_id',$product_id)
            ->get();
        }

    }
    public function getPriceByBothVariant(Request $request)
    {
        $data = $request->input();
        $type = $data['type'];
        $varval1 = $data['varval1'];
        $varval2 = $data['varval2'];
        $product_id = $data['product_id'];
        if($type == 0)
        {
            return DB::table('product')->select('variant_value2','price','offer_price','stock')
            ->where('id',$product_id)
            ->get();
        }
        else
        {
            return DB::table('tblproductvariant')->groupBy('id')->select('variant_value2','offer_price','price','stock')
            ->where('variant_value1',$varval1)
            ->where('variant_value2',$varval2)
            ->where('product_id',$product_id)
            ->get();
        }

    }
    public function updateCart(Request $request)
    {
        $data = $request->input();
        if(Session::get('user_id'))
        {
            $user_id = Session::get('user_id');
            $product_id = $data['product_id'];
            $qty = $data['qty'];
            $updatecart = DB::table('cart')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->update(
                    [
                        'qty' => $qty,
                        'updated_at'=>date('Y-m-d')
                    ]
                );
                if($updatecart)
                {
                    echo 'Saved';
                }
                else
                {
                    echo 'Error';
                }
            }
            else
            {
                echo 'auth';
            }
    }
    public function getVendorIdByPincode()
    {
        if(Session::get('user_id'))
        {
            $user_id = Session::get('user_id');
            $user_detail = User::select('assigned_store')
            ->where('id',$user_id)
            ->get();
            foreach($user_detail as $ud)
            {
                $assigned_store = $ud['assigned_store'];
            }
            if($assigned_store == 0)
            {
                $pincode = Session::get('pincode');
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
            }
            else
            {
                $vendor_id = $assigned_store;
            }
        }
        else
        {
            $pincode = Session::get('pincode');
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
        }
        return $vendor_id;
    }
    public function saveProductReview(Request $request)
    {
        $data = $request->input();
        $product_id = $data['product_id'];
        $slug = $data['slug'];
        $message = $data['reviews'];
        if(Session::get('user_id'))
        {
            $user_id = Session::get('user_id');
            $getreview = DB::table('product_review')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
                
            $checkpurchase = DB::table('order_product')
                ->where('product_id', $product_id)
                ->where('user_id', $user_id)
                ->get();
                
                if(count($getreview) > 0)
                {
                    return  redirect('product-detail/'.$slug)->with('error', 'You already reviewed this product !');
                }
                elseif(count($checkpurchase) == 0)
                {
                    return  redirect('product-detail/'.$slug)->with('error', 'You did not purchase this product !');
                }
                else
                {
                    $savewishlist = DB::table('product_review')->insert(
                        [
                         'product_id' => $data['product_id'],
                         'rating' => $data['rating'],
                         'reviews' => $data['reviews'],
                         'user_id' => $user_id
                        ]
                    );
                    if($savewishlist)
                    {
                        echo 'Saved';
                    }
                    else
                    {
                        echo 'Error';
                    }
                    return  redirect('product-detail/'.$slug)->with('success', 'Review sent !');
                }
            }
            else
            {
                return  redirect('product-detail/'.$slug)->with('error', 'Login required !');
            }
    }
    public function ourProductList()
    {
        $product = db::table('tbl_our_product')->select()
        ->get();
        return view('our_product')->with('productlist', $product);
    }
    
}
