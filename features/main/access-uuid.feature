Feature: Test accessing Uuid-based entity ids

  Scenario: Can create and reference Uuid entity
    Given there is an "entity with uid" named "the object"
    Then "entity with uid" named "the object" should exist

  Scenario: Can access last id for specific Uuid entity type
    Given there is an "entity with uid" named "B"
    # Uuid v7 ids are time-ordered, so the database resolves the latest row.
    # We just need to verify the placeholder resolves, the 404 is expected.
    When I am on "/<foundry:lastId(entity with uid)>"
    Then the response status code should be 404

  Scenario: Can access id from reference for Uuid entity
    Given there is an "entity with uid" named "my-uid-entity"
    When I am on "/<foundry:id(entity with uid, my-uid-entity)>"
    Then the response status code should be 404

  Scenario: lastId works when the identifier field is not named "id"
    Given there is an "entity with custom id" named "the object"
    # resolving the placeholder sorts on the actual identifier field ("uuid"):
    # a hardcoded "id" sort field would fail with an "Unrecognized field" error
    When I am on "/<foundry:lastId(entity with custom id)>"
    Then the response status code should be 404
