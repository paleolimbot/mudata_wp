<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// creates a temporary directory 
function mudata_tempdir() {
    $tempfile = tempnam(sys_get_temp_dir(), '');
    if(file_exists($tempfile)) {
        // this will happen, since tempnam creates the file
        unlink($tempfile);
    }
    
    // create the directory
    mkdir($tempfile);
    
    // make sure directory returned is a directory
    if(is_dir($tempfile)) { 
        return $tempfile; 
    } else {
        // return null on failure
        return null;
    }
}

// recursively remove directory
function mudata_rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (is_dir($dir."/".$object)) {
           rrmdir($dir."/".$object);
         } else {
           unlink($dir."/".$object); 
         }
       } 
     }
     rmdir($dir); 
   } 
 }

 

// converts a YYYY-MM-DD date into a number
$mudata_epoch = date_create_from_format('Y-m-d', '1970-01-01');
function mudata_convert_date($date_string, $format = 'Y-m-d') {
    global $mudata_epoch;
    $date = date_create_from_format($format, $date_string);
    $diff = date_diff($mudata_epoch, $date, false);
    
    // return difference in days
    $days = $diff->days;
    if($diff->invert) {
        return -$days;
    } else {
        return $days;
    }
}
 
// unzip a compressed mudata file
function mudata_unzip($zipfile, $tmpdir) {
    
    // check that $tmpdir is a directory
    if(!is_dir($tmpdir)) {
        return null;
    }
    
    // open the zip file
    $zip = zip_open($zipfile);

    // return null if the zipfile is not a zipfile
    if (!is_resource($zip)) {
        return null;
    }
    
    // look for data.csv, locations.csv, params.csv, datasets.csv,
    // and columns.csv in the zip entries
    $csv_files = array();
    $names = array('data.csv', 'locations.csv', 'params.csv', 'datasets.csv', 
        'columns.csv');
    
    // loop through zip entries
    while($zip_entry = zip_read($zip)) {
        $entry_name = zip_entry_name($zip_entry);
        $entry_base = basename($entry_name);
        
        // if the zip entry is one of the names to extract, extract it
        // to tmpdir
        if(in_array($entry_base, $names)) {
            $extract_name = path_join($tmpdir, $entry_base);

            // Get the content of the zip entry
            $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            // copy file
            $copy_result = file_put_contents($extract_name, $fstream );
            
            // copy result is FALSE or 0 means failure
            if($copy_result) {
                // set rights to read/write but not execute
                // Set the rights
                chmod($extract_name, 0666);
                
                // add to $csv_files
                $csv_files[$entry_base] = $extract_name;
            }  
        }
        
        // Close the entry
        zip_entry_close($zip_entry);
    }
    
    // close the zip file
    zip_close($zip);
    
    // return $csv_files
    return $csv_files;
}

// closes a file handle and returns an array with 'status' of
// $error
function import_error($error, $file = null) {
    $out = array('status' => $error);
    if(is_resource($file)) {
        fclose($file);
    }
    return $out;
}

// imports a mudata datasets csv
function mudata_import_datasets($csv) {
    global $mudata_table_datasets;
    
    // open the csv
    $file = fopen($csv, 'r');
    
    // check if the file could be opened
    if(!is_resource($file)) {
        return import_error('The "datasets.csv" file does not exist"');
    }
        
    // process the header line
    $first_line = fgetcsv($file);
    if($first_line === false) {
        return import_error('The datasets CSV contains no header line', $file);
    }

    // check for required fields ("datasets", "tags")
    if($first_line[0] != 'dataset') {
        return import_error('datasets.csv column 1 is not "dataset"', $file);
    }
    if($first_line[1] != 'tags') {
        return import_error('datasets.csv column 2 is not "tags"', $file);
    }

    // read the file line by line, doing the following things
    // 1. insert a new post (of type dataset)
    // 2. insert a row into the databases table
    // 3. insert an array entry mapping the 'dataset' column to the dataset_id
    $datasets = array();
    $warnings = array();
    $line_number = 1;
    while (($line = fgetcsv($file)) !== false) {
        $dataset_slug = $line[0];
        $dataset_tags = json_decode($line[1]);
        
        if($dataset_slug) {
            // insert dataset (and tags as post meta)
            $insert_info = mudata_insert($mudata_table_datasets,
                    'dataset', $dataset_slug, $dataset_tags);
            
            if($insert_info['status'] != 'OK') {
                return import_error($insert_info['status'], $file);
            }
            
            // update datasets array
            $datasets[$dataset_slug] = $insert_info['id'];
        } else {
            $warnings[] = "Skipping blank line at line $line_number";
        }
        $line_number++;
    }

    fclose($file);
    return array('status' => 'OK', 'datasets' => $datasets,
        'warnings' => $warnings);
}

