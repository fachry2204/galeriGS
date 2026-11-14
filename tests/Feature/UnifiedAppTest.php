<?php
namespace Tests\Feature;
use Tests\TestCase;
class UnifiedAppTest extends TestCase {
 public function test_gallery_and_admin_paths_serve_vue_application(): void {
  $this->get('/')->assertOk()->assertSee('id="app"',false);
  $this->get('/admin')->assertOk()->assertSee('id="app"',false);
  $this->get('/albums/example')->assertOk()->assertSee('id="app"',false);
 }
 public function test_unknown_api_path_is_not_served_as_web_page(): void {
  $this->getJson('/api/v1/nonexistent')->assertNotFound()->assertJsonMissingPath('app');
 }
}
