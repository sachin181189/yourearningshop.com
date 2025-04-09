<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use App\Models\Slider;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Coupon;
use App\Models\User;
use App\Models\company_profile;
use Illuminate\Support\Facades\DB;
use Image;


class Homecontroller extends Controller
{
    public function sliderList()
    {
        $slider = Slider::select()
        ->get();
        return view('admin/slider_list')->with('sliderlist', $slider);
    }
    public function addNewSlider(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $slider = Slider::select()
                ->where('id', $id)
                ->get();
            return view('admin/new_slider')->with('slider', $slider)->with('id',$id);
        }
        else
        {
            return view('admin/new_slider')->with('id','');;
        }
    }
    public function saveSlider(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8048',
            'title'=>'required',
            'status'=>'required'
        ]);
    
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
   
        if(Request()->file->move(public_path('slider_image'), $imageName))
        {
            $image = $imageName;
        }
        else
        {
            $image = 'default.png';
        }

        $data = $request->input();
        $saveslider = DB::table('slider')->insert(
            [
             'title' => $data['title'],
             'status' => $data['status'],
             'slider_image' => $image,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($saveslider)
        {
            return  redirect('admin/add-new-slider')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-slider')->with('error', 'Data not saved !');
        }
    }
    public function updateSlider(Request $request)
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
    
            if(Request()->file->move(public_path('slider_image'), $imageName))
            {
                $image = $imageName;
            }
            else
            {
                $image = 'default.png';
            }
            $fFilePath = 'public/slider_image/'.$data['hidden_image'];
            if(file_exists( public_path().'/slider_image/'.$data['hidden_image'])){
                unlink($fFilePath);
            }
        }
        else
        {
                $image = $data['hidden_image'];
        }

        $updateslider = DB::table('slider')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'title' => $data['title'],
                        'status' => $data['status'],
                        'slider_image' => $image,
                        'updated_at'=>date('y-m-d')
                    ]
                );
        if($updateslider)
        {
            return  redirect('admin/edit-slider/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-slider/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function removeSlider(Request $request)
    {
        $data = $request->input();
        $sliderid = $data['sliderid'];
        $removeslider = DB::table('slider')
                ->where('id', $sliderid)
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
    
    public function videoList()
    {
        $video = DB::table('tbl_video')
        ->get();
        return view('admin/video')->with('video_list', $video);
    }
    public function addNewVideo(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $video = DB::table('tbl_video')
                ->where('id', $id)
                ->get();
            return view('admin/new_video')->with('video', $video)->with('id',$id);
        }
        else
        {
            return view('admin/new_video')->with('id','');;
        }
    }
    public function saveVideo(Request $request)
    {
        $request->validate([
            'video_url'=>'required',
            'status'=>'required'
        ]);

        $data = $request->input();
        $savevideo = DB::table('tbl_video')->insert(
            [
             'video_url' => $data['video_url'],
             'status' => $data['status'],
             'created_at'=>date('y-m-d')
             ]
        );
        if($savevideo)
        {
            return  redirect('admin/add-new-video')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-video')->with('error', 'Data not saved !');
        }
    }
    public function updateVideo(Request $request)
    {
        $request->validate([
            'video_url'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();

        $updatevideo = DB::table('tbl_video')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'video_url' => $data['video_url'],
                        'status' => $data['status'],
                        'created_at'=>date('Y-m-d H:i:s')
                    ]
                );
        if($updatevideo)
        {
            return  redirect('admin/edit-video/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-video/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function removeVideo(Request $request)
    {
        $data = $request->input();
        $id = $data['id'];
        $removevideo = DB::table('tbl_video')
                ->where('id', $id)
                ->delete();
        if($removevideo)
        {
            return "Changed";
        }
        else
        {
             return "Failed";
        }
    }
    
    public function bannerList()
    {
        $banner = Banner::select()
        ->get();
        return view('admin/banner_list')->with('bannerlist', $banner);
    }
    public function editBanner(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $banner = Banner::select()
                ->where('id', $id)
                ->get();
            return view('admin/edit_banner')->with('banner', $banner)->with('id',$id);
        }
        else
        {
            return view('admin/edit_banner')->with('id','');;
        }
    }
    public function updateBanner(Request $request)
    {
        $request->validate([
            'file' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8048',
            'title'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();

        if($request->hasFile('file'))
        { 
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
    
            if(Request()->file->move(public_path('banner_image'), $imageName))
            {
                $image = $imageName;
            }
            else
            {
                $image = 'default.png';
            }
            $fFilePath = 'public/banner_image/'.$data['hidden_image'];
            if(file_exists( public_path().'/banner_image/'.$data['hidden_image'])){
                unlink($fFilePath);
            }
        }
        else
        {
                $image = $data['hidden_image'];
        }

        $updatebanner = DB::table('banner')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'title' => $data['title'],
                        'position' => $data['position'],
                        'status' => $data['status'],
                        'banner_image' => $image,
                        'updated_at'=>date('y-m-d')
                    ]
                );
        if($updatebanner)
        {
            return  redirect('admin/edit-banner/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-banner/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function blogList()
    {
        $blog = Blog::select()
        ->get();
        return view('admin/blog_list')->with('bloglist', $blog);
    }
    public function addNewBlog(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $blog = Blog::select()
                ->where('id', $id)
                ->get();
            return view('admin/new_blog')->with('blog', $blog)->with('id',$id);
        }
        else
        {
            return view('admin/new_blog')->with('id','');;
        }
    }
    public function saveBlog(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8048',
            'title'=>'required',
            'status'=>'required'
        ]);
    
        $imageName = time().'.'.Request()->file->getClientOriginalExtension();
   
        if(Request()->file->move(public_path('blog_image'), $imageName))
        {
            $image = $imageName;
        }
        else
        {
            $image = 'default.png';
        }

        $data = $request->input();
        $saveblog = DB::table('blog')->insert(
            [
             'title' => $data['title'],
             'short_description' => $data['short_description'],
             'description' => $data['description'],
             'status' => $data['status'],
             'image' => $image,
             'created_at'=>date('y-m-d'),
             'updated_at'=>date('y-m-d')
             ]
        );
        if($saveblog)
        {
            return  redirect('admin/add-new-blog')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-blog')->with('error', 'Data not saved !');
        }
    }
    public function updateBlog(Request $request)
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
    
            if(Request()->file->move(public_path('blog_image'), $imageName))
            {
                $image = $imageName;
            }
            else
            {
                $image = 'default.png';
            }
            $fFilePath = 'public/blog_image/'.$data['hidden_image'];
            if(file_exists( public_path().'/blog_image/'.$data['hidden_image'])){
                unlink($fFilePath);
            }
        }
        else
        {
                $image = $data['hidden_image'];
        }

        $updateblog = DB::table('blog')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'title' => $data['title'],
                        'short_description' => $data['short_description'],
                        'description' => $data['description'],
                        'status' => $data['status'],
                        'image' => $image,
                        'updated_at'=>date('y-m-d')
                    ]
                );
        if($updateblog)
        {
            return  redirect('admin/edit-blog/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-blog/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function manageCompanyProfile()
    {
        $company_profile = company_profile::select()
        ->get();
        return view('admin/manage_company_profile')->with('company_profile', $company_profile);
    }
    public function updateCompanyProfile(Request $request)
    {
        $request->validate([
            'file' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'company_name'=>'required'
            ]);
        $data = $request->input();
        if($_FILES['file']['size'] > 0)
        {
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
    
            if(Request()->file->move(public_path('company_image'), $imageName))
            {
                $image = $imageName;
            }
            else
            {
                return redirect('admin/company-profile')->with('error', 'Image can not be blank !');
            }
        }
        else
        {
            $image = $data['hidden_image'];
        }
        $updatecompanyprofile = DB::table('company_profile')
        ->where('id', $data['hidden_id'])
        ->update(
            [
                'company_name' => $data['company_name'],
                'address' => $data['address'],
                'conatct_no' => $data['conatct_no'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'aatmnirbhar_mahila_no' => $data['aatmnirbhar_mahila_no'],
                'whatsapp_no' => $data['whatsapp_no'],
                'facebook_link' => $data['facebook_link'],
                'twitter_link' => $data['twitter_link'],
                'linkdin_link' => $data['linkdin_link'],
                'logo' => $image,
                'updated_at'=>date('y-m-d')
            ]
        );
        if($updatecompanyprofile)
        {
            return  redirect('admin/company-profile')->with('success', 'Data saved !');
        }
        else
        {
            return redirect('admin/company-profile')->with('error', 'Data not saved !');
        }
    }
    public function editAboutUs(Request $request)
    {

            $aboutsdata = DB::table('aboutus')
                ->where('id', 1)
                ->get();
            return view('admin/aboutus')->with('aboutus', $aboutsdata);
    }
    public function editPrivacyPolicy(Request $request)
    {

            $aboutsdata = DB::table('privacy_policy')
                ->where('id', 1)
                ->get();
            return view('admin/privacy_policy')->with('privacy_policy', $aboutsdata);
    }
    public function editTermCondition(Request $request)
    {

            $aboutsdata = DB::table('term_condition')
                ->where('id', 1)
                ->get();
            return view('admin/term_condition')->with('term_condition', $aboutsdata);
    }
    public function editShippingPolicy(Request $request)
    {

            $shipping_policy = DB::table('shipping_policy')
                ->where('id', 1)
                ->get();
            return view('admin/shipping_policy')->with('shipping_policy', $shipping_policy);
    }
    public function updateAboutUs(Request $request)
    {
        $request->validate([
            'content' => 'required'
        ]);
        $data = $request->input();
        if($request->hasFile('file'))
        { 
            $rimage = $request->file;
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
            $folder = 'banner_image/';
            $image = $this->resizeWithDunamicFolderImage($rimage,$imageName,1300,400,$folder);
        }
        else
        {
                $image = $data['hidden_image'];
        }
        $updateaboutus = DB::table('aboutus')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'content' => $data['content'],
                        'banner' => $image,
                        'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
        if($updateaboutus)
        {
            return  redirect('admin/edit-aboutus')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-aboutus')->with('error', 'Data not saved !');
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
    public function updatePrivacyPolicy(Request $request)
    {
        $request->validate([
            'content' => 'required'
        ]);
        $data = $request->input();
        if($request->hasFile('file'))
        { 
            $rimage = $request->file;
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
            $folder = 'banner_image/';
            $image = $this->resizeWithDunamicFolderImage($rimage,$imageName,1300,400,$folder);
        }
        else
        {
                $image = $data['hidden_image'];
        }
        $updateprivacypolicy = DB::table('privacy_policy')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'content' => $data['content'],
                        'banner' => $image,
                        'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
        if($updateprivacypolicy)
        {
            return  redirect('admin/edit-privacy-policy')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-privacy-policy')->with('error', 'Data not saved !');
        }
    }
    public function updatetermCondition(Request $request)
    {
        $request->validate([
            'content' => 'required'
        ]);
        $data = $request->input();
        if($request->hasFile('file'))
        { 
            $rimage = $request->file;
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
            $folder = 'banner_image/';
            $image = $this->resizeWithDunamicFolderImage($rimage,$imageName,1300,400,$folder);
        }
        else
        {
                $image = $data['hidden_image'];
        }
         $updatetermcondition = DB::table('term_condition')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'content' => $data['content'],
                        'banner' => $image,
                        'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
        if($updatetermcondition)
        {
            return  redirect('admin/edit-term-condition')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-term-condition')->with('error', 'Data not saved !');
        }
    }
    public function updateShippingPolicy(Request $request)
    {
        $request->validate([
            'content' => 'required'
        ]);
        $data = $request->input();
        if($request->hasFile('file'))
        { 
            $rimage = $request->file;
            $imageName = time().'.'.Request()->file->getClientOriginalExtension();
            $folder = 'banner_image/';
            $image = $this->resizeWithDunamicFolderImage($rimage,$imageName,1300,400,$folder);
        }
        else
        {
                $image = $data['hidden_image'];
        }
        $updateshippingpolicy = DB::table('shipping_policy')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'content' => $data['content'],
                        'banner' => $image,
                        'updated_at'=>date('y-m-d H:i:s')
                    ]
                );
        if($updateshippingpolicy)
        {
            return  redirect('admin/edit-shipping-policy')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-shipping-policy')->with('error', 'Data not saved !');
        }
    }
    
    public function add_new_store(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $storeRequest = DB::table('tblstorerequest')
                        ->where('id',$id)
                        ->orderBy('status','ASC')
                        ->get();
            return view('admin/new_store')->with('store', $storeRequest)->with('id',$id);
        }
        else
        {
            return view('admin/new_store')->with('store', '')->with('id','');
        }
    }
    public function save_store(Request $request)
    {
        $request->validate([
            'fullname'=>'required',
            'email'=>'required',
            'phone'=>'required',
            'pincode'=>'required',
        ]);
        $data = $request->input();
        $email = $data['email'];
        $phone = $data['phone'];
        $pincode = $data['pincode'];
        DB::beginTransaction();
        $getUser =  DB::table('tblstorerequest')
                        ->where(function($q) use ($email,$phone,$pincode){
                          $q->where('email', $email)
                            ->orWhere('mobile', $phone)
                            ->orWhere('pincode', $pincode);
                        })
                    ->where('status',1)
                    ->get();
        if(count($getUser) > 0)
        {
            return redirect('/register-store')->with('error', 'Store already exist with this email or mobile no. or with pincode !');
        }
        else
        {
            $saveuseraddress = DB::table('tblstorerequest')->insert(
                [
                'fullname' => $data['fullname'],
                'email' => $data['email'],
                'mobile' => $data['phone'],
                'address' => $data['address'],
                'pincode' => $data['pincode'],
                'status' => 1
                ]
            );
            if($saveuseraddress)
            {
                    $saveVendor = DB::table('vendor')->insert(
                        [
                             'company_name' => $data['fullname'],
                             'vendor_name' => $data['fullname'],
                             'email' => $data['email'],
                             'zip' => $data['pincode'],
                             'store_id' => 'YES'.rand(103,999),
                             'password' => md5('YES'.rand(100,999)),
                             'mobile' => $data['phone'],
                             'status' => 1
                         ]
                    );
                DB::commit();
                return  redirect('save-store')->with('success', 'Registered successfully !');
            }
            else
            {
                DB::rollBack();
                return redirect('save-store')->with('error', 'Something went wrong !');
            }
        }
    }
    
    public function update_store(Request $request)
    {
        $request->validate([
            'fullname'=>'required',
            'email'=>'required',
            'phone'=>'required',
            'pincode'=>'required',
        ]);
        $data = $request->input();
        $email = $data['email'];
        $phone = $data['phone'];
        $pincode = $data['pincode'];
        $status = $data['status'];
        $id = $data['hidden_id'];
        DB::beginTransaction();
        $getUser =  DB::table('tblstorerequest')
                        ->where(function($q) use ($email,$phone,$pincode){
                          $q->where('email', $email)
                            ->orWhere('mobile', $phone)
                            ->orWhere('pincode', $pincode);
                        })
                    ->where('id','!=',$id)
                    ->get();
        if(count($getUser) > 0)
        {
            return redirect('/register-store')->with('error', 'Store already exist with this email or mobile no. or with pincode !');
        }
        else
        {
            $update = DB::table('tblstorerequest')
                ->where('id', $id)
                ->update(
                [
                    'fullname' => $data['fullname'],
                    'email' => $data['email'],
                    'mobile' => $data['phone'],
                    'address' => $data['address'],
                    'pincode' => $data['pincode'],
                    'status' => $status,
                    'update_date'=>date('Y-m-d H:i:s')
                ]
            );
            if($update)
            {
                DB::table('vendor')
                    ->where('store_id', $data['store_id'])
                    ->update(
                        [
                             'company_name' => $data['fullname'],
                             'email' => $data['email'],
                             'zip' => $data['pincode'],
                             'mobile' => $data['phone'],
                             'status' => $status,
                             'updated_at'=>date('Y-m-d H:i:s')
                         ]
                    );
                DB::commit();
                return  redirect('admin/edit-store/'.$id)->with('success', 'Updated successfully !');
            }
            else
            {
                DB::rollBack();
                return redirect('admin/edit-store/'.$id)->with('error', 'Something went wrong !');
            }
        }
    }
    
    public function storeRequest()
    {
        $storeRequest = DB::table('tblstorerequest')
                        ->orderBy('status','ASC')
                        ->get();
        return view('admin/store-request')->with('storeRequest', $storeRequest);
    }
    
    public function provideCredential(Request $request)
    {
        $request->validate([
            'email'=>'required',
            'password'=>'required',
            'pincode'=>'required',
            'deposit_amount'=>'required'
        ]);
        $store_id = 'YES'.date('Ymdhis');
        $data = $request->input();
        $reffer_by = $data['reffer_by'];
                $update = DB::table('tblstorerequest')
                ->where('email', $data['email'])
                ->update(
                [
                    'status' => 1,
                    'store_id' => $store_id,
                    'deposit_amount' => $data['deposit_amount']
                ]
            );
        if($update)
        {
            $saveVendor = DB::table('vendor')->insertGetId(
                [
                 'email' => $data['email'],
                 'company_name' => $data['fullname'],
                 'vendor_name' => $data['fullname'],
                 'zip' => $data['pincode'],
                 'store_id' => $store_id,
                 'password' => md5($data['password']),
                 'mobile' => $data['mobile'],
                 'status' => 1
                 ]
            );
            if($saveVendor)
            {
                if($reffer_by)
                {
                    $settings = DB::table('tbl_setting')->select('dp_to_bp_referall_royality_income','dp_to_bp_referall_deposit_amount_percentage')->first();
                    $dp_to_bp_referall_royality_income = $settings->dp_to_bp_referall_royality_income;
                    $dp_to_bp_referall_deposit_amount_percentage = $settings->dp_to_bp_referall_deposit_amount_percentage;
                    
                    // DP to BP royality income 5%
                    $user_detail = DB::table('users')->select('id')->where('referral_code', $referral_id)->first();
                    $user_id = $user_detail->id;
                    $depositamount = $data['deposit_amount'];
                    $amount = ($dp_to_bp_referall_royality_income / 100) * $depositamount;
                    
                    $savearr = array(
                    'user_id'=>$user_id,
                    'vendor_id'=>$saveVendor,
                    'settlement_date'=>date('Y-m-d'),
                    'settlement_time'=>date('H:i:s'),
                    'amount'=>$amount,
                    'type'=>'DIPOSIT_INCOME'
                    );
                    DB::table('tbl_user_dp_settlement')->insert($savearr);
                    
                    // Update user balance
                    $this->update_user_balance($user_id,$amount);
                    
                    // FIVE level user commision from store (one time settlement).1%
                    
                    $store_referal_code_list = DB::table('tblstorerequest')->select('id','reffer_by','deposit_amount')
                    ->where('status', 1)
                    ->where_not_null('reffer_by')
                    ->get();
                    if(count($store_referal_code_list) > 0)
                    {
                        foreach($store_referal_code_list as $sl)
                        {
                            $deposit_amount = $sl->deposit_amount;
                            $store_referal_code_list = DB::table('users')->select('users.id','users.referral_code')
                            ->where('users.status', 1)
                            ->where('referral_code',$sl->referral_code)
                            ->get();
                            
                            $amount = ($dp_to_bp_referall_deposit_amount_percentage / 100) * $deposit_amount;
                            $savearr = array(
                            'user_id'=>$sl->id,
                            'vendor_id'=>$saveVendor,
                            'settlement_date'=>date('Y-m-d'),
                            'settlement_time'=>date('H:i:s'),
                            'amount'=>$amount,
                            'type'=>'DEPOSIT_COMMISSION'
                            );
                            DB::table('tbl_user_dp_settlement')->insert($savearr);
                            
                            // Update user balance
                            $this->update_user_balance($sl->id,$amount);
                    
                        }
                    }
                    
                    
                }
                
                $mailto = $data['email'];
                $from_name = "YES";
                $from_mail = "yourearningshop@gmail.com";
                $subject = "Store request accepted";
                $message = 'Dear user your store request is accepted .You can login now from below credential .';
                $message .= '<p>Url : https://www.yourearningshop.com/vendor </p>';
                $message .= '<p>Email : '.$data['email'].'</p>';
                $message .= '<p>Password : '.$data['password'].'</p>';
                $this->sendMail($mailto, $from_mail, $from_name, $subject, $message);
            }
            return  redirect('admin/store-request')->with('success', 'Credential Created !');
        }
        else
        {
             return redirect('admin/store-request')->with('error', 'Credential Not Created !');
        }
    }
    
    public function deleteRequest(Request $request)
    {
        $data = $request->input();
        $requestId = $data['requestId'];
        $deleteRequest = DB::table('tblstorerequest')
                ->where('id', $requestId)
                ->delete();
        if($deleteRequest)
        {
            return "Changed";
        }
        else
        {
             return "Failed";
        }
    }
    public function documentList()
    {
        $document = DB::table('tbl_company_document')->select()
        ->get();
        return view('admin/document_list')->with('documentlist', $document);
    }
    public function editDocument(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $document = DB::table('tbl_company_document')->select()
                ->where('id', $id)
                ->get();
            return view('admin/edit_document')->with('document', $document)->with('id',$id);
        }
        else
        {
            return view('admin/edit_document')->with('id','');;
        }
    }
    public function updateDocument(Request $request)
    {
        $request->validate([
            'document_name'=>'required',
            'status'=>'required'
        ]);
        $data = $request->input();

        if($request->hasFile('file'))
        { 
            $docName = time().'.'.Request()->file->getClientOriginalExtension();
    
            if(Request()->file->move(public_path('document'), $docName))
            {
                $doc = $docName;
            }
            else
            {
                $doc = 'default.png';
            }
            
        }
        else
        {
                $doc = $data['hidden_document'];
        }
        $updatedocument = DB::table('tbl_company_document')
                ->where('id', $data['hidden_id'])
                ->update(
                    [
                        'document_name' => $data['document_name'],
                        'document' => $doc
                    ]
                );
        if($updatedocument)
        {
            return  redirect('admin/edit-document/'.$data['hidden_id'])->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/edit-document/'.$data['hidden_id'])->with('error', 'Data not saved !');
        }
    }
    public function manageInvestmentPlan()
    {
        $investment_plan = DB::table('tbl_business_plan')
        ->where('type',1)
        ->get();
        return view('admin/investment_plan')->with('investment_plan', $investment_plan);
    }
    public function updateInvestmentPlan(Request $request)
    {
        $request->validate([
            'content'=>'required'
            ]);
        $data = $request->input();
        
        // IMAGE 1 UPLOAD
        
        if($_FILES['file1']['size'] > 0)
        {
            $imageName1 = rand().'.'.Request()->file1->getClientOriginalExtension();
            if(Request()->file1->move(public_path('business_plan'), $imageName1))
            {
                $image1 = $imageName1;
            }
            else
            {
                return redirect('admin/investment-plan')->with('error', 'Image can not be blank !');
            }
        }
        else
        {
            $image1 = $data['image1'];
        }
        
        // IMAGE 1 UPLOAD
        
        // IMAGE 2 UPLOAD START
        
        if($_FILES['file2']['size'] > 0)
        {
            $imageName2 = rand().'.'.Request()->file2->getClientOriginalExtension();
    
            if(Request()->file2->move(public_path('business_plan'), $imageName2))
            {
                $image2 = $imageName2;
            }
            else
            {
                return redirect('admin/investment-plan')->with('error', 'Image can not be blank !');
            }
        }
        else
        {
            $image2 = $data['image2'];
        }
        
        // IMAGE 2 UPLOAD END
        
        // IMAGE 3 UPLOAD START
        
        if($_FILES['file3']['size'] > 0)
        {
            $imageName3 = rand().'.'.Request()->file3->getClientOriginalExtension();
    
            if(Request()->file3->move(public_path('business_plan'), $imageName3))
            {
                $image3 = $imageName3;
            }
            else
            {
                return redirect('admin/investment-plan')->with('error', 'Image can not be blank !');
            }
        }
        else
        {
            $image3 = $data['image3'];
        }
        
        // IMAGE 3 UPLOAD END
        
        // IMAGE 4 UPLOAD START
        
        if($_FILES['file4']['size'] > 0)
        {
            $imageName4 = time().'.'.Request()->file4->getClientOriginalExtension();
    
            if(Request()->file4->move(public_path('business_plan'), $imageName4))
            {
                $image4 = $imageName4;
            }
            else
            {
                return redirect('admin/investment-plan')->with('error', 'Image can not be blank !');
            }
        }
        else
        {
            $image4 = $data['image4'];
        }
        
        // IMAGE 4 UPLOAD END
        
        $updatecompanyprofile = DB::table('tbl_business_plan')
        ->where('id', $data['hidden_id'])
        ->update(
            [
                'content' => $data['content'],
                'image1' => $image1,
                'image2' => $image2,
                'image3' => $image3,
                'image4' => $image4
            ]
        );
        if($updatecompanyprofile)
        {
            return  redirect('admin/investment-plan')->with('success', 'Data saved !');
        }
        else
        {
            return redirect('admin/investment-plan')->with('error', 'Data not saved !');
        }
    }
    public function manageIncomePlan()
    {
        $income_plan = DB::table('tbl_business_plan')
        ->where('type',0)
        ->get();
        return view('admin/income_plan')->with('income_plan', $income_plan);
    }
    public function updateIncomePlan(Request $request)
    {
        $request->validate([
            'content'=>'required'
            ]);
        $data = $request->input();
        
        // IMAGE 1 UPLOAD
        
        if($_FILES['file1']['size'] > 0)
        {
            $imageName1 = rand().'.'.Request()->file1->getClientOriginalExtension();
            if(Request()->file1->move(public_path('business_plan'), $imageName1))
            {
                $image1 = $imageName1;
            }
            else
            {
                return redirect('admin/income-plan')->with('error', 'Image can not be blank !');
            }
        }
        else
        {
            $image1 = $data['image1'];
        }
        
        // IMAGE 1 UPLOAD
        
        // IMAGE 2 UPLOAD START
        
        if($_FILES['file2']['size'] > 0)
        {
            $imageName2 = rand().'.'.Request()->file2->getClientOriginalExtension();
    
            if(Request()->file2->move(public_path('business_plan'), $imageName2))
            {
                $image2 = $imageName2;
            }
            else
            {
                return redirect('admin/income-plan')->with('error', 'Image can not be blank !');
            }
        }
        else
        {
            $image2 = $data['image2'];
        }
        
        // IMAGE 2 UPLOAD END
        
        // IMAGE 3 UPLOAD START
        
        if($_FILES['file3']['size'] > 0)
        {
            $imageName3 = rand().'.'.Request()->file3->getClientOriginalExtension();
    
            if(Request()->file3->move(public_path('business_plan'), $imageName3))
            {
                $image3 = $imageName3;
            }
            else
            {
                return redirect('admin/income-plan')->with('error', 'Image can not be blank !');
            }
        }
        else
        {
            $image3 = $data['image3'];
        }
        
        // IMAGE 3 UPLOAD END
        
        // IMAGE 4 UPLOAD START
        
        if($_FILES['file4']['size'] > 0)
        {
            $imageName4 = time().'.'.Request()->file4->getClientOriginalExtension();
    
            if(Request()->file4->move(public_path('business_plan'), $imageName4))
            {
                $image4 = $imageName4;
            }
            else
            {
                return redirect('admin/income-plan')->with('error', 'Image can not be blank !');
            }
        }
        else
        {
            $image4 = $data['image4'];
        }
        
        // IMAGE 4 UPLOAD END
        
        $updatecompanyprofile = DB::table('tbl_business_plan')
        ->where('id', $data['hidden_id'])
        ->update(
            [
                'content' => $data['content'],
                'image1' => $image1,
                'image2' => $image2,
                'image3' => $image3,
                'image4' => $image4
            ]
        );
        if($updatecompanyprofile)
        {
            return  redirect('admin/income-plan')->with('success', 'Data saved !');
        }
        else
        {
            return redirect('admin/income-plan')->with('error', 'Data not saved !');
        }
    }
    public function delete_plan_image(Request $request)
    {
        $id = $request->id;
        $column = $request->column;
        $type = $request->type;
        $updateimage = DB::table('tbl_business_plan')
        ->where('id', $id)
        ->update(
            [
                $column => '',
                'updated_at'=>date('Y-m-d H:i:s')
            ]
        );
        if($updateimage)
        {
            if($type == 1)
            {
                return  redirect('admin/investment-plan')->with('success', 'Image removed !');
            }
            else
            {
               return redirect('admin/income-plan')->with('success', 'Image removed !'); 
            }
        }
        else
        {
            if($type == 1)
            {
                return  redirect('admin/investment-plan')->with('error', 'Something went wrong !');
            }
            else
            {
               return redirect('admin/income-plan')->with('error', 'Something went wrong !'); 
            }
        }
    }
    
    public function update_user_balance($user_id,$amount)
    {
        $user_wallet = DB::table('user_wallet')->select('*')->where('user_id',$user_id)->first();
        if($user_wallet)
        {
            $old_wallet_amount = $user_wallet->wallet_amount;
            $old_yes_amount = $user_wallet->yes_amount;
        }
        else
        {
            $old_wallet_amount = 0;
            $old_yes_amount = 0;
        }
        $new_wallet_amount = $old_wallet_amount+$amount;
        $yes_amount = ($amount*10)/100;
        $new_yes_amount = $old_yes_amount+$yes_amount;
        
        $update_wallet = DB::table('user_wallet')
        ->where('user_id', $user_id)
        ->update(
            [
                'wallet_amount' => $new_wallet_amount,
                'yes_amount' => $new_yes_amount,
                'update_date' => date('Y-m-d H:i:s')
            ]
        );
        if($update_wallet)
        {
            return true;
        }
        else
        {
            return false;
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
    public function couponList()
    {
        $coupon = Coupon::select()
        ->get();
        return view('admin/coupon_list')->with('couponlist', $coupon);
    }
    public function addNewCoupon(Request $request)
    {
        if($request->id)
        {
            $id = $request->id;
            $coupon = Coupon::select()
                ->where('id', $id)
                ->get();
                
            $user = User::select()
            ->where('status', 1)
            ->get();
            
            $coupon_user = DB::table('tbl_coupon_for_user')->select()
                ->where('coupon_id', $id)
                ->get();
            $coupon_user_id = [];
            foreach($coupon_user as $cu)
            {
                array_push($coupon_user_id,$cu->user_id);
            }
            return view('admin/new_coupon')->with('coupon_detail', $coupon)->with('id',$id)->with('user', $user)->with('coupon_user_id', $coupon_user_id);
        }
        else
        {
            $user = User::select()
            ->where('status', 1)
            ->get();
            $coupon_user_id = [];

            return view('admin/new_coupon')->with('id','')->with('user', $user)->with('coupon_user_id', $coupon_user_id);
        }
    }
    public function saveCoupon(Request $request)
    {
        $request->validate([
            'coupon_name'=>'required',
            'description'=>'required',
            'coupon_code'=>'required',
            'coupon_type'=>'required',
            'coupon_val'=>'required',
            'status'=>'required'
        ]);


        $data = $request->input();
        $savecoupon = DB::table('coupon')->insertGetId(
            [
             'coupon_name' => $data['coupon_name'],
             'status' => $data['status'],
             'description' => $data['description'],
             'coupon_code' => $data['coupon_code'],
             'coupon_type' => $data['coupon_type'],
             'coupon_val' => $data['coupon_val'],
             'for_all_user' => $data['for_all_user'],
             'created_at'=>date('Y-m-d h:i:s'),
             'updated_at'=>date('Y-m-d h:i:s')
             ]
        );
        if($savecoupon)
        {
            if($data['for_all_user'] == 1 && count($data['user_id']) > 0)
            {
                foreach($data['user_id'] as $u)
                {
                    DB::table('tbl_coupon_for_user')->insert(
                        [
                         'coupon_id' => $savecoupon,
                         'user_id' => $u,
                         'created_at'=>date('Y-m-d H:i:s'),
                         'updated_at'=>date('Y-m-d H:i:s')
                         ]
                    );
                }
            }
            return  redirect('admin/add-new-coupon')->with('success', 'Data saved !');
        }
        else
        {
             return redirect('admin/add-new-coupon')->with('error', 'Data not saved !');
        }
    }
    public function updateCoupon(Request $request)
    {
        $request->validate([
            'coupon_name'=>'required',
            'description'=>'required',
            'coupon_code'=>'required',
            'coupon_type'=>'required',
            'coupon_val'=>'required',
            'coupon_id'=>'required',
            'status'=>'required'
        ]);


        $data = $request->input();
        $updatecoupon = DB::table('coupon')->where('id', $data['coupon_id'])->update(
            [
             'coupon_name' => $data['coupon_name'],
             'status' => $data['status'],
             'description' => $data['description'],
             'coupon_code' => $data['coupon_code'],
             'coupon_type' => $data['coupon_type'],
             'coupon_val' => $data['coupon_val'],
             'updated_at'=>date('Y-m-d h:i:s')
             ]
        );
        if($updatecoupon)
        {
            if($data['for_all_user'] == 1 && count($data['user_id']) > 0)
            {
                DB::table('tbl_coupon_for_user')
                ->where('coupon_id', $data['coupon_id'])
                ->delete();
                
                foreach($data['user_id'] as $u)
                {
                    DB::table('tbl_coupon_for_user')->insert(
                        [
                         'coupon_id' => $data['coupon_id'],
                         'user_id' => $u,
                         'created_at'=>date('Y-m-d H:i:s'),
                         'updated_at'=>date('Y-m-d H:i:s')
                        ]
                    );
                }
            }
            return  redirect('admin/edit-coupon/'.$data['coupon_id'])->with('success', 'Data updated !');
        }
        else
        {
             return redirect('admin/edit-coupon/'.$data['coupon_id'])->with('error', 'Data not update !');
        }
    }
    public function deleteCoupon(Request $request)
    {
        $data = $request->input();
        $coupon_id = $data['couponid'];
        $deletecoupon = DB::table('coupon')
                ->where('id', $coupon_id)
                ->delete();
        if($deletecoupon)
        {
            DB::table('tbl_coupon_for_user')
                ->where('coupon_id', $coupon_id)
                ->delete();
            return "Changed";
        }
        else
        {
             return "Failed";
        }
    }

}
