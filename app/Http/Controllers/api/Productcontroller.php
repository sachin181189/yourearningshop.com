<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Products;
use App\Models\ProductImage;
use App\Models\Undersubcategory;
use App\Models\Brands;
use App\Models\Cart;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Validator;

class Productcontroller extends Controller
{
    public function getProductList(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'category_id' => 'required',
        'sub_category_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $category_id = $data['category_id'];
        $sub_category_id = $data['sub_category_id'];
        if($sub_category_id == '')
        {
            $productlist = Products::select('product.id','product.product_name','product.price','product.offer_price',
            'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
            ->where('category_id', $category_id)
            ->where('product.status', 1)
            ->get();
        }
        else
        {
            $productlist = Products::select('product.id','product.product_name','product.price','product.offer_price',
            'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
            ->where('category_id', $category_id)
            ->where('subcategory_id', $sub_category_id)
            ->where('product.status', 1)
            ->get();
        }
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
    
    public function productDetail(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'product_id' => 'required',
        'user_id' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $sfdata = array();
        
        $data = $request->input();
        $product_id = $data['product_id'];
        $user_id = $data['user_id'];
        $productlist = Products::select('product.id','product.product_name','product.price','product.offer_price',
        'product.product_image','category.category','subcategory.subcategory',
        'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2',
        'product.product_description','product.stock','product.category_id','brand.brand_name')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->join('brand', 'brand.id', '=', 'product.brand_id')
            ->leftJoin('subcategory', 'subcategory.id', '=', 'product.subcategory_id')
            ->where('product.id', $product_id)
            ->where('product.status', 1)
            ->get();

            $productimage = ProductImage::select('product_image.image','product_image.id')
            ->where('product_id', $product_id)
            ->get();
            
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
            
            $specification = DB::table('tblproductspecification')
                    ->where('product_id', $product_id)
                    ->get();
            if(count($specification) > 0)
            {
                foreach($specification as $spe)
                {
                        $specidata1['specification'] = $spe->specification;
                        $specidata1['speci_value'] = $spe->speci_value;
                        $specidata[] = $specidata1;
                       
                }
            }
            else
            {
                $specidata = array();
            }


            if(count($productimage) > 0)
            {
                foreach($productimage as $pu)
                {
                    $idata['id'] = $pu['id'];
                    $idata['image'] = url('/public/product_image').'/'.$pu['image'];
                    $imfdata[]=$idata;
                }
            }
            else
            {
                $imfdata = array();
            }
            $variaty = DB::table('tblproductvariant')->groupBy('variant_value1')
            ->where('product_id',$product_id)
            ->get();
            if(count($variaty) > 0)
            {
                foreach($variaty as $pv)
                {
                    $vdata['id'] = $pv->id;
                    if($pv->variant_name1 == '')
                     {
                         $vdata['variant_name1'] = '';
                     }
                     else
                     {
                         $vdata['variant_name1'] = $pv->variant_name1;
                     }
                     if($pv->variant_value1 == '')
                     {
                         $vdata['variant_value1'] = '';
                     }
                     else
                     {
                         $vdata['variant_value1'] = $pv->variant_value1;
                     }
                     $vdata['price'] = $pv->price;
                     $vdata['offer_price'] = $pv->offer_price;
                     $vdata['stock'] = $pv->stock;
                     $allvariant[]=$vdata;
                }
                
            }
            else
            {
                $allvariant = array();
            }
            if(count($productlist) > 0)
            {
                foreach($productlist as $u)
                {
                    $similerproduct = Products::select('product.id','product.product_name','product.price','product.offer_price',
                    'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2',
                    'product.product_image')
                    ->where('product.category_id', $u['category_id'])
                    ->where('product.status', 1)
                    ->where('product.id','!=', $u['id'])
                    ->get();

                    foreach($similerproduct as $su)
                    {
                         $sdata['id'] = $su['id'];
                         $sdata['product_name'] = $su['product_name'];
                         $sdata['price'] = number_format($su['price'],2);
                         $sdata['offer_price'] = number_format($su['offer_price'],2);
                         $sdata['discount'] = number_format((($su['price']-$su['offer_price'])*100)/$su['price'],2);
                         if($su['variant_name1'] == '')
                         {
                             $sdata['variant_name1'] = '';
                         }
                         else
                         {
                             $sdata['variant_name1'] = $su['variant_name1'];
                         }
                         if($su['variant_value1'] == '')
                         {
                             $sdata['variant_value1'] = '';
                         }
                         else
                         {
                             $sdata['variant_value1'] = $su['variant_value1'];
                         }
                         if($su['variant_name2'] == '')
                         {
                             $sdata['variant_name2'] = '';
                         }
                         else
                         {
                             $sdata['variant_name2'] = $su['variant_name2'];
                         }
                         if($su['variant_value2'] == '')
                         {
                             $sdata['variant_value2'] = '';
                         }
                         else
                         {
                             $sdata['variant_value2'] = $su['variant_value2'];
                         }
                         $sdata['image'] = url('/public/product_image').'/'.$su['product_image'];
                         $sfdata[]=$sdata;
                    }

                     $udata['id'] = $u['id'];
                     $udata['product_name'] = $u['product_name'];
                     $udata['brand_name'] = $u['brand_name'];
                     $udata['price'] = number_format($u['price'],2);
                     $udata['offer_price'] = number_format($u['offer_price'],2);
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
                     
                     $udata['category'] = $u['category'];
                     $udata['is_wishlist_added'] = $is_wishlist_added;
                     $udata['subcategory'] = $u['subcategory'];
                     $udata['product_description'] = $u['product_description'];
                     $udata['stock'] = $u['stock'];
                     $udata['image'] = url('/public/product_image').'/'.$u['product_image'];
                     
                     
                }
            $product_review = DB::table('product_review AS r')
                       ->select(
                         DB::raw('COUNT(*) AS no_of_reviews'),
                         DB::raw('SUM(rating) AS total_rating'),
                         DB::raw('ROUND(AVG(r.rating)) AS average_rating'))
                       ->join('product AS l', 'l.id', '=', 'r.product_id')
                       ->where('r.product_id',$product_id)
                       ->orderBy('no_of_reviews', 'DESC')
                       ->groupBy('product_id')
                        ->first();
            if($product_review == null)
                {
                    $product_review = array();
                }
                $data = array(
                    'status'=>true,
                    'data'=>$udata,
                    'product_review'=>$product_review,
                    'product_image'=>$imfdata,
                    'similerproduct'=>$sfdata,
                    'variantval1'=>$allvariant,
                    'specification'=>$specidata

                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>true,
                    'data'=>array(),
                    'product_review'=>array(),
                    'variantval1'=>array(),
                    'product_image'=>array(),
                    'similerproduct'=>array(),
                    'specification'=>array()
                );
                return $data;
            }
    }
    
    public function getBestSellerProduct()
    {
    
            $column = 'id';
            $columnval = 'DESC';
    
            $productlist = Products::orderBy($column, $columnval)->select('product.id','product.product_name','product.price','product.offer_price',
            'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
            ->where('product.is_best_seller', 1)
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
    public function getTodayDealProduct()
    {
            $column = 'id';
            $columnval = 'DESC';

        
            $productlist = Products::orderBy($column, $columnval)->select('product.id','product.product_name','product.price','product.offer_price',
            'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
            ->where('product.is_todays_deal', 1)
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
    public function getProductBySubcategory(Request $request)
    {
        $data = $request->input();
        $subcategory_id = $data['subcategory_id'];

            $column = 'id';
            $columnval = 'DESC';
        
            $productlist = Products::orderBy($column, $columnval)->select('product.id','product.product_name','product.price','product.offer_price',
            'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
            ->where('product.subcategory_id', $subcategory_id)
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
    public function getProductByCategory(Request $request)
    {
        $data = $request->input();
        $cat_id = $data['cat_id'];

            $column = 'id';
            $columnval = 'DESC';

            $productlist = Products::orderBy($column, $columnval)->select('product.id','product.product_name','product.price','product.offer_price',
            'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
            ->where('product.category_id', $cat_id)
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
    
    public function getProductBySearch(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'search_key' => 'required'
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        
        $data = $request->input();
        $search_key = $data['search_key'];

        $productlist = Products::orderBy('id', 'DESC')->select('product.id','product.product_name','product.price','product.offer_price',
        'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
        ->where('product.product_name', 'like', '%'.$search_key.'%')
        ->where('product.status', 1)
        ->get();
        if(count($productlist) > 0){
        
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
    public function getFashionProduct(Request $request)
    {
        $data = $request->input();
        $order_by = $data['order_by'];
        if($order_by == 'new')
        {
            $column = 'id';
            $columnval = 'DESC';
        }
        elseif($order_by == 'price_low_to_high')
        {
            $column = 'offer_price';
            $columnval = 'ASC';
        }
        elseif($order_by == 'price_high_to_low')
        {
            $column = 'offer_price';
            $columnval = 'DESC';
        }
        else
        {
            $column = 'id';
            $columnval = 'DESC';
        }
        $undersubcategory = Undersubcategory::select('undersubcategory.id','undersubcategory.undersubcategory')
        ->join('product', 'product.sub_subcategory_id', '=', 'undersubcategory.id')
        ->where('product.category_id', 3)
        ->where('product.status', 1)
        ->groupBy('undersubcategory.id')
        ->groupBy('undersubcategory.undersubcategory')
        ->get();

        $brand = Brands::select('brand.brand_name','brand.id')
        ->join('product', 'product.brand_id', '=', 'brand.id')
        ->where('product.category_id', 3)
        ->where('product.status', 1)
        ->groupBy('brand.id')
        ->groupBy('brand.brand_name')
        ->get();

        $brandIds = $data['brand_id'];
        $subsubcat = $data['categoryId'];

        if(count($brandIds) > 0 && $subsubcat !='')
        {
            $productlist = Products::orderBy($column, $columnval)->select('product.id','product.product_name','product.price','product.offer_price',
            'product.color','product.size','product.product_image')
            ->where('product.category_id', 3)
            ->where('product.status', 1)
            ->whereIn('product.brand_id', $brandIds)
            ->where('product.sub_subcategory_id', $subsubcat)
            ->get();
        }
        elseif(count($brandIds) > 0 && $subsubcat =='')
        {
            $productlist = Products::orderBy($column, $columnval)->select('product.id','product.product_name','product.price','product.offer_price',
            'product.color','product.size','product.product_image')
            ->where('product.category_id', 3)
            ->where('product.status', 1)
            ->whereIn('product.brand_id', $brandIds)
            ->get();
        }
        elseif(count($brandIds) == 0 && $subsubcat !='')
        {
            $productlist = Products::orderBy($column, $columnval)->select('product.id','product.product_name','product.price','product.offer_price',
            'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
            ->where('product.category_id', 3)
            ->where('product.status', 1)
            ->where('product.sub_subcategory_id', $subsubcat)
            ->get();
        }
        else
        {
            $productlist = Products::orderBy($column, $columnval)->select('product.id','product.product_name','product.price','product.offer_price',
            'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
            ->where('product.category_id', 3)
            ->where('product.status', 1)
            ->get();
        }
                foreach($brand as $brd)
                {
                    if (in_array($brd['id'], $brandIds))
                    {
                        $checked = 1;
                    }
                    else
                    {
                        $checked=0;
                    }
                     $budata['id'] = $brd['id'];
                     $budata['brand_name'] = $brd['brand_name'];
                     $budata['checked'] = $checked;
                     $bdata[]=$budata;
                }
                foreach($productlist as $u)
                {
                     $udata['id'] = $u['id'];
                     $udata['product_name'] = $u['product_name'];
                     $udata['price'] = $u['price'];
                     $udata['offer_price'] = $u['offer_price'];
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
                    'data'=>$fdata,
                    'brand'=>$bdata,
                    'undersubcategory'=>$undersubcategory
                );
                return $data;
    }
    
    public function submitReview(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'user_id' => 'required',
        'review' => 'required',
        'product_id' => 'required',
        'rating' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $user_id = $data['user_id'];
        $review = $data['review'];
        $product_id = $data['product_id'];
        $productreview = ProductReview::select()
        ->where('user_id', $user_id)
        ->where('product_id', $product_id)
        ->count();
        if($productreview > 0)
        {
            $data1 = array(
                'status'=>true,
                'msg'=>'You already review this product'
            );
            return $data1;
        }
        else
        {
            $sumitreview = DB::table('product_review')->insert(
                [
                'reviews' => $data['review'],
                'product_id' => $data['product_id'],
                'user_id' => $data['user_id'],
                'rating' => $data['rating']
                ]
            );
            if($sumitreview)
            {
                $data1 = array(
                    'status'=>true,
                    'msg'=>'Review submitted and publish after approvel'
                );
            }
            else
            {
                $data1 = array(
                    'status'=>true,
                    'msg'=>'Something went wrong'
                );
            }
            return $data1;
        }

    }
    public function getProductReview(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'product_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $product_id = $data['product_id'];

        $productreview = ProductReview::select('users.fname','users.lname','product_review.reviews','product_review.created_at','product_review.rating')
        ->join('users', 'users.id', '=', 'product_review.user_id')
        ->where('product_id', $product_id)
        ->get();
        $data = array(
            'status'=>true,
            'data'=>$productreview
        );
        return $data;
    }
    
    public function getCartCount(Request $request)
    {
        $data = $request->input();
        $user_id = $data['user_id'];

        $cartnumber = DB::table('cart')
        ->where('user_id', $user_id)
        ->count();

        $data = array(
            'status'=>true,
            'cart_count'=>$cartnumber
        );
        return $data;
    }
    public function getWishlistCount(Request $request)
    {
        $data = $request->input();
        $user_id = $data['user_id'];

        $wishlistnumber = DB::table('wishlist')
        ->where('user_id', $user_id)
        ->count();

        $data = array(
            'status'=>true,
            'wishlist_count'=>$wishlistnumber
        );
        return $data;
    }
    public function getBrand(Request $request)
    {
        $data = $request->input();
        $brandlist = Brands::select()
        ->where('brand.status',1)
        ->get();
        if(count($brandlist) > 0)
            {
                foreach($brandlist as $u)
                {
                     $udata['id'] = $u['id'];
                     $udata['brand_name'] = $u['brand_name'];
                     $udata['image'] = url('/public/brand_image').'/'.$u['image'];
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
    public function getProductByBrand(Request $request)
    {
        $validator =  Validator::make($request->all(),[
        'brand_id' => 'required',
        ]);
    
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 200);
        }
        $data = $request->input();
        $brand_id = $data['brand_id'];
        
        $brandlist = DB::table('brand_image')->select('brand_image.image','brand.brand_name','brand_image.id','brand.short_description','brand.description')
        ->join('brand', 'brand.id', '=', 'brand_image.brand_id')
        ->where('brand_image.brand_id',$brand_id)
        ->get();
        if(count($brandlist) > 0)
        {
                foreach($brandlist as $b)
                {
                     $bdata['id'] = $b->id;
                     $bdata['brand_image'] = url('/public/brand_slider_image').'/'.$b->image;
                     $brdata[]=$bdata;
                }
                $brand_name = $brandlist[0]->brand_name;
                $short_description = $brandlist[0]->short_description;
                $description = $brandlist[0]->description;
        }
        else
        {
            $brdata = array();
            $brand_name = '';
            $short_description = '';
            $description = '';
        }
        
        $productlist = Products::orderBy('id', 'DESC')->select('product.id','product.product_name','product.price','product.offer_price',
        'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2','product.product_image')
        ->where('brand_id', $brand_id)
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
        }
        else
        {
            $fdata = [];
        }

            $data = array(
                    'status'=>true,
                    'brand_name'=>$brand_name,
                    'data'=>$fdata,
                    'brand_image'=>$brdata,
                    'short_description'=>$short_description,
                    'description'=>$description
                );
                
        return $data;
    }
    public function getAllVariant(Request $request)
    {
        $data = $request->input();
        $product_id = $data['product_id'];
        $user_id = $data['user_id'];
        $productlist = Products::select('product.id','product.product_name','product.price','product.offer_price',
        'product.product_image','category.category','subcategory.subcategory',
        'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2',
        'product.product_description','product.stock','product.category_id','brand.brand_name')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->join('brand', 'brand.id', '=', 'product.brand_id')
            ->leftJoin('subcategory', 'subcategory.id', '=', 'product.subcategory_id')
            ->where('product.id', $product_id)
            ->where('product.status', 1)
            ->get();

            $productimage = ProductImage::select('product_image.image','product_image.id')
            ->where('product_id', $product_id)
            ->get();
            
            $Wishlistcount = Wishlist::select('id')
            ->where('product_id', $product_id)
            ->where('user_id', $user_id)
            ->get();
            
            $product_review = DB::table('product_review AS r')
           ->select(
             DB::raw('COUNT(*) AS no_of_reviews'),
             DB::raw('SUM(rating) AS total_rating'),
             DB::raw('ROUND(AVG(r.rating)) AS average_rating'))
           ->join('product AS l', 'l.id', '=', 'r.product_id')
           ->where('r.product_id',$product_id)
           ->orderBy('no_of_reviews', 'DESC')
           ->groupBy('product_id')
            ->first();
            if($product_review == null)
            {
                $product_review = (object)[];
            }
            
            if(count($Wishlistcount) == 1)
            {
                $is_wishlist_added = 1;
            }
            else
            {
                $is_wishlist_added = 0;
            }
            
            $specification = DB::table('tblproductspecification')
                    ->where('product_id', $product_id)
                    ->get();
            if(count($specification) > 0)
            {
                foreach($specification as $spe)
                {
                        $specidata1['specification'] = $spe->specification;
                        $specidata1['speci_value'] = $spe->speci_value;
                        $specidata[] = $specidata1;
                       
                }
            }
            else
            {
                $specidata = array();
            }


            if(count($productimage) > 0)
            {
                foreach($productimage as $pu)
                {
                    $idata['id'] = $pu['id'];
                    $idata['image'] = url('/public/product_image').'/'.$pu['image'];
                    $imfdata[]=$idata;
                }
            }
            else
            {
                $imfdata = array();
            }
            $variaty = DB::table('tblproductvariant')->groupBy('variant_value1')
            ->where('product_id',$product_id)
            ->get();
            if(count($variaty) > 0)
            {
                foreach($variaty as $pv)
                {
                    $vdata['id'] = $pv->id;
                    if($pv->variant_name1 == '')
                     {
                         $vdata['variant_name1'] = '';
                     }
                     else
                     {
                         $vdata['variant_name1'] = $pv->variant_name1;
                     }
                     if($pv->variant_value1 == '')
                     {
                         $vdata['variant_value1'] = '';
                     }
                     else
                     {
                         $vdata['variant_value1'] = $pv->variant_value1;
                     }
                     $vdata['price'] = $pv->price;
                     $vdata['offer_price'] = $pv->offer_price;
                     $vdata['discount'] = number_format((($pv->price-$pv->offer_price)*100)/$pv->price,2);
                     $vdata['stock'] = $pv->stock;
                     
                     $valdata= DB::table('tblproductvariant')->groupBy('id')->select('variant_value2','offer_price','price','stock')
                    ->where('variant_value1',$pv->variant_value1)
                    ->where('product_id',$product_id)
                    ->get();
                    $vdata['var2data'] = $valdata;
                     
                     $allvariant[]=$vdata;
                }
                
            }
            else
            {
                $allvariant = array();
            }
            if(count($productlist) > 0)
            {
                foreach($productlist as $u)
                {
                    $similerproduct = Products::select('product.id','product.product_name','product.price','product.offer_price',
                    'product.variant_name1','product.variant_value1','product.variant_name2','product.variant_value2',
                    'product.product_image')
                    ->where('product.category_id', $u['category_id'])
                    ->where('product.status', 1)
                    ->where('product.id','!=', $u['id'])
                    ->get();
                    if(count($similerproduct) > 0)
                    {
                        foreach($similerproduct as $su)
                        {
                             $sdata['id'] = $su['id'];
                             $sdata['product_name'] = $su['product_name'];
                             $sdata['price'] = number_format($su['price'],2);
                             $sdata['offer_price'] = number_format($su['offer_price'],2);
                             $sdata['discount'] = number_format((($su['price']-$su['offer_price'])*100)/$su['price'],2);
                             if($su['variant_name1'] == '')
                             {
                                 $sdata['variant_name1'] = '';
                             }
                             else
                             {
                                 $sdata['variant_name1'] = $su['variant_name1'];
                             }
                             if($su['variant_value1'] == '')
                             {
                                 $sdata['variant_value1'] = '';
                             }
                             else
                             {
                                 $sdata['variant_value1'] = $su['variant_value1'];
                             }
                             if($su['variant_name2'] == '')
                             {
                                 $sdata['variant_name2'] = '';
                             }
                             else
                             {
                                 $sdata['variant_name2'] = $su['variant_name2'];
                             }
                             if($su['variant_value2'] == '')
                             {
                                 $sdata['variant_value2'] = '';
                             }
                             else
                             {
                                 $sdata['variant_value2'] = $su['variant_value2'];
                             }
                             $sdata['image'] = url('/public/product_image').'/'.$su['product_image'];
                             $sfdata[]=$sdata;
                        }
                    }
                    else
                    {
                        $sfdata = array();
                    }

                     $udata['id'] = $u['id'];
                     $udata['product_name'] = $u['product_name'];
                     $udata['brand_name'] = $u['brand_name'];
                     $udata['price'] = number_format($u['price'],2);
                     $udata['offer_price'] = number_format($u['offer_price'],2);
                     $udata['discount'] = number_format((($u['price']-$u['offer_price'])*100)/$u['price'],2);
                     
                     $udata['category'] = $u['category'];
                     $udata['is_wishlist_added'] = $is_wishlist_added;
                     $udata['subcategory'] = $u['subcategory'];
                     $udata['product_description'] = $u['product_description'];
                     $udata['stock'] = $u['stock'];
                     $udata['image'] = url('/public/product_image').'/'.$u['product_image'];
                     
                     
                }
                $recentviewed = DB::table('recently_viewed_product')->select('*')
                ->where('user_id',$user_id)
                ->where('product_id',$product_id)
                ->get();
                if(count($recentviewed) == 0)
                {
                    DB::table('recently_viewed_product')->insert(
                        [
                            'product_id' => $product_id,
                            'user_id' => $user_id,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                else
                {
                    DB::table('recently_viewed_product')
                        ->where('product_id', $product_id)
                        ->where('user_id', $user_id)
                        ->update(
                            [
                                'product_id' => $product_id,
                                'user_id' => $user_id,
                                'updated_at' => date('Y-m-d H:i:s'),
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                }
                
                $data = array(
                    'status'=>true,
                    'data'=>$udata,
                    'product_image'=>$imfdata,
                    'similerproduct'=>$sfdata,
                    'product_review'=>$product_review,
                    'variantval1'=>$allvariant,
                    'specification'=>$specidata

                );
                return $data;
            }
            else
            {
                $data = array(
                    'status'=>true,
                    'data'=>(object)[],
                    'variantval1'=>array(),
                    'product_image'=>array(),
                    'product_review'=>(object)[],
                    'similerproduct'=>array(),
                    'specification'=>array()
                );
                return $data;
            }
    }
    
}
