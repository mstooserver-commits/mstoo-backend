<?php

namespace Tests\Unit;

use Modules\SMSModule\Lib\SMS_gateway;
use Tests\TestCase;

class SandeshSmsCredentialTest extends TestCase
{
    public function test_sandesh_api_key_comes_from_config_when_admin_value_is_empty()
    {
        config(['services.sandesh.api_key' => 'cached-sandesh-key']);

        $method = new \ReflectionMethod(SMS_gateway::class, 'sandeshCredential');
        $method->setAccessible(true);

        $this->assertSame(
            'cached-sandesh-key',
            $method->invoke(null, ['status' => 1, 'api_key' => ''], 'api_key', 'SANDESH_SMS_API_KEY')
        );
    }

    public function test_sandesh_ignores_placeholder_admin_api_key()
    {
        config(['services.sandesh.api_key' => 'from-env']);

        $method = new \ReflectionMethod(SMS_gateway::class, 'sandeshCredential');
        $method->setAccessible(true);

        $this->assertSame(
            'from-env',
            $method->invoke(null, ['api_key' => 'data'], 'api_key', 'SANDESH_SMS_API_KEY')
        );
    }
}
