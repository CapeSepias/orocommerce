@fixture-product_collections.yml
Feature:
  In order to add more than one product by some criteria into the content nodes
  As an Administrator
  I want to have ability of adding Product Collection variant

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
     | Admin  | first_session  |
     | Buyer  | second_session |
    And I set "Default Web Catalog" as default web catalog

  Scenario: Add Product Collection variant
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click "Content Variants"
    Then I should see 1 elements "Product Collection Variant Label"
    Then I should see an "Product Collection Preview Grid" element
    And I should see "No records found"

  Scenario: Saving Product Collection with empty filters results in validation error
    When I click "Save"
    Then I should see "Should be specified filters or added some products manually."

  Scenario: Apply filters
    When I click "Content Variants"
    And I click on "Advanced Filter"
    And I should see "DRAG TO SELECT"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU1" in "value"
    And I click on "Preview"
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |

  Scenario: Save Product Collection with defined filters and applied query
    When I save form
    Then I should not see text matching "You have changes in the Filters section that have not been applied"
    Then I should see "Content Node has been saved" flash message
    Then I reload the page
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |

  Scenario: Created Product Collection accessible at frontend
    Given I operate as the Buyer
    And I am on homepage
    Then I should see "PSKU1"
    And I should not see "PSKU2"
    And Page title equals to "Root Node"
    And Page meta keywords equals "CollectionMetaKeyword"
    And Page meta description equals "CollectionMetaDescription"

  Scenario: Autogenerated Product Collection segments are available in Manage Segments section
    Given I proceed as the Admin
    When I go to Reports & Segments/ Manage Segments
    Then I should see "Product Collection" in grid with following data:
      | Entity                  | Product               |
      | Type                    | Dynamic               |
    And I should see following actions for Product Collection in grid:
      | View                           |
      | Edit within Web Catalog |
    When I click on Product Collection in grid
    Then I should not see an "Entity Edit Button" element
    And I should not see an "Entity Delete Button" element
    And I should see an "Edit within Web Catalog" element
    When I click "Edit within Web Catalog"
    Then "Content Node Form" must contains values:
      | Titles | Root Node |
      | Meta Keywords | CollectionMetaKeyword |
      | Meta Description | CollectionMetaDescription |

  Scenario: Product Collection can be edited
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Content Variants"
    And I click on "Advanced Filter"
    And type "PSKU" in "value"
    And I click on "Preview"
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU2 | Product 2 |
      | PSKU1 | Product 1 |
    Then I fill in "Segment Name" with "Some Custom Segment Name"

  Scenario: Edited Product Collection can be saved
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I reload the page
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU2 | Product 2 |
      | PSKU1 | Product 1 |

  Scenario: Adding Product Collection with duplicated name results in validation error
    When I click "Content Variants"
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU" in "value"
    And I fill "Content Node Form" with:
      | First Product Collection Segment Name  | Some Product Collection Name |
      | Second Product Collection Segment Name | Some Product Collection Name |
    And I save form
    Then I should see text matching "You have changes in the Filters section that have not been applied"
    When I click "Continue" in modal window
    And I click "Content Variants"
    Then I should see "Content Node Form" validation errors:
      | Second Product Collection Segment Name | This name already in use |

  Scenario: Add another product collection
    When I reload the page
    Then "Content Node Form" must contains values:
      | First Product Collection Segment Name  | Some Custom Segment Name |
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU" in "value"
    And I fill "Content Node Form" with:
      | Second Product Collection Segment Name  | Unique Name |
    And I save form
    Then I should see text matching "You have changes in the Filters section that have not been applied"
    When I click "Continue" in modal window
    Then I should see "Content Node has been saved" flash message

  Scenario: Changing names to same for saved Product Collections results in validation error
    Given I click "Content Variants"
    And I fill "Content Node Form" with:
      | First Product Collection Segment Name  | Same Name |
      | Second Product Collection Segment Name | Same Name |
    When I save form
    Then I should see "Content Node Form" validation errors:
      | Second Product Collection Segment Name | This name already in use |

  Scenario: Remove product collection
    When I reload the page
    Then "Content Node Form" must contains values:
      | First Product Collection Segment Name  | Unique Name |
      | Second Product Collection Segment Name | Some Custom Segment Name |
    When I click on "Remove First Product Collection Variant Button"
    And I save form
    Then I should see "Content Node has been saved" flash message
    And I should see 1 element "Product Collection Variant Label"

  Scenario: Modification of Product Collection segment's name, reflected in Manage Segments section
    Given I proceed as the Admin
    When I go to Reports & Segments/ Manage Segments
    Then I should see "Some Custom Segment Name" in grid with following data:
      | Entity | Product |
      | Type   | Dynamic |

  Scenario: Edited Product Collection accessible at frontend
    Given I operate as the Buyer
    And I am on homepage
    Then I should see "PSKU1"
    And I should see "PSKU2"

  Scenario: Change "Product 2" SKU in order to exclude it from product collection filter
    Given I operate as the Admin
    And go to Products/ Products
    And I click view Product 2 in grid
    And I click "Edit"
    And I fill form with:
      | SKU | XSKU |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: "Product 2" that already not confirm to filter, excluded from product collection grid at backend
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |

  Scenario: "Product 2" that already not confirm to filter, excluded from product collection grid at frontend
    Given I operate as the Buyer
    And I am on homepage
    Then I should see "PSKU1"
    And I should not see "PSKU2"
    And I should not see "XSKU"

  Scenario: Change "Product 2" SKU in order to include it to the product collection filter again
    Given I operate as the Admin
    And go to Products/ Products
    And I click view Product 2 in grid
    And I click "Edit"
    And I fill form with:
      | SKU | PSKU2 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: "Product 2" that confirm to filter again, included into product collection grid at backend
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU2 | Product 2 |
      | PSKU1 | Product 1 |

  Scenario: "Product 2" that confirm to filter again, included into product collection grid at frontend
    Given I operate as the Buyer
    And I am on homepage
    Then I should see "PSKU1"
    And I should see "PSKU2"

  Scenario: Confirmation cancel after save changed not applied filters
    Given I proceed as the Admin
    And I click "Content Variants"
    And I click on "Advanced Filter"
    When type "PSKU1" in "value"
    And I save form
    Then I should see text matching "You have changes in the Filters section that have not been applied"
    And I click "Cancel" in modal window
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU2 | Product 2 |
      | PSKU1 | Product 1 |

  Scenario: Confirmation accept after save changed not applied filters
    When I click "Content Variants"
    And type "PSKU1" in "value"
    And I save form
    Then I should see text matching "You have changes in the Filters section that have not been applied"
    And I click "Continue" in modal window
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |

  Scenario: Confirmation cancel, after save changed not applied filters several product collections
    When I click "Content Variants"
    And I click on "Advanced Filter"
    And type "PSKU2" in "value"
    Then I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU" in "value"
    And I click "Content Variants"
    And I click on "Advanced Filter"
    Then I should see 2 elements "Product Collection Variant Label"
    And I save form
    Then I should see text matching "You have changes in the Filters section that have not been applied"
    And I click "Cancel" in modal window
    Then I should not see text matching "You have changes in the Filters section that have not been applied"
    And I press "Cancel"
    Then I should see "Web Catalogs"

  Scenario: Reset Product Collection after filters change
    When I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Content Variants"
    And I click on "Advanced Filter"
    When type "SKU42" in "value"
    And I click "Preview"
    And I click on "Reset"
    Then I should not see "SKU42"
    And I click "Preview"
    Then I should see "PSKU1"
    And I click "Cancel"

  Scenario: Content Node changes are reflected on frontend
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I fill "Content Node Form" with:
      | Meta Keywords    | AnotherCollectionMetaKeyword     |
      | Meta Description | AnotherCollectionMetaDescription |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Products which belong to product collection are searchable by Content Node meta information for this Product Collection
    Given I operate as the Buyer
    When I am on homepage
    Then Page meta keywords equals "AnotherCollectionMetaKeyword"
    And Page meta description equals "AnotherCollectionMetaDescription"
    When I fill in "search" with "AnotherCollectionMetaKeyword"
    And I press "Search Button"
    Then I should see "PSKU1"

  Scenario: Products Collection is deletable
    Given I proceed as the Admin
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    When I click on "Remove Variant Button"
    Then I should see 0 elements "Product Collection Variant Label"
    When I click on "Show Variants Dropdown"
    And I click "Add System Page"
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Products are not searchable by Content Node meta information for deleted Product Collection
    Given I proceed as the Buyer
    And I am on homepage
    When I fill in "search" with "AnotherCollectionMetaKeyword"
    And I press "Search Button"
    Then I should not see "PSKU1"
