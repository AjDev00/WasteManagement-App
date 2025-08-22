<?php

namespace App\Http\Controllers;

use App\Models\TempImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TempImageController extends Controller
{
    public function store(Request $request){
        //validate the image field, make it required.
        $validate = Validator::make($request->all(), [
            'picture' => 'required|image'
        ]);

        //check if validation fails.
        if($validate->fails()){
            return response()->json([
                'status'  => false,
                'message' => 'Please upload an image',
                'error'   => $validate->errors()
            ]);
        }

        //getting image extension and creating a dynamic name for every image.
        $image = $request->file('picture');
        $ext = $image->getClientOriginalExtension();
        $imageName = time().'.'.$ext;

        //create an object instance of the temp image model and save into the DB.
        $tempImage = new TempImage();
        $tempImage->name = $imageName;
        $tempImage->save();

        //every uploaded image should be moved to the temp folder in the publicpath directory.
        $image->move(public_path('uploads/temp'), $imageName);

        return response()->json([
            'status' => true,
            'message' => 'Image uploaded successfully',
            'image' => $tempImage
        ]);
    }
}
