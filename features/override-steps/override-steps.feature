Feature: Overriding built-in step definitions

  Scenario: Re-worded single creation step
    Given create a contact called "john"
    Then 1 contact should exist
    Then contact named "john" should exist

  Scenario: Re-worded table creation step keeps placeholders and table normalization
    Given the following contacts exist:
      | _ref | name     |
      | A    | John Doe |
      | B    | Jane Doe |
    Then 2 contacts should exist
    Then contact named A should have properties:
      | name     |
      | John Doe |

  Scenario: Re-worded table step with an anonymous regex capture group
    Given a contact exists with:
      | name     |
      | John Doe |
    Then 1 contact should exist

  Scenario: Re-worded id transform (#[Transform] patterns can be overridden too)
    Given create a contact called "john"
    When I am on "/[lastId(contact)]"
    Then the response status code should be 404

  Scenario: Re-declaring the built-in pattern keeps the wording but changes the implementation (!)
    Given create a contact called "john"
    Then the contact with id "<foundry:id(contact, john)>" should exist
    Then a "LogicException" exception should be thrown containing message "overridden implementation called"
