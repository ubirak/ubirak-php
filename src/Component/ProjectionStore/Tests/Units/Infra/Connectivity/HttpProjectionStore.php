<?php
declare(strict_types=1);

namespace Ubirak\Component\ProjectionStore\Tests\Units\Infra\Connectivity;

use atoum;

class HttpProjectionStore extends atoum
{
    public function test it fetch projection()
    {
        $this
            ->given(
                $httpClient = new \Http\Mock\Client(),
                $httpClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], 'my projection')),
                $this->newTestedInstance($httpClient, 'hostname')
            )
            ->when(
                $result = $this->testedInstance->fetchProjection('myProjection')
            )
            ->then
                ->variable($result)
                    ->isEqualTo('my projection')
            ->and($requests = $httpClient->getRequests())
                ->phpArray($requests)
                    ->hasSize(1)
            ->and($request = $requests[0])
                ->phpString($request->getMethod())
                    ->isEqualTo('GET')
                ->phpString((string) $request->getUri())
                    ->isEqualTo('http://hostname/projection/myProjection/result')
                ->phpString($request->getHeader('Content-Type')[0])
                    ->isEqualTo('application/json')
        ;
    }

    public function test it fetch projection of stream()
    {
        $this
            ->given(
                $httpClient = new \Http\Mock\Client(),
                $httpClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], 'my dedicated projection')),
                $this->newTestedInstance($httpClient, 'hostname')
            )
            ->when(
                $result = $this->testedInstance->fetchProjectionOf('myProjection', 'myStream')
            )
            ->then
                ->variable($result)
                    ->isEqualTo('my dedicated projection')
            ->and($requests = $httpClient->getRequests())
                ->phpArray($requests)
                    ->hasSize(1)
            ->and($request = $requests[0])
                ->phpString($request->getMethod())
                    ->isEqualTo('GET')
                ->phpString((string) $request->getUri())
                    ->isEqualTo('http://hostname/projection/myProjection/state?partition=myStream')
                ->phpString($request->getHeader('Content-Type')[0])
                    ->isEqualTo('application/json')
        ;
    }

    public function test wrong status code lead to unknown projection()
    {
        $this
            ->given(
                $httpClient = new \Http\Mock\Client(),
                $httpClient->addResponse(new \GuzzleHttp\Psr7\Response(404, [], '')),
                $this->newTestedInstance($httpClient, 'hostname')
            )
            ->exception(function () {
                $this->testedInstance->fetchProjection('myProjection');
            })
                ->isInstanceOf(\Ubirak\Component\ProjectionStore\Domain\UnknownProjection::class)
        ;
    }

    /**
     * @dataProvider invalidStatusCode
     */
    public function test invalid status code lead to error($invalidStatusCode)
    {
        $this
            ->given(
                $httpClient = new \Http\Mock\Client(),
                $httpClient->addResponse(new \GuzzleHttp\Psr7\Response($invalidStatusCode, [], '')),
                $this->newTestedInstance($httpClient, 'hostname')
            )
            ->exception(function () {
                $this->testedInstance->fetchProjection('myProjection');
            })
                ->message->contains('Error during projection request')
        ;
    }

    protected function invalidStatusCode()
    {
        return [
            ['invalidStatusCode' => 201],
            ['invalidStatusCode' => 204],
            ['invalidStatusCode' => 301],
            ['invalidStatusCode' => 302],
            ['invalidStatusCode' => 400],
            ['invalidStatusCode' => 408],
            ['invalidStatusCode' => 500],
            ['invalidStatusCode' => 504],
        ];
    }
}
