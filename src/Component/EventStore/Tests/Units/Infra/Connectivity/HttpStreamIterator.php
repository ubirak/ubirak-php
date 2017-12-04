<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Tests\Units\Infra\Connectivity;

use Ubirak\Component\EventStore\Domain\NormalizedDomainEvent;
use atoum;

class HttpStreamIterator extends atoum
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

    public function test it fetch a stream in chronological order()
    {
        $this
            ->given(
                $this->httpClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], <<<'EOT'
{
  "headOfStream": true,
  "links": [
  ],
  "entries": [
    {
        "eventId": "74609f70-30f1-4412-b8c2-59a32e9cda66",
        "eventType": "allowed_font_was_added.website_designer.karibbu",
        "eventNumber": 4,
        "data": "{ \"design_id\": \"yNWVzdqxCCUL87kzYxPSSd\" }"
    },
    {
        "eventId": "74609f70-30f1-4412-b8c2-59a32e9cda66",
        "eventType": "allowed_font_was_added.website_designer.karibbu",
        "eventNumber": 3,
        "data": "{ \"design_id\": \"yNWVzdqxCCUL87kzYxPSSd\" }"
    }
  ]
}
EOT
                )),
                $this->newTestedInstance(
                    $this->httpClient,
                    $this->serializer,
                    'stream',
                    'hostname'
                )
            )
            ->when(
                $first = $this->testedInstance->current(),
                $this->testedInstance->next(),
                $second = $this->testedInstance->current()
            )->then
                ->object($first)
                    ->isEqualTo(new NormalizedDomainEvent(
                        '74609f70-30f1-4412-b8c2-59a32e9cda66',
                        'allowed_font_was_added.website_designer.karibbu',
                        ['design_id' => 'yNWVzdqxCCUL87kzYxPSSd'],
                        [],
                        3
                    ))
                ->object($second)
                    ->isEqualTo(new NormalizedDomainEvent(
                        '74609f70-30f1-4412-b8c2-59a32e9cda66',
                        'allowed_font_was_added.website_designer.karibbu',
                        ['design_id' => 'yNWVzdqxCCUL87kzYxPSSd'],
                        [],
                        4
                    ))
        ;
    }

    public function test it fetch history of a stream through multiple pages()
    {
        $this
            ->given(
                $this->httpClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], <<<'EOT'
{
  "headOfStream": true,
  "links": [
    {
        "uri": "http://nexturl",
        "relation": "next"
    }
  ],
  "entries": [
    {
        "eventId": "74609f70-30f1-4412-b8c2-59a32e9cda66",
        "eventType": "allowed_font_was_added.website_designer.karibbu",
        "eventNumber": 9,
        "data": "{\n  \"design_id\": {\n    \"value\": \"yNWVzdqxCCUL87kzYxPSSd\"\n  },\n  \"font_family\": \"muli\",\n  \"font_appearance\": \"readable\"\n}"
    }
  ]
}
EOT
                )),
$this->httpClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], <<<'EOT'
{
  "headOfStream": false,
  "links": [
  ],
  "entries": [
    {
        "eventId": "74609f70-30f1-4412-b8c2-59a32e9cda66",
        "eventType": "allowed_font_was_added.website_designer.karibbu",
        "eventNumber": 5,
        "data": "{ \"design_id\": \"yNWVzdqxCCUL87kzYxPSSd\" }"
    }
  ]
}
EOT
                )),
                $this->newTestedInstance(
                    $this->httpClient,
                    $this->serializer,
                    'stream',
                    'hostname'
                )
            )
            ->when(
                $this->testedInstance->next(),
                $second = $this->testedInstance->current()
            )
            ->then
                ->object($second)
                    ->isEqualTo(new NormalizedDomainEvent(
                        '74609f70-30f1-4412-b8c2-59a32e9cda66',
                        'allowed_font_was_added.website_designer.karibbu',
                        ['design_id' => 'yNWVzdqxCCUL87kzYxPSSd'],
                        [],
                        5
                    ))
                ->phpArray($this->httpClient->getRequests())
                    ->hasSize(2)
                ->and($secondRequest = $this->httpClient->getRequests()[1])
                ->castToString($secondRequest->getUri())
                    ->isEqualTo('http://nexturl?embed=body')
        ;
    }

    public function test it leads to unknown stream exception()
    {
        $this
            ->given(
                $this->httpClient->addResponse(new \GuzzleHttp\Psr7\Response(404, [], ''))
            )
            ->exception(function () {
                $this->newTestedInstance(
                    $this->httpClient,
                    $this->serializer,
                    'stream',
                    'hostname'
                );
            })
                ->isInstanceOf(\Ubirak\Component\EventStore\Domain\UnknownStream::class)
        ;
    }
}
