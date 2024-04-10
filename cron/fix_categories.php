<?php
// Include Zen Cart configuration
require('../includes/configure.php');
ini_set('include_path', DIR_FS_CATALOG . PATH_SEPARATOR . ini_get('include_path'));
chdir(DIR_FS_CATALOG);
require_once('includes/application_top.php');

// Function to recursively fetch all sub-categories of a given category
function fetchSubcategories($parent_id, &$allCategories, &$subCategories) {
    foreach ($allCategories[$parent_id]['children'] as $child_id) {
        $subCategories[] = $child_id;
        fetchSubcategories($child_id, $allCategories, $subCategories);
    }
}

// Query to fetch all categories and their parent-child relationships
$query = "SELECT c.categories_id, c.parent_id
          FROM " . TABLE_CATEGORIES . " c";
$result = $db->Execute($query);

// Build an array to store parent-child relationships
$allCategories = [];
while (!$result->EOF) {
    $categories_id = (int)$result->fields['categories_id'];
    $parent_id = (int)$result->fields['parent_id'];
    $allCategories[$categories_id] = ['parent_id' => $parent_id, 'children' => []];
    if ($parent_id != 0) {
        $allCategories[$parent_id]['children'][] = $categories_id;
    }
    $result->MoveNext();
}

// Array to store disabled categories
$disabledCategories = [];

// Query to disable categories with no active products
$query = "UPDATE " . TABLE_CATEGORIES . " c
          SET c.categories_status = 0
          WHERE NOT EXISTS (
              SELECT 1
              FROM " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc
              JOIN " . TABLE_PRODUCTS . " p ON ptc.products_id = p.products_id
              WHERE ptc.categories_id = c.categories_id
              AND p.products_status = 1
          )";
$db->Execute($query);

// Query to disable categories with no sub-categories containing active products
foreach ($allCategories as $category_id => $category) {
    if (!in_array($category_id, $disabledCategories)) {
        $subCategories = [];
        fetchSubcategories($category_id, $allCategories, $subCategories);
        $subCategories[] = $category_id; // Include the category itself
        $query = "UPDATE " . TABLE_CATEGORIES . " c
                  SET c.categories_status = 0
                  WHERE c.categories_id IN (" . implode(',', $subCategories) . ")
                  AND NOT EXISTS (
                      SELECT 1
                      FROM " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc
                      JOIN " . TABLE_PRODUCTS . " p ON ptc.products_id = p.products_id
                      WHERE ptc.categories_id = c.categories_id
                      AND p.products_status = 1
                  )";
        $db->Execute($query);
        $disabledCategories = array_merge($disabledCategories, $subCategories);
    }
}
?>
