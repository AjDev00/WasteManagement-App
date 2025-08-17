<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Notification;
use App\Models\TempImage;
use App\Models\Type;
use App\Models\WasteInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CollectionController extends Controller
{
    //store a newly created collection with waste invoice.
    public function storeCollection(Request $request){
        //validate the collection data.
        $collectionData = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'location_id' => 'required|exists:locations,id',
            'pickup_on' => 'required|date',
            'accepted_by' => 'required|exists:residents,id',
            'invoices' => 'required|array|min:1',
            'invoices.*.type_id' => 'required|exists:types,id',
            'invoices.*.kg' => 'required|numeric|min:0.1',
            'invoices.*.description' => 'nullable|string',
            'invoices.*.created_by' => 'required|exists:residents,id',
            // 'invoices.*.picture' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        return DB::transaction(function () use($collectionData, $request) {
            //insert the collection data.
            $collection = Collection::create([
                'resident_id' => $collectionData['resident_id'],
                'location_id' => $collectionData['location_id'],
                'accepted_by' => $collectionData['accepted_by'],
                'pickup_on' => $collectionData['pickup_on'],
                'amount_total' => 0
            ]);

            $totalAmount = 0;

            //loop through each collectionData invoices as invoiceData - allows dropping of multiple invoices in the DB.
            foreach($collectionData['invoices'] as $index => $invoiceData){
                $type = Type::find($invoiceData['type_id']); //find the type of waste with the invoice data type_id
                $amount = $invoiceData['kg'] * $type->min_price_per_kg;


                $wasteInvoice = WasteInvoice::create([
                    'collection_id' => $collection->id,
                    'type_id' => $invoiceData['type_id'],
                    'kg' => $invoiceData['kg'],
                    'created_by' => $invoiceData['created_by'],
                    'description' => $invoiceData['description'],
                    'status' => 'pending',
                    'amount' => $amount
                ]);

                // if ($request->hasFile("invoices.$index.picture")) {
                //     $file = $request->file("invoices.$index.picture");
                //     $imageName = time() . '-' . $wasteInvoice->id . '.' . $file->getClientOriginalExtension();
                //     $file->move(public_path('uploads/invoices'), $imageName);

                //     $wasteInvoice->update(['picture' => $imageName]);
                // }
                //save image.
                $tempImage = TempImage::find($request->image_id);

                if($tempImage != null){
                    $imageExtArray = explode('.', $tempImage->name); //seperates the image name and it's extension.
                    $ext = last($imageExtArray); //save the extension in a variable.
                    $imageName = time().'-'.$wasteInvoice->id.'.'.$ext; //create a unique name for the image with the time function followed by the blog id and the previously saved extension.

                    $wasteInvoice->picture = $imageName;
                    $wasteInvoice->save();

                    //move image from a temporary directory to a permanent directory with the new image name.
                    $sourcePath = public_path('uploads/temp/'.$tempImage->name);
                    $destinationPath = public_path('uploads/invoices/'.$imageName);

                    File::copy($sourcePath, $destinationPath); //copies the image from the source path to the destination path.
                }
                
                $totalAmount += $amount;
            }

            //update the amount total column in the collection table.
            $collection->update(['amount_total' => $totalAmount]);

            //household notification
            Notification::create([
                'resident_id'        => $collection->resident_id,
                'waste_collector_id' => null,
                'title'              => 'Waste Added',
                'message'            => "You added waste. Your request has been received and is pending assignment to a picker. Thank you for helping keep your community clean!",
                'message_type'       => 'waste_added',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Successfully',
                'collection' => $collection->fresh(),
                'waste_invoice' => $wasteInvoice
            ]);
        });
    }

    //update waste_collector_id in the collections table.
    public function attachPicker(Request $request, Collection $collection)
    {
        $request->validate([
            'waste_collector_id' => 'required|exists:waste_collectors,id',
            'pickup_on'          => 'required|date',
            'delivered_on'       => 'required|date'
        ]);

        DB::transaction(function () use ($request, $collection) {
            //update the collection
            $collection->update([
                'waste_collector_id' => $request->waste_collector_id,
                'pickup_on'          => $request->pickup_on,
                'delivered_on'       => $request->delivered_on,
                'status'             => 'picker assigned'
            ]);

            //refresh to make sure it has the updated waste_collector_id
            $collection->refresh();

            //household notification
            Notification::create([
                'resident_id'        => $collection->resident_id,
                'waste_collector_id' => null,
                'title'              => 'Picker Assigned',
                'message'            => "You assigned a picker. Pickup: {$request->pickup_on}, Delivery: {$request->delivered_on}.",
                'message_type'       => 'picker_assigned',
            ]);

            //picker notification
            Notification::create([
                'resident_id'        => null,
                'waste_collector_id' => $collection->waste_collector_id,
                'title'              => 'New Waste Collection Assigned',
                'message'            => "A new collection has been assigned to you. Pickup: {$request->pickup_on}, Delivery: {$request->delivered_on}.",
                'message_type'       => 'picker_assigned',
            ]);
        });

        return response()->json([
            'status'    => true,
            'message'   => 'Picker assigned successfully',
            'notification' => 'You have a new notification',
            'data'      => $collection->fresh()
        ]);
    }

    // Route for updating a collection by ID
    public function updateCollection(Request $request, $id){
        $collection = Collection::find($id);

        if($collection === null){
            return response()->json([
                'status' => false,
                'message' => 'collection not found'
            ]);
        }

        $validator = Validator::make($request->all(), [
           'resident_id' => 'required|exists:residents,id',
            'waste_collector_id' => 'required|exists:waste_collectors,id',
            'amount' => 'required',
            'location_id' => 'required|exists:locations,id',
            'pickup_on' => 'required',
            'delivered_on' => 'required',
            'accepted_by' => 'required',
            'picture' => 'required',
            'summary' => 'required',
        ]);

        //error handling
        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        // Update the location
        $collection->resident_id = $request->resident_id;
        $collection->waste_collector_id = $request->waste_collector_id;
        $collection->amount = $request->amount;
        $collection->location_id = $request->location_id;
        $collection->pickup_on = $request->pickup_on;
        $collection->delivered_on = $request->delivered_on;
        $collection->accepted_by = $request->accepted_by;
        $collection->picture = $request->picture;
        $collection->summary = $request->summary;
        $collection->save();

        return response()->json([
            'status' => true,
            'message' => 'Supervisor updated successfully',
            'data' => $collection
        ]);
    }
}
