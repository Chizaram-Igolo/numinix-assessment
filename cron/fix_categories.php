<?php
require('../includes/configure.php');
ini_set('include_path', DIR_FS_CATALOG . PATH_SEPARATOR . ini_get('include_path'));
chdir(DIR_FS_CATALOG);
require_once('includes/application_top.php');

// Query to fetch all categories and their parent-child relationships
$query = "SELECT c.categories_id, c.parent_id 
          FROM " . TABLE_CATEGORIES . " c";
$categoriesResult = $db->Execute($query);

// Build an array to store parent-child relationships
$allCategories = [];
$parent = []; // Array to map each category to its parent for easier parent tracking
$categoriesStatus = [];

while (!$categoriesResult->EOF) {
    $categoryId = $categoriesResult->fields['categories_id'];
    $parentId = $categoriesResult->fields['parent_id'];

    if (!isset($allCategories[$parentId])) {
        $allCategories[$parentId] = [];
    }
    $allCategories[$parentId][] = $categoryId;

    // Map the current category to its parent
    $parent[$categoryId] = $parentId;
    $categoriesStatus[$categoryId] = false;

    $categoriesResult->MoveNext();
}

// Query to fetch all enabled products and their categories
$query = "SELECT DISTINCT ptc.categories_id
          FROM " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc
          JOIN " . TABLE_PRODUCTS . " p ON ptc.products_id = p.products_id
          WHERE p.products_status = '1'";
$productsResult = $db->Execute($query);

// Mark categories with active products and their parents
while (!$productsResult->EOF) {
    $categoryId = $productsResult->fields['categories_id'];
    
    // Mark this category and all its parents as having an active product
    while ($categoryId != 0 && !isset($categoriesStatus[$categoryId])) {
        $categoriesStatus[$categoryId] = true;
        $categoryId = $parent[$categoryId] ?? 0;
    }

    $productsResult->MoveNext();
}

// Query to disable categories with no sub-categories containing active products
foreach ($categoriesStatus as $categoryId => $status) {
    if (!$status) {
        $db->Execute("UPDATE " . TABLE_CATEGORIES . "
                      SET categories_status = '0'
                      WHERE categories_id = " . (int)$categoryId);
    }
}

?>
