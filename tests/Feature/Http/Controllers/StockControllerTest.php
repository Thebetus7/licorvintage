<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\StockController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see StockController
 */
final class StockControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(StockController::class));
    }
}
