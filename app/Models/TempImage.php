<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class TempImage extends Model
{
    use HasFactory;

    public function store(Request $request){
        //validate the image field, make it required.
        $validate = Validator::make($request->all(), [
            'image' => 'required|image'
        ]);

        //check if validation fails.
        if($validate->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please upload an image',
                'error' => $validate->errors()
            ]);
        }

        //getting image extension and creating a dynamic name for every image.
        $image = $request->file('image');
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
