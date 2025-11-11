<?php

declare(strict_types=1);

namespace AtlasRelay\Tests\Feature;

use AtlasRelay\Facades\Relay;
use AtlasRelay\Tests\TestCase;
use Illuminate\Http\Request;

class RelayFacadeTest extends TestCase
{
    public function test_payload_builder_retains_payload_state(): void
    {
        $builder = Relay::payload(['status' => 'queued']);
        $context = $builder->context();

        $this->assertSame(['status' => 'queued'], $context->payload);
        $this->assertNull($context->request);
    }

    public function test_request_builder_captures_request_instance(): void
    {
        $request = Request::create('/relay', 'POST', ['hello' => 'world']);
        $builder = Relay::request($request);
        $context = $builder->context();

        $this->assertSame($request, $context->request);
    }
}
