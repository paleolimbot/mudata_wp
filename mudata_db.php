<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$mudata_db_version = '1.0';
$mudata_table_data = $data_table_name = $wpdb->prefix . 'mudata_data';

function mudata_db_install() {
    global $mudata_db_version;
    global $mudata_table_data;

    // create the data table, if it does not exist
    
    if(!mudata_table_exists($mudata_table_data)) {
        mudata_create_table($mudata_table_data, array(
            'id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
            'dataset' => 'bigint(20) NOT NULL',
            'location' => 'bigint(20) NOT NULL',
            'param' => 'bigint(20) NOT NULL',
            'x' => 'DOUBLE NOT NULL',
            'value' => 'DOUBLE',
            'text_value' => 'varchar(55)',
            'tags' => 'longtext'
        ), 'id');
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
    global $wpdb;
    
    // remove the data table
    $sql = "DROP TABLE IF EXISTS " . $mudata_table_data;
    $wpdb->query($sql);
}

function mudata_create_table($table_name, $fields, $primary_key) {
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
    
    // run the query on the database
    return $wpdb->query($sql);
}
