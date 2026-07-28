Feature: Test objects creation

  Scenario: Can create entity with properties via PyTable
    Given there is a contact named A with:
      | name     |
      | John Doe |
    Then 1 contact should exist
    Then contact named A should have properties:
      | name     |
      | John Doe |

  Scenario: Can create one entity with two lines in the PyTable (!)
    Given there is a contact named A with:
      | name     |
      | John Doe |
      | Jane Doe |
    Then an "InvalidArgumentException" exception should be thrown containing message "Expected exactly one line of properties to create one object, got 2 lines"

  Scenario: Can create entity with properties via PyTable (!)
    Given there is a "i don't exist"
    Then an "FactoryNotResolvable" exception should be thrown containing message "Cannot resolve factory for name \"i don't exist\""

  Scenario: Multiple objects created with the same reference is handled (!)
    Given there is a contact named A
    And there is a contact named A
    Then an "ObjectAlreadyRegistered" exception should be thrown containing message "Object \"A\" is already registered"

  Scenario: Reference to a non existent objet handled (!)
    Then contact named "I don't exist" should have properties:
      | foo |
      | bar |
    Then an "ObjectNotFound" exception should be thrown containing message "Object \"contact I don't exist\" was not found"

  Scenario: Invalid property name handled (!)
    Given there is a contact named A with:
      | foo |
      | bar |
    Then an "InvalidArgumentException" exception should be thrown containing message "Cannot set attribute \"foo\" for object"

  Scenario: Can create multiple entities via PyTable
    Then 0 contacts should exist
    Given there are contacts with:
      | _ref | name     |
      | A    | John Doe |
      | B    | Jane Doe |
    Then 2 contacts should exist
    Then contact named A should have properties:
      | name     |
      | John Doe |
    Then contact named B should have properties:
      | name     |
      | Jane Doe |

  Scenario: Multiple objects created within a table with the same reference is handled (!)
    Given there are contacts with:
      | _ref | name     |
      | A    | John Doe |
      | A    | Jane Doe |
    Then an "ObjectAlreadyRegistered" exception should be thrown containing message "Object \"A\" is already registered"

  Scenario: Can reference another object
    Given there is a category named MyCategory
    And there is an address named "the address"
    And there is a contact named A with:
      | name     | category                    | address                     |
      | John Doe | <foundry:object(category, MyCategory)> | <foundry:object(address, the address)> |
    When I am on "/"
    Then contact named A should have properties:
      | name     | category                    | address                     |
      | John Doe | <foundry:object(category, MyCategory)> | <foundry:object(address, the address)> |
    Then 1 contact should exist
    Then 1 category should exist
    Then 1 address should exist

  Scenario: Can reference another object with a quoted name
    Given there is a category named "My Category"
    And there is a contact named A with:
      | name     | category                                  |
      | John Doe | <foundry:object(category, "My Category")> |
    Then contact named A should have properties:
      | category                                  |
      | <foundry:object(category, "My Category")> |

  Scenario: Can reference the latest object of a type
    Given there is a category named "old"
    And there is a category named "new"
    And there is a contact named A with:
      | name     | category                       |
      | John Doe | <foundry:lastObject(category)> |
    # the expected side resolves through the registry by name, independently from
    # the database: a lastObject resolving to anything but the latest category fails here
    Then contact named A should have properties:
      | category                        |
      | <foundry:object(category, new)> |
    # the previous assertion pinned the actual relation to "new": this one now
    # discriminates lastObject used on the expected side
    Then contact named A should have properties:
      | category                       |
      | <foundry:lastObject(category)> |

  Scenario: Can reference another object with short syntax
    Given there is a category named MyCategory
    And there is an address named "the address"
    And there is a contact named A with:
      | name     | category   | address     |
      | John Doe | MyCategory | the address |
    When I am on "/"
    Then contact named A should have properties:
      | name     | category   | address     |
      | John Doe | MyCategory | the address |

  Scenario: Can reference object with date
    Given there is a "generic entity" named "GE" with:
      | prop1 | propInteger | date       | dateMutable | bool  | float | stringEnum | intEnum |
      | foo   | 1           | 2026-01-01 | 2026-01-02  | false | 3.14  | some_value | 0       |
    When I am on "/"
    Then "generic entity" named "GE" should have properties:
      | prop1 | propInteger | date       | dateMutable | bool  | float | stringEnum | intEnum |
      | foo   | 1           | 2026-01-01 | 2026-01-02  | false | 3.14  | some_value | 0       |

  Scenario: Wrong assertion on string correctly handled (!)
    Given there is a "generic entity" named "GE" with:
      | prop1 |
      | foo   |
    Then "generic entity" named "GE" should have properties:
      | prop1 |
      | bar   |
    Then an "AssertionFailedError" exception should be thrown matching pattern "/foo(.*)bar/"

  Scenario: Wrong assertion on string correctly handled (!)
    Given there is a "generic entity" named "GE" with:
      | prop1 | propInteger |
      | foo   | 1           |
    Then "generic entity" named "GE" should have properties:
      | propInteger |
      | 42          |
    Then an "AssertionFailedError" exception should be thrown matching pattern "/1(.*)42/"

  Scenario: Wrong assertion on date correctly handled (!)
    Given there is a "generic entity" named "GE" with:
      | prop1 | date       |
      | foo   | 2026-01-01 |
    Then "generic entity" named "GE" should have properties:
      | date       |
      | 2026-01-02 |
    Then an "AssertionFailedError" exception should be thrown matching pattern "/2026/"

  Scenario: Wrong assertion on bool correctly handled (!)
    Given there is a "generic entity" named "GE" with:
      | prop1 | bool |
      | foo   | true |
    Then "generic entity" named "GE" should have properties:
      | bool  |
      | false |
    Then an "AssertionFailedError" exception should be thrown matching pattern "/true(.*)false/"

  Scenario: Wrong assertion on bool correctly handled (!)
    Given there is a "generic entity" named "GE" with:
      | prop1 | bool  |
      | foo   | false |
    Then "generic entity" named "GE" should have properties:
      | bool |
      | true |
    Then an "AssertionFailedError" exception should be thrown matching pattern "/false(.*)true/"

  Scenario: Wrong assertion on enum correctly handled (!)
    Given there is a "generic entity" named "GE" with:
      | prop1 | stringEnum |
      | foo   | some_value |
    Then "generic entity" named "GE" should have properties:
      | stringEnum  |
      | other_value |
    Then an "AssertionFailedError" exception should be thrown matching pattern "/StringBackedEnum/"

  Scenario: Can compare null
    Given there is a "generic entity" named "GE" with:
      | prop1 | bool |
      | foo   | null |
    When I am on "/"
    Then "generic entity" named "GE" should have properties:
      | prop1 | bool |
      | foo   | null |

  Scenario: Wrong assertion with null works (!)
    Given there is a "generic entity" named "GE" with:
      | prop1 | bool |
      | foo   | null |
    When I am on "/"
    Then "generic entity" named "GE" should have properties:
      | prop1 | bool   |
      | foo   | "null" |
    Then an "AssertionFailedError" exception should be thrown matching pattern "/null(.*)null/"

  Scenario: Wrong assertion with null works in the other way (!)
    Given there is a "generic entity" named "GE" with:
      | prop1 | date       |
      | foo   | 2026-01-01 |
    When I am on "/"
    Then "generic entity" named "GE" should have properties:
      | prop1 | date |
      | foo   | null |
    Then an "AssertionFailedError" exception should be thrown matching pattern "/DateTimeImmutable(.*)null/"

  Scenario: Can use a factory with disambiguated name
    Given there is a "tag2"
    Then 1 tag2 should exist

  Scenario: Can use a factory with changed name & plural
    Given there is a "child of contact"
    And there is a "child of contact"
    Then 2 "children of contact" should exist

  Scenario: Cannot use a factory with ambiguous name (!)
    Given there is a "tag"
    Then an "FactoryNotResolvable" exception should be thrown containing message "Multiple factories found for name \"tag\""
