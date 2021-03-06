<?php  defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * PageStudio
 *
 * A web application for managing website content. For use with PHP 5.4+
 * 
 * This application is based on the CodeIgniter CMS application; 
 * CMS Canvas <http://cmscanvas.com/>. It has been greatly altered to work 
 * for the purposes of our development team. Additional resources and 
 * concepts have been borrowed from PyroCMS http://pyrocms.com, for further 
 * improvement and reliability. 
 *
 * @package     PageStudio
 * @author      Cosmo Mathieu <cosmo@cosmointeractive.co>
 * @copyright   Copyright (c) 2015, CosmoInteractive, LLC
 * @license     MIT License
 * @link        http://pagestudioapp.com
 */

// ------------------------------------------------------------------------

/**
 * Grid
 *
 * Provides the ability to dynamically add / delete and sort rows of data 
 * similar to a spreadsheet. Each data row can have one or more cells which 
 * are assigned to different custom fields.
 * 
 * @note        Only the following fields are available: Text input, Textarea, 
 *              Select box, Checkbox, Dates
 *
 * @package		  PageStudio
 * @subpackage	Models
 * @category	  Module
 * @author		  Cosmo Mathieu <cosmo@cosmointeractive.co>
 * @link		    http://pagestudioapp.com/user_guide/
 */
class Grid_field extends Field_type
{
    public $tracker = [];
    public $entry_id = null;
    
    public function settings()
    {
      $data = get_object_vars($this);
      
      return $this->load->view('settings/grid', $data, TRUE);
    }

    public function display_field()
    {
      $data = get_object_vars($this);

      // Build options array
      $option_array = array();
      foreach (explode("\n", $this->Field->options) as $option) {
        $option = explode("=", $option, 2);
        $option_array[$option[0]] = (count($option) == 2) ? $option[1] : $option[0];
      }

      $data['Field']->options = $option_array;        
      $data['grid_fields_table'] = $this->build_table();
      
      return $this->load->view('grid', $data, TRUE);
    }
    
    // ------------------------------------------------------------------
    
    private function build_table()
    {
        $this->entry_id = $entry_id = $this->uri->segment(6);
        $content_type_id = $this->uri->segment(5);
        $content_fields_array = ($this->session->userdata('content_fields')) ? $this->session->userdata('content_fields') : [];
        
        // Get all elligible fields 
        $content_fields = $this->db->select('*')
							   ->where('content_type_id', $content_type_id)
							   ->where('content_field_type_id', 16)
							   ->get('content_fields');
        $content_field_array = $content_fields->result();
		
        for($i=0; $i<count($content_field_array); $i++) {
            if( ! array_key_exists($i, $content_fields_array)) {
                $content_fields_array[] = $content_field_array[$i]->id;
                $this->session->set_userdata(['content_fields' => $content_fields_array]);
                
                // Get the table cols and headers 
                $this->db->select('*');
                $this->db->where('content_field_id', $content_field_array[$i]->id);
                $grid_headers = $this->db->get('grid_cols');
                
                // Get the table rows
                $this->db->select('*, grid_col_data.id');
                $this->db->where('grid_col_data.entry_id', $entry_id);
                $this->db->where('grid_cols.content_field_id', $content_field_array[$i]->id);
                $this->db->join('grid_cols', 'grid_cols.id = grid_col_data.grid_col_id', 'left');
                $this->db->order_by("grid_col_data.row_order", 'asc'); 
                $grid_rows = $this->db->get('grid_col_data');
                
                // Get the field settings
                $field_settings = $this->db->select('settings, sort')
                    ->where('content_type_id', $content_type_id)
                    ->get('content_fields')
                    ->result();
                    
                return $this->table_markup([
                    'grid_headers' => $grid_headers->result(),
                    'grid_rows' => $grid_rows->result(),
                    'content_field_id' => $content_field_array[$i]->id,
                    'content_type_id' => $content_type_id,
                    'entry_id' => $entry_id,
                    'field_settings' => $field_settings[0]->settings,
                    'field_sort' => $field_settings[0]->sort
                ]);
            }
        }
    }
    
