# AdminKit demo module

This directory is a copyable Bitrix module example. It contains two different
ways to work with settings:

- `lib/Admin/SettingsPage.php` is an `OptionsPage` backed by
  `Bitrix\Main\Config\Option` and is suitable for one fixed set of module
  options.
- `lib/Admin/SettingsResource.php` is a complete D7 ORM CRUD section backed by
  `vendor_demo_settings`. It is suitable when administrators need to create,
  edit, filter, sort and remove an arbitrary number of settings.

## Add the settings resource to a module

1. Copy `lib/Orm/SettingsTable.php` and `lib/Admin/SettingsResource.php` to
   the corresponding namespaces in your module.
2. Change `Vendor\Demo` and the table name to your module namespace and table
   prefix.
3. Create the table in your module installer. The SQL in `install/index.php`
   is ready to copy; it is deliberately idempotent for module updates.
4. Render the module through
   `AdminKit::forModule('your.module')->getCurrentPage()->render()` and use
   `getMenu()` to enable automatic discovery of `SettingsResource`.

The resource demonstrates index, create, edit and detail pages in a SidePanel;
required fields and defaults; text and select filters; row actions; bulk
activation/deactivation; and destructive bulk deletion with the standard
confirmation flow.

After installing this demo module, open the **Settings registry** item in the
AdminKit demo menu. The original **Settings** item remains an `OptionsPage`
example so both persistence approaches can be compared.
