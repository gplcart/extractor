[![Build Status](https://scrutinizer-ci.com/g/gplcart/extractor/badges/build.png?b=master)](https://scrutinizer-ci.com/g/gplcart/extractor/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gplcart/extractor/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gplcart/extractor/?branch=master)

**Extractor** is a module for [GPL Cart shopping cart](https://github.com/gplcart/gplcart) to extract translatable strings from various system and module files.
Essentially it searches for *text()* functions and extracts their arguments. Extracted strings are stored in CSV files which can be downloaded.

**Installation**

1. Download and extract to `system/modules` manually or using composer `composer require gplcart/extractor`. IMPORTANT: If you downloaded the module manually, be sure that the name of extracted module folder doesn't contain a branch/version suffix, e.g `-master`. Rename if needed.
2. Enable at `admin/module/list`

**Usage**

Go to `admin/tool/extract`, extract strings and download the file
