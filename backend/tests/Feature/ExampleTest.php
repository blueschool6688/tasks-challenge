<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_root_to_docs(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/docs');
    }

    public function test_swagger_documentation_page_is_accessible(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $response->assertSee('Swagger API Docs');
    }
}
