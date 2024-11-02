<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use Illuminate\Http\Request;
use App\Models\User;

class AchievementController extends Controller
{
    public function index()
    {
        return Achievement::all();
    }

    public function store(Request $request)
    {
        $achievement = Achievement::create($request->all());
        return response()->json($achievement, 201);
    }

    public function show(Achievement $achievement)
    {
        return $achievement;
    }

    public function update(Request $request, Achievement $achievement)
    {
        $achievement->update($request->all());
        return response()->json($achievement);
    }

    public function destroy(Achievement $achievement)
    {
        $achievement->delete();
        return response()->json(null, 204);
    }

}
