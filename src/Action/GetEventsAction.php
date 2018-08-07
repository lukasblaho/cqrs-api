<?php
declare(strict_types=1);

namespace CQRSApi\Action;

use CQRS\Domain\Message\EventMessageInterface;
use CQRS\EventStore\EventStoreInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Zend\Diactoros\Response\JsonResponse;

final class GetEventsAction implements RequestHandlerInterface
{
    /** @var EventStoreInterface  */
    private $eventStore;

    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
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
