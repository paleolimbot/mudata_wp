
# Mudata WP

Use Wordpress as a time-series data repository using the (mostly) universal data structure.

The general theory is that datasets, locations, and parameters are stored as custom posts in the Wordpress structure. This means they can be heiarchical, so that they can be arbitrarily nested. These "super groups" will likely be slightly confusing but useful for selecting relevant data on an ongoing basis. Data is stored in a mudata-style data table with columns `id`, `dataset`, `location`, `param`, `x`, `value`, `text_value`, and `tags`. Here, `dataset`, `location`, and `param` are the `post_id`s of the associated custom posts. There will probably need to be a quite extensive database wrapper to ensure the data table is valid at all times.

Done so far:

* Custom post types for datasets, locations, and params (heiarchical)
* Create custom taxonomy for subsets (also heiarchical)
* Create the database table for data

Need to do:

* Create the database table to link datasets and locations
* Create a database object for the mudata side of things to take care of syncing datasets, locations, params, and data.
