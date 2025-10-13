<?php

namespace App\Http\Controllers;

use App\Models\DepositWaste;
use App\Models\Notification;
use App\Models\TempImage;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DepositWasteController extends Controller
{
    //store a newly created collection with waste invoice.
    public function storeDepositWaste(Request $request){
        //validate the deposit waste data.
        $depositData = $request->validate([
            'invoices' => 'required|array|min:1',
            'invoices.*.recycler_company_id' => 'required|exists:recycler_companies,id',
            'invoices.*.waste_collector_id' => 'required|exists:waste_collectors,id',
            'invoices.*.deposited_at' => 'required|date',
            'invoices.*.type_id' => 'required|exists:types,id',
            'invoices.*.kg' => 'required|numeric|min:1',
            'invoices.*.picture' => 'nullable|string'
        ]);

        return DB::transaction(function () use($depositData, $request) {

            //loop through each depositData invoices as invoiceData - allows dropping of multiple invoices in the DB.
            foreach($depositData['invoices'] as $index => $invoiceData){
                $deposit = DepositWaste::create([
                    'waste_collector_id' => $invoiceData['waste_collector_id'],
                    'recycler_company_id' => $invoiceData['recycler_company_id'],
                    'deposited_at' => $invoiceData['deposited_at'],
                    'type_id' => $invoiceData['type_id'],
                    'kg' => $invoiceData['kg'],
                    'picture'  => $invoiceData['picture'],
                ]);
                
                //save image.
                $imageId = $invoiceData['image_id'] ?? null;

                if (!empty($imageId)) {
                    $tempImage = TempImage::find($imageId);

                    if ($tempImage) {
                        // determine extension safely
                        $ext = pathinfo($tempImage->name, PATHINFO_EXTENSION);
                        $imageName = time() . '-' . $deposit->id . '.' . $ext;

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

                            $deposit->picture = $imageName;
                            $deposit->save();
                        } else {
                            // log or handle missing source file
                            Log::warning("Temp image file not found: " . $sourcePath . " for tempImage id: " . $imageId);
                        }
                    }
                }  
            }

            //recycler notification
            // Notification::create([
            //     'resident_id'        => null,
            //     'waste_collector_id' => null,
            //     'recycler_company_id'=> $depositData->recycler_company_id,
            //     'title'              => 'Waste is coming',
            //     'message'            => "A picker chose your company to come deposit his waste. Happy staying clean!",
            //     'message_type'       => 'waste_added',
            // ]);

            return response()->json([
                'status' => true,
                'message' => 'Successfully',
                'data' => $deposit
            ]);
        });
    }

    //show all waste deposited to a company.
    public function displayDepositWaste($recycler_company_id){
        $deposit_waste = DepositWaste::where('recycler_company_id', $recycler_company_id)->get();

        if($deposit_waste->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No waste has been deposited with you!'
            ]);   
        } else{
            return response()->json([
                'status' => true,
                'data' => $deposit_waste
            ]);
        }
    }
}
