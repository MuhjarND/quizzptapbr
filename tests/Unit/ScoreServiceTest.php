<?php
namespace Tests\Unit;
use App\Services\ScoreService;
use PHPUnit\Framework\TestCase;
class ScoreServiceTest extends TestCase
{
    public function test_speed_formula_and_bounds()
    {
        $s = new ScoreService;
        $this->assertSame(1000, $s->calculate(true, 0, 20));
        $this->assertSame(750, $s->calculate(true, 10000, 20));
        $this->assertSame(500, $s->calculate(true, 20000, 20));
        $this->assertSame(500, $s->calculate(true, 25000, 20));
        $this->assertSame(1000, $s->calculate(true, -500, 20));
        $this->assertSame(0, $s->calculate(false, 1, 20));
    }
}
