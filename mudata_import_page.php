<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// require import functionality
require_once plugin_dir_path(__FILE__) . '/mudata_import.php';

?>

<div class="wrap">
<h1>mudata WP import</h1>

<?php if(!isset($_POST['mudata_import_action'])) : ?>

<form method="post" action="" enctype="multipart/form-data"> 
    
    <p><b>Step 1:</b> Select a File</p>
    
    <p>
        The file uploaded here is a condensed mudata zip file, which is a zip
        archive containing a 'data.csv' file, a 'locations.csv' file, a
        'params.csv' file, a 'datasets.csv' file, and a 'columns.csv' file. All
        of these files are required to use this import page. You could generate
        such a file using the <code>mudata.write()</code> function with the
        argument <code>condense.tags = TRUE</code>. These functions are 
        available in the <a href="https://cran.r-project.org/package=mudata">
        mudata package</a> for R.
    </p>
    
    <p><input type="file" name="mudata_import_file" id="mudata_import_file"/></p>
    
    <input type="hidden" name="mudata_import_action" value="upload_file"/>
    
    <?php submit_button('Upload File'); ?>
</form>

<?php elseif($_POST['mudata_import_action'] == 'upload_file') : ?>

    <p><b>Step 2:</b> Preview Upload</p>
    
    <?php
    
    // process file upload
    $file_upload = mudata_upload_file($_FILES['mudata_import_file']);

    // check for fail
    if($file_upload['status'] != 'OK') {
        echo '<p>The file upload failed: ' . $file_upload['status'] .
                '</p>';
    } else {
        
        // extract preview data
        $all_lines = mudata_preview_zip($file_upload['filename']);
        
        // check for fail
        if($all_lines['status'] != 'OK') {
            echo '<p>The file preview failed: ' . $all_lines['status'] .
                '</p>';
        } else {
            
            // display preview
            $errors = array();
            
            foreach($all_lines as $name => $lines) : ?>
                <?php if($name == 'status') continue; ?>
    
                <h3><?php echo $name; ?></h3>
    
                <?php 
                // if error, add error to errors
                if($lines['status'] != 'OK') { 
                    $errors[] = $name . ': ' . $lines['status'];
                } else {
                    // write table
                ?>
                
                <table>
                    <!-- eventually will need something better than this -->
                    <?php foreach($lines['lines'] as $line) : ?>
                    <tr>
                        <?php foreach($line as $cell) : ?>
                            <td><?php echo $cell; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </table>
    
            <?php 
                }
             endforeach;
            
            // if there were errors, remove attachment and don't give option to
            // continue
            if(!empty($errors)) {
                // cleanup!
                wp_delete_attachment($file_upload['attachment_id'], true);
                
                echo '<p>Please address the above errors and try another file</p>';
            } else { ?>
               
                <form method="post" action="">
                    <input type="hidden" name="mudata_import_action" value="import_attachment"/>
                    <input type="hidden" name="mudata_import_attachment" 
                           value="<?php echo $file_upload['attachment_id']; ?>"/>
                    
                    <?php submit_button('Import Uploaded File'); ?>
                    
                </form>
                
            <?php }
            
        }
    }

    ?>

<?php elseif($_POST['mudata_import_action'] == 'import_attachment') : ?>
                
    <p><b>Step 3:</b> Importing File</p>
    
    <?php 
    
    $attach_id = $_POST['mudata_import_attachment'];
    if(empty($attach_id)) {
        die('Invalid input to page');
    }
    
    $zipfile = get_attached_file(intval($attach_id)); // Full path
    if(empty($zipfile)) {
        die('Could not find uploaded file for attachment ' . $attach_id);
    }
    
    $result = mudata_import_zip($zipfile);
            
    if($result['status'] != 'OK') {
        // cleanup! import should already have been rolled back
        wp_delete_attachment($attach_id, true); // also deletes file
        echo '<p>' . $result['status'] . '</p>';
    } else {
        echo '<p>File imported successfully.</p>';
        
        foreach($result as $key => $item) {
            if(is_array($item) && !empty($item['warnings'])) {
                echo '<h3>' . $key . '</h3>';
                echo '<p>The following warnings were generated:</p>';
                echo '<p>' . implode('<br/>\n', $result['warnings']) . '</p>';
            }
        }
    }
    
    ?>

<?php endif; ?>

</div>