<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$mudata_db_version = '1.0';
$mudata_table_data = $wpdb->prefix . 'mudata_data';
$mudata_table_datasets = $wpdb->prefix . 'mudata_datasets';
$mudata_table_params = $wpdb->prefix . 'mudata_params';
$mudata_table_locations = $wpdb->prefix . 'mudata_locations';
$mudata_table_columns = $wpdb->prefix . 'mudata_columns';

function mudata_db_install() {
    global $mudata_db_version;
    global $wpdb;
    
    // these need to be declared internally because $wpdb doesn't
    // yet exist when the base file is loaded on activation
    $mudata_table_data = $wpdb->prefix . 'mudata_data';
    $mudata_table_datasets = $wpdb->prefix . 'mudata_datasets';
    $mudata_table_params = $wpdb->prefix . 'mudata_params';
    $mudata_table_locations = $wpdb->prefix . 'mudata_locations';
    $mudata_table_columns = $wpdb->prefix . 'mudata_columns';
    
    mudata_install_log("Creating tables: " . implode(", ", array(
        $mudata_table_data, 
        $mudata_table_datasets,
        $mudata_table_columns,
        $mudata_table_locations,
        $mudata_table_params
     )));

    // create the data table, if it does not exist
    if(!mudata_table_exists($mudata_table_data)) {
        mudata_create_table($mudata_table_data, array(
            'datum_id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
            'dataset_id' => 'bigint(20) NOT NULL',
            'location_id' => 'bigint(20) NOT NULL',
            'param_id' => 'bigint(20) NOT NULL',
            'x' => 'DOUBLE NOT NULL',
            'value' => 'varchar(55)',
            'tags' => 'longtext'
        ), 'datum_id');
    }
    
    // create the datasets table, if does not exist
    if(!mudata_table_exists($mudata_table_datasets)) {
        mudata_create_table($mudata_table_datasets, array(
            'dataset_id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
            'dataset' => 'varchar(55) NOT NULL',
            'post_id' => 'bigint(20)',
            'tags' => 'longtext'
        ), 'dataset_id');
    }
    
    // create the locations table, if does not exist
    if(!mudata_table_exists($mudata_table_locations)) {
        mudata_create_table($mudata_table_locations, array(
            'location_id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
            'dataset_id' => 'bigint(20) NOT NULL',
            'location' => 'varchar(55) NOT NULL',
            'bbox_n' => 'DOUBLE',
            'bbox_e' => 'DOUBLE',
            'bbox_s' => 'DOUBLE',
            'bbox_w' => 'DOUBLE',
            'geometry' => 'longtext',
            'post_id' => 'bigint(20)',
            'tags' => 'longtext'
        ), 'location_id');
    }
    
    // create the params table, if does not exist
    if(!mudata_table_exists($mudata_table_params)) {
        mudata_create_table($mudata_table_params, array(
            'param_id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
            'dataset_id' => 'bigint(20) NOT NULL',
            'param' => 'varchar(55) NOT NULL',
            'unit_code' => 'varchar(10)',
            'post_id' => 'bigint(20)',
            'tags' => 'longtext'
        ), 'param_id');
    }
    
    // create the columns table, if does not exist
    if(!mudata_table_exists($mudata_table_columns)) {
        mudata_create_table($mudata_table_columns, array(
            'column_id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
            'dataset_id' => 'bigint(20) NOT NULL',
            'table_' => 'varchar(10) NOT NULL',
            'column_' => 'varchar(10) NOT NULL',
            'type_' => 'varchar(10)',
            'tags' => 'longtext'
        ), 'column_id');
    }

    // store the database version in case of upgrade
    add_option('mudata_db_version', $mudata_db_version);
}

function mudata_table_exists($table_name) {
    global $wpdb;
    $installed_table = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    return $installed_table == $table_name;
}

function mudata_db_uninstall() {
    global $mudata_table_data;
    global $mudata_table_datasets;
    global $mudata_table_locations;
    global $mudata_table_params;
    global $mudata_table_columns;
    global $wpdb;
    
    // remove the mudata tables
    $wpdb->query("DROP TABLE IF EXISTS " . $mudata_table_data);
    $wpdb->query("DROP TABLE IF EXISTS " . $mudata_table_datasets);
    $wpdb->query("DROP TABLE IF EXISTS " . $mudata_table_locations);
    $wpdb->query("DROP TABLE IF EXISTS " . $mudata_table_params);
    $wpdb->query("DROP TABLE IF EXISTS " . $mudata_table_columns);
}

function mudata_create_table($table_name, $fields, $primary_key) {
    // require non-empty table name
    if(empty($table_name)) {
        die("mudata_create_table called with emtpy table name");
    }
    
    global $wpdb;
    
    // assemble the create table statment
    $charset_collate = $wpdb->get_charset_collate();
    
    $first_line = "CREATE TABLE $table_name (\n";
    $last_line = "PRIMARY KEY ($primary_key)
                  ) $charset_collate;";
    
    $sql = $first_line;
    foreach($fields as $key => $value) {
        $sql = $sql . $key . " " . $value . ",\n";
    }
    $sql = $sql . $last_line;
    mudata_install_log($sql);
    
    // run the query on the database
    $result = $wpdb->query($sql);
    mudata_install_log($result);
    return $result;
}

function mudata_install_log($thing) {
    $logfile = plugin_dir_path(__FILE__) . '/install.log';
    $message = "[" . date("Y/m/d h:i:sa") . "]: " .  $thing . "\n";
    file_put_contents($logfile, $message, FILE_APPEND);
}
