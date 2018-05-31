<?php
/*
* @package		lpexpress
* @copyright	2017 e-tools.lt
* @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @license		GNU/GPL based on AceShop www.joomace.net
*
*checkout.twig #collapse-shipping-method select, 
*/

 
class ModelExtensionShippingLpexpress extends Model {    
    function getQuote($address) {
        $this->language->load('extension/shipping/lpexpress');
        if($address['iso_code_2'] != 'LT')
            return array();
		$currency = "EUR";
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_lpexpress_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if (!$this->config->get('shipping_lpexpress_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        if ($this->cart->getSubTotal() < $this->config->get('shipping_lpexpress_total')) {
            $status = false;
        }

        $error = '';
        $bError = false;
        $method_data = array();

        if ($status  && ($this->config->get('shipping_lpexpress_terminal') || $this->config->get('shipping_lpexpress_post'))) {
            $quote_data = array();
            $sub_quote = array();
            $cost = $this->config->get('shipping_lpexpress_cost');
			$cost_text = $this->currency->format($this->tax->calculate($cost, 
																	   $this->config->get('shipping_lpexpress_tax_class_id'), 
																	   $this->config->get('config_tax')), 
												$this->session->data['currency']);
			

            if( ($this->config->get('shipping_lpexpress_free_shipping_total') > 0)  && 
                ($this->cart->getSubTotal() >= $this->config->get('shipping_lpexpress_free_shipping_total')) ) {
                $cost = 0;
				$cost_text = $this->currency->format($this->currency->convert($cost, 
																			  $currency, 
																			  $this->session->data['currency']), 
																$this->session->data['currency']);
            }

            $terminals = $this->getTerminals();

            if(!empty($terminals) && $this->config->get('shipping_lpexpress_terminal')) {
                $format = $this->config->get('shipping_lpexpress_format');
                $find = array('{city}','{address}','{name}');
                $cabine_select = '<script>$( "input[name=shipping_method]" ).focus(function() { $( this ).blur(); });
                $(".lpexpress_terminal_opt").parent().parent().hide();
                </script>
                <select name="lpexpress_terminal" id="lpexpress_terminal" class="form-control form-inline input-sm" style="width: 70%; display: inline;"
                onchange="if($(this).val()!=\'\')$(\'#lpexpress_terminal\').parent().find(\'input\').eq(0).val(\'lpexpress.lpexpress_true\'); 
                else $(\'#lpexpress_terminal\').parent().find(\'input\').eq(0).val(\'fake.lpexpress_true\'); 
                $(\'#lpexpress_terminal\').parent().find(\'input\').eq(0).prop(\'checked\',true);"
                onfocus="$(\'#lpexpress_terminal\').parent().find(\'input\').eq(0).prop(\'checked\',true);">
                <option value="">'.$this->language->get('text_select_terminal').'</option>';
                
                foreach ($terminals as $key => $t) {
                    $replace = array('city' => $t['city'], 'address'  => $t['address'], 'name'   => $t['name'] );

                    //$cabine_select .= '<option value="lpexpress.'.$t["id"].'">'.trim(str_replace($find, $replace, $format)).'</option>';
                    $cabine_select .= '<option>'.trim(str_replace($find, $replace, $format)).'</option>';

                    $sub_quote[$t['id']] = array(
                        'code'         => 'lpexpress.'.$t['id'],
                        'title'        => '<div class="lpexpress_terminal_opt">'.trim(str_replace($find, $replace, $format)).'</div>',
                        'cost'         => $cost,
                        'tax_class_id' => $this->config->get('shipping_lpexpress_tax_class_id'),
                        'sort_order' => $this->config->get('shipping_omnivalt_sort_order'),
                        'text'         => $cost_text,
                    );

                }
                $cabine_select .= '</select>';


            $sub_quote = array();
            $sub_quote['lpexpress_true'] = array(
                'code'         => 'lpexpress.lpexpress_true',
                'title'        => '<div class="lpexpress_terminal_opt">LPexpress terminal</div>',
                'cost' => $this->currency->convert($cost, $currency, $this->config->get('config_currency')),
                'tax_class_id' => $this->config->get('shipping_lpexpress_tax_class_id'),
                'sort_order' => $this->config->get('shipping_omnivalt_sort_order'),
                'text' => $this->currency->format(
                                    $this->currency->convert(
                                                        $cost, 
                                                        $currency, 
                                                        $this->session->data['currency']), 
                                                        $this->session->data['currency']),
            );
            $quote_data['lpexpress'] = array(
                'code'         => 'fake.lpexpress',
                'title'        => $this->language->get('text_title') . $cabine_select ,
                'cost' => $this->currency->convert($cost, $currency, $this->config->get('config_currency')),
                'tax_class_id' => $this->config->get('shipping_lpexpress_tax_class_id'),
                'sort_order' => $this->config->get('shipping_omnivalt_sort_order'),
                'text' => $this->currency->format(
                                    $this->currency->convert(
                                                        $cost, 
                                                        $currency, 
                                                        $this->session->data['currency']), 
                                                        $this->session->data['currency']),
            );
        } else {
            $status = false;
        }
        if($this->config->get('shipping_lpexpress_post')) {
            $quote_data['lpexpress_post'] = array(
                'code'         => 'lpexpress.lpexpress_post',
                'title'        => $this->language->get('text_title_post'),
                'cost'         => $cost,
                'tax_class_id' => $this->config->get('shipping_lpexpress_tax_class_id'),
                'sort_order' => $this->config->get('shipping_omnivalt_sort_order'),
                'text'         =>  $this->currency->format($this->tax->calculate($cost, $this->config->get('lpexpress_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
            );
        }
            $method_data = array(
                'code'       => 'lpexpress',
                'title'      => $this->language->get('text_title'),
                'quote'      => array_merge($quote_data, $sub_quote),
                'sort_order' => $this->config->get('shipping_lpexpress_sort_order'),
                'cost'         => $cost_text,
                'error'      => $bError
            );
        }

        return $method_data;
    }


    private function getTerminals() {

        $order_by = $this->config->get('shipping_lpexpress_terminal_order');
        $order_dir = $this->config->get('shipping_lpexpress_terminal_order_dir');

        //$sql = "SELECT name, CONCAT(city, ' ', address) AS address, comment, collectinghours, workinghours FROM ".DB_PREFIX."lpexpress_terminals WHERE active = 1 ORDER BY sort_order ASC";
        $sql = "SELECT id, name, city , address, comment, collectinghours, workinghours FROM ".DB_PREFIX."lpexpress_terminals WHERE active = 1 ORDER BY ".$order_by." " .$order_dir;
        $tq = $this->db->query($sql);

        if(!empty($tq->rows)) {
            return $tq->rows;
        }

        return array();
    }

    public function getTerminalName($terminal_id) {
        $sql = "SELECT  name, city , address FROM ".DB_PREFIX."lpexpress_terminals WHERE id = '".$this->db->escape($terminal_id)."' LIMIT 1";
        $tq = $this->db->query($sql);

        if ($tq->num_rows) {

            $format = $this->config->get('lpexpress_format');

            $find = array(
                '{city}',
                '{address}',
                '{name}'
            );

            $replace = array(
                'city' => $tq->row['city'],
                'address'  => $tq->row['address'],
                'name'   => $tq->row['name']
            );

            return trim(str_replace($find, $replace, $format));
        }

        return '';

    }

}