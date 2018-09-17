-- Creating module tables;

CREATE TABLE IF NOT EXISTS `oc_mundipagg_payments` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `brand_name` VARCHAR(20),
                `is_enabled` TINYINT(1),
                `installments_up_to` TINYINT,
                `installments_without_interest` TINYINT,
                `interest` DOUBLE,
                `incremental_interest` DOUBLE
            );

CREATE TABLE IF NOT EXISTS `oc_mundipagg_charge` (
                `opencart_id` INT NOT NULL,
                `charge_id` VARCHAR(30) NOT NULL,
                `payment_method` VARCHAR(45) NOT NULL,
                `status` VARCHAR(45) NOT NULL,
                `paid_amount` INT NOT NULL,
                `amount` INT NOT NULL,
                `canceled_amount` INT NULL,
                PRIMARY KEY (`opencart_id`, `charge_id`),
                UNIQUE INDEX `charge_id_UNIQUE` (`charge_id` ASC)
            );

CREATE TABLE IF NOT EXISTS `oc_mundipagg_customer` (
                `customer_id` INT(11) NOT NULL,
                `mundipagg_customer_id` VARCHAR(30) NOT NULL,
                UNIQUE INDEX `customer_id` (`customer_id`),
                UNIQUE INDEX `mundipagg_customer_id` (`mundipagg_customer_id`)
            );

CREATE TABLE IF NOT EXISTS `oc_mundipagg_order` (
                `opencart_id` INT(11) NOT NULL,
                `mundipagg_id` VARCHAR(30) NOT NULL,
                UNIQUE INDEX `opencart_id` (`opencart_id`),
                UNIQUE INDEX `mundipagg_id` (`mundipagg_id`)
            );

CREATE TABLE IF NOT EXISTS `oc_mundipagg_creditcard` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `mundipagg_creditcard_id` VARCHAR(30) ,
                `mundipagg_customer_id` VARCHAR(30) NOT NULL,
                `first_six_digits` INT(6) NOT NULL,
                `last_four_digits` INT(4) NOT NULL,
                `brand` VARCHAR(15) NOT NULL,
                `holder_name` VARCHAR(50) NOT NULL,
                `exp_month` INT(2) NOT NULL,
                `exp_year` YEAR NOT NULL
            );

CREATE TABLE IF NOT EXISTS `oc_mundipagg_order_boleto_info` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `opencart_order_id` INT(11) NOT NULL,
                `charge_id` VARCHAR(30) NOT NULL,
                `line_code` VARCHAR(60) NOT NULL DEFAULT '(INVALID DATA)',
                `due_at` VARCHAR(30) NOT NULL DEFAULT '(INVALID DATA)',
                `link` VARCHAR(256) NOT NULL DEFAULT '(INVALID DATA)'
            );

CREATE TABLE IF NOT EXISTS `oc_mundipagg_order_creditcard_info` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `opencart_order_id` INT(11) NOT NULL,
                `charge_id` VARCHAR(30) NOT NULL,
                `holder_name` VARCHAR(100) NOT NULL DEFAULT '(INVALID DATA)',
                `brand` VARCHAR(30) NOT NULL DEFAULT '(INVALID DATA)',
                `last_four_digits` INT NOT NULL DEFAULT 0000,
                `installments` INT NOT NULL DEFAULT 0
            );

 CREATE TABLE IF NOT EXISTS `oc_mundipagg_template` (
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
            PRIMARY KEY (`id`));

CREATE TABLE IF NOT EXISTS `oc_mundipagg_template_repetition` (
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
              REFERENCES `oc_mundipagg_template` (`id`)
              ON DELETE NO ACTION
              ON UPDATE NO ACTION);

CREATE TABLE IF NOT EXISTS `oc_mundipagg_recurrency_product` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `is_disabled` TINYINT NOT NULL DEFAULT 0,
            `product_id` INT NOT NULL,
            `template_snapshot` TEXT NOT NULL,
            `template_id` INT NULL,
            `mundipagg_plan_id` VARCHAR(45) NULL,
            `is_single` TINYINT NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `fk_plan_template1_idx` (`template_id` ASC),
            CONSTRAINT `fk_plan_template1`
              FOREIGN KEY (`template_id`)
              REFERENCES `oc_mundipagg_template` (`id`)
              ON DELETE NO ACTION
              ON UPDATE NO ACTION);

