@skip-with-native-dama
Feature: Skip database reset with @noResetDB tag

  Scenario: First scenario creates data
    Given there is a contact
    Then 1 contact should exist

  @noResetDB
  Scenario: Data persists with @noResetDB tag
    Then 1 contact should exist
    Given there is a contact
    Then 2 contacts should exist

  Scenario: Normal reset resumes after @noResetDB
    Then 0 contacts should exist

  @withFixture(behat-contacts)
  Scenario: Fixtures are loaded in a fresh database
    Then 1 contact should exist

  @noResetDB @withFixture(behat-contacts)
  Scenario: Fixtures are not reloaded and named references survive with @noResetDB
    Then 1 contact should exist
    Then contact named "john-doe" should have properties:
      | name     |
      | John Doe |
