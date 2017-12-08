<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Ubirak\Component\EventBus\Domain\EventBusPublisher;
use Ubirak\Component\EventBus\Domain\NormalizedDomainEvent;
use Ubirak\MocoBehatExtension\MocoWriter;
use Ubirak\RestApiBehatExtension\Json\JsonContext;
use Ubirak\RestApiBehatExtension\Rest\RestApiBrowser;

class FeatureContext implements Context
{
    private $restApiBrowser;

    private $mocoWriter;

    private $ackUrl;

    private $nackUrl;

    private $jsonContext;

    private $faker;

    private $endpoint;

    public function __construct(
        RestApiBrowser $restApiBrowser,
        MocoWriter $mocoWriter,
        EventBusPublisher $eventBusPublisher
    ) {
        $this->restApiBrowser = $restApiBrowser;
        $this->mocoWriter = $mocoWriter;
        $this->eventBusPublisher = $eventBusPublisher;
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->jsonContext = $environment->getContext(JsonContext::class);
    }

    /**
     * @Given a third party endpoint
     */
    public function aThirdPartyEndpoint()
    {
        $this->endpoint = parse_url($this->faker->url, PHP_URL_PATH);
    }

    /**
     * @Given third party app is up
     */
    public function thirdPartyAppIsUp()
    {
        if (null === $this->ackUrl) {
            $this->ackUrl = $this->createAckEndpoint();
        }

        if (null !== $this->nackUrl) {
            // because we mock the same uri in the same scenario we need to reset
            $this->mocoWriter->reset();
        }
        $this->mocoWriter->mockHttpCall(
            ['uri' => $this->endpoint],
            ['status' => 200, 'json' => 'ok'],
            [
                'complete' => ['get' => ['url' => $this->ackUrl]],
            ]
        );
        $this->mocoWriter->writeForMoco();
    }

    /**
     * @Given third party app is down
     */
    public function thirdPartyAppIsDown()
    {
        if (null === $this->nackUrl) {
            $this->nackUrl = $this->createAckEndpoint();
        }

        $this->mocoWriter->mockHttpCall(
            ['uri' => $this->endpoint],
            ['status' => 502],
            [
                'complete' => ['get' => ['url' => $this->nackUrl]],
            ]
        );
        $this->mocoWriter->writeForMoco();
    }

    /**
     * @When I want to call third party app
     */
    public function iWantToCallThirdPartyApp()
    {
        $this->eventBusPublisher->publish(...[
            new NormalizedDomainEvent(
                $this->faker->uuid,
                'third_party_was_called.common.karibbu',
                [
                    'aggregate_id' => $this->faker->uuid,
                    'endpoint' => $this->endpoint,
                ]
            ),
        ]);
    }

    /**
     * @Then the third party app should be called
     */
    public function theThirdPartyAppShouldBeCalled()
    {
        $this->restApiBrowser->sendRequestUntil('GET', $this->getAckEnpointStatsUrl($this->ackUrl), null, function () {
            $this->jsonContext->theJsonPathExpressionShouldBeEqualToJson('request_count', 1);
        });
    }

    /**
     * @Then the third party app should not be called
     */
    public function theThirdPartyAppShouldNotBeCalled()
    {
        $this->restApiBrowser->sendRequestUntil('GET', $this->getAckEnpointStatsUrl($this->nackUrl), null, function () {
            $this->jsonContext->theJsonPathExpressionShouldBeEqualToJson('request_count', 1);
        });
    }

    private function createAckEndpoint()
    {
        $this->restApiBrowser->setRequestHeader('Content-Type', 'application/json');
        $this->restApiBrowser->sendRequest('POST', 'http://requestb.localtest/api/v1/bins');

        $response = $this->restApiBrowser->getResponse();

        if (200 !== $response->getStatusCode()) {
            throw new \LogicException('Cannot create RequestBin : '.$response->getStatusCode());
        }

        $payload = json_decode($response->getBody(), true);

        return sprintf('http://requestb.localtest/%s', $payload['name']);
    }

    private function getAckEnpointStatsUrl($ackUrl)
    {
        $id = str_replace('http://requestb.localtest/', '', $ackUrl);

        return sprintf('http://requestb.localtest/api/v1/bins/%s', $id);
    }
}
