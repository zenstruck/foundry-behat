Feature: Fixtures in disabled mode - Part 2

  @withFixture(behat-generic-entity)
  Scenario: A new feature reloads the story without any reset, rows accumulate
    Then 2 "generic entities" should exist
    And "generic entity" named "generic fixture" should exist
