<?php

namespace App\Http\Controllers;

use App\Models\MaterialRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MaterialApprovalController extends Controller
{
    public function approval()
    {
        $query = MaterialRequest::with('items')
        ->where('status', 'pending')
        ->orderBy('created_at', 'desc');
    
        // Ambil filter dari request
        $startDate = request('start_date');
        $endDate = request('end_date');
    
        // Terapkan filter jika tersedia
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        } elseif ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }
        $approval = $query->get();
    
        return view('user.material_approval', compact('approval', 'startDate', 'endDate'));
    }

    // CHECKER APPROVE
    public function checkerApprove($id)
    {
        $request = MaterialRequest::findOrFail($id);
        $request->checker = Auth::user()->user_fullname . ' (approved)';
        $request->checker_at = now();
        $request->approved = 'waiting'; // trigger tahap approver
        $request->save();

        return redirect()->back()->with('success', 'Request approved by checker.');
    }

    // CHECKER REJECT
    public function checkerReject($id)
    {
        $request = MaterialRequest::findOrFail($id);
        $request->checker = Auth::user()->user_fullname . ' (rejected)';
        $request->checker_at = now();
        $request->approved = 'rejected';
        $request->status = 'rejected';
        $request->save();

        return redirect()->back()->with('error', 'Request rejected by checker.');
    }

    // APPROVER APPROVE
    public function approverApprove($id)
    {
        $request = MaterialRequest::findOrFail($id);
        $request->approved =  Auth::user()->user_fullname . ' (approved)';
        $request->approved_at = now();
        $request->approved =  Auth::user()->user_fullname . ' (approved)';
        $request->status = 'approved';
        $request->save();

        return redirect()->back()->with('success', 'Request fully approved.');
    }

    // APPROVER REJECT
    public function approverReject($id)
    {
        $request = MaterialRequest::findOrFail($id);
        $request->approved_by = Auth::user()->user_fullname . ' (rejected)';
        $request->approved_at = now();
        $request->approved = 'rejected';
        $request->status = 'rejected';
        $request->save();

        return redirect()->back()->with('error', 'Request rejected by approver.');
    }
}
