<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Notification;
use App\Models\TempImage;
use App\Models\Type;
use Carbon\Carbon;
use App\Models\WasteInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
            'invoices.*.kg' => 'required|numeric|min:1',
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
                
                //save image.
                $imageId = $invoiceData['image_id'] ?? null;

                if (!empty($imageId)) {
                    $tempImage = TempImage::find($imageId);

                    if ($tempImage) {
                        // determine extension safely
                        $ext = pathinfo($tempImage->name, PATHINFO_EXTENSION);
                        $imageName = time() . '-' . $wasteInvoice->id . '.' . $ext;

                        // ensure destination directory exists
                        $destDir = public_path('uploads/invoices');
                        if (!File::exists($destDir)) {
                            File::makeDirectory($destDir, 0755, true);
                        }

                        $sourcePath = public_path('uploads/temp/' . $tempImage->name);
                        $destinationPath = $destDir . '/' . $imageName;

                        // only proceed if source file exists
                        if (File::exists($sourcePath)) {
                            // move the file from temp to permanent (use move to avoid stale files)
                            try {
                                File::move($sourcePath, $destinationPath);
                            } catch (\Exception $e) {
                                // fallback to copy then unlink
                                File::copy($sourcePath, $destinationPath);
                                File::delete($sourcePath);
                            }

                            // update invoice record with picture name
                            $wasteInvoice->picture = $imageName;
                            $wasteInvoice->save();

                            // optional: delete the TempImage DB record if you don't need it
                            // $tempImage->delete();
                        } else {
                            // log or handle missing source file
                            Log::warning("Temp image file not found: " . $sourcePath . " for tempImage id: " . $imageId);
                        }
                    }
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

    //view a single collection with id.
    public function viewCollection($id){
        $collection = Collection::with('wasteInvoices')->find($id);

        if(!$collection){
            return response()->json([
                'status' => false,
                'message' => 'Collection not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $collection
        ]);
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
                'message'            => "You waste pickup order has been verified and a picker has been assigned to come pick up your waste. Pickup: {$request->pickup_on}, Delivery: {$request->delivered_on}.",
                'message_type'       => 'picker_assigned',
            ]);

            //update status of waste from pending to verified.
            WasteInvoice::where('collection_id', $collection->id)
            ->update(['status' => 'verified']);

            //picker notification
            Notification::create([
                'resident_id'        => null,
                'waste_collector_id' => $collection->waste_collector_id,
                'title'              => 'New Waste Collection Assigned',
                'message'            => "A new waste collection has been assigned to you. Pickup: {$request->pickup_on}, Delivery: {$request->delivered_on}.",
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

    //get waste within a particular waste invoice.
    public function newRequests()
    {
        $from = Carbon::now()->subHours(5); //5 hours ago
        $to   = Carbon::now()->subSecond(); //1 second ago

        $collections = Collection::with(['wasteInvoices' => function ($query) use ($from, $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }])
        ->where('status', 'awaiting picker') //only collections still awaiting picker
        ->whereHas('wasteInvoices', function ($query) use ($from, $to) {
            $query->whereBetween('created_at', [$from, $to]);
        })
        ->get();

        if($collections->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'No new requests found',
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'New requests fetched successfully',
            'data'    => $collections
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
