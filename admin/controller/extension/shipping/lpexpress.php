<?php
/*
* @package		lpexpress
* @copyright	2017 e-tools, e-tools.lt
* @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @license		GNU/GPL based on AceShop www.joomace.net
*/


class ControllerExtensionShippingLpexpress extends Controller { 
    private $error = array();

    public function importTerminals() {
        $c = file_get_contents('https://www.lpexpress.lt/index.php?&cl=terminals&fnc=getTerminals');
        $data = json_decode($c);
        $this->db->query("DELETE FROM " . DB_PREFIX . "lpexpress_terminals");
        foreach($data as $t) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "lpexpress_terminals SET
				id='".$t->oxid."',
				active='".$this->db->escape($t->nfqactive)."',
				name='".$this->db->escape($t->name)."',
				address='".$this->db->escape($t->address)."',
				zip='".$this->db->escape($t->zip)."',
				city='".$this->db->escape($t->city)."',
				latitude='".$this->db->escape($t->latitude)."',
				longitude='".$this->db->escape($t->longitude)."',
				comment='".$this->db->escape($t->comment)."',
				collectinghours='".$this->db->escape($t->collectinghours)."',
				workinghours='".$this->db->escape($t->workinghours)."',
				sort_order='".(int)$t->nfqsort."'
			");
        }
		$this->response->redirect(
			$this->url->link('extension/shipping/lpexpress', 'user_token=' . $this->session->data['user_token'], 'SSL'));

