<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductIndexControllerTest extends TestCase
{
    public function test_renders_the_product_inventory_workspace(): void
    {
        $response = $this->get(route('products.index'));

        $response
            ->assertOk()
            ->assertViewIs('products.index');
    }
}
