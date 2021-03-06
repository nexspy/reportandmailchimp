# Report Generator and Mailchimp Plugin

This plugin helps to create reports of orders and send them as email using mailchimp.

Some functions are kept in **theme_functions** folder which is to be placed in the theme folder and include them.

Install the csv generator and mailchimp package using composer inside the theme folder.

This plugin was made from **wppb.me** plugin skeleton generator.

**[ This Plugins is only for viewing the plugin mechanism / code and cannot be used in real project asap ]**

**[ This plugin is has been copied from my last project and has not been fully tested after renaming some project specific texts ]**

## Packages

```
> composer require league/csv
```


## Features

1. Plugin settings page to set the admin emails, regional emails, REST Api tokens, etc.
2. Send automatically on cron jobs.
3. Manual downloading of reports using date filters.


## Screenshots

[Settings](https://raw.githubusercontent.com/nexspy/reportandmailchimp/main/images/1.settings.png)

[Manual Export](https://raw.githubusercontent.com/nexspy/reportandmailchimp/main/images/2.manual_export.png)