CREATE TABLE IF NOT EXISTS `oc_mundipagg_recurrency_subproduct` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `recurrency_product_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `quantity` INT NOT NULL,
            `cycles` INT NOT NULL,
            `cycle_type` CHAR NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `fk_recurrency_subproduct_recurrency_product1_idx` (`recurrency_product_id` ASC),
            CONSTRAINT `fk_recurrency_subproduct_recurrency_product1`
            FOREIGN KEY (`recurrency_product_id`)
            REFERENCES `oc_mundipagg_recurrency_product` (`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION);

-- populating module tables;

INSERT INTO opencart.oc_mundipagg_payments (
    brand_name,
    is_enabled,
    installments_up_to,
    installments_without_interest,
    interest,
    incremental_interest
) VALUES
('Visa', 1, 12, 4, 3, 0.1),
('Mastercard', 1, 12, 4, 3, 0.1),
('Hipercard', 1, 12, 4, 3, 0.1),
('Elo', 1, 12, 4, 3, 0.1),
('Diners', 1, 12, 4, 3, 0.1),
('Amex', 1, 12, 4, 3, 0.1),
('Default', 1, 12, 4, 3, 0.1);

-- setting module configs

INSERT INTO opencart.oc_custom_field (custom_field_id, type, value, validation, location, status, sort_order) VALUES
(1, 'text', '', '', 'address', 1, 0),
(2, 'text', '', '', 'address', 1, 0);

INSERT INTO opencart.oc_custom_field_description (custom_field_id, language_id, name) VALUES
(1, 1, 'Number'),
(2, 1, 'Complement');

INSERT INTO opencart.oc_custom_field_customer_group (custom_field_id, customer_group_id, required) VALUES
(1, 1, 1),
(2, 1, 0);

INSERT INTO opencart.oc_event (code, `trigger`, action, status, sort_order) VALUES
('payment_mundipagg_add_order_actions', 'admin/view/sale/order_list/before', 'extension/payment/mundipagg/callEvents', 1, 0),
('payment_mundipagg_add_mundipagg_menu', 'admin/view/common/column_left/before', 'extension/payment/mundipagg/callEvents', 1, 0),
('payment_mundipagg_add_product_plan_tab', 'admin/view/catalog/product_form/before', 'extension/payment/mundipagg/callEvents', 1, 0),
('payment_mundipagg_saved_creditcards', 'catalog/view/account/*/after', 'extension/payment/mundipagg_events/showSavedCreditcards', 1, 9999),
('payment_mundipagg_show_account_order_info', 'catalog/view/account/order_info/after', 'extension/payment/mundipagg_events/showAccountOrderInfo', 1, 0),
('payment_mundipagg_show_checkout_order_info', 'catalog/view/common/success/after', 'extension/payment/mundipagg_events/showCheckoutOrderInfo', 1, 0),
('payment_mundipagg_prepare_checkout_order_info', 'catalog/controller/checkout/success/before', 'extension/payment/mundipagg_events/prepareCheckoutOrderInfo', 1, 0),
('payment_mundipagg_add_product_plan_delete_middleware', 'admin/controller/catalog/product/delete/before', 'extension/payment/mundipagg/callEvents', 1, 90),
('payment_mundipagg_add_product_plan_index_middleware', 'admin/controller/catalog/product/before', 'extension/payment/mundipagg/callEvents', 1, 90),
('payment_mundipagg_add_product_list_recurrency_filter', 'admin/view/catalog/product_list/before', 'extension/payment/mundipagg/callEvents', 1, 90);

INSERT INTO opencart.oc_extension (type, code) VALUES
('payment', 'mundipagg');

INSERT INTO opencart.oc_setting (store_id, code, `key`, value, serialized) VALUES
(0, 'payment_mundipagg', 'payment_mundipagg_title', 'Mundipagg', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_status', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_mapping_number', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_mapping_complement', '2', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_test_mode', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_log_enabled', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_credit_card_status', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_credit_card_payment_title', 'credit card', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_credit_card_invoice_name', 'credit card', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_credit_card_operation', 'Auth', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_credit_card_is_saved_enabled', 'true', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_credit_card_two_credit_cards_enabled', 'true', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_credit_card_two_credit_cards_payment_title', 'two credit card', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_boleto_status', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_boleto_title', 'boleto', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_boleto_name', 'boleto', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_boleto_bank', '341', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_boleto_due_date', '3', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_boleto_instructions', 'instructions', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_boletoCreditCard_status', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_antifraud_status', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_antifraud_minval', '100', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_recurrence_checkoutconflictmessage', 'conflito', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_recurrence_paymentUpdateCustomer_status', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_recurrence_subscriptionInstallment_status', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_recurrence_creditcardUpdateCustomer_status', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_recurrence_subscriptionByPlan_status', '1', 0),
(0, 'payment_mundipagg', 'payment_mundipagg_recurrence_singleSubscription_status', '1', 0)
;

