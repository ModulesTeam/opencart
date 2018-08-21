<?php

class ModelExtensionPaymentMundipaggProduct extends ModelCatalogProduct
{
    public function getProducts($data = array())
    {
        $filter = '';
        if (isset($this->request->get['filter_mp_type'])) {
            $filter = strtolower($this->request->get['filter_mp_type']);
        }

        $sql = "
            SELECT
              r.id as mundipagg_recurrency_product_id,
              r.is_single as mundipagg_recurrency_product_is_single,
              p.*,
              pd.*
            FROM
              " . DB_PREFIX . "product p
              LEFT JOIN " . DB_PREFIX . "product_description pd
                ON (p.product_id = pd.product_id)
              LEFT JOIN " . DB_PREFIX . "mundipagg_recurrency_product as r
                ON (p.product_id = r.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";

        switch ($filter) {
            case "normal":
                $sql .= " AND r.id IS NULL ";
                break;
            case "single":
                $sql .= " AND r.id IS NOT NULL AND r.is_single = TRUE";
                break;
            case "plan":
                $sql .= " AND r.id IS NOT NULL AND r.is_single = FALSE";
                break;
        }

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '" . $this->db->escape((string)$data['filter_name']) . "%'";
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape((string)$data['filter_model']) . "%'";
        }

        if (!empty($data['filter_price'])) {
            $sql .= " AND p.price LIKE '" . $this->db->escape((string)$data['filter_price']) . "%'";
        }

        if (isset($data['filter_quantity']) && $data['filter_quantity'] !== '') {
            $sql .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        $sql .= " GROUP BY p.product_id, r.id";

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.price',
            'p.quantity',
            'p.status',
            'p.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
}