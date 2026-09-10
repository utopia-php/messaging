<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Messenger;

class MessengerTest extends TestCase
{
    public function test_uses_first_adapter_when_it_succeeds(): void
    {
        $firstAdapter = $this->createMock(Adapter::class);
        $firstAdapter->method('getName')->willReturn('First');
        $firstAdapter->method('getType')->willReturn('sms');
        $firstAdapter->method('getMessageType')->willReturn(SMS::class);
        $firstAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $firstAdapter->method('send')->willReturn([
            'deliveredTo' => 1,
            'type' => 'sms',
            'results' => [['recipient' => '+1234567890', 'status' => 'success', 'error' => '']],
        ]);

        $secondAdapter = $this->createMock(Adapter::class);
        $secondAdapter->method('getName')->willReturn('Second');
        $secondAdapter->method('getType')->willReturn('sms');
        $secondAdapter->method('getMessageType')->willReturn(SMS::class);
        $secondAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $secondAdapter->expects($this->never())->method('send');

        $messenger = new Messenger([$firstAdapter, $secondAdapter]);

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test message'
        );

        $result = $messenger->send($message);

        $this->assertEquals(1, $result['deliveredTo']);
        $this->assertEquals('success', $result['results'][0]['status']);
    }

    public function test_falls_back_to_second_adapter_when_first_throws(): void
    {
        $firstAdapter = $this->createMock(Adapter::class);
        $firstAdapter->method('getName')->willReturn('First');
        $firstAdapter->method('getType')->willReturn('sms');
        $firstAdapter->method('getMessageType')->willReturn(SMS::class);
        $firstAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $firstAdapter->method('send')->willThrowException(new \Exception('Connection failed'));

        $secondAdapter = $this->createMock(Adapter::class);
        $secondAdapter->method('getName')->willReturn('Second');
        $secondAdapter->method('getType')->willReturn('sms');
        $secondAdapter->method('getMessageType')->willReturn(SMS::class);
        $secondAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $secondAdapter->method('send')->willReturn([
            'deliveredTo' => 1,
            'type' => 'sms',
            'results' => [['recipient' => '+1234567890', 'status' => 'success', 'error' => '']],
        ]);

        $messenger = new Messenger([$firstAdapter, $secondAdapter]);

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test message'
        );

        $result = $messenger->send($message);

        $this->assertEquals(1, $result['deliveredTo']);
        $this->assertEquals('success', $result['results'][0]['status']);
    }

    public function test_tries_multiple_adapters_until_success(): void
    {
        $firstAdapter = $this->createMock(Adapter::class);
        $firstAdapter->method('getName')->willReturn('First');
        $firstAdapter->method('getType')->willReturn('sms');
        $firstAdapter->method('getMessageType')->willReturn(SMS::class);
        $firstAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $firstAdapter->method('send')->willThrowException(new \Exception('Error 1'));

        $secondAdapter = $this->createMock(Adapter::class);
        $secondAdapter->method('getName')->willReturn('Second');
        $secondAdapter->method('getType')->willReturn('sms');
        $secondAdapter->method('getMessageType')->willReturn(SMS::class);
        $secondAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $secondAdapter->method('send')->willThrowException(new \Exception('Error 2'));

        $thirdAdapter = $this->createMock(Adapter::class);
        $thirdAdapter->method('getName')->willReturn('Third');
        $thirdAdapter->method('getType')->willReturn('sms');
        $thirdAdapter->method('getMessageType')->willReturn(SMS::class);
        $thirdAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $thirdAdapter->method('send')->willReturn([
            'deliveredTo' => 1,
            'type' => 'sms',
            'results' => [['recipient' => '+1234567890', 'status' => 'success', 'error' => '']],
        ]);

        $messenger = new Messenger([$firstAdapter, $secondAdapter, $thirdAdapter]);

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test message'
        );

        $result = $messenger->send($message);

        $this->assertEquals(1, $result['deliveredTo']);
        $this->assertEquals('success', $result['results'][0]['status']);
    }

    public function test_throws_when_all_adapters_fail(): void
    {
        $firstAdapter = $this->createMock(Adapter::class);
        $firstAdapter->method('getName')->willReturn('FirstAdapter');
        $firstAdapter->method('getType')->willReturn('sms');
        $firstAdapter->method('getMessageType')->willReturn(SMS::class);
        $firstAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $firstAdapter->method('send')->willThrowException(new \Exception('Connection timeout'));

        $secondAdapter = $this->createMock(Adapter::class);
        $secondAdapter->method('getName')->willReturn('SecondAdapter');
        $secondAdapter->method('getType')->willReturn('sms');
        $secondAdapter->method('getMessageType')->willReturn(SMS::class);
        $secondAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $secondAdapter->method('send')->willThrowException(new \Exception('API error'));

        $messenger = new Messenger([$firstAdapter, $secondAdapter]);

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test message'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('All 2 adapters failed');
        $this->expectExceptionMessage('FirstAdapter (adapter 1): Connection timeout');
        $this->expectExceptionMessage('SecondAdapter (adapter 2): API error');

        $messenger->send($message);
    }

    public function test_throws_when_single_adapter_fails(): void
    {
        $adapter = $this->createMock(Adapter::class);
        $adapter->method('getName')->willReturn('OnlyAdapter');
        $adapter->method('getType')->willReturn('sms');
        $adapter->method('getMessageType')->willReturn(SMS::class);
        $adapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $adapter->method('send')->willThrowException(new \Exception('Network error'));

        $messenger = new Messenger([$adapter]);

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test message'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('All 1 adapter failed');
        $this->expectExceptionMessage('OnlyAdapter (adapter 1): Network error');

        $messenger->send($message);
    }

    public function test_accepts_single_adapter_instance(): void
    {
        $adapter = $this->createMock(Adapter::class);
        $adapter->method('getName')->willReturn('OnlyAdapter');
        $adapter->method('getType')->willReturn('sms');
        $adapter->method('getMessageType')->willReturn(SMS::class);
        $adapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $adapter->method('send')->willReturn([
            'deliveredTo' => 1,
            'type' => 'sms',
            'results' => [['recipient' => '+1234567890', 'status' => 'success', 'error' => '']],
        ]);

        $messenger = new Messenger($adapter);

        $result = $messenger->send(new SMS(
            to: ['+1234567890'],
            content: 'Test message'
        ));

        $this->assertEquals(1, $result['deliveredTo']);
        $this->assertEquals('success', $result['results'][0]['status']);
    }

    public function test_rejects_empty_adapter_list(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one adapter must be provided');

        new Messenger([]);
    }

    public function test_rejects_non_adapter_array_element(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All elements must be instances of Adapter, but element 1 is string.');

        new Messenger([
            $this->createMock(Adapter::class),
            'not-an-adapter',
        ]);
    }

    public function test_rejects_mixed_adapter_types(): void
    {
        $smsAdapter = $this->createMock(Adapter::class);
        $smsAdapter->method('getName')->willReturn('SMS');
        $smsAdapter->method('getType')->willReturn('sms');
        $smsAdapter->method('getMessageType')->willReturn(SMS::class);

        $emailAdapter = $this->createMock(Adapter::class);
        $emailAdapter->method('getName')->willReturn('Email');
        $emailAdapter->method('getType')->willReturn('email');
        $emailAdapter->method('getMessageType')->willReturn(Email::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All adapters must be of the same type');

        new Messenger([$smsAdapter, $emailAdapter]);
    }

    public function test_rejects_mixed_message_types(): void
    {
        $smsAdapter1 = $this->createMock(Adapter::class);
        $smsAdapter1->method('getName')->willReturn('SMS1');
        $smsAdapter1->method('getType')->willReturn('sms');
        $smsAdapter1->method('getMessageType')->willReturn(SMS::class);

        $smsAdapter2 = $this->createMock(Adapter::class);
        $smsAdapter2->method('getName')->willReturn('SMS2');
        $smsAdapter2->method('getType')->willReturn('sms');
        $smsAdapter2->method('getMessageType')->willReturn(Email::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All adapters must support the same message type');

        new Messenger([$smsAdapter1, $smsAdapter2]);
    }

    public function test_get_max_messages_per_request_returns_minimum(): void
    {
        $adapter1 = $this->createMock(Adapter::class);
        $adapter1->method('getName')->willReturn('Adapter1');
        $adapter1->method('getType')->willReturn('sms');
        $adapter1->method('getMessageType')->willReturn(SMS::class);
        $adapter1->method('getMaxMessagesPerRequest')->willReturn(500);

        $adapter2 = $this->createMock(Adapter::class);
        $adapter2->method('getName')->willReturn('Adapter2');
        $adapter2->method('getType')->willReturn('sms');
        $adapter2->method('getMessageType')->willReturn(SMS::class);
        $adapter2->method('getMaxMessagesPerRequest')->willReturn(100);

        $adapter3 = $this->createMock(Adapter::class);
        $adapter3->method('getName')->willReturn('Adapter3');
        $adapter3->method('getType')->willReturn('sms');
        $adapter3->method('getMessageType')->willReturn(SMS::class);
        $adapter3->method('getMaxMessagesPerRequest')->willReturn(1000);

        $messenger = new Messenger([$adapter1, $adapter2, $adapter3]);

        $this->assertEquals(100, $messenger->getMaxMessagesPerRequest());
    }

    public function test_get_type_returns_first_adapter_type(): void
    {
        $adapter = $this->createMock(Adapter::class);
        $adapter->method('getName')->willReturn('Test');
        $adapter->method('getType')->willReturn('sms');
        $adapter->method('getMessageType')->willReturn(SMS::class);
        $adapter->method('getMaxMessagesPerRequest')->willReturn(100);

        $messenger = new Messenger([$adapter]);

        $this->assertEquals('sms', $messenger->getType());
    }

    public function test_get_message_type_returns_first_adapter_message_type(): void
    {
        $adapter = $this->createMock(Adapter::class);
        $adapter->method('getName')->willReturn('Test');
        $adapter->method('getType')->willReturn('sms');
        $adapter->method('getMessageType')->willReturn(SMS::class);
        $adapter->method('getMaxMessagesPerRequest')->willReturn(100);

        $messenger = new Messenger([$adapter]);

        $this->assertEquals(SMS::class, $messenger->getMessageType());
    }

    public function test_rejects_invalid_message_type(): void
    {
        $adapter = $this->createMock(Adapter::class);
        $adapter->method('getName')->willReturn('SMS');
        $adapter->method('getType')->willReturn('sms');
        $adapter->method('getMessageType')->willReturn(SMS::class);
        $adapter->method('getMaxMessagesPerRequest')->willReturn(100);

        $messenger = new Messenger([$adapter]);

        // Create an Email message when Messenger expects SMS
        $message = new Email(
            to: ['test@example.com'],
            subject: 'Test',
            content: 'Test content',
            fromName: 'Sender',
            fromEmail: 'sender@example.com'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid message type');

        $messenger->send($message);
    }

    public function test_works_with_email_adapters(): void
    {
        $adapter1 = $this->createMock(Adapter::class);
        $adapter1->method('getName')->willReturn('Sendgrid');
        $adapter1->method('getType')->willReturn('email');
        $adapter1->method('getMessageType')->willReturn(Email::class);
        $adapter1->method('getMaxMessagesPerRequest')->willReturn(100);
        $adapter1->method('send')->willThrowException(new \Exception('API down'));

        $adapter2 = $this->createMock(Adapter::class);
        $adapter2->method('getName')->willReturn('Mailgun');
        $adapter2->method('getType')->willReturn('email');
        $adapter2->method('getMessageType')->willReturn(Email::class);
        $adapter2->method('getMaxMessagesPerRequest')->willReturn(100);
        $adapter2->method('send')->willReturn([
            'deliveredTo' => 1,
            'type' => 'email',
            'results' => [['recipient' => 'test@example.com', 'status' => 'success', 'error' => '']],
        ]);

        $messenger = new Messenger([$adapter1, $adapter2]);

        $message = new Email(
            to: ['test@example.com'],
            subject: 'Test',
            content: 'Test content',
            fromName: 'Sender',
            fromEmail: 'sender@example.com'
        );

        $result = $messenger->send($message);

        $this->assertEquals(1, $result['deliveredTo']);
        $this->assertEquals('success', $result['results'][0]['status']);
    }

    public function test_does_not_fallback_on_returned_failure_payload(): void
    {
        // This tests that we ONLY fallback on exceptions, not on failure responses
        $firstAdapter = $this->createMock(Adapter::class);
        $firstAdapter->method('getName')->willReturn('First');
        $firstAdapter->method('getType')->willReturn('sms');
        $firstAdapter->method('getMessageType')->willReturn(SMS::class);
        $firstAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        // Returns a failure response (no exception thrown)
        $firstAdapter->method('send')->willReturn([
            'deliveredTo' => 0,
            'type' => 'sms',
            'results' => [['recipient' => '+1234567890', 'status' => 'failure', 'error' => 'Rate limited']],
        ]);

        $secondAdapter = $this->createMock(Adapter::class);
        $secondAdapter->method('getName')->willReturn('Second');
        $secondAdapter->method('getType')->willReturn('sms');
        $secondAdapter->method('getMessageType')->willReturn(SMS::class);
        $secondAdapter->method('getMaxMessagesPerRequest')->willReturn(100);
        $secondAdapter->expects($this->never())->method('send');

        $messenger = new Messenger([$firstAdapter, $secondAdapter]);

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test message'
        );

        $result = $messenger->send($message);

        // Should return the first adapter's failure response
        $this->assertEquals(0, $result['deliveredTo']);
        $this->assertEquals('failure', $result['results'][0]['status']);
    }
}
