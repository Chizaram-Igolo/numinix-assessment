# Numinix Assessment

## Task

Write a script that will be executed outside of Zen Cart via a cron job and disable:

- Any categories (SET categories_status = 0) with no enabled products (products_status = 0) in the category and
- Any categories with no sub-categories containing enabled products.

In other words, disable any categories that do not have active products in the
category or in all of its sub-categories.
