@noResetDB @skip-with-native-dama
Feature: Feature-level @noResetDB propagates to every scenario

  Scenario: Create a named entity
    Given there is a "generic entity" named "feature-kept" with:
      | prop1 |
      | kept  |
    Then "generic entity" named "feature-kept" should exist

  Scenario: Named reference and data survive the next scenario
    Then "generic entity" named "feature-kept" should have properties:
      | prop1 |
      | kept  |
