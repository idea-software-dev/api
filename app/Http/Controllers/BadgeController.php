<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    /**
     * Display a listing of the badges.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $data = [];
        $badges = Badge::where('enabled', 1)
            ->where('dev_status', 'live')
            ->where('is_event', '0')
            ->orderBy('category', 'ASC')
            ->orderBy('award', 'ASC')
            ->get()
        ;
        foreach ($badges as $badge) {
            $award = 'Bronze';

            switch ($badge->award) {
                case 1: $award = 'Bronze';

                    break;

                case 2: $award = 'Silver';

                    break;

                case 3: $award = 'Gold';

                    break;
            }
            $url = 'https://idea.org.uk/badge/'.$badge->url_handle;
            $data[] = [
                'uid' => $badge->id,
                'status' => $badge->dev_status,
                'name' => $badge->name,
                'description' => $badge->description,
                'category' => ucwords($badge->category),
                'icon' => $badge->icon_url,
                'completed_icon' => $badge->completed_icon_url,
                'award' => $award,
                'url' => $url,
            ];
        }

        return response()->json(['badges' => $data], 200);
    }

    // Incomplete methods for CRUD operations
    public function show($id)
    {
        $badge = Badge::findOrFail($id);

        return response()->json($badge);
    }

    // Incomplete methods for CRUD operations
    public function store(Request $request)
    {
        // $badge = Badge::create($request->all());

        return response()->json($badge, 201);
    }

    // Incomplete methods for CRUD operations
    public function update(Request $request, $id)
    {
        $badge = Badge::findOrFail($id);
        // $badge->update($request->all());

        return response()->json($badge);
    }

    // Incomplete methods for CRUD operations
    public function destroy($id)
    {
        // Badge::destroy($id);

        return response()->json(null, 204);
    }
}
