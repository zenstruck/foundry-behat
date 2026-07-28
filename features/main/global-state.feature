Feature: Global state stories load exactly once per test

  Scenario: The global state is available
    Then 1 "entity with custom id" should exist

  Scenario: The global state is not duplicated across scenarios
    # with DAMA's native extension, the global stories are committed by the initial
    # reset: reloading them on every scenario would make this count grow
    Then 1 "entity with custom id" should exist
