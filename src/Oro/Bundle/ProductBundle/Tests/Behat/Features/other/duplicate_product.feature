@regression
@ticket-BB-10111
@ticket-BB-14588
@fixture-OroProductBundle:ProductDuplicateFixture.yml
Feature: Duplicate product
  In order to manage products
  As administrator
  I need to be able to create an independent copy of the original product
  Any changes of this copy should not affect the original product

  Scenario: Open original product and duplicate it
    Given I login as administrator
    And I go to Products/ Products
    And I click Edit Product1 in grid
    And I set Images with:
      | File     | Main  | Listing | Additional |
      | cat1.jpg | 1     | 1       | 1          |
    When I save and duplicate form
    Then I should see "Product has been saved and duplicated" flash message

  Scenario: Verify copied product
    Given I go to Products/ Products
    When I click View PSKU1-1 in grid
    Then Main should be an owner
    And I should see "Disabled" gray status
    And I should see product with:
      | Category          | Category1                  |
      | SKU               | PSKU1-1                    |
      | Name              | Product1                   |
      | Description       | Product1 Description       |
      | Short Description | Product1 Short Description |
      | Is Featured       | Yes                        |
      | New Arrival       | Yes                        |
      | Brand             | Brand1                     |
      | Unit              | item                       |
      | Slugs             | N/A                        |
      | Tax Code          | TaxCode1                   |
    And I should see "No page template"
    And I should see following product additional units:
      | set | 5 |  | Yes |
    And I should see following product images:
      | cat1.jpg | 1 | 1 | 1 |
    And I should see following "ProductPricesGrid" grid:
      | Price List   | Quantity  | Unit | Value  | Currency |
      | Price List 1 | 1         | item | 10     | USD      |
    And I should not see "RelatedProductsViewGrid"
    And I should not see "UpsellProductsViewGrid"

  Scenario: Edit copied product
    Given I click "Edit"
    And I click on "Remove Additional Unit Precision 1"
    And I click "Yes, Delete" in confirmation dialogue
    And I click on "Add More Rows"
    When fill "ProductForm" with:
      | Owner                                  | Extra Business Unit        |
      | SKU                                    | PSKU2                      |
      | Name                                   | Product2                   |
      | URL Slug                               | product2                   |
      | Description                            | Product2 Description       |
      | Short Description                      | Product2 Short Description |
      | Status                                 | Disabled                   |
      | Is Featured                            | No                         |
      | New Arrival                            | No                         |
      | Brand                                  | Brand2                     |
      | PrimaryUnit                            | kilogram                   |
      | PrimaryPrecision                       | 2                          |
      | Additional Unit 2                      | piece                      |
      | Additional Precision 2                 | 10                         |
      | Page Template Use Fallback             | false                      |
      | Page Template                          | Two columns page           |
      | Tax Code                               | TaxCode2                   |
      | Price Quantity 1                       | 2                          |
      | Price Value 1                          | 20                         |
      | Price Unit 1                           | kilogram                   |
    And I click on "Remove Image 1"
    And I set Images with:
      | File     | Main  | Listing | Additional |
      | cat2.jpg | 0     | 0       | 1          |
    And click "Category2"
    And I save and close form
    Then I should see "Product has been saved" flash message
    And Extra Business Unit should be an owner
    And I should see "Disabled" gray status
    And I should see product with:
      | Category          | Category2                  |
      | SKU               | PSKU2                      |
      | Name              | Product2                   |
      | Description       | Product2 Description       |
      | Short Description | Product2 Short Description |
      | Is Featured       | No                         |
      | New Arrival       | No                         |
      | Brand             | Brand2                     |
      | Unit              | kilogram                   |
      | Page Template     | Two columns page           |
      | Tax Code          | TaxCode2                   |
    And I should see following product additional units:
      | piece | 10 |  | No |
    And I should see following product images:
      | cat2.jpg |  |  | 1 |
    And I should see following "ProductPricesGrid" grid:
      | Price List   | Quantity  | Unit     | Value  | Currency |
      | Price List 1 | 2         | kilogram | 20     | USD      |
    And I should not see "RelatedProductsViewGrid"
    And I should not see "UpsellProductsViewGrid"

  Scenario: Verify that original product is not changed
    And I go to Products/ Products
    When I click View Product1 in grid
    Then Main should be an owner
    And I should see "Enabled" green status
    And I should see product with:
      | Category          | Category1                  |
      | SKU               | PSKU1                      |
      | Name              | Product1                   |
      | Description       | Product1 Description       |
      | Short Description | Product1 Short Description |
      | Is Featured       | Yes                        |
      | New Arrival       | Yes                        |
      | Brand             | Brand1                     |
      | Unit              | item                       |
      | Tax Code          | TaxCode1                   |
    And I should see "No page template"
    And I should see following product additional units:
      | set | 5 |  | Yes |
    And I should see following product images:
      | cat1.jpg | 1 | 1 | 1 |
    And I should see following "ProductPricesGrid" grid:
      | Price List   | Quantity  | Unit | Value  | Currency |
      | Price List 1 | 1         | item | 10     | USD      |
    And I should see following "RelatedProductsViewGrid" grid:
      | SKU      | Name     | Inventory Status | Status  |
      | RELATED1 | Related1 | In Stock         | Enabled |
    And I click on "Upsell Products View Tab"
    And I should see following "UpsellProductsViewGrid" grid:
      | SKU      | Name     | Inventory Status | Status  |
      | UPSELL1  | Upsell1  | In Stock         | Enabled |
