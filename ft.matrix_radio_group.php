<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Matrix Radio Group
 *
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Matrix_radio_group_ft extends EE_Fieldtype {

	var $info = array(
		'name'    => 'Matrix Radio Group',
		'version' => '1.0'
	);

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		/** ----------------------------------------
		/**  Prepare Cache
		/** ----------------------------------------*/

		if (! isset($this->EE->session->cache['matrix_radio_group']))
		{
			$this->EE->session->cache['matrix_radio_group'] = array();
		}

		$this->cache =& $this->EE->session->cache['matrix_radio_group'];
	}

	// --------------------------------------------------------------------

	/**
	 * Display Cell Settings
	 */
	function display_cell_settings($data)
	{
		$this->EE->lang->loadfile('matrix_radio_group');

		if (! isset($data['label'])) $data['label'] = '';

		return array(
			array(
				lang('label'),
				'<input type="text" class="matrix-textarea" name="label" value="'.$data['label'].'" />'
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field
	 */
	function display_field($data)
	{
		return 'This fieldtype only works within Matrix fields.';
	}

	/**
	 * Display Cell
	 */
	function display_cell($data)
	{
		if (! isset($this->cache['included_resources']))
		{
			$css = '<style type="text/css">'
			     .   'td.matrix_radio_group { vertical-align: middle !important; }'
			     . '</style>';

			$js = '<script type="text/javascript">'
			    .   'Matrix.bind("matrix_radio_group", "display", function(cell){'
			    .     'var $inputs = jQuery("input", cell.dom.$td);'
			    .     'if ($inputs.val() == "0") $inputs.val(cell.row.id);'
			    .   '});'
			    . '</script>';

			$this->EE->cp->add_to_head($css);
			$this->EE->cp->add_to_foot($js);

			$this->cache['included_resources'] = TRUE;
		}

		$val = isset($this->row_id) ? 'row_id_'.$this->row_id : '0';

		$html = '<label><input type="radio" name="matrix_radio_group[col_id_'.$this->col_id.']" value="'.$val.'" '.($data == 'y' ? 'checked="checked"' : '').' />'
		      . NBS.NBS.NBS . $this->settings['label'].'</label>'
		      . '<input type="hidden" name="'.$this->cell_name.'" value="'.$val.'" />';

		return array(
			'data' => $html,
			'class' => 'matrix_radio_group'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Save Cell
	 */
	function save_cell($data)
	{
		$post = $this->EE->input->post('matrix_radio_group');

		return $post[$this->settings['col_name']] == $data ? 'y' : '';
	}

}