        $data = $this->getTerminals();
        header('Content-Type: application/json');
        die(json_encode($data));

    }

    private function getTerminals() {

        $sql = "SELECT name, CONCAT(address, ' ', city) AS address, comment, collectinghours, workinghours FROM ".DB_PREFIX."lpexpress_terminals WHERE active = 1 ORDER BY sort_order ASC";
        $tq = $this->db->query($sql);

        if(!empty($tq->rows)) {
            return $tq->rows;
        }

        return array();
    }

    public function index() {
		$this->config->set('template_cache', false);

        $this->language->load('extension/shipping/lpexpress');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('shipping_lpexpress', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'type=shipping&user_token=' . $this->session->data['user_token'], 'SSL'));
        }



        $data['terminals'] = array();

        $data['terminals'] = $this->getTerminals();





        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_shipping'),
            'href'      => $this->url->link('marketplace/extension', 'type=shipping&user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('extension/shipping/lpexpress', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        //$data['action'] = $this->url->link('extenion/shipping/lpexpress', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['action'] = $this->url->link('extension/shipping/lpexpress', 'user_token=' . $this->session->data['user_token']);
		$data['update_terminals'] = $this->url->link('extension/shipping/lpexpress/importterminals', 'user_token=' . $this->session->data['user_token']);
        //$data['cancel'] = $this->url->link('extension/shipping', 'type=shipping&user_token=' . $this->session->data['user_token'], 'SSL');
        $data['cancel'] = $this->url->link('marketplace/extension', 'type=shipping&user_token=' . $this->session->data['user_token'], 'SSL');

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('localisation/geo_zone');

        $geo_zones = $this->model_localisation_geo_zone->getGeoZones();
        $data['geo_zones'] = $geo_zones;

        if (isset($this->request->post['shipping_lpexpress_tax_class_id'])) {
            $data['shipping_lpexpress_tax_class_id'] = $this->request->post['shipping_lpexpress_tax_class_id'];
        } else {
            $data['shipping_lpexpress_tax_class_id'] = $this->config->get('shipping_lpexpress_tax_class_id');
        }

        if (isset($this->request->post['shipping_lpexpress_geo_zone_id'])) {
            $data['shipping_lpexpress_geo_zone_id'] = $this->request->post['shipping_lpexpress_geo_zone_id'];
        } else {
            $data['shipping_lpexpress_geo_zone_id'] = $this->config->get('shipping_lpexpress_geo_zone_id');
        }



		$this->load->model('localisation/tax_class');
				
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
        if (isset($this->request->post['shipping_lpexpress_terminal'])) {
            $data['shipping_lpexpress_terminal'] = $this->request->post['shipping_lpexpress_terminal'];
        } else {
            $data['shipping_lpexpress_terminal'] = $this->config->get('shipping_lpexpress_terminal');
        }
        if (isset($this->request->post['shipping_lpexpress_post'])) {
            $data['shipping_lpexpress_post'] = $this->request->post['shipping_lpexpress_post'];
        } else {
            $data['shipping_lpexpress_post'] = $this->config->get('shipping_lpexpress_post');
        }
        if (isset($this->request->post['shipping_lpexpress_status'])) {
            $data['shipping_lpexpress_status'] = $this->request->post['shipping_lpexpress_status'];
        } else {
            $data['shipping_lpexpress_status'] = $this->config->get('shipping_lpexpress_status');
        }

        if (isset($this->request->post['shipping_lpexpress_total'])) {
            $data['shipping_lpexpress_total'] = $this->request->post['shipping_lpexpress_total'];
        } else if( $this->config->get('shipping_lpexpress_total') ) {
            $data['shipping_lpexpress_total'] = $this->config->get('shipping_lpexpress_total');
        } else {
            $data['shipping_lpexpress_total']  = 0;
        }

        if (isset($this->request->post['shipping_lpexpress_free_shipping_total'])) {
            $data['shipping_lpexpress_free_shipping_total'] = $this->request->post['shipping_lpexpress_free_shipping_total'];
        } else if( $this->config->get('shipping_lpexpress_free_shipping_total') ) {
            $data['shipping_lpexpress_free_shipping_total'] = $this->config->get('shipping_lpexpress_free_shipping_total');
        } else {
            $data['shipping_lpexpress_free_shipping_total']  = 0;
        }

        if (isset($this->request->post['shipping_lpexpress_cost'])) {
            $data['shipping_lpexpress_cost'] = $this->request->post['shipping_lpexpress_cost'];
        } else if( $this->config->get('shipping_lpexpress_cost') ) {
            $data['shipping_lpexpress_cost'] = $this->config->get('shipping_lpexpress_cost');
        } else {
            $data['shipping_lpexpress_cost']  = 0;
        }

        if (isset($this->request->post['shipping_lpexpress_sort_order'])) {
            $data['shipping_lpexpress_sort_order'] = $this->request->post['shipping_lpexpress_sort_order'];
        } else if( $this->config->get('shipping_lpexpress_sort_order') ) {
            $data['shipping_lpexpress_sort_order'] = $this->config->get('shipping_lpexpress_sort_order');
        } else {
            $data['shipping_lpexpress_sort_order'] = 0;
        }

        if (isset($this->request->post['shipping_lpexpress_format'])) {
            $data['shipping_lpexpress_format'] = $this->request->post['shipping_lpexpress_format'];
        } else if( $this->config->get('shipping_lpexpress_sort_order') ) {
            $data['shipping_lpexpress_format'] = $this->config->get('shipping_lpexpress_format');
        } else {
            $data['shipping_lpexpress_format'] = '{city}, {address} - {name}';
        }

        if (isset($this->request->post['shipping_lpexpress_terminal_order'])) {
            $data['shipping_lpexpress_terminal_order'] = $this->request->post['shipping_lpexpress_terminal_order'];
        } else if( $this->config->get('shipping_lpexpress_terminal_order') ) {
            $data['shipping_lpexpress_terminal_order'] = $this->config->get('shipping_lpexpress_terminal_order');
        } else {
            $data['shipping_lpexpress_terminal_order'] = 'name';
        }

        if (isset($this->request->post['shipping_lpexpress_terminal_order_dir'])) {
            $data['shipping_lpexpress_terminal_order_dir'] = $this->request->post['shipping_lpexpress_terminal_order_dir'];
        } else if( $this->config->get('shipping_lpexpress_terminal_order_dir') )  {
            $data['shipping_lpexpress_terminal_order_dir'] = $this->config->get('shipping_lpexpress_terminal_order_dir');
        } else {
            $data['shipping_lpexpress_terminal_order_dir'] = 'ASC';
        }


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/lpexpress', $data));
		//$this->response->setOutput($this->load->view('extension/shipping/omniva', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/shipping/lpexpress')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }


    public function install() {

        $sql = "CREATE TABLE IF NOT EXISTS  `".DB_PREFIX."lpexpress_terminals` (
		 `id` varchar(32) NOT NULL,
		 `active` tinyint(4) NOT NULL DEFAULT '1',
		 `name` varchar(255) NOT NULL,
		 `address` varchar(255) NOT NULL,
		 `zip` varchar(10) NOT NULL,
		 `city` varchar(255) NOT NULL,
		 `latitude` varchar(10) NOT NULL,
		 `longitude` varchar(10) NOT NULL,
		 `comment` text NOT NULL,
		 `collectinghours` varchar(255) NOT NULL,
		 `workinghours` varchar(50) NOT NULL,
		 `sort_order` int(11) NOT NULL,
		 PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $this->db->query($sql);

        $this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."lpexpress_terminal_to_order` (`order_id` bigint(20) NOT NULL, `terminal_id` varchar(32) NOT NULL, UNIQUE KEY `order_id` (`order_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    }

    public function uninstall() {
        $this->db->query("DROP TABLE ".DB_PREFIX."lpexpress_terminals");
        $this->db->query("DROP TABLE ".DB_PREFIX."lpexpress_terminal_to_order");
    }

}