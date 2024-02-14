<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DateTime;
use Exception;

class FileUploadController extends Controller
{
    public function index()
    {

        return view('xml.upload');
    }


    //validation with upload
    public function BarFormUpload(Request $request)
    {
        try {
            // Comment out the validation for now
            $request->validate([
                'BarForm.*' => 'required|mimes:xml',
            ]);
            
            
    
            foreach ($request->file('BarForm') as $file) {
                $filename = $file->getClientOriginalName();
    
                if (strpos($filename, '_FORM_XML_A.xml') !== false) {
                    // Store the file in the 'xml/bar' directory
                    $originalPath = $file->storeAs('xml/bar', $filename, 'local');
    
                    // Copy the file to another location
                    $copyPath = $file->storeAs('xml/bar_copy', $filename, 'local');
    
                    // Additional processing or save file paths to the database here
                } else {
                    // If the file name doesn't match the desired suffix, return an error
                    return back()->withErrors(['BarForm' => 'Invalid file name.'])->with('BarForm_upload_status', 0);
                }
            }
    
            return back()->with('BarForm_upload_status', 1);
        } catch (\Exception $e) {
            return back()->with('BarForm_upload_status', 0);
        }
    }
    
    


    public function deleteBarForm(Request $request)
    {
        try {
            $fileName = $request->input('file_name'); // Assuming you pass the file name to be deleted in the request

            // Delete file from storage
            Storage::delete("xml/bar/{$fileName}");

            // Additional logic, if needed, to update database or perform other actions

            return back()->with('BarForm_delete_status', 1);
        } catch (Exception $e) {
            return back()->with('BarForm_delete_status', 0);
        }
    }

}
