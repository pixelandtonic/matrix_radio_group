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
	 * Display Cell
	 */
	function display_cell($data)
	{
		if (! isset($this->cache['included_resources']))
		{
			$js = '<script type="text/javascript">'
			    .   'Matrix.bind("matrix_radio_group", "display", function(cell){'
			    .     'var $inputs = jQuery("input", cell.dom.$td);'
			    .     'if ($inputs.val() == "0") $inputs.val(cell.row.id);'
			    .   '});'
			    . '</script>';

			$this->EE->cp->add_to_foot($js);

			$this->cache['included_resources'] = TRUE;
		}

		$val = isset($this->row_id) ? 'row_id_'.$this->row_id : '0';

		return '<label><input type="radio" name="matrix_radio_group[col_id_'.$this->col_id.']" value="'.$val.'" '.($data == 'y' ? 'checked="checked"' : '').' />'
		       . NBS.NBS.NBS . $this->settings['label'].'</label>'
		       . '<input type="hidden" name="'.$this->cell_name.'" value="'.$val.'" />';
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

	// --------------------------------------------------------------------

	/**
	 * Save Selections
	 */
	private function _save_selections($selections, $data)
	{
		// Delete previous selections
		$this->EE->db->where($data)
		             ->delete('assets_entries');


		if ($selections)
		{
			foreach ($selections as $asset_order => $asset_id)
			{
				$selection_data = array_merge($data, array(
					'asset_id'    => $asset_id,
					'asset_order' => $asset_order
				));

				$this->EE->db->insert('assets_entries', $selection_data);
			}
		}
	}

	/**
	 * Post Save
	 */
	function post_save($data)
	{
		// make sure this should have been called in the first place
		if (! isset($this->cache['selections'][$this->settings['field_id']])) return;

		// get the selections from the cache
		$selections = $this->cache['selections'][$this->settings['field_id']];

		$data = array(
			'entry_id' => $this->settings['entry_id'],
			'field_id' => $this->settings['field_id']
		);

		// save the changes
		$this->_save_selections($selections, $data);
	}

	/**
	 * Post Save Cell
	 */
	function post_save_cell($data)
	{
		// get the selections from the cache
		$selections = $this->cache['selections'][$this->settings['field_id']][$this->settings['col_id']][$this->settings['row_name']];

		$data = array(
			'entry_id' => $this->settings['entry_id'],
			'field_id' => $this->settings['field_id'],
			'col_id'   => $this->settings['col_id'],
			'row_id'   => $this->settings['row_id']
		);

		// save the changes
		$this->_save_selections($selections, $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Pre Process
	 */
	function pre_process()
	{
		$sql = 'SELECT a.* FROM exp_assets a
		        INNER JOIN exp_assets_entries ae ON ae.asset_id = a.asset_id
		        WHERE ae.entry_id = "'.$this->row['entry_id'].'"
		          AND ae.field_id = "'.$this->field_id.'"';

		if (isset($this->row_id))
		{
			$sql .= ' AND ae.col_id = "'.$this->col_id.'" AND ae.row_id = "'.$this->row_id.'"';
		}

		$sql .= ' ORDER BY ae.asset_order';

		$assets = $this->EE->db->query($sql);

		$files = array();

		foreach ($assets->result_array() as $asset)
		{
			$this->helper->parse_filedir_path($asset['file_path'], $filedir, $path);

			if ($path)
			{
				$asset['full_path'] = $this->helper->get_server_path($filedir) . $path;

				if (file_exists($asset['full_path']))
				{
					$asset['filedir'] = $filedir;
					$asset['path'] = $path;

					$files[] = $asset;
				}
			}
		}

		return $files;
	}

	/**
	 * Replace Tag
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		if (! $data || ! $tagdata) return;

		$total_files = count($data);

		$vars = array();

		foreach ($data as $asset)
		{
			$kind = $this->helper->get_kind($asset['full_path']);

			if ($kind == 'image') $image_size = getimagesize($asset['full_path']);

			$vars[] = array(
				'url'         => $asset['filedir']->url . $asset['path'],
				'filename'    => basename($asset['file_path']),
				'extension'   => strtolower(pathinfo($asset['full_path'], PATHINFO_EXTENSION)),
				'kind'        => $kind,
				'date'        => $asset['date'],
				'width'       => ($kind == 'image' ? $image_size[0] : ''),
				'height'      => ($kind == 'image' ? $image_size[1] : ''),
				'size'        => $this->helper->format_filesize(filesize($asset['full_path'])),
				'title'       => $asset['title'],
				'alt_text'    => $asset['alt_text'],
				'caption'     => $asset['caption'],
				'author'      => $asset['author'],
				'desc'        => $asset['desc'],
				'location'    => $asset['location'],
				'total_files' => $total_files
			);
		}

		return $this->EE->TMPL->parse_variables($tagdata, $vars);
	}

	/**
	 * Replace URL
	 */
	function replace_url($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $asset['filedir']->url . $asset['path'];
	}

	/**
	 * Replace Filename
	 */
	function replace_filename($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return basename($asset['file_path']);
	}

	/**
	 * Replace Extenison
	 */
	function replace_($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return strtolower(pathinfo($asset['full_path'], PATHINFO_EXTENSION));
	}

	/**
	 * Replace Kind
	 */
	function replace_kind($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $this->helper->get_kind($asset['full_path']);;
	}

	/**
	 * Replace XYZ
	 */
	function replace_date($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $asset['date'];
	}

	/**
	 * Replace Width
	 */
	function replace_width($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		$kind = $this->helper->get_kind($asset['full_path']);
		return $kind == 'image' ? $image_size[0] : '';
	}

	/**
	 * Replace Height
	 */
	function replace_height($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		$kind = $this->helper->get_kind($asset['full_path']);
		return $kind == 'image' ? $image_size[1] : '';
	}

	/**
	 * Replace Size
	 */
	function replace_size($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $this->helper->format_filesize(filesize($asset['full_path']));
	}

	/**
	 * Replace Title
	 */
	function replace_title($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $asset['title'];
	}

	/**
	 * Replace Alt Text
	 */
	function replace_alt_text($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $asset['alt_text'];
	}

	/**
	 * Replace Caption
	 */
	function replace_caption($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $asset['caption'];
	}

	/**
	 * Replace Author
	 */
	function replace_author($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $asset['author'];
	}

	/**
	 * Replace Description
	 */
	function replace_desc($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $asset['desc'];
	}

	/**
	 * Replace Location
	 */
	function replace_location($data)
	{
		if (! $data) return;
		if (is_array($data)) $data = $data[0];

		return $asset['location'];
	}

	/**
	 * Replace Tag
	 */
	function replace_total_files($data)
	{
		return (string) count($data);
	}

}
