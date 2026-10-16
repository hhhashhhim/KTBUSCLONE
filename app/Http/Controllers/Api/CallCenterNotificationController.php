<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CallCenterScope as Scope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallCenterNotificationController extends Controller
{
    private function inbox(Request $request)
    {
        $user = $request->user();
        abort_unless($user->company_id && Scope::allows($user, 'call-center') && Scope::allows($user, 'call-center-view-complaints'), 403, 'Complaint viewing permission required.');
        // Recheck live access: reassignment, role, terminal or route changes must not leak a complaint.
        $scope = Request::create('/');
        $scope->setUserResolver(function () use ($user) { return $user; });
        return DB::table('call_center_notifications')->where('company_id', $user->company_id)
            ->where('recipient_id', $user->id)->whereNull('superseded_at')
            ->whereIn('call_id', Scope::calls($scope)->where('category', 'C')->where('assigned_to', $user->id)->select('id'));
    }

    public function index(Request $request)
    {
        $request->validate(['page' => 'nullable|integer|min:1']);
        $query = $this->inbox($request);
        $unread = (clone $query)->whereNull('read_at')->count();
        $items = $query->orderByDesc('id')->paginate(20);
        $actors = User::where('company_id', $request->user()->company_id)
            ->whereIn('id', $items->getCollection()->pluck('actor_id'))->pluck('name', 'id');
        $items->setCollection($items->getCollection()->map(function ($item) use ($actors) {
            $reference = 'CC-' . $item->call_id;
            return ['id' => $item->id, 'call_id' => $item->call_id, 'reference' => $reference,
                'message' => 'Complaint ' . $reference . ' assigned to you by ' . ($actors[$item->actor_id] ?? 'a former user') . '.',
                'created_at' => $item->created_at, 'read_at' => $item->read_at];
        }));
        return response()->json(['status' => true, 'data' => ['unread_count' => $unread, 'notifications' => $items]]);
    }

    public function read(Request $request, int $id)
    {
        $query = $this->inbox($request)->where('id', $id);
        abort_unless((clone $query)->exists(), 404);
        // Opening twice is harmless and preserves the first-read time.
        $query->whereNull('read_at')->update(['read_at' => Carbon::now('Asia/Karachi')->format('Y-m-d H:i:s')]);
        return response()->json(['status' => true, 'data' => ['id' => $id]]);
    }
}
