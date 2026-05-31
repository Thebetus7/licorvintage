<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\PromocionController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see PromocionController
 */
final class PromocionControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(PromocionController::class));
    }
}
