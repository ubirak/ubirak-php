@moco
Feature: Event bus listener

    In order to make async handling
    As a cli user
    I should be able to run async event listener

    Background:
        Given a third party endpoint

    Scenario: Successful handling
        Given third party app is up
        When I want to call third party app
        Then the third party app should be called

    Scenario: Failed handling
        Given third party app is down
        When I want to call third party app
        Then the third party app should not be called

    Scenario: Successful handling after a first fail
        Given third party app is down
        When I want to call third party app
        And third party app is up
        Then the third party app should be called
