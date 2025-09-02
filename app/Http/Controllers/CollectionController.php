<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Earning;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Resident;
use App\Models\TempImage;
use App\Models\Type;
use App\Models\WasteCollector;
use Carbon\Carbon;
use App\Models\WasteInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
            'invoices.*.picture' => 'nullable|string'
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
                    'picture'  => $invoiceData['picture'],
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
            // Notification::create([
            //     'resident_id'        => $collection->resident_id,
            //     'waste_collector_id' => null,
            //     'title'              => 'Waste Added',
            //     'message'            => "You added waste. Your request has been received and is pending assignment to a picker. Thank you for helping keep your community clean!",
            //     'message_type'       => 'waste_added',
            // ]);

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
        $collection = Collection::with(['wasteInvoices', 'location'])->find($id);

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

    public function filterCollectionViaLocation($locationName)
    {
        // Get all matching locations
        $locations = Location::where('city', $locationName)->pluck('id');

        // Fetch collections linked to any of those locations
        $collection = Collection::whereIn('location_id', $locations)
            ->with(['wasteInvoices', 'location'])
            ->where('status', 'awaiting picker')
            ->latest()
            ->get();

        if ($collection->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No collection with this location'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $collection
        ]);
    }

    //view a all collection.
    public function viewAllCollections(){
        $collections = Collection::with(['wasteInvoices', 'location'])
                        ->where('status', 'awaiting picker')
                        ->latest()
                        ->get();

        if($collections->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No collections found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $collections
        ]);
    }

    //update waste_collector_id in the collections table.
    public function attachPicker(Request $request, Collection $collection)
    {
        $request->validate([
            'waste_collector_id' => 'required|exists:waste_collectors,id',
            'pickup_on'          => 'required|date',
            'pickup_on_time'     => 'required'
        ]);

        try {
            DB::transaction(function () use ($request, $collection) {
                //update the collection
                $collection->update([
                    'waste_collector_id' => $request->waste_collector_id,
                    'pickup_on'          => $request->pickup_on,
                    'pickup_on_time'     => $request->pickup_on_time,
                    'status'             => 'picker assigned'
                ]);

                //refresh to make sure it has the updated waste_collector_id
                $collection->refresh();

                //get picker id and name.
                $pickerId = $collection->waste_collector_id;
                $pickerName = WasteCollector::find($pickerId);

                //get resident's location.
                $residentLocationId = $collection->location_id;
                $location = Location::find($residentLocationId);

                $resident = $collection->resident_id;
                
                $referenceNo = strtoupper(Str::uuid()->toString());
                $referenceNo = substr($referenceNo, 0, 5);

                //get resident’s current total before this assignment
                $previousTotal = Earning::where('resident_id', $resident)->sum('total_earning');

                $previousTotal1 = Earning::where('waste_collector_id', $pickerId)->sum('total_earning');

                //create earning for resident.
                Earning::create([
                    'resident_id'        => $collection->resident_id,
                    'waste_collector_id' => null,
                    'collection_id'      => $collection->id,
                    'earning'            => 0, // no payment yet
                    'total_earning'      => $previousTotal, //snapshot of total at assignment time
                    'reference_no'       => $referenceNo,
                    'authorized_by'      => null, //not yet authorized
                    'total_kg'           => null,
                    'used_at'            => null,
                ]);

                //create earning for picker.
                Earning::create([
                    'resident_id'        => null,
                    'waste_collector_id' => $pickerId,
                    'collection_id'      => $collection->id,
                    'earning'            => 0, // no payment yet
                    'total_earning'      => $previousTotal1, //snapshot of total at assignment time
                    'reference_no'       => $referenceNo,
                    'authorized_by'      => null, //not yet authorized.
                    'total_kg'           => null,
                    'used_at'            => null,
                ]);

                //household notification
                Notification::create([
                    'resident_id'        => $collection->resident_id,
                    'waste_collector_id' => null,
                    'title'              => 'Picker Assigned',
                    'message'            => "You waste pickup order has been verified and a picker has been assigned to come pick up your waste. 
                                            Pickup Date: {$request->pickup_on}, Pickup Time: {$request->pickup_on_time}.
                                            Picker's Name: {$pickerName->firstname} {$pickerName->lastname}.
                                            Picker's Email: {$pickerName->email}.
                                            Picker's Phone: {$pickerName->phone_number}.
                                            Waste Ref No: {$collection->id}.
                                            Verification Ref No: {$referenceNo}.",
                    'message_type'       => 'picker_assigned',
                ]);

                //update status of waste from pending to verified.
                WasteInvoice::where('collection_id', $collection->id)
                ->update(['status' => 'verified']);
                
                $residentName = Resident::find($resident);

                //picker notification
                Notification::create([
                    'resident_id'        => null,
                    'waste_collector_id' => $collection->waste_collector_id,
                    'title'              => 'Collection Assigned',
                    'message'            => "A new waste collection has been assigned to you.
                                            Pickup: {$request->pickup_on}, Pickup Time: {$request->pickup_on_time}.
                                            Resident's Name: {$residentName->fullname}.
                                            Resident's Email: {$residentName->email}.
                                            Resident's Phone: {$residentName->phone_number}.
                                            Resident's Location: {$location->title}, {$location->country}, {$location->state}, {$location->city}.
                                            Waste Ref No: {$collection->id}.",
                    'message_type'       => 'picker_assigned',
                ]);
            });

            return response()->json([
                'status'    => true,
                'message'   => 'Picker assigned successfully',
                'notification' => 'You have a new notification',
                'data'      => $collection->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    //get waste within a particular waste invoice.
    public function newRequests()
    {
        $from = Carbon::now()->subHours(5); //5 hours ago
        $to   = Carbon::now()->subSecond(); //1 second ago

        $collections = Collection::with(['wasteInvoices', 'location' => function ($query) use ($from, $to) {
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

    //get ongoing invoices.
    public function onGoing($waste_collector_id){
        $collection = Collection::where('status', 'picker assigned')
            ->with(['wasteInvoices', 'location'])
            ->where('waste_collector_id', $waste_collector_id)
            ->latest()
            ->get();

        if ($collection->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'No ongoing collections found',
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Ongoing collections fetched successfully',
            'data'    => $collection
        ]);
    }

    //get completed invoices.
    public function completed($waste_collector_id){
        $collection = Collection::where('status', 'completed')
            ->with(['wasteInvoices', 'location'])
            ->where('waste_collector_id', $waste_collector_id)
            ->latest()
            ->get();

        if ($collection->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'No ongoing collections found',
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Completed collections fetched successfully',
            'data'    => $collection
        ]);
    }

    //cancel picker assignment.
    public function cancelAssignment($id, Collection $collection){
        $collection = Collection::find($id);
        $wci = $collection->waste_collector_id;

        WasteInvoice::where('collection_id', $id)->update(['status' => 'pending']);

        $collection->waste_collector_id = null;
        $collection->status = 'awaiting picker';
        $collection->pickup_on_time = null;
        $collection->save();

        //refresh to make sure it has the updated waste_collector_id
        $collection->refresh();

        //household notification
        Notification::create([
            'resident_id'        => $collection->resident_id,
            'waste_collector_id' => null,
            'title'              => 'Picker Cancelled',
            'message'            => "You waste pickup order was cancelled and status now back to pending. We are sorry for the inconvenience.
                                    Waste Ref No: {$collection->id}",
            'message_type'       => 'picker_cancelled',
        ]);

        //picker notification
        Notification::create([
            'resident_id'        => null,
            'waste_collector_id' => $wci,
            'title'              => 'You Cancelled',
            'message'            => "You cancelled a waste pickup order. Your assigned resident has been notified.
                                    Waste Ref No: {$collection->id}",   
            'message_type'       => 'picker_cancelled',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Collection assignment cancelled successfully',
            'data' => $collection
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
