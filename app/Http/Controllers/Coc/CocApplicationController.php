<?php

namespace App\Http\Controllers\Coc;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; 
use App\Models\CocDocuments;
use App\Models\CocApplication;
use App\Models\CocAttachment;
use Auth;
use Carbon\Carbon;



class CocApplicationController extends Controller
{
  public function get_document(){
    $data = CocDocuments::all();
    return response()->json($data);
  }

  public function create(Request $req){
    $app_number = 'APP-' . rand(100000, 999999); // e.g., APP-123456
    $user_id = Auth::user()->id;
    $date_issued = Carbon::parse($req->date_issued);
    $date_end = Carbon::parse($req->date_end);
    $validated = array(
      'app_number'=> $app_number,
      'rpo_number'=> $req -> rpo_number,
      'balance' => null,
      'hours' => $req->hours,
      'date_issued' => $date_issued,
      'date_end' => null,
      'remarks' => $req->remarks,
      'user_id' => $user_id,
      'status' => 1,
      'coc_documents_id' => 1,
      'remarks' => $req->remarks,
    );
    $cocform = CocApplication::create($validated);
    if($cocform){
      return response()->json(['success' => true, 'app_number' => $app_number, 'message' => 'Application successfully added.']);
    }else{
      return response()->json(['success' => false, 'message' => 'Failed to save application.'], 400);
    }
  }
  public function edit($app_number){
    $applicationData = CocApplication::where('app_number', $app_number)->first();
    if ($applicationData) {
      return response()->json([
        'success' => true,
        'message' => 'Application successfully retrieved.',
        'applications' => $applicationData,
      ]);
    } else {
      return response()->json([
        'success' => false,
        'message' => 'Application not found.',
      ], 404);
    }
  }


  public function updateData(Request $req)
{
    // Validate input data
    $req->validate([
        'app_number' => 'required|string',
        'rpo_number' => 'required|string',
        'hours' => 'required|numeric',
        'date_issued' => 'required|date',
        'date_end' => 'nullable|date',
        'remarks' => 'nullable|string',
        'coc_documents_id' => 'required|array',
        'coc_documents_id.*' => 'exists:coc_documents,id', // Validate each document ID exists
        'documents' => 'required|array',
        'documents.*' => 'file|max:2048',
    ]);

    // Begin transaction
    DB::beginTransaction();

    try {
        // Find and update `CocApplication`
        $applicationData = CocApplication::where('app_number', $req->app_number)->first();
        if (!$applicationData) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found. Please contact administrator.',
            ], 404);
        }

        $applicationData->update([
            'rpo_number' => $req->rpo_number,
            'hours' => $req->hours,
            'date_issued' => $req->date_issued,
            'date_end' => $req->date_end,
            'remarks' => $req->remarks,
        ]);

        // Update existing attachments or create new ones if necessary
        foreach ($req->documents as $index => $document) {
            $attachment = CocAttachment::where('app_number', $req->app_number)
                ->where('coc_documents_id', $req->coc_documents_id[$index])
                ->first();

            if ($attachment) {
                // Update existing attachment
                $attachment->update([
                    'file_data' => file_get_contents($document),
                    'file_name' => $document->getClientOriginalName(),
                ]);
            } else {
                // Create new attachment if it doesn't exist
                CocAttachment::create([
                    'app_number' => $req->app_number,
                    'coc_documents_id' => $req->coc_documents_id[$index],
                    'file_data' => file_get_contents($document),
                    'file_name' => $document->getClientOriginalName(),
                ]);
            }
        }

        // Commit transaction
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Application and attachments successfully updated',
            'applicationData' => $applicationData,
        ]);
    } catch (\Exception $e) {
        // Rollback transaction on error
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while updating data. Please try again.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

  


  

  public function uploadAttachments(Request $request){
    $request->validate([
      'coc_documents_id' => 'required|array',
      'documents' => 'required|array',
      'documents.*' => 'file|max:2048',
    ]);
    foreach ($request->documents as $index => $document) {
      CocAttachment::create([
        'app_number' => $request->app_number,
        'coc_documents_id' => $request->coc_documents_id[$index], // Match file to its ID
        'file_data' => file_get_contents($document),
        'file_name' => $document->getClientOriginalName(),
      ]);
    }
    return response()->json(['success' => true, 'message' => 'Files uploaded successfully']);
  }

  //TableData
  public function getUserCOC(){
    try {
      $user_id = Auth::id(); // Auth::id() is simpler and directly retrieves the logged-in user ID
      $coc = CocApplication::where('user_id', $user_id)->orderBy('date_issued','desc')->get();
      if ($coc->isEmpty()) {
        return response()->json([
          'success' => false,
          'message' => 'No COC found for this user.',
        ], 404);
      }
      return response()->json([
        'success' => true,
        'data' => $coc,
      ]);
    } catch (\Exception $e) {
        // Handle unexpected errors
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while retrieving COC data.',
            'error' => $e->getMessage(), // Optional: include the error for debugging
        ], 500);
      }
  }

  public function getCOCWithAttachments($app_number)
{
    // Load CocApplication with attachments and their related documents
    $cocApplication = CocApplication::with(['attachments.document'])
        ->where('app_number', $app_number)
        ->first();

    if ($cocApplication) {
        $attachments = $cocApplication->attachments->map(function ($attachment) {
            return [
                'documentType' => $attachment->document->documents_name ?? 'Unknown Document',
                'files' => $attachment->file_name,
                'file_data' => base64_encode($attachment->file_data),
                'existingFileName' => $attachment->file_name,
                'documentId' => $attachment->id,
            ];
        });

        return response()->json([
            'success' => true,
            'applicationData' => [
                'app_number' => $cocApplication->app_number,
            ],
            'attachments' => $attachments,
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Application not found.',
    ], 404);
}

  


}




