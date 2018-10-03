<?php

/**
 * ModelExtensionPaymentMundipagg is responsible for module basic routines
 *
 * The purpose of this class is create and destroy data used by module. It
 * creates (and destroy) two tables: mundipagg_payments and mundipagg_customer,
 * which are used, respectively, to store user's credit card payments settings
 * and the relation between opencart and mundipagg customer ids.
 *
 * @package Mundipagg
 */
class ModelExtensionPaymentMundipagg extends Model
{
    /**
     * Install module
     *
     * @return void
     */
    public function install()
    {
        $this->createPaymentTable();
        $this->createCustomerTable();
        $this->createOrderTable();
        $this->createChargeTable();
        $this->createCreditCardTable();
        $this->createOrderBoletoInfoTable();
        $this->createOrderCardInfoTable();
        $this->createSubscriptionTable();

        //aggregates
        $this->createTemplateAggregateTables();
        $this->createRecurrencyProductAggregateTables();

        $this->populatePaymentTable();

        $this->installEvents();
    }

    /**
     * Uninstall module
     *
     * @return void
     */
    public function uninstall()
    {
        $this->dropPaymentTable();
        $this->dropCustomerTable();
        $this->dropOrderTable();
        $this->dropChargeTable();
        $this->dropCreditCardTable();
        $this->dropOrderBoletoInfoTable();
        $this->dropOrderCardInfoTable();
        $this->dropSubscriptionTable();

        //aggregates
        $this->dropRecurrencyProductAggregateTables();
        $this->dropTemplateAggregateTables();

        $this->uninstallEvents();
    }

    private function createTemplateAggregateTables()
    {
        //template table
        $createTemplate = "
            CREATE TABLE IF NOT EXISTS 
            `" . DB_PREFIX . "mundipagg_template` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `is_disabled` TINYINT NOT NULL DEFAULT 0,
            `is_single` TINYINT NOT NULL DEFAULT 0,
            `name` VARCHAR(45) NULL,
            `description` TEXT NULL,
            `accept_credit_card` TINYINT NOT NULL DEFAULT 0,
            `accept_boleto` TINYINT NOT NULL DEFAULT 0,
            `allow_installments` TINYINT NOT NULL DEFAULT 0,
            `due_type` CHAR NOT NULL,
            `due_value` TINYINT NOT NULL DEFAULT 0,            
            `trial` TINYINT NOT NULL DEFAULT 0,
            `installments` VARCHAR(45) NULL,
            PRIMARY KEY (`id`))   
        ";
        $this->db->query($createTemplate);

