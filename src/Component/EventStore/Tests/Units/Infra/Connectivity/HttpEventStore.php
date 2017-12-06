<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Tests\Units\Infra\Connectivity;

use atoum;
use function GuzzleHttp\Psr7\copy_to_string;
use Ubirak\Component\EventStore\Domain\NormalizedDomainEvent;

class HttpEventStore extends atoum
{
    private $httpClient;

    private $serializer;

    public function beforeTestMethod($testMethod)
    {
        $this->httpClient = new \Http\Mock\Client();
        $this->serializer = new \Symfony\Component\Serializer\Serializer(
            [
                new \Symfony\Component\Serializer\Normalizer\PropertyNormalizer(
                    null,
                    new \Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter()
                ),
            ],
            [
                new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            ]
        );
    }

    public function test it commit events()
    {
        $this
            ->given(
                $this->httpClient->addResponse(new \GuzzleHttp\Psr7\Response(201, [], '')),
                $this->newTestedInstance(
                    $this->httpClient,
                    'hostname',
                    $this->serializer
                ),
                $events = [
                    new NormalizedDomainEvent('id', 'foo.bor', ['foo' => 'bar']),
                ]
            )
            ->when(
                $this->testedInstance->commitToStream('foo', $events)
            )
            ->then($requests = $this->httpClient->getRequests())
                ->phpArray($requests)
                    ->hasSize(1)
            ->and($request = $requests[0])
                ->phpString($request->getMethod())
                    ->isEqualTo('POST')
                ->phpString((string) $request->getUri())
                    ->isEqualTo('http://hostname/streams/foo')
                ->phpString($request->getHeader('Accept')[0])
                    ->isEqualTo('application/json')
                ->phpString($request->getHeader('Content-Type')[0])
                    ->isEqualTo('application/vnd.eventstore.events+json')
                ->phpString(copy_to_string($request->getBody()))
                    ->isEqualTo('[{"eventId":"id","eventType":"foo.bor","data":{"foo":"bar"},"metadata":[]}]')
        ;
    }

    public function test it validate the optimistic concurency()
    {
        $this
            ->given(
                $this->httpClient->addResponse(new \GuzzleHttp\Psr7\Response(400, [
                        'Es-ExpectedVersion' => 42,
                    ], '')),
                $this->newTestedInstance(
                    $this->httpClient,
                    'hostname',
                    $this->serializer
                )
            )
            ->then
                ->exception(function () {
                    $this->testedInstance->commitToStream('foo', [new NormalizedDomainEvent('id', 'foo.bor', ['foo' => 'bar'])], 12);
                })
                    ->isInstanceOf(\Ubirak\Component\EventStore\Domain\OptimisticConcurrencyConflicted::class)
        ;
    }

    /**
     * @dataProvider rejectedStatusCodeWhileComitting
     */
    public function test it rejetcs wrong http status code($statusCode)
    {
        $this
            ->given(
                $this->httpClient->addResponse(new \GuzzleHttp\Psr7\Response($statusCode, [], '')),
                $this->newTestedInstance(
                    $this->httpClient,
                    'hostname',
                    $this->serializer
                )
            )
            ->then
                ->exception(function () {
                    $this->testedInstance->commitToStream('foo', [new NormalizedDomainEvent('id', 'foo.bor', ['foo' => 'bar'])]);
                })
                    ->message->contains("Error during the event store commit request $statusCode")
        ;
    }

    protected function rejectedStatusCodeWhileComitting()
    {
        return [
            ['statusCode' => 200],
            ['statusCode' => 204],
            ['statusCode' => 301],
            ['statusCode' => 400],
            ['statusCode' => 404],
            ['statusCode' => 500],
        ];
    }

    public function test it deletes stream()
    {
        $this
            ->given(
                $this->httpClient->addResponse(new \GuzzleHttp\Psr7\Response(204, [], '')),
                $this->newTestedInstance(
                    $this->httpClient,
                    'hostname',
                    $this->serializer
                )
            )
            ->when(
                $this->testedInstance->deleteStream('foo')
            )
            ->then($requests = $this->httpClient->getRequests())
                ->phpArray($requests)
                    ->hasSize(1)
            ->and($request = $requests[0])
                ->phpString($request->getMethod())
                    ->isEqualTo('DELETE')
                ->phpString((string) $request->getUri())
                    ->isEqualTo('http://hostname/streams/foo')
        ;
    }

    /**
     * @dataProvider rejectedStatusCodeWhileDeleting
     */
    public function test it accepts valid status code to delete stream($rejectedStatusCode)
    {
        $this
            ->given(
                $this->httpClient->addResponse(new \GuzzleHttp\Psr7\Response($rejectedStatusCode, [], '')),
                $this->newTestedInstance(
                    $this->httpClient,
                    'hostname',
                    $this->serializer
                )
            )
            ->exception(function () {
                $this->testedInstance->deleteStream('foo');
            })
                ->hasMessage('Error during eventStore delete request')
        ;
    }

    protected function rejectedStatusCodeWhileDeleting()
    {
        return [
            ['rejectedStatusCode' => 200],
            ['rejectedStatusCode' => 201],
            ['rejectedStatusCode' => 301],
            ['rejectedStatusCode' => 400],
            ['rejectedStatusCode' => 404],
            ['rejectedStatusCode' => 408],
            ['rejectedStatusCode' => 500],
        ];
    }
}
