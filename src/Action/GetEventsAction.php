<?php

namespace CQRSApi\Action;

use CQRS\Domain\Message\EventMessageInterface;
use CQRS\EventStore\EventStoreInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Zend\Diactoros\Response\JsonResponse;

class GetEventsAction
{
    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $queryParams = $request->getQueryParams();

        $previousEventId = $queryParams['previousEventId'] ?? null;
        $count = $queryParams['count'] ?? 10;

        if ($previousEventId) {
            $previousEventId = Uuid::fromString($previousEventId);
        }

        $iterator = $this->eventStore->iterate($previousEventId);

        $selfUrl = (string) $request->getUri();

        $lastEventId = $previousEventId;
        $events = [];
        $i = 0;
        /** @var EventMessageInterface $event */
        foreach ($iterator as $event) {
            if ($i >= $count) {
                break;
            }

            $events[] = $event;
            $i++;
            $lastEventId = $event->getId()->toString();
        }

        $nextUrl = (string) $request->getUri()->withQuery(http_build_query([
            'previousEventId' => (string)$lastEventId,
        ]));

        $data = [
            '_links' => [
                'self' => $selfUrl,
                'next' => $nextUrl
            ],
            'count' => count($events),
            '_embedded' => [
                'event' => array_values($events),
            ],
        ];

        return new JsonResponse($data);
    }
}
