@bobopinna @availability @availability_auth
Feature: availability_auth sections
  In order to control student access to sections
  As a teacher
  I need to set authentication conditions which prevent student access

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | auth   |
      | teacher1 | manual |
      | student1 | manual |
      | student2 | email  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Restrict sections based on authentication
    # Section1 for Manual authenticated users only hidden.
    When I edit the section "1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Authentication" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Authentication" "button" in the "Add restriction..." "dialogue"
    Then I should see "Please set" in the "region-main" "region"
    And I set the field "Authentication" to "manual"
    Then I should not see "Please set" in the "region-main" "region"
    And I click on "Save changes" "button"

    # Section2 for Manual authenticated users only.
    When I edit the section "2"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Authentication" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Authentication" "button" in the "Add restriction..." "dialogue"
    And I set the field "Authentication" to "manual"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save changes" "button"

    # Section3 for Email authenticated users only hidden.
    When I edit the section "3"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Authentication" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Authentication" "button" in the "Add restriction..." "dialogue"
    And I set the field "Authentication" to "email"
    And I click on "Save changes" "button"

    # Section4 for Email authenticated users only.
    When I edit the section "4"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Authentication" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Authentication" "button" in the "Add restriction..." "dialogue"
    And I set the field "Authentication" to "email"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save changes" "button"
    And I log out

    # Log in as student.
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Topic 1" in the "region-main" "region"
    And I should see "Topic 2" in the "region-main" "region"
    And I should not see "Topic 3" in the "region-main" "region"
    And I should see "Topic 4" in the "region-main" "region"
    And I log out

    When I am on the "C1" "Course" page logged in as "student2"
    Then I should not see "Topic 1" in the "region-main" "region"
    And I should see "Topic 2" in the "region-main" "region"
    And I should see "Topic 3" in the "region-main" "region"
    And I should see "Topic 4" in the "region-main" "region"
