<?php
namespace AthleteDashboard\Tests;

use WP_Mock;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;

class TestCase extends PHPUnit_TestCase {
    public function setUp(): void {
        parent::setUp();
        WP_Mock::setUp();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Helper function to create a mock user
     */
    protected function createMockUser($id = 1, $role = 'subscriber') {
        $user = new \stdClass();
        $user->ID = $id;
        $user->roles = [$role];
        $user->user_login = 'test_user';
        $user->user_email = 'test@example.com';
        $user->user_registered = '2023-01-01 00:00:00';
        return $user;
    }

    /**
     * Helper function to mock WordPress functions
     */
    protected function mockWordPressFunctions() {
        WP_Mock::userFunction('wp_cache_get')->andReturn(false);
        WP_Mock::userFunction('wp_cache_set')->andReturn(true);
        WP_Mock::userFunction('get_transient')->andReturn(false);
        WP_Mock::userFunction('set_transient')->andReturn(true);
        WP_Mock::userFunction('delete_transient')->andReturn(true);
        WP_Mock::userFunction('wp_using_ext_object_cache')->andReturn(false);
        WP_Mock::userFunction('is_user_logged_in')->andReturn(true);
        WP_Mock::userFunction('get_current_user_id')->andReturn(1);
        WP_Mock::userFunction('current_user_can')->andReturn(false);
        WP_Mock::userFunction('rest_ensure_response')->andReturnUsing(function($data) {
            return new \WP_REST_Response($data);
        });
    }

    /**
     * Helper function to mock WordPress errors
     */
    protected function mockWordPressErrors() {
        WP_Mock::userFunction('is_wp_error')->andReturnUsing(function($thing) {
            return $thing instanceof \WP_Error;
        });
    }

    /**
     * Helper function to mock WordPress cache functions
     */
    protected function mockWordPressCache() {
        WP_Mock::userFunction('wp_cache_get')->andReturn(false);
        WP_Mock::userFunction('wp_cache_set')->andReturn(true);
        WP_Mock::userFunction('wp_cache_delete')->andReturn(true);
        WP_Mock::userFunction('wp_cache_flush')->andReturn(true);
        WP_Mock::userFunction('wp_using_ext_object_cache')->andReturn(false);
    }

    /**
     * Helper function to mock WordPress transient functions
     */
    protected function mockWordPressTransients() {
        WP_Mock::userFunction('get_transient')->andReturn(false);
        WP_Mock::userFunction('set_transient')->andReturn(true);
        WP_Mock::userFunction('delete_transient')->andReturn(true);
    }

    /**
     * Helper function to mock WordPress user functions
     */
    protected function mockWordPressUser($user = null) {
        if (!$user) {
            $user = $this->createMockUser();
        }

        WP_Mock::userFunction('get_user_by')->andReturn($user);
        WP_Mock::userFunction('get_userdata')->andReturn($user);
        WP_Mock::userFunction('get_user_meta')->andReturn('');
        WP_Mock::userFunction('update_user_meta')->andReturn(true);
        WP_Mock::userFunction('delete_user_meta')->andReturn(true);
    }

    /**
     * Helper function to mock WordPress post functions
     */
    protected function mockWordPressPost($post = null) {
        if (!$post) {
            $post = new \stdClass();
            $post->ID = 1;
            $post->post_type = 'post';
            $post->post_status = 'publish';
        }

        WP_Mock::userFunction('get_post')->andReturn($post);
        WP_Mock::userFunction('get_post_meta')->andReturn('');
        WP_Mock::userFunction('update_post_meta')->andReturn(true);
        WP_Mock::userFunction('delete_post_meta')->andReturn(true);
    }

    /**
     * Helper function to mock WordPress option functions
     */
    protected function mockWordPressOptions() {
        WP_Mock::userFunction('get_option')->andReturn('');
        WP_Mock::userFunction('update_option')->andReturn(true);
        WP_Mock::userFunction('delete_option')->andReturn(true);
    }

    /**
     * Helper function to mock WordPress REST functions
     */
    protected function mockWordPressRest() {
        WP_Mock::userFunction('register_rest_route')->andReturn(true);
        WP_Mock::userFunction('rest_ensure_response')->andReturnUsing(function($data) {
            return new \WP_REST_Response($data);
        });
    }
} 