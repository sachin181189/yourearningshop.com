<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Products;
use App\Models\Category;
use App\Models\Vendor;
use App\Models\Product_Size_Unit;
use App\Models\Product_Type;
use App\Models\Subcategory;
use App\Models\Undersubcategory;
use App\Models\Brands;
use Illuminate\Support\Facades\DB;
use Image;
use App\Models\OrderProduct;

class Productcontroller extends Controller
{
    public function productList()
    {
        $product = Products::orderBy('id','DESC')->select()
        ->get();
        return view('admin/product_list')->with('product', $product);
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
    
                $vendor = Vendor::select()
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
                

            return view('admin/new_product_var')->with('product_variant_label',$variant_label)->with('product', $product)->with('id',$id)->with('category',$category)->with('vendor',$vendor)->with('brandlist',$brandlist)->with('product_variant',$product_variant);
        }
        else
        {
            $category = Category::select()
            ->where('status',1)
            ->get();

            $vendor = Vendor::select()
            ->where('status',1)
            ->get();

            $brandlist = Brands::select()
                ->where('status',1)
                ->get();
            $product_variant=array();
            $variant_label = array();
            return view('admin/new_product_var')->with('id','')->with('product_variant_label',$variant_label)->with('category',$category)->with('vendor',$vendor)->with('brandlist',$brandlist)->with('product_variant',$product_variant);
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

        DB::beginTransaction();	    
        $insertId = DB::table('product')->insertGetId(
            [
             'vendor_id' => $data['vendor_id'],
             'product_name' => $data['product_name'],
             'slug' => $data['slug'],
             'category_id' => $data['category_id'],
             'subcategory_id' => $data['sub_category_id'],
             'brand_id' => $data['brand'],
             'product_image' => $image,
             'product_description' => $data['product_description'],
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
                                if(!$updateproduct)
                                {
                                    DB::rollBack();
                                }
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
                                if(!$updateproduct)
                                {
                                    DB::rollBack();
                                }
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
                $save = DB::table('tblproductvariant')->insert($arraydata);
                if(!$save)
                {
                    DB::rollBack();
                }
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
                if(!$updateproduct)
                {
                    DB::rollBack();
                }
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
        		$save = DB::table('tblproductspecification')->insert($finalarr);
        		if(!$save)
                {
                    DB::rollBack();
                }
        		
	        }
	        DB::commit();
            return  redirect('admin/add-new-product')->with('success', 'Data saved !');
        }
        else
        {
             DB::rollBack();
             return redirect('admin/add-new-product')->with('error', 'Data not saved !');
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

    public function updateProduct(Request $request)
    {
        $request->validate([
            'category_id'=>'required',
            'brand'=>'required',
            'vendor_id'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();
        // echo "<pre>";
                                // print_r(array_key_exists($data['variant_name'][1],$data));
                                // die();
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
                'vendor_id' => $data['vendor_id'],
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
                                if(!$updateproduct)
                                {
                                    DB::rollBack();
                                }
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
                                // echo "<pre>";
                                // print_r(array_key_exists($data['variant_name'][1],$data));
                                // die();
                                if(array_key_exists($data['variant_name'][1],$data))
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
                                if(!$updateproduct)
                                {
                                    DB::rollBack();
                                }
                            }
                            else
                            {
                                if(array_key_exists($data['variant_name'][1],$data))
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
                                }
                                array_push($arraydata,$vdata1);
                            }
                        }
                        
                    }
                    $save = DB::table('tblproductvariant')->insert($arraydata);
                    if(!$save)
                    {
                        DB::rollBack();
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
                if(!$updateproduct)
                {
                    DB::rollBack();
                }
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
        		$save = DB::table('tblproductspecification')->insert($finalarr);
        		if(!$save)
                {
                    DB::rollBack();
                }
        		
	        }
	        DB::commit();
            return  redirect('admin/edit-product/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
            
            DB::rollBack();
            return redirect('admin/edit-product/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function changeProductStatus(Request $request)
    {
        $data = $request->input();
        $productid = $data['productid'];
        $status = $data['status'];
        if($status == 1)
        {
            $column = 'is_best_seller';
        }
        elseif($status == 2)
        {
            $column = 'is_trending';

        }
        elseif($status == 3)
        {
            $column = 'is_featured';
            
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
            $column = 'is_best_seller';
        }
        elseif($status == 2)
        {
            $column = 'is_trending';

        }
        elseif($status == 3)
        {
            $column = 'is_featured';
            
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
    public function productReport()
    {
        $product = DB::select("SELECT p.id,p.product_name,v.vendor_name,v.company_name,p.color,p.size,psu.unit,(select SUM(op.offer_price) from order_product op WHERE op.product_id=p.id) as total_price,
        (select SUM(op.qty) from order_product op WHERE op.product_id=p.id) as total_qty
        FROM product p
        INNER JOIN vendor v on v.id=p.vendor_id
        INNER JOIN product_size_unit psu on psu.id=p.unit");

        $price_summary = DB::select("select SUM(op.offer_price) as total_price from order_product op group by op.product_id");
        $qty_summary = DB::select("select SUM(op.qty) as total_qty from order_product op group by op.product_id");
        $total_price = 0;
        foreach($price_summary as $value){
            $total_price = $value->total_price+$total_price;
        }
        $total_qty = 0;
        foreach($qty_summary as $value){
            $total_qty = $value->total_qty+$total_qty;
        }
        return view('admin/product_report')->with('product', $product)->with('total_qty',$total_qty)->with('total_price',number_format($total_price,2));
    }
    public function productDetail()
    {
        $product = DB::select("SELECT p.id,p.product_name,v.vendor_name,v.company_name,p.color,p.size,psu.unit,
        p.product_description,p.price,p.stock,p.offer_price,p.product_image,b.brand_name,sc.subcategory,usc.undersubcategory,
        c.category,p.status
        FROM product p
        INNER JOIN brand b on b.id=p.brand_id
        INNER JOIN category c on c.id=p.category_id
        INNER JOIN subcategory sc on sc.id=p.subcategory_id
        LEFT JOIN undersubcategory usc on usc.id=p.sub_subcategory_id
        INNER JOIN vendor v on v.id=p.vendor_id
        INNER JOIN product_size_unit psu on psu.id=p.unit");

        return view('admin/product_detail')->with('product_detail', $product);

    }
    public function brand_list()
    {
        $brand = Brands::select()
        ->where('status',1)
        ->orderBy('id','DESC')
        ->get();
        return view('admin/brand_list')->with('brand_list', $brand);
    }
    public function addNewBrand(Request $request)
    {
        
        if($request->id)
        {
            $id = $request->id;
            $brand = Brands::select()
                ->where('id', $id)
                ->get();
            return view('admin/new_brand')->with('brand_detail', $brand)->with('id',$id);
        }
        else
        {
            return view('admin/new_brand')->with('id','');;
        }
    }
    public function saveBrand(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'brand_name' => 'required',
            'slug' => 'required',
            'short_description' => 'required',
            'description' => 'required'
        ]);
        $data = $request->input();
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
        
        $rimage = $request->file;
        $folder = 'brand_image/';
        $image = $this->resizeWithDunamicFolderImage($rimage,$imageName,170,100,$folder);

        $savebrand = DB::table('brand')->insert(
            [
             'brand_name' => $data['brand_name'],
             'slug' => $data['slug'],
             'short_description' => $data['short_description'],
             'description' => $data['description'],
             'image' => $image,
             'status' => $data['status'],
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($savebrand)
        {
            return  redirect('admin/add-new-brand')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-brand')->with('error', 'Data not saved !');
        }
    }
    public function updateBrand(Request $request)
    {
        $request->validate([
            'brand_name' => 'required',
            'slug' => 'required',
            'short_description' => 'required',
            'description' => 'required'
        ]);
        $data = $request->input();
        if($request->hasFile('file'))
        { 
            $rimage = $request->file;
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
            $folder = 'brand_image/';
            $image = $this->resizeWithDunamicFolderImage($rimage,$imageName,170,100,$folder);

            $fFilePath = 'public/brand_image/'.$data['hidden_image'];
            if(file_exists( public_path().'/brand_image/'.$data['hidden_image'])){
                unlink($fFilePath);
            }
        }
        else
        {
                $image = $data['hidden_image'];
        }
        $updatebrand = DB::table('brand')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'brand_name' => $data['brand_name'],
                        'slug' => $data['slug'],
                        'short_description' => $data['short_description'],
                        'description' => $data['description'],
                        'image' => $image,
                        'status' => $data['status'],
                        'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
        if($updatebrand)
        {
            return  redirect('admin/edit-brand/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-brand/'.$data['hidden_id'])->with('error', 'Data not saved !');
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
                
            return view('admin/new_product_image')->with('product_image', $product_image)->with('product_id',$product_id)->with('id',$id)->with('single_product_image', $single_product_image);
        }
        else
        {
            $product_id = request()->segment(3);
            $product_image = db::table('product_image')
                ->where('product_id', $product_id)
                ->get();
            $id = '';
            $single_product_image = array();
            return view('admin/new_product_image')->with('product_image', $product_image)->with('product_id',$product_id)->with('id',$id)->with('single_product_image', $single_product_image);
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

            return  redirect('admin/add-product-gallery/'.$hidden_product_id)->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-product-gallery/'.$hidden_product_id)->with('error', 'Data not saved !');
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

            // $fFilePath = 'public/product_image/'.$data['hidden_image'];
            // if(file_exists( public_path().'/product_image/'.$data['hidden_image'])){
            //     unlink($fFilePath);
            // }
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

            return  redirect('admin/edit-product-gallery/'.$hidden_product_id.'/'.$hidden_id)->with('success', 'Data updated !');
        }
        else
        {
             return redirect('admin/edit-product-gallery/'.$hidden_product_id.'/'.$hidden_id)->with('error', 'Data not updated !');
        }
    }
    public function getUnitList()
    {
       
        $unit_list = product_Size_Unit::select()
        ->get();
        return view('admin/unit_list')->with('unit', $unit_list);

    }
    public function addNewUnit(Request $request)
    {

        if($request->id)
        {
            $id = $request->id;
            $unit = product_Size_Unit::select()
                ->where('id', $id)
                ->get();
            return view('admin/new_unit')->with('unit', $unit)->with('id',$id);
        }
        else
        {
            return view('admin/new_unit')->with('id','');;
        }
    }
    public function saveUnit(Request $request)
    {
        $request->validate([
            'unit' => 'required'
        ]);
        $data = $request->input();
        $saveunit = DB::table('product_size_unit')->insert(
            [
             'unit' => $data['unit'],
             'status' => $data['status'],
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($saveunit)
        {
            return  redirect('admin/add-new-unit')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-unit')->with('error', 'Data not saved !');
        }
    }
    public function updateUnit(Request $request)
    {
        $data = $request->input();
        $updateunit = DB::table('product_size_unit')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'unit' => $data['unit'],
                        'status' => $data['status'],
                        'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
        if($updateunit)
        {
            return  redirect('admin/edit-unit/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-unit/'.$data['hidden_id'])->with('error', 'Data not saved !');
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
    public function addNewBrandGallery(Request $request)
    {
        if(request()->segment(4))
        {
            $brand_id = request()->segment(3);
            $brand_image = db::table('brand_image')
                ->where('brand_id', $brand_id)
                ->get();

            $id = request()->segment(4);
            $single_brand_image = db::table('brand_image')
                ->where('id', $id)
                ->get();
                
            return view('admin/brand_slider')->with('brand_image', $brand_image)->with('brand_id',$brand_id)->with('id',$id)->with('single_brand_image', $single_brand_image);
        }
        else
        {
            $brand_id = request()->segment(3);
            $brand_image = db::table('brand_image')
                ->where('brand_id', $brand_id)
                ->get();
            $id = '';
            $single_brand_image = array();
            return view('admin/brand_slider')->with('brand_image', $brand_image)->with('brand_id',$brand_id)->with('id',$id)->with('single_brand_image', $single_brand_image);
        }
    }
    public function saveBrandImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg'
        ]);
    
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
        
        $rimage = $request->file;
        $folder = 'brand_slider_image/';
        $image = $this->resizeWithDunamicFolderImage($rimage,$imageName,1000,350,$folder);

        $data = $request->input();
        $hidden_brand_id = $data['hidden_brand_id'];
        $savebrand = DB::table('brand_image')->insert(
            [
             'image' => $image,
             'brand_id' => $hidden_brand_id,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($savebrand)
        {

            return  redirect('admin/add-brand-gallery/'.$hidden_brand_id)->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-brand-gallery/'.$hidden_brand_id)->with('error', 'Data not saved !');
        }
    }
    public function updateBrandImage(Request $request)
    {
        $data = $request->input();

        if($request->hasFile('file'))
        { 
                        $rimage = $request->file;
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
            $folder = 'brand_slider_image/';
            $image = $this->resizeWithDunamicFolderImage($rimage,$imageName,1000,350,$folder);

            $fFilePath = 'public/brand_slider_image/'.$data['hidden_image'];
            if(file_exists( public_path().'/brand_slider_image/'.$data['hidden_image'])){
                unlink($fFilePath);
            }
        }
        else
        {
                $image = $data['hidden_image'];
        }
        $hidden_id = $data['hidden_id'];
        $hidden_brand_id = $data['hidden_brand_id'];
        $updatebrand = DB::table('brand_image')
        ->where('id', $data['hidden_id'])
        ->update(
            [
             'image' => $image,
             'updated_at'=>date('y-m-d H:i:s')
             ]
        );
        if($updatebrand)
        {

            return  redirect('admin/edit-brand-gallery/'.$hidden_brand_id.'/'.$hidden_id)->with('success', 'Data updated !');
        }
        else
        {
             return redirect('admin/edit-brand-gallery/'.$hidden_brand_id.'/'.$hidden_id)->with('error', 'Data not updated !');
        }
    }
    public function getAllUnitList()
    {
       
        return product_Size_Unit::select()
        ->get();

    }
    public function getSubcategoryVariant(Request $request)
    {
        $subcategory_id = $request->input('subcategory_id');
        return db::table('tblsubcategory_variant')
        ->where('subcategory_id', $subcategory_id)
        ->get();
    }
    // OUR PRODUCT
    public function ourProductList()
    {
        $product = db::table('tbl_our_product')->select()
        ->get();
        return view('admin/our_product')->with('productlist', $product);
    }
    public function addNewOurProduct(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $our_product = db::table('tbl_our_product')->select()
                ->where('id', $id)
                ->get();
            return view('admin/new_our_product')->with('our_product', $our_product)->with('id',$id);
        }
        else
        {
            return view('admin/new_our_product')->with('id','');;
        }
    }
    public function saveOurProduct(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8048',
            'title'=>'required',
            'status'=>'required'
        ]);
    
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
   
        if(Request()->file->move(public_path('our_product'), $imageName))
        {
            $image = $imageName;
        }
        else
        {
            $image = 'default.png';
        }

        $data = $request->input();
        $saveslider = DB::table('tbl_our_product')->insert(
            [
             'title' => $data['title'],
             'status' => $data['status'],
             'image' => $image,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($saveslider)
        {
            return  redirect('admin/add-new-our-product')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-our-product')->with('error', 'Data not saved !');
        }
    }
    public function updateOurProduct(Request $request)
    {
        $request->validate([
            'file' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();

        if($request->hasFile('file'))
        { 
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
    
            if(Request()->file->move(public_path('our_product'), $imageName))
            {
                $image = $imageName;
            }
            else
            {
                $image = 'default.png';
            }
            $fFilePath = 'public/our_product/'.$data['hidden_image'];
            if(file_exists( public_path().'/our_product/'.$data['hidden_image'])){
                unlink($fFilePath);
            }
        }
        else
        {
                $image = $data['hidden_image'];
        }

        $updateslider = DB::table('tbl_our_product')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'title' => $data['title'],
                        'status' => $data['status'],
                        'image' => $image,
                        'updated_at'=>date('y-m-d')
                    ]
                );
        if($updateslider)
        {
            return  redirect('admin/edit-our-product/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-our-product/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function removeOurProduct(Request $request)
    {
        $data = $request->input();
        $id = $data['id'];
        $removeslider = DB::table('tbl_our_product')
                ->where('id', $id)
                ->delete();
        if($removeslider)
        {
            return "Changed";
        }
        else
        {
             return "Failed";
        }
    }
    public function removeProductImage(Request $request)
    {
        $id = $request->id;
        $product_id = $request->product_id;
        $removeimage = DB::table('product_image')
                ->where('id', $id)
                ->delete();
        if($removeimage)
        {
            return  redirect('admin/add-product-gallery/'.$product_id)->with('success', 'Imae deleted !');
        }
        else
        {
             return redirect('admin/add-product-gallery/'.$product_id)->with('error', 'Error in deleted !');
        }
    }
    
    // new add new product functionality
    public function addProduct(Request $request)
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
    
                $vendor = Vendor::select()
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
                

            return view('admin/new_product_new')->with('product_variant_label',$variant_label)->with('product', $product)->with('id',$id)->with('category',$category)->with('vendor',$vendor)->with('brandlist',$brandlist)->with('product_variant',$product_variant);
        }
        else
        {
            $category = Category::select()
            ->where('status',1)
            ->get();

            $vendor = Vendor::select()
            ->where('status',1)
            ->get();

            $brandlist = Brands::select()
                ->where('status',1)
                ->get();
            $product_variant=array();
            $variant_label = array();
            return view('admin/new_product_new')->with('id','')->with('product_variant_label',$variant_label)->with('category',$category)->with('vendor',$vendor)->with('brandlist',$brandlist)->with('product_variant',$product_variant);
        }
    }
    public function saveNewProduct(Request $request)
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

        DB::beginTransaction();	    
        $insertId = DB::table('product')->insertGetId(
            [
             'vendor_id' => $data['vendor_id'],
             'product_name' => $data['product_name'],
             'slug' => $data['slug'],
             'category_id' => $data['category_id'],
             'subcategory_id' => $data['sub_category_id'],
             'brand_id' => $data['brand'],
             'product_image' => $image,
             'product_description' => $data['product_description'],
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
                                if(!$updateproduct)
                                {
                                    DB::rollBack();
                                }
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
                                if(!$updateproduct)
                                {
                                    DB::rollBack();
                                }
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
                $save = DB::table('tblproductvariant')->insert($arraydata);
                if(!$save)
                {
                    DB::rollBack();
                }
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
                if(!$updateproduct)
                {
                    DB::rollBack();
                }
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
        		$save = DB::table('tblproductspecification')->insert($finalarr);
        		if(!$save)
                {
                    DB::rollBack();
                }
        		
	        }
	        DB::commit();
            return  redirect('admin/add-new-product')->with('success', 'Data saved !');
        }
        else
        {
             DB::rollBack();
             return redirect('admin/add-new-product')->with('error', 'Data not saved !');
        }
    }
    
}
