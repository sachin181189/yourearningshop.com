<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Undersubcategory;
use Illuminate\Support\Facades\DB;
use App\Models\Http\Controllers\Session;

class Categorycontroller extends Controller
{
    public function getCategory()
    {
       
        $category = Category::select()
        ->get();
        return view('admin/category_list')->with('category', $category);

    }
    public function addNewCategory(Request $request)
    {
        
        if($request->id)
        {
            $id = $request->id;
            $category = Category::select()
                ->where('id', $id)
                ->get();
            return view('admin/new_category')->with('category', $category)->with('id',$id);
        }
        else
        {
            return view('admin/new_category')->with('id','');;
        }
    }
    public function saveCategory(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category'=>'required',
            'slug'=>'required',
            'status'=>'required'
        ]);
    
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
   
        if(Request()->file->move(public_path('category_image'), $imageName))
        {
            $image = $imageName;
        }
        else
        {
            $image = 'default.png';
        }

        $data = $request->input();
        $savecategory = DB::table('category')->insert(
            [
             'category' => $data['category'],
             'slug' => $data['slug'],
             'sequence' => $data['sequence'],
             'show_in_menu' => 0,
             'status' => $data['status'],
             'image_icon' => $image,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($savecategory)
        {
            return  redirect('admin/add-new-category')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-category')->with('error', 'Data not saved !');
        }
    }
    public function updateCategory(Request $request)
    {
        $request->validate([
            'file' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category'=>'required',
            'slug'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();

        if($request->hasFile('file'))
        { 
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
    
            if(Request()->file->move(public_path('category_image'), $imageName))
            {
                $image = $imageName;
            }
            else
            {
                $image = 'default.png';
            }
            // $fFilePath = 'public/category_image/'.$data['hidden_image'];
            // if(file_exists( public_path().'/category_image/'.$data['hidden_image'])){
            //     unlink($fFilePath);
            // }
        }
        else
        {
                $image = $data['hidden_image'];
        }

        $updatecategory = DB::table('category')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'category' => $data['category'],
                        'slug' => $data['slug'],
                        'sequence' => $data['sequence'],
                        'show_in_menu' => 0,
                        'status' => $data['status'],
                        'image_icon' => $image,
                        'updated_at'=>date('y-m-d')
                    ]
                );
        if($updatecategory)
        {
            return  redirect('admin/edit-category/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-category/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function getSubategory()
    {
        $subcategory = Subcategory::select('subcategory.subcategory', 'category.category', 'subcategory.id','subcategory.status')
            ->join('category', 'category.id', '=', 'subcategory.category')
            ->get();
        return view('admin/sub_category_list')->with('subcategory', $subcategory);

    }
    public function addNewSubategory(Request $request)
    {

        if($request->id)
        {
            $category = Category::select()
            ->get();

            $id = $request->id;
            $subcategory = Subcategory::select()
                ->where('id', $id)
                ->get();
            
            $subcategoryvariant = DB::table('tblsubcategory_variant')->select()
                ->where('subcategory_id', $id)
                ->get();
            return view('admin/new_sub_category')->with('subcategory', $subcategory)->with('id',$id)->with('category', $category)->with('subcategoryvariant',$subcategoryvariant);
        }
        else
        {
            $category = Category::select()
            ->where('status',1)
            ->get();
            return view('admin/new_sub_category')->with('id','')->with('category', $category);
        }
    }
    public function saveSubategory(Request $request)
    {
        $request->validate([
            'category' => 'required',
            'subcategory'=>'required',
            'slug'=>'required',
            'status'=>'required',
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);
        
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
   
        if(Request()->file->move(public_path('sub_category_image'), $imageName))
        {
            $image = $imageName;
        }
        else
        {
            $image = 'default.png';
        }
        $data = $request->input();
        $savecategory = DB::table('subcategory')->insertGetId(
            [
             'subcategory' => $data['subcategory'],
             'category' => $data['category'],
             'slug' => $data['slug'],
             'image_icon' => $image,
             'status' => $data['status'],
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($savecategory)
        {
            $variant = $data['variant'];
            $count = count($variant);
        		$finalarr = array();
        		for($i=1;$i<=$count;$i++)
        		{
        		    if($variant[$i-1] != '')
        		    {
            			$arra = array(
            				'variant_name'=>$variant[$i-1],
            				'subcategory_id'=>$savecategory
            			);
            			$finalarr[] = $arra;
        		    }
        		}
        		DB::table('tblsubcategory_variant')->insert($finalarr);
            return  redirect('admin/add-new-subcategory')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-subcategory')->with('error', 'Data not saved !');
        }
    }
    public function updateSubategory(Request $request)
    {
        $request->validate([
            'subcategory'=>'required',
            'category'=>'required',
            'slug'=>'required',
            'status'=>'required',
            'file' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        $data = $request->input();

        if($request->hasFile('file'))
        { 
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
    
            if(Request()->file->move(public_path('sub_category_image'), $imageName))
            {
                $image = $imageName;
            }
            else
            {
                $image = 'default.png';
            }
            // $fFilePath = 'public/category_image/'.$data['hidden_image'];
            // if(file_exists( public_path().'/category_image/'.$data['hidden_image'])){
            //     unlink($fFilePath);
            // }
        }
        else
        {
                $image = $data['hidden_image'];
        }

        $updatecategory = DB::table('subcategory')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'subcategory' => $data['subcategory'],
                        'category' => $data['category'],
                        'slug' => $data['slug'],
                        'image_icon'=>$image,
                        'status' => $data['status'],
                        'updated_at'=>date('y-m-d H:i:s')
                        ]
                );
        if($updatecategory)
        {
            $variant = $data['variant'];
            $count = count($variant);
            $removespec = DB::table('tblsubcategory_variant')
                ->where('subcategory_id', $data['hidden_id'])
                ->delete();
        		$finalarr = array();
        		for($i=1;$i<=$count;$i++)
        		{
        		    if($variant[$i-1] != '')
        		    {
            			$arra = array(
            				'variant_name'=>$variant[$i-1],
            				'subcategory_id'=>$data['hidden_id']
            			);
            			$finalarr[] = $arra;
        		    }
        		}
        		DB::table('tblsubcategory_variant')->insert($finalarr);
            return  redirect('admin/edit-subcategory/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-subcategory/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function getSubsubategory()
    {
        $subcategory = Undersubcategory::select('undersubcategory.undersubcategory', 'category.category', 'undersubcategory.id','undersubcategory.status','subcategory.subcategory')
            ->join('category', 'category.id', '=', 'undersubcategory.category')
            ->join('subcategory', 'subcategory.id', '=', 'undersubcategory.subcategory')
            ->get();
        return view('admin/sub_subcategory_list')->with('subcategory', $subcategory);

    }
    public function addNewSubsubcategory(Request $request)
    {
        if($request->id)
        {
            $category = Category::select()
            ->get();

            $id = $request->id;
            $subcategory = Undersubcategory::select()
                ->where('id', $id)
                ->get();
            return view('admin/new_sub_subcategory')->with('subcategory', $subcategory)->with('id',$id)->with('category', $category);
        }
        else
        {
            $category = Category::select()
            ->where('status',1)
            ->get();
            return view('admin/new_sub_subcategory')->with('id','')->with('category', $category);
        }
    }
    public function getSubcategoryByAjax(Request $request)
    {
        $category_id = $request->input('category_id');
            $subcategory = Subcategory::select()
            ->where('category', '=', $category_id)
            ->get();
            return json_encode($subcategory);
            exit;
    }
    public function getSubsubcategoryByAjax(Request $request)
    {
        $subcategory_id = $request->input('subcategory_id');
            $subsubcategory = Undersubcategory::select()
            ->where('subcategory', '=', $subcategory_id)
            ->get();
            return json_encode($subsubcategory);
            exit;
    }
    public function saveSubsubcategory(Request $request)
    {
        $request->validate([
            'category'=>'required',
            'subcategory'=>'required',
            'subsubcategory'=>'required',
            'status'=>'required'
        ]);

        $data = $request->input();
        $savesubcategory = DB::table('undersubcategory')->insert(
            [
             'undersubcategory' => $data['subsubcategory'],
             'subcategory' => $data['subcategory'],
             'category' => $data['category'],
             'image_icon' => '',
             'status' => $data['status'],
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
            ]
        );
        if($savesubcategory)
        {
            return  redirect('admin/add-new-subsubcategory')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-subsubcategory')->with('error', 'Data not saved !');
        }
    }
    public function updateSubsubcategory(Request $request)
    {
        $request->validate([
            'category'=>'required',
            'subcategory'=>'required',
            'subsubcategory'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();

        $updateundersubcategory = DB::table('undersubcategory')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'undersubcategory' => $data['subsubcategory'],
                        'subcategory' => $data['subcategory'],
                        'category' => $data['category'],
                        'status' => $data['status'],
                        'updated_at'=>date('y-m-d')
                        ]
                );
        if($updateundersubcategory)
        {
            return  redirect('admin/edit-subsubcategory/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-subsubcategory/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
}
