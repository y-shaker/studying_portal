<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Achievement extends Model
{
    use HasFactory;

    protected $table = 'achievements';

    // Define arrays for different achievements
    public static $lessonsWatchedAchievements = [
        'First Lesson Watched' => 1,
        '5 Lessons Watched' => 5,
        '10 Lessons Watched' => 10,
        '25 Lessons Watched' => 25,
        '50 Lessons Watched' => 50,
    ];

    public static $commentsWrittenAchievements = [
        'First Comment Written' => 1,
        '3 Comments Written' => 3,
        '5 Comments Written' => 5,
        '10 Comments Written' => 10,
        '20 Comments Written' => 20,
    ];

}