// imports a mudata params csv
function mudata_import_params($csv, $datasets) {
    global $mudata_table_params;
    
    // open the csv
    $file = fopen($csv, 'r');
    
    // check if the file could be opened
    if(!is_resource($file)) {
        return import_error('The "params.csv" file does not exist"');
    }
        
    // process the header line
    $first_line = fgetcsv($file);
    if($first_line === false) {
        return import_error('The params CSV contains no header line', $file);
    }

    // check for required fields
    if($first_line[0] != 'dataset') {
        return import_error('params.csv column 1 is not "dataset"', $file);
    }
    if($first_line[1] != 'param') {
        return import_error('params.csv column 2 is not "param"', $file);
    }
    if($first_line[2] != 'tags') {
        return import_error('params.csv column 3 is not "tags"', $file);
    }

    // read the file line by line, doing the following things
    // 1. insert a new post (of type dataset)
    // 2. insert a row into the databases table
    // 3. insert an array entry mapping the 'dataset' column to the dataset_id
    $params = array();
    $warnings = array();
    $line_number = 1;
    while (($line = fgetcsv($file)) !== false) {
        $dataset_slug = $line[0];
        $param_slug = $line[1];
        $param_tags = json_decode($line[2]);
        if(empty($param_tags)) {
            $param_tags = array();
        }
        
        if($dataset_slug && $param_slug) {
            // find dataset_id
            $dataset_id = $datasets[$dataset_slug];
            if(empty($dataset_id)) {
                return import_error("Could not find dataset id for dataset $dataset_slug",
                        $file);
            }
            
            // insert param (and tags as post meta)
            $insert_info = mudata_insert($mudata_table_params,
                    'param', $param_slug, $param_tags,
                    array('dataset_id' => $dataset_id));
            
            if($insert_info['status'] != 'OK') {
                return import_error($insert_info['status'], $file);
            }
            
            // update params array
            if(!array_key_exists($dataset_slug, $params)) {
                $params[$dataset_slug] = array();
            }
            $params[$dataset_slug][$param_slug] = $insert_info['id'];
        } else {
            $warnings[] = "Skipping malformed line at line $line_number";
        }
        $line_number++;
    }

    fclose($file);
    return array('status' => 'OK', 'params' => $params,
        'warnings' => $warnings);
}

// imports a mudata locations csv
function mudata_import_locations($csv, $datasets) {
    global $mudata_table_locations;
    
    // open the csv
    $file = fopen($csv, 'r');
    
    // check if the file could be opened
    if(!is_resource($file)) {
        return import_error('The "locations.csv" file does not exist"');
    }
        
    // process the header line
    $first_line = fgetcsv($file);
    if($first_line === false) {
        return import_error('The locations CSV contains no header line', $file);
    }

    // check for required fields 
    if($first_line[0] != 'dataset') {
        return import_error('locations.csv column 1 is not "dataset"', $file);
    }
    if($first_line[1] != 'location') {
        return import_error('locations.csv column 2 is not "location"', $file);
    }
    if($first_line[2] != 'tags') {
        return import_error('locations.csv column 3 is not "tags"', $file);
    }

    // read the file line by line, doing the following things
    // 1. insert a new post (of type dataset)
    // 2. insert a row into the databases table
    // 3. insert an array entry mapping the 'dataset' column to the dataset_id
    $locations = array();
    $warnings = array();
    $line_number = 1;
    while (($line = fgetcsv($file)) !== false) {
        $dataset_slug = $line[0];
        $location_slug = $line[1];
        $location_tags = json_decode($line[2]);
        if(empty($location_tags)) {
            $location_tags = array();
        }
        
        if($dataset_slug && $location_slug) {
            // find dataset_id
            $dataset_id = $datasets[$dataset_slug];
            if(empty($dataset_id)) {
                return import_error("Could not find dataset id for dataset $dataset_slug",
                        $file);
            }
            
            // insert param (and tags as post meta)
            $insert_info = mudata_insert($mudata_table_locations,
                    'location', $location_slug, $location_tags,
                    array('dataset_id' => $dataset_id));
            
            if($insert_info['status'] != 'OK') {
                return import_error($insert_info['status'], $file);
            }
            
            // update params array
            if(!array_key_exists($dataset_slug, $locations)) {
                $locations[$dataset_slug] = array();
            }
            $locations[$dataset_slug][$location_slug] = $insert_info['id'];
        } else {
            $warnings[] = "Skipping malformed line at line $line_number";
        }
        $line_number++;
    }

    fclose($file);
    return array('status' => 'OK', 'locations' => $locations,
        'warnings' => $warnings);
}

