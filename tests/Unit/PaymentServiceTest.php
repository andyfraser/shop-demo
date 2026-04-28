<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Payment\PaymentService;
use App\Services\Payment\ManualGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use App\Models\Order;
use Tests\NullLogger;

class PaymentServiceTest extends TestCase {
    private PaymentService $paymentService;
    private NullLogger $logger;

    public function setUp() {
        $this->logger = new NullLogger();
        $this->paymentService = new PaymentService($this->logger);
    }

    public function testRegisterAndGetGateway() {
        $gateway = new ManualGateway();
        $this->paymentService->registerGateway($gateway);

        $this->assertCount(1, $this->paymentService->getGateways());
        $this->assertSame($gateway, $this->paymentService->getGateway('manual'));
    }

    public function testProcessSuccess() {
        $gateway = new ManualGateway();
        $this->paymentService->registerGateway($gateway);

        $order = new Order($this->logger);
        $order->id = 123;
        $order->total = 100.0;

        $result = $this->paymentService->process('manual', $order);

        $this->assertTrue($result->success);
        $this->assertEquals('paid', $result->status);
        $this->assertNotNull($result->transactionId);
        $this->assertTrue(strpos($result->transactionId, 'TEST-') === 0);
    }

    public function testProcessGatewayNotFound() {
        $order = new Order($this->logger);
        $order->id = 123;
        $order->total = 100.0;

        $result = $this->paymentService->process('non_existent', $order);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not found', $result->message);
    }

    public function testProcessGatewayException() {
        $mockGateway = new class implements PaymentGatewayInterface {
            public function getIdentifier(): string { return 'error'; }
            public function getName(): string { return 'Error Gateway'; }
            public function process(Order $order, array $options = []): PaymentResult {
                throw new \Exception("Gateway error");
            }
            public function refund(Order $order, ?float $amount = null): PaymentResult {
                throw new \Exception("Refund error");
            }
        };

        $this->paymentService->registerGateway($mockGateway);
        $order = new Order($this->logger);
        $order->id = 123;
        $order->total = 100.0;

        $result = $this->paymentService->process('error', $order);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('An error occurred', $result->message);
    }
}