    // ------------------------------------------------------------------
    
    // Build the html markup and javascript output 
    private function table_markup($params)
    {
      extract($params);
      extract(unserialize($field_settings));
      $max_rows = ( ! empty($max_rows)) ? $max_rows : 50;
      $total_cols = count($grid_headers);     // Get number of columns
      $row_count = 1;
      $count = 0;
      $out  = '';
      
      $out .= '
        <table id="content_type_'.$content_field_id.'" class="matrix order-list" border="0" cellpadding="0" cellspacing="0">
          <thead class="matrix">
            <tr class="matrix matrix-first matrix-last odd">
              <th class="matrix matrix-first matrix-tr-header">&nbsp;</th>';
              if ( ! empty($grid_headers)) {
                foreach($grid_headers as $key => $col ) {
                  $header_items[] = $col->id;
                  $out .= '
                  <th class="matrix'. (($count === $total_cols) ? 'matrix-last ' : '') .'">'. $col->label .'</th>';
                  $count++;
                }
              }
      $out .= '</tr>
          </thead>
          <tbody class="matrix">';
            
          if( ! empty($grid_rows)) {
            $count          = 1;
            $missing_fields = $header_items;
            $total_cols     = $total_cols + 1;
            $rows           = [];
            $known_ids      = [];
            
            // Find new grid fields 
            // Find fields that are in the grid_cols table that have no data 
            // associated with them in the grid_col_data table if any...
            foreach($grid_rows as $key => $col ) {
                if(($row_key = array_search($col->grid_col_id, $missing_fields)) !== false) {
                    unset($missing_fields[$row_key]);
                }
                if( ! in_array($col->grid_col_id, $known_ids)){
                    $known_ids[] = $col->grid_col_id;
                }
            }
            
            // Build associatve array containing rows and columns 
            $key = 0;
            $j = 0;
            for($i = 0; $i < count($grid_rows); $i++) {
                $rows[$key][$j] = $grid_rows[$i];
                if(count($known_ids) === $count) {
                    foreach($missing_fields as $x) {
                        $j++;
                        $rows[$key][$j] = $this->_get_missin_field_atts($x, $grid_headers);
                    }
                    $key++;
                    $count = 0;
                    $j = -1;
                }
                $j++;
                $count++;
            }
            
            // Build html table rows with newly constructed associative array... 
            if( ! empty($rows)) {
                $count = 1;
                $row_order = 1;
                foreach($rows as $key => $columns ) {
                    $out .= '<tr class="matrix matrix-first" id="tbl_row_1">';
                    foreach($columns as $key => $col) {
                        if($count === $total_cols) {
                            $count = 1;
                            $row_count++;
                        }
                        if($count === 1) {
                            $out .= '
                            <th class="matrix matrix-first matrix-tr-header">
                                <div>
                                    <span>'.$row_count.'</span><a class="delRow" style="opacity: 1; display: inline;" title="Options"></a>
                                </div>
                                <input name="content_type_'.$content_field_id.'[row_order][]" value="'.$row_order.'" type="hidden">
                            </th>';
                            $row_order++;
                        }
                        $out .= $this->format_field_type(
                            $col->content_field_type_id,
                            $col->grid_col_id,
                            $col->options,
                            $col->row_data,
                            $col->id, 
                            $row_order
                        );
                        
                        $count++;
                    }
                    $out .= '</tr>';
                }
            }
            
          }
      $out .= '
          </tbody>
        </table>
        <a class="matrix-btn matrix-add" id="field_'.$content_field_id.'_addrow_btn" title="Add row"></a>';
        
        // Build a single html table row for the javascript methods to append 
        // to tables when the add new row button is clicked.
        $dynamic_rows = '';
        $count = 0;
        foreach($grid_headers as $key => $col ) {
            $dynamic_rows .= $this->format_field_type(
                $col->content_field_type_id,
                $col->id,
                $col->options,
                '', 
                '', 
                '',
                TRUE
            );
            $count++;
        }
        $dynamic_rows = json_encode($dynamic_rows); 
        
        // Add module level javascript to html head
        $script = "$(document).ready( function(){
            if ( ! $.isFunction($.fn.is_json) )
            {
                function is_json(str) 
                {
                    try {
                        JSON.parse(str);
                    } catch (e) {
                        return false;
                    }
                    return true;
                }
            }
            
            if ( ! $.isFunction($.fn.showNoRowExist) )
            {
                function showNoRowExist(){
                    var row = '';
                    var rows = $('#content_type_{$content_field_id} > tbody > tr').length;
                    if(rows === 0) {                        
                        row +=
                        '<tr class=\"matrix matrix-first matrix-last matrix-norows even\">' +
                        '   <td colspan=\"". ($total_cols = $total_cols + 1) ."\" class=\"matrix matrix-first matrix-firstcell matrix-last\">' +
                        '  	    No rows exist yet. <a id=\"createFirstRow_{$content_field_id}\">Create the first one.</a>' +
                        '   </td>' +
                        '</tr>';
                        $('table#content_type_{$content_field_id}').append(row);
                    }
                }
            }
            
            if ( ! $.isFunction($.fn.renumberRows) ) 
            {
                function renumberRows(tableRow = ''){
                    var spanOpen = '<div><span>';
                    var spanClose = '</span><a class=\"delRow\" style=\"opacity: 1; display: inline;\" title=\"Options\"></a></div>';
                    var theKey = '';
                    
                    $(tableRow).each(function(index, el){
                        $(this).children('th').first().html(function(i, text) {
                            if(index > 0){
                                var value = index++;
                                theKey = value;
                                
                                return spanOpen + value + spanClose + '<input name=\"content_type_{$content_field_id}[row_order][]\" value=\"'+ value +'\" type=\"hidden\">';
                            }
                        });
                        $(this).children('td').find('input, select, textarea').each(function(i){
                            $(this).attr('name', function(i){
                                var str = $(this).attr('name').slice(0, -3);
                                return str + '['+theKey+']'
                            });
                        })
                        .end();                        
                    });
                } 
            }
            
            if ( ! $.isFunction($.fn.createNewTableRow) )
            {
                function createNewTableRow(){
                    var newRow = $('<tr class=\"matrix\" id=\"tbl_row_\"+ counter +\"\">');
                    var cols   = '';
                    $('table#content_type_".$content_field_id." .matrix-norows').remove();
                    
                    cols += 
                    '<th class=\"matrix matrix-first matrix-tr-header\">' +
                    '    <div>' +
                    '        <span>'+ counter +'</span><a class=\"delRow\" style=\"display: inline; opacity: 1;\" title=\"Options\"></a>' +
                    '    </div>' +
                    '    <input name=\"content_type_".$content_field_id."[row_order][]\" value=\"'+ counter +'\" type=\"hidden\">' +
                    '</th>';
                    cols += 'jQuery.parseJSON({$dynamic_rows})';
                    newRow.append(cols);
                    
                    if (counter === ".$max_rows.") {
                        $('#field_{$content_field_id}_addrow_btn').hide();
                    }
                    $('table#content_type_{$content_field_id}').append(newRow);
                    
                    renumberRows('table#content_type_{$content_field_id} tr');
                    
                    counter++;
                }
            }
          
            // -----------------------------------------------------------
            
            var counter = ". (($row_count >= 0) ? (($row_count === 1) ? 1 : $row_count + 1) : 1) .";
            if(counter > 1) {
                $('table#content_type_".$content_field_id." .matrix-norows').remove();
            }
            if(counter === 1) {
                showNoRowExist();
            }
            
            /**
             * Make table rows sortable 
             */
            $('table#content_type_".$content_field_id.".matrix tbody').sortable({
                axis: 'y',
                placeholder: \"ui-state-highlight\",
                update: function(event, ui){
                    renumberRows('table#content_type_".$content_field_id." tr');
                }
            });
            
            /**
             * Add new row to table event listeners 
             */
            $('#field_{$content_field_id}_addrow_btn').on('click', function(){                
                createNewTableRow();
            });
            $('#createFirstRow_{$content_field_id}').on('click', function(){
                createNewTableRow();
            });
    
            /**
             * Remove row from table and save field IDs to an array 
             */
            $('table#content_type_{$content_field_id}').on('click', '.delRow', function(e){
                if (confirm('Are you sure you want to delete this?')) {
                    var array = new Array();
                    var value = $('input[name^=deleted_fields]').val();
                    var final = '';
                    var isNewField = true;
                    
                    // Loop over each field in the table row and add the field 
                    // name to a temporary array.
                    $(this).closest('tr').children('td').find('input, select, textarea').each(function(i, field){
                        var name = $(field).attr('name');
                        if (name.indexOf('grid_col_data') >= 0) {
                            array.push(name);
                            isNewField = false;
                        }
                    });
                    
                    // Only add fields that exists in the database to the deletable array
                    if( ! isNewField){
                        if(is_json(value)){
                            value = JSON.parse(value);
                        }
                        
                        final = JSON.stringify($.merge(array, value));
                        $('input[name^=deleted_fields]').val(final);
                    }
                    
                    $(this).closest('tr').remove(); // Remove the html table row
                
                    counter -= 1;
                    if(counter <= {$max_rows}){
                        $('#field_{$content_field_id}_addrow_btn').show();
                    }
                
                    renumberRows('table#content_type_{$content_field_id} tr');
                    showNoRowExist();
                }
            });
        });";

      $this->template->add_script($script);
        
      return $out;
    }
    
    // ------------------------------------------------------------------
   
    private function format_field_type($type, $grid_col_id, $options, $row_data, $grid_col_data_id = '', $row_order = '', $is_new_field = false)
    {
        $options    = (is_serialized($options)) ? unserialize($options) : $options;
        $row_order  = ($row_order >= 1) ? $row_order - 1 : 'x';
        $field_name = ($is_new_field) ? 'new_field' : 'grid_col_data';
        $field      = '';
        
        $data['field_name'] = $field_name.'['.$grid_col_data_id.']['.$grid_col_id.']['.$row_order.']';
        $data['field_id']   = $field_name.'_'.$grid_col_data_id.'_'.$grid_col_id;
        $data['field_css']  = '';
        $data['content']    = $row_data;
        $data['options']    = $options;
        
        switch($type) 
        {
            // CKEditor field type
            case 1 : 
                $this->template->add_javascript('/application/modules/content/content_fields/assets/js/ckeditor_grid_textarea_fields.js');
                $field = $this->load->view('grid/ckeditor', $data, TRUE);
                break;
            
            // Dropdown field type
            case 4 :
                $field = $this->load->view('grid/dropdown', $data, TRUE);
                break;
			
            // Textarea field type
			case 6 :
                $field = $this->load->view('grid/textarea', $data, TRUE);
                break;
                
            // Image upload field type
            case 8 :
                $this->template->add_javascript('/application/modules/content/content_fields/assets/js/image.js');
                $field = $this->load->view('grid/image', $data, TRUE);
                break;
                
            // File upload field type
            case 9 :
                $this->template->add_javascript('/application/modules/content/content_fields/assets/js/file.js');
                $field = $this->load->view('grid/file', $data, TRUE);
                break;
        }
        
        return $field;
    }

    // ------------------------------------------------------------------
    
    /**
     * Helper function to $this->table_markup() method
     */
    private function _get_missin_field_atts($field_id, $fields_array) 
    {
        $missing_fields = [];
        
        foreach($fields_array as $key => $field) {
            if($field_id === $field->id) {
                $missing_fields = (object) [
                    'id' => '',
                    'grid_col_id' => $field->id,
                    'row_data' => '',
                    'content_field_type_id' => $field->content_field_type_id,
                    'options' => $field->options,
                ];
            }
        }
        
        return $missing_fields;
    }
}