// imports a mudata data csv
function mudata_import_data($csv, $datasets, $locations, $params) {
    global $mudata_table_data;
    
    // open the csv
    $file = fopen($csv, 'r');
    
    // check if the file could be opened
    if(!is_resource($file)) {
        return import_error('The "data.csv" file does not exist"');
    }
        
    // process the header line
    $first_line = fgetcsv($file);
    if($first_line === false) {
        return import_error('The datasets CSV contains no header line', $file);
    }

    // check for required fields
    if($first_line[0] != 'dataset') {
        return import_error('datasets.csv column 1 is not "dataset"', $file);
    }
    if($first_line[1] != 'location') {
        return import_error('datasets.csv column 2 is not "location"', $file);
    }
    if($first_line[2] != 'param') {
        return import_error('datasets.csv column 3 is not "param"', $file);
    }
    if($first_line[3] != 'x') {
        return import_error('datasets.csv column 4 is not "x"', $file);
    }
    if($first_line[4] != 'value') {
        return import_error('datasets.csv column 5 is not "value"', $file);
    }
    if($first_line[5] != 'tags') {
        return import_error('datasets.csv column 6 is not "tags"', $file);
    }

    // read the file line by line, doing the following things
    // 1. insert a row into the data table
    $warnings = array();
    $line_number = 1;
    while (($line = fgetcsv($file)) !== false) {
        $dataset_slug = $line[0];
        $location_slug = $line[1];
        $param_slug = $line[2];
        $x_value = $line[3];
        $value = $line[4];
        $tags = json_decode($line[1]);
        
        if($dataset_slug && $location_slug && $param_slug && $x_value && $value) {
            // find ids for dataset, location, and param
            $dataset_id = $datasets[$dataset_slug];
            if(empty($dataset_id)) {
                return import_error("Could not find dataset id for dataset $dataset_slug",
                        $file);
            }
            
            $location_id = $locations[$dataset_slug][$location_slug];
            if(empty($location_id)) {
                return import_error("Could not find location id for dataset $dataset_slug"
                        . " and location $location_slug",
                        $file);
            }
            
            $param_id = $params[$dataset_slug][$param_slug];
            if(empty($param_id)) {
                return import_error("Could not find param id for dataset $dataset_slug"
                        . " and param $param_slug",
                        $file);
            }
            
            // insert dataset (and tags as post meta)
            $insert_info = mudata_insert_data($dataset_id, $location_id, 
                    $param_id, mudata_convert_date($x_value), $value, $tags);
            
            if($insert_info['status'] != 'OK') {
                return import_error($insert_info['status'], $file);
            }
        } else {
            $warnings[] = "Skipping malformed line at line $line_number";
        }
        $line_number++;
    }

    fclose($file);
    return array('status' => 'OK', 'warnings' => $warnings);
}

function mudata_import_zip($zipfile) {
    global $wpdb;
    
    // create temp dir
    $tempdir = mudata_tempdir();
    if(is_null($tempdir)) {
        return array('status' => 'Could not create temporary directory');
    }
    
    // extract zip archive
    $mudata_files = mudata_unzip($zipfile, $tempdir);
    if(is_null($mudata_files)) {
        mudata_rrmdir($tempdir);
        return array('status' => 'Could not extract zip archive');
    }
    
    // check for required tables
    $missing_files = array();
    $names = array('data.csv', 'locations.csv', 'params.csv', 'datasets.csv', 
        'columns.csv');
    foreach($names as $name) {
        if(!array_key_exists($name, $mudata_files)) {
            $missing_files[] = $name;
        }
    }
    
    if(!empty($missing_files)) {
        mudata_rrmdir($tempdir);
        return array('status' => 'The zip archive did not contain the following'
            . ' required files: ' . impolode(', ', $missing_files));
    }
    
    // start database transaction (transactions not supported in MySQL by default)
    
    // import datasets
    $datasets = mudata_import_datasets($mudata_files['datasets.csv']);
    
    // check for failure
    if($datasets['status'] != 'OK') {
        mudata_rrmdir($tempdir);
        return array('status' => 'Datasets import failed: ' . $datasets['status'],
            'datasets' => $datasets);
    }
    
    // import locations
    $locations = mudata_import_locations($mudata_files['locations.csv'], $datasets['datasets']);
    
    // check for failure
    if($locations['status'] != 'OK') {
        mudata_rrmdir($tempdir);
        return array('status' => 'Locations import failed: ' . $locations['status'],
            'datasets' => $datasets, 'locations' => $locations);
    }
    
    // import params
    $params = mudata_import_params($mudata_files['params.csv'], $datasets['datasets']);
    
    // check for failure
    if($params['status'] != 'OK') {
        mudata_rrmdir($tempdir);
        return array('status' => 'Params import failed: ' . $params['status'],
            'datasets' => $datasets, 'locations' => $locations,
            'params' => $params);
    }
    
    // import data
    $data = mudata_import_data($mudata_files['data.csv'], $datasets['datasets'], 
            $locations['locations'], $params['params']);
    
    // check for failure
    if($data['status'] != 'OK') {
        mudata_rrmdir($tempdir);
        return array('status' => 'Data import failed: ' . $data['status'],
            'datasets' => $datasets, 'locations' => $locations,
            'params' => $params, 'data' => $data);
    }
    
    // return success
    return array('status' => 'OK', 
        'datasets' => $datasets, 'locations' => $locations,
        'params' => $params, 'data' => $data);
}

function dummy_import() {
    return mudata_import_zip('/home/anneke/www/paleoarc/wp-content/uploads/2017/04/kg.mudata.zip');
}
