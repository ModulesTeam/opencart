# features/test.feature
  Feature: Test
    In order to test Functional Test Integration
    As a developer
    I need to be able to login on Opencart

  @javascript
  Scenario: Login into Opencart
    Given I am on "/index.php?route=account/login"
    When I fill in "email" with "test@test.com"
    And I fill in "password" with "test12"
    And I press "Login"
    Then I wait for text "My Account" to appear, for 10 seconds