Feature: Test object names starting with digits

  Scenario: Quoted object names can start with digits
    Given there is a "generic entity" named "123" with:
      | prop1 |
      | foo   |
    Then "generic entity" named "123" should exist
    And "generic entity" named "123" should have properties:
      | prop1 |
      | foo   |

  Scenario: Quoted object names can be pure numbers
    Given there is a contact named "007"
    Then contact named "007" should exist

  Scenario: The falsy name "0" is still a valid reference
    Given there is a contact named "0"
    Then contact named "0" should exist

  Scenario: Count assertions still work correctly alongside digit names
    Given there is a contact named "1"
    And there is a contact named "2"
    Then 2 contacts should exist
    And contact named "1" should exist
    And contact named "2" should exist
