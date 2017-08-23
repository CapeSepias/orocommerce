@fixture-OroPromotionBundle:promotions_for_coupons.yml
Feature: Generation of coupons

  Scenario: Generate coupons with suffix and prefix
    Given I login as administrator
    And go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill form with:
      | Promotion       | order Discount Promotion         |
      | Coupon Quantity | 10                               |
      | Uses per Coupon | 5                                |
      | Uses per Person | 5                                |
      | Valid Until     | <DateTime:Jul 1, 2018, 12:00 AM> |
      | Code Length     | 1                                |
      | Code Type       | Numeric                          |
      | Code Prefix     | test                             |
      | Code Suffix     | new                              |
    And I click "Generate"
    Then I should see "Coupons have been generated successfully" flash message
    And I should see "test7new" in grid with following data:
      | Uses per Coupon | 5                        |
      | Uses per Person | 5                        |
      | Promotion       | order Discount Promotion |

  Scenario: Generate more coupons then available combination
    Given I go to Marketing/Promotions/Coupons
    And there are 10 records in grid
    And I click "Generate Coupon"
    When I fill form with:
      | Coupon Quantity | 150                              |
      | Uses per Coupon | 5                                |
      | Uses per Person | 5                                |
      | Valid Until     | <DateTime:Jul 1, 2018, 12:00 AM> |
      | Code Length     | 2                                |
      | Code Type       | Numeric                          |
    And I click "Generate"
    Then I should see "Coupons have been generated successfully" flash message
    And I should see "Total Of 160 Records"

  Scenario: Generate coupons with minimum data
    Given I go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill form with:
      | Coupon Quantity | 1          |
      | Code Length     | 4          |
      | Code Type       | Alphabetic |
    And I click "Generate"
    Then I should see "Coupons have been generated successfully" flash message

  Scenario: Check that grid contains only Promotions that can use Coupons
    Given I go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    And I press "Promotions Grid Button"
    And I should see "Order Discount Promotion"
    And I should not see "Shipping Discount Promotion"
