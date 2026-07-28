Feature: Using a subset of the built-in contexts

  Scenario: Creation steps and placeholders work without the assertion context
    Given there is a "generic entity" named "the object" with:
      | prop1 |
      | foo   |
    When I am on "/orm/update/<foundry:id(generic entity, the object)>/bar"
    Then the response status code should be 200

  Scenario: The assertion wording is free for the user's own definition (!)
    Given there is a contact
    Then 1 "contact" should exist
    Then a "LogicException" exception should be thrown containing message "custom step called"
