<?php
declare(strict_types=1);

namespace App\Handler\Command\ProcessLeads;

use App\Handler\Command\GenerateLeads\Handler as PublishHandler;
use App\Handler\HandlerInterface;
use App\Util\AMQP\SystemDescriber\LeadGeneratingWorkflowAMQPSystemDescriber;
use App\Util\Redis\RedisSimpleResolver;
use App\Util\SafelyArrayJsonEncoder;
use DateTimeImmutable;
use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class Handler implements HandlerInterface
{
    public const LOG_FILE_PATH= __DIR__.'/../../../../var';

    private RedisSimpleResolver $redisSimpleResolver;

    private AMQPStreamConnection $amqpStreamConnection;

    private LeadGeneratingWorkflowAMQPSystemDescriber $leadGeneratingWorkflowAMQPSystemDescriber;

    private SafelyArrayJsonEncoder $safelyArrayJsonEncoder;

    public function __construct()
    {
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
        $amqpQueueNames = $this->safelyArrayJsonEncoder->decode(
            $this->redisSimpleResolver->get(PublishHandler::REDIS_KEY), true, 512, 0
        );

        $amqpDisabledQueueNames = array_map(
            function (string $amqpQueueName): string {
                return strtolower($amqpQueueName);
            },
            explode(',', $_ENV['DISABLED_LEAD_CATEGORIES'])
        );

        $channel = $this->leadGeneratingWorkflowAMQPSystemDescriber->getAmqpChannel();
        $channel->basic_qos(null, 1, null);

        $this->leadGeneratingWorkflowAMQPSystemDescriber->createExchange();

        foreach ($amqpQueueNames as $amqpQueueName) {
            if (in_array($amqpQueueName, $amqpDisabledQueueNames, true)) {
                continue;
            }

            $this->leadGeneratingWorkflowAMQPSystemDescriber->createQueue($amqpQueueName);

            $channel->basic_consume($amqpQueueName, '', false, false, false, false, function (AMQPMessage $message): void {
                try {
                    sleep(2);

                    $leadData = $this->safelyArrayJsonEncoder->decode($message->body, true, 512, 0);

                    file_put_contents('/var/www/html/var/log.txt', $leadData['id'].' | '.$leadData['categoryName'].' | '. (new DateTimeImmutable())->format('Y-m-d H:i:s'). "\r\n", FILE_APPEND | LOCK_EX);

                } catch (Exception $exception) {
                    $message->nack(true, false);
                }

                $message->ack(false);
            });
        }

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
