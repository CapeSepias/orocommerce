@ticket-BB-14800
@fixture-OroPricingBundle:FractionalProductPrices.yml

Feature: Order product with fractional price in different locales
  In order to use correct decimal separator for fractional prices in different locales
  As an Administrator
    I want to have ability to use fractional prices with appropriate decimal separator for create and edit Order in different locales.
    All fractional prices should be displayed according selected locale.

  Scenario: Feature Background
    Given I login as administrator
    When I go to System/Configuration
    And follow "System Configuration/General Setup/Localization" on configuration sidebar
    And fill "Configuration Localization Form" with:
      | Locale Use Default | false            |
      | Locale             | German (Germany) |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create order
    When I go to Sales/Orders
    And click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer      | first customer |
      | Customer User | Amanda Cole    |
      | Product       | PSKU1          |
      | Quantity      | 500            |
      | Price         | 12,99          |
    Then I see next line item taxes for backoffice order:
      | SKU   | Unit Price Incl Tax | Unit Price Excl Tax | Unit Price Tax Amount | Row Total Incl Tax | Row Total Excl Tax | Row Total Tax Amount |
      | PSKU1 | 12,99 $             | 12,99 $             | 0,00 $                | 6.495,00 $            | 6.495,00 $            | 0,00 $               |
    And I see next line item discounts for backoffice order:
      | SKU   | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | PSKU1 | 6.495,00 $         | 6.495,00 $         | 0,00 $   |

    When I click "Save and Close"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see Order with:
      | Subtotal  | 6.495,00 $ |

    When I click "Line Items"
    Then I should see following "Backend Order Line Items Grid" grid:
      | SKU   | Product   | Quantity | Product Unit Code | Price   |
      | PSKU1 | Product 1 | 500      | each              | 12,99 $ |

    When I click "Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal | Amount     |
      | Subtotal | 6.495,00 $ |
      | Total    | 6.495,00 $ |

  Scenario: Edit order
    When I click "Edit"
    Then "Order Form" must contains values:
      | Price | 12,9900 |

    When I click "Line Items"
    Then I see next line item taxes for backoffice order:
      | SKU   | Unit Price Incl Tax | Unit Price Excl Tax | Unit Price Tax Amount | Row Total Incl Tax | Row Total Excl Tax | Row Total Tax Amount |
      | PSKU1 | 12,99 $             | 12,99 $             | 0,00 $                | 6.495,00 $            | 6.495,00 $            | 0,00 $               |
    And I see next line item discounts for backoffice order:
      | SKU   | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | PSKU1 | 6.495,00 $         | 6.495,00 $         | 0,00 $   |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount     |
      | Subtotal | 6.495,00 $ |
      | Total    | 6.495,00 $ |

    When I click "Add Special Discount"
    And I type "123,45" in "Discount Value"
    And I type "Christmas discounts" in "Discount Description"
    Then I should see "123,45 $ (1,9%)"

    When I click "Apply"
    And I click "Save and Close"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

    When I click "Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal                       | Amount     |
      | Subtotal                       | 6.495,00 $ |
      | Christmas discounts (Discount) | -123,45 $  |
      | Total                          | 6.371,55 $ |

  Scenario: Discount on the view page
    When I click "Add Special Discount"
    And I type "678,90" in "Discount Value"
    And I type "Additional discounts" in "Discount Description"
    Then I should see "678,90 $ (10,45%)"

    When I click "Apply"
    Then I should see "Order Discount successfully added" flash message
    And I click "Totals"
    And I see next subtotals for "Backend Order":
      | Subtotal                        | Amount     |
      | Subtotal                        | 6.495,00 $ |
      | Christmas discounts (Discount)  | -123,45 $  |
      | Additional discounts (Discount) | -678,90 $  |
      | Total                           | 5.692,65 $ |
