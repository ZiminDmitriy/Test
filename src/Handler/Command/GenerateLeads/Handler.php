<?php
declare(strict_types=1);

namespace App\Handler\Command\GenerateLeads;

use App\Handler\HandlerInterface;
use App\Util\AMQP\SystemDescriber\LeadGeneratingWorkflowAMQPSystemDescriber;
use App\Util\Redis\RedisSimpleResolver;
use App\Util\SafelyArrayJsonEncoder;
use LeadGenerator\Generator;
use LeadGenerator\Lead;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class Handler implements HandlerInterface
{
    public const REDIS_KEY = 'key';

    private Generator $generator;

    private RedisSimpleResolver $redisSimpleResolver;

    private AMQPStreamConnection $amqpStreamConnection;

    private LeadGeneratingWorkflowAMQPSystemDescriber $leadGeneratingWorkflowAMQPSystemDescriber;

    private SafelyArrayJsonEncoder $safelyArrayJsonEncoder;

    public function __construct()
    {
        $this->generator = new Generator();
        $this->redisSimpleResolver = new RedisSimpleResolver();
        $this->amqpStreamConnection = new AMQPStreamConnection(
            $_ENV['RABBIT_MQ_HOST'], $_ENV['RABBIT_MQ_PORT'], $_ENV['RABBIT_MQ_USER'], $_ENV['RABBIT_MQ_PASSWORD']
        );
        $this->leadGeneratingWorkflowAMQPSystemDescriber = new LeadGeneratingWorkflowAMQPSystemDescriber(
            $this->amqpStreamConnection->channel()
        );
        $this->safelyArrayJsonEncoder = new SafelyArrayJsonEncoder();
    }

    public function handle(): void
    {
        $amqpQueueNamesRegistry = [];
        $leadGeneratingWorkflowAMQPSystemDescriber = $this->leadGeneratingWorkflowAMQPSystemDescriber;
        $amqpChannel = $leadGeneratingWorkflowAMQPSystemDescriber->getAmqpChannel();

        $this->leadGeneratingWorkflowAMQPSystemDescriber->createExchange();

        $this->generator->generateLeads(
            10000, function (Lead $lead) use (&$amqpQueueNamesRegistry, $leadGeneratingWorkflowAMQPSystemDescriber, $amqpChannel): void {
            if (!in_array($categoryName = strtolower($lead->categoryName), $amqpQueueNamesRegistry, false)) {
                $amqpQueueNamesRegistry[] = $categoryName;

                $leadGeneratingWorkflowAMQPSystemDescriber->createQueue($categoryName);
            }

            $message = new AMQPMessage(
                $this->safelyArrayJsonEncoder->encode(['id' => $lead->id, 'categoryName' => $lead->categoryName], 0, 512),
                array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
            );

            $amqpChannel->basic_publish($message, $leadGeneratingWorkflowAMQPSystemDescriber->getExchange(), $categoryName );
        });

        $this->redisSimpleResolver->set(self::REDIS_KEY, $this->safelyArrayJsonEncoder->encode($amqpQueueNamesRegistry, 0, 512));

        $amqpChannel->close();
        $this->amqpStreamConnection->close();
    }
}