UPDATE opencart.oc_user_group SET permission = '{"access":["catalog\\/attribute","catalog\\/attribute_group","catalog\\/category","catalog\\/download","catalog\\/filter","catalog\\/information","catalog\\/manufacturer","catalog\\/option","catalog\\/product","catalog\\/product_option","catalog\\/recurring","catalog\\/review","common\\/column_left","common\\/developer","common\\/filemanager","common\\/profile","common\\/security","customer\\/custom_field","customer\\/customer","customer\\/customer_approval","customer\\/customer_group","design\\/banner","design\\/layout","design\\/theme","design\\/translation","design\\/seo_url","event\\/statistics","event\\/theme","extension\\/analytics\\/google","extension\\/captcha\\/basic","extension\\/captcha\\/google","extension\\/dashboard\\/activity","extension\\/dashboard\\/chart","extension\\/dashboard\\/customer","extension\\/dashboard\\/map","extension\\/dashboard\\/online","extension\\/dashboard\\/order","extension\\/dashboard\\/recent","extension\\/dashboard\\/sale","extension\\/extension\\/analytics","extension\\/extension\\/captcha","extension\\/extension\\/currency","extension\\/extension\\/dashboard","extension\\/extension\\/feed","extension\\/extension\\/fraud","extension\\/extension\\/menu","extension\\/extension\\/module","extension\\/extension\\/payment","extension\\/extension\\/report","extension\\/extension\\/shipping","extension\\/extension\\/theme","extension\\/extension\\/total","extension\\/feed\\/google_base","extension\\/feed\\/google_sitemap","extension\\/feed\\/openbaypro","extension\\/fraud\\/fraudlabspro","extension\\/fraud\\/ip","extension\\/fraud\\/maxmind","extension\\/marketing\\/remarketing","extension\\/module\\/account","extension\\/module\\/amazon_login","extension\\/module\\/amazon_pay","extension\\/module\\/banner","extension\\/module\\/bestseller","extension\\/module\\/carousel","extension\\/module\\/category","extension\\/module\\/divido_calculator","extension\\/module\\/ebay_listing","extension\\/module\\/featured","extension\\/module\\/filter","extension\\/module\\/google_hangouts","extension\\/module\\/html","extension\\/module\\/information","extension\\/module\\/klarna_checkout_module","extension\\/module\\/latest","extension\\/module\\/laybuy_layout","extension\\/module\\/pilibaba_button","extension\\/module\\/pp_button","extension\\/module\\/pp_login","extension\\/module\\/sagepay_direct_cards","extension\\/module\\/sagepay_server_cards","extension\\/module\\/slideshow","extension\\/module\\/special","extension\\/module\\/store","extension\\/openbay\\/amazon","extension\\/openbay\\/amazon_listing","extension\\/openbay\\/amazon_product","extension\\/openbay\\/amazonus","extension\\/openbay\\/amazonus_listing","extension\\/openbay\\/amazonus_product","extension\\/openbay\\/ebay","extension\\/openbay\\/ebay_profile","extension\\/openbay\\/ebay_template","extension\\/openbay\\/etsy","extension\\/openbay\\/etsy_product","extension\\/openbay\\/etsy_shipping","extension\\/openbay\\/etsy_shop","extension\\/openbay\\/fba","extension\\/payment\\/amazon_login_pay","extension\\/payment\\/authorizenet_aim","extension\\/payment\\/authorizenet_sim","extension\\/payment\\/bank_transfer","extension\\/payment\\/bluepay_hosted","extension\\/payment\\/bluepay_redirect","extension\\/payment\\/cardconnect","extension\\/payment\\/cardinity","extension\\/payment\\/cheque","extension\\/payment\\/cod","extension\\/payment\\/divido","extension\\/payment\\/eway","extension\\/payment\\/firstdata","extension\\/payment\\/firstdata_remote","extension\\/payment\\/free_checkout","extension\\/payment\\/g2apay","extension\\/payment\\/globalpay","extension\\/payment\\/globalpay_remote","extension\\/payment\\/klarna_account","extension\\/payment\\/klarna_checkout","extension\\/payment\\/klarna_invoice","extension\\/payment\\/laybuy","extension\\/payment\\/liqpay","extension\\/payment\\/nochex","extension\\/payment\\/paymate","extension\\/payment\\/paypoint","extension\\/payment\\/payza","extension\\/payment\\/perpetual_payments","extension\\/payment\\/pilibaba","extension\\/payment\\/pp_express","extension\\/payment\\/pp_payflow","extension\\/payment\\/pp_payflow_iframe","extension\\/payment\\/pp_pro","extension\\/payment\\/pp_pro_iframe","extension\\/payment\\/pp_standard","extension\\/payment\\/realex","extension\\/payment\\/realex_remote","extension\\/payment\\/sagepay_direct","extension\\/payment\\/sagepay_server","extension\\/payment\\/sagepay_us","extension\\/payment\\/securetrading_pp","extension\\/payment\\/securetrading_ws","extension\\/payment\\/skrill","extension\\/payment\\/twocheckout","extension\\/payment\\/web_payment_software","extension\\/payment\\/worldpay","extension\\/module\\/pp_braintree_button","extension\\/payment\\/pp_braintree","extension\\/report\\/customer_activity","extension\\/report\\/customer_order","extension\\/report\\/customer_reward","extension\\/report\\/customer_search","extension\\/report\\/customer_transaction","extension\\/report\\/marketing","extension\\/report\\/product_purchased","extension\\/report\\/product_viewed","extension\\/report\\/sale_coupon","extension\\/report\\/sale_order","extension\\/report\\/sale_return","extension\\/report\\/sale_shipping","extension\\/report\\/sale_tax","extension\\/shipping\\/auspost","extension\\/shipping\\/citylink","extension\\/shipping\\/ec_ship","extension\\/shipping\\/fedex","extension\\/shipping\\/flat","extension\\/shipping\\/free","extension\\/shipping\\/item","extension\\/shipping\\/parcelforce_48","extension\\/shipping\\/pickup","extension\\/shipping\\/royal_mail","extension\\/shipping\\/ups","extension\\/shipping\\/usps","extension\\/shipping\\/weight","extension\\/theme\\/default","extension\\/total\\/coupon","extension\\/total\\/credit","extension\\/total\\/handling","extension\\/total\\/klarna_fee","extension\\/total\\/low_order_fee","extension\\/total\\/reward","extension\\/total\\/shipping","extension\\/total\\/sub_total","extension\\/total\\/tax","extension\\/total\\/total","extension\\/total\\/voucher","localisation\\/country","localisation\\/currency","localisation\\/geo_zone","localisation\\/language","localisation\\/length_class","localisation\\/location","localisation\\/order_status","localisation\\/return_action","localisation\\/return_reason","localisation\\/return_status","localisation\\/stock_status","localisation\\/tax_class","localisation\\/tax_rate","localisation\\/weight_class","localisation\\/zone","mail\\/affiliate","mail\\/customer","mail\\/forgotten","mail\\/return","mail\\/reward","mail\\/transaction","marketing\\/contact","marketing\\/coupon","marketing\\/marketing","marketplace\\/api","marketplace\\/event","marketplace\\/cron","marketplace\\/extension","marketplace\\/install","marketplace\\/installer","marketplace\\/marketplace","marketplace\\/modification","marketplace\\/openbay","report\\/online","report\\/report","report\\/statistics","sale\\/order","sale\\/recurring","sale\\/return","sale\\/voucher","sale\\/voucher_theme","setting\\/setting","setting\\/store","startup\\/error","startup\\/event","startup\\/login","startup\\/permission","startup\\/router","startup\\/sass","startup\\/startup","tool\\/backup","tool\\/log","tool\\/upgrade","tool\\/upload","user\\/api","user\\/user","user\\/user_permission","extension\\/payment\\/mundipagg"],"modify":["catalog\\/attribute","catalog\\/attribute_group","catalog\\/category","catalog\\/download","catalog\\/filter","catalog\\/information","catalog\\/manufacturer","catalog\\/option","catalog\\/product","catalog\\/product_option","catalog\\/recurring","catalog\\/review","common\\/column_left","common\\/developer","common\\/filemanager","common\\/profile","common\\/security","customer\\/custom_field","customer\\/customer","customer\\/customer_approval","customer\\/customer_group","design\\/banner","design\\/layout","design\\/theme","design\\/translation","design\\/seo_url","event\\/statistics","event\\/theme","extension\\/analytics\\/google","extension\\/captcha\\/basic","extension\\/captcha\\/google","extension\\/dashboard\\/activity","extension\\/dashboard\\/chart","extension\\/dashboard\\/customer","extension\\/dashboard\\/map","extension\\/dashboard\\/online","extension\\/dashboard\\/order","extension\\/dashboard\\/recent","extension\\/dashboard\\/sale","extension\\/extension\\/analytics","extension\\/extension\\/captcha","extension\\/extension\\/currency","extension\\/extension\\/dashboard","extension\\/extension\\/feed","extension\\/extension\\/fraud","extension\\/extension\\/menu","extension\\/extension\\/module","extension\\/extension\\/payment","extension\\/extension\\/report","extension\\/extension\\/shipping","extension\\/extension\\/theme","extension\\/extension\\/total","extension\\/feed\\/google_base","extension\\/feed\\/google_sitemap","extension\\/feed\\/openbaypro","extension\\/fraud\\/fraudlabspro","extension\\/fraud\\/ip","extension\\/fraud\\/maxmind","extension\\/marketing\\/remarketing","extension\\/module\\/account","extension\\/module\\/amazon_login","extension\\/module\\/amazon_pay","extension\\/module\\/banner","extension\\/module\\/bestseller","extension\\/module\\/carousel","extension\\/module\\/category","extension\\/module\\/divido_calculator","extension\\/module\\/ebay_listing","extension\\/module\\/featured","extension\\/module\\/filter","extension\\/module\\/google_hangouts","extension\\/module\\/html","extension\\/module\\/information","extension\\/module\\/klarna_checkout_module","extension\\/module\\/latest","extension\\/module\\/laybuy_layout","extension\\/module\\/pilibaba_button","extension\\/module\\/pp_button","extension\\/module\\/pp_login","extension\\/module\\/sagepay_direct_cards","extension\\/module\\/sagepay_server_cards","extension\\/module\\/slideshow","extension\\/module\\/special","extension\\/module\\/store","extension\\/openbay\\/amazon","extension\\/openbay\\/amazon_listing","extension\\/openbay\\/amazon_product","extension\\/openbay\\/amazonus","extension\\/openbay\\/amazonus_listing","extension\\/openbay\\/amazonus_product","extension\\/openbay\\/ebay","extension\\/openbay\\/ebay_profile","extension\\/openbay\\/ebay_template","extension\\/openbay\\/etsy","extension\\/openbay\\/etsy_product","extension\\/openbay\\/etsy_shipping","extension\\/openbay\\/etsy_shop","extension\\/openbay\\/fba","extension\\/payment\\/amazon_login_pay","extension\\/payment\\/authorizenet_aim","extension\\/payment\\/authorizenet_sim","extension\\/payment\\/bank_transfer","extension\\/payment\\/bluepay_hosted","extension\\/payment\\/bluepay_redirect","extension\\/payment\\/cardconnect","extension\\/payment\\/cardinity","extension\\/payment\\/cheque","extension\\/payment\\/cod","extension\\/payment\\/divido","extension\\/payment\\/eway","extension\\/payment\\/firstdata","extension\\/payment\\/firstdata_remote","extension\\/payment\\/free_checkout","extension\\/payment\\/g2apay","extension\\/payment\\/globalpay","extension\\/payment\\/globalpay_remote","extension\\/payment\\/klarna_account","extension\\/payment\\/klarna_checkout","extension\\/payment\\/klarna_invoice","extension\\/payment\\/laybuy","extension\\/payment\\/liqpay","extension\\/payment\\/nochex","extension\\/payment\\/paymate","extension\\/payment\\/paypoint","extension\\/payment\\/payza","extension\\/payment\\/perpetual_payments","extension\\/payment\\/pilibaba","extension\\/payment\\/pp_express","extension\\/payment\\/pp_payflow","extension\\/payment\\/pp_payflow_iframe","extension\\/payment\\/pp_pro","extension\\/payment\\/pp_pro_iframe","extension\\/payment\\/pp_standard","extension\\/payment\\/realex","extension\\/payment\\/realex_remote","extension\\/payment\\/sagepay_direct","extension\\/payment\\/sagepay_server","extension\\/payment\\/sagepay_us","extension\\/payment\\/securetrading_pp","extension\\/payment\\/securetrading_ws","extension\\/payment\\/skrill","extension\\/payment\\/twocheckout","extension\\/payment\\/web_payment_software","extension\\/payment\\/worldpay","extension\\/module\\/pp_braintree_button","extension\\/payment\\/pp_braintree","extension\\/report\\/customer_activity","extension\\/report\\/customer_order","extension\\/report\\/customer_reward","extension\\/report\\/customer_search","extension\\/report\\/customer_transaction","extension\\/report\\/marketing","extension\\/report\\/product_purchased","extension\\/report\\/product_viewed","extension\\/report\\/sale_coupon","extension\\/report\\/sale_order","extension\\/report\\/sale_return","extension\\/report\\/sale_shipping","extension\\/report\\/sale_tax","extension\\/shipping\\/auspost","extension\\/shipping\\/citylink","extension\\/shipping\\/ec_ship","extension\\/shipping\\/fedex","extension\\/shipping\\/flat","extension\\/shipping\\/free","extension\\/shipping\\/item","extension\\/shipping\\/parcelforce_48","extension\\/shipping\\/pickup","extension\\/shipping\\/royal_mail","extension\\/shipping\\/ups","extension\\/shipping\\/usps","extension\\/shipping\\/weight","extension\\/theme\\/default","extension\\/total\\/coupon","extension\\/total\\/credit","extension\\/total\\/handling","extension\\/total\\/klarna_fee","extension\\/total\\/low_order_fee","extension\\/total\\/reward","extension\\/total\\/shipping","extension\\/total\\/sub_total","extension\\/total\\/tax","extension\\/total\\/total","extension\\/total\\/voucher","localisation\\/country","localisation\\/currency","localisation\\/geo_zone","localisation\\/language","localisation\\/length_class","localisation\\/location","localisation\\/order_status","localisation\\/return_action","localisation\\/return_reason","localisation\\/return_status","localisation\\/stock_status","localisation\\/tax_class","localisation\\/tax_rate","localisation\\/weight_class","localisation\\/zone","mail\\/affiliate","mail\\/customer","mail\\/forgotten","mail\\/return","mail\\/reward","mail\\/transaction","marketing\\/contact","marketing\\/coupon","marketing\\/marketing","marketplace\\/event","marketplace\\/cron","marketplace\\/api","marketplace\\/extension","marketplace\\/install","marketplace\\/installer","marketplace\\/marketplace","marketplace\\/modification","marketplace\\/openbay","report\\/online","report\\/report","report\\/statistics","sale\\/order","sale\\/recurring","sale\\/return","sale\\/voucher","sale\\/voucher_theme","setting\\/setting","setting\\/store","startup\\/error","startup\\/event","startup\\/login","startup\\/permission","startup\\/router","startup\\/sass","startup\\/startup","tool\\/backup","tool\\/log","tool\\/upgrade","tool\\/upload","user\\/api","user\\/user","user\\/user_permission","extension\\/payment\\/mundipagg"]}'
 WHERE user_group_id = 1 AND name = 'Administrator';

-- adding a user;

INSERT INTO opencart.oc_customer (
customer_id,customer_group_id,store_id,language_id,firstname,lastname,email,
telephone,fax,password,salt,cart,wishlist,newsletter,address_id,custom_field,
ip,status,safe,token,code,date_added
) VALUES (
   1, 1, 0, 1, 'test', 'test', 'test@test.com', '21999999999', '',
   'eeca8a018ea874598c92cda1e5a4ff2fcf7718e8','WdnMvM47v',
   '', '', 0, 0, '', '::1', 1, 0, '', '', '2018-04-12 16:44:02'
);


INSERT INTO opencart.oc_address (
customer_id, firstname, lastname, company, address_1,
address_2, city, postcode, country_id, zone_id, custom_field
) VALUES (
1, 'test', 'test', 'company', 'addr1', 'addr2', 'city', '26000000',
30, 458, '{"1":"99","2":"complemento"}'
);
