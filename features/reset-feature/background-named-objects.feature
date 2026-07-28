Feature: Background re-creates named objects before every scenario

  Background:
    Given there is a contact named "bg-contact" with:
      | name |
      | Bob  |

  Scenario: First scenario uses the background contact
    Then contact named "bg-contact" should have properties:
      | name |
      | Bob  |
    And 1 contact should exist

  Scenario: Second scenario re-registers the background contact
    # without a per-scenario duplicate window this Background would fail
    # with ObjectAlreadyRegistered: the registry survives within the feature
    Then contact named "bg-contact" should have properties:
      | name |
      | Bob  |
    # rows accumulate in feature mode: each scenario's Background created one
    And 2 contacts should exist
