
# React Custom Api Plugin

This plugin provides an API with various data sources that are used in the react plugins.

## Installation

Clone this repo into the mu-plugins folder of your wordpress configuration.

```bash
git clone git@github.com:donovan-herion/wordpressmarche-custom-api.git
```

call the plugin in the load.php file so that it runs on the wordpress site by default.

```bash
require_once WPMU_PLUGIN_DIR . '/custom-api/custom-api.php';
```

## Usage

This plugin gives access to its functions and what it returns globally on the wordpress configuration.
