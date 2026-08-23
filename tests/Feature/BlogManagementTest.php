<?php

namespace Tests\Feature;

use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class BlogManagementTest extends TestCase
{
    public function test_guest_cannot_open_admin_blog_pages()
    {
        $this->get('/admin/blog')->assertRedirect('admin/auth/login');
        $this->get('/admin/blog/create')->assertRedirect('admin/auth/login');
        $this->get('/admin/blog-category')->assertRedirect('admin/auth/login');
    }

    public function test_guest_cannot_store_or_delete_blogs()
    {
        $this->post('/admin/blog/store', ['title' => 'Test'])->assertRedirect('admin/auth/login');
        $this->delete('/admin/blog/delete/not-a-real-id')->assertRedirect('admin/auth/login');
    }

    public function test_admin_can_open_blog_pages()
    {
        $admin = User::query()->where('user_type', 'super-admin')->where('is_active', 1)->first();
        if (!$admin) {
            $this->markTestSkipped('No super-admin is available in this database.');
        }

        $this->actingAs($admin)->get('/admin/blog')->assertOk()->assertSee('Blog', false);
        $this->actingAs($admin)->get('/admin/blog/create')->assertOk();
        $this->actingAs($admin)->get('/admin/blog-category')->assertOk();
    }

    public function test_public_api_does_not_expose_drafts()
    {
        $response = $this->getJson('/api/v1/customer/blogs?limit=10&offset=1');
        $response->assertOk();

        $content = $response->json('content.blogs.data') ?? $response->json('content.blogs') ?? [];
        if (is_array($content)) {
            foreach ($content as $blog) {
                if (isset($blog['status'])) {
                    $this->assertNotEquals('draft', $blog['status']);
                    $this->assertNotEquals('archived', $blog['status']);
                }
            }
        }
    }

    public function test_public_blog_detail_rejects_unknown_slug()
    {
        $this->getJson('/api/v1/customer/blogs/this-slug-should-not-exist-xyz')->assertStatus(404);
    }

    public function test_html_sanitizer_strips_scripts()
    {
        $clean = sanitize_html('<p>Hello</p><script>alert(1)</script><a href="javascript:alert(1)">x</a>');
        $this->assertStringContainsString('<p>Hello</p>', $clean);
        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
    }
}
