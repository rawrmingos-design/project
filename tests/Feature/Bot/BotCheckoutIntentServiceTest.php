<?php

namespace Tests\Feature\Bot;

use App\Models\BotCheckoutIntent;
use App\Services\Checkout\BotCheckoutIntentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BotCheckoutIntentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_origin_message_replays_one_intent(): void
    {
        $service = app(BotCheckoutIntentService::class);
        $payload = $this->payload();
        $quote = $this->quote();
        $context = $this->context();

        $created = $service->create($payload, $quote, $context);
        $replayed = $service->create($payload, $quote, $context);

        $this->assertFalse($created['replayed']);
        $this->assertTrue($replayed['replayed']);
        $this->assertSame(
            $created['intent']->intent_id,
            $replayed['intent']->intent_id,
        );
        $this->assertSame($created['token'], $replayed['token']);
        $this->assertDatabaseCount('bot_checkout_intents', 1);
    }

    public function test_same_origin_message_rejects_a_different_payload(): void
    {
        $service = app(BotCheckoutIntentService::class);
        $context = $this->context();
        $service->create($this->payload(), $this->quote(), $context);

        $this->expectException(ValidationException::class);

        $service->create(
            $this->payload(['uid' => 'different']),
            $this->quote(),
            $context,
        );
    }

    public function test_separate_messages_allow_identical_purchases(): void
    {
        $service = app(BotCheckoutIntentService::class);

        $first = $service->create(
            $this->payload(),
            $this->quote(),
            $this->context(['message_id' => 'message-1']),
        );
        $second = $service->create(
            $this->payload(),
            $this->quote(),
            $this->context(['message_id' => 'message-2']),
        );

        $this->assertNotSame(
            $first['intent']->intent_id,
            $second['intent']->intent_id,
        );
        $this->assertDatabaseCount('bot_checkout_intents', 2);
    }

    public function test_claim_is_sender_scoped_and_duplicate_claim_stays_processing(): void
    {
        $service = app(BotCheckoutIntentService::class);
        $context = $this->context();
        $created = $service->create(
            $this->payload(),
            $this->quote(),
            $context,
        );

        $wrongSender = $service->claim(
            $created['token'],
            $this->context([
                'external_user_id' => 'telegram:9999',
                'message_id' => 'confirm-wrong',
            ]),
        );
        $claimed = $service->claim(
            $created['token'],
            $this->context(['message_id' => 'confirm-1']),
        );
        $duplicate = $service->claim(
            $created['token'],
            $this->context(['message_id' => 'confirm-2']),
        );

        $this->assertSame('invalid', $wrongSender['status']);
        $this->assertSame('claimed', $claimed['status']);
        $this->assertSame('processing', $duplicate['status']);
    }

    public function test_provider_dispatch_is_single_use_and_unknown_failure_requires_reconciliation(): void
    {
        $service = app(BotCheckoutIntentService::class);
        $context = $this->context();
        $created = $service->create(
            $this->payload(),
            $this->quote(),
            $context,
        );
        $claim = $service->claim(
            $created['token'],
            $this->context(['message_id' => 'confirm-1']),
        );
        $intent = $claim['intent'];
        $intent = $service->prepareMutation(
            $intent->intent_id,
            $context,
        );

        $this->assertTrue($service->markProviderDispatch($intent));
        $this->assertFalse($service->markProviderDispatch($intent));

        $service->markFailure($intent, true);

        $this->assertSame(
            BotCheckoutIntent::STATUS_REQUIRES_RECONCILIATION,
            $intent->fresh()->status,
        );
    }

    public function test_pre_dispatch_failure_can_be_claimed_again(): void
    {
        $service = app(BotCheckoutIntentService::class);
        $context = $this->context();
        $created = $service->create(
            $this->payload(),
            $this->quote(),
            $context,
        );
        $claim = $service->claim(
            $created['token'],
            $this->context(['message_id' => 'confirm-1']),
        );

        $service->markFailure($claim['intent']);
        $retry = $service->claim(
            $created['token'],
            $this->context(['message_id' => 'confirm-2']),
        );

        $this->assertSame('claimed', $retry['status']);
    }

    public function test_completed_intent_replays_as_completed(): void
    {
        $service = app(BotCheckoutIntentService::class);
        $context = $this->context();
        $created = $service->create(
            $this->payload(),
            $this->quote(),
            $context,
        );
        $claim = $service->claim(
            $created['token'],
            $this->context(['message_id' => 'confirm-1']),
        );

        $service->markCompleted($claim['intent'], 'ORDER-1');
        $replay = $service->claim(
            $created['token'],
            $this->context(['message_id' => 'confirm-2']),
        );

        $this->assertSame('completed', $replay['status']);
        $this->assertSame('ORDER-1', $replay['intent']->order_id);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'service' => 1,
            'payment_method' => 'QRIS',
            'uid' => '12345',
            'zone' => '6789',
            'email' => '9876@telegram.user',
        ], $overrides);
    }

    private function quote(): array
    {
        return [
            'data' => [
                'service_id' => 1,
                'service_name' => '100 Diamond',
                'payment_method' => [
                    'code' => 'QRIS',
                    'name' => 'QRIS',
                    'type' => 'tokopay',
                ],
                'base_amount' => 10000,
                'discount' => 0,
                'payment_fee' => 500,
                'total_amount' => 10500,
            ],
        ];
    }

    private function context(array $overrides = []): array
    {
        return array_replace([
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'message-1',
        ], $overrides);
    }
}
