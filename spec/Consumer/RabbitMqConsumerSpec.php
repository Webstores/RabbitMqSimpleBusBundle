<?php

namespace spec\SyliusLabs\RabbitMqSimpleBusBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use SyliusLabs\RabbitMqSimpleBusBundle\Bus\MessageBusInterface;
use SyliusLabs\RabbitMqSimpleBusBundle\Denormalizer\DenormalizationFailedException;
use SyliusLabs\RabbitMqSimpleBusBundle\Denormalizer\DenormalizerInterface;

/**
 * @author Kamil Kokot <kamil@kokot.me>
 */
final class RabbitMqConsumerSpec extends ObjectBehavior
{
    function let(DenormalizerInterface $denormalizer, MessageBusInterface $messageBus, LoggerInterface $logger)
    {
        $this->beConstructedWith($denormalizer, $messageBus, $logger);
    }

    function it_is_a_oldsound_rabbitmq_bundle_consumer()
    {
        $this->shouldImplement(ConsumerInterface::class);
    }

    function it_uses_message_bus_to_dispatch_denormalized_message(
        DenormalizerInterface $denormalizer,
        MessageBusInterface $messageBus
    ) {
        $amqpMessage = new AMQPMessage('Message body');
        $denormalizedMessage = new \stdClass();

        $denormalizer->denormalize($amqpMessage)->willReturn($denormalizedMessage);

        $messageBus->handle($denormalizedMessage)->shouldBeCalled();

        $this->execute($amqpMessage);
    }

    function it_logs_exception_message_if_denormalization_fails(
        DenormalizerInterface $denormalizer,
        LoggerInterface $logger
    ) {
        $amqpMessage = new AMQPMessage('Invalid message body');

        $denormalizer->denormalize($amqpMessage)->willThrow(new DenormalizationFailedException('Message body is invalid'));

        $logger->error(Argument::containingString('Message body is invalid'))->shouldBeCalled();

        $this->execute($amqpMessage);
    }

    function it_logs_any_error(
        DenormalizerInterface $denormalizer,
        LoggerInterface $logger
    ) {
        $amqpMessage = new AMQPMessage('Invalid message body');

        $denormalizer->denormalize($amqpMessage)->will(function () {
            /** @noinspection PhpUndefinedVariableInspection */
            return $undefinedVariable;
        });

        $logger->error(Argument::containingString('notice: Undefined variable: undefinedVariable'))->shouldBeCalled();

        $this->execute($amqpMessage);
    }
}
