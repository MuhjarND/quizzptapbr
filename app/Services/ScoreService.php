<?php
namespace App\Services;
class ScoreService
{
    public function calculate(bool $correct, int $elapsedMs, int $durationSeconds): int
    {
        if (!$correct) return 0;
        $ratio = min(1, max(0, $elapsedMs / ($durationSeconds * 1000)));
        return (int) round(1000 - 500 * $ratio);
    }
}
