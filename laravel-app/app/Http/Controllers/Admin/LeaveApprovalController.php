<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Odoo\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveApprovalController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService,
    ) {}

    /**
     * Get all pending leave requests across the organisation.
     */
    public function pending(Request $request)
    {
        try {
            $pendingLeaves = $this->leaveService->getPendingLeaves();

            return view('admin.leaves', [
                'leaves' => $pendingLeaves,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch pending leaves: ' . $e->getMessage());
        }
    }

    /**
     * Approve a pending leave request.
     */
    public function approve(int $id)
    {
        try {
            $result = $this->leaveService->approveLeave($id);
            return response()->json([
                'success' => true,
                'message' => 'Leave request approved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a pending leave request with a reason.
     */
    public function reject(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $result = $this->leaveService->rejectLeave($id, $validated['reason']);
            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject leave request: ' . $e->getMessage()
            ], 500);
        }
    }
}