        //template_repetition table
        $createTemplateRepetition = "
            CREATE TABLE IF NOT EXISTS 
            `" . DB_PREFIX . "mundipagg_template_repetition` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `template_id` INT NOT NULL,
            `cycles` INT NOT NULL,
            `frequency` INT NOT NULL,
            `interval_type` CHAR NOT NULL,
            `discount_type` CHAR NOT NULL,
            `discount_value` FLOAT NOT NULL,
            PRIMARY KEY (`id`, `template_id`),
            INDEX `fk_template_repetition_template1_idx` (`template_id` ASC),
            CONSTRAINT `fk_template_repetition_template1`
              FOREIGN KEY (`template_id`)
              REFERENCES `" . DB_PREFIX . "mundipagg_template` (`id`)
              ON DELETE NO ACTION
              ON UPDATE NO ACTION)
        ";
        $this->db->query($createTemplateRepetition);
    }

    private function dropTemplateAggregateTables()
    {
        $dropTemplateRepetition = "
            DROP TABLE IF EXISTS 
            `" . DB_PREFIX . "mundipagg_template_repetition`
             CASCADE;
        ";
        $this->db->query($dropTemplateRepetition);

        $dropTemplate = "
            DROP TABLE IF EXISTS 
            `" . DB_PREFIX . "mundipagg_template` 
            CASCADE;
        ";
        $this->db->query($dropTemplate);
    }

    private function createRecurrencyProductAggregateTables()
    {
        //recurrency product table
        $createProductTable = "
            CREATE TABLE IF NOT EXISTS 
            `" . DB_PREFIX . "mundipagg_recurrency_product` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `is_disabled` TINYINT NOT NULL DEFAULT 0,
            `product_id` INT NOT NULL,
            `template_snapshot` TEXT NOT NULL,
            `template_id` INT NULL,
            `mundipagg_plan_id` VARCHAR(45) NULL,
            `mundipagg_plan_status` VARCHAR(45) NULL,
            `is_single` TINYINT NOT NULL,
            `price` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            INDEX `fk_plan_template1_idx` (`template_id` ASC),
            CONSTRAINT `fk_plan_template1`
              FOREIGN KEY (`template_id`)
              REFERENCES `" . DB_PREFIX . "mundipagg_template` (`id`)
              ON DELETE NO ACTION
              ON UPDATE NO ACTION)
        ";
        $this->db->query($createProductTable);

        //recurrency sub product table
        $createSubProduct = "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mundipagg_recurrency_subproduct` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `recurrency_product_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `quantity` INT NOT NULL,
            `cycles` INT NOT NULL,
            `cycle_type` CHAR NOT NULL,
            `price_in_cents` INT NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `fk_recurrency_subproduct_recurrency_product1_idx` (`recurrency_product_id` ASC),
            CONSTRAINT `fk_recurrency_subproduct_recurrency_product1`
            FOREIGN KEY (`recurrency_product_id`)
            REFERENCES `" . DB_PREFIX . "mundipagg_recurrency_product` (`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ";
        $this->db->query($createSubProduct);
    }

    private function dropRecurrencyProductAggregateTables()
    {
        $dropSubProduct = "
            DROP TABLE IF EXISTS 
            `" . DB_PREFIX . "mundipagg_recurrency_subproduct` 
            CASCADE;
        ";
        $this->db->query($dropSubProduct);

        $dropRecurrencyProduct = "
            DROP TABLE IF EXISTS 
            `" . DB_PREFIX . "mundipagg_recurrency_product` 
            CASCADE;
        ";
        $this->db->query($dropRecurrencyProduct);
    }

    private function createSubscriptionTable()
    {
        $createSubscription = "        
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mundipagg_subscription`
            (
              id              int auto_increment
                primary key,
              subscription_id varchar(45) not null,
              order_id        int         not null,
              payment_method  varchar(45) not null,
              status          varchar(45) not null,
              canceled_amount int         null
            );
        ";
        $this->db->query($createSubscription);
    }

    private function dropSubscriptionTable()
    {
        $dropSubscription = "
            DROP TABLE IF EXISTS 
            `" . DB_PREFIX . "mundipagg_subscription` 
            CASCADE;
        ";
        $this->db->query($dropSubscription);
    }
    /**
     * Install opencart event handlers
     *
     * @return void
     */
    private function installEvents()
    {
        //Add button to order list in admin
        $this->model_setting_event->addEvent(
            'payment_mundipagg_add_order_actions',
            'admin/view/sale/order_list/before',
            'extension/payment/mundipagg/callEvents'
        );

        //Add Mundipagg options in admin menu
        $this->model_setting_event->addEvent(
            'payment_mundipagg_add_mundipagg_menu',
            'admin/view/common/column_left/before',
            'extension/payment/mundipagg/callEvents'
        );

        //Add plan options in product page
        $this->model_setting_event->addEvent(
            'payment_mundipagg_add_product_plan_tab',
            'admin/view/catalog/product_form/before',
            'extension/payment/mundipagg/callEvents'
        );

        //Add product plan delete middleware
        $this->model_setting_event->addEvent(
            'payment_mundipagg_add_product_plan_delete_middleware',
            'admin/controller/catalog/product/delete/before',
            'extension/payment/mundipagg/callEvents'
        );

        //Add product plan index middleware
        $this->model_setting_event->addEvent(
            'payment_mundipagg_add_product_plan_index_middleware',
            'admin/controller/catalog/product/before',
            'extension/payment/mundipagg/callEvents'
        );

        //Add recurrency filters to product list in admin
        $this->model_setting_event->addEvent(
            'payment_mundipagg_add_product_list_recurrency_filter',
            'admin/view/catalog/product_list/before',
            'extension/payment/mundipagg/callEvents'
        );;

        //Add saved credit card list
        $this->model_setting_event->addEvent(
            'payment_mundipagg_saved_creditcards',
            'catalog/view/account/*/after',
            'extension/payment/mundipagg_events/showSavedCreditcards',
            1,
            9999
        );

        $this->model_setting_event->addEvent(
            'payment_mundipagg_show_account_order_info',
            'catalog/view/account/order_info/after',
            'extension/payment/mundipagg_events/showAccountOrderInfo'
        );

        $this->model_setting_event->addEvent(
            'payment_mundipagg_show_checkout_order_info',
            'catalog/view/common/success/after',
            'extension/payment/mundipagg_events/showCheckoutOrderInfo'
        );

        $this->model_setting_event->addEvent(
            'payment_mundipagg_prepare_checkout_order_info',
            'catalog/controller/checkout/success/before',
            'extension/payment/mundipagg_events/prepareCheckoutOrderInfo'
        );

        //add checkout payment method interceptor
        $this->model_setting_event->addEvent(
            'payment_mundipagg_recurrence_product_checkout_handler',
            'catalog/view/checkout/payment_method/after',
            'extension/payment/mundipagg/callEvents'
        );
    }

    /***
     * Uninstall opencart event handlers
     *
     * @return void
     */
    private function uninstallEvents()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEvent('payment_mundipagg');
        $this->model_setting_event->deleteEvent('payment_mundipagg_saved_creditcards');
        $this->model_setting_event->deleteEvent('payment_mundipagg_show_checkout_order_info');
        $this->model_setting_event->deleteEvent('payment_mundipagg_prepare_checkout_order_info');
    }

    /**
     * Create Payment table (mundipagg_payments)
     *
     * This table is used to store user settings on credit card transactions
     *
     * @return void
     */
    private function createPaymentTable()
    {
        $createPayment = "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mundipagg_payments` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `brand_name` VARCHAR(20),
                `is_enabled` TINYINT(1),
                `installments_up_to` TINYINT,
                `installments_without_interest` TINYINT,
                `interest` DOUBLE,
                `incremental_interest` DOUBLE
            );
        ";
        $this->db->query($createPayment);
    }

    private function createChargeTable()
    {
        $createCharge = '
          CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'mundipagg_charge` (
            `opencart_id` INT NOT NULL,
            `charge_id` VARCHAR(30) NOT NULL,
            `payment_method` VARCHAR(45) NOT NULL,
            `status` VARCHAR(45) NOT NULL,
            `paid_amount` INT NOT NULL,
            `amount` INT NOT NULL,
            `canceled_amount` INT NULL,
            PRIMARY KEY (`opencart_id`, `charge_id`),
            UNIQUE INDEX `charge_id_UNIQUE` (`charge_id` ASC));';
        $this->db->query($createCharge);
    }

    private function dropChargeTable()
    {
        $dropCharge = '
          DROP TABLE IF EXISTS 
          `' . DB_PREFIX . 'mundipagg_charge` CASCADE;'
        ;
        $this->db->query($dropCharge);
    }

    /**
     * Drop mundipagg_payments table
     *
     * @return void
     */
    private function dropPaymentTable()
    {
        $dropPayment =
            "DROP TABLE IF EXISTS 
            `" . DB_PREFIX . "mundipagg_payments`;";

        $this->db->query($dropPayment);
    }

    /**
     * Populate mundipagg_payments with preset info from remote json
     *
     * @return void
     */
    private function populatePaymentTable()
    {
        $preset = $this->getPaymentInfo()->brands;
        $preset->Default = $this->getDefaultCerditCardPreset();

        foreach ($preset as $brand => $value) {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "mundipagg_payments`
                (brand_name, is_enabled, installments_up_to, installments_without_interest, interest, incremental_interest)
                VALUES ('" .
                    $brand . "', " .
                    $value->enabled . ", " .
                    $value->installmentsUpTo . ", " .
                    $value->installmentsWithoutInterest . ", " .
                    $value->interest . ", " .
                    $value->incrementalInterest . "
                );"
            );
        }
    }

    /**
     * Save payment information from module admin panel
     *
     * @param array $payments Payment information from admin panel
     * @return void
     */
    public function savePaymentInformation($payments)
    {
        foreach ($payments as $brand => $info) {
            $sql = "UPDATE `" . DB_PREFIX . "mundipagg_payments` SET " .
            "is_enabled='" . $info['is_enabled'] . "', " .
            "installments_up_to='" . $info['installments_up_to'] . "', " .
            "installments_without_interest='" . $info['installments_without_interest'] . "', " .
            "interest='" . $info['interest'] . "', " .
            "incremental_interest='" . $info['incremental_interest'] . "' " .
            "WHERE brand_name='" . $brand . "'";

            $this->db->query($sql);
        }
    }

    /**
     * Get credit card information from database
     *
     * @return array
     */
    public function getCreditCardInformation()
    {
        $sql = "SELECT * from `". DB_PREFIX ."mundipagg_payments` order by id DESC";
        $query = $this->db->query($sql);
        $brands = $query->rows;
        $brandImages = $this->getCreditCardBrands();
        
        foreach ($brands as $index => $brand) {
            $brands[$index]['image'] = '';

            if (isset($brandImages[$brand['brand_name']]['image'])) {
                $brands[$index]['image'] =  $brandImages[$brand['brand_name']]['image'];
            }
        }
        
        return $brands;
    }
    
    /**
     * Get credit cards images from json
     *
     * @return Object
     */
    public function getCreditCardBrands()
    {
        try {
            $json = json_decode(
                file_get_contents(
                    'https://dashboard.mundipagg.com/emb/bank_info.json'
                )
            );
            if (isset($brandName)) {
                $brandName = ucfirst($brandName);
                return $json->brands->$brandName;
            }

            $brands = (array) $json->brands;
            foreach ($brands as $brandName => $brandImage) {
                $creditCardBrands[$brandName] = [
                    'name' => $brandName,
                    'image' => $brandImage->image
                ];
            }
            return $creditCardBrands;
        } catch (\Exception $e) {
            // @todo log error message
        }
    }

    /**
     * Get bank information used to generate boleto
     *
     * @return array
     */
    public function getBoletoInformation()
    {
        return array(
            '341' => 'Itau',
            '033' => 'Santander',
            '237' => 'Bradesco',
            '001' => 'Banco do Brasil',
            '399' => 'HSBC ',
            '104' => 'Caixa',
            '745' => 'CitiBank'
        );
    }

    /**
     * Create customer table, called mundipagg_customer
     *
     * This table is used to create a relation between opencart customers
     * and its respective profile in mundipagg api
     *
     * @return void
     */
    private function createCustomerTable()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mundipagg_customer` (
                `customer_id` INT(11) NOT NULL,
                `mundipagg_customer_id` VARCHAR(30) NOT NULL,
                UNIQUE INDEX `customer_id` (`customer_id`),
                UNIQUE INDEX `mundipagg_customer_id` (`mundipagg_customer_id`)
            );"
        );
    }

    /**
     * Drop mundipagg_customer table
     *
     * @return void
     */
    private function dropCustomerTable()
    {
        $this->db->query(
            "DROP TABLE IF EXISTS `" . DB_PREFIX . "mundipagg_customer`;"
        );
    }

    /**
     * Create order table, called mundipagg_order
     *
     * This table is used to create a relation between opencart orders
     * and its respective orders in mundipagg api
     *
     * @return void
     */
    private function createOrderTable()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mundipagg_order` (
                `opencart_id` INT(11) NOT NULL,
                `mundipagg_id` VARCHAR(30) NOT NULL,
                UNIQUE INDEX `opencart_id` (`opencart_id`),
                UNIQUE INDEX `mundipagg_id` (`mundipagg_id`)
            );"
        );
    }

    /**
     * Drop mundipagg_order table
     *
     * @return void
     */
    private function dropOrderTable()
    {
        $this->db->query(
            "DROP TABLE IF EXISTS `" . DB_PREFIX . "mundipagg_order`;"
        );
    }

    /**
     * Get preset payment information from json
     *
     * @return array
     */
    private function getPaymentInfo()
    {
        try {
            return json_decode(
                file_get_contents(
                    'https://dashboard.mundipagg.com/emb/payment.json'
                )
            );
        } catch(\Exception $e) {
            // @todo log error message
        }
    }

    private function createCreditCardTable()
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `'. DB_PREFIX .'mundipagg_creditcard` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `mundipagg_creditcard_id` VARCHAR(30) ,
                `mundipagg_customer_id` VARCHAR(30) NOT NULL,
                `first_six_digits` INT(6) NOT NULL,
                `last_four_digits` INT(4) NOT NULL,
                `brand` VARCHAR(15) NOT NULL,
                `holder_name` VARCHAR(50) NOT NULL,
                `exp_month` INT(2) NOT NULL,
                `exp_year` YEAR NOT NULL
                );'
        );
    }

    private function dropCreditCardTable()
    {
        $this->db->query(
            'DROP TABLE IF EXISTS `' . DB_PREFIX . 'mundipagg_creditcard`;'
        );
    }

    private function createOrderBoletoInfoTable()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mundipagg_order_boleto_info` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `opencart_order_id` INT(11) NOT NULL,
                `charge_id` VARCHAR(30) NOT NULL,
                `line_code` VARCHAR(60) NOT NULL DEFAULT '(INVALID DATA)',
                `due_at` VARCHAR(30) NOT NULL DEFAULT '(INVALID DATA)',
                `link` VARCHAR(256) NOT NULL DEFAULT '(INVALID DATA)'
                );"
        );
    }

    private function dropOrderBoletoInfoTable()
    {
        $this->db->query(
            'DROP TABLE IF EXISTS `' . DB_PREFIX . 'mundipagg_order_boleto_info`;'
        );
    }

    private function createOrderCardInfoTable()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mundipagg_order_creditcard_info` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `opencart_order_id` INT(11) NOT NULL,
                `charge_id` VARCHAR(30) NOT NULL,
                `holder_name` VARCHAR(100) NOT NULL DEFAULT '(INVALID DATA)',
                `brand` VARCHAR(30) NOT NULL DEFAULT '(INVALID DATA)',
                `last_four_digits` INT NOT NULL DEFAULT 0000,
                `installments` INT NOT NULL DEFAULT 0
                );"
        );
    }

    private function dropOrderCardInfoTable()
    {
        $this->db->query(
            'DROP TABLE IF EXISTS `' . DB_PREFIX . 'mundipagg_order_creditcard_info`;'
        );
    }

    private function getDefaultCerditCardPreset()
    {
        $default = new stdClass();
        $default->brandName = "Default";
        $default->enabled = 1;
        $default->installmentsUpTo = 12;
        $default->installmentsWithoutInterest = 4;
        $default->interest = 3;
        $default->incrementalInterest = "0.1";

        return $default;
    }
}

