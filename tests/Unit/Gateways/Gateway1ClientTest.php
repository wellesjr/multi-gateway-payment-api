<?php

namespace Tests\Unit\Gateways;

use App\Dtos\Payment\ChargePayloadDto;
use App\Gateways\Gateway1Client;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Gateway1ClientTest extends TestCase
{
    public function test_it_authenticates_before_charge_transaction(): void
    {
        config([
            'services.gateway1.url' => 'http://gateway1.local',
            'services.gateway1.email' => 'dev@betalent.tech',
            'services.gateway1.token' => 'TOKEN',
        ]);

        Http::fake([
            'http://gateway1.local/login' => Http::response([
                'token' => 'GW1_TOKEN',
            ], 200),
            'http://gateway1.local/transactions' => Http::response([
                'id' => 'gw1-ext-123',
            ], 201),
        ]);

        $client = new Gateway1Client(app(HttpFactory::class));

        $result = $client->charge(new ChargePayloadDto(
            amountInCents: 2590,
            name: 'Cliente Teste',
            email: 'cliente.teste@example.com',
            cardNumber: '5569000000006063',
            cvv: '010',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('gw1-ext-123', $result->externalId);

        Http::assertSentCount(2);

        $recorded = Http::recorded()->values();
        $firstRequest = $recorded->get(0)[0];
        $secondRequest = $recorded->get(1)[0];

        $this->assertSame('http://gateway1.local/login', $firstRequest->url());
        $this->assertSame('http://gateway1.local/transactions', $secondRequest->url());
        $this->assertTrue($secondRequest->hasHeader('Authorization', 'Bearer GW1_TOKEN'));
    }

    public function test_it_normalizes_short_name_for_gateway1_min_length_rule(): void
    {
        config([
            'services.gateway1.url' => 'http://gateway1.local',
            'services.gateway1.email' => 'dev@betalent.tech',
            'services.gateway1.token' => 'TOKEN',
        ]);

        $sentName = null;

        Http::fake([
            'http://gateway1.local/login' => Http::response([
                'token' => 'GW1_TOKEN',
            ], 200),
            'http://gateway1.local/transactions' => function ($request) use (&$sentName) {
                $payload = $request->data();
                $sentName = (string) ($payload['name'] ?? '');

                return Http::response([
                    'id' => 'gw1-ext-456',
                ], 201);
            },
        ]);

        $client = new Gateway1Client(app(HttpFactory::class));

        $result = $client->charge(new ChargePayloadDto(
            amountInCents: 2590,
            name: 'Ana',
            email: 'cliente.teste@example.com',
            cardNumber: '5569000000006063',
            cvv: '010',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('gw1-ext-456', $result->externalId);
        $this->assertNotNull($sentName);
        $this->assertGreaterThanOrEqual(5, mb_strlen($sentName));
        $this->assertSame('Cliente Ana', $sentName);
    }
}
