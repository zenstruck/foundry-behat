Feature: Fixtures in disabled mode - Part 1

  @withFixture(behat-generic-entity)
  Scenario: The fixture is loaded for the first tagged scenario
    Then 1 "generic entity" should exist
    And "generic entity" named "generic fixture" should exist

  @withFixture(behat-generic-entity)
  Scenario: The story is cached within the feature, no new rows
    Then 1 "generic entity" should exist
