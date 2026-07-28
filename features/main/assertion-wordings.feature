Feature: Every wording variant of the property assertion steps

  Scenario: Assertion steps accept every wording variant
    Given there is a contact named V with:
      | name |
      | Vera |
    Then contact named V should have:
      | name |
      | Vera |
    And contact named V should exist and have:
      | name |
      | Vera |
    And contact named V should exist and have properties:
      | name |
      | Vera |
    And the contact with id "<foundry:id(contact, V)>" should exist and have:
      | name |
      | Vera |
