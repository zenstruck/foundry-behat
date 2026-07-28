Feature: Relations to Uuid-id entities across scenarios

  Scenario: First scenario creates a named Uuid entity graph
    Given there is an "entity with uid" named "parent"
    And there is an "entity with uid" named "child" with:
      | parent                                    |
      | <foundry:object(entity with uid, parent)> |

  Scenario: Second scenario asserts the relation after the kernel rebooted
    # the entity fetched by id hydrates fresh Uuid instances, while the expected
    # side still holds the instances registered by the previous scenario
    Then the "entity with uid" with id "<foundry:id(entity with uid, child)>" should have properties:
      | parent                                    |
      | <foundry:object(entity with uid, parent)> |
