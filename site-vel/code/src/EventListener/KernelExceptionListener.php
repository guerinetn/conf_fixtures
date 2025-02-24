<?php

namespace App\EventListener;

use App\Exception\IdpException;
use App\Exception\VelException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelExceptionListener implements EventSubscriberInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    /**
     * @throws \JsonException
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof VelException) {
            $this->log($event);
            $event->setResponse(
                new JsonResponse(
                    $exception->errors,
                    0 !== $exception->getCode() ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR
                )
            );
        }

        if ($exception instanceof IdpException) {
            $this->log($event);
            $event->setResponse(new JsonResponse(
                status: 0 !== $exception->getCode() ? $exception->getCode() : Response::HTTP_UNAUTHORIZED
            ));
        }
    }

    private function getEventContext(ExceptionEvent $event): array
    {
        return [
            'request' => $event->getRequest()->request,
            'query' => $event->getRequest()->query,
            'attributes' => $event->getRequest()->attributes,
            'files' => $event->getRequest()->files,
            'content' => $event->getRequest()->getContent(),
            'method' => $event->getRequest()->getMethod(),
            'route' => $event->getRequest()->getRequestUri(),
        ];
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @throws \JsonException
     */
    public function log(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $log = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'errors' => $exception->errors ?? null,
            'context' => array_merge(
                method_exists($exception, 'getContext') ? $exception->getContext() : [],
                $this->getEventContext($event)
            ),
            'called' => [
                'file' => $exception->getTrace()[0]['file'],
                'line' => $exception->getTrace()[0]['line'],
                'function' => $exception->getTrace()[0]['function'],
            ],
            'occurred' => [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
            'trace' => [],
        ];

        foreach ($exception->getTrace() as $trace) {
            $log['trace'][] = [
                'file' => $trace['file'],
                'line' => $trace['line'],
                'function' => $trace['function'],
            ];
        }

        if ($exception->getPrevious() instanceof \Exception) {
            $log += [
                'previous' => [
                    'message' => $exception->getPrevious()->getMessage(),
                    'exception' => get_class($exception->getPrevious()),
                    'file' => $exception->getPrevious()->getFile(),
                    'line' => $exception->getPrevious()->getLine(),
                ],
            ];
        }

        $this->logger->error(json_encode($log, JSON_THROW_ON_ERROR));
    }
}
