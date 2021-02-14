<?php
declare(strict_types=1);

namespace App\Util\AMQP\SystemDescriber;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exchange\AMQPExchangeType;

final class LeadGeneratingWorkflowAMQPSystemDescriber
{
    private AMQPChannel $amqpChannel;

    private string $exchange;

    public function __construct(AMQPChannel $amqpChannel)
    {
        $this->amqpChannel = $amqpChannel;
        $this->exchange = 'leadGeneratingWorkflow';
    }

    public function createQueue(string $queueName): self
    {
        $this->amqpChannel->queue_declare($queueName, false, true, false, false, false, [], null);
        $this->amqpChannel->queue_bind($queueName, $this->exchange, $queueName, false, [], null);

        return $this;
    }

    public function createExchange(): self
    {
        $this->amqpChannel->exchange_declare($this->exchange, AMQPExchangeType::DIRECT, false, true, false, false, false, [], null);

        return $this;
    }

    public function getExchange(): string
    {
        return $this->exchange;
    }

    public function getAmqpChannel(): AMQPChannel
    {
        return $this->amqpChannel;
    }
